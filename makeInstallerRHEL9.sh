#!/bin/sh

# build the NiDB .rpm from the local source tree (the git repository containing this script).
# tracked files are exported, including uncommitted changes; untracked files are not included.
set -e

if ! command -v rpmbuild >/dev/null 2>&1; then
	echo "rpmbuild not found. Install it with: sudo dnf install rpm-build"
	exit 1
fi

SRCDIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SRCDIR"
SNAPSHOT=$(git stash create) # commit object of the working tree, or empty if there are no local changes
if [ -n "$SNAPSHOT" ]; then
	echo "Building from $SRCDIR (HEAD $(git rev-parse --short HEAD) plus uncommitted changes)"
else
	SNAPSHOT=HEAD
	echo "Building from $SRCDIR (HEAD $(git rev-parse --short HEAD))"
fi

cd ~
rm -rfv rpmbuild
mkdir -p rpmbuild/BUILD rpmbuild/RPMS rpmbuild/SOURCES rpmbuild/SPECS rpmbuild/SRPMS
git -C "$SRCDIR" archive "$SNAPSHOT" | tar -x -C rpmbuild/SOURCES/
cp -v rpmbuild/SOURCES/src/setup/nidb.el9.spec rpmbuild/SPECS/
cd rpmbuild/SPECS
QA_RPATHS=$((0x0002|0x0010)) rpmbuild -bb nidb.el9.spec
