#!/bin/sh

if grep -q -i "release 8" /etc/redhat-release
then
	echo "RHEL 8 detected. Enabling gcc 10"
	#scl enable gcc-toolset-10 bash
	#scl enable gcc-toolset-10 bash || true
	source /opt/rh/gcc-toolset-10/enable
fi

# global build variables
if [ -z "$1" ]; then
	QMAKEBIN=~/Qt/6.9.3/gcc_64/bin/qmake
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

# ----- build NiDB cluster (cluster-only executable; links neither dcmtk nor squirrel) -----
echo -e "\nBuilding NiDB cluster\n"
$QMAKEBIN -o $BUILDDIR/nidbcluster/Makefile $SRCDIR/nidb/nidbcluster.pro -spec linux-g++
cd $BUILDDIR/nidbcluster
make -B -j 16

# try to copy the binaries to their final locations (this may fail because it requires sudo, but its not a critical step to build)
cd $ORIGDIR
echo -e "\nCopying libsquirrel to /lib"
sudo cp -uv $BUILDDIR/squirrel/libsquirrel.a /lib64/
sudo cp -uv $BUILDDIR/squirrel/libsquirrel.a /lib/x86_64-linux-gnu/
#sudo cp -auv $BUILDDIR/gdcm/bin/libgdcm* /lib64/
#sudo cp -auv $BUILDDIR/gdcm/bin/libgdcm* /lib/x86_64-linux-gnu/
#sudo cp -auv $BUILDDIR/smtp/libSMTPEmail* /lib/
#sudo cp -auv $BUILDDIR/smtp/libSMTPEmail* /lib/x86_64-linux-gnu/

echo -e "\nCopying nidb to /nidb/bin"
sudo mkdir -p /nidb/bin
sudo cp -uv $BUILDDIR/nidb/nidb /nidb/bin/

echo -e "\nCopying nidbcluster to /nidb/bin"
sudo cp -uv $BUILDDIR/nidbcluster/nidbcluster /nidb/bin/
