#!/bin/sh
# this script will download additional programs and features specific to nidb
# additional configuration steps are noted below

NIDBROOT="/nidb"

initializeANSI()
{
# ANSI Color -- use these variables to easily have different color
#    and format output. Make sure to output the reset sequence after 
#    colors (f = foreground, b = background), and use the 'off'
#    feature for anything you turn on.
  esc=""

  blackf="${esc}[30m";   redf="${esc}[31m";    greenf="${esc}[32m"
  yellowf="${esc}[33m"   bluef="${esc}[34m";   purplef="${esc}[35m"
  cyanf="${esc}[36m";    whitef="${esc}[37m"
  
  blackb="${esc}[40m";   redb="${esc}[41m";    greenb="${esc}[42m"
  yellowb="${esc}[43m"   blueb="${esc}[44m";   purpleb="${esc}[45m"
  cyanb="${esc}[46m";    whiteb="${esc}[47m"

  boldon="${esc}[1m";    boldoff="${esc}[22m"
  italicson="${esc}[3m"; italicsoff="${esc}[23m"
  ulon="${esc}[4m";      uloff="${esc}[24m"
  invon="${esc}[7m";     invoff="${esc}[27m"

  reset="${esc}[0m"
}

initializeANSI

clear
echo 
echo 
echo 
echo "******************************************************"
echo 
echo "           " ${boldon}Neuroimaging Database Setup${reset}
echo 
echo "            (will be installed to ${NIDBROOT})"
echo 
echo ${redf}Maximize this terminal window to read all instructions${reset}
echo 
echo "******************************************************"
echo 
echo 
echo 
echo 

read -p "Press [enter] to continue"

# ---------- RPMforge respository ----------
echo "Adding the rpmforge respository"
rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY*
rpm --import http://dag.wieers.com/rpm/packages/RPM-GPG-KEY.dag.txt
wget http://pkgs.repoforge.org/rpmforge-release/rpmforge-release-0.5.2-2.el6.rf.x86_64.rpm
rpm -ivh rpmforge-release-0.5.2-2.el6.rf.x86_64.rpm

# ---------- yum based installs ----------
echo "${redb}${whitef}${boldon}----------------- Installing YUM based packages -----------------${reset}"

echo "------ Installing system utils -----"
yum install -y system* vnc* vim emacs

echo "------ Installing VNC ------"
yum install -y vnc*
echo "------ Installing perl ------"
yum install -y perl
yum install -y perl-File-Copy-Recursive
yum install -y perl-Sort-Naturally
yum install -y perl-Net-SMTP-TLS
yum install -y perl-Data-Dumper
#yum install -y perl-Image-ExifTool
yum install -y perl-Math-Round
yum install -y perl-Math-Derivative
yum install -y perl-Math-MatrixReal
yum install -y perl-Math-Combinatorics
yum install -y cpan
yum install -y perl-YAML
echo "------ Installing PHP ------"
#yum install -y php
#yum install -y php-mysql
yum install -y php-gd
yum install -y php-process
echo "------ Installing PHP ------"
yum install -y httpd
#yum install -y httpd*
#yum install -y httpd-*
echo "------ Installing mysql ------"
yum install -y mysql
#yum install -y mysql*
yum install -y mysql-bench
yum install -y mysql-server
echo "------ Installing ImageMagick ------"
#yum install -y ImageMagick
#yum install -y ImageMagick*
wget http://www.imagemagick.org/download/linux/CentOS/x86_64/ImageMagick-6.8.0-4.x86_64.rpm
rpm -U ImageMagick-6.8.0-4.x86_64.rpm
echo "------ Installing subversion ------"
yum install -y subversion*
echo "------ Installing gcc ------"
yum install -y gcc
yum install -y gcc-c++
echo "------ Installing gedit ------"
yum install -y gedit*
echo "------ Installing iptraf ------"
yum install -y iptraf*
echo "------ Installing java ------"
yum install -y java
#yum install -y java*
echo "------ Installing fftw ------"
yum install -y fftw*
echo "------ Installating audio/video codecs ------"
yum install -y vorbis-tools
#yum install -y vorbis*
yum install -y theora-tools
#yum install -y theora*
yum install -y ffmpeg
#yum install -y ffmpeg*

# --------- Perl/CPAN based installs ----------
echo "${redb}${whitef}${boldon}----------------- Installing Perl modules from CPAN -----------------${reset}"
cpan File::Copy
cpan File::Find
cpan File::Path
cpan List::Util
cpan Date::Parse
cpan Image::ExifTool

cp -r Mysql* /usr/lib64/perl5/

# compile ImageMagick with fft support
wget http://www.fftw.org/fftw-3.3.2.tar.gz
tar -xvzf fftw-3.3.2.tar.gz
cd fftw3
./configure CXXFLAGS=-fPIC CFLAGS=-fPIC
make
make install
cd ..
wget http://www.imagemagick.org/download/ImageMagick.tar.gz
tar -xvzf ImageMagick.tar.gz
cd ImageMagick
./configure --enable-hdri -with-fftw
make
make install

# ---------- configure system based services ----------
echo "${redb}${whitef}${boldon}----------------- Configuring system services -----------------${reset}"
echo "------ Disable SELinux ------"
setenforce 0
#echo "${blueb}${whitef}${boldon}SELinux is now temporarily disabled. To permanently disable it, the following must be done"
#echo "This step must be done manually. edit /etc/selinux/config and change SELINUX=enforcing to SELINUX=disabled${reset}"
sed -i 's/^SELINUX=.*/SELINUX=disabled/g' /etc/selinux/config
#read -p "Press [enter] to continue"
echo "------ Enabling services at boot ------"
chkconfig httpd on
chkconfig mysqld on
echo "------ Starting services ------"
service httpd start
service mysqld start


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


# ---------- create an nidb account ----------
echo "${redb}${whitef}${boldon}----------------- Creating nidb user account -----------------${reset}"
echo "Creating an nidb user account"
useradd -m -s /bin/bash nidb
# add nidb to sudoers file
echo "Adding nidb to the sudoers file"
chmod 777 /etc/sudoers
echo "nidb ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers
chmod 440 /etc/sudoers
# get the user to enter a password for the nidb account
echo "Enter the password for the nidb account"
passwd nidb


# ---------- Web based installs ----------
echo "${redb}${whitef}${boldon}----------------- Web based installs (Webmin, phpMyAdmin) -----------------${reset}"
echo "------ Installing Webmin... ------"
wget http://prdownloads.sourceforge.net/webadmin/webmin-1.600-1.noarch.rpm
rpm -U webmin-1.600-1.noarch.rpm

echo "${blueb}${whitef}${boldon}------ Manually configure PHP variables ------"
echo "Go to https://$HOSTNAME:10000"
echo "then go to Others -> PHP Configuration -> Manage -> Other Settings ... change PHP Timezone to your timezone"
echo "then go to Others -> PHP Configuration -> Manage -> Error Logging ... change Expression for error types = E_ALL & ~E_DEPRECATED & ~E_NOTICE${reset}"

sed -i 's/^short_open_tag = .*/short_open_tag = On/g' /etc/php.ini
sed -i 's/^session.gc_maxlifetime = .*/session.gc_maxlifetime = 28800/g' /etc/php.ini
sed -i 's/^memory_limit = .*/memory_limit = 1000M/g' /etc/php.ini
sed -i 's/^upload_tmp_dir = .*/upload_tmp_dir = \/${NIDBROOT}\/uploadtmp/g' /etc/php.ini
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 1000M/g' /etc/php.ini
sed -i 's/^max_input_time = .*/max_input_time = 360/g' /etc/php.ini
sed -i 's/^max_execution_time = .*/max_execution_time = 360/g' /etc/php.ini
sed -i 's/^post_max_size = .*/post_max_size = 1000M/g' /etc/php.ini
sed -i 's/^display_errors = .*/display_errors = On/g' /etc/php.ini
#sed -i 's/^error_reporting = .*/error_reporting = E_ALL \& \~E_DEPRECATED \& \~E_NOTICE/g' /etc/php.ini

read -p "Press [enter] to continue"

echo "------ Setting up MySQL database - default password is 'password' ------"
mysqladmin -u root password 'password'
echo "Assigning permissions to mysql root account"
echo "GRANT ALL PRIVILEGES on *.* to root@'%'" >> ~/tempsql.txt
mysql -uroot -ppassword < ~/tempsql.txt
rm ~/tempsql.txt

echo "------ Install phpMyAdmin ------"
wget http://downloads.sourceforge.net/project/phpmyadmin/phpMyAdmin/3.5.3/phpMyAdmin-3.5.3-english.zip
unzip phpMyAdmin-3.5.3-english.zip
mv phpMyAdmin-3.5.3-english /var/www/html/phpMyAdmin
chmod 777 /var/www/html
chown -R nidb:nidb /var/www/html
#echo "Edit the /var/www/html/phpMyAdmin/config.sample.inc.php file and add:"
#echo "      \$cfg['McryptDisableWarning'] = TRUE;"
#echo "      \$cfg['LoginCookieValidity'] = 28800;${reset}"
sed '$ i $cfg[''McryptDisableWarning''] = TRUE;' /var/www/html/phpMyAdmin/config.sample.inc.php;
sed '$ i $cfg[''LoginCookieValidity''] = 28800;' /var/www/html/phpMyAdmin/config.sample.inc.php;
#echo "Rename config.sample.inc.php to config.inc.php"
cp /var/www/html/phpMyAdmin/config.sample.inc.php /var/www/html/phpMyAdmin/config.inc.php
chmod 755 /var/www/html/phpMyAdmin/config.inc.php
echo "You should be able to see this" >> /var/www/html/index.php
echo "${blueb}${whitef}${boldon}Check to make sure you can see http://$HOSTNAME/index.php${reset}"
read -p "Press [enter] to continue"
echo "${blueb}${whitef}${boldon}phpMyAdmin installed, but must be configured"
echo "Go to http://$HOSTNAME/phpMyAdmin/setup to add the local DB server"
read -p "Press [enter] to continue"


# --------- install all nidb files and db ----------
echo "${redb}${whitef}${boldon}----------------- Copying nidb program/html files -----------------${reset}"
# copy all files to their final location
echo "Copying nidb program files"
mkdir ${NIDBROOT}
mkdir ${NIDBROOT}/archive
mkdir ${NIDBROOT}/backup
mkdir ${NIDBROOT}/dicomincoming
mkdir ${NIDBROOT}/download
mkdir ${NIDBROOT}/ftp
mkdir ${NIDBROOT}/incoming
mkdir ${NIDBROOT}/problem
mkdir ${NIDBROOT}/programs
mkdir ${NIDBROOT}/uploadtmp
cp -R ../programs/* ${NIDBROOT}/programs
chown -R nidb:nidb ${NIDBROOT}
# copy html files
cp -R ../html/* /var/www/html
chown -R nidb:nidb /var/www/html
# create default database from .sql file
echo "Creating default database"
mysql -uroot -ppassword -e "create database if not exists nidb; grant all on *.* to 'root'@'localhost' identified by 'password'; flush privileges;"
mysql -uroot -ppassword nidb < nidb.sql


# ---------- dcm4che ----------
echo "${redb}${whitef}${boldon}----------------- Installing DICOM receiver -----------------${reset}"
echo "Installing dcm4che receiver to listen on port 8104"
#echo "${blueb}${whitef}${boldon}Go to Webmin -> System -> Bootup and Shutdown"
#echo "     Click 'create a new bootup and shutdown action'"
#echo "     Enter the following and click 'Create'"
#echo "     Name: dcmrcv"
#echo "     Description: dcmrcv"
#echo "     Bootup commands: su nidb -c '${NIDBROOT}/programs/dcm4che/bin/./dcmrcv NIDB:8104 -dest ${NIDBROOT}/dicomincoming > /dev/null 2>&1 &'"
#echo "     Shutdown commands: "
#echo "     Start at boot time: Yes${reset}"
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
echo "${redb}${whitef}${boldon}----------------- Setup scheduled cron jobs -----------------${reset}"
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
echo "${redb}${whitef}${boldon}----------------- Remaining items to be done by you -----------------${reset}"
echo "${blueb}${whitef}${boldon}Install FSL to the default path [/usr/local/fsl]"
echo "Your default mysql account is root, password is 'password'. Change these as soon as possible"
echo "Edit /nidb/programs/config.pl to reflect the paths , usernames, and passwords of your installation${reset}"
echo "Edit /var/www/html/config.php to reflect your paths, usernames, and passwords"
echo "All processes specified by cron jobs are disabled. Go to Webmin -> System -> Scheduled Cron Jobs, to enable the cron jobs ${reset}"
