# NeuroInformatics Database

## Overview
The Neuroinformatics Database (NiDB) is designed to store, retrieve, analyze, and share neuroimaging data. Modalities include MR, EEG, ET, video, genetics, assessment data, and any binary data. Subject demographics can also be stored.

Watch an overview of the main features of NiDB: <a href="https://youtu.be/tOX7VamHGvM">Part 1</a> | <a href="https://youtu.be/dX11HRj_kEs">Part 2</a> | <a href="https://youtu.be/aovrq-oKO-M">Part 3</a>

This is a unified repository for the NiDB project. It is composed of four main sections:

* programs - the behind the scenes programs and scripts that make things happen without the user seeing it. Usually copied to `/nidb/programs`
* web - the website that the user interacts with. Usually copied to `/var/www/html`
* setup - setup script and SQL schema files
* documentation - Word documents for usage and administration

## Current version of NiDB - March 2020
NiDB was re-written in 2019 using C++ instead of Perl. This allowed much more reliable code. All Perl files have been moved to the <i>old</i> directory within programs for historical purposes.

As part of the rewrite, a new installer was created. See the <i>Releases<i> section to download the current installer. The installer should be used the first time NiDB is installed, and the internal update tool used for subsequent updates.

Further changes include:
 * Only CentOS 8 is supported
 * Only MariaDB 10.0+ supported
 * Only PHP7+ is supported

## Install
Use the binary installer. It will go through the process of installing several packages, and requires root privileges. Once finished, the installer will launch the web browser, through which you will complete the setup.

After setup, go to http://localhost/ and login with `admin` and `password`. Change the default password immediately after logging in!

## Support
Visit the NiDB's github <a href="https://github.com/gbook/nidb/issues">issues</a> page for more support.
