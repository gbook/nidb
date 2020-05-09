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
	
    /* add nidb to the apache group, and apache to the nidb group */
    component.addElevatedOperation("Execute", "{0}", "usermod", "-G", "apache", "nidb");
    component.addElevatedOperation("Execute", "{0}", "usermod", "-G", "nidb", "apache");

    /* change permissions of the /nidb directory */
    component.addElevatedOperation("Execute", "{0}", "chown", "-R", "nidb:nidb", "/nidb");  /* change ownership of the install directory */
    component.addElevatedOperation("Execute", "{0}", "chmod", "-R", "g+w", "/nidb");        /* change permissions of the install directory's contents */
    component.addElevatedOperation("Execute", "{0}", "chmod", "777", "/nidb");              /* change permissions of the install directory */

    /* setup cron jobs */
    component.addElevatedOperation("Execute", "{0}", "crontab", "-u", "nidb", "/nidb/crontab.txt");

    /* database stuff */
    component.addElevatedOperation("Execute", "{0}", "chmod", "777", "/nidb/mysql.sh");
    component.addElevatedOperation("Execute", "{0}", "/nidb/mysql.sh");

    /* add dcmrcv service at boot */
    component.addElevatedOperation("Execute", "{0}", "cp", "/nidb/dcmrcv", "/etc/init.d");  /* copy the dcmrcv init script */
    component.addElevatedOperation("Execute", "{0}", "chmod", "755", "/etc/init.d/dcmrcv"); /* change permissions of the script */
    component.addElevatedOperation("Execute", "{0}", "chkconfig", "--add", "dcmrcv");       /* add the script to start at boot */

    /* add 'nidb' user to the apache group */
    //component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/User apache.*/User nidb/", "/etc/httpd/conf/httpd.conf");
    //component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/Group apache.*/Group nidb/", "/etc/httpd/conf/httpd.conf");
    //component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^user = .*/user = nidb/", "/etc/php-fpm.d/www.conf");
    //component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^group = .*/group = nidb/", "/etc/php-fpm.d/www.conf");
    //component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/.*listen.owner = .*/listen.owner = nidb/", "/etc/php-fpm.d/www.conf");
    //component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/.*listen.group = .*/listen.group = nidb/", "/etc/php-fpm.d/www.conf");
    //component.addElevatedOperation("Execute", "{0}", "chown", "-R", "nidb:nidb", "/var/lib/php/session");
    //component.addElevatedOperation("Execute", "{0}", "chmod", "777", "/var/lib/php/session");
    //component.addElevatedOperation("Execute", "{0}", "chown", "nidb:nidb", "/run/php-fpm/www.sock");
    //component.addElevatedOperation("Execute", "{0}", "chmod", "777", "/run/php-fpm/www.sock");
    //component.addElevatedOperation("Execute", "{0}", "systemctl", "restart", "httpd.service");      /* restart the apache web server */
    //component.addElevatedOperation("Execute", "{0}", "systemctl", "restart", "php-fpm.service");    /* restart the php-fpm (FastCGI process manager) service */
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
