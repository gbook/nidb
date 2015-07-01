# nidb
NeuroInformatics Database

This is a unified repository for the NiDB project. It is composed of three main sections:

* programs - the behind the scenes programs and scripts that make things happen without the user seeing it. Usually copied to `/nidb/programs`
* web - the website that the user interacts with. Usually copied to `/var/www/html`
* setup - setup script and SQL schema files
* documentation - Word documents for usage and administration

To install on CentOS 7, type the following on the command line as root, and follow the instructions:<br>
`> svn export http://github.com/gbook/nidb/trunk/setup/setup-centos7.sh .`<br>
`> chmod 777 setup-centos7.sh`<br>
`> ./setup-centos7.sh`

After setup, go to http://localhost/ and login with admin/password. Change the default password immediately after logging in!

Visit the NiDB <a href="http://neuroinfodb.com/forums/">forums</a> for more support.
