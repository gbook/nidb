---
description: How to build NiDB and contribute to its development
---

# Building NiDB

## Compatible Linux Distributions

The following OS configurations have been tested to build nidb. It may be possible to build NiDB on other OS configurations, but only the below environments have been tested.

* <mark style="color:green;">**Tested & Compatible**</mark>
  * RHEL 9 compatible (Rocky Linux 9, AlmaLinux 9, RHEL 9)
  * RHEL 8 compatible (Rocky Linux 8, AlmaLinux 8, CentOS 8, RHEL 8)
  * RHEL 7 compatible (RHEL 7, CentOS 7)
  * Ubuntu 20
  * Debian 12
* <mark style="color:red;">**Incompatible**</mark>
  * RHEL-compatible 8.6 (RHEL 8.6, Rocky 8.6, AlmaLinux 8.6)
  * CentOS 8 Stream
* **Unknown**
  * Windows 10/11 - NiDB will compile and build on Windows, but NiDB uses Linux system calls to perform many background operations, and thus would not work on Windows.

{% hint style="danger" %}
**NiDB cannot be built on CentOS Stream 8 or Rocky Linux 8.6.** These distros contain kernel bugs which are incompatible with the QProcess library.
{% endhint %}

## Prepare Build Environment

#### Step 1 - Install development tools

Run these commands as root (or sudo) based on your distribution

{% tabs %}
{% tab title="RHEL 10" %}
```bash
dnf group install 'Development Tools'
dnf install cmake3
dnf install rpmdevtools
dnf install xcb-util-wm xcb-util-cursor xcb-util-keysyms
dnf install libxkbcommon-x11 libxcb-devel
dnf install libX11-xcb
```
{% endtab %}

{% tab title="RHEL 9" %}
```bash
dnf group install 'Development Tools'
dnf install cmake3
dnf install rpmdevtools
dnf install xcb-util-wm xcb-util-cursor xcb-util-keysyms
dnf install libxkbcommon-x11
dnf install git
```
{% endtab %}

{% tab title="RHEL 8" %}
```bash
dnf group install 'Development Tools'
dnf install cmake3 wget
dnf install rpmdevtools
dnf install xcb-util-wm xcb-util-keysyms
dnf install libxkbcommon-x11
dnf install gcc-toolset-10
dnf install libxcb-devel
dnf install libX11-xcb
dnf install xcb-util-cursor xcb-util-cursor-devel
```
{% endtab %}

{% tab title="RHEL 7" %}
```bash
yum install epel-release
yum group install 'Development Tools'
yum install cmake3 rpmdevtools rpm-build
yum install git
```
{% endtab %}

{% tab title="Ubuntu 20" %}
```bash
apt install build-essential
apt install libxcb*
apt install make
apt install cmake
apt install git
```
{% endtab %}

{% tab title="Debian 12" %}
```bash
apt install build-essential make cmake git
apt install libxcb* libxkb* libX11-xcb*
apt install libdbus-1*
apt install libzstd-dev
apt install libglib2.0-dev
apt install wget   # if needed
```
{% endtab %}
{% endtabs %}

#### Step 2 - Install Qt 6.9.1

1. Download Qt open-source from [https://www.qt.io/download-open-source](https://www.qt.io/download-open-source)
2. Make the installer executable `chmod 777 qt-unified-linux-x64-x.x.x-online.run`
3. Run `./qt-unified-linux-x64-x.x.x-online.run`
4. The Qt Maintenance Tool will start. An account is required to download Qt open source
5. On the components screen, select the checkbox for **Qt 6.9.3 → Desktop gcc 64-bit**
6. **(Optional for building MySQL/MariaDB driver)** On the components screen, select the checkbox for **Qt 6.9.3 → Sources**

#### Step 3 - (Optional) Build MySQL/MariaDB driver for Qt

Sometimes the MySQL/MariaDB driver supplied with Qt will not work correctly, and needs to be built manually. This happens on Debian 12, for example. If building is successful, the path to the driver should eventually be `~/Qt/6.9.3/gcc_64/plugins/sqldrivers/libqsqlmysql.so`

{% tabs %}
{% tab title="RHEL 9" %}
```bash
# need to fix this, don't use it yet.

sudo apt install ninja-build
sudo apt install libmariadb-dev*
sudo apt install libglib2*
cd ~
mkdir build-sqldrivers
cd build-sqldrivers
~/Qt/6.9.1/gcc_64/bin/qt-cmake -G Ninja ~/Qt/6.9.1/Src/qtbase/src/plugins/sqldrivers -DCMAKE_INSTALL_PREFIX=~/Qt/6.9.1/gcc_64 -DMySQL_INCLUDE_DIR="/usr/include/mariadb" -DMySQL_LIBRARY="/usr/lib/x86_64-linux-gnu/libmariadbclient.so"
cmake --build .
cmake --install .
```
{% endtab %}

{% tab title="Debian 12" %}
```bash
sudo apt install ninja-build
sudo apt install libmariadb-dev*
sudo apt install libglib2*
cd ~
mkdir build-sqldrivers
cd build-sqldrivers
~/Qt/6.9.3/gcc_64/bin/qt-cmake -G Ninja ~/Qt/6.9.3/Src/qtbase/src/plugins/sqldrivers -DCMAKE_INSTALL_PREFIX=~/Qt/6.9.3/gcc_64 -DMySQL_INCLUDE_DIR="/usr/include/mariadb" -DMySQL_LIBRARY="/usr/lib/x86_64-linux-gnu/libmariadbclient.so"
cmake --build .
cmake --install .
```
{% endtab %}
{% endtabs %}

## Building the NiDB executable

Once the build environment is setup, the builds can be done by script. The `build.sh` script will build only the nidb executable, this is useful when testing. The `rpmbuildx.sh` scripts will build the rpm which will create releases.

{% tabs %}
{% tab title="RHEL 9" %}
**First time build** on this machine, perform the following

```bash
cd ~
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master nidb
cd nidb
./build.sh      # build only the NiDB executable
./rpmbuild9.sh  # build the nidb .rpm
```

All **subsequent builds** on this machine can be done with the following

```bash
cd ~/nidb
./build.sh      # build only the executable
./rpmbuild9.sh  # build the .rpm
```
{% endtab %}

{% tab title="RHEL 8" %}
**First time build** on this machine, perform the following

```bash
cd ~
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master nidb
cd nidb
./build.sh      # build only the NiDB executable
./rpmbuild8.sh  # build the nidb .rpm
```

All **subsequent builds** on this machine can be done with the following

```bash
cd ~/nidb
./build.sh      # build only the executable
./rpmbuild8.sh  # build the .rpm
```
{% endtab %}

{% tab title="RHEL 7" %}
**First time build** on this machine, perform the following

```bash
cd ~
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master nidb
cd nidb
./build.sh      # build only the NiDB executable
./rpmbuild7.sh  # build the nidb .rpm
```

All **subsequent builds** on this machine can be done with the following

```bash
cd ~/nidb
./build.sh      # build only the executable
./rpmbuild7.sh  # build the .rpm
```
{% endtab %}

{% tab title="Ubuntu 20" %}
**First time build** on this machine, perform the following

```bash
cd ~
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master nidb
cd nidb
./build.sh      # build only the NiDB executable
```

All **subsequent builds** on this machine can be done with the following

```bash
cd ~/nidb
./build.sh      # build only the executable
```
{% endtab %}

{% tab title="Debian 12" %}
**First time build** on this machine, perform the following

```bash
cd ~
wget https://github.com/gbook/nidb/archive/master.zip
unzip master.zip
mv nidb-master nidb
cd nidb
./build.sh      # build only the NiDB executable
```

All **subsequent builds** on this machine can be done with the following

```bash
cd ~/nidb
./build.sh      # build only the executable
```
{% endtab %}
{% endtabs %}

## Contributing to the NiDB Project

### Setting up a development server

A development server can be a full server, a VM, or any installation of one of the supported Linux operating systems. Once you've been granted access to the nidb project on github, you'll need to add your SSH key under your account (github.com --> click your username --> Settings --> SSH and GPG keys). There are directions on the github site for how to do this. Then you can clone the current source code into your .

### Cloning a new repository with SSH

```bash
cd ~
git clone git@github.com:gbook/nidb.git nidb
```

This will create a git repository called nidb in your home directory.

### Committing changes

```bash
cd ~/nidb

# copy IN any webpage changes. Be careful not to overwrite uncommitted edits
cp -uv /var/www/html/*.php ~/nidb/src/web/
git commit -am "Comments about the changes"
git push origin master
```

### Updating your repository

To keep your local copy of the repository up to date, you'll need to pull any changes from github.

```bash
cd ~/nidb
git pull origin master

# copy OUT any webpage changes. Be careful not to overwrite uncommitted edits
cp -uv ~/nidb/src/web/*.php /var/www/html/
```

## Troubleshooting

#### Build freezes

This may happen if the build machine does not have enough RAM or processors. More likely, this is happening inside of a VM if the VM does not have enough RAM or processors allocated.

#### Build fails with "QMAKE\_CXX.COMPILER\_MACROS not defined"

This error happens because of a kernel bug in Rocky Linux 8.6 and any qmake built with Qt 6.3. Downgrade or use a lower version kernel until this kernel bug is fixed.

#### Library error when running nidb executable

If you get an error similar to the following, you'll need to install the missing library

```bash
./nidb: error while loading shared libraries: libsquirrel.so.1: cannot open shared object file: No such file or directory./nidb: error while loading shared libraries: libsquirrel.so.1: cannot open shared object file: No such file or directory
```

You can check which libraries are missing by running `ldd` on the `nidb` executable

```bash
[nidb@ado2dev bin]$ ldd nidb
        linux-vdso.so.1 (0x00007ffd07fe4000)
        libSMTPEmail.so.1 => /lib/libSMTPEmail.so.1 (0x00007fdb4e2b0000)
        libsquirrel.so.1 => not found
        libgdcmMSFF.so.3.0 => /lib/libgdcmMSFF.so.3.0 (0x00007fdb4dd88000)
        libgdcmCommon.so.3.0 => /lib/libgdcmCommon.so.3.0 (0x00007fdb4db60000)
        libgdcmDICT.so.3.0 => /lib/libgdcmDICT.so.3.0 (0x00007fdb4d688000)
        libgdcmDSED.so.3.0 => /lib/libgdcmDSED.so.3.0 (0x00007fdb4d348000)
```

Copy the missing library file(s) to `/lib` as root. Then run `ldconfig` to register any new libraries.

#### Virtual Machine Has No Network

If you are using a virtual machine to build NiDB, there are a couple of weird bugs in VMWare Workstation Player (possibly other VMWare products as well) where the network adapters on a Linux guest simply stop working. You can't activate them, you can't do anything with them, they just are offline and can't be activated. Or it's connected and network connection is present, but your VM is inaccessible from the outside.

Try these two fixes to get the network back:

1\) While the VM is running, suspend the guest OS. Wait for it to suspend and close itself. Then resume the guest OS. No idea why, but this should fix the lack of network adapter in Linux

2\) (This is if you are using bridged networking only) Open the VM settings. Go to network, and click the button to edit the bridged adapters. Uncheck the VM adapter.
