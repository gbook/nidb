#!/bin/sh

# global build variables
if [ -z "$1" ]; then
	QMAKEBIN=~/Qt/6.6.3/gcc_64/bin/qmake
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

# ----- build bit7z library -----
echo -e "\n ----- Building bit7z -----\n"
mkdir -p $BIT7ZDIR/build
cd $BIT7ZDIR
cmake -DBIT7Z_AUTO_FORMAT:BOOL=ON -DBIT7Z_USE_LEGACY_IUNKNOWN=ON -DBIT7Z_GENERATE_PIC=ON -DCMAKE_CXX_FLAGS:STRING=-fPIC -DCMAKE_C_FLAGS:STRING=-fPIC -S $BIT7ZDIR -B $BIT7ZDIR/build
cmake --build . --config Release

# ----- build smtp module -----
echo $QMAKEBIN -o $BUILDDIR/smtp/Makefile $SRCDIR/smtp/SMTPEmail.pro -spec linux-g++
$QMAKEBIN -o $BUILDDIR/smtp/Makefile $SRCDIR/smtp/SMTPEmail.pro -spec linux-g++
cd $BUILDDIR/smtp
make -j 16

# ----- build squirrel library -----
echo -e "\nBuilding squirrel library\n"
echo $QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrellib.pro -spec linux-g++
$QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrellib.pro -spec linux-g++
cd $BUILDDIR/squirrel
make -j 16

# ----- build NiDB core -----
echo -e "\nBuilding NiDB core\n"
# create make file in the build directory
$QMAKEBIN -o $BUILDDIR/nidb/Makefile $SRCDIR/nidb/nidb.pro -spec linux-g++
cd $BUILDDIR/nidb
make -B -j 16
