Name:           nidb
Version:        2020.1.183
Release:        1%{?dist}
Summary:        NeuroInformatics Database

License:        GPLv3
URL:            http://github.com/gbook/nidb
#Source0:        

BuildArch:	x86_64
BuildRequires:  gcc, cmake3, make
Requires:       php, php-mysqlnd, php-gd, php-cli, php-process, php-pear, php-mbstring, php-fpm, mariadb, mariadb-common, mariadb-server, mariadb-server-utils, mariadb-connector-c-devel, mariadb-connector-c, mariadb-connector-c-config, mariadb-backup, httpd, ImageMagick, perl-Image-ExifTool, openssl

%description
NeuroInformatics Database (NiDB) is a full neuroimaging database system to store, retrieve, analyze, and distribute neuroscience data.

%build # This section does the building. all the binary files will end up in %{builddir}
%{_sourcedir}/build.sh ~/Qt/5.12.8/gcc_64/bin/qmake %{_sourcedir}/src %{_builddir}/bin

%install # This section installs the files to the BUILDROOT dir, which is basically a copy of what the user's computer will look like after the RPM installs
mkdir -p %{buildroot}/usr/lib
mkdir -p %{buildroot}/nidb/bin
mkdir -p %{buildroot}/nidb/bin/lock
mkdir -p %{buildroot}/nidb/bin/logs
mkdir -p %{buildroot}/var/www/html
cp %{_sourcedir}/src/install/rpm_post_install.sh %{buildroot}/nidb/ # RPM post-install script
cp -r %{_sourcedir}/src/web/* %{buildroot}/var/www/html/ # copy web files to the end location
cp %{_builddir}/bin/nidb/nidb %{buildroot}/nidb/bin/
cp -r %{_sourcedir}/tools/* %{buildroot}/nidb/bin/
cp %{_sourcedir}/src/setup/* %{buildroot}/nidb/
cp %{_builddir}/bin/smtp/libSMTPEmail.* %{buildroot}/usr/lib/ # copy SMTP libs
cp %{_builddir}/bin/gdcm/bin/lib* %{buildroot}/usr/lib/ # copy GDCM libs
cp ~/Qt/5.12.8/gcc_64/lib/libQt5Core* %{buildroot}/usr/lib/ # copy Qt libs
cp ~/Qt/5.12.8/gcc_64/lib/libQt5Network* %{buildroot}/usr/lib/ # copy Qt libs
cp ~/Qt/5.12.8/gcc_64/lib/libQt5Sql* %{buildroot}/usr/lib/ # copy Qt libs
cp ~/Qt/5.12.8/gcc_64/lib/libicudata* %{buildroot}/usr/lib/ # copy Qt libs
cp ~/Qt/5.12.8/gcc_64/lib/libicui18n* %{buildroot}/usr/lib/ # copy Qt libs
cp ~/Qt/5.12.8/gcc_64/lib/libicuuc* %{buildroot}/usr/lib/ # copy Qt libs

# This section LISTS the files that are available once everything is installed, but this is NOT the specification for what files will be installed...
%files
/nidb
/var/www/html
/usr/lib/*

%post
/nidb/rpm_post_install.sh

- 
