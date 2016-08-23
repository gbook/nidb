#!/bin/sh
# this script will download additional programs and features specific to nidb
# additional configuration steps are noted below

# if you change the default path, NiDB is not guaranteed to work correctly
NIDBROOT="/nidb"
WWWROOT="/var/www/html"
NIDBUSER="nidb"
MYSQLUSER="root"
MYSQLPASS="password"

clear
echo 
echo 
echo 
echo "******************************************************"
echo 
echo "           " Neuroimaging Database Setup
echo 
echo "     (installing in ${NIDBROOT} and ${WWWROOT})"
echo 
echo "  Maximize this terminal window to read all instructions"
echo 
echo "******************************************************"
echo 
echo 
echo 
echo 

read -p "Press [enter] to continue"

#---------------create an nidb account --------------
echo "${redb}${whitef}${boldon}--------------- Creating nidb user account ----------------${reset}"
echo "Please enter the Linux account under which NiDB will run (it will be created if it does not exist)"
useradd $NIDBUSER
echo "Enter the password for the NiDB account"
passwd $NIDBUSER

# ---------- package manager based installs ----------
echo "----------------- Installing packages -----------------"
apt-get -yqf install vim
apt-get -yqf install perl
apt-get -yqf install apache2
apt-get -yqf install libapache2-mod-php
apt-get -yqf install php
apt-get -yqf install php-mysql
apt-get -yqf install php-gd
apt-get -yqf install php-pear
apt-get -yqf install php-mcrypt
apt-get -yqf install php-mbstring
apt-get -yqf install mariadb-server
apt-get -yqf install git
apt-get -yqf install gcc
apt-get -yqf install gedit
apt-get -yqf install iptraf
apt-get -yqf install imagemagick
apt-get -yqf install iptables
apt-get -yqf install subversion

# --------- extra Perl/CPAN based installs ----------
echo "----------------- Installing Perl modules from CPAN -----------------"
cpan File::Path
cpan Net::SMTP::TLS
cpan List::Util
cpan Date::Parse
cpan Image::ExifTool
cpan String::CRC32
cpan Date::Manip
cpan Sort::Naturally
cpan Digest::MD5
cpan Digest::MD5::File
cpan Statistics::Basic
cpan Email::Send::SMTP::Gmail
cpan Switch
cpan XML::Writer
cpan XML::Generator::DBI
cpan XML::Handler::YAWriter
cpan XML::Bare
cpan File::Slurp
cpan Math::Derivative
cpan Math::Round

# copy the installed Perl modules to a directory accessible by the nidb account
#cp -rv /root/perl5/lib/perl/* /usr/local/lib64/perl5/

echo "----------------- Installing PHP modules from pear -----------------"
pear install Mail
pear install Mail_Mime
pear install Net_SMTP

#cp -rv Mysql* /usr/local/lib64/perl5/

# ---------- configure system based services ----------
echo "----------------- Configuring system services -----------------"

echo "Setting up port forwarding to forward 8104 to 104"
# configure the firewall to accept everything, and still forward port 104 to 8104 (using iptables)
# stop the existing firewalld service and mask it
systemctl stop firewalld
systemctl mask firewalld
systemctl enable iptables
# setup the port forwarding in iptables
iptables -F
iptables -X
iptables -t nat -F
iptables -t nat -X
iptables -t mangle -F
iptables -t mangle -X
iptables -P INPUT ACCEPT
iptables -P FORWARD ACCEPT
iptables -P OUTPUT ACCEPT
iptables -A FORWARD -p tcp --destination-port 104 -j ACCEPT
iptables -t nat -A PREROUTING -j REDIRECT -p tcp --destination-port 104 --to-port 8104
iptables-save > /etc/sysconfig/iptables
systemctl start ufw
echo "Done setting up port forwarding and disabling the firewall"

echo "------ Enabling services at boot ------"
systemctl enable apache2
systemctl enable mysql
echo "------ Starting services ------"
systemctl start apache2
systemctl start mysql

echo "------ Configuring PHP variables ------"
sed -i 's/^short_open_tag = .*/short_open_tag = On/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^session.gc_maxlifetime = .*/session.gc_maxlifetime = 28800/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^memory_limit = .*/memory_limit = 5000M/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^upload_tmp_dir = .*/upload_tmp_dir = \/${NIDBROOT}\/uploadtmp/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 5000M/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^max_file_uploads = .*/max_file_uploads = 1000/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^max_input_time = .*/max_input_time = 600/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^max_execution_time = .*/max_execution_time = 600/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^post_max_size = .*/post_max_size = 5000M/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^display_errors = .*/display_errors = On/g' /etc/php/7.0/apache2/php.ini
sed -i 's/^error_reporting = .*/error_reporting = E_ALL \& \~E_DEPRECATED \& \~E_STRICT \& \~E_NOTICE/g' /etc/php/7.0/apache2/php.ini

echo "------ Modifying httpd to run as nidb user ------"
sed -i "s/APACHE_RUN_USER.*/APACHE_RUN_USER=$NIDBUSER/" /etc/apache2/envvars
sed -i "s/APACHE_RUN_GROUP.*/APACHE_RUN_GROUP=$NIDBUSER/" /etc/apache2/envvars
chown -Rv $NIDBUSER:$NIDBUSER /var/lib/php/sessions
echo "------ Restarting httpd ------"
systemctl restart apache2

echo "------ Setting up MySQL database ------"
mysqladmin -u ${MYSQLUSER} password '${MYSQLPASS}'
echo "Assigning permissions to mysql [${MYSQLUSER}] account"
echo "GRANT ALL PRIVILEGES on *.* to ${MYSQLUSER}@'%'" >> ~/tempsql.txt
mysql -u ${MYSQLUSER} -p${MYSQLPASS} < ~/tempsql.txt
rm ~/tempsql.txt

echo "------ Installing phpMyAdmin (follow prompts) ------"
apt-get install phpmyadmin
systemctl restart apache2

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

# clear the default apache index page
rm -f /var/www/html/index.html

# copy in the NiDB files
cd ${NIDBROOT}
svn export --force https://github.com/gbook/nidb/trunk install
cd ${NIDBROOT}/install
cp -Rf programs/* ${NIDBROOT}/programs
cp -Rf web/* ${WWWROOT}
chown -R $NIDBUSER:$NIDBUSER ${NIDBROOT}
chown -R $NIDBUSER:$NIDBUSER ${WWWROOT}

sed -i "s!\$cfg = LoadConfig(.*)!\$cfg = LoadConfig(\"$NIDBROOT/programs/nidb.cfg\");!g" ${WWWROOT}/functions.php

# create default database from .sql file
echo "Creating default database"
cd ${NIDBROOT}/install/setup
mysql -u ${MYSQLUSER} -p${MYSQLPASS} -e "create database if not exists nidb; grant all on *.* to '${MYSQLUSER}'@'localhost' identified by '${MYSQLPASS}'; flush privileges;"
mysql -u ${MYSQLUSER} -p${MYSQLPASS} nidb < nidb.sql
mysql -u ${MYSQLUSER} -p${MYSQLPASS} nidb < nidb-data.sql

# ---------- dcm4che ----------
echo "----------------- Installing DICOM receiver -----------------"
echo "Installing dcm4che receiver to listen on port 8104"

# add dcmrcv service at boot
cp ${NIDBROOT}/install/programs/dcmrcv /etc/init.d
sed -i "s/su nidb/su $NIDBUSER/" /etc/init.d/dcmrcv
chmod 755 /etc/init.d/dcmrcv
update-rc.d dcmrcv defaults

# ---------- setup cron jobs ----------
echo "----------------- Setup scheduled cron jobs -----------------"
echo "Setting up cron jobs for nidb"
echo "* * * * * cd ${NIDBROOT}/programs; perl parsedicom.pl > /dev/null 2>&1" >> tempcron.txt
echo "#* * * * * cd ${NIDBROOT}/programs; perl parseincoming.pl > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * FSLDIR=/usr/local/fsl; PATH=\${FSLDIR}/bin:\${PATH}; . \${FSLDIR}/etc/fslconf/fsl.sh; export FSLDIR PATH; cd ${NIDBROOT}/programs; perl mriqa.pl > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * cd ${NIDBROOT}/programs; perl datarequests.pl > /dev/null 2>&1" >> tempcron.txt
echo "#@daily cd ${NIDBROOT}/programs; perl dailyreport.pl > /dev/null 2>&1" >> tempcron.txt
echo "#0,5,10,15,20,25,30,35,40,45,50,55 * * * * FSLDIR=/usr/local/fsl; PATH=\${FSLDIR}/bin:\${PATH}; . \${FSLDIR}/etc/fslconf/fsl.sh; export FSLDIR PATH; cd ${NIDBROOT}/programs; perl mristudyqa.pl > /dev/null 2>&1" >> tempcron.txt
echo "@daily /usr/bin/mysqldump nidb -u ${MYSQLUSER} -p${MYSQLPASS} | gzip > ${NIDBROOT}/backup/db-\`date +%Y-%m-%d\`.sql.gz" >> tempcron.txt
crontab -u $NIDBUSER tempcron.txt
rm ~/tempcron.txt

# ---------- list the remaining things to be done by the user ----------
echo "----------------- Remaining items to be done by you -----------------"
echo " *** Install FSL to the default path [/usr/local/fsl] ***"
echo "Edit /etc/php/7.0/apache2/php.ini to reflect your timezone"
echo "Edit ${NIDBROOT}/programs/nidb.cfg.sample to reflect your paths, usernames, and passwords. Rename to nidb.cfg"
echo "Some modules are disabled in cron. Use crontab -e to enable them"
echo "TIP: A reboot can be useful to make sure everything works"
