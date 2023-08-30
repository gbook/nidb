---
description: Details about how pipeline scripts are formatted for squirrel and NiDB
---

# Pipeline scripts

Pipeline scripts are meant to run in `bash`. They are traditionally formatted to run with a RHEL distribution such as CentOS or Rocky Linux. The scripts are bash compliant, but have some nuances that allow them to run more effectively under an NiDB pipeline setup.

The bash script is interpreted to run on a cluster. Some commands are added to your script to allow it to check in and give status to NiDB as it is running.

### The script

There is no need for a shebang line at the beginning (for example #!/bin/sh) because this script is only interested in the commands being run.

**Example script...**

```bash
export FREESURFER_HOME=/opt/freesurfer-6.0     #  The Freesurfer home directory (version) you want to use
export FSFAST_HOME=/opt/freesurfer-6.0/fsfast     #  Not sure if these next two are needed but keep them just in case
export MNI_DIR=/opt/freesurfer-6.0/mni     #  Not sure if these next two are needed but keep them just in case
source $FREESURFER_HOME/SetUpFreeSurfer.sh     #  MGH's shell script that sets up Freesurfer to run
export SUBJECTS_DIR={analysisrootdir}     #  Point to the subject directory you plan to use - all FS data will go there
freesurfer > {analysisrootdir}/version.txt     # {NOLOG} get the freesurfer version
perl /opt/pipeline/ImportFreesurferData.pl {analysisrootdir}/data analysis     #  import data. the perl program allows importing of multiple T1s
recon-all -hippocampal-subfields-T1 -no-isrunning -all -notal-check -subjid analysis     #  Autorecon all {PROFILE}
if tail -n 1 {analysisrootdir}/analysis/scripts/recon-all-status.log | grep 'finished without error' ; then touch {analysisrootdir}/reconallsuccess.txt; fi     # {NOLOG} {NOCHECKIN}
recon-all -subjid analysis -qcache     #  do the qcache step {PROFILE}
```

Before being submitted to the cluster, the script is passed through the NiDB interpreter, and the actual bash script will look like below. This script is running on subject `S2907GCS`, study `8`, under the `freesurferUnified6` pipeline. This script will then be submitted to the cluster.

**... script is submitted to the cluster**

```bash
#!/bin/sh
#$ -N freesurferUnified6
#$ -S /bin/bash
#$ -j y
#$ -o /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/
#$ -V
#$ -u onrc
#$ -l h_rt=72:00:00
LD_LIBRARY_PATH=/opt/pipeline/nidb/; export LD_LIBRARY_PATH;
echo Hostname: `hostname`
echo Username: `whoami`

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s started -m 'Cluster processing started'
cd /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6;

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 1 of 10'
# The Freesurfer home directory (version) you want to use
echo Running export FREESURFER_HOME=/opt/freesurfer-6.0
export FREESURFER_HOME=/opt/freesurfer-6.0 >> /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/Step1

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 2 of 10'
# Not sure if these next two are needed but keep them just in case
echo Running export FSFAST_HOME=/opt/freesurfer-6.0/fsfast
export FSFAST_HOME=/opt/freesurfer-6.0/fsfast >> /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/Step2

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 3 of 10'
# Not sure if these next two are needed but keep them just in case
echo Running export MNI_DIR=/opt/freesurfer-6.0/mni
export MNI_DIR=/opt/freesurfer-6.0/mni >> /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/Step3

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 4 of 10'
# MGH's shell script that sets up Freesurfer to run
echo Running source $FREESURFER_HOME/SetUpFreeSurfer.sh
source $FREESURFER_HOME/SetUpFreeSurfer.sh >> /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/Step4

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 5 of 10'
# Point to the subject directory you plan to use - all FS data will go there
echo Running export SUBJECTS_DIR=/home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6
export SUBJECTS_DIR=/home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6 >> /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/Step5

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 6 of 10'
# get the freesurfer version
echo Running freesurfer > /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/version.txt
freesurfer > /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/version.txt

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 7 of 10'
# import data. the perl program allows importing of multiple T1s
echo Running perl /opt/pipeline/ImportFreesurferData.pl /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/data analysis
perl /opt/pipeline/ImportFreesurferData.pl /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/data analysis >> /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/Step7

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 8 of 10'
# Autorecon all {PROFILE}
echo Running recon-all -hippocampal-subfields-T1 -no-isrunning -all -notal-check -subjid analysis
/usr/bin/time -v recon-all -hippocampal-subfields-T1 -no-isrunning -all -notal-check -subjid analysis >> /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/Step8
if tail -n 1 /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/analysis/scripts/recon-all-status.log | grep 'finished without error' ; then touch /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/reconallsuccess.txt; fi

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 10 of 10'
# do the qcache step {PROFILE}
echo Running recon-all -subjid analysis -qcache
/usr/bin/time -v recon-all -subjid analysis -qcache >> /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/Step10

/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'Processing result script'
# Running result script
echo Running perl /opt/pipeline/ParseFreesurferResults.pl -r /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6 -p /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/analysis/stats -a 3151385     #  dump results back into ado2 > /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/stepResults.log 2>&1
perl /opt/pipeline/ParseFreesurferResults.pl -r /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6 -p /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/analysis/stats -a 3151385     #  dump results back into ado2 > /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/stepResults.log 2>&1
chmod -Rf 777 /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6
/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'Updating analysis files'
/opt/pipeline/nidb/nidb cluster -u updateanalysis -a 3151385
/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'Checking for completed files'
/opt/pipeline/nidb/nidb cluster -u checkcompleteanalysis -a 3151385
/opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s complete -m 'Cluster processing complete'
chmod -Rf 777 /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6
```

**How to interpret the altered script**

1. Details for the grid engine are added at the beginning
   * This includes max wall time, output directories, run-as user, etc
   *   ```bash
       #!/bin/sh
       #$ -N freesurferUnified6
       #$ -S /bin/bash
       #$ -j y
       #$ -o /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/
       #$ -V
       #$ -u onrc
       #$ -l h_rt=72:00:00
       ```


2. Each command is changed to include logging and check-ins
   * ```
     /opt/pipeline/nidb/nidb cluster -u pipelinecheckin -a 3151385 -s processing -m 'processing step 1 of 10'
     # The Freesurfer home directory (version) you want to use
     echo Running export FREESURFER_HOME=/opt/freesurfer-6.0
     export FREESURFER_HOME=/opt/freesurfer-6.0 >> /home/pipeline/onrc/data/pipeline/S2907GCS/8/freesurferUnified6/pipeline/Step1
     ```
   * `nidb cluster -u pipelinecheckin` checks in to the database the current step. This is displayed on the Pipelines --> Analysis webpage
   * Each command is also echoed to the grid engine log file so you can check the log file for the status
   * The output of each command is echoed to a separate log file in the last line using the `>>`

### Pipeline Variables

There are a few pipeline variables that are interpreted by NiDB when running. **The variable is replaced with the value before the final script is written out.** Each study on which a pipeline runs will have a different script, with different paths, IDs, and other variables listed below.

<table data-header-hidden><thead><tr><th width="150"></th><th></th></tr></thead><tbody><tr><td><strong>Variable</strong></td><td><strong>Description</strong></td></tr><tr><td>{NOLOG}</td><td>This does not append <code>>></code> to the end of a command to log the output</td></tr><tr><td>{NOCHECKIN}</td><td>This does not prepend a command with a check in, and does not echo the command being run. This is useful (necessary) when running multi-line commands like for loops and if/then statements</td></tr><tr><td>{PROFILE}</td><td>This prepends the command with a profiler to output information about CPU and memory usage.</td></tr><tr><td>{analysisrootdir}</td><td>The full path to the analysis root directory. ex <code>/home/user/thePipeline/S1234ABC/1/</code></td></tr><tr><td>{subjectuid}</td><td>The UID of the subject being analyzed. Ex <code>S1234ABC</code></td></tr><tr><td>{studynum}</td><td>The study number of the study being analyzed. ex <code>2</code></td></tr><tr><td>{uidstudynum}</td><td>UID and studynumber together. ex <code>S1234ABC2</code></td></tr><tr><td>{pipelinename}</td><td>The pipeline name</td></tr><tr><td>{studydatetime}</td><td>The study datetime. ex <code>2022-07-04 12:34:56</code></td></tr><tr><td>{first_ext_file}</td><td>Replaces the variable with the first file (alphabetically) found with the <code>ext</code> extension</td></tr><tr><td>{first_n_ext_files}</td><td>Replaces the variable with the first <code>N</code> files (alphabetically) found with the <code>ext</code> extension</td></tr><tr><td>{last_ext_file}</td><td>Replaces the variable with the last file (alphabetically) found with the <code>ext</code> extension</td></tr><tr><td>{all_ext_files}</td><td>Replaces the variable with all files (alphabetically) found with the <code>ext</code> extension</td></tr><tr><td>{command}</td><td>The command being run. ex <code>ls -l</code></td></tr><tr><td>{workingdir}</td><td>The current working directory</td></tr><tr><td>{description}</td><td>The description of the command. This is anything following the <code>#</code>, also called a comment</td></tr><tr><td>{analysisid}</td><td>The analysisID of the analysis. This is useful when inserting analysis results, as the analysisID is required to do that</td></tr><tr><td>{subjectuids}</td><td>[<em>Second level analysis</em>] List of subjectIDs</td></tr><tr><td>{studydatetimes}</td><td>[<em>Second level analysis</em>] List of studyDateTimes in the group</td></tr><tr><td>{analysisgroupid}</td><td>[<em>Second level analysis</em>] The analysisID</td></tr><tr><td>{uidstudynums}</td><td>[<em>Second level analysis</em>] List of UIDStudyNums</td></tr><tr><td>{numsubjects}</td><td>[<em>Second level analysis</em>] Total number of subjects in the group analysis</td></tr><tr><td>{groups}</td><td>[<em>Second level analysis</em>] List of group names contributing to the group analysis. Sometimes this can be used when comparing groups</td></tr><tr><td>{numsubjects_groupname}</td><td>[<em>Second level analysis</em>] Number of subjects within the specified <code>groupname</code></td></tr><tr><td>{uidstudynums_groupname}</td><td>[<em>Second level analysis</em>] Number of studies within the specified <code>groupname</code></td></tr></tbody></table>
