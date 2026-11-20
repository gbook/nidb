/* ------------------------------------------------------------------------------
  Squirrel GUI
  Copyright (C) 2004 - 2026
  Gregory A Book <gregory.a.book@gmail.com>
  Olin Neuropsychiatry Research Center, Hartford Hospital
  ------------------------------------------------------------------------------
  GPLv3 License:

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
  ------------------------------------------------------------------------------ */

/* ------------------------------------------------------------------------------
  squirrel-gui: a view-only browser for squirrel packages, with a DICOM viewer
  and "Extract to…" for any object.

  Usage: squirrel-gui [package.sqrl ...]   (each package opens in its own window)
  ------------------------------------------------------------------------------ */

#include <QApplication>
#include <QCommandLineParser>
#include <QIcon>

#include "dicomslice.h"
#include "mainwindow.h"
#include "packageworker.h"
#include "settings.h"
#include "theme.h"

int main(int argc, char *argv[]) {
    QApplication app(argc, argv);
    QApplication::setOrganizationName("squirrel");
    QApplication::setApplicationName("squirrel-gui");
    QApplication::setApplicationDisplayName("Squirrel");
    QApplication::setWindowIcon(QIcon(":/resources/squirrel.png"));

    qRegisterMetaType<SnapshotPtr>("SnapshotPtr");
    qRegisterMetaType<ExtractRequest>("ExtractRequest");
    qRegisterMetaType<SliceList>("SliceList");

    RegisterDicomCodecs();
    Theme::Apply(Theme::ModeFromString(Settings::ThemeName()));

    QCommandLineParser parser;
    parser.setApplicationDescription("Browse squirrel data packages");
    parser.addHelpOption();
    parser.addPositionalArgument("packages", "Packages to open, each in its own window", "[package.sqrl ...]");
    parser.process(app);

    const QStringList files = parser.positionalArguments();
    if (files.isEmpty()) {
        auto *w = new MainWindow;
        w->show();
    }
    for (const QString &f : files) {
        auto *w = new MainWindow;
        w->show();
        w->OpenPackage(f);
    }
    return app.exec();
}
