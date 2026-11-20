/* ------------------------------------------------------------------------------
  squirrel-gui packagesnapshot.h
  Plain, copyable description of an open package for the GUI thread.

  The squirrel object lives on the worker thread (it owns a QSqlDatabase
  connection, which Qt ties to the thread that opened it). The worker walks the
  package once after Read() and copies everything the GUI shows into these
  structs. The GUI never holds pointers into library objects.
  ------------------------------------------------------------------------------ */

#ifndef PACKAGESNAPSHOT_H
#define PACKAGESNAPSHOT_H

#include <QList>
#include <QMetaType>
#include <QPair>
#include <QSharedPointer>
#include <QString>
#include <QStringList>
#include <QVector>

class squirrel;

typedef QList<QPair<QString, QString>> PropList;

/* kind of node in the package tree. Scoped, because squirrelTypes.h declares
   an unscoped ObjectType enum with values named Subject, Series, ... */
enum class NodeKind {
    Package, Group,
    Subject, Study, Series,
    Experiment, Pipeline, GroupAnalysis, DataDictionary
};

/* a simple table shown in the details pane (child objects, observations, ...) */
struct NodeTable {
    QString title;
    QStringList columns;
    QList<QStringList> rows;
    QList<int> rowNodes;   /* node index each row links to, or -1 */
    QString emptyText;     /* shown when there are no rows */
};

/* what "Extract to..." does for a node */
struct ExtractPlan {
    bool possible = false;
    bool wholePackage = false;      /* squirrel::Extract() */
    int objectType = -1;            /* ObjectType for ExtractObject() */
    QList<qint64> rowIDs;           /* one, or one per child for a group */
    QStringList subdirs;            /* per rowID; empty = extract directly into the destination */
    QString title;                  /* "Series 4 SpinEchoFieldMap_RL" */
    QString detail;                 /* "72 files (14.6 MB) and series.json" */
    QString folderName;             /* default new-folder name */
};

struct PackageNode {
    NodeKind kind = NodeKind::Package;
    QString groupId;                /* Group nodes: "subjects", "experiments", ... */
    qint64 rowID = -1;
    int parent = -1;
    QList<int> children;

    QString label;                  /* tree text */
    QString meta;                   /* tree secondary text */
    QString title;                  /* details header */
    QString subtitle;
    QStringList crumbs;             /* breadcrumb labels, root first */
    QString virtualPath;            /* path inside the archive, if it has files */

    PropList props;                 /* overview property grid */
    NodeTable childTable;           /* overview table of contents */
    QList<NodeTable> extraTabs;     /* observations, interventions, analyses */
    QString readme;                 /* package only */
    QString changes;                /* package only */

    /* series only */
    PropList params;                /* params.json, sorted by key */
    qint64 fileCount = 0;
    qint64 size = 0;
    int studyNode = -1;             /* parent study, for the viewer's series list */

    ExtractPlan extract;
};

struct PackageSnapshot {
    QString path;
    QString name;
    QString dataFormat;
    qint64 unzippedSize = 0;
    int subjectCount = 0;
    int studyCount = 0;
    int seriesCount = 0;
    QVector<PackageNode> nodes;    /* nodes[0] is the package root */

    QString StatusSummary() const;
};

typedef QSharedPointer<const PackageSnapshot> SnapshotPtr;
Q_DECLARE_METATYPE(SnapshotPtr)

/* builds a snapshot from a package that has been Read(). Must run on the
   thread that owns sqrl. */
SnapshotPtr BuildSnapshot(squirrel *sqrl);

QString HumanSize(qint64 bytes);
QString HumanizeKey(const QString &key);

#endif // PACKAGESNAPSHOT_H
