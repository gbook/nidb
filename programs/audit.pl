#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB usage.pl
# Copyright (C) 2004 - 2017
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
use Image::ExifTool;

require 'nidbroutines.pl';

# -------------- variables declariation ---------------------------------------
#my %config = do 'config.pl';
our %cfg;
LoadConfig();

# database variables
our $db;
# script specific information
our $scriptname = "audit";
our $lockfileprefix = "audit";		# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently
# debugging
our $debug = 0;
our $audittype = "full";			# 'quick' or 'full'

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
	my $x = &Audit();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);

# --------------------------------------------------------
# -------------- Audit ------------------------
# --------------------------------------------------------
sub Audit() {
	# no idea why, but perl is buffering output to the screen, and these 3 statements turn off buffering
	my $old_fh = select(STDOUT);
	$| = 1;
	select($old_fh);

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");

	# update the start time
	SetModuleRunning();
	
	# check if this module should be running now or not
	if (!ModuleCheckIfActive($scriptname, $db)) {
		print "Module is currently not enabled\n";
		WriteLog("Not supposed to be running right now");
		SetModuleStopped();
		return 0;
	}
	
	# ********** 1) check if entries in the database exist in the filesystem **********
	# get new audit number
	my $sqlstring = "select max(audit_num) 'newauditnum' from audit_results";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	my %row = $result->fetchhash;
	my $auditnum = $row{'newauditnum'} + 1;
	
	$sqlstring = "select * from subjects order by uid";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	my $numSubjects = $result->numrows;
	my $ii = 1;
	while (my %row = $result->fetchhash) {
		
		my $uid = $row{'uid'};
		my $SubjectID = $row{'subject_id'};
		my $SubjectName = $row{'name'};
		my $SubjectBirthdate = $row{'birthdate'};
		my $SubjectSex = $row{'gender'};
		my @altuids;
		my $sqlstring1 = "select altuid from subject_altuid where subject_id = '$SubjectID' order by altuid";
		my $result1 = SQLQuery($sqlstring1, __FILE__, __LINE__);
		while (my %row1 = $result1->fetchhash) {
			push @altuids, $row1{'altuid'};
		}

		# CHANGE
		# 04/27/2017
		if ($uid eq '') {
			print "UID does not exists for subject $SubjectID";
			my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtofile','empty uid', '$SubjectID', now())";
			my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
		}



	}
	
	SetModuleStopped();
	return 1;
}


# ----------------------------------------------------------
# --------- FlipName ---------------------------------------
# ----------------------------------------------------------
sub FlipName {
	my ($n) = @_;
	
	my @parts = split(/\^/,$n);
	
	if (scalar @parts > 1) {
		my $ret = $parts[1] . '^' . $parts[0];
		print " [$n -> $ret] ";
		return $ret;
	}
	else {
		return $n;
	}
}


# ----------------------------------------------------------
# --------- InArray ----------------------------------------
# ----------------------------------------------------------
sub InArray {
	my ($e, @a) = @_;
	
	my $inarray = 0;
	foreach my $i (@a) {
		if ("$i" eq "$e") {
			$inarray = 1;
			last;
		}
	}
	
	return $inarray;
}
