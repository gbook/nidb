#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB MRGeneralStats.pl
# Copyright (C) 2004-2013
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
# This program performs basic MR quality control calculations
# 
# [7/25/2013] - Greg Book
#		* Wrote initial program.
# -----------------------------------------------------------------------------

use strict;
use warnings;
use Mysql;
use Image::ExifTool;
use Data::Dumper;
use File::Path;
use File::Copy;
use XML::Simple;
#use XML::Bare;
use Switch;
use Sort::Naturally;
use List::Util qw(first max maxstr min minstr reduce shuffle sum);
use Cwd;
require '../../nidbroutines.pl';

our %cfg;
LoadConfig();

our $db;
our $log;
our $moduleseriesid = $ARGV[0];

# ------------- end variable declaration --------------------------------------
# -----------------------------------------------------------------------------


# ----------------------------------------------------------
# --------- QC ---------------------------------------------
# ----------------------------------------------------------
sub QC() {
	my $sqlstring;

	if ($moduleseriesid eq "") {
		WriteLog("Blank moduleseriesid passed");
		return;
	}
	
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");

	$sqlstring = "select * from qc_moduleseries where qcmoduleseries_id = '$moduleseriesid'";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	my %row = $result->fetchhash;
	my $seriesid = $row{'series_id'};
	my $modality = $row{'modality'};
	
	if ($seriesid eq "") {
		WriteLog("Invalid moduleseriesid [$moduleseriesid]");
		return;
	}
	
	# find where the files are, build the directory path to the dicom data
	$sqlstring = "select a.series_num, a.is_derived, a.data_type, b.study_num, d.uid, e.project_costcenter 
	from mr_series a
	left join studies b on a.study_id = b.study_id
	left join enrollment c on b.enrollment_id = c.enrollment_id
	left join subjects d on c.subject_id = d.subject_id
	left join projects e on c.project_id = e.project_id
	where a.mrseries_id = $seriesid and a.bold_reps > 1";
	print "$sqlstring\n";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
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
			
			my $starttime = GetTotalCPUTime();
			
			my $qadir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa";
			my $indir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/$datatype";
			print("Working on [$indir]\n");
			my $systemstring;
			chdir($indir);
			
			my $tmpdir = "/tmp/" . GenerateRandomString(10);
			mkpath($tmpdir, {error => \my $err1} );
			mkpath($qadir, {error => \my $err2} );

			if ($datatype eq "parrec") {
				chdir($indir); print `pwd`;
				
				# convert the dicoms to analyze (which also copies the Niftis to the tmpdir)
				$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y -g y -p n -i n -d n -f n -o '$tmpdir' *";
				print("$systemstring (" . `$systemstring 2>&1` . ")");
				
				# run analyze2bxh
				$systemstring = "/opt/bxh_xcede_tools/bin/./analyze2bxh --xcede $tmpdir/*.nii.gz $tmpdir/WRAPPED.xml";
				print("[$systemstring] (" . `$systemstring 2>&1` . ")\n");
			}
			else {
				# copy all the DICOM data to the tmpdir
				print "Copying DICOM files to [$tmpdir]\n";
				$systemstring = "cp -v $indir/*.dcm $tmpdir/";
				print("[$systemstring] (" . `$systemstring 2>&1` . ")\n");
				
				chdir($tmpdir);
				print `pwd`;
				# run dicom2bxh
				$systemstring = "/opt/bxh_xcede_tools/bin/./dicom2bxh --xcede $tmpdir/*.dcm $tmpdir/WRAPPED.xml";
				print("[$systemstring] (\n" . `$systemstring 2>&1` . "\n)\n");
			}
			
			# run the fmriqa_phantom.pl
			$systemstring = "perl /opt/bxh_xcede_tools/bin/fmriqa_phantomqa.pl --overwrite $tmpdir/WRAPPED.xml $qadir";
			print("[$systemstring] (" . `$systemstring 2>&1` . ")\n");
			
			# ----- check if the thumbnails exist -----
			chdir($qadir);
		
			if (-e "$qadir/qa_cmassx.png") { InsertQCImageFile("qa_cmassx.png",$moduleseriesid, 'qa_cmassx','image'); }
			if (-e "$qadir/qa_cmassy.png") { InsertQCImageFile("qa_cmassy.png",$moduleseriesid, 'qa_cmassy','image'); }
			if (-e "$qadir/qa_cmassz.png") { InsertQCImageFile("qa_cmassz.png",$moduleseriesid, 'qa_cmassz','image'); }
			if (-e "$qadir/qa_fwhmx.png") { InsertQCImageFile("qa_fwhmx.png",$moduleseriesid, 'qa_fwhmx','image'); }
			if (-e "$qadir/qa_fwhmy.png") { InsertQCImageFile("qa_fwhmy.png",$moduleseriesid, 'qa_fwhmy','image'); }
			if (-e "$qadir/qa_fwhmz.png") { InsertQCImageFile("qa_fwhmz.png",$moduleseriesid, 'qa_fwhmz','image'); }
			if (-e "$qadir/qa_ghost.png") { InsertQCImageFile("qa_ghost.png",$moduleseriesid, 'qa_ghost','image'); }
			if (-e "$qadir/qa_relstd.png") { InsertQCImageFile("qa_relstd.png",$moduleseriesid, 'qa_relstd','image'); }
			if (-e "$qadir/qa_signal.png") { InsertQCImageFile("qa_signal.png",$moduleseriesid, 'qa_signal','image'); }
			if (-e "$qadir/qa_spectrum.png") { InsertQCImageFile("qa_spectrum.png",$moduleseriesid, 'qa_spectrum','image'); }

			if (-e "$qadir/summaryQA.xml") {
				InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/summaryQA.xml", $moduleseriesid, 'fBIRN-QA-Summary','textfile','text','text');
				
				my $xs = XML::Simple->new();
				my $xml = $xs->XMLin("$qadir/summaryQA.xml");
				
				my $sliceorder = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{sliceorder}->{content}));
				my $mean = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{mean}->{content}));
				my $SNR = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{SNR}->{content}));
				my $SFNR = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{SFNR}->{content}));
				my $std = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{std}->{content}));
				my $percentFluc = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{percentFluc}->{content}));
				my $drift = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{drift}->{content}));
				my $driftfit = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{driftfit}->{content}));
				my $rdc = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{rdc}->{content}));
				my $minCMassX = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{minCMassX}->{content}));
				my $minCMassY = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{minCMassY}->{content}));
				my $minCMassZ = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{minCMassZ}->{content}));
				my $maxCMassX = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{maxCMassX}->{content}));
				my $maxCMassY = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{maxCMassY}->{content}));
				my $maxCMassZ = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{maxCMassZ}->{content}));
				my $meanCMassX = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{meanCMassX}->{content}));
				my $meanCMassY = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{meanCMassY}->{content}));
				my $meanCMassZ = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{meanCMassZ}->{content}));
				my $dispCMassX = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{dispCMassX}->{content}));
				my $dispCMassY = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{dispCMassY}->{content}));
				my $dispCMassZ = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{dispCMassZ}->{content}));
				my $driftCMassX = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{driftCMassX}->{content}));
				my $driftCMassY = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{driftCMassY}->{content}));
				my $driftCMassZ = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{driftCMassZ}->{content}));
				my $minFWHMX = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{minFWHMX}->{content}));
				my $minFWHMY = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{minFWHMY}->{content}));
				my $minFWHMZ = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{minFWHMZ}->{content}));
				my $maxFWHMX = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{maxFWHMX}->{content}));
				my $maxFWHMY = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{maxFWHMY}->{content}));
				my $maxFWHMZ = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{maxFWHMZ}->{content}));
				my $meanFWHMX = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{meanFWHMX}->{content}));
				my $meanFWHMY = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{meanFWHMY}->{content}));
				my $meanFWHMZ = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{meanFWHMZ}->{content}));
				my $meanGhost = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{meanGhost}->{content}));
				my $meanBrightGhost = EscapeMySQLString(trim($xml->{analysis}->{measurementGroup}->{observation}->{meanBrightGhost}->{content}));
				
				InsertQCResult($sliceorder, $moduleseriesid, 'sliceorder','sliceorder','');
				InsertQCResult($mean, $moduleseriesid, 'mean','intensity','');
				InsertQCResult($SNR, $moduleseriesid, 'SNR','SNR','');
				InsertQCResult($SFNR, $moduleseriesid, 'SFNR','SNR','');
				InsertQCResult($std, $moduleseriesid, 'std','intensity','');
				InsertQCResult($percentFluc, $moduleseriesid, 'percentFluc','intensity','');
				InsertQCResult($drift, $moduleseriesid, 'drift','intensity','');
				InsertQCResult($driftfit, $moduleseriesid, 'driftfit','intensity','');
				InsertQCResult($rdc, $moduleseriesid, 'rdc','voxels','');
				InsertQCResult($minCMassX, $moduleseriesid, 'minCMassX','voxels','');
				InsertQCResult($minCMassY, $moduleseriesid, 'minCMassY','voxels','');
				InsertQCResult($minCMassZ, $moduleseriesid, 'minCMassZ','voxels','');
				InsertQCResult($maxCMassX, $moduleseriesid, 'maxCMassX','voxels','');
				InsertQCResult($maxCMassY, $moduleseriesid, 'maxCMassY','voxels','');
				InsertQCResult($maxCMassZ, $moduleseriesid, 'maxCMassZ','voxels','');
				InsertQCResult($meanCMassX, $moduleseriesid, 'meanCMassX','voxels','');
				InsertQCResult($meanCMassY, $moduleseriesid, 'meanCMassY','voxels','');
				InsertQCResult($meanCMassZ, $moduleseriesid, 'meanCMassZ','voxels','');
				InsertQCResult($dispCMassX, $moduleseriesid, 'dispCMassX','voxels','');
				InsertQCResult($dispCMassY, $moduleseriesid, 'dispCMassY','voxels','');
				InsertQCResult($dispCMassZ, $moduleseriesid, 'dispCMassZ','voxels','');
				InsertQCResult($driftCMassX, $moduleseriesid, 'driftCMassX','voxels','');
				InsertQCResult($driftCMassY, $moduleseriesid, 'driftCMassY','voxels','');
				InsertQCResult($driftCMassZ, $moduleseriesid, 'driftCMassZ','voxels','');
				InsertQCResult($minFWHMX, $moduleseriesid, 'minFWHMX','voxels','');
				InsertQCResult($minFWHMY, $moduleseriesid, 'minFWHMY','voxels','');
				InsertQCResult($minFWHMZ, $moduleseriesid, 'minFWHMZ','voxels','');
				InsertQCResult($maxFWHMX, $moduleseriesid, 'maxFWHMX','voxels','');
				InsertQCResult($maxFWHMY, $moduleseriesid, 'maxFWHMY','voxels','');
				InsertQCResult($maxFWHMZ, $moduleseriesid, 'maxFWHMZ','voxels','');
				InsertQCResult($meanFWHMX, $moduleseriesid, 'meanFWHMX','voxels','');
				InsertQCResult($meanFWHMY, $moduleseriesid, 'meanFWHMY','voxels','');
				InsertQCResult($meanFWHMZ, $moduleseriesid, 'meanFWHMZ','voxels','');
				InsertQCResult($meanGhost, $moduleseriesid, 'meanGhost','voxels','');
				InsertQCResult($meanBrightGhost, $moduleseriesid, 'meanBrightGhost','voxels','');
			}

			# delete the 4D file and temp directory
			if (trim($tmpdir) ne "") {
				$systemstring = "rm -rfv $tmpdir";
				print("$systemstring (" . `$systemstring 2>&1` . ")\n");
				rmdir($tmpdir);
			}
			
			# calculate the total time running
			my $endtime = GetTotalCPUTime();
			my $cputime = $endtime - $starttime;
				
			# only process 10 before exiting the script. Since the script always starts with the newest when it first runs,
			# this will allow newly collect studies a chance to be QC'd if there is a backlog of old studies
			$numProcessed = $numProcessed + 1;
			#if ($numProcessed > 5) {
			#	last;
			#}
		}
	}
	else {
		print "Nothing to do. No series matched the SQL statement\n";
	}
}




# ----------------------------------------------------------
# --------- InsertQCResultFile -----------------------------
# ----------------------------------------------------------
sub InsertQCResultFile() {
	my ($filename, $moduleseriesid, $resultname, $resulttype, $units, $labels) = @_;
	
	if (-e $filename) {
		my $text;
		open(F,$filename) || print "Could not open [$filename]! In MRAdvancedStatsPhantom.pl\n";
		while(<F>) {
			$text .= $_;
		}
		close(F);
		
		#print "Reading file [$filename]: $text\n";
		$text = EscapeMySQLString($text);
		my $resultnameid = InsertQCResultName($resultname, $resulttype, $units, $labels);
		my $sqlstringA = "insert ignore into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuetext, qcresults_datetime) values ($moduleseriesid, $resultnameid, compress('$text'), now())";
		#print("[$sqlstringA]\n");
		my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
	}
	else { print "[$filename] not found\n"; }
}


# ----------------------------------------------------------
# --------- InsertQCImageFile ------------------------------
# ----------------------------------------------------------
sub InsertQCImageFile() {
	my ($filename, $moduleseriesid, $resultname) = @_;
	
	my $resultnameid = InsertQCResultName($resultname,'image','');
	my $sqlstringA = "insert ignore into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuefile, qcresults_datetime) values ($moduleseriesid, $resultnameid, '$filename', now())";
	print("[$sqlstringA]\n");
	my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
}


# ----------------------------------------------------------
# --------- InsertQCResult ---------------------------------
# ----------------------------------------------------------
sub InsertQCResult() {
	my ($value, $moduleseriesid, $resultname, $units, $labels) = @_;
	
	my $resultnameid = InsertQCResultName($resultname, 'number', $units, $labels);
	my $sqlstringA = "insert ignore into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuenumber, qcresults_datetime) values ($moduleseriesid, $resultnameid, '$value', now())";
	print("[$sqlstringA]\n");
	my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
}


# ----------------------------------------------------------
# --------- InsertQCResultName -----------------------------
# ----------------------------------------------------------
sub InsertQCResultName() {
	my ($name, $type, $units, $labels) = @_;
	
	my $id;
	$name = EscapeMySQLString($name);
	$units = EscapeMySQLString($units);
	$labels = EscapeMySQLString($labels);
	
	my $sqlstring = "select qcresultname_id from qc_resultnames where qcresult_name = '$name'";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$id = $row{'qcresultname_id'};
	}
	else {
		$sqlstring = "insert into qc_resultnames (qcresult_name, qcresult_type, qcresult_units, qcresult_labels) values ('$name','$type','$units','$labels')";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$id = $result->insertid;
	}
	return $id;
}


# ----------------------------------------------------------
# --------- ConvertToNifti ---------------------------------
# ----------------------------------------------------------
sub ConvertToNifti() {
	my ($indir,$tmpdir,$is_derived,$datatype) = @_;
	
	my $nvols = 0;

	unless (-e "$tmpdir/4D.nii.gz") {
	
		print "[[[[$tmpdir/4D.nii.gz does not exist]]]]";
		
		if (($is_derived) || ($datatype ne 'dicom')) {
			my $systemstring = "cp -v $indir/* $tmpdir";
			print("$systemstring (" . `$systemstring 2>&1` . ")");
		}
		else {
			my $currentdir = getcwd;
			chdir($indir);
			# create a 4D file
			my $systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y -g y -p n -i n -d n -f n -o '$tmpdir' *.dcm";
			print("$systemstring (" . `$systemstring 2>&1` . ")");
			
			chdir($currentdir);
		}
		
		my $systemstring = "mv $tmpdir/*.nii.gz $tmpdir/4D.nii.gz";
		print("$systemstring (" . `$systemstring 2>&1` . ")");
	}
	my $systemstring = "fslval $tmpdir/*.nii.gz dim4";
	$nvols = trim(`$systemstring 2>&1`);
	print "Num Vols: $nvols\n";

	if ($nvols > 1) {
		print "Num Vols is greater than 1 [$nvols]\n";
		unless ((-e "$tmpdir/mc4D.nii.gz") && (-e "$tmpdir/Tmean.nii.gz") && (-e "$tmpdir/Tsigma.nii.gz") && (-e "$tmpdir/Tvariance.nii.gz")) {

			print "[[[[ One of ($tmpdir/mc4D.nii.gz, $tmpdir/Tmean.nii.gz, $tmpdir/Tsigma.nii.gz, $tmpdir/Tvariance.nii.gz) does not exist]]]]\n";
		
			if (($is_derived) || ($datatype ne 'dicom')) {
				my $systemstring = "cp -v $indir/* $tmpdir";
				print("$systemstring (" . `$systemstring 2>&1` . ")");
			}
			else {
				my $currentdir = getcwd;
				chdir($tmpdir);
				# realign the 4D file
				my $systemstring = "mcflirt -in 4D -out mc4D -rmsrel -rmsabs -plots -stats";
				print("$systemstring (" . `$systemstring 2>&1` . ")");

				# move and rename the mean,sigma,variance volumes to the archive directory
				$systemstring = "mv *mc4D_meanvol.nii.gz Tmean.nii.gz";
				print("$systemstring (" . `$systemstring 2>&1` . ")");
				$systemstring = "mv *mc4D_sigma.nii.gz Tsigma.nii.gz";
				print("$systemstring (" . `$systemstring 2>&1` . ")");
				$systemstring = "mv *mc4D_variance.nii.gz Tvariance.nii.gz";
				print("$systemstring (" . `$systemstring 2>&1` . ")");

				# rename the realignment file to something meaningful
				$systemstring = "mv *.par MotionCorrection.txt";
				print("$systemstring (" . `$systemstring 2>&1` . ")");
				
				chdir($currentdir);
			}
		}
	}
	
	return $nvols;
}


# ----------------------------------------------------------
# --------- GetMovementStats -------------------------------
# ----------------------------------------------------------
sub GetMovementStats() {
	my ($filepath) = @_;
	
	print("Opening realignment file: $filepath");
	if (!-e $filepath) {
		print("Could not find $filepath");
		return (0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0);
	}
	open(FILE,$filepath) or die ("Could not open $filepath!");
	my @filecontents = <FILE>;
	close(FILE);

	#print(@filecontents);
	
	my (@rotx,@roty,@rotz,@trax,@tray,@traz);

	# rearrange the text file columns into arrays to pass to the max/min functions
	foreach my $line (@filecontents) {
		#print($line);
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


# ----------------------------------------------------------
# --------- Main Function ----------------------------------
# ----------------------------------------------------------
QC();