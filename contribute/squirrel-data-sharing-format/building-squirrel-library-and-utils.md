# Building squirrel library and utils

## Building squirrel library

The following OS configurations have been tested to build squirrel with Qt 6.4

* <mark style="color:green;">Compatible</mark>
  * Rocky Linux 8.5, 8.7 (not 8.6)
  * CentOS 8
  * CentOS 7
  * Windows 10/11

{% hint style="danger" %}
**squirrel library and utils cannot be built on CentOS Stream 8 or Rocky Linux 8.6.** There are kernel bugs which do not work correctly with Qt's QProcess library. This can lead to inconsistencies when running shell commands, and qmake build errors.
{% endhint %}

Other OS configurations may work to build squirrel, but have not been tested.

### Prepare Build Environment

Install development tools on **Rocky Linux 8.5** (Recommended)

```bash
yum group install 'Development Tools'
yum install cmake3
yum install xcb*
yum install libxcb*
yum install gcc-toolset-10
```

Install development tools on **CentOS 7**

```bash
yum install epel-release
yum group install 'Development Tools'
yum install cmake3
```

### Install Qt 6.3.2

1. Download Qt open-source from [https://www.qt.io/download-open-source](https://www.qt.io/download-open-source)
2. Make the installer executable `chmod 777 qt-unified-linux-x64-x.x.x-online.run`
3. Run `./qt-unified-linux-x64-x.x.x-online.run`
4. The Qt Maintenance Tool will start. An account is required to download Qt open source
5. On the components screen, select the checkbox for **Qt 6.3.2 → Desktop gcc 64-bit**

### Building squirrel

Once the build environment is setup, the build process can be performed by script. The `build.sh` script will build the squirrel library files and the squirrel utils.

#### Rocky Linux 8.5

If this is the first time building squirrel on this machine, perform the following

```bash
cd ~
wget https://github.com/gbook/squirrel/archive/main.zip
unzip main.zip
mv squirrel-main squirrel
cd squirrel
./build.sh
```

This will build gdcm (on which squirrel depends for reading DICOM headers), squirrel lib, and squirrel-gui.

All subsequent builds on this machine can be done with the following

```bash
cd ~/squirrel
./build.sh
```

### Contributing to the squirrel Library

#### Setting up a development environment

Once you've been granted access to the squirrel project on github, you'll need to add your server's SSH key to your github account (github.com --> click your username --> Settings --> SSH and GPG keys). There are directions on the github site for how to do this. Then you can clone the current source code into your server.

#### Cloning a new repository with SSH

```bash
cd ~
git clone git@github.com:gbook/squirrel.git squirrel
```

This will create a git repository called squirrel in your home directory.

#### Committing changes

```bash
cd ~/squirrel
git commit -am "Comments about the changes"
git push origin main
```

#### Updating your repository

To keep your local copy of the repository up to date, you'll need to pull any changes from github.

```bash
cd ~/squirrel
git pull origin main
```

### Troubleshooting

#### Build freezes

This may happen if the build machine does not have enough RAM or processors. More likely this is happening inside of a VM in which the VM does not have enough RAM or processors allocated.

#### Build fails with "QMAKE\_CXX.COMPILER\_MACROS not defined"

This error happens because of a kernel bug in RHEL 8.6. Downgrade to 8.5 or upgrade to 8.7.

#### Library error

This example is from the nidb example. If you get an error similar to the following, you'll need to install the missing library

```
./nidb: error while loading shared libraries: libsquirrel.so.1: cannot open shared object file: No such file or directory./nidb: error while loading shared libraries: libsquirrel.so.1: cannot open shared object file: No such file or directory
```

You can check which libraries are missing by running `ldd` on the `nidb` executable

```
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

If you are using a virtual machine to build, there are a couple of weird bugs in VMWare Workstation Player (possibly other VMWare products as well) where the network adapters on a Linux guest simply stop working. You can't activate them, you can't do anything with them, they just are offline and can't be activated. Or it's connected and network connection is present, but your VM is inaccessible from the outside.

Try these fixes to get the network back:

1. While the VM is running, suspend the guest OS. Wait for it to suspend and close itself. Then resume the guest OS. No idea why, but this should fix the lack of network adapter in Linux.
2. Open the VM settings. Go to network, and click the button to edit the bridged adapters. Uncheck the VM adapter. This is if you are using **bridged networking** only.
3. Switch to NAT networking. This may be better if you are connected to a public wifi.

## Using the squirrel Library

Copy the squirrel library files to the lib directory. The libs will then be available for the whole system.

```
cd ~/squirrel/bin/squirrel
sudo cp -uv libsquirrel* /lib/
```

## Building on Windows

### Prepare the build environment

* Install [**Visual Studio 2019 Community**](https://visualstudio.microsoft.com/vs/older-downloads/) edition, available from Microsoft. Install the C++ extensions.
* Install [**CMake3**](https://cmake.org/download/)****
* Install **Qt 6.4.2** for MSVC2019 x64
* Install [Github Desktop](https://desktop.github.com/), or TortoiseGit, or other Git interface

### Clone the repository

* Using Github Desktop, clone the squirrel repository to `C:\squirrel`
* Build GDCM
  * Open CMake
  * Set _source_ directory to `C:\squirrel\src\gdcm`
  * Set _build_ directory to `C:\squirrel\bin\gdcm`
  * Click **Configure** (click **Yes** to create the build directory)
  * Select _Visual Studio 16 2019_. Click **Finish**
  * After it's done generating, make sure `GDCM_BUILD_SHARED_LIBS` is checked
  * Click **Configure** again
  * Click **Generate**. This will create the Visual Studio solution and project files
  * Open the `C:\squirrel\bin\gdcm\GDCM.sln` file in Visual Studio
  * Change the build to **Release**
  * Right-click **ALL\_BUILD** and click **Build**
* Build squirrel library
  * Double-click `C:\squirrel\src\squirrel\squirrellib.pro`
  * Configure the project for Qt 6.4.2 as necessary
  * Switch the build to **Release** and build it
  * `squirrel.dll` and `squirrel.lib` will now be in `C:\squirrel\bin\squirrel`
* Build squirrel-gui
  * Configure the project for Qt 6.4.2 as necessary
  * Double-click `C:\squirrel\src\squirrel-gui\squirrel-gui.pro`
  * Switch the build to **Release** and build it
