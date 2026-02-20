Name:           nidb
Version:        2026.2.1362
Release:        1%{?dist}
Summary:        NeuroInformatics Database

License:        GPLv3
URL:            http://github.com/gbook/nidb
#Source0:        

BuildArch:	x86_64
BuildRequires:  gcc, cmake, make
Requires:       php, php-mysqlnd, php-gd, php-cli, php-process, php-pear, php-mbstring, php-fpm, php-json, php-opcache, mariadb, mariadb-common, mariadb-server, mariadb-server-utils, mariadb-connector-c, mariadb-connector-c-config, mariadb-backup, httpd, ImageMagick, perl-Image-ExifTool, openssl, compat-openssl10, zip, unzip, p7zip, p7zip-plugins, java

%description
NeuroInformatics Database (NiDB) is a full neuroimaging database system to store, retrieve, analyze, and distribute neuroscience data.

%build # This section does the building. all the binary files will end up in %{builddir}
%{_sourcedir}/build-rpm.sh ~/Qt/6.9.3/gcc_64/bin/qmake %{_sourcedir}/src %{_builddir}/bin

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
mkdir -p %{buildroot}/usr/local/share/dcmtk-3.7.0/
cp -f %{_sourcedir}/src/setup/rpm_post_install.sh %{buildroot}/nidb/setup/ # RPM post-install script
cp -rf %{_sourcedir}/src/web/* %{buildroot}/var/www/html/ # copy web files to the end location
cp -f %{_builddir}/bin/nidb/nidb %{buildroot}/nidb/bin/
cp -rf %{_sourcedir}/tools/* %{buildroot}/nidb/bin/
cp -f %{_builddir}/bin/squirrel/squirrel %{buildroot}/usr/local/bin/ # squirrel utilities
cp -f %{_sourcedir}/src/setup/* %{buildroot}/nidb/setup/
cp -f %{_builddir}/bin/bit7z/libbit7z64.a %{buildroot}/usr/lib/ # copy bit7z lib
cp -f %{_builddir}/bin/squirrel/libsquirrel.so.1 %{buildroot}/usr/lib/ # copy squirrel lib
cp -f /usr/local/lib64/libcmr.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmdata.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmdsig.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmect.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmfg.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmimage.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmimgle.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmiod.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmjpeg.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmjpls.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmnet.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmpmap.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmpstat.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmqrdb.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmrt.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmseg.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmsr.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmtkcharls.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmtls.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmtract.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmwlm.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libdcmxml.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libi2d.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libijg12.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libijg16.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libijg8.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/liboficonv.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/liboflog.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f /usr/local/lib64/libofstd.so.20.3.7.0 %{buildroot}/usr/lib/ # copy dcmtk libs
cp -f ~/Qt/6.9.3/gcc_64/lib/libQt6Core.so.6 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.3/gcc_64/lib/libQt6Core.so.6 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.3/gcc_64/lib/libQt6Network.so.6 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.3/gcc_64/lib/libQt6Sql.so.6 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.3/gcc_64/lib/libicudata.so.73 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.3/gcc_64/lib/libicui18n.so.73 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.3/gcc_64/lib/libicuuc.so.73 %{buildroot}/usr/lib/ # copy Qt libs
cp -f ~/Qt/6.9.3/gcc_64/plugins/sqldrivers/libqsqlmysql.so %{buildroot}/usr/lib/sqldrivers/ # copy Qt MySQL lib
cp -f ~/Qt/6.9.3/gcc_64/plugins/sqldrivers/libqsqlmysql.so %{buildroot}/nidb/bin/sqldrivers/ # copy Qt MySQL lib to here also... sometimes the binary only checks this location for the lib
cp -f ~/Qt/6.9.3/gcc_64/plugins/sqldrivers/libqsqlite.so %{buildroot}/usr/lib/sqldrivers/ # copy Qt MySQL lib
cp -f ~/Qt/6.9.3/gcc_64/plugins/sqldrivers/libqsqlite.so %{buildroot}/nidb/bin/sqldrivers/ # copy Qt MySQL lib to here also... sometimes the binary only checks this location for the lib
cp -rf /usr/local/share/dcmtk-3.7.0/* %{buildroot}/usr/local/share/dcmtk-3.7.0/ # copy dcmtk .dic files

# This section LISTS the files that are available once everything is installed, but this is NOT the specification for what files will be installed...
%files
/nidb
/var/www/html
/usr/lib/*
/usr/local/bin
/usr/local/share/dcmtk-3.7.0

%post
/nidb/setup/rpm_post_install9.sh
