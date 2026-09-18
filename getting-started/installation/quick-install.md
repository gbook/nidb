# Quick Install

This is a summary of the [Installation](./) page. See that page for full details.

#### Prerequisites

1. **Hardware** - There are no minimum specifications. Hardware must be able to run 64-bit (x86\_64) Linux.
2. **Operating system** - RHEL 8, 9, or 10 compatible (RHEL, Rocky Linux, AlmaLinux), or Debian 12 or 13. NiDB does not run on Fedora or CentOS Stream. Avoid RHEL/Rocky 8.6, which contains a kernel bug (see the Installation page).
3. **FSL** - Install FSL by following the instructions at [https://fsl.fmrib.ox.ac.uk/fsl/docs/#/install/linux](https://fsl.fmrib.ox.ac.uk/fsl/docs/#/install/linux). After installation, note the location of FSL, usually `/usr/local/fsl`.
4. **firejail** - firejail is used to run user-defined scripts in a sandboxed environment. On RHEL compatible systems, install firejail from https://firejail.wordpress.com/ . On Debian, run `sudo apt install firejail`.
5. **EPEL** (RHEL compatible only) - Enable the EPEL repository, which provides some of the required packages. On Rocky Linux or AlmaLinux run `sudo dnf install epel-release`. See the Installation page for RHEL.

#### Install NiDB

1. Download the latest package for your OS from [https://github.com/gbook/nidb/releases](https://github.com/gbook/nidb/releases)
2. Install the package
   * RHEL compatible - `sudo dnf --nogpgcheck install ./nidb-xxxx.xx.xx-1.elN.x86_64.rpm` (where `elN` is `el8`, `el9`, or `el10`), then reboot so SELinux is disabled
   * Debian - `sudo apt install ./nidb_xxxx.xx.xx.deb`
3. Secure the MariaDB installation by running `sudo mariadb-secure-installation` (`mysql_secure_installation` on older MariaDB versions) and using the following responses. The current root password is `password`.

```bash
    Enter current password for root (enter for none): password
    Change the root password? [Y/n] n
    Remove anonymous users? [Y/n] Y
    Disallow root login remotely? [Y/n] Y
    Remove test database and access to it? [Y/n] Y
    Reload privilege tables now? [Y/n] Y
```

4. **Finish Setup** - Use a web browser to view http://localhost/setup.php (or http://servername/setup.php). Follow the instructions on the page to configure the server.
   * For a new installation, the setup page can be accessed from any computer.
   * For an upgrade, the setup page can only be accessed from localhost, or from IP addresses listed in the `[setupips]` config variable in `/nidb/nidb.cfg`. It should look something like `[setupips] = 127.0.0.1, 192.168.0.1` depending on the IP(s)
5. Log in to NiDB as `admin` with the password `password`, and change the default passwords.
