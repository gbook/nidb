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
    installer.installationFinished.connect(this, Component.prototype.installationFinishedPageIsShown);
    installer.finishButtonClicked.connect(this, Component.prototype.installationFinished);
}

Component.prototype.createOperations = function()
{
    component.createOperations();
	
    /* change permissions of the /nidb directory */
    component.addElevatedOperation("Execute", "{0}", "chown", "-R", "nidb:nidb", "/nidb");

    /* setup cron jobs */
    component.addElevatedOperation("Execute", "{0}", "crontab", "-u", "nidb", "/nidb/crontab.txt");

    /* database stuff */
    if (component.addElevatedOperation("Execute", "{0}", "mysqladmin", "-u", "root", "password", "password")) {
        var result = QMessageBox.question("dbpassword.notice", "Installer", "MariaDB appears to already have the root password set, skipping creation of root account", QMessageBox.Ok);
    }
    component.addElevatedOperation("Execute", "{0}", "mysql", "-uroot", "-ppassword", "-e", "CREATE DATABASE IF NOT EXISTS nidb; GRANT ALL ON *.* TO 'root'@'localhost' IDENTIFIED BY 'password'; FLUSH PRIVILEGES;");

    /* secure the installation */
    //mysql -u root -p"$DATABASE_PASS" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1')"
    //mysql -u root -p"$DATABASE_PASS" -e "DELETE FROM mysql.user WHERE User=''"
    //mysql -u root -p"$DATABASE_PASS" -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%'"
    //mysql -u root -p"$DATABASE_PASS" -e "FLUSH PRIVILEGES"

    //component.addElevatedOperation("Execute", "{0}", "mysql", "-uroot", "-ppassword", "nidb", "<", "/nidb/nidb.sql");
    //component.addElevatedOperation("Execute", "{0}", "mysql", "-uroot", "-ppassword", "nidb", "<", "/nidb/nidb-data.sql");
    component.addElevatedOperation("Execute", "{0}", "mysql", "-uroot", "-ppassword", "-e", "CREATE USER IF NOT EXISTS 'nidb'@'%' IDENTIFIED BY 'password';GRANT ALL PRIVILEGES ON *.* TO 'nidb'@'%';");

    /* add dcmrcv service at boot */
    component.addElevatedOperation("Execute", "{0}", "cp", "/nidb/dcmrcv", "/etc/init.d");
    component.addElevatedOperation("Execute", "{0}", "chmod", "755", "/etc/init.d/dcmrcv");
    component.addElevatedOperation("Execute", "{0}", "chkconfig", "--add", "dcmrcv");

    /* make Apache run as the 'nidb' user */
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/User apache.*/User nidb/", "/etc/httpd/conf/httpd.conf");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/Group apache.*/Group nidb/", "/etc/httpd/conf/httpd.conf");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/user = .*/user = nidb/", "/etc/php-fpm.d/www.conf");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/group = .*/group = nidb/", "/etc/php-fpm.d/www.conf");
    component.addElevatedOperation("Execute", "{0}", "chown", "-R", "nidb:nidb", "/var/lib/php/session");
    component.addElevatedOperation("Execute", "{0}", "chmod", "777", "/var/lib/php/session");
    component.addElevatedOperation("Execute", "{0}", "chown", "nidb:nidb", "/run/php-fpm/www.sock");
    component.addElevatedOperation("Execute", "{0}", "systemctl", "restart", "httpd.service");
    component.addElevatedOperation("Execute", "{0}", "systemctl", "restart", "php-fpm.service");
}

Component.prototype.installationFinishedPageIsShown = function()
{
    try {
        //if (installer.isInstaller() && installer.status == QInstaller.Success) {
            installer.addWizardPageItem( component, "FinishCheckBoxForm", QInstaller.InstallationFinished );
        //}
    } catch(e) {
        console.log(e);
    }
}

Component.prototype.installationFinished = function()
{
    try {
        //if (installer.isInstaller() && installer.status == QInstaller.Success) {
            var isFinishCheckBoxChecked = component.userInterface( "FinishCheckBoxForm" ).finishCheckBox.checked;
            if (isFinishCheckBoxChecked) {
                QDesktopServices.openUrl("http://localhost/setup.php");
            }
        //}
    } catch(e) {
        console.log(e);
    }
}
