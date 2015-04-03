#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB usage.pl
# Copyright (C) 2004 - 2015
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
	
	# ********** 1) check if entries in the database exist in the filesystem **********
	# get new audit number
	my $sqlstring = "select max(audit_num) 'newauditnum' from audit_results";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
	my %row = $result->fetchhash;
	my $auditnum = $row{'newauditnum'} + 1;
	
	$sqlstring = "select * from subjects order by uid";
	$result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
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
		my $result1 = $db->query($sqlstring1) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring1);
		while (my %row1 = $result1->fetchhash) {
			push @altuids, $row1{'altuid'};
		}

		print "\nChecking $uid [$ii of $numSubjects]\n";
		
		# check if the UID directory exists in the filesystem
		my $subjectdir = $cfg{'archivedir'} . '/' . $uid;
		
		if (-d $subjectdir) {
			# get list of studies for this subject
			my $sqlstringA = "select * from enrollment where subject_id = $SubjectID";
			my $resultA = $db->query($sqlstringA) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringA);
			while (my %rowA = $resultA->fetchhash) {
				my $EnrollmentID = $rowA{'enrollment_id'};
				my $ProjectID = $rowA{'project_id'};
				
				# check if the project ID is valid
				my $sqlstringB = "select count(*) 'count' from projects where project_id = '$ProjectID'";
				my $resultB = $db->query($sqlstringB) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringB);
				my %rowB = $resultB->fetchhash;
				if ($rowB{'count'} < 1) {
					print "ProjectID [$ProjectID] does not exist\n";
					my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, enrollment_id, project_id, subject_uid, audit_date) values ('$auditnum', 'dbtofile','invalidprojectid', '$SubjectID', '$EnrollmentID', '$ProjectID','$uid', now())";
					my $resultC = $db->query($sqlstringC) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringC);
				}
				
				# get list of studies for this enrollment
				$sqlstringB = "select * from studies where enrollment_id = $EnrollmentID order by study_num+1 asc";
				#print "[$sqlstringB]\n";
				$resultB = $db->query($sqlstringB) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringB);
				while (my %rowB = $resultB->fetchhash) {
					my $StudyID = $rowB{'study_id'} . '';
					my $StudyAltID = $rowB{'study_alternateid'} . '';
					my $StudyNum = $rowB{'study_num'} . '';
					my $modality = $rowB{'study_modality'} . '';
					
					print " [$StudyNum]";
					if (trim($modality) eq '') {
						print "Blank modality\n";
						my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, enrollment_id, project_id, study_id, study_num, subject_uid, audit_date) values ('$auditnum', 'dbtofile','invalidprojectid', '$SubjectID', '$EnrollmentID', '$ProjectID', '$StudyID', '$StudyNum','$uid', now())";
						my $resultC = $db->query($sqlstringC) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringC);
					}
					
					# check if the study actually exists on disk
					my $studydir = $subjectdir . '/' . $StudyNum;
					if (-d $studydir) {
						# get list of series
						my $sqlstringC = "select * from " . lc($modality) . "_series where study_id = $StudyID";
						my $resultC = $db->query($sqlstringC) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringC);
						while (my %rowC = $resultC->fetchhash) {
							my $SeriesID = $rowC{lc($modality) . 'series_id'} . '';
							my $SeriesDateTime = $rowC{'series_datetime'} . '';
							my $SeriesNum = $rowC{'series_num'} . '';
							my $SeriesDesc = $rowC{'series_desc'} . '';
							my $SeriesProtocol = $rowC{'series_protocol'} . '';
							my $DataType = '';
							my $NumFiles = '';
							if (lc($modality) eq 'mr') {
								$DataType = $rowC{'data_type'} . '';
								$NumFiles = $rowC{'numfiles'} . '';
							}
							print " $SeriesNum";
							
							# check if the series exists on disk
							my $seriesdir = $studydir . '/' . $SeriesNum;
							if (-d $seriesdir) {
								# start checking the numbers of files, and the contents of the files
								my $seriesdatadir = $seriesdir . "/$DataType";
								if (-d $seriesdatadir) {
									# get number of files
									if ($DataType eq "dicom") {
										my @files = <$seriesdatadir/*.dcm>;
										my $filecount = @files;
										if ($filecount != $NumFiles) {
											print "$uid-$StudyNum-$SeriesNum-$DataType file number mismatch\n";
											my $sqlstringD = "insert into audit_results (audit_num, compare_direction, problem, subject_id, enrollment_id, project_id, study_id, modality, series_id, subject_uid, study_num, series_num, data_type, file_numfiles, db_numfiles, audit_date) values ('$auditnum', 'dbtofile','filecountmismatch', '$SubjectID', '$EnrollmentID', '$ProjectID', '$StudyID', '$modality', '$SeriesID','$uid', '$StudyNum', '$SeriesNum', '$DataType', '$filecount', '$NumFiles', now())";
											my $resultD = $db->query($sqlstringD) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringD);
										}
										
										# check all of the DICOM files in the directory and see if they match the database
										my %mm;
										foreach my $f (@files) {

											my $exifTool = new Image::ExifTool;
											my $tags = $exifTool->ImageInfo($f);
											my $type = $tags->{'FileType'};
											if (defined($type)) {
												if (($type eq "DICOM") || ($type eq 'ACR')) {
													if (defined($tags->{'Error'})) {
														$mm{'error'}{'count'}++;
													}
													else {
														my $fInstitutionName = trim($tags->{'InstitutionName'});
														my $fInstitutionAddress = trim($tags->{'InstitutionAddress'});
														my $fModality = trim($tags->{'Modality'});
														my $fStationName = trim($tags->{'StationName'});
														my $fManufacturer = trim($tags->{'Manufacturer'});
														my $fManufacturersModelName = trim($tags->{'ManufacturersModelName'});
														my $fOperatorsName = trim($tags->{'OperatorsName'});
														my $fPatientID = uc(trim($tags->{'PatientID'}));
														my $fPatientBirthDate = trim($tags->{'PatientBirthDate'});
														my $fPatientName = trim($tags->{'PatientName'});
														my $fPatientSex = trim($tags->{'PatientSex'});
														my $fPatientWeight = trim($tags->{'PatientWeight'});
														my $fPatientSize = trim($tags->{'PatientSize'});
														my $fPatientAge = trim($tags->{'PatientAge'});
														my $fPerformingPhysiciansName = trim($tags->{'PerformingPhysicianName'});
														my $fProtocolName = trim($tags->{'ProtocolName'});
														my $fSeriesDate = trim($tags->{'SeriesDate'});
														my $fSeriesNumber = trim($tags->{'SeriesNumber'});
														my $fSeriesTime = trim($tags->{'SeriesTime'});
														my $fStudyDate = trim($tags->{'StudyDate'});
														my $fStudyDescription = trim($tags->{'StudyDescription'});
														my $fSeriesDescription = trim($tags->{'SeriesDescription'});
														my $fStudyTime = trim($tags->{'StudyTime'});
														my $fRows = trim($tags->{'Rows'});
														my $fColumns = trim($tags->{'Columns'});
														my $fAccessionNumber = trim($tags->{'AccessionNumber'});
														my $fSliceThickness = trim($tags->{'SliceThickness'});
														my $fPixelSpacing = trim($tags->{'PixelSpacing'});
														my $fSequenceName = trim($tags->{'SequenceName'});
														my $fImageType = trim($tags->{'ImageType'});
														my $fImageComments = trim($tags->{'ImageComments'});
														my $fMagneticFieldStrength = trim($tags->{'MagneticFieldStrength'});
														my $fRepetitionTime = trim($tags->{'RepetitionTime'});
														my $fFlipAngle = trim($tags->{'FlipAngle'});
														my $fEchoTime = trim($tags->{'EchoTime'});
														my $fAcquisitionMatrix = trim($tags->{'AcquisitionMatrix'});
														
														if ((lc($fPatientID) ne lc($uid)) && (lc($fPatientID) ne lc($StudyAltID))) {
															if (!InArray($fPatientID, @altuids)) {
																$mm{'PatientID'}{'count'}++;
																$mm{'PatientID'}{'dbstring'} = $uid;
																$mm{'PatientID'}{'filestring'} = $fPatientID;
															}
														}
														
														if ((lc($fPatientName) ne lc($SubjectName)) && (lc($fPatientName) ne lc(FlipName($SubjectName)))) {
															$mm{'PatientName'}{'count'}++;
															$mm{'PatientName'}{'dbstring'} = $SubjectName;
															$mm{'PatientName'}{'filestring'} = $fPatientName;
														}
														if (lc($fPatientSex) ne lc($SubjectSex)) {
															$mm{'PatientSex'}{'count'}++;
															$mm{'PatientSex'}{'dbstring'} = $SubjectSex;
															$mm{'PatientSex'}{'filestring'} = $fPatientSex;
														}
														if ((lc($fSeriesDescription) ne lc($SeriesDesc)) && (lc($fProtocolName) ne lc($SeriesDesc))) {
															$mm{'SeriesDescription'}{'count'}++;
															$mm{'SeriesDescription'}{'dbstring'} = $SeriesDesc;
															$mm{'SeriesDescription'}{'filestring'} = $fSeriesDescription;
														}
														if (lc($fSeriesNumber) ne lc($SeriesNum)) {
															$mm{'SeriesNumber'}{'count'}++;
															$mm{'SeriesNumber'}{'dbstring'} = $SeriesNum;
															$mm{'SeriesNumber'}{'filestring'} = $fSeriesNumber;
														}
													}
												}
											}
											else {
												$mm{'nondicom'}{'count'}++;
											}
										}
										
										# insert any mismatches
										foreach my $mismatch (keys %mm) {
											
											my $count = $mm{$mismatch}{'count'};
											my $FileString = $mm{$mismatch}{'filestring'};
											my $DBString = $mm{$mismatch}{'dbstring'};
											my $sqlstringD = "insert into audit_results (audit_num, compare_direction, problem, mismatch, mismatchcount, subject_id, enrollment_id, project_id, study_id, modality, series_id, subject_uid, study_num, series_num, data_type, file_string, db_string, audit_date) values ('$auditnum', 'dbtofile','dicommismatch', '$mismatch', '$count', '$SubjectID', '$EnrollmentID', '$ProjectID', '$StudyID', '$modality', '$SeriesID','$uid', '$StudyNum', '$SeriesNum', '$DataType', '$FileString', '$DBString', now())";
											my $resultD = $db->query($sqlstringD) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringD);
										}
									}
								}
								else {
									print "$uid-$StudyNum-$SeriesNum-$DataType does not exist\n";
									my $sqlstringD = "insert into audit_results (audit_num, compare_direction, problem, subject_id, enrollment_id, project_id, study_id, modality, series_id, subject_uid, study_num, series_num, data_type, audit_date) values ('$auditnum', 'dbtofile','seriesdatatypemissing', '$SubjectID', '$EnrollmentID', '$ProjectID', '$StudyID', '$modality', '$SeriesID','$uid', '$StudyNum', '$SeriesNum', '$DataType', now())";
									my $resultD = $db->query($sqlstringD) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringD);
								}
							}
							else {
								print "$uid-$StudyNum-$SeriesNum does not exist\n";
								my $sqlstringD = "insert into audit_results (audit_num, compare_direction, problem, subject_id, enrollment_id, project_id, study_id, modality, series_id, subject_uid, study_num, series_num, audit_date) values ('$auditnum', 'dbtofile','seriesmissing', '$SubjectID', '$EnrollmentID', '$ProjectID', '$StudyID', '$modality', '$SeriesID','$uid', '$StudyNum', '$SeriesNum', now())";
								my $resultD = $db->query($sqlstringD) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringD);
							}
						}
					}
					else {
						print "$uid-$StudyNum does not exist\n";
						my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, enrollment_id, project_id, study_id, study_num, subject_uid, audit_date) values ('$auditnum', 'dbtofile','studymissing', '$SubjectID', '$EnrollmentID', '$ProjectID', '$StudyID', '$StudyNum','$uid', now())";
						my $resultC = $db->query($sqlstringC) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringC);
					}
				}
			}
		}
		else {
			print "$uid does not exist\n";
			my $sqlstringA = "insert into audit_results (audit_num, compare_direction, problem, subject_uid, audit_date) values ('$auditnum', 'dbtofile','subjectmissing','$uid', now())";
			my $resultA = $db->query($sqlstringA) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringA);
		}
		$ii++;
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
	
	my $ret = $parts[1] . '^' . $parts[0];
	print " [$n -> $ret] ";
	return $ret;
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
