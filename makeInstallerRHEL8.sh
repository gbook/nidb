#!/bin/sh

cd ~
rm -rfv master.zip nidb-master rpmbuild
rpmdev-setuptree
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master/* rpmbuild/SOURCES/
cp -v rpmbuild/SOURCES/src/setup/nidb.el8.spec rpmbuild/SPECS/
cd rpmbuild/SPECS
QA_RPATHS=$((0x0002|0x0010)) rpmbuild -bb nidb.el8.spec