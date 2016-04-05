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

my $old_fh = select(STDOUT);
$| = 1;
select($old_fh);

# ----------------------------------------------------------
# --------- QC ---------------------------------------------
# ----------------------------------------------------------
sub QC() {
	my $sqlstring;

	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");

	$sqlstring = "select * from qc_moduleseries where qcmoduleseries_id = $moduleseriesid";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	my %row = $result->fetchhash;
	my $seriesid = $row{'series_id'};
	my $modality = $row{'modality'};
	
	# find where the files are, build the directory path to the dicom data
	$sqlstring = "select a.series_num, a.is_derived, a.data_type, b.study_num, d.uid, e.project_costcenter 
	from mr_series a
	left join studies b on a.study_id = b.study_id
	left join enrollment c on b.enrollment_id = c.enrollment_id
	left join subjects d on c.subject_id = d.subject_id
	left join projects e on c.project_id = e.project_id
	where a.mrseries_id = $seriesid";
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
			print("Working on [$indir]");
			my $systemstring;
			chdir($indir);

			my $tmpdir = "/tmp/" . GenerateRandomString(10);
			mkpath($tmpdir);
			mkpath("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa");

			
			# ----- check if all of the text files already exist, if they do insert them into the database -----
			my $dimension = ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);

			if ($dimension > 1) {
				# fMRIMeanIntensityOverTime
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt") {
					print "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt exists!\n";
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt", $moduleseriesid, 'fMRIMeanIntensityOverTime','graph','intensity','intensity');
				}
				else {
					print "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt does NOT exist!\n";
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/mc4D -m > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt", $moduleseriesid, 'fMRIMeanIntensityOverTime','graph','intensity','intensity');
				}
				
				# fMRIStdevIntensityOverTime
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt") {
					print "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt exists!\n";
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt", $moduleseriesid, 'fMRIStdevIntensityOverTime','graph','intensity','intensity');
				}
				else {
					print "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt does NOT exist!\n";
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/mc4D -s > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt", $moduleseriesid, 'fMRIStdevIntensityOverTime','graph','intensity','intensity');
				}
				
				# fMRIEntropyOverTime
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/entropyOverTime.txt") {
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/entropyOverTime.txt", $moduleseriesid, 'fMRIEntropyOverTime','graph','entropy','entropy');
				}
				else {
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/mc4D -e > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/entropyOverTime.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/entropyOverTime.txt", $moduleseriesid, 'fMRIEntropyOverTime','graph','entropy','entropy');
				}

				# fMRICenterOfGravityOverTimeMM
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeMM.txt") {
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeMM.txt", $moduleseriesid, 'fMRICenterOfGravityOverTimeMM','graph','mm','Center of graivty over time');
				}
				else {
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/mc4D -c > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeMM.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeMM.txt", $moduleseriesid, 'fMRICenterOfGravityOverTimeMM','graph','mm','Center of graivty over time');
				}

				# fMRICenterOfGravityOverTimeVox
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeVox.txt") {
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeVox.txt", $moduleseriesid, 'fMRICenterOfGravityOverTimeVox','graph','voxels','Center of graivty over time');
				}
				else {
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/mc4D -C > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeVox.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeVox.txt", $moduleseriesid, 'fMRICenterOfGravityOverTimeVox','graph','voxels','Center of graivty over time');
				}
				
				# fMRIHistogramOverTime
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/histogramOverTime.txt") {
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/histogramOverTime.txt", $moduleseriesid, 'fMRIHistogramOverTime','histogram','intensity','histogram');
				}
				else {
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/mc4D -h 100 > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/histogramOverTime.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/histogramOverTime.txt", $moduleseriesid, 'fMRIHistogramOverTime','histogram','intensity','histogram');
				}

				# fMRIMinMaxMean
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxMean.txt") {
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxMean.txt", $moduleseriesid, 'fMRIMinMaxMean','minmax','intensity','Mean');
				}
				else {
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/Tmean -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxMean.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxMean.txt", $moduleseriesid, 'fMRIMinMaxMean','minmax','intensity','Mean');
				}

				# fMRIMinMaxSigma
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxSigma.txt") {
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxSigma.txt", $moduleseriesid, 'fMRIMinMaxSigma','minmax','intensity','Sigma');
				}
				else {
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/Tsigma -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxSigma.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxSigma.txt", $moduleseriesid, 'fMRIMinMaxSigma','minmax','intensity','Sigma');
				}

				# fMRIMinMaxVariance
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxVariance.txt") {
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxVariance.txt", $moduleseriesid, 'fMRIMinMaxVariance','minmax','intensity','Variance');
				}
				else {
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/Tvariance -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxVariance.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxVariance.txt", $moduleseriesid, 'fMRIMinMaxVariance','minmax','intensity','Variance');
				}

				# fMRIMotionCorrection
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt") {
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt", $moduleseriesid, 'fMRIMotionCorrection','graph','mm','Pitch,Roll,Yaw,X,Y,Z');
				}
				else {
					ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
					my $systemstring = "fslstats -t $tmpdir/Tvariance -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt", $moduleseriesid, 'fMRIMotionCorrection','graph','mm','Pitch,Roll,Yaw,X,Y,Z');
				}
				
				# parse the movement correction file
				my ($maxrx,$maxry,$maxrz,$maxtx,$maxty,$maxtz,$maxax,$maxay,$maxaz,$minrx,$minry,$minrz,$mintx,$minty,$mintz,$minax,$minay,$minaz) = GetMovementStats("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt");

				InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection2.txt", $moduleseriesid, 'fMRIMotionCorrection','graph','mm','Pitch,Roll,Yaw,X,Y,Z');
	
				InsertQCResult($maxrx, $moduleseriesid, 'fMRIMaxRotationPitch','radians','Pitch');
				InsertQCResult($maxry, $moduleseriesid, 'fMRIMaxRotationRoll','radians','Roll');
				InsertQCResult($maxrz, $moduleseriesid, 'fMRIMaxRotationYaw','radians','Yaw');
				InsertQCResult($maxtx, $moduleseriesid, 'fMRIMaxTranslationX','mm','X');
				InsertQCResult($maxty, $moduleseriesid, 'fMRIMaxTranslationY','mm','Y');
				InsertQCResult($maxtz, $moduleseriesid, 'fMRIMaxTranslationZ','mm','Z');
				InsertQCResult($maxax, $moduleseriesid, 'fMRIMaxAccelerationX','mm/TR','X');
				InsertQCResult($maxay, $moduleseriesid, 'fMRIMaxAccelerationY','mm/TR','Y');
				InsertQCResult($maxaz, $moduleseriesid, 'fMRIMaxAccelerationZ','mm/TR','Z');
				InsertQCResult($minrx, $moduleseriesid, 'fMRIMinRotationPitch','radians','Pitch');
				InsertQCResult($minry, $moduleseriesid, 'fMRIMinRotationRoll','radians','Roll');
				InsertQCResult($minrz, $moduleseriesid, 'fMRIMinRotationYaw','radians','Yaw');
				InsertQCResult($mintx, $moduleseriesid, 'fMRIMinTranslationX','mm','X');
				InsertQCResult($minty, $moduleseriesid, 'fMRIMinTranslationY','mm','Y');
				InsertQCResult($mintz, $moduleseriesid, 'fMRIMinTranslationZ','mm','Z');
				InsertQCResult($minax, $moduleseriesid, 'fMRIMinAccelerationX','mm/TR','X');
				InsertQCResult($minay, $moduleseriesid, 'fMRIMinAccelerationY','mm/TR','Y');
				InsertQCResult($minaz, $moduleseriesid, 'fMRIMinAccelerationZ','mm/TR','Z');
				
				# ----- check if the thumbnails exist -----
			
				# Tmean.png
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.png") {
					InsertQCImageFile("Tmean.png",$moduleseriesid, 'fMRITmean','image');
				}
				else {
					unless (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.nii.gz") {
						ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
						my $systemstring = "fslstats -t $tmpdir/Tmean -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt";
						print("$systemstring (" . `$systemstring 2>&1` . ")");
					}
					$systemstring = "slicer $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.png";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCImageFile("Tmean.png",$moduleseriesid, 'fMRITmean','image');
				}

				# Tsigma.png
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.png") {
					InsertQCImageFile("Tsigma.png",$moduleseriesid, 'fMRITsigma','image');
				}
				else {
					unless (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.nii.gz") {
						ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
						my $systemstring = "fslstats -t $tmpdir/Tsigma -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt";
						print("$systemstring (" . `$systemstring 2>&1` . ")");
					}
					$systemstring = "slicer $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.png";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCImageFile("Tsigma.png",$moduleseriesid, 'fMRITsigma','image');
				}

				# Tvariance.png
				if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.png") {
					InsertQCImageFile("Tvariance.png",$moduleseriesid, 'fMRITvariance','image');
				}
				else {
					unless (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.nii.gz") {
						ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
						my $systemstring = "fslstats -t $tmpdir/Tvariance -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt";
						print("$systemstring (" . `$systemstring 2>&1` . ")");
					}
					$systemstring = "slicer $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.png";
					print("$systemstring (" . `$systemstring 2>&1` . ")");
					InsertQCImageFile("Tvariance.png",$moduleseriesid, 'fMRITvariance','image');
				}
			}
			# delete the 4D file and temp directory
			if (trim($tmpdir) ne "") {
				$systemstring = "rm -rf $tmpdir";
				print("$systemstring (" . `$systemstring 2>&1` . ")");
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

}


# ----------------------------------------------------------
# --------- GetQAStats -------------------------------------
# ----------------------------------------------------------
sub GetQAStats() {
	my ($filepath) = @_;

	print("Opening SNR file: $filepath");
	if (!-e $filepath) {
		return (-1,-1);
	}
	open(FILE,$filepath) or die ("Could not open $filepath!");
	my @filecontents = <FILE>;
	close(FILE);

	#print(@filecontents);
	
	#my ($pvsnr,$iosnr);
	
	my $line = $filecontents[1];
	#print($line);
	$line =~ s/\n//g;
	my ($fname, $pvsnr, $iosnr) = split(/\t/, trim($line));
	
	if (trim($pvsnr) eq "") { $pvsnr = 0.0; }
	if (trim($iosnr) eq "") { $pvsnr = 0.0; }
	
	return ($pvsnr,$iosnr);
}


# ----------------------------------------------------------
# --------- InsertQCResultFile -----------------------------
# ----------------------------------------------------------
sub InsertQCResultFile() {
	my ($filename, $moduleseriesid, $resultname, $resulttype, $units, $labels) = @_;
	
	my $text;
	open(F,$filename);
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
			my $systemstring = "cp $indir/* $tmpdir";
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
		my $currentdir = getcwd;
		chdir($indir);
		# go into the directory
		my $systemstring = "cd $tmpdir; gzip *.nii; mv $tmpdir/*.nii.gz $tmpdir/4D.nii.gz";
		print("$systemstring (" . `$systemstring 2>&1` . ")");
		chdir($currentdir);
	}
	my $systemstring = "fslval $tmpdir/*.nii.gz dim4";
	$nvols = trim(`$systemstring 2>&1`);
	print "Num Vols: $nvols\n";

	if ($nvols > 1) {
		print "Num Vols is greater than 1 [$nvols]\n";
		unless ((-e "$tmpdir/mc4D.nii.gz") && (-e "$tmpdir/Tmean.nii.gz") && (-e "$tmpdir/Tsigma.nii.gz") && (-e "$tmpdir/Tvariance.nii.gz")) {

			print "[[[[ One of ($tmpdir/mc4D.nii.gz, $tmpdir/Tmean.nii.gz, $tmpdir/Tsigma.nii.gz, $tmpdir/Tvariance.nii.gz) does not exist]]]]\n";
		
			if (($is_derived) || ($datatype ne 'dicom')) {
				my $systemstring = "cp $indir/* $tmpdir";
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