---
description: How to build NiDB and contribute to its development
---

# Building NiDB

## Building NiDB

The following OS configurations have been tested to build nidb with Qt 6.3

* <mark style="color:green;">Compatible</mark>
  * Rocky Linux 8.6
  * CentOS 8
  * CentOS 7

{% hint style="danger" %}
**CentOS Stream 8 is incompatible with NiDB.** There are kernel bugs which do not work correctly with Qt's QProcess library. This can lead to inconsistencies when running shell commands through NiDB.
{% endhint %}

Other OS configurations may work to build nidb, but extensive testing is needed.

### Prepare Build Environment

Install development tools on **Rocky Linux 8.5** (Recommended)

```bash
yum group install 'Development Tools'
yum install cmake3
yum install rpmdevtools
yum install xcb*
yum install libxcb*
yum install gcc-toolset-10
```

Install development tools on **CentOS 7**

```bash
yum install epel-release
yum group install 'Development Tools'
yum install cmake3 rpmdevtools rpm-build
```

### Install Qt 6.3.1

1. Download Qt open-source from https://www.qt.io/download-open-source
2. Make the installer executable `chmod 777 qt-unified-linux-x64-x.x.x-online.run`
3. Run `./qt-unified-linux-x64-x.x.x-online.run`
4. The Qt Maintenance Tool will start. An account is required to download Qt open source
5. On the components screen, select the checkbox for **Qt 6.3.1 → Desktop gcc 64-bit**

### Building NiDB

Once the build environment is setup, the builds can be done by script. The `build.sh` script will build only the nidb executable, this is useful when testing. The `rpmbuildx.sh` scripts will build the rpm which will create releases.

#### Rocky Linux 8.5

The first time building NiDB on this machine, perform the following

```bash
cd ~
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master nidb
cd nidb
./build.sh      # build only the NiDB executable
./rpmbuild8.sh  # build the nidb .rpm
```

All subsequent builds on this machine can be done with the following

```bash
cd ~/nidb
./build.sh      # build only the NiDB executable
./rpmbuild8.sh  # build the nidb .rpm
```

### Contributing to the NiDB Project

#### Setting up a development server

A development server can be a full server, a VM, or any installation of one of the supported Linux operating systems. Once you've been granted access to the nidb project on github, you'll need to add your SSH key under your account (github.com --> click your username --> Settings --> SSH and GPG keys). There are directions on the github site for how to do this. Then you can clone the current source code into your .

#### Cloning a new repository with SSH

```bash
cd ~
git clone git@github.com:gbook/nidb.git nidb
```

This will create a git repository called nidb in your home directory.

#### Committing changes

```bash
cd ~/nidb
# Copy in any webpage changes. Be careful not to overwrite uncommitted edits
cp -uv /var/www/html/*.php ~/nidb/src/web/
git commit -am "Comments about the changes"
git push origin master
```

#### Updating your repository

To keep your local copy of the repository up to date, you'll need to pull any changes from github.

```bash
cd ~/nidb
git pull origin master
# Copy out any webpage changes. Be careful not to overwrite uncommitted edits
cp -uv ~/nidb/src/web/*.php /var/www/html/
```

### Troubleshooting

#### Build freezes

This may happen if the build machine does not have enough RAM or processors. More likely this is happening inside of a VM in which the VM does not have enough RAM or processors allocated.

#### Build fails with "QMAKE\_CXX.COMPILER\_MACROS not defined"

Unclear why this happens, but it appears to be a corrupt Qt installation. First check if you can build the project using Qt Creator. If the build fails in Qt Creator, then there is most likely an issue with the Qt installation. Try completely uninstalling Qt, and then reinstalling it.