# Quick Install

#### Prerequisites

1. **Hardware** - There are no minimum specifications. If the hardware can run RHEL 8, then it can run NiDB.
2. **RHEL 8 compatible** - NiDB runs only on RHEL8 compatible (CentOS 8, Rocky Linux 8, AlmaLinux 8).
3. **FSL** - Install FSL from https://fsl.fmrib.ox.ac.uk/fsl/fslwiki/FslInstallation After installation, note the location of FSL, usually `/usr/local/fsl/bin`. Or try these commands to install FSL.
   1. `wget https://fsl.fmrib.ox.ac.uk/fsldownloads/fslinstaller.py # this may work`
   2. `yum install python2`
   3. `python2 fslinstaller.py`
4. **firejail** - firejail is used to run user-defined scripts in a sandboxed environment. Install firejail from https://firejail.wordpress.com/
   * `rpm -i firejail-x.y.z.rpm`
5. **OS packages** - `yum install epel-release` for repo for ImageMagick

#### Install NiDB

1. Download the latest .rpm package
2. `yum --nogpgcheck localinstall nidb-xxxx.xx.xx-1.el8.x86_64.rpm`
3. Secure the MariaDB installation by running `sudo mysql_secure_installation` and using the following responses

```bash
    Enter current password for root (enter for none):
    Change the root password? [Y/n] n
    Remove anonymous users? [Y/n] Y
    Disallow root login remotely? [Y/n] Y
    Remove test database and access to it? [Y/n] Y
    Reload privilege tables now? [Y/n] Y
```

1. **Finish Setup** - Use Firefox to view http://localhost/setup.php . Follow instructions on the page to configure the server
   * The setup page must be accessed from localhost -or- the config file must be manually edited to include the IP address of the computer you are using the access setup.php.
   * Edit `/nidb/nidb.cfg` and add your IP address (comma separated list) to the `[setupips]` config variable. It should look something like `[setupips] 127.0.0.1, 192.168.0.1` depending on the IP(s)
