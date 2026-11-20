#!/bin/sh

# WSL appends the Windows PATH (which contains spaces, e.g. "Program Files"),
# which breaks PATH-walking in the sudo copy steps below (env: 'Files': No such
# file or directory). Drop the /mnt/ (Windows) entries.
PATH="$(printf %s "$PATH" | tr ':' '\n' | grep -v '^/mnt/' | paste -sd: -)"
export PATH

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

ORIGDIR=$PWD

# this script requires make, gcc, cmake (3.16+), and qmake
command -v make >/dev/null 2>&1 || { echo -e "\nThis script requires make, but it is not installed\n"; exit 1; }
command -v gcc >/dev/null 2>&1 || { echo -e "\nThis script requires gcc, but it is not installed\n"; exit 1; }
command -v cmake >/dev/null 2>&1 || { echo -e "\nThis script requires cmake 3.16 or newer, but it is not installed\n"; exit 1; }

# create the build directory
echo "Creating build directory"
mkdir -p $BUILDDIR

# ----- build squirrel library (CMake) -----
# src/squirrel is a verbatim copy of the squirrel repo's src/ directory. Its top-level CMakeLists.txt builds bit7z,
# libsquirrel, and the modern 7-Zip library (7z.so) that libsquirrel loads at runtime:
#   $BUILDDIR/squirrel/squirrel/libsquirrel.a
#   $BUILDDIR/squirrel/bit7z/libbit7z64.a
#   $BUILDDIR/squirrel/7zip/7z.so (+ 7zip-*.txt licenses), installed next to the nidb binary
# BIT7Z_USE_LEGACY_IUNKNOWN must be OFF to match the bundled modern 7-Zip.
QTDIR=$(dirname "$(dirname "$QMAKEBIN")")
SQUIRRELBUILDDIR=$BUILDDIR/squirrel

# remove output from the old qmake-based squirrel build and the old standalone bit7z build
if [ -f "$SQUIRRELBUILDDIR/Makefile" ] && [ ! -f "$SQUIRRELBUILDDIR/CMakeCache.txt" ]; then
	echo "Removing old qmake squirrel build in $SQUIRRELBUILDDIR"
	rm -rf "$SQUIRRELBUILDDIR"
fi
rm -rf "$BUILDDIR/bit7z"

# CMakeCache.txt stores the absolute source path. makeAllInstallers.sh may reach the same tree through a different
# path, and cmake aborts if the spelling differs, so drop a cache written for another path
if [ -f "$SQUIRRELBUILDDIR/CMakeCache.txt" ] && ! grep -qxF "CMAKE_HOME_DIRECTORY:INTERNAL=$SRCDIR/squirrel" "$SQUIRRELBUILDDIR/CMakeCache.txt"; then
	echo "Removing stale CMake cache in $SQUIRRELBUILDDIR"
	rm -rf "$SQUIRRELBUILDDIR/CMakeCache.txt" "$SQUIRRELBUILDDIR/CMakeFiles"
fi

echo -e "\n ----- Configuring squirrel library -----\n"
cmake -S "$SRCDIR/squirrel" -B "$SQUIRRELBUILDDIR" \
	-DCMAKE_BUILD_TYPE=Release \
	-DCMAKE_PREFIX_PATH="$QTDIR" \
	-DBIT7Z_USE_LEGACY_IUNKNOWN=OFF \
	-DSQUIRREL_BUILD_CLI=OFF \
	-DSQUIRREL_BUILD_GUI=OFF || { echo "squirrel CMake configure failed"; exit 1; }

echo -e "\n ----- Building squirrel library -----\n"
cmake --build "$SQUIRRELBUILDDIR" -j 16 || { echo "squirrel build failed"; exit 1; }
echo -e "\n Built squirrel library in $SQUIRRELBUILDDIR\n"

# ----- build NiDB core -----
echo -e "\nBuilding NiDB core\n"
# create make file in the build directory
$QMAKEBIN -o $BUILDDIR/nidb/Makefile $SRCDIR/nidb/nidb.pro -spec linux-g++
cd $BUILDDIR/nidb
make -B -j 16

# try to copy the binaries to their final locations (this may fail because it requires sudo, but its not a critical step to build)
cd $ORIGDIR
#sudo cp -auv $BUILDDIR/gdcm/bin/libgdcm* /lib64/
#sudo cp -auv $BUILDDIR/gdcm/bin/libgdcm* /lib/x86_64-linux-gnu/
#sudo cp -auv $BUILDDIR/smtp/libSMTPEmail* /lib/
#sudo cp -auv $BUILDDIR/smtp/libSMTPEmail* /lib/x86_64-linux-gnu/

echo -e "\nCopying nidb to /nidb/bin"
sudo mkdir -p /nidb/bin
sudo cp -uv $BUILDDIR/nidb/nidb /nidb/bin/
# the 7-Zip library libsquirrel loads, which it looks for next to the nidb binary first
sudo cp -uv $BUILDDIR/squirrel/7zip/7z.so $BUILDDIR/squirrel/7zip/7zip-*.txt /nidb/bin/
