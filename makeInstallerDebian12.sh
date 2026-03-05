#!/bin/sh

PACKAGE=nidb_2026.2.1374
LIBDIR=$PACKAGE/lib/x86_64-linux-gnu/
BINDIR=$PACKAGE/usr/local/bin/
DEBDIR=$PACKAGE/DEBIAN

mkdir -p $LIBDIR
mkdir -p $BINDIR
mkdir -p $DEBDIR

# try to copy the binaries to their final locations (this may fail because it requires sudo, but its not a critical step to build)
cp -auv bin/squirrel/libsquirrel* $LIBDIR
cp -auv /usr/local/lib/libcmr* $LIBDIR
cp -auv /usr/local/lib/libdcm* $LIBDIR
cp -auv /usr/local/lib/libi2d* $LIBDIR
cp -auv /usr/local/lib/libigj* $LIBDIR
cp -auv /usr/local/lib/libof* $LIBDIR
cp -auv ~/Qt/6.9.3/gcc_64/lib/libQt6Sql.so* ~/Qt/6.9.3/gcc_64/lib/libQt6Network.so* ~/Qt/6.9.3/gcc_64/lib/libQt6Core.so* $LIBDIR
cp -auv ~/Qt/6.9.3/gcc_64/lib/libicu* $LIBDIR

cp -uv bin/nidb/nidb $BINDIR

echo "Package: nidb
Version: 2026.2.1374
Section: base
Priority: optional
Architecture: amd64
Maintainer: Greg Book <gregory.a.book@gmail.com>
Description: Neuroinformatics Database" > $DEBDIR/control

dpkg-deb --build $PACKAGE