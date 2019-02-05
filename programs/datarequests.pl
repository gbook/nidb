#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB datarequests.pl
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
# Program to process all data requests
#
# [5/26/2011] - Greg Book
#		* Wrote initial program.
# -----------------------------------------------------------------------------

use strict;
use warnings;
use threads;
use threads::shared;
use Mysql;
use Image::ExifTool;
use Net::SMTP::TLS;
use File::Find;
use File::Path qw(make_path remove_tree rmtree);
use Switch;
use Sort::Naturally;
use Date::Parse;
use XML::Writer;
use DBI;
use XML::Generator::DBI;
use XML::Handler::YAWriter;
use Cwd;
use Digest::MD5::File qw(dir_md5_hex file_md5_hex url_md5_hex);
use Data::Dumper;

require 'nidbroutines.pl';
our %cfg;
LoadConfig();

our $db;

# script specific variables
our $scriptname = "datarequests";
our $lockfileprefix = "datarequests"; # lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					 # lockfile name created for this instance of the program
our $log;							 # logfile handle created for this instance of the program
our $numinstances = 4;				 # number of times this program can be run concurrently

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
	$logfilename = "$cfg{'logdir'}/$scriptname" . CreateLogDate() . ".log";
	open $log, '> ', $logfilename;
	my $x = ProcessDataExports();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- ProcessDataExports -----------------------------
# ----------------------------------------------------------
sub ProcessDataExports {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my $ret = 0;
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die ("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	# check if this module should be running now or not
	my $sqlstring = "select * from modules where module_name = '$scriptname' and module_isactive = 1";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows < 1) {
		return 0;
	}

	# update the start time
	SetModuleRunning();
	ModuleDBCheckIn($scriptname, $db);
	ModuleRunningCheckIn($scriptname, $db);
	
	$sqlstring = "select * from exports where status = 'submitted'";
	WriteLog($sqlstring);
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
		
			# check if this module should be running now or not
			if (!ModuleCheckIfActive($scriptname, $db)) {
				WriteLog("Not supposed to be running right now");
				# update the stop time
				ModuleDBCheckOut($scriptname, $db);
				return 0;
			}
		
			# also just check in every so often
			ModuleRunningCheckIn($scriptname, $db);
		
			my $exportid = $row{'export_id'};
			my $username = $row{'username'};
			my $destinationtype = $row{'destinationtype'};
			my $downloadimaging = $row{'download_imaging'};
			my $downloadbeh = $row{'download_beh'};
			my $downloadqc = $row{'download_qc'};
			my $nfsdir = $row{'nfsdir'};
			my $filetype = $row{'filetype'};
			my $dirformat = $row{'dirformat'};
			my $preserveseries = $row{'do_preserveseries'};
			my $gzip = $row{'do_gzip'};
			my $anonymize = $row{'anonymize'};
			my $beh_format = $row{'beh_format'};
			my $beh_dirrootname = $row{'beh_dirrootname'};
			my $beh_dirseriesname = $row{'beh_dirseriesname'};
			my $remoteftpusername = $row{'remoteftp_username'};
			my $remoteftppassword = $row{'remoteftp_password'};
			my $remoteftpserver = $row{'remoteftp_server'};
			my $remoteftpport = $row{'remoteftp_port'};
			my $remoteftppath = $row{'remoteftp_path'};
			my $remotenidbserver = $row{'remotenidb_server'};
			my $remotenidbusername = $row{'remotenidb_username'};
			my $remotenidbpassword = $row{'remotenidb_password'};
			my $remotenidbinstanceid = $row{'remotenidb_instanceid'};
			my $remotenidbprojectid = $row{'remotenidb_projectid'};
			my $remotenidbsiteid = $row{'remotenidb_siteid'};
			my $publicdownloadid = $row{'publicdownloadid'};
			my $bidsreadme = $row{'bidsreadme'};
			
			WriteLog("Working on export [$exportid] of type [$destinationtype]. Checking to see if is still submitted, pending processing");
			my $sqlstringA = "select status from exports where export_id = $exportid";
			my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
			my %rowA = $resultA->fetchhash;
			if (trim($rowA{'status'} ne "submitted")) { continue; } # the status of this export has changed, so another instance of this program may be working on it
			
			my $status = "processing";
			my $log = "";
			$sqlstringA = "update exports set status = '$status' where export_id = $exportid";
			$resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
			switch ($destinationtype) {
				case 'web' 				{ ($status,$log) = ExportLocal($exportid, 'web', '', 0, $downloadimaging, $downloadbeh, $downloadqc, $filetype, $dirformat, $preserveseries, $gzip, $anonymize, $beh_format, $beh_dirrootname, $beh_dirseriesname); }
				case 'publicdownload' 	{ ($status,$log) = ExportLocal($exportid, 'publicdownload', '', $publicdownloadid, $downloadimaging, $downloadbeh, $downloadqc, $filetype, $dirformat, $preserveseries, $gzip, $anonymize, $beh_format, $beh_dirrootname, $beh_dirseriesname); }
				case 'nfs' 				{ ($status,$log) = ExportLocal($exportid, 'nfs', $nfsdir, 0, $downloadimaging, $downloadbeh, $downloadqc, $filetype, $dirformat, $preserveseries, $gzip, $anonymize, $beh_format, $beh_dirrootname, $beh_dirseriesname); }
				case 'localftp' 		{ ($status,$log) = ExportLocal($exportid, 'localftp', $nfsdir, 0, $downloadimaging, $downloadbeh, $downloadqc, $filetype, $dirformat, $preserveseries, $gzip, $anonymize, $beh_format, $beh_dirrootname, $beh_dirseriesname); }
				case 'export' 			{ ($status,$log) = ExportNiDB($exportid); }
				case 'ndar' 			{ ($status,$log) = ExportNDAR($exportid, 0); }
				case 'ndarcsv' 			{ ($status,$log) = ExportNDAR($exportid, 1); }
				case 'bids' 			{ ($status,$log) = ExportBIDS($exportid, $bidsreadme); }
				case 'remotenidb' 		{ ($status,$log) = ExportToRemoteNiDB($exportid, $remotenidbserver, $remotenidbusername, $remotenidbpassword, $remotenidbinstanceid, $remotenidbprojectid, $remotenidbsiteid); }
				case 'remoteftp' 		{ ($status,$log) = ExportToRemoteFTP($exportid, $remoteftpusername, $remoteftppassword, $remoteftpserver, $remoteftpport, $remoteftppath); }
				else 					{ WriteLog("Unknown export type [$destinationtype]"); continue; }
			}
			$log = EscapeMySQLString($log);
			$sqlstringA = "update exports set status = '$status', log = '$log' where export_id = $exportid";
			$resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
			$ret = 1;
		}
	}

	# end the module and return the code
	SetModuleStopped();
	
	# update the stop time
	ModuleDBCheckOut($scriptname, $db);

	return $ret;
}


# ----------------------------------------------------------
# --------- ExportLocal ------------------------------------
# ----------------------------------------------------------
sub ExportLocal() {
	my ($exportid, $exporttype, $nfsdir, $publicdownloadid, $downloadimaging, $downloadbeh, $downloadqc, $filetype, $dirformat, $preserveseries, $gzip, $anonymize, $beh_format, $beh_dirrootname, $beh_dirseriesname) = @_;
	WriteLog("Entering ExportLocal($exportid, $exporttype, $nfsdir, $publicdownloadid, $downloadimaging, $downloadbeh, $downloadqc, $filetype, $dirformat, $preserveseries, $gzip, $anonymize, $beh_format, $beh_dirrootname, $beh_dirseriesname)...");

	my %s = GetExportSeriesList($exportid);

	my $tmpexportdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(20);

	my $log = "";
	my $exportstatus = "complete";
	my $systemstring = "";
	my $laststudynum = 0;
	my $newseriesnum = 1;
	foreach my $uid (nsort keys %s) {
		foreach my $studynum (nsort keys $s{$uid}) {
			foreach my $seriesnum (nsort keys $s{$uid}{$studynum}) {
				my $exportseriesid = $s{$uid}{$studynum}{$seriesnum}{'exportseriesid'};
				my $sqlstring = "update exportseries set status = 'processing' where exportseries_id = $exportseriesid";
				my $result = SQLQuery($sqlstring, __FILE__, __LINE__);

				my $seriesstatus = "complete";
				
				my $subjectid = $s{$uid}{$studynum}{$seriesnum}{'subjectid'};
				my $seriesid = $s{$uid}{$studynum}{$seriesnum}{'seriesid'};
				my $primaryaltuid = $s{$uid}{$studynum}{$seriesnum}{'primaryaltuid'};
				my $altuids = $s{$uid}{$studynum}{$seriesnum}{'altuids'};
				my $projectname = $s{$uid}{$studynum}{$seriesnum}{'projectname'};
				my $studyid = $s{$uid}{$studynum}{$seriesnum}{'studyid'};
				my $studytype = $s{$uid}{$studynum}{$seriesnum}{'studytype'};
				my $studyaltid = $s{$uid}{$studynum}{$seriesnum}{'studyaltid'};
				my $modality = $s{$uid}{$studynum}{$seriesnum}{'modality'};
				my $seriessize = $s{$uid}{$studynum}{$seriesnum}{'seriessize'};
				my $seriesdesc = $s{$uid}{$studynum}{$seriesnum}{'seriesdesc'};
				my $datatype = $s{$uid}{$studynum}{$seriesnum}{'datatype'};
				my $indir = $s{$uid}{$studynum}{$seriesnum}{'datadir'};
				my $behindir = $s{$uid}{$studynum}{$seriesnum}{'behdir'};
				my $qcindir = $s{$uid}{$studynum}{$seriesnum}{'qcdir'};
				my $numfiles = $s{$uid}{$studynum}{$seriesnum}{'numfiles'};
				my $datadirexists = $s{$uid}{$studynum}{$seriesnum}{'datadirexists'};
				my $behdirexists = $s{$uid}{$studynum}{$seriesnum}{'behdirexists'};
				my $qcdirexists = $s{$uid}{$studynum}{$seriesnum}{'qcdirexists'};
				my $datadirempty = $s{$uid}{$studynum}{$seriesnum}{'datadirempty'};
				my $behdirempty = $s{$uid}{$studynum}{$seriesnum}{'behdirempty'};
				my $qcdirempty = $s{$uid}{$studynum}{$seriesnum}{'qcdirempty'};

				my $subjectdir = "$uid$studynum";
				switch ($dirformat) {
					case "shortid" { $subjectdir = "$uid$studynum"; }
					case "shortstudyid" { $subjectdir = "$uid/$studynum"; }
					case "altuid" {
						if ($primaryaltuid eq "") { $subjectdir = $uid; }
						else { $subjectdir = $primaryaltuid; }
					}
				}
				
				# renumber series if necessary
				if (!$preserveseries) {
					if ($laststudynum != $studynum) { $newseriesnum = 1; }
					else { $newseriesnum++; }
				}
				else { $newseriesnum = $seriesnum; }

				if ($preserveseries == 2) {
					my $seriesdir = $seriesdesc;
					$seriesdir =~ s/[^a-zA-Z0-9_-]/_/g;
					$newseriesnum = "$seriesnum" . "_$seriesdir";
				}
				
				WriteLog("Series number [$seriesnum] --> [$newseriesnum]");
				$log .= "$subjectdir - Series number [$seriesnum] --> [$newseriesnum]\n";
				my $rootoutdir;
				if ($exporttype eq 'nfs') {
					$rootoutdir = "$cfg{'mountdir'}$nfsdir/$subjectdir";
				}
				elsif ($exporttype eq 'web') {
					$rootoutdir = "$tmpexportdir/$subjectdir";
				}
				elsif ($exporttype eq 'localftp') {
					$rootoutdir = "$cfg{'ftpdir'}/NiDB-" . CreateLogDate() . "/$subjectdir";
				}
				elsif ($exporttype eq 'publicdownload') {
					$rootoutdir = "$tmpexportdir/$subjectdir";
				}
				else {
					$rootoutdir = "$tmpexportdir/$subjectdir";
				}
				if (MakePath($rootoutdir)) {
					WriteLog("Created rootoutdir [$rootoutdir]");
					$log .= "Writing data to [$rootoutdir]\n";
				}
				else {
					$seriesstatus = "error";
					$exportstatus = "error";
					WriteLog("ERROR unable to create rootoutdir [$rootoutdir]");
					$log .= "Unable to create output directory [$rootoutdir]\n";
				}
				
				my $outdir = "$rootoutdir/$newseriesnum";
				my $qcoutdir = "$outdir/qa";
				my $behoutdir;
				switch ($beh_format) {
					case "behroot" { $behoutdir = $rootoutdir; }
					case "behrootdir" { $behoutdir = "$rootoutdir/$beh_dirrootname"; }
					case "behseries" { $behoutdir = $outdir; }
					case "behseriesdir" { $behoutdir = "$outdir/$beh_dirseriesname"; }
					else { $behoutdir = "$rootoutdir"; }
				}
				WriteLog("Destination is '$exporttype'. rootoutdir [$rootoutdir], outdir [$outdir], qcoutdir [$qcoutdir], behoutdir [$behoutdir]");

				if ($downloadimaging) {
					if ($numfiles > 0) {
						if ($datadirexists) {
							if (!$datadirempty) {
								# output the correct file type
								if (($modality ne "mr") || ($filetype eq "dicom") || (($datatype ne "dicom") && ($datatype ne "parrec"))) {
									# use rsync instead of cp because of the number of files limit
									$systemstring = "rsync $indir/* $outdir/";
									WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
									$log .= "Copying raw data from [$indir] to [$outdir]\n";
								}
								elsif ($filetype eq "qc") {
									# copy only the qc data
									$systemstring = "cp -R $indir/qa $qcoutdir";
									WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
									$log .= "Copying QC data from [$indir/qa] to [$qcoutdir]\n";
									
									# write the series info to a text file
									open (MRFILE,"> $outdir/seriesInfo.txt");
									my $sqlstringC = "select * from mr_series where mrseries_id = $seriesid";
									my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
									my %rowC = $resultC->fetchhash;
									foreach my $key ( keys %rowC ) {
										print MRFILE "$key: $rowC{$key}\n";
									}
									close (MRFILE);
								}
								else {
									my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
									MakePath($tmpdir);
									ConvertDicom($filetype, $indir, $tmpdir, $gzip, $uid, $studynum, $seriesnum, $datatype);
									WriteLog("About to copy files from $tmpdir to $outdir");
									$systemstring = "rsync $tmpdir/* $outdir/";
									WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
									WriteLog("Done copying files...");
									if (($tmpdir ne "") && ($tmpdir ne "/") && ($tmpdir ne "/tmp")) {
										rmtree($tmpdir);
									}
									$log .= "Converted DICOM/parrec data into $filetype using tmpdir [$tmpdir]. Final directory [$outdir]\n";
								}
							}
							else {
								$seriesstatus = "error";
								$exportstatus = "error";
								WriteLog("ERROR [$indir] is empty");
								$log .= "Directory [$indir] is empty\n";
							}
						}
						else {
							$seriesstatus = "error";
							$exportstatus = "error";
							WriteLog("ERROR indir [$indir] does not exist");
							$log .= "Directory [$indir] does not exist\n";
						}
					}
					else {
						WriteLog("numfiles is 0");
						$log .= "Series contains 0 files\n";
					}
				}
				
				# copy the beh data
				if ($downloadbeh) {
					if ($behdirexists) {
						MakePath($behoutdir);
						$systemstring = "cp -R $behindir/* $behoutdir";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						$systemstring = "chmod -Rf 777 $behoutdir";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						$log .= "Copying behavioral data from [$behindir] to [$behoutdir]\n";
					}
					else {
						WriteLog("WARNING behindir [$behindir] does not exist");
						$log .= "Directory [$behindir] does not exist\n";
					}
				}
				else {
					WriteLog("Not downloading beh data");
					$log .= "Not downloading beh data\n";
				}
				
				# copy the QC data
				if ($downloadqc) {
					if ($qcdirexists) {
						MakePath($qcoutdir);
						$systemstring = "cp -R $qcindir/* $qcoutdir";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						$systemstring = "chmod -Rf 777 $qcoutdir";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						$log .= "Copying QC data from [$qcindir] to [$qcoutdir]\n";
					}
					else {
						$seriesstatus = "error";
						$exportstatus = "error";
						WriteLog("ERROR qcindir [$qcindir] does not exist");
						$log .= "Directory [$qcindir] does not exist\n";
					}
				}
				
				# give full permissions to the files that were downloaded
				if ($exporttype eq "nfs") {
					$systemstring = "chmod -Rf 777 $outdir";
					WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
					
					# change the modification/access timestamp to the current date/time
					find sub { #print $File::Find::name;
					utime(time,time,$File::Find::name) }, "$outdir";
				}

				
				if ($filetype eq 'dicom') {
					Anonymize($outdir,$anonymize,'Anonymous','Anonymous');
				}
				
				$sqlstring = "update exportseries set status = '$seriesstatus' where exportseries_id = $exportseriesid";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				$log .= "Series [$uid$studynum-$seriesnum ($seriesdesc)] complete\n";
				
				$laststudynum = $studynum;
			}
		}
	}

	# extra steps for web download
	if ($exporttype eq "web") {
		my $zipfile = "$cfg{'webdownloaddir'}/NIDB-$exportid.zip";
		my $outdir;
		WriteLog("Final zip file will be [$zipfile]");
		WriteLog("tmpexportdir: [$tmpexportdir]");
		$outdir = $tmpexportdir;
		
		my $pwd = getcwd;
		WriteLog("Current directory is [$pwd], changing directory to [$outdir]");
		if (-e $outdir) {
			chdir($outdir);
			if (-e $zipfile) { $systemstring = "zip -1grv $zipfile ."; }
			else { $systemstring = "zip -1rv $zipfile ."; }
			WriteLog("Beginning zipping...");
			WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
			WriteLog("Finished zipping...");
			WriteLog("Changing directory to [$pwd]");
			chdir($pwd);
		}
		else {
			WriteLog("outdir [$outdir] does not exist");
		}
		# if the tmpexportdir was created, delete it
		if (-e $tmpexportdir) {
			if ((trim($tmpexportdir) ne "") && (trim($tmpexportdir) ne ".") && (trim($tmpexportdir) ne "..") && (trim($tmpexportdir) ne "/")) {
				WriteLog("Temporary export dir [$tmpexportdir] exists and will be deleted");
				rmtree($tmpexportdir);
			}
		}
		if (-e $zipfile) {
			$log .= "Created .zip file [$zipfile]\n";
		}
		else {
			$log .= "Unable to create [$zipfile]\n";
		}
	}
	
	# extra steps for publicdownload
	if ($exporttype eq "publicdownload") {
		my $sqlstringC = "select * from public_downloads where pd_id = $publicdownloadid";
		WriteLog("SQL: $sqlstringC");
		my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
		my %rowC = $resultC->fetchhash;
		my $expiredays = $rowC{'pd_expiredays'};
		my $zippedsize = $rowC{'pd_zippedsize'};
		my $unzippedsize = $rowC{'pd_unzippedsize'};
		
		my $filename = "NiDB-$exportid.zip";
		my $zipfile = "$cfg{'webdownloaddir'}/$filename";
		my $outdir = $tmpexportdir;
		
		my $pwd = getcwd;
		WriteLog("Current directory is [$pwd], changing directory to [$outdir]");
		if (-e $outdir) {
			chdir($outdir);
			if (-e $zipfile) { $systemstring = "zip -1grq $zipfile ."; }
			else { $systemstring = "zip -1rq $zipfile ."; }
			WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
			WriteLog("Changing directory to [$pwd]");
			if (chdir($pwd)) { WriteLog("Successfully changed directory to [$pwd]"); }
			$systemstring = "unzip -vl $zipfile";
			WriteLog("Running [$systemstring]");
			my $filecontents = `$systemstring`;
			my @lines = split(/\n/, $filecontents);
			my $lastline = $lines[-1];
			my @parts = split(/\s+/,trim($lastline));
			$unzippedsize = $parts[0];
			$zippedsize = $parts[1];
			$filecontents = EscapeMySQLString($filecontents);
			
			# update status, size, expire date, etc in the public download table
			$sqlstringC = "update public_downloads set pd_createdate = now(), pd_expiredate = date_add(now(), interval $expiredays day), pd_zippedsize = '$zippedsize', pd_unzippedsize = '$unzippedsize', pd_filename = '$filename', pd_filecontents = '$filecontents', pd_key = upper(sha1(now())), pd_status = 'preparing' where pd_id = $publicdownloadid";
			#WriteLog("SQL: $sqlstringC");
			$resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
		}
		else {
			$exportstatus = "error";
			WriteLog("ERROR directory [$outdir] does not exist");
			$log .= "Outdir [$zipfile] does not exist\n";
		}
		
		if (-e $zipfile) {
			$log .= "Created .zip file [$zipfile]\n";
		}
		else {
			$exportstatus = "error";
			WriteLog("ERROR unable to create zip file [$zipfile]");
			$log .= "Unable to create [$zipfile]\n";
		}
		
	}
	
	WriteLog("Leaving ExportLocal()...");
	
	return ($exportstatus, $log);
}


# ----------------------------------------------------------
# --------- ExportNiDB -------------------------------------
# ----------------------------------------------------------
sub ExportNiDB() {
	my ($exportid) = @_;
	WriteLog("Entering ExportNiDB($exportid)...");

	# ********************************************************************
	# This function is deprecated
	# ********************************************************************
	
	WriteLog("Leaving ExportNiDB()...");
}


# ----------------------------------------------------------
# --------- ExportNDAR -------------------------------------
# ----------------------------------------------------------
sub ExportNDAR() {
	my ($exportid, $csvonly) = @_;
	WriteLog("Entering ExportNDAR($exportid, $csvonly)...");
	my $exportstatus = "complete";

	my %s = GetExportSeriesList($exportid);

	my $rootoutdir = "$cfg{'ftpdir'}/NiDB-NDAR-" . CreateLogDate();
	my $headerfile = "$rootoutdir/ndar.csv";
	
	if (MakePath($rootoutdir)) {
		WriteLog("Created rootoutdir [$rootoutdir]");
	}
	else {
		$exportstatus = "error";
		WriteLog("ERROR unable to create rootoutdir [$rootoutdir]");
		$log .= "Unable to create output directory [$rootoutdir]\n";
		return ("error", $log);
	}
	
	my $log = "";
	my $systemstring = "";
	my $laststudynum = 0;
	my $newseriesnum = 1;
	foreach my $uid (nsort keys %s) {
		foreach my $studynum (nsort keys $s{$uid}) {
			foreach my $seriesnum (nsort keys $s{$uid}{$studynum}) {
				my $exportseriesid = $s{$uid}{$studynum}{$seriesnum}{'exportseriesid'};
				my $sqlstring = "update exportseries set status = 'processing' where exportseries_id = $exportseriesid";
				my $result = SQLQuery($sqlstring, __FILE__, __LINE__);

				my $seriesstatus = "complete";
				
				my $subjectid = $s{$uid}{$studynum}{$seriesnum}{'subjectid'};
				my $seriesid = $s{$uid}{$studynum}{$seriesnum}{'seriesid'};
				my $primaryaltuid = $s{$uid}{$studynum}{$seriesnum}{'primaryaltuid'};
				my $altuids = $s{$uid}{$studynum}{$seriesnum}{'altuids'};
				my $projectname = $s{$uid}{$studynum}{$seriesnum}{'projectname'};
				my $studyid = $s{$uid}{$studynum}{$seriesnum}{'studyid'};
				my $studytype = $s{$uid}{$studynum}{$seriesnum}{'studytype'};
				my $studyaltid = $s{$uid}{$studynum}{$seriesnum}{'studyaltid'};
				my $modality = $s{$uid}{$studynum}{$seriesnum}{'modality'};
				my $seriessize = $s{$uid}{$studynum}{$seriesnum}{'seriessize'};
				my $numfilesbeh = $s{$uid}{$studynum}{$seriesnum}{'numfilesbeh'};
				my $datatype = $s{$uid}{$studynum}{$seriesnum}{'datatype'};
				my $indir = $s{$uid}{$studynum}{$seriesnum}{'datadir'};
				my $behindir = $s{$uid}{$studynum}{$seriesnum}{'behdir'};
				my $qcindir = $s{$uid}{$studynum}{$seriesnum}{'qcdir'};
				my $datadirexists = $s{$uid}{$studynum}{$seriesnum}{'datadirexists'};
				my $behdirexists = $s{$uid}{$studynum}{$seriesnum}{'behdirexists'};
				my $qcdirexists = $s{$uid}{$studynum}{$seriesnum}{'qcdirexists'};
				my $datadirempty = $s{$uid}{$studynum}{$seriesnum}{'datadirempty'};
				my $behdirempty = $s{$uid}{$studynum}{$seriesnum}{'behdirempty'};
				my $qcdirempty = $s{$uid}{$studynum}{$seriesnum}{'qcdirempty'};

				if ($datadirexists) {
					if (!-e $headerfile) {
						WriteNDARHeader($headerfile, $modality);
					}
					
					my $behzipfile = "";
					my $behdesc = "";
					if (!$csvonly) {
						my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
						MakePath($tmpdir);
						if (($modality eq "mr") && ($datatype eq "dicom")) {
							$systemstring = "find $indir -iname '*.dcm' -exec cp {} $tmpdir \\;";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							Anonymize($tmpdir,2,'','');
						}
						elsif (($modality eq "mr") && ($datatype eq "parrec")) {
							$systemstring = "find $indir -iname '*.par' -exec cp {} $tmpdir \\;";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							$systemstring = "find $indir -iname '*.rec' -exec cp {} $tmpdir \\;";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						}
						else {
							$systemstring = "rsync $indir/* $tmpdir/";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						}
						
						# zip the data to the out directory
						my $zipfile = "$rootoutdir/$uid-$studynum-$seriesnum.zip";
						$systemstring = "zip -vjrq1 $zipfile $tmpdir";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						WriteLog("Done zipping image files...");
						$systemstring = "unzip -Z $zipfile";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");

						if ($numfilesbeh > 0) {
							$behzipfile = "$uid-$studynum-$seriesnum-beh.zip";
							$systemstring = "zip -vjrq1 $rootoutdir/$behzipfile $behindir";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							WriteLog("Done zipping beh files...");
							$systemstring = "unzip -Z $zipfile";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							
							$behdesc = "Behavioral/design data file";
						}
						if ($modality eq "mr") {
							rmtree($tmpdir);
						}
						
					}
					WriteNDARSeries($headerfile, "$uid-$studynum-$seriesnum.zip", $behzipfile, $behdesc, $seriesid, $modality, "$indir/$datatype");
					
					$sqlstring = "update exportseries set status = '$seriesstatus' where exportseries_id = $exportseriesid";
					$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				}
				else {
					$log .= "Unable to export $indir. Directory does not exist\n";
				}
			}
		}
	}
	
	WriteLog("Leaving ExportNDAR()...");
	
	return ($exportstatus, $log);
}


# ----------------------------------------------------------
# --------- ExportBIDS -------------------------------------
# ----------------------------------------------------------
sub ExportBIDS() {
	my ($exportid, $bidsreadme) = @_;
	WriteLog("Entering ExportBIDS($exportid)...");
	
	my %s = GetExportSeriesList($exportid);

	my $rootoutdir = "$cfg{'ftpdir'}/NiDB-BIDS-" . CreateLogDate();

	my $log = "";
	my $exportstatus = "complete";
	my $systemstring = "";
	my $laststudynum = 0;
	my $newseriesnum = 1;
	my $i = 1; # the subject counter
	foreach my $uid (nsort keys %s) {
		my $j = 1; # the session (study) counter
		foreach my $studynum (nsort keys $s{$uid}) {
			foreach my $seriesnum (nsort keys $s{$uid}{$studynum}) {
				my $exportseriesid = $s{$uid}{$studynum}{$seriesnum}{'exportseriesid'};
				my $sqlstring = "update exportseries set status = 'processing' where exportseries_id = $exportseriesid";
				my $result = SQLQuery($sqlstring, __FILE__, __LINE__);

				my $seriesstatus = "complete";
				
				my $subjectid = $s{$uid}{$studynum}{$seriesnum}{'subjectid'};
				my $seriesid = $s{$uid}{$studynum}{$seriesnum}{'seriesid'};
				my $primaryaltuid = $s{$uid}{$studynum}{$seriesnum}{'primaryaltuid'};
				my $altuids = $s{$uid}{$studynum}{$seriesnum}{'altuids'};
				my $projectname = $s{$uid}{$studynum}{$seriesnum}{'projectname'};
				my $studyid = $s{$uid}{$studynum}{$seriesnum}{'studyid'};
				my $studytype = $s{$uid}{$studynum}{$seriesnum}{'studytype'};
				my $studyaltid = $s{$uid}{$studynum}{$seriesnum}{'studyaltid'};
				my $modality = $s{$uid}{$studynum}{$seriesnum}{'modality'};
				my $seriessize = $s{$uid}{$studynum}{$seriesnum}{'seriessize'};
				my $seriesdesc = $s{$uid}{$studynum}{$seriesnum}{'seriesdesc'};
				my $seriesaltdesc = $s{$uid}{$studynum}{$seriesnum}{'seriesaltdesc'};
				my $datatype = $s{$uid}{$studynum}{$seriesnum}{'datatype'};
				my $indir = $s{$uid}{$studynum}{$seriesnum}{'datadir'};
				my $behindir = $s{$uid}{$studynum}{$seriesnum}{'behdir'};
				my $qcindir = $s{$uid}{$studynum}{$seriesnum}{'qcdir'};
				my $datadirexists = $s{$uid}{$studynum}{$seriesnum}{'datadirexists'};
				my $behdirexists = $s{$uid}{$studynum}{$seriesnum}{'behdirexists'};
				my $qcdirexists = $s{$uid}{$studynum}{$seriesnum}{'qcdirexists'};
				my $datadirempty = $s{$uid}{$studynum}{$seriesnum}{'datadirempty'};
				my $behdirempty = $s{$uid}{$studynum}{$seriesnum}{'behdirempty'};
				my $qcdirempty = $s{$uid}{$studynum}{$seriesnum}{'qcdirempty'};

				# create the subject identifier
				my $subjectdir = "subj" . sprintf( "%04d", $i);
				
				# create the session (study) identifier
				my $sessiondir = "sess" . sprintf( "%04d", $i);
				
				# determine the datatype (what they call the 'modality')
				my $seriesdir = "";
				if ($seriesaltdesc eq "") {
					$seriesdir = $seriesdesc;
					$seriesdir =~ s/[^a-zA-Z0-9_-]/_/g;
				}
				else {
					$seriesdir = $seriesaltdesc;
				}

				my $outdir = $rootoutdir . "/$subjectdir/$sessiondir/$seriesdir";
				
				if (MakePath($outdir)) {
					WriteLog("Created outdir [$outdir]");
				}
				else {
					$seriesstatus = "error";
					$exportstatus = "error";
					WriteLog("ERROR unable to create outdir [$outdir]");
					$log .= "Unable to create output directory [$outdir]\n";
				}
				
				if ($datadirexists) {
					if (!$datadirempty) {
						my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
						MakePath($tmpdir);
						ConvertDicom('bids', $indir, $tmpdir, 1, $subjectdir, $sessiondir, $seriesdir, $datatype);
						
						WriteLog("About to copy files from $tmpdir to $outdir");
						$systemstring = "rsync $tmpdir/* $outdir/";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						WriteLog("Done copying files...");
						if (($tmpdir ne "") && ($tmpdir ne "/") && ($tmpdir ne "/tmp")) {
							rmtree($tmpdir);
						}
					}
					else {
						$seriesstatus = "error";
						$exportstatus = "error";
						WriteLog("ERROR [$indir] is empty");
						$log .= "Directory [$indir] is empty\n";
					}
				}
				else {
					$seriesstatus = "error";
					$exportstatus = "error";
					WriteLog("ERROR indir [$indir] does not exist");
					$log .= "Directory [$indir] does not exist\n";
				}
				
				# copy the beh data
				if ($behdirexists) {
					$systemstring = "cp -R $behindir/* $outdir";
					WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
					$systemstring = "chmod -Rf 777 $outdir";
					WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
				}
				
				$sqlstring = "update exportseries set status = '$seriesstatus' where exportseries_id = $exportseriesid";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				
				$laststudynum = $studynum;
			}
		}
	}
	
	# write the readme file
	my $readmefilename = "$rootoutdir/README";
	open(my $f, '>', $readmefilename) or WriteLog("Could not open file '$readmefilename' $!");
	print $f $bidsreadme;
	close $f;

	WriteLog("Leaving ExportBIDS()...");
	return ($exportstatus, $log);
}


# ----------------------------------------------------------
# --------- ExportToRemoteNiDB -----------------------------
# ----------------------------------------------------------
sub ExportToRemoteNiDB() {
	my ($exportid, $remotenidbserver, $remotenidbusername, $remotenidbpassword, $remotenidbinstanceid, $remotenidbprojectid, $remotenidbsiteid) = @_;
	WriteLog("Entering ExportToRemoteNiDB($exportid, $remotenidbserver, $remotenidbusername, $remotenidbpassword, $remotenidbinstanceid, $remotenidbprojectid, $remotenidbsiteid)...");

	my $log = "";
	# check to see if the remote server is reachable ...
	#my $systemstring = "ping -c 1 $remotenidbserver > /dev/null && echo '1' || echo '0'"; # ping isn't always enabled in some networks :(
	my $systemstring = "curl -sSf $remotenidbserver > /dev/null";
	my $serverResponse = trim(`$systemstring 2>&1`);
	if ($serverResponse ne "") {
		WriteLog("ERROR: Unable to access remote NiDB server [$remotenidbserver]. Received error [$serverResponse]");
		$log .= "Unable to access remote NiDB server [$remotenidbserver]. Received error [$serverResponse]";
		return ("error", $log);
	}
	# ... and if our credentials work and we can start a transaction on it
	my $transactionID = StartRemoteNiDBTransaction($remotenidbserver, $remotenidbusername, $remotenidbpassword);
	if (($transactionID eq "") || ($transactionID < 1)) {
		WriteLog("ERROR: Invalid transaction ID [$transactionID] received from [$remotenidbserver]");
		$log .= "Invalid transaction ID [$transactionID] received from [$remotenidbserver]";
		return ("error", $log);
	}
	
	my $tmpexportdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(20);

	# get list of series to be transferred
	my %s = GetExportSeriesList($exportid);

	#WriteLog(Dumper(\%s));

	my $exportstatus = "complete";
	$systemstring = "";
	my $lastsid = 0;
	my $newseriesnum = 1;
	foreach my $uid (nsort keys %s) {
		foreach my $studynum (nsort keys $s{$uid}) {
			foreach my $seriesnum (nsort keys $s{$uid}{$studynum}) {
				my $exportseriesid = $s{$uid}{$studynum}{$seriesnum}{'exportseriesid'};
				my $sqlstring = "update exportseries set status = 'processing' where exportseries_id = $exportseriesid";
				my $result = SQLQuery($sqlstring, __FILE__, __LINE__);

				my $seriesstatus = "complete";
				
				my $subjectid = $s{$uid}{$studynum}{$seriesnum}{'subjectid'};
				my $primaryaltuid = $s{$uid}{$studynum}{$seriesnum}{'primaryaltuid'};
				my $altuids = $s{$uid}{$studynum}{$seriesnum}{'altuids'};
				my $projectname = $s{$uid}{$studynum}{$seriesnum}{'projectname'};
				my $studyid = $s{$uid}{$studynum}{$seriesnum}{'studyid'};
				my $studytype = $s{$uid}{$studynum}{$seriesnum}{'studytype'};
				my $studyaltid = $s{$uid}{$studynum}{$seriesnum}{'studyaltid'};
				my $modality = $s{$uid}{$studynum}{$seriesnum}{'modality'};
				my $seriesid = $s{$uid}{$studynum}{$seriesnum}{'seriesid'};
				my $seriessize = $s{$uid}{$studynum}{$seriesnum}{'seriessize'};
				my $seriesnotes = $s{$uid}{$studynum}{$seriesnum}{'seriesnotes'};
				my $seriesdesc = $s{$uid}{$studynum}{$seriesnum}{'seriesdesc'};
				my $datatype = $s{$uid}{$studynum}{$seriesnum}{'datatype'};
				my $indir = $s{$uid}{$studynum}{$seriesnum}{'datadir'};
				my $behindir = $s{$uid}{$studynum}{$seriesnum}{'behdir'};
				my $qcindir = $s{$uid}{$studynum}{$seriesnum}{'qcdir'};
				my $datadirexists = $s{$uid}{$studynum}{$seriesnum}{'datadirexists'};
				my $behdirexists = $s{$uid}{$studynum}{$seriesnum}{'behdirexists'};
				my $qcdirexists = $s{$uid}{$studynum}{$seriesnum}{'qcdirexists'};
				my $datadirempty = $s{$uid}{$studynum}{$seriesnum}{'datadirempty'};
				my $behdirempty = $s{$uid}{$studynum}{$seriesnum}{'behdirempty'};
				my $qcdirempty = $s{$uid}{$studynum}{$seriesnum}{'qcdirempty'};

				$log .= "uid [$uid] indir [$indir] datadirexists [$datadirexists]\n";
				if ($datadirexists) {
					if (!$datadirempty) {
						# --------------- Send to remote NiDB site --------------------------
						# for now, only DICOM data and beh can be sent to remote sites
					
						my $numfails = 0;
						my $error = 1;
						my $results = "";
						
						while (($error == 1) && ($numfails < 5)) {
							my $indir = "$cfg{'archivedir'}/$uid/$studynum/$seriesnum/$datatype";
							my $behindir = "$cfg{'archivedir'}/$uid/$studynum/$seriesnum/beh";
							my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
							my $tmpzip = $cfg{'tmpdir'} . "/" . GenerateRandomString(12) . ".tar.gz";
							my $tmpzipdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(12);
							MakePath($tmpdir);
							MakePath($tmpzipdir);
							MakePath("$tmpzipdir/beh");
							$systemstring = "rsync $indir/* $tmpdir/";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							Anonymize($tmpdir,4,'Anonymous','0000-00-00');
							
							# get the list of DICOM files
							my @dcmfiles;
							opendir(DIR,$tmpdir) || Error("Cannot open directory [$tmpdir]\n");
							my @files = readdir(DIR);
							closedir(DIR);
							foreach my $f (@files) {
								my $fulldir = "$tmpdir/$f";
								WriteLog("Checking on [$fulldir]");
								if ((-f $fulldir) && ($f ne '.') && ($f ne '..')) {
									push(@dcmfiles,$f);
								}
							}
							my $numdcms = $#dcmfiles + 1;
							WriteLog("Found [$numdcms] dcmfiles");
							
							if ($numdcms < 1) {
								WriteLog("************* ERROR - Didn't find any DICOM files!!!! *************");
							}
							
							my @behfiles;
							# get the list of beh files
							if (-e $behindir) {
								opendir(DIR,$behindir) || Error("Cannot open directory [$behindir]\n");
								my @bfiles = readdir(DIR);
								closedir(DIR);
								foreach my $f (@bfiles) {
									my $fulldir = "$behindir/$f";
									if ((-f $fulldir) && ($f ne '.') && ($f ne '..')) {
										push(@behfiles,$f);
									}
								}
							}
							
							# build the cURL string to send the actual data
							$systemstring = "curl -gs -F 'action=UploadDICOM' -F 'u=$remotenidbusername' -F 'p=$remotenidbpassword' -F 'transactionid=$transactionID' -F 'instanceid=$remotenidbinstanceid' -F 'projectid=$remotenidbprojectid' -F 'siteid=$remotenidbsiteid' -F 'dataformat=$datatype' -F 'modality=$modality' -F 'seriesnotes=$seriesnotes' -F 'altuids=$altuids' -F 'seriesnum=$seriesnum' ";
							my $c = 0;
							foreach my $f (@dcmfiles) {
								$c++;
								my $systemstring2 = "cp '$tmpdir/$f' $tmpzipdir/";
								my $res = `$systemstring2 2>&1`;
								if ($res ne "") {
									WriteLog("$systemstring2 ($res)");
								}
								
							}
							
							$c = 0;
							foreach my $f (@behfiles) {
								$c++;
								my $systemstring2 = "cp '$behindir/$f' $tmpzipdir/beh/";
								my $res = `$systemstring2 2>&1`;
								if ($res ne "") {
									WriteLog("$systemstring2 ($res)");
								}
							}
							
							# send the zip and send file
							my $systemstring2 = "cd $tmpzipdir;GZIP=-1; tar -czf $tmpzip --warning=no-timestamp .; chmod 777 $tmpzip";
							WriteLog("$systemstring2 (".`$systemstring2 2>&1`.")");
							# get size before sending
							my $zipsize = -s $tmpzip;
							my $starttime = time();
							# get MD5 before sending
							my $zipmd5 = file_md5_hex($tmpzip);
							$systemstring .= "-F 'files[]=\@$tmpzip' ";
							$systemstring .= "$remotenidbserver/api.php";
							$results = `$systemstring 2>&1`;
							WriteLog("$systemstring ($results)");
							my $elapsedtime = time() - $starttime + 0.0000001; # to avoid a divide by zero!
							my $MBps = $zipsize/$elapsedtime/1000/1000;
							WriteLog("$zipsize bytes transferred at " . sprintf("%.2f",$MBps) . " MB/s ");
							$log .= "$zipsize bytes transferred at " . sprintf("%.2f",$MBps) . " MB/s ";
							
							my @parts = split(',',$results);
							if (trim($parts[0]) eq 'SUCCESS') {
								# a file was successfully received by api.php, now check the return md5
								if (trim(uc($parts[1])) eq uc($zipmd5)) {
									$seriesstatus = 'complete';
									WriteLog("Upload success: MD5 match");
									$log .= "Successfully sent data to [$remotenidbserver]";
									$error = 0;
								}
								else {
									$seriesstatus = 'error';
									$exportstatus = 'error';
									$log .= "Upload fail: MD5 non-match\n";
									WriteLog("Upload fail: MD5 non-match");
									$error = 1;
									$numfails++;
								}
							}
							else {
								$seriesstatus = 'error';
								$exportstatus = 'error';
								$log .= "Upload fail: got message [" . $results . "]\n";
								WriteLog("Upload fail: got message [" . $results . "]");
								$error = 1;
								$numfails++;
							}
						}
					}
					else {
						$seriesstatus = "error";
						$exportstatus = "error";
						WriteLog("ERROR indir [$indir] is empty");
						$log .= "ERROR indir [$indir] is empty\n";
					}
				}
				else {
					$seriesstatus = "error";
					$exportstatus = "error";
					WriteLog("ERROR indir [$indir] does not exist");
					$log .= "ERROR indir [$indir] does not exist\n";
				}
				$sqlstring = "update exportseries set status = '$seriesstatus' where exportseries_id = $exportseriesid";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				$log .= "Series [$uid$studynum-$seriesnum ($seriesdesc)] complete\n";
			}
		}
	}
	
	EndRemoteNiDBTransaction($transactionID, $remotenidbserver, $remotenidbusername, $remotenidbpassword);

	WriteLog("Leaving ExportToRemoteNiDB()...");
	
	return ($exportstatus, $log);
}


# ----------------------------------------------------------
# --------- ExportToRemoteFTP ------------------------------
# ----------------------------------------------------------
sub ExportToRemoteFTP() {
	my ($exportid, $remoteftpusername, $remoteftppassword, $remoteftpserver, $remoteftpport, $remoteftppath) = @_;
	WriteLog("Entering ExportToRemoteFTP($exportid)...");
	
	# ********************************************************************
	# This function is deprecated
	# ********************************************************************

	WriteLog("Leaving ExportToRemoteFTP()...");
}


# ----------------------------------------------------------
# --------- StartRemoteNiDBTransaction ---------------------
# ----------------------------------------------------------
sub StartRemoteNiDBTransaction() {
	my ($remotenidbserver, $remotenidbusername, $remotenidbpassword) = @_;

	# build a cURL string to start the transaction
	my $systemstring = "curl -gs -F 'action=startTransaction' -F 'u=$remotenidbusername' -F 'p=$remotenidbpassword' $remotenidbserver/api.php";
	WriteLog("[$systemstring] --> (" . `$systemstring 2>&1` . ")");
	my $t = trim(`$systemstring`);
	WriteLog("Remote NiDB transactionID: [$t]");
	
	return $t;
}


# ----------------------------------------------------------
# --------- EndRemoteNiDBTransaction -----------------------
# ----------------------------------------------------------
sub EndRemoteNiDBTransaction() {
	my ($tid, $remotenidbserver, $remotenidbusername, $remotenidbpassword) = @_;
	
	# build a cURL string to end the transaction
	my $systemstring = "curl -gs -F 'action=endTransaction' -F 'u=$remotenidbusername' -F 'p=$remotenidbpassword' -F 'transactionid=$tid' $remotenidbserver/api.php";
	WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
}


# ----------------------------------------------------------
# --------- GetExportSeriesList ----------------------------
# ----------------------------------------------------------
sub GetExportSeriesList() {
	my ($exportid) = @_;
	
	my %series;

	my $sqlstring = "select * from exportseries where export_id = $exportid";
	WriteLog($sqlstring);
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $modality = lc($row{'modality'});
			my $seriesid = $row{'series_id'};
			my $exportseriesid = $row{'exportseries_id'};
			my $status = $row{'status'};

			my $sqlstringA = "select a.*, b.*, c.enrollment_id, d.project_name, e.uid, e.subject_id from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id where a.$modality" . "series_id = $seriesid order by uid, study_num, series_num";
			#WriteLog($sqlstringA);
			my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
			if ($resultA->numrows > 0) {
				while (my %rowA = $resultA->fetchhash) {
					my $uid = $rowA{'uid'};
					my $subjectid = $rowA{'subject_id'};
					my $studynum = $rowA{'study_num'};
					my $studyid = $rowA{'study_id'};
					my $studydatetime = $rowA{'study_datetime'};
					my $seriesnum = $rowA{'series_num'};
					my $seriessize = $rowA{'series_size'};
					my $seriesnotes = $rowA{'series_notes'};
					my $seriesdesc = $rowA{'series_desc'};
					my $seriesaltdesc = $rowA{'series_altdesc'};
					my $projectname = $rowA{'project_name'};
					my $studyaltid = $rowA{'study_alternateid'};
					my $studytype = $rowA{'study_type'};
					my $datatype = $rowA{'data_type'};
					# if datatype (dicom, nifti, parrec) is blank because its not MR, then the datatype will actually be the modality
					if (!defined($datatype) || $datatype eq '') {
						$datatype = $modality;
					}
					my $numfiles = $rowA{'numfiles'};
					if ($modality ne "mr") {
						$numfiles = $rowA{'series_numfiles'};
					}
					my $numfilesbeh = $rowA{'numfiles_beh'};
					my $enrollmentid = $rowA{'enrollment_id'};
					my $datadir = "$cfg{'archivedir'}/$uid/$studynum/$seriesnum/$datatype";
					my $behdir = "$cfg{'archivedir'}/$uid/$studynum/$seriesnum/beh";
					my $qcdir = "$cfg{'archivedir'}/$uid/$studynum/$seriesnum/qa";
					
					$series{$uid}{$studynum}{$seriesnum}{'exportseriesid'} = $exportseriesid;
					$series{$uid}{$studynum}{$seriesnum}{'seriesid'} = $seriesid;
					$series{$uid}{$studynum}{$seriesnum}{'subjectid'} = $subjectid;
					$series{$uid}{$studynum}{$seriesnum}{'studyid'} = $studyid;
					$series{$uid}{$studynum}{$seriesnum}{'modality'} = $modality;
					$series{$uid}{$studynum}{$seriesnum}{'seriessize'} = $seriessize;
					$series{$uid}{$studynum}{$seriesnum}{'seriesnotes'} = $seriesnotes;
					$series{$uid}{$studynum}{$seriesnum}{'seriesdesc'} = $seriesdesc;
					$series{$uid}{$studynum}{$seriesnum}{'seriesaltdesc'} = $seriesaltdesc;
					$series{$uid}{$studynum}{$seriesnum}{'numfilesbeh'} = $numfilesbeh;
					$series{$uid}{$studynum}{$seriesnum}{'numfiles'} = $numfiles;
					$series{$uid}{$studynum}{$seriesnum}{'projectname'} = $projectname;
					$series{$uid}{$studynum}{$seriesnum}{'studyaltid'} = $studyaltid;
					$series{$uid}{$studynum}{$seriesnum}{'studytype'} = $studytype;
					$series{$uid}{$studynum}{$seriesnum}{'datatype'} = $datatype;
					$series{$uid}{$studynum}{$seriesnum}{'datadir'} = $datadir;
					$series{$uid}{$studynum}{$seriesnum}{'behdir'} = $behdir;
					$series{$uid}{$studynum}{$seriesnum}{'qcdir'} = $qcdir;

					# ************* Check if source data directories exist *****************
					# check if the indir directory exists or is empty
					if (-e $datadir) {
						$series{$uid}{$studynum}{$seriesnum}{'datadirexists'} = 1;
						if (IsDirEmpty($datadir)) { $series{$uid}{$studynum}{$seriesnum}{'datadirempty'} = 1; }
						else { $series{$uid}{$studynum}{$seriesnum}{'datadirempty'} = 0; }
					}
					else { $series{$uid}{$studynum}{$seriesnum}{'datadirexists'} = 0; }
					# check if the behdir directory exists or is empty
					if (-e $behdir) {
						$series{$uid}{$studynum}{$seriesnum}{'behdirexists'} = 1;
						if (IsDirEmpty($behdir)) { $series{$uid}{$studynum}{$seriesnum}{'behdirempty'} = 1; }
						else { $series{$uid}{$studynum}{$seriesnum}{'behdirempty'} = 0; }
					}
					else { $series{$uid}{$studynum}{$seriesnum}{'behdirexists'} = 0; }
					
					# check if the qcdir directory exists or is empty
					if (-e $qcdir) {
						$series{$uid}{$studynum}{$seriesnum}{'qcdirexists'} = 1;
						if (IsDirEmpty($qcdir)) { $series{$uid}{$studynum}{$seriesnum}{'qcdirempty'} = 1; }
						else { $series{$uid}{$studynum}{$seriesnum}{'qcdirempty'} = 0; }
					}
					else { $series{$uid}{$studynum}{$seriesnum}{'qcdirexists'} = 0; }

					# get any alternate IDs
					my @altuids;
					my $primaryaltuid = "";
					my $sqlstringB = "select altuid, isprimary from subject_altuid where enrollment_id = $enrollmentid and subject_id = $subjectid";
					my $resultB = SQLQuery($sqlstringB, __FILE__, __LINE__);
					if ($resultB->numrows > 0) {
						while (my %rowB = $resultB->fetchhash) {
							if ($rowB{'isprimary'}) { $primaryaltuid = $rowB{'altuid'}; }
							push(@altuids, $rowB{'altuid'});
						}
						$series{$uid}{$studynum}{$seriesnum}{'primaryaltuid'} = $primaryaltuid;
						$series{$uid}{$studynum}{$seriesnum}{'altuids'} = join(',',@altuids);
					}
					
					# format the studydatetime if necessary
					my ($sec, $min, $hour, $day, $month, $year, $tz) = strptime($studydatetime);
					$year -= 100;
					$year += 2000;
					$month++;
					if (length($hour) == 1) { $hour = "0" . $hour; }
					if (length($sec) == 1) { $sec = "0" . $sec; }
					if (length($min) == 1) { $min = "0" . $min; }
					if (length($month) == 1) { $month = "0" . $month; }
					if (length($day) == 1) { $day = "0" . $day; }
					$series{$uid}{$studynum}{$seriesnum}{'studydatetime'} = "$year$month$day" . "_$hour$min$sec";
					
				}
			}
			else {
				WriteLog("No rows found for this seriesid [$seriesid] and modality [$modality]");
			}
		}
	}
	else {
		WriteLog("No series rows found for this exportid [$exportid]");
	}
	
	#WriteLog(Dumper(\%series));
	
	return %series;
}


# ----------------------------------------------------------
# --------- GetOutputDirectories ---------------------------
# ----------------------------------------------------------
sub GetOutputDirectories() {
	my ($req_destinationtype, $newdir, $newseriesnum, $req_behdirrootname, $req_behdirseriesname, $tmpwebdir, $req_behformat, $req_nfsdir, $groupid) = @_;
	
	my $fullexportdir = "";
	my $qcoutdir = "";
	my $behoutdir = "";
	
	switch ($req_destinationtype) {
		case "localftp" {
			$fullexportdir = "$cfg{'ftpdir'}/$newdir/$newseriesnum";
			$qcoutdir = "$cfg{'ftpdir'}/$newdir/$newseriesnum/qa";
			switch ($req_behformat) {
				case "behroot" { $behoutdir = "$cfg{'ftpdir'}/$newdir"; }
				case "behrootdir" { $behoutdir = "$cfg{'ftpdir'}/$newdir/$req_behdirrootname"; }
				case "behseries" { $behoutdir = "$cfg{'ftpdir'}/$newdir/$newseriesnum"; }
				case "behseriesdir" { $behoutdir = "$cfg{'ftpdir'}/$newdir/$newseriesnum/$req_behdirseriesname"; }
				else { $behoutdir = "$cfg{'ftpdir'}/$newdir"; }
			}
			WriteLog("Destination is 'localftp'. fullexportdir=[$fullexportdir] qcoutdir=[$qcoutdir] behoutdir=[$behoutdir]");
		}
		case "web" {
			$fullexportdir = "$tmpwebdir/$newdir/$newseriesnum";
			$qcoutdir = "$tmpwebdir/$newdir/$newseriesnum/qa";
			switch ($req_behformat) {
				case "behroot" { $behoutdir = "$tmpwebdir/$newdir"; }
				case "behrootdir" { $behoutdir = "$tmpwebdir/$newdir/$req_behdirrootname"; }
				case "behseries" { $behoutdir = "$tmpwebdir/$newdir/$newseriesnum"; }
				case "behseriesdir" { $behoutdir = "$tmpwebdir/$newdir/$newseriesnum/$req_behdirseriesname"; }
				else { $behoutdir = "$tmpwebdir/$newdir"; }
			}
			WriteLog("Destination is 'web'. fullexportdir=[$fullexportdir] qcoutdir=[$qcoutdir] behoutdir=[$behoutdir]");
		}
		case "bids" {
			$fullexportdir = "$cfg{'ftpdir'}/$groupid/$newdir/$newseriesnum";
			$qcoutdir = "$cfg{'ftpdir'}/$groupid/$newdir/$newseriesnum/qa";
			switch ($req_behformat) {
				case "behroot" { $behoutdir = "$cfg{'ftpdir'}/$groupid/$newdir"; }
				case "behrootdir" { $behoutdir = "$cfg{'ftpdir'}/$groupid/$newdir/$req_behdirrootname"; }
				case "behseries" { $behoutdir = "$cfg{'ftpdir'}/$groupid/$newdir/$newseriesnum"; }
				case "behseriesdir" { $behoutdir = "$cfg{'ftpdir'}/$groupid/$newdir/$newseriesnum/$req_behdirseriesname"; }
				else { $behoutdir = "$cfg{'ftpdir'}/$groupid/$newdir"; }
			}
			WriteLog("Destination is 'bids'. fullexportdir=[$fullexportdir] qcoutdir=[$qcoutdir] behoutdir=[$behoutdir]");
		}
		case "publicdownload" {
			$fullexportdir = "$tmpwebdir/$newdir/$newseriesnum";
			$qcoutdir = "$tmpwebdir/$newdir/$newseriesnum/qa";
			switch ($req_behformat) {
				case "behroot" { $behoutdir = "$tmpwebdir/$newdir"; }
				case "behrootdir" { $behoutdir = "$tmpwebdir/$newdir/$req_behdirrootname"; }
				case "behseries" { $behoutdir = "$tmpwebdir/$newdir/$newseriesnum"; }
				case "behseriesdir" { $behoutdir = "$tmpwebdir/$newdir/$newseriesnum/$req_behdirseriesname"; }
				else { $behoutdir = "$tmpwebdir/$newdir"; }
			}
			WriteLog("Destination is 'publicdownload'. fullexportdir=[$fullexportdir] qcoutdir=[$qcoutdir] behoutdir=[$behoutdir]");
		}
		case "nfs" {
			$fullexportdir = "$cfg{'mountdir'}$req_nfsdir/$newdir/$newseriesnum";
			$qcoutdir = "$cfg{'mountdir'}$req_nfsdir/$newdir/$newseriesnum/qa";
			switch ($req_behformat) {
				case "behroot" { $behoutdir = "$cfg{'mountdir'}$req_nfsdir/$newdir"; }
				case "behrootdir" { $behoutdir = "$cfg{'mountdir'}$req_nfsdir/$newdir/$req_behdirrootname"; }
				case "behseries" { $behoutdir = "$cfg{'mountdir'}$req_nfsdir/$newdir/$newseriesnum"; }
				case "behseriesdir" { $behoutdir = "$cfg{'mountdir'}$req_nfsdir/$newdir/$newseriesnum/$req_behdirseriesname"; }
				else { $behoutdir = "$cfg{'mountdir'}$req_nfsdir/$newdir"; }
			}
			WriteLog("Destination is 'nfs'. fullexportdir=[$fullexportdir] qcoutdir=[$qcoutdir] behoutdir=[$behoutdir]");
		}
		case "remoteftp" {
			$fullexportdir = "$req_nfsdir/$newdir/$newseriesnum";
			$qcoutdir = "$req_nfsdir/$newdir/$newseriesnum/qa";
			switch ($req_behformat) {
				case "behroot" { $behoutdir = "$req_nfsdir/$newdir"; }
				case "behrootdir" { $behoutdir = "$req_nfsdir/$newdir/$req_behdirrootname"; }
				case "behseries" { $behoutdir = "$req_nfsdir/$newdir/$newseriesnum"; }
				case "behseriesdir" { $behoutdir = "$req_nfsdir/$newdir/$newseriesnum/$req_behdirseriesname"; }
				else { $behoutdir = "$req_nfsdir/$newdir"; }
			}
			WriteLog("Destination is 'remoteftp'. fullexportdir=[$fullexportdir] qcoutdir=[$qcoutdir] behoutdir=[$behoutdir]");
		}
	}
	
	return ($fullexportdir, $behoutdir, $qcoutdir);
}


# -------------------------------------------------------------------------
# -------------- Anonymize ------------------------------------------------
# -------------------------------------------------------------------------
sub Anonymize() {
	my ($dir,$anon,$randstr1,$randstr2) = @_;
	
	if ($anon == 0) {
		return;
	}
	
	my @systemstrings;
	my @md5s;
	
	find sub {
		if ($File::Find::name =~ /\.dcm/) {
			my $systemstring;
			if ($anon == 4) {
				# encrypt patient name, leave everything else
				$systemstring = "GDCM_RESOURCES_PATH=$cfg{'scriptdir'}/gdcm/Source/InformationObjectDefinition; export GDCM_RESOURCES_PATH; $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name --replace 10,10='$randstr1' -o $File::Find::name";
				WriteLog("Anonymizing (level 4) $File::Find::name");
				push(@systemstrings,$systemstring);
				push(@md5s, file_md5_hex($File::Find::name));
			}
			if ($anon == 1) {
				# remove ReferringPhysicianName
				$systemstring = "GDCM_RESOURCES_PATH=$cfg{'scriptdir'}/gdcm/Source/InformationObjectDefinition; export GDCM_RESOURCES_PATH; $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name --replace 8,90='Anonymous' --replace 8,1050='Anonymous' --replace 8,1070='Anonymous' --replace 10,10='Anonymous-$randstr1' --replace 10,30='Anonymous-$randstr2' -o $File::Find::name";
				WriteLog("Anonymizing (level 1) $File::Find::name");
				push(@systemstrings,$systemstring);
				push(@md5s, file_md5_hex($File::Find::name));
			}
			if ($anon == 2) {
				# Full anonymization. remove all names, dates, locations. ANYTHING identifiable
				# gdcmanon cannot handle more than 8 --replace arguments, it ignores anything more than that and leaves them un-anonymized
				
				my $s = "GDCM_RESOURCES_PATH=$cfg{'scriptdir'}/gdcm/Source/InformationObjectDefinition; export GDCM_RESOURCES_PATH; $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name";
				$s .= " --replace 8,12='19000101'"; # InstanceCreationDate
				$s .= " --replace 8,13='19000101'"; # InstanceCreationTime
				$s .= " --replace 8,20='19000101'"; # StudyDate
				$s .= " --replace 8,21='19000101'"; # SeriesDate
				$s .= " --replace 8,22='19000101'"; # AcquisitionDate
				$s .= " --replace 8,23='19000101'"; # ContentDate
				$s .= " --replace 8,30='000000.000000'"; #StudyTime
				$s .= " --replace 8,31='000000.000000'"; #SeriesTime
				
				$s .= " -o $File::Find::name;"; # separate into multiple calls with different tags each time
				$s .= " $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name"; # 
				
				$s .= " --replace 8,32='000000.000000'"; #AcquisitionTime
				$s .= " --replace 8,33='000000.000000'"; #ContentTime
				$s .= " --replace 8,80='Anonymous'"; # InstitutionName
				$s .= " --replace 8,81='Anonymous'"; # InstitutionAddress
				$s .= " --replace 8,90='Anonymous'"; # ReferringPhysicianName
				$s .= " --replace 8,92='Anonymous'"; # ReferringPhysicianAddress
				$s .= " --replace 8,94='Anonymous'"; # ReferringPhysicianTelephoneNumber
				$s .= " --replace 8,96='Anonymous'"; # ReferringPhysicianIDSequence
				
				$s .= " -o $File::Find::name;"; # separate into multiple calls with different tags each time
				$s .= " $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name"; # 
				
				$s .= " --replace 8,1010='Anonymous'"; # StationName
				$s .= " --replace 8,1030='Anonymous'"; # StudyDescription
				$s .= " --replace 8,103E='Anonymous'"; # SeriesDescription
				$s .= " --replace 8,1048='Anonymous'"; # PhysiciansOfRecord
				$s .= " --replace 8,1050='Anonymous'"; # PerformingPhysicianName
				$s .= " --replace 8,1060='Anonymous'"; # NameOfPhysicianReadingStudy
				$s .= " --replace 8,1070='Anonymous'"; # OperatorsName
				$s .= " --replace 10,10='Anonymous'"; # PatientName
				$s .= " --replace 10,20='Anonymous'"; # PatientID
				
				$s .= " -o $File::Find::name;"; # separate into multiple calls with different tags each time
				$s .= " $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name"; # 
				
				$s .= " --replace 10,21='Anonymous'"; # IssuerOfPatientID
				$s .= " --replace 10,30='19000101'"; # PatientBirthDate
				$s .= " --replace 10,32='000000.000000'"; # PatientBirthTime
				$s .= " --replace 10,50='Anonymous'"; # PatientInsurancePlanCodeSequence
				$s .= " --replace 10,1000='Anonymous'"; # OtherPatientIDs
				$s .= " --replace 10,1001='Anonymous'"; # OtherPatientNames
				$s .= " --replace 10,1005='Anonymous'"; # PatientBirthName
				$s .= " --replace 10,1010='Anonymous'"; # PatientAge

				$s .= " -o $File::Find::name;"; # separate into multiple calls with different tags each time
				$s .= " $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name"; # 

				$s .= " --replace 10,1020='Anonymous'"; # PatientSize
				$s .= " --replace 10,1030='Anonymous'"; # PatientWeight
				$s .= " --replace 10,1040='Anonymous'"; # PatientAddress
				$s .= " --replace 10,1060='Anonymous'"; # PatientMotherBirthName
				$s .= " --replace 10,2154='Anonymous'"; # PatientTelephoneNumbers
				$s .= " --replace 10,21b0='Anonymous'"; # AdditionalPatientHistory
				$s .= " --replace 10,21f0='Anonymous'"; # PatientReligiousPreference
				$s .= " --replace 10,4000='Anonymous'"; # PatientComments
				$s .= " --replace 18,1030='Anonymous'"; # ProtocolName
				
				$s .= " -o $File::Find::name;"; # separate into multiple calls with different tags each time
				$s .= " $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name"; # 

				$s .= " --replace 32,1032='Anonymous'"; # RequestingPhysician
				$s .= " --replace 32,1060='Anonymous'"; # RequestedProcedureDescription
				$s .= " --replace 40,6='Anonymous'"; # ScheduledPerformingPhysiciansName
				$s .= " --replace 40,244='19000101'"; # PerformedProcedureStepStartDate
				$s .= " --replace 40,245='000000.000000'"; # PerformedProcedureStepStartTime
				$s .= " --replace 40,253='Anonymous'"; # PerformedProcedureStepID
				$s .= " --replace 40,254='Anonymous'"; # PerformedProcedureStepDescription
				$s .= " --replace 40,4036='Anonymous'"; # HumanPerformerOrganization
				
				$s .= " -o $File::Find::name;"; # separate into multiple calls with different tags each time
				$s .= " $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name"; # 
				
				$s .= " --replace 40,4037='Anonymous'"; # HumanPerformerName
				$s .= " --replace 40,a123='Anonymous'"; # PersonName
				$s .= " -o $File::Find::name;";
				WriteLog("Anonymizing (level 2 - FULL) $File::Find::name");
				
				my $systemstring = "GDCM_RESOURCES_PATH=$cfg{'scriptdir'}/gdcm/Source/InformationObjectDefinition; export GDCM_RESOURCES_PATH; cd $cfg{'scriptdir'}/DicomAnonymizer; ./DicomAnonymizer.sh 1 1 1 1 1 1 $File::Find::name";
				WriteLog("Anonymizing (full) $File::Find::name");
				
				push(@systemstrings,$systemstring);
				push(@md5s, file_md5_hex($File::Find::name));
			}
			if ($anon == 3) {
				$systemstring = "GDCM_RESOURCES_PATH=$cfg{'scriptdir'}/gdcm/Source/InformationObjectDefinition; export GDCM_RESOURCES_PATH; $cfg{'scriptdir'}/./gdcmanon -V --dumb -i $File::Find::name --replace 8,90='Anonymous' --replace 8,1050='Anonymous' --replace 8,1070='Anonymous' --replace 10,10='Anonymous-$randstr1' --replace 10,30='Anonymous-$randstr2' -o $File::Find::name";
				WriteLog("Anonymizing (level 3) $File::Find::name");
				push(@systemstrings,$systemstring);
				push(@md5s, file_md5_hex($File::Find::name));
			}
		}
		# remove an txt files, which may contain PHI
		if ($File::Find::name =~ /\.gif/) { unlink($File::Find::name); }
		if ($File::Find::name =~ /\.txt/) { unlink($File::Find::name); }
	}, "$dir";
	
	# thread them N at a time
	my $i = 0;
	my $totalcpu = 0;
	my $numthreads = 40;
	while ($i<=($#systemstrings)) {
		my @threads;
		# create all the threads
		for (my $j=0;$j<$numthreads;$j++) {
			if ($j>($#systemstrings)) {
				last;
			}
			if (trim($systemstrings[$i]) ne "") {
				my $t = threads->new(\&ThreadedSystemCall,$systemstrings[$i]);
				push(@threads,$t);
			}
			$i++;
		}
		WriteLog("Launched $i threads, waiting for them to finish");
		# wait for them all to return
		foreach my $t (@threads) {
			my $cpu = $t->join;
			$totalcpu += $cpu;
		}
		WriteLog("Anonymize threads finished. Cumulative CPU usage [$totalcpu]");
	}
	
	return @md5s;
}


# ----------------------------------------------------------
# --------- ThreadedSystemCall -----------------------------
# ----------------------------------------------------------
sub ThreadedSystemCall {
	my $systemstring = shift;
	
	my $starttime = time;
	`$systemstring 2>&1`;
	WriteLog("ThreadedSystemCall [$systemstring] output: " . `$systemstring 2>&1`);
	my $endtime = time;
	
	return $endtime - $starttime;
}


# ----------------------------------------------------------
# --------- ConvertDicom -----------------------------------
# ----------------------------------------------------------
sub ConvertDicom() {
	my ($filetype, $indir, $outdir, $req_gzip, $uid, $studynum, $seriesnum, $datatype) = @_;

	my $sqlstring;

	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $origDir = getcwd;
	
	my $gzip;
	if ($req_gzip) { $gzip = "-z y"; }
	else { $gzip = "-z n"; }
	
	my $starttime = time;
			
	WriteLog("Working on [$indir]");

	# in case of par/rec, the argument list to dcm2niix is a file instead of a directory
	my $fileext = "";
	if ($datatype eq "parrec") { $fileext = "/*.par"; }
	
	my $systemstring;
	chdir($indir);
	switch ($filetype) {
		case "nifti4d" { $systemstring = "$cfg{'scriptdir'}/./dcm2niix -1 -b n -z y -o '$outdir' $indir$fileext"; }
		case "nifti3d" { $systemstring = "$cfg{'scriptdir'}/./dcm2niix -1 -b n -z 3 -o '$outdir' $indir$fileext"; }
		case "bids" { $systemstring = "$cfg{'scriptdir'}/./dcm2niix -1 -b y -z y -o '$outdir' $indir$fileext"; }
		else { return(0,0,0,0,0,0); }
	}
	
	WriteLog("Systemstring: $systemstring");

	MakePath($outdir);
	# delete any files that may already be in the output directory.. example, an incomplete series was put in the output directory
	# remove any stuff and start from scratch to ensure proper file numbering
	if (($outdir ne "") && ($outdir ne "/") ) {
		WriteLog(`rm -f $outdir/*.hdr $outdir/*.img $outdir/*.nii $outdir/*.gz 2>&1`);
	}
	WriteLog(CompressText("$systemstring (" . `$systemstring 2>&1` . ")"));

	# converstion should be done, so check if it actually gzipped the file
	if (($req_gzip) && ($filetype ne "bids")) {
		$systemstring = "cd $outdir; gzip *";
		WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
	}
	
	# rename the files into something meaningful
	my ($numimg, $numhdr, $numnii, $numgz) = BatchRenameFiles($filetype, $outdir, $seriesnum, $studynum, $uid);
	WriteLog("Done renaming files: $numimg, $numhdr, $numnii, $numgz");

	WriteLog("About to get directory size...");
	my $dirsize = GetDirectorySize($outdir);
	WriteLog("Done with directory size, about to get total cpu time...");
	my $endtime = time;
	WriteLog("Done getting total cpu time...");
	my $cputime = $endtime - $starttime;

	# change back to original directory before leaving
	chdir($origDir);
	WriteLog("done changing back to $origDir");
	return ($numimg, $numhdr, $numnii, $numgz, $dirsize, $cputime);
}


# ----------------------------------------------------------
# --------- BatchRenameFiles -------------------------------
# ----------------------------------------------------------
sub BatchRenameFiles {
	my ($filetype, $dir, $seriesnum, $studynum, $uid) = @_;
	
	chdir($dir) || die("Cannot open directory $dir!\n");
	my @imgfiles = <*.img>;
	my @hdrfiles = <*.hdr>;
	my @niifiles = <*.nii>;
	my @gzfiles = <*.nii.gz>;
	my @jsonfiles = <*.json>;
	my @bvecfiles = <*.bvec>;
	my @bvalfiles = <*.bval>;

	my $i = 1;
	foreach my $imgfile (nsort @imgfiles) {
		my $oldfile = $imgfile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".img";
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}

	$i = 1;
	foreach my $hdrfile (nsort @hdrfiles) {
		my $oldfile = $hdrfile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".hdr";
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}
	
	$i = 1;
	foreach my $niifile (nsort @niifiles) {
		my $oldfile = $niifile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".nii";
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}

	$i = 1;
	foreach my $gzfile (nsort @gzfiles) {
		my $oldfile = $gzfile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".nii.gz";
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}
	
	$i = 1;
	foreach my $jsonfile (nsort @jsonfiles) {
		my $oldfile = $jsonfile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".json";
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}

	$i = 1;
	foreach my $bvecfile (nsort @bvecfiles) {
		my $oldfile = $bvecfile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".bvec";
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}

	$i = 1;
	foreach my $bvalfile (nsort @bvalfiles) {
		my $oldfile = $bvalfile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".bval";
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}
	
	return ($#imgfiles+1, $#hdrfiles+1, $#niifiles+1, $#gzfiles+1);
}


# -------------------------------------------------------------------------
# -------------- WriteNDARHeader ------------------------------------------
# -------------------------------------------------------------------------
sub WriteNDARHeader() {
	my ($file, $modality) = @_;

	open(F,"> $file");
	
	if (lc($modality) eq 'mr') {
		print F "image,3\n";
		print F "subjectkey,src_subject_id,interview_date,interview_age,gender,comments_misc,image_file,image_thumbnail_file,image_description,image_file_format,image_modality,scanner_manufacturer_pd,scanner_type_pd,scanner_software_versions_pd,magnetic_field_strength,mri_repetition_time_pd,mri_echo_time_pd,flip_angle,acquisition_matrix,mri_field_of_view_pd,patient_position,photomet_interpret,receive_coil,transmit_coil,transformation_performed,transformation_type,image_history,image_num_dimensions,image_extent1,image_extent2,image_extent3,image_extent4,extent4_type,image_extent5,extent5_type,image_unit1,image_unit2,image_unit3,image_unit4,image_unit5,image_resolution1,image_resolution2,image_resolution3,image_resolution4,image_resolution5,image_slice_thickness,image_orientation,qc_outcome,qc_description,qc_fail_quest_reason,decay_correction,frame_end_times,frame_end_unit,frame_start_times,frame_start_unit,pet_isotope,pet_tracer,time_diff_inject_to_image,time_diff_units,scan_type,scan_object,data_file2,data_file2_type,experiment_description,experiment_id,pulse_seq,slice_acquisition,software_preproc,study,week,slice_timing,bvek_bval_files\n";
	}
	if (lc($modality) eq 'eeg') {
		print F "eeg_sub_files,1\n";
		print F "subjectkey,src_subject_id,interview_date,interview_age,gender,comments_misc,capused,ofc,experiment_id,experiment_notes,experiment_terminated,experiment_validity,data_behavioralperformance_acc,data_behavioralperformance_rt,data_file1,data_file1_type,data_file2,data_file2_type,data_file3,data_file3_type,data_file4,data_file4_type,data_includedtrials,data_validity\n";
	}
	if (lc($modality) eq 'et') {
		print F "et_subject_experiment,1\n";
		print F "subjectkey,src_subject_id,interview_date,interview_age,gender,phenotype,experiment_id,comments_misc,experiment_validity,experiment_notes,experiment_terminated,expcond_validity,expcond_notes,data_file1,data_file1_type,data_file2,data_file2_type,data_file3,data_file3_type,data_file4,data_file4_type\n"
	}
	close(F);
}


# -------------------------------------------------------------------------
# -------------- WriteNDARSeries ------------------------------------------
# -------------------------------------------------------------------------
sub WriteNDARSeries() {
	my ($file, $imagefile, $behfile, $behdesc, $seriesid, $modality, $indir) = @_;

	# get the information on the subject and series
	my $sqlstring = "select *, date_format(study_datetime,'%m/%d/%Y') 'study_datetime', TIMESTAMPDIFF(MONTH, birthdate, study_datetime) 'ageatscan' from " . lc($modality) . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join projects e on c.project_id = e.project_id where " . lc($modality) . "series_id = $seriesid";
	WriteLog($sqlstring);
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		my $subjectid = $row{'subject_id'};
		my $enrollmentid = $row{'enrollment_id'};
		my $guid = $row{'guid'};
		my $seriesdatetime = $row{'series_datetime'};
		my $seriestr = $row{'series_tr'};
		my $serieste = $row{'series_te'};
		my $seriesflip = $row{'series_flip'};
		my $seriesprotocol = $row{'series_protocol'};
		my $seriessequence = $row{'series_sequencename'};
		my $seriesnotes = $row{'series_notes'};
		my $imagetype = $row{'image_type'};
		my $imagecomments = $row{'image_comments'};
		my $seriesspacingx = $row{'series_spacingx'};
		my $seriesspacingy = $row{'series_spacingy'};
		my $seriesspacingz = $row{'series_spacingz'};
		my $seriesfieldstrength = $row{'series_fieldstrength'};
		my $imgrows = $row{'img_rows'};
		my $imgcols = $row{'img_cols'};
		my $imgslices = $row{'img_slices'};
		my $datatype = uc($row{'data_type'});
		my $studydatetime = $row{'study_datetime'};
		my $birthdate = $row{'birthdate'};
		my $gender = $row{'gender'};
		my $uid = $row{'uid'};
		my $ageatscan = $row{'ageatscan'};
		my $studyageatscan = $row{'study_ageatscan'};
		my $seriesdesc = $row{'series_desc'};
		my $boldreps = $row{'bold_reps'};
		my $usecustomid = $row{'project_usecustomid'};
		my $srcsubjectid = $uid;
		my $projectid = $row{'project_id'};
	
		# skip this if the GUID is blank... can't submit to NDAR/RDoC if its blank anyway
		if (trim($guid) eq "") { WriteLog("GUID was blank, skipping writing this row"); return; }
		
		my $numdim;
		if ($boldreps > 1) {
			$numdim = 4;
		}
		else {
			$numdim = 3;
		}
		if ($modality eq "mr") { $modality = "mri";}
		$modality = uc($modality);
		
		if ($imgrows < 1) { $imgrows = 1; }
		if ($imgcols < 1) { $imgcols = 1; }
		if ($imgslices < 1) { $imgslices = 1; }
		
		if (($studyageatscan > 0) && ($studyageatscan < 120)) {
			$ageatscan = $studyageatscan*12;
		}
		
		#if ($usecustomid) {
		my $altuid = GetPrimaryAlternateUID($subjectid, $enrollmentid);
		if ($altuid eq "") {
			$srcsubjectid = $uid;
		}
		else {
			$srcsubjectid = $altuid;
		}
		
		# get some DICOM specific tags from the first file in the series
		chdir($indir);
		my @dcmfiles = <*.dcm>;
		my $exifTool = new Image::ExifTool;
		my $tags = $exifTool->ImageInfo($dcmfiles[0]);
		if (!defined($tags->{'ProtocolName'})) { $tags->{'ProtocolName'} = ""; }
		if (!defined($tags->{'PercentPhaseFieldOfView'})) { $tags->{'PercentPhaseFieldOfView'} = 0; }
		
		my $Manufacturer = $tags->{'Manufacturer'};
		my $PatientPosition = $tags->{'PatientPosition'};
		my $AcquisitionMatrix = $tags->{'AcquisitionMatrix'};
		my $SoftwareVersion = $tags->{'SoftwareVersion'};
		my $PhotometricInterpretation = $tags->{'PhotometricInterpretation'};
		my $PercentPhaseFieldOfView = $tags->{'PercentPhaseFieldOfView'};
		my $ManufacturersModelName = $tags->{'ManufacturersModelName'};
		my $TransmitCoilName = $tags->{'TransmitCoilName'};
		my $ProtocolName = $tags->{'ProtocolName'};
		my $SequenceName = $tags->{'SequenceName'};

		# clean up the tags
		if (trim($Manufacturer) eq "") { $Manufacturer = "Unknown"; }
		if (trim($PatientPosition) eq "") { $PatientPosition = "Unknown"; }
		if (trim($SoftwareVersion) eq "") { $SoftwareVersion = "Unknown"; }
		if (trim($PhotometricInterpretation) eq "") { $PhotometricInterpretation = "RGB"; }
		if (trim($ManufacturersModelName) eq "") { $ManufacturersModelName = "Unknown"; }
		if (trim($TransmitCoilName) eq "") { $TransmitCoilName = "Unknown"; }
		
		# figure out the scan type (T1,T2,DTI,fMRI)
		my $scantype = "MR structural (T1)";
		if (($boldreps > 1) || ($seriessequence =~ /epfid2d1/)) {
			$scantype = "fMRI";
		}
		if (($seriesdesc =~ /perfusion/i) && ($seriessequence =~ /ep2d_perf_tra/i)) {
			$scantype = "MR diffusion";
		}
		if (($seriesdesc =~ /dti/i) || ($seriesdesc =~ /dwi/i)) {
			$scantype = "MR diffusion";
		}
		if ($seriesdesc =~ /T2/i) {
			$scantype = "MR structural (T2)";
		}
		
		# build the aquisition matrix
		if (trim($AcquisitionMatrix) eq "") {
			$AcquisitionMatrix = "0 0 0 0";
		}
		
		my @AcqParts = split(' ', $AcquisitionMatrix);
		my $FOV = "0x0";
		$FOV = ($AcqParts[0]*$seriesspacingx*$PercentPhaseFieldOfView)/100.0 . "mm x " . ($AcqParts[3]*$seriesspacingy*$PercentPhaseFieldOfView)/100.0 . "mm";
		
		open(F,">> $file");
		
		if ($modality eq "MRI") {
			print F "$guid,$srcsubjectid,$studydatetime,$ageatscan,$gender,$imagetype,$imagefile,,$seriesdesc,$datatype,$modality,$Manufacturer,$ManufacturersModelName,$SoftwareVersion,$seriesfieldstrength,$seriestr,$serieste,$seriesflip,$AcquisitionMatrix,$FOV,$PatientPosition,$PhotometricInterpretation,,$TransmitCoilName,No,,,$numdim,$imgcols,$imgrows,$imgslices,$boldreps,timeseries,,,Millimeters,Millimeters,Millimeters,Milliseconds,,$seriesspacingx,$seriesspacingy,$seriesspacingz,$seriestr,,$seriesspacingz,Axial,,,,,,,,,,,,,$scantype,Live,$behfile,$behdesc,$ProtocolName,,$seriessequence,1,,,0,Yes,Yes\n";
		}
		elsif ($modality eq "EEG") {
			my $expid = 0;
			if (($seriesprotocol eq 'domino') || ($seriesprotocol eq 'domino') || ($seriesprotocol eq 'domino 10')) { $expid = 115; }
			if (($seriesprotocol eq '1SPMain') || ($seriesprotocol eq '2SPMain') || ($seriesprotocol eq '3SPMain')) { $expid = 114; }
			if (($seriesprotocol eq '1SPGender') || ($seriesprotocol eq '2SPGender') || ($seriesprotocol eq '3SPGender')) { $expid = 114; }
			if (($seriesprotocol eq '1HNumber') || ($seriesprotocol eq '2HNumber') || ($seriesprotocol eq '3HNumber')) { $expid = 113; }
			if (($seriesprotocol eq '1HPain') || ($seriesprotocol eq '2HPain') || ($seriesprotocol eq '3HPain')) { $expid = 113; }
			
			if ((lc($seriesprotocol) eq 'gating') || (lc($seriesprotocol) eq 'gating2') || (lc($seriesprotocol) eq 'gating3')) { $expid = 530; }
			if ((lc($seriesprotocol) eq 'resteyesopen') || (lc($seriesprotocol) eq 'rest') || (lc($seriesprotocol) eq 'rest - eyes open')) { $expid = 528; }
			if ((lc($seriesprotocol) eq 'resteyesclosed') || (lc($seriesprotocol) eq 'rest - eyes closed')) { $expid = 556; }
			if ((lc($seriesprotocol) eq 'oddball') || (lc($seriesprotocol) eq 'oddball - beh data')) { $expid = 529; }
			
			# PARDIP
			if ( (($projectid == 173) || ($projectid == 174) || ($projectid == 176)) && (lc($seriesprotocol) eq 'auditory steady state') ) { $expid = 538; }
			if ( (($projectid == 173) || ($projectid == 174) || ($projectid == 176)) && (lc($seriesprotocol) eq 'rmr') ) { $expid = 575; }
			if ( (($projectid == 173) || ($projectid == 174) || ($projectid == 176)) && (lc($seriesprotocol) eq 'rest - eyes open') ) { $expid = 531; }
			if ( (($projectid == 173) || ($projectid == 174) || ($projectid == 176)) && (lc($seriesprotocol) eq 'pro-saccade') ) { $expid = 566; }
			if ( (($projectid == 173) || ($projectid == 174) || ($projectid == 176)) && (lc($seriesprotocol) eq 'anti-saccade') ) { $expid = 569; }
			if ( (($projectid == 173) || ($projectid == 174) || ($projectid == 176)) && (lc($seriesprotocol) eq 'iaps') ) { $expid = 537; }
			if ( (($projectid == 173) || ($projectid == 174) || ($projectid == 176)) && (lc($seriesprotocol) eq 'visual steady state') ) { $expid = 539; }
			if ( (($projectid == 173) || ($projectid == 174) || ($projectid == 176)) && (lc($seriesprotocol) eq 'oddball') ) { $expid = 532; }
			if ( (($projectid == 173) || ($projectid == 174) || ($projectid == 176)) && (lc($seriesprotocol) eq 'gating') ) { $expid = 536; }

			# BSNIP2
			if ( (($projectid == 185) || ($projectid == 187) || ($projectid == 191) || ($projectid == 192) || ($projectid == 194)) && (lc($seriesprotocol) eq 'rest - eyes open') ) { $expid = 549; }
			if ( (($projectid == 185) || ($projectid == 187) || ($projectid == 191) || ($projectid == 192) || ($projectid == 194)) && (lc($seriesprotocol) eq 'rmr') ) { $expid = 587; }
			if ( (($projectid == 185) || ($projectid == 187) || ($projectid == 191) || ($projectid == 192) || ($projectid == 194)) && (lc($seriesprotocol) eq 'anti-saccade') ) { $expid = 559; }
			if ( (($projectid == 185) || ($projectid == 187) || ($projectid == 191) || ($projectid == 192) || ($projectid == 194)) && (lc($seriesprotocol) eq 'pro-saccade') ) { $expid = 558; }
			if ( (($projectid == 185) || ($projectid == 187) || ($projectid == 191) || ($projectid == 192) || ($projectid == 194)) && (lc($seriesprotocol) eq 'iaps') ) { $expid = 582; }
			if ( (($projectid == 185) || ($projectid == 187) || ($projectid == 191) || ($projectid == 192) || ($projectid == 194)) && (lc($seriesprotocol) eq 'visual steady state') ) { $expid = 584; }
			if ( (($projectid == 185) || ($projectid == 187) || ($projectid == 191) || ($projectid == 192) || ($projectid == 194)) && (lc($seriesprotocol) eq 'auditory steady state') ) { $expid = 583; }
			if ( (($projectid == 185) || ($projectid == 187) || ($projectid == 191) || ($projectid == 192) || ($projectid == 194)) && (lc($seriesprotocol) eq 'oddball') ) { $expid = 550; }
			if ( (($projectid == 185) || ($projectid == 187) || ($projectid == 191) || ($projectid == 192) || ($projectid == 194)) && (lc($seriesprotocol) eq 'gating') ) { $expid = 581; }
			
			print F "$guid,$uid,$studydatetime,$ageatscan,$gender,$seriesprotocol,,,$expid,\"$seriesnotes\",,,,,$imagefile,,,,,,,,,\n";
		}
		elsif ($modality eq "ET") {
			my $expid = 0;
			print F "$guid,$uid,$studydatetime,$ageatscan,$gender,Unknown,$expid,$seriesprotocol,,\"$seriesnotes\",,,,$imagefile,Eyetracking,,,,,,\n";
		}
		close(F);
	}
	else {
		WriteLog("No rows found for this series... [$file, $imagefile, $behfile, $behdesc, $seriesid, $modality, $indir] ");
	}
}
