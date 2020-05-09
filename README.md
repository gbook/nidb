# NeuroInformatics Database

## Overview
The Neuroinformatics Database (NiDB) is designed to store, retrieve, analyze, and share neuroimaging data. Modalities include MR, EEG, ET, video, genetics, assessment data, and any binary data. Subject demographics, family relationships, and data imported from RedCap can be stored and queried in the database.

Watch an overview of the main features of NiDB (Recorded 2015): <a href="https://youtu.be/tOX7VamHGvM">Part 1</a> | <a href="https://youtu.be/dX11HRj_kEs">Part 2</a> | <a href="https://youtu.be/aovrq-oKO-M">Part 3</a>

The git repository is composed of the following sections:

* `doc` - Documentation, Word documents
* `setup` - Files related to installing NiDB. Including C++/Qt installer
* `src` - Source code. Qt required for nidb core program
* `tools` - Various tools, binary helper programs, and scripts

## Current version of NiDB - March 2020
NiDB was re-written in 2019 using C++ instead of Perl. This allowed much more reliable code. All Perl files have been moved to the <i>src/old</i> directory for historical reference.

As part of the rewrite, a new installer was created. See the *Releases* section to download the current installer. The installer should be used the first time NiDB is installed, and the internal update tool used for subsequent updates.

Further changes include:
 * Only CentOS 8 is supported
 * Only MariaDB 10.0+ supported
 * Only PHP7+ is supported

## Install
Use the binary installer. It will go through the process of installing several packages, and requires root privileges. Once finished, the installer will launch the web browser, through which you will complete the setup.

After setup, go to http://localhost/ and login with `admin` and `password`. Change the default password immediately after logging in!

## Support
Visit the NiDB's github <a href="https://github.com/gbook/nidb/issues">issues</a> page for more support.
