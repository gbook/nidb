Name:           nidb
Version:        2020.1.189
Release:        1%{?dist}
Summary:        NeuroInformatics Database

License:        GPLv3
URL:            http://github.com/gbook/nidb
#Source0:        

BuildArch:	x86_64
BuildRequires:  gcc, cmake3, make
Requires:       php, php-mysqlnd, php-gd, php-cli, php-process, php-pear, phpmbstring, php-fpm, mariadb, mariadb-common, mariadb-server, mariadb-server-utils, mariadb-connector-c-devel, mariadb-connector-c, mariadb-connector-c-config, mariadb-backup, httpd, epel-release, ImageMagick, exiftool

%description
NeuroInformatics Database (NiDB) is a full neuroimaging database system to store, retrieve, analyze, and distribute neuroscience data.

%build # This section does the building. all the binary files will end up in %{builddir}
%{_sourcedir}/build.sh

%install # This section installs the files to the BUILDROOT dir, which is basically a copy of what the user's computer will look like after the RPM installs
mkdir -p %{buildroot}/nidb/bin
mkdir -p %{buildroot}/nidb/bin/lock
mkdir -p %{buildroot}/nidb/bin/logs
mkdir -p %{buildroot}/var/www/html
cp -r %{_sourcedir}/src/web/* %{buildroot}/var/www/html
cp -r %{builddir}/* %{buildroot}/nidb/bin

# This section LISTS the files that are available once everything is installed, but this is NOT the specification for what files will be installed...
%files
/nidb
/var/www/html

%post
/nidb/rpm_post_install.sh

- 
