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

<a href="index.html">Home</a>

# Building NiDB
The following OS configurations have been tested to build nidb successfully with Qt 6.3
- RHEL9 compatible
  - <strike>CentOS 9 Stream</strike>(NOPE. Don't use CentOS Stream. There are some significant bugs with Qt running on CentOS Stream)
- RHEL8 compatible
  - Rocky Linux 8.5

Other OS configurations may work if building with Qt 5.15

## Prepare Build Environment
Install development tools on **Rocky Linux 8.5** (Recommended)
```
yum group install 'Development Tools'
yum install cmake3
yum install rpmdevtools
yum install xcb*
yum install libxcb*
yum install gcc-toolset-10
```

Install development tools on **CentOS 7**
```
yum install epel-release
yum group install 'Development Tools'
yum install cmake3 rpmdevtools rpm-build
```

<strike>Install development tools on **CentOS 9 Stream**
```
yum group install 'Development Tools'
yum install cmake rpmdevtools rpm-build
```
</strike>

## Install Qt 6.3.1
   - Download Qt open-source from https://www.qt.io/download-open-source
   - Make the installer executable `chmod 777 qt-unified-linux-x64-x.x.x-online.run`
   - Run `./qt-unified-linux-x64-x.x.x-online.run`
   - The Qt Maintenance Tool will start. An account is required to download Qt open source
   - On the components screen, select the checkbox for **Qt 6.3.1 &rarr; Desktop gcc 64-bit**

## Building NiDB
Once the build environment is setup, the builds can be done by script. The `build.sh` script will build onlty the nidb executable, this is useful when testing. The `rpmbuildX.sh` scripts will build the rpm, this is useful when creating releases.

### CentOS 9 Stream
The first time building NiDB on this machine, perform the following
```
cd ~
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master nidb
cd nidb
./build.sh      # build only the NiDB executable
./rpmbuild9.sh  # build the nidb .rpm
```
All subsequent builds on this machine can be done with the following
```
cd ~/nidb
./build.sh      # build only the NiDB executable
./rpmbuild9.sh  # build the nidb .rpm
```

### Rocky Linux 8.5
The first time building NiDB on this machine, perform the following
```
cd ~
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master nidb
cd nidb
./build.sh      # build only the NiDB executable
./rpmbuild8.sh  # build the nidb .rpm
```
All subsequent builds on this machine can be done with the following
```
cd ~/nidb
./build.sh      # build only the NiDB executable
./rpmbuild8.sh  # build the nidb .rpm
```

## Contributing to the NiDB Project
### Setting up a development server
A development server can be a full server, a VM, or any instance of one of the supported Linux operating systems. Once you've been granted access to the nidb project on github, you'll need to add your SSH key (github.com --> click your username --> Settings --> SSH and GPG keys). Then you can clone the current source code.

### Cloning a new repository with SSH
```
cd ~
git clone git@github.com:gbook/nidb.git nidb
```
This will create a git repository called nidb in your home directory.

### Committing changes
```
cd ~/nidb
git commit -am "Comments about the changes"
git push origin master
```

### Updating your repository
To keep your local copy of the repository up to date, you'll need to pull any changes from github.
```
cd ~/nidb
git pull origin master
```

## Troubleshooting

### Build freezes
This may happen if the build machine does not have enough RAM or processors. More likely this is happening inside of a VM in which the VM does not have enough RAM or processors allocated.

## Build fails with "QMAKE_CXX.COMPILER_MACROS not defined"
Unclear why this happens, but it appears to be a corrupt Qt installation. First check if you can build the project using Qt Creator. If the build fails in Qt Creator, then there is most likely an issue with the Qt installation. Try completely uninstalling Qt, and then reinstalling it.
