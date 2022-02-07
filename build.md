<style
  type="text/css">
h1 { counter-reset: h2counter; }
h2 { counter-reset: h3counter; }
h3 { counter-reset: h4counter; }
h4 { counter-reset: h5counter; }
h5 { counter-reset: h6counter; }
h6 {}

h2:before {
    counter-increment: h2counter;
    content: counter(h2counter) ".\0000a0\0000a0";
}

h3:before {
    counter-increment: h3counter;
    content: counter(h2counter) "." counter(h3counter) ".\0000a0\0000a0";
}

h4:before {
    counter-increment: h4counter;
    content: counter(h2counter) "." counter(h3counter) "." counter(h4counter) ".\0000a0\0000a0";
}

h5:before {
    counter-increment: h5counter;
    content: counter(h2counter) "." counter(h3counter) "." counter(h4counter) "." counter(h5counter) ".\0000a0\0000a0";
}

h6:before {
    counter-increment: h6counter;
    content: counter(h2counter) "." counter(h3counter) "." counter(h4counter) "." counter(h5counter) "." counter(h6counter) ".\0000a0\0000a0";
}
</style>

# Building NiDB
## Prepare Build Environment
Install development tools on **CentOS 9 Stream**
```
yum group install 'Development Tools'
yum install cmake rpmdevtools rpm-build
```
Install development tools on **CentOS 8 Stream / CentOS 8**
```
yum group install 'Development Tools'
yum install cmake3 rpmdevtools rpm-build
```
Install development tools on **CentOS 7**
```
yum install epel-release
yum group install 'Development Tools'
yum install cmake3 rpmdevtools rpm-build
```

## Install Qt 6.2.3
   - Download Qt open-source from https://www.qt.io/download-open-source
   - Make the installer executable `chmod 777 qt-unified-linux-x64-x.x.x-online.run`
   - Run `./qt-unified-linux-x64-x.x.x-online.run`
   - The Qt Maintenance Tool will start. An account is required to download Qt open source
   - On the components screen, select the checkbox for Qt 6.2.3 &rarr; Desktop gcc 64-bit

## Build rpm Package
### CentOS 9 Stream
The first time building NiDB on this machine, perform the following
```
cd ~
rpmdev-setuptree
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master/* rpmbuild/SOURCES/
cp rpmbuild/SOURCES/src/setup/nidb.el8.spec rpmbuild/SPECS/
cd rpmbuild/SPECS
QA_RPATHS=$((0x0002|0x0010)) rpmbuild -bb nidb.el8.spec
```
All subsequent builds on this machine can be done with the following
```
cd ~/nidb
./rpmbuild9.sh
```
