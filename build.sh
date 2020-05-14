#!/bin/sh

# global build variables
QMAKEBIN=~/Qt/5.12.3/gcc_64/bin/qmake
BUILDDIR=~/nidb/bin
SRCDIR=~/nidb/src


# this script requires make, cmake3, and qmake
command -v make >/dev/null 2>&1 || { echo -e "\nThis script requires make, but it's not installed\n"; exit 1; }
command -v gcc >/dev/null 2>&1 || { echo -e "\nThis script requires gcc, but it's not installed\n"; exit 1; }

# create the build directory
echo "Creating build directory"
mkdir -p $BUILDDIR

# ----- build pre-requisites -----

# build gdcm (make sure cmake3 is installed)
if [ ! -d "$BUILDDIR/gdcm" ]; then
	command -v cmake3 >/dev/null 2>&1 || { echo -e "\nThis script requires cmake3, but it's not installed. Install using 'yum install cmake3'.\n"; exit 1; }

	echo -e "\ngdcm not built. Building gdcm now\n"

	mkdir -p $BUILDDIR/gdcm
	cd $BUILDDIR/gdcm
	cmake3 -DGDCM_BUILD_SHARED_LIBS:STRING=YES -DGDCM_BUILD_TESTING:STRING=NO -DGDCM_BUILD_EXAMPLES:STRING=NO $SRCDIR/gdcm
	make
else
	echo -e "\ngdcm already built. Using $BUILDDIR/gdcm\n"
fi

# build smtp module
if [ ! -d "$BUILDDIR/smtp" ]; then

	echo -e "\nsmtp module not built. Building smtp module now\n"

	$QMAKEBIN -o $BUILDDIR/smtp/Makefile $SRCDIR/smtp/SMTPEmail.pro -spec linux-g++
	cd $BUILDDIR/smtp
	make
else
	echo -e "\nsmtp already built. Using $BUILDDIR/smtp\n"
fi

# build NiDB core
echo -e "\nBuilding NiDB core\n"
# create make file in the build directory
$QMAKEBIN -o $BUILDDIR/nidb/Makefile $SRCDIR/nidb/nidb.pro -spec linux-g++
cd $BUILDDIR/nidb
make

