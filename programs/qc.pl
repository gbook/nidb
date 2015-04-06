#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB qc.pl
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
use Switch;
use Sort::Naturally;
use Math::Derivative qw(Derivative1 Derivative2);
use List::Util qw(first max maxstr min minstr reduce shuffle sum);
use Time::HiRes qw (sleep);
require 'nidbroutines.pl';

our %cfg;
LoadConfig();

# script specific information
our $scriptname = "qc";
our $lockfileprefix = "qc";		# lock files will be numbered lock.1, lock.2 ...
our $numinstances = 1;			# number of times this program can be run concurrently
our $debug = 0;

our $lockfile;
our $log;
our $db;


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
	my $x = DoQC();
	close $log;
	#if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoQC -------------------------------------------
# ----------------------------------------------------------
sub DoQC {
	# no idea why, but perl is buffering output to the screen, and these 3 statements turn off buffering
	my $old_fh = select(STDOUT);
	$| = 1;
	select($old_fh);
	
	my $old_fh2 = select($log);
	$| = 1;
	select($old_fh2);
	
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my %dicomfiles;
	my $ret = 0;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	WriteLog("Connected to database");

	# check if this module should be running now or not
	if (!ModuleCheckIfActive($scriptname, $db)) {
		WriteLog("Not supposed to be running right now");
		return 0;
	}
	
	# update the start time
	ModuleDBCheckIn($scriptname, $db);

	# get list of active modules
	my $sqlstring = "select * from qc_modules where qcm_isenabled = 1";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $moduleid = $row{'qcmodule_id'};
			my $modality = lc($row{'qcm_modality'});
			
			# look through DB for all series (of this modality) that don't have an associated QCdata row
			#my $sqlstring = "SELECT a.$modality" . "series_id FROM $modality" . "_series a LEFT JOIN qc_moduleseries b ON a.$modality" . "series_id = b.series_id WHERE b.qcmoduleseries_id IS NULL and (b.qc_moduleid = $moduleid or b.qcmodule_id is NULL) order by a.series_datetime desc";
			my $sqlstring = "SELECT $modality" . "series_id FROM $modality" . "_series where $modality" . "series_id not in (select series_id from qc_moduleseries where qcmodule_id = $moduleid) order by series_datetime desc";
			WriteLog("$sqlstring");
			my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
			if ($result->numrows > 0) {
				while (my %row = $result->fetchhash) {
					my $series_id = $row{$modality . 'series_id'};
					QC($moduleid, $series_id, $modality);
					
					# check if this module should be running now or not
					if (!ModuleCheckIfActive($scriptname, $db)) {
						WriteLog("Not supposed to be running right now");
						# update the stop time
						ModuleDBCheckOut($scriptname, $db);
						return 0;
					}
					
					sleep(0.75);
				}
				WriteLog("Finished reconstructing data");
				$ret = 1;
			}
			else {
				WriteLog("Nothing to do");
			}
			
		}
		WriteLog("Finished all modules");
	}
	else {
		WriteLog("No QC modules exist!");
	}
	
	# update the stop time
	ModuleDBCheckOut($scriptname, $db);

	return $ret;
}


# ----------------------------------------------------------
# --------- QC ---------------------------------------------
# ----------------------------------------------------------
sub QC() {
	my ($moduleid, $seriesid, $modality) = @_;

	my $sqlstring;
	
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	$sqlstring = "select * from qc_modules where qcmodule_id = $moduleid";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	my %row = $result->fetchhash;
	my $modulename = $row{'qcm_name'};
	
	# find where the files are, build the directory path to the dicom data
	$sqlstring = "select a.series_num, a.is_derived, a.data_type, b.study_num, d.uid, e.project_costcenter 
	from $modality" . "_series a
	left join studies b on a.study_id = b.study_id
	left join enrollment c on b.enrollment_id = c.enrollment_id
	left join subjects d on c.subject_id = d.subject_id
	left join projects e on c.project_id = e.project_id
	where a.$modality" . "series_id = '$seriesid'";
	#WriteLog("$sqlstring");
	
	WriteLog("Running $moduleid on $modality series $seriesid");
	#return;
	my $qcmoduleseriesid;

	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		WriteLog("Found " . $result->numrows);
		my $numProcessed = 0;
		while (my %row = $result->fetchhash) {
			my $series_num = $row{'series_num'};
			my $study_num = $row{'study_num'};
			my $is_derived = $row{'is_derived'};
			my $uid = $row{'uid'};
			my $project_costcenter = $row{'project_costcenter'};
			my $datatype = $row{'data_type'};
			my $mrqaid;
			
			#WriteLog("Checkpoint A");
			# check if this qc_moduleseries row exists
			my $sqlstringA = "select * from qc_moduleseries where series_id = $seriesid and modality = '$modality' and qcmodule_id = $moduleid";
			my $resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
			if ($resultA->numrows > 0) {
				# if a row does exist, go onto the next row
				next;
			}
			else {
				# insert a blank row for this qc_moduleseries and get the row ID
				my $sqlstringB = "insert ignore into qc_moduleseries (qcmodule_id, series_id, modality) values ($moduleid, $seriesid, '$modality')";
				WriteLog("[$sqlstringB]");
				my $resultB = $db->query($sqlstringB) || SQLError($db->errmsg(),$sqlstringB);
				$qcmoduleseriesid = $resultB->insertid;
			}
			
			my $starttime = GetTotalCPUTime();
			
			my $qcpath = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa";
			mkpath($qcpath,1,0777);
			chmod(0777,$qcpath);
			WriteLog("Working on [$qcpath]");
			#my $systemstring;
			if ($cfg{'usecluster'}) {
				# submit this module to the cluster
				# create the SGE job file
				WriteLog("About to create the SGE job file");
				my $sgebatchfile = CreateSGEJobFile($modulename, $qcmoduleseriesid, $qcpath);
			
				#WriteLog($sgebatchfile);
				# submit the SGE job
				my $sgefile = "sge-" . GenerateRandomString(10) . ".job";
				open SGEFILE, "> $qcpath/$sgefile";
				print SGEFILE $sgebatchfile;
				close SGEFILE;
				chmod(0777,"$qcpath/$sgefile");
				
				WriteLog("Submitting to cluster: [ssh $cfg{'clustersubmithost'} $cfg{'qsubpath'} -u $cfg{'queueuser'} -q $cfg{'queuename'} \"$qcpath/$sgefile\"]");
				
				# submit the sucker to the cluster
				WriteLog(`ssh $cfg{'clustersubmithost'} $cfg{'qsubpath'} -u $cfg{'queueuser'} -q $cfg{'queuename'} "$qcpath/$sgefile"`);
			}
			else {
				chdir("$cfg{'qcmoduledir'}/$modulename");
				WriteLog("Running the following file: [$cfg{'qcmoduledir'}/$modulename/$modulename.sh $qcmoduleseriesid]");
				my $systemstring = "$cfg{'qcmoduledir'}/$modulename/./$modulename.sh $qcmoduleseriesid";
				print "$systemstring\n";
				WriteLog("$systemstring (" . `$systemstring` . ")");
			}

			# calculate the total time running
			my $endtime = GetTotalCPUTime();
			my $cputime = $endtime - $starttime;
			
			$sqlstringA = "update qc_moduleseries set cpu_time = $cputime where qcmoduleseries_id = $qcmoduleseriesid";
			$resultA = $db->query($sqlstringA) || SQLError($db->errmsg(),$sqlstringA);
			
			# only process 10 before exiting the script. Since the script always starts with the newest when it first runs,
			# this will allow studies collected since the script started a chance to be QC'd
			$numProcessed = $numProcessed + 1;
			#if ($numProcessed > 5) {
			#	last;
			#}
			
			sleep(10);
		}
	}
	else {
		WriteLog("No series to process");
		# there were no series to process
	}
}


# ----------------------------------------------------------
# --------- CreateSGEJobFile -------------------------------
# ----------------------------------------------------------
sub CreateSGEJobFile() {
	my ($modulename, $qcmoduleseriesid, $qcpath) = @_;

	print "Analysis path: $qcpath\n";
	
	# check if any of the variables might be blank
	if (trim($modulename) eq "") { return ""; }
	if (trim($qcmoduleseriesid) eq "") { return ""; }
	if (trim($qcpath) eq "") { return ""; }
	
	my $jobfile = "";
	
	$jobfile .= "#!/bin/sh\n";
	$jobfile .= "#\$ -N NIDB-QC-$modulename\n";
	$jobfile .= "#\$ -S /bin/sh\n";
	$jobfile .= "#\$ -j y\n";
	$jobfile .= "#\$ -V\n";
	$jobfile .= "#\$ -o $qcpath\n";
	$jobfile .= "#\$ -u onrc\n\n";
	$jobfile .= "cd $cfg{'qcmoduledir'}/$modulename\n";
	$jobfile .= "$cfg{'qcmoduledir'}/$modulename/./$modulename.sh $qcmoduleseriesid\n";
	
	return $jobfile;
}
