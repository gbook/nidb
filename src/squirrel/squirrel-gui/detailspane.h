/* ------------------------------------------------------------------------------
  squirrel-gui detailspane.h
  Right-hand side of the main window: breadcrumb, title, Extract button and the
  tabs for the selected object.

    package          Overview, README, Changes
    group            Overview
    subject          Overview, Observations, Interventions
    study            Overview, Analyses
    series           Overview, Image, Parameters, Files
    experiment etc.  Overview

  The Files and Image tabs load on demand through the package worker
  (FilesNeeded / ImagesNeeded); MainWindow passes the results back.
  ------------------------------------------------------------------------------ */

#ifndef DETAILSPANE_H
#define DETAILSPANE_H

#include <QWidget>

#include "packagesnapshot.h"
#include "packageworker.h"

class ImageViewer;
class PropertyGrid;
class QLabel;
class QLineEdit;
class QPushButton;
class QTabWidget;
class QTableWidget;

class DetailsPane : public QWidget {
    Q_OBJECT
public:
    explicit DetailsPane(QWidget *parent = nullptr);

    void SetSnapshot(SnapshotPtr snap);
    void ShowNode(int node);
    int CurrentNode() const { return node; }

    void SetFiles(int node, const QStringList &files, const QString &error);
    void SetImages(int node, const SliceList &slices, const QString &error);
    void ShowImageTab();

signals:
    void NodeActivated(int node);
    void ExtractRequested(int node);
    void FilesNeeded(int node, const QString &virtualPath);
    void ImagesNeeded(int node, const QString &virtualPath);

private:
    void ClearTabs();
    void AddTab(QWidget *page, const QString &label, const QString &id, bool dynamic);
    QWidget *TablePage(const NodeTable &t);
    QWidget *TextPage(const QString &text, const QString &emptyText);
    void FillTable(QTableWidget *table, const NodeTable &t);
    void FillParams();
    void OnTabChanged(int index);
    QString CurrentTabId() const;

    SnapshotPtr snap;
    int node = -1;

    QLabel *crumbs;
    QLabel *title;
    QLabel *subtitle;
    QPushButton *extractButton;
    QTabWidget *tabs;
    QList<QWidget *> dynamicPages;
    QString lastSeriesTab = "overview";

    /* overview */
    QWidget *overviewPage;
    PropertyGrid *grid;
    QLabel *childTitle;
    QTableWidget *childTable;
    QLabel *childEmpty;
    QList<int> childRowNodes;

    /* parameters */
    QWidget *paramsPage;
    QLineEdit *paramsFilter;
    QLabel *paramsCount;
    QTableWidget *paramsTable;

    /* files */
    QWidget *filesPage;
    QLabel *filesInfo;
    QTableWidget *filesTable;
    int filesNode = -1;

    /* image */
    ImageViewer *viewer;
    int imagesNode = -1;
};

#endif // DETAILSPANE_H
