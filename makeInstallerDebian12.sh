#!/bin/sh

PACKAGE=nidb_2025.10.1305
LIBDIR=$PACKAGE/lib/x86_64-linux-gnu/
BINDIR=$PACKAGE/usr/local/bin/
DEBDIR=$PACKAGE/DEBIAN

mkdir -p $LIBDIR
mkdir -p $BINDIR
mkdir -p $DEBDIR

# try to copy the binaries to their final locations (this may fail because it requires sudo, but its not a critical step to build)
cp -uv bin/squirrel/libsquirrel* $LIBDIR
cp -uv bin/gdcm/bin/libgdcm* $LIBDIR
cp -uv bin/smtp/libSMTPEmail* $LIBDIR
cp -uv ~/Qt/6.9.3/gcc_64/lib/libQt6Sql.so* ~/Qt/6.9.3/gcc_64/lib/libQt6Network.so* ~/Qt/6.9.3/gcc_64/lib/libQt6Core.so* $LIBDIR
cp -uv ~/Qt/6.9.3/gcc_64/lib/libicu* $LIBDIR

cp -uv bin/nidb/nidb $BINDIR

echo "Package: nidb
Version: 2025.10.1305
Section: base
Priority: optional
Architecture: amd64
Maintainer: Greg Book <gregory.a.book@gmail.com>
Description: Neuroinformatics Database" > $DEBDIR/control

dpkg-deb --build $PACKAGE