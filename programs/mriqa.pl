#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB mriqa.pl
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
use Math::Derivative qw(Derivative1 Derivative2);
use List::Util qw(first max maxstr min minstr reduce shuffle sum);
require 'nidbroutines.pl';

#my %config = do 'config.pl';
our %cfg;
LoadConfig();

# database variables
our $db;

# script specific information
our $scriptname = "mriqa";
our $lockfileprefix = "mriqa";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 2;				# number of times this program can be run concurrently

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
	my $x = DoQA();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoQA ----------------------------------------
# ----------------------------------------------------------
sub DoQA {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my %dicomfiles;
	my $ret = 0;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	WriteLog("Connected to database");

	# check if this module should be running now or not
	if (!ModuleCheckIfActive($scriptname, $db)) {
		WriteLog("Not supposed to be running right now");
		return 0;
	}
	
	# update the start time
	ModuleDBCheckIn($scriptname, $db);

	# look through DB for all series that don't have an associated mr_qa row
	my $sqlstring = "SELECT a.mrseries_id FROM mr_series a LEFT JOIN mr_qa b ON a.mrseries_id = b.mrseries_id WHERE b.mrqa_id IS NULL and a.lastupdate < date_sub(now(), interval 3 minute) order by a.mrseries_id desc";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $mrseries_id = $row{'mrseries_id'};
			QA($mrseries_id);
		}
		WriteLog("Finished reconstructing data");
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
# --------- QA ---------------------------------------------
# ----------------------------------------------------------
sub QA() {
	my ($seriesid, $format) = @_;

	my $sqlstring;

	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	# find where the files are, build the directory path to the dicom data
	$sqlstring = "select a.series_num, a.is_derived, a.data_type, b.study_num, d.uid, e.project_costcenter 
	from mr_series a
	left join studies b on a.study_id = b.study_id
	left join enrollment c on b.enrollment_id = c.enrollment_id
	left join subjects d on c.subject_id = d.subject_id
	left join projects e on c.project_id = e.project_id
	where a.mrseries_id = $seriesid";
	print "$sqlstring\n";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my $numProcessed = 0;
		while (my %row = $result->fetchhash) {
			my $series_num = $row{'series_num'};
			my $study_num = $row{'study_num'};
			my $is_derived = $row{'is_derived'};
			my $uid = $row{'uid'};
			my $project_costcenter = $row{'project_costcenter'};
			my $datatype = $row{'data_type'};
			my $mrqaid;
			
			# check if this mr_qa row exists
			my $sqlstringA = "select * from mr_qa where mrseries_id = $seriesid";
			my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
			if ($resultA->numrows > 0) {
				# if a row does exist, go onto the next row
				next;
			}
			else {
				# insert a blank row for this mr_qa and get the row ID
				my $sqlstringB = "insert into mr_qa (mrseries_id) values ($seriesid)";
				WriteLog("[$sqlstring]");
				my $resultB = $db->query($sqlstringB) || SQLError($db->errmsg(),$sqlstringB);
				$mrqaid = $resultB->insertid;
			}
			
			my $starttime = GetTotalCPUTime();
			
			my $indir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/$datatype";
			WriteLog("Working on [$indir]");
			my $systemstring;
			chdir($indir);

			# unfortunately, for now, this tmpdir must match the tmpdir in the nii_qa.sh script
			my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
			mkpath($tmpdir, {mode => 0777});
			mkpath("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa", {mode => 0777});

			if (($is_derived) || ($datatype ne 'dicom')) {
				$systemstring = "cp $cfg{'archivedir'}/$uid/$study_num/$series_num/nifti/* $tmpdir";
				WriteLog("$systemstring (" . `$systemstring` . ")");
			}
			else {
				# create a 4D file to pass to the SNR program and run the SNR program on it
				$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y -g y -p n -i n -d n -f n -o '$tmpdir' *.dcm";
				WriteLog("$systemstring (" . `$systemstring` . ")");
				
				$systemstring = "mv $tmpdir/*.nii.gz $tmpdir/4D.nii.gz";
				WriteLog("$systemstring (" . `$systemstring` . ")");
			}
			
			# create a 4D file to pass to the SNR program and run the SNR program on it
			$systemstring = "$cfg{'scriptdir'}/./nii_qa.sh -i $tmpdir/*.nii* -o $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa.txt -v 2 -t $tmpdir";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			
			# move the realignment file(s) to the archive directory
			$systemstring = "mv $tmpdir/*.par $cfg{'archivedir'}/$uid/$study_num/$series_num/qa";
			WriteLog("$systemstring (" . `$systemstring` . ")");

			# rename the realignment file to something meaningful
			$systemstring = "mv $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/*.par $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			
			# move and rename the mean,sigma,variance volumes to the archive directory
			$systemstring = "mv $tmpdir/*mcvol_meanvol.nii.gz $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.nii.gz";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "mv $tmpdir/*mcvol_sigma.nii.gz $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.nii.gz";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "mv $tmpdir/*mcvol_variance.nii.gz $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.nii.gz";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "mv $tmpdir/*mcvol.nii.gz $tmpdir/mc4D.nii.gz";
			WriteLog("$systemstring (" . `$systemstring` . ")");

			# get min/max intensity in the mean/variance/stdev volumes
			$systemstring = "fslstats $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.nii.gz -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxMean.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "fslstats $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.nii.gz -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxSigma.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "fslstats $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.nii.gz -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxVariance.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");

			# create thumbnails
			$systemstring = "slicer $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.png";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "slicer $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.png";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "slicer $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.png";
			WriteLog("$systemstring (" . `$systemstring` . ")");

			# get mean/stdev in intensity over time
			$systemstring = "fslstats -t $tmpdir/mc4D -m > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "fslstats -t $tmpdir/mc4D -s > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "fslstats -t $tmpdir/mc4D -e > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/entropyOverTime.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "fslstats -t $tmpdir/mc4D -c > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeMM.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "fslstats -t $tmpdir/mc4D -C > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeVox.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "fslstats -t $tmpdir/mc4D -h 100 > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/histogramOverTime.txt";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			
			# parse the QA output file
			my ($pvsnr,$iosnr) = GetQAStats("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa.txt");
			
			# parse the movement correction file
			my ($maxrx,$maxry,$maxrz,$maxtx,$maxty,$maxtz,$maxax,$maxay,$maxaz,$minrx,$minry,$minrz,$mintx,$minty,$mintz,$minax,$minay,$minaz) = GetMovementStats("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt");

			# delete the 4D file and temp directory
			$systemstring = "rm $tmpdir/*";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			rmdir($tmpdir);
			
			# make a color mapped version of the thumbnail
			$systemstring = "convert -version";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			my $thumbpath = "$cfg{'archivedir'}/$uid/$study_num/$series_num/thumb.png";
			my $gradientfile = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/gradient.png";
			my $thumblut = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/thumb_lut.png";
			chdir("$cfg{'archivedir'}/$uid/$study_num/$series_num");
			$systemstring = "convert -size 1x30 gradient:black-red gradient:red-yellow gradient:yellow-white -append $gradientfile";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			$systemstring = "convert $thumbpath $gradientfile -clut $thumblut";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			# make an fft of the thumbnail
			my $fftthumb = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/thumb_fft.png";
			my $fftthumb0 = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/thumb_fft-0.png";
			$systemstring = "convert $thumbpath -fft -delete 1 -auto-level -evaluate log 10000 $fftthumb";
			WriteLog("$systemstring (" . `$systemstring` . ")");
			
			# get a 1D plot from the 2D FFT
			#my $macrofile = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/macro.ijm";
			#my $plotfile = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/thumb_fft_1d.png";
			#my $plotfiletxt = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/thumb_fft_1d.txt";
			# get original fft_thumb size
			#$systemstring = "identify $fftthumb";
			#my $output = `$systemstring`;
			#my @parts = split(" ", $output);
			#my ($width, $height) = split("x", $parts[2]);
			
			#open(FILE,"> $macrofile") or die ("Could not open $macrofile!");
			#print FILE "open(\"$fftthumb\");\n";
			#print FILE "run(\"Select All\");\n";
			#print FILE "run(\"Radial Profile\", \"x=" . $width/2 . " y=" . $height/2 . " radius=" . $width/2 . "\");\n";
			#print FILE "selectWindow(\"Radial Profile Plot\");\n";
			#print FILE "saveAs(\"PNG\",\"$plotfile\");\n";
			#print FILE "Plot.getValues(x,y);\n";
			#print FILE "for (i=0;i<x.length;i++)\n";
			#print FILE "     print(x[i],y[i]);\n";
			#print FILE "saveAs(\"Text\",\"$plotfiletxt\");\n";
			#print FILE "run(\"Text...\",\"save=$plotfiletxt\");\n";
			#print FILE "run(\"Quit\");\n";
			#close(FILE);
			#$systemstring = "java -jar $cfg{'scriptdir'}/ImageJ/ij.jar ij.ImageJ -ijpath $cfg{'scriptdir'}/ImageJ/plugins -batch $macrofile";
			#my $plotnumbers = `$systemstring`;
			#open PLOTFILE, ("> $plotfiletxt");
			#print PLOTFILE $plotnumbers;
			#close PLOTFILE;
			#unlink($macrofile);
			
			# run the motion detection program (for 3D volumes only)
			my $motion_rsq;
			$systemstring = "fslval $tmpdir/4D.nii.gz dim4";
			my $dim4 = trim(`$systemstring`);
			if ($dim4 == 1) {
				$systemstring = "python $cfg{'scriptdir'}/StructuralMRIQA.py $cfg{'archivedir'}/$uid/$study_num/$series_num";
				WriteLog("Running structural motion calculation [$systemstring]");
				$motion_rsq = trim(`$systemstring`);
			}
			else {
				$motion_rsq = 0;
			}

			# calculate the total time running
			my $endtime = GetTotalCPUTime();
			my $cputime = $endtime - $starttime;
				
			# insert this row into the DB
			my $sqlstringC = "update mr_qa set mrseries_id = $seriesid, io_snr = '$iosnr', pv_snr = '$pvsnr', move_minx = '$mintx', move_miny = '$minty', move_minz = '$mintz', move_maxx = '$maxtx', move_maxy = '$maxty', move_maxz = '$maxtz', acc_minx = '$minax', acc_miny = '$minay', acc_minz = '$minaz', acc_maxx = '$maxax', acc_maxy = '$maxay', acc_maxz = '$maxaz', rot_minp = '$minrx', rot_minr = '$minry', rot_miny = '$minrz', rot_maxp = '$maxrx', rot_maxr = '$maxry', rot_maxy = '$maxrz', motion_rsq = '$motion_rsq', cputime = $cputime where mrqa_id = $mrqaid";
			WriteLog("[$sqlstringC]");
			my $resultC = $db->query($sqlstringC) || SQLError($db->errmsg(),$sqlstringC);
			
			# only process 5 before exiting the script. Since the script always starts with the newest when it first runs,
			# this will allow newly collect studies a chance to be QC'd if there is a backlog of old studies
			$numProcessed = $numProcessed + 1;
			if ($numProcessed > 5) {
				last;
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
		WriteLog("Could not find $filepath");
		return (0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0);
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
	my @accx = Derivative(@trax);
	my @accy = Derivative(@tray);
	my @accz = Derivative(@traz);
	
	my $accfile = $filepath;
	$accfile =~ s/MotionCorrection/MotionCorrection2/g;
	
	open(AFILE, "> $accfile");
	print AFILE join(',',@accx) . "\n";
	print AFILE join(',',@accy) . "\n";
	print AFILE join(',',@accz);
	close AFILE;
	
	return (max(@rotx),max(@roty),max(@rotz),max(@trax),max(@tray),max(@traz),max(@accx),max(@accy),max(@accz),  min(@rotx),min(@roty),min(@rotz),min(@trax),min(@tray),min(@traz),min(@accx),min(@accy),min(@accz) );
}


# ----------------------------------------------------------
# --------- Derivative -------------------------------------
# ----------------------------------------------------------
sub Derivative() {
	my (@array) = @_;
	
	my @outarr;
	
	foreach my $i(1..$#array-1) {
		push @outarr,(($array[$i]-$array[$i-1]) + 0.0);
	}
	
	return @outarr;
}