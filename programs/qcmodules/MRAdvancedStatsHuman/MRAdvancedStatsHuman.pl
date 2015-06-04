#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB MRAdvancedStatsHuman.pl
# Copyright (C) 2004-2015
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
# This program performs advanced MR quality control calculations
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
#use Math::Derivative qw(Derivative1 Derivative2);
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
			mkpath($tmpdir, {error => \my $err} );
			mkpath($qadir, {error => \my $err} );

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
				print("[$systemstring] (" . `$systemstring 2>&1` . ")\n");
			}
			
			# run the fmriqa_phantom.pl
			$systemstring = "perl /opt/bxh_xcede_tools/bin/fmriqa_generate.pl --overwrite $tmpdir/WRAPPED.xml $qadir";
			print("[$systemstring] (" . `$systemstring 2>&1` . ")\n");
			
			# ----- check if the thumbnails exist -----
			chdir($qadir);
		
			if (-e "$qadir/qa_cmassx_all.png") { InsertQCImageFile("qa_cmassx_all.png",$moduleseriesid, 'qa_cmassx_all','image'); }
			if (-e "$qadir/qa_cmassx_all_histo.png") { InsertQCImageFile("qa_cmassx_all_histo.png",$moduleseriesid, 'qa_cmassx_all_histo','image'); }
			if (-e "$qadir/qa_cmassx_allnorm.png") { InsertQCImageFile("qa_cmassx_allnorm.png",$moduleseriesid, 'qa_cmassx_all_norm','image'); }
			if (-e "$qadir/qa_cmassy_all.png") { InsertQCImageFile("qa_cmassy_all.png",$moduleseriesid, 'qa_cmassy_all','image'); }
			if (-e "$qadir/qa_cmassy_all_histo.png") { InsertQCImageFile("qa_cmassy_all_histo.png",$moduleseriesid, 'qa_cmassy_all_histo','image'); }
			if (-e "$qadir/qa_cmassy_allnorm.png") { InsertQCImageFile("qa_cmassy_allnorm.png",$moduleseriesid, 'qa_cmassy_all_norm','image'); }
			if (-e "$qadir/qa_cmassz_all.png") { InsertQCImageFile("qa_cmassz_all.png",$moduleseriesid, 'qa_cmassz_all','image'); }
			if (-e "$qadir/qa_cmassz_all_histo.png") { InsertQCImageFile("qa_cmassz_all_histo.png",$moduleseriesid, 'qa_cmassz_all_histo','image'); }
			if (-e "$qadir/qa_cmassz_allnorm.png") { InsertQCImageFile("qa_cmassz_allnorm.png",$moduleseriesid, 'qa_cmassz_all_norm','image'); }
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_cmassx.txt", $moduleseriesid, 'qa_data_cmassx','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_cmassy.txt", $moduleseriesid, 'qa_data_cmassy','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_cmassz.txt", $moduleseriesid, 'qa_data_cmassz','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_FWHMx-X.txt", $moduleseriesid, 'qa_data_FWHMx-X','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_FWHMx-Y.txt", $moduleseriesid, 'qa_data_FWHMx-Y','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_FWHMx-Z.txt", $moduleseriesid, 'qa_data_FWHMx-Z','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_maskedcmassx.txt", $moduleseriesid, 'qa_data_maskedcmassx','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_maskedcmassy.txt", $moduleseriesid, 'qa_data_maskedcmassy','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_maskedcmassz.txt", $moduleseriesid, 'qa_data_maskedcmassz','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_maskedtdiffvolmeans.txt", $moduleseriesid, 'qa_data_maskedtdiffvolmeans','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_maskedvolmeans.txt", $moduleseriesid, 'qa_data_maskedvolmeans','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_mdiffvolmeans.txt", $moduleseriesid, 'qa_data_mdiffvolmeans','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_outliercount.txt", $moduleseriesid, 'qa_data_outliercount','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_spectrummax.txt", $moduleseriesid, 'qa_data_spectrummax','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_spectrummean.txt", $moduleseriesid, 'qa_data_spectrummean','textfile','text','text');
			InsertQCResultFile("$cfg{'archivedir'}/$uid/$study_num/$series_num/qa/qa_data_volmeans.txt", $moduleseriesid, 'qa_data_volmeans','textfile','text','text');
			if (-e "$qadir/qa_FWHMx-X_all.png") { InsertQCImageFile("qa_FWHMx-X_all.png",$moduleseriesid, 'qa_FWHMx-X_all','image'); }
			if (-e "$qadir/qa_FWHMx-X_all_histo.png") { InsertQCImageFile("qa_FWHMx-X_all_histo.png",$moduleseriesid, 'qa_FWHMx-X_all_histo','image'); }
			if (-e "$qadir/qa_FWHMx-Y_all.png") { InsertQCImageFile("qa_FWHMx-Y_all.png",$moduleseriesid, 'qa_FWHMx-Y_all','image'); }
			if (-e "$qadir/qa_FWHMx-Y_all_histo.png") { InsertQCImageFile("qa_FWHMx-Y_all_histo.png",$moduleseriesid, 'qa_FWHMx-Y_all_histo','image'); }
			if (-e "$qadir/qa_FWHMx-Z_all.png") { InsertQCImageFile("qa_FWHMx-Z_all.png",$moduleseriesid, 'qa_FWHMx-Z_all','image'); }
			if (-e "$qadir/qa_FWHMx-Z_all_histo.png") { InsertQCImageFile("qa_FWHMx-Z_all_histo.png",$moduleseriesid, 'qa_FWHMx-Z_all_histo','image'); }
			if (-e "$qadir/qa_maskdata_WRAPPED.xml.png") { InsertQCImageFile("qa_maskdata_WRAPPED.xml.png",$moduleseriesid, 'qa_maskdata_WRAPPED.xml','image'); }
			if (-e "$qadir/qa_maskedcmassx_all.png") { InsertQCImageFile("qa_maskedcmassx_all.png",$moduleseriesid, 'qa_maskedcmassx_all','image'); }
			if (-e "$qadir/qa_maskedcmassx_all_histo.png") { InsertQCImageFile("qa_maskedcmassx_all_histo.png",$moduleseriesid, 'qa_maskedcmassx_all_histo','image'); }
			if (-e "$qadir/qa_maskedcmassx_allnorm.png") { InsertQCImageFile("qa_maskedcmassx_allnorm.png",$moduleseriesid, 'qa_maskedcmassx_allnorm','image'); }
			if (-e "$qadir/qa_maskedcmassy_all.png") { InsertQCImageFile("qa_maskedcmassy_all.png",$moduleseriesid, 'qa_maskedcmassy_all','image'); }
			if (-e "$qadir/qa_maskedcmassy_all_histo.png") { InsertQCImageFile("qa_maskedcmassy_all_histo.png",$moduleseriesid, 'qa_maskedcmassy_all_histo','image'); }
			if (-e "$qadir/qa_maskedcmassy_allnorm.png") { InsertQCImageFile("qa_maskedcmassy_allnorm.png",$moduleseriesid, 'qa_maskedcmassy_allnorm','image'); }
			if (-e "$qadir/qa_maskedcmassz_all.png") { InsertQCImageFile("qa_maskedcmassz_all.png",$moduleseriesid, 'qa_maskedcmassz_all','image'); }
			if (-e "$qadir/qa_maskedcmassz_all_histo.png") { InsertQCImageFile("qa_maskedcmassz_all_histo.png",$moduleseriesid, 'qa_maskedcmassz_all_histo','image'); }
			if (-e "$qadir/qa_maskedcmassz_allnorm.png") { InsertQCImageFile("qa_maskedcmassz_allnorm.png",$moduleseriesid, 'qa_maskedcmassz_allnorm','image'); }
			if (-e "$qadir/qa_maskedtdiffvolmeans_all.png") { InsertQCImageFile("qa_maskedtdiffvolmeans_all.png",$moduleseriesid, 'qa_maskedtdiffvolmeans_all','image'); }
			if (-e "$qadir/qa_maskedtdiffvolmeans_all_histo.png") { InsertQCImageFile("qa_maskedtdiffvolmeans_all_histo.png",$moduleseriesid, 'qa_maskedtdiffvolmeans_all_histo','image'); }
			if (-e "$qadir/qa_maskedtdiffvolmeans_allnorm.png") { InsertQCImageFile("qa_maskedtdiffvolmeans_allnorm.png",$moduleseriesid, 'qa_maskedtdiffvolmeans_allnorm','image'); }
			if (-e "$qadir/qa_maskedvolmeans_all.png") { InsertQCImageFile("qa_maskedvolmeans_all.png",$moduleseriesid, 'qa_maskedvolmeans_all','image'); }
			if (-e "$qadir/qa_maskedvolmeans_all_histo.png") { InsertQCImageFile("qa_maskedvolmeans_all_histo.png",$moduleseriesid, 'qa_maskedvolmeans_all_histo','image'); }
			if (-e "$qadir/qa_maskedvolmeans_allnorm.png") { InsertQCImageFile("qa_maskedvolmeans_allnorm.png",$moduleseriesid, 'qa_maskedvolmeans_allnorm','image'); }
			if (-e "$qadir/qa_mdiffvolmeans_all.png") { InsertQCImageFile("qa_mdiffvolmeans_all.png",$moduleseriesid, 'qa_mdiffvolmeans_all','image'); }
			if (-e "$qadir/qa_mdiffvolmeans_all_histo.png") { InsertQCImageFile("qa_mdiffvolmeans_all_histo.png",$moduleseriesid, 'qa_mdiffvolmeans_all_histo','image'); }
			if (-e "$qadir/qa_mdiffvolmeans_allnorm.png") { InsertQCImageFile("qa_mdiffvolmeans_allnorm.png",$moduleseriesid, 'qa_mdiffvolmeans_allnorm','image'); }
			if (-e "$qadir/qa_meandata_WRAPPED.xml.jpg") { InsertQCImageFile("qa_meandata_WRAPPED.xml.jpg",$moduleseriesid, 'qa_meandata_WRAPPED.xml','image'); }
			if (-e "$qadir/qa_outliercount_all.png") { InsertQCImageFile("qa_outliercount_all.png",$moduleseriesid, 'qa_outliercount_all','image'); }
			if (-e "$qadir/qa_outliercount_all_histo.png") { InsertQCImageFile("qa_outliercount_all_histo.png",$moduleseriesid, 'qa_outliercount_all_histo','image'); }
			if (-e "$qadir/qa_sfnrdata_WRAPPED.xml.jpg") { InsertQCImageFile("qa_sfnrdata_WRAPPED.xml.jpg",$moduleseriesid, 'qa_sfnrdata_WRAPPED.xml','image'); }
			if (-e "$qadir/qa_slicevardata_WRAPPED.xml.png") { InsertQCImageFile("qa_slicevardata_WRAPPED.xml.png",$moduleseriesid, 'qa_slicevardata_WRAPPED.xml','image'); }
			if (-e "$qadir/qa_spectrummax_all.png") { InsertQCImageFile("qa_spectrummax_all.png",$moduleseriesid, 'qa_spectrummax_all','image'); }
			if (-e "$qadir/qa_spectrummax_all_histo.png") { InsertQCImageFile("qa_spectrummax_all_histo.png",$moduleseriesid, 'qa_spectrummax_all_histo','image'); }
			if (-e "$qadir/qa_spectrummean_all.png") { InsertQCImageFile("qa_spectrummean_all.png",$moduleseriesid, 'qa_spectrummean_all','image'); }
			if (-e "$qadir/qa_spectrummean_all_histo.png") { InsertQCImageFile("qa_spectrummean_all_histo.png",$moduleseriesid, 'qa_spectrummean_all_histo','image'); }
			if (-e "$qadir/qa_stddevdata_WRAPPED.xml.jpg") { InsertQCImageFile("qa_stddevdata_WRAPPED.xml.jpg",$moduleseriesid, 'qa_stddevdata_WRAPPED.xml','image'); }
			if (-e "$qadir/qa_volmeans_all.png") { InsertQCImageFile("qa_volmeans_all.png",$moduleseriesid, 'qa_volmeans_all','image'); }
			if (-e "$qadir/qa_volmeans_all_histo.png") { InsertQCImageFile("qa_volmeans_all_histo.png",$moduleseriesid, 'qa_volmeans_all_histo','image'); }
			if (-e "$qadir/qa_volmeans_allnorm.png") { InsertQCImageFile("qa_volmeans_allnorm.png",$moduleseriesid, 'qa_volmeans_allnorm','image'); }

			# delete the 4D file and temp directory
			if (trim($tmpdir) ne "") {
				$systemstring = "rm -rf $tmpdir";
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
}




# ----------------------------------------------------------
# --------- InsertQCResultFile -----------------------------
# ----------------------------------------------------------
sub InsertQCResultFile() {
	my ($filename, $moduleseriesid, $resultname, $resulttype, $units, $labels) = @_;
	
	if (-e $filename) {
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