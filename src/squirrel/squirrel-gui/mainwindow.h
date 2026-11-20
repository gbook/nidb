/* ------------------------------------------------------------------------------
  squirrel-gui mainwindow.h
  One package per window. Opening a package in a window that already has one
  opens a new window.
  ------------------------------------------------------------------------------ */

#ifndef MAINWINDOW_H
#define MAINWINDOW_H

#include <QMainWindow>
#include <QPointer>

#include "packagesnapshot.h"
#include "packageworker.h"

class DetailsPane;
class ExtractDialog;
class LoadingDialog;
class QAction;
class QActionGroup;
class QLabel;
class QLineEdit;
class QMenu;
class QPlainTextEdit;
class QSortFilterProxyModel;
class QStackedWidget;
class QStandardItemModel;
class QThread;
class QTimer;
class QTreeView;
class StartPage;

class MainWindow : public QMainWindow {
    Q_OBJECT
public:
    explicit MainWindow(QWidget *parent = nullptr);
    ~MainWindow() override;

    void OpenPackage(const QString &path);      /* in this window if empty, else a new one */

protected:
    void closeEvent(QCloseEvent *e) override;
    void dragEnterEvent(QDragEnterEvent *e) override;
    void dragLeaveEvent(QDragLeaveEvent *e) override;
    void dropEvent(QDropEvent *e) override;

private:
    void BuildActions();
    void BuildMenus();
    void BuildToolbar();
    void BuildCentral();
    void BuildLogDock();
    void StartWorker();
    void StopWorker();

    void ChooseAndOpen();
    void LoadHere(const QString &path);
    void ClosePackage();
    void OnOpened(SnapshotPtr snapshot);
    void OnOpenFailed(const QString &message);
    void PopulateTree();
    void SelectNode(int node);
    int SelectedNode() const;
    void ShowTreeMenu(const QPoint &pos);
    void StartExtract(int node);
    void PollLog();
    void UpdateRecentMenu();
    void UpdateActions();
    void SetTheme(const QString &name);

    /* worker */
    QThread *thread = nullptr;
    PackageWorker *worker = nullptr;
    QTimer *logTimer;
    bool loading = false;
    bool loadCancelled = false;
    QString loadingPath;
    QPointer<LoadingDialog> loadingDialog;
    QPointer<ExtractDialog> extractDialog;

    SnapshotPtr snap;

    /* widgets */
    QStackedWidget *stack;
    StartPage *startPage;
    QWidget *browser;
    QWidget *treePanel;
    QTreeView *tree;
    QStandardItemModel *model;
    QSortFilterProxyModel *proxy;
    DetailsPane *details;
    QLineEdit *filter;
    QPlainTextEdit *logView;
    QLabel *statusPath;
    QLabel *statusCounts;
    QLabel *statusSize;

    /* actions */
    QAction *openAction, *closeAction, *extractAction, *quitAction;
    QAction *validateAction, *infoAction, *treeAction, *aboutAction;
    QActionGroup *themeGroup;
    QMenu *recentMenu;
};

#endif // MAINWINDOW_H
