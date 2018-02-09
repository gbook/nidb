#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB parsedicomnew.pl
# Copyright (C) 2004 - 2018
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
use String::CRC32;
use Date::Manip;
use Scalar::Util qw(looks_like_number);

require 'nidbroutines.pl';
our %cfg;
LoadConfig();

our $db;

# script specific information
our $scriptname = "parsedicom";
our $lockfileprefix = "parsedicom";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently

# debugging
our $debug = 0;

# turn on auto flushing (for flushing the console and file buffers)
$|++;
use IO::Handle;

# ------------- end variable declaration --------------------------------------
# -----------------------------------------------------------------------------
	
# no idea why, but perl is buffering output to the screen, and these 3 statements turn off buffering
my $old_fh = select(STDOUT);
$| = 1;
select($old_fh);

# check if this program can run or not
if (CheckNumLockFiles($lockfileprefix, $cfg{'lockdir'}) >= $numinstances) {
	print "Can't run, too many of me already running\n";
	exit(0);
}
else {
	my $logfilename;
	($lockfile, $logfilename) = CreateLockFile($lockfileprefix, $cfg{'lockdir'}, $numinstances);
	$logfilename = "$cfg{'logdir'}/$scriptname" . CreateLogDate() . ".log";
	open $log, '> ', $logfilename;
	$log->autoflush;
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
	if (!ModuleCheckIfActive($scriptname, $db)) {
		WriteLog("Not supposed to be running right now");
		print "*** Module not enabled. Enable this module on the NiDB website to allow it to run ***";
		return 0;
	}
	
	# update the start time
	ModuleDBCheckIn($scriptname, $db);
	ModuleRunningCheckIn($scriptname, $db);

	WriteLog("Connected to database");
	
	# before starting things off, delete any rows older than 30 days from the importlogs table
	#my $sqlstring = "delete from importlogs where importstartdate < date_sub(now(), interval 30 day)";
	#my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	
	# ----- parse all files in the main directory -----
	if (ParseDirectory($cfg{'incomingdir'}, '')) {
		$ret = 1;
	}
	
	# ----- parse the sub directories -----
	# if there's a sub directory, the directory name is a rowID from the import table,
	# which contains additional information about the files being imported, such as project and site
	opendir(DIR,$cfg{'incomingdir'}) || Error("Cannot open directory [" . $cfg{'incomingdir'} . "]\n");
	my @dirs = readdir(DIR);
	closedir(DIR);
	foreach my $dir (@dirs) {
		my $fulldir = $cfg{'incomingdir'} . "/$dir";
		if ((-d $fulldir) && ($dir ne '.') && ($dir ne '..')) {
			WriteLog("Checking on [$fulldir]");
			WriteLog("Calling ParseDirectory($fulldir,$dir)");
			if (ParseDirectory($fulldir, $dir)) {
				# rmdir will always fail if the directory contains files, so try to run it
				rmdir($fulldir) || WriteLog("rmdir($fulldir) failed, [$fulldir] probably not empty: $!");
				$ret = 1;
			}
			# check if this module should be running now or not
			if (!ModuleCheckIfActive($scriptname, $db)) {
				WriteLog("Not supposed to be running right now");
				ModuleDBCheckOut($scriptname, $db);
				return 1;
			}
		}
	}
	
	# update the stop time
	ModuleDBCheckOut($scriptname, $db);
	WriteLog("normal stop [ret code: $ret]");
	
	return $ret;
}


# ----------------------------------------------------------
# --------- ParseDirectory ---------------------------------
# ----------------------------------------------------------
sub ParseDirectory {
	my ($dir, $importRowID) = @_;

	WriteLog("********** Working on directory [$dir] with importRowID [$importRowID] **********");
	ModuleRunningCheckIn($scriptname, $db);
	
	my $archivereport = "";
	my $useImportFields = 0;
	my $importStatus = '';
	my $importModality = '';
	my $importDatatype = '';
	# if there is an importRowID, check to see how that thing is doing
	my $sqlstring = "select * from import_requests where importrequest_id = '$importRowID'";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		WriteLog("[$sqlstring]");
		my %row = $result->fetchhash;
		$importStatus = $row{'import_status'};
		$importModality = $row{'import_modality'};
		$importDatatype = $row{'import_datatype'};
		
		#if (($importStatus ne 'complete') && ($importStatus ne "")) {
		if (($importStatus eq 'complete') || ($importStatus eq "") || ($importStatus eq "received") || ($importStatus eq "error")) { }
		else {
			WriteLog("This import is not complete. Status is [$importStatus]. Skipping.");
			# cleanup so this import can continue another time
			$sqlstring = "update import_requests set import_status = '', import_enddate = now() where importrequest_id = '$importRowID'";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			return 0;
		}
	}
	
	$sqlstring = "update import_requests set import_status = 'archiving' where importrequest_id = '$importRowID'";
	WriteLog($sqlstring);
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	
	my %dicomfiles;
	my $ret = 0;
	my $i = 0;
	my $problem = 0;
	my $iscomplete = 0;

	# ----- parse all files in /incoming -----
	opendir(DIR,$dir) || Error("Cannot open directory [$dir]!\n");
	my @files = readdir(DIR);
	closedir(DIR);
	my $numfiles = $#files + 1;
	WriteLog("Found $numfiles files in $dir");
	
	my $runningCount = 0;
	foreach my $file (@files) {
		my $fsize = -s "$dir/$file";
		if ($fsize < 1) {
			WriteLog("Filesize of [$dir/$file] is [$fsize] bytes");
			next;
		}
		$runningCount++;
		#WriteLog("Processing [$runningCount] [$file]");
		if ($runningCount%1000 == 0) {
			WriteLog("Processed $runningCount files...");
			ModuleRunningCheckIn($scriptname, $db);
		}
		if ($runningCount >= 5000) {
			WriteLog("Reached [$runningCount] files, going to archive them now");
			last;
		}
		if ( ($file ne ".") && ($file ne "..") ) {
			#WriteLog("File is not a . or ..");
			# again, make sure this file still exists... another instance of the program may have altered it
			if (-e "$dir/$file") {
				#WriteLog("$dir/$file exists");
				my($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime,$blksize,$blocks) = stat("$dir/$file");
				my $todaydate = time;
				#WriteLog("now: $todaydate -- file:$mtime");

				if (-d "$dir/$file") { WriteLog("[$dir/$file] is a directory"); }
				else {
					#WriteLog("Working on [$dir/$file]");
					chdir($dir);
					if (lc($file) =~ /\.par$/) {
						WriteLog("Filetype is .par");
						my ($ret,$report) = InsertParRec($file, $importRowID);
						$archivereport .= $report;
						if ($ret ne "") {
							WriteLog("InsertParRec($file, $importRowID) failed: [$ret]");
							$ret = EscapeMySQLString(trim($ret));
							$archivereport = EscapeMySQLString(trim($archivereport));
							my $sqlstring = "insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values ('$file', 'PARREC', '$importRowID', now(), '[$ret], moving to the problem directory')";
							WriteLog($sqlstring);
							my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
							move("$dir/$file","$cfg{'problemdir'}/$file");
							# change the import status to reflect the error
							$sqlstring = "update import_requests set import_status = 'error', import_message = 'Problem inserting PAR/REC: $ret', import_enddate = now(), archivereport = '$archivereport' where importrequest_id = '$importRowID'";
							WriteLog($sqlstring);
							$result = SQLQuery($sqlstring, __FILE__, __LINE__);
						}
						else {
							$iscomplete = 1;
						}
						$i++;
					}
					elsif (lc($file) =~ /\.rec$/) { WriteLog("Filetype is a .rec"); }
					elsif ((lc($file) =~ /\.cnt$/) || (lc($file) =~ /\.3dd$/) || (lc($file) =~ /\.dat$/) || (lc($file) =~ /\.edf$/) || (lc($importModality eq 'eeg')) || (lc($importDatatype eq 'eeg')) || (lc($importModality eq 'et')) || (lc($importDatatype eq 'et')) ) {
						WriteLog("Filetype is one of [.cnt .3dd .dat .edf]");
						my ($ret,$report) = InsertEEG($file, $importRowID, uc($importDatatype));
						$archivereport .= $report;
						if ($ret ne "") {
							WriteLog("InsertEEG($file, $importRowID) failed: [$ret]");
							$ret = EscapeMySQLString(trim($ret));
							$archivereport = EscapeMySQLString(trim($archivereport));
							my $sqlstring = "insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values ('$file', '" . uc($importDatatype) . "', '$importRowID', now(), '[$ret], moving to the problem directory')";
							my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
							move("$dir/$file","$cfg{'problemdir'}/$file");
							# change the import status to reflect the error
							$sqlstring = "update import_requests set import_status = 'error', import_message = 'Problem inserting " . uc($importDatatype) . ": $ret', import_enddate = now(), archivereport = '$archivereport' where importrequest_id = '$importRowID'";
							WriteLog($sqlstring);
							$result = SQLQuery($sqlstring, __FILE__, __LINE__);
						}
						else {
							$iscomplete = 1;
						}
						$i++;
					}
					else {
						#WriteLog("Filetype is not specified, so maybe DICOM");
						my ($tags,$newfilename,$filetype) = ParseDICOMFile("$dir/$file");
						if ($tags != 0) {
							if (trim($tags->{'Warning'}) eq "Error reading DICOM file (corrupted? still being copied?)") {
								WriteLog("Dicom file [$file] corrupted or not valid DICOM syntax");
								my $sqlstring = "insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values ('$file', '$filetype', '$importRowID', now(), 'Corrupted DICOM or not valid DICOM file, moving to the problem directory')";
								my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
								move("$dir/$file","$cfg{'problemdir'}/$file");
							}
							else {
								push @{ $dicomfiles{ trim($tags->{'InstitutionName'}) }{ trim($tags->{'StationName'}) }{ trim($tags->{'Modality'}) }{ trim($tags->{'PatientName'}) }{ trim($tags->{'PatientBirthDate'}) }{ trim($tags->{'PatientSex'}) }{ trim($tags->{'StudyDateTime'}) }{ trim($tags->{'SeriesNumber'}) }{ 'files' } } , $newfilename;
								# if any file is less than 2 minutes old, the series may still be being transferred, so ignore the whole series
								if ( ($todaydate - $mtime) < 10 ) {
									$dicomfiles{ trim($tags->{'InstitutionName'}) }{ trim($tags->{'StationName'}) }{ trim($tags->{'Modality'}) }{ trim($tags->{'PatientName'}) }{ trim($tags->{'PatientBirthDate'}) }{ trim($tags->{'PatientSex'}) }{ trim($tags->{'StudyDateTime'}) }{ trim($tags->{'SeriesNumber'}) }{ 'unfinished' } = 1;
								}
							}
						}
						else {
							WriteLog("File [$dir/$file] - size [$fsize] is most likely not a dicom file");
							my $sqlstring = "insert into importlogs (filename_orig, fileformat, importgroupid, importstartdate, result) values ('$file', '$filetype', '$importRowID', now(), 'Not a DICOM file, moving to the problem directory')";
							my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
							#move("$dir/$file","$cfg{'problemdir'}/$file");
							if (trim($importRowID) != "") {
								$dir = EscapeMySQLString(trim($dir));
								$file = EscapeMySQLString(trim($file));
								$archivereport = EscapeMySQLString(trim($archivereport));
								$sqlstring = "update import_requests set import_status = 'error', import_message = '[$dir/$file] is not a valid DICOM file', import_enddate = now(), archivereport = '$archivereport' where importrequest_id = '$importRowID'";
								WriteLog($sqlstring);
								$result = SQLQuery($sqlstring, __FILE__, __LINE__);
							}
						}
					}
				}
			}
			else {
				WriteLog("$dir/$file does not exist");
			}
		}
		# check if this module should be running now or not
		if (!ModuleCheckIfActive($scriptname, $db)) {
			WriteLog("Not supposed to be running right now");
			return 1;
		}
	}

	$Data::Dumper::Indent = 1;
	$Data::Dumper::Sortkeys = 1;
	#WriteLog("dicomfiles: " . Dumper(\%dicomfiles) );
	
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

										# we know these files are part of a complete series
										# unique to the institution, equipment, modality, patient, DOB, sex, studydatetime, series...
										# so send them forth
										my ($ret,$report) = InsertDICOM($importRowID, @files);
										if ($ret ne "") {
											WriteLog("InsertDICOM($importRowID, ...) failed: [$ret]");
										}
										else {
											$iscomplete = 1;
										}
									}
									else {
										WriteLog("$institute->$patient->$dob->$sex->$date->$series: incomplete");
										# cleanup so this import can continue another time
										$sqlstring = "update import_requests set import_status = '', import_enddate = now(), archivereport = '$archivereport' where importrequest_id = '$importRowID'";
										WriteLog($sqlstring);
										$result = SQLQuery($sqlstring, __FILE__, __LINE__);
									}
								}
								ModuleRunningCheckIn($scriptname, $db);
								# check if this module should be running now or not
								if (!ModuleCheckIfActive($scriptname, $db)) {
									WriteLog("Not supposed to be running right now");
									# cleanup so this import can continue another time
									$sqlstring = "update import_requests set import_status = '', import_enddate = now(), archivereport = '$archivereport' where importrequest_id = '$importRowID'";
									WriteLog($sqlstring);
									$result = SQLQuery($sqlstring, __FILE__, __LINE__);
									return 1;
								}
							}
						}
					}
				}
			}
		}
	}
	
	if (($importRowID ne "") && ($iscomplete)) {
		my $uploaddir = "$cfg{'incomingdir'}/$importRowID";
		if (-d $uploaddir) {
			# delete the uploaded directory
			WriteLog("Attempting to remove [$uploaddir]");
			my $mode = (stat($uploaddir))[2];
			WriteLog(sprintf "permissions are %04o", $mode &07777);
			if (($uploaddir ne '.') && ($uploaddir ne '..') && ($uploaddir ne '') && ($uploaddir ne '/') && ($uploaddir ne '*') && ($importRowID ne '')) {
				#my $systemstring = "rm -rf $uploaddir";
				my $systemstring = "cd $uploaddir; find . -type d -empty -exec rmdir {} \;";
				WriteLog("We'll attempt to run this [$systemstring] to remove empty directories");
				WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
			}
		}
		$archivereport = EscapeMySQLString(trim($archivereport));
		$sqlstring = "update import_requests set import_status = 'archived', import_message = 'DICOM successfuly archived', import_enddate = now(), archivereport = '$archivereport' where importrequest_id = '$importRowID'";
		WriteLog($sqlstring);
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	}

	if ($i > 0) {
		WriteLog("Finished extracting data for [$dir]");
		$ret = 1;
	}
	else {
		WriteLog("Nothing to do for [$dir]");
		$ret = 0;
	}
	
	return $ret;
}


# ----------------------------------------------------------
# --------- ParseDICOMFile ---------------------------------
# ----------------------------------------------------------
sub ParseDICOMFile {
	my ($file) = @_;
	
	if ($file !~ /\.dcm$/) {
		rename $file, "$file.dcm";
		$file = "$file.dcm";
	}
	
	# get DICOM tags
	my $exifTool = new Image::ExifTool;
	my $tags = $exifTool->ImageInfo($file);
	my $type = $tags->{'FileType'};
	if (defined($type)) {
		#print "IsDICOMFile($f) filetype [$type]";
		if (($type ne 'DICOM') && ($type ne 'ACR')) {
			return (0,0,$type);
		}
	}
	else {
		return (0,0,'UNREADABLE');
	}

	if (defined($tags->{'Error'})) {
		return (0,0,'ERROR');
	}

	#WriteLog("Valid DICOM file [$file]");
	
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
	if ((!defined($tags->{'PatientBirthDate'})) || ($tags->{'PatientBirthDate'} eq "")) { $tags->{'PatientBirthDate'} = "0001-01-01"; }
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
	
	return ($tags, $file, 'DICOM');
}


# ----------------------------------------------------------
# --------- InsertDICOM -----------------------------------
# ----------------------------------------------------------
sub InsertDICOM {
	my ($importRowID, @files) = @_;

	my $report = "";
	
	$report .= WriteLog("----- Inside InsertDICOM() with [" . scalar @files . "] files -----") . "\n";
	
	# import log variables
	my ($IL_modality_orig, $IL_patientname_orig, $IL_patientdob_orig, $IL_patientsex_orig, $IL_stationname_orig, $IL_institution_orig, $IL_studydatetime_orig, $IL_seriesdatetime_orig, $IL_seriesnumber_orig, $IL_studydesc_orig, $IL_patientage_orig, $IL_modality_new, $IL_patientname_new, $IL_patientdob_new, $IL_patientsex_new, $IL_stationname_new, $IL_institution_new, $IL_studydatetime_new, $IL_seriesdatetime_new, $IL_seriesnumber_new, $IL_studydesc_new, $IL_seriesdesc_orig, $IL_protocolname_orig, $IL_patientage_new, $IL_subject_uid, $IL_study_num, $IL_enrollmentid, $IL_project_number, $IL_seriescreated, $IL_studycreated, $IL_subjectcreated, $IL_familycreated, $IL_enrollmentcreated, $IL_overwrote_existing);
	
	my $sqlstring;
	my $subjectRowID;
	my $subjectRealUID;
	my $familyRealUID;
	my $familyRowID;
	my $projectRowID;
	my $enrollmentRowID;
	my $studyRowID;
	my $seriesRowID;
	my $costcenter;
	my $study_num;

	my $importID = '';
	my $importInstanceID = '';
	my $importSiteID = '';
	my $importProjectID = '';
	my $importPermanent = '';
	my $importAnonymize = '';
	my $importMatchIDOnly = '';
	my $importUUID = '';
	my $importSeriesNotes = '';
	my $importAltUIDs = '';
	# if there is an importRowID, check to see how that thing is doing
	$sqlstring = "select * from import_requests where importrequest_id = '$importRowID'";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		$report .= WriteLog("[$sqlstring]") . "\n";
		my %row = $result->fetchhash;
		$importID = $row{'importrequest_id'};
		$importInstanceID = $row{'import_instanceid'};
		$importSiteID = $row{'import_siteid'};
		$importProjectID = $row{'import_projectid'};
		$importPermanent = $row{'import_permanent'};
		$importAnonymize = $row{'import_anonymize'};
		$importMatchIDOnly = $row{'import_matchidonly'};
		$importUUID = $row{'import_uuid'};
		$importSeriesNotes = $row{'import_seriesnotes'};
		$importAltUIDs = $row{'import_altuids'};
	}
	
	$report .= WriteLog("Parsing $files[0]") . "\n";
	my $fsize = -s $files[0];
	if (-e $files[0]) {
		$report .= WriteLog($files[0] . " exists, size [$fsize] bytes") . "\n";
	}
	else {
		$report .= WriteLog($files[0] . " does not exist!") . "\n";
	}
	
	# get DICOM tags from first file of this series
	my $type = Image::ExifTool::GetFileType($files[0]);
	if ($type ne "DICM") {
		$report .= WriteLog("This is not a DICM file") . "\n";
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
	my $PatientID = uc(EscapeMySQLString(trim($info->{'PatientID'})));
	my $PatientBirthDate = trim($info->{'PatientBirthDate'});
	my $PatientName = EscapeMySQLString(trim($info->{'PatientName'}));
	my $PatientSex = trim($info->{'PatientSex'});
	my $PatientWeight = trim($info->{'PatientWeight'});
	my $PatientSize = trim($info->{'PatientSize'});
	my $PatientAge = trim($info->{'PatientAge'});
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
	my $NumberOfTemporalPositions = trim($info->{'NumberOfTemporalPositions'});
	my $ImagesInAcquisition = trim($info->{'ImagesInAcquisition'});
	my $SequenceName = EscapeMySQLString(trim($info->{'SequenceName'}));
	my $ImageType = EscapeMySQLString(trim($info->{'ImageType'}));
	my $ImageComments = EscapeMySQLString(trim($info->{'ImageComments'}));

	# MR specific tags
	my $MagneticFieldStrength = trim($info->{'MagneticFieldStrength'});
	my $RepetitionTime = trim($info->{'RepetitionTime'});
	my $FlipAngle = trim($info->{'FlipAngle'});
	my $EchoTime = trim($info->{'EchoTime'});
	my $AcquisitionMatrix = trim($info->{'AcquisitionMatrix'});
	my $InPlanePhaseEncodingDirection = EscapeMySQLString(trim($info->{'InPlanePhaseEncodingDirection'}));
	my $InversionTime = trim($info->{'InversionTime'});
	my $PercentSampling = trim($info->{'PercentSampling'});
	my $PercentPhaseFieldOfView = trim($info->{'PercentPhaseFieldOfView'});
	my $PixelBandwidth = trim($info->{'PixelBandwidth'});
	my $SpacingBetweenSlices = trim($info->{'SpacingBetweenSlices'});
	my $EchoTrainLength = trim($info->{'EchoTrainLength'});
	
	# attempt to get the phase encode angle (In Plane Rotation) from the siemens CSA header
	my $PhaseEncodeAngle = "";
	my $PhaseEncodingDirectionPositive = "";
	my $dicomfile = $files[0];
	open(F, $dicomfile); # open the dicom file as a text file, since part of the CSA header is stored as text, not binary
	my @dcmlines=<F>;
	close(F);
	foreach my $line(@dcmlines) {	
		if ($line =~ /\]\.dInPlaneRot/i) {
			if (length($line) > 150) {
				my $idx = index($line, '.dInPlaneRot');
				$report .= WriteLog("Found dInPlaneRot line [$line]") . "\n";
				$line = substr($line,$idx,23);
			}
			my @values = split /\s*=\s*/, $line;
			my $value = trim($values[-1]);
			if ($value > 3.5) { $value = ""; }
			if ($value < -3.5) { $value = ""; }
			#print "[$line]: [$value]\n";
			$PhaseEncodeAngle = substr($value,0,8);
			last;
		}
	}
	$report .= WriteLog("PhaseEncodeAngle = [$PhaseEncodeAngle]") . "\n";
	
	# get the other part of the CSA header, the PhaseEncodingDirectionPositive value
	chdir($cfg{'scriptdir'});
	my $systemstring = "./gdcmdump -C $dicomfile | grep PhaseEncodingDirectionPositive";
	$report .= WriteLog("Running [$systemstring]") . "\n";
	my $header = trim(`$systemstring`);
	$report .= WriteLog("$header") . "\n";
	my @parts = split(',', $header);
	my $val = "";
	if (defined($parts[4])) {
		$val = $parts[4];
		$val =~ s/Data '//g;
		$val =~ s/'//g;
		$val = trim($val);
	}
	$report .= WriteLog("PhaseEncodingDirectionPositive = [$val]") . "\n";
	$PhaseEncodingDirectionPositive = EscapeMySQLString(trim($val));
	$PhaseEncodeAngle = EscapeMySQLString(trim($PhaseEncodeAngle));
	
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
	
	# set the import log variables
	$IL_modality_orig = $Modality;
	$IL_patientname_orig = $PatientName;
	$IL_patientdob_orig = $PatientBirthDate;
	$IL_patientsex_orig = $PatientSex;
	$IL_stationname_orig = $StationName;
	$IL_institution_orig = "$InstitutionName - $InstitutionAddress";
	$IL_studydatetime_orig = "$StudyDate $StudyTime";
	$IL_seriesdatetime_orig = "$SeriesDate $SeriesTime";
	$IL_seriesnumber_orig = $SeriesNumber;
	$IL_studydesc_orig = $StudyDescription;
	$IL_seriesdesc_orig = $SeriesDescription;
	$IL_protocolname_orig = $ProtocolName;
	$IL_patientage_orig = $PatientAge;
	
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

	# check if the patient age contains any characters
	if ($PatientAge =~ /Y/) { $PatientAge =~ s/Y//g; }
	if ($PatientAge =~ /M/) { $PatientAge =~ s/M//g; $PatientAge = $PatientAge/12.0; }
	if ($PatientAge =~ /W/) { $PatientAge =~ s/W//g; $PatientAge = $PatientAge/52.0; }
	if ($PatientAge =~ /D/) { $PatientAge =~ s/D//g; $PatientAge = $PatientAge/365.25; }
	
	my $patientage;
	if (($PatientAge eq '') || ($PatientAge == 0)) {
		$patientage = "abs(datediff('$PatientBirthDate','$StudyDateTime')/365.25)";
	}
	else {
		$patientage = "'$PatientAge'";
	}
	
	# remove non-printable characters
	$PatientName =~ s/[[:^print:]]+//g;
	$PatientSex =~ s/[[:^print:]]+//g;
	
	if (($PatientBirthDate eq "") || ($PatientBirthDate eq "XXXXXXXX") || ($PatientBirthDate =~ /[a-z]/i) || ($PatientBirthDate =~ /anonymous/i)) {
		$report .= WriteLog("Patient birthdate invalid [$PatientBirthDate] setting to [0001-01-01]") . "\n";
		$PatientBirthDate = "0001-01-01";
	}

	$report .= WriteLog("Birthdate: [$PatientBirthDate]") . "\n";
	
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
	
	$report .= WriteLog("$PatientID - $StudyDescription") . "\n";
	
	# create the possible ID search lists and arrays
	my @altuidlist = '';
	my @idsearchlist;
	if (trim($importAltUIDs) ne "") {
		@altuidlist = split(/,/, $importAltUIDs);
	}
	push @idsearchlist, $PatientID;
	push @idsearchlist, @altuidlist;
	my $SQLIDs = "'$PatientID'";
	foreach my $tmpID (@idsearchlist) {
		if ((trim($tmpID) ne '') && (trim($tmpID) ne 'none') && (trim(lc($tmpID)) ne 'na') && (trim($tmpID) ne '0')) {
			$SQLIDs .= ",'$tmpID'";
		}
	}
	# check if the project and subject exist
	$sqlstring = "select (SELECT count(*) FROM `projects` WHERE project_costcenter = '$costcenter') 'projectcount', (SELECT count(*) FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in ($SQLIDs) or a.uid = SHA1('$PatientID') or b.altuid in ($SQLIDs) or b.altuid = SHA1('$PatientID')) 'subjectcount'";
	$report .= WriteLog("[$sqlstring]") . "\n";
	$report .= WriteLog("Checking if the subject exists by UID [$PatientID] or AltUID [$PatientID]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	my %row = $result->fetchhash;
	my $projectcount = $row{'projectcount'};
	my $subjectcount = $row{'subjectcount'};
	
	# if subject can't be found by UID, check by name/dob/sex (except if importMatchIDOnly is set), or create the subject
	if ($subjectcount < 1) {

		my $subjectFoundByName = 0;
		# search for an existing subject by name, dob, gender
		if (!$importMatchIDOnly) {
			$sqlstring = "select subject_id, uid from subjects where name like '%$PatientName%' and gender = left('$PatientSex',1) and birthdate = '$PatientBirthDate' and isactive = 1";
			$report .= WriteLog("Subject not found by UID. Checking if the subject exists using PatientName [$PatientName] PatientSex [$PatientSex] PatientBirthDate [$PatientBirthDate]") . "\n";
			my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
			if ($result->numrows > 0) {
				my %row = $result->fetchhash;
				$subjectRealUID = uc($row{'uid'});
				$subjectRowID = $row{'subject_id'};
				$report .= WriteLog("This subject exists. UID [$subjectRealUID]") . "\n";
				$IL_subjectcreated = 0;
				$subjectFoundByName = 1;			
			}
		}
		# if it couldn't be found, create a new subject
		if (!$subjectFoundByName) {
			my $count = 0;
			$subjectRealUID = "";
			
			$report .= WriteLog("Searching for an unused UID") . "\n";
			# create a new subjectRealUID
			do {
				$subjectRealUID = CreateUID('S');
				$sqlstring = "SELECT * FROM `subjects` WHERE uid = '$subjectRealUID'";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				$count = $result->numrows;
			} while ($count > 0);
			
			$report .= WriteLog("This subject does not exist. New UID: $subjectRealUID") . "\n";
			my $uuid = 'uuid()';
			if ($importUUID eq '') { $uuid = "'$importUUID'"; }
			$sqlstring = "insert into subjects (name, birthdate, gender, weight, height, uid, uuid, uuid2) values ('$PatientName', '$PatientBirthDate', '$PatientSex', '$PatientWeight', '$PatientSize', '$subjectRealUID', ucase(md5(concat(RemoveNonAlphaNumericChars('$PatientName'), RemoveNonAlphaNumericChars('$PatientBirthDate'),RemoveNonAlphaNumericChars('$PatientSex')))), ucase($uuid) )";
			$report .= WriteLog("Adding new subject [$subjectRealUID]") . "\n";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			$subjectRowID = $result->insertid;
			
			# insert the PatientID as an alternate UID
			if (trim($PatientID) ne '') {
				$sqlstring = "insert ignore into subject_altuid (subject_id, altuid) values ('$subjectRowID', '$PatientID')";
				$report .= WriteLog("Adding alternate UID [$PatientID]") . "\n";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			}
			$IL_subjectcreated = 1;
		}
	}
	else {
		# get the existing subject ID, and UID! (the PatientID may be an alternate UID)
		$sqlstring = "SELECT a.subject_id, a.uid FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in ($SQLIDs) or a.uid = SHA1('$PatientID') or b.altuid in ($SQLIDs) or b.altuid = SHA1('$PatientID')";
		my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
		my %row = $result->fetchhash;
		$subjectRowID = $row{'subject_id'};
		$subjectRealUID = uc($row{'uid'});
		$report .= WriteLog("Found [$subjectRealUID,$subjectRowID] using [SELECT a.subject_id, a.uid FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in ($SQLIDs) or a.uid = SHA1('$PatientID') or b.altuid in ($SQLIDs) or b.altuid = SHA1('$PatientID')]") . "\n";
		
		# insert the PatientID as an alternate UID
		if (trim($PatientID) ne '') {
			$sqlstring = "insert ignore into subject_altuid (subject_id, altuid) values ('$subjectRowID', '$PatientID')";
			$report .= WriteLog("Adding alternate UID [$PatientID]") . "\n";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		}
		$IL_subjectcreated = 0;
	}
	
	if ($subjectRealUID eq "") {
		return ("Error, UID is blank", $report);
	}
	
	# check if the subject is part of a family, if not create a family for it
	$sqlstring = "select family_id from family_members where subject_id = $subjectRowID";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	$report .= WriteLog("Checking to see if this subject [$subjectRowID] is part of a family") . "\n";
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$familyRowID = $row{'family_id'};
		$report .= WriteLog("This subject is part of a family [$familyRowID]") . "\n";
		$IL_familycreated = 0;
	}
	else {
		my $count = 0;
		$familyRealUID = "";
		
		# create family UID
		$report .= WriteLog("Subject is not part of family, finding a unique family UID") . "\n";
		do {
			$familyRealUID = CreateUID('F');
			$sqlstring = "SELECT * FROM `families` WHERE family_uid = '$familyRealUID'";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			$count = $result->numrows;
		} while ($count > 0);
		#$familyRealUID = CreateUID('F');
		
		# create familyRowID if it doesn't exist
		$sqlstring = "insert into families (family_uid, family_createdate, family_name) values ('$familyRealUID', now(), 'Proband-$subjectRealUID')";
		$report .= WriteLog("Create a family [$familyRealUID] for this subject") . "\n";
		my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
		$familyRowID = $result2->insertid;
		
		$sqlstring = "insert into family_members (family_id, subject_id, fm_createdate) values ($familyRowID, $subjectRowID, now())";
		$report .= WriteLog("Adding this subject [$subjectRealUID] to the family [$familyRealUID]") . "\n";
		my $result3 = SQLQuery($sqlstring, __FILE__, __LINE__);
		$IL_familycreated = 1;
	}
	
	# if project doesn't exist, use the generic project
	if ($projectcount < 1) {
		$costcenter = "999999";
	}
	
	# get the projectRowID
	$sqlstring = "select project_id from projects where project_costcenter = '$costcenter'";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	%row = $result->fetchhash;
	if (($importProjectID eq '') || ($importProjectID eq '0') || ($importProjectID == 0)) {
		$projectRowID = $row{'project_id'};
	}
	else {
		# need to create the project if it doesn't exist
		$report .= WriteLog("Project [$costcenter] does not exist, assigning project id [$importProjectID]") . "\n";
		$projectRowID = $importProjectID;
	}
	
	# check if the subject is enrolled in the project
	$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectRowID and project_id = $projectRowID";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$enrollmentRowID = $row{'enrollment_id'};
		$report .= WriteLog("Subject is enrolled in this project [$projectRowID]: enrollment [$enrollmentRowID]") . "\n";
		$IL_enrollmentcreated = 0;
	}
	else {
		# create enrollmentRowID if it doesn't exist
		$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
		my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
		$enrollmentRowID = $result2->insertid;
		$report .= WriteLog("Subject was not enrolled in this project. New enrollment [$enrollmentRowID]") . "\n";
		$IL_enrollmentcreated = 1;
	}

	# update alternate IDs, if there are any
	if (@altuidlist > 0) {
		foreach my $altuid (@altuidlist) {
			if ($altuid ne "") {
				$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, enrollment_id) values ('$subjectRowID', '$altuid', '$enrollmentRowID')";
				$report .= WriteLog("[$sqlstring]") . "\n";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			}
		}
	}
	
	# now determine if this study exists or not...
	# basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
	# also checks the accession number against the study_num to see if this study was pre-registered
	# HOWEVER, if there is an instanceID specified, we should only match a study that's part of an enrollment in the same instance
	my $studyFound = 0;
	$sqlstring = "select study_id, study_num from studies where enrollment_id = $enrollmentRowID and (study_num = '$AccessionNumber' or ((study_datetime between date_sub('$StudyDateTime', interval 30 second) and date_add('$StudyDateTime', interval 30 second)) and study_modality = '$Modality' and study_site = '$StationName'))";
	$report .= WriteLog("[$sqlstring]") . "\n";
	$report .= WriteLog("Checking if this study exists: enrollmentID [$enrollmentRowID] study(accession)Number [$AccessionNumber] StudyDateTime [$StudyDateTime] Modality [$Modality] StationName [$StationName]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $study_id = $row{'study_id'};
			$study_num = $row{'study_num'};
			my $foundInstanceRowID = -1;
			# check which instance this study is enrolled in
			my $sqlstringB = "select instance_id from projects where project_id = (select project_id from enrollment where enrollment_id = (select enrollment_id from studies where study_id = $study_id))";
			$report .= WriteLog("[$sqlstringB]") . "\n";
			my $resultB = SQLQuery($sqlstringB, __FILE__, __LINE__);
			$report .= WriteLog("SQL returned [" . $resultB->numrows . "] rows") . "\n";
			my %rowB = $resultB->fetchhash;
			$foundInstanceRowID = $rowB{'instance_id'};
			$report .= WriteLog("Found instance ID [$foundInstanceRowID] comparing to import instance ID [$importInstanceID]") . "\n";
			
			# if the study already exists within the instance specified in the project, then update the existing study, otherwise create a new one
			if (($foundInstanceRowID == $importInstanceID) || ($importInstanceID eq '') || ($importInstanceID == 0)) {
				$studyFound = 1;
				$studyRowID = $study_id;
				my $sqlstringA = "update studies set study_modality = '$Modality', study_datetime = '$StudyDateTime', study_ageatscan = $patientage, study_height = '$PatientSize', study_weight = '$PatientWeight', study_desc = '$StudyDescription', study_operator = '$OperatorsName', study_performingphysician = '$PerformingPhysiciansName', study_site = '$StationName', study_nidbsite = '$importSiteID', study_institution = '$InstitutionName - $InstitutionAddress', study_status = 'complete' where study_id = $studyRowID";
				$report .= WriteLog("[$sqlstringA]") . "\n";
				$report .= WriteLog("StudyID [$study_id] exists, updating") . "\n";
				my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
				$IL_studycreated = 0;
				last;
			}
		}
	}
	if (!$studyFound) {
		# create studyRowID if it doesn't exist
		$sqlstring = "SELECT max(a.study_num) 'study_num' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = $subjectRowID";
		$report .= WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		%row = $result->fetchhash;
		$study_num = $row{'study_num'} + 1;
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_ageatscan, study_height, study_weight, study_desc, study_operator, study_performingphysician, study_site, study_nidbsite, study_institution, study_status, study_createdby, study_createdate) values ($enrollmentRowID, $study_num, '$PatientID', '$Modality', '$StudyDateTime', $patientage, '$PatientSize', '$PatientWeight', '$StudyDescription', '$OperatorsName', '$PerformingPhysiciansName', '$StationName', '$importSiteID', '$InstitutionName - $InstitutionAddress', 'complete', '$scriptname', now())";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		$studyRowID = $result->insertid;
		$report .= WriteLog("[$sqlstring]") . "\n";
		$report .= WriteLog("Study did not exist, creating") . "\n";
		
		$IL_studycreated = 1;
	}

	# gather series information
	my $boldreps = 1;
	my $numfiles = $#files + 1;
	my $zsize;
	my $mrtype = "structural";
	
	# check if its an EPI sequence, but not a perfusion sequence
	if (($SequenceName =~ m/^epfid2d1_/) || ($SequenceName =~ m/^epfid2d1_64/) || ($SequenceName =~ m/^epfid2d1_128/)) {
		if (($ProtocolName =~ /perfusion/i) && ($ProtocolName =~ /ep2d_perf_tra/i)) { }
		else {
			$mrtype = "epi";
			# get the bold reps and attempt to get the z size
			$boldreps = $numfiles;
			
			# this method works ... sometimes
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
		my $NumberOfTemporalPositions = 0;
		my $ImagesInAcquisition = 0;
		$NumberOfTemporalPositions = trim($info->{'NumberOfTemporalPositions'});
		$ImagesInAcquisition = trim($info->{'ImagesInAcquisition'});
	}
	# if any of the DICOM fields were populated, use those instead
	if (($ImagesInAcquisition ne "") && ($ImagesInAcquisition > 0)) { $zsize = $ImagesInAcquisition; }
	if (($NumberOfTemporalPositions ne "") && ($NumberOfTemporalPositions > 0)) { $boldreps = $NumberOfTemporalPositions; }
	
	# insert or update the series based on modality
	my $dbModality;
	if (uc($Modality) eq "MR") {
		$dbModality = "mr";
		$sqlstring = "select mrseries_id from mr_series where study_id = $studyRowID and series_num = $SeriesNumber";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$seriesRowID = $row{'mrseries_id'};
			
			$sqlstring = "update mr_series set series_datetime = '$SeriesDateTime', series_desc = '$SeriesDescription', series_protocol = '$ProtocolName', series_sequencename = '$SequenceName',series_tr = '$RepetitionTime', series_te = '$EchoTime',series_flip = '$FlipAngle', phaseencodedir = '$InPlanePhaseEncodingDirection', phaseencodeangle = '$PhaseEncodeAngle', PhaseEncodingDirectionPositive = '$PhaseEncodingDirectionPositive', series_spacingx = '$pixelX',series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', series_fieldstrength = '$MagneticFieldStrength', img_rows = '$Rows', img_cols = '$Columns', img_slices = '$zsize', series_ti = '$InversionTime', percent_sampling = '$PercentSampling', percent_phasefov = '$PercentPhaseFieldOfView', acq_matrix = '$AcquisitionMatrix', slicethickness = '$SliceThickness', slicespacing = '$SpacingBetweenSlices', bandwidth = '$PixelBandwidth', image_type = '$ImageType', image_comments = '$ImageComments', bold_reps = '$boldreps', numfiles = '$numfiles', series_notes = '$importSeriesNotes', series_status = 'complete'";
			if ($NumberOfTemporalPositions > 0) {
				$sqlstring .= ", dimT = '$NumberOfTemporalPositions', dimN = 4";
			}
			if ($ImagesInAcquisition > 0) {
				$sqlstring .= ", dimZ = '$ImagesInAcquisition'";
			}
			$sqlstring .= " where mrseries_id = $seriesRowID";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			$report .= WriteLog("This MR series [$SeriesNumber] exists, updating") . "\n";
			$IL_seriescreated = 0;
			
			# if the series is being updated, the QA information might be incorrect or be based on the wrong number of files, so delete the mr_qa row
			$sqlstring = "delete from mr_qa where mrseries_id = $seriesRowID";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			
			$report .= WriteLog("Deleted from mr_qa... about to delete from qc_results") . "\n";
			
			# ... and delete the qc module rows
			$sqlstring = "select qcmoduleseries_id from qc_moduleseries where series_id = $seriesRowID and modality = 'mr'";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			my @qcidlist;
			if ($result->numrows > 0) {
				my %row = $result->fetchhash;
				push @qcidlist,$row{'qcmoduleseries_id'};

				$sqlstring = "delete from qc_results where qcmoduleseries_id in (" . join(',',@qcidlist) . ")";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			}
			
			$report .= WriteLog("Deleted from qc_results... about to delete from qc_moduleseries") . "\n";
			$sqlstring = "delete from qc_moduleseries where series_id = $seriesRowID and modality = 'mr'";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		}
		else {
			
			# create seriesRowID if it doesn't exist
			$sqlstring = "insert into mr_series (study_id, series_datetime, series_desc, series_protocol, series_sequencename, series_num, series_tr, series_te, series_flip, phaseencodedir, phaseencodeangle, PhaseEncodingDirectionPositive, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, series_ti, percent_sampling, percent_phasefov, acq_matrix, slicethickness, slicespacing, bandwidth, image_type, image_comments, bold_reps, numfiles, series_notes, data_type, series_status, series_createdby, series_createdate) values ($studyRowID, '$SeriesDateTime', '$SeriesDescription', '$ProtocolName', '$SequenceName', '$SeriesNumber', '$RepetitionTime', '$EchoTime', '$FlipAngle', '$InPlanePhaseEncodingDirection', '$PhaseEncodeAngle', '$PhaseEncodingDirectionPositive', '$pixelX', '$pixelY', '$SliceThickness', '$MagneticFieldStrength', '$Rows', '$Columns', '$zsize', '$InversionTime', '$PercentSampling', '$PercentPhaseFieldOfView', '$AcquisitionMatrix', '$SliceThickness', '$SpacingBetweenSlices', '$PixelBandwidth', '$ImageType', '$ImageComments', '$boldreps', '$numfiles', '$importSeriesNotes', 'dicom', 'complete', '$scriptname', now())";
			#print "[$sqlstring]\n";
			my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
			$seriesRowID = $result2->insertid;
			$report .= WriteLog("MR series [$SeriesNumber] did not exist, creating") . "\n";
			$IL_seriescreated = 1;
		}
	}
	elsif (uc($Modality) eq "CT") {
		$dbModality = "ct";
		$sqlstring = "select ctseries_id from ct_series where study_id = $studyRowID and series_num = $SeriesNumber";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$seriesRowID = $row{'ctseries_id'};
			
			$sqlstring = "update ct_series set series_datetime = '$SeriesDateTime', series_desc = '$SeriesDescription', series_protocol = '$ProtocolName', series_spacingx = '$pixelX', series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', series_imgrows = '$Rows', series_imgcols = '$Columns', series_imgslices = '$zsize', series_numfiles = '$numfiles', series_contrastbolusagent = '$ContrastBolusAgent', series_bodypartexamined = '$BodyPartExamined', series_scanoptions = '$ScanOptions', series_kvp = '$KVP', series_datacollectiondiameter = '$DataCollectionDiameter', series_contrastbolusroute = '$ContrastBolusRoute', series_rotationdirection = '$RotationDirection', series_exposuretime = '$ExposureTime', series_xraytubecurrent = '$XRayTubeCurrent', series_filtertype = '$FilterType', series_generatorpower = '$GeneratorPower', series_convolutionkernel = '$ConvolutionKernel', series_status = 'complete' where ctseries_id = $seriesRowID";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			$report .= WriteLog("This CT series [$SeriesNumber] exists, updating") . "\n";
			$IL_seriescreated = 0;
		}
		else {
			# create seriesRowID if it doesn't exist
			$sqlstring = "insert into ct_series ( study_id, series_datetime, series_desc, series_protocol, series_num, series_contrastbolusagent, series_bodypartexamined, series_scanoptions, series_kvp, series_datacollectiondiameter, series_contrastbolusroute, series_rotationdirection, series_exposuretime, series_xraytubecurrent, series_filtertype,series_generatorpower, series_convolutionkernel, series_spacingx, series_spacingy, series_spacingz, series_imgrows, series_imgcols, series_imgslices, numfiles, series_datatype, series_status, series_createdby
			) values (
			$studyRowID, '$SeriesDateTime', '$SeriesDescription', '$ProtocolName', '$SeriesNumber', '$ContrastBolusAgent', '$BodyPartExamined', '$ScanOptions', '$KVP', '$DataCollectionDiameter', '$ContrastBolusRoute', '$RotationDirection', '$ExposureTime', '$XRayTubeCurrent', '$FilterType', '$GeneratorPower', '$ConvolutionKernel', '$pixelX', '$pixelY', '$SliceThickness', '$Rows', '$Columns', '$zsize', '$numfiles', 'dicom', 'complete', '$scriptname')";
			#print "[$sqlstring]\n";
			my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
			$seriesRowID = $result2->insertid;
			$report .= WriteLog("CT series [$SeriesNumber] did not exist, creating") . "\n";
			$IL_seriescreated = 1;
		}
	}
	else {
		# this is the catch all for modalities which don't have a table in the database
		$dbModality = "ot";
		$sqlstring = "select otseries_id from ot_series where study_id = $studyRowID and series_num = $SeriesNumber";
		$report .= WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$seriesRowID = $row{'otseries_id'};
			
			$sqlstring = "update ot_series set series_datetime = '$SeriesDateTime', series_desc = '$ProtocolName', series_sequencename = '$SequenceName', series_spacingx = '$pixelX',series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', img_rows = '$Rows', img_cols = '$Columns', img_slices = '$zsize', numfiles = '$numfiles', series_status = 'complete' where otseries_id = $seriesRowID";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			$report .= WriteLog("This OT series [$SeriesNumber] exists, updating") . "\n";
			$IL_seriescreated = 0;
		}
		else {
			
			# create seriesRowID if it doesn't exist
			$sqlstring = "insert into ot_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols, img_slices, numfiles, modality, data_type, series_status, series_createdby) values ($studyRowID, '$SeriesDateTime', '$ProtocolName', '$SequenceName', '$SeriesNumber', '$pixelX', '$pixelY', '$SliceThickness', '$Rows', '$Columns', '$zsize', '$numfiles', '$Modality', 'dicom', 'complete', '$scriptname')";
			#print "[$sqlstring]\n";
			my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
			$seriesRowID = $result2->insertid;
			$report .= WriteLog("OT series [$SeriesNumber] did not exist, creating") . "\n";
			$IL_seriescreated = 1;
		}
	}
	
	# copy the file to the archive, update db info
	$report .= WriteLog("SeriesRowID: [$seriesRowID]") . "\n";
	
	# create data directory if it doesn't already exist
	my $outdir = "$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/dicom";
	$report .= WriteLog("OutDir: $outdir") . "\n";
	MakePath($outdir);
	
	# rename the files and move them to the archive
	# SubjectUID_EnrollmentRowID_SeriesNum_FileNum
	# S1234ABC_SP1_5_0001.dcm
	
	$report .= WriteLog("CWD: " . getcwd) . "\n";
	# check if there are .dcm files already in the archive
	my $cwd = getcwd;
	chdir($outdir);
	my @existingdcmfiles = <*.dcm>;
	chdir($cwd);
	@existingdcmfiles = sort @existingdcmfiles;
	my $numexistingdcmfiles = @existingdcmfiles;
	# rename EXISTING files in the output directory
	$report .= WriteLog("Checking for existing files in the outputdir [$outdir]") . "\n";
	if ($numexistingdcmfiles > 0) {
	
		# check all files to see if its the same study datetime, patient name, dob, gender, series #
		# if anything is different, move the file to a UID/Study/Series/dicom/existing directory
		
		# if they're all the same, consolidate the files into one list of new and old, remove duplicates
		$report .= WriteLog("There are $numexistingdcmfiles existing files in $outdir. Beginning renaming...") . "\n";
		
		my $filecnt = 0;
		# rename the existing files to make them unique
		foreach my $file (sort @existingdcmfiles) {
		
			# check if its already in the intended filename format
			my @parts = split('_', $file);
			if (@parts == 8) {
				next;
			}
			
			my $tags2 = $exifTool->ImageInfo("$outdir/$file");
			my $SliceNumber = trim($tags2->{'AcquisitionNumber'});
			my $InstanceNumber = trim($tags2->{'InstanceNumber'});
			my $SliceLocation = trim($tags2->{'SliceLocation'});
			my $AcquisitionTime = trim($tags2->{'AcquisitionTime'});
			my $ContentTime = trim($tags2->{'ContentTime'});
			my $SOPInstance = trim($tags2->{'SOPInstanceUID'});
			$AcquisitionTime =~ s/://g;
			$AcquisitionTime =~ s/\.//g;
			$ContentTime =~ s/://g;
			$ContentTime =~ s/\.//g;
			$SOPInstance = crc32($SOPInstance);

			my $newname = $subjectRealUID . "_$study_num" . "_$SeriesNumber" . "_" . sprintf('%05d',$SliceNumber) . "_" . sprintf('%05d',$InstanceNumber) . "_$AcquisitionTime" . "_$ContentTime" . "_$SOPInstance.dcm";
			
			move("$outdir/$file","$outdir/$newname");
			$filecnt++;
		}
		$report .= WriteLog("Done renaming [$filecnt] files") . "\n";
	}
	
	$report .= WriteLog("Beginning renumbering of new files") . "\n";
	# renumber the NEWLY added files to make them unique
	# create a SQL string for batch insert
	my $sqlstringA = "insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, importpermanent, importanonymize, importuuid, modality_orig, patientname_orig, patientdob_orig, patientsex_orig, stationname_orig, institution_orig, studydatetime_orig, seriesdatetime_orig, seriesnumber_orig, studydesc_orig, seriesdesc_orig, protocol_orig, patientage_orig, slicenumber_orig, instancenumber_orig, slicelocation_orig, acquisitiondatetime_orig, contentdatetime_orig, sopinstance_orig, modality_new, patientname_new, patientdob_new, patientsex_new, stationname_new, studydatetime_new, seriesdatetime_new, seriesnumber_new, studydesc_new, seriesdesc_new, protocol_new, patientage_new, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, project_number, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values ";
	my @sqlinserts;
	foreach my $file (sort @files) {
		my $tags3 = $exifTool->ImageInfo($file);
		my $SliceNumber = trim($tags3->{'AcquisitionNumber'});
		my $InstanceNumber = trim($tags3->{'InstanceNumber'});
		my $SliceLocation = trim($tags3->{'SliceLocation'});
		my $AcquisitionTime = trim($tags3->{'AcquisitionTime'});
		my $ContentTime = trim($tags3->{'ContentTime'});
		my $SOPInstance = trim($tags3->{'SOPInstanceUID'});
		$AcquisitionTime =~ s/://g;
		$AcquisitionTime =~ s/\.//g;
		$ContentTime =~ s/://g;
		$ContentTime =~ s/\.//g;
		$SOPInstance = crc32($SOPInstance);
		
		# sort by slice #, or instance #
		
		my $newname = $subjectRealUID . "_$study_num" . "_$SeriesNumber" . "_" . sprintf('%05d',$SliceNumber) . "_" . sprintf('%05d',$InstanceNumber) . "_$AcquisitionTime" . "_$ContentTime" . "_$SOPInstance.dcm";

		# check if a file with the same name already exists
		if (-e "$outdir/$newname") {
			$IL_overwrote_existing = 1;
		}
		else {
			$IL_overwrote_existing = 0;
		}
		
		# move the file, and overwrite if necessary
		my $systemstring = "mv -f $file $outdir/$newname";
		`$systemstring 2>&1`;
		
		# insert an import log record
		push(@sqlinserts, "('$file', '$outdir/$newname', 'DICOM', now(), 'successful', '$importID', '$importRowID', '$importSiteID', '$importProjectID', '$importPermanent', '$importAnonymize', '$importUUID', '$IL_modality_orig', '$IL_patientname_orig', '$IL_patientdob_orig', '$IL_patientsex_orig', '$IL_stationname_orig', '$IL_institution_orig', '$IL_studydatetime_orig', '$IL_seriesdatetime_orig', '$IL_seriesnumber_orig', '$IL_studydesc_orig', '$IL_seriesdesc_orig', '$IL_protocolname_orig', '$IL_patientage_orig', '$SliceNumber', '$InstanceNumber', '$SliceLocation', '".trim($tags3->{'AcquisitionTime'})."', '".trim($tags3->{'ContentTime'})."', '".trim($tags3->{'SOPInstanceUID'})."', '$Modality', '$PatientName', '$PatientBirthDate', '$PatientSex', '$StationName', '$StudyDateTime', '$SeriesDateTime', '$SeriesNumber', '$StudyDescription', '$SeriesDescription', '$ProtocolName', '".EscapeMySQLString($patientage)."', '$subjectRealUID', '$study_num', '$subjectRowID', '$studyRowID', '$seriesRowID', '$enrollmentRowID', '$costcenter', '$IL_seriescreated', '$IL_studycreated', '$IL_subjectcreated', '$IL_familycreated', '$IL_enrollmentcreated', '$IL_overwrote_existing')");
	}
	$report .= WriteLog("Done renaming files A") . "\n";
	$sqlstringA .= join(',', @sqlinserts);
	my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
	$report .= WriteLog("Done renaming files B") . "\n";
	
	# get the size of the dicom files and update the DB
	my $dirsize = 0;
	($dirsize, $numfiles) = GetDirectorySize($outdir);
	$report .= WriteLog("Got size [$dirsize] and numfiles [$numfiles] for directory [$outdir]") . "\n";
	$report .= WriteLog("CWD: " . getcwd) . "\n";
	
	# check if its an EPI sequence, but not a perfusion sequence
	if (($SequenceName =~ m/^epfid2d1_/) || ($SequenceName =~ m/^epfid2d1_64/) || ($SequenceName =~ m/^epfid2d1_128/)) {
		if (($ProtocolName =~ /perfusion/i) && ($ProtocolName =~ /ep2d_perf_tra/i)) { }
		else {
			$mrtype = "epi";
			# get the bold reps and attempt to get the z size
			$boldreps = $numfiles;
			
			# this method works ... sometimes
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
	
	$report .= WriteLog("zsize [$zsize]") . "\n";
	
	# update the database with the correct number of files/BOLD reps
	if (lc($dbModality) eq "mr") {
		$sqlstring = "update " . lc($dbModality) . "_series set series_size = '$dirsize', numfiles = '$numfiles', bold_reps = '$boldreps' where " . lc($dbModality) . "series_id = $seriesRowID";
		$report .= WriteLog($sqlstring) . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	}

	# create a thumbnail of the middle slice in the dicom directory (after getting the size, so the thumbnail isn't included in the size)
	CreateThumbnail("$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber", $mrtype, $Columns, $Rows);

	# if a beh directory exists for this series from an import, move it to the final series directory
	$report .= WriteLog("Checking for [$cfg{'incomingdir'}/$importID/beh]") . "\n";
	if (-d "$cfg{'incomingdir'}/$importID/beh") {
		$report .= WriteLog("Attempting to mkpath($cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/beh)") . "\n";
		MakePath("$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/beh");
		my $systemstring = "mv -v $cfg{'incomingdir'}/$importID/beh/* $cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/beh/";
		$report .= WriteLog("$systemstring (" . `$systemstring 2>&1` . ")") . "\n";
		
		# update the database to reflect the 
		$report .= WriteLog("GetDirectorySize($cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/beh)") . "\n";
		
		my ($behdirsize, $behnumfiles) = GetDirectorySize("$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/beh");
		$sqlstring = "update " . lc($dbModality) . "_series set beh_size = '$behdirsize', numfiles_beh = '$behnumfiles' where " . lc($dbModality) . "series_id = $seriesRowID";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	}
	
	# change the permissions to 777 so the webpage can read/write the directories
	$report .= WriteLog("About to change permissions on $cfg{'archivedir'}/$subjectRealUID") . "\n";
	$systemstring = "chmod -Rf 777 $cfg{'archivedir'}/$subjectRealUID";
	$report .= WriteLog("$systemstring (" . `$systemstring 2>&1` . ")") . "\n";
	# change back to original directory before leaving
	$report .= WriteLog("Finished changing permissions on $cfg{'archivedir'}/$subjectRealUID") . "\n";
	
	# copy everything to the backup directory
	my $backdir = "$cfg{'backupdir'}/$subjectRealUID/$study_num/$SeriesNumber";
	if (-d $backdir) {
		$report .= WriteLog("Directory [$backdir] already exists") . "\n";
	}
	else {
		$report .= WriteLog("Directory [$backdir] does not exist. About to create it...") . "\n";
		#mkpath($backdir, { verbose => 1, mode => 0777} );
		MakePath($backdir);
		$report .= WriteLog("Finished creating [$backdir]") . "\n";
	}
	$report .= WriteLog("About to copy to the backup directory") . "\n";
	$systemstring = "cp -R $cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/* $backdir";
	$report .= WriteLog("$systemstring (" . `$systemstring 2>&1` . ")") . "\n";
	$report .= WriteLog("Finished copying to the backup directory") . "\n";
	
	return ("", $report);
}


# ----------------------------------------------------------
# --------- InsertParRec -----------------------------------
# ----------------------------------------------------------
sub InsertParRec {
	my ($file, $importRowID) = @_;
	
	my $report = "";
	
	$report .= WriteLog("----- In InsertParRec($file, $importRowID) -----") . "\n";

	# import log variables
	my ($IL_modality_orig, $IL_patientname_orig, $IL_patientdob_orig, $IL_patientsex_orig, $IL_stationname_orig, $IL_institution_orig, $IL_studydatetime_orig, $IL_seriesdatetime_orig, $IL_seriesnumber_orig, $IL_studydesc_orig, $IL_patientage_orig, $IL_modality_new, $IL_patientname_new, $IL_patientdob_new, $IL_patientsex_new, $IL_stationname_new, $IL_institution_new, $IL_studydatetime_new, $IL_seriesdatetime_new, $IL_seriesnumber_new, $IL_studydesc_new, $IL_seriesdesc_orig, $IL_protocolname_orig, $IL_patientage_new, $IL_subject_uid, $IL_study_num, $IL_enrollmentid, $IL_project_number, $IL_seriescreated, $IL_studycreated, $IL_subjectcreated, $IL_familycreated, $IL_enrollmentcreated, $IL_overwrote_existing);
	
	my $familyRealUID;
	my $familyRowID;
	
	my $parfile = $file;
	my $recfile = $file;
	$recfile =~ s/\.par/\.rec/;
	my $sqlstring;
	my $result;
	my %row;
	
	my $PatientName;
	my $PatientBirthDate = "0001-01-01";
	my $PatientID = "NotSpecified";
	my $PatientSex = "U";
	my $PatientWeight = "0";
	#my $costcenter = "999999";
	my $StudyDescription;
	my $SeriesDescription;
	my $StationName = "PAR/REC";
	my $OperatorsName = "NotSpecified";
	my $PerformingPhysiciansName = "NotSpecified";
	my $InstitutionName = "NotSpecified";
	my $InstitutionAddress = "NotSpecified";
	#my $studydatetime;
	my $AccessionNumber = "";
	my $SequenceName;
	my $MagneticFieldStrength = "0";
	my $ProtocolName;
	my $StudyDateTime;
	my $SeriesDateTime;
	my $Modality;
	my $SeriesNumber;
	my $zsize;
	my $boldreps;
	my $numfiles = 2; # should always be 2 for .par/.rec
	my ($resolutionX, $resolutionY);
	my $seriessequencename;
	my $RepetitionTime;
	my ($Columns, $Rows);
	my ($pixelX, $pixelY, $SliceThickness, $xspacing, $yspacing, $EchoTime, $FlipAngle);

	my $importID = '';
	my $importInstanceID = '';
	my $importProjectID = '';
	my $importSiteID = '';
	my $importPermanent = '';
	my $importAnonymize = '';
	my $importUUID = '';
	my $importSeriesNotes = '';
	my $importAltUIDs = '';
	# if there is an importRowID, check to see how that thing is doing
	$sqlstring = "select * from import_requests where importrequest_id = '$importRowID'";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		$report .= WriteLog("[$sqlstring]") . "\n";
		my %row = $result->fetchhash;
		$importID = $row{'importrequest_id'};
		$importInstanceID = $row{'import_instanceid'};
		$importProjectID = $row{'import_projectid'};
		$importSiteID = $row{'import_siteid'};
		$importPermanent = $row{'import_permanent'};
		$importAnonymize = $row{'import_anonymize'};
		$importUUID = $row{'import_uuid'};
		$importSeriesNotes = $row{'import_seriesnotes'};
		$importAltUIDs = $row{'import_altuids'};
	}
	
	# read the .par file into an array, get all the useful info out of it
	open (FH, "< $file") or die "Inside ParseParRec(): Cannot open [$file] for read: $!";
	my @lines = <FH>;
	close FH or die "Inside ParseParRec(): Cannot close [$file]: $!";

	$report .= WriteLog("-----$file-----") . "\n";
	
	foreach my $line (@lines) {
		$line = trim($line);
		
		#print "$line\n";
		if ($line =~ m/Patient name/) {
			my @parts = split(/:/, $line);
			$PatientName = trim($parts[1]);
			$PatientID = $PatientName;
		}
		if ($line =~ m/Examination name/) {
			my @parts = split(/:/, $line);
			$StudyDescription = trim($parts[1]);
		}
		if ($line =~ m/Protocol name/) {
			my @parts = split(/:/, $line);
			$ProtocolName = trim($parts[1]);
			$SeriesDescription = $ProtocolName;
		}
		if ($line =~ m/Examination date\/time/) {
			my $datetime = $line;
			$datetime =~ s/\.\s+Examination date\/time\s+://;
			my @parts = split(/\//, $datetime);
			my $date = trim($parts[0]);
			my $time = trim($parts[1]);
			$date =~ s/\./\-/g;
			$StudyDateTime = "$date $time";
			$SeriesDateTime = "$date $time";
		}
		if ($line =~ m/Series Type/) {
			my @parts = split(/:/, $line);
			$Modality = trim($parts[1]);
			$Modality =~ s/Image//gi;
			$Modality =~ s/SERIES//gi;
			$Modality = trim(uc($Modality));
		}
		if ($line =~ m/Acquisition nr/) {
			my @parts = split(/:/, $line);
			$SeriesNumber = trim($parts[1]);
		}
		if ($line =~ m/Max. number of slices\/locations/) {
			my @parts = split(/:/, $line);
			$zsize = trim($parts[1]);
		}
		if ($line =~ m/Max. number of dynamics/) {
			my @parts = split(/:/, $line);
			$boldreps = trim($parts[1]);
		}
		if ($line =~ m/Technique/) {
			my @parts = split(/:/, $line);
			$SequenceName = trim($parts[1]);
		}
		if ($line =~ m/Scan resolution/) {
			my @parts = split(/:/, $line);
			my $resolution = trim($parts[1]);
			my @parts2 = split(/\s+/, $resolution);
			$Columns = $parts2[0];
			$Rows = $parts2[1];
		}
		if ($line =~ m/Repetition time/) {
			my @parts = split(/:/, $line);
			$RepetitionTime = trim($parts[1]);
		}
		# get the first line of the image list... should contain flip angle
		if (($line !~ m/^\./) && ($line !~ m/^#/) && (trim($line) ne "")) {
			#print "[$line]\n";
			my @parts = split(/\s+/,$line);
			# 10 - xsize
			$pixelX = trim($parts[9]);
			# 11 - ysize
			$pixelY = trim($parts[10]);
			# 23 - slice thickness
			$SliceThickness = trim($parts[22]);
			# 29 - xspacing
			$xspacing = trim($parts[28]);
			# 30 - yspacing
			$yspacing = trim($parts[29]);
			# 31 - TE
			$EchoTime = trim($parts[30]);
			# 36 - flip
			$FlipAngle = trim($parts[35]);
			last;
		}
	}
	
	# check if anything is funny
	if (trim($SeriesNumber) eq "") { return ("SeriesNumber blank",$report); }
	if (trim($PatientName) eq "") { return ("PatientName blank",$report); }

	# set the import log variables
	$IL_modality_orig = $Modality;
	$IL_patientname_orig = $PatientName;
	$IL_patientdob_orig = $PatientBirthDate;
	$IL_patientsex_orig = $PatientSex;
	$IL_stationname_orig = $StationName;
	$IL_institution_orig = "$InstitutionName - $InstitutionAddress";
	$IL_studydatetime_orig = "$StudyDateTime";
	$IL_seriesdatetime_orig = "$SeriesDateTime";
	$IL_seriesnumber_orig = $SeriesNumber;
	$IL_studydesc_orig = $StudyDescription;
	$IL_seriesdesc_orig = $ProtocolName;
	$IL_protocolname_orig = $ProtocolName;
	$IL_patientage_orig = 0;
	
	# ----- check if this subject/study/series/etc exists -----
	my $projectRowID;
	my $subjectRealUID = $PatientName;
	my $subjectRowID;
	my $enrollmentRowID;
	my $studyRowID;
	my $seriesRowID;
	my $costcenter;
	my $study_num;
	
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
	
	$report .= WriteLog("$PatientID - $StudyDescription") . "\n";
	
	# create the possible ID search lists and arrays
	my @altuidlist = '';
	my @idsearchlist;
	if (trim($importAltUIDs) ne "") {
		@altuidlist = split(/,/, $importAltUIDs);
	}
	push @idsearchlist, $PatientID;
	push @idsearchlist, @altuidlist;
	my $SQLIDs = "'$PatientID'";
	foreach my $tmpID (@idsearchlist) {
		if (trim($tmpID) ne '') {
			$SQLIDs .= ",'$tmpID'";
		}
	}
	# check if the project and subject exist
	$sqlstring = "select (SELECT count(*) FROM `projects` WHERE project_costcenter = '$costcenter') 'projectcount', (SELECT count(*) FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in ($SQLIDs) or a.uid = SHA1('$PatientID') or b.altuid in ($SQLIDs) or b.altuid = SHA1('$PatientID')) 'subjectcount'";
	$report .= WriteLog("[$sqlstring]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	%row = $result->fetchhash;
	my $projectcount = $row{'projectcount'};
	my $subjectcount = $row{'subjectcount'};
	
	# if subject doesn't exist, create the subject
	if ($subjectcount < 1) {
		$report .= WriteLog("Subject count < 1") . "\n";

		# search for an existing subject by name, dob, gender
		$sqlstring = "select subject_id, uid from subjects where name like '%$PatientName%' and gender = '$PatientSex' and birthdate = '$PatientBirthDate' and isactive = 1";
		$report .= WriteLog("[$sqlstring]") . "\n";
		my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$subjectRealUID = uc($row{'uid'});
			$subjectRowID = $row{'subject_id'};
			$IL_subjectcreated = 0;
		}
		# if it couldn't be found, create a new subject
		else {
			my $count = 0;
			$subjectRealUID = "";
			
			# create a new subjectRealUID
			do {
				$subjectRealUID = CreateUID('S');
				$sqlstring = "SELECT * FROM `subjects` WHERE uid = '$subjectRealUID'";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				$count = $result->numrows;
			} while ($count > 0);
			
			$report .= WriteLog("New subject ID: $subjectRealUID") . "\n";
			$sqlstring = "insert into subjects (name, birthdate, gender, weight, uid, uuid) values ('$PatientName', '$PatientBirthDate', '$PatientSex', '$PatientWeight', '$subjectRealUID', ucase(md5(concat(RemoveNonAlphaNumericChars('$PatientName'), RemoveNonAlphaNumericChars('$PatientBirthDate'),RemoveNonAlphaNumericChars('$PatientSex')))) )";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			$subjectRowID = $result->insertid;
			
			# add the alternate UID
			$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values ('$subjectRowID', '$PatientID', 0, '$enrollmentRowID')";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			$report .= WriteLog("[$sqlstring]") . "\n";
			
			$IL_subjectcreated = 1;
		}
	}
	else {
		# get the existing subject ID
		#$sqlstring = "select subject_id from subjects where uid = '$PatientID'";
		$sqlstring = "SELECT a.subject_id, a.uid FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in ($SQLIDs) or a.uid = SHA1('$PatientID') or b.altuid in ($SQLIDs) or b.altuid = SHA1('$PatientID')";
		$report .= WriteLog("The PatientID [$PatientID] exists, getting the SubjectRowID and SubjectRealUID [$sqlstring]") . "\n";
		my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
		my %row = $result->fetchhash;
		$subjectRowID = $row{'subject_id'};
		$subjectRealUID = uc($row{'uid'});
		$IL_subjectcreated = 0;
	}
	
	if ($subjectRealUID eq "") {
		$report .= WriteLog("ERROR: UID blank") . "\n";
		return ("Error, UID blank",$report);
	}
	else {
		$report .= WriteLog("UID found [$subjectRealUID]") . "\n";
	}
	
	# check if the subject is part of a family, if not create a family for it
	$report .= WriteLog("Checking to see if this subject [$subjectRowID] is part of a family") . "\n";
	$sqlstring = "select family_id from family_members where subject_id = $subjectRowID";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$familyRowID = $row{'family_id'};
		$report .= WriteLog("This subject is part of a family [$familyRowID]") . "\n";
		$IL_familycreated = 0;
	}
	else {
		my $count = 0;
		$familyRealUID = "";
		
		# create family UID
		$report .= WriteLog("Subject is not part of family, finding a unique family UID") . "\n";
		do {
			$familyRealUID = CreateUID('F');
			$sqlstring = "SELECT * FROM `families` WHERE family_uid = '$familyRealUID'";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			$count = $result->numrows;
		} while ($count > 0);
		
		# create familyRowID if it doesn't exist
		$sqlstring = "insert into families (family_uid, family_createdate, family_name) values ('$familyRealUID', now(), 'Proband-$subjectRealUID')";
		$report .= WriteLog("Create a family [$familyRealUID] for this subject") . "\n";
		my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
		$familyRowID = $result2->insertid;
		
		$sqlstring = "insert into family_members (family_id, subject_id, fm_createdate) values ($familyRowID, $subjectRowID, now())";
		$report .= WriteLog("Adding this subject [$subjectRealUID] to the family [$familyRealUID]") . "\n";
		my $result3 = SQLQuery($sqlstring, __FILE__, __LINE__);
		$IL_familycreated = 1;
	}
	
	# if project doesn't exist, use the generic project
	if ($projectcount < 1) {
		$costcenter = "999999";
	}
	
	if (($importProjectID eq '') || ($importProjectID == 0)) {
		# get the projectRowID
		$sqlstring = "select project_id from projects where project_costcenter = '$costcenter'";
		$report .= WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		%row = $result->fetchhash;
		$projectRowID = $row{'project_id'};
	}
	else {
		$projectRowID = $importProjectID;
	}
	
	# check if the subject is enrolled in the project
	$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectRowID and project_id = $projectRowID";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		$report .= WriteLog("[$sqlstring]") . "\n";
		my %row = $result->fetchhash;
		$enrollmentRowID = $row{'enrollment_id'};
		$IL_enrollmentcreated = 0;
	}
	else {
		# create enrollmentRowID if it doesn't exist
		$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
		$report .= WriteLog("[$sqlstring]") . "\n";
		my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
		$enrollmentRowID = $result2->insertid;
		$IL_enrollmentcreated = 1;
	}
	
	# now determine if this study exists or not...
	# basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
	# also checks the accession number against the study_num to see if this study was pre-registered
	$sqlstring = "select study_id, study_num from studies where enrollment_id = $enrollmentRowID and (study_num = '$AccessionNumber' or (study_datetime = '$StudyDateTime' and study_modality = '$Modality' and study_site = '$StationName'))";
	$report .= WriteLog("[$sqlstring]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$studyRowID = $row{'study_id'};
		$study_num = $row{'study_num'};
		
		$sqlstring = "update studies set study_modality = '$Modality', study_datetime = '$StudyDateTime', study_desc = '$StudyDescription', study_operator = '$OperatorsName', study_performingphysician = '$PerformingPhysiciansName', study_site = '$StationName', study_institution = '$InstitutionName - $InstitutionAddress', study_status = 'complete' where study_id = $studyRowID";
		$report .= WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		$IL_studycreated = 0;
	}
	else {
		# create studyRowID if it doesn't exist
		$sqlstring = "SELECT max(a.study_num) 'study_num' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id  WHERE b.subject_id = $subjectRowID";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		%row = $result->fetchhash;
		$study_num = $row{'study_num'} + 1;
		#$study_num = $result->numrows + 1;
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status, study_createdby, study_createdate) values ($enrollmentRowID, $study_num, '$PatientID', '$Modality', '$StudyDateTime', '$StudyDescription', '$OperatorsName', '$PerformingPhysiciansName', '$StationName', '$InstitutionName - $InstitutionAddress', 'complete', 'parseincoming.pl', now())";
		$report .= WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		$studyRowID = $result->insertid;
		$IL_studycreated = 1;
	}

	WriteLog("Going forward using the following: SubjectRowID [$subjectRowID] ProjectRowID [$projectRowID] EnrollmentRowID [$enrollmentRowID] StudyRowID [$studyRowID]");
	#$sqlstring = "select * from ";
	#WriteLog("Values obtained from the above IDs. UID [] ");
	
	# ----- insert or update the series -----
	$sqlstring = "select mrseries_id from mr_series where study_id = $studyRowID and series_num = $SeriesNumber";
	$report .= WriteLog("[$sqlstring]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$seriesRowID = $row{'mrseries_id'};
		$sqlstring = "update mr_series set series_datetime = '$SeriesDateTime',series_desc = '$ProtocolName', series_sequencename = '$SequenceName',series_tr = '$RepetitionTime', series_te = '$EchoTime',series_flip = '$FlipAngle', series_spacingx = '$pixelX',series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', series_fieldstrength = '$MagneticFieldStrength', img_rows = '$Rows', img_cols = '$Columns', img_slices = '$zsize', bold_reps = '$boldreps', numfiles = '$numfiles', series_status = 'complete' where mrseries_id = $seriesRowID";
		$report .= WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		$IL_seriescreated = 0;
	}
	else {
		# create seriesRowID if it doesn't exist
		$sqlstring = "insert into mr_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_tr, series_te, series_flip, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, bold_reps, numfiles, data_type, series_status, series_createdby, series_createdate) values ($studyRowID, '$SeriesDateTime', '$ProtocolName', '$SequenceName', '$SeriesNumber', '$RepetitionTime', '$EchoTime', '$FlipAngle', '$pixelX', '$pixelY', '$SliceThickness', '$MagneticFieldStrength', '$Rows', '$Columns', '$zsize', $boldreps, '$numfiles', 'parrec', 'complete', 'parsedicom.pl', now())";
		$report .= WriteLog("[$sqlstring]") . "\n";
		my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
		$seriesRowID = $result2->insertid;
		$IL_seriescreated = 0;
	}
	
	my ($path, $uid, $studynum, $seriesnum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesRowID, 'mr');
	$report .= WriteLog("Values from GetDataPathFromSeriesID($seriesRowID, 'mr'): Path [$path] UID [$uid] StudyNum [$studynum] SeriesNum [$seriesnum] StudyID [$studyid] SubjectID [$subjectid]") . "\n";
	
	# copy the file to the archive, update db info
	$report .= WriteLog("$seriesRowID") . "\n";
	
	# create data directory if it doesn't already exist
	my $outdir = "$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/parrec";
	$report .= WriteLog("Outdir [$outdir]");
	MakePath($outdir);
	
	# move the files into the outdir
	$report .= WriteLog("Moving " . $cfg{'incomingdir'} . "/$importID/$parfile -> $outdir/$parfile") . "\n";
	$report .= WriteLog("Moving " . $cfg{'incomingdir'} . "/$importID/$recfile -> $outdir/$recfile") . "\n";
	move($cfg{'incomingdir'} . "/$importID/$parfile","$outdir/$parfile");
	move($cfg{'incomingdir'} . "/$importID/$recfile","$outdir/$recfile");

	# insert an import log record (.par file)
	$sqlstring = "insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, importpermanent, importanonymize, importuuid, modality_orig, patientname_orig, patientdob_orig, patientsex_orig, stationname_orig, institution_orig, studydatetime_orig, seriesdatetime_orig, seriesnumber_orig, studydesc_orig, seriesdesc_orig, protocol_orig, patientage_orig, slicenumber_orig, instancenumber_orig, slicelocation_orig, acquisitiondatetime_orig, contentdatetime_orig, sopinstance_orig, modality_new, patientname_new, patientdob_new, patientsex_new, stationname_new, studydatetime_new, seriesdatetime_new, seriesnumber_new, studydesc_new, seriesdesc_new, protocol_new, patientage_new, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, project_number, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values ('$file', '" . $cfg{'incomingdir'} . "/$importID/$parfile', 'PARREC', now(), 'successful', '$importID', '$importRowID', '$importSiteID', '$importProjectID', '$importPermanent', '$importAnonymize', '$importUUID', '$IL_modality_orig', '$IL_patientname_orig', '$IL_patientdob_orig', '$IL_patientsex_orig', '$IL_stationname_orig', '$IL_institution_orig', '$IL_studydatetime_orig', '$IL_seriesdatetime_orig', '$IL_seriesnumber_orig', '$IL_studydesc_orig', '$IL_seriesdesc_orig', '$IL_protocolname_orig', '$IL_patientage_orig', '0', '0', '0', '$SeriesDateTime', '$SeriesDateTime', 'Unknown', '$Modality', '$PatientName', '$PatientBirthDate', '$PatientSex', '$StationName', '$StudyDateTime', '$SeriesDateTime', '$SeriesNumber', '$StudyDescription', '$SeriesDescription', '$ProtocolName', '', '$subjectRealUID', '$study_num', '$subjectRowID', '$studyRowID', '$seriesRowID', '$enrollmentRowID', '$costcenter', '$IL_seriescreated', '$IL_studycreated', '$IL_subjectcreated', '$IL_familycreated', '$IL_enrollmentcreated', '$IL_overwrote_existing')";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	# insert an import log record (.rec file)
	$sqlstring = "insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, importpermanent, importanonymize, importuuid, modality_orig, patientname_orig, patientdob_orig, patientsex_orig, stationname_orig, institution_orig, studydatetime_orig, seriesdatetime_orig, seriesnumber_orig, studydesc_orig, seriesdesc_orig, protocol_orig, patientage_orig, slicenumber_orig, instancenumber_orig, slicelocation_orig, acquisitiondatetime_orig, contentdatetime_orig, sopinstance_orig, modality_new, patientname_new, patientdob_new, patientsex_new, stationname_new, studydatetime_new, seriesdatetime_new, seriesnumber_new, studydesc_new, seriesdesc_new, protocol_new, patientage_new, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, project_number, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values ('$file', '" . $cfg{'incomingdir'} . "/$importID/$recfile', 'PARREC', now(), 'successful', '$importID', '$importRowID', '$importSiteID', '$importProjectID', '$importPermanent', '$importAnonymize', '$importUUID', '$IL_modality_orig', '$IL_patientname_orig', '$IL_patientdob_orig', '$IL_patientsex_orig', '$IL_stationname_orig', '$IL_institution_orig', '$IL_studydatetime_orig', '$IL_seriesdatetime_orig', '$IL_seriesnumber_orig', '$IL_studydesc_orig', '$IL_seriesdesc_orig', '$IL_protocolname_orig', '$IL_patientage_orig', '0', '0', '0', '$SeriesDateTime', '$SeriesDateTime', 'Unknown', '$Modality', '$PatientName', '$PatientBirthDate', '$PatientSex', '$StationName', '$StudyDateTime', '$SeriesDateTime', '$SeriesNumber', '$StudyDescription', '$SeriesDescription', '$ProtocolName', '', '$subjectRealUID', '$study_num', '$subjectRowID', '$studyRowID', '$seriesRowID', '$enrollmentRowID', '$costcenter', '$IL_seriescreated', '$IL_studycreated', '$IL_subjectcreated', '$IL_familycreated', '$IL_enrollmentcreated', '$IL_overwrote_existing')";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);

	# delete any rows older than 10 days from the import log
	$sqlstring = "delete from importlogs where importstartdate < date_sub(now(), interval 10 day)";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	
	# get the size of the files and update the DB
	my $dirsize;
	($dirsize, $numfiles) = GetDirectorySize($outdir);
	$sqlstring = "update mr_series set series_size = $dirsize where mrseries_id = $seriesRowID";
	$report .= WriteLog("[$sqlstring]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);

	# change the permissions to 777 so the webpage can read/write the directories
	$report .= WriteLog("Current directory: " . getcwd) . "\n";
	my $systemstring = "chmod -Rf 777 $cfg{'archivedir'}/$subjectRealUID";
	$report .= WriteLog("$systemstring (" . `$systemstring` . ")") . "\n";
	# change back to original directory before leaving
	#WriteLog("Changing back to $origDir");
	#chdir($origDir);
	$report .= WriteLog("Finished changing permissions on $cfg{'archivedir'}/$subjectRealUID") . "\n";
	
	# copy everything to the backup directory
	my $backdir = "$cfg{'backupdir'}/$subjectRealUID/$study_num/$SeriesNumber";
	if (-d $backdir) {
		$report .= WriteLog("Directory [$backdir] already exists") . "\n";
	}
	else {
		$report .= WriteLog("Directory [$backdir] does not exist. About to create it...") . "\n";
		#mkpath($backdir, { verbose => 1, mode => 0777} );
		MakePath($backdir);
		$report .= WriteLog("Finished creating [$backdir]") . "\n";
	}
	$report .= WriteLog("About to copy to the backup directory") . "\n";
	$systemstring = "cp -Rv $cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/* $backdir";
	$report .= WriteLog("$systemstring (" . `$systemstring` . ")") . "\n";
	$report .= WriteLog("Finished copying to the backup directory") . "\n";
	
	return ("",$report);
}


# ----------------------------------------------------------
# --------- InsertEEG --------------------------------------
# ----------------------------------------------------------
sub InsertEEG {
	my ($file, $importRowID, $Modality) = @_;
	
	my $report = "";
	
	$report .= WriteLog("----- In InsertEEG($file, $importRowID) -----") . "\n";
	# import log variables
	my ($IL_modality_orig, $IL_patientname_orig, $IL_patientdob_orig, $IL_patientsex_orig, $IL_stationname_orig, $IL_institution_orig, $IL_studydatetime_orig, $IL_seriesdatetime_orig, $IL_seriesnumber_orig, $IL_studydesc_orig, $IL_patientage_orig, $IL_modality_new, $IL_patientname_new, $IL_patientdob_new, $IL_patientsex_new, $IL_stationname_new, $IL_institution_new, $IL_studydatetime_new, $IL_seriesdatetime_new, $IL_seriesnumber_new, $IL_studydesc_new, $IL_seriesdesc_orig, $IL_protocolname_orig, $IL_patientage_new, $IL_subject_uid, $IL_study_num, $IL_enrollmentid, $IL_project_number, $IL_seriescreated, $IL_studycreated, $IL_subjectcreated, $IL_familycreated, $IL_enrollmentcreated, $IL_overwrote_existing);
	
	# initialize variables... to prevent warnings
	$IL_modality_orig = $IL_patientname_orig = $IL_patientdob_orig = $IL_patientsex_orig = $IL_stationname_orig = $IL_institution_orig = $IL_studydatetime_orig = $IL_seriesdatetime_orig = $IL_seriesnumber_orig = $IL_studydesc_orig = $IL_patientage_orig = $IL_modality_new = $IL_patientname_new = $IL_patientdob_new = $IL_patientsex_new = $IL_stationname_new = $IL_institution_new = $IL_studydatetime_new = $IL_seriesdatetime_new = $IL_seriesnumber_new = $IL_studydesc_new = $IL_seriesdesc_orig = $IL_protocolname_orig = $IL_patientage_new = $IL_subject_uid = $IL_study_num = $IL_enrollmentid = $IL_project_number = $IL_seriescreated = $IL_studycreated = $IL_subjectcreated = $IL_familycreated = $IL_enrollmentcreated = $IL_overwrote_existing = '';
	
	my $familyRealUID;
	my $familyRowID;

	my $projectRowID;
	my $subjectRealUID;
	my $subjectRowID;
	my $enrollmentRowID;
	my $studyRowID;
	my $seriesRowID;
	my $costcenter;
	my $study_num;
	
	my $sqlstring;
	my $result;
	my %row;
	
	my $PatientName = "NotSpecified";
	my $PatientBirthDate = "0001-01-01";
	my $PatientID = "NotSpecified";
	my $PatientSex = "U";
	my $PatientWeight = "0";
	my $StudyDescription = "NotSpecified";
	my $SeriesDescription;
	my $StationName = "";
	my $OperatorsName = "NotSpecified";
	my $PerformingPhysiciansName = "NotSpecified";
	my $InstitutionName = "NotSpecified";
	my $InstitutionAddress = "NotSpecified";
	my $SequenceName;
	my $ProtocolName;
	my $StudyDateTime;
	my $SeriesDateTime;
	#my $Modality = "EEG";
	my $SeriesNumber;
	my $FileNumber;
	my $numfiles = 1;

	my $importID = '';
	my $importSiteID = '';
	my $importProjectID = '';
	my $importPermanent = '';
	my $importAnonymize = '';
	my $importUUID = '';
	my $importEquipment = '';
	my $importSeriesNotes = '';
	my $importAltUIDs = '';
	# if there is an importRowID, check to see how that thing is doing
	$sqlstring = "select * from import_requests where importrequest_id = '$importRowID'";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		$report .= WriteLog("[$sqlstring]") . "\n";
		my %row = $result->fetchhash;
		$importID = $row{'importrequest_id'};
		$importSiteID = $row{'import_siteid'};
		$importProjectID = $projectRowID = $row{'import_projectid'};
		$importPermanent = $row{'import_permanent'};
		$importAnonymize = $row{'import_anonymize'};
		$importUUID = $row{'import_uuid'};
		$importEquipment = $row{'import_equipment'};
		$importSeriesNotes = $row{'import_seriesnotes'};
		$importAltUIDs = $row{'import_altuids'};
	}
	else {
		$report .= WriteLog("ImportID [$importRowID] not found. Using default import parameters") . "\n";
	}
	$report .= WriteLog($file) . "\n";
	# split the filename into the appropriate fields
	# AltUID_Date_task_operator_series.*
	my $FileName = $file;
	$FileName =~ s/\..*+$//; # remove everything after the first dot
	my @parts = split('_', $FileName);
	$PatientID = trim($parts[0]);
	if (length($parts[1]) == 6) {
		$StudyDateTime = $SeriesDateTime = substr($parts[1],4,2) . "-" . substr($parts[1],0,2) . "-" . substr($parts[1],2,2) . " 00:00:00";
	}
	elsif (length($parts[1]) == 8) {
		$StudyDateTime = $SeriesDateTime = substr($parts[1],0,4) . "-" . substr($parts[1],4,2) . "-" . substr($parts[1],6,2) . " 00:00:00";
	}
	elsif (length($parts[1]) == 14) {
		$StudyDateTime = $SeriesDateTime = substr($parts[1],0,4) . "-" . substr($parts[1],4,2) . "-" . substr($parts[1],6,2) . " " . substr($parts[1],8,2) . ":" . substr($parts[1],10,2) . ":" . substr($parts[1],10,2);
	}
	
	$SeriesDescription = $ProtocolName = trim($parts[2]);
	$OperatorsName = trim($parts[3]);
	$SeriesNumber = trim($parts[4]);
	$FileNumber = trim($parts[5]);
	
	$report .= WriteLog("Before fixing: PatientID [$PatientID], StudyDateTime [$StudyDateTime], SeriesDateTime [$SeriesDateTime], SeriesDescription [$SeriesDescription], OperatorsName [$OperatorsName], SeriesNumber [$SeriesNumber], FileNumber [$FileNumber]") . "\n";
	
	# check if anything is funny
	if ($StudyDateTime eq "") { $StudyDateTime = "0000-00-00 00:00:00"; }
	if ($SeriesDateTime eq "") { $SeriesDateTime = "0000-00-00 00:00:00"; }
	if ($SeriesDescription eq "") { $SeriesDescription = "Unknown"; }
	if ($ProtocolName eq "") { $ProtocolName = "Unknown"; }
	if ($OperatorsName eq "") { $OperatorsName = "Unknown"; }
	if (($SeriesNumber eq "") || (!looks_like_number($SeriesNumber))) { $SeriesNumber = 1; }
	if ($FileNumber eq "") { $FileNumber = 0; }
	
	$report .= WriteLog("After fixing: PatientID [$PatientID], StudyDateTime [$StudyDateTime], SeriesDateTime [$SeriesDateTime], SeriesDescription [$SeriesDescription], OperatorsName [$OperatorsName], SeriesNumber [$SeriesNumber], FileNumber [$FileNumber]") . "\n";

	# set the import log variables
	$IL_modality_orig = $Modality;
	$IL_patientname_orig = $PatientName;
	$IL_patientdob_orig = $PatientBirthDate;
	$IL_patientsex_orig = $PatientSex;
	$IL_stationname_orig = $StationName;
	$IL_institution_orig = "$InstitutionName - $InstitutionAddress";
	$IL_studydatetime_orig = "$StudyDateTime";
	$IL_seriesdatetime_orig = "$SeriesDateTime";
	$IL_seriesnumber_orig = $SeriesNumber;
	$IL_studydesc_orig = $StudyDescription;
	$IL_seriesdesc_orig = $ProtocolName;
	$IL_protocolname_orig = $ProtocolName;
	$IL_patientage_orig = 0;
	
	# ----- check if this subject/study/series/etc exists -----
	$report .= WriteLog("$PatientID - $StudyDescription") . "\n";
	
	# create the possible ID search lists and arrays
	my @altuidlist = '';
	my @idsearchlist;
	if (trim($importAltUIDs) ne "") {
		@altuidlist = split(/,/, $importAltUIDs);
	}
	push @idsearchlist, $PatientID;
	push @idsearchlist, @altuidlist;
	my $SQLIDs = "'$PatientID'";
	foreach my $tmpID (@idsearchlist) {
		if (trim($tmpID) ne '') {
			$SQLIDs .= ",'$tmpID'";
		}
	}
	# check if the project and subject exist
	$sqlstring = "SELECT a.subject_id, a.uid FROM `subjects` a left join subject_altuid b on a.subject_id = b.subject_id WHERE a.uid in ($SQLIDs) or a.uid = SHA1('$PatientID') or b.altuid in ($SQLIDs) or b.altuid = SHA1('$PatientID')";
	$report .= WriteLog("SQL: [$sqlstring]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		%row = $result->fetchhash;
		$subjectRowID = $row{'subject_id'};
		$subjectRealUID = uc($row{'uid'});
		if (trim($subjectRowID) eq '') {
			# subject doesn't already exist. Not creating new subjects as part of EEG/ET/etc upload, so note this failure in the import_logs table
			return ("Subject with ID [$PatientID] or alternate IDs [$SQLIDs] does not exist", $report);
		}
	}
	else {
		# subject doesn't already exist. Not creating new subjects as part of EEG/ET/etc upload, so note this failure in the import_logs table
		return ("Subject with ID [$PatientID] does not exist", $report);
	}
	
	if ($subjectRealUID eq "") {
		$report .= WriteLog("ERROR: UID blank") . "\n";
		return ("Error, UID blank", $report);
	}
	else {
		$report .= WriteLog("UID found [$subjectRealUID]") . "\n";
	}
	
	# get the generic projectRowID if the requested one is empty
	if ((!defined($projectRowID)) || ($projectRowID eq "")) {
		$sqlstring = "select project_id from projects where project_costcenter = '$costcenter'";
		#WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		%row = $result->fetchhash;
		$projectRowID = $row{'project_id'};
	}
	
	# check if the subject is enrolled in the project
	$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectRowID and project_id = $projectRowID";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		$report .= WriteLog("[$sqlstring]") . "\n";
		my %row = $result->fetchhash;
		$enrollmentRowID = $row{'enrollment_id'};
		$IL_enrollmentcreated = 0;
	}
	else {
		# create enrollmentRowID if it doesn't exist
		$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
		$report .= WriteLog("[$sqlstring]") . "\n";
		my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
		$enrollmentRowID = $result2->insertid;
		$IL_enrollmentcreated = 1;
	}
	
	# now determine if this study exists or not...
	# basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
	# also checks the accession number against the study_num to see if this study was pre-registered
	$sqlstring = "select study_id, study_num from studies where enrollment_id = $enrollmentRowID and (study_datetime = '$StudyDateTime' and study_modality = '$Modality' and study_site = '$StationName')";
	$report .= WriteLog("[$sqlstring]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$studyRowID = $row{'study_id'};
		$study_num = $row{'study_num'};
		
		$sqlstring = "update studies set study_modality = '$Modality', study_datetime = '$StudyDateTime', study_desc = '$StudyDescription', study_operator = '$OperatorsName', study_performingphysician = '$PerformingPhysiciansName', study_site = '$StationName', study_institution = '$InstitutionName - $InstitutionAddress', study_status = 'complete' where study_id = $studyRowID";
		$report .= WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		$IL_studycreated = 0;
	}
	else {
		# create studyRowID if it doesn't exist
		$sqlstring = "SELECT max(a.study_num) 'study_num' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id  WHERE b.subject_id = $subjectRowID";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		%row = $result->fetchhash;
		$study_num = $row{'study_num'} + 1;
		#$study_num = $result->numrows + 1;
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status, study_createdby, study_createdate) values ($enrollmentRowID, $study_num, '$PatientID', '$Modality', '$StudyDateTime', '$StudyDescription', '$OperatorsName', '$PerformingPhysiciansName', '$StationName', '$InstitutionName - $InstitutionAddress', 'complete', 'parseincoming.pl', now())";
		$report .= WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		$studyRowID = $result->insertid;
		$IL_studycreated = 1;
	}
	
	# ----- insert or update the series -----
	$sqlstring = "select " . lc($Modality) . "series_id from " . lc($Modality) . "_series where study_id = $studyRowID and series_num = $SeriesNumber";
	$report .= WriteLog("[$sqlstring]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$seriesRowID = $row{lc($Modality) . "series_id"};
		$sqlstring = "update " . lc($Modality) . "_series set series_datetime = '$SeriesDateTime', series_desc = '$ProtocolName', series_protocol = '$ProtocolName', series_numfiles = '$numfiles', series_notes = '$importSeriesNotes' where " . lc($Modality) . "series_id = $seriesRowID";
		$report .= WriteLog("[$sqlstring]") . "\n";
		$result = SQLQuery($sqlstring, __FILE__, __LINE__);
		$IL_seriescreated = 0;
	}
	else {
		# create seriesRowID if it doesn't exist
		$sqlstring = "insert into " . lc($Modality) . "_series (study_id, series_datetime, series_desc, series_protocol, series_num, series_numfiles, series_notes, series_createdby) values ($studyRowID, '$SeriesDateTime', '$ProtocolName', '$ProtocolName', '$SeriesNumber', '$numfiles', '$importSeriesNotes', 'parsedicom.pl')";
		$report .= WriteLog("[$sqlstring]") . "\n";
		my $result2 = SQLQuery($sqlstring, __FILE__, __LINE__);
		$seriesRowID = $result2->insertid;
		$IL_seriescreated = 1;
	}
		
	# copy the file to the archive, update db info
	$report .= WriteLog("$seriesRowID") . "\n";
	
	# create data directory if it doesn't already exist
	my $outdir = "$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/" . lc($Modality);
	$report .= WriteLog("$outdir") . "\n";
	#mkpath($outdir, {mode => 0777});
	MakePath($outdir);
	
	# move the files into the outdir
	$report .= WriteLog("Moving " . $cfg{'incomingdir'} . "/$importID/$file -> $outdir/$file") . "\n";
	move($cfg{'incomingdir'} . "/$importID/$file","$outdir/$file");

	# insert an import log record
	#$sqlstring = "insert into importlogs (filename_orig, filename_new, fileformat, importstartdate, result, importid, importgroupid, importsiteid, importprojectid, importpermanent, importanonymize, importuuid, modality_orig, patientname_orig, patientdob_orig, patientsex_orig, stationname_orig, institution_orig, studydatetime_orig, seriesdatetime_orig, seriesnumber_orig, studydesc_orig, seriesdesc_orig, protocol_orig, patientage_orig, slicenumber_orig, instancenumber_orig, slicelocation_orig, acquisitiondatetime_orig, contentdatetime_orig, sopinstance_orig, modality_new, patientname_new, patientdob_new, patientsex_new, stationname_new, studydatetime_new, seriesdatetime_new, seriesnumber_new, studydesc_new, seriesdesc_new, protocol_new, patientage_new, subject_uid, study_num, subjectid, studyid, seriesid, enrollmentid, project_number, series_created, study_created, subject_created, family_created, enrollment_created, overwrote_existing) values ('$file', '" . $cfg{'incomingdir'} . "/$importID/$file', '" . uc($Modality) . "', now(), 'successful', '$importID', '$importRowID', '$importSiteID', '$importProjectID', '$importPermanent', '$importAnonymize', '$importUUID', '$IL_modality_orig', '$IL_patientname_orig', '$IL_patientdob_orig', '$IL_patientsex_orig', '$IL_stationname_orig', '$IL_institution_orig', '$IL_studydatetime_orig', '$IL_seriesdatetime_orig', '$IL_seriesnumber_orig', '$IL_studydesc_orig', '$IL_seriesdesc_orig', '$IL_protocolname_orig', '$IL_patientage_orig', '0', '0', '0', '$SeriesDateTime', '$SeriesDateTime', 'Unknown', '$Modality', '$PatientName', '$PatientBirthDate', '$PatientSex', '$StationName', '$StudyDateTime', '$SeriesDateTime', '$SeriesNumber', '$StudyDescription', '$SeriesDescription', '$ProtocolName', '', '$subjectRealUID', '$study_num', '$subjectRowID', '$studyRowID', '$seriesRowID', '$enrollmentRowID', '$costcenter', '$IL_seriescreated', '$IL_studycreated', '$IL_subjectcreated', '$IL_familycreated', '$IL_enrollmentcreated', '$IL_overwrote_existing')";
	#$report .= WriteLog("Inside InsertEEG() [$sqlstring]") . "\n";
	#$result = SQLQuery($sqlstring, __FILE__, __LINE__);

	# delete any rows older than 10 days from the import log
	#$sqlstring = "delete from importlogs where importstartdate < date_sub(now(), interval 10 day)";
	#$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	
	# get the size of the files and update the DB
	my $dirsize;
	($dirsize, $numfiles) = GetDirectorySize($outdir);
	$sqlstring = "update " . lc($Modality) . "_series set series_size = $dirsize where " . lc($Modality) . "series_id = $seriesRowID";
	$report .= WriteLog("[$sqlstring]") . "\n";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);

	# change the permissions to 777 so the webpage can read/write the directories
	WriteLog("Current directory: " . getcwd) . "\n";
	my $systemstring = "chmod -Rf 777 $cfg{'archivedir'}/$subjectRealUID";
	$report .= WriteLog("$systemstring (" . `$systemstring` . ")") . "\n";
	# change back to original directory before leaving
	$report .= WriteLog("Finished changing permissions on $cfg{'archivedir'}/$subjectRealUID") . "\n";
	
	# copy everything to the backup directory
	my $backdir = "$cfg{'backupdir'}/$subjectRealUID/$study_num/$SeriesNumber";
	if (-d $backdir) {
		$report .= WriteLog("Directory [$backdir] already exists") . "\n";
	}
	else {
		$report .= WriteLog("Directory [$backdir] does not exist. About to create it...") . "\n";
		#mkpath($backdir, { verbose => 1, mode => 0777} );
		MakePath($backdir);
		$report .= WriteLog("Finished creating [$backdir]") . "\n";
	}
	$report .= WriteLog("About to copy to the backup directory") . "\n";
	$systemstring = "cp -Rv $cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/* $backdir";
	$report .= WriteLog("$systemstring (" . `$systemstring` . ")") . "\n";
	$report .= WriteLog("Finished copying to the backup directory") . "\n";
	
	return ("", $report);
}


# ----------------------------------------------------------
# --------- CreateThumbnail --------------------------------
# ----------------------------------------------------------
sub CreateThumbnail {
	my ($dir, $type, $xdim, $ydim) = @_;

	# print the ImageMagick version
	#my $systemstring = "which convert";
	#WriteLog("$systemstring (" . trim(`$systemstring 2>&1`) . ")");
	#$systemstring = "convert --version";
	#WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
	
	my $origDir = getcwd;
	
	# get list of dicom files
	chdir("$dir/dicom");
	my @dcmfiles = <*.dcm>;
	
	@dcmfiles = sort @dcmfiles;
	my $numdcmfiles = @dcmfiles;
	my $dcmfile = $dcmfiles[int($numdcmfiles/2)];
	my $outfile = "$dir/thumb.png";

	if ($numdcmfiles < 1) {
		WriteLog("Could not find any DICOM files to create a thumbnail");
		return;
	}
	my $systemstring = "convert -normalize $dir/dicom/$dcmfile $outfile";
	WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");

	# only create animated gif if there are few files... otherwise this is a bottleneck
	# ***** on second thought, don't create the animated gif... it's a bottleneck when doing YUGE datasets *****
	#if ($type eq "epi") {
	#	if ($numdcmfiles < 16) {
	#		my $systemstring = "";
	#		if ($xdim == 384) {
	#			$systemstring = "convert -crop 64x64+256+64\\! -fill white -pointsize 10 -annotate +45+62 '%p' +map -delay 10 -loop 0 +repage *.dcm $dir/thumb.gif";
	#		}
	#		if ($xdim == 518) {
	#			$systemstring = "convert -crop 74x74+296+74\\! -fill white -pointsize 10 -annotate +55+72 '%p' +map -delay 10 -loop 0 +repage *.dcm $dir/thumb.gif";
	#		}
	#		if ($xdim == 672) {
	#			$systemstring = "convert -crop 84x84+336+84\\! -fill white -pointsize 10 -annotate +65+82 '%p' +map -delay 10 -loop 0 +repage *.dcm $dir/thumb.gif";
	#		}
	#		if ($xdim == 658) {
	#			$systemstring = "convert -crop 94x94+376+94\\! -fill white -pointsize 10 -annotate +75+92 '%p' +map -delay 10 -loop 0 +repage *.dcm $dir/thumb.gif";
	#		}
	#		if ($systemstring ne "") {
	#			WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
	#		}
	#	}
	#	else {
	#		WriteLog("EPI sequence contains $numdcmfiles volumes. Not going to create the animated gif");
	#	}
	#}
	#if ($type eq "structural") {
	#	if ($numdcmfiles < 16) {
	#		my $systemstring = "convert -resize 50% -fill white -pointsize 10 -annotate +2+12 '%p' +map -delay 20 -loop 0 *.dcm $dir/thumb.gif";
	#		WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
	#	}
	#	else {
	#		WriteLog("Structural sequence contains $numdcmfiles slices. Not going to create the animated gif");
	#	}
	#}
	
	# change back to original directory before leaving
	chdir($origDir);
	
	return $outfile;
}


# ----------------------------------------------------------
# --------- CreateUID --------------------------------------
# ----------------------------------------------------------
sub CreateUID {
	my ($prefix) = @_;
	
	my $newID;
	
	my $C1 = int(rand(10));
	my $C2 = int(rand(10));
	my $C3 = int(rand(10));
	my $C4 = int(rand(10));
	
	# ASCII codes 65 through 90 are upper case letters
	my $C5 = chr(int(rand(25)) + 65);
	my $C6 = chr(int(rand(25)) + 65);
	my $C7 = chr(int(rand(25)) + 65);
	
	$newID = "$prefix$C1$C2$C3$C4$C5$C6$C7";
	return $newID;
}
