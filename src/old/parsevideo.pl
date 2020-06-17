#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB parsevideo.pl
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
our $scriptname = "parsevideo";
our $lockfileprefix = "parsevideo";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 10;				# number of times this program can be run concurrently

# debugging
our $debug = 0;

# setup the directories to be used
#our $cfg{'logdir'}			= $config{logdir};			# where the log files are kept
#our $cfg{'lockdir'}		= $config{lockdir};			# where the lock files are
#our $cfg{'scriptdir'}		= $config{scriptdir};		# where this program and others are run from
#our $cfg{'incomingdir'}	= $config{incomingdir};		# data is pulled from the dicom server and placed in this directory
#our $cfg{'archivedir'}		= $config{archivedir};		# this is the final place for zipped/recon/formatted subject directories
#our $cfg{'ftpdir'}			= $config{ftpdir};			# local FTP directory
#our $cfg{'mountdir'}		= $config{mountdir};		# directory in which all NFS directories are mounted
#our $cfg{'backupdir'}		= $config{backupdir};		# backup directory. all handled data is copied here


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
	my $x = DoQA();
	close $log;
	#if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoQA ----------------------------------------
# ----------------------------------------------------------
sub DoQA {
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

	# look through DB for all video series
	my $sqlstring = "SELECT videoseries_id from video_series";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $videoseries_id = $row{'videoseries_id'};
			ConvertVideo($videoseries_id);
		}
		WriteLog("Finished converting data");
		$ret = 1;
	}
	else {
		WriteLog("Nothing to do");
	}
	
	# update the stop time
	ModuleDBCheckOut($scriptname, $db);

	return $ret;
}


# ----------------------------------------------------------
# --------- ConvertVideo -----------------------------------
# ----------------------------------------------------------
sub ConvertVideo() {
	my ($seriesid, $format) = @_;

	my $sqlstring;

	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	# find where the files are, build the directory path to the video data
	$sqlstring = "select a.series_num, b.study_num, d.uid 
	from video_series a
	left join studies b on a.study_id = b.study_id
	left join enrollment c on b.enrollment_id = c.enrollment_id
	left join subjects d on c.subject_id = d.subject_id
	left join projects e on c.project_id = e.project_id
	where a.videoseries_id = $seriesid";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $series_num = $row{'series_num'};
			my $study_num = $row{'study_num'};
			my $uid = $row{'uid'};
			
			if ($uid eq '') { WriteLog("UID for $seriesid is empty"); next; }
			if ($study_num eq '') { WriteLog("Study num for $seriesid is empty"); next; }
			if ($series_num eq '') { WriteLog("Series num for $seriesid is empty"); next; }
			
			my $starttime = GetTotalCPUTime();
			
			my $indir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/video";
			WriteLog("Working on [$indir]");
			my $systemstring;
			chdir($indir);
			
			# get list of wmv files in this directory
			my @wmvfiles = <*.wmv>;
			foreach my $wmvfile(@wmvfiles) {
				WriteLog("Working on file [$indir/$wmvfile]");
				
				# check if the ogv file exists
				my $oggfile = $wmvfile;
				$oggfile =~ s/\.wmv/\.ogv/;
				$oggfile =~ s/\s+//g;
				$oggfile =~ s/,//g;
				$oggfile =~ s/\-/_/g;
				
				WriteLog("Checking if [$indir/$oggfile] exists");
				unless (-e "$indir/$oggfile") {
					WriteLog("[$indir/$oggfile] does not exist. Converting to .ogv format");
					$systemstring = "ffmpeg2theora -o '$oggfile' '$wmvfile'";
					WriteLog("[$systemstring] " . `$systemstring`);
					chmod(0777, $oggfile);
					
					# copy everything to the backup directory
					my $backdir = "$cfg{'backupdir'}/$uid/$study_num/$series_num/video";
					if (-d $backdir) {
						WriteLog("Directory [$backdir] already exists");
					}
					else {
						WriteLog("Directory [$backdir] does not exist. About to create it...");
						mkpath($backdir, { verbose => 1, mode => 0777} );
						WriteLog("Finished creating [$backdir]");
					}
					WriteLog("About to copy to the backup directory");
					$systemstring = "cp -R $cfg{'archivedir'}/$uid/$study_num/$series_num/video/* $backdir";
					WriteLog("$systemstring (" . `$systemstring` . ")");
					WriteLog("Finished copying to the backup directory");
				}
			}

			my ($seriessize, $numfiles) = GetDirectorySize($indir);
			
			# calculate the total time running
			my $endtime = GetTotalCPUTime();
			my $cputime = $endtime - $starttime;
				
			$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
			
			# insert this row into the DB
			my $sqlstringC = "update video_series set series_size = '$seriessize', series_numfiles = '$numfiles', video_cputime = '$cputime' where videoseries_id = $seriesid";
			WriteLog("[$sqlstringC]");
			my $resultC = $db->query($sqlstringC) || SQLError($db->errmsg(),$sqlstringC);
		}
	}
}
