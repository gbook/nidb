#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB pipeline.pl
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

# ------------------------------------------------------------------------------
# This program will check the database for pipelines which need
# to be run, and will run them
# ------------------------------------------------------------------------------


use strict;
use warnings;
use Mysql;
use DBI;
use File::Copy;
use File::Copy::Recursive;
use File::Path qw(make_path remove_tree);
use Switch;
use Cwd;
use Sort::Naturally;
use Net::SMTP::TLS;
use Data::Dumper;
use Text::ParseWords;

require 'nidbroutines.pl';

# -------------- variables declariation ---------------------------------------
#my %config = do 'config.pl';
our %cfg;
LoadConfig();

# database variables
our $db;
# script specific information
our $scriptname = "pipeline";
our $lockfileprefix = "pipeline";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 10;				# number of times this program can be run concurrently
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
	my $x = &ProcessPipelines();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done.\nDeleting $lockfile\n";
	unlink $lockfile;
}

exit(0);

# --------------------------------------------------------
# -------------- ProcessPipelines ------------------------
# --------------------------------------------------------
# The main function, which finds all studies which do not
# have an entry in the analysis table
# --------------------------------------------------------
sub ProcessPipelines() {
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
	SetPipelineProcessStatus('started',0,0);
	
	# check if this module should be running now or not
	if (!ModuleCheckIfActive($scriptname, $db)) {
		WriteLog("Not supposed to be running right now. Exiting module");
		print "Module disabled. Stopping execution\n";
		SetModuleStopped();
		return 0;
	}
	
	# determine which pipeline isn't running and has been the longest since starting
	# we're only going to run 1 pipeline per instance of pipeline.pl
	my $sqlstring = "select * from pipelines where pipeline_status <> 'running' and (pipeline_enabled = 1 or pipeline_testing = 1) order by pipeline_laststart asc";
	WriteLog($sqlstring);
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows < 1) {
		WriteLog("No pipelines need to be run. Exiting module");
		# update the stop time
		SetModuleStopped();
		return 0;
	}
	# get the new pipeline status run number
	my $newrunnum = 0;
	# populate the pipeline_status table so we know what order these pipelines will run in
	my $i = 0;
	my @pipelinerows;
	while (my %row = $result->fetchhash) {
		$pipelinerows[$i] = {%row};
		my $pid = $row{'pipeline_id'};
		my $pipelinename = $row{'pipeline_name'};
		$i++;
	}
	# go back to the beginning of the dataset
	#$result->dataseek();
	#print Dumper(@pipelinerows);
	#exit(0);
	#PIPELINE: while (my %row = $result->fetchhash) {
	PIPELINE: for my $i (0 .. $#pipelinerows) {
		#print Dumper($pipelinerows[$i]);
		my %row = %{$pipelinerows[$i]};
		
		my $pid = $row{'pipeline_id'};
		
		my $pipelinename = $row{'pipeline_name'};
		my $pipelineversion = $row{'pipeline_version'};
		my $pipelinedataand = $row{'pipeline_dataand'};
		my $pipelinedependency = $row{'pipeline_dependency'};
		my $pipelinegroupids = $row{'pipeline_groupid'} . '';
		my $pipelinedirectory = $row{'pipeline_directory'};
		my $usetmpdir = $row{'pipeline_usetmpdir'};
		my $tmpdir = $row{'pipeline_tmpdir'};
		my $pipelinequeue = $row{'pipeline_queue'};
		my $pipelinesubmithost;
		if ($row{'pipeline_submithost'} eq "") { $pipelinesubmithost = $cfg{'clustersubmithost'}; }
		else { $pipelinesubmithost = $row{'pipeline_submithost'}; }
		my $pipelinelevel = $row{'pipeline_level'};
		my $deplevel = $row{'pipeline_dependencylevel'};
		my $depdir = $row{'pipeline_dependencydir'};
		my $testing = $row{'pipeline_testing'};
		my $pipelineremovedata = $row{'pipeline_removedata'};
		my $pipelineresultscript = $row{'pipeline_resultsscript'};
		print "Working on pipeline [$pid] - [$pipelinename] Submitting to queue [$pipelinequeue] through host [$pipelinesubmithost]\n";
		WriteLog("Working on pipeline [$pid] - [$pipelinename] Submitting to queue [$pipelinequeue] through host [$pipelinesubmithost]");

		SetPipelineProcessStatus('running',$pid,0);
		
		# mark the pipeline as having been checked
		my $sqlstringC = "update pipelines set pipeline_lastcheck = now() where pipeline_id = '$pid'";
		my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
		
		if (trim($pipelinequeue) eq "") {
			WriteLog("No queue specified");
			SetPipelineStatusMessage($pid, 'No queue specified.');
			SetPipelineStopped($pid, $newrunnum);
			next PIPELINE;
		}
		my $analysisRowID;
		my $analysisGroupRowID;
	
		# check if the pipeline is running, if so, go on to the next one
		my $sqlstringX = "select pipeline_status from pipelines where pipeline_id = $pid";
		my $resultX = SQLQuery($sqlstringX, __FILE__, __LINE__);
		my %rowX = $resultX->fetchhash;
		my $status = trim($rowX{'pipeline_status'});
		if ($status eq 'running') {
			# another process has started running this pipeline, so go on to the next one
			WriteLog("This pipeline is already running");
			next PIPELINE;
		}
		
		# update the pipeline start time
		SetPipelineStatusMessage($pid, 'Running');
		SetPipelineRunning($pid);

		WriteLog("Running pipeline $pid");

		my $dd = ();
		my @datadef = ();
		if ($pipelinelevel != 0) {
			if ( (($pipelinedataand != -1) && ($pipelinelevel == 1)) || (($pipelinelevel == 2) && ($pipelinedependency eq ''))) {
				$dd = GetPipelineDataDef($pid, $pipelineversion);
				if (!$dd) {
					WriteLog("Pipeline [$pipelinename - $pid] has no data definition. Skipping.");
					
					# update the statuses, and stop the modules
					SetPipelineStatusMessage($pid, 'Pipeline has no data definition. Skipping');
					SetPipelineStopped($pid, $newrunnum);
					next PIPELINE;
				}
				@datadef = @$dd;
			}
		}

		# get the pipeline steps (the script)
		my $ps = GetPipelineSteps($pid, $pipelineversion);
		#WriteLog( Dumper($ps) );
		if (!$ps) {
			WriteLog("Pipeline [$pipelinename - $pid] has no steps. Skipping.");

			# update the statuses and stop the modules
			SetPipelineStatusMessage($pid, 'Pipeline has no steps. Skipping');
			SetPipelineStopped($pid, $newrunnum);
			next PIPELINE;
		}
		my @pipelinesteps = @$ps;
		
		# connect to the DB (in case it became disconnected)
		DatabaseConnect();
		
		# determine which analysis level this is
		if ($pipelinelevel == 0) {
			# check if this module should be running now or not
			$sqlstring = "select * from modules where module_name = '$scriptname' and module_isactive = 1";
			my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
			if ($result->numrows < 1) {
				WriteLog("Module disabled. Stopping execution. Exiting module");
				print "Module disabled. Stopping execution\n";
				SetPipelineStopped($pid, $newrunnum);
				SetModuleStopped();
				return 1;
			}

			# only 1 analysis should ever be run with the oneshot level, so if 1 already exists, regardless of state or pipeline version, then
			# leave this function without running the analysis
			my $sqlstring = "select * from analysis where pipeline_id = $pid";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			if ($result->numrows > 0) {
				WriteLog("An analysis already exists for this one-shot level pipeline, exiting");
				SetPipelineStatusMessage($pid, 'An analysis already exists for this one-shot pipeline. Skipping');
				SetPipelineStopped($pid, $newrunnum);
				#SetModuleStopped();
				next PIPELINE;
			}			
			# create the analysis path
			my $analysispath = "/mount$pipelinedirectory/$pipelinename";
			WriteLog("Creating path [$analysispath/pipeline]");
			MakePath("$analysispath/pipeline");
			my $systemstring = "mkdir -p $analysispath/pipeline";
			#WriteLog("[$systemstring]: " . `$systemstring 2>&1`);
			#mkpath("$analysispath/pipeline", { verbose => 1, mode => 0777});
			chmod(0777,"$analysispath/pipeline");
			
			if (-d "$analysispath/pipeline") {
				WriteLog("Directory [$analysispath/pipeline] exists!");
			}
			
			# this file will record any events during setup
			my $setuplog = "/mount$analysispath/pipeline/analysisSetup.log";
			AppendLog($setuplog, "Beginning recording");
			WriteLog("Should have created this analysis setup log [$setuplog]");
			
			# insert a temporary row, to be updated later, in the analysis table as a placeholder
			# so that no other processes end up running it
			$sqlstring = "insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate) values ($pid,$pipelineversion,'','','processing',now())";
			#WriteLog($sqlstring);
			AppendLog($setuplog, $sqlstring);
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			my $analysisRowID = $result->insertid;
			
			# create the SGE job file
			my $sgebatchfile = CreateSGEJobFile($analysisRowID, 0, 'UID', 'STUDYNUM', 'STUDYDATETIME',$analysispath, $usetmpdir, $tmpdir, $pipelinename, $pid, $pipelineremovedata, $pipelineresultscript, @pipelinesteps);
		
			$systemstring = "chmod -Rf 777 $analysispath";
			WriteLog("[$systemstring]");
			`$systemstring 2>&1`;
			
			# submit the SGE job
			if (-d $analysispath) {
				WriteLog("[$analysispath] exists!");
				AppendLog($setuplog, "[$analysispath] exists!");
			}
			open SGEFILE, "> $analysispath/sge.job" || die ("Could not open [$analysispath/sge.job] because [$!]");
			print SGEFILE $sgebatchfile;
			close SGEFILE;
			chmod(0777,$sgebatchfile);
			chmod(0777,$analysispath);
			
			# submit the sucker to the cluster
			$systemstring = "ssh $pipelinesubmithost qsub -u onrc -q $pipelinequeue \"$analysispath/sge.job\"";
			my $sgeresult = `$systemstring 2>&1`;
			print "SGE submit result [$sgeresult]\n";
			WriteLog("SGE submit result [$sgeresult]");
			my @parts = split(' ', $sgeresult);
			my $jobid = $parts[2];
			WriteLog(join('|',@parts));
			WriteLog("[$systemstring]: " . $sgeresult);
			AppendLog($setuplog, $sgeresult);
			
			$sqlstring = "update analysis set analysis_status = 'submitted', analysis_statusmessage = 'Submitted to $pipelinequeue', analysis_qsubid = '$jobid' where analysis_id = $analysisRowID";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			
			$jobsWereSubmitted = 1;
		}
		# ======================= LEVEL 1 =======================
		elsif ($pipelinelevel == 1) {
			my $setuplog = "";
		
			# fix the directory if its special
			if ($pipelinedirectory eq "") {
				$pipelinedirectory = $cfg{'analysisdir'};
			}
			else {
				$pipelinedirectory = $cfg{'mountdir'} . $pipelinedirectory;
			}

			if ($pipelinedependency eq "") {
				$pipelinedependency = "0";
			}
			
			# if there are multiple dependencies, we'll need to loop through all of them separately
			my @deps = split(',', $pipelinedependency);
			foreach my $pipelinedep(@deps) {
				# --- first level pipeline ---
				#WriteLog("Calling GetStudyToDoList($pid, " . $datadef[0]{'modality'} . ", $pipelinedep, $pipelinegroupids)");
				my @studyids = GetStudyToDoList($pid, $datadef[0]{'modality'}, $pipelinedep, $pipelinegroupids);
				
				my $numsubmitted = 0;
				foreach my $sid(@studyids) {

					SetPipelineProcessStatus('running',$pid,$sid);

					$numchecked = $numchecked + 1;
					WriteLog("--------------------- Working on study [$sid] for [$pipelinename] --------------------");
					
					# connect to the DB (in case it became disconnected)
					$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
					
					WriteLog("Checking if this module should be running");
					# check if this module should be running now or not
					$sqlstring = "select * from modules where module_name = '$scriptname' and module_isactive = 1";
					my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
					if ($result->numrows < 1) {
						WriteLog("Module disabled. Stopping execution. Exiting module");
						print "Module disabled. Stopping execution\n";
						SetPipelineStopped($pid, $newrunnum);
						SetModuleStopped();
						return 1;
					}
					
					WriteLog("Checking if we've reached the max number of concurrent analyses");
					# check if the number of concurrent jobs is reached, also check if this module is enabled
					while (my $filled = IsQueueFilled($pid)) {
						if ($filled == 0) { last; }
						if ($filled == 1) {
							# update the pipeline status message
							SetPipelineStatusMessage($pid, 'Process quota reached. Waiting 5 minutes to resubmit');
							WriteLog("Concurrent analysis quota reached, waiting 5 minutes");
							print "Queue full, waiting 5 minutes...";
							sleep(300); # sleep for 5 minutes
						}
						if ($filled == 2) { return 1; }
					}
					
					WriteLog("Checking if this analysis already exists");
					$setuplog .= "Checking if this analysis already exists\n";
					# check if the analysis has an entry in the analysis table
					my $sqlstring = "select * from analysis where pipeline_id = $pid and study_id = $sid";
					WriteLog($sqlstring);
					$result = SQLQuery($sqlstring, __FILE__, __LINE__);
					my %row = $result->fetchhash;
					my $r = 0;
					my $analysisRowID = trim($row{'analysis_id'});
					my $rerunresults = trim($row{'analysis_rerunresults'});
					WriteLog("analysisRowID [$analysisRowID]  rerunresults [$rerunresults]");
					#$setuplog .= "analysisRowID [$analysisRowID]  rerunresults [$rerunresults]\n";
					# only continue through this section if there is no analysis, or there is an analysis and it needs the results rerun
					if (($rerunresults eq "1") || ($analysisRowID eq "")) {
						SetPipelineStatusMessage($pid, "Checking analysis $r of " . $result->numrows);
						$r++;
						# insert a temporary row, to be updated later, in the analysis table as a placeholder
						# so that no other processes end up running it
						if ($analysisRowID eq "") {
							$sqlstring = "insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate) values ($pid,$pipelineversion,$pipelinedep,$sid,'processing',now())";
							WriteLog($sqlstring);
							my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
							$analysisRowID = $result->insertid;
						}
						
						# get information about the study
						$sqlstring = "select *, date_format(study_datetime,'%Y%m%d_%H%i%s') 'studydatetime' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = $sid";
						#$setuplog .= WriteLog($sqlstring) . "\n";
						my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
						my %row = $result->fetchhash;
						my $uid = $row{'uid'};
						my $studynum = $row{'study_num'};
						my $subjectid = $row{'subject_id'};
						my $studydatetime = $row{'studydatetime'};
						my $numseries = 0;
						my $datalog;
						my $datareport;
						if (defined($uid)) {
							WriteLog("StudyDateTime: [$studydatetime], Working on: [$uid$studynum]");
							print "StudyDateTime: $studydatetime\n";
							my $analysispath = "$pipelinedirectory/$uid/$studynum/$pipelinename";
							
							# this file will record any events during setup
							my $setuplogF = "/mount" . $cfg{'analysisdir'} . "/$uid/$studynum/$pipelinename/pipeline/analysisSetup.log";
							#$setuplog .= "Beginning recording\n";
							WriteLog("Should have created this analysis setup log [$setuplogF]");
							
							# get the nearest study for this subject that has the dependency
							my $sqlstringA = "select study_num from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.subject_id = $subjectid and a.pipeline_id = '$pipelinedep' and a.analysis_status = 'complete' and a.analysis_isbad <> 1 order by abs(datediff(b.study_datetime, '$studydatetime')) limit 1";
							#$setuplog .= WriteLog($sqlstringA) . "\n";
							my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
							my %rowA = $resultA->fetchhash;
							my $studynum_nearest = $rowA{'study_num'};
							
							my $deppath;
							if (($pipelinedep != 0) && ($pipelinedep ne "")) {
								if ($deplevel eq "subject") {
									WriteLog("Dependency is a subject level (will match dep for same subject, any study)");
									#$setuplog .= "Dependency is a subject level (will match dep for same subject, any study)\n";
									$deppath = "$pipelinedirectory/$uid/$studynum_nearest";
								}
								else {
									WriteLog("Dependency is a study level (will match dep for same subject, same study)");
									#$setuplog .= "Dependency is a study level (will match dep for same subject, same study)";
									$deppath = "$pipelinedirectory/$uid/$studynum";
									
									# check if this study actually has this dependency
									my $sqlstringB = "select * from analysis where study_id = '$sid' and pipeline_id = '$pipelinedep'";
									my $resultB = $db->query($sqlstringB) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstringB);
									if ($resultB->numrows < 1) {
										# this study has no dependencies within the same study
										my $datalog2 = EscapeMySQLString($setuplog);
										my $sqlstringC = "update analysis set analysis_datalog = '$datalog2', analysis_status = 'NoMatchingStudyDependency', analysis_startdate = null where analysis_id = $analysisRowID";
										my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
										next;
									}
									
									# check if the dependency is complete
									$sqlstringB = "select * from analysis where study_id = '$sid' and pipeline_id = '$pipelinedep' and analysis_status = 'complete'";
									$resultB = SQLQuery($sqlstringB, __FILE__, __LINE__);
									if ($resultB->numrows < 1) {
										# this study has no dependencies within the same study
										my $datalog2 = EscapeMySQLString($setuplog);
										my $sqlstringC = "update analysis set analysis_datalog = '$datalog2', analysis_status = 'IncompleteDependency', analysis_startdate = null where analysis_id = $analysisRowID";
										my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
										next;
									}
									
									# check if the dependency is not bad
									$sqlstringB = "select * from analysis where study_id = '$sid' and pipeline_id = '$pipelinedep' and analysis_isbad <> 1";
									$resultB = SQLQuery($sqlstringB, __FILE__, __LINE__);
									if ($resultB->numrows < 1) {
										# this study has no dependencies within the same study
										my $datalog2 = EscapeMySQLString($setuplog);
										my $sqlstringC = "update analysis set analysis_datalog = '$datalog2', analysis_status = 'BadDependency', analysis_startdate = null where analysis_id = $analysisRowID";
										my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
										next;
									}
								}
							}
							else {
								
							}
							WriteLog("Dependency path is [$deppath]");
							#$setuplog .= "Dependency path is [$deppath]\n";
							
							WriteLog("This will be the analysis path [$analysispath]. If data exists for it");
							#$setuplog .= "This will be the analysis path [$analysispath]. If data exists for it\n";
							
							# download the data for the study, if there is any that satisfies the search criteria
							if ($pipelinedataand == -1) {
								$numseries = 1;
								$datalog = "No data downloaded in accordance with this pipeline specification";
							}
							else {
								($numseries,$datalog,$datareport) = GetData($sid, $analysispath, $uid, $analysisRowID, $pipelinedataand, @datadef);
							}
							#WriteLog("Datalog: $datalog");
							if (($numseries > 0) || ($rerunresults eq "1")) {
								#print "11\n";
								WriteLog(" ----- Study [$sid] has [$numseries] matching series downloaded (or needs results rerun). Beginning analysis ----- ");
								$setuplog .= " ----- Study [$sid] has [$numseries] matching series downloaded (or needs results rerun). Beginning analysis ----- \n";

								my $dependencyname;
								if ($rerunresults ne "1") {
									if ($pipelinedep != 0) {
										$setuplog .= WriteLog("There is a pipeline dependency [$pipelinedep]") . "\n";
										my $sqlstring = "select pipeline_name from pipelines where pipeline_id = $pipelinedep";
										$setuplog .= WriteLog($sqlstring) . "\n";
										my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
										if ($result->numrows > 0) {
											my %row = $result->fetchhash;
											$dependencyname = $row{'pipeline_name'};
											WriteLog("Found " . $result->numrows . " pipeline dependency [$dependencyname]");
											$setuplog .= "Found " . $result->numrows . " pipeline dependency [$dependencyname]\n";
										}
										else {
											WriteLog("Pipeline dependency ($pipelinedep) does not exist!");
											$setuplog .= "Pipeline dependency ($pipelinedep) does not exist!\n";
											SetPipelineStatusMessage($pid, "Pipeline dependency ($pipelinedep) does not exist!");
											SetPipelineStopped($pid, $newrunnum);
											next PIPELINE;
										}
									}
									else {
										WriteLog("No pipeline dependencies [$pipelinedep]");
										$setuplog .= "No pipeline dependencies [$pipelinedep]\n";
									}
								
									MakePath("$analysispath/pipeline");
									chmod(0777,"$analysispath/pipeline");
									if ($pipelinedep != 0) {
										#chdir($deppath);
										if (-e "$deppath/$dependencyname") {
											$setuplog .= WriteLog("Full dependency path [$deppath/$dependencyname] exists") . "\n";
										}
										else {
											$setuplog .= WriteLog("Full dependency path [$deppath/$dependencyname] does NOT exist") . "\n";
										}
										
										my $systemstring;
										if ($depdir eq "subdir") {
											$setuplog .= WriteLog("Dependency will be copied to a subdir") . "\n";
											$systemstring = "cp -rl $deppath/$dependencyname $analysispath/";
										}
										else { # root dir
											$setuplog .= WriteLog("Dependency will be copied to the root dir") . "\n";
											$systemstring = "cp -rl $deppath/$dependencyname/* $analysispath/";
										}
										# copy any dependencies
										$setuplog .= WriteLog("pwd: [" . getcwd . "], [$systemstring] :" . `$systemstring 2>&1`) . "\n";
										
										# delete any log files and SGE files that came with the dependency
										$systemstring = "rm --preserve-root $analysispath/pipeline/* $analysispath/origfiles.log $analysispath/sge.job";
										$setuplog .= WriteLog("[$systemstring] " . __LINE__ . " :" . `$systemstring 2>&1`) . "\n";
										
										# make sure the whole tree is writeable
										$systemstring = "chmod -R 777 $analysispath";
										WriteLog("pwd: [" . getcwd . "], [$systemstring]");
										`$systemstring 2>&1`;
									}
									else {
										$setuplog .= WriteLog("Pipelinedep was 0 [$pipelinedep]") . "\n";
									}
									
									# now safe to write out the setuplog 
									AppendLog($setuplogF, $setuplog);
									
									# and safe to write out the datalog
									open DATALOG, "> $analysispath/pipeline/data.log" or die("[Line: " . __LINE__ . "] Could not create $analysispath/pipeline/data.log");
									print DATALOG $datalog;
									close DATALOG;
									chmod(0777,"$analysispath/pipeline/data.log");
								}
								my $realanalysispath = $analysispath;
								$realanalysispath =~ s/\/mount//g;
								
								# create the SGE job file
								my $sgebatchfile = CreateSGEJobFile($analysisRowID, 0, $uid, $studynum, $realanalysispath, $usetmpdir, $tmpdir, $studydatetime, $pipelinename, $pid, $pipelineremovedata, $pipelineresultscript, @pipelinesteps);
							
								`chmod -Rf 777 $analysispath`;
								#WriteLog($sgebatchfile);
								# submit the SGE job
								my $sgejobfilename = "";
								if ($rerunresults eq "1") {
									$sgejobfilename = "$analysispath/sgererunresults.job";
								}
								else {
									$sgejobfilename = "$analysispath/sge.job";
								}
								open SGEFILE, "> $sgejobfilename";
								print SGEFILE $sgebatchfile;
								close SGEFILE;
								chmod(0777,$sgebatchfile);
								chmod(0777,$analysispath);
								
								# submit the sucker to the cluster
								my $systemstring = "ssh $pipelinesubmithost qsub -u onrc -q $pipelinequeue \"$realanalysispath/sge.job\"";
								my $sgeresult = `$systemstring 2>&1`;
								print "SGE submit result [$sgeresult]\n";
								WriteLog("SGE submit result [$sgeresult]");
								my @parts = split(' ', $sgeresult);
								my $jobid = $parts[2];
								WriteLog(join('|',@parts));
								AppendLog($setuplogF, WriteLog("[$systemstring]: " . $sgeresult));
								
								$sqlstring = "update analysis set analysis_status = 'submitted', analysis_statusmessage = 'Submitted to $pipelinequeue', analysis_qsubid = '$jobid' where analysis_id = $analysisRowID";
								AppendLog($setuplogF, WriteLog($sqlstring));
								my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
								
								$numsubmitted = $numsubmitted + 1;
								$jobsWereSubmitted = 1;

								SetPipelineStatusMessage($pid, "Submitted $uid$studynum");
								
								# check if this module should be running now or not
								if (!ModuleCheckIfActive($scriptname, $db)) {
									AppendLog($setuplogF, WriteLog("Not supposed to be running right now. Exiting module"));
									# update the stop time
									ModuleDBCheckOut($scriptname, $db);
									return 0;
								}
							}
							else {
								AppendLog($setuplogF, WriteLog("GetData() returned 0 series"));
								# update the analysis table with the datalog to people can check later on why something didn't process
								my $datalog2 = EscapeMySQLString($datalog);
								my $sqlstringC = "update analysis set analysis_datalog = '$datalog2' where analysis_id = $analysisRowID";
								my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
							}
							AppendLog($setuplogF, WriteLog("Submitted $numsubmitted jobs so far"));
							print "Submitted $numsubmitted jobs so far\n";
						}
						
						# mark the study in the analysis table
						if ($numseries > 0) {
							#$datalog = EscapeMySQLString($datalog);
							if ($rerunresults eq "1") {
								$sqlstring = "update analysis set analysis_status = 'pending' where analysis_id = $analysisRowID";
							}
							else {
								$sqlstring = "update analysis set analysis_status = 'pending', analysis_numseries = $numseries, analysis_enddate = now() where analysis_id = $analysisRowID";
							}
							my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
						}
						else {
							# save some database space, since most entries will be blank
							$sqlstring = "update analysis set analysis_status = 'NoMatchingStudies', analysis_startdate = null where analysis_id = $analysisRowID";
							my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
						}
					}
					else {
						WriteLog("This analysis already has an entry in the analysis table");
					}
					if (!IsPipelineEnabled($pid)) {
						SetPipelineStatusMessage($pid, 'Pipeline disabled while running. Normal stop.');
						SetPipelineStopped($pid, $newrunnum);
						#SetModuleStopped();
						next PIPELINE;
					}
					
					if (($numchecked%1000) == 0) {
						WriteLog("$numchecked studies checked");
					}
				}
			}
		}
		# ======================= LEVEL 2 =======================
		elsif ($pipelinelevel == 2) {
			# --- process second level pipeline ---
			WriteLog("Level 2");
			
			# only 1 analysis should ever be run with the second level, so if 1 already exists, regardless of state or pipeline version, then
			# leave this function without running the analysis
			my $sqlstring = "select * from analysis where pipeline_id = $pid";
			my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
			if ($result->numrows > 0) {
				WriteLog("An analysis already exists for this second level pipeline, exiting pipeline");
				SetPipelineStatusMessage($pid, 'An analysis already exists for this second level pipeline. Delete the analysis if you want to re-run it');
				SetPipelineStopped($pid, $newrunnum);
				next PIPELINE;
			}
		
			my $analysispath;
			my $studypath;
			my $analysisRowID;
			my $pseudogroupanalysispath;
			my $groupanalysispath;
			my $realpipelinedirectory;
			
			if ($pipelinedependency eq "") {
				$pipelinedependency = "0";
			}
			# if there are multiple dependencies, we'll need to loop through all of them separately
			my @deps = split(',', $pipelinedependency);
			foreach my $pipelinedep(@deps) {
			
				# get the dependency name
				my $dependencyname;
				my $dependencylevel;
				if ($pipelinedep != 0) {
					my $sqlstring = "select pipeline_name, pipeline_level from pipelines where pipeline_id = $pipelinedep";
					my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
					if ($result->numrows > 0) {
						WriteLog("Found " . $result->numrows . " series for second level pipeline");
						my %row = $result->fetchhash;
						$dependencyname = $row{'pipeline_name'};
						$dependencylevel = $row{'pipeline_level'};
					}
					else {
						WriteLog("Pipeline dependency ($pipelinedep) does not exist!");
						SetPipelineStopped($pid, $newrunnum);
						SetModuleStopped();
						next PIPELINE;
					}
				}
				
				# build the path
				if ($pipelinedirectory eq "") {
					$pipelinedirectory = $cfg{'groupanalysisdir'};
					$realpipelinedirectory = $pipelinedirectory;
					$realpipelinedirectory =~ s/\/mount//;
					WriteLog("1 [$pipelinedirectory] --> [$realpipelinedirectory]");
					print "1 [$pipelinedirectory] --> [$realpipelinedirectory]\n";
				}
				else {
					$pipelinedirectory = $cfg{'mountdir'} . $pipelinedirectory;
					$realpipelinedirectory = $pipelinedirectory;
					WriteLog("2 [$pipelinedirectory] --> [$realpipelinedirectory]");
					print "2 [$pipelinedirectory] --> [$realpipelinedirectory]\n";
				}
				
				#WriteLog(`mkdir -p $pipelinedirectory/$pipelinename/$dependencyname`);
				MakePath("$pipelinedirectory/$pipelinename/$dependencyname");
				$groupanalysispath = "$pipelinedirectory/$pipelinename";
				#WriteLog(`mkdir -p $groupanalysispath`);
				MakePath($groupanalysispath);
				chmod(0777,$groupanalysispath);
				$pseudogroupanalysispath = $groupanalysispath;
				$pseudogroupanalysispath =~ s/\/mount//;

				MakePath("$groupanalysispath/pipeline");
				chmod(0777,"$groupanalysispath/pipeline");
				
				# check if the groupanalysis has an entry in the analysis_group table
				my $sqlstring = "select * from analysis where pipeline_id = $pid and pipeline_version = $pipelineversion";
				my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
				if ($result->numrows < 1) {
					# insert a temporary row, to be updated later, in the analysis_group table as a placeholder
					# so that no other processes end up running it
					$sqlstring = "insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, analysis_status, analysis_startdate) values ($pid,$pipelineversion,$pipelinedep,'processing',now())";
					my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
					$analysisRowID = $result->insertid;
				}
				else {
					my %row = $result->fetchhash;
					$analysisRowID = $row{'analysis_id'};
				}
				
				# loop through the groups
				$sqlstring = "select * from groups where group_id in ($pipelinegroupids)";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				if ($result->numrows > 0) {
					while (my %row = $result->fetchhash) {
						my $groupname = $row{'group_name'};
						my $groupid = $row{'group_id'};
						# get a list of subjects in the group that have the dependency
						my $sqlstringA = "select a.study_id from studies a left join group_data b on a.study_id = b.data_id where a.study_id in (select study_id from analysis where pipeline_id in ($pipelinedependency) and analysis_status = 'complete' and analysis_isbad <> 1) and (a.study_datetime < date_sub(now(), interval 6 hour)) and b.group_id in ($groupid) order by a.study_datetime desc";
						my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
						WriteLog($sqlstringA);
						my @studyids = ();
						if ($resultA->numrows > 0) {
							while (my %rowA = $resultA->fetchhash) {
								push(@studyids,$rowA{'study_id'});
								#WriteLog("Found study " . $rowA{'study_id'});
							}
						}
						else {
							WriteLog("No studies found [$sqlstringA]");
							SetPipelineStatusMessage($pid, "No studies found (Maybe 1st/2nd level group mismatch?)");
							SetPipelineStopped($pid, $newrunnum);
							SetModuleStopped();
							next PIPELINE;
						}

						foreach my $sid(@studyids) {
							# get information about the study
							my $sqlstringB = "select *, date_format(study_datetime,'%Y%m%d_%H%i%s') 'studydatetime' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = $sid";
							WriteLog($sqlstringB);
							my $resultB = SQLQuery($sqlstringB, __FILE__, __LINE__);
							my %rowB = $resultB->fetchhash;
							my $uid = $rowB{'uid'};
							my $studynum = $rowB{'study_num'};
							my $studydatetime = $rowB{'studydatetime'};
							my $numseries = 0;
							my $datalog;
							if (defined($uid)) {
								WriteLog("StudyDateTime: $studydatetime");
								print "StudyDateTime: $studydatetime\n";
								
								if ($pipelinedirectory eq "") {
									$pipelinedirectory = $cfg{'analysisdir'};
								}
								$analysispath = "$pipelinedirectory/$uid/$studynum/$pipelinename";
								$studypath = "$pipelinedirectory/$uid/$studynum";
								WriteLog("$analysispath");
								
								MakePath("$pipelinedirectory/$pipelinename/$groupname/$dependencyname");
								# create hard link in the analysis directory
								my $systemstring;
								if ($dependencylevel == 1) {
									$systemstring = "cp -aul $cfg{'analysisdir'}/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$uid$studynum";
								}
								else {
									$systemstring = "cp -aul $cfg{'groupanalysisdir'}/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$uid$studynum";
								}
								
								my $cpresults = `$systemstring 2>&1`;
								if (($cpresults =~ /cannot stat/) || ($cpresults =~ /No such file or/) || ($cpresults =~ /error/)) {
									WriteLog($cpresults);
								}
								WriteLog("pwd: [" . getcwd . "], [$systemstring] :" . $cpresults);
							}
						}
					}
				}
			}

			$sqlstring = "select date_format(now(),'%Y%m%d_%H%i%s') 'studydatetime'";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			%row = $result->fetchhash;
			my $studydatetime = $row{'studydatetime'};
				
			# create the SGE job file
			my $sgebatchfile = CreateSGEJobFile($analysisRowID, 1, "GROUPLEVEL", 0, $groupanalysispath, $usetmpdir, $tmpdir, $studydatetime, $pipelinename, $pid, $pipelineremovedata, $pipelineresultscript, @pipelinesteps);
		
			`chmod -Rf 777 $groupanalysispath`;
			WriteLog($sgebatchfile);
			
			# submit the SGE job
			open SGEFILE, "> $pipelinedirectory/$pipelinename/pipeline/sge.job";
			print SGEFILE $sgebatchfile;
			close SGEFILE;
			chmod(0777,$sgebatchfile);
			chmod(0777,"$pipelinedirectory/$pipelinename");

			# submit the sucker to the cluster
			my $systemstring = "ssh $pipelinesubmithost qsub -u onrc -q $pipelinequeue \"$realpipelinedirectory/$pipelinename/pipeline/sge.job\"";
			print "SGE submit string [$systemstring]\n";
			WriteLog("SGE submit string [$systemstring]");
			my $sgeresult = `$systemstring 2>&1`;
			print "SGE submit result [$sgeresult]\n";
			WriteLog("SGE submit result [$sgeresult]");
			my @parts = split(' ', $sgeresult);
			my $jobid = $parts[2];
			WriteLog(join('|',@parts));
			WriteLog("[$systemstring]: " . $sgeresult);
			
			if ($sgeresult =~ /error/) {
				$sqlstring = "update analysis set analysis_qsubid = '$jobid', analysis_status = 'error', analysis_statusmessage = 'Error submitting to $pipelinequeue', analysis_enddate = now() where analysis_id = $analysisRowID";
			}
			else {
				$sqlstring = "update analysis set analysis_qsubid = '$jobid', analysis_status = 'submitted', analysis_statusmessage = 'Submitted to $pipelinequeue', analysis_enddate = now() where analysis_id = $analysisRowID";
			}
			$jobsWereSubmitted = 1;
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			
			# check if this module should be running now or not
			if (!ModuleCheckIfActive($scriptname, $db)) {
				WriteLog("Not supposed to be running right now");
				# update the stop time
				ModuleDBCheckOut($scriptname, $db);
				return 0;
			}
			
			# a second level pipeline should run once, so disable it after submitting
			SetPipelineDisabled($pid);
		}
		else {
			WriteLog("Pipeline level invalid");
		}
		
		print "Done with pipeline [$pid] - [$pipelinename]\n";
		WriteLog("Done with pipeline [$pid] - [$pipelinename]");
		WriteLog("Done");
		SetPipelineStatusMessage($pid, 'Normal stop.');
		SetPipelineStopped($pid, $newrunnum);
		
		if ($jobsWereSubmitted) {
			SetModuleStopped();
			return 1;
		}
	}

	SetPipelineProcessStatus('complete',0,0);
	
	# end the module and return the code
	SetModuleStopped();
	if ($jobsWereSubmitted) { return 1; }
	else { return 0; }
}


# ----------------------------------------------------------
# --------- IsQueueFilled ----------------------------------
# ----------------------------------------------------------
sub IsQueueFilled() {
	my ($pid) = @_;

	# connect to the DB (in case it became disconnected)
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");

	# check if this module should be running now or not
	my $sqlstring = "select * from modules where module_name = '$scriptname' and module_isactive = 1";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows < 1) {
		WriteLog("Module disabled. Stopping execution");
		print "Module disabled. Stopping execution\n";
		SetPipelineStopped($pid, 0);
		# update the stop time
		SetModuleStopped();
		return 2;
	}			

	# find out how many processes are allowed to run
	my $numprocallowed = 0;
	$sqlstring = "select pipeline_enabled, pipeline_numproc from pipelines where pipeline_id = $pid";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$numprocallowed = $row{'pipeline_numproc'};
	}
	
	# find out how many processes are actually running
	my $numprocrunning = 0;
	$sqlstring = "select count(*) 'count' from analysis where pipeline_id = $pid and (analysis_status = 'processing' or analysis_status = 'started' or analysis_status = 'submitted' or analysis_status = 'pending')";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$numprocrunning = $row{'count'};
	}
	
	if ($numprocrunning >= $numprocallowed) {
		return 1;
	}
	else {
		return 0;
	}
}


# ----------------------------------------------------------
# --------- CreateSGEJobFile -------------------------------
# ----------------------------------------------------------
sub CreateSGEJobFile() {
	my ($analysisid, $isgroup, $uid, $studynum, $analysispath, $usetmpdir, $tmpdir, $studydatetime, $pipelinename, $pipelineid, $removedata, $resultscript, @pipelinesteps) = @_;

	# (re)connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	# check if this analysis only needs part of it rerun, and not the whole thing
	my $sqlstring = "select * from analysis where analysis_id = $analysisid";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	my %row = $result->fetchhash;
	my $rerunresults = trim($row{'analysis_rerunresults'});
	WriteLog("ReRunResults: [$rerunresults]");
	
	my $checkinscript = "analysischeckin.pl";
	my $jobfile = "";
	my $realanalysispath = "$analysispath";
	
	my $workinganalysispath = "$tmpdir/$pipelinename-$analysisid";
	
	WriteLog("Analysis path: $analysispath");
	print "Analysis path: $analysispath\n";
	WriteLog("Working Analysis path (temp directory): $workinganalysispath");
	print "Working Analysis path (temp dir): $workinganalysispath\n";
	
	$jobfile .= "#!/bin/sh\n";
	$jobfile .= "#\$ -N $pipelinename\n";
	$jobfile .= "#\$ -S /bin/sh\n";
	$jobfile .= "#\$ -j y\n";
	$jobfile .= "#\$ -o $analysispath/pipeline\n";
	$jobfile .= "#\$ -V\n";
	$jobfile .= "#\$ -u onrc\n\n";
	$jobfile .= "echo Hostname: `hostname`\n";
	$jobfile .= "echo Hostname: `whoami`\n\n";
	if ((trim($resultscript) ne "") && ($rerunresults)) {
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid startedrerun 'Cluster processing started'\n";
	}
	else {
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid started 'Cluster processing started'\n";
	}
	$jobfile .= "cd $analysispath;\n";
	if ($usetmpdir) {
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid started 'Beginning data copy to /tmp'\n";
		$jobfile .= "mkdir -pv $workinganalysispath\n";
		$jobfile .= "cp -Rv $analysispath/* $workinganalysispath/\n";
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid started 'Done copying data to /tmp'\n";
	}

	# check if any of the variables might be blank
	if (trim($analysispath) eq "") { return ""; }
	if (trim($workinganalysispath) eq "") { return ""; }
	if (trim($analysisid) eq "") { return ""; }
	if (trim($uid) eq "") { return ""; }
	if (trim($studynum) eq "") { return ""; }
	if (trim($studydatetime) eq "") { return ""; }
	
	chdir($realanalysispath);
	if (!$rerunresults) {
		# go through list of data search criteria
		foreach my $i(0..$#pipelinesteps) {
			my $id = $pipelinesteps[$i]{'id'};
			my $order = $pipelinesteps[$i]{'order'};
			my $command = trim($pipelinesteps[$i]{'command'});
			my $workingdir = trim($pipelinesteps[$i]{'workingdir'});
			my $description = trim($pipelinesteps[$i]{'description'});
			my $logged = $pipelinesteps[$i]{'logged'};
			my $enabled = $pipelinesteps[$i]{'enabled'};

			if (($command =~ m/\{NOLOG\}/) || ($description =~ m/\{NOLOG\}/)) { $logged = 0; }
			
			# format the command (replace pipeline variables, etc)
			if ($usetmpdir) {
				$command = FormatCommand($pipelineid, $realanalysispath, $command, $workinganalysispath, $analysisid, $uid, $studynum, $studydatetime, $pipelinename, $workingdir, $description);
			}
			else {
				$command = FormatCommand($pipelineid, $realanalysispath, $command, $analysispath, $analysisid, $uid, $studynum, $studydatetime, $pipelinename, $workingdir, $description);
			}
			if (($command =~ m/\{NOCHECKIN\}/) || ($description =~ m/\{NOCHECKIN\}/)) { }
			else {
				$jobfile .= "\nperl /opt/pipeline/$checkinscript $analysisid processing 'processing step " . ($i + 1) . " of " . ($#pipelinesteps + 1) . "'\n# $description\necho Running $command\n";
			}
			
			# write to a log file if logging is requested
			if ($logged) { $command .= " > $analysispath/pipeline/step$i.log 2>&1"; }
			
			if (trim($workingdir) ne "") { $jobfile .= "cd $workingdir;\n"; }
			if (!$enabled) { $jobfile .= "# "; }
			
			$jobfile .= "$command\n";
		}
	}
	if ($usetmpdir) {
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid started 'Copying data from temp dir'\n";
		$jobfile .= "cp -Ruv $workinganalysispath/* $analysispath/\n";
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid started 'Deleting temp dir'\n";
		$jobfile .= "rm --preserve-root -rv $workinganalysispath\n";
	}
	
	if ((trim($resultscript) ne "") && ($rerunresults)) {
		#$jobfile .= "env\n";
		# tack on the result script command
		my $resultcommand = FormatCommand($pipelineid, $realanalysispath, $resultscript, $analysispath, $analysisid, $uid, $studynum, $studydatetime, $pipelinename, '', '');
		$resultcommand .= " > $analysispath/pipeline/stepResultScript.log 2>&1";
		$jobfile .= "\nperl /opt/pipeline/$checkinscript $analysisid processing 'Processing result script'\n# Running result script\necho Running $resultcommand\n";
		$jobfile .= "$resultcommand\n";
		
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid completererun 'Results re-run complete'\n";
		$jobfile .= "chmod -Rf 777 $analysispath";
	}
	if (!$rerunresults) {
		# run the results import script
		my $resultcommand = FormatCommand($pipelineid, $realanalysispath, $resultscript, $analysispath, $analysisid, $uid, $studynum, $studydatetime, $pipelinename, '', '');
		$resultcommand .= " > $analysispath/pipeline/stepResultScript.log 2>&1";
		$jobfile .= "\nperl /opt/pipeline/$checkinscript $analysisid processing 'Processing result script'\n# Running result script\necho Running $resultcommand\n";
		$jobfile .= "$resultcommand\n";
	
		# clean up and log everything
		$jobfile .= "chmod -Rf 777 $analysispath\n";
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid complete 'Updating analysis files'\n";
		$jobfile .= "perl /opt/pipeline/UpdateAnalysisFiles.pl -a $analysisid -d $analysispath\n";
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid complete 'Checking for completed files'\n";
		$jobfile .= "perl /opt/pipeline/CheckCompleteResults.pl -a $analysisid -d $analysispath\n";
		
		# check if the downloaded data should be deleted upon script completion
		if ($removedata == 1) {
			$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid complete 'deleting downloaded data...'\n";
			$jobfile .= "rm --preserve-root -r $analysispath/data\n";
		}
			
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid complete 'Cluster processing complete'\n";
		$jobfile .= "chmod -Rf 777 $analysispath";
	}
	
	return $jobfile;
}


# ----------------------------------------------------------
# --------- FormatCommand ----------------------------------
# ----------------------------------------------------------
sub FormatCommand() {
	my ($pipelineid, $realanalysispath, $command, $analysispath, $analysisid, $uid, $studynum, $studydatetime, $pipelinename, $workingdir, $description) = @_;

		$command =~ s/\{NOLOG\}//g; # remove any {NOLOG} commands
		$command =~ s/\x0D//g; # remove any ^M characters
		$command =~ s/\{analysisrootdir\}/$analysispath/g;
		$command =~ s/\{analysisid\}/$analysisid/g;
		$command =~ s/\{subjectuid\}/$uid/g;
		$command =~ s/\{studynum\}/$studynum/g;
		$command =~ s/\{uidstudynum\}/$uid$studynum/g;
		$command =~ s/\{studydatetime\}/$studydatetime/g;
		$command =~ s/\{pipelinename\}/$pipelinename/g;
		$command =~ s/\{workingdir\}/$workingdir/g;
		$command =~ s/\{description\}/$description/g;
		
		# expand {groups}
		my @groups = GetGroupList($pipelineid);
		#WriteLog("@groups");
		my $grouplist = join(' ',@groups);
		#WriteLog("Group list: $grouplist");
		#WriteLog("Replacing '{groups}' with '$grouplist'");
		$command =~ s/\{groups\}/$grouplist/g;
		
		my @alluidstudynums;
		foreach my $group(@groups) {
			# {numsubjects_groupname}
			# {uidstudynums_groupname}
			my @uidStudyNums = GetUIDStudyNumListByGroup($group);
			push(@alluidstudynums,@uidStudyNums);
			my $uidlist = join(' ',@uidStudyNums);
			my $numuids = $#uidStudyNums+1;
			#WriteLog("Replacing '{uidstudynums_$group}' with '$uidlist'");
			$command =~ s/\{uidstudynums_$group\}/$uidlist/g;
			#WriteLog("Replacing '{numsubjects_$group}' with '$numuids'");
			$command =~ s/\{numsubjects_$group\}/$numuids/g;
		}
		my $alluidlist = join(' ',@alluidstudynums);
		my $numsubjects = $#alluidstudynums+1;
		#WriteLog("Replacing '{uidstudynums}' with '$alluidlist'");
		$command =~ s/\{uidstudynums\}/$alluidlist/g;
		#WriteLog("Replacing '{numsubjects}' with '$numsubjects'");
		$command =~ s/\{numsubjects\}/$numsubjects/g;
		
		#WriteLog("Command (check0): [$command]");
		if ($command =~ m/\s+(\S*)\{first_(.*)_file\}/) {
			#WriteLog("Command (check1): [$command]");
			my $path = $1;
			my $ext = $2;
			my $searchpath = "$realanalysispath/$path*.$ext";
			WriteLog("Searchpath: [$searchpath]");
			my @files = glob $searchpath;
			my $replacement = $files[0];
			$replacement =~ s/$realanalysispath/$analysispath/g;
			$command =~ s/\s+(\S*)\{first_(.*)_file\}/ $replacement/g;
		}
		if ($command =~ m/\s+(\S*)\{first_(\d+)_(.*)_files\}/) {
			#WriteLog("Command (check2): [$command]");
			my $path = $1;
			my $numfiles = $2;
			my $ext = $3;
			my $searchpath = "$realanalysispath/$path*.$ext";
			my @files = glob $searchpath;
			my $replacement = "";
			foreach my $j (0..$numfiles - 1) {
				$replacement .= " ".$files[$j];
			}
			$command = s/\s+(\S*)\{first_(\d+)_(.*)_file\}/ $replacement/g;
		}
		if ($command =~ m/ (.*)\{last_(.*)_file\}/) {
			#WriteLog("Command (check3): [$command]");
			my $path = $1;
			my $ext = $2;
			my $searchpath = "$realanalysispath/$path*.$ext";
			my @files = glob $searchpath;
			my $replacement = $files[-1];
			$command = s/\s+(\S*)\{last_(.*)_file\}/ $replacement/g;
		}
		#WriteLog("Command (check4): [$command]");
		$command =~ s/\{command\}/$command/g;
		#WriteLog("Command (check5): [$command]");
		
		# remove semi-colon from the end of the line in case its there (it will prevent logging)
		if (substr($command,-1,1) eq ";") {
			chop($command);
		}
		#WriteLog("Command (check6): [$command]");

		return $command;
}


# ----------------------------------------------------------
# --------- GetGroupList -----------------------------------
# ----------------------------------------------------------
sub GetGroupList() {
	my ($pid) = @_;

	my @grouplist;
	
	# connect to the database
	DatabaseConnect();
	
	# get list of groups associated with this pipeline
	my $sqlstring = "select pipeline_groupid from pipelines where pipeline_id = $pid";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		my $groupid = trim($row{'pipeline_groupid'});
		
		if ($groupid ne '') {
			my $sqlstringA = "select group_name from groups where group_id in ($groupid)";
			#WriteLog($sqlstringA);
			my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
			if ($resultA->numrows > 0) {
				while (my %rowA = $resultA->fetchhash) {
					my $groupname = trim($rowA{'group_name'});
					#WriteLog("Pushing $groupname onto @grouplist");
					push(@grouplist,$groupname);
				}
			}
		}
	}
	
	return @grouplist;
}


# ----------------------------------------------------------
# --------- GetUIDStudyNumListByGroup ----------------------
# ----------------------------------------------------------
sub GetUIDStudyNumListByGroup() {
	my ($group) = @_;

	my @uidlist;
	
	# connect to the database
	DatabaseConnect();
	
	# get list of groups associated with this pipeline
	my $sqlstring = "select concat(uid,cast(study_num as char)) 'uidstudynum' from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = (select group_id from groups where group_name = '$group') group by d.uid order by d.uid,b.study_num";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		while (my %row = $result->fetchhash) {
			my $uidstudynum = trim($row{'uidstudynum'});
			push(@uidlist,$uidstudynum);
		}
	}
	
	return @uidlist;
}

# ----------------------------------------------------------
# --------- GetData ----------------------------------------
# ----------------------------------------------------------
sub GetData() {
	my ($studyid, $analysispath, $uid, $analysisid, $pipelinedataand, @datadef) = @_;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $numdownloaded = 0;
	my $datalog = "";
	my $datareport = "";
	WriteLog("Inside GetData($analysispath)");
	
	# get list of series for this study
	my $sqlstring = "select study_modality, study_num from studies where study_id = $studyid";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		WriteLog("Found " . $result->numrows . " studies matching studyid [$studyid]");
		$datalog .= "Found " . $result->numrows . " studies matching studyid [$studyid]\n";
		my %row = $result->fetchhash;
		my $modality = $row{'study_modality'};
		my $studynum = $row{'study_num'};
		
		WriteLog("Study modality is [$modality]");
		WriteLog("Data AND: $pipelinedataand");
		$datalog .= "Study modality is [$modality]\nData AND: $pipelinedataand";
		# if this data specification is an 'and', check it first. if this study doesn't have at least 1 of each of the protocols specified
		# in the steps, then leave the function and don't download any data
		if ($pipelinedataand == 1) {
			$datalog .= "This is an AND data specification... checking to see if all required primary data exists in the study.\n";
			WriteLog("This is an AND data spec. Checking to see if ALL data is found for this study");
			#$datareport .= "This pipeline uses an AND data specification\n";
			foreach my $i(0..$#datadef) {
				my $protocol = $datadef[$i]{'protocol'};
				my $modality = $datadef[$i]{'modality'};
				my $imagetype = $datadef[$i]{'imagetype'};
				my $enabled = $datadef[$i]{'enabled'};
				my $type = $datadef[$i]{'type'};
				# seperate any protocols that have multiples
				my $protocols;
				if ($protocol =~ /\"/) {
					my @prots = shellwords($protocol);
					$protocols = "'" . join("','", @prots) . "'";
				}
				else {
					$protocols = "'$protocol'";
				}
				
				WriteLog("Checking data spec, step [$i]");
				$datalog .= "Checking data spec, step [$i]\n";
				if (!$enabled) {
					WriteLog("Data specification is NOT enabled");
					$datalog .= "Data specification step is not enabled\n";
					next;
				}
				else {
					WriteLog("Data specification is enabled");
				}
				
				if ($type eq 'primary') {
					$modality = lc($modality);
					# make sure the requested modality table exists
					$sqlstring = "show tables like '$modality"."_series'";
					my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
					if ($result->numrows > 0) {
					
						# get a list of series satisfying the search criteria, if it exists
						$sqlstring = "select * from $modality"."_series where study_id = $studyid and (series_desc in ($protocols) or series_protocol in ($protocols)) and image_type like '%$imagetype%'";
						WriteLog($sqlstring);
						$datalog .= "[$sqlstring]\n";
						my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
						if ($result->numrows < 1) {
							$datalog .= "This study did NOT contain step [$i]: [$protocol] [$modality] [$imagetype]\n";
							WriteLog("This study did NOT contain step [$i]: [$protocol] [$modality] [$imagetype]");
							return (0, $datalog);
						}
						else {
							WriteLog("This study contained step [$i]: [$protocol] [$modality] [$imagetype]");
							$datalog .= "This study contained step [$i]: [$protocol] [$modality] [$imagetype]\n";
						}
					}
					else {
						WriteLog("The [$modality"."_series] table does not exist");
						$datalog .= "The [$modality"."_series] table does not exist\n";
					}
				}
			}
			WriteLog("All protocols were found in this study");
			$datalog .= "All protocols were found in this study\n";
		}
		else {
			WriteLog("This is a data OR spec. Checking to see if at least one of the data steps exists");
			$datalog .= "This is a data OR specification\n";
			# check to make sure at least some of the primary data exists
			my $primaryDataFound = 0;
			foreach my $i(0..$#datadef) {
				my $protocol = $datadef[$i]{'protocol'};
				my $modality = $datadef[$i]{'modality'};
				my $imagetype = $datadef[$i]{'imagetype'};
				my $enabled = $datadef[$i]{'enabled'};
				my $type = $datadef[$i]{'type'};
				# seperate any protocols that have multiples
				my $protocols;
				if ($protocol =~ /\"/) {
					my @prots = shellwords($protocol);
					$protocols = "'" . join("','", @prots) . "'";
				}
				else {
					$protocols = "'$protocol'";
				}
				
				WriteLog("Checking data spec, step [$i]");
				
				if (!$enabled) {
					WriteLog("Data specification is NOT enabled");
					$datalog .= "Data specification step [$] is NOT enabled\n";
					next;
				}
				else {
					WriteLog("Data specification is enabled");
				}
				
				WriteLog("This protocol [$protocol] is type [$type], modality [$modality]");
				$datalog .= "This protocol [$protocol] is type [$type], modality [$modality]\n";
				if ($type eq 'primary') {
					WriteLog("This protocol [$protocol] is primary. type: [$type]");
					$datalog .= "This protocol [$protocol] is primary. type: [$type]\n";
					$modality = lc($modality);
					# make sure the requested modality table exists
					$sqlstring = "show tables like '$modality"."_series'";
					my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
					if ($result->numrows > 0) {
					
						# get a list of series satisfying the search criteria, if it exists
						$sqlstring = "select * from $modality"."_series where study_id = $studyid and (series_desc in ($protocols) or series_protocol in ($protocols) and image_type like '%$imagetype%')";
						WriteLog($sqlstring);
						$datalog .= "[$sqlstring]\n";
						my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
						if ($result->numrows > 0) {
							WriteLog("This study contained step [$i]: [$protocol] [$modality] [$imagetype]");
							$datalog .= "This study contained [$protocol]\n";
							$primaryDataFound = 1;
							last;
						}
						else {
							$datalog .= "This study did not contain [$protocol]\n";
							WriteLog("This study did NOT contain step [$i]: [$protocol] [$modality] [$imagetype]");
						}
					}
					else {
						$datalog .= "Modality [$modality] not found\n";
						WriteLog("Modality [$modality] not found");
					}
				}
				else {
					$datalog .= "This type [$type] is not primary\n";
					WriteLog("This type [$type] is not primary");
				}
			}
			if (!$primaryDataFound) {
				return (0, $datalog, $datareport);
			}
			else {
				WriteLog("Primary data was found for study [$studyid]");
			}
		}
		
		# by this point we can assume primary data exists, so associated data will downloaded just like regular data
		
		WriteLog("Modality: $modality");
		
		# go through list of data search criteria
		foreach my $i(0..$#datadef) {
			my $id = $datadef[$i]{'id'};
			my $order = $datadef[$i]{'order'};
			my $criteria = $datadef[$i]{'criteria'};
			my $type = $datadef[$i]{'type'};
			my $assoctype = $datadef[$i]{'assoctype'};
			my $protocol = $datadef[$i]{'protocol'};
			my $modality = $datadef[$i]{'modality'};
			my $dataformat = $datadef[$i]{'dataformat'};
			my $imagetype = $datadef[$i]{'imagetype'};
			my $gzip = $datadef[$i]{'gzip'};
			my $location = $datadef[$i]{'location'};
			my $useseries = $datadef[$i]{'useseries'};
			my $preserveseries = $datadef[$i]{'preserveseries'};
			my $behformat = $datadef[$i]{'behformat'};
			my $behdir = $datadef[$i]{'behdir'};
			my $enabled = $datadef[$i]{'enabled'};
			# seperate any protocols that have multiples
			my $protocols;
			if ($protocol =~ /\"/) {
				my @prots = shellwords($protocol);
				$protocols = "'" . join("','", @prots) . "'";
			}
			else {
				$protocols = "'$protocol'";
			}
			WriteLog("Working on step [$i]: $id");

			# check to see if we should even run this step
			if ($enabled) {
				WriteLog("---------- Checking for $protocol for $modality ----------");
				$datalog .= "---------- Checking for protocol [$protocol] modality [$modality] imagetype [$imagetype] ----------\n";
				$modality = lc($modality);
				# make sure the requested modality table exists
				$sqlstring = "show tables like '$modality"."_series'";
				my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
				if ($result->numrows > 0) {
				
					# get a list of series satisfying the search criteria, if it exists
					if ($type eq 'primary') {
						WriteLog("This data step is PRIMARY [$protocol], criteria: [$criteria]");
						$datalog .= "This data step is PRIMARY [$protocol] criteria: [$criteria]\n";
						if ($criteria eq 'first') {
							$sqlstring = "select * from $modality"."_series where study_id = $studyid and series_desc in ($protocols) and image_type like '%$imagetype%' order by series_num asc limit 1";
						}
						elsif ($criteria eq 'last') {
							$sqlstring = "select * from $modality"."_series where study_id = $studyid and series_desc in ($protocols) and image_type like '%$imagetype%' order by series_num desc limit 1";
						}
						elsif ($criteria eq 'largestsize') {
							$sqlstring = "select * from $modality"."_series where study_id = $studyid and series_desc in ($protocols) and image_type like '%$imagetype%' order by series_size desc, numfiles desc, img_slices desc limit 1";
						}
						elsif ($criteria eq 'smallestsize') {
							$sqlstring = "select * from $modality"."_series where study_id = $studyid and series_desc in ($protocols) and image_type like '%$imagetype%' order by series_size asc, numfiles asc, img_slices asc limit 1";
						}
						else {
							$sqlstring = "select * from $modality"."_series where study_id = $studyid and series_desc in ($protocols) and image_type like '%$imagetype%' order by series_num asc";
						}
					}
					else {
						WriteLog("This data step is ASSOCIATED [$protocol], association type [$assoctype]");
						# get the subject ID and study type, based on the current study ID
						my $sqlstringA = "select b.subject_id, a.study_type, a.study_datetime from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where a.study_id = $studyid";
						my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
						WriteLog($sqlstringA);
						if ($resultA->numrows > 0) {
							my %rowA = $resultA->fetchhash;
							my $subjectid = $rowA{'subject_id'};
							my $studytype = $rowA{'study_type'};
							my $studydate = $rowA{'study_datetime'};
							
							if ($assoctype eq 'nearesttime') {
								# find the data from the same subject and modality that has the nearest (in time) matching scan
								WriteLog("Searching for associated data nearest in time...");
								$datalog .= "    Searching for associated data nearest in time\n";
								$sqlstring = "SELECT *, `$modality" . "_series`.$modality" . "series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `$modality" . "_series` on `$modality" . "_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '$modality' AND `subjects`.subject_id = $subjectid AND `$modality" . "_series`.series_desc = '$protocol' and `$modality" . "_series`.image_type like '%$imagetype%' ORDER BY ABS( DATEDIFF( `$modality" . "_series`.series_datetime, '$studydate' ) ) LIMIT 1";
							}
							elsif ($assoctype eq 'all') {
								WriteLog("Searching for all associated data...");
								$datalog .= "    Searching for all associated data\n";
								$sqlstring = "SELECT *, `$modality" . "_series`.$modality" . "series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `$modality" . "_series` on `$modality" . "_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '$modality' AND `subjects`.subject_id = $subjectid AND `$modality" . "_series`.series_desc = '$protocol' and `$modality" . "_series`.image_type like '%$imagetype%'";
							}
							else {
								# find the data from the same subject and modality that has the same study_type
								WriteLog("Searching for associated data with same study type...");
								$datalog .= "    Searching for associated data with same study type\n";
								$sqlstring = "SELECT *, `$modality" . "_series`.$modality" . "series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `$modality" . "_series` on `$modality" . "_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '$modality' AND `subjects`.subject_id = $subjectid AND `$modality" . "_series`.series_desc = '$protocol' and `$modality" . "_series`.image_type like '%$imagetype%' and `studies`.study_type = '$studytype'";
							}
						}
					}
					WriteLog($sqlstring);
					my $newseriesnum = 1;
					my $resultC = SQLQuery($sqlstring, __FILE__, __LINE__);
					if ($resultC->numrows > 0) {
					
						WriteLog("Found " . $resultC->numrows . " matching associated series");
						$datalog .= "$type data found [" . $resultC->numrows . "] rows, creating [$analysispath]\n";
						# in theory, data for this analysis exists for this study, so lets now create the analysis directory
						MakePath($analysispath);
						while (my %rowC = $resultC->fetchhash) {
							$numdownloaded = $numdownloaded+1;
							my $localstudynum;
							WriteLog("NumDownloaded: $numdownloaded");
							my $seriesid = $rowC{$modality.'series_id'};
							my $seriesnum = $rowC{'series_num'};
							my $seriesdesc = $rowC{'series_desc'};
							my $seriesdatetime = $rowC{'series_datetime'};
							my $datatype = $rowC{'data_type'};
							my $seriessize = $rowC{'series_size'};
							my $numfiles = $rowC{'numfiles'};
							if ($type eq 'primary') {
								# studynum is not returned as part of this current result set, so reuse the studynum from outside this
								# resultset loop
								$localstudynum = $studynum;
							}
							else {
								$localstudynum = $rowC{'study_num'};
							}
							WriteLog("Processing $seriesdesc [$seriessize bytes] [$numfiles files]");
							$datalog .= "Processing [$seriesdesc] series [$seriesnum] datetime [$seriesdatetime]...\n";
							my $behoutdir;
							my $indir = "$cfg{'archivedir'}/$uid/$localstudynum/$seriesnum/$datatype";
							my $behindir = "$cfg{'archivedir'}/$uid/$localstudynum/$seriesnum/beh";
							
							# start building the analysis path
							my $newanalysispath = $analysispath . "/$location";
							
							# check if the series numbers are used, and if so, are they preserved
							if ($useseries) {
								if (!$preserveseries) {
									# renumber the series
									$newanalysispath = $newanalysispath . "/$newseriesnum";
									
									if ($behformat eq "behroot") { $behoutdir = "$analysispath/$location"; }
									if ($behformat eq "behrootdir") { $behoutdir = "$analysispath/$location/$behdir"; }
									if ($behformat eq "behseries") { $behoutdir = "$analysispath/$location/$newseriesnum"; }
									if ($behformat eq "behseriesdir") { $behoutdir = "$analysispath/$location/$newseriesnum/$behdir"; }
									
									$newseriesnum=$newseriesnum+1;
								}
								else {
									$newanalysispath = $newanalysispath . "/$seriesnum";
									if ($behformat eq "behroot") { $behoutdir = "$analysispath/$location"; }
									if ($behformat eq "behrootdir") { $behoutdir = "$analysispath/$location/$behdir"; }
									if ($behformat eq "behseries") { $behoutdir = "$analysispath/$location/$seriesnum"; }
									if ($behformat eq "behseriesdir") { $behoutdir = "$analysispath/$location/$seriesnum/$behdir"; }
								}
							}
							
							$datalog .= "    Creating directory [$newanalysispath]\n";
							MakePath($newanalysispath);
							#mkpath($newanalysispath, {mode => 0777});
							my $systemstring = "chmod -Rf 777 $newanalysispath";
							$datalog .= "    Running [$systemstring]\n";
							`$systemstring 2>&1`;
							
							# output the correct file type
							if (($dataformat eq "dicom") || (($datatype ne "dicom") && ($datatype ne "parrec"))) {
								$systemstring = "cp $indir/* $newanalysispath";
								$datalog .= "    Running [$systemstring]\n";
								`$systemstring 2>&1`;
							}
							else {
								my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
								MakePath($tmpdir);
								#mkpath($tmpdir, {mode => 0777});
								$datalog .= "    Created temp directory [$tmpdir]\n";
								$datalog .= "    Calling ConvertDicom($dataformat, $indir, $tmpdir, $gzip, $uid, $localstudynum, $seriesnum, $datatype)\n";
								ConvertDicom($dataformat, $indir, $tmpdir, $gzip, $uid, $localstudynum, $seriesnum, $datatype);
								
								$systemstring = "cp $tmpdir/* $newanalysispath";
								$datalog .= "    Running [$systemstring]\n";
								`$systemstring 2>&1`;
								
								WriteLog("Removing temp directory [$tmpdir]");
								$datalog .= "    Removing temp directory [$tmpdir]\n";
								remove_tree($tmpdir);
							}
						
							
							# copy the beh data
							if ($behformat ne "behnone") {
								$datalog .= "  Copying behavioral data...\n";
								$datalog .= "    Creating directory [$behoutdir]\n";
								MakePath($behoutdir);
								#mkpath($behoutdir, {mode => 0777});
								$systemstring = "cp -R $behindir/* $behoutdir";
								$datalog .= "    Running [$systemstring]\n";
								`$systemstring 2>&1`;
								
								$systemstring = "chmod -Rf 777 $behoutdir";
								$datalog .= "    Running [$systemstring]\n";
								`$systemstring 2>&1`;
								$datalog .= "  Done copying behavioral data...\n";
							}

							$datalog .= "  Giving full read/write permissions to all users...\n";
							# give full read/write permissions to everyone
							$systemstring = "chmod -Rf 777 $newanalysispath";
							$datalog .= "    Running [$systemstring]\n";
							`$systemstring 2>&1`;
							
							WriteLog("Done writing data to $newanalysispath");
							
							$datalog .= "Done writing data to $newanalysispath\n";
						}
					}
					else {
						WriteLog("Found no matching associated [$protocol] series. SQL: [$sqlstring]");
						$datalog .= "Found no matching associated data [$protocol]. SQL: [$sqlstring]\n";
					}
				}
				else {
					WriteLog("No matching modality [$modality] tables found");
					$datalog .= "No matching modality [$modality] tables found\n";
				}
			}
			else {
				WriteLog("Data step [$i] not enabled");
				$datalog .= "This data item [$protocol] is not enabled\n";
			}
		}
		WriteLog("Leaving GetData() successfully => ret($numdownloaded, $datalog)");
		return ($numdownloaded, $datalog, $datareport);
	}
	else {
		$datareport .= "Study [$studyid] does not exist";
		$datalog .= "No data to download";
		WriteLog("Leaving GetData() unsuccessfully => ret(0, $datalog). This study had no series at all");
		return (0,$datalog, $datareport);
	}
}


# ----------------------------------------------------------
# --------- GetPipelineList --------------------------------
# ----------------------------------------------------------
sub GetPipelineList() {
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	# get list of enabled pipelines
	my $sqlstring = "select * from pipelines where pipeline_enabled = 1 order by pipeline_createdate asc";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my @list;
		while (my %row = $result->fetchhash) {
			push(@list, $row{'pipeline_id'});
			#print $row{'pipeline_id'} . "\n";
		}
		WriteLog("Found " . $#list+1 . " pipelines that are enabled");
		return \@list;
	}
	else {
		WriteLog("Found no pipelines that are enabled");
		return ();
	}
}


# ----------------------------------------------------------
# --------- GetStudyToDoList -------------------------------
# ----------------------------------------------------------
sub GetStudyToDoList() {
	my ($pipelineid, $modality, $depend, $groupids) = @_;

	# make the WriteLog()s happy:
	$groupids = $groupids . "";
	$modality = $modality . "";
	WriteLog("In GetStudyToDoList($pipelineid, $modality, $depend, $groupids). This step simply checks for studies that have not already been flagged as being 'checked'");
	# connect to the database
	DatabaseConnect();
	my $dbh = DBI->connect("dbi:mysql:database=$cfg{'mysqldatabase'};host=$cfg{'mysqlhost'}", $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) or die $DBI::errstr;
	
	my $sqlstring;
	
	# get list of studies which do not have an entry in the analysis table
	if (($depend ne '') && ($depend != 0)) {
		# there is a dependency
		# need to check if ANY of the subject's studies have the dependency...
		# step 1) get list of SUBJECTs who have completed the dependency 
		my $studyidlist = "select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id in (select a.subject_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.study_id in (select study_id from analysis where pipeline_id in ($depend) and analysis_status = 'complete' and analysis_isbad <> 1))";
		# step 2) then find all STUDIES that those subjects have completed
		if ($groupids eq "") {
			# with no groupids
			$sqlstring = "select study_id from studies where study_id not in (select study_id from analysis where pipeline_id = $pipelineid) and study_id in ($studyidlist) and (study_datetime < date_sub(now(), interval 6 hour)) order by study_datetime desc";
			WriteLog("GetStudyToDoList(): dependency and no groupids [$sqlstring]");
		}
		else {
			# with groupids
			$sqlstring = "select a.study_id from studies a left join group_data b on a.study_id = b.data_id where a.study_id not in (select study_id from analysis where pipeline_id = $pipelineid) and a.study_id in ($studyidlist) and (a.study_datetime < date_sub(now(), interval 6 hour)) and b.group_id in ($groupids) order by a.study_datetime desc";
			WriteLog("GetStudyToDoList(): dependency and groupids [$sqlstring]");
		}
	}
	else {
		# no dependency
		if ($groupids eq "") {
			# with no groupids
			$sqlstring = "select study_id from studies where study_id not in (select study_id from analysis where pipeline_id = $pipelineid) and (study_datetime < date_sub(now(), interval 6 hour)) and study_modality = '$modality' order by study_datetime desc";
			WriteLog("GetStudyToDoList(): No dependency and no groupids [$sqlstring]");
		}
		else {
			# with groupids
			$sqlstring = "SELECT a.study_id FROM studies a left join group_data b on a.study_id = b.data_id WHERE a.study_id NOT IN (SELECT study_id FROM analysis WHERE pipeline_id = $pipelineid) AND ( a.study_datetime < DATE_SUB( NOW( ) , INTERVAL 6 hour )) AND a.study_modality =  '$modality' and b.group_id in ($groupids) ORDER BY a.study_datetime DESC";
			WriteLog("GetStudyToDoList(): No dependency and groupids [$sqlstring]");
		}
	}
	
	my @list = ();
	my $result = $dbh->prepare($sqlstring);
	$result->execute();
	WriteLog("Pushing all the studyids onto an array");
	if ($result->rows > 0) {
		while (my $row = $result->fetchrow_hashref()) {
			my $studyid = $row->{study_id};
			
			my $sqlstringA = "select b.study_num, c.uid from enrollment a left join studies b on a.enrollment_id = b.enrollment_id left join subjects c on a.subject_id = c.subject_id where b.study_id = $studyid";
			my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
			#WriteLog($sqlstringA);
			my %rowA = $resultA->fetchhash;
			my $uid = $rowA{'uid'};
			my $studynum = $rowA{'study_num'};
			
			WriteLog("Found study [" . $studyid . "] [$uid$studynum]");
			push @list,$studyid;
		}
	}
	
	# now get just the studies that need to have their results rerun
	$sqlstring = "select study_id from studies where study_id in (select study_id from analysis where pipeline_id = $pipelineid and analysis_rerunresults = 1 and analysis_status = 'complete' and analysis_isbad <> 1) order by study_datetime desc";
	WriteLog($sqlstring);
	$result = $dbh->prepare($sqlstring);
	$result->execute();
	#WriteLog($sqlstring);
	if ($result->rows > 0) {
		while (my $row = $result->fetchrow_hashref()) {
			my $studyid = $row->{study_id};
			#WriteLog("Found study [" . $studyid . "]");
			push @list,$studyid;
		}
	}
	
	my $numstudies = $#list+1;
	WriteLog("Found $numstudies studies that met criteria");

	return @list;
}


# ----------------------------------------------------------
# --------- GetPipelineDataDef -----------------------------
# ----------------------------------------------------------
sub GetPipelineDataDef() {
	my ($pipelineid, $pipelineversion) = @_;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	# get data definition
	my $sqlstring = "select * from pipeline_data_def where pipeline_id = $pipelineid and pipeline_version = $pipelineversion order by pdd_type, pdd_order asc";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my @list;
		while (my %row = $result->fetchhash) {
			my $rec = {};
			$rec->{'id'} = $row{'pipelinedatadef_id'};
			$rec->{'order'} = $row{'pdd_order'};
			$rec->{'type'} = $row{'pdd_type'};
			$rec->{'criteria'} = $row{'pdd_seriescriteria'};
			$rec->{'assoctype'} = $row{'pdd_assoctype'};
			$rec->{'protocol'} = $row{'pdd_protocol'};
			$rec->{'modality'} = $row{'pdd_modality'};
			$rec->{'dataformat'} = $row{'pdd_dataformat'};
			$rec->{'imagetype'} = $row{'pdd_imagetype'};
			$rec->{'gzip'} = $row{'pdd_gzip'};
			$rec->{'location'} = $row{'pdd_location'};
			$rec->{'useseries'} = $row{'pdd_useseries'};
			$rec->{'preserveseries'} = $row{'pdd_preserveseries'};
			$rec->{'behformat'} = $row{'pdd_behformat'};
			$rec->{'behdir'} = $row{'pdd_behdir'};
			$rec->{'enabled'} = $row{'pdd_enabled'};
			if (!defined($rec->{'modality'})) {
				$rec->{'modality'} = '';
			}
			#WriteLog("$rec->{'id'}, $rec->{'order'}, $rec->{'type'}, $rec->{'assoctype'}, $rec->{'protocol'}, $rec->{'modality'}, $rec->{'dataformat'}, $rec->{'imagetype'}, $rec->{'gzip'}, $rec->{'location'}, $rec->{'useseries'}, $rec->{'preserveseries'}, $rec->{'behformat'}, $rec->{'behdir'}, $rec->{'enabled'}");
			push @list,$rec;
		}
		return \@list;
	}
	else {
		return ();
	}
}


# ----------------------------------------------------------
# --------- GetPipelineSteps -------------------------------
# ----------------------------------------------------------
sub GetPipelineSteps() {
	my ($pipelineid, $pipelineversion) = @_;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	# get data definition
	my $sqlstring = "select * from pipeline_steps where pipeline_id = $pipelineid and pipeline_version = $pipelineversion order by ps_order asc";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my @list;
		while (my %row = $result->fetchhash) {
			my $rec = {};
			$rec->{'id'} = $row{'pipelinestep_id'};
			$rec->{'command'} = $row{'ps_command'};
			$rec->{'workingdir'} = $row{'ps_workingdir'};
			$rec->{'order'} = $row{'ps_order'};
			$rec->{'description'} = $row{'ps_description'};
			$rec->{'logged'} = $row{'ps_logged'};
			$rec->{'enabled'} = $row{'ps_enabled'};
			push @list,$rec;
		}
		return \@list;
	}
	else {
		return ();
	}
}


# ----------------------------------------------------------
# --------- IsPipelineEnabled ------------------------------
# ----------------------------------------------------------
sub IsPipelineEnabled() {
	my ($pid) = @_;

	my $enabled = 0;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring = "select * from pipelines where pipeline_id = $pid";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		if ($row{'pipeline_enabled'}) {
			$enabled = 1;
		}
	}
	
	return $enabled;
}


# ----------------------------------------------------------
# --------- SetPipelineStopped -----------------------------
# ----------------------------------------------------------
sub SetPipelineStopped() {
	my ($pid, $newrunnum) = @_;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring = "update pipelines set pipeline_status = 'stopped', pipeline_lastfinish = now() where pipeline_id = '$pid'";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
}


# ----------------------------------------------------------
# --------- SetPipelineTestingOff --------------------------
# ----------------------------------------------------------
sub SetPipelineTestingOff() {
	my ($pid) = @_;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring = "update pipelines set pipeline_testing = 0, pipeline_status = 'stopped', pipeline_lastfinish = now() where pipeline_id = $pid";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
}


# ----------------------------------------------------------
# --------- SetPipelineDisabled ----------------------------
# ----------------------------------------------------------
sub SetPipelineDisabled() {
	my ($pid) = @_;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring = "update pipelines set pipeline_enabled = 0 where pipeline_id = $pid";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
}


# ----------------------------------------------------------
# --------- SetPipelineRunning -----------------------------
# ----------------------------------------------------------
sub SetPipelineRunning() {
	my ($pid) = @_;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring = "update pipelines set pipeline_status = 'running', pipeline_laststart = now() where pipeline_id = $pid";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
}


# ----------------------------------------------------------
# --------- SetPipelineStatusMessage -----------------------
# ----------------------------------------------------------
sub SetPipelineStatusMessage() {
	my ($pid, $msg) = @_;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring = "update pipelines set pipeline_statusmessage = '$msg' where pipeline_id = '$pid'";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
}


# ----------------------------------------------------------
# --------- SetPipelineProcessStatus -----------------------
# ----------------------------------------------------------
sub SetPipelineProcessStatus() {
	my ($status, $pipelineid, $studyid) = @_;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring;
	my $result;
	
	if ($status eq 'started') {
		$sqlstring = "insert into pipeline_procs (pp_processid, pp_status, pp_startdate, pp_lastcheckin, pp_currentpipeline, pp_currentsubject, pp_currentstudy) values ($$,'started',now(),now(),0,0,0)";
	}
	elsif ($status eq 'complete') {
		$sqlstring = "delete from pipeline_procs where pp_processid = $$";
	}
	else {
		$sqlstring = "update pipeline_procs set pp_status = 'running', pp_lastcheckin = now(), pp_currentpipeline = '$pipelineid', pp_currentstudy = '$studyid' where pp_processid = '$$'";
	}
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
}


# ----------------------------------------------------------
# --------- ConvertDicom -----------------------------------
# ----------------------------------------------------------
sub ConvertDicom() {
	my ($req_filetype, $indir, $outdir, $req_gzip, $uid, $study_num, $series_num, $data_type) = @_;

	my $sqlstring;

	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $origDir = getcwd;
	
	my $gzip;
	if ($req_gzip) { $gzip = "-g y"; }
	else { $gzip = "-g n"; }
	
	my $starttime = GetTotalCPUTime();
			
	WriteLog("Working on [$indir] -> [$outdir]");
	#my $outdir;
	my $fileext;
	
	# delete any files that may already be in the output directory.. example, an incomplete series was put in the output directory. Remove any stuff and start from scratch to ensure proper file numbering
	if (($outdir ne "") && ($outdir ne "/") ) {
		my $systemstring1 = `rm --preserve-root -f $outdir/*.hdr $outdir/*.img $outdir/*.nii $outdir/*.gz`;
		WriteLog("Running [$systemstring1]");
		`$systemstring1`;
	}
	
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

	#WriteLog(CompressText("$systemstring (" . `$systemstring 2>&1` . ")"));
	WriteLog("Running [$systemstring] [" . `$systemstring 2>&1` . "]");

	# rename the files into something meaningful
	my ($numimg, $numhdr, $numnii, $numgz) = BatchRenameFiles($outdir, $series_num, $study_num, $uid);
	WriteLog("Done renaming files: $numimg, $numhdr, $numnii, $numgz");
	
	# gzip any remaining .nii files if they were supposed to be gzipped but weren't
	if ($req_gzip) {
		my $systemstring = "cd $outdir; gzip *.nii";
		WriteLog("Running [$systemstring]: [" . `$systemstring 2>&1` . "]");
	}

	WriteLog("Getting size of $outdir");
	my ($dirsize,$count) = GetDirectorySize($outdir);
	my $endtime = GetTotalCPUTime();
	my $cputime = $endtime - $starttime;

	WriteLog("Converted $dirsize bytes of data using $cputime sec of CPU time");
	
	# change back to original directory before leaving
	chdir($origDir);
	
	return ($numimg, $numhdr, $numnii, $numgz, $dirsize, $cputime);
}


# ----------------------------------------------------------
# --------- BatchRenameFiles -------------------------------
# ----------------------------------------------------------
sub BatchRenameFiles {
	my ($dir, $seriesnum, $studynum, $uid, $costcenter) = @_;
	
	chdir($dir) || die("Cannot chdir($dir) in BatchRenameFiles() !\n");
	my @imgfiles = <*.img>;
	my @hdrfiles = <*.hdr>;
	my @niifiles = <*.nii>;
	my @gzfiles = <*.nii.gz>;

	WriteLog("Begin file renaming...");

	my $i = 1;
	foreach my $imgfile (nsort @imgfiles) {
		my $oldfile = $imgfile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".img";
		`mv $oldfile $newfile`;
		$i++;
	}

	$i = 1;
	foreach my $hdrfile (nsort @hdrfiles) {
		my $oldfile = $hdrfile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".hdr";
		#WriteLog("$oldfile => $newfile");
		`mv $oldfile $newfile`;
		$i++;
	}
	
	$i = 1;
	foreach my $niifile (nsort @niifiles) {
		my $oldfile = $niifile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".nii";
		#WriteLog("$oldfile => $newfile");
		`mv $oldfile $newfile`;
		$i++;
	}

	$i = 1;
	foreach my $gzfile (nsort @gzfiles) {
		my $oldfile = $gzfile;
		my $newfile = $uid . "_$studynum" . "_$seriesnum" . "_" . sprintf('%05d',$i) . ".nii.gz";
		#WriteLog($log,"$oldfile => $newfile");
		`mv $oldfile $newfile`;
		$i++;
	}
	
	#WriteLog("Done file renaming (".$#imgfiles+1 .",".$#hdrfiles+1 .",".$#niifiles+1 .",".$#gzfiles+1 .")...");
	
	return ($#imgfiles+1, $#hdrfiles+1, $#niifiles+1, $#gzfiles+1);
}