# NeuroInformatics Database
![Image](http://neuroinfodb.org/wp-content/uploads/2021/05/NIDB_logo-300x115.png)

Installation & Upgrade
- [Install](#new-installation)<br>
- [Quick Upgrade](#upgrade-existing-installation)<br>
- <a href="upgrade.html">Detailed Upgrade</a><br>

NiDB Usage
- <a href="administration.html">Administration</a><br>
- <a href="user-manual.html">Users Manual</a><br>

<br>

<hr>

## New Installation

### Pre-requisities

1. **Hardware** - There are no minimum specifications. If the hardware can run CentOS 8, then it should be able to run NiDB.
2. **CentOS 8** - NiDB runs only on CentOS8 (or CentOS 8 Stream).
3. **FSL** - Install FSL from https://fsl.fmrib.ox.ac.uk/fsl/fslwiki/FslInstallation After installation, note the location of FSL, usually `/usr/local/fsl/bin`. Or try these commands to install FSL.
    * `wget https://fsl.fmrib.ox.ac.uk/fsldownloads/fslinstaller.py # this may work`
    * `yum install python2`
    * `python2 fslinstaller.py`
4. **firejail** - firejail is used to run user-defined scripts in a sandboxed environment. Install firejail from https://firejail.wordpress.com/
    * `rpm -i firejail-x.y.z.rpm`
6. **OS packages** - `yum install epel-release` for repo for ImageMagick

### Install NiDB
1. Download the latest .rpm package
2. `yum --nogpgcheck localinstall nidb-xxxx.xx.xx-1.el8.x86_64.rpm`
3. Secure the MariaDB installation by running `sudo mysql_secure_installation` and using the following responses
```
    Enter current password for root (enter for none):
    Change the root password? [Y/n] n
    Remove anonymous users? [Y/n] Y
    Disallow root login remotely? [Y/n] Y
    Remove test database and access to it? [Y/n] Y
    Reload privilege tables now? [Y/n] Y
```
4. **Finish Setup** - Use firefox to view http://localhost/setup.php . Follow instructions on the page to configure the server
    * The setup page must be acessed from localhost -or- the config file must be manually edited to include the IP address of the computer you are using the access setup.php.
    * Edit `/nidb/nidb.cfg` and add your IP address (comma separated list) to the `[setupips]` config variable. It should look something like `[setupips] 127.0.0.1, 192.168.0.1` depending on the IP(s)

### Optional Software
phpMyAdmin is not required to use NiDB, but is extremely useful to maintain the SQL database that NiDB relies on.
1. Download latest version of phpMyAdmin from http://phpmyadmin.net/
2. Unzip the contents of .zip file into /var/www/html/phpMyAdmin
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