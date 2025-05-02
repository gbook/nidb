---
description: Detailed installation instructions
---

# Installation

## Prerequisites

**Hardware** - There are no minimum specifications. Hardware must be able to run Linux.

**Operating system** - NiDB runs RHEL 8, RHEL 9 compatible OSes. NiDB does not run on Fedora or CentOS Stream.

{% hint style="danger" %}
NiDB will not run correctly on Fedora, CentOS Stream 8, or RHEL/Rocky 8.6 as they contain a kernel bug. If you have already updated to this version, you can downgrade the kernel or boot into the previous kernel. Kernel `4.18.0-348.12.2.el8_5.x86_64` is known to work correctly.
{% endhint %}

#### FSL

FSL is required for MRI QC modules. FSL requires at least 20GB free disk space to install. Download FSL from [https://fsl.fmrib.ox.ac.uk/fsl/fslwiki/FslInstallation](https://fsl.fmrib.ox.ac.uk/fsl/fslwiki/FslInstallation) and follow the installation instructions. After installation, note the location of FSL, usually `/usr/local/fsl`.

Alternatively, try these commands to install FSL

```bash
wget https://fsl.fmrib.ox.ac.uk/fsldownloads/fslinstaller.py # this may work
sudo yum install python2
sudo python2 fslinstaller.py
```

#### firejail

firejail is used to run user-defined scripts in a sandboxed environment. This may be deprecated in future releases of NiDB. Install firejail from https://firejail.wordpress.com/

```bash
sudo rpm -i firejail-x.y.z.rpm
```

## Install NiDB rpm

Download the latest .rpm package from [https://github.com/gbook/nidb/release](https://github.com/gbook/nidb/releases) and run the following commands

{% tabs %}
{% tab title="RHEL 9" %}
Run the following commands

{% code fullWidth="false" %}
```bash
# add repository to install OpenSSL 1.0
sudo curl -JLo /etc/yum.repos.d/mroche-vfx-compatibility.repo "https://copr.fedorainfracloud.org/coprs/mroche/vfx-compatibility/repo/epel-9/mroche-vfx-compatibility-epel-9.repo"

sudo dnf install epel-release # for ImageMagick
sudo dnf install mysql-libs --enablerepo=devel # for libmysql2.1
sudo yum --nogpgcheck localinstall nidb-xxxx.xx.xx-1.el9.x86_64.rpm

reboot # you must reboot to ensure SELinux is disabled before continuing
```
{% endcode %}
{% endtab %}

{% tab title="RHEL 8" %}
```bash
sudo dnf install epel-release # for ImageMagick
sudo yum --nogpgcheck localinstall nidb-xxxx.xx.xx-1.el8.x86_64.rpm
```
{% endtab %}
{% endtabs %}

Secure the MariaDB installation by running `mysql_secure_installation` as root and using the following responses. The MariaDB root password is already set to `password`.

```
> sudo mysql_secure_installation
  
  Enter current password for root (enter for none): password
  Change the root password? [Y/n] n
  Remove anonymous users? [Y/n] Y
  Disallow root login remotely? [Y/n] Y
  Remove test database and access to it? [Y/n] Y
  Reload privilege tables now? [Y/n] Y
```

## Finish Setup

Use Firefox to view [http://localhost/setup.php](http://localhost/setup.php) (or http://servername/setup.php). Follow the instructions on the webpage to configure the server.

{% hint style="info" %}
**If you encounter an error when viewing the Setup page...**

* The setup page must be accessed from localhost.
* Or the config file must be manually edited to include the IP address of the computer you are using the access setup.php. Add your IP address by editing `/nidb/nidb.cfg` and add your IP address (comma separated list) to the `[setupips]` config variable. It should look something like `[setupips] 127.0.0.1, 192.168.0.1` depending on the IP(s)
{% endhint %}

### **1 - Backup SQL database**

![Beginning the website based setup process. The backup file must exist before setup can continue.](https://user-images.githubusercontent.com/8302215/162640572-c1d6ff3f-20d9-4caa-9a95-8602a220c91e.png)

Copy the `mysqldump` command and run that on the command line. It should create a `.sql` file that contains a backup of the database. This is required even for new installations because you should become familiar with, and get int the habit of, backing up the SQL database. After you've backed up the database using `mysqldump`, refresh the setup page and it should allow you to continue with the setup.

![](https://user-images.githubusercontent.com/8302215/162640676-6ea51f70-8fa5-4de3-ae0e-378f7a975c5f.png)

Click **Next** to continue, and the following page will show the status of Linux packages required by NiDB.

### 2 - Linux Prerequisites

![](https://user-images.githubusercontent.com/8302215/162640726-9654b0dd-36bb-4eee-b103-a9e5c4224399.png)

If there are any missing packages or if a version needs to be updated, it will show here. Install the package and refresh the page. Click **Next** to continue, and the following page will show the SQL schema upgrade information.

### 3 - Database connection

![](https://user-images.githubusercontent.com/8302215/162640778-a5cf1971-7030-44d6-9381-508aa021b76e.png)

Enter the MariaDB root password, which should be `password` if this is the first installation. The SQL schema will be upgraded using the .sql file listed at the bottom. As your instance of NiDB collects more data, the tables can get very large and tables over 100 million rows are possible. This will cause the setup webpage to time out, so there is an option to skip tables that have more than x rows. This should really only be done if a specific table is preventing the schema upgrade because it so large and you are familiar with how to manually update the schema. The debug option is available to test the upgrade without actually changing the table structure. Click **Next** to continue, and the following page will perform the actual schema upgrade.

### 4 - Schema upgrade

![](https://user-images.githubusercontent.com/8302215/162641016-ce2bde85-f818-472d-b48a-e66329ca9cba.png)

If any errors occur during upgrade, they will be displayed at the bottom of the page. You can attempt to fix these, or preferably seek help on the NiDB github support page! Click the red box to dismiss any error messages. Click **Next** to go to the next page which will show the configuration variables.

![](https://user-images.githubusercontent.com/8302215/162641071-6d7c71da-c4ad-4d9f-9265-a7d075d59521.png)

### 5 - Config settings

On this page you can edit variables, paths, name of the instance, email configuration, enable features.

![](https://user-images.githubusercontent.com/8302215/162641160-ce57d223-941f-43ba-8c35-d08837998d49.png)

Click **Write Config** to continue.

![](https://user-images.githubusercontent.com/8302215/162641179-b36025a1-4923-42a3-a83c-d77f90f00180.png)

The locations of the written config file(s) are noted on this page. `nidb-cluster.cfg` is meant to be placed on cluster nodes, to allow nidb pipelines running on the cluster to communicate with the main nidb instance and perform check-ins and storing of pipeline results.

Setup should now be complete and you can visit the home page.

***
