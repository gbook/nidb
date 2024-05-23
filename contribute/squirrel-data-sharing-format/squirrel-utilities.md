---
description: The squirrel command line program
---

# squirrel utilities

The squirrel command line program allows converstion of DICOM to squirrel, BIDS to squirrel, modification of existing squirrel packages, and listing of information from packages.

## Installing squirrel utilities

squirrel utilities will run on Linux.&#x20;

## Basic Command Line Usage

### Convert DICOM to squirrel

```
# Default DICOM to squirrel conversion
squirrel dicom2squirrel /path/to/dicoms outPackgeName.sqrl

# Specify the output format
squirrel dicom2squirrel /path/to/dicoms outPackge.sqrl --dataformat niti4gz

# Specify the package directory format
squirrel dicom2squirrel /path/to/dicoms outPackage.sqrl --dirformat seq
```

### Convert BIDS to squirrel

```
squirrel bids2squirrel /path/to/bids outPackage.sqrl
```

### Modify existing squirrel package

```
# add a subject to a package
squirrel modify /path/to/package.sqrl --add subject --datapath /path/to/new/data --objectdata 'SubjectID=S1234ABC&DateOfBorth=199-12-31&Sex=M&Gender=M'

# remove a study (remove study 1 from subject S1234ABC)
squirrel modify /path/to/package.sqrl --remove study --subjectid S1234ABC --objectid 1
```

### List information about a squirrel package

```bash
[user@hostname]$ squirrel info ~/testing.sqrl

Squirrel Package: /home/nidb/testing.sqrl
  DataFormat: orig
  Date: Thu May 23 16:16:16 2024
  Description: Dataset description
  DirectoryFormat (subject, study, series): orig, orig, orig
  FileMode: ExistingPackage
  Files:
    314 files
    19181701506 bytes (unzipped)
  PackageName: Squirrel package
  SquirrelBuild: 2024.5.218
  SquirrelVersion: 1.0
  Objects:
    ├── 8 subjects
    │  ├── 8 measures
    │  ├── 0 drugs
    │  ├── 11 studies
    │  ├──── 314 series
    │  └──── 0 analyses
    ├── 0 experiments
    ├── 0 pipelines
    ├── 0 group analyses
    └── 0 data dictionary

```
