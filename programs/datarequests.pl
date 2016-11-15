#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB datarequests.pl
# Copyright (C) 2004 - 2016
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

require 'nidbroutines.pl';
our %cfg;
LoadConfig();

our $db;

# script specific variables
our $scriptname = "datarequests";
our $lockfileprefix = "datarequests"; # lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					 # lockfile name created for this instance of the program
our $log;							 # logfile handle created for this instance of the program
our $numinstances = 2;				 # number of times this program can be run concurrently

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
	my $x = ProcessDataRequests();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- ProcessDataRequests ----------------------------
# ----------------------------------------------------------
sub ProcessDataRequests {
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
	$sqlstring = "update modules set module_laststart = now(), module_status = 'running' where module_name = '$scriptname'";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	
	my $systemstring;
	my $exportdir = CreateLogDate();
	my %subjectwritten = ();
	my %studywritten = ();
	my %serieswritten = ();
	my $headerwritten = 0;
	my $req_destinationtype;
	my $publicdownloadid = 0;
	my $groupid = 0;
	my $lastgroupid = 0;
	
	# loop through all groups of data requests. each request in a group should have the same the modality
	$sqlstring = "select distinct(req_groupid) 'req_groupid', req_modality, req_username, req_destinationtype, req_nidbserver, req_nidbusername, req_nidbpassword from data_requests where req_status = 'pending' or req_status = '' order by req_groupid, req_date";
	WriteLog($sqlstring);
	#WriteLog('A');
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my $tmpwebdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(20);
		while (my %row = $result->fetchhash) {
			$groupid = $row{'req_groupid'};
			my $modality = $row{'req_modality'};
			my $req_username = $row{'req_username'};
			$req_destinationtype = $row{'req_destinationtype'};
			my $remotenidbserver = $row{'req_nidbserver'};
			my $remotenidbusername = $row{'req_nidbusername'};
			my $remotenidbpassword = $row{'req_nidbpassword'};
			my $newstatus = "";
			my $results = "";
			#WriteLog('B');
			my $transactionid;

			# check to see if ANY of the series in this group have already started processing, if so, skip it
			my $sqlstringA = "select req_status, req_date from data_requests where req_groupid = $groupid and (req_status <> '' and req_status <> 'pending' and req_status <> 'cancelled')";
			WriteLog("$sqlstringA");
			my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
			if ($resultA->numrows > 0) {
				my %rowA = $resultA->fetchhash;
				my $reqdate = $rowA{'req_date'};
				
				WriteLog("This group [$groupid] already has " . $resultA->numrows . " series which do not have a pending or blank status. That means at least one of the series is probably already processing (as of $reqdate) or is cancelled");
				next;
			}
			
			# needed to know the modality before we can get the actual series information
			$sqlstringA = "select sha1(e.name) 'sha1name', sha1(birthdate) 'sha1dob', a.*, b.*, d.project_name, d.project_costcenter, e.uid, e.subject_id, e.uuid2, f.* from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id left join data_requests f on f.req_seriesid = a.$modality" . "series_id where f.req_groupid = $groupid order by b.study_id, a.series_num";
			WriteLog("$sqlstringA");
			$resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
			my $currentstudyid;
			my $laststudyid = 0;
			my $newseriesnum = 0;
			# only create a transaction if there is something to send
			if ($resultA->numrows > 0) {
				# if this is sent remotely to an NiDB server, get a transaction ID
				if ($req_destinationtype eq "remotenidb") {
					# build a cURL string to start the transaction
					my $systemstring = "curl -gs -F 'action=startTransaction' -F 'u=$remotenidbusername' -F 'p=$remotenidbpassword' $remotenidbserver/api.php";
					WriteLog("[$systemstring] --> " . `$systemstring 2>&1`);
					$transactionid = trim(`$systemstring`);
					WriteLog("TransactionID: [$transactionid]");
				}
			}
			while (my %rowA = $resultA->fetchhash) {
				#WriteLog('C');
				my $request_id = $rowA{'request_id'};
				my $series_id = $rowA{$modality . 'series_id'};
				$req_destinationtype = $rowA{'req_destinationtype'};
				my $req_downloadimaging = $rowA{'req_downloadimaging'};
				my $req_downloadbeh = $rowA{'req_downloadbeh'};
				my $req_downloadqc = $rowA{'req_downloadqc'};
				my $req_nfsdir = $rowA{'req_nfsdir'};
				my $req_filetype = $rowA{'req_filetype'};
				my $req_dirformat = $rowA{'req_dirformat'};
				my $req_seriesid = $rowA{'req_seriesid'};
				my $req_preserveseries = $rowA{'req_preserveseries'};
				my $req_gzip = $rowA{'req_gzip'};
				my $req_pipelinedownloadid = $rowA{'req_pipelinedownloadid'};
				my $req_anonymize = $rowA{'req_anonymize'};
				my $req_timepoint = $rowA{'req_timepoint'};
				my $req_behonly = $rowA{'req_behonly'};
				my $req_behformat = $rowA{'req_behformat'};
				my $req_behdirrootname = $rowA{'req_behdirrootname'};
				my $req_behdirseriesname = $rowA{'req_behdirseriesname'};
				my $remoteftpusername = $rowA{'req_ftpusername'};
				my $remoteftppassword = $rowA{'req_ftppassword'};
				my $remoteftpserver = $rowA{'req_ftpserver'};
				my $remoteftpport = $rowA{'req_ftpport'};
				my $remoteftppath = $rowA{'req_ftppath'};
				$remotenidbserver = $rowA{'req_nidbserver'};
				$remotenidbusername = $rowA{'req_nidbusername'};
				$remotenidbpassword = $rowA{'req_nidbpassword'};
				my $remotenidbinstanceid = $rowA{'req_nidbinstanceid'};
				my $remotenidbprojectid = $rowA{'req_nidbprojectid'};
				my $remotenidbsiteid = $rowA{'req_nidbsiteid'};
				$publicdownloadid = $rowA{'req_downloadid'};
				my $study_datetime = $rowA{'study_datetime'};
				my $study_alternateid = $rowA{'study_alternateid'};
				my $study_num = $rowA{'study_num'};
				my $study_id = $rowA{'study_id'};
				my $series_num = $rowA{'series_num'};
				my $seriesnotes = $rowA{'series_notes'};
				my $data_type = $rowA{'data_type'};
				my $uid = $rowA{'uid'};
				my $subjectid = $rowA{'subject_id'};
				my $uuid = $rowA{'uuid2'};
				my $sha1name = $rowA{'sha1name'};
				my $sha1dob = $rowA{'sha1dob'};
				my $project_costcenter = $rowA{'project_costcenter'};
				my $numfilesbeh = $rowA{'numfiles_beh'};
				$currentstudyid = $study_id;

				# if datatype (dicom, nifti, parrec) is blank because its not MR, then the datatype will actually be the modality
				if ($data_type eq '') {
					$data_type = $modality;
				}

				# check if this module should be running now or not
				if (!ModuleCheckIfActive($scriptname, $db)) {
					WriteLog("Not supposed to be running right now");
					# update the stop time
					ModuleDBCheckOut($scriptname, $db);
					return 0;
				}
				
				# get the list of all IDs to send along with the series
				my $sqlstringX = "select altuid from subject_altuid where subject_id = '$subjectid'";
				WriteLog("SQL: $sqlstringX");
				my $resultX = SQLQuery($sqlstringX, __FILE__, __LINE__);
				my $altuids = $uid;
				while (my %rowX = $resultX->fetchhash) {
					$altuids .= "," . $rowX{'altuid'};
				}
				
				# first check if this status of this row has changed... it may been changed since the list was first gathered
				my $sqlstringB  = "select * from data_requests where request_id = $request_id";
				WriteLog("SQL: $sqlstringB");
				my $resultB = $db->query($sqlstringB) || SQLError($sqlstringB, $db->errmsg());
				my %rowB = $resultB->fetchhash;
				my $status = $rowB{'req_status'};
				if (($status eq "processing") || ($status eq "complete")) {
					WriteLog("Woah Nelly! Should be skipping this row, the status is now [$status], but theres only one instance of this program running, so how is the status being changed? Unless there is another server running the same programs against THIS database!");
					next;
				}
				else {
					# indicate that the row is now being processing
					my $sqlstring2  = "update data_requests set req_status = 'processing' where request_id = $request_id";
					WriteLog("SQL: $sqlstring2");
					my $result2 = SQLQuery($sqlstring2, __FILE__, __LINE__);
					WriteLog("Updated " . $result2->affectedrows . " rows");
				}
				my $starttime = time;

				#WriteLog('D');
			
				WriteLog("Destination type: $req_destinationtype");

				my $subjectdir;
				my $fullexportdir;
				my $newdir;
				my $behoutdir;
				my $qcoutdir;
				
				my ($sec, $min, $hour, $day, $month, $year, $tz) = strptime($study_datetime);
				$year -= 100;
				$year += 2000;
				$month++;
				if (length($hour) == 1) { $hour = "0" . $hour; }
				if (length($sec) == 1) { $sec = "0" . $sec; }
				if (length($min) == 1) { $min = "0" . $min; }
				if (length($month) == 1) { $month = "0" . $month; }
				if (length($day) == 1) { $day = "0" . $day; }
				my $datetime = "$year$month$day" . "_$hour$min$sec";
				
				# create output directory name
				switch ($req_dirformat) {
					case "datetime" { $newdir = $datetime; }
					case "datetimeshortid" { $newdir = $datetime . "_$uid" . $study_num; }
					case "datetimelongid" { $newdir = $datetime . "_$uid" . "_$project_costcenter" . "_$study_num";}
					case "datetimeorigid" { $newdir = $datetime . "_$study_alternateid"; }
					case "shortid" { $newdir = $uid . $study_num; }
					case "longid" { $newdir = $uid . "_$project_costcenter" . "_$study_num"; }
					case "longitudinal" { $newdir = "$uid/time$req_timepoint"; }
					case "altuid" {
						# get the primary (or first) alternate UID
						$sqlstringB = "select altuid from subject_altuid where subject_id = '$subjectid' order by isprimary desc limit 1";
						my $resultB = SQLQuery($sqlstringB, __FILE__, __LINE__);
						my %rowB = $resultB->fetchhash;
						my $altuid = trim($rowB{'altuid'});
						if ($altuid eq "") { $altuid = $uid; }
						
						$newdir = $altuid;
					}
				}

				my $finalseriesnum;
				# create the new series number
				if ($req_preserveseries == 1) {
					$newseriesnum = $series_num;
					$finalseriesnum = $newseriesnum;
				}
				if ($req_preserveseries == 2) {
					# get the protocol name to be used in place of the series number
					my $sqlstringC = "select series_desc from $modality" . "_series where $modality" . "series_id = $series_id";
					WriteLog($sqlstringC);
					my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
					my %rowC = $resultC->fetchhash;
					$newseriesnum = trim($rowC{'series_desc'});
					WriteLog("NewSeriesNum: [$newseriesnum]");
					$newseriesnum =~ s/ /\_/gi;
					$finalseriesnum = $newseriesnum;
				}
				# ABIDE format
				if ($req_preserveseries == 3) {
					# get the protocol name to be used in place of the series number
					my $sqlstringC = "select series_altdesc from $modality" . "_series where $modality" . "series_id = $series_id";
					WriteLog($sqlstringC);
					my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
					my %rowC = $resultC->fetchhash;
					my $seriesdesc = trim($rowC{'series_altdesc'});
					$seriesdesc =~ s/ /\_/gi;
					
					if ($laststudyid ne $currentstudyid) {
						$newseriesnum = 1;
					}
					else {
						$newseriesnum++;
					}
					WriteLog("NewSeriesNum: [$newseriesnum]");
					$finalseriesnum = $seriesdesc . '_' . $newseriesnum;
					$newdir .= "/session_" . $study_num;
				}
				if ($req_preserveseries == 0) {
					WriteLog("current: $currentstudyid... last: $laststudyid");
					if ($laststudyid ne $currentstudyid) {
						$newseriesnum = 1;
					}
					else {
						$newseriesnum++;
					}
					$finalseriesnum = $newseriesnum
				}
				WriteLog("Preserve [$req_preserveseries] Old [$series_num] New [$finalseriesnum]");
			
				# determine what the actual export directory should be
				($fullexportdir, $behoutdir, $qcoutdir) = GetOutputDirectories($req_destinationtype, $newdir, $finalseriesnum, $req_behdirrootname, $req_behdirseriesname, $tmpwebdir, $req_behformat, $req_nfsdir);

				my $indir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/$data_type";
				my $behindir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/beh";
				my $qcindir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/qa";

				# check if the indir directory exists or is empty
				if (-e $indir) {
					WriteLog("indir [$indir] exists");
					if (IsDirEmpty($indir)) {
						WriteLog("indir [$indir] exists but is empty");
					}
				}
				else {
					WriteLog("indir [$indir] does not exist");
					$newstatus = 'problem';
					$results .= "[$indir] does not exist";
				}
				
				# check if the behindir directory exists or is empty
				if (-e $behindir) {
					WriteLog("behindir [$behindir] exists");
					if (IsDirEmpty($behindir)) {
						WriteLog("behindir [$behindir] exists but is empty");
					}
				}
				else {
					WriteLog("behindir [$behindir] does not exist");
				}
				
				# check if the qcindir directory exists or is empty
				if (-e $qcindir) {
					WriteLog("qcindir [$qcindir] exists");
					if (IsDirEmpty($qcindir)) {
						WriteLog("qcindir [$qcindir] exists but is empty");
					}
				}
				else {
					WriteLog("qcindir [$qcindir] does not exist");
				}
				
				# ----- copy locally (NFS, local FTP, Web, or Public download) -----
				if (($req_destinationtype eq "nfs") || ($req_destinationtype eq "localftp") || ($req_destinationtype eq "web") || ($req_destinationtype eq "publicdownload")) {
					#WriteLog("About to create $fullexportdir");
					# try to create the path
					if (!-d $fullexportdir) {
						WriteLog("Point 1");
						if (!MakePath($fullexportdir)) {
							$newstatus = "problem";
							$results = "$fullexportdir not created. Check permissions on destination directory.";
						}
					}
					#WriteLog('H');
					# see if the directory has been created
					if (-d $fullexportdir) {
						$systemstring = "chmod -Rf 777 $fullexportdir";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						
						my $tmpdir;
						$tmpdir = "";
						if ($req_downloadimaging) {
							WriteLog("Download Imaging option selected");
							# output the correct file type
							if (($req_filetype eq "dicom") || (($data_type ne "dicom") && ($data_type ne "parrec"))) {
								$systemstring = "cp $indir/* $fullexportdir";
								WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							}
							elsif ($req_filetype eq "qc") {
								# copy only the qc data
								$systemstring = "cp -R $cfg{'archivedir'}/$uid/$study_num/$series_num/qa $fullexportdir";
								WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
								
								# write the series info to a text file
								open (MRFILE,"> $fullexportdir/seriesInfo.txt");
								my $sqlstringC = "select * from mr_series where mrseries_id = $series_id";
								my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
								my %rowC = $resultC->fetchhash;
								foreach my $key ( keys %rowC ) {
									print MRFILE "$key: $rowC{$key}\n";
								}
								close (MRFILE);
							}
							else {
								$tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
								MakePath($tmpdir);
								WriteLog("Point 2");
							
								WriteLog("Calling ConvertDicom($req_filetype, $indir, $tmpdir, $req_gzip, $uid, $project_costcenter, $study_num, $series_num)");
								ConvertDicom($req_filetype, $indir, $tmpdir, $req_gzip, $uid, $project_costcenter, $study_num, $series_num, $data_type);
								WriteLog("Done calling ConvertDicom($req_filetype, $indir, $tmpdir, $req_gzip, $uid, $project_costcenter, $study_num, $series_num, $data_type)");
								
								WriteLog("About to copy files from $tmpdir to $fullexportdir");
								$systemstring = "cp $tmpdir/* $fullexportdir";
								WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
								WriteLog("Done copying files...");
							}
						}
						
						# copy the beh data
						if ($req_downloadbeh) {
							MakePath($behoutdir);
							$systemstring = "cp -R $behindir/* $behoutdir";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							
							$systemstring = "chmod -Rf 777 $behoutdir";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						}
						
						# copy the QC data
						if ($req_downloadqc) {
							MakePath($qcoutdir);
							$systemstring = "cp -R $qcindir/* $qcoutdir";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							
							$systemstring = "chmod -Rf 777 $qcoutdir";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						}
						
						# give full permissions to the files that were downloaded
						if ($req_destinationtype eq "nfs") {
							$systemstring = "chmod -Rf 777 $cfg{'mountdir'}$req_nfsdir/$newdir";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						}

						# change the modification/access timestamp to the current date/time
						find sub { #print $File::Find::name;
						utime(time,time,$File::Find::name) }, "$fullexportdir";
						
						if ($req_filetype eq 'dicom') {
							Anonymize($fullexportdir,$req_anonymize,uc($sha1name),uc($sha1dob));
						}
						
						# if its a public download, zip it and update the entry in the public downloads table
						if (($req_destinationtype eq "publicdownload") || ($req_destinationtype eq "web")) {
							$systemstring = "cp $tmpdir/* $tmpwebdir";
						}
						
						$newstatus = "complete";
						#$results = "$results";
						if (($tmpdir ne "") && ($tmpdir ne "/") && ($tmpdir ne "/tmp")) {
							rmtree($tmpdir);
						}
					}
				}
				
				# ----- send to remote NiDB site -----
				# for now, only DICOM data and beh can be sent to remote sites
				if ($req_destinationtype eq "remotenidb") {
					my $indir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/$data_type";
					my $behindir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/beh";
					my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
					my $tmpzip = $cfg{'tmpdir'} . "/" . GenerateRandomString(12) . ".tar.gz";
					my $tmpzipdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(12);
					MakePath($tmpdir);
					MakePath($tmpzipdir);
					MakePath("$tmpzipdir/beh");
					$systemstring = "cp $indir/* $tmpdir/";
					WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
					Anonymize($tmpdir,4,uc($sha1name),uc($sha1dob));
					
					# get the list of DICOM files
					my @dcmfiles;
					opendir(DIR,$tmpdir) || Error("Cannot open directory [$tmpdir]\n");
					my @files = readdir(DIR);
					closedir(DIR);
					foreach my $f (@files) {
						my $fulldir = "$tmpdir/$f";
						#WriteLog("Checking on [$fulldir]");
						if ((-f $fulldir) && ($f ne '.') && ($f ne '..')) {
							push(@dcmfiles,$f);
						}
					}
					my $numdcms = $#dcmfiles + 1;
					WriteLog("Found [$numdcms] dcmfiles");
					
					my @behfiles;
					# get the list of beh files
					if (-e $behindir) {
						opendir(DIR,$behindir) || Error("Cannot open directory [$behindir]\n");
						my @bfiles = readdir(DIR);
						closedir(DIR);
						foreach my $f (@bfiles) {
							my $fulldir = "$behindir/$f";
							#WriteLog("Checking on [$fulldir]");
							if ((-f $fulldir) && ($f ne '.') && ($f ne '..')) {
								push(@behfiles,$f);
							}
						}
					}
					
					# build the cURL string to send the actual data
					$systemstring = "curl -gs -F 'action=UploadDICOM' -F 'u=$remotenidbusername' -F 'p=$remotenidbpassword' -F 'transactionid=$transactionid' -F 'instanceid=$remotenidbinstanceid' -F 'projectid=$remotenidbprojectid' -F 'siteid=$remotenidbsiteid' -F 'dataformat=$modality' -F 'uuid=$uuid' -F 'seriesnotes=$seriesnotes' -F 'altuids=$altuids' -F 'seriesnum=$series_num' ";
					my $c = 0;
					foreach my $f (@dcmfiles) {
						$c++;
						#WriteLog("Appending file [$c] -> $f");
						#$systemstring .= "-F 'files[]=\@$tmpdir/$f' ";
						my $systemstring2 = "cp $tmpdir/$f $tmpzipdir/";
						WriteLog("$systemstring2 (".`$systemstring2 2>&1`.")");
					}
					
					$c = 0;
					foreach my $f (@behfiles) {
						$c++;
						#WriteLog("Appending file [$c] -> $f");
						#$systemstring .= "-F 'behs[]=\@$behindir/$f' ";
						my $systemstring2 = "cp $behindir/$f $tmpzipdir/beh/";
						WriteLog("$systemstring2 (".`$systemstring2 2>&1`.")");
					}
					
					# send the zip and send file
					my $systemstring2 = "cd $tmpzipdir; tar -czvf $tmpzip .";
					WriteLog("$systemstring2 (".`$systemstring2 2>&1`.")");
					# get MD5 before sending
					my $zipmd5 = file_md5_hex($tmpzip);
					$systemstring .= "-F 'files[]=\@$tmpzip' ";
					$systemstring .= "$remotenidbserver/api.php";
					$results = `$systemstring 2>&1`;
					WriteLog("$systemstring ($results)");
					
					my @parts = split(',',$results);
					if (trim($parts[0]) eq 'SUCCESS') {
						# a file was successfully received by api.php, now check the return md5
						if (trim(uc($parts[1])) eq uc($zipmd5)) {
							$newstatus = 'complete';
							WriteLog("Upload success: MD5 match");
						}
						else {
							$newstatus = 'problem';
							WriteLog("Upload fail: MD5 non-match");
						}
					}
					else {
						$newstatus = 'problem';
						WriteLog("Upload fail: got message [" . $parts[0] . "]");
					}
					
				}
				
				# ----- export data -----
				if ($req_destinationtype eq "export") {
					# build destination path
					my $indir = "$cfg{'archivedir'}/$uid/$study_num/$series_num";
					$fullexportdir = "$cfg{'ftpdir'}/NIDB-$exportdir/$uid/$study_num/$series_num";

					# try to create the path
					if (!-d $fullexportdir) {
						WriteLog("Point 1");
						if (!MakePath($fullexportdir)) {
							$newstatus = "problem";
							$results = "$fullexportdir not created. Check permissions on destination directory.";
						}
					}
					# see if the directory has been created
					if (-d $fullexportdir) {
						$systemstring = "chmod -Rf 777 $fullexportdir";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
					
						if (-d $indir) {
							$systemstring = "cp -R $indir/* $fullexportdir";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							WriteLog("Done copying files...");
							if (!defined($subjectwritten{'site'})) {
								WriteSiteMeta("$cfg{'ftpdir'}/NIDB-$exportdir");
								$subjectwritten{'site'} = 1;
							}
							if (!defined($subjectwritten{$uid})) {
								WriteSubjectMeta("$cfg{'ftpdir'}/NIDB-$exportdir", $uid);
								$subjectwritten{$uid} = 1;
							}
							WriteLog("Checkpoint i");
							if (!defined($studywritten{"$uid$study_num"})) {
								WriteStudyMeta("$cfg{'ftpdir'}/NIDB-$exportdir", $uid, $study_num);
								$studywritten{"$uid$study_num"} = 1;
							}
							WriteLog("Checkpoint ii");
							if (!defined($serieswritten{"$uid$study_num$series_num"})) {
								WriteSeriesMeta("$cfg{'ftpdir'}/NIDB-$exportdir", $uid, $study_num, $series_num, $series_id, $modality);
								$serieswritten{"$uid$study_num$series_num"} = 1;
							}
							WriteLog("Checkpoint iii");
						}
						else {
							$results .= "Unable to export $indir. Directory does not exist";
						}
					}
					$newstatus = 'complete';
				}
				
				# ----- export data -----
				if ($req_destinationtype eq "ndar") {
					# build destination path
					my $indir = "$cfg{'archivedir'}/$uid/$study_num/$series_num";
					my $behindir = "$cfg{'archivedir'}/$uid/$study_num/$series_num/beh";
					$fullexportdir = "$cfg{'ftpdir'}/NDAR-$exportdir";
					my $headerfile = "$fullexportdir/ndar.csv";

					# try to create the path
					if (!-d $fullexportdir) {
						WriteLog("Point 1");
						if (!MakePath($fullexportdir)) {
							$newstatus = "problem";
							$results = "$fullexportdir not created. Check permissions on destination directory.";
						}
					}
					# see if the directory has been created
					if (-d $fullexportdir) {
						$systemstring = "chmod -Rf 777 $fullexportdir";
						WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
					
						my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
						MakePath($tmpdir);
						if (($modality eq "mr") && ($data_type eq "dicom")) {
							$systemstring = "find $indir -iname '*.dcm' -exec cp {} $tmpdir \\;";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							Anonymize($tmpdir,2,'','');
						}
						elsif (($modality eq "mr") && ($data_type eq "parrec")) {
							$systemstring = "find $indir -iname '*.par' -exec cp {} $tmpdir \\;";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							$systemstring = "find $indir -iname '*.rec' -exec cp {} $tmpdir \\;";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						}
						else {
							$systemstring = "cp -r $indir/* $tmpdir";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
						}
						
						if (-d $indir) {
							# zip the data to the out directory
							my $zipfile = "$fullexportdir/$uid-$study_num-$series_num.zip";
							$systemstring = "zip -jrq $zipfile $tmpdir";
							WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
							WriteLog("Done zipping image files...");
							
							my $behzipfile;
							my $behdesc;
							if ($numfilesbeh > 0) {
								$behzipfile = "$uid-$study_num-$series_num-beh.zip";
								$systemstring = "zip -jrq $fullexportdir/$behzipfile $behindir";
								WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
								WriteLog("Done zipping beh files...");
								$behdesc = "Behavioral/design data file";
							}
							else {
								$behzipfile = "";
								$behdesc = "";
							}

							if (!$headerwritten) {
								WriteNDARHeader($headerfile, $modality);
								$headerwritten = 1;
							}
							WriteNDARSeries($headerfile, "$uid-$study_num-$series_num.zip", $behzipfile, $behdesc, $series_id, $modality, "$indir/$data_type");
						}
						else {
							$results .= "Unable to export $indir. Directory does not exist";
						}
						
						if ($modality eq "mr") {
							rmtree($tmpdir);
						}
					}
					$newstatus = 'complete';
				}
				
				# ----- copy to remote ftp site -----
				if ($req_destinationtype eq "remoteftp") {
					SendToRemoteFTP($req_behonly, $data_type, $indir, $req_filetype, $req_gzip, $uid, $project_costcenter, $study_num, $series_num, $req_behformat, $behoutdir, $behindir, $newseriesnum, $remoteftpserver, $remoteftpusername, $remoteftppassword, $remoteftppath,$newdir);
				}
				
				# finish up and record the time it took to process and the status
				my $endtime = time;
				my $totaltime = $endtime - $starttime;
				$results = EscapeMySQLString($results);
				$sqlstring = "update data_requests set req_cputime = $totaltime, req_completedate = now(), req_status = '$newstatus', req_results = '$results' where request_id = $request_id";
				WriteLog("SQL: $sqlstring");
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				
				# if this was a pipeline download, insert it into the pipeline_data table
				if ($req_pipelinedownloadid > 0) {
					$sqlstring = "insert into pipeline_data (pipelinedownload_id, pdata_groupnum, pdata_seriesid, pdata_modality, pdata_downloaddate, pdata_datadir) values ($req_pipelinedownloadid, $groupid, $req_seriesid, '$modality', now(), '$req_nfsdir/$newdir/$newseriesnum')";
					$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				}
				#WriteLog('K');
				
				$laststudyid = $currentstudyid;
			}
			
			# zip up the directory if its an export
			if ($req_destinationtype eq "export") {
				# zip up the directory (.tar.gz)
				$systemstring = "cd $cfg{'ftpdir'}; tar -czf NIDB-$exportdir.tar.gz --remove-files NIDB-$exportdir";
				WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
			}
			
			# if this is sent remotely to an NiDB server, end the transaction
			if ($req_destinationtype eq "remotenidb") {
				# build a cURL string to end the transaction
				$systemstring = "curl -gs -F 'action=endTransaction' -F 'u=$remotenidbusername' -F 'p=$remotenidbpassword' -F 'transactionid=$transactionid' $remotenidbserver/api.php";
				WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
			}

			$sqlstring = "select * from users where username = '$req_username'";
			WriteLog("SQL: $sqlstring");
			my $resultC = SQLQuery($sqlstring, __FILE__, __LINE__);
			my %rowC = $resultC->fetchhash;
			my $email = $rowC{'user_email'};
			my $sendmail_singlerequest = $rowC{'sendmail_singlerequest'};
			if ($sendmail_singlerequest) {
				# send an email
				SendTextEmail($email, "ADO server data request: $newstatus", $results);
			}
			
			# check if we're switching groups, then attempt to do a zip
			if ($groupid != $lastgroupid) {
				# should be doing the zip creation here (for each group!):
				# get the information about the download from the public_download table
				if ($req_destinationtype eq "publicdownload") {
					my $sqlstringC = "select * from public_downloads where pd_id = $publicdownloadid";
					WriteLog("SQL: $sqlstringC");
					my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
					my %rowC = $resultC->fetchhash;
					my $createdate = $rowC{'pd_createdate'};
					my $expiredate = $rowC{'pd_expiredate'};
					my $expiredays = $rowC{'pd_expiredays'};
					my $createdby = $rowC{'pd_createdby'};
					my $zippedsize = $rowC{'pd_zippedsize'};
					my $unzippedsize = $rowC{'pd_unzippedsize'};
					my $desc = $rowC{'pd_desc'};
					my $notes = $rowC{'pd_notes'};
					my $shareinternal = $rowC{'pd_shareinternal'};
					my $password = $rowC{'pd_password'};
					my $status = $rowC{'pd_status'};
					
					my $filename = "NiDB-$groupid.zip";
					my $zipfile = "$cfg{'webdownloaddir'}/$filename";
					my $outdir = $tmpwebdir;
					
					my $pwd = getcwd;
					WriteLog("Changing directory to [$outdir]");
					if (chdir($outdir)) { WriteLog("Successfully changed directory to [$outdir]"); }
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
				# if its a web download, do the zipping at the end
				if ($req_destinationtype eq "web") {
					my $zipfile = "$cfg{'webdownloaddir'}/NIDB-$groupid.zip";
					my $outdir;
					WriteLog("Final zip file will be [$zipfile]");
					WriteLog("tmpwebdir: [$tmpwebdir]");
					$outdir = $tmpwebdir;
					
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
						WriteLog("temp dir [$outdir] does not exist");
					}
				}
				
				# if the tmpwebdir was created, delete it
				if (-e $tmpwebdir) {
					if (trim($tmpwebdir) ne "") {
						WriteLog("Temporary web dir [$tmpwebdir] exists and will be deleted");
						rmtree($tmpwebdir);
					}
				}
				
				if ($publicdownloadid != 0) {
					my $sqlstringC = "update public_downloads set pd_status = 'complete' where pd_id = '$publicdownloadid'";
					WriteLog("SQL: $sqlstringC");
					my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
				}
				
			}
			
			$lastgroupid = $groupid;
		}
		
		$time = CreateCurrentDate();
		WriteLog("$scriptname Done... Current Time is $time");
		$ret = 1;
	}
	else {
		WriteLog("Nothing done");
	}
	
	# update the stop time
	$sqlstring = "update modules set module_laststop = now(), module_status = 'stopped' where module_name = '$scriptname'";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	WriteLog('M');
	
	return $ret;
}


# ----------------------------------------------------------
# --------- GetOutputDirectories ---------------------------
# ----------------------------------------------------------
sub GetOutputDirectories() {
	my ($req_destinationtype, $newdir, $newseriesnum, $req_behdirrootname, $req_behdirseriesname, $tmpwebdir, $req_behformat, $req_nfsdir) = @_;
	
	my $fullexportdir;
	my $qcoutdir;
	my $behoutdir;
	
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


# ----------------------------------------------------------
# --------- SendToRemoteFTP --------------------------------
# ----------------------------------------------------------
sub SendToRemoteFTP() {
	my ($req_behonly, $data_type, $indir, $req_filetype, $req_gzip, $uid, $project_costcenter, $study_num, $series_num, $req_behformat, $behoutdir, $behindir, $newseriesnum, $remoteftpserver, $remoteftpusername, $remoteftppassword, $remoteftppath,$newdir) = @_;
	
	my $origDir = getcwd;
	my $systemstring;
	my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
	MakePath($tmpdir);

	if (!$req_behonly) {
		if ($data_type ne "dicom") {
			my $systemstring = "cp $indir/* $tmpdir";
			WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
		}
		else {
			WriteLog("Calling ConvertDicom($req_filetype, $indir, $tmpdir, $req_gzip, $uid, $project_costcenter, $study_num, $series_num)");
			ConvertDicom($req_filetype, $indir, $tmpdir, $req_gzip, $uid, $project_costcenter, $study_num, $series_num, $data_type);
			WriteLog("Done calling ConvertDicom($req_filetype, $indir, $tmpdir, $req_gzip, $uid, $project_costcenter, $study_num, $series_num)");
		}
	}
	# copy the beh data
	if ($req_behformat ne "behnone") {
		unless(-d "$tmpdir$behoutdir"){
			MakePath("$tmpdir$behoutdir") or die ("Could not create $tmpdir$behoutdir because [$!]");
		}
		$systemstring = "cp -R $behindir/* $tmpdir$behoutdir";
		WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
	}
	
	my $zipfile = "$tmpdir/$newseriesnum.zip";
	$systemstring = "zip -j $zipfile $tmpdir/*";
	WriteLog(`$systemstring 2>&1`);

	chdir($tmpdir);
	
	# #my $zipfilepath = "$cfg{'archivedir'}/$studyscannerid/$studyscannerid" . "_$seriesnumber" . "_$imgformat.7z";
	my $ftpbatchfile = "FTPBatch-" . CreateLogDate() . ".sh";
	open FTPBAT, "> $ftpbatchfile";
	
	print FTPBAT "#!/bin/sh\n";
	print FTPBAT "/usr/kerberos/bin/ftp -nuiv $remoteftpserver <<EOT\n";
	print FTPBAT "user $remoteftpusername $remoteftppassword ^D\n";
	print FTPBAT "cd / ^D\n";
	# # loop through mkdir's that need to be made
	print FTPBAT "mkdir $remoteftppath/$newdir ^D\n";
	print FTPBAT "cd $remoteftppath/$newdir ^D\n";
	print FTPBAT "binary ^D\n";
	#print FTPBAT "lcd $tmpdir ^D\n";
	print FTPBAT "put $tmpdir/$newseriesnum.zip $newseriesnum.zip ^D\n";
	print FTPBAT "pwd ^D\n";
	print FTPBAT "status ^D\n";
	print FTPBAT "quit ^D\n";
	print FTPBAT "EOT\n";
	close FTPBAT;
	chmod(0777, $ftpbatchfile);

	my $results = `./$ftpbatchfile 2>&1`;
	WriteLog("[$results]");
	unlink $ftpbatchfile;
	my $newstatus = "check log";
	
	rmtree($tmpdir);
	
	# change back to original directory before leaving
	chdir($origDir);
	
	return ($results,$newstatus);
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
				#WriteLog("Anonymizing (level 2 - FULL) $File::Find::name");
				push(@systemstrings,$s);
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
			my $t = threads->new(\&ThreadedSystemCall,$systemstrings[$i]);
			push(@threads,$t);
			$i++;
		}
		WriteLog("Launched $numthreads threads, waiting for them to finish");
		# wait for them all to return
		foreach my $t (@threads) {
			my $cpu = $t->join;
			$totalcpu += $cpu;
		}
		WriteLog("Anonymize threads finished");
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
	#WriteLog("ThreadedSystemCall output: " . `$systemstring 2>&1`);
	my $endtime = time;
	
	return $endtime - $starttime;
}


# ----------------------------------------------------------
# --------- ConvertDicom -----------------------------------
# ----------------------------------------------------------
sub ConvertDicom() {
	my ($req_filetype, $indir, $outdir, $req_gzip, $uid, $project_costcenter, $study_num, $series_num, $data_type) = @_;

	my $sqlstring;

	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $origDir = getcwd;
	
	my $gzip;
	if ($req_gzip) { $gzip = "-g y"; }
	else { $gzip = "-g n"; }
	
	my $starttime = time;
			
	WriteLog("Working on [$indir]");
	#my $outdir;
	my $fileext;
	
	if ($data_type eq "dicom") { $fileext = "dcm"; }
	elsif ($data_type eq "parrec") { $fileext = "par"; }
	my $systemstring;
	chdir($indir);
	switch ($req_filetype) {
		case "nifti4d" {
			$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y $gzip -p n -i n -d n -f n -o '$outdir' *.$fileext";
		}
		case "nifti3d" {
			$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_3D.ini' -a y -e y $gzip -p n -i n -d n -f n -o '$outdir' *.$fileext";
		}
		case "analyze4d" {
			$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y $gzip -p n -i n -d n -f n -n n -s y -o '$outdir' *.$fileext";
		}
		case "analyze3d" {
			$systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_3D.ini' -a y -e y $gzip -p n -i n -d n -f n -n n -s y -o '$outdir' *.$fileext";
		}
		else {
			return(0,0,0,0,0,0);
		}
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
	if ($req_gzip) {
		$systemstring = "cd $outdir; gzip *";
		WriteLog("$systemstring (" . `$systemstring 2>&1` . ")");
	}
	
	# rename the files into something meaningful
	my ($numimg, $numhdr, $numnii, $numgz) = BatchRenameFiles($outdir, $series_num, $study_num, $uid, $project_costcenter);
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
	my ($dir, $seriesnum, $studynum, $uid, $costcenter) = @_;
	
	chdir($dir) || die("Cannot open directory $dir!\n");
	my @imgfiles = <*.img>;
	my @hdrfiles = <*.hdr>;
	my @niifiles = <*.nii>;
	my @gzfiles = <*.nii.gz>;

	my $i = 1;
	foreach my $imgfile (nsort @imgfiles) {
		my $oldfile = $imgfile;
		my $newfile = $uid . "_P$costcenter" . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".img";
		#WriteLog("$oldfile => $newfile");
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}

	$i = 1;
	foreach my $hdrfile (nsort @hdrfiles) {
		my $oldfile = $hdrfile;
		my $newfile = $uid . "_P$costcenter" . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".hdr";
		#WriteLog("$oldfile => $newfile");
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}
	
	$i = 1;
	foreach my $niifile (nsort @niifiles) {
		my $oldfile = $niifile;
		my $newfile = $uid . "_P$costcenter" . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".nii";
		#WriteLog("$oldfile => $newfile");
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}

	$i = 1;
	foreach my $gzfile (nsort @gzfiles) {
		my $oldfile = $gzfile;
		my $newfile = $uid . "_P$costcenter" . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".nii.gz";
		WriteLog("$oldfile => $newfile");
		WriteLog(`mv $oldfile $newfile 2>&1`);
		$i++;
	}
	
	return ($#imgfiles+1, $#hdrfiles+1, $#niifiles+1, $#gzfiles+1);
}


# -------------------------------------------------------------------------
# -------------- WriteSiteMeta --------------------------------------------
# -------------------------------------------------------------------------
sub WriteSiteMeta() {
	my ($outpath) = @_;

	my $sqlstring = "SELECT site_uuid, site_name, site_address, site_contact from nidb_sites where site_id = 1";
	WriteXMLFromSQL($sqlstring, "$outpath/site.xml");
}


# -------------------------------------------------------------------------
# -------------- WriteSubjectMeta -----------------------------------------
# -------------------------------------------------------------------------
sub WriteSubjectMeta() {
	my ($outpath, $uid) = @_;

	my $sqlstring = "SELECT birthdate, gender, ethnicity1, ethnicity2, height, weight, handedness, education, uid, uuid from subjects where uid = '$uid'";
	WriteXMLFromSQL($sqlstring, "$outpath/$uid/subject.xml");
}


# -------------------------------------------------------------------------
# -------------- WriteStudyMeta -----------------------------------------
# -------------------------------------------------------------------------
sub WriteStudyMeta() {
	my ($outpath, $uid, $study_num) = @_;

	my $sqlstring = "SELECT enroll_subgroup from enrollment where enrollment_id in (select enrollment_id from studies where study_num = $study_num and enrollment_id in (SELECT enrollment_id from enrollment where subject_id in (select subject_id from subjects where uid = '$uid')))";
	WriteXMLFromSQL($sqlstring, "$outpath/$uid/enrollment.xml");
	
	$sqlstring = "select study_num, study_desc, study_alternateid, study_modality, study_datetime, study_ageatscan, study_height, study_weight, study_bmi, study_performingphysician, study_site, study_institution, study_notes, study_radreadfindings from studies where study_num = $study_num and enrollment_id in (SELECT enrollment_id from enrollment where subject_id in (select subject_id from subjects where uid = '$uid'))";
	WriteXMLFromSQL($sqlstring, "$outpath/$uid/$study_num/study.xml");
	
}


# -------------------------------------------------------------------------
# -------------- WriteSeriesMeta ------------------------------------------
# -------------------------------------------------------------------------
sub WriteSeriesMeta() {
	my ($outpath, $uid, $study_num, $series_num, $seriesid, $modality) = @_;

	my $sqlstring = "SELECT * from " . $modality . "_series where " . $modality . "series_id = $seriesid";
	WriteXMLFromSQL($sqlstring, "$outpath/$uid/$study_num/$series_num/series.xml");
	
	if (lc($modality) eq "mr") {
		$sqlstring = "select * from mr_qa where mrseries_id = $seriesid";
		WriteXMLFromSQL($sqlstring, "$outpath/$uid/$study_num/$series_num/qa/qa.xml");
	}
}


# -------------------------------------------------------------------------
# -------------- WriteXMLFromSQL ------------------------------------------
# -------------------------------------------------------------------------
sub WriteXMLFromSQL() {
	my ($sql, $outpath) = @_;
	
	WriteLog("Running [$sql]");
	my $str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
	my $result = SQLQuery($sql, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			foreach my $column (keys %row) {
				$str .= "\n\t<$column>" . $row{$column} . "</$column>";
			}
		}
	}
	
	open(FILE,"> $outpath");
	print FILE $str;
	close(FILE);
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
	
	close(F);
}


# -------------------------------------------------------------------------
# -------------- WriteNDARSeries ------------------------------------------
# -------------------------------------------------------------------------
sub WriteNDARSeries() {
	my ($file, $imagefile, $behfile, $behdesc, $seriesid, $modality, $indir) = @_;

	# get the information on the subject and series
	my $sqlstring = "select *, date_format(study_datetime,'%m/%d/%Y') 'study_datetime', TIMESTAMPDIFF(MONTH, birthdate, study_datetime) 'ageatscan' from " . lc($modality) . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where " . lc($modality) . "series_id = $seriesid";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
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
	
		my $numdim;
		if ($boldreps > 1) {
			$numdim = 4;
		}
		else {
			$numdim = 3;
		}
		if ($modality eq "mr") { $modality = "mri";}
		$modality = uc($modality);
		
		if (($birthdate == "0001-01-01") || ($birthdate == "0000-00-00") || ($birthdate == 0) || ($ageatscan == 0) || (lc($ageatscan) eq 'null') || ($ageatscan eq '')) {
			$ageatscan = $studyageatscan*12;
		}
		
		# get some DICOM specific tags from the first file in the series
		chdir($indir);
		my @dcmfiles = <*.dcm>;
		my $exifTool = new Image::ExifTool;
		my $tags = $exifTool->ImageInfo($dcmfiles[0]);
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
			print F "$guid,$uid,$studydatetime,$ageatscan,$gender,$imagetype,$imagefile,,$seriesdesc,$datatype,$modality,$Manufacturer,$ManufacturersModelName,$SoftwareVersion,$seriesfieldstrength,$seriestr,$serieste,$seriesflip,$AcquisitionMatrix,$FOV,$PatientPosition,$PhotometricInterpretation,,$TransmitCoilName,No,,,$numdim,$imgcols,$imgrows,$imgslices,$boldreps,timeseries,,,Millimeters,Millimeters,Millimeters,Seconds,,$seriesspacingx,$seriesspacingy,$seriesspacingz,,,$seriesspacingz,Axial,,,,,,,,,,,,,$scantype,Live,$behfile,$behdesc,$ProtocolName,,$seriessequence,1,,,0,Yes\n";
		}
		if ($modality eq "EEG") {
			#print F "$guid,$uid,$studydatetime,$ageatscan,$gender,$seriesprotocol,\"$seriesnotes\",,,,,,,,,,,,$imagefile,,,\n";
			my $expid = 115;
			if (($seriesprotocol eq '1SPMain') || ($seriesprotocol eq '2SPMain') || ($seriesprotocol eq '3SPMain')) { $expid = 114; }
			if (($seriesprotocol eq '1SPGender') || ($seriesprotocol eq '2SPGender') || ($seriesprotocol eq '3SPGender')) { $expid = 114; }
			if (($seriesprotocol eq '1HNumber') || ($seriesprotocol eq '2HNumber') || ($seriesprotocol eq '3HNumber')) { $expid = 113; }
			if (($seriesprotocol eq '1HPain') || ($seriesprotocol eq '2HPain') || ($seriesprotocol eq '3HPain')) { $expid = 113; }
			
			print F "$guid,$uid,$studydatetime,$ageatscan,$gender,$seriesprotocol,,,$expid,\"$seriesnotes\",,,,,$imagefile,,,,,,,,,\n";
		}
		close(F);
	}
}
