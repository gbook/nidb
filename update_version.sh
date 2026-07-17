#!/bin/bash

YEAR=$(date +%Y)
MONTH=$(date +%-m)
COMMITS=$(git rev-list --count HEAD)
VERSION="${YEAR}.${MONTH}.${COMMITS}"

echo "Updating version to $VERSION"

# src/nidb/version.h
sed -i "s/#define VERSION_MAJ \"[^\"]*\"/#define VERSION_MAJ \"${YEAR}\"/" src/nidb/version.h
sed -i "s/#define VERSION_MIN \"[^\"]*\"/#define VERSION_MIN \"${MONTH}\"/" src/nidb/version.h
sed -i "s/#define BUILD_NUM \"[^\"]*\"/#define BUILD_NUM \"${COMMITS}\"/" src/nidb/version.h

# spec files
sed -i "s/^Version:.*/Version:        ${VERSION}/" src/setup/nidb.el8.spec
sed -i "s/^Version:.*/Version:        ${VERSION}/" src/setup/nidb.el9.spec
sed -i "s/^Version:.*/Version:        ${VERSION}/" src/setup/nidb.el10.spec

# makeInstallerDebian12.sh
sed -i "s/^PACKAGE=nidb_.*/PACKAGE=nidb_${VERSION}/" makeInstallerDebian12.sh

echo "Done."
