/* ------------------------------------------------------------------------------
  squirrel-gui settings.cpp
  ------------------------------------------------------------------------------ */

#include "settings.h"

#include <QDir>
#include <QSettings>
#include <QVariantMap>

namespace {
const int maxRecent = 10;
}

namespace Settings {

QList<RecentPackage> Recent() {
    QSettings s;
    QList<RecentPackage> list;
    for (const QVariant &v : s.value("recent").toList()) {
        const QVariantMap m = v.toMap();
        RecentPackage r;
        r.path = m.value("path").toString();
        r.name = m.value("name").toString();
        r.summary = m.value("summary").toString();
        r.opened = m.value("opened").toDateTime();
        if (!r.path.isEmpty())
            list.append(r);
    }
    return list;
}

static void Save(const QList<RecentPackage> &list) {
    QVariantList out;
    for (const RecentPackage &r : list) {
        QVariantMap m;
        m["path"] = r.path;
        m["name"] = r.name;
        m["summary"] = r.summary;
        m["opened"] = r.opened;
        out.append(m);
    }
    QSettings().setValue("recent", out);
}

void AddRecent(const RecentPackage &r) {
    QList<RecentPackage> list = Recent();
    for (int i = list.size() - 1; i >= 0; i--)
        if (list[i].path == r.path)
            list.removeAt(i);
    list.prepend(r);
    while (list.size() > maxRecent)
        list.removeLast();
    Save(list);
}

void RemoveRecent(const QString &path) {
    QList<RecentPackage> list = Recent();
    for (int i = list.size() - 1; i >= 0; i--)
        if (list[i].path == path)
            list.removeAt(i);
    Save(list);
}

void ClearRecent() { Save({}); }

QString ThemeName() { return QSettings().value("theme", "system").toString(); }
void SetThemeName(const QString &name) { QSettings().setValue("theme", name); }

QString LastExtractDir() { return QSettings().value("lastExtractDir", QDir::homePath() + "/squirrel-extract").toString(); }
void SetLastExtractDir(const QString &dir) { QSettings().setValue("lastExtractDir", dir); }

QString LastOpenDir() { return QSettings().value("lastOpenDir", QDir::homePath()).toString(); }
void SetLastOpenDir(const QString &dir) { QSettings().setValue("lastOpenDir", dir); }

} // namespace Settings
