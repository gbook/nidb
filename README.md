# nidb
NeuroInformatics Database

This is a unified repository for the NiDB project. It is composed of three main sections:

* programs - the behind the scenes programs and scripts that make things happen without the user seeing it. Usually copied to `/nidb/programs`
* web - the website that the user interacts with. Usually copied to `/var/www/html`
* setup - setup script and SQL schema files
* documentation - Word documents for usage and administration

To install on CentOS 7, type the following on the command line as root, and follow the instructions:
`> svn export http://github.com/gbook/nidb/trunk/setp/set-centos7.sh .`
`> chmod 777 setup-centos7.sh`
`> ./setup-centos7.sh`
