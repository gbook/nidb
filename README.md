
# NeuroInformatics Database

## Overview
The Neuroinformatics Database (NiDB) is designed to store, retrieve, analyze, and share neuroimaging data. Modalities include MR, EEG, ET, video, genetics, assessment data, and any binary data. Subject demographics, family relationships, and data imported from RedCap can be stored and queried in the database.

Watch an overview of the main features of NiDB (Recorded 2015): <a href="https://youtu.be/tOX7VamHGvM">Part 1</a> | <a href="https://youtu.be/dX11HRj_kEs">Part 2</a> | <a href="https://youtu.be/aovrq-oKO-M">Part 3</a>

The git repository is composed of the following sections:

* `doc` - Documentation, Word documents
* `src` - Source code. Qt required for nidb core program
* `tools` - Various tools, binary helper programs, and scripts

# FIX for v2020.6.508
In version 2020.6.508, the nidb executable may not launch because it could not load the MySQL driver. This is fixed in the newest version, but to fix this minor issue without upgrading, perform the following

`mkdir /nidb/bin/sqldrivers; cp /usr/lib/libqsqlmysql.so /nidb/bin/sqldrivers`

## Current version of NiDB
NiDB was re-written in 2019-2020 using C++ in place of Perl. This allowed for much more reliable code. All Perl files have been moved to the <i>src/old</i> directory for historical reference. As part of the rewrite, a new installer using .rpm was created. See the *Releases* section to download the current .rpm.

Further changes include:
 * Only CentOS 8 is supported
 * Only MariaDB 10.0+ supported
 * Only PHP7+ is supported

## Install
Follow the NiDB-Install.pdf directions included with the current release.

## Building NiDB from Source
Follow the NiDB-Build.pdf directions included with the current release.

## Support
Visit the NiDB's github <a href="https://github.com/gbook/nidb/issues">issues</a> page for more support.
