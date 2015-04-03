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
use Net::SMTP::TLS;
use Data::Dumper;
use File::Path;
use File::Copy;
use Switch;
use Sort::Naturally;
use Math::Derivative qw(Derivative1 Derivative2);
use List::Util qw(first max maxstr min minstr reduce shuffle sum);
use File::Slurp;
use Cwd;
require '../../nidbroutines.pl';

#my %config = do 'config.pl';
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

			# check if all of the common files already exist, if they do insert them into the database
			if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt") {
				# read the file and put into database
				#my $text = read_file("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt");
				#my $resultnameid = InsertQCResultName('fMRIMeanIntensityOverTime');
				#my $sqlstringA = "insert into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuetext, qcresults_datetime) values ($moduleseriesid, $resultnameid, '$text', now())";
				#print($sqlstringA);
				#my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
				InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt", $moduleseriesid, 'fMRIMeanIntensityOverTime');
			}
			else {
				ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
				my $systemstring = "fslstats -t $tmpdir/mc4D -m > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt";
				print("$systemstring (" . `$systemstring` . ")");
				#my $text = read_file("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt");
				#my $resultnameid = InsertQCResultName('fMRIMeanIntensityOverTime');
				#my $sqlstringA = "insert into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuetext, qcresults_datetime) values ($moduleseriesid, $resultnameid, '$text', now())";
				#my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
				InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt", $moduleseriesid, 'fMRIMeanIntensityOverTime');
			}
			
			if (-e "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt") {
				# read the file and put into database
				#my $text = read_file("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt");
				#my $resultnameid = InsertQCResultName('fMRIStdevIntensityOverTime');
				#my $sqlstringA = "insert into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuetext, qcresults_datetime) values ($moduleseriesid, $resultnameid, '$text', now())";
				#print($sqlstringA);
				#my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
				InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt", $moduleseriesid, 'fMRIStdevIntensityOverTime');
			}
			else {
				ConvertToNifti("$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti", $tmpdir,$is_derived,$datatype);
				my $systemstring = "fslstats -t $tmpdir/mc4D -s > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt";
				print("$systemstring (" . `$systemstring` . ")");
				#my $text = read_file("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt");
				#my $resultnameid = InsertQCResultName('fMRIStdevIntensityOverTime');
				#my $sqlstringA = "insert into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuetext, qcresults_datetime) values ($moduleseriesid, $resultnameid, '$text', now())";
				#my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
				InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt", $moduleseriesid, 'fMRIStdevIntensityOverTime');
			}

				# # move the realignment file(s) to the archive directory
				# $systemstring = "mv $tmpdir/*.par $cfg{'archivedir'}/$uid/$study_num/$series_num/qa";
				# print("$systemstring (" . `$systemstring` . ")");

				# # rename the realignment file to something meaningful
				# $systemstring = "mv $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/*.par $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt";
				# print("$systemstring (" . `$systemstring` . ")");
				
				# # move and rename the mean,sigma,variance volumes to the archive directory
				# $systemstring = "mv $tmpdir/*mcvol_meanvol.nii.gz $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.nii.gz";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "mv $tmpdir/*mcvol_sigma.nii.gz $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.nii.gz";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "mv $tmpdir/*mcvol_variance.nii.gz $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.nii.gz";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "mv $tmpdir/*mcvol.nii.gz $tmpdir/mc4D.nii.gz";
				# print("$systemstring (" . `$systemstring` . ")");

				# # get min/max intensity in the mean/variance/stdev volumes
				# $systemstring = "fslstats $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.nii.gz -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxMean.txt";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "fslstats $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.nii.gz -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxSigma.txt";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "fslstats $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.nii.gz -R > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/minMaxVariance.txt";
				# print("$systemstring (" . `$systemstring` . ")");

				# # create thumbnails
				# $systemstring = "slicer $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tmean.png";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "slicer $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tsigma.png";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "slicer $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.nii.gz -a $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/Tvariance.png";
				# print("$systemstring (" . `$systemstring` . ")");

				# # get mean/stdev in intensity over time
				# $systemstring = "fslstats -t $tmpdir/mc4D -m > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/meanIntensityOverTime.txt";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "fslstats -t $tmpdir/mc4D -s > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/stdevIntensityOverTime.txt";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "fslstats -t $tmpdir/mc4D -e > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/entropyOverTime.txt";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "fslstats -t $tmpdir/mc4D -c > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeMM.txt";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "fslstats -t $tmpdir/mc4D -C > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/centerOfGravityOverTimeVox.txt";
				# print("$systemstring (" . `$systemstring` . ")");
				# $systemstring = "fslstats -t $tmpdir/mc4D -h 100 > $cfg{'archivedir'}/$uid/$study_num/$series_num/qa/histogramOverTime.txt";
				# print("$systemstring (" . `$systemstring` . ")");
			# }
			# parse the QA output file
			#my ($pvsnr,$iosnr) = GetQAStats("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa.txt");
			
			# parse the movement correction file
			#my ($maxrx,$maxry,$maxrz,$maxtx,$maxty,$maxtz,$maxax,$maxay,$maxaz,$minrx,$minry,$minrz,$mintx,$minty,$mintz,$minax,$minay,$minaz) = GetMovementStats("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/MotionCorrection.txt");

			# delete the 4D file and temp directory
			$systemstring = "rm $tmpdir/*";
			print("$systemstring (" . `$systemstring` . ")");
			rmdir($tmpdir);
			
			# calculate the total time running
			my $endtime = GetTotalCPUTime();
			my $cputime = $endtime - $starttime;
				
			# insert this row into the DB
			#my $sqlstringC = "update mr_qa set mrseries_id = $seriesid, io_snr = '$iosnr', pv_snr = '$pvsnr', move_minx = '$mintx', move_miny = '$minty', move_minz = '$mintz', move_maxx = '$maxtx', move_maxy = '$maxty', move_maxz = '$maxtz', acc_minx = '$minax', acc_miny = '$minay', acc_minz = '$minaz', acc_maxx = '$maxax', acc_maxy = '$maxay', acc_maxz = '$maxaz', rot_minp = '$minrx', rot_minr = '$minry', rot_miny = '$minrz', rot_maxp = '$maxrx', rot_maxr = '$maxry', rot_maxy = '$maxrz', motion_rsq = '$motion_rsq', cputime = $cputime where mrqa_id = $mrqaid";
			#print("[$sqlstringC]");
			#my $resultC = $db->query($sqlstringC) || SQLError($db->errmsg(),$sqlstringC);
			
			# only process 10 before exiting the script. Since the script always starts with the newest when it first runs,
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
	my ($filename, $moduleseriesid, $resultname) = @_;
	
	my $text = read_file($filename);
	my $resultnameid = InsertQCResultName($resultname);
	my $sqlstringA = "insert into qc_results (qcmoduleseries_id, qcresultname_id, qcresults_valuetext, qcresults_datetime) values ($moduleseriesid, $resultnameid, '$text', now())";
	print($sqlstringA);
	my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
}


# ----------------------------------------------------------
# --------- InsertQCResultName -----------------------------
# ----------------------------------------------------------
sub InsertQCResultName() {
	my ($name) = @_;
	
	my $id;
	
	my $sqlstring = "select qcresultname_id from qc_resultnames where qcresult_name = '$name'";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$id = $row{'qcresultname_id'};
	}
	else {
		$sqlstring = "insert into qc_resultnames (qcresult_name) values ('$name')";
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

	unless (-e "$tmpdir/4D.nii.gz") {
		if (($is_derived) || ($datatype ne 'dicom')) {
			my $systemstring = "cp $indir/* $tmpdir";
			print("$systemstring (" . `$systemstring` . ")");
		}
		else {
			my $currentdir = getcwd;
			chdir($indir);
			# create a 4D file
			my $systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y -g y -p n -i n -d n -f n -o '$tmpdir' *.dcm";
			print("$systemstring (" . `$systemstring` . ")");
			
			chdir($currentdir);
		}
		
		my $systemstring = "mv $tmpdir/*.nii.gz $tmpdir/4D.nii.gz";
		print("$systemstring (" . `$systemstring` . ")");
	}
	
	unless (-e "$tmpdir/mc4D.nii.gz") {
		if (($is_derived) || ($datatype ne 'dicom')) {
			my $systemstring = "cp $indir/* $tmpdir";
			print("$systemstring (" . `$systemstring` . ")");
		}
		else {
			my $currentdir = getcwd;
			chdir($tmpdir);
			# realign the 4D file
			my $systemstring = "mcflirt -in 4D -out mc4D -rmsrel -rmsabs -plots -stats";
			print("$systemstring (" . `$systemstring` . ")");
			chdir($currentdir);
		}
	}

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