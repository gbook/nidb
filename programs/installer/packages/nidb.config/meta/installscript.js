/****************************************************************************
**
** Copyright (C) 2017 The Qt Company Ltd.
** Contact: https://www.qt.io/licensing/
**
** This file is part of the FOO module of the Qt Toolkit.
**
** $QT_BEGIN_LICENSE:GPL-EXCEPT$
** Commercial License Usage
** Licensees holding valid commercial Qt licenses may use this file in
** accordance with the commercial license agreement provided with the
** Software or, alternatively, in accordance with the terms contained in
** a written agreement between you and The Qt Company. For licensing terms
** and conditions see https://www.qt.io/terms-conditions. For further
** information use the contact form at https://www.qt.io/contact-us.
**
** GNU General Public License Usage
** Alternatively, this file may be used under the terms of the GNU
** General Public License version 3 as published by the Free Software
** Foundation with exceptions as appearing in the file LICENSE.GPL3-EXCEPT
** included in the packaging of this file. Please review the following
** information to ensure the GNU General Public License requirements will
** be met: https://www.gnu.org/licenses/gpl-3.0.html.
**
** $QT_END_LICENSE$
**
****************************************************************************/

function Component()
{
}

Component.prototype.createOperations = function()
{
    component.createOperations();
	
    /* change permissions of the /nidb directory */
    component.addElevatedOperation("Execute", "{0}", "chown", "-R", "nidb:nidb", "/nidb");

    /* setup cron jobs */
    component.addElevatedOperation("Execute", "{0}", "crontab", "-u", "nidb", "/nidb/crontab.txt");

    /* database stuff */
    component.addElevatedOperation("Execute", "{0}", "mysqladmin", "-u", "root", "password", "password");
    component.addElevatedOperation("Execute", "{0}", "mysql", "-uroot", "-ppassword", "-e", "create database if not exists nidb; grant all on *.* to 'root'@'localhost' identified by 'password'; flush privileges;");

    /* secure the installation */
    //mysql -u root -p"$DATABASE_PASS" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1')"
    //mysql -u root -p"$DATABASE_PASS" -e "DELETE FROM mysql.user WHERE User=''"
    //mysql -u root -p"$DATABASE_PASS" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%'"
    //mysql -u root -p"$DATABASE_PASS" -e "FLUSH PRIVILEGES"

    component.addElevatedOperation("Execute", "{0}", "mysql", "-uroot", "-ppassword", "nidb", "<", "/nidb/nidb.sql");
    component.addElevatedOperation("Execute", "{0}", "mysql", "-uroot", "-ppassword", "nidb", "<", "/nidb/nidb-data.sql");
    //component.addOperation("AppendFile", "/nidb/tempsql.txt", "CREATE USER 'nidb'\@'%' identified by 'password';\nGRANT ALL PRIVILEGES on *.* to 'nidb'\@'%';\n");
    component.addElevatedOperation("Execute", "{0}", "mysql", "-uroot", "-ppassword", "-e", "CREATE USER 'nidb'\@'%' identified by 'password';\nGRANT ALL PRIVILEGES on *.* to 'nidb'\@'%';");
    //component.addElevatedOperation("Execute", "{0}", "mysql", "-uroot", "-ppassword", "<", "/nidb/tempsql.txt");

    /* add dcmrcv service at boot */
    component.addElevatedOperation("Execute", "cp", "/nidb/dcmrcv", "/etc/init.d");
    component.addElevatedOperation("Execute", "chmod", "755", "/etc/init.d/dcmrcv");
    component.addElevatedOperation("Execute", "chkconfig", "--add", "dcmrcv");

    /* make Apache run as the 'nidb' user */
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/User apache/User nidb/", "/etc/httpd/conf/httpd.conf");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/Group apache/Group nidb/", "/etc/httpd/conf/httpd.conf");
    component.addElevatedOperation("Execute", "{0}", "chown", "-R", "nidb:nidb", "/var/lib/php/session");
    component.addElevatedOperation("Execute", "{0}", "chmod", "777", "/var/lib/php/session");
    component.addElevatedOperation("Execute", "{0}", "chown", "nidb:nidb", "/run/php-fpm/www.sock");
    component.addElevatedOperation("Execute", "{0}", "systemctl", "restart", "httpd.service");

}
