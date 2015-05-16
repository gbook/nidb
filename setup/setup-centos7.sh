#!/bin/sh
# this script will download additional programs and features specific to nidb
# additional configuration steps are noted below

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

# ---------- yum based installs ----------
echo "----------------- Installing YUM based packages -----------------"
yum install -y system* vnc* vim emacs
yum install -y vnc*
yum install -y perl
yum install -y perl-File-Copy-Recursive
yum install -y perl-Sort-Naturally
yum install -y perl-Net-SMTP-TLS
yum install -y perl-Data-Dumper
yum install -y perl-Math-Round
yum install -y perl-Math-Derivative
yum install -y perl-Math-MatrixReal
yum install -y perl-Math-Combinatorics
yum install -y cpan
yum install -y perl-YAML
yum install -y php
yum install -y php-mysql
yum install -y php-gd
yum install -y php-process
yum install pear
yum install -y httpd
yum install -y mysql
yum install -y mysql-server
yum install -y mariadb
yum install -y mariadb-server
yum install -y subversion*
yum install -y git
yum install -y gcc
yum install -y gcc-c++
yum install -y gedit*
yum install -y iptraf*
yum install -y java
yum install -y ImageMagick

# --------- Perl/CPAN based installs ----------
echo "----------------- Installing Perl modules from CPAN -----------------"
cpan File::Copy
cpan File::Find
cpan File::Path
cpan List::Util
cpan Date::Parse
cpan Image::ExifTool

echo "----------------- Installing PHP modules from pear -----------------"
pear install Mail
pear install Mail_Mime

#wget http://www.imagemagick.org/download/linux/CentOS/x86_64/ImageMagick-6.8.0-4.x86_64.rpm
#rpm -U ImageMagick-6.8.0-4.x86_64.rpm

cp -r Mysql* /usr/lib64/perl5/

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
echo "------ Enabling services at boot ------"
systemctl enable httpd.service
systemctl enable mariadb.service
echo "------ Starting services ------"
systemctl start httpd.service
systemctl start mariadb.service

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
echo "------ Installing Webmin... ------"
wget http://prdownloads.sourceforge.net/webadmin/webmin-1.750-1.noarch.rpm
rpm -U webmin-1.750-1.noarch.rpm

echo "------ Manually configure PHP variables ------"
echo "Go to https://$HOSTNAME:10000"
echo "then go to Others -> PHP Configuration -> Manage -> Other Settings ... change PHP Timezone to your timezone"
echo "then go to Others -> PHP Configuration -> Manage -> Error Logging ... change Expression for error types = E_ALL & ~E_DEPRECATED & ~E_NOTICE"

sed -i 's/^short_open_tag = .*/short_open_tag = On/g' /etc/php.ini
sed -i 's/^session.gc_maxlifetime = .*/session.gc_maxlifetime = 28800/g' /etc/php.ini
sed -i 's/^memory_limit = .*/memory_limit = 1000M/g' /etc/php.ini
sed -i 's/^upload_tmp_dir = .*/upload_tmp_dir = \/${NIDBROOT}\/uploadtmp/g' /etc/php.ini
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 1000M/g' /etc/php.ini
sed -i 's/^max_input_time = .*/max_input_time = 360/g' /etc/php.ini
sed -i 's/^max_execution_time = .*/max_execution_time = 360/g' /etc/php.ini
sed -i 's/^post_max_size = .*/post_max_size = 1000M/g' /etc/php.ini
sed -i 's/^display_errors = .*/display_errors = On/g' /etc/php.ini

read -p "Press [enter] to continue"

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
chown -R nidb:nidb /var/www/html
#echo "Edit the /var/www/html/phpMyAdmin/config.sample.inc.php file and add:"
#echo "      \$cfg['McryptDisableWarning'] = TRUE;"
#echo "      \$cfg['LoginCookieValidity'] = 28800;"
sed '$ i $cfg[''McryptDisableWarning''] = TRUE;' /var/www/html/phpMyAdmin/config.sample.inc.php;
sed '$ i $cfg[''LoginCookieValidity''] = 28800;' /var/www/html/phpMyAdmin/config.sample.inc.php;
#echo "Rename config.sample.inc.php to config.inc.php"
cp /var/www/html/phpMyAdmin/config.sample.inc.php /var/www/html/phpMyAdmin/config.inc.php
chmod 755 /var/www/html/phpMyAdmin/config.inc.php
echo "You should be able to see this" >> /var/www/html/index.php
echo "Check to make sure you can see http://$HOSTNAME/index.php"
read -p "Press [enter] to continue"
echo "phpMyAdmin installed, but must be configured"
echo "Go to http://$HOSTNAME/phpMyAdmin/setup to add the local DB server"
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

cd ${NIDBROOT}
svn export https://github.com/gbook/nidb/trunk install
cd ${NIDBROOT}/install
cp -R programs/* ${NIDBROOT}/programs
cp -R web/* /var/www/html/
chown -R nidb:nidb ${NIDBROOT}
chown -R nidb:nidb /var/www/html
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
cp dcmrcv /etc/init.d
chmod 755 /etc/init.d/dcmrcv
chkconfig --add dcmrcv

# echo "#!/bin/sh" >> /etc/init.d/dcmrcv
# echo "# description: DICOM receiver" >> /etc/init.d/dcmrcv
# echo "# chkconfig: 2345 99 00" >> /etc/init.d/dcmrcv
# echo "case \"$1\" in" >> /etc/init.d/dcmrcv
# echo "'start')" >> /etc/init.d/dcmrcv
# echo "        su onrc -c '${NIDBROOT}/programs/dcm4che/bin/./dcmrcv NIDB:8104 -dest ${NIDBROOT}/dicomincoming > /dev/null 2>&1 &'" >> /etc/init.d/dcmrcv
# echo "        touch /var/lock/subsys/dcmrcv" >> /etc/init.d/dcmrcv
# echo "        ;;" >> /etc/init.d/dcmrcv
# echo "'stop')" >> /etc/init.d/dcmrcv
# echo "        rm -f /var/lock/subsys/dcmrcv" >> /etc/init.d/dcmrcv
# echo "        ;;" >> /etc/init.d/dcmrcv
# echo "*)" >> /etc/init.d/dcmrcv
# echo "        echo \"Usage: $0 { start | stop }\"" >> /etc/init.d/dcmrcv
# echo "        ;;" >> /etc/init.d/dcmrcv
# echo "esac" >> /etc/init.d/dcmrcv
# echo "exit 0" >> /etc/init.d/dcmrcv
# chkconfig --add dcmrcv


# ---------- setup cron jobs ----------
echo "----------------- Setup scheduled cron jobs -----------------"
echo "Setting up cron jobs for nidb"
echo "#* * * * * cd ${NIDBROOT}/programs; perl parsedicom.pl > /dev/null 2>&1" >> tempcron.txt
echo "#* * * * * cd ${NIDBROOT}/programs; perl parseincoming.pl > /dev/null 2>&1" >> tempcron.txt
echo "#* * * * * FSLDIR=/usr/local/fsl; PATH=\${FSLDIR}/bin:\${PATH}; . \${FSLDIR}/etc/fslconf/fsl.sh; export FSLDIR PATH; cd ${NIDBROOT}/programs; perl mriqa.pl > /dev/null 2>&1" >> tempcron.txt
echo "#* * * * * cd ${NIDBROOT}/programs; perl datarequests.pl > /dev/null 2>&1" >> tempcron.txt
echo "#@daily cd ${NIDBROOT}/programs; perl dailyreport.pl > /dev/null 2>&1" >> tempcron.txt
echo "#0,5,10,15,20,25,30,35,40,45,50,55 * * * * FSLDIR=/usr/local/fsl; PATH=\${FSLDIR}/bin:\${PATH}; . \${FSLDIR}/etc/fslconf/fsl.sh; export FSLDIR PATH; cd ${NIDBROOT}/programs; perl mristudyqa.pl > /dev/null 2>&1" >> tempcron.txt
echo "#@daily /usr/bin/mysqldump nidb -u root -ppassword | gzip > ${NIDBROOT}/backup/db-\`date +%Y-%m-%d\`.sql.gz" >> tempcron.txt
crontab -u nidb tempcron.txt
#rm ~/tempcron.txt


# ---------- list the remaining things to be done by the user ----------
echo "----------------- Remaining items to be done by you -----------------"
echo "Install FSL to the default path [/usr/local/fsl]"
echo "Your default mysql account is root, password is 'password'. Change these as soon as possible"
echo "Edit /nidb/programs/config.pl to reflect the paths , usernames, and passwords of your installation"
echo "Edit /var/www/html/config.php to reflect your paths, usernames, and passwords"
echo "All processes specified by cron jobs are disabled. Go to Webmin -> System -> Scheduled Cron Jobs, to enable the cron jobs "
