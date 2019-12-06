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
	
    /* yum packages */
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "php");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "php-mysqlnd");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "php-gd");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "php-cli");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "php-process");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "php-pear");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "php-mbstring");
    component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "php-fpm");
    component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "mariadb");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "mariadb-common");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "mariadb-server");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "mariadb-server-utils");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "mariadb-connector-c-devel");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "mariadb-connector-c");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "mariadb-connector-c-config");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "mariadb-backup");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "httpd");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "epel-release");
	component.addElevatedOperation("Execute", "{0,100}", "yum", "install", "-y", "ImageMagick");

    /* PHP packages */
	component.addElevatedOperation("Execute", "{0,1}", "pear", "install", "Mail", "Mail_Mime", "Net_SMTP");

    /* disable SE Linux */
    component.addElevatedOperation("Execute", "{0}", "setenforce", "0");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^SELINUX=.*/SELINUX=disabled/g", "/etc/selinux/config");

    /* change php.ini settings */
	component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^short_open_tag = .*/short_open_tag = On/g", "/etc/php.ini");
	component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^session.gc_maxlifetime = .*/session.gc_maxlifetime = 28800/g", "/etc/php.ini");
	component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^memory_limit = .*/memory_limit = 5000M/g", "/etc/php.ini");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s!^;.*upload_tmp_dir = .*!upload_tmp_dir = /nidb/uploadtmp!g", "/etc/php.ini");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^upload_max_filesize = .*/upload_max_filesize = 5000M/g", "/etc/php.ini");
	component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^max_file_uploads = .*/max_file_uploads = 1000/g", "/etc/php.ini");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^;.*max_input_vars = .*/max_input_vars = 1000/g", "/etc/php.ini"); /* this line is probably commented out */
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^max_input_time = .*/max_input_time = 600/g", "/etc/php.ini");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^max_execution_time = .*/max_execution_time = 600/g", "/etc/php.ini");
	component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^post_max_size = .*/post_max_size = 5000M/g", "/etc/php.ini");
	component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^display_errors = .*/display_errors = On/g", "/etc/php.ini");
    component.addElevatedOperation("Execute", "{0}", "sed", "-i", "s/^error_reporting = .*/error_reporting = E_ALL \\& \\~E_DEPRECATED \\& \\~E_STRICT \\& \\~E_NOTICE/", "/etc/php.ini");

    /* enable and start services */
    component.addElevatedOperation("Execute", "{0}", "systemctl", "enable", "httpd.service");   /* enable the apache web service */
    component.addElevatedOperation("Execute", "{0}", "systemctl", "enable", "mariadb.service"); /* enable the MariaDB service */
    component.addElevatedOperation("Execute", "{0}", "systemctl", "enable", "php-fpm.service"); /* enable PHP-FastCGI Process Manager service */
    component.addElevatedOperation("Execute", "{0}", "systemctl", "start", "httpd.service");
    component.addElevatedOperation("Execute", "{0}", "systemctl", "start", "mariadb.service");
    component.addElevatedOperation("Execute", "{0}", "systemctl", "start", "php-fpm.service");

    /* make sure port 80 is accessible through the firewall */
    component.addElevatedOperation("Execute", "{0}", "firewall-cmd", "--permanent", "--add-port=80/tcp");
    component.addElevatedOperation("Execute", "{0}", "firewall-cmd", "--reload");
}
