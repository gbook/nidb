#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB recondicom.pl
# Copyright (C) 2011 - 2013
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
our $scriptname = "recondicom";
our $lockfileprefix = "recondicom";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently

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
	my $x = DoRecon();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoRecon ----------------------------------------
# ----------------------------------------------------------
sub DoRecon {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my %dicomfiles;
	my $ret = 0;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	WriteLog("Connected to database");

	# look through DB for all series that don't have an associated mr_recon row
	my $sqlstring = "SELECT a.mrseries_id FROM mr_series a LEFT JOIN mr_recon b ON a.mrseries_id = b.mrseries_id WHERE b.mrrecon_id IS NULL";
	my $result = $db->query($sqlstring);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $mrseries_id = $row{'mrseries_id'};
			#ConvertDicom($mrseries_id, "nifti4D");
			ConvertDicom($mrseries_id, "nifti3D");
			#ConvertDicom($mrseries_id, "analyze4D");
			ConvertDicom($mrseries_id, "analyze3D");
		}
		WriteLog("Finished reconstructing data");
		$ret = 1;
	}
	else {
		WriteLog("Nothing to do");
	}

	return $ret;
}


# ----------------------------------------------------------
# --------- ConvertDicom -----------------------------------
# ----------------------------------------------------------
sub ConvertDicom() {
	my ($seriesid, $format) = @_;

	my $sqlstring;

	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	WriteLog("Working on series ID [$seriesid]");
	
	# find where the files are
	$sqlstring = "select a.series_num, b.study_num, d.uid, e.project_costcenter 
	from mr_series a
	left join studies b on a.study_id = b.study_id
	left join enrollment c on b.enrollment_id = c.enrollment_id
	left join subjects d on c.subject_id = d.subject_id
	left join projects e on c.project_id = e.project_id
	where a.mrseries_id = $seriesid";
	my $result = $db->query($sqlstring);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $series_num = $row{'series_num'};
			my $study_num = $row{'study_num'};
			my $uid = $row{'uid'};
			my $project_costcenter = $row{'project_costcenter'};

			my $starttime = GetTotalCPUTime();
			
			my $indir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/dicom";
			WriteLog("Working on [$indir]");
			my $outdir;
			my $systemstring;
			chdir($indir);
			switch ($format) {
				case "nifti4D" {
					# nifti 4D
					$outdir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti4d";
					$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y -g y -p n -i n -d n -f n -o '$outdir' *.dcm";
				}
				case "nifti3D" {
					# nifti 3D
					$outdir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/nifti3d";
					$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_3D.ini' -a y -e y -g y -p n -i n -d n -f n -o '$outdir' *.dcm";
				}
				case "analyze4D" {
					# analyze 4D
					$outdir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/analyze3d";
					$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y -g n -p n -i n -d n -f n -n n -s y -o '$outdir' *.dcm";
				}
				case "analyze3D" {
					# analyze 3D
					$outdir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/analyze3d";
					$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_3D.ini' -a y -e y -g n -p n -i n -d n -f n -n n -s y -o '$outdir' *.dcm";
				}
			}
			
			mkpath($outdir, {mode => 0777});
			# delete any files that may already be in the output directory.. example, an incomplete series was put in the output directory
			# remove any stuff and start from scratch to ensure proper file numbering
			if (($outdir ne "") && ($outdir ne "/") ) {
				WriteLog(`rm -f $outdir/*.hdr $outdir/*.img $outdir/*.nii $outdir/*.gz`);
			}
			WriteLog(CompressText("$systemstring (" . `$systemstring` . ")"));

			# rename the files into something meaningful
			my ($numimg, $numhdr, $numnii, $numniigz) = BatchRenameFiles($outdir, $series_num, $study_num, $uid, $project_costcenter);
			WriteLog("Done renaming files");

			if (($numimg > 0) || ($numhdr > 0) || ($numnii > 0) || ($numniigz > 0)) {
				my $dirsize = GetDirectorySize($outdir);
				
				my $endtime = GetTotalCPUTime();
				my $cputime = $endtime - $starttime;
				
				# insert this row into the DB
				$sqlstring = "insert into mr_recon (mrseries_id, recon_type, numfiles_img, numfiles_hdr, numfiles_nii, numfiles_niigz, recon_size, cputime) values ($seriesid, '$format', $numimg, $numhdr, $numnii, $numniigz, $dirsize, $cputime)";
				my $result = $db->query($sqlstring);
			}
			else {
				# conversion was not successful, delete the conversion directory
				WriteLog("Conversion not successful");
				rmdir($outdir);
			}
		}
	}

}


# ----------------------------------------------------------
# --------- BatchRenameFiles -------------------------------
# ----------------------------------------------------------
sub BatchRenameFiles {
	my ($dir, $seriesnum, $studynum, $uid, $costcenter) = @_;
	
	chdir($dir) || die("Cannot open directory $dir!\n");
	my @imgfiles = <*.img>;
	my @hdrfiles = <*.hdr>;
	my @niifiles = <*.nii>;
	my @niigzfiles = <*.nii.gz>;

	my $i = 1;
	foreach my $imgfile (nsort @imgfiles) {
		my $oldfile = $imgfile;
		my $newfile = $uid . "_P$costcenter" . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".img";
		WriteLog("$oldfile => $newfile");
		WriteLog(`mv $oldfile $newfile`);
		$i++;
	}

	$i = 1;
	foreach my $hdrfile (nsort @hdrfiles) {
		my $oldfile = $hdrfile;
		my $newfile = $uid . "_P$costcenter" . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".hdr";
		WriteLog("$oldfile => $newfile");
		WriteLog(`mv $oldfile $newfile`);
		$i++;
	}
	
	$i = 1;
	foreach my $niifile (nsort @niifiles) {
		my $oldfile = $niifile;
		my $newfile = $uid . "_P$costcenter" . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".nii";
		WriteLog("$oldfile => $newfile");
		WriteLog(`mv $oldfile $newfile`);
		$i++;
	}

	$i = 1;
	foreach my $niigzfile (nsort @niigzfiles) {
		my $oldfile = $niigzfile;
		my $newfile = $uid . "_P$costcenter" . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".nii.gz";
		WriteLog("$oldfile => $newfile");
		WriteLog(`mv $oldfile $newfile`);
		$i++;
	}
	
	return ($#imgfiles+1, $#hdrfiles+1, $#niifiles+1, $#niigzfiles+1);
}