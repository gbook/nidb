#!/bin/sh

scl enable gcc-toolset-10 bash

# global build variables
if [ -z "$1" ]; then
	QMAKEBIN=~/Qt/6.9.1/gcc_64/bin/qmake
else
	QMAKEBIN=$1
fi

if [ -z "$2" ]; then
	SRCDIR=$PWD/src
else
	SRCDIR=$2
fi

if [ -z "$3" ]; then
	BUILDDIR=$PWD/bin
else
	BUILDDIR=$3
fi

BIT7ZDIR=$PWD/bit7z
ORIGDIR=$PWD

# this script requires make, cmake (3), and qmake
command -v make >/dev/null 2>&1 || { echo -e "\nThis script requires make, but it is not installed\n"; exit 1; }
command -v gcc >/dev/null 2>&1 || { echo -e "\nThis script requires gcc, but it is not installed\n"; exit 1; }

CMAKEBIN=cmake3
command -v cmake3 >/dev/null 2>&1 || { CMAKEBIN=cmake; }

# create the build directory
echo "Creating build directory"
mkdir -p $BUILDDIR

# ----- build pre-requisites -----

mkdir -p $BUILDDIR/gdcm
cd $BUILDDIR/gdcm
$CMAKEBIN -DGDCM_BUILD_APPLICATIONS:STRING=NO -DGDCM_BUILD_DOCBOOK_MANPAGES:BOOL=OFF -DGDCM_BUILD_SHARED_LIBS:STRING=YES -DGDCM_BUILD_TESTING:STRING=NO -DGDCM_BUILD_EXAMPLES:STRING=NO $SRCDIR/gdcm
make -j 16
echo -e "\n Built gdcm in $BUILDDIR/gdcm\n"

# ----- build bit7z library -----
echo -e "\n ----- Building bit7z -----\n"
echo -e "\n ----- Created path $BUILDDIR/bit7z -----\n"
mkdir -p $BUILDDIR/bit7z
echo -e "\n ----- Running cmake -DBIT7Z_AUTO_FORMAT:BOOL=ON -DBIT7Z_USE_LEGACY_IUNKNOWN=ON -DBIT7Z_GENERATE_PIC=ON -DCMAKE_CXX_FLAGS:STRING=-fPIC -DCMAKE_C_FLAGS:STRING=-fPIC -S $SRCDIR/bit7z -B $BUILDDIR/bit7z -----\n"
cmake -DBIT7Z_AUTO_FORMAT:BOOL=ON -DBIT7Z_USE_LEGACY_IUNKNOWN=ON -DBIT7Z_GENERATE_PIC=ON -DCMAKE_CXX_FLAGS:STRING=-fPIC -DCMAKE_C_FLAGS:STRING=-fPIC -S $SRCDIR/bit7z -B $BUILDDIR/bit7z
echo -e "\n ----- chdir to $BUILDDIR/bit7z -----\n"
cd $BUILDDIR/bit7z
echo -e "\n ----- Running cmake --build . --config Release -----\n"
cmake --build . --config Release
cp -uv $SRCDIR/bit7z/lib/x64/libbit7z64.a $BUILDDIR/bit7z/
cp -uv $SRCDIR/bit7z/lib/x64/libbit7z64.a $SRCDIR/bit7z/
mkdir -p ~/rpmbuild/SOURCES/bin/bit7z/
cp -uv $SRCDIR/bit7z/lib/x64/libbit7z64.a ~/rpmbuild/SOURCES/bin/bit7z/
cp -uv $SRCDIR/bit7z/lib/x64/libbit7z64.a ~/rpmbuild/SOURCES/src/bit7z/
echo -e "\n Built bit7z in $BUILDDIR/bit7z\n"

# ----- build smtp module -----
echo $QMAKEBIN -o $BUILDDIR/smtp/Makefile $SRCDIR/smtp/SMTPEmail.pro -spec linux-g++
$QMAKEBIN -o $BUILDDIR/smtp/Makefile $SRCDIR/smtp/SMTPEmail.pro -spec linux-g++
cd $BUILDDIR/smtp
make -j 16
echo -e "\n Built smtp in $BUILDDIR/smtp\n"

# ----- build squirrel library -----
echo -e "\nBuilding squirrel library\n"
echo $QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrellib.pro -spec linux-g++
$QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrellib.pro -spec linux-g++
cd $BUILDDIR/squirrel
make -j 16
echo -e "\n Built squirrel library in $BUILDDIR/squirrel\n"

# ----- build squirrel command line utilities -----
echo -e "\nBuilding squirrel utilities\n"
echo $QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrel.pro -spec linux-g++
$QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrel.pro -spec linux-g++
cd $BUILDDIR/squirrel
make -B -j 16
echo -e "\n Built squirrel utilites in $BUILDDIR/squirrel\n"

# ----- build NiDB core -----
echo -e "\nBuilding NiDB core\n"
# create make file in the build directory
$QMAKEBIN -o $BUILDDIR/nidb/Makefile $SRCDIR/nidb/nidb.pro -spec linux-g++
cd $BUILDDIR/nidb
make -B -j 16
echo -e "\n Built nidb in $BUILDDIR/nidb\n"
