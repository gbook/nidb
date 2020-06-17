#!/bin/sh
# this script will download additional programs and features specific to nidb
# additional configuration steps are noted below

# if you change the default path, NiDB is not guaranteed to work correctly
NIDBROOT="/nidb"
WWWROOT="/var/www/html"
NIDBUSER="nidb"
MYSQLUSER="nidb"
MYSQLPASS="password"
MYSQLROOTPASS="password"

clear
echo 
echo 
echo 
echo "************************************************************"
echo 
echo "              " Neuroimaging Database Setup
echo 
echo "   Installing using the following variables:"
echo "       NIDBROOT: ${NIDBROOT}"
echo "       WWWROOT: ${WWWROOT}"
echo "       NIDBUSER: ${NIDBUSER}"
echo "       MYSQLUSER: ${MYSQLUSER}"
echo "       MYSQLPASS: ${MYSQLPASS}"
echo "       MYSQLROOTPASS: ${MYSQLROOTPASS}"
echo 
echo "   If you would like to change these variables, exit this"
echo "   script, edit the variables and run this script again."
echo
echo "   You must be connected to the internet to install NiDB"
echo "   This script will run mostly unattended, but you will be "
echo "   asked to provide input occasionally, especially when"
echo "   securing the MariaDB installation"
echo 
echo "******************************************************"
echo 
echo 
echo 
echo 

read -p "Press [ctrl-C] to exit, or [enter] to continue"

#---------------create an nidb account --------------
echo "--------------- Creating nidb user account ----------------"
echo "Please enter the Linux account under which NiDB will run (it will be created if it does not exist)"
read NIDBUSER
useradd -m -s /bin/bash $NIDBUSER
echo "Enter the password for the NiDB account"
passwd $NIDBUSER

# enable repos for PHP7 and mariadb 10
#yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
#yum install -y http://rpms.remirepo.net/enterprise/remi-release-7.rpm
#yum install -y yum-utils
#yum-config-manager --enable remi-php72   # Install PHP 7.2

#echo "# MariaDB 10.2 CentOS repository list - created 2017-12-15 15:51 UTC
## http://downloads.mariadb.org/mariadb/repositories/
#[mariadb]
#name = MariaDB
#baseurl = http://yum.mariadb.org/10.2/centos7-amd64
#gpgkey=https://yum.mariadb.org/RPM-GPG-KEY-MariaDB
#gpgcheck=1" >> /etc/yum.repos.d/MariaDB.repo

# ---------- yum based installs ----------
echo "----------------- Installing YUM based packages -----------------"
yum install -y vim
#yum install -y perl*
#yum install -y cpan
yum install -y php php-mysql php-gd php-process php-pear php-mcrypt php-mbstring
yum install -y httpd
yum install -y MariaDB-server MariaDB-client
yum install -y git
yum install -y gcc gcc-c++
yum install -y gedit*
yum install -y iptraf*
yum install -y java
yum install -y ImageMagick
yum install -y iptables-services

# --------- extra Perl/CPAN based installs ----------
#echo "----------------- Installing Perl modules from CPAN -----------------"
#cpan File::Path
#cpan Net::SMTP::TLS
#cpan List::Util
#cpan Date::Parse
#cpan Image::ExifTool
#cpan String::CRC32
#cpan Date::Manip
#cpan Sort::Naturally
#cpan Digest::MD5
#cpan Digest::MD5::File
#cpan Statistics::Basic
#cpan Email::Send::SMTP::Gmail

# copy recently installed Perl modules to a directory accessible by the
# nidb account
#cp -rv /root/perl5/lib/perl/* /usr/local/lib64/perl5/

echo "----------------- Installing PHP modules from pear -----------------"
pear install Mail
pear install Mail_Mime
pear install Net_SMTP

#cp -rv Mysql* /usr/local/lib64/perl5/

# ---------- configure system based services ----------
echo "----------------- Configuring system services -----------------\n"
echo "------ Disabling SELinux ------"
setenforce 0
sed -i 's/^SELINUX=.*/SELINUX=disabled/g' /etc/selinux/config

#echo "Setting up port forwarding to forward 8104 to 104"
# configure the firewall to accept everything, and still forward port 104 to 8104 (using iptables)
# stop the existing firewalld service and mask it
#systemctl stop firewalld
#systemctl mask firewalld
#systemctl enable iptables
# setup the port forwarding in iptables
#iptables -F
#iptables -X
#iptables -t nat -F
#iptables -t nat -X
#iptables -t mangle -F
#iptables -t mangle -X
#iptables -P INPUT ACCEPT
#iptables -P FORWARD ACCEPT
#iptables -P OUTPUT ACCEPT
#iptables -A FORWARD -p tcp --destination-port 104 -j ACCEPT
#iptables -t nat -A PREROUTING -j REDIRECT -p tcp --destination-port 104 --to-port 8104
#iptables-save > /etc/sysconfig/iptables
#systemctl start iptables
#echo "Done setting up port forwarding and disabling the firewall"

# ---------- Web based installs ----------
echo "----------------- Web based installs (Webmin, phpMyAdmin) -----------------"

echo "------ Enabling services at boot ------"
systemctl enable httpd.service
systemctl enable mariadb.service
echo "------ Starting services ------"
systemctl start httpd.service
systemctl start mariadb.service

sed -i 's/^short_open_tag = .*/short_open_tag = On/g' /etc/php.ini
sed -i 's/^session.gc_maxlifetime = .*/session.gc_maxlifetime = 28800/g' /etc/php.ini
sed -i 's/^memory_limit = .*/memory_limit = 5000M/g' /etc/php.ini
sed -i 's/^upload_tmp_dir = .*/upload_tmp_dir = \/${NIDBROOT}\/uploadtmp/g' /etc/php.ini
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 5000M/g' /etc/php.ini
sed -i 's/^max_file_uploads = .*/max_file_uploads = 1000/g' /etc/php.ini
sed -i 's/^max_input_time = .*/max_input_time = 600/g' /etc/php.ini
sed -i 's/^max_execution_time = .*/max_execution_time = 600/g' /etc/php.ini
sed -i 's/^post_max_size = .*/post_max_size = 5000M/g' /etc/php.ini
sed -i 's/^display_errors = .*/display_errors = On/g' /etc/php.ini
sed -i 's/^error_reporting = .*/error_reporting = E_ALL \& \~E_DEPRECATED \& \~E_STRICT \& \~E_NOTICE/' /etc/php.ini

echo "------ Modifying httpd to run as nidb user ------"
sed -i "s/User apache/User $NIDBUSER/" /etc/httpd/conf/httpd.conf
sed -i "s/Group apache/Group $NIDBUSER/" /etc/httpd/conf/httpd.conf
chown -R $NIDBUSER:$NIDBUSER /var/lib/php/session
echo "------ Restarting httpd ------"
systemctl restart httpd.service

# secure the mysql installation
echo "----------- Securing MySQL/MariaDB installation -------------"
mysql_secure_installation

echo "------ Setting up MySQL database - default password is 'password' ------"
mysqladmin -u root password '${MYSQLROOTPASS}'
echo "Assigning permissions to mysql root account"
echo "CREATE USER '${MYSQLUSER}'@'%' identified by '${MYSQLPASS}';" >> ~/tempsql.txt
echo "GRANT ALL PRIVILEGES on *.* to '${MYSQLUSER}'@'%';" >> ~/tempsql.txt
mysql -uroot -p${MYSQLROOTPASS} < ~/tempsql.txt
rm ~/tempsql.txt

echo "------ Install phpMyAdmin ------"
wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-english.zip
unzip phpMyAdmin-latest-english.zip
mv phpMyAdmin-*-english /var/www/html/phpMyAdmin
chmod 777 /var/www/html
chown -R $NIDBUSER:$NIDBUSER /var/www/html
sed '$ i $cfg[''McryptDisableWarning''] = TRUE;' /var/www/html/phpMyAdmin/config.sample.inc.php;
sed '$ i $cfg[''LoginCookieValidity''] = 28800;' /var/www/html/phpMyAdmin/config.sample.inc.php;
#echo "Rename config.sample.inc.php to config.inc.php"
cp /var/www/html/phpMyAdmin/config.sample.inc.php /var/www/html/phpMyAdmin/config.inc.php
chmod 755 /var/www/html/phpMyAdmin/config.inc.php
echo "You should be able to see this" >> /var/www/html/index.php
echo "Check to make sure you can see http://$HOSTNAME/index.php"
read -p "Press [enter] to continue"

# --------- install all nidb files and db ----------
echo "----------------- Copying nidb program/html files -----------------"
# copy all files to their final location
echo "Creating NiDB directories"
mkdir -pv ${NIDBROOT}
mkdir -pv ${NIDBROOT}/archive
mkdir -pv ${NIDBROOT}/backup
mkdir -pv ${NIDBROOT}/dicomincoming
mkdir -pv ${NIDBROOT}/deleted
mkdir -pv ${NIDBROOT}/download
mkdir -pv ${NIDBROOT}/ftp
mkdir -pv ${NIDBROOT}/incoming
mkdir -pv ${NIDBROOT}/problem
mkdir -pv ${NIDBROOT}/programs
mkdir -pv ${NIDBROOT}/programs/lock
mkdir -pv ${NIDBROOT}/programs/logs
mkdir -pv ${NIDBROOT}/uploadtmp
mkdir -pv ${NIDBROOT}/uploaded

cd ${NIDBROOT}
svn export https://github.com/gbook/nidb/trunk install
cd ${NIDBROOT}/install
cp -Rv programs/* ${NIDBROOT}/programs
cp -Rv web/* /var/www/html/
chown -R $NIDBUSER:$NIDBUSER ${NIDBROOT}
chown -R $NIDBUSER:$NIDBUSER /var/www/html

sed -i 's!\$cfg = LoadConfig(.*)!\$cfg = LoadConfig("/nidb/programs/nidb.cfg");!g' /var/www/html/functions.php

# create default database from .sql file
echo "Creating default database"
cd ${NIDBROOT}/install/setup
mysql -uroot -ppassword -e "create database if not exists nidb; grant all on *.* to 'root'@'localhost' identified by '${MYSQLROOTPASS}'; flush privileges;"
mysql -uroot -ppassword nidb < nidb.sql
mysql -uroot -ppassword nidb < nidb-data.sql

# ---------- dcm4che ----------
echo "----------------- Installing DICOM receiver -----------------"
echo "Installing dcm4che receiver to listen on port 8104"
# add dcmrcv service at boot
cp ${NIDBROOT}/install/programs/dcmrcv /etc/init.d
sed -i "s/su nidb/su $NIDBUSER/" /etc/init.d/dcmrcv
chmod 755 /etc/init.d/dcmrcv
chkconfig --add dcmrcv

# ---------- setup cron jobs ----------
echo "----------------- Setup scheduled cron jobs -----------------"
echo "Setting up cron jobs for nidb"

echo "# NiDB modules" >> tempcron.txt
echo "* * * * * cd /nidb/programs/bin; LD_LIBRARY_PATH=$PWD; export LD_LIBRARY_PATH; ./nidb modulemanager > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * cd /nidb/programs/bin; LD_LIBRARY_PATH=$PWD; export LD_LIBRARY_PATH; ./nidb import > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * cd /nidb/programs/bin; LD_LIBRARY_PATH=$PWD; export LD_LIBRARY_PATH; ./nidb export > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * cd /nidb/programs/bin; LD_LIBRARY_PATH=$PWD; export LD_LIBRARY_PATH; ./nidb importuploaded > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * cd /nidb/programs/bin; LD_LIBRARY_PATH=$PWD; export LD_LIBRARY_PATH; ./nidb fileio > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * cd /nidb/programs/bin; LD_LIBRARY_PATH=$PWD; export LD_LIBRARY_PATH; ./nidb mriqa > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * cd /nidb/programs/bin; LD_LIBRARY_PATH=$PWD; export LD_LIBRARY_PATH; ./nidb qc > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * cd /nidb/programs/bin; LD_LIBRARY_PATH=$PWD; export LD_LIBRARY_PATH; ./nidb pipeline > /dev/null 2>&1" >> tempcron.txt
echo "" >> tempcron.txt
echo "# NiDB cleanup" >> tempcron.txt
echo "@hourly find /nidb/programs/logs/*.log -mtime +4 -exec rm {} \;" >> tempcron.txt
echo "@hourly find /nidb/programs/logs/*.log -mtime +4 -exec rm {} \;" >> tempcron.txt
crontab -u $NIDBUSER tempcron.txt

# ---------- list the remaining things to be done by the user ----------
echo "----------------- Remaining items to be done by you -----------------"
echo "1) Install FSL (https://fsl.fmrib.ox.ac.uk/fsl/fslwiki) to the default path [/usr/local/fsl] ***"
echo "2) Edit /etc/php.ini to reflect your timezone"
echo "3) Your default mysql account is root, password is '${MYSQLROOTPASS}'. Change the password as soon as possible"
echo "4) Edit ${NIDBROOT}/programs/nidb.cfg.sample to reflect your paths, usernames, and passwords. Rename to nidb.cfg"
echo "       nidb.cfg can be edited using the Admin->NiDB Settings... menu item, once you have logged in as admin";
echo "5) Some modules are disabled by default in cron. Use crontab -e to enable them"
echo "       Consider reviewing the mysql backup procedure and passwords in cron"
echo "TIP: A reboot can be useful to make sure everything works"
