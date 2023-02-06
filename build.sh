#!/bin/sh

# global build variables
if [ -z "$1" ]; then
	QMAKEBIN=~/Qt/6.4.2/gcc_64/bin/qmake
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

ORIGDIR=$PWD

# this script requires make, cmake (3), and qmake
command -v make >/dev/null 2>&1 || { echo -e "\nThis script requires make, but it is not installed\n"; exit 1; }
command -v gcc >/dev/null 2>&1 || { echo -e "\nThis script requires gcc, but it is not installed\n"; exit 1; }

# create the build directory
echo "Creating build directory"
mkdir -p $BUILDDIR

# ----- build pre-requisites -----

# build gdcm (make sure cmake 3 is installed)
if [ ! -d "$BUILDDIR/gdcm" ]; then
	command -v cmake >/dev/null 2>&1 || { echo -e "\nThis script requires cmake 3.x. Install using 'yum install cmake' or 'apt-get cmake'.\n"; exit 1; }

	echo -e "\ngdcm not built. Building gdcm now\n"

	mkdir -p $BUILDDIR/gdcm
	mkdir -p $BUILDDIR/gdcm
	cd $BUILDDIR/gdcm
	cmake -DGDCM_BUILD_APPLICATIONS:STRING=NO -DGDCM_BUILD_DOCBOOK_MANPAGES:BOOL=OFF -DGDCM_BUILD_SHARED_LIBS:STRING=YES -DGDCM_BUILD_TESTING:STRING=NO -DGDCM_BUILD_EXAMPLES:STRING=NO $SRCDIR/gdcm
	make -j 16
else
	echo -e "\ngdcm already built in $BUILDDIR/gdcm\n"
fi

# ----- build smtp module -----
if [ ! -d "$BUILDDIR/smtp" ]; then

	echo -e "\nsmtp module not built. Building smtp module now\n"

	echo $QMAKEBIN -o $BUILDDIR/smtp/Makefile $SRCDIR/smtp/SMTPEmail.pro -spec linux-g++
	
	$QMAKEBIN -o $BUILDDIR/smtp/Makefile $SRCDIR/smtp/SMTPEmail.pro -spec linux-g++
	cd $BUILDDIR/smtp
	make -j 16
else
	echo -e "\nsmtp already built in $BUILDDIR/smtp\n"
fi

# ----- build squirrel library -----
#if [ ! -d "$BUILDDIR/squirrel" ]; then

#	echo -e "\nsquirrel module not built. Building squirrel module now\n"
	echo -e "\nBuilding squirrel library\n"

	echo $QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrellib.pro -spec linux-g++
	
	$QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrellib.pro -spec linux-g++
	cd $BUILDDIR/squirrel
	make -j 16
#else
#	echo -e "\nsquirrel already built in $BUILDDIR/squirrel\n"
#fi

# ----- build NiDB core -----
echo -e "\nBuilding NiDB core\n"
# create make file in the build directory
$QMAKEBIN -o $BUILDDIR/nidb/Makefile $SRCDIR/nidb/nidb.pro -spec linux-g++
cd $BUILDDIR/nidb
make -B -j 16

# try to copy the binaries to their final locations (this may fail because it requires sudo, but its not a critical step to build)
cd $ORIGDIR
echo -e "\nCopying libsquirrel to /lib"
sudo cp -uv bin/squirrel/libsquirrel* /lib/

echo -e "\nCopying nidb to /nidb/bin"
sudo cp -uv bin/nidb/nidb /nidb/bin/
