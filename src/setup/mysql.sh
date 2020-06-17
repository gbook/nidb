#!/bin/sh

# set the root password
mysqladmin -uroot password password

# secure the installation
mysql -u root -ppassword -e "DELETE FROM mysql.user WHERE User=''"
mysql -u root -ppassword -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%'"
mysql -u root -ppassword -e "FLUSH PRIVILEGES"

# create database and nidb user
mysql -uroot -ppassword -e "CREATE DATABASE IF NOT EXISTS nidb; GRANT ALL ON *.* TO 'root'@'localhost' IDENTIFIED BY 'password'; FLUSH PRIVILEGES;"
mysql -uroot -ppassword -e "CREATE USER IF NOT EXISTS 'nidb'@'%' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON *.* TO 'nidb'@'%'; FLUSH PRIVILEGES;"