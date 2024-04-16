#!/bin/sh

PACKAGE=nidb_2024.4.1085
LIBDIR=$PACKAGE/lib/x86_64-linux-gnu/
BINDIR=$PACKAGE/usr/local/bin/
DEBDIR=$PACKAGE/DEBIAN

mkdir -p $LIBDIR
mkdir -p $BINDIR

# try to copy the binaries to their final locations (this may fail because it requires sudo, but its not a critical step to build)
cp -uv bin/squirrel/libsquirrel* $LIBDIR
cp -uv bin/gdcm/bin/libgdcm* $LIBDIR
cp -uv bin/smtp/libSMTPEmail* $LIBDIR
cp -uv ~/Qt/6.6.3/gcc_64/lib/libQt6Sql.so* ~/Qt/6.6.3/gcc_64/lib/libQt6Network.so* ~/Qt/6.6.3/gcc_64/lib/libQt6Core.so* $LIBDIR
cp -uv ~/Qt/6.6.3/gcc_64/lib/libicu* $LIBDIR

cp -uv bin/nidb/nidb $BINDIR

echo "Package: nidb\n
Version: 2024.4.1085\n
Section: base
Priority: optional
Architecture: x86_64
Maintainer: Greg Book <gregory.a.book@gmail.com>
Description: Neuroinformatics Database" > $DEBDIR/control

dpkg-deb --build $PACKAGE