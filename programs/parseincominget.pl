#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB parseincominget.pl
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
# This program reads from a special et incoming directory, populates the database, and
# moves the non-dicom files to their archive location
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
use Cwd;
use Switch;
use Sort::Naturally;

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
our $scriptname = "parseincominget";
our $lockfileprefix = "parseincominget";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently

# debugging
our $debug = 0;

# setup the directories to be used
#our $cfg{'logdir'}			= $config{logdir};			# where the log files are kept
#our $cfg{'lockdir'}		= $config{lockdir};			# where the lock files are
#our $cfg{'scriptdir'}		= $config{scriptdir};		# where this program and others are run from
#our $cfg{'incomingdir'}	= $config{incomingdir};		# DICOM data from the dicom receiver is placed here
#our $incoming2dir	= $config{incoming2dir};	# Non-DICOM data is placed here
#our $cfg{'archivedir'}		= $config{archivedir};		# this is the final place for dicom data
#our $cfg{'backupdir'}		= $config{backupdir};		# backup directory. all handled data is copied here
#our $cfg{'ftpdir'}			= $config{ftpdir};			# local FTP directory
#our $cfg{'mountdir'}		= $config{mountdir};		# directory in which all NFS directories are mounted


# ------------- end variable declaration --------------------------------------
# -----------------------------------------------------------------------------


# check if this program can run or not
#if (CheckNumLockFiles($lockfileprefix, $cfg{'lockdir'}) >= $numinstances) {
#	print "Can't run, too many of me already running\n";
#	exit(0);
#}
#else {
	my $logfilename;
#	($lockfile, $logfilename) = CreateLockFile($lockfileprefix, $cfg{'lockdir'}, $numinstances);
	#my $logfilename = "$lockfile";
	$logfilename = "$cfg{'logdir'}/$scriptname" . CreateLogDate() . ".log";
	open $log, '> ', $logfilename;
	my $x = DoParse();
	close $log;
#	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
#	print "Done. Deleting $lockfile\n";
#	unlink $lockfile;
#}

exit(0);


# ----------------------------------------------------------
# --------- DoParse ----------------------------------------
# ----------------------------------------------------------
sub DoParse {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my $ret = 0;
	my $i = 0;
	my $costcenter = "126185";
	my $datadir = "/mount/mammoth/data2/displaypc/EYETRACKINGDATA/B-SNIP\ DATA";
	my $alternateuid;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	print "Connected to database\n";
	
	# ----- parse all files in /incoming -----
	opendir(DIR,$datadir) || die("Cannot open directory (1) $datadir!\n");
	my @dirs = readdir(DIR);
	closedir(DIR);
	print "$datadir\n";
	foreach my $dir (@dirs) {
		if ( ($dir ne ".") && ($dir ne "..") ) {
			if (-d "$datadir/$dir") {
				$alternateuid = uc(trim($dir));
				if ($alternateuid =~ /^(GP[0-9]{4})/) {
					$alternateuid = $1;
				#if ($alternateuid =~ /^M0[0-9]{7}$/) {
					#$alternateuid =~ s/\_FOLLOWUP//g;
					#chdir($datadir);
					print "$dir\n";
					print "InsertSeries($dir, $datadir, $costcenter, $alternateuid)\n";
					InsertSeries($dir, $datadir, $costcenter, $alternateuid);
					#last;
				}
				else {
					print "$alternateuid not in correct format\n";
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
#	$sqlstring = "update modules set module_laststop = now(), module_status = 'stopped' where module_name = '$scriptname'";
#	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	
	return $ret;
}


# ----------------------------------------------------------
# --------- InsertSeries -----------------------------------
# ----------------------------------------------------------
sub InsertSeries {
	my ($dir, $datadir, $costcenter, $alternateuid) = @_;
	
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
	my $SeriesNumber = "0";
	my $StudyDateTime;
	my $SeriesDateTime;
	my $Modality = "ET";
	my $StudyDesc = "ET";
	my $StudyOperator;
	my $StudyPerformingPhysician = "Pearlson";
	my $StudySite = "EyeLink II";
	my $StudyInstitution = "Institute of Living - ONRC";
	my $SeriesDesc;
	
	my $i = 1;
	
	$studyRowID = "";
	$seriesRowID = "";
	
	print "Parsing $dir\n";
	#my $dirpath = cetcwd() . "/$file";
	
	# loop through the files in this directory to determine the date and operator
	opendir(DIR,"$datadir/$dir") || die("Cannot open directory (1) $datadir!\n");
	my @files = readdir(DIR);
	closedir(DIR);
	foreach my $file (@files) {
		if ( ($file ne ".") && ($file ne "..") ) {
			my $filepath = "$datadir/$dir/$file";
			my $ext = lc(($file =~ m/([^.]+)$/)[0]);
			print "$filepath [[$ext]]\n";
			if ($ext eq "edf") {
				#my ($junk, $studydate, $taskdesc, $operator) = split(/_/, $file);
				my ($altid, $studydate, $project, $taskdesc, $run, $operator);
				my @parts = split(/_/, $file);
				if (scalar(@parts) == 5) {
					($altid, $studydate, $project, $taskdesc, $operator) = split(/_/, $file);
					$operator =~ s/\.edf//g;
				}
				elsif (scalar(@parts) == 6) {
					($altid, $studydate, $project, $taskdesc, $run, $operator) = split(/_/, $file);
				}
				else {
					next;
				}
				
				$operator =~ s/\.edf//g;
				if (trim($StudyDateTime) eq "") {
					if (length($studydate) == 8) {
						$StudyDateTime = substr($studydate,4,4) . "-" . substr($studydate,0,2) . "-" . substr($studydate,2,2) . " 12:00:00";
						$SeriesDateTime = $StudyDateTime;
					}
					if (length($studydate) == 6) {
						$StudyDateTime = "20" . substr($studydate,4,2) . "-" . substr($studydate,0,2) . "-" . substr($studydate,2,2) . " 12:00:00";
						$SeriesDateTime = $StudyDateTime;
					}
				}
				if (trim($StudyOperator) eq "") {
					$StudyOperator = $operator;
				}
				#last;
			}
		}
	}
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
	$sqlstring = "SELECT * FROM `subjects` WHERE altuid1 = '$alternateuid' or altuid2 = '$alternateuid' or altuid3 = '$alternateuid'";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$subjectRowID = $row{'subject_id'};
		$subjectRealUID = $row{'uid'};
		WriteLog("Found subject ID: $subjectRowID");
	}
	else {
		WriteLog("Found no subject ID");
		return;
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
		print "[$sqlstring]\n";
		my $result2 = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$enrollmentRowID = $result2->insertid;
	}
	
	# create studyRowID if it doesn't exist
	if ($studyRowID eq "") {
		$sqlstring = "SELECT * FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = $subjectRowID";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$studyNum = $result->numrows + 1;
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status) values ($enrollmentRowID, $studyNum, '$Modality', '$StudyDateTime', '$StudyDesc', '$StudyOperator', '$StudyPerformingPhysician', '$StudySite', '$StudyInstitution', 'complete')";
		print "[$sqlstring]\n";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$studyRowID = $result->insertid;
	}

	#return 0;
	
	# loop through the files again and insert them into the database
	foreach my $file (nsort @files) {
		if ( ($file ne ".") && ($file ne "..") ) {
			my $filepath = "$datadir/$dir/$file";
			my $ext = lc(($file =~ m/([^.]+)$/)[0]);
			
			if ($ext eq "edf") {
				my ($altid, $studydate, $project, $taskdesc, $run, $operator);
				my @parts = split(/_/, $file);
				if (scalar(@parts) == 5) {
					($altid, $studydate, $project, $taskdesc, $operator) = split(/_/, $file);
				}
				elsif (scalar(@parts) == 6) {
					($altid, $studydate, $project, $taskdesc, $run, $operator) = split(/_/, $file);
					$taskdesc = $taskdesc . "-$run";
				}
				else {
					next;
				}
				$operator =~ s/\.edf//g;
				
				print "$filepath [[$ext]]\n";
				print "parsed string ($altid, $studydate, $project, $taskdesc, $run, $operator)\n";
				$SeriesNumber++;
				if (uc(trim($taskdesc)) eq "ANTIPRAC") { $SeriesDesc = "Anti-saccade practice"; }
				elsif (uc(trim($taskdesc)) eq "ANTI") { $SeriesDesc = "Anti-saccade"; }
				elsif (uc(trim($taskdesc)) eq "PRO") { $SeriesDesc = "Pro-saccade"; }
				else {
					$SeriesDesc = $taskdesc;
				}
				
				
				# insert the series, using the info from the previous operations
				my $sqlstring3 = "select * from et_series where study_id = $studyRowID and series_datetime = '$SeriesDateTime' and series_num = $SeriesNumber and series_protocol = '$SeriesDesc'";
				my $result3 = $db->query($sqlstring3) || SQLError($db->errmsg(),$sqlstring3);
				if ($result3->numrows > 0) {
					my %row3 = $result3->fetchhash;
					$seriesRowID = $row3{'etseries_id'};
				}
				else {
					$sqlstring = "insert into et_series (study_id, series_datetime, series_num, series_protocol) values ($studyRowID, '$SeriesDateTime', $SeriesNumber, '$SeriesDesc')";
					print "[$sqlstring]\n";
					my $result2 = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
					$seriesRowID = $result2->insertid;
				}
				
				# copy the file to the archive, update db info
				print "$seriesRowID\n";
				
				# create data directory if it doesn't already exist
				my $outdir;
				$outdir = "$cfg{'archivedir'}/$subjectRealUID/$studyNum/$SeriesNumber/et";
				print "Creating path $outdir\n";
				mkpath($outdir, {mode => 0777});
				
				print "copy($filepath,$outdir/$file)";
				copy($filepath,"$outdir/$file");
				
				# get the size and number of the files and update the DB
				my ($dirsize, $numfiles) = GetDirectorySize($outdir);
				$sqlstring = "update et_series set series_size = $dirsize, series_numfiles = $numfiles where etseries_id = $seriesRowID";
				$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);

				# change the permissions to 777 so the webpage can read/write the directories
				my $origDir = getcwd;
				chdir("$cfg{'archivedir'}");
				my $systemstring = "chmod -Rf 777 $subjectRealUID";
				print "$systemstring (" . `$systemstring` . ")\n";
				# change back to original directory before leaving
				chdir($origDir);
				
				# copy everything to the backup directory
				my $backdir = "$cfg{'backupdir'}/$subjectRealUID/$studyNum/$SeriesNumber";
				mkpath($backdir, {mode => 0777});
				$systemstring = "cp -R $cfg{'archivedir'}/$subjectRealUID/$studyNum/$SeriesNumber/* $backdir";
				print "$systemstring (" . `$systemstring` . ")\n";
			}
		}
	}
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
