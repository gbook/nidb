#!/bin/sh

# optional flags (may appear anywhere; removed before reading the positional args below)
#   --cluster   also build the nidbcluster executable (not built by default)
BUILDCLUSTER=0
for arg in "$@"; do
	shift
	case "$arg" in
		--cluster) BUILDCLUSTER=1 ;;
		*) set -- "$@" "$arg" ;;
	esac
done

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

# ----- build NiDB cluster (cluster-only executable; links neither dcmtk nor squirrel) -----
if [ "$BUILDCLUSTER" = "1" ]; then
	echo -e "\nBuilding NiDB cluster\n"
	$QMAKEBIN -o $BUILDDIR/nidbcluster/Makefile $SRCDIR/nidb/nidbcluster.pro -spec linux-g++
	cd $BUILDDIR/nidbcluster
	make -B -j 16
	echo -e "\n Built nidbcluster in $BUILDDIR/nidbcluster\n"
fi
