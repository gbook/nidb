#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB parseincomingabide.pl
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
# This program imports non-dicom data, specifically from the INDI-ABIDE dataset
# 
# [3/20/2012] - Greg Book
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
use Getopt::Long;
use Cwd;
use Switch;

require 'nidbroutines.pl';
#my %config = do 'config.pl';
our %cfg;
LoadConfig();

# database variables
#our $cfg{'mysqlhost'} = $config{mysqlhost};
#our $cfg{'mysqldatabase'} = $config{mysqldatabase};
#our $cfg{'mysqluser'} = $config{mysqluser};
#our $cfg{'mysqlpassword'} = $config{mysqlpassword};
our $db;

# email parameters (SMTP through TLS)
#our $cfg{'emailusername'} = $config{emailusername};
#our $cfg{'emailpassword'} = $config{emailpassword};
#our $cfg{'emailserver'} = $config{emailserver};
#our $cfg{'emailport'} = $config{emailport};
#our $cfg{'adminemail'} = $config{adminemail};

# script specific information
our $scriptname = "parseincomingabide";
our $lockfileprefix = "parseincomingabide";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently

# debugging
our $debug = 0;

# setup the directories to be used
#our $cfg{'logdir'}			= $config{logdir};			# where the log files are kept
#our $cfg{'lockdir'}		= $config{lockdir};			# where the lock files are
#our $cfg{'scriptdir'}		= $config{scriptdir};		# where this program and others are run from
#our $cfg{'archivedir'}		= $config{archivedir};		# this is the final place for dicom data
#our $cfg{'backupdir'}		= $config{backupdir};		# backup directory. all handled data is copied here
#our $cfg{'mountdir'}		= $config{mountdir};		# directory in which all NFS directories are mounted


# ------------- end variable declaration --------------------------------------
# -----------------------------------------------------------------------------

# get the command line options
my ($costcenter, $label, $datadir, $studysite);
my $r = GetOptions ("costcenter|c=s" => \$costcenter, "label|l=s" => \$label, "datadir|d=s" => \$datadir, "studysite|s=s" => \$studysite);

if (!defined($costcenter)) {
	print "Cost center not defined. Use 6-digit #, ex '800000'";
	DisplayUsage();
	exit(0);
}
if (!defined($label)) {
	print "Label not defined. Label allows easier searching in NIDB. Use string, ex: 'ABIDE'";
	exit(0);
}
if (!defined($datadir)) {
	print "Data directory not defined. Specify absolute directory of the unencrypted/unzipped data, ex: /nidb/import/ABIDE/ONRC";
	exit(0);
}
if (!defined($studysite)) {
	print "Study site not defined. Specify a unique site name, ex: 'ONRC1'";
	exit(0);
}

# create a log file and do the import
my $logfilename;
$logfilename = "$cfg{'logdir'}/$scriptname" . CreateLogDate() . ".log";
open $log, '> ', $logfilename;
my $x = DoParse($costcenter, $label, $datadir, $studysite);
close $log;

exit(0);


# ----------------------------------------------------------
# --------- DoParse ----------------------------------------
# ----------------------------------------------------------
sub DoParse {
	my ($costcenter, $label, $datadir, $StudySite) = @_;
	
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my $ret = 0;
	my $i = 0;
	my $alternateuid;
	
	# connect to the database
	#$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	DatabaseConnect();
	print "Connected to database\n";
	
	# ----- parse all files in /incoming -----
	opendir(DIR,$datadir) || die("Cannot open directory (1) $datadir!\n");
	my @dirs = readdir(DIR);
	closedir(DIR);
	WriteLog("$datadir");
	foreach my $dir (sort @dirs) {
		if ( ($dir ne ".") && ($dir ne "..") ) {
			if (-d "$datadir/$dir") {
				$alternateuid = uc(trim($dir));
				if ($alternateuid =~ /^[0-9]{7}$/) {
					#chdir($datadir);
					WriteLog("$dir");
					
					opendir(DIR,"$datadir/$dir") || die("Cannot open directory (1) $datadir!\n");
					my @dirs2 = readdir(DIR);
					closedir(DIR);
					foreach my $dir2 (sort @dirs2) {
						if ( ($dir2 ne ".") && ($dir2 ne "..") ) {
							if (-d "$datadir/$dir/$dir2") {
								my $session = trim($dir2);
								if ($session =~ /^session/) {
									my $altstudyid = $session;
									#chdir($datadir);
									WriteLog("$datadir/$dir/$dir2");
					
									opendir(DIR,"$datadir/$dir/$dir2") || die("Cannot open directory (1) $datadir!\n");
									my @dirs3 = readdir(DIR);
									closedir(DIR);
									foreach my $dir3 (sort @dirs3) {
										if ( ($dir3 ne ".") && ($dir3 ne "..") ) {
											if (-d "$datadir/$dir/$dir2/$dir3") {
												my $seriesname = trim($dir3);
												#chdir($datadir);
												WriteLog("$datadir/$dir/$dir2/$dir3");

												WriteLog("InsertSeries($datadir/$dir/$dir2/$dir3, $costcenter, $StudySite, $alternateuid, $seriesname, $label)");
												InsertSeries("$datadir/$dir/$dir2/$dir3", $costcenter, $StudySite, $alternateuid, $altstudyid, $seriesname, $label);
												#exit(0);
											}
										}
									}
								}
							}
						}
					}
				}
				else {
					WriteLog("$alternateuid not in correct format");
				}
			}
		}
	}
	
	if ($i > 0) {
		WriteLog("Finished extracting data");
		$ret = 1;
	}
	else {
		WriteLog("Nothing to do");
	}
	
	return $ret;
}


# ----------------------------------------------------------
# --------- InsertSeries -----------------------------------
# ----------------------------------------------------------
sub InsertSeries {
	my ($datadir, $costcenter, $StudySite, $alternateuid, $altstudyid, $seriesname, $label) = @_;
	
	my $sqlstring;
	my $result;
	my %row;
	
	my $subjectRowID;
	my $subjectRealUID;
	my $projectRowID;
	my $enrollmentRowID;
	my $studyRowID;
	my $seriesRowID;
	my $studyNum;
	my $PatientName = "$label^$alternateuid";
	my $SeriesNumber = "";
	my $StudyDateTime = "2012-08-30 00:00:00";
	my $SeriesDateTime = "2012-08-30 00:00:00";
	my $Modality = "MR";
	my $StudyDesc = "$label - MR";
	my $StudyOperator = "Unknown";
	my $StudyPerformingPhysician = "$label";
	my $StudyInstitution = $StudySite;
	my $SeriesDesc = $seriesname;
	my $ProtocolName = $seriesname;
	my $SequenceName = $seriesname;
	
	$studyRowID = "";
	$seriesRowID = "";
	
	# find the first file and determine the dimensions and spacing
	opendir(DIR,"$datadir") || die("Cannot open directory (1) $datadir!\n");
	my @files = readdir(DIR);
	closedir(DIR);
	my $firstfile;
	foreach my $file (sort @files) {
		if ( ($file ne ".") && ($file ne "..") && (substr($file,0,1) ne ".") ) {
			if (-f "$datadir/$file") {
				$firstfile = "$datadir/$file";
				last;
			}
		}
	}

	# get image dimensions
	my $xdim = trim(`fslval $firstfile dim1`); WriteLog("fslval $firstfile dim1");
	my $ydim = trim(`fslval $firstfile dim2`); WriteLog("fslval $firstfile dim2");
	my $zdim = trim(`fslval $firstfile dim3`); WriteLog("fslval $firstfile dim3");
	my $tdim = trim(`fslval $firstfile dim4`); WriteLog("fslval $firstfile dim4");
	my $xpix = trim(`fslval $firstfile pixdim1`); WriteLog("fslval $firstfile pixdim1");
	my $ypix = trim(`fslval $firstfile pixdim2`); WriteLog("fslval $firstfile pixdim2");
	my $zpix = trim(`fslval $firstfile pixdim3`); WriteLog("fslval $firstfile pixdim3");
	#my $filesize = -s $firstfile;
	
	# check for invalid return values
	if ($xdim eq "Usage: fslval <input> <keyword>") { $xdim = "-1"; }
	if ($ydim eq "Usage: fslval <input> <keyword>") { $ydim = "-1"; }
	if ($zdim eq "Usage: fslval <input> <keyword>") { $zdim = "-1"; }
	if ($tdim eq "Usage: fslval <input> <keyword>") { $tdim = "-1"; }
	if ($xpix eq "Usage: fslval <input> <keyword>") { $xpix = "-1"; }
	if ($ypix eq "Usage: fslval <input> <keyword>") { $ypix = "-1"; }
	if ($zpix eq "Usage: fslval <input> <keyword>") { $zpix = "-1"; }
	#if ($filesize eq "") { $filesize = "0"; }
	
	WriteLog("Image dimensions: $xdim,$ydim,$zdim,$xpix,$ypix,$zpix");
	#exit(0);
	
	# get the project DB ID
	$sqlstring = "SELECT * FROM `projects` WHERE project_costcenter = '$costcenter'";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$projectRowID = $row{'project_id'};
		WriteLog("Found project ID: $projectRowID");
	}
	else {
		WriteLog("Found no project ID");
		return;
	}
	
	# get the subject ID and create a subject
	$sqlstring = "SELECT a.* FROM subjects a left join subject_altuid b on a.subject_id = b.subject_id WHERE b.altuid = '$alternateuid' and a.isactive = 1";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$subjectRowID = $row{'subject_id'};
		$subjectRealUID = $row{'uid'};
		WriteLog("Found subject ID: $subjectRowID");
	}
	else {
		# create a new UID
		my $count = 0;
		$subjectRealUID = "";
		
		# create a new subjectRealUID
		do {
			$subjectRealUID = CreateSubjectID();
			$sqlstring = "SELECT * FROM `subjects` WHERE uid = '$subjectRealUID'";
			WriteLog("[$sqlstring]");
			$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
			$count = $result->numrows;
		} while ($count > 0);
		
		WriteLog("New subject ID: $subjectRealUID");
		$sqlstring = "insert into subjects (name, birthdate, gender, weight, uid, uuid) values ('$PatientName', '1776-07-04', 'O', '0', '$subjectRealUID', ucase(md5(concat(RemoveNonAlphaNumericChars('$PatientName'), RemoveNonAlphaNumericChars('1776-07-04'),RemoveNonAlphaNumericChars('O')))))";
		WriteLog("[$sqlstring]");
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		$subjectRowID = $result->insertid;
		
		$sqlstring = "insert into subject_altuid (subject_id, altuid) values ($subjectRowID, '$alternateuid')";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	}

	# check if the subject is enrolled in the project
	$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectRowID and project_id = $projectRowID";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		WriteLog("[$sqlstring]");
		my %row = $result->fetchhash;
		$enrollmentRowID = $row{'enrollment_id'};
	}
	else {
		# create enrollmentRowID if it doesn't exist
		$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
		WriteLog("[$sqlstring]");
		my $result2 = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$enrollmentRowID = $result2->insertid;
	}
	
	# create studyRowID if it doesn't exist
	$sqlstring = "select study_id, study_num from studies where enrollment_id = $enrollmentRowID and study_alternateid = '$altstudyid'";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		WriteLog("[$sqlstring]");
		my %row = $result->fetchhash;
		$studyRowID = $row{'study_id'};
		$studyNum = $row{'study_num'};
	}
	else {
		$sqlstring = "SELECT * FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = $subjectRowID";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$studyNum = $result->numrows + 1;
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status, study_alternateid) values ($enrollmentRowID, $studyNum, '$Modality', '$StudyDateTime', '$StudyDesc', '$StudyOperator', '$StudyPerformingPhysician', '$StudySite', '$StudyInstitution', 'complete', '$altstudyid')";
		WriteLog("[$sqlstring]");
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$studyRowID = $result->insertid;
	}

	# get the next series number. never update a series, only insert new ones
	$sqlstring = "select * from mr_series where study_id = $studyRowID";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	$SeriesNumber = $result->numrows + 1;
	
	# create seriesRowID if it doesn't exist
	$sqlstring = "insert into mr_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_tr, series_te, series_flip, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, bold_reps, data_type, series_status, series_createdby) values ($studyRowID, '$SeriesDateTime', '$ProtocolName', '$SequenceName', '$SeriesNumber', '0', '0', '0', '$xpix', '$ypix', '$zpix', '0', '$xdim', '$ydim', '$zdim', $tdim, 'nifti', 'complete', 'parseincomingabide.pl')";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	$seriesRowID = $result->insertid;
				
	# copy the file to the archive, update db info
	WriteLog("$seriesRowID");
	
	# create data directory if it doesn't already exist
	my $outdir;
	$outdir = "$cfg{'archivedir'}/$subjectRealUID/$studyNum/$SeriesNumber/nifti";
	WriteLog("Creating path $outdir");
	mkpath($outdir, {mode => 0777});
	
	my $systemstring = "cp $datadir/* $outdir";
	WriteLog(`$systemstring`);
	#copy($filepath,"$outdir/$file");
	
	# get the size and number of the files and update the DB
	my ($dirsize, $numfiles) = GetDirectorySize($outdir);
	$sqlstring = "update mr_series set series_size = $dirsize, numfiles = $numfiles where mrseries_id = $seriesRowID";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);

	# create a thumbnail
	$systemstring = "slicer $outdir/*.nii.gz -a $cfg{'archivedir'}/$subjectRealUID/$studyNum/$SeriesNumber/thumb.png";
	WriteLog("$systemstring (" . `$systemstring` . ")");

	# change the permissions to 777 so the webpage can read/write the directories
	my $origDir = getcwd;
	chdir("$cfg{'archivedir'}");
	$systemstring = "chmod -Rf 777 $subjectRealUID";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	# change back to original directory before leaving
	chdir($origDir);
	
	# copy everything to the backup directory
	my $backdir = "$cfg{'backupdir'}/$subjectRealUID/$studyNum/$SeriesNumber";
	mkpath($backdir, {mode => 0777});
	$systemstring = "cp -R $cfg{'archivedir'}/$subjectRealUID/$studyNum/$SeriesNumber/* $backdir";
	WriteLog("$systemstring (" . `$systemstring` . ")");

	return 0;
}

# ----------------------------------------------------------
# --------- CreateSubjectID --------------------------------
# ----------------------------------------------------------
sub CreateSubjectID {
	my $newID;
	
	my $C1 = int(rand(10));
	my $C2 = int(rand(10));
	my $C3 = int(rand(10));
	my $C4 = int(rand(10));
	
	# ASCII codes 65 through 90 are upper case letters
	my $C5 = chr(int(rand(25)) + 65);
	my $C6 = chr(int(rand(25)) + 65);
	my $C7 = chr(int(rand(25)) + 65);
	
	$newID = "S$C1$C2$C3$C4$C5$C6$C7";
	return $newID;
}


# -------------------------------------------------------------------
# ----------- DisplayUsage ------------------------------------------
# -------------------------------------------------------------------
sub DisplayUsage {
	my ($str) = @_;
	
	print "
	
	parseincomingabide.pl usage:
	
    -c, --costcenter         * 6-digit cost center
    -l, --label              * prepended to subject name, assists in searching
    -d, --datadir            * path to the unencrypted, unzipped data
    -s, --studysite          * name for the site, ex 'ONRC1'
	
    (* required)

    Example:
	    perl parseincomingabide.pl -c 888888 -l ABIDE -d /path/to/abide/data -s ONRC1

";	
}