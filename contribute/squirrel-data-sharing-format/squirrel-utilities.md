---
description: The squirrel command line program
---

# squirrel utilities

The squirrel command line program allows converstion of DICOM to squirrel, BIDS to squirrel, modification of existing squirrel packages, and listing of information from packages.

## Installing squirrel utilities

Download squirrel from [https://github.com/gbook/squirrel/releases](https://github.com/gbook/squirrel/releases)

{% tabs %}
{% tab title="RHEL/Rocky/CentOS" %}
```bash
sudo yum localinstall --nogpgcheck squirrel-xxx.xx.xxx-1.elx.x86_64.rpm
```
{% endtab %}

{% tab title="Ubuntu/Debian" %}
```bash
sudo apt install p7zip # p7zip required by squirrel
sudo dpkg -i squirrel_xxxx.xx.xxx.deb
```
{% endtab %}
{% endtabs %}

{% hint style="info" %}
**Too many open files error**

If you encounter an error "too many open files", or you are unable to write squirrel packages, try increasing the open files limit within Linux

```bash
# increase open file limit (temporarily for the current session)
ulimit -n 2048

# increase open file limit (permanently)
# append these lines to /etc/security/limits.conf
*               soft    nofile            2048
*               hard    nofile            2048
```
{% endhint %}

## Basic Command Line Usage

### Convert DICOM to squirrel

```bash
# Default DICOM to squirrel conversion
squirrel dicom2squirrel /path/to/dicoms outPackgeName.sqrl

# Specify the output format
squirrel dicom2squirrel /path/to/dicoms outPackge.sqrl --dataformat niti4gz

# Specify the package directory format
squirrel dicom2squirrel /path/to/dicoms outPackage.sqrl --dirformat seq
```

### Convert BIDS to squirrel

```bash
squirrel bids2squirrel /path/to/bids outPackage.sqrl
```

### Modify existing squirrel package

```bash
# add a subject to a package
squirrel modify /path/to/package.sqrl --add subject --datapath /path/to/new/data --objectdata 'SubjectID=S1234ABC&DateOfBorth=199-12-31&Sex=M&Gender=M'

# remove a study (remove study 1 from subject S1234ABC)
squirrel modify /path/to/package.sqrl --remove study --subjectid S1234ABC --objectid 1
```

### List information about a squirrel package

```bash
#list package information
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
    
# list subjects
[user@hostname]$ squirrel info ~/testing.sqrl --object subject
Subjects: sub-ASDS3050KAE sub-ASDS6316BWH sub-ASDS6634GJK sub-ASDS7478SKA sub-ASDS8498GQDCBT sub-HCS8276XPS sub-S4328FSC sub-S7508DDH

# list studies for a specific subject
[user@hostname]$ squirrel info ~/testing.sqrl --object study --subjectid sub-ASDS3050KAE
Studies: 1 2

#list all subjects as CSV format
[user@hostname]$ squirrel info ~/testing.sqrl --object subject --csv
ID, AlternateIDs, DateOfBirth, Ethnicity1, Ethnicity2, GUID, Gender, Sex
"sub-ASDS3050KAE","","","","","","U","U"
"sub-ASDS6316BWH","","","","","","U","U"
"sub-ASDS6634GJK","","","","","","U","U"
"sub-ASDS7478SKA","","","","","","U","U"
"sub-ASDS8498GQDCBT","","","","","","U","U"
"sub-HCS8276XPS","","","","","","U","U"
"sub-S4328FSC","","","","","","",""
"sub-S7508DDH","","","","","","",""
```

