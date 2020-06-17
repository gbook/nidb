#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB pipeline.pl
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
use POSIX qw(ceil floor);

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
	# Perl buffers output to STDOUT (screen), and these 3 statements turn off buffering
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
		SetPipelineProcessStatus('complete',0,0);
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
		SetPipelineProcessStatus('complete',0,0);
		SetModuleStopped();
		return 0;
	}

	# update the start time
	ModuleDBCheckIn($scriptname, $db);
	ModuleRunningCheckIn($scriptname, $db);
	
	# create a list of pipelines to be run
	my $i = 0;
	my @pipelinerows;
	while (my %row = $result->fetchhash) {
		$pipelinerows[$i] = {%row};
		my $pid = $row{'pipeline_id'};
		my $pipelinename = $row{'pipeline_name'};
		$i++;
	}
	# loop through all the pipelines. Allow the program to return to this point
	# and continue running the other pipelines if something happens to a particular pipeline
	PIPELINE: for my $i (0 .. $#pipelinerows) {
		my %row = %{$pipelinerows[$i]};
		
		my $pid = $row{'pipeline_id'};
		
		my $pipelinename = $row{'pipeline_name'};
		my $pipelineversion = $row{'pipeline_version'};
		my $pipelinedependency = $row{'pipeline_dependency'};
		my $pipelinegroupids = $row{'pipeline_groupid'} . '';
		my $pipelinedirectory = $row{'pipeline_directory'};
		my $pipelinedirstructure; if (!defined($row{'pipeline_dirstructure'})) { $pipelinedirstructure = ""; } else { $pipelinedirstructure = $row{'pipeline_dirstructure'}; }
		my $usetmpdir = $row{'pipeline_usetmpdir'};
		my $tmpdir = $row{'pipeline_tmpdir'};
		my $clustertype = $row{'pipeline_clustertype'};
		my $pipelinequeue = $row{'pipeline_queue'};
		my $pipelinedatacopymethod = $row{'pipeline_datacopymethod'};
		my $pipelinesubmithost;
		if ($row{'pipeline_submithost'} eq "") { $pipelinesubmithost = $cfg{'clustersubmithost'}; }
		else { $pipelinesubmithost = $row{'pipeline_submithost'}; }
		my $pipelinemaxwalltime = $row{'pipeline_maxwalltime'};
		my $pipelinesubmitdelay = $row{'pipeline_submitdelay'};
		my $pipelinelevel = $row{'pipeline_level'};
		my $deplevel = $row{'pipeline_dependencylevel'};
		my $depdir = $row{'pipeline_dependencydir'};
		my $deplinktype = $row{'pipeline_deplinktype'};
		my $testing = $row{'pipeline_testing'};
		my $pipelineuseprofile = $row{'pipeline_useprofile'};
		my $pipelineremovedata = $row{'pipeline_removedata'};
		my $pipelineresultscript = $row{'pipeline_resultsscript'};
		my $pipelinegroupbysubject = $row{'pipeline_groupbysubject'};
		
		if (!defined($pipelinesubmitdelay)) {
			$pipelinesubmitdelay = 6;
		}
		elsif (($pipelinesubmitdelay eq "") || ($pipelinesubmitdelay == 0)) {
			$pipelinesubmitdelay = 6;
		}
		my $analysisdir = "";
		if ($pipelinedirstructure eq "b") {
			$analysisdir = $cfg{'analysisdirb'};
		}
		else {
			$analysisdir = $cfg{'analysisdir'};
		}
		
		# remove any whitespace from the queue... SGE hates whitespace
		$pipelinequeue =~ s/\s+//g;
		
		print "Working on pipeline [$pid] - [$pipelinename] Submits to queue [$pipelinequeue] through host [$pipelinesubmithost]\n";
		WriteLog("Working on pipeline [$pid] - [$pipelinename] Submits to queue [$pipelinequeue] through host [$pipelinesubmithost]");

		SetPipelineProcessStatus('running',$pid,0);
		
		# mark the pipeline as having been checked
		my $sqlstringC = "update pipelines set pipeline_lastcheck = now() where pipeline_id = '$pid'";
		my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
		
		if (trim($pipelinequeue) eq "") {
			WriteLog("No queue specified");
			SetPipelineStatusMessage($pid, 'No queue specified.');
			SetPipelineStopped($pid);
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
		SetPipelineStatusMessage($pid, 'Submitting jobs');
		SetPipelineRunning($pid);

		WriteLog("Running pipeline $pid");

		my $dd = ();
		my @datadef = ();
		if ($pipelinelevel != 0) {
			if (($pipelinelevel == 1) || (($pipelinelevel == 2) && ($pipelinedependency eq ''))) {
				$dd = GetPipelineDataDef($pid, $pipelineversion);
				
				# if there is no data definition and no dependency
				if ((!$dd) && ($pipelinedependency eq '')) {
					WriteLog("Pipeline [$pipelinename - $pid] has no data definition. Skipping.");
					
					# update the statuses, and stop the modules
					SetPipelineStatusMessage($pid, 'Pipeline has no data definition. Skipping');
					SetPipelineStopped($pid);
					next PIPELINE;
				}
				if (defined($dd)) {
					@datadef = @$dd;
				}
			}
		}

		# get the pipeline steps (the script)
		my $ps = GetPipelineSteps($pid, $pipelineversion);
		#WriteLog( Dumper($ps) );
		if (!$ps) {
			WriteLog("Pipeline [$pipelinename - $pid] has no steps. Skipping.");

			# update the statuses and stop the modules
			SetPipelineStatusMessage($pid, 'Pipeline has no steps. Skipping');
			SetPipelineStopped($pid);
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
				SetPipelineStopped($pid);
				SetPipelineProcessStatus('complete',0,0);
				SetModuleStopped();
				# update the stop time
				ModuleDBCheckOut($scriptname, $db);
				return 1;
			}

			# only 1 analysis should ever be run with the oneshot level, so if 1 already exists, regardless of state or pipeline version, then
			# leave this function without running the analysis
			my $sqlstring = "select * from analysis where pipeline_id = $pid";
			$result = SQLQuery($sqlstring, __FILE__, __LINE__);
			if ($result->numrows > 0) {
				WriteLog("An analysis already exists for this one-shot level pipeline, exiting");
				SetPipelineStatusMessage($pid, 'An analysis already exists for this one-shot pipeline. Skipping');
				SetPipelineStopped($pid);
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
			my $sgebatchfile = CreateClusterJobFile($clustertype, $analysisRowID, 0, 'UID', 'STUDYNUM', 'STUDYDATETIME',$analysispath, $usetmpdir, $tmpdir, $pipelineuseprofile, $pipelinename, $pid, $pipelineremovedata, $pipelineresultscript, $pipelinemaxwalltime, 0, @pipelinesteps);
		
			$systemstring = "chmod -Rf 777 $analysispath";
			WriteLog("[$systemstring]");
			`$systemstring 2>&1`;
			
			# submit the SGE job
			if (-d $analysispath) {
				WriteLog("[$analysispath] exists!");
				AppendLog($setuplog, "[$analysispath] exists!");
			}
			my $sgefilename = "$analysispath/sge.job";
			open SGEFILE, "> $sgefilename" || die ("Could not open [$sgefilename] because [$!]");
			print SGEFILE $sgebatchfile;
			close SGEFILE;
			chmod(0777,$sgefilename);
			chmod(0777,$sgebatchfile);
			chmod(0777,$analysispath);
			
			# submit the sucker to the cluster
			$systemstring = "ssh $pipelinesubmithost qsub -u onrc -q $pipelinequeue \"$analysispath/sge.job\"";
			my $sgeresult = trim(`$systemstring 2>&1`);
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
		# ================================= LEVEL 1 =================================
		elsif ($pipelinelevel == 1) {
		
			# fix the directory if its not the default or blank
			if ($pipelinedirectory eq "") {
				$pipelinedirectory = $analysisdir;
			}
			else { $pipelinedirectory = $cfg{'mountdir'} . $pipelinedirectory; }

			if ($pipelinedependency eq "") { $pipelinedependency = "0"; }
			
			# if there are multiple dependencies, we'll need to loop through all of them separately
			my @deps = split(',', $pipelinedependency);
			foreach my $pipelinedep(@deps) {
				
				# get the list of studies which meet the criteria for being processed through the pipeline
				my @studyids = GetStudyToDoList($pid, $datadef[0]{'modality'}, $pipelinedep, $pipelinegroupids);
				
				my $numsubmitted = 0;
				foreach my $sid(@studyids) {

					my $setuplog = "";
					
					SetPipelineProcessStatus('running',$pid,$sid);

					$numchecked = $numchecked + 1;
					WriteLog("--------------------- Working on study [$sid] for [$pipelinename] --------------------");
					
					# connect to the DB (in case it became disconnected)
					$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");

					# check if the number of concurrent jobs is reached. the function also checks if this pipeline module is enabled
					WriteLog("Checking if we've reached the max number of concurrent analyses");
					while (my $filled = IsQueueFilled($pid)) {
						# check if this pipeline is enabled
						if (!IsPipelineEnabled($pid)) {
							SetPipelineStatusMessage($pid, 'Pipeline disabled while running. Normal stop.');
							SetPipelineStopped($pid);
							next PIPELINE;
						}
						
						# otherwise check
						if ($filled == 0) { last; }
						if ($filled == 1) {
							# update the pipeline status message
							SetPipelineStatusMessage($pid, 'Process quota reached. Waiting 1 minute to resubmit');
							WriteLog("Concurrent analysis quota reached, waiting 1 minute");
							print "Queue full, waiting 1 minute...";
							ModuleRunningCheckIn($scriptname, $db);
							sleep(60); # sleep for 1 minute
						}
						if ($filled == 2) {
							# update the stop time
							ModuleDBCheckOut($scriptname, $db);
							return 1;
						}
					}

					# get the analysis info, if an analysis already exists for this study
					my ($analysisRowID, $rerunresults, $runsupplement, $msg) = GetAnalysisInfo($pid,$sid);
					$setuplog .= $msg;

					# ********************
					# only continue through this section (and submit the analysis) if
					# a) there is no analysis
					# b) OR there is an existing analysis and it needs the results rerun
					# c) OR there is an existing analysis and it needs a supplement run
					# ********************
					if (($runsupplement eq "1") || ($rerunresults eq "1") || ($analysisRowID eq "")) {
						# if the analysis doesn't yet exist, insert a temporary row, to be updated later, in the analysis table as a placeholder so that no other pipeline processes try to run it
						if ($analysisRowID eq "") {
							$sqlstring = "insert into analysis (pipeline_id, pipeline_version, pipeline_dependency, study_id, analysis_status, analysis_startdate) values ($pid,$pipelineversion,$pipelinedep,$sid,'processing',now())";
							WriteLog($sqlstring);
							my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
							$analysisRowID = $result->insertid;

							InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysiscreated', 'Analysis created');
						}
						
						# get information about the study
						$sqlstring = "select *, date_format(study_datetime,'%Y%m%d_%H%i%s') 'studydatetime' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = $sid";
						$setuplog .= "Getting study information [$sqlstring]";
						my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
						my %row = $result->fetchhash;
						my $uid = $row{'uid'};
						my $studynum = $row{'study_num'};
						my $subjectid = $row{'subject_id'};
						my $studydatetime = $row{'studydatetime'};
						my $numseries = 0;
						my $datalog;
						my $datareport;
						my $datatable;
						if (defined($uid)) {
						
							my $dependencyanalysisid = 0;
							my $submiterror = 0;
							my $errormsg;
							
							WriteLog("StudyDateTime: [$studydatetime], Working on: [$uid$studynum]");
							print "StudyDateTime: $studydatetime\n";
							
							my $analysispath = "";
							if ($pipelinedirstructure eq "b") {
								$analysispath = "$pipelinedirectory/$pipelinename/$uid/$studynum";
							}
							else {
								$analysispath = "$pipelinedirectory/$uid/$studynum/$pipelinename";
							}
							
							# this file will record any events during setup
							my $setuplogF = "$analysispath/pipeline/analysisSetup.log";
							WriteLog("Should have created this analysis setup log [$setuplogF]");
							
							# get the nearest study for this subject that has the dependency
							my $sqlstringA = "select analysis_id, study_num from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where c.subject_id = $subjectid and a.pipeline_id = '$pipelinedep' and a.analysis_status = 'complete' and a.analysis_isbad <> 1 order by abs(datediff(b.study_datetime, '$studydatetime')) limit 1";
							my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
							my %rowA = $resultA->fetchhash;
							my $studynum_nearest = $rowA{'study_num'};
							$dependencyanalysisid = $rowA{'analysis_id'};
							
							# create the dependency path
							my $deppath = "";
							if (($pipelinedep != 0) && ($pipelinedep ne "")) {
								if ($deplevel eq "subject") {
									$setuplog .= WriteLog("Dependency is a subject level (will match dep for same subject, any study)") . "\n";
									#$deppath = "$pipelinedirectory/$uid/$studynum_nearest";
								}
								else {
									$setuplog .= WriteLog("Dependency is a study level (will match dep for same subject, same study)") . "\n";
									#$deppath = "$pipelinedirectory/$uid/$studynum";
									
									# check the dependency and see if there's anything amiss about it
									my $depstatus = CheckDependency($sid,$pipelinedep);
									if ($depstatus ne "") {
										my $datalog2 = EscapeMySQLString($setuplog);
										my $datatable2 = EscapeMySQLString($datatable);
										my $sqlstringC = "update analysis set analysis_datalog = '$datalog2', analysis_datatable = '$datatable2', analysis_status = '$depstatus', analysis_startdate = null where analysis_id = $analysisRowID";
										my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
										next;
									}
								}
							}
							else {
								$pipelinedep = 0;
							}
							$setuplog .= WriteLog("Dependency path is [$deppath] and analysis path is [$analysispath]") . "\n";

							# get the data if we are not running a supplement, and not rerunning the results
							if ((!$runsupplement) && (!$rerunresults)) {
								($numseries, $datalog, $datareport, $datatable) = GetData($sid, $analysispath, $uid, $analysisRowID, $pipelineversion, $pid, $pipelinedep, $deplevel, @datadef);
							}
							my $datatable2 = EscapeMySQLString($datatable);
							my $sqlstringC = "update analysis set analysis_datatable = '$datatable2' where analysis_id = $analysisRowID";
							my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);

							# again check if there are any series to actually run the pipeline on...
							# ... but its ok to run if any of the following are true
							#     a) rerunresults is true
							#     b) runsupplement is true
							#     c) this pipeline is dependent on another pipeline
							my $okToRun = 0;
							if ($numseries > 0) { $okToRun = 1; } # there is data to download from this study
							if (($rerunresults) || ($rerunresults eq "1")) { $okToRun = 1; }
							if (($runsupplement) || ($runsupplement eq "1")) { $okToRun = 1; }
							if (($pipelinedep > 0) && ($deplevel eq "study")) { $okToRun = 1; } # there is a parent pipeline and we're using the same study from the parent pipeline. may or may not have data to download
							
							# one of the above criteria has been satisfied, so its ok to run
							if ($okToRun) {
								$setuplog .= WriteLog(" ----- Study [$sid] has [$numseries] matching series downloaded (or needs results rerun, or is a supplement, or is dependent on another pipeline). Beginning analysis ----- ") . "\n";

								my $dependencyname;
								if ((!$rerunresults) && (!$runsupplement)) {
									if ($pipelinedep != 0) {
										$setuplog .= WriteLog("There is a pipeline dependency [$pipelinedep]") . "\n";
										my $sqlstring = "select pipeline_name from pipelines where pipeline_id = $pipelinedep";
										$setuplog .= WriteLog($sqlstring) . "\n";
										my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
										if ($result->numrows > 0) {
											my %row = $result->fetchhash;
											$dependencyname = $row{'pipeline_name'};
											$setuplog .= WriteLog("Found " . $result->numrows . " pipeline dependency [$dependencyname]") . "\n";
										}
										else {
											$setuplog .= WriteLog("Pipeline dependency ($pipelinedep) does not exist!") . "\n";
											SetPipelineStatusMessage($pid, "Pipeline dependency ($pipelinedep) does not exist!");
											SetPipelineStopped($pid);
											next PIPELINE; # using a GOTO... so sad, but it had to be done
										}
										InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysismessage', "This pipeline is dependent on pipeline [$dependencyname]");
									}
									else {
										$setuplog .= WriteLog("No pipeline dependencies [$pipelinedep]") . "\n";
									}
								
									MakePath("$analysispath/pipeline");
									chmod(0777,"$analysispath/pipeline");
									if ($pipelinedep != 0) {
										if ($deplevel eq "subject") {
											if ($pipelinedirstructure eq "b") {
												$deppath = "$pipelinedirectory/$dependencyname/$uid/$studynum_nearest";
											}
											else {
												$deppath = "$pipelinedirectory/$uid/$studynum_nearest/$dependencyname";
											}
										}
										else {
											if ($pipelinedirstructure eq "b") {
												$deppath = "$pipelinedirectory/$dependencyname/$uid/$studynum";
											}
											else {
												$deppath = "$pipelinedirectory/$uid/$studynum/$dependencyname";
											}
											#$deppath = "$pipelinedirectory/$uid/$studynum/$dependencyname";
										}

										if (-e "$deppath/$dependencyname") {
											$setuplog .= WriteLog("Full dependency path [$deppath/$dependencyname] exists") . "\n";
										}
										else {
											$setuplog .= WriteLog("Full dependency path [$deppath/$dependencyname] does NOT exist") . "\n";
										}
										
										my $systemstring;

										WriteLog("This is a level [$pipelinelevel] pipeline. deplinktype [$deplinktype] depdir [$depdir]");
										$setuplog .= "This is a level [$pipelinelevel] pipeline. deplinktype [$deplinktype] depdir [$depdir]";
										
										InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysismessage', "Parent pipeline (dependency) will be copied to directory [$depdir] using method [$deplinktype]");
										
										if ($depdir eq "subdir") {
											$setuplog .= WriteLog("Dependency will be copied to a subdir") . "\n";
											switch ($deplinktype) {
												case "hardlink" { $systemstring = "cp -aul $deppath $analysispath/"; }
												case "softlink" { $systemstring = "cp -aus $deppath $analysispath/"; }
												case "regularcopy" { $systemstring = "cp -au $deppath $analysispath/"; }
											}
										}
										else { # root dir
											$setuplog .= WriteLog("Dependency will be copied to the root dir") . "\n";
											switch ($deplinktype) {
												case "hardlink" { $systemstring = "cp -aul $deppath/* $analysispath/"; }
												case "softlink" { $systemstring = "cp -aus $deppath/* $analysispath/"; }
												case "regularcopy" { $systemstring = "cp -au $deppath/* $analysispath/"; }
											}
										}

										InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysismessage', "Copied dependency by running [$systemstring]");
										InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysisdependencyid', "$dependencyanalysisid");
										
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
										InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysismessage', "No dependencies specified for this pipeline");
									}
									
									# now safe to write out the setuplog 
									AppendLog($setuplogF, $setuplog);
									
									# die here if we can't write the log... because if its not writeable, then something is wrong and we could submit a few hundred jobs that will fail
									open DATALOG, "> $analysispath/pipeline/data.log" or die("[Line: " . __LINE__ . "] Could not create $analysispath/pipeline/data.log");
									print DATALOG $datalog;
									close DATALOG;
									chmod(0777,"$analysispath/pipeline/data.log");
								}
								my $realanalysispath = $analysispath;
								$realanalysispath =~ s/\/mount//g;
								
								# create the SGE job file
								my $sgebatchfile = CreateClusterJobFile($clustertype, $analysisRowID, 0, $uid, $studynum, $realanalysispath, $usetmpdir, $tmpdir, $pipelineuseprofile, $studydatetime, $pipelinename, $pid, $pipelineremovedata, $pipelineresultscript, $pipelinemaxwalltime, $runsupplement, @pipelinesteps);
							
								`chmod -Rf 777 $analysispath`;
								# create the SGE job file
								my $sgejobfilename = "";
								my $sgeshortfilename = "";
								if (($rerunresults) || ($rerunresults eq "1")) {
									$sgejobfilename = "$analysispath/sgererunresults.job";
									$sgeshortfilename = "sgererunresults.job";
								}
								elsif (($runsupplement) || ($runsupplement eq "1")) {
									$sgejobfilename = "$analysispath/sge-supplement.job";
									$sgeshortfilename = "sge-supplement.job";
								}
								else {
									$sgejobfilename = "$analysispath/sge.job";
									$sgeshortfilename = "sge.job";
								}
								open SGEFILE, "> $sgejobfilename";
								print SGEFILE $sgebatchfile;
								close SGEFILE;
								chmod(0777,$sgebatchfile);
								chmod(0777,$analysispath);
								
								# submit the sucker to the cluster
								my $systemstring = "ssh $pipelinesubmithost $cfg{'qsubpath'} -u $cfg{'clusteruser'} -q $pipelinequeue \"$realanalysispath/$sgeshortfilename\"";
								my $sgeresult = trim(`$systemstring 2>&1`);
								print "SGE submit result [$sgeresult]\n";
								WriteLog("SGE submit result [$sgeresult]");
								my @parts = split(' ', $sgeresult);
								my $jobid = $parts[2];
								WriteLog(join('|',@parts));
								AppendLog($setuplogF, WriteLog("[$systemstring]: " . $sgeresult));
								
								# check the return message from qsub
								if ($sgeresult =~ /invalid option/i) {
									$submiterror = 1;
									$errormsg = "Invalid qsub option";
								}
								elsif ($sgeresult =~ /directive error/i) {
									$errormsg = "Invalid qsub directive";
									$submiterror = 1;
								}
								elsif ($sgeresult =~ /cannot connect to server/i) {
									$errormsg = "Invalid qsub hostname";
									$submiterror = 1;
								}
								elsif ($sgeresult =~ /unknown queue/i) {
									$errormsg = "Invalid queue";
									$submiterror = 1;
								}
								elsif ($sgeresult =~ /queue is not enabled/i) {
									$errormsg = "Queue is not enabled";
									$submiterror = 1;
								}
								elsif ($sgeresult =~ /job exceeds queue resource limits/i) {
									$errormsg = "Job exceeds resource limits";
									$submiterror = 1;
								}

								if ($submiterror) {
									$sqlstring = "update analysis set analysis_qsubid = '0', analysis_status = 'error', analysis_statusmessage = 'Submit Error [$errormsg]', analysis_enddate = now() where analysis_id = $analysisRowID";
								}
								else {
									$sqlstring = "update analysis set analysis_qsubid = '$jobid', analysis_status = 'submitted', analysis_statusmessage = 'Submitted to $pipelinequeue', analysis_enddate = now() where analysis_id = $analysisRowID";
								}
								
								AppendLog($setuplogF, WriteLog($sqlstring));
								my $result = SQLQuery($sqlstring, __FILE__, __LINE__);

								InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysissubmitted', $sgeresult);
								
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
								my $datatable2 = EscapeMySQLString($datatable);
								my $sqlstringC = "update analysis set analysis_datalog = '$datalog2', analysis_datatable = '$datatable2' where analysis_id = $analysisRowID";
								my $resultC = SQLQuery($sqlstringC, __FILE__, __LINE__);
								InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysissetuperror', "No data found, 0 series returned from search");
							}
							AppendLog($setuplogF, WriteLog("Submitted $numsubmitted jobs so far"));
							print "Submitted $numsubmitted jobs so far\n";
							
							# mark the study in the analysis table
							if (($numseries > 0) || (($pipelinedep != 0) && ($deplevel eq "study")) || ($runsupplement) || ($rerunresults eq "1")) {
								if (($rerunresults eq "1") || ($runsupplement)) {
									$sqlstring = "update analysis set analysis_status = 'pending' where analysis_id = $analysisRowID";
								}
								else {
									$sqlstring = "update analysis set analysis_status = 'pending', analysis_numseries = $numseries, analysis_enddate = now() where analysis_id = $analysisRowID";
								}
								my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
								if ($submiterror) { InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysissubmiterror', "Analysis submitted to cluster, but was rejected with errors"); }
								else { InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysispending', "Analysis has been submitted to the cluster [$pipelinequeue] and is waiting to run"); }
							}
							else {
								# save some database space, since most entries will be blank
								$sqlstring = "update analysis set analysis_status = 'NoMatchingSeries', analysis_startdate = null where analysis_id = $analysisRowID";
								my $result = SQLQuery($sqlstring, __FILE__, __LINE__);

								InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysismessage', "This study did not have any matching data");
							}
							
						}
					}
					else {
						WriteLog("This analysis already has an entry in the analysis table");
						InsertAnalysisEvent($analysisRowID, $pid, $pipelineversion, $sid, 'analysismessage', "This analysis already has an entry in the analysis table");
					}
					if (!IsPipelineEnabled($pid)) {
						SetPipelineStatusMessage($pid, 'Pipeline disabled while running. Normal stop.');
						SetPipelineStopped($pid);
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
				SetPipelineStopped($pid);
				next PIPELINE;
			}
		
			#my $analysispath;
			my $analysisRowID;
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
						WriteLog("Found " . $result->numrows . " pipelines for second level pipeline");
						my %row = $result->fetchhash;
						$dependencyname = $row{'pipeline_name'};
						$dependencylevel = $row{'pipeline_level'};
					}
					else {
						WriteLog("Pipeline dependency ($pipelinedep) does not exist!");
						SetPipelineStopped($pid);
						next PIPELINE;
					}
				}
				
				# build the path
				if ($pipelinedirectory eq "") {
					$pipelinedirectory = $cfg{'groupanalysisdir'};
					$realpipelinedirectory = $pipelinedirectory;
					$realpipelinedirectory =~ s/\/mount//;
					WriteLog("1 [$pipelinedirectory] --> [$realpipelinedirectory]");
				}
				else {
					$pipelinedirectory = $cfg{'mountdir'} . $pipelinedirectory;
					$realpipelinedirectory = $pipelinedirectory;
					WriteLog("2 [$pipelinedirectory] --> [$realpipelinedirectory]");
				}
				
				MakePath("$pipelinedirectory/$pipelinename/$dependencyname");
				$groupanalysispath = "$pipelinedirectory/$pipelinename";
				MakePath($groupanalysispath);
				chmod(0777,$groupanalysispath);

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
				if ($pipelinegroupids eq "") {
					next PIPELINE;
				}
				$sqlstring = "select * from groups where group_id in ($pipelinegroupids)";
				$result = SQLQuery($sqlstring, __FILE__, __LINE__);
				if ($result->numrows > 0) {
					while (my %row = $result->fetchhash) {
						my $groupname = $row{'group_name'};
						my $groupid = $row{'group_id'};
						# get a list of studies in the group that have the dependency
						my $sqlstringA = "select a.study_id from studies a left join group_data b on a.study_id = b.data_id where a.study_id in (select study_id from analysis where pipeline_id in ($pipelinedependency) and analysis_status = 'complete' and analysis_isbad <> 1) and (a.study_datetime < date_sub(now(), interval $pipelinesubmitdelay hour)) and b.group_id in ($groupid) order by a.study_datetime desc";
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
							SetPipelineStopped($pid);
							next PIPELINE;
						}

						foreach my $sid(@studyids) {
							# get information about the study
							my $sqlstringB = "select *, date_format(study_datetime,'%Y%m%d_%H%i%s') 'studydatetime' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = $sid";
							#WriteLog($sqlstringB);
							my $resultB = SQLQuery($sqlstringB, __FILE__, __LINE__);
							my %rowB = $resultB->fetchhash;
							my $uid = $rowB{'uid'};
							my $studynum = $rowB{'study_num'};
							my $studydatetime = $rowB{'studydatetime'};
							my $numseries = 0;
							my $datalog;
							if (defined($uid)) {
								#WriteLog("StudyDateTime: $studydatetime");
								print "StudyDateTime: $studydatetime\n";
								
								if ($pipelinedirectory eq "") {
									$pipelinedirectory = $analysisdir;
								}

								WriteLog("StudyDateTime [$studydatetime]. This is a level [$pipelinelevel] pipeline. dependencylevel [$dependencylevel] deplinktype [$deplinktype] groupbysubject [$pipelinegroupbysubject]");
								my $studydir;
								if ($pipelinegroupbysubject) {
									$studydir = "$uid/$studydatetime";
									MakePath("$pipelinedirectory/$pipelinename/$groupname/$dependencyname/$uid");
								}
								else {
									$studydir = "$uid$studynum";
									MakePath("$pipelinedirectory/$pipelinename/$groupname/$dependencyname");
								}
								
								# create hard link in the analysis directory
								my $systemstring;
								if ($dependencylevel == 1) {
									switch ($deplinktype) {
										case "hardlink" { $systemstring = "cp -auL $analysisdir/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
										case "softlink" { $systemstring = "cp -aus $analysisdir/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
										case "regularcopy" { $systemstring = "cp -au $analysisdir/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
									}
								}
								else {
									switch ($deplinktype) {
										case "hardlink" { $systemstring = "cp -aul $cfg{'groupanalysisdir'}/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
										case "softlink" { $systemstring = "cp -aus $cfg{'groupanalysisdir'}/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
										case "regularcopy" { $systemstring = "cp -au $cfg{'groupanalysisdir'}/$uid/$studynum/$dependencyname $pipelinedirectory/$pipelinename/$groupname/$dependencyname/$studydir"; }
									}
								}
								#WriteLog("Running copy command [$systemstring]");
								
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
			my $sgebatchfile = CreateClusterJobFile($clustertype, $analysisRowID, 1, "GROUPLEVEL", 0, $groupanalysispath, $usetmpdir, $tmpdir, $pipelineuseprofile, $studydatetime, $pipelinename, $pid, $pipelineremovedata, $pipelineresultscript, $pipelinemaxwalltime, 0, @pipelinesteps);
		
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
			my $sgeresult = trim(`$systemstring 2>&1`);
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
		
		WriteLog("Done with pipeline [$pid] - [$pipelinename]");
		WriteLog("Done");
		SetPipelineStatusMessage($pid, 'Finished submitting jobs');
		SetPipelineStopped($pid);
		
		if ($jobsWereSubmitted) {
			SetPipelineProcessStatus('complete',0,0);
			SetModuleStopped();
			# update the stop time
			ModuleDBCheckOut($scriptname, $db);
			return 1;
		}
	}

	SetPipelineProcessStatus('complete',0,0);
	
	# end the module and return the code
	SetModuleStopped();
	
	# update the stop time
	ModuleDBCheckOut($scriptname, $db);
	
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
		SetPipelineStopped($pid);
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
	# if this is 0, the pipeline may have disappeared, or someone set the concurrent limit to 0
	# in which case this check will never be valid, so exit the look with a return code of 2
	if ($numprocallowed == 0) {
		return 2;
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
# --------- CreateClusterJobFile -------------------------------
# ----------------------------------------------------------
sub CreateClusterJobFile() {
	my ($clustertype, $analysisid, $isgroup, $uid, $studynum, $analysispath, $usetmpdir, $tmpdir, $pipelineuseprofile, $studydatetime, $pipelinename, $pipelineid, $removedata, $resultscript, $maxwalltime, $runsupplement, @pipelinesteps) = @_;

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
	
	if ($runsupplement eq '') {
		$runsupplement = 0;
	}
		
	WriteLog("Analysis path: $analysispath");
	print "Analysis path: $analysispath\n";
	WriteLog("Working Analysis path (temp directory): $workinganalysispath");
	print "Working Analysis path (temp dir): $workinganalysispath\n";
	
	# different submission parameters for slurm
	if ($clustertype eq "slurm") {
		$jobfile .= "#!/bin/sh\n";
		if ($runsupplement) {
			$jobfile .= "#\$ -J $pipelinename-supplement\n";
		}
		else {
			$jobfile .= "#\$ -J $pipelinename\n";
		}
		$jobfile .= "#\$ -o $analysispath/pipeline/\n";
		$jobfile .= "#\$ --export=ALL\n";
		$jobfile .= "#\$ --uid=" . $cfg{'queueuser'} . "\n\n";
	}
	else { # assuming SGE or derivative if not slurm
		$jobfile .= "#!/bin/sh\n";
		if ($runsupplement) {
			$jobfile .= "#\$ -N $pipelinename-supplement\n";
		}
		else {
			$jobfile .= "#\$ -N $pipelinename\n";
		}
		$jobfile .= "#\$ -S /bin/bash\n";
		$jobfile .= "#\$ -j y\n";
		$jobfile .= "#\$ -o $analysispath/pipeline\n";
		$jobfile .= "#\$ -V\n";
		$jobfile .= "#\$ -u " . $cfg{'queueuser'} . "\n";
		if ($maxwalltime > 0) {
			my $hours = floor($maxwalltime/60);
			my $min = $maxwalltime%60;
			
			$jobfile .= "#\$ -l h_rt=" . sprintf("%.2d:%.2d:00",$hours,$min) . "\n";
		}
	}
	
	$jobfile .= "echo Hostname: `hostname`\n";
	$jobfile .= "echo Hostname: `whoami`\n\n";
	if ((trim($resultscript) ne "") && ($rerunresults)) {
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid startedrerun 'Cluster processing started'\n";
	}
	elsif ($runsupplement) {
		$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid startedsupplement 'Supplement processing started'\n";
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
			my $issupplement = trim($pipelinesteps[$i]{'supplement'});
			my $command = trim($pipelinesteps[$i]{'command'});
			my $workingdir = trim($pipelinesteps[$i]{'workingdir'});
			my $description = trim($pipelinesteps[$i]{'description'});
			my $logged = $pipelinesteps[$i]{'logged'};
			my $enabled = $pipelinesteps[$i]{'enabled'};
			my $checkedin = 1;
			my $profile = 0;
			
			my $supplement;
			if ($issupplement) { $supplement = "supplement-"; } else { $supplement = ""; }

			# check if we are operating on regular commands or supplement commands
			#WriteLog("PRE: runsupplement [$runsupplement] issupplement [$issupplement] - $command");
			
			if (($runsupplement eq '1') && ($issupplement eq '0')) { next; }
			if (($runsupplement == 1) && ($issupplement == 0)) { next; }
			if (($runsupplement == 1) && ($issupplement eq '')) { next; }
			
			if (($runsupplement eq '0') && ($issupplement eq '1')) { next; }
			if (($runsupplement == 0) && ($issupplement == 1)) { next; }
			if (($runsupplement eq '') && ($issupplement == 1)) { next; }

			#WriteLog("POST: runsupplement [$runsupplement] issupplement [$issupplement] - $command");

			if (($command =~ m/\{NOLOG\}/) || ($description =~ m/\{NOLOG\}/)) { $logged = 0; }
			if (($command =~ m/\{NOCHECKIN\}/) || ($description =~ m/\{NOCHECKIN\}/)) { $checkedin = 0; }
			if (($command =~ m/\{PROFILE\}/) || ($description =~ m/\{PROFILE\}/)) { $profile = 1; }
			
			# format the command (replace pipeline variables, etc)
			if ($usetmpdir) {
				$command = FormatCommand($pipelineid, $realanalysispath, $command, $workinganalysispath, $analysisid, $uid, $studynum, $studydatetime, $pipelinename, $workingdir, $description);
			}
			else {
				$command = FormatCommand($pipelineid, $realanalysispath, $command, $analysispath, $analysisid, $uid, $studynum, $studydatetime, $pipelinename, $workingdir, $description);
			}
			
			if ($checkedin) {
				my $cleandesc = $description;
				$cleandesc =~ s/'//g;
				$cleandesc =~ s/"//g;
				$jobfile .= "\nperl /opt/pipeline/$checkinscript $analysisid processing 'processing $supplement"."step " . ($i + 1) . " of " . ($#pipelinesteps + 1) . "' '$cleandesc'";
				
				$jobfile .= "\n# $description\necho Running $command\n";
			}
			
			# prepend with 'time' if the neither NOLOG nor NOCHECKIN are specified
			if ($profile && $logged && $checkedin) {
				$command = "/usr/bin/time -v $command";
			}
			
			# write to a log file if logging is requested
			if ($logged) { $command .= " >> $analysispath/pipeline/$supplement"."step$i.log 2>&1"; }
			
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
	else {
		# run the results import script
		my $resultcommand = FormatCommand($pipelineid, $realanalysispath, $resultscript, $analysispath, $analysisid, $uid, $studynum, $studydatetime, $pipelinename, '', '');
		$resultcommand .= " > $analysispath/pipeline/stepResultScript.log 2>&1";
		$jobfile .= "\nperl /opt/pipeline/$checkinscript $analysisid processing 'Processing result script'\n# Running result script\necho Running $resultcommand\n";
		$jobfile .= "$resultcommand\n";
	
		# clean up and log everything
		$jobfile .= "chmod -Rf 777 $analysispath\n";
		if ($runsupplement) {
			$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid processing 'Updating analysis files'\n";
			$jobfile .= "perl /opt/pipeline/UpdateAnalysisFiles.pl -a $analysisid -d $analysispath\n";
			$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid processing 'Checking for completed files'\n";
			$jobfile .= "perl /opt/pipeline/CheckCompleteResults.pl -a $analysisid -d $analysispath\n";
			$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid completesupplement 'Supplement processing complete'\n";
		}
		else {
			$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid processing 'Updating analysis files'\n";
			$jobfile .= "perl /opt/pipeline/UpdateAnalysisFiles.pl -a $analysisid -d $analysispath\n";
			$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid processing 'Checking for completed files'\n";
			$jobfile .= "perl /opt/pipeline/CheckCompleteResults.pl -a $analysisid -d $analysispath\n";
			$jobfile .= "perl /opt/pipeline/$checkinscript $analysisid complete 'Cluster processing complete'\n";
		}
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
		$command =~ s/\{NOCHECKIN\}//g; # remove any {NOCHECKIN} commands
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
	my ($studyid, $analysispath, $uid, $analysisid, $pipelineversion, $pid, $pipelinedep, $deplevel, @datadef) = @_;
	WriteLog("Inside GetData($analysispath)");
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $numdownloaded = 0;
	my $datalog = "";
	my $datareport = "";
	my $datatable = "Empty data table";
	
	# get pipeline information, for data copying preferences
	my $sqlstring = "select * from pipelines where pipeline_id = $pid";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	my %row = $result->fetchhash;
	my $modality = $row{'study_modality'};
	my $clustertype = $row{'pipeline_clustertype'};
	my $pipelinequeue = $row{'pipeline_queue'};
	my $pipelinedatacopymethod = $row{'pipeline_datacopymethod'};
	my $pipelinesubmithost;
	if ($row{'pipeline_submithost'} eq "") { $pipelinesubmithost = $cfg{'clustersubmithost'}; }
	else { $pipelinesubmithost = $row{'pipeline_submithost'}; }
	my $clusteruser;
	if ($row{'pipeline_clusteruser'} eq "") { $clusteruser = $cfg{'clusteruser'}; }
	else { $clusteruser = $row{'pipeline_clusteruser'}; }
	
	#InsertAnalysisEvent($analysisid, $pid, $pipelineversion, $studyid, 'analysiscopydata', 'Checking for data');
	
	# get list of series for this study
	$sqlstring = "select study_modality, study_num from studies where study_id = $studyid";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		my $modality = $row{'study_modality'};
		my $studynum = $row{'study_num'};
		WriteLog("Found " . $result->numrows . " studies matching studyid [$studyid]");
		$datalog .= "===== Working on [$uid$studynum] Found " . $result->numrows . " studies matching studyid [$studyid] =====\n\n";
		
		WriteLog("Study modality is [$modality]");
		$datalog .= "-----------------------------------------------\n";
		$datalog .= "---------- Checking data steps ----------------\n";
		$datalog .= "-----------------------------------------------\n";
		
		my $datarows;
		$datarows->[0]->[0] = 'step';
		$datarows->[0]->[1] = 'enabled';
		$datarows->[0]->[2] = 'optional';
		$datarows->[0]->[3] = 'protocol';
		$datarows->[0]->[4] = 'modality';
		$datarows->[0]->[5] = 'imagetype';
		$datarows->[0]->[6] = 'type';
		$datarows->[0]->[7] = 'level';
		$datarows->[0]->[8] = 'assoctype';
		$datarows->[0]->[9] = 'numboldreps';
		$datarows->[0]->[10] = 'FOUND';
		$datarows->[0]->[11] = 'dir';
		$datarows->[0]->[12] = 'explanation';
		
		# ------------------------------------------------------------------------
		# check all of the steps to see if this data spec is valid
		# ------------------------------------------------------------------------
		my $stepIsInvalid = 0;
		foreach my $i(0..$#datadef) {
			my $protocol = $datadef[$i]{'protocol'};
			my $modality = lc($datadef[$i]{'modality'});
			my $imagetype = $datadef[$i]{'imagetype'};
			my $enabled = $datadef[$i]{'enabled'};
			my $type = $datadef[$i]{'type'};
			my $level = $datadef[$i]{'level'};
			my $assoctype = $datadef[$i]{'assoctype'};
			my $optional = $datadef[$i]{'optional'};
			my $numboldreps = trim($datadef[$i]{'numboldreps'});

			# use the correct SQL schema column name for the series_desc...
			my $seriesdescfield = 'series_desc';
			if ($modality ne 'mr') { $seriesdescfield = 'series_protocol'; }
			
			$datalog .= "Step [$i], CHECKING:
        protocol [$protocol]
        modality [$modality]
        imagetype [$imagetype]
        enabled [$enabled]
        type [$type]
        level [$level]
        assoctype [$assoctype]
        optional [$optional]
        numboldreps [$numboldreps]\n";
		
			$datarows->[$i+1]->[0] = $i;
			$datarows->[$i+1]->[1] = $enabled;
			$datarows->[$i+1]->[2] = $optional;
			$datarows->[$i+1]->[3] = $protocol;
			$datarows->[$i+1]->[4] = $modality;
			$datarows->[$i+1]->[5] = $imagetype;
			$datarows->[$i+1]->[6] = $type;
			$datarows->[$i+1]->[7] = $level;
			$datarows->[$i+1]->[8] = $assoctype;
			$datarows->[$i+1]->[9] = $numboldreps;
			$datarows->[$i+1]->[10] = "";
			$datarows->[$i+1]->[11] = "";
			$datarows->[$i+1]->[12] = "";
			
			# check if the step is enabled
			if (!$enabled) {
				$datarows->[$i+1]->[12] = "This step is not enabled, skipping";
				$datalog .= "    Data specification step [$i] is NOT enabled. Skipping this download step\n"; next;
			}
			
			# check if the step is optional
			if ($optional) {
				$datarows->[$i+1]->[12] = "This step is optional, skipping the checks";
				$datalog .= "    Data step [$i] is OPTIONAL. Ignoring the checks\n"; next;
			}
				
			# make sure the requested modality table exists
			$sqlstring = "show tables like '$modality"."_series'";
			my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
			if ($result->numrows < 1) {
				$datalog .= "    ERROR: modality [$modality] not found\n";
				$datarows->[$i+1]->[10] = "no";
				$stepIsInvalid = 1;
				last;
			}
			
			# seperate any protocols that have multiples
			my $protocols;
			if ($protocol =~ /\"/) {
				my @prots = shellwords($protocol);
				$protocols = "'" . join("','", @prots) . "'";
			}
			else {
				$protocols = "'$protocol'";
			}
			
			# separate image types
			my $imagetypes;
			if ($imagetype =~ /,/) {
				my @types = split(/,\s*/, $imagetype);
				$imagetypes = "'" . join("','", @types) . "'";
			}
			else {
				$imagetypes = "'$imagetype'";
			}
			
			# expand the comparison into SQL
			my ($comparison, $num) = GetSQLComparison($numboldreps);
			
			# if its a subject level, check the subject for the protocol(s)
			if ($level eq "subject") {
				$datalog .= "    This data step is subject level [$protocol], association type [$assoctype]\n";
				# get the subject ID and study type, based on the current study ID
				my $sqlstringA = "select b.subject_id, a.study_type, a.study_datetime from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where a.study_id = $studyid";
				my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
				WriteLog($sqlstringA);
				if ($resultA->numrows > 0) {
					my %rowA = $resultA->fetchhash;
					my $subjectid = $rowA{'subject_id'};
					my $studytype = $rowA{'study_type'};
					my $studydate = $rowA{'study_datetime'};
					
					if (($assoctype eq 'nearesttime') || ($assoctype eq 'nearestintime')) {
						# find the data from the same subject and modality that has the nearest (in time) matching scan
						WriteLog("Searching for subject-level data nearest in time...");
						$sqlstring = "SELECT *, `$modality" . "_series`.$modality" . "series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `$modality" . "_series` on `$modality" . "_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '$modality' AND `subjects`.subject_id = $subjectid AND trim(`$modality" . "_series`.$seriesdescfield) in ($protocols)";
						if ($imagetypes ne "''") {
							$sqlstring .= " and `$modality" . "_series`.image_type in ($imagetypes)";
						}
						$sqlstring .= " ORDER BY ABS( DATEDIFF( `$modality" . "_series`.series_datetime, '$studydate' ) ) LIMIT 1";
						$datalog .= "    Searching for subject-level data nearest in time [$sqlstring]\n";
						
						$datarows->[$i+1]->[12] = "Searching for data from the same SUBJECT and modality that has the nearest (in time) matching scan";
					}
					elsif ($assoctype eq 'all') {
						WriteLog("Searching for all subject-level data...");
						$sqlstring = "SELECT *, `$modality" . "_series`.$modality" . "series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `$modality" . "_series` on `$modality" . "_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '$modality' AND `subjects`.subject_id = $subjectid AND trim(`$modality" . "_series`.$seriesdescfield) in ($protocols)";
						if ($imagetypes ne "''") {
							$sqlstring .= " and `$modality" . "_series`.image_type in ($imagetypes)";
						}
						$datalog .= "    Searching for all subject-level data [$sqlstring]\n";
						
						$datarows->[$i+1]->[12] = "Searching for ALL data from the same SUBJECT and modality";
					}
					else {
						# find the data from the same subject and modality that has the same study_type
						WriteLog("Searching for subject-level data with same study type...");
						$sqlstring = "SELECT *, `$modality" . "_series`.$modality" . "series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `$modality" . "_series` on `$modality" . "_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '$modality' AND `subjects`.subject_id = $subjectid AND trim(`$modality" . "_series`.$seriesdescfield) in ($protocols)";
						if ($imagetypes ne "''") {
							$sqlstring .= " and `$modality" . "_series`.image_type in ($imagetypes)";
						}
						$sqlstring .= " and `studies`.study_type = '$studytype'";
						$datalog .= "    Searching for subject-level data with same study type [$sqlstring]\n";
						
						$datarows->[$i+1]->[12] = "Searching for data from the same SUBJECT and modality, with the same study type (visit)";
					}
				}
				WriteLog($sqlstring);
				my $newseriesnum = 1;
				my $resultC = SQLQuery($sqlstring, __FILE__, __LINE__);
				if ($resultC->numrows > 0) {
					$datalog .= "    Found " . $resultC->numrows . " matching subject-level series\n";
					$datarows->[$i+1]->[10] = "yes (".$resultC->numrows.")";
				}
				else {
					$datalog .= "    Found 0 rows matching the subject-level required protocol(s)\n";
					$stepIsInvalid = 1;
					$datarows->[$i+1]->[10] = "no";
					last;
				}
			}
			# otherwise, check the study for the protocol(s)
			else {
				$datalog .= "    Checking the study [$studyid] for the protocol ($protocols)\n";
				# get a list of series satisfying the search criteria, if it exists
				if (($comparison eq "") || ($num == 0)) {
					$sqlstring = "select * from $modality"."_series where study_id = $studyid and (trim($seriesdescfield) in ($protocols))";
					if ($imagetypes ne "''") {
						$sqlstring .= " and image_type in ($imagetypes)";
					}
				}
				else {
					$sqlstring = "select * from $modality"."_series where study_id = $studyid and (trim($seriesdescfield) in ($protocols))";
					if ($imagetypes ne "''") {
						$sqlstring .= " and image_type in ($imagetypes)";
					}
					$sqlstring .= " and numfiles $comparison $num";
				}
				WriteLog($sqlstring);
				$datalog .= "    Checking if study contains data [$sqlstring]\n";
				my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
				if ($result->numrows > 0) {
					$datalog .= "    This study contained step [$i]\n";
				}
				else {
					$datalog .= "    This study did NOT contain this required step [$i]\n";
					$stepIsInvalid = 1;
					last;
				}
			}
		}
		$datalog .= "---------- Done checking data steps ----------\n";

		# if it's a subject level dependency, but there is no data found, we don't want to copy any dependencies
		if (($stepIsInvalid) && ($deplevel eq "subject")) {
			my $datatable = generate_table(rows => $datarows, header_row => 1);
		
			# bail out of this function, the data spec didn't work out for this subject
			return (0,$datalog, $datareport, $datatable);
		}
		
		# if there is a dependency, don't worry about the previous checks
		if ($pipelinedep != 0) {
			$stepIsInvalid = 0;
		}
		
		# check for any bad data items
		if ($stepIsInvalid) {
			my $datatable = generate_table(rows => $datarows, header_row => 1);
		
			# bail out of this function, the data spec didn't work out for this subject
			return (0,$datalog, $datareport, $datatable);
		}
		
		
		# ------ end checking the data steps --------------------------------------
		# if we get to here, the data spec is valid for this study
		# so we can assume all of the data exists, and start copying it
		# -------------------------------------------------------------------------
		
		WriteLog("Modality: $modality");

		InsertAnalysisEvent($analysisid, $pid, $pipelineversion, $studyid, 'analysiscopydata', "Started copying data to [<tt>$analysispath</tt>]");
		
		$datalog .= "\n\n------------------------------------------------------------------------------\n";
		$datalog .= "---------- Required data for this study exists, beginning data copy ----------\n";
		$datalog .= "------------------------------------------------------------------------------\n";
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
			my $usephasedir = $datadef[$i]{'usephasedir'};
			my $behformat = $datadef[$i]{'behformat'};
			my $behdir = $datadef[$i]{'behdir'};
			my $enabled = $datadef[$i]{'enabled'};
			my $level = $datadef[$i]{'level'};
			my $optional = $datadef[$i]{'optional'};
			my $numboldreps = trim($datadef[$i]{'numboldreps'});
			
			# seperate any protocols that have multiples
			my $protocols;
			if ($protocol =~ /\"/) {
				my @prots = shellwords($protocol);
				$protocols = "'" . join("','", @prots) . "'";
			}
			else {
				$protocols = "'$protocol'";
			}
			# separate image types
			my $imagetypes;
			if ($imagetype =~ /,/) {
				my @types = split(/,\s*/, $imagetype);
				$imagetypes = "'" . join("','", @types) . "'";
			}
			else {
				$imagetypes = "'$imagetype'";
			}

			WriteLog("Working on step [$i] id [$id]");

			# expand the comparison into SQL
			my ($comparison, $num) = GetSQLComparison($numboldreps);
			#WriteLog("BOLD reps comparison [$comparison] [$num]");

			$datalog .= "Copying data for step [$i] id [$id]\n";
			#$datalog .= "    bold reps comparison [$comparison] [$num]\n";

			# check to see if we should even run this step
			if ($enabled) {
				#WriteLog("---------- Checking for $protocol for $modality ----------");
				#$datalog .= "---------- Checking for protocol [$protocol] modality [$modality] imagetype [$imagetype] ----------\n";
				my $neareststudynum = "";
				$modality = lc($modality);
				# use the correct SQL schema column name for the series_desc...
				my $seriesdescfield = 'series_desc';
				if ($modality ne 'mr') { $seriesdescfield = 'series_protocol'; }
				# make sure the requested modality table exists
				$sqlstring = "show tables like '$modality"."_series'";
				my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
				if ($result->numrows > 0) {
				
					# get a list of series satisfying the search criteria, if it exists
					if ($level eq 'study') {
						#WriteLog("BOLD reps comparison [$comparison] [$num] inside StudyLevel");
						#$datalog .= "    BOLD reps check: comparison [$comparison] num [$num] inside StudyLevel\n";
						
						$datalog .= "    This data step is study-level\n        protocols [$protocols]\n        criteria [$criteria]\n        imagetype [$imagetype]\n";
						
						$sqlstring = "select * from $modality"."_series where study_id = $studyid and (trim($seriesdescfield) in ($protocols))";
						if ($imagetypes ne "''") { $sqlstring .= " and image_type in ($imagetypes)"; }
						
						switch ($criteria) {
							case "first" { $sqlstring .= " order by series_num asc limit 1"; }
							case "last" { $sqlstring .= " order by series_num desc limit 1"; }
							case "largestsize" { $sqlstring .= " order by series_size desc, numfiles desc, img_slices desc limit 1"; }
							case "smallestsize" { $sqlstring .= " order by series_size asc, numfiles asc, img_slices asc limit 1"; }
							case "usesizecriteria" { $sqlstring .= " and numfiles $comparison '$num' order by series_num asc"; }
							else { $sqlstring .= " order by series_num asc"; }
						}
						
						$datalog .= "    ... at the end of the study level section, SQL [$sqlstring]\n";
					}
					else {
						$datalog .= "    This data step is subject-level\n        protocols[$protocols]\n        association type [$assoctype]\n        imagetype [$imagetypes]\n";
						# get the subject ID and study type, based on the current study ID
						my $sqlstringA = "select b.subject_id, a.study_type, a.study_datetime from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where a.study_id = $studyid";
						my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
						#WriteLog($sqlstringA);
						if ($resultA->numrows > 0) {
							my %rowA = $resultA->fetchhash;
							my $subjectid = $rowA{'subject_id'};
							my $studytype = $rowA{'study_type'};
							my $studydate = $rowA{'study_datetime'};
							
							if (($assoctype eq 'nearesttime') || ($assoctype eq 'nearestintime')) {
								# find the data from the same subject and modality that has the nearest (in time) matching scan
								WriteLog("Searching for subject-level data nearest in time...");
								$datalog .= "    Searching for subject-level data nearest in time\nSearching for nearest study first...";
								# get the otherstudyid
								$sqlstringA = "SELECT `studies`.study_id, `studies`.study_num FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `$modality" . "_series` on `$modality" . "_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '$modality' AND `subjects`.subject_id = $subjectid AND trim(`$modality" . "_series`.$seriesdescfield) in ($protocols)";
								if ($imagetypes ne "''") {
									$sqlstringA .= "and `$modality" . "_series`.image_type in ($imagetypes)";
								}
								$sqlstringA .= " ORDER BY ABS( DATEDIFF( `$modality" . "_series`.series_datetime, '$studydate' ) ) LIMIT 1";
								my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
								#WriteLog($sqlstringA);
								my $otherstudyid;
								if ($resultA->numrows > 0) {
									my %rowA = $resultA->fetchhash;
									$otherstudyid = $rowA{'study_id'};
									$neareststudynum = $rowA{'study_num'};
								}
								else {
									WriteLog("Could not find a matching row: [$sqlstring]");
									# this data item is probably optional, so go to the next item
									next;
								}
								
								$datalog .= "    Still within the subject-level data search\n        protocols [$protocols]\n        criteria [$criteria]\n        imagetype [$imagetypes]\n";

								# base SQL string
								$sqlstring = "select * from $modality"."_series where study_id = $otherstudyid and trim($seriesdescfield) in ($protocols)";
								if ($imagetypes ne "''") {
									$sqlstring .= " and image_type in ($imagetypes)";
								}
								
								# determine the ORDERing and LIMITs
								switch ($criteria) {
									case "first" { $sqlstring .= " order by series_num asc limit 1"; }
									case "last" { $sqlstring .= " order by series_num desc limit 1"; }
									case "largestsize" { $sqlstring .= " order by series_size desc, numfiles desc, img_slices desc limit 1"; }
									case "smallestsize" { $sqlstring .= " order by series_size asc, numfiles asc, img_slices asc limit 1"; }
									case "usesizecriteria" { $sqlstring .= " and numfiles $comparison '$num' order by series_num asc"; }
									else { $sqlstring .= " order by series_num asc"; }
								}
								
								$datalog .= "    ... now searching for the data IN the nearest study\n";
								
							}
							elsif ($assoctype eq 'all') {
								WriteLog("Searching for all subject-level data...");
								$sqlstring = "SELECT *, `$modality" . "_series`.$modality" . "series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `$modality" . "_series` on `$modality" . "_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '$modality' AND `subjects`.subject_id = $subjectid AND trim(`$modality" . "_series`.$seriesdescfield) in ($protocols)";
								if ($imagetypes ne "''") {
									$sqlstring .= " and `$modality" . "_series`.image_type in ($imagetypes)";
								}
								if (($comparison != 0) && ($num != 0)) {
									$sqlstring .= " and numfiles $comparison $num";
								}
								$datalog .= "    Searching for all subject-level data [$sqlstring]\n";
							}
							else {
								# find the data from the same subject and modality that has the same study_type
								WriteLog("Searching for subject-level data with same study type...");
								$sqlstring = "SELECT *, `$modality" . "_series`.$modality" . "series_id FROM `enrollment` JOIN `projects` on `enrollment`.project_id = `projects`.project_id JOIN `subjects` on `subjects`.subject_id = `enrollment`.subject_id JOIN `studies` on `studies`.enrollment_id = `enrollment`.enrollment_id JOIN `$modality" . "_series` on `$modality" . "_series`.study_id = `studies`.study_id WHERE `subjects`.isactive = 1 AND `studies`.study_modality = '$modality' AND `subjects`.subject_id = $subjectid AND trim(`$modality" . "_series`.$seriesdescfield) in ($protocols)";
								if ($imagetypes ne "''") {
									$sqlstring .= " and `$modality" . "_series`.image_type in ($imagetypes)";
								}
								if (($comparison != 0) && ($num != 0)) {
									$sqlstring .= " and numfiles $comparison $num";
								}
								$sqlstring .= " and `studies`.study_type = '$studytype'";
								$datalog .= "    Searching for subject-level data with same study type [$sqlstring]\n";
							}
						}
					}
					WriteLog($sqlstring);
					$datalog .= "    Resulting SQL string [$sqlstring]\n";
					my $newseriesnum = 1;
					my $resultC = SQLQuery($sqlstring, __FILE__, __LINE__);
					if ($resultC->numrows > 0) {
					
						$datarows->[$i+1]->[10] = "yes";
					
						WriteLog("Found " . $resultC->numrows . " matching subject-level series");
						$datalog .= "    type [$type] data found [" . $resultC->numrows . "] rows, creating [$analysispath]\n";
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
							my $phaseplane = $rowC{'phaseencodedir'};
							my $phaseangle = $rowC{'phaseencodeangle'};
							my $phasepositive = $rowC{'PhaseEncodingDirectionPositive'};
							
							if ($datatype eq "") { $datatype = $modality; }
							
							if ($level eq 'study') {
								# studynum is not returned as part of this current result set, so reuse the studynum from outside this
								# resultset loop
								if ($neareststudynum eq '') {
									$localstudynum = $studynum;
								}
								else {
									$localstudynum = $neareststudynum;
								}
							}
							else {
								if ($neareststudynum eq '') {
									$localstudynum = $rowC{'study_num'};
								}
								else {
									$localstudynum = $neareststudynum;
								}
							}
							WriteLog("Processing $seriesdesc [$seriessize bytes] [$numfiles files]");
							$datalog .= "    Working on seriesdesc [$seriesdesc] seriesnum [$seriesnum] seriesdatetime [$seriesdatetime]...\n";
							my $behoutdir = "";
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
							else {
								if ($behformat eq "behroot") { $behoutdir = "$analysispath/$location"; }
								if ($behformat eq "behrootdir") { $behoutdir = "$analysispath/$location/$behdir"; }
								if ($behformat eq "behseries") { $behoutdir = "$analysispath/$location/$seriesnum"; }
								if ($behformat eq "behseriesdir") { $behoutdir = "$analysispath/$location/$seriesnum/$behdir"; }
							}
							
							$datalog .= "    behformat [$behformat] behoutdir [$behoutdir]\n";
							if ($usephasedir) {
								my $phasedir = "unknownPE";
								
								$datalog .= "    PhasePlane [$phaseplane] PhasePositive [$phasepositive]\n";
								if (($phaseplane eq "COL") && ($phasepositive eq "1")) { $phasedir = "AP"; }
								if (($phaseplane eq "COL") && ($phasepositive eq "0")) { $phasedir = "PA"; }
								if (($phaseplane eq "COL") && ($phasepositive eq "")) { $phasedir = "COL"; }
								if (($phaseplane eq "ROW") && ($phasepositive eq "1")) { $phasedir = "RL"; }
								if (($phaseplane eq "ROW") && ($phasepositive eq "0")) { $phasedir = "LR"; }
								if (($phaseplane eq "ROW") && ($phasepositive eq "")) { $phasedir = "ROW"; }
								
								$newanalysispath = $newanalysispath . "/$phasedir";
							}
							
							$datalog .= "    Creating directory [$newanalysispath]\n";
							MakePath($newanalysispath);
							#mkpath($newanalysispath, {mode => 0777});
							my $systemstring = "chmod -Rf 777 $newanalysispath";
							$datalog .= "    Changing permissions [$systemstring]\n";
							`$systemstring 2>&1`;
							
							# output the correct file type
							if (($dataformat eq "dicom") || (($datatype ne "dicom") && ($datatype ne "parrec"))) {
								if ($pipelinedatacopymethod eq "scp") {
									$systemstring = "scp $indir/* $cfg{'clusteruser'}\@$pipelinesubmithost:$newanalysispath";
								}
								else {
									$systemstring = "cp -v $indir/* $newanalysispath";
								}

								my $output = trim(`$systemstring 2>&1`);
								WriteLog("Copying data using command (DICOM) [$systemstring] output [$output]");
								$datalog .= "    Copying dats [$systemstring] output [$output]\n";
							}
							else {
								my $tmpdir = $cfg{'tmpdir'} . "/" . GenerateRandomString(10);
								MakePath($tmpdir);
								$datalog .= "    Created temp directory [$tmpdir]\n";
								$datalog .= "    Calling ConvertDicom($dataformat, $indir, $tmpdir, $gzip, $uid, $localstudynum, $seriesnum, $datatype)\n";
								ConvertDicom($dataformat, $indir, $tmpdir, $gzip, $uid, $localstudynum, $seriesnum, $datatype);
								
								if ($pipelinedatacopymethod eq "scp") {
									$systemstring = "scp $tmpdir/* $cfg{'clusteruser'}\@$pipelinesubmithost:$newanalysispath";
								}
								else {
									$systemstring = "cp -v $tmpdir/* $newanalysispath";
								}
								
								my $output = trim(`$systemstring 2>&1`);
								$datalog .= "    Copying data [$systemstring] output [$output]\n";
								WriteLog("Copying data using command (ConvertToNifti) [$systemstring] output [$output]");
								
								WriteLog("Removing temp directory [$tmpdir]");
								$datalog .= "    Removing temp directory [$tmpdir]\n";
								remove_tree($tmpdir);
							}
						
							$datarows->[$i+1]->[11] = $newanalysispath;
							
							# copy the beh data
							if ($behformat ne "behnone") {
								$datalog .= "    Copying behavioral data\n";
								$datalog .= "        - Creating directory [$behoutdir]\n";
								MakePath($behoutdir);
								#mkpath($behoutdir, {mode => 0777});
								$systemstring = "cp -Rv $behindir/* $behoutdir";
								my $output = trim(`$systemstring 2>&1`);
								$datalog .= "        - Copying beh data [$systemstring] output [$output]\n";
								#`$systemstring 2>&1`;
								
								$systemstring = "chmod -Rf 777 $behoutdir";
								$output = trim(`$systemstring 2>&1`);
								$datalog .= "        - Changing permissions [$systemstring] output [$output]\n";
								#`$systemstring 2>&1`;
								$datalog .= "        Done copying behavioral data...\n";
							}

							# give full read/write permissions to everyone
							$systemstring = "chmod -Rf 777 $newanalysispath";
							my $output = trim(`$systemstring 2>&1`);
							$datalog .= "    Changing permissions [$systemstring] output [$output]\n";
							#`$systemstring 2>&1`;
							
							WriteLog("Done writing data to $newanalysispath");
							
							$datalog .= "    Done writing data to $newanalysispath\n";
						}
					}
					else {
						WriteLog("Found no matching subject-level [$protocol] series. SQL: [$sqlstring]");
						$datalog .= "    Found no matching subject-level data [$protocol]. SQL: [$sqlstring]\n";
					}
				}
				else {
					WriteLog("No matching modality [$modality] tables found");
					$datalog .= "    No matching modality [$modality] tables found\n";
				}
			}
			else {
				WriteLog("Data step [$i] not enabled");
				$datalog .= "    This data item [$protocol] is not enabled\n";
			}
		}
		#WriteLog(Dumper($datarows));
		my $datatable = generate_table(rows => $datarows, header_row => 1);
		WriteLog("\n$datatable");
		$datalog .= "\n$datatable\nLeaving GetData(). Copied [$numdownloaded] series\n";
		WriteLog("Leaving GetData() successfully => ret($numdownloaded, datalog)");
		InsertAnalysisEvent($analysisid, $pid, $pipelineversion, $studyid, 'analysiscopydataend', "Finished copying data [$numdownloaded] series downloaded");
		return ($numdownloaded, $datalog, $datareport, $datatable);
	}
	else {
		$datareport .= "Study [$studyid] does not exist";
		$datalog .= "Study [$studyid] does not exist - No data to download\n";
		WriteLog("Leaving GetData() unsuccessfully => ret(0, $datalog). This study had no series at all");
		return (0,$datalog, $datareport, $datatable);
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
	
	#WriteLog("Checkin A");
	# get list of studies which do not have an entry in the analysis table for this pipeline
	if (($depend ne '') && ($depend != 0)) {
		# there is a dependency
		# need to check if ANY of the subject's studies have the dependency...
		# step 1) get list of SUBJECTs who have completed the dependency 
		$sqlstring = "select a.study_id from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id in (select a.subject_id from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.study_id in (select study_id from analysis where pipeline_id in ($depend) and analysis_status = 'complete' and analysis_isbad <> 1) and a.isactive = 1)";
		WriteLog("StudyIDList SQL [$sqlstring]");
		#WriteLog("Checkin B.1");
		my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
		#WriteLog("Checkin K");
		my @list;
		while (my %row = $result->fetchhash) {
			push @list,$row{'study_id'};
		}
		my $studyidlist = join(',',@list);
		
		if ($studyidlist eq "") { $studyidlist = "0"; }
		
		#WriteLog("Checkin B.2");
		# step 2) then find all STUDIES that those subjects have completed
		if ($groupids eq "") {
			# with no groupids
			$sqlstring = "select study_id from studies where study_id not in (select study_id from analysis where pipeline_id = $pipelineid) and study_id in ($studyidlist) and (study_datetime < date_sub(now(), interval 6 hour)) order by study_datetime desc";
			WriteLog("GetStudyToDoList(): dependency and no groupids [$sqlstring]");
			#WriteLog("Checkin C");
		}
		else {
			# with groupids
			$sqlstring = "select a.study_id from studies a left join group_data b on a.study_id = b.data_id where a.study_id not in (select study_id from analysis where pipeline_id = $pipelineid) and a.study_id in ($studyidlist) and (a.study_datetime < date_sub(now(), interval 6 hour)) and b.group_id in ($groupids) order by a.study_datetime desc";
			WriteLog("GetStudyToDoList(): dependency and groupids [$sqlstring]");
			#WriteLog("Checkin D");
		}
	}
	else {
		# no dependency
		if ($groupids eq "") {
			# with no groupids
			$sqlstring = "select study_id from studies where study_id not in (select study_id from analysis where pipeline_id = $pipelineid) and (study_datetime < date_sub(now(), interval 6 hour)) and study_modality = '$modality' order by study_datetime desc";
			WriteLog("GetStudyToDoList(): No dependency and no groupids [$sqlstring]");
			#WriteLog("Checkin E");
		}
		else {
			# with groupids
			$sqlstring = "SELECT a.study_id FROM studies a left join group_data b on a.study_id = b.data_id WHERE a.study_id NOT IN (SELECT study_id FROM analysis WHERE pipeline_id = $pipelineid) AND ( a.study_datetime < DATE_SUB( NOW( ) , INTERVAL 6 hour )) AND a.study_modality =  '$modality' and b.group_id in ($groupids) ORDER BY a.study_datetime DESC";
			WriteLog("GetStudyToDoList(): No dependency and groupids [$sqlstring]");
			#WriteLog("Checkin F");
		}
	}
	
	my @list = ();
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	#WriteLog("Pushing all the studyids onto an array");
	if ($result->rows > 0) {
		while (my $row = $result->fetchrow_hashref()) {
			my $studyid = $row->{study_id};
			
			my $sqlstringA = "select b.study_num, c.uid from enrollment a left join studies b on a.enrollment_id = b.enrollment_id left join subjects c on a.subject_id = c.subject_id where b.study_id = $studyid and c.isactive = 1";
			my $resultA = SQLQuery($sqlstringA, __FILE__, __LINE__);
			my %rowA = $resultA->fetchhash;
			my $uid = $rowA{'uid'};
			my $studynum = $rowA{'study_num'};

			if (!defined($studynum)) {
				#WriteLog("Studynum is blank");
			}
			elsif (!defined($uid)) {
				#WriteLog("UID is blank");
			}
			else {
				WriteLog("Found study [" . $studyid . "] [$uid$studynum]");
				push @list,$studyid;
			}
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
			WriteLog("Found study (results rerun) [" . $studyid . "]");
			push @list,$studyid;
		}
	}
	
	# now get just the studies that need to have their supplements run
	$sqlstring = "select study_id from studies where study_id in (select study_id from analysis where pipeline_id = $pipelineid and analysis_runsupplement = 1 and analysis_status = 'complete' and analysis_isbad <> 1) order by study_datetime desc";
	WriteLog($sqlstring);
	$result = $dbh->prepare($sqlstring);
	$result->execute();
	#WriteLog($sqlstring);
	if ($result->rows > 0) {
		while (my $row = $result->fetchrow_hashref()) {
			my $studyid = $row->{study_id};
			WriteLog("Found study (supplement run) [" . $studyid . "]");
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
	#WriteLog("Checkin from GetPipelineDataDef");
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
			$rec->{'usephasedir'} = $row{'pdd_usephasedir'};
			$rec->{'behformat'} = $row{'pdd_behformat'};
			$rec->{'behdir'} = $row{'pdd_behdir'};
			$rec->{'enabled'} = $row{'pdd_enabled'};
			$rec->{'optional'} = $row{'pdd_optional'};
			$rec->{'numboldreps'} = $row{'pdd_numboldreps'};
			$rec->{'level'} = $row{'pdd_level'};
			
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
			$rec->{'supplement'} = $row{'ps_supplement'};
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
	my ($pid) = @_;

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring = "update pipelines set pipeline_status = 'stopped', pipeline_lastfinish = now() where pipeline_id = '$pid'";
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
		$sqlstring = "insert ignore into pipeline_procs (pp_processid, pp_status, pp_startdate, pp_lastcheckin, pp_currentpipeline, pp_currentsubject, pp_currentstudy) values ($$,'started',now(),now(),0,0,0)";
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
		case "nifti4d" { $systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y $gzip -p n -i n -d n -f n -o '$outdir' *.$fileext"; }
		case "nifti3d" { $systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_3D.ini' -a y -e y $gzip -p n -i n -d n -f n -o '$outdir' *.$fileext"; }
		case "analyze4d" { $systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_4D.ini' -a y -e y $gzip -p n -i n -d n -f n -n n -s y -o '$outdir' *.$fileext"; }
		case "analyze3d" { $systemstring = "$cfg{'scriptdir'}/./dcm2nii -b '$cfg{'scriptdir'}/dcm2nii_3D.ini' -a y -e y $gzip -p n -i n -d n -f n -n n -s y -o '$outdir' *.$fileext"; }
		else { return(0,0,0,0,0,0); }
	}

	#WriteLog(CompressText("$systemstring (" . `$systemstring 2>&1` . ")"));
	WriteLog("Running [$systemstring] [" . trim(`$systemstring 2>&1`) . "]");

	# rename the files into something meaningful
	my ($numimg, $numhdr, $numnii, $numgz) = BatchRenameFiles($outdir, $series_num, $study_num, $uid);
	WriteLog("Done renaming files: $numimg, $numhdr, $numnii, $numgz");
	
	# gzip any remaining .nii files if they were supposed to be gzipped but weren't
	if ($req_gzip) {
		my $systemstring = "cd $outdir; gzip *.nii";
		WriteLog("Running [$systemstring]: [" . trim(`$systemstring 2>&1`) . "]");
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


# ----------------------------------------------------------
# --------- GetAnalysisInfo --------------------------------
# ----------------------------------------------------------
sub GetAnalysisInfo {
	my ($pid,$sid) = @_;
	
	# check if the analysis has an entry in the analysis table
	WriteLog("Checking if this analysis already exists");
	my $msg = "Checking if this analysis already exists\n";
	my $sqlstring = "select * from analysis where pipeline_id = $pid and study_id = $sid";
	WriteLog($sqlstring);
	$msg .= " [$sqlstring]";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	my %row = $result->fetchhash;
	my $analysisRowID = trim($row{'analysis_id'});
	my $rerunresults = trim($row{'analysis_rerunresults'});
	my $runsupplement = trim($row{'analysis_runsupplement'});
	WriteLog("analysisRowID [$analysisRowID]  rerunresults [$rerunresults]");
					
	return ($analysisRowID, $rerunresults, $runsupplement, $msg);
}


# ----------------------------------------------------------
# --------- CheckDependency --------------------------------
# ----------------------------------------------------------
sub CheckDependency {
	my ($sid,$pipelinedep) = @_;

	# check if the dependency exists
	my $sqlstring = "select * from analysis where study_id = '$sid' and pipeline_id = '$pipelinedep'";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows < 1) {
		return "NoMatchingStudyDependency";
	}
	
	# check if the dependency is complete
	$sqlstring = "select * from analysis where study_id = '$sid' and pipeline_id = '$pipelinedep' and analysis_status = 'complete'";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows < 1) {
		return "IncompleteDependency";
	}

	# check if the dependency is marked as bad
	$sqlstring = "select * from analysis where study_id = '$sid' and pipeline_id = '$pipelinedep' and analysis_isbad <> 1";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows < 1) {
		return "BadDependency";
	}
	
	return "";
}
