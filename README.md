
# NeuroInformatics Database

## Install
### Pre-requisites
1. Install FSL (required for MRI qc module) https://fsl.fmrib.ox.ac.uk/fsl/fslwiki/FslInstallation
2. Install firejail (required for minipipeline module) https://firejail.wordpress.com/
3. Enable ImageMagick repository
   1. `sudo yum install epel-release`
### NiDB
1. Download most recent .rpm: https://github.com/gbook/nidb/releases
2. Install .rpm
   1. `sudo yum --nogpgcheck localinstall nidb-xxxx.x.xx-1.el8.x86_64.rpm`
3. Prepare MariaDB, using the following responses
```bash$ sudo mysql_secure_installation
Enter current password for root (enter for none):
Change the root password? [Y/n] n
Remove anonymous users? [Y/n] Y
Disallow root login remotely? [Y/n] Y
Remove test database and access to it? [Y/n] Y
Reload privilege tables now? [Y/n] Y
```
4. Launch firefox from the server on which you’ve installed nidb. Go to http://localhost/setup.php and follow the instructions.

## Overview
The Neuroinformatics Database (NiDB) is designed to store, retrieve, analyze, and share neuroimaging data. Modalities include MR, EEG, ET, video, genetics, assessment data, and any binary data. Subject demographics, family relationships, and data imported from RedCap can be stored and queried in the database.

Visit http://neuroinfodb.org for more support and information about NiDB.

Watch an overview of the main features of NiDB (Recorded 2015): <a href="https://youtu.be/tOX7VamHGvM">Part 1</a> | <a href="https://youtu.be/dX11HRj_kEs">Part 2</a> | <a href="https://youtu.be/aovrq-oKO-M">Part 3</a>

The git repository is composed of the following sections:

* `doc` - Documentation, Word documents (out-of-date)
* `src` - Source code
* `tools` - Various tools, binary helper programs, and scripts

## Fix for MySQL driver not loading
In version 2020.6.508, the nidb executable may not launch because it could not load the MySQL driver. This is fixed in the newest version, but to fix this minor issue without upgrading, perform the following

`mkdir /nidb/bin/sqldrivers; cp /usr/lib/libqsqlmysql.so /nidb/bin/sqldrivers`

## Current version of NiDB
NiDB was re-written in 2019-2020 using C++ instead of Perl. This allowed for much more reliable code and an opporunity for more people to contribute to development. All Perl files have been moved to the <i>src/old</i> directory for historical reference. As part of the rewrite, a new installer using .rpm was created. See the *Releases* section to download the current .rpm.

Further changes include:
 * Only CentOS 8 is supported (CentOS 7 is still somewhat supported)
 * Only MariaDB 10.0+ supported
 * Only PHP7+ is supported

## Install
Follow the NiDB-Install.pdf directions included with the current release.

## Building NiDB from Source
Follow the NiDB-Build.pdf directions included with the current release.

## Support
Visit the NiDB's github <a href="https://github.com/gbook/nidb/issues">issues</a> page for more support.
