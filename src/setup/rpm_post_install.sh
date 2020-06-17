#!/bin/sh

# create link to the mariadb libraries (may or may not be necessary)
echo 'Create libmariadb link'
ln -sf /lib64/libmariadb.so.3 /lib64/libmysqlclient.so.18

# PHP packages
echo 'Install PHP packages'
pear install Mail Mail_Mime Net_SMTP

# disable SE Linux
echo 'Disable SE Linux'
setenforce 0
sed -i s/^SELINUX=.*/SELINUX=disabled/g /etc/selinux/config

# change php.ini settings
echo 'Change php.ini settings'
sed -i 's/^short_open_tag = .*/short_open_tag = On/g' /etc/php.ini
sed -i 's/^session.gc_maxlifetime = .*/session.gc_maxlifetime = 28800/g' /etc/php.ini
sed -i 's/^memory_limit = .*/memory_limit = 5000M/g' /etc/php.ini
sed -i 's!^;.*upload_tmp_dir = .*!upload_tmp_dir = /nidb/uploadtmp!g' /etc/php.ini
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 5000M/g' /etc/php.ini
sed -i 's/^max_file_uploads = .*/max_file_uploads = 1000/g' /etc/php.ini
sed -i 's/^;.*max_input_vars = .*/max_input_vars = 1000/g' /etc/php.ini # this line is probably commented out
sed -i 's/^max_input_time = .*/max_input_time = 600/g' /etc/php.ini
sed -i 's/^max_execution_time = .*/max_execution_time = 600/g' /etc/php.ini
sed -i 's/^post_max_size = .*/post_max_size = 5000M/g' /etc/php.ini
sed -i 's/^display_errors = .*/display_errors = On/g' /etc/php.ini
sed -i 's/^error_reporting = .*/error_reporting = E_ALL \& \~E_DEPRECATED \& \~E_STRICT \& \~E_NOTICE/' /etc/php.ini

# enable and start services
echo 'Enable and start services'
systemctl enable httpd.service   # enable the apache web service
systemctl enable mariadb.service # enable the MariaDB service
systemctl enable php-fpm.service # enable PHP-FastCGI Process Manager service
systemctl start httpd.service
systemctl start mariadb.service
systemctl start php-fpm.service

# make sure port 80 is accessible through the firewall
echo 'Add port 80 to firewall'
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --reload

# create nidb user if it does not exist, add nidb to the apache group, and apache to the nidb group
echo 'Add nidb user'
id -u nidb &>/dev/null || useradd -p $(openssl passwd -1 password) nidb
usermod -G apache nidb
usermod -G nidb apache

# change permissions of the /nidb directory
echo 'Change permissions and ownership'
chown -R nidb:nidb /nidb  # change ownership of the install directory
chmod -R g+w /nidb        # change permissions of the install directorys contents
chmod 777 /nidb              # change permissions of the install directory

# setup cron jobs
echo 'Install crontab'
crontab -u nidb /nidb/setup/crontab.txt

# database stuff
echo 'Set root MariaDB password'
mysqladmin -uroot password password # set the root password
echo 'Create MariaDB nidb account'
mysql -uroot -ppassword -e "CREATE USER IF NOT EXISTS 'nidb'@'%' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON *.* TO 'nidb'@'%'; FLUSH PRIVILEGES;"

# add dcmrcv service at boot
echo 'dcmrcv'
cp /nidb/setup/dcmrcv /etc/init.d  # copy the dcmrcv init script
chmod 755 /etc/init.d/dcmrcv # change permissions of the script
chkconfig --add dcmrcv       # add the script to start at boot

# create data directories
echo 'Create data directories and change owner'
mkdir -p /nidb/data
mkdir -p /nidb/data/archive
mkdir -p /nidb/data/backup
mkdir -p /nidb/data/deleted
mkdir -p /nidb/data/dicomincoming
mkdir -p /nidb/data/download
mkdir -p /nidb/data/ftp
mkdir -p /nidb/data/problem
mkdir -p /nidb/data/tmp
mkdir -p /nidb/data/upload
chown -R nidb:nidb /var/www/html
