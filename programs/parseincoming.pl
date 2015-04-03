#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB parseincoming.pl
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
# moves the non-dicom files to their archive location
# 
# [4/27/2011] - Greg Book
#		* Wrote initial program.
# [3/24/2014] - Greg Book
#       * added other non-dicom parsing formats
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

require 'nidbroutines.pl';
our %cfg;
LoadConfig();

# database variables
our $db;

# script specific information
our $scriptname = "parseincoming";
our $lockfileprefix = "parseincoming";	# lock files will be numbered lock.1, lock.2 ...
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
	my $x = DoParse();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoParse ----------------------------------------
# ----------------------------------------------------------
sub DoParse {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my %dicomfiles;
	my $ret = 0;
	my $i = 0;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	WriteLog("Connected to database");
	
	# check if this module should be running now or not
	my $sqlstring = "select * from modules where module_name = '$scriptname' and module_isactive = 1";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows < 1) {
		return 0;
	}
	# update the start time
	$sqlstring = "update modules set module_laststart = now(), module_status = 'running' where module_name = '$scriptname'";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);

	# ----- parse all files in /incoming -----
	opendir(DIR,$incoming2dir) || Error("Cannot open directory (1) $incoming2dir!\n");
	my @files = readdir(DIR);
	closedir(DIR);
	foreach my $file (@files) {
		if ( ($file ne ".") && ($file ne "..") ) {
			# again, make sure this file still exists... another instance of the program may have altered it
			if (-e "$incoming2dir/$file") {
				my($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime,$blksize,$blocks) = stat("$incoming2dir/$file");
				my $todaydate = time;
				#print "now: $todaydate -- file:$mtime\n";
				
				if (-d "$incoming2dir/$file") { }
				else {
					chdir($incoming2dir);
					# if the file is less than 5 minutes old, it may still be being transferred, so ignore it
					if ( ($todaydate - $mtime) > 300 ) {
					
						if ($file =~ /\.par$/) {
							InsertParRec($file);
						}
						else {
							InsertSeries($file);
						}
						$i++;
					}
					else {
						print "$file too new\n";
					}
				}
			}
		}
	}
	WriteLog(Dumper(%dicomfiles));
	
	if ($i > 0) {
		WriteLog("Finished extracting data");
		$ret = 1;
	}
	else {
		WriteLog("Nothing to do");
	}
	
	# update the stop time
	$sqlstring = "update modules set module_laststop = now(), module_status = 'stopped' where module_name = '$scriptname'";
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	
	return $ret;
}


# ----------------------------------------------------------
# --------- InsertParRec -----------------------------------
# ----------------------------------------------------------
sub InsertParRec {
	my ($file) = @_;
	
	print "$file\n";
	#exit(0);
	
	my $parfile = $file;
	my $recfile = $file;
	$recfile =~ s/\.par/\.rec/;
	my $sqlstring;
	my $result;
	my %row;
	
	my $PatientName;
	my $PatientBirthDate = "1776-07-04";
	my $PatientID = "NotSpecified";
	my $PatientSex = "U";
	my $PatientWeight = "0";
	#my $costcenter = "999999";
	my $StudyDescription;
	my $StationName = "PAR/REC";
	my $OperatorsName = "NotSpecified";
	my $PerformingPhysiciansName = "NotSpecified";
	my $InstitutionName = "NotSpecified";
	my $InstitutionAddress = "NotSpecified";
	#my $studydatetime;
	my $AccessionNumber = "";
	my $SequenceName;
	my $MagneticFieldStrength = "0";
	my $ProtocolName;
	my $StudyDateTime;
	my $SeriesDateTime;
	my $Modality;
	my $SeriesNumber;
	my $zsize;
	my $boldreps;
	my $numfiles = 2; # should always be 2 for .par/.rec
	my ($resolutionX, $resolutionY);
	my $seriessequencename;
	my $RepetitionTime;
	my ($Columns, $Rows);
	#$my ($PixelX, $PixelY);
	my ($pixelX, $pixelY, $SliceThickness, $xspacing, $yspacing, $EchoTime, $FlipAngle);
	
	# read the .par file into an array, get all the useful info out of it
	open (FH, "< $file") or die "Can't open $file for read: $!";
	my @lines = <FH>;
	close FH or die "Cannot close $file: $!";

	print "-----$file-----\n";
	
	foreach my $line (@lines) {
		$line = trim($line);
		
		#print "$line\n";
		if ($line =~ m/Patient name/) {
			my @parts = split(/:/, $line);
			$PatientName = trim($parts[1]);
			print "$PatientName\n";
		}
		if ($line =~ m/Examination name/) {
			my @parts = split(/:/, $line);
			$StudyDescription = trim($parts[1]);
			print "$StudyDescription\n";
		}
		if ($line =~ m/Protocol name/) {
			my @parts = split(/:/, $line);
			$ProtocolName = trim($parts[1]);
			print "$ProtocolName\n";
		}
		if ($line =~ m/Examination date\/time/) {
			my $datetime = $line;
			$datetime =~ s/\.\s+Examination date\/time\s+://;
			my @parts = split(/\//, $datetime);
			my $date = trim($parts[0]);
			my $time = trim($parts[1]);
			$date =~ s/\./\-/g;
			$StudyDateTime = "$date $time";
			$SeriesDateTime = "$date $time";
			print "$date $time\n";
		}
		if ($line =~ m/Series Type/) {
			my @parts = split(/:/, $line);
			$Modality = trim($parts[1]);
			$Modality =~ s/Image//g;
			$Modality =~ s/SERIES//g;
			$Modality = trim($Modality);
			print "$Modality\n";
		}
		if ($line =~ m/Acquisition nr/) {
			my @parts = split(/:/, $line);
			$SeriesNumber = trim($parts[1]);
			print "$SeriesNumber\n";
		}
		if ($line =~ m/Max. number of slices\/locations/) {
			my @parts = split(/:/, $line);
			$zsize = trim($parts[1]);
			print "$zsize\n";
		}
		if ($line =~ m/Max. number of dynamics/) {
			my @parts = split(/:/, $line);
			$boldreps = trim($parts[1]);
			print "$boldreps\n";
		}
		if ($line =~ m/Technique/) {
			my @parts = split(/:/, $line);
			$SequenceName = trim($parts[1]);
			print "$SequenceName\n";
		}
		if ($line =~ m/Scan resolution/) {
			my @parts = split(/:/, $line);
			my $resolution = trim($parts[1]);
			my @parts2 = split(/\s+/, $resolution);
			$Columns = $parts2[0];
			$Rows = $parts2[1];
			print "$Columns,$Rows\n";
		}
		if ($line =~ m/Repetition time/) {
			my @parts = split(/:/, $line);
			$RepetitionTime = trim($parts[1]);
			print "$RepetitionTime\n";
		}
		# get the first line of the image list... should contain flip angle
		if (($line !~ m/^\./) && ($line !~ m/^#/) && (trim($line) ne "")) {
			#print "[$line]\n";
			my @parts = split(/\s+/,$line);
			# 10 - xsize
			$pixelX = trim($parts[9]);
			# 11 - ysize
			$pixelY = trim($parts[10]);
			# 23 - slice thickness
			$SliceThickness = trim($parts[22]);
			# 29 - xspacing
			$pixelX = trim($parts[28]);
			# 30 - yspacing
			$pixelY = trim($parts[29]);
			# 31 - TE
			$EchoTime = trim($parts[30]);
			# 36 - flip
			$FlipAngle = trim($parts[35]);
			print "$pixelX, $pixelY, $SliceThickness, $xspacing, $yspacing, $EchoTime, $FlipAngle\n";
			last;
		}
	}		

	# ----- check if this subject/study/series/etc exists -----
	my $projectRowID;
	my $subjectRealUID = $PatientName;
	my $subjectRowID;
	#my $sqlstring;
	#my $subjectRowID;
	#my $subjectRealUID;
	#my $projectRowID;
	my $enrollmentRowID;
	my $studyRowID;
	my $seriesRowID;
	my $costcenter;
	my $study_num;
	
	# extract the costcenter
	if ( $StudyDescription =~ /clinical/i ) {
		$costcenter = "888888";
	}
	elsif ( $StudyDescription =~ /\((.*?)\)/ )
	{
		$costcenter = $1;
	}
	else {
		$costcenter = $StudyDescription;
	}
	
	WriteLog("$PatientID - $StudyDescription");
	
	# check if project and subject exist
	$sqlstring = "select (SELECT count(*) FROM `projects` WHERE project_costcenter = '$costcenter') 'projectcount', (SELECT count(*) FROM `subjects` WHERE uid = '$PatientID') 'subjectcount'";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	%row = $result->fetchhash;
	my $projectcount = $row{'projectcount'};
	my $subjectcount = $row{'subjectcount'};
	
	# if subject doesn't exist, create the subject
	if ($subjectcount < 1) {

		# search for an existing subject by name, dob, gender
		$sqlstring = "select subject_id, uid from subjects where name like '%$PatientName%' and gender = '$PatientSex' and birthdate = '$PatientBirthDate'";
		my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$subjectRealUID = uc($row{'uid'});
			$subjectRowID = $row{'subject_id'};
		}
		# if it couldn't be found, create a new subject
		else {
			my $count = 0;
			$subjectRealUID = "";
			
			# create a new subjectRealUID
			do {
				$subjectRealUID = CreateSubjectID();
				$sqlstring = "SELECT * FROM `subjects` WHERE uid = '$subjectRealUID'";
				$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
				$count = $result->numrows;
			} while ($count > 0);
			
			WriteLog("New subject ID: $subjectRealUID");
			$sqlstring = "insert into subjects (name, birthdate, gender, weight, uid, uuid) values ('$PatientName', '$PatientBirthDate', '$PatientSex', '$PatientWeight', '$subjectRealUID', ucase(md5(concat(RemoveNonAlphaNumericChars('$PatientName'), RemoveNonAlphaNumericChars('$PatientBirthDate'),RemoveNonAlphaNumericChars('$PatientSex')))) )";
			$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
			$subjectRowID = $result->insertid;
		}
	}
	else {
		# get the existing subject ID
		$sqlstring = "select subject_id from subjects where uid = '$PatientID'";
		my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		my %row = $result->fetchhash;
		$subjectRowID = $row{'subject_id'};
		$subjectRealUID = uc($PatientID);
	}
	
	# if project doesn't exist, use the generic project
	if ($projectcount < 1) {
		$costcenter = "999999";
	}
	
	# get the projectRowID
	$sqlstring = "select project_id from projects where project_costcenter = '$costcenter'";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "] " . $db->errmsg(),$sqlstring);
	%row = $result->fetchhash;
	$projectRowID = $row{'project_id'};
	
	# check if the subject is enrolled in the project
	$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectRowID and project_id = $projectRowID";
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		WriteLog("[$sqlstring]");
		my %row = $result->fetchhash;
		$enrollmentRowID = $row{'enrollment_id'};
	}
	else {
		# create enrollmentRowID if it doesn't exist
		$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values ($projectRowID, $subjectRowID, now())";
		WriteLog("[$sqlstring]");
		my $result2 = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		$enrollmentRowID = $result2->insertid;
	}
	
	# now determine if this study exists or not...
	# basically check for a unique studydatetime, modality, and site (StationName), because we already know this subject/project/etc is unique
	# also checks the accession number against the study_num to see if this study was pre-registered
	$sqlstring = "select study_id, study_num from studies where enrollment_id = $enrollmentRowID and (study_num = '$AccessionNumber' or (study_datetime = '$StudyDateTime' and study_modality = '$Modality' and study_site = '$StationName'))";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$studyRowID = $row{'study_id'};
		$study_num = $row{'study_num'};
		
		$sqlstring = "update studies set study_modality = '$Modality', study_datetime = '$StudyDateTime', study_desc = '$StudyDescription', study_operator = '$OperatorsName', study_performingphysician = '$PerformingPhysiciansName', study_site = '$StationName', study_institution = '$InstitutionName - $InstitutionAddress', study_status = 'complete' where study_id = $studyRowID";
		WriteLog("[$sqlstring]");
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		
	}
	else {
		# create studyRowID if it doesn't exist
		$sqlstring = "SELECT max(a.study_num) 'study_num' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id  WHERE b.subject_id = $subjectRowID";
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		%row = $result->fetchhash;
		$study_num = $row{'study_num'} + 1;
		#$study_num = $result->numrows + 1;
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status, study_createdby) values ($enrollmentRowID, $study_num, '$PatientID', '$Modality', '$StudyDateTime', '$StudyDescription', '$OperatorsName', '$PerformingPhysiciansName', '$StationName', '$InstitutionName - $InstitutionAddress', 'complete', 'parseincoming.pl')";
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		$studyRowID = $result->insertid;
	}
	
	# ----- insert or update the series -----
	$sqlstring = "select mrseries_id from mr_series where study_id = $studyRowID and series_num = $SeriesNumber";
	WriteLog("[$sqlstring]");
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$seriesRowID = $row{'mrseries_id'};
		
		$sqlstring = "update mr_series set series_datetime = '$SeriesDateTime',series_desc = '$ProtocolName', series_sequencename = '$SequenceName',series_tr = '$RepetitionTime', series_te = '$EchoTime',series_flip = '$FlipAngle', series_spacingx = '$pixelX',series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', series_fieldstrength = '$MagneticFieldStrength', img_rows = '$Rows', img_cols = '$Columns', img_slices = '$zsize', bold_reps = '$boldreps', numfiles = '$numfiles', series_status = 'complete' where mrseries_id = $seriesRowID";
		$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	}
	else {
		
		# create seriesRowID if it doesn't exist
		$sqlstring = "insert into mr_series (study_id, series_datetime, series_desc, series_sequencename, series_num, series_tr, series_te, series_flip, series_spacingx, series_spacingy, series_spacingz, series_fieldstrength, img_rows, img_cols, img_slices, bold_reps, numfiles, data_type, series_status, series_createdby) values ($studyRowID, '$SeriesDateTime', '$ProtocolName', '$SequenceName', '$SeriesNumber', '$RepetitionTime', '$EchoTime', '$FlipAngle', '$pixelX', '$pixelY', '$SliceThickness', '$MagneticFieldStrength', '$Rows', '$Columns', '$zsize', $boldreps, '$numfiles', 'parrec', 'complete', 'parsedicom.pl')";
		#print "[$sqlstring]\n";
		my $result2 = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
		$seriesRowID = $result2->insertid;
	}
	
	# copy the file to the archive, update db info
	WriteLog("$seriesRowID");
	
	# create data directory if it doesn't already exist
	my $outdir = "$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/parrec";
	WriteLog("$outdir");
	mkpath($outdir, {mode => 0777});
	
	# move the files into the outdir
	move("$incoming2dir/$parfile","$outdir/$parfile");
	move("$incoming2dir/$recfile","$outdir/$recfile");
	
	# rename the files and move them to the archive
	# SubjectUID_EnrollmentRowID_SeriesNum_FileNum
	# S1234ABC_SP1_5_0001.dcm
	
	# check if there are .dcm files already in the archive
	# my $cwd = getcwd;
	# chdir($outdir);
	# my @existingdcmfiles = <*.dcm>;
	# chdir($cwd);
	# @existingdcmfiles = sort @existingdcmfiles;
	# my $numexistingdcmfiles = @existingdcmfiles;
	# if ($numexistingdcmfiles > 0) {
	
		# # check all files to see if its the same study datetime, patient name, dob, gender, series #
		# # if anything is different, move the file to a UID/Study/Series/dicom/existing directory
		
		# # if they're all the same, consolidate the files into one list of new and old, remove duplicates
		# WriteLog("There are $numexistingdcmfiles existing files in $outdir. Renaming accordingly");
		
		# # rename the existing files to make them unique
		# foreach my $file (sort @existingdcmfiles) {
			# my $tags2 = $exifTool->ImageInfo("$outdir/$file");
			# my $SliceNumber = trim($tags2->{'AcquisitionNumber'});
			# my $InstanceNumber = trim($tags2->{'InstanceNumber'});
			# my $SliceLocation = trim($tags2->{'SliceLocation'});
			# my $AcquisitionTime = trim($tags2->{'AcquisitionTime'});
			# $AcquisitionTime =~ s/://g;
			# $AcquisitionTime =~ s/\.//g;
			
			# # sort by slice #, or instance #
			# WriteLog("$file: SliceNumber: $SliceNumber, InstanceNumber: $InstanceNumber, SliceLocation: $SliceLocation, Acquisition Time: $AcquisitionTime");
			
			# my $newname = $subjectRealUID . "_$study_num" . "_$SeriesNumber" . "_" . sprintf('%05d',$SliceNumber) . "_" . sprintf('%05d',$InstanceNumber) . "_$AcquisitionTime.dcm";
			# WriteLog("Renaming [$outdir/$file] to [$outdir/$newname]");
			
			# move("$outdir/$file","$outdir/$newname");
		# }
		
	# }
	
	# # renumber to make unique
	# foreach my $file (sort @files) {
		# my $tags = $exifTool->ImageInfo($file);
		# my $SliceNumber = trim($tags->{'AcquisitionNumber'});
		# my $InstanceNumber = trim($tags->{'InstanceNumber'});
		# my $SliceLocation = trim($tags->{'SliceLocation'});
		# my $AcquisitionTime = trim($tags->{'AcquisitionTime'});
		# $AcquisitionTime =~ s/://g;
		# $AcquisitionTime =~ s/\.//g;
		
		# # sort by slice #, or instance #
		# WriteLog("$file: SliceNumber: $SliceNumber, InstanceNumber: $InstanceNumber, SliceLocation: $SliceLocation, Acquisition Time: $AcquisitionTime");
		
		# my $newname = $subjectRealUID . "_$study_num" . "_$SeriesNumber" . "_" . sprintf('%05d',$SliceNumber) . "_" . sprintf('%05d',$InstanceNumber) . "_$AcquisitionTime.dcm";
		# #print "  [$newname]";

		# # check if a file with the same name already exists
		# if (-e "$outdir/$newname") {
			# WriteLog("File [$outdir/$newname] already exists, moving this one to the duplicates directory");
			# # if so, rename it randomly and dump it to a duplicates directory
			# unless (-d "$outdir/duplicates") {
				# mkdir "$outdir/duplicates";
			# }
			# move($file, "$outdir/duplicates/" . GenerateRandomString(20) . "$newname");
		# }
		# else {
			# move($file,"$outdir/$newname");
		# }
	# }
	
	# get the size of the files and update the DB
	my $dirsize;
	($dirsize, $numfiles) = GetDirectorySize($outdir);
	$sqlstring = "update mr_series set series_size = $dirsize where mrseries_id = $seriesRowID";
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);

	# create a thumbnail of the middle slice in the dicom directory (after getting the size, so the thumbnail isn't included in the size)
	#CreateThumbnail("$cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber");

	# change the permissions to 777 so the webpage can read/write the directories
	#my $origDir = getcwd;
	#chdir("$cfg{'archivedir'}");
	#WriteLog("Current directory: " . getcwd);
	my $systemstring = "chmod -Rf 777 $cfg{'archivedir'}/$subjectRealUID";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	# change back to original directory before leaving
	#WriteLog("Changing back to $origDir");
	#chdir($origDir);
	WriteLog("Finished changing permissions on $cfg{'archivedir'}/$subjectRealUID");
	
	# copy everything to the backup directory
	my $backdir = "$cfg{'backupdir'}/$subjectRealUID/$study_num/$SeriesNumber";
	if (-d $backdir) {
		WriteLog("Directory [$backdir] already exists");
	}
	else {
		WriteLog("Directory [$backdir] does not exist. About to create it...");
		mkpath($backdir, { verbose => 1, mode => 0777} );
		WriteLog("Finished creating [$backdir]");
	}
	WriteLog("About to copy to the backup directory");
	$systemstring = "cp -R $cfg{'archivedir'}/$subjectRealUID/$study_num/$SeriesNumber/* $backdir";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	WriteLog("Finished copying to the backup directory");
}


# ----------------------------------------------------------
# --------- InsertSeries -----------------------------------
# ----------------------------------------------------------
sub InsertSeries {
	my ($file) = @_;
	
	my $sqlstring;
	my $result;
	my %row;
	
	my $subjectRowID;
	my $subjectRealUID;
	my $projectRowID;
	my $enrollmentRowID;
	my $studyRowID;
	my $seriesRowID;
	my $costcenter;
	my $studyNum;
	my $SeriesNumber;
	my $StudyDateTime;
	my $SeriesDateTime;
	my $Modality;
	
	$studyRowID = "";
	$seriesRowID = "";
	
	WriteLog("Parsing $file");
	
	# check if it has the format S###AAA_######_#_#
	# UID, project, studyNum, seriesNum
	if ($file =~ /S[0-9]{4}[A-Za-z]{3}_[0-9]{6}_[0-9]*_[0-9]*/) {
		# if filenames contain all this information, then we can create the study if it doesn't already exist
		# chances are if the filename is in this format, it corresponds to a valid project/subject, but we will not create a subject
		
		($subjectRealUID, $costcenter, $studyNum, $SeriesNumber) = split(/_/, $file);
		
		# check if the UID and project actually exist
		$sqlstring = "SELECT * FROM `projects` WHERE project_costcenter = '$costcenter'";
		my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$projectRowID = $row{'project_id'};
			WriteLog("Found project ID: $projectRowID");
		}
		else {
			WriteLog("Found no project ID");
			return;
		}
		
		$sqlstring = "SELECT * FROM `subjects` WHERE uid = '$subjectRealUID'";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			my %row = $result->fetchhash;
			$subjectRowID = $row{'subject_id'};
			WriteLog("Found subject ID: $subjectRowID");
		}
		else {
			WriteLog("Found no subject ID");
			return;
		}
		
		# if the UID is not enrolled in the project, ignore the file
		$sqlstring = "select enrollment_id from enrollment where subject_id = $subjectRowID and project_id = $projectRowID";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			WriteLog("[$sqlstring]");
			my %row = $result->fetchhash;
			$enrollmentRowID = $row{'enrollment_id'};
			WriteLog("Found enrollment ID: $enrollmentRowID");
		}
		else {
			WriteLog("Found no enrollment ID");
			return;
		}
		
		# if the study doesn't exist, create it
		$sqlstring = "select * from studies where enrollment_id = $enrollmentRowID and study_num = $studyNum";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			WriteLog("[$sqlstring]");
			my %row = $result->fetchhash;
			$studyRowID = $row{'study_id'};
			WriteLog("Found study ID: $studyRowID");
		}
		else {
			WriteLog("Found no study ID");
			return;
		}
		
		# if the series doesn't exist, create it
		$sqlstring = "select binaryseries_id from series where study_id = $studyRowID and series_num = $SeriesNumber";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		if ($result->numrows > 0) {
			WriteLog("[$sqlstring]");
			my %row = $result->fetchhash;
			$seriesRowID = $row{'series_id'};
			WriteLog("Found series ID: $seriesRowID");
		}
		else {
			WriteLog("Found no series ID");
			$seriesRowID = "";
		}

		# set study datetime, series datetime
		my $filepath = cetcwd() . "/$file";
		$SeriesDateTime = $StudyDateTime = CreateMySQLDateFromFile($filepath);
		
	}
	else {
		# these files will only be inserted if a study already exists... no need to clutter up the DB if someone dumps tons of random files into /incoming
		# check if it contains a date, at least 14 digits on the left YYYYMMDDHHMISS
		if ($file =~ /^\d{12,}/) {
			# look up the study date in the DB
			$SeriesDateTime = substr($file,0,4) . "-" . substr($file,4,2) . "-" . substr($file,6,2) . " " . substr($file,8,2) . ":" . substr($file,10,2) . ":" . substr($file,12,2);
			$sqlstring = "select a.study_id, a.study_num, b.project_id, b.subject_id, b.enrollment_id, c.uid from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on c.subject_id = b.subject_id where a.study_datetime between (adddate('$SeriesDateTime', interval -4 minute)) and (adddate('$SeriesDateTime', interval 4 minute))";
			my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
			if ($result->numrows == 1) {
				WriteLog("Found a study date matching the seriesdate: $SeriesDateTime");
				my %row = $result->fetchhash;
				$subjectRowID = $row{'subject_id'};
				$studyRowID = $row{'study_id'};
				$studyNum = $row{'study_num'};
				$projectRowID = $row{'project_id'};
				$enrollmentRowID = $row{'enrollment_id'};
				$subjectRealUID = $row{'uid'};
				$Modality = "MR";
				$SeriesNumber = "0";
				
				WriteLog("StudyRowID: $studyRowID, SubjectRowID: $subjectRowID, ProjectRowID: $projectRowID, EnrollmentRowID: $enrollmentRowID");
				
				my $sqlstringA = "select mrseries_id from mr_series where study_id = $studyRowID and series_num = '0'";
				WriteLog("SQL: $sqlstringA");
				my $resultA = $db->query($sqlstringA) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
				if ($resultA->numrows > 0) {
					my %rowA = $resultA->fetchhash;
					$seriesRowID = $rowA{'mrseries_id'};
				}
				else {
					$seriesRowID = "";
				}
			}
			else {
				# can't really determine where this study goes
				return;
			}
		}
		else {
			return;
		}
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
		$sqlstring = "insert into enrollment (project_id, subject_id, enrollment_date) values ($projectRowID, $subjectRowID, now())";
		WriteLog("[$sqlstring]");
		my $result2 = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$enrollmentRowID = $result2->insertid;
	}
	
	# create studyRowID if it doesn't exist
	if ($studyRowID eq "") {
		$sqlstring = "SELECT * FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id WHERE b.subject_id = $subjectRowID";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$studyNum = $result->numrows + 1;
		
		$sqlstring = "insert into studies (enrollment_id, study_num, study_alternateid, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_institution, study_status) values ($enrollmentRowID, $studyNum, '$subjectRealUID', '$Modality', '$StudyDateTime', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'complete')";
		$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$studyRowID = $result->insertid;
	}
	
	# insert or update the series
	if ($seriesRowID eq "") {
		# create seriesRowID if it doesn't exist
		if ($Modality eq "MR") {
			$sqlstring = "insert into mr_series (study_id, series_datetime, series_desc, series_sequencename, series_num, data_type, series_status) values ($studyRowID, '$SeriesDateTime', 'Real & Imaginary data', 'raw save', '$SeriesNumber', 'raw', 'complete')";
		}
		else {
			# put it in the binary_series table
			$sqlstring = "insert into binary_series (study_id, series_datetime, series_num) values ($studyRowID, '$SeriesDateTime', $SeriesNumber)";
		}
		#print "[$sqlstring]\n";
		my $result2 = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
		$seriesRowID = $result2->insertid;
	}
	
	# copy the file to the archive, update db info
	WriteLog("$seriesRowID");
	
	# create data directory if it doesn't already exist
	my $outdir;
	if ($Modality eq "MR") {
		$outdir = "$cfg{'archivedir'}/$subjectRealUID/$studyNum/$SeriesNumber/raw";
	}
	else {
		$outdir = "$cfg{'archivedir'}/$subjectRealUID/$studyNum/$SeriesNumber/binary";
	}
	WriteLog("Creating path $outdir");
	mkpath($outdir, {mode => 0777});
	
	WriteLog("move($file,$outdir/$file)");
	move($file,"$outdir/$file");
	
	# get the size and number of the files and update the DB
	my ($dirsize, $numfiles) = GetDirectorySize($outdir);
	if ($Modality eq "MR") {
		$sqlstring = "update mr_series set series_size = $dirsize where mrseries_id = $seriesRowID";
	}
	else {
		$sqlstring = "update binary_series set series_size = $dirsize, series_numfiles = $numfiles where binaryseries_id = $seriesRowID";
	}
	$result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);

	# change the permissions to 777 so the webpage can read/write the directories
	my $origDir = getcwd;
	chdir("$cfg{'archivedir'}");
	my $systemstring = "chmod -Rf 777 $subjectRealUID";
	WriteLog("$systemstring (" . `$systemstring` . ")");
	# change back to original directory before leaving
	chdir($origDir);
	
	# copy everything to the backup directory
	my $backdir = "$cfg{'backupdir'}/$subjectRealUID/$studyNum/$SeriesNumber";
	mkpath($backdir, {mode => 0777});
	$systemstring = "cp -R $cfg{'archivedir'}/$subjectRealUID/$studyNum/$SeriesNumber/* $backdir";
	WriteLog("$systemstring (" . `$systemstring` . ")");
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
