# NeuroInformatics Database

## Overview
The Neuroinformatics Database (NiDB) is designed to store, retrieve, analyze, and share neuroimaging data. Modalities include MR, EEG, ET, video, genetics, assessment data, and any binary data. Subject demographics can also be stored.

Watch an overview of the main features of NiDB: <a href="https://youtu.be/tOX7VamHGvM">Part 1</a> | <a href="https://youtu.be/dX11HRj_kEs">Part 2</a> | <a href="https://youtu.be/aovrq-oKO-M">Part 3</a>

This is a unified repository for the NiDB project. It is composed of four main sections:

* programs - the behind the scenes programs and scripts that make things happen without the user seeing it. Usually copied to `/nidb/programs`
* web - the website that the user interacts with. Usually copied to `/var/www/html`
* setup - setup script and SQL schema files
* documentation - Word documents for usage and administration

## Install
After setup, go to http://localhost/ and login with `admin` and `password`. Change the default password immediately after logging in!

### CentOS 7
To <u>install on CentOS 7</u>, type the following on the command line (as root), and follow the instructions: (This has been tested and is generally stable)<br>
`> wget https://raw.githubusercontent.com/gbook/nidb/master/setup/setup-centos7.sh .`<br>
`> chmod 777 setup-centos7.sh`<br>
`> sudo ./setup-centos7.sh`

### Ubuntu 16
To <u>install on Ubuntu 16</u>, type the following on the command line (as root), and follow the instructions: (This is untested and might or might not work...)<br>
`> wget https://raw.githubusercontent.com/gbook/nidb/master/setup/setup-ubuntu16.sh .`<br>
`> chmod 777 setup-ubuntu16.sh`<br>
`> sudo ./setup-ubuntu16.sh`

### Generic requirements for installation any OS
Most of these are available through `yum` or `apt-get`
* httpd
* MySQL/MariaDB 10.0+
* Perl 5.16+ - including the following libraries: `File::Path`, `Net::SMTP::TLS`, `List::Util`, `Date::Parse`, `Image::ExifTool`, `String::CRC32`, `Date::Manip`, `Sort::Naturally`, `Digest::MD5`, `Digest::MD5::File`, `Statistics::Basic`, `Email::Send::SMTP::Gmail`
* PHP 7+ - including the following packages (through yum or PEAR): `php-mysql`, `php-gd`, `php-process`, `php-pear`, `php-mcrypt`, `php-mbstring`, `Mail`, `Mail_Mime`, `Net_SMTP`
* iptables (configured to forward external port 104 to internal port 8104)
* svn (for downloading updates from github)
* java
* ImageMagick
* phpMyAdmin
* FSL

## Upgrade
To <u>upgrade</u> an existing installation of NiDB, do the following (as root). (Tested, should work. <b>Backup your database before attempting the upgrade!!</b>)<br>
`> wget https://raw.githubusercontent.com/gbook/nidb/master/setup/Upgrade.php .`<br>
`> wget https://raw.githubusercontent.com/gbook/nidb/master/setup/nidb.sql .`<br>
Edit the options at the top of Upgrade.php to reflect your site (usernames/passwords) and the options you want to execute. Then run the updater by typing<br>
`> php Upgrade.php`

## Support
Visit the NiDB's github <a href="https://github.com/gbook/nidb/issues">issues</a> page for more support.
