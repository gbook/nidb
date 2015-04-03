#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB mristudyqa.pl
# Copyright (C) 2004 - 2015
# Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
# Olin Neuropsychiatry Research Center, Hartford Hospital
# ------------------------------------------------------------------------------
# GPLv3 License:
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
# ------------------------------------------------------------------------------

# -----------------------------------------------------------------------------
# This program reads from the incoming directory, populates the database, and
# moves the dicom files to their archive location
# 
# [4/27/2011] - Greg Book
#		* Wrote initial program.
# -----------------------------------------------------------------------------

use strict;
use warnings;
use Mysql;
use Image::ExifTool;
use Net::SMTP::TLS;
use Data::Dumper;
use File::Path;
use File::Copy;
use Switch;
use Sort::Naturally;
use List::Util qw(first max maxstr min minstr reduce shuffle sum);
use Math::MatrixReal;
use Math::Combinatorics;
require 'nidbroutines.pl';

#my %config = do 'config.pl';
our %cfg;
LoadConfig();

# database variables
our $db;

# script specific information
our $scriptname = "mristudyqa";
our $lockfileprefix = "mristudyqa";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently

# debugging
our $debug = 0;

# ------------- end variable declaration --------------------------------------
# -----------------------------------------------------------------------------


# check if this program can run or not
if (CheckNumLockFiles($lockfileprefix, $cfg{'lockdir'}) >= $numinstances) {
	print "Can't run, too many of me already running\n";
	exit(0);
}
else {
	my $logfilename;
	($lockfile, $logfilename) = CreateLockFile($lockfileprefix, $cfg{'lockdir'}, $numinstances);
	#my $logfilename = "$lockfile";
	$logfilename = "$cfg{'logdir'}/$scriptname" . CreateLogDate() . ".log";
	open $log, '> ', $logfilename;
	my $x = DoStudyQA();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoStudyQA --------------------------------------
# ----------------------------------------------------------
sub DoStudyQA {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my %dicomfiles;
	my $ret = 0;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	WriteLog("Connected to database");

	# check if this module should be running now or not
	if (!ModuleCheckIfActive($scriptname, $db)) { return 0; }
	
	# update the start time
	ModuleDBCheckIn($scriptname, $db);

	# look through DB for all studies that don't have an associated mr_studyqa row
	my $sqlstring = "SELECT a.study_id FROM studies a LEFT JOIN mr_studyqa b ON a.study_id = b.study_id WHERE a.study_modality = 'MR' and b.mrstudyqa_id IS NULL";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $study_id = $row{'study_id'};
			#print "$study_id\n";
			StudyQA($study_id);
		}
		WriteLog("Finished study QA");
		$ret = 1;
	}
	else {
		WriteLog("Nothing to do");
	}
	
	# update the stop time
	ModuleDBCheckOut($scriptname, $db);

	return $ret;
}


# ----------------------------------------------------------
# --------- StudyQA ----------------------------------------
# ----------------------------------------------------------
sub StudyQA() {
	my ($studyid, $format) = @_;

	my $sqlstring;
	my $starttime = GetTotalCPUTime();
	my $valid = 1;

	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");

	# do the MP Rage (T1) qa first
	# find where the files are, build the directory path to the dicom data
	$sqlstring = "select a.series_num, a.series_sequencename, b.study_num, d.uid 
	from mr_series a
	left join studies b on a.study_id = b.study_id
	left join enrollment c on b.enrollment_id = c.enrollment_id
	left join subjects d on c.subject_id = d.subject_id
	left join projects e on c.project_id = e.project_id
	where a.study_id = $studyid and series_sequencename like '%tfl3d1%' and b.study_datetime < date_add(now(), interval -1 day) and d.uid is not null and d.isactive = 1 order by a.series_num";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	
	WriteLog($sqlstring);
	
	my $t1_comparedseriesids = "";
	my $t1_derivedseriesid = 0;
	my $t1_comparisonmatrix = "";
	my $t1_matrixremovethreshold = 0.0;
	my $t1_snrremovethreshold = 0.0;
	my @t1images;
	my $numcompimages = 0;
	my $systemstring;
	
	# only perform the MPRage QA if there is more than 1 MPRage in the study
	if ($result->numrows > 1) {

		my $workdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
		mkpath($workdir, {mode => 0777});
		
		my $i = 0;
		my $series_num;
		my $study_num;
		my $uid;
		# convert the SNR surviving images to nifti
		while (my %row = $result->fetchhash) {
			$series_num = $row{'series_num'};
			$study_num = $row{'study_num'};
			$uid = $row{'uid'};
			
			#if ($uid == NULL) {
			#	next;
			#}
			
			my $indir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/dicom";
			WriteLog("Working on [$indir]");
			my $systemstring;
			chdir($indir);

			my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
			mkpath($tmpdir, {mode => 0777});

			# create a 3D file to pass to the registration program
			$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_3D.ini' -a y -e y -g y -p n -i n -d n -f n -o '$tmpdir' *.dcm";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			
			# change the filename to something meaningful and move it to the workdir
			chdir($tmpdir) || die("Cannot open directory $tmpdir!\n");
			my @niifiles = <*.nii.gz>;
			if (($#niifiles + 1) > 1) {
				WriteLog("Found more than 1 .nii file, where there should only be 1");
				last;
			}
			my $oldfile = $niifiles[0];
			my $newfile = "T1$i" . ".nii.gz";
			WriteLog("$oldfile => $newfile");
			WriteLog(`mv $tmpdir/$oldfile $workdir/$newfile`);
			
			print "Converted .nii file: $workdir/$newfile\n";
			$t1images[$i]{'series'} = $series_num;
			$t1images[$i]{'path'} = "$workdir/$newfile";
			
			# delete the tmp file
			$systemstring = "rm $tmpdir/*; rmdir $tmpdir";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$i++;
		}

		# now perform the different image comparison methods
		my ($derivedpath1, $summary) = RegisterSimple($workdir, \@t1images, 1, "meanT1");
		# connect to the database (it may have timed out during the calculation above)
		$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
		WriteLog("Connected to database");
		InsertSeries($studyid, $derivedpath1, $uid, $study_num, "Derived T1 - Simple average", 'derivedT1');
		
		#my ($derivedpath2, @series) = RegisterCombinations($workdir, \@t1images);
		# connect to the database (it may have timed out during the calculation above)
		#$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
		#WriteLog("Connected to database");
		#InsertSeries($studyid, $derivedpath2, $uid, $study_num, "Derived T1 - Combinations [@series]", 'derivedT1');
		
		# delete the working directory
		$systemstring = "rm $workdir/*; rmdir $workdir";
		WriteLog("$systemstring (" . `$systemstring` . ")");
	}
	my $endtime = GetTotalCPUTime();
	my $cputime = $endtime - $starttime;
	
	# connect to the database (it may have timed out during the calculation above)
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	WriteLog("Connected to database");
	
	# insert this row into the studyqa table
	$sqlstring = "insert into mr_studyqa (study_id, t1_numcompared, t1_comparedseriesids, t1_derivedseriesid, t1_comparisonmatrix, t1_matrixremovethreshold, t1_snrremovethreshold, cputime) values ($studyid, $numcompimages, '$t1_comparedseriesids', '$t1_derivedseriesid', '$t1_comparisonmatrix', '$t1_matrixremovethreshold', '$t1_snrremovethreshold', $cputime)";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
}


# ----------------------------------------------------------
# --------- InsertSeries -----------------------------------
# ----------------------------------------------------------
sub InsertSeries() {
	my ($studyid, $derivedpath, $uid, $study_num, $seriesdesc, $sequencename) = @_;

	# get new series number
	my $newseriesnum;
	my $sqlstring = "select max(series_num) 'max_series' from mr_series where study_id = $studyid";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	my %row = $result->fetchhash;
	my $maxseries = $row{'max_series'};
	if ($maxseries >= 5000) {
		$newseriesnum = $maxseries + 1;
	}
	else {
		$newseriesnum = 5000;
	}
	
	# get image dimensions
	my $xdim = trim(`fslval $derivedpath dim1`);
	my $ydim = trim(`fslval $derivedpath dim2`);
	my $zdim = trim(`fslval $derivedpath dim3`);
	my $xpix = trim(`fslval $derivedpath pixdim1`);
	my $ypix = trim(`fslval $derivedpath pixdim2`);
	my $zpix = trim(`fslval $derivedpath pixdim3`);
	my $filesize = -s $derivedpath;
	
	# check for invalid return values
	if ($xdim eq "Usage: fslval <input> <keyword>") { $xdim = "-1"; }
	if ($ydim eq "Usage: fslval <input> <keyword>") { $ydim = "-1"; }
	if ($zdim eq "Usage: fslval <input> <keyword>") { $zdim = "-1"; }
	if ($xpix eq "Usage: fslval <input> <keyword>") { $xpix = "-1"; }
	if ($ypix eq "Usage: fslval <input> <keyword>") { $ypix = "-1"; }
	if ($zpix eq "Usage: fslval <input> <keyword>") { $zpix = "-1"; }
	if ($filesize eq "") { $filesize = "0"; }
	
	# move the derived file to the ado2/archive location
	my $outdir = "$cfg{'archivedir'}/$uid/$study_num/$newseriesnum/nifti";
	WriteLog("$outdir");
	mkpath($outdir, {mode => 0777});
	my $systemstring = "mv $derivedpath $outdir";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	
	# create a thumbnail
	$systemstring = "slicer $outdir/*.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$newseriesnum/thumb.png";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	
	# insert a new series into the mr_series table
	$sqlstring = "insert into mr_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols, img_slices, numfiles, series_size, data_type, is_derived, series_status) values ($studyid, now(), '$seriesdesc', '$sequencename', '$newseriesnum', '$xpix', '$ypix', '$zpix', '$xdim', '$ydim', '$zdim', '1', '$filesize', 'nifti', 1, 'complete')";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
}


# ----------------------------------------------------------
# --------- RegisterCombinations ---------------------------
# ----------------------------------------------------------
# This function will calculate the SNR on all possible
# combinations of T1 images, except the combination of
# registering all images together.
# If there are 4 images, it will try the following
# combinations: [1] [2] [3] [4] [12] [13] [14] [23] [24]
# [34] [123] [124] [134] [234]
# but will not compute [1234] because that one was is done
# in the RegisterSimple function
# ----------------------------------------------------------
sub RegisterCombinations() {
	my ($workdir, $t1s, $refvol) = @_;

	my $summary;
	my $systemstring;
	my @t1images = @{$t1s};
	my $numcompimages = $#t1images+1;
	my $finalpath;
	my @finalseries;
	my $maxsnr = 0.0;
	
	my $n = $#t1images+1;
	my $combonum = 0;
	foreach my $r (1..$n-1) {
		# choose r images from n
		WriteLog("Trying $n choose $r:");
		my $comb = Math::Combinatorics->new(count => $r, data => [@t1images]);
		while (my @combo = $comb->next_combination) {
			#my @tmparray = @combo;
			
			my ($derivedpath, $summary);
			if ($r > 1) {
				WriteLog("Combining: [" . join(", ", @combo) . "]");
				($derivedpath, $summary) = RegisterSimple($workdir, \@combo, 1, "meanT1_$combonum");
			}
			else {
				$derivedpath = $combo[0]{'path'};
			}
			
			# print information about this combination
			my @comboseries;
			foreach my $i (0..$#combo) {
				push @comboseries,$combo[$i]{'series'};
			}
			WriteLog("Combination $combonum: [" . join(", ", @comboseries) . "]");


			# perform qa on this new mean image

			
			# create a 4D file to pass to the SNR program and run the SNR program on it
			$systemstring = "$cfg{'scriptdir'}/./nii_qa.sh -i $derivedpath -o $workdir/qa.txt -v 2";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			
			# parse the QA output file
			my ($pvsnr,$iosnr) = GetQAStats("$workdir/qa.txt");
			
			WriteLog("SNR for [@comboseries] = $iosnr");
			if ($iosnr > $maxsnr) {
				$finalpath = $derivedpath;
				@finalseries = @comboseries;
			}
			
			unlink("$workdir/qa.txt");
			
			$combonum++;
		}
	}
	
	return ($finalpath, @finalseries);
}


# ----------------------------------------------------------
# --------- RegisterSimple ---------------------------------
# ----------------------------------------------------------
sub RegisterSimple() {
	my ($workdir, $t1s, $refvol, $meanimagename) = @_;

	my $summary;
	my $systemstring;
	my @t1images = @{$t1s};
	my $numcompimages = $#t1images+1;
	
	print "Dump! " . Dumper(@t1images);
	
	print "Beginning register of $numcompimages total images\n";

	$summary = "Beginning register of $numcompimages total images\n";
	
	# run flirt to register each volume to the ref
	foreach my $i (1..$#t1images) {
		my $inimg = $t1images[$i]{'path'};
		my $refimg = $t1images[0]{'path'};
		print "Registering $inimg [$t1images[$i]{'series'}] to $refimg [$t1images[0]{'series'}]\n";
		#next;
		
		$systemstring = "flirt -in $inimg -ref $refimg -omat $workdir/transform_$i.txt -o $workdir/realigned$i.nii.gz";
		print "$systemstring (" . `$systemstring` . ")";

		open(FILE,"$workdir/transform_$i.txt") or die ("Could not open $workdir/transform_$i.txt!");
		my @f = <FILE>;
		close(FILE);
		
		#print "Transformation matrix:\n @f\n";
		print "Done registering $inimg [$t1images[$i]{'series'}] to $refimg [$t1images[0]{'series'}]\n";
		$summary .= "Transformation matrix:\n";
		$summary .= @f;
	}
	
	# concatenate the images into 1 4D volume
	$systemstring = "fslmerge -t $workdir/4DT1.nii.gz $t1images[0]{'path'} ";
	foreach my $i (1..$#t1images) {
		$systemstring .= "$workdir/realigned$i.nii.gz ";
	}
	print "[$systemstring]\n";
	$summary .= "$systemstring (" . `$systemstring` . ")";
	
	# get the mean image
	$systemstring = "fslmaths $workdir/4DT1.nii.gz -Tmean $workdir/$meanimagename.nii.gz";
	print "[$systemstring]\n";
	$summary .= "$systemstring (" . `$systemstring` . ")";

	my $derivedimage = "$workdir/$meanimagename.nii.gz";
	return ($derivedimage, $summary);
}


# ----------------------------------------------------------
# --------- RegisterWithCensor -----------------------------
# ----------------------------------------------------------
sub RegisterWithCensor() {
	my (@t1images,$x,$y,$z,$pi,$ro,$ya) = @_;

	my $numcompimages = $#t1images+1;
}


# ----------------------------------------------------------
# --------- RegisterComplex --------------------------------
# ----------------------------------------------------------
sub RegisterComplex() {
	my ($workdir, @t1images) = @_;

	my $numcompimages = $#t1images+1;
	
	# create A x A comparison matrix, fill with -1 (could there be a negative correlation, eh?)
	my @compmat1;
	my @compmat2;
	foreach my $x (0..$#t1images) {
		foreach my $y (0..$#t1images) {
			$compmat1[$x][$y] = -1;
			$compmat2[$x][$y] = -1;
		}
	}
	print "Finished preparing the comparison matrix\n";
	#print Dumper(@compmat);
	
	my $systemstring;
	my $totaldet = 0.0;
	my $totaldisp = 0.0;
	my $numcomparisons = 0;
	# now do the realignment for all image pairs. realign 1 --> 4 should be the same as 4 --> 1, so only do them once
	foreach my $x (0..$#t1images) {
		foreach my $y (0..$#t1images) {
			# only do an alignment if the images are not the same, if it hasn't already been done, and if the inverse hasn't already been done
			if (($x != $y) && ($compmat1[$x][$y] == -1) && ($compmat1[$y][$x] == -1)) {
			
				# do alignment and get the rms and place rms back into the matrix
				$compmat1[$x][$y] = 0.0;
				$compmat2[$x][$y] = 0.0;
				
				my $im1 = $t1images[$x];
				my $im2 = $t1images[$y];
				
				$systemstring = "flirt -in $im2 -ref $im1 -omat $workdir/transform_$x" . "_$y.txt";
				WriteLog("$systemstring (" . `$systemstring` . ")");
				
				# compute determinant of the transformation matrix
				open(FILE,"$workdir/transform_$x" . "_$y.txt") or die ("Could not open $workdir/transform_$x" . "_$y.txt!");
				my @f = <FILE>;
				close(FILE);
				
				my @r0 = split(/\s+/,$f[0]);
				my @r1 = split(/\s+/,$f[1]);
				my @r2 = split(/\s+/,$f[2]);
				my @r3 = split(/\s+/,$f[3]);
				my $matrix = Math::MatrixReal->new_from_rows([ [$r0[0],$r0[1],$r0[2],$r0[3]], [$r1[0],$r1[1],$r1[2],$r1[3]], [$r2[0],$r2[1],$r2[2],$r2[3]], [$r3[0],$r3[1],$r3[2],$r3[3]] ]);
				print $matrix;
				my $det = $matrix->det();
				
				my $xe = $matrix->element(1,4);
				my $ye = $matrix->element(2,4);
				my $ze = $matrix->element(3,4);
				my $displacement = sqrt($xe*$xe + $ye*$ye + $ze*$ze);
				
				print "[$det,$displacement]\n";
				$compmat1[$x][$y] = $det;
				$compmat2[$x][$y] = $displacement;
				
				$totaldet += $det;
				$totaldisp += $displacement;
				$numcomparisons++;
			}
		}
	}
	print "Finished with the comparison\n";
	print Dumper(@compmat1);
	print Dumper(@compmat2);
	
	# calculate mean determinant and displacement
	my $meandet = $totaldet/$numcomparisons;
	my $meandisp = $totaldisp/$numcomparisons;

	# find the %difference for each image to the mean
	foreach my $x (0..$#t1images) {
		foreach my $y (0..$#t1images) {
			# only do an alignment if the images are not the same, if it hasn't already been done, and if the inverse hasn't already been done
			if (($x != $y) && ($compmat1[$x][$y] == -1) && ($compmat1[$y][$x] == -1)) {
			
				# do alignment and get the rms and place rms back into the matrix
				$compmat1[$x][$y] = 0.0;
				$compmat2[$x][$y] = 0.0;
			}
		}
	}
}


# ----------------------------------------------------------
# --------- GetQAStats -------------------------------------
# ----------------------------------------------------------
sub GetQAStats() {
	my ($filepath) = @_;

	WriteLog("Opening SNR file: $filepath");
	if (!-e $filepath) {
		return (-1,-1);
	}
	open(FILE,$filepath) or die ("Could not open $filepath!");
	my @filecontents = <FILE>;
	close(FILE);

	#WriteLog(@filecontents);
	
	#my ($pvsnr,$iosnr);
	
	my $line = $filecontents[1];
	#WriteLog($line);
	$line =~ s/\n//g;
	my ($fname, $pvsnr, $iosnr) = split(/\t/, trim($line));
	
	if (trim($pvsnr) eq "") { $pvsnr = 0.0; }
	if (trim($iosnr) eq "") { $pvsnr = 0.0; }
	
	return ($pvsnr,$iosnr);
}


# ----------------------------------------------------------
# --------- GetMovementStats -------------------------------
# ----------------------------------------------------------
sub GetMovementStats() {
	my ($filepath) = @_;
	
	WriteLog("Opening realignment file: $filepath");
	if (!-e $filepath) {
		return (0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0);
	}
	open(FILE,$filepath) or die ("Could not open $filepath!");
	my @filecontents = <FILE>;
	close(FILE);

	#WriteLog(@filecontents);
	
	my (@rotx,@roty,@rotz,@trax,@tray,@traz);

	# rearrange the text file columns into arrays to pass to the max/min functions
	foreach my $line (@filecontents) {
		#WriteLog($line);
		$line =~ s/\n//g;
		my ($rx,$ry,$rz,$tx,$ty,$tz) = split(/\s+/, trim($line));
		#print "$rx,$ry,$rz,$tx,$ty,$tz\n";
		# add 0.0 to make sure its stored as a number
		push @rotx,($rx+0.0);
		push @roty,($ry+0.0);
		push @rotz,($rz+0.0);
		push @trax,($tx+0.0);
		push @tray,($ty+0.0);
		push @traz,($tz+0.0);
	}
	
	return (max(@rotx),max(@roty),max(@rotz),max(@trax),max(@tray),max(@traz), min(@rotx),min(@roty),min(@rotz),min(@trax),min(@tray),min(@traz) );
}
