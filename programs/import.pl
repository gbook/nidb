#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB import.pl
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
# This program imports .tar.gz packages that are generated from the NIDB exporter
# 
# [3/21/2013] - Greg Book
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
use XML::Bare;
use File::Slurp;
use Switch;
use Sort::Naturally;

require 'nidbroutines.pl';
#my %config = do 'config.pl';
our %cfg;
LoadConfig();

# debugging
our $debug = 0;
our $dev = 0;
our $db;

# script specific information
our $scriptname = "import";
our $lockfileprefix = "import";		# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently

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
	my $x = DoImport();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoImport ---------------------------------------
# ----------------------------------------------------------
sub DoImport {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my %dicomfiles;
	my $ret = 0;
	
	# connect to the database
	#$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	DatabaseConnect();
	
	# check if this module should be running now or not
	my $sqlstring = "select * from modules where module_name = '$scriptname' and module_isactive = 1";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows < 1) {
		# update the stop time
		$sqlstring = "update modules set module_laststop = now(), module_status = 'stopped', module_numrunning = module_numrunning - 1 where module_name = '$scriptname'";
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		WriteLog("module is not active" . $sqlstring);
		return 0;
	}
	# update the start time
	$sqlstring = "update modules set module_laststart = now(), module_status = 'running', module_numrunning = module_numrunning + 1 where module_name = '$scriptname'";
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);

	WriteLog("Connected to database");
	
	# ----- get list of .tar.gz files in /import -----
	chdir($cfg{'packageimportdir'});
	#opendir(DIR,$cfg{'packageimportdir'}) || Error("Cannot open directory (1) $cfg{'packageimportdir'}!\n");
	my @files = <*.tar.gz>;
	#closedir(DIR);
	my $numfiles = $#files + 1;
	WriteLog("Found $numfiles import packages");
	#return 1;
	
	my $runningCount = 0;
	foreach my $file (@files) {
		$runningCount++;
		if ($runningCount%1000 == 0) {
			WriteLog("Processed $runningCount files...");
		}
		# again, make sure this file still exists... another instance of the program may have altered it
		if (-e "$cfg{'packageimportdir'}/$file") {
			my($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime,$blksize,$blocks) = stat("$cfg{'packageimportdir'}/$file");
			my $todaydate = time;
			print "now: $todaydate -- file:$mtime\n";
			
			chdir($cfg{'packageimportdir'});
			# if any file is less than 5 minutes old, the file may still be being transferred, so ignore it
			if ( ($todaydate - $mtime) < (300) ) {
				next;
			}
			else {
				WriteLog("Ready to import [$file]");
				# create tmp directory in which to unzip the files
				my $tmpdir = $cfg{'tmpdir'} . '/' . GenerateRandomString(20);
				if (mkdir($tmpdir)) {
					WriteLog("Create directory [$tmpdir]");
				}
				else {
					WriteLog("Directory [$tmpdir] not created errno: [$!]");
				}

				my $systemstring = "tar -xzf $file -C $tmpdir";
				WriteLog("$systemstring (" . `$systemstring` . ")");
				
				# start parsing the directory: /tmp/xxxxxxxxx/NIDB-XXXXXXXX/SubjectUID/StudyNum/SeriesNum
				WriteLog("Opening $tmpdir");
				opendir(DIR,$tmpdir);
				my @dirs = grep {! /^\.{1,2}$/} readdir(DIR);
				closedir(DIR);
				foreach my $packagedir(@dirs) {
					# export package level
					if (-d "$tmpdir/$packagedir") {
					
						# setup the site uuid to be associated with any subjects that are created
						# uuid will be set when the site.xml file is encountered and parsed
						my $siteuuid;
						my $subjectRowID;
						my $subjectNewUID;
						my $enrollmentRowID;
						my $studyRowID;
						my $studynum;
						my $modality;
						my $seriesRowID;
						
						# read the site.xml
						my $sitexml = "$tmpdir/$packagedir/site.xml";
						$siteuuid = InsertSite($sitexml);
						
						WriteLog("Opening packagedir level: $tmpdir/$packagedir");
						opendir(DIR2,"$tmpdir/$packagedir");
						my @subjects = grep {! /^\.{1,2}$/} readdir(DIR2);
						closedir(DIR2);
						foreach my $subjectOrigUID(@subjects) {
							
							if (-d "$tmpdir/$packagedir/$subjectOrigUID") {
								# should be a subject directory
								
								# read the subject.xml files
								my $subjectxml = "$tmpdir/$packagedir/$subjectOrigUID/subject.xml";
								my $enrollmentxml = "$tmpdir/$packagedir/$subjectOrigUID/enrollment.xml";
								($subjectRowID, $subjectNewUID, $enrollmentRowID) = InsertSubject($subjectxml, $enrollmentxml, $siteuuid);
								
								WriteLog("Opening subjectOrigUID level: $tmpdir/$packagedir/$subjectOrigUID");
								opendir(DIR3,"$tmpdir/$packagedir/$subjectOrigUID");
								my @studies = nsort( grep {! /^\.{1,2}$/} readdir(DIR3) );
								closedir(DIR3);
								foreach my $study(@studies) {
									if (-d "$tmpdir/$packagedir/$subjectOrigUID/$study") {
										# should be study directories
									
										# read the study.xml files
										my $studyxml = "$tmpdir/$packagedir/$subjectOrigUID/$study/study.xml";
										($studyRowID, $studynum, $modality) = InsertStudy($studyxml, $subjectRowID, $enrollmentRowID);
									
										WriteLog("Opening study level: $tmpdir/$packagedir/$subjectOrigUID/$study");
										opendir(DIR4,"$tmpdir/$packagedir/$subjectOrigUID/$study");
										my @seriess = nsort( grep {! /^\.{1,2}$/} readdir(DIR4) );
										closedir(DIR4);
										my $newseries = 1;
										foreach my $series(@seriess) {
											if (-d "$tmpdir/$packagedir/$subjectOrigUID/$study/$series") {
												# should be series level directories
												my $seriesxml = "$tmpdir/$packagedir/$subjectOrigUID/$study/$series/series.xml";
												my $qaxml = "$tmpdir/$packagedir/$subjectOrigUID/$study/$series/qa/qa.xml";
												my $indir = "$tmpdir/$packagedir/$subjectOrigUID/$study/$series";
												my $outdir = "$cfg{'archivedir'}/$subjectNewUID/$studynum/$newseries";
												switch ($modality) {
													case 'MR' {
															$seriesRowID = InsertMRSeries($seriesxml, $qaxml, $studyRowID, $newseries, $indir, $outdir);
															$newseries++;
														}
													case 'CT' {
															$seriesRowID = InsertCTSeries($seriesxml, $studyRowID, $newseries, $indir, $outdir);
															$newseries++;
														}
												}
												
												# once all of the study/series info is known, the entire contents of the subdirectory
												# of the series can be copied to the archive
												# mkpath(, {mode => 0777});
											}
										}
									}
								}
							}
						}
					}
				}
			
				# delete the tmp directory and all of its contents
				$systemstring = "rm -r $tmpdir";
				WriteLog("$systemstring (" . `$systemstring` . ")");
			}
		}
	}
	#WriteLog(Dumper(%dicomfiles));
	return 1;

	my $i;
	if ($i > 0) {
		WriteLog("Finished extracting data");
		$ret = 1;
	}
	else {
		WriteLog("Nothing to do");
	}
	
	# update the stop time
	#$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	DatabaseConnect();
	$sqlstring = "update modules set module_laststop = now(), module_status = 'stopped', module_numrunning = module_numrunning - 1 where module_name = '$scriptname'";
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	#print_r($result);
	WriteLog("normal stop: " . $sqlstring);
	
	return $ret;
}


# ----------------------------------------------------------
# --------- ParseSiteXML -----------------------------------
# ----------------------------------------------------------
sub ParseSiteXML {
	my ($f) = @_;
	
	my $xml = new XML::Bare( file => "$f" );
	my $xmlroot = $xml->parse();
	my $siteuuid = EscapeMySQLString(trim($xmlroot->{site_uuid}->{value}));
	my $sitename = EscapeMySQLString(trim($xmlroot->{site_name}->{value}));
	my $siteaddress = EscapeMySQLString(trim($xmlroot->{site_address}->{value}));
	my $sitecontact = EscapeMySQLString(trim($xmlroot->{site_contact}->{value}));
	WriteLog("Site: [$siteuuid] [$sitename] [$siteaddress] [$sitecontact]");
	
	return ($siteuuid,$sitename,$siteaddress,$sitecontact);
}


# ----------------------------------------------------------
# --------- ParseSubjectXML --------------------------------
# ----------------------------------------------------------
sub ParseSubjectXML {
	my ($f) = @_;
	
	my $xml = new XML::Bare( file => "$f" );
	my $xmlroot = $xml->parse();
	my $subjectuid = EscapeMySQLString(trim($xmlroot->{uid}->{value}));
	my $subjectuuid = EscapeMySQLString(trim($xmlroot->{uuid}->{value}));
	my $subjectweight = EscapeMySQLString(trim($xmlroot->{weight}->{value}));
	my $subjectheight = EscapeMySQLString(trim($xmlroot->{height}->{value}));
	my $subjectgender = EscapeMySQLString(trim($xmlroot->{gender}->{value}));
	my $subjectbirthdate = EscapeMySQLString(trim($xmlroot->{birthdate}->{value}));
	my $subjecthandedness = EscapeMySQLString(trim($xmlroot->{handedness}->{value}));
	my $subjecteducation = EscapeMySQLString(trim($xmlroot->{education}->{value}));
	my $subjectethnicity1 = EscapeMySQLString(trim($xmlroot->{ethnicity1}->{value}));
	my $subjectethnicity2 = EscapeMySQLString(trim($xmlroot->{ethnicity2}->{value}));
	WriteLog("Subject: $subjectuid,$subjectuuid,$subjectweight,$subjectheight,$subjectgender,$subjectbirthdate,$subjecthandedness,$subjecteducation,$subjectethnicity1,$subjectethnicity2");
	return ($subjectuid,$subjectuuid,$subjectweight,$subjectheight,$subjectgender,$subjectbirthdate,$subjecthandedness,$subjecteducation,$subjectethnicity1,$subjectethnicity2);
}


# ----------------------------------------------------------
# --------- ParseEnrollmentXML -----------------------------
# ----------------------------------------------------------
sub ParseEnrollmentXML {
	my ($f) = @_;
	
	my $xml = new XML::Bare( file => "$f" );
	my $xmlroot = $xml->parse();
	my $enrollsubgroup = EscapeMySQLString(trim($xmlroot->{enroll_subgroup}->{value}));
	WriteLog("Site: [$enrollsubgroup]");
	
	return ($enrollsubgroup);
}


# ----------------------------------------------------------
# --------- ParseStudyXML ----------------------------------
# ----------------------------------------------------------
sub ParseStudyXML {
	my ($f) = @_;
	
	#if (-e $f) {
	#	print "$f exists!";
	#}
	#else {
	#	print "$f does not exist!";
	#}
	my $xml = new XML::Bare( file => "$f" );
	my $xmlroot = $xml->parse();
	my $study_modality = EscapeMySQLString(trim($xmlroot->{study_modality}->{value}));
	my $study_bmi = EscapeMySQLString(trim($xmlroot->{study_bmi}->{value}));
	my $study_site = EscapeMySQLString(trim($xmlroot->{study_site}->{value}));
	my $study_datetime = EscapeMySQLString(trim($xmlroot->{study_datetime}->{value}));
	my $study_weight = EscapeMySQLString(trim($xmlroot->{study_weight}->{value}));
	my $study_performingphysician = EscapeMySQLString(trim($xmlroot->{study_performingphysician}->{value}));
	my $study_institution = EscapeMySQLString(trim($xmlroot->{study_institution}->{value}));
	my $study_height = EscapeMySQLString(trim($xmlroot->{study_height}->{value}));
	my $study_num = EscapeMySQLString(trim($xmlroot->{study_num}->{value}));
	my $study_notes = EscapeMySQLString(trim($xmlroot->{study_notes}->{value}));
	my $study_desc = EscapeMySQLString(trim($xmlroot->{study_desc}->{value}));
	my $study_radreadfindings = EscapeMySQLString(trim($xmlroot->{study_radreadfindings}->{value}));
	my $study_ageatscan = EscapeMySQLString(trim($xmlroot->{study_ageatscan}->{value}));
	my $study_alternateid = EscapeMySQLString(trim($xmlroot->{study_alternateid}->{value}));

	WriteLog("Study: $study_modality, $study_bmi, $study_site, $study_datetime, $study_weight, $study_performingphysician, $study_institution, $study_height, $study_num, $study_notes, $study_desc, $study_radreadfindings, $study_ageatscan, $study_alternateid");
	
	return ($study_modality, $study_bmi, $study_site, $study_datetime, $study_weight, $study_performingphysician, $study_institution, $study_height, $study_num, $study_notes, $study_desc, $study_radreadfindings, $study_ageatscan, $study_alternateid);
}


# ----------------------------------------------------------
# --------- ParseMRSeriesXML -------------------------------
# ----------------------------------------------------------
sub ParseMRSeriesXML {
	my ($f) = @_;
	
	my $xml = new XML::Bare( file => "$f" );
	my $xmlroot = $xml->parse();
	my $img_cols = EscapeMySQLString(trim($xmlroot->{img_cols}->{value}));
	my $series_spacingy = EscapeMySQLString(trim($xmlroot->{series_spacingy}->{value}));
	my $series_te = EscapeMySQLString(trim($xmlroot->{series_te}->{value}));
	my $series_spacingx = EscapeMySQLString(trim($xmlroot->{series_spacingx}->{value}));
	my $series_size = EscapeMySQLString(trim($xmlroot->{series_size}->{value}));
	my $numfiles = EscapeMySQLString(trim($xmlroot->{numfiles}->{value}));
	my $data_type = EscapeMySQLString(trim($xmlroot->{data_type}->{value}));
	my $is_derived = EscapeMySQLString(trim($xmlroot->{is_derived}->{value}));
	my $series_tr = EscapeMySQLString(trim($xmlroot->{series_tr}->{value}));
	my $bold_reps = EscapeMySQLString(trim($xmlroot->{bold_reps}->{value}));
	my $series_sequencename = EscapeMySQLString(trim($xmlroot->{series_sequencename}->{value}));
	my $series_desc = EscapeMySQLString(trim($xmlroot->{series_desc}->{value}));
	my $beh_size = EscapeMySQLString(trim($xmlroot->{beh_size}->{value}));
	my $img_rows = EscapeMySQLString(trim($xmlroot->{img_rows}->{value}));
	my $numfiles_beh = EscapeMySQLString(trim($xmlroot->{numfiles_beh}->{value}));
	my $series_notes = EscapeMySQLString(trim($xmlroot->{series_notes}->{value}));
	my $series_status = EscapeMySQLString(trim($xmlroot->{series_status}->{value}));
	my $series_protocol = EscapeMySQLString(trim($xmlroot->{series_protocol}->{value}));
	my $series_fieldstrength = EscapeMySQLString(trim($xmlroot->{series_fieldstrength}->{value}));
	my $img_slices = EscapeMySQLString(trim($xmlroot->{img_slices}->{value}));
	my $series_flip = EscapeMySQLString(trim($xmlroot->{series_flip}->{value}));
	my $series_spacingz = EscapeMySQLString(trim($xmlroot->{series_spacingz}->{value}));
	my $series_datetime = EscapeMySQLString(trim($xmlroot->{series_datetime}->{value}));

	WriteLog("MR Series: [$img_cols,$series_spacingy,$series_te,$series_spacingx,$series_size,$numfiles,$data_type,$is_derived,$series_tr,$bold_reps,$series_sequencename,$series_desc,$beh_size,$img_rows,$numfiles_beh,$series_notes,$series_status,$series_protocol,$series_fieldstrength,$img_slices,$series_flip,$series_spacingz,$series_datetime]");
	
	return ($img_cols,$series_spacingy,$series_te,$series_spacingx,$series_size,$numfiles,$data_type,$is_derived,$series_tr,$bold_reps,$series_sequencename,$series_desc,$beh_size,$img_rows,$numfiles_beh,$series_notes,$series_status,$series_protocol,$series_fieldstrength,$img_slices,$series_flip,$series_spacingz,$series_datetime);
}


# ----------------------------------------------------------
# --------- ParseMRSeriesQAXML -----------------------------
# ----------------------------------------------------------
sub ParseMRSeriesQAXML {
	my ($f) = @_;
	
	my $xml = new XML::Bare( file => "$f" );
	my $xmlroot = $xml->parse();
	my $acc_maxx = EscapeMySQLString(trim($xmlroot->{acc_maxx}->{value}));
	my $acc_maxy = EscapeMySQLString(trim($xmlroot->{acc_maxy}->{value}));
	my $acc_maxz = EscapeMySQLString(trim($xmlroot->{acc_maxz}->{value}));
	my $acc_minx = EscapeMySQLString(trim($xmlroot->{acc_minx}->{value}));
	my $acc_miny = EscapeMySQLString(trim($xmlroot->{acc_miny}->{value}));
	my $acc_minz = EscapeMySQLString(trim($xmlroot->{acc_minz}->{value}));
	my $io_snr = EscapeMySQLString(trim($xmlroot->{io_snr}->{value}));
	my $motion_rsq = EscapeMySQLString(trim($xmlroot->{motion_rsq}->{value}));
	my $move_maxx = EscapeMySQLString(trim($xmlroot->{move_maxx}->{value}));
	my $move_maxy = EscapeMySQLString(trim($xmlroot->{move_maxy}->{value}));
	my $move_maxz = EscapeMySQLString(trim($xmlroot->{move_maxz}->{value}));
	my $move_minx = EscapeMySQLString(trim($xmlroot->{move_minx}->{value}));
	my $move_miny = EscapeMySQLString(trim($xmlroot->{move_miny}->{value}));
	my $move_minz = EscapeMySQLString(trim($xmlroot->{move_minz}->{value}));
	my $pv_snr = EscapeMySQLString(trim($xmlroot->{pv_snr}->{value}));
	my $rot_maxp = EscapeMySQLString(trim($xmlroot->{rot_maxp}->{value}));
	my $rot_maxr = EscapeMySQLString(trim($xmlroot->{rot_maxr}->{value}));
	my $rot_maxy = EscapeMySQLString(trim($xmlroot->{rot_maxy}->{value}));
	my $rot_minp = EscapeMySQLString(trim($xmlroot->{rot_minp}->{value}));
	my $rot_minr = EscapeMySQLString(trim($xmlroot->{rot_minr}->{value}));
	my $rot_miny = EscapeMySQLString(trim($xmlroot->{rot_miny}->{value}));
	
	WriteLog("MR series QA: [$acc_maxx, $acc_maxy, $acc_maxz, $acc_minx, $acc_miny, $acc_minz, $io_snr, $motion_rsq, $move_maxx, $move_maxy, $move_maxz, $move_minx, $move_miny, $move_minz, $pv_snr, $rot_maxp, $rot_maxr, $rot_maxy, $rot_minp, $rot_minr, $rot_miny]");
	
	return ($acc_maxx, $acc_maxy, $acc_maxz, $acc_minx, $acc_miny, $acc_minz, $io_snr, $motion_rsq, $move_maxx, $move_maxy, $move_maxz, $move_minx, $move_miny, $move_minz, $pv_snr, $rot_maxp, $rot_maxr, $rot_maxy, $rot_minp, $rot_minr, $rot_miny);
}


# ----------------------------------------------------------
# --------- InsertStudy ------------------------------------
# ----------------------------------------------------------
sub InsertStudy {
	my ($f, $subjectRowID, $enrollmentRowID) = @_;
	
	my $studyRowID;
	my $newstudynum;

	my ($study_modality, $study_bmi, $study_site, $study_datetime, $study_weight, $study_performingphysician, $study_institution, $study_height, $study_num, $study_notes, $study_desc, $study_radreadfindings, $study_ageatscan, $study_alternateid) = ParseStudyXML($f);

	# get next study # for this subject
	my $sqlstring = "select (max(study_num) + 1) 'newstudynum' from studies where enrollment_id in (select enrollment_id from enrollment where subject_id = $subjectRowID)";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$newstudynum = $row{'newstudynum'};
	}

	# insert the study
	$sqlstring = "insert into studies (enrollment_id, study_num, study_desc, study_alternateid, study_modality, study_datetime, study_ageatscan, study_height, study_weight, study_bmi, study_performingphysician, study_site, study_institution, study_notes, study_radreadfindings, study_status, study_createdby) values ($enrollmentRowID, $newstudynum, '$study_desc', '$study_alternateid', '$study_modality', '$study_datetime', '$study_ageatscan', '$study_height', '$study_weight', '$study_bmi', '$study_performingphysician', '$study_site', '$study_institution', '$study_notes', '$study_radreadfindings', 'complete', 'import.pl')";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	$studyRowID = $result->insertid;
	
	return ($studyRowID, $newstudynum, $study_modality);
}


# ----------------------------------------------------------
# --------- InsertSubject ----------------------------------
# ----------------------------------------------------------
sub InsertSubject {
	my ($f1, $f2, $importsiteuuid) = @_;

	my $subjectNewUID = "";
	my $subjectRowID;
	my $enrollmentRowID;
	
	my ($subjectuid,$subjectuuid,$subjectweight,$subjectheight,$subjectgender,$subjectbirthdate,$subjecthandedness,$subjecteducation,$subjectethnicity1,$subjectethnicity2) = ParseSubjectXML($f1);
	my ($enrollsubgroup) = ParseEnrollmentXML($f2);
	$enrollsubgroup = EscapeMySQLString($enrollsubgroup);
	
	# set the isimported flag, and the site uuid before creating the subject,
	my $sqlstring = "select subject_id, uid from subjects where uuid = '$subjectuuid'";
	WriteLog("[$sqlstring]");
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		# subject already exists!
		WriteLog("Subject exists!!");
		my %row = $result->fetchhash;
		$subjectRowID = $row{'subject_id'};
		$subjectNewUID = $row{'uid'};
	}
	else {
		# create a new UID for the subject. Only the old UID will be retained as altuid1
		my $count = 0;
		
		# create a new subjectNewUID
		do {
			$subjectNewUID = CreateSubjectID();
			$sqlstring = "SELECT * FROM `subjects` WHERE uid = '$subjectNewUID'";
			WriteLog("[$sqlstring]");
			$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
			$count = $result->numrows;
		} while ($count > 0);
		
		WriteLog("New subject ID: $subjectNewUID");
		
		$sqlstring = "insert into subjects (name, birthdate, gender, ethnicity1, ethnicity2, height, weight, handedness, education, uid, uuid, altuid1, isimported, importedsiteuuid) values ('Imported^$subjectNewUID', '$subjectbirthdate','$subjectgender','$subjectethnicity1','$subjectethnicity2','$subjectheight','$subjectweight','$subjecthandedness','$subjecteducation','$subjectNewUID','$subjectuuid','$subjectuid',1,'$importsiteuuid')";
		WriteLog("[$sqlstring]");
		#my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		$subjectRowID = $result->insertid;
	}
	
	# create the enrollment as well, since all imported subjects will be enrolled in the same project
	$sqlstring = "select project_id from projects where project_costcenter = '100001'";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	my %row = $result->fetchhash;
	my $projectRowID = $row{'project_id'};
	
	$sqlstring = "select enrollment_id from enrollment where project_id = $projectRowID and subject_id = $subjectRowID";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$enrollmentRowID = $row{'enrollment_id'};
	}
	else {
		$sqlstring = "insert into enrollment (project_id, subject_id, enroll_subgroup, enroll_startdate, enroll_enddate) values ($projectRowID, $subjectRowID, '$enrollsubgroup', now(), now())";
		WriteLog("[$sqlstring]");
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		$enrollmentRowID = $result->insertid;
	}
	
	return ($subjectRowID, $subjectNewUID, $enrollmentRowID);
}


# ----------------------------------------------------------
# --------- InsertSite -------------------------------------
# ----------------------------------------------------------
sub InsertSite {
	my ($f) = @_;
	
	my ($siteuuid,$sitename,$siteaddress,$sitecontact) = ParseSiteXML($f);
	
	# create the site in nidb_sites table if it doesn't already exist
	my $sqlstring = "select * from nidb_sites where site_uuid = '$siteuuid'";
	WriteLog("[$sqlstring]");
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows < 1) {
		# insert the site info into the nidb_sites table
		$siteuuid = EscapeMySQLString($siteuuid);
		$sitename = EscapeMySQLString($sitename);
		$siteaddress = EscapeMySQLString($siteaddress);
		$sitecontact = EscapeMySQLString($sitecontact);
		$sqlstring = "insert into nidb_sites (site_uuid, site_name, site_address, site_contact) values ('$siteuuid','$sitename','$siteaddress','$sitecontact')";
		my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	}

	return $siteuuid;
}


# ----------------------------------------------------------
# --------- InsertMRSeries ---------------------------------
# ----------------------------------------------------------
sub InsertMRSeries {
	my ($f1, $f2, $studyid, $newseriesnum, $indir, $outdir) = @_;
	
	my ($img_cols,$series_spacingy,$series_te,$series_spacingx,$series_size,$numfiles,$data_type,$is_derived,$series_tr,$bold_reps,$series_sequencename,$series_desc,$beh_size,$img_rows,$numfiles_beh,$series_notes,$series_status,$series_protocol,$series_fieldstrength,$img_slices,$series_flip,$series_spacingz,$series_datetime) = ParseMRSeriesXML($f1);
	
	my $sqlstring = "insert into mr_series (study_id, series_num, img_cols, series_spacingy, series_te, series_spacingx, series_size, numfiles, data_type, is_derived, series_tr, bold_reps, series_sequencename, series_desc, beh_size, img_rows, numfiles_beh, series_notes, series_status, series_protocol, series_fieldstrength, img_slices, series_flip, series_spacingz, series_datetime) values ($studyid, $newseriesnum, '$img_cols', '$series_spacingy', '$series_te', '$series_spacingx', '$series_size', '$numfiles', '$data_type', '$is_derived', '$series_tr', '$bold_reps', '$series_sequencename', '$series_desc', '$beh_size', '$img_rows', '$numfiles_beh', '$series_notes', '$series_status', '$series_protocol', '$series_fieldstrength', '$img_slices', '$series_flip', '$series_spacingz', '$series_datetime')";
	WriteLog("[$sqlstring]");
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	my $seriesRowID = $result->insertid;
	
	# create the output path
	mkpath($outdir, {mode => 0777});
	
	# copy all of the files to the output path
	my $systemstring = "cp -R $indir/* $outdir";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	
	# if there is a qa.xml file, load that into the database
	if (-e $f2) {
		my ($acc_maxx, $acc_maxy, $acc_maxz, $acc_minx, $acc_miny, $acc_minz, $io_snr, $motion_rsq, $move_maxx, $move_maxy, $move_maxz, $move_minx, $move_miny, $move_minz, $pv_snr, $rot_maxp, $rot_maxr, $rot_maxy, $rot_minp, $rot_minr, $rot_miny) = ParseMRSeriesQAXML($f2);
		$sqlstring = "insert into mr_qa (mrseries_id, acc_maxx, acc_maxy, acc_maxz, acc_minx, acc_miny, acc_minz, io_snr, motion_rsq, move_maxx, move_maxy, move_maxz, move_minx, move_miny, move_minz, pv_snr, rot_maxp, rot_maxr, rot_maxy, rot_minp, rot_minr, rot_miny) values ($seriesRowID, '$acc_maxx', '$acc_maxy', '$acc_maxz', '$acc_minx', '$acc_miny', '$acc_minz', '$io_snr', '$motion_rsq', '$move_maxx', '$move_maxy', '$move_maxz', '$move_minx', '$move_miny', '$move_minz', '$pv_snr', '$rot_maxp', '$rot_maxr', '$rot_maxy', '$rot_minp', '$rot_minr', '$rot_miny')";
		my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	}
	
	return $seriesRowID;
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
