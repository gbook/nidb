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
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'},
		$cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");

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

		#################################################################################################
		# CHANGE
		# April - May 2017
		# Find the subjects without a uid or a name
		if ($uid eq '') {
			print "$SubjectID does not have a UID\n";
			my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','empty uid', '$SubjectID', now())";
			my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

            $sqlstringC = "insert into audit_subjects (subject_id, audit_datetime, audit_message, audit_number) values ('$SubjectID', now(), 'empty uid' '$auditnum')";
            $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
		}

		#print "$SubjectID: $SubjectName\n";
		if ($SubjectName eq '') {
			print "$SubjectID does not have a name\n";
			my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','empty name', '$SubjectID', now())";
			my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

            $sqlstringC = "insert into audit_subjects (subject_id, audit_datetime, audit_message, audit_number) values ('$SubjectID', now(), 'empty name' '$auditnum')";
            $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
		}

		#print "\nChecking $uid [$ii of $numSubjects]\n";

		# check if the UID directory exists in the filesystem
		my $subjectdir = $cfg{'archivedir'}.'/'.$uid;

		#print "DIRECTORY\n";
		#print "$subjectdir \n";



		$ii++;
	}

	# Change end
	#################################################################################################

	#################################################################################################
	# CHANGE
	# April - May 2017
	# Find the studies without series or with a blank or an invalid modality
	$sqlstring = "select * from studies";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	while (my %row = $result->fetchhash) {

        my $study_id = $row{'study_id'};
        my $study_modality = $row{'study_modality'};
        my $study_datetime = $row{'study_datetime'};

        my $series = '';
        switch ($study_modality)
        {
            case 'ASSESSMENT'{
                $series = 'assessment_series';
            }
            case 'AUDIO'{
                $series = 'audio_series';
            }
            case 'CONSENT'{
                $series = 'consent_series';
            }
            case 'CR'{
                $series = 'cr_series';
            }
            case 'CT'{
                $series = 'ct_series';
            }
            case 'EEG'{
                $series = 'eeg_series';
            }
            case 'ET'{
                $series = 'et_series';
            }
            case 'GSR'{
                $series = 'gsr_series';
            }
            case 'MR'{
                $series = 'mr_series';
            }
            case 'NM'{
                $series = 'nm_series';
            }
            case 'OT'{
                $series = 'ot_series';
            }
            case 'PPI'{
                $series = 'ppi_series';
            }
            case 'SNP'{
                $series = 'snp_series';
            }
            case 'SR'{
                $series = 'sr_series';
            }
            case 'TASK'{
                $series = 'task_series';
            }
            case 'US'{
                $series = 'us_series';
            }
            case 'VIDEO'{
                $series = 'video_series';
            }
            case 'XA'{
                $series = 'xa_series';
            }
        }

        if ($study_modality eq '') {
            print "$study_id does not have a modality\n";
            my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','study does not have a modality', '$study_id', now())";
            my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
        }
        else {
            my $sqlstring1 = "select * from $series where study_id = $study_id";
            my $result1 = SQLQuery($sqlstring1, __FILE__, __LINE__);
            if (!(my %rowA = $result1->fetchhash)) {
                print "$study_id does not have a series\n";
                my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','study does not have series', '$study_id', now())";
                my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

                $sqlstringC = "insert into audit_studies (study_id, audit_datetime, audit_message, audit_number) values ('$study_id', now(), 'study does not have series', '$auditnum')";
                $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
            }
        }

        # Find the studies without invalid date
        use Time::Piece();
        my $now = Time::Piece->new -> ymd();
        my $yearN = substr($now, 0, 4);
        my $monthN = substr($now, 5, 2);
        my $dayN = substr($now, 8, 2);

        my $yearDB = substr($study_datetime, 0, 4);
        my $monthDB = substr($study_datetime, 5, 2);
        my $dayDB = substr($study_datetime, 8, 2);

        if ($study_datetime eq ''){
            print "$study_id does not have a date\n";
            my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','study does not have a date', '$study_id', now())";
            my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

            $sqlstringC = "insert into audit_studies (study_id, audit_datetime, audit_message, audit_number) values ('$study_id', now(), 'study does not have a date', '$auditnum')";
            $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

        }elsif ($yearDB < 2000){
            insertErrorIntoDB('study_id does have an invalid date\n', 'study does have an invalid date', $auditnum, $study_id);
        }
        elsif ($yearN < $yearDB){
            insertErrorIntoDB('study_id does have an invalid date\n', 'study does have an invalid date', $auditnum, $study_id);
        }
        elsif (($yearN == $yearDB) && ($monthN < $monthDB)){
            insertErrorIntoDB('study_id does have an invalid date\n', 'study does have an invalid date', $auditnum, $study_id);
        }
        elsif (($yearN == $yearDB) && ($monthN == $monthDB) && ($dayN < $dayDB)){
            insertErrorIntoDB('study_id does have an invalid date\n', 'study does have an invalid date', $auditnum, $study_id);
        }
    }

	# Change end
	#################################################################################################
	#
	SetModuleStopped();
	return 1;
}

sub insertErrorIntoDB{
    use strict;
    my $i=0;
    my $errorMsg='';
    my $errorMsgDB='';
    my $auditnum=0;
    my $study_id=0;
    foreach my $item (@_){
        if ($i==0){
            $errorMsg = $item;
        }
        elsif ($i==1){
            $errorMsgDB = $item;
        }
        elsif ($i==2){
            $auditnum = $item;
        }
        elsif ($i==3){
            $study_id = $item;
        }
        $i++;
    }
    print("\n");
    my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb', '$errorMsgDB', '$study_id', now())";
    print "$sqlstringC";
    my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

    $sqlstringC = "insert into audit_studies (study_id, audit_datetime, audit_message, audit_number) values ('$study_id', now(), '$errorMsgDB', '$auditnum')";
    $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
}

# ----------------------------------------------------------
# --------- FlipName ---------------------------------------
# ----------------------------------------------------------
sub FlipName {
	my ($n) = @_;
	
	my @parts = split(/\^/,$n);
	
	if (scalar @parts > 1) {
		my $ret = $parts[1] . '^' . $parts[0];
		#print " [$n -> $ret] ";
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
