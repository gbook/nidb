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

| **Variable**              | **Description**                                                                                                                                                                             |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| {NOLOG}                   | This does not append `>>` to the end of a command to log the output                                                                                                                         |
| {NOCHECKIN}               | This does not prepend a command with a check in, and does not echo the command being run. This is useful (necessary) when running multi-line commands like for loops and if/then statements |
| {PROFILE}                 | This prepends the command with a profiler to output information about CPU and memory usage.                                                                                                 |
| {analysisrootdir}         | The full path to the analysis root directory. ex `/home/user/thePipeline/S1234ABC/1/`                                                                                                       |
| {subjectuid}              | The UID of the subject being analyzed. Ex `S1234ABC`                                                                                                                                        |
| {studynum}                | The study number of the study being analyzed. ex `2`                                                                                                                                        |
| {uidstudynum}             | UID and studynumber together. ex `S1234ABC2`                                                                                                                                                |
| {pipelinename}            | The pipeline name                                                                                                                                                                           |
| {studydatetime}           | The study datetime. ex `2022-07-04 12:34:56`                                                                                                                                                |
| {first\_ext\_file}        | Replaces the variable with the first file (alphabetically) found with the `ext` extension                                                                                                   |
| {first\_n\_ext\_files}    | Replaces the variable with the first `N` files (alphabetically) found with the `ext` extension                                                                                              |
| {last\_ext\_file}         | Replaces the variable with the last file (alphabetically) found with the `ext` extension                                                                                                    |
| {all\_ext\_files}         | Replaces the variable with all files (alphabetically) found with the `ext` extension                                                                                                        |
| {command}                 | The command being run. ex `ls -l`                                                                                                                                                           |
| {workingdir}              | The current working directory                                                                                                                                                               |
| {description}             | The description of the command. This is anything following the `#`, also called a comment                                                                                                   |
| {analysisid}              | The analysisID of the analysis. This is useful when inserting analysis results, as the analysisID is required to do that                                                                    |
| {subjectuids}             | \[_Second level analysis_] List of subjectIDs                                                                                                                                               |
| {studydatetimes}          | \[_Second level analysis_] List of studyDateTimes in the group                                                                                                                              |
| {analysisgroupid}         | \[_Second level analysis_] The analysisID                                                                                                                                                   |
| {uidstudynums}            | \[_Second level analysis_] List of UIDStudyNums                                                                                                                                             |
| {numsubjects}             | \[_Second level analysis_] Total number of subjects in the group analysis                                                                                                                   |
| {groups}                  | \[_Second level analysis_] List of group names contributing to the group analysis. Sometimes this can be used when comparing groups                                                         |
| {numsubjects\_groupname}  | \[_Second level analysis_] Number of subjects within the specified `groupname`                                                                                                              |
| {uidstudynums\_groupname} | \[_Second level analysis_] Number of studies within the specified `groupname`                                                                                                               |