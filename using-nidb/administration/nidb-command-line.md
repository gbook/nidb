---
description: Command line usage of nidb
---

# nidb command line

## Overview

All modules in NiDB system are run from the nidb command line program. Modules are automated by being started from cron.

nidb can be run manually to test modules and get debugging information. It can also be used when running on a cluster to insert results back into the database. Running nidb without command line parameters will display the usage.

```
> ./nidb

Neuroinformatics Database (NiDB)

Options:
  -h, --help                     Displays help on commandline options.
  --help-all                     Displays help including Qt specific options.
  -v, --version                  Displays version information.
  -d, --debug                    Enable debugging
  -q, --quiet                    Dont print headers and checks
  -r, --reset                    Reset, and then run, the specified module
  -u, --submodule <submodule>    For running on cluster. Sub-modules [
                                 resultinsert, pipelinecheckin, updateanalysis,
                                 checkcompleteanalysis ]
  -a, --analysisid <analysisid>  resultinsert -or- pipelinecheckin submodules
                                 only
  -s, --status <status>          pipelinecheckin submodule
  -m, --message <message>        pipelinecheckin submodule
  -c, --command <command>        pipelinecheckin submodule
  -t, --text <text>              Insert text result (resultinsert submodule)
  -n, --number <number>          Insert numerical result (resultinsert
                                 submodule)
  -f, --file <filepath>          Insert file result (resultinsert submodule)
  -i, --image <imagepath>        Insert image result (resultinsert submodule)
  -e, --desc <desc>              Result description (resultinsert submodule)
  --unit <unit>                  Result unit (resultinsert submodule)

Arguments:
  module                         Available modules:  import  export  fileio
                                 mriqa  qc  modulemanager  importuploaded
                                 upload  pipeline  cluster  minipipeline  backup
```

## Running Modules

Avaiable modules are: import, export, fileio, mriqa, qc, modulemanager, importuploaded, upload, pipeline, minipipeline, and backup

For example, to run the import module, run as the `nidb` user

```
./nidb import
```

This will output

```
-------------------------------------------------------------
----- Starting Neuroinformatics Database (NiDB) backend -----
-------------------------------------------------------------
Loading config file /nidb/nidb.cfg                                              [Ok]
Connecting to database                                                          [Ok]
   NiDB version 2023.2.942
   Build date [Feb 10 2023 11:22:26]
   C++ [201703]
   Qt compiled [6.4.2]
   Qt runtime [6.4.2]
   Build system [x86_64-little_endian-lp64]
Found [0] lockfiles for module [import]
Creating lock file [/nidb/lock/import.441787]                                   [Ok]
Creating log file [/nidb/logs/import20230428113035.log]                         [Ok]
Checking module into database                                                   [Ok]
.Deleting log file [/nidb/logs/import20230428113035.log]                        [Ok]
Module checked out of database
Deleting lock file [/nidb/lock/import.441787]                                   [Ok]
-------------------------------------------------------------
----- Terminating (NiDB) backend ----------------------------
-------------------------------------------------------------
```

As with all modules, detailed log files are written to `/nidb/logs` and are kept for 4 days.

## Running from cluster

To run `nidb` from the cluster, for the purpose of inserting results into the database or for checkins while running pipelines, this would be run on the cluster node itself. Access to an `nidb.cfg` file is necessary to run nidb somewhere other than on the main database server. A second config file `/nidb/nidb-cluster.cfg` can be copied to the cluster location along with the `nidb` executable.

### pipelinecheckin

To check-in when running a pipeline, use the following

```
./nidb cluster -u pipelinecheckin -a <analysisid> -s <status> -m <message>

# example
./nidb cluster -u pipelinecheckin -a 12235 -s started -m "Copying data"
```

The analysisid is the rowid of the analysis which is bring reported on. **Status** can include one of the following: `started`, `startedrerun`, `startedsupplement`, `processing`, `completererun`, `completesupplement`, `complete`. **Message** can be an string, enclosed in double quotes.

### updateanalysis

This option counts the byte size of the analysis directory and number of files and updates the analysis details in the main database.

```
./nidb cluster -u updateanalysis -a <analysisid>
```

### checkcompleteanalysis

This option checks if the 'complete files' list exists. These files are specified as part of the pipeline definition. If the files exist, the analysis is marked as successfuly complete.

```
./nidb cluster -u checkcompleteanalysis -a <analysisid>
```

### resultinsert

Text, number, and images can be inserted using this command. Examples

```
./nidb cluster -u resultinsert -t 'Yes' -e 'subject response'

./nidb cluster -u resultinsert -n 9.6 -e 'reactiontime' --unit 's'

./nidb cluster -u resultinsert -i <imagepath> -e 'realignment results'

./nidb cluster -u resultinsert -f <filepath> -e 'useful file'
```
