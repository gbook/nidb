# NeuroInformatics Database

The Neuroinformatics Database (NiDB) is designed to store, retrieve, analyze, and share neuroimaging data. Modalities include MR, EEG, ET, Video, Genetics, and assessment data. Subject demographics can also be stored.

Watch an overview of the main features of NiDB: <a href="https://youtu.be/tOX7VamHGvM">Part 1</a> | <a href="https://youtu.be/dX11HRj_kEs">Part 2</a> | <a href="https://youtu.be/aovrq-oKO-M">Part 3</a>

This is a unified repository for the NiDB project. It is composed of four main sections:

* programs - the behind the scenes programs and scripts that make things happen without the user seeing it. Usually copied to `/nidb/programs`
* web - the website that the user interacts with. Usually copied to `/var/www/html`
* setup - setup script and SQL schema files
* documentation - Word documents for usage and administration

To install on CentOS 7, type the following on the command line as root, and follow the instructions:<br>
`> wget https://github.com/gbook/nidb/trunk/setup/setup-centos7.sh .`<br>
`> chmod 777 setup-centos7.sh`<br>
`> ./setup-centos7.sh`

To install on Ubuntu 16, type the following on the command line as root, and follow the instructions:<br>
`> wget https://github.com/gbook/nidb/trunk/setup/setup-ubuntu16.sh .`<br>
`> chmod 777 setup-ubuntu16.sh`<br>
`> ./setup-ubuntu16.sh`

After setup, go to http://localhost/ and login with admin/password. Change the default password immediately after logging in!

Visit the NiDB's github <a href="https://github.com/gbook/nidb/issues">issues</a> page for more support.
