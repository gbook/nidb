#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB modulemanager.pl
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
# This program checks the 'module_procs' table in the database and deletes
# any lock files from modules which have not checked in in 
# 
# [2/23/2017] - Greg Book
#		* Wrote initial program.
# -----------------------------------------------------------------------------

use strict;
use warnings;
use Mysql;
use Data::Dumper;
use File::Path;
use File::Copy;
use Cwd;
use Date::Manip;

require 'nidbroutines.pl';
our %cfg;
LoadConfig();

our $db;

# script specific information
our $scriptname = "modulemanager";
our $lockfileprefix = "modulemanager";	# lock files will be numbered lock.1, lock.2 ...
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
	my $x = DoModuleManagement();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoModuleManagement -----------------------------
# ----------------------------------------------------------
sub DoModuleManagement {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

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
	
	# get list of modules with a last checkin older than 1 hours
	my $sqlstring = "select * from module_procs where last_checkin < date_sub(now(), interval 1 hour)";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $modulename = $row{'module_name'};
			my $pid = $row{'process_id'};
			my $lastcheckin = $row{'last_checkin'};
			
			my $lockfile = "$modulename.$pid";
			
			print "Deleting [$cfg{'scriptdir'}/lock/$lockfile] last checked in on [$lastcheckin]\n";
			my $systemstring = "rm $cfg{'scriptdir'}/lock/$lockfile";
			print `$systemstring`;
			
			my $sqlstringA = "delete from module_procs where module_name = '$modulename' and process_id = '$pid'";
			my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
	
			$ret = 1;
		}
	}
	else {
		print "Found no lock files to delete\n";
	}
	
	# update the stop time
	ModuleDBCheckOut($scriptname, $db);
	WriteLog("normal stop");
	
	return $ret;
}


