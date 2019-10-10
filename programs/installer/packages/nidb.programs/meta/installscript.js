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
    gui.pageWidgetByObjectName("LicenseAgreementPage").entered.connect(changeLicenseLabels);
}

changeLicenseLabels = function()
{
    page = gui.pageWidgetByObjectName("LicenseAgreementPage");
	page.AcceptLicenseLabel.setText("Yes");
    page.RejectLicenseLabel.setText("No");
}

Component.prototype.createOperations = function()
{
    component.createOperations();
	
    /* create program directories */
	component.addOperation("Mkdir", "/nidb/programs/logs");
	component.addOperation("Mkdir", "/nidb/programs/lock");

    /* create data directories */
	component.addOperation("Mkdir", "/nidb/data");
	component.addOperation("Mkdir", "/nidb/data/archive");
	component.addOperation("Mkdir", "/nidb/data/backup");
	component.addOperation("Mkdir", "/nidb/data/deleted");
	component.addOperation("Mkdir", "/nidb/data/dicomincoming");
	component.addOperation("Mkdir", "/nidb/data/download");
	component.addOperation("Mkdir", "/nidb/data/ftp");
	component.addOperation("Mkdir", "/nidb/data/problem");
	component.addOperation("Mkdir", "/nidb/data/tmp");
	component.addOperation("Mkdir", "/nidb/data/upload");


}

