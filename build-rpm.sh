#!/bin/sh

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

# ----- choose the bit7z IUnknown ABI to match the installed 7-Zip -----
# bit7z must be built for the same 7-Zip generation that squirrel loads at runtime. 7-Zip 23.01 changed the
# IUnknown interface on Linux: p7zip and 7-Zip <= 22.01 need BIT7Z_USE_LEGACY_IUNKNOWN=ON, 7-Zip >= 23.01
# (EPEL 9/10 "7zip", which replaces p7zip; Debian 13; Ubuntu 24) needs OFF. A mismatch segfaults inside 7z.so
# when a package is written. The paths are checked in the same order as squirrel::Get7zipLibPath(), so this
# picks the library squirrel will load on a machine like this one. Set BIT7Z_LEGACY_IUNKNOWN=ON|OFF to override.
if [ -z "$BIT7Z_LEGACY_IUNKNOWN" ]; then
	for lib in /usr/libexec/p7zip/7z.so /usr/libexec/p7zip/7za.so /usr/lib/7zip/7z.so /usr/lib/p7zip/7z.so /usr/libexec/7zip/7z.so; do
		[ -f "$lib" ] || continue
		case "$lib" in
			*/p7zip/*) ver="16.02" ;; # p7zip's last release
			*)
				# version of the package that owns the library, with any epoch and suffix removed (1:23.01+dfsg-1 -> 23.01)
				if command -v rpm >/dev/null 2>&1 && rpm -qf "$lib" >/dev/null 2>&1; then
					ver=$(rpm -q --qf '%{VERSION}' -f "$lib")
				elif command -v dpkg-query >/dev/null 2>&1; then
					ver=$(dpkg-query -W -f '${Version}' "$(dpkg-query -S "$lib" 2>/dev/null | head -1 | cut -d: -f1)" 2>/dev/null)
				fi
				ver=$(echo "$ver" | sed 's/^[0-9]*://; s/[^0-9.].*//')
				;;
		esac
		if [ -z "$ver" ]; then
			echo "Found 7-Zip library $lib but could not determine its version. Set BIT7Z_LEGACY_IUNKNOWN=ON (7-Zip <= 22.01) or OFF (>= 23.01)"
			exit 1
		fi
		if [ "$(printf '%s\n%s\n' "$ver" 23.01 | sort -V | head -1)" = "23.01" ]; then
			BIT7Z_LEGACY_IUNKNOWN=OFF
		else
			BIT7Z_LEGACY_IUNKNOWN=ON
		fi
		echo "Found 7-Zip $ver library $lib. Building bit7z with BIT7Z_USE_LEGACY_IUNKNOWN=$BIT7Z_LEGACY_IUNKNOWN"
		break
	done
fi
if [ -z "$BIT7Z_LEGACY_IUNKNOWN" ]; then
	echo "No 7-Zip library found, so bit7z can't be matched to it. Install 7zip (or p7zip-plugins/p7zip-full on older systems), or set BIT7Z_LEGACY_IUNKNOWN=ON|OFF"
	exit 1
fi

# ----- build bit7z library -----
echo -e "\n ----- Building bit7z -----\n"
echo -e "\n ----- Created path $BUILDDIR/bit7z -----\n"
mkdir -p $BUILDDIR/bit7z
echo -e "\n ----- Running cmake -DBIT7Z_AUTO_FORMAT:BOOL=ON -DBIT7Z_USE_LEGACY_IUNKNOWN=$BIT7Z_LEGACY_IUNKNOWN -DBIT7Z_GENERATE_PIC=ON -DCMAKE_CXX_FLAGS:STRING=-fPIC -DCMAKE_C_FLAGS:STRING=-fPIC -S $SRCDIR/bit7z -B $BUILDDIR/bit7z -----\n"
cmake -DBIT7Z_AUTO_FORMAT:BOOL=ON -DBIT7Z_USE_LEGACY_IUNKNOWN=$BIT7Z_LEGACY_IUNKNOWN -DBIT7Z_GENERATE_PIC=ON -DCMAKE_CXX_FLAGS:STRING=-fPIC -DCMAKE_C_FLAGS:STRING=-fPIC -S $SRCDIR/bit7z -B $BUILDDIR/bit7z
echo -e "\n ----- chdir to $BUILDDIR/bit7z -----\n"
cd $BUILDDIR/bit7z
echo -e "\n ----- Running cmake --build . --config Release -----\n"
# bit7z writes its library into the source tree (src/bit7z/lib/x64), which makeAllInstallers.sh shares between
# distros. Remove it first, so it's always relinked from this build directory's objects, not left from another distro
rm -f $SRCDIR/bit7z/lib/x64/libbit7z64.a
cmake --build . --config Release
cp -fv $SRCDIR/bit7z/lib/x64/libbit7z64.a $BUILDDIR/bit7z/
cp -fv $SRCDIR/bit7z/lib/x64/libbit7z64.a $SRCDIR/bit7z/
mkdir -p ~/rpmbuild/SOURCES/bin/bit7z/
cp -fv $SRCDIR/bit7z/lib/x64/libbit7z64.a ~/rpmbuild/SOURCES/bin/bit7z/
cp -fv $SRCDIR/bit7z/lib/x64/libbit7z64.a ~/rpmbuild/SOURCES/src/bit7z/
echo -e "\n Built bit7z in $BUILDDIR/bit7z\n"

# ----- build squirrel library -----
echo -e "\nBuilding squirrel library\n"
echo $QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrellib.pro -spec linux-g++
$QMAKEBIN -o $BUILDDIR/squirrel/Makefile $SRCDIR/squirrel/squirrellib.pro -spec linux-g++
cd $BUILDDIR/squirrel
make -j 16
echo -e "\n Built squirrel library in $BUILDDIR/squirrel\n"

# ----- build NiDB core -----
echo -e "\nBuilding NiDB core\n"
# create make file in the build directory
$QMAKEBIN -o $BUILDDIR/nidb/Makefile $SRCDIR/nidb/nidb.pro -spec linux-g++
cd $BUILDDIR/nidb
make -B -j 16
echo -e "\n Built nidb in $BUILDDIR/nidb\n"

