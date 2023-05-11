---
description: How to build NiDB and contribute to its development
---

# Building NiDB

## Building NiDB

The following OS configurations have been tested to build nidb with Qt 6.3

* <mark style="color:green;">Compatible</mark>
  * Rocky Linux 9
  * Rocky Linux 8 (not 8.6)
  * CentOS 8
  * CentOS 7

Other OS configurations may work to build nidb, but only these environments have been tested.

{% hint style="danger" %}
**NiDB cannot be built on CentOS Stream 8 or Rocky Linux 8.6.** There are kernel bugs which do not work correctly with Qt's QProcess library. This can lead to inconsistencies when running shell commands through NiDB, and qmake build errors.
{% endhint %}

{% tabs %}
{% tab title="Rocky 9" %}

{% endtab %}

{% tab title="Rocky 8" %}

{% endtab %}

{% tab title="CentOS 7" %}

{% endtab %}
{% endtabs %}

### Prepare Build Environment

Install development tools on **Rocky 9**

```bash
yum group install 'Development Tools'
yum install cmake3
yum install rpmdevtools
yum install xcb*
yum install libxcb*
```

Install development tools on **Rocky 8**

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

### Install Qt 6.5

1. Download Qt open-source from https://www.qt.io/download-open-source
2. Make the installer executable `chmod 777 qt-unified-linux-x64-x.x.x-online.run`
3. Run `./qt-unified-linux-x64-x.x.x-online.run`
4. The Qt Maintenance Tool will start. An account is required to download Qt open source
5. On the components screen, select the checkbox for **Qt 6.5 → Desktop gcc 64-bit**

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
