Name:           nidb
Version:        2020.6.502
Release:        1%{?dist}
Summary:        NeuroInformatics Database

License:        GPLv3
URL:            http://github.com/gbook/nidb
#Source0:        

BuildArch:	x86_64
BuildRequires:  gcc, cmake3, make
Requires:       php, php-mysqlnd, php-gd, php-cli, php-process, php-pear, php-mbstring, php-fpm, php-json, mariadb, mariadb-common, mariadb-server, mariadb-server-utils, mariadb-connector-c-devel, mariadb-connector-c, mariadb-connector-c-config, mariadb-backup, httpd, ImageMagick, perl-Image-ExifTool, openssl

%description
NeuroInformatics Database (NiDB) is a full neuroimaging database system to store, retrieve, analyze, and distribute neuroscience data.

%build # This section does the building. all the binary files will end up in %{builddir}
%{_sourcedir}/build.sh ~/Qt/5.12.8/gcc_64/bin/qmake %{_sourcedir}/src %{_builddir}/bin

%install # This section installs the files to the BUILDROOT dir, which is basically a copy of what the user's computer will look like after the RPM installs
mkdir -p %{buildroot}/usr/lib
mkdir -p %{buildroot}/nidb/bin
mkdir -p %{buildroot}/nidb/lock
mkdir -p %{buildroot}/nidb/logs
mkdir -p %{buildroot}/nidb/qcmodules
mkdir -p %{buildroot}/nidb/setup
mkdir -p %{buildroot}/var/www/html
cp -f %{_sourcedir}/src/setup/rpm_post_install.sh %{buildroot}/nidb/setup/ # RPM post-install script
cp -rf %{_sourcedir}/src/web/* %{buildroot}/var/www/html/ # copy web files to the end location
cp -f %{_builddir}/bin/nidb/nidb %{buildroot}/nidb/bin/
cp -rf %{_sourcedir}/tools/* %{buildroot}/nidb/bin/
#cp -rf %{_sourcedir}/src/qcmodules/* %{buildroot}/nidb/qcmodules/
cp -f %{_sourcedir}/src/setup/* %{buildroot}/nidb/setup/
cp -f %{_builddir}/bin/smtp/libSMTPEmail.* %{buildroot}/usr/lib/ # copy SMTP libs
cp -f %{_builddir}/bin/gdcm/bin/lib* %{buildroot}/usr/lib/ # copy GDCM libs
cp -f ~/Qt/5.12.8/gcc_64/lib/libQt5Core* %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/5.12.8/gcc_64/lib/libQt5Network* %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/5.12.8/gcc_64/lib/libQt5Sql* %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/5.12.8/gcc_64/lib/libicudata* %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/5.12.8/gcc_64/lib/libicui18n* %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/5.12.8/gcc_64/lib/libicuuc* %{buildroot}/usr/lib/ # copy Qt libs

# This section LISTS the files that are available once everything is installed, but this is NOT the specification for what files will be installed...
%files
/nidb
/var/www/html
/usr/lib/*

%post
/nidb/setup/rpm_post_install.sh

- 
