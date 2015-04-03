#!/usr/bin/perl

# DONT FORGET TO CHANGE THE PROJECT INTO WHICH THE IMPORTED DICOMS ARE GOING!!!
# change it on line 417


# ------------------------------------------------------------------------------
# NIDB parsedicomimport.pl
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
use Cwd;

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
our $scriptname = "parsedicomimport";
our $lockfileprefix = "parsedicomimport";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently

# debugging
our $debug = 0;

# setup the directories to be used
#our $cfg{'logdir'}			= $config{logdir};			# where the log files are kept
#our $cfg{'lockdir'}		= $config{lockdir};			# where the lock files are
#our $cfg{'scriptdir'}		= $config{scriptdir};		# where this program and others are run from
#our $cfg{'incomingdir'}	= $config{importdir};		# uses IMPORTDIR, not INCOMINGDIR
#our $cfg{'archivedir'}		= $config{archivedir};		# this is the final place for zipped/recon/formatted subject directories
#our $cfg{'backupdir'}		= $config{backupdir};		# backup directory. all handled data is copied here
#our $cfg{'ftpdir'}			= $config{ftpdir};			# local FTP directory
#our $cfg{'mountdir'}		= $config{mountdir};		# directory in which all NFS directories are mounted
#our $cfg{'problemdir'}		= $config{problemdir};		# place to dump non-dicom, unreadble or corrupt dicom files


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
	my $x = DoParse();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoParse ----------------------------------------
# ----------------------------------------------------------
sub DoParse {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my %dicomfiles;
	my $ret = 0;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	# check if this module should be running now or not
	my $sqlstring = "select * from modules where module_name = '$scriptname' and module_isactive = 1";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows < 1) {
		WriteLog("Not supposed to be running right now");
		return 0;
	}
	# update the start time
	$sqlstring = "update modules set module_laststart = now(), module_status = 'running', module_numrunning = module_numrunning + 1 where module_name = '$scriptname'";
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);

	WriteLog("Connected to database");
	
	# ----- parse all files in /incoming -----
	opendir(DIR,$cfg{'incomingdir'}) || Error("Cannot open directory (1) $cfg{'incomingdir'}!\n");
	my @files = readdir(DIR);
	closedir(DIR);
	my $numfiles = $#files + 1;
	WriteLog("Found $numfiles dicom files");
	
	my $runningCount = 0;
	foreach my $file (@files) {
		$runningCount++;
		if ($runningCount%1000 == 0) {
			WriteLog("Processed $runningCount files...");
		}
		if ( ($file ne ".") && ($file ne "..") ) {
			# again, make sure this file still exists... another instance of the program may have altered it
			if (-e "$cfg{'incomingdir'}/$file") {
				my($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime,$blksize,$blocks) = stat("$cfg{'incomingdir'}/$file");
				my $todaydate = time;
				#print "now: $todaydate -- file:$mtime\n";
				
				if (-d "$cfg{'incomingdir'}/$file") { }
				else {
					chdir($cfg{'incomingdir'});
					my ($tags,$newfilename) = ParseDICOMFile($file);
					if ($tags != 0) {
						if (trim($tags->{'Warning'}) eq "Error reading DICOM file (corrupted? still being copied?)") {
							WriteLog("Dicom file [$file] corrupted or not valid DICOM syntax");
							#move("$cfg{'incomingdir'}/$file","$cfg{'problemdir'}/$file");
						}
						else {
							push @{ $dicomfiles{ trim($tags->{'InstitutionName'}) }{ trim($tags->{'StationName'}) }{ trim($tags->{'Modality'}) }{ trim($tags->{'PatientName'}) }{ trim($tags->{'PatientBirthDate'}) }{ trim($tags->{'PatientSex'}) }{ trim($tags->{'StudyDateTime'}) }{ trim($tags->{'SeriesNumber'}) }{ 'files' } } , $newfilename;
							# if any file is less than 2 minutes old, the series may still be being transferred, so ignore the whole series
							if ( ($todaydate - $mtime) < 120 ) {
								$dicomfiles{ trim($tags->{'InstitutionName'}) }{ trim($tags->{'StationName'}) }{ trim($tags->{'Modality'}) }{ trim($tags->{'PatientName'}) }{ trim($tags->{'PatientBirthDate'}) }{ trim($tags->{'PatientSex'}) }{ trim($tags->{'StudyDateTime'}) }{ trim($tags->{'SeriesNumber'}) }{ 'unfinished' } = 1;
							}
						}
					}
					else {
						WriteLog("File [$file] is most likely not a dicom file");
						move("$cfg{'incomingdir'}/$file","$cfg{'problemdir'}/$file");
					}
				}
			}
		}
	}
	#WriteLog(Dumper(%dicomfiles));
	
	#my @things = keys %dicomfiles;
	#if ($#things > 0) {
	my $i = 0;
	# go through the %dicomfiles hash by SERIES. ignore any series that are unfinished
	foreach my $institute (keys %dicomfiles) {
		foreach my $equip (keys %{$dicomfiles{$institute}}) {
			foreach my $modality (keys %{$dicomfiles{$institute}{$equip}}) {
				foreach my $patient (keys %{$dicomfiles{$institute}{$equip}{$modality}}) {
					foreach my $dob (keys %{$dicomfiles{$institute}{$equip}{$modality}{$patient}}) {
						foreach my $sex (keys %{$dicomfiles{$institute}{$equip}{$modality}{$patient}{$dob}}) {
							foreach my $date (keys %{$dicomfiles{$institute}{$equip}{$modality}{$patient}{$dob}{$sex}}) {
								foreach my $series (keys %{$dicomfiles{$institute}{$equip}{$modality}{$patient}{$dob}{$sex}{$date}}) {
									$i++;
									if (!defined($dicomfiles{$institute}{$equip}{$modality}{$patient}{$dob}{$sex}{$date}{$series}{'unfinished'})) {
										my @files = @{ $dicomfiles{$institute}{$equip}{$modality}{$patient}{$dob}{$sex}{$date}{$series}{'files'} };
										my $size = $#files + 1;
										WriteLog("$institute->$equip->$modality->$patient->$dob->$sex->$date->$series: $size files");

										# check if the backup directory is mounted. don't process anything if it isn't
										#my $systemstring = "df -t nfs | grep dicom";
										#if (trim(`$systemstring`) eq "") {
										#	WriteLog("/dicombackup directory is not mounted. Leaving program");
										#	exit(0);
										#}

										# we know these files are part of a complete series
										# unique to the institution, equipment, modality, patient, DOB, sex, studydatetime, series...
										# so send them forth
										InsertSeries(@files);
									}
									else {
										WriteLog("$institute->$patient->$dob->$sex->$date->$series: incomplete");
									}
								}
							}
						}
					}
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
	
	# update the stop time
	$sqlstring = "update modules set module_laststop = now(), module_status = 'stopped', module_numrunning = module_numrunning - 1 where module_name = '$scriptname'";
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	
	return $ret;
}


# ----------------------------------------------------------
# --------- ParseDICOMFile ---------------------------------
# ----------------------------------------------------------
sub ParseDICOMFile {
	my ($file) = @_;
	
	#print "Parsing $file\n";
	if ($file !~ /\.dcm$/) {
		WriteLog("Renaming to $file.dcm");
		rename $file, "$file.dcm";
		$file = "$file.dcm";
	}
	
	# check if its really a dicom file...
	my $type = Image::ExifTool::GetFileType($file);
	if ($type ne "DICM") {
		return (0,0);
	}
	
	# get DICOM tags
	my $exifTool = new Image::ExifTool;
	my $tags = $exifTool->ImageInfo($file);
	
	if (defined($tags->{'Error'})) {
		return (0,0);
	}

	# some images may not have a series date/time, so substitute the studyDateTime for seriesDateTime
	if ((!defined($tags->{'StudyDate'})) || ($tags->{'StudyDate'} eq "") ) {
		$tags->{'StudyDate'} = CreateMySQLDate();
	}
	else {
		$tags->{'StudyDate'} =~ s/:/\-/g;
	}
	
	if ((!defined($tags->{'SeriesDate'})) || ($tags->{'SeriesDate'} eq "") ) {
		$tags->{'SeriesDate'} = $tags->{'StudyDate'};
	}
	else {
		$tags->{'SeriesDate'} =~ s/:/\-/g;
	}
	
	if ((!defined($tags->{'SeriesTime'})) || ($tags->{'SeriesTime'} eq "") ) { $tags->{'SeriesTime'} = $tags->{'StudyTime'}; }
	
	$tags->{'StudyDateTime'} = $tags->{'StudyDate'} . " " . $tags->{'StudyTime'};
	$tags->{'SeriesDateTime'} = $tags->{'SeriesDate'} . " " . $tags->{'SeriesTime'};
	
	# check for other undefined or blank fields
	if ((!defined($tags->{'PatientSex'})) || ($tags->{'PatientSex'} eq "")) { $tags->{'PatientSex'} = "U"; }
	if ((!defined($tags->{'PatientBirthDate'})) || ($tags->{'PatientBirthDate'} eq "")) { $tags->{'PatientBirthDate'} = "1776-07-04"; }
	if ((!defined($tags->{'StationName'})) || ($tags->{'StationName'} eq "")) { $tags->{'StationName'} = "Unknown"; }
	if ((!defined($tags->{'InstitutionName'})) || ($tags->{'InstitutionName'} eq "")) { $tags->{'InstitutionName'} = "Unknown"; }
	if ((!defined($tags->{'SeriesNumber'})) || (trim($tags->{'SeriesNumber'}) eq "")) {
		my $timestamp = $tags->{'SeriesTime'};
		$timestamp =~ s/://g;
		$timestamp =~ s/\-//g;
		$timestamp =~ s/ //g;
		#$timestamp =~ s/[a-z][A-Z]//g;
		$tags->{'SeriesNumber'} = $timestamp;
	}
	
	return ($tags, $file);
}


# ----------------------------------------------------------
# --------- InsertSeries -----------------------------------
# ----------------------------------------------------------
sub InsertSeries {
	my (@files) = @_;
	
	my $sqlstring;
	my $subjectRowID;
	my $subjectRealUID;
	my $projectRowID;
	my $enrollmentRowID;
	my $studyRowID;
	my $seriesRowID;
	my $costcenter;
	my $study_num;
	
	WriteLog("Parsing $files[0]");
	
	# get DICOM tags from first file of this series
	my $type = Image::ExifTool::GetFileType($files[0]);
	if ($type ne "DICM") {
		WriteLog("This is not a DICM file");
	}
	my $exifTool = new Image::ExifTool;
	my $info = $exifTool->ImageInfo($files[0]);

	my $InstitutionName = EscapeMySQLString(trim($info->{'InstitutionName'}));
	my $InstitutionAddress = EscapeMySQLString(trim($info->{'InstitutionAddress'}));
	my $Modality = EscapeMySQLString(trim($info->{'Modality'}));
	my $StationName = EscapeMySQLString(trim($info->{'StationName'}));
	my $Manufacturer = EscapeMySQLString(trim($info->{'Manufacturer'}));
	my $ManufacturersModelName = EscapeMySQLString(trim($info->{'ManufacturersModelName'}));
	my $OperatorsName = EscapeMySQLString(trim($info->{'OperatorsName'}));
	my $PatientID = EscapeMySQLString(trim($info->{'PatientID'}));
	my $PatientBirthDate = trim($info->{'PatientBirthDate'});
	my $PatientName = EscapeMySQLString(trim($info->{'PatientName'}));
	my $PatientSex = trim($info->{'PatientSex'});
	my $PatientWeight = trim($info->{'PatientWeight'});
	my $PerformingPhysiciansName = EscapeMySQLString(trim($info->{'PerformingPhysicianName'}));
	my $ProtocolName = EscapeMySQLString(trim($info->{'ProtocolName'}));
	my $SeriesDate = trim($info->{'SeriesDate'});
	my $SeriesNumber = trim($info->{'SeriesNumber'});
	my $SeriesTime = trim($info->{'SeriesTime'});
	my $StudyDate = trim($info->{'StudyDate'});
	my $StudyDescription = EscapeMySQLString(trim($info->{'StudyDescription'}));
	my $SeriesDescription = EscapeMySQLString(trim($info->{'SeriesDescription'}));
	my $StudyTime = trim($info->{'StudyTime'});
	my $Rows = trim($info->{'Rows'});
	my $Columns = trim($info->{'Columns'});
	my $AccessionNumber = trim($info->{'AccessionNumber'});
	my $SliceThickness = trim($info->{'SliceThickness'});
	my $PixelSpacing = trim($info->{'PixelSpacing'});
	my $SequenceName = EscapeMySQLString(trim($info->{'SequenceName'}));

	# MR specific tags
	my $MagneticFieldStrength = trim($info->{'MagneticFieldStrength'});
	my $RepetitionTime = trim($info->{'RepetitionTime'});
	my $FlipAngle = trim($info->{'FlipAngle'});
	my $EchoTime = trim($info->{'EchoTime'});
	my $AcquisitionMatrix = trim($info->{'AcquisitionMatrix'});
	
	# CT specific tags
	my $ContrastBolusAgent = trim($info->{'ContrastBolusAgent'});
	my $BodyPartExamined = trim($info->{'BodyPartExamined'});
	my $ScanOptions = trim($info->{'ScanOptions'});
	my $KVP = trim($info->{'KVP'});
	my $DataCollectionDiameter = trim($info->{'DataCollectionDiameter'});
	my $ContrastBolusRoute = trim($info->{'ContrastBolusRoute'});
	my $RotationDirection = trim($info->{'RotationDirection'});
	my $ExposureTime = trim($info->{'ExposureTime'});
	my $XRayTubeCurrent = trim($info->{'XRayTubeCurrent'});
	my $FilterType = trim($info->{'FilterType'});
	my $GeneratorPower = trim($info->{'GeneratorPower'});
	my $ConvolutionKernel = trim($info->{'ConvolutionKernel'});
	
	# fix some of the fields to be amenable to the DB
	if ($Modality eq "") { $Modality = 'OT'; }
	$StudyDate =~ s/:/\-/g;
	$SeriesDate =~ s/:/\-/g;
	my $StudyDateTime = $info->{'StudyDateTime'} = $StudyDate . " " . $StudyTime;
	my $SeriesDateTime = $info->{'SeriesDateTime'} = $SeriesDate . " " . $SeriesTime;
	my ($pixelX, $pixelY) = split(/\\/, $PixelSpacing);
	my ($mat1, $mat2, $mat3, $mat4) = split(/ /, $AcquisitionMatrix);
	if (($SeriesNumber eq '') || (!defined($SeriesNumber))) {
	#if ($SeriesNumber eq "") {
		my $timestamp = $SeriesTime;
		$timestamp =~ s/://g;
		$timestamp =~ s/\-//g;
		$timestamp =~ s/ //g;
		#$timestamp =~ s/[a-z][A-Z]//g;
		$SeriesNumber = $timestamp;
		if ($SeriesNumber eq '') {
			$SeriesNumber = 0;
		}
	}
	
	if (!defined($pixelX)) { $pixelX = 0;}
	if (!defined($pixelY)) { $pixelY = 0;}
	
	if (($PatientBirthDate eq "") || ($PatientBirthDate eq "0000-00-00") || ($PatientBirthDate eq "XXXXXXXX") || ($PatientBirthDate eq "anonymous")) { $PatientBirthDate = "1000-01-01"; }

	WriteLog("Birthdate: [$PatientBirthDate]");
	
	# extract the costcenter
	if ( $StudyDescription =~ /clinical/i ) {
		$costcenter = "888888";
	}
	elsif ( $StudyDescription =~ /\((.*?)\)/ )
	{
		$costcenter = $1;
	}
	else {
		$costcenter = $StudyDescription;
	}
	
	# CHANGE PROJECT # HERE (comment the line to allow the DICOM file to determine the project)
	$costcenter = "999500";
	
	WriteLog("$PatientID - $StudyDescription");
	
	# check if project and subject exist
	$sqlstring = "select (SELECT count(*) FROM `projects` WHERE project_costcenter = '$costcenter') 'projectcount', (SELECT count(*) FROM `subjects` WHERE uid = '$PatientID') 'subjectcount'";
	WriteLog("[$sqlstring]");
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	my %row = $result->fetchhash;
	my $projectcount = $row{'projectcount'};
	my $subjectcount = $row{'subjectcount'};
	
	# if subject doesn't exist, create the subject
	if ($subjectcount < 1) {

		# search for an existing subject by name, dob, gender
		$sqlstring = "select subject_id, uid from subjects where name like '%$PatientName%' and gender = '$PatientSex' and birthdate = '$PatientBirthDate'";
		WriteLog("[$sqlstring]");
		my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$subjectRealUID = uc($row{'uid'});
			$subjectRowID = $row{'subject_id'};
		}
		# if it couldn't be found, create a new subject
		else {
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
			$sqlstring = "insert into subjects (name, birthdate, gender, weight, uid, uuid) values ('$PatientName', '$PatientBirthDate', '$PatientSex', '$PatientWeight', '$subjectRealUID', ucase(md5(concat(RemoveNonAlphaNumericChars('$PatientName'), RemoveNonAlphaNumericChars('$PatientBirthDate'),RemoveNonAlphaNumericChars('$PatientSex')))) )";
			WriteLog("[$sqlstring]");
			$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
			$subjectRowID = $result->insertid;
		}
	}
	else {
		# get the existing subject ID
		$sqlstring = "select subject_id from subjects where uid = '$PatientID'";
		my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		my %row = $result->fetchhash;
		$subjectRowID = $row{'subject_id'};
		$subjectRealUID = uc($PatientID);
	}
	
	# if project doesn't exist, use the generic project
	if ($projectcount < 1) {
		$costcenter = "999999";
	}
	
	# get the projectRowID
	$sqlstring = "select project_id from projects where project_costcenter = '$costcenter'";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "] " . $db->errmsg(),$sqlstring);
	%row = $result->fetchhash;
	$projectRowID = $row{'project_id'};
	
	# check if the subject is enrolled in the project
	$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectRowID and project_id = $projectRowID";
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		WriteLog("[$sqlstring]");
		my %row = $result->fetchhash;
		$enrollmentRowID = $row{'enrollment_id'};
	}
	else {
		# create enrollmentRowID if it doesn't exist
		$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
		WriteLog("[$sqlstring]");
		my $result2 = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		$enrollmentRowID = $result2->insertid;
	}
	
	# now determine if this study exists or not...
	# basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
	# also checks the accession number against the study_num to see if this study was pre-registered
	$sqlstring = "select study_id, study_num from studies where enrollment_id = $enrollmentRowID and (study_num = '$AccessionNumber' or (study_datetime = '$StudyDateTime' and study_modality = '$Modality' and study_site = '$StationName'))";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$studyRowID = $row{'study_id'};
		$study_num = $row{'study_num'};
		
		$sqlstring = "update studies set study_modality = '$Modality', study_datetime = '$StudyDateTime', study_desc = '$StudyDescription', study_operator = '$OperatorsName', study_performingphysician = '$PerformingPhysiciansName', study_site = '$StationName', study_institution = '$InstitutionName - $InstitutionAddress', study_status = 'complete' where study_id = $studyRowID";
		WriteLog("[$sqlstring]");
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		
	}
	else {
		# create studyRowID if it doesn't exist
		$sqlstring = "SELECT max(a.study_num) 'study_num' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id  WHERE b.subject_id = $subjectRowID";
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		%row = $result->fetchhash;
		$study_num = $row{'study_num'} + 1;
		#$study_num = $result->numrows + 1;
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status, study_createdby) values ($enrollmentRowID, $study_num, '$PatientID', '$Modality', '$StudyDateTime', '$StudyDescription', '$OperatorsName', '$PerformingPhysiciansName', '$StationName', '$InstitutionName - $InstitutionAddress', 'complete', 'parsedicomimport.pl')";
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		$studyRowID = $result->insertid;
	}

	# gather series information
	my $boldreps = 1;
	my $numfiles = $#files + 1;
	my $zsize;
	my $mrtype = "structural";
	
	# check if its an EPI sequence, but not a perfusion sequence
	if (($SequenceName =~ m/^epfid2d1_64/) || ($SequenceName =~ m/^epfid2d1_128/)) {
		if (($ProtocolName =~ /perfusion/i) && ($ProtocolName =~ /ep2d_perf_tra/i)) { }
		else {
			$mrtype = "epi";
			# get the bold reps and attempt to get the z size
			$boldreps = $numfiles;
			
			# this method works sometimes
			if (($mat1 > 0) && ($mat4 > 0)) {
				$zsize = ($Rows/$mat1)*($Columns/$mat4); # example (384/64)*(384/64) = 6*6 = 36 possible slices in a mosaic
			}
			else {
				$zsize = $numfiles;
			}
		}
	}
	else {
		$zsize = $numfiles;
	}
	
	# insert or update the series based on modality
	my $dbModality;
	if (uc($Modality) eq "MR") {
		$dbModality = "mr";
		$sqlstring = "select mrseries_id from mr_series where study_id = $studyRowID and series_num = $SeriesNumber";
		WriteLog("[$sqlstring]");
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$seriesRowID = $row{'mrseries_id'};
			
			$sqlstring = "update mr_series set series_datetime = '$SeriesDateTime',series_desc = '$ProtocolName', series_sequencename = '$SequenceName',series_tr = '$RepetitionTime', series_te = '$EchoTime',series_flip = '$FlipAngle', series_spacingx = '$pixelX',series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', series_fieldstrength = '$MagneticFieldStrength', img_rows = '$Rows', img_cols = '$Columns', img_slices = '$zsize', bold_reps = '$boldreps', numfiles = '$numfiles', series_status = 'complete' where mrseries_id = $seriesRowID";
			$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		}
		else {
			
			# create seriesRowID if it doesn't exist
			$sqlstring = "insert into mr_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_tr, series_te, series_flip, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, bold_reps, numfiles, data_type, series_status, series_createdby) values ($studyRowID, '$SeriesDateTime', '$ProtocolName', '$SequenceName', '$SeriesNumber', '$RepetitionTime', '$EchoTime', '$FlipAngle', '$pixelX', '$pixelY', '$SliceThickness', '$MagneticFieldStrength', '$Rows', '$Columns', '$zsize', $boldreps, '$numfiles', 'dicom', 'complete', 'parsedicomimport.pl')";
			#print "[$sqlstring]\n";
			my $result2 = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
			$seriesRowID = $result2->insertid;
		}
	}
	elsif (uc($Modality) eq "CT") {
		$dbModality = "ct";
		$sqlstring = "select ctseries_id from ct_series where study_id = $studyRowID and series_num = $SeriesNumber";
		WriteLog("[$sqlstring]");
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$seriesRowID = $row{'ctseries_id'};
			
			$sqlstring = "update ct_series set series_datetime = '$SeriesDateTime', series_desc = '$SeriesDescription', series_protocol = '$ProtocolName', series_spacingx = '$pixelX', series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', series_imgrows = '$Rows', series_imgcols = '$Columns', series_imgslices = '$zsize', series_numfiles = '$numfiles', series_contrastbolusagent = '$ContrastBolusAgent', series_bodypartexamined = '$BodyPartExamined', series_scanoptions = '$ScanOptions', series_kvp = '$KVP', series_datacollectiondiameter = '$DataCollectionDiameter', series_contrastbolusroute = '$ContrastBolusRoute', series_rotationdirection = '$RotationDirection', series_exposuretime = '$ExposureTime', series_xraytubecurrent = '$XRayTubeCurrent', series_filtertype = '$FilterType', series_generatorpower = '$GeneratorPower', series_convolutionkernel = '$ConvolutionKernel', series_status = 'complete' where ctseries_id = $seriesRowID";
			$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		}
		else {
			# create seriesRowID if it doesn't exist
			$sqlstring = "insert into ct_series ( study_id, series_datetime, series_desc, series_protocol, series_num, series_contrastbolusagent, series_bodypartexamined, series_scanoptions, series_kvp, series_datacollectiondiameter, series_contrastbolusroute, series_rotationdirection, series_exposuretime, series_xraytubecurrent, series_filtertype,series_generatorpower, series_convolutionkernel, series_spacingx, series_spacingy, series_spacingz, series_imgrows, series_imgcols, series_imgslices, numfiles, series_datatype, series_status, series_createdby
			) values (
			$studyRowID, '$SeriesDateTime', '$SeriesDescription', '$ProtocolName', '$SeriesNumber', '$ContrastBolusAgent', '$BodyPartExamined', '$ScanOptions', '$KVP', '$DataCollectionDiameter', '$ContrastBolusRoute', '$RotationDirection', '$ExposureTime', '$XRayTubeCurrent', '$FilterType', '$GeneratorPower', '$ConvolutionKernel', '$pixelX', '$pixelY', '$SliceThickness', '$Rows', '$Columns', '$zsize', '$numfiles', 'dicom', 'complete', 'parsedicomimport.pl')";
			#print "[$sqlstring]\n";
			my $result2 = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
			$seriesRowID = $result2->insertid;
		}
	}
	else {
		# this is the catch all for modalities which don't have a table in the database
		$dbModality = "ot";
		$sqlstring = "select otseries_id from ot_series where study_id = $studyRowID and series_num = $SeriesNumber";
		WriteLog("[$sqlstring]");
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$seriesRowID = $row{'otseries_id'};
			
			$sqlstring = "update ot_series set series_datetime = '$SeriesDateTime', series_desc = '$ProtocolName', series_sequencename = '$SequenceName', series_spacingx = '$pixelX',series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', img_rows = '$Rows', img_cols = '$Columns', img_slices = '$zsize', numfiles = '$numfiles', series_status = 'complete' where otseries_id = $seriesRowID";
			$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		}
		else {
			
			# create seriesRowID if it doesn't exist
			$sqlstring = "insert into ot_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols, img_slices, numfiles, modality, data_type, series_status, series_createdby) values ($studyRowID, '$SeriesDateTime', '$ProtocolName', '$SequenceName', '$SeriesNumber', '$pixelX', '$pixelY', '$SliceThickness', '$Rows', '$Columns', '$zsize', '$numfiles', '$Modality', 'dicom', 'complete', 'parsedicomimport.pl')";
			#print "[$sqlstring]\n";
			my $result2 = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
			$seriesRowID = $result2->insertid;
		}
	}
	
	# copy the file to the archive, update db info
	WriteLog("$seriesRowID");
	
	# create data directory if it doesn't already exist
	my $outdir = "$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/dicom";
	WriteLog("$outdir");
	mkpath($outdir, {mode => 0777});
	
	# rename the files and move them to the archive
	# SubjectUID_EnrollmentRowID_SeriesNum_FileNum
	# S1234ABC_SP1_5_0001.dcm
	
	# check if there are .dcm files already in the archive
	my $cwd = getcwd;
	chdir($outdir);
	my @existingdcmfiles = <*.dcm>;
	chdir($cwd);
	@existingdcmfiles = sort @existingdcmfiles;
	my $numexistingdcmfiles = @existingdcmfiles;
	if ($numexistingdcmfiles > 0) {
	
		# check all files to see if its the same study datetime, patient name, dob, gender, series #
		# if anything is different, move the file to a UID/Study/Series/dicom/existing directory
		
		# if they're all the same, consolidate the files into one list of new and old, remove duplicates
		WriteLog("There are $numexistingdcmfiles existing files in $outdir. Renaming accordingly");
		
		# rename the existing files to make them unique
		foreach my $file (sort @existingdcmfiles) {
			my $tags2 = $exifTool->ImageInfo("$outdir/$file");
			my $SliceNumber = trim($tags2->{'AcquisitionNumber'});
			my $InstanceNumber = trim($tags2->{'InstanceNumber'});
			my $SliceLocation = trim($tags2->{'SliceLocation'});
			my $AcquisitionTime = trim($tags2->{'AcquisitionTime'});
			my $ContentTime = trim($tags2->{'ContentTime'});
			$AcquisitionTime =~ s/://g;
			$AcquisitionTime =~ s/\.//g;
			$ContentTime =~ s/://g;
			$ContentTime =~ s/\.//g;
			
			if (!defined($SliceNumber)) { $SliceNumber = 0;}
			if (!defined($InstanceNumber)) { $InstanceNumber = 0;}
			# sort by slice #, or instance #
			#WriteLog("$file: SliceNumber: $SliceNumber, InstanceNumber: $InstanceNumber, SliceLocation: $SliceLocation, Acquisition Time: $AcquisitionTime");
			
			my $newname = $subjectRealUID . "_$study_num" . "_$SeriesNumber" . "_" . sprintf('%05d',$SliceNumber) . "_" . sprintf('%05d',$InstanceNumber) . "_$AcquisitionTime" . "_$ContentTime.dcm";
			WriteLog("Renaming [$file] to [$newname]");
			
			move("$outdir/$file","$outdir/$newname");
		}
		
	}
	
	# renumber to make unique
	foreach my $file (sort @files) {
		my $tags = $exifTool->ImageInfo($file);
		my $SliceNumber = trim($tags->{'AcquisitionNumber'});
		my $InstanceNumber = trim($tags->{'InstanceNumber'});
		my $SliceLocation = trim($tags->{'SliceLocation'});
		my $AcquisitionTime = trim($tags->{'AcquisitionTime'});
		my $ContentTime = trim($tags->{'ContentTime'});
		$AcquisitionTime =~ s/://g;
		$AcquisitionTime =~ s/\.//g;
		$ContentTime =~ s/://g;
		$ContentTime =~ s/\.//g;
		
		if (!defined($SliceNumber)) { $SliceNumber = 0;}
		if (!defined($InstanceNumber)) { $InstanceNumber = 0;}
		
		# sort by slice #, or instance #
		#WriteLog("$file: SliceNumber: $SliceNumber, InstanceNumber: $InstanceNumber, SliceLocation: $SliceLocation, Acquisition Time: $AcquisitionTime");
		
		my $newname = $subjectRealUID . "_$study_num" . "_$SeriesNumber" . "_" . sprintf('%05d',$SliceNumber) . "_" . sprintf('%05d',$InstanceNumber) . "_$AcquisitionTime" . "_$ContentTime.dcm";
		#print "  [$newname]";

		# check if a file with the same name already exists
		if (-e "$outdir/$newname") {
			WriteLog("File [$outdir/$newname] is duplicate. Discarding $file");
			# if so, rename it randomly and dump it to a duplicates directory
			#unless (-d "$outdir/duplicates") {
			#	mkdir "$outdir/duplicates";
			#}
			unlink($file);
			#move($file, "$outdir/duplicates/" . GenerateRandomString(20) . "$newname");
		}
		else {
			move($file,"$outdir/$newname");
		}
	}
	
	# get the size of the dicom files and update the DB
	my $dirsize;
	($dirsize, $numfiles) = GetDirectorySize($outdir);
	$sqlstring = "update " . lc($dbModality) . "_series set series_size = $dirsize, numfiles = $numfiles where " . lc($dbModality) . "series_id = $seriesRowID";
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);

	# create a thumbnail of the middle slice in the dicom directory (after getting the size, so the thumbnail isn't included in the size)
	CreateThumbnail("$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber", $mrtype);

	# change the permissions to 777 so the webpage can read/write the directories
	#my $origDir = getcwd;
	#chdir("$cfg{'archivedir'}");
	#WriteLog("Current directory: " . getcwd);
	my $systemstring = "chmod -Rf 777 $cfg{'archivedir'}/$subjectRealUID";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	# change back to original directory before leaving
	#WriteLog("Changing back to $origDir");
	#chdir($origDir);
	WriteLog("Finished changing permissions on $cfg{'archivedir'}/$subjectRealUID");
	
	# copy everything to the backup directory
	my $backdir = "$cfg{'backupdir'}/$subjectRealUID/$study_num/$SeriesNumber";
	if (-d $backdir) {
		WriteLog("Directory [$backdir] already exists");
	}
	else {
		WriteLog("Directory [$backdir] does not exist. About to create it...");
		mkpath($backdir, { verbose => 1, mode => 0777} );
		WriteLog("Finished creating [$backdir]");
	}
	WriteLog("About to copy to the backup directory");
	$systemstring = "cp -R $cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/* $backdir";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	WriteLog("Finished copying to the backup directory");
}


# ----------------------------------------------------------
# --------- CreateThumbnail --------------------------------
# ----------------------------------------------------------
sub CreateThumbnail {
	my ($dir, $type) = @_;

	# print the ImageMagick version
	my $systemstring = "/usr/bin/./convert --version";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	$systemstring = "convert --version";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	$systemstring = "/usr/local/bin/./convert --version";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	
	my $origDir = getcwd;
	
	# get list of dicom files
	chdir("$dir/dicom");
	my @dcmfiles = <*.dcm>;
	
	@dcmfiles = sort @dcmfiles;
	my $numdcmfiles = @dcmfiles;
	my $dcmfile = $dcmfiles[int($numdcmfiles/2)];
	my $outfile = "$dir/thumb.png";
	$systemstring = "/usr/local/bin/./convert -normalize $dir/dicom/$dcmfile $outfile";
	WriteLog("$systemstring (" . `$systemstring` . ")");

	if ($type eq "epi") {
		my $systemstring = "/usr/local/bin/./convert -crop 64x64+256+64\\! -fill white -pointsize 10 -annotate +45+62 '%p' +map -delay 10 -loop 0 *.dcm $dir/thumb.gif";
		WriteLog("$systemstring (" . `$systemstring` . ")");
	}
	if ($type eq "structural") {
		my $systemstring = "/usr/local/bin/./convert -resize 50% -fill white -pointsize 10 -annotate +2+12 '%p' +map -delay 20 -loop 0 *.dcm $dir/thumb.gif";
		WriteLog("$systemstring (" . `$systemstring` . ")");
	}
	
	# change back to original directory before leaving
	chdir($origDir);
	
	return $outfile;
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
