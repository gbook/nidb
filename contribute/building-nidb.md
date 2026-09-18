---
description: How to build NiDB and contribute to its development
---

# Building NiDB

## Compatible Linux Distributions

The following OS configurations have been tested to build nidb. It may be possible to build NiDB on other OS configurations, but only the below environments have been tested.

* <mark style="color:green;">**Tested & Compatible**</mark>
  * RHEL 10 compatible (Rocky Linux 10, AlmaLinux 10, RHEL 10)
  * RHEL 9 compatible (Rocky Linux 9, AlmaLinux 9, RHEL 9)
  * RHEL 8 compatible (Rocky Linux 8, AlmaLinux 8, CentOS 8, RHEL 8)
  * Debian 12
  * Debian 13
* <mark style="color:red;">**Incompatible**</mark>
  * RHEL-compatible 8.6 (RHEL 8.6, Rocky 8.6, AlmaLinux 8.6)
  * CentOS 8 Stream
* **Unknown**
  * Ubuntu - Previously built on Ubuntu 20, but Ubuntu is no longer tested and there is no Ubuntu package script.
  * Windows 10/11 - NiDB will compile and build on Windows, but NiDB uses Linux system calls to perform many background operations, and thus would not work on Windows.

{% hint style="danger" %}
**NiDB cannot be built on CentOS Stream 8 or Rocky Linux 8.6.** These distros contain kernel bugs which are incompatible with the QProcess library.
{% endhint %}

## Prepare Build Environment

### Step 1 - Install development tools

Run these commands as root (or sudo) based on your distribution

{% tabs %}
{% tab title="RHEL 10" %}
```bash
dnf group install 'Development Tools'
dnf install cmake3 wget unzip
dnf install rpmdevtools
dnf install xcb-util-wm xcb-util-cursor xcb-util-keysyms
dnf install libxkbcommon-x11 libxcb-devel
dnf install libX11-xcb
dnf install git
dnf install mesa-libGL-devel
```
{% endtab %}

{% tab title="RHEL 9" %}
```bash
dnf group install 'Development Tools'
dnf install cmake3 wget unzip
dnf install rpmdevtools
dnf install xcb-util-wm xcb-util-cursor xcb-util-keysyms
dnf install libxkbcommon-x11
dnf install git
dnf install mesa-libGL-devel
```
{% endtab %}

{% tab title="RHEL 8" %}
```bash
dnf group install 'Development Tools'
dnf install cmake3 wget unzip
dnf install rpmdevtools
dnf install xcb-util-wm xcb-util-keysyms
dnf install libxkbcommon-x11
dnf install gcc-toolset-10
dnf install libxcb-devel
dnf install libX11-xcb
dnf install xcb-util*
dnf install mesa-libGL-devel
```

The build scripts automatically enable `gcc-toolset-10` on RHEL 8.
{% endtab %}

{% tab title="Debian 12 / 13" %}
```bash
apt install build-essential make cmake git
apt install libxcb* libxkb* libX11-xcb*
apt install libdbus-1*
apt install libzstd-dev
apt install libglib2.0-dev
apt install wget unzip   # if needed
```
{% endtab %}
{% endtabs %}

### Step 2 - Build DCMTK

{% hint style="warning" %}
Use **DCMTK 3.7.0**. The package build scripts expect the DCMTK 3.7.0 libraries (`libdcm*.so.20.3.7.0`) and data dictionaries (`/usr/local/share/dcmtk-3.7.0`). A different version of DCMTK will cause the package build to fail.
{% endhint %}

{% tabs %}
{% tab title="Linux" %}
1. Install CMake
   1. RHEL `sudo dnf install cmake cmake-gui`
   2. Debian `sudo apt install cmake cmake-gui`
2. Download the DCMTK 3.7.0 source code from [https://github.com/DCMTK/dcmtk/releases](https://github.com/DCMTK/dcmtk/releases)
3. Unzip the source code to a directory such as `~/dcmtk-source`. Make sure the source code exists at the root of that directory and is not unzipped into a sub-directory.
4. Open CMake (GUI)
   1. Set source code directory to to `~/dcmtk-source`
   2. Set binary directory to `~/dcmtk-bin`
   3. Click **Configure**.
      1. Click **Yes** to create the directory
      2. Set the generator to _Unix Makefiles_ and _Use default native compilers_. Click **Finish**.
   4. Make sure
      1. `BUILD_APPS` is unchecked
      2. `BUILD_SHARED_LIBS` is checked
      3. `DCMTK_ENABLE_PRIVATE_TAGS` is checked
   5. Leave all other options the same
   6. Click **Configure**.
   7. The variable list will refresh. If any lines are <mark style="color:red;">**red**</mark>, fix those lines and click **Configure** again.
   8. Click **Generate**.
   9. Close cmake-gui
5. From the command line, run the following
   1. `cd ~/dcmtk-bin`
   2. `make`
   3. `sudo make install`
6. DCMTK files will be installed in the following locations
   1. RHEL
      1. include - `/usr/local/include`
      2. libs - `/usr/local/lib64`
   2. Debian
      1. include - `/usr/local/include`
      2. libs - `/usr/local/lib`
   3. data dictionaries - `/usr/local/share/dcmtk-3.7.0`

#### Add /usr/local/lib64 to ldconfig (RHEL)

```bash
echo "/usr/local/lib64" | sudo tee /etc/ld.so.conf.d/local-lib64.conf
sudo ldconfig
```
{% endtab %}

{% tab title="Windows" %}
1. Install CMake - [https://cmake.org/download/](https://cmake.org/download/)
2. Download the DCMTK 3.7.0 source code from [https://github.com/DCMTK/dcmtk/releases](https://github.com/DCMTK/dcmtk/releases)
3. Unzip the source code to a directory such as `C:/dcmtk-source`. Make sure the source code exists at the root of that directory and is not unzipped into a sub-directory.
4. Open CMake (GUI)
   1. Set source code directory to to `C:/dcmtk-source`
   2. Set binary directory to `C:/dcmtk-bin`
   3. Make sure
      1. `BUILD_APPS` is unchecked
      2. `BUILD_SHARED_LIBS` is checked
      3. `DCMTK_ENABLE_PRIVATE_TAGS` is checked
   4. Leave all other options the same
   5. Click **Configure**. Set the generator to Visual Studio 17, 2022
   6. The variable list will refresh. If any lines are <mark style="color:red;">**red**</mark>, fix those lines and click **Configure** again.
   7. Click **Generate**.
5. Right-click **Visual Studio 2022** and select **Run as administrator**. Then open `C:/dcmtk-bin/DCMTK.sln`
   1. On the Solution explorer, right-click and select **Batch Build...**
   2. For the ALL\_BUILD and INSTALL rows check off the _Release_ option, and click **Build**.
   3. Building will take some time.
6. The NiDB project file expects DCMTK to be installed in `C:/Program Files (x86)/DCMTK`
{% endtab %}
{% endtabs %}

### Step 3 - Install Qt

1. Download Qt open-source from [https://www.qt.io/download-open-source](https://www.qt.io/download-open-source)
2. Make the installer executable `chmod 777 qt-unified-linux-x64-x.x.x-online.run`
3. Run `./qt-unified-linux-x64-x.x.x-online.run`
4. The Qt Maintenance Tool will start. An account is required to download Qt open source
5. On the components screen, select the checkbox for **Qt 6.9.3 → Desktop gcc 64-bit**

The build scripts expect Qt to be installed in `~/Qt/6.9.3/gcc_64`.

### Optional - Build MySQL/MariaDB driver for Qt

Sometimes the MySQL/MariaDB driver supplied with Qt will not work correctly, and needs to be built manually. This happens on Debian 12, for example. If building is successful, the path to the driver should eventually be `~/Qt/6.9.3/gcc_64/plugins/sqldrivers/libqsqlmysql.so`

1. In step 3 above (using the Qt Maintenance Tool), also select the checkbox for **Qt 6.9.3 → Sources**
2. Run the following commands

{% tabs %}
{% tab title="Debian 12 / 13" %}
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

# install in the system
sudo mkdir -p /usr/local/bin/sqldrivers
sudo cp -uv ~/Qt/6.9.3/gcc_64/plugins/sqldrivers/* /usr/local/bin/sqldrivers/
```
{% endtab %}
{% endtabs %}

## Building NiDB

Once the build environment is set up, the builds are done by script. All scripts are run from the root of the NiDB source directory.

| Script                                                                     | What it does                                                                                                                                                                                                                                                                                      |
| -------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `build.sh`                                                                 | Development build. Builds the bit7z library, the squirrel library, and the `nidb` and `nidbcluster` executables into `./bin`. It then uses `sudo` to copy `nidb` and `nidbcluster` to `/nidb/bin` and the squirrel library to `/lib64`. The copy steps are not required for the build to succeed. |
| `build-rpm.sh` (`build-rpm8.sh` on RHEL 8)                                 | Package build. Builds bit7z, the squirrel library, the `squirrel` command-line utility, and `nidb` into `./bin`. This is the script run by the RPM spec file.                                                                                                                                     |
| `makeInstallerRHEL8.sh`, `makeInstallerRHEL9.sh`, `makeInstallerRHEL10.sh` | Build the NiDB `.rpm` for a release, from the local source directory.                                                                                                                                                                                                                             |
| `makeInstallerDebian12.sh`, `makeInstallerDebian13.sh`                     | Build the NiDB `.deb` for a release, from the binaries already built in `./bin`.                                                                                                                                                                                                                  |

{% hint style="info" %}
The `makeInstallerRHELx.sh` scripts build the .rpm from the git repository they are run from. They export all files tracked by git, **including uncommitted changes**, into a fresh `~/rpmbuild` directory. Files not tracked by git (for example a local `phpMyAdmin` or `vendor` directory under `src/web`) are not included in the .rpm. The script prints the commit it is building from, and whether uncommitted changes are included.
{% endhint %}

**First time build** on this machine, clone the source code (see [Cloning a new repository with SSH](building-nidb.md#cloning-a-new-repository-with-ssh) below), or download it

```bash
cd ~
git clone https://github.com/gbook/nidb.git nidb
```

Then build, based on your distribution

{% tabs %}
{% tab title="RHEL 10" %}
```bash
cd ~/nidb
./build.sh                # development build of nidb and nidbcluster
./makeInstallerRHEL10.sh  # build the nidb .rpm
```
{% endtab %}

{% tab title="RHEL 9" %}
```bash
cd ~/nidb
./build.sh               # development build of nidb and nidbcluster
./makeInstallerRHEL9.sh  # build the nidb .rpm
```
{% endtab %}

{% tab title="RHEL 8" %}
```bash
cd ~/nidb
./build.sh               # development build of nidb and nidbcluster
./makeInstallerRHEL8.sh  # build the nidb .rpm
```
{% endtab %}

{% tab title="Debian 12 / 13" %}
```bash
cd ~/nidb
./build.sh                  # development build of nidb and nidbcluster

# build the nidb .deb
./build-rpm.sh              # builds nidb and the squirrel utility into ./bin
./makeInstallerDebian13.sh  # or ./makeInstallerDebian12.sh on Debian 12
```
{% endtab %}
{% endtabs %}

The .rpm will be created in `~/rpmbuild/RPMS/x86_64/`. The .deb will be created in the NiDB source directory.

## Contributing to the NiDB Project

### Setting up a development server

A development server can be a full server, a VM, or any installation of one of the supported Linux operating systems. Once you've been granted access to the nidb project on github, you'll need to add your SSH key under your account (github.com --> click your username --> Settings --> SSH and GPG keys). There are directions on the github site for how to do this. Then you can clone the current source code into your home directory.

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

If you get an error similar to the following, a shared library is missing or is not registered with `ldconfig`

```bash
./nidb: error while loading shared libraries: libdcmdata.so.20: cannot open shared object file: No such file or directory
```

You can check which libraries are missing by running `ldd` on the `nidb` executable, and looking for lines that say `not found`

```bash
ldd /nidb/bin/nidb | grep "not found"
```

The squirrel and bit7z libraries are built into the `nidb` executable, so the shared libraries that are usually missing are

* **DCMTK** (`libdcm*`, `libofstd`, `liboflog`, etc) - installed in `/usr/local/lib64` (RHEL) or `/usr/local/lib` (Debian). On RHEL, make sure `/usr/local/lib64` has been added to ldconfig as described in Step 2.
* **Qt** (`libQt6Core`, `libQt6Gui`, `libQt6Sql`, `libQt6Network`, `libQt6DBus`, `libicu*`) - located in `~/Qt/6.9.3/gcc_64/lib`

Copy the missing library file(s) to `/usr/lib` (RHEL) or `/usr/lib/x86_64-linux-gnu` (Debian) as root. Then run `sudo ldconfig` to register any new libraries.

If NiDB cannot connect to the database, the Qt MySQL driver `libqsqlmysql.so` may be missing. It should be in `/nidb/bin/sqldrivers`. See _Optional - Build MySQL/MariaDB driver for Qt_ above.

#### Virtual Machine Has No Network

If you are using a virtual machine to build NiDB, there are a couple of weird bugs in VMWare Workstation Player (possibly other VMWare products as well) where the network adapters on a Linux guest simply stop working. You can't activate them, you can't do anything with them, they just are offline and can't be activated. Or it's connected and network connection is present, but your VM is inaccessible from the outside.

Try these two fixes to get the network back:

1\) While the VM is running, suspend the guest OS. Wait for it to suspend and close itself. Then resume the guest OS. No idea why, but this should fix the lack of network adapter in Linux

2\) (This is if you are using bridged networking only) Open the VM settings. Go to network, and click the button to edit the bridged adapters. Uncheck the VM adapter.
