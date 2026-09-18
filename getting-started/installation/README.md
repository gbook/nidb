---
description: Detailed installation instructions
---

# Installation

## Prerequisites

**Hardware** - There are no minimum specifications. Hardware must be able to run 64-bit (x86\_64) Linux.

**Operating system** - NiDB is packaged for RHEL 8, RHEL 9, and RHEL 10 compatible OSes (RHEL, Rocky Linux, AlmaLinux), and for Debian 12 and Debian 13. NiDB does not run on Fedora or CentOS Stream.

{% hint style="danger" %}
NiDB will not run correctly on Fedora, CentOS Stream 8, or RHEL/Rocky 8.6 as they contain a kernel bug. If you have already updated to this version, you can downgrade the kernel or boot into the previous kernel. Kernel `4.18.0-348.12.2.el8_5.x86_64` is known to work correctly.
{% endhint %}

#### FSL

FSL is required for the MRI QC modules. FSL requires at least 20GB free disk space to install. Follow the installation instructions at [https://fsl.fmrib.ox.ac.uk/fsl/docs/#/install/linux](https://fsl.fmrib.ox.ac.uk/fsl/docs/#/install/linux), which download and run `fslinstaller.py` with Python 3. After installation, note the location of FSL, usually `/usr/local/fsl`. You will enter this location in the `fsldir` setting during the setup below.

#### firejail

firejail is used to run user-defined scripts (such as mini-pipelines) in a sandboxed environment. This may be deprecated in future releases of NiDB.

* **RHEL compatible** - Install firejail from [https://firejail.wordpress.com/](https://firejail.wordpress.com/), for example `sudo dnf install ./firejail-x.y.z.rpm`
* **Debian** - `sudo apt install firejail`

## Install the NiDB package

Download the latest package for your OS from [https://github.com/gbook/nidb/releases](https://github.com/gbook/nidb/releases). The package installs all other required software (Apache, MariaDB, PHP, ImageMagick, etc.) from your OS repositories. On RHEL compatible systems some of these packages come from the EPEL repository, so EPEL must be enabled first.

{% tabs %}
{% tab title="RHEL 9 / Rocky 9 / AlmaLinux 9" %}
```bash
# enable EPEL - Rocky Linux or AlmaLinux
sudo dnf config-manager --set-enabled crb
sudo dnf install epel-release

# enable EPEL - RHEL
sudo subscription-manager repos --enable codeready-builder-for-rhel-9-$(arch)-rpms
sudo dnf install https://dl.fedoraproject.org/pub/epel/epel-release-latest-9.noarch.rpm

# install NiDB
sudo dnf --nogpgcheck install ./nidb-xxxx.xx.xx-1.el9.x86_64.rpm

sudo reboot # you must reboot to ensure SELinux is disabled before continuing
```
{% endtab %}

{% tab title="RHEL 10 / Rocky 10 / AlmaLinux 10" %}
```bash
# enable EPEL - Rocky Linux or AlmaLinux
sudo dnf config-manager --set-enabled crb
sudo dnf install epel-release

# enable EPEL - RHEL
sudo subscription-manager repos --enable codeready-builder-for-rhel-10-$(arch)-rpms
sudo dnf install https://dl.fedoraproject.org/pub/epel/epel-release-latest-10.noarch.rpm

# install NiDB
sudo dnf --nogpgcheck install ./nidb-xxxx.xx.xx-1.el10.x86_64.rpm

sudo reboot # you must reboot to ensure SELinux is disabled before continuing
```
{% endtab %}

{% tab title="RHEL 8 / Rocky 8 / AlmaLinux 8" %}
```bash
# enable EPEL - Rocky Linux or AlmaLinux
sudo dnf config-manager --set-enabled powertools
sudo dnf install epel-release

# enable EPEL - RHEL
sudo subscription-manager repos --enable codeready-builder-for-rhel-8-$(arch)-rpms
sudo dnf install https://dl.fedoraproject.org/pub/epel/epel-release-latest-8.noarch.rpm

# install NiDB
sudo dnf --nogpgcheck install ./nidb-xxxx.xx.xx-1.el8.x86_64.rpm

sudo reboot # you must reboot to ensure SELinux is disabled before continuing
```
{% endtab %}

{% tab title="Debian 12 / Debian 13" %}
```bash
# install NiDB (apt installs the required packages)
sudo apt install ./nidb_xxxx.xx.xx.deb
```

Debian does not use SELinux, so no reboot is needed.
{% endtab %}
{% endtabs %}

### What the package configures

The package's post-install script configures the server for NiDB. You don't need to do these steps yourself, but you should know about them.

* Creates the `nidb` Linux user (password `password`) and the `/nidb/data` directories
* Sets the MariaDB `root` password to `password`, and creates the MariaDB `nidb` account (password `password`)
* Disables SELinux (RHEL compatible only)
* Changes `php.ini` limits (upload size, memory, execution time) and runs php-fpm as the `nidb` user
* Enables the MariaDB event scheduler, and enables and starts the httpd/apache, MariaDB, and php-fpm services
* Opens firewall ports 80 (web), and 104 and 8104 (DICOM receiver). This uses firewalld on RHEL compatible systems, and ufw (if active) on Debian
* Installs and starts the `dcmrcv` DICOM receiver service. If your DICOM incoming directory is not the default, edit `/etc/systemd/system/dcmrcv.service` to use the correct path
* Installs the `nidb` user's crontab, which runs the NiDB modules

The default usernames and passwords are listed in [How to change passwords](how-to-change-passwords.md). Change them after setup is complete.

### Secure MariaDB

Secure the MariaDB installation by running `sudo mariadb-secure-installation` (on older MariaDB versions, such as on RHEL 8, the command is `mysql_secure_installation`) and use the following responses. The MariaDB root password is already set to `password`.

```
> sudo mariadb-secure-installation

  Enter current password for root (enter for none): password
  Change the root password? [Y/n] n
  Remove anonymous users? [Y/n] Y
  Disallow root login remotely? [Y/n] Y
  Remove test database and access to it? [Y/n] Y
  Reload privilege tables now? [Y/n] Y
```

Newer MariaDB versions may also ask whether to switch to unix\_socket authentication. Answer `n`, because the setup page needs to log in to MariaDB as root with a password.

## Finish Setup

Use a web browser to view [http://localhost/setup.php](http://localhost/setup.php) (or http://servername/setup.php). Follow the instructions on the webpage to configure the server.

{% hint style="info" %}
**Who can access the Setup page**

* For a **new installation**, the setup page can be accessed from any computer.
* For an **upgrade**, the setup page can only be accessed from localhost, or from IP addresses listed in the config file. To add your IP address, edit `/nidb/nidb.cfg` and add your IP address (comma separated list) to the `[setupips]` config variable. It should look something like `[setupips] = 127.0.0.1, 192.168.0.1` depending on the IP(s)
{% endhint %}

### 1 - Welcome

For a **new installation**, the page will say _This is a new installation_. Click **Next** to continue.

For an **upgrade**, do the following before continuing.

* **Disable access to NiDB during the upgrade** by setting `[offline] = 1` in `/nidb/nidb.cfg`. Change it back to `0` after the upgrade is complete.
* **Backup the SQL database.** Setup will not continue until the backup file exists. Copy the `mysqldump` command shown on the page and run it on the command line, replacing `PASSWORD` with the MariaDB `nidb` password. It will create a `.sql` file containing a backup of the database. After the backup is created, refresh the setup page and it will allow you to continue.

<img src="https://user-images.githubusercontent.com/8302215/162640572-c1d6ff3f-20d9-4caa-9a95-8602a220c91e.png" alt="Beginning the website based setup process. The backup file must exist before an upgrade can continue." width="563">

&#x20;

<img src="https://user-images.githubusercontent.com/8302215/162640676-6ea51f70-8fa5-4de3-ae0e-378f7a975c5f.png" alt="" width="563">

Click **Next** to continue, and the following page will show information about the server.

### 2 - Linux Prerequisites

<img src="https://user-images.githubusercontent.com/8302215/162640726-9654b0dd-36bb-4eee-b103-a9e5c4224399.png" alt="" width="563">

This page shows the OS, number of CPU cores, and memory, and the versions of httpd, MariaDB (10.0 or newer is required), PHP (7.0 or newer is required), and ImageMagick. It also shows whether a new installation will be configured or an existing installation will be upgraded. If a version is incorrect, it will be shown in red. Install or update the package and refresh the page. Click **Next** to continue, and the following page will show the SQL schema upgrade information.

### 3 - Database connection

<img src="https://user-images.githubusercontent.com/8302215/162640778-a5cf1971-7030-44d6-9381-508aa021b76e.png" alt="" width="563">

Enter the MariaDB root password, which should be `password` if this is the first installation. The SQL schema will be upgraded using the .sql file listed at the bottom. As your instance of NiDB collects more data, the tables can get very large and tables over 100 million rows are possible. This will cause the setup webpage to time out, so there is an option to skip tables that have more than x rows. This should really only be done if a specific table is preventing the schema upgrade because it so large and you are familiar with how to manually update the schema. The debug option is available to test the upgrade without actually changing the table structure. Click **Next** to continue, and the following page will perform the actual schema upgrade.

### 4 - Schema upgrade

<img src="https://user-images.githubusercontent.com/8302215/162641016-ce2bde85-f818-472d-b48a-e66329ca9cba.png" alt="" width="563">

If any errors occur during upgrade, they will be displayed at the bottom of the page. You can attempt to fix these, or preferably seek help on the NiDB github support page! Click the red box to dismiss any error messages. Click **Next** to go to the next page which will show the configuration variables.

<img src="https://user-images.githubusercontent.com/8302215/162641071-6d7c71da-c4ad-4d9f-9265-a7d075d59521.png" alt="" width="563">

### 5 - Config settings

On this page you can edit variables, paths, name of the instance, email configuration, and enable features. Enter the location of FSL in the `fsldir` setting.

<img src="https://user-images.githubusercontent.com/8302215/162641160-ce57d223-941f-43ba-8c35-d08837998d49.png" alt="" width="563">

Click **Write Config** to continue. The config is written to `/nidb/nidb.cfg`, and a cluster config file `nidb-cluster.cfg` is written to the same directory. `nidb-cluster.cfg` is meant to be placed on cluster nodes, to allow NiDB pipelines running on the cluster to communicate with the main NiDB instance and perform check-ins and storing of pipeline results.

### 6 - Setup complete

Click **Done** to go to the NiDB home page. Log in as `admin` with the password `password`, then use the **Admin** menu to administer this instance of NiDB. Change the default passwords as described in [How to change passwords](how-to-change-passwords.md).

***
