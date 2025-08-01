Name:           nidb
Version:        2025.7.1272
Release:        1%{?dist}
Summary:        NeuroInformatics Database

License:        GPLv3
URL:            http://github.com/gbook/nidb
#Source0:        

BuildArch:	x86_64
# 'cmake3' is required by CentOS 8, 'cmake' is required by CentOS 8 Stream. Make sure the right one is used depending on your build OS
BuildRequires:  gcc, cmake, make, gcc-toolset-10
Requires:       php, php-mysqlnd, php-gd, php-cli, php-process, php-pear, php-mbstring, php-fpm, php-json, php-opcache, mariadb, mariadb-common, mariadb-server, mariadb-server-utils, mariadb-connector-c-devel, mariadb-connector-c, mariadb-connector-c-config, mariadb-backup, httpd, ImageMagick, perl-Image-ExifTool, openssl, openssl3, gcc-toolset-10, p7zip, p7zip-plugins

%description
NeuroInformatics Database (NiDB) is a full neuroimaging database system to store, retrieve, analyze, and distribute neuroscience data.

%build # This section does the building. all the binary files will end up in %{builddir}
%{_sourcedir}/build-rpm8.sh ~/Qt/6.9.1/gcc_64/bin/qmake %{_sourcedir}/src %{_builddir}/bin

%install # This section installs the files to the BUILDROOT dir, which is basically a copy of what the user's computer will look like after the RPM installs
mkdir -p %{buildroot}/usr/lib/sqldrivers
mkdir -p %{buildroot}/usr/local/bin
mkdir -p %{buildroot}/nidb/bin
mkdir -p %{buildroot}/nidb/bin/sqldrivers
mkdir -p %{buildroot}/nidb/lock
mkdir -p %{buildroot}/nidb/logs
mkdir -p %{buildroot}/nidb/qcmodules
mkdir -p %{buildroot}/nidb/setup
mkdir -p %{buildroot}/var/www/html
cp -f %{_sourcedir}/src/setup/rpm_post_install.sh %{buildroot}/nidb/setup/ # RPM post-install script
cp -rf %{_sourcedir}/src/web/* %{buildroot}/var/www/html/ # copy web files to the end location
cp -f %{_builddir}/bin/nidb/nidb %{buildroot}/nidb/bin/
cp -f %{_builddir}/bin/squirrel/squirrel %{buildroot}/usr/local/bin/ # squirrel utilities
cp -rf %{_sourcedir}/tools/* %{buildroot}/nidb/bin/
cp -rf %{_sourcedir}/src/qcmodules/* %{buildroot}/nidb/qcmodules/
cp -f %{_sourcedir}/src/setup/* %{buildroot}/nidb/setup/
cp -f %{_builddir}/bin/smtp/libSMTPEmail.so.1 %{buildroot}/usr/lib/ # copy SMTP libs
cp -f %{_builddir}/bin/bit7z/libbit7z64.a %{buildroot}/usr/lib/ # copy bit7z lib
cp -f %{_builddir}/bin/squirrel/libsquirrel.so.1 %{buildroot}/usr/lib/ # copy squirrel lib
cp -f %{_builddir}/bin/gdcm/bin/libgdcmMSFF.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmopenjp2.so.7 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmuuid.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmzlib.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmcharls.so.2 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmCommon.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmDICT.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmDSED.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmexpat.so.2.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmIOD.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmjpeg8.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmjpeg12.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmjpeg16.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libgdcmMEXD.so.3.0 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f %{_builddir}/bin/gdcm/bin/libsocketxx.so.1.2 %{buildroot}/usr/lib/ # copy GDCM libs
cp -f ~/Qt/6.9.1/gcc_64/lib/libQt6Core.so.6 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.1/gcc_64/lib/libQt6Network.so.6 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.1/gcc_64/lib/libQt6Sql.so.6 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.1/gcc_64/lib/libicudata.so.73 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.1/gcc_64/lib/libicui18n.so.73 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.1/gcc_64/lib/libicuuc.so.73 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.1/gcc_64/plugins/sqldrivers/libqsqlmysql.so %{buildroot}/usr/lib/sqldrivers/ # copy Qt MySQL lib
cp -f ~/Qt/6.9.1/gcc_64/plugins/sqldrivers/libqsqlmysql.so %{buildroot}/nidb/bin/sqldrivers/ # copy Qt MySQL lib to here also... sometimes the binary only checks this location for the lib
cp -f ~/Qt/6.9.1/gcc_64/plugins/sqldrivers/libqsqlite.so %{buildroot}/usr/lib/sqldrivers/ # copy Qt MySQL lib
cp -f ~/Qt/6.9.1/gcc_64/plugins/sqldrivers/libqsqlite.so %{buildroot}/nidb/bin/sqldrivers/ # copy Qt MySQL lib to here also... sometimes the binary only checks this location for the lib

# This section LISTS the files that are available once everything is installed, but this is NOT the specification for what files will be installed...
%files
/nidb
/var/www/html
/usr/lib/*
/usr/local/bin/*

%post
/nidb/setup/rpm_post_install.sh
