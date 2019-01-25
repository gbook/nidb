#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB parsesnp.pl
# Copyright (C) 2004 - 2019
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
# This program imports SNP data
# 
# [4/11/2013] - Greg Book
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
our $scriptname = "parsesnp";
our $lockfileprefix = "parsesnp";	# lock files will be numbered lock.1, lock.2 ...
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
my ($costcenter, $datadir, $build);
my $r = GetOptions ("project|p=s" => \$costcenter, "datadir|d=s" => \$datadir, "build|b=s" => \$build);

if (!defined($costcenter)) {
	print "Project number not defined. Use 6-digit #, ex '999999'";
	DisplayUsage();
	exit(0);
}
if (!defined($datadir)) {
	print "Data directory not defined. Specify absolute directory of the SNP files, ex: /nidb/import/SNP";
	DisplayUsage();
	exit(0);
}
if (!defined($build)) {
	print "Build not defined. Specify a unique build, ex: 'GenomeBuild-36.3'";
	DisplayUsage();
	exit(0);
}

# create a log file and do the import
my $logfilename;
$logfilename = "$cfg{'logdir'}/$scriptname" . CreateLogDate() . ".log";
open $log, '> ', $logfilename;
my $x = DoParse($costcenter, $datadir, $build);
close $log;

exit(0);


# ----------------------------------------------------------
# --------- DoParse ----------------------------------------
# ----------------------------------------------------------
sub DoParse {
	my ($costcenter, $datadir, $StudySite) = @_;
	
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my $ret = 0;
	my $i = 0;
	my $alternateuid;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	print "Connected to database\n";

	# ----- get list of files in the data directory -----
	# we should really only be expecting 3 files (.bed .bim .fam)
	opendir(DIR,$datadir) || die("Cannot open directory (1) $datadir!\n");
	my @dirs = readdir(DIR);
	closedir(DIR);
	WriteLog("$datadir");
	foreach my $file (sort @dirs) {
		if ( ($file ne ".") && ($file ne "..") ) {
			if (!-d "$datadir/$file") {
				# check for the .fam file, which will give a list of the included subjects
				if ($file =~ /\.fam$/) {
				
					chdir($datadir);
					
					my $plinkprefix = $file;
					$plinkprefix =~ s/\.fam//;
					# get the file into an array
					open(FILE, "$datadir/$file") || die ("Unable to open file [$file]");
					my @famfile = <FILE>;
					close(FILE);
					
					# read the file line by line
					foreach my $line (@famfile) {
						my ($id, $famid, $u1, $u2, $sex, $group) = split(/\s+/, $line);
						print "$id $famid\n";
						open(F, "> $datadir/$id.txt") || die ("Could not create file [$datadir/$id.txt: $!]");
						print F "$id $famid\n";
						close(F);
						
						my $systemstring = "$cfg{'scriptdir'}/./plink --noweb --bfile $plinkprefix --keep $id.txt --make-bed --out $id";
						WriteLog("$systemstring (" . `$systemstring` . ")");
						
						InsertStudy($datadir, $costcenter, $id, $build);
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
	
	return $ret;
}


# ----------------------------------------------------------
# --------- InsertStudy ------------------------------------
# ----------------------------------------------------------
sub InsertStudy {
	my ($datadir, $costcenter, $altuid, $build) = @_;
	
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
	my $PatientName;
	my $SeriesNumber = "";
	my $StudyDateTime = "2011-06-01 00:00:00";
	my $SeriesDateTime = "2011-06-01 00:00:00";
	my $Modality = "SNP";
	my $StudyDesc = "SNP";
	my $StudyOperator = "Unknown";
	my $StudyPerformingPhysician = "Andreas Windemuth";
	my $StudyInstitution = "Genomas Inc";
	my $StudySite = $build;
	my $SeriesDesc = "SNP data";
	
	$studyRowID = "";
	$seriesRowID = "";
	
	my $id = $altuid;
	$altuid =~ s/GP//g;
	
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
	$sqlstring = "SELECT * FROM `subjects` WHERE (altuid1 = '$altuid' or altuid2 = '$altuid' or altuid3 = '$altuid') and isactive = 1";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$subjectRowID = $row{'subject_id'};
		$subjectRealUID = $row{'uid'};
		WriteLog("Found subject ID: $subjectRowID");
	}
	else {
		# report that this altUID was not found
		print "$altuid not found\n";
		open(EF, ">> $datadir/errors.txt") || die ("Could not create file [$datadir/errors.txt: $!]");
		print EF "$altuid not found, data not copied\n";
		close(EF);
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
		WriteLog("[$sqlstring]");
		my $result2 = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$enrollmentRowID = $result2->insertid;
	}
	
	# create studyRowID
	$sqlstring = "SELECT * FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = $subjectRowID";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	$studyNum = $result->numrows + 1;
	
	$sqlstring = "insert into studies (enrollment_id, study_num, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status, study_alternateid, study_createdby) values ($enrollmentRowID, $studyNum, '$Modality', '$StudyDateTime', '$StudyDesc', '$StudyOperator', '$StudyPerformingPhysician', '$StudySite', '$StudyInstitution', 'complete', '$id', 'parsesnp.pl')";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	$studyRowID = $result->insertid;

	# get the next series number. never update a series, only insert new ones
	$sqlstring = "select * from snp_series where study_id = $studyRowID";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	$SeriesNumber = $result->numrows + 1;
	
	# create seriesRowID if it doesn't exist
	$sqlstring = "insert into snp_series (study_id, series_datetime, series_desc, series_protocol, series_num, series_createdby, series_notes) values ($studyRowID, '$SeriesDateTime', '$SeriesDesc', '$SeriesDesc', '$SeriesNumber', 'parsesnp.pl', concat('Data imported by parsesnp.pl on ', now()))";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	$seriesRowID = $result->insertid;
				
	# copy the file to the archive, update db info
	WriteLog("$seriesRowID");
	
	# create data directory if it doesn't already exist
	my $outdir;
	$outdir = "$cfg{'archivedir'}/$subjectRealUID/$studyNum/$SeriesNumber/snp";
	WriteLog("Creating path $outdir");
	mkpath($outdir, {mode => 0777});
	
	my $systemstring = "mv $datadir/$id.* $outdir";
	WriteLog(`$systemstring`);
	#copy($filepath,"$outdir/$file");
	
	# get the size and number of the files and update the DB
	my ($dirsize, $numfiles) = GetDirectorySize($outdir);
	$sqlstring = "update snp_series set series_size = $dirsize, series_numfiles = $numfiles where snpseries_id = $seriesRowID";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);

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


# -------------------------------------------------------------------
# ----------- DisplayUsage ------------------------------------------
# -------------------------------------------------------------------
sub DisplayUsage {
	my ($str) = @_;
	
	print "
	
	parsesnp.pl usage:
	
    -p, --project            * 6-digit project number
    -d, --datadir            * path to the unencrypted, unzipped data
    -b, --build              * SNP chip build (ex GenomeBuild-36.6)
	
    (* required)

    Example:
	    perl parsesnp.pl -p 999999 -d /path/to/snp/data -b GenomeBuild-36.3

";	
}