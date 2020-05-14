#!/bin/sh

# global build variables
QMAKEBIN=/Qt/5.12.3/gcc_64/bin/qmake
BUILDDIR=/nidb/nidbgit/bin
SRCDIR=/nidb/nidbgit/src


# this script requires make, cmake3, and qmake
command -v make >/dev/null 2>&1 || { echo >&2 "This script requires make, but it's not installed"; exit 1; }
command -v gcc >/dev/null 2>&1 || { echo >&2 "This script requires gcc, but it's not installed"; exit 1; }

# create the build directory
echo "Creating build directory"
mkdir -p $BUILDDIR

# ----- build pre-requisites -----

# build gdcm (make sure cmake3 is installed)
if [ ! -d "$BUILDDIR/gdcm" ]; then
	command -v cmake3 >/dev/null 2>&1 || { echo >&2 "This script requires cmake3, but it's not installed. Install using 'yum install cmake3'."; exit 1; }

	echo "gdcm not built. Building gdcm now"

	mkdir -p $BUILDDIR/gdcm
	cd $BUILDDIR/gdcm
	cmake3 $SRCDIR/gdcm
	make
else
	echo "gdcm already built. Using $BUILDDIR/gdcm"
fi

# build smtp module
if [ ! -d "$BUILDDIR/smtp" ]; then

	echo "smtp module not built. Building smtp module now"

	$QMAKEBIN -o $BUILDDIR/smtp/Makefile $SRCDIR/smtp/SMTPEmail.pro -spec linux-g++
	cd $BUILDDIR/smtp
	make
else
	echo "smtp already built. Using $BUILDDIR/gdcm"
fi

# build NiDB core
echo "Building NiDB core"
# create make file in the build directory
$QMAKEBIN -o $BUILDDIR/nidb/Makefile $SRCDIR/nidb/nidb.pro -spec linux-g++ # CONFIG+=qtquickcompiler
cd $BUILDDIR/nidb
make

