#!/bin/sh

# build the NiDB .rpm from the local source tree (the git repository containing this script).
# tracked files are exported, including uncommitted changes; untracked files are not included.
set -e

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
rpmdev-setuptree
git -C "$SRCDIR" archive "$SNAPSHOT" | tar -x -C rpmbuild/SOURCES/
cp -v rpmbuild/SOURCES/src/setup/nidb.el10.spec rpmbuild/SPECS/
cd rpmbuild/SPECS
QA_RPATHS=$((0x0002|0x0010)) rpmbuild -bb nidb.el10.spec
