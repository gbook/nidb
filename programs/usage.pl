#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB usage.pl
# Copyright (C) 2004 - 2016
# Gregory A Book <gregory.book@hhchealth.org> <gregory.a.book@gmail.com>
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

# ------------------------------------------------------------------------------
# This program will calculate usage for each instance and produce a monthly invoice
# ------------------------------------------------------------------------------


use strict;
use warnings;
use Mysql;
use DBI;
use File::Copy;
use File::Copy::Recursive;
use File::Path;
use Switch;
use Cwd;
use Sort::Naturally;
use Net::SMTP::TLS;
use Data::Dumper;

require 'nidbroutines.pl';

# -------------- variables declariation ---------------------------------------
#my %config = do 'config.pl';
our %cfg;
LoadConfig();

# database variables
our $db;
# script specific information
our $scriptname = "usage";
our $lockfileprefix = "usage";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently
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
	my $x = &CalculateUsage();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);

# --------------------------------------------------------
# -------------- CalculateUsage ------------------------
# --------------------------------------------------------
# The main function, which finds all studies which do not
# have an entry in the analysis table
# --------------------------------------------------------
sub CalculateUsage() {
	# no idea why, but perl is buffering output to the screen, and these 3 statements turn off buffering
	my $old_fh = select(STDOUT);
	$| = 1;
	select($old_fh);

	my $numchecked = 0;
	my $jobsWereSubmitted = 0;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");

	# update the start time
	SetModuleRunning();
	
	# check if this module should be running now or not
	if (!ModuleCheckIfActive($scriptname, $db)) {
		WriteLog("Not supposed to be running right now");
		SetModuleStopped();
		return 0;
	}
	
	# loop through all instances
	my $sqlstring = "select * from instance";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $instanceid = $row{'instance_id'};
			
			my $totalenrollments = 0;
			my $totalgb = 0;
			
			# get all projects
			my $sqlstringA = "select * from projects where instance_id = $instanceid";
			my $resultA = $db->query($sqlstringA) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringA);
			if ($resultA->numrows > 0) {
				while (my %rowA = $resultA->fetchhash) {
					my $projectid = $rowA{'project_id'};
					
					# get all enrollments
					my $sqlstringB = "select * from enrollment where project_id = $projectid";
					my $resultB = $db->query($sqlstringB) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringB);
					if ($resultB->numrows > 0) {
						while (my %rowB = $resultB->fetchhash) {
							my $enrollmentid = $rowB{'enrollment_id'};
							my $subjectid = $rowB{'subject_id'};
					
							# get only enrollments that have studies/assessments/measures attached AND for subjects who are active
							
							# check if the subject is active
							my $sqlstringC = "select isactive, uid from subjects where subject_id = $subjectid";
							my $resultC = $db->query($sqlstringC) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringC);
							my %rowC = $resultC->fetchhash;
							my $uid = $rowC{'uid'};
							if ($rowC{'isactive'} == 0) {
								# this subject isn't active, therefore the enrollment shouldn't be counted
								next;
							}
							
							my $enrollmentHasData = 0;
							
							# check if the enrollment has any active studies
							$sqlstringC = "select * from studies where enrollment_id = $enrollmentid and study_isactive = 1";
							$resultC = $db->query($sqlstringC) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringC);
							if ($resultC->numrows > 0) {
								$enrollmentHasData = 1;
							}
							
							# check if the enrollment has any active assessments
							$sqlstringC = "select * from assessments where enrollment_id = $enrollmentid";
							$resultC = $db->query($sqlstringC) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringC);
							if ($resultC->numrows > 0) {
								$enrollmentHasData = 1;
							}
							
							# check if the enrollment has any active measures
							$sqlstringC = "select * from measures where enrollment_id = $enrollmentid";
							$resultC = $db->query($sqlstringC) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringC);
							if ($resultC->numrows > 0) {
								$enrollmentHasData = 1;
							}
							
							# they're enrolled, but there's no data, so don't count this enrollment
							if (!$enrollmentHasData) {
								next;
							}
							
							$totalenrollments += 1;
							
							# get the data size of all series
							$sqlstringC = "select * from studies where enrollment_id = $enrollmentid and study_isactive = 1";
							$resultC = $db->query($sqlstringC) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringC);
							if ($resultC->numrows > 0) {
								while (my %rowC = $resultC->fetchhash) {
									my $studyid = $rowC{'study_id'};
									my $studynum = $rowC{'study_num'};
									my $modality = lc($rowC{'study_modality'});
									
									if (trim($modality) ne '') {
										my $sqlstringD = "select sum(series_size) 'seriessize' from $modality" . "_series where study_id = $studyid";
										my $resultD = $db->query($sqlstringD) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringD);
										my %rowD = $resultD->fetchhash;
										my $seriesize = $rowD{'seriessize'};
										$totalgb += $seriesize;
									}
								}
							}
							
							print "Total: enroll [$totalenrollments] GB [" . $totalgb/1000/1000/1000 . "]\n";
						}
					}
				}
			}
			
			$totalgb = $totalgb/1000/1000/1000;
			$sqlstringA = "insert into instance_usage (instance_id, usage_date, pricing_id, usage_amount) values ($instanceid, date_sub(now(), interval 1 day), 2, $totalgb)";
			my $resultA = $db->query($sqlstringA) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringA);
			$sqlstringA = "insert into instance_usage (instance_id, usage_date, pricing_id, usage_amount) values ($instanceid, date_sub(now(), interval 1 day), 3, $totalenrollments)";
			my $resultA = $db->query($sqlstringA) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringA);
		}
	}
	else {
		WriteLog("No Instances");
	}
	
	SetModuleStopped();
	return 1;
}
