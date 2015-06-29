#!/bin/sh
# this script will download additional programs and features specific to nidb
# additional configuration steps are noted below

# if you change the default path, NiDB is not guaranteed to work correctly
NIDBROOT="/nidb"

clear
echo 
echo 
echo 
echo "******************************************************"
echo 
echo "           " Neuroimaging Database Setup
echo 
echo "     (installing in ${NIDBROOT} and /var/www/html)"
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
echo "Please enter account-name for nidb"
read NIDBUSER
useradd -m -s /bin/bash $NIDBUSER
echo "Enter the passowrd for the nidb account"
passwd $NIDBUSER

# ---------- yum based installs ----------
echo "----------------- Installing YUM based packages -----------------"
echo "Because of Perl dependency issues, all perl packages will be installed"
yum install -y -q vim
yum install -y -q perl*
yum install -y -q cpan
yum install -y -q php
yum install -y -q php-mysql
yum install -y -q php-gd
yum install -y -q php-process
yum install -y -q php-pear
yum install -y -q php-mcrypt
yum install -y -q php-mbstring
yum install -y -q httpd
yum install -y -q mysql
yum install -y -q mysql-server
yum install -y -q mariadb
yum install -y -q mariadb-server
yum install -y -q git
yum install -y -q gcc
yum install -y -q gcc-c++
yum install -y -q gedit*
yum install -y -q iptraf*
yum install -y -q java
yum install -y -q ImageMagick

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

# copy recently installed Perl modules to a directory accessible by the
# nidb account
cp -rv /root/perl5/lib/perl/* /usr/local/lib64/perl5/

echo "----------------- Installing PHP modules from pear -----------------"
pear install Mail
pear install Mail_Mime

cp -rv Mysql* /usr/local/lib64/perl5/

# compile ImageMagick with fft support
#wget http://www.fftw.org/fftw-3.3.2.tar.gz
#tar -xvzf fftw-3.3.2.tar.gz
#cd fftw3
#./configure CXXFLAGS=-fPIC CFLAGS=-fPIC
#make
#make install
#cd ..
#wget http://www.imagemagick.org/download/ImageMagick.tar.gz
#tar -xvzf ImageMagick.tar.gz
#cd ImageMagick
#./configure --enable-hdri -with-fftw
#make
#make install

# ---------- configure system based services ----------
echo "----------------- Configuring system services -----------------"
echo "------ Disable SELinux ------"
setenforce 0
#echo "SELinux is now temporarily disabled. To permanently disable it, the following must be done"
#echo "This step must be done manually. edit /etc/selinux/config and change SELINUX=enforcing to SELINUX=disabled"
sed -i 's/^SELINUX=.*/SELINUX=disabled/g' /etc/selinux/config
#read -p "Press [enter] to continue"

echo "Setting up port forwarding to forward 8104 to 104"
# configure the firewall to accept everything, and still forward port 104 to 8104
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
echo "Done setting up port forwarding and disabling the firewall"

# ---------- Web based installs ----------
echo "----------------- Web based installs (Webmin, phpMyAdmin) -----------------"
#echo "------ Installing Webmin... ------"
#wget http://prdownloads.sourceforge.net/webadmin/webmin-1.750-1.noarch.rpm
#rpm -U webmin-1.750-1.noarch.rpm

echo "------ Enabling services at boot ------"
systemctl enable httpd.service
systemctl enable mariadb.service
echo "------ Starting services ------"
systemctl start httpd.service
systemctl start mariadb.service

#echo "------ Manually configure PHP variables ------"
#echo "Go to https://$HOSTNAME:10000"
#echo "then go to Others -> PHP Configuration -> Manage -> Other Settings ... change PHP Timezone to your timezone"

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
sed -i 's/^error_reporting = .*/error_reporting = E_ALL & \~E_DEPRECATED & \~E_STRICT & \~E_NOTICE/' /etc/php.ini

echo "------ Modifying httpd to run as nidb user ------"
sed -i "s/User apache/User $NIDBUSER/" /etc/httpd/conf/httpd.conf
sed -i "s/Group apache/Group $NIDBUSER/" /etc/httpd/conf/httpd.conf
chown -R $NIDBUSER:$NIDBUSER /var/lib/php/session
echo "------ Restarting httpd ------"
systemctl restart httpd.service

echo "------ Setting up MySQL database - default password is 'password' ------"
mysqladmin -u root password 'password'
echo "Assigning permissions to mysql root account"
echo "GRANT ALL PRIVILEGES on *.* to root@'%'" >> ~/tempsql.txt
mysql -uroot -ppassword < ~/tempsql.txt
rm ~/tempsql.txt

echo "------ Install phpMyAdmin ------"
wget http://downloads.sourceforge.net/project/phpmyadmin/phpMyAdmin/4.4.7/phpMyAdmin-4.4.7-english.zip
unzip phpMyAdmin-4.4.7-english.zip
mv phpMyAdmin-4.4.7-english /var/www/html/phpMyAdmin
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
mysql -uroot -ppassword -e "create database if not exists nidb; grant all on *.* to 'root'@'localhost' identified by 'password'; flush privileges;"
mysql -uroot -ppassword nidb < nidb.sql

# ---------- dcm4che ----------
echo "----------------- Installing DICOM receiver -----------------"
echo "Installing dcm4che receiver to listen on port 8104"
#echo "Go to Webmin -> System -> Bootup and Shutdown"
#echo "     Click 'create a new bootup and shutdown action'"
#echo "     Enter the following and click 'Create'"
#echo "     Name: dcmrcv"
#echo "     Description: dcmrcv"
#echo "     Bootup commands: su nidb -c '${NIDBROOT}/programs/dcm4che/bin/./dcmrcv NIDB:8104 -dest ${NIDBROOT}/dicomincoming > /dev/null 2>&1 &'"
#echo "     Shutdown commands: "
#echo "     Start at boot time: Yes"
#read -p "Press [enter] to continue"

# add dcmrcv service at boot
cp ${NIDBROOT}/install/programs/dcmrcv /etc/init.d
sed -i "s/su nidb/su $NIDBUSER/" /etc/init.d/dcmrcv
chmod 755 /etc/init.d/dcmrcv
chkconfig --add dcmrcv

# ---------- setup cron jobs ----------
echo "----------------- Setup scheduled cron jobs -----------------"
echo "Setting up cron jobs for nidb"
echo "* * * * * cd ${NIDBROOT}/programs; perl parsedicom.pl > /dev/null 2>&1" >> tempcron.txt
echo "#* * * * * cd ${NIDBROOT}/programs; perl parseincoming.pl > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * FSLDIR=/usr/local/fsl; PATH=\${FSLDIR}/bin:\${PATH}; . \${FSLDIR}/etc/fslconf/fsl.sh; export FSLDIR PATH; cd ${NIDBROOT}/programs; perl mriqa.pl > /dev/null 2>&1" >> tempcron.txt
echo "* * * * * cd ${NIDBROOT}/programs; perl datarequests.pl > /dev/null 2>&1" >> tempcron.txt
echo "#@daily cd ${NIDBROOT}/programs; perl dailyreport.pl > /dev/null 2>&1" >> tempcron.txt
echo "#0,5,10,15,20,25,30,35,40,45,50,55 * * * * FSLDIR=/usr/local/fsl; PATH=\${FSLDIR}/bin:\${PATH}; . \${FSLDIR}/etc/fslconf/fsl.sh; export FSLDIR PATH; cd ${NIDBROOT}/programs; perl mristudyqa.pl > /dev/null 2>&1" >> tempcron.txt
echo "@daily /usr/bin/mysqldump nidb -u root -ppassword | gzip > ${NIDBROOT}/backup/db-\`date +%Y-%m-%d\`.sql.gz" >> tempcron.txt
crontab -u $NIDBUSER tempcron.txt
rm ~/tempcron.txt

# ---------- list the remaining things to be done by the user ----------
echo "----------------- Remaining items to be done by you -----------------"
echo " *** Install FSL to the default path [/usr/local/fsl] ***"
echo "Edit /etc/php.ini to reflect your timezone"
echo "Your default mysql account is root, password is 'password'. Change these as soon as possible"
echo "Edit ${NIDBROOT}/programs/nidb.cfg.sample to reflect your paths, usernames, and passwords. Rename to nidb.cfg"
echo "Some modules are disabled in cron. Use crontab -e to enable them"
echo "TIP: A reboot can be useful to make sure everything works"
