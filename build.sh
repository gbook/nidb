#!/bin/sh

# global build variables
#QMAKEBIN=~/Qt/5.12.8/gcc_64/bin/qmake
QMAKEBIN=$1
BUILDDIR=bin
SRCDIR=src

# this script requires make, cmake3, and qmake
command -v make >/dev/null 2>&1 || { echo -e "\nThis script requires make, but it is not installed\n"; exit 1; }
command -v gcc >/dev/null 2>&1 || { echo -e "\nThis script requires gcc, but it is not installed\n"; exit 1; }

# create the build directory
echo "Creating build directory"
mkdir -p $BUILDDIR

# ----- build pre-requisites -----

# build gdcm (make sure cmake 3.x is installed)
if [ ! -d "$BUILDDIR/gdcm" ]; then
	command -v cmake >/dev/null 2>&1 || { echo -e "\nThis script requires cmake 3.x. Install using 'yum install cmake3' or 'apt-get cmake'.\n"; exit 1; }

	echo -e "\ngdcm not built. Building gdcm now\n"

	mkdir -p $BUILDDIR/gdcm
	cd $BUILDDIR/gdcm
	cmake -DGDCM_BUILD_SHARED_LIBS:STRING=YES -DGDCM_BUILD_TESTING:STRING=NO -DGDCM_BUILD_EXAMPLES:STRING=NO $SRCDIR/gdcm
	make
	cd ..
else
	echo -e "\ngdcm already built. Using $BUILDDIR/gdcm\n"
fi

# build smtp module
if [ ! -d "$BUILDDIR/smtp" ]; then

	echo -e "\nsmtp module not built. Building smtp module now\n"

	$QMAKEBIN -o $BUILDDIR/smtp/Makefile $SRCDIR/smtp/SMTPEmail.pro -spec linux-g++
	cd $BUILDDIR/smtp
	make
	cd ..
else
	echo -e "\nsmtp already built. Using $BUILDDIR/smtp\n"
fi

# build NiDB core
echo -e "\nBuilding NiDB core\n"
# create make file in the build directory
$QMAKEBIN -o $BUILDDIR/nidb/Makefile $SRCDIR/nidb/nidb.pro -spec linux-g++
cd $BUILDDIR/nidb
make
cd ..