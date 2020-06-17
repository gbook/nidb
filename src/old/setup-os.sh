#!/bin/sh
# if you change the default path, NiDB is not guaranteed to work correctly
NIDBROOT="/nidb"
WWWROOT="/var/www/html"
NIDBUSER="nidb"
MYSQLUSER="nidb"
MYSQLPASS="password"
MYSQLROOTPASS="password"

# disabling SELINUX
setenforce 0
sed -i 's/^SELINUX=.*/SELINUX=disabled/g' /etc/selinux/config

# change php.ini settings
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
cp /var/www/html/phpMyAdmin/config.sample.inc.php /var/www/html/phpMyAdmin/config.inc.php
chmod 755 /var/www/html/phpMyAdmin/config.inc.php
echo "You should be able to see this" >> /var/www/html/index.php
echo "Check to make sure you can see http://$HOSTNAME/index.php"
read -p "Press [enter] to continue"

# create default database from .sql file
echo "Creating default database"
cd ${NIDBROOT}/install/setup
mysql -uroot -ppassword -e "create database if not exists nidb; grant all on *.* to 'root'@'localhost' identified by '${MYSQLROOTPASS}'; flush privileges;"
mysql -uroot -ppassword nidb < nidb.sql
mysql -uroot -ppassword nidb < nidb-data.sql

# add dcmrcv service at boot
cp ${NIDBROOT}/install/programs/dcmrcv /etc/init.d
sed -i "s/su nidb/su $NIDBUSER/" /etc/init.d/dcmrcv
chmod 755 /etc/init.d/dcmrcv
chkconfig --add dcmrcv

# setup cron jobs
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
