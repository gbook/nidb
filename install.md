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


# Installing NiDB

## New Installation

### Pre-requisities

**Hardware** - There are no minimum specifications. If the hardware can run Linux, then it should be able to run NiDB.

**RHEL8/Rocky8/CentOS 8** - NiDB runs only on RHEL 8 compatible OSes. NiDB does not run on Fedora or CentOS Stream.

**FSL** - Download FSL from https://fsl.fmrib.ox.ac.uk/fsl/fslwiki/FslInstallation and follow the installation instructions. After installation, note the location of FSL, usually `/usr/local/fsl`.

Alternatively, try these commands to install FSL.
```
> wget https://fsl.fmrib.ox.ac.uk/fsldownloads/fslinstaller.py # this may work
> sudo yum install python2
> sudo python2 fslinstaller.py
```

**firejail** - firejail is used to run user-defined scripts in a sandboxed environment. This may be deprecated in future releases of NiDB. Install firejail from https://firejail.wordpress.com/
```
> sudo rpm -i firejail-x.y.z.rpm
```

### Install NiDB rpm
Download the latest .rpm package from http://github.com/gbook/nidb
```
> sudo yum install epel-release
> sudo yum --nogpgcheck localinstall nidb-xxxx.xx.xx-1.el8.x86_64.rpm
```

Secure the MariaDB installation by running mysql_secure_installation and using the following responses. The MariaDB root password is already set to `password`.
```
> sudo mysql_secure_installation
  
  Enter current password for root (enter for none): password
  Change the root password? [Y/n] n
  Remove anonymous users? [Y/n] Y
  Disallow root login remotely? [Y/n] Y
  Remove test database and access to it? [Y/n] Y
  Reload privilege tables now? [Y/n] Y
```
### Finish Setup
Use firefox to view http://localhost/setup.php . Follow instructions on the page to configure the server
  * The setup page must be acessed from localhost -or- the config file must be manually edited to include the IP address of the computer you are using the access setup.php.
  * Edit `/nidb/nidb.cfg` and add your IP address (comma separated list) to the `[setupips]` config variable. It should look something like `[setupips] 127.0.0.1, 192.168.0.1` depending on the IP(s)
  * If you encounter this error when viewing the setup page, follow the directions above
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/162640328-0c29622f-ef1a-4ad3-a20d-cc3d0d3e35b1.png" width="60%"></div>

Setup has 4 parts: Backup, System check, Satabase, NiDB settings. The first screen will look like the following
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/162640572-c1d6ff3f-20d9-4caa-9a95-8602a220c91e.png" width="60%"></div>
Copy the backup command and run that on the command line. It should generate a file that contains a backup of the database. This is required even for new installations because you should become familiar with, and get int the habit of, backing up the SQL database. After you've backed up the database using mysqldump, refresh the setup page and it should allow you to continue with the setup.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/162640676-6ea51f70-8fa5-4de3-ae0e-378f7a975c5f.png" width="60%"></div>
Click Next to continue.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/162640726-9654b0dd-36bb-4eee-b103-a9e5c4224399.png" width="60%"></div>
If there are any missing packages or if a version needs to be updated, it will show here. Install the package and refresh the page. Click Next to contine.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/162640778-a5cf1971-7030-44d6-9381-508aa021b76e.png" width="60%"></div>
This page will show the SQL schema upgrade information. Enter the MariaDB root password, which should be 'password' if this is the first installation. The next page will upgrade the database schema, using the .sql file listed at the bottom. As NiDB collects more data, the tables can get very large and tables over 100 million rows are possible. This will take cause the setup webpage to time out, so there is an option to skip tables that have more than x rows. This should really only be done if a specific table is preventing an upgrade and you are familiar with how to manually update the schema. There's also an option to test the upgrade without actually changing the table structure. Click Next to continue.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/162641016-ce2bde85-f818-472d-b48a-e66329ca9cba.png" width="60%"></div>
If any errors occur during upgrade, they will be displayed at the bottom of the page. You can attempt to fix these, or preferably seek help on the NiDB github support page! Click the red box to dismiss it.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/162641071-6d7c71da-c4ad-4d9f-9265-a7d075d59521.png" width="60%"></div>
The next page shows the configuration variables. Here you can edit variables, paths, name of the instance, email configuration, enable features.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/162641160-ce57d223-941f-43ba-8c35-d08837998d49.png" width="60%"></div>
Click Write Config to continue.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/162641179-b36025a1-4923-42a3-a83c-d77f90f00180.png" width="60%"></div>
The locations of the written config file(s) are noted on this page. Setup should now be complete and you can visit the home page.

### Optional Software
**phpMyAdmin** is not required to use NiDB, but is extremely useful to maintain the SQL database that NiDB relies on.
1. Download latest version of phpMyAdmin from http://phpmyadmin.net/
2. Unzip the contents of .zip file into `/var/www/html/phpMyAdmin`
3. Visit http://localhost/phpMyAdmin

### Changing Passwords
The default usernames and passwords are as follows, change them using the method listed. Changed MariaDB passwords must also be updated in the config file (Edit `/nidb/nidb.cfg` or use **Admin** --> **Settings**)

|Username|Default password|How to change password|
|---:|---|---|
|(Linux)	`nidb`|`password`|(as root) `passwd nidb`<br>(as nidb) `passwd`|
|(MariaDB)	`root`|`password`|Login to http://localhost/phpMyAdmin using the root MySQL account and password. Go to the **User Accounts** menu option. Then click **Edit privileges** for the root account that has a `‘%’` as the hostname. Then click **Change password** button at the top of the page. Enter a new password and click **Go**|
|(MariaDB)	`nidb`|`password`|See above|
|(NiDB) `admin`|`password`|When logged in as `admin`, go to **My Account**. Enter a new password in the password field(s). Click **Save** to change the password.|

<hr>

## Upgrade Existing Installation
Quick upgrade instructions below. See <a href="upgrade.html">detailed upgrade</a> instructions for a more in-depth explanation of the upgrade.
1. Download latest NiDB release.
2. `yum --nogpgcheck localinstall nidb-xxxx.xx.xx-1.el8.x86_64.rpm`
3. Make sure your IP address is set in the `[setupips]` variable in the config file. This can be done manually by editing `/nidb/nidb.cfg` or by going to **Admin** &#8594; **Settings**
4. Go to http://localhost/setup.php (Or within NiDB, go to **Admin** &#8594; **Setup/upgrade**)
5. Follow the instructions on the webpages to complete the upgrade

<hr>

## Migrate Existing Installation to New Server
1. On the *old server*, export the SQL database
    * `mysqldump -uroot -ppassword nidb > nidb-backup.sql`
2. Copy the exported .sql file to the *new server*.
3. On the *new server*, install NiDB as a new installation
4. On the *new server*, import the new database
     * `mysql -uroot -ppassword nidb < nidb-backup.sql`
5. Finish upgrade, by going to http://localhost/setup.php . Follow the instructions to continue the upgrade.
