#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB usage.pl
# Copyright (C) 2004 - 2019
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
        #################################################################################################


        if ($uid eq '') {
            print "$SubjectID does not have a UID\n";
            my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','empty uid', '$SubjectID', now())";
            my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

            $sqlstringC = "insert into audit_subject (subject_id, audit_datetime, audit_message, audit_number) values ('$SubjectID', now(), 'empty uid', '$auditnum')";
            $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
        }

        #print "$SubjectID: $SubjectName\n";
        if ($SubjectName eq '') {
            print "$SubjectID does not have a name\n";
            my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','empty name', '$SubjectID', now())";
            my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

            $sqlstringC = "insert into audit_subject (subject_id, audit_datetime, audit_message, audit_number) values ('$SubjectID', now(), 'empty name', '$auditnum')";

            $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
        }

        #################################################################################################
        # UNFINISHED
        # Check the subject directory
        #################################################################################################

        # check if the UID directory exists in the filesystem
        my $subjectdir = $cfg{'archivedir'}.'/'.$uid;

        #print "DIRECTORY\n";
        #print "$subjectdir \n";

        if (-d $subjectdir){

        }
        else{
            print "The directory for $uid does not exist\n";
            my $sqlstringA = "insert into audit_results (audit_num, compare_direction, problem, subject_uid, audit_date) values ('$auditnum', 'dbtofile','subject''s directory is missing','$uid', now())";
            my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
        }

        #################################################################################################
        # UNFINISHED
        # Check the study directory
        #################################################################################################


        #print "\nChecking $uid [$ii of $numSubjects]\n";
        $ii++;

    }

    # Change end
    #################################################################################################

    #################################################################################################
    # CHANGE
    # April - May 2017
    # Find the studies without series or with a blank or an invalid modality
    # Check Study date
    # Check series date
    # check if it has series
    # UNFINISHED
    # Check the study directory
    #################################################################################################


    $sqlstring = "select * from studies";
    $result = SQLQuery($sqlstring, __FILE__, __LINE__);
    while (my %row = $result->fetchhash) {

        my $study_id = $row{'study_id'};
        my $study_modality = $row{'study_modality'};
        my $study_datetime = $row{'study_datetime'};

        my $series = lc $study_modality . '_series';

        if ($series eq '_series') {

            #################################################################################################
            # Check if it has modality
            #################################################################################################

            print "$study_id does not have a modality\n";
            my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','study does not have a modality', '$study_id', now())";
            my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
        }
        else {
            my $sqlstring1 = "select * from $series where study_id = $study_id";
            my $result1 = SQLQuery($sqlstring1, __FILE__, __LINE__);

            if (my %row = $result1->fetchhash){
                my $series_datetime = $row{'series_datetime'};
                my $a = lc $study_modality.'series_id';
                my $series_id = $row{$a};

                #################################################################################################
                # Check series date
                #################################################################################################

                checkDate('series', $series_id, $auditnum, 'audit_series', $series_datetime, $study_modality);

            }
            else {
                #################################################################################################
                # check if has series
                #################################################################################################
                print "$study_id does not have a series\n";

                my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','study does not have series', '$study_id', now())";
                my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

                $sqlstringC = "insert into audit_study (study_id, audit_datetime, audit_message, audit_number) values ('$study_id', now(), 'study does not have series', '$auditnum')";
                $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

            }
        }

        #################################################################################################
        # Check Study date
        #################################################################################################

        checkDate('study', $study_id, $auditnum, 'audit_study', $study_datetime, $study_modality);





    }

    # Change end
    #################################################################################################
    #
    SetModuleStopped();
    return 1;
}
sub checkDate{
    my $i=0;
    my $problemType = '';
    my $id = '';
    my $auditnum = '';
    my $auditTable = '';
    my $datetime = '';
    my $study_modality = '';

    foreach my $item (@_){
        if ($i==0){
            $problemType = $item;
        }
        elsif ($i==1){
            $id = $item;
        }
        elsif ($i==2){
            $auditnum = $item;
        }
        elsif ($i==3){
            $auditTable = $item;
        }
        elsif ($i==4){
            $datetime = $item;
        }
        elsif ($i==5){
            $study_modality = $item;
        }
        $i++;
    }
    use Time::Piece();
    my $now = Time::Piece->new -> ymd();
    my $yearN = substr($now, 0, 4);
    my $monthN = substr($now, 5, 2);
    my $dayN = substr($now, 8, 2);

    my $yearDB = substr($datetime, 0, 4);
    my $monthDB = substr($datetime, 5, 2);
    my $dayDB = substr($datetime, 8, 2);

    if ($datetime eq ''){
        print "$id does not have a date\n";
        my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb','$problemType not have a date', '$id', now())";
        my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

        if ($problemType eq 'series') {
            $sqlstringC = "insert into $auditTable ($problemType.'_id', modality, audit_datetime, audit_message, audit_number) values ('$id', $study_modality, now(), $problemType does not have a date', '$auditnum')";
        }
        else {
            $sqlstringC = "insert into $auditTable ($problemType.'_id', audit_datetime, audit_message, audit_number) values ('$id', now(), '$problemType does not have a date', '$auditnum')";
        }
        $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

    }elsif (($yearDB < 2000) || ($yearN < $yearDB) || (($yearN == $yearDB) && ($monthN < $monthDB)) || (($yearN == $yearDB) && ($monthN == $monthDB) && ($dayN < $dayDB))) {
        insertErrorIntoDB($problemType, $id, $auditnum, $auditTable, 'does have an invalid date\n', 'does have an invalid date', $study_modality);
    }
}

sub insertErrorIntoDB{

    use strict;
    my $i=0;
    my $problemType = '';
    my $id = '';
    my $auditnum = '';
    my $auditTable = '';
    my $errorMsg='';
    my $errorMsgDB='';
    my $study_modality='';

    foreach my $item (@_){
        if ($i==0){
            $problemType = $item;
        }
        elsif ($i==1){
            $id = $item;
        }
        elsif ($i==2){
            $auditnum = $item;
        }
        elsif ($i==3){
            $auditTable = $item;
        }
        elsif ($i==4){
            $errorMsg = $item;
        }
        elsif ($i==5){
            $errorMsgDB = $item;
        }
        elsif ($i==6){
            $study_modality = $item;
        }
        $i++;
    }

    my $sqlstringC = "insert into audit_results (audit_num, compare_direction, problem, subject_id, audit_date) values ('$auditnum', 'dbtodb', '$problemType $id $errorMsgDB', '$id', now())";

    my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

    if ($problemType eq 'series') {
        $sqlstringC = "insert into $auditTable (series_id, modality, audit_datetime, audit_message, audit_number) values ('$id', '$study_modality', now(), '$problemType $id $errorMsgDB', '$auditnum')";
    }
    else{
        my $a = $problemType.'_id';
        $sqlstringC = "insert into $auditTable ($a, audit_datetime, audit_message, audit_number) values ('$id', now(), '$problemType $id $errorMsgDB', '$auditnum')";
    }

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
