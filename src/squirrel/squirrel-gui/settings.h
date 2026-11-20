/* ------------------------------------------------------------------------------
  squirrel-gui settings.h
  Persistent settings (QSettings): recent packages, theme, last extract folder.
  ------------------------------------------------------------------------------ */

#ifndef SETTINGS_H
#define SETTINGS_H

#include <QDateTime>
#include <QList>
#include <QString>

struct RecentPackage {
    QString path;
    QString name;
    QString summary;      /* "3 subjects · 1.84 GB" */
    QDateTime opened;
};

namespace Settings {

QList<RecentPackage> Recent();
void AddRecent(const RecentPackage &r);
void RemoveRecent(const QString &path);
void ClearRecent();

QString ThemeName();
void SetThemeName(const QString &name);

QString LastExtractDir();
void SetLastExtractDir(const QString &dir);

QString LastOpenDir();
void SetLastOpenDir(const QString &dir);

} // namespace Settings

#endif // SETTINGS_H
