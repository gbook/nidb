/* ------------------------------------------------------------------------------
  squirrel-gui mainwindow.cpp
  ------------------------------------------------------------------------------ */

#include "mainwindow.h"

#include <QAction>
#include <QActionGroup>
#include <QApplication>
#include <QClipboard>
#include <QCloseEvent>
#include <QDateTime>
#include <QDir>
#include <QTime>
#include <QDockWidget>
#include <QDragEnterEvent>
#include <QFileDialog>
#include <QFileInfo>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QLabel>
#include <QLineEdit>
#include <QMenu>
#include <QMenuBar>
#include <QMessageBox>
#include <QMimeData>
#include <QPlainTextEdit>
#include <QSortFilterProxyModel>
#include <QSplitter>
#include <QStackedWidget>
#include <QStandardItemModel>
#include <QStatusBar>
#include <QThread>
#include <QTimer>
#include <QToolBar>
#include <QTreeView>
#include <QVBoxLayout>
#include <functional>

#include "detailspane.h"
#include "extractdialog.h"
#include "icons.h"
#include "loadingdialog.h"
#include "settings.h"
#include "startpage.h"
#include "theme.h"
#include "squirrelVersion.h"

namespace {
const int NodeRole = Qt::UserRole + 1;
const int MetaRole = Qt::UserRole + 2;

bool IsPackageFile(const QString &path) {
    return path.endsWith(".sqrl", Qt::CaseInsensitive) || path.endsWith(".zip", Qt::CaseInsensitive) || path.endsWith(".7z", Qt::CaseInsensitive);
}
} // namespace

/* ------------------------------------------------------------ */
MainWindow::MainWindow(QWidget *parent) : QMainWindow(parent) {
    setAttribute(Qt::WA_DeleteOnClose);
    setWindowTitle("Squirrel");
    setAcceptDrops(true);
    resize(1440, 900);

    BuildActions();
    BuildMenus();
    BuildToolbar();
    BuildCentral();
    BuildLogDock();

    statusPath = new QLabel;
    statusCounts = new QLabel;
    statusSize = new QLabel;
    statusBar()->addPermanentWidget(statusPath, 1);
    statusBar()->addPermanentWidget(statusCounts);
    statusBar()->addPermanentWidget(statusSize);
    statusBar()->showMessage("No package open");

    logTimer = new QTimer(this);
    logTimer->setInterval(250);
    connect(logTimer, &QTimer::timeout, this, &MainWindow::PollLog);

    StartWorker();
    UpdateActions();
}

MainWindow::~MainWindow() {
    StopWorker();
}

/* ------------------------------------------------------------ */
void MainWindow::StartWorker() {
    thread = new QThread(this);
    worker = new PackageWorker;
    worker->moveToThread(thread);
    connect(thread, &QThread::finished, worker, &QObject::deleteLater);
    connect(worker, &PackageWorker::Opened, this, &MainWindow::OnOpened);
    connect(worker, &PackageWorker::OpenFailed, this, &MainWindow::OnOpenFailed);
    connect(worker, &PackageWorker::FilesListed, details, &DetailsPane::SetFiles);
    connect(worker, &PackageWorker::SeriesImagesReady, details, &DetailsPane::SetImages);
    connect(worker, &PackageWorker::ExtractFinished, this, [this](bool ok, const QString &msg) {
        PollLog();
        logTimer->stop();
        statusBar()->showMessage(ok ? msg : "Extract failed", 8000);
    });
    connect(worker, &PackageWorker::Validated, this, [this](bool ok, const QString &msg) {
        PollLog();
        validateAction->setEnabled(true);
        statusBar()->showMessage(msg, 8000);
        if (ok)
            QMessageBox::information(this, "Validate", msg);
        else
            QMessageBox::warning(this, "Validate", msg);
    });
    thread->start();
}

void MainWindow::StopWorker() {
    if (!thread)
        return;
    worker->RequestCancel();
    thread->quit();
    /* a running Read() or extract cannot be interrupted; wait for it */
    thread->wait();
    thread = nullptr;
    worker = nullptr;
}

/* ------------------------------------------------------------ */
void MainWindow::BuildActions() {
    openAction = new QAction(Icons::Open(), "Open…", this);
    openAction->setShortcut(QKeySequence::Open);
    connect(openAction, &QAction::triggered, this, &MainWindow::ChooseAndOpen);

    closeAction = new QAction(Icons::Close(), "Close package", this);
    closeAction->setShortcut(QKeySequence("Ctrl+W"));
    connect(closeAction, &QAction::triggered, this, &MainWindow::ClosePackage);

    extractAction = new QAction(Icons::Extract(), "Extract selected to…", this);
    extractAction->setShortcut(QKeySequence("Ctrl+E"));
    connect(extractAction, &QAction::triggered, this, [this]() { StartExtract(SelectedNode()); });

    quitAction = new QAction("Quit", this);
    quitAction->setShortcut(QKeySequence("Ctrl+Q"));
    connect(quitAction, &QAction::triggered, qApp, &QApplication::closeAllWindows);

    validateAction = new QAction(Icons::Validate(), "Validate", this);
    validateAction->setToolTip("Check the package for problems");
    connect(validateAction, &QAction::triggered, this, [this]() {
        if (!snap)
            return;
        validateAction->setEnabled(false);
        statusBar()->showMessage("Validating…");
        logTimer->start();
        QMetaObject::invokeMethod(worker, "Validate", Qt::QueuedConnection);
    });

    infoAction = new QAction(Icons::Info(), "Package info", this);
    infoAction->setToolTip("Show the package overview");
    connect(infoAction, &QAction::triggered, this, [this]() { SelectNode(0); });

    treeAction = new QAction("Package tree", this);
    treeAction->setCheckable(true);
    treeAction->setChecked(true);

    aboutAction = new QAction("About Squirrel", this);
    connect(aboutAction, &QAction::triggered, this, [this]() {
        QMessageBox::about(this, "About Squirrel",
                           QString("<b>Squirrel</b><br>Build %1.%2.%3<br><br>Viewer for squirrel data packages.<br>GPLv3")
                               .arg(UTIL_VERSION_MAJ, UTIL_VERSION_MIN, UTIL_BUILD_NUM));
    });
}

void MainWindow::BuildMenus() {
    QMenu *file = menuBar()->addMenu("&File");
    file->addAction(openAction);
    recentMenu = file->addMenu("Open recent");
    connect(recentMenu, &QMenu::aboutToShow, this, &MainWindow::UpdateRecentMenu);
    file->addAction(closeAction);
    file->addSeparator();
    file->addAction(extractAction);
    file->addSeparator();
    file->addAction(quitAction);

    QMenu *view = menuBar()->addMenu("&View");
    view->addAction(treeAction);
    /* the log dock's toggle action is added in BuildLogDock() */
    view->setObjectName("viewMenu");
    view->addSeparator();
    QMenu *theme = view->addMenu("Theme");
    themeGroup = new QActionGroup(this);
    const QString current = Settings::ThemeName();
    for (const auto &t : QList<QPair<QString, QString>>{{"system", "Follow system"}, {"light", "Light"}, {"dark", "Dark"}}) {
        QAction *a = theme->addAction(t.second);
        a->setCheckable(true);
        a->setData(t.first);
        a->setChecked(t.first == current);
        themeGroup->addAction(a);
        connect(a, &QAction::triggered, this, [this, a]() { SetTheme(a->data().toString()); });
    }

    QMenu *tools = menuBar()->addMenu("&Tools");
    tools->addAction(validateAction);
    tools->addAction(infoAction);

    QMenu *help = menuBar()->addMenu("&Help");
    help->addAction(aboutAction);
}

void MainWindow::BuildToolbar() {
    QToolBar *tb = addToolBar("Main");
    tb->setObjectName("mainToolbar");
    tb->setMovable(false);
    tb->setToolButtonStyle(Qt::ToolButtonTextBesideIcon);
    tb->setIconSize(QSize(16, 16));
    tb->addAction(openAction);
    tb->addAction(closeAction);
    tb->addSeparator();
    tb->addAction(validateAction);
    tb->addAction(infoAction);
    auto *spacer = new QWidget;
    spacer->setSizePolicy(QSizePolicy::Expanding, QSizePolicy::Preferred);
    tb->addWidget(spacer);
    filter = new QLineEdit;
    filter->setPlaceholderText("Filter subjects, studies, series");
    filter->setClearButtonEnabled(true);
    filter->addAction(Icons::Search(), QLineEdit::LeadingPosition);
    filter->setMinimumWidth(260);
    filter->setMaximumWidth(360);
    tb->addWidget(filter);
}

void MainWindow::BuildCentral() {
    stack = new QStackedWidget;
    startPage = new StartPage;
    stack->addWidget(startPage);
    connect(startPage, &StartPage::OpenRequested, this, &MainWindow::ChooseAndOpen);
    connect(startPage, &StartPage::RecentRequested, this, &MainWindow::OpenPackage);

    /* browser: tree | details */
    auto *split = new QSplitter(Qt::Horizontal);
    split->setChildrenCollapsible(false);
    treePanel = new QWidget;
    treePanel->setMinimumWidth(240);
    auto *tv = new QVBoxLayout(treePanel);
    tv->setContentsMargins(0, 0, 0, 0);
    tv->setSpacing(0);
    auto *treeHeader = new QLabel("  PACKAGE");
    QFont hf = treeHeader->font();
    hf.setPointSizeF(hf.pointSizeF() * 0.85);
    hf.setWeight(QFont::DemiBold);
    hf.setLetterSpacing(QFont::PercentageSpacing, 106);
    treeHeader->setFont(hf);
    treeHeader->setMinimumHeight(30);
    treeHeader->setAutoFillBackground(true);
    treeHeader->setBackgroundRole(QPalette::Base);
    tree = new QTreeView;
    tree->setHeaderHidden(true);
    tree->setUniformRowHeights(true);
    tree->setContextMenuPolicy(Qt::CustomContextMenu);
    tree->setEditTriggers(QAbstractItemView::NoEditTriggers);
    tree->setFrameShape(QFrame::NoFrame);
    tv->addWidget(treeHeader);
    tv->addWidget(tree, 1);

    model = new QStandardItemModel(this);
    model->setColumnCount(2);
    proxy = new QSortFilterProxyModel(this);
    proxy->setSourceModel(model);
    proxy->setRecursiveFilteringEnabled(true);
    proxy->setFilterCaseSensitivity(Qt::CaseInsensitive);
    proxy->setFilterKeyColumn(0);
    tree->setModel(proxy);

    details = new DetailsPane;
    split->addWidget(treePanel);
    split->addWidget(details);
    split->setStretchFactor(0, 0);
    split->setStretchFactor(1, 1);
    split->setSizes({340, 1100});
    browser = split;
    stack->addWidget(browser);
    setCentralWidget(stack);

    connect(treeAction, &QAction::toggled, treePanel, &QWidget::setVisible);
    connect(filter, &QLineEdit::textChanged, this, [this](const QString &text) {
        proxy->setFilterFixedString(text);
        if (!text.isEmpty())
            tree->expandAll();
    });
    connect(tree->selectionModel(), &QItemSelectionModel::currentChanged, this, [this](const QModelIndex &current) {
        const QModelIndex src = proxy->mapToSource(current.siblingAtColumn(0));
        const QVariant v = model->data(src, NodeRole);
        if (v.isValid())
            details->ShowNode(v.toInt());
        UpdateActions();
    });
    connect(tree, &QTreeView::customContextMenuRequested, this, &MainWindow::ShowTreeMenu);
    connect(details, &DetailsPane::NodeActivated, this, &MainWindow::SelectNode);
    connect(details, &DetailsPane::ExtractRequested, this, &MainWindow::StartExtract);
    connect(details, &DetailsPane::FilesNeeded, this, [this](int node, const QString &vp) {
        QMetaObject::invokeMethod(worker, "ListFiles", Qt::QueuedConnection, Q_ARG(int, node), Q_ARG(QString, vp));
    });
    connect(details, &DetailsPane::ImagesNeeded, this, [this](int node, const QString &vp) {
        QMetaObject::invokeMethod(worker, "PrepareSeriesImages", Qt::QueuedConnection, Q_ARG(int, node), Q_ARG(QString, vp));
    });
}

void MainWindow::BuildLogDock() {
    auto *dock = new QDockWidget("Log", this);
    dock->setObjectName("logDock");
    dock->setFeatures(QDockWidget::DockWidgetClosable | QDockWidget::DockWidgetMovable);
    logView = new QPlainTextEdit;
    logView->setReadOnly(true);
    logView->setMaximumBlockCount(5000);
    QFont mono("monospace");
    mono.setStyleHint(QFont::Monospace);
    logView->setFont(mono);
    dock->setWidget(logView);
    addDockWidget(Qt::BottomDockWidgetArea, dock);
    resizeDocks({dock}, {120}, Qt::Vertical);

    QMenu *view = menuBar()->findChild<QMenu *>("viewMenu");
    QAction *toggle = dock->toggleViewAction();
    toggle->setText("Log");
    if (view)
        view->insertAction(view->actions().value(1), toggle);
}

/* ------------------------------------------------------------ */
void MainWindow::ChooseAndOpen() {
    const QString path = QFileDialog::getOpenFileName(this, "Open squirrel package", Settings::LastOpenDir(),
                                                      "Squirrel packages (*.sqrl *.zip *.7z);;All files (*)");
    if (path.isEmpty())
        return;
    Settings::SetLastOpenDir(QFileInfo(path).absolutePath());
    OpenPackage(path);
}

void MainWindow::OpenPackage(const QString &path) {
    if (snap || loading) {
        auto *w = new MainWindow;
        w->move(pos() + QPoint(40, 40));
        w->show();
        w->LoadHere(path);
        return;
    }
    LoadHere(path);
}

void MainWindow::LoadHere(const QString &path) {
    loading = true;
    loadCancelled = false;
    loadingPath = QFileInfo(path).absoluteFilePath();
    statusBar()->showMessage("Opening " + loadingPath);
    logView->appendPlainText(QString("%1  Opening %2").arg(QTime::currentTime().toString("HH:mm:ss"), loadingPath));

    loadingDialog = new LoadingDialog(QDir::toNativeSeparators(loadingPath), this);
    loadingDialog->setAttribute(Qt::WA_DeleteOnClose);
    connect(loadingDialog, &QDialog::rejected, this, [this]() {
        loadCancelled = true;
        statusBar()->showMessage("Opening cancelled; the package will be closed when reading finishes");
    });
    loadingDialog->show();
    logTimer->start();
    QMetaObject::invokeMethod(worker, "OpenPackage", Qt::QueuedConnection, Q_ARG(QString, loadingPath));
    UpdateActions();
}

void MainWindow::OnOpened(SnapshotPtr s) {
    PollLog();
    loading = false;
    logTimer->stop();
    if (loadingDialog)
        loadingDialog->accept();

    if (loadCancelled) {
        /* discard: a fresh worker drops the package that was read */
        StopWorker();
        StartWorker();
        statusBar()->showMessage("No package open");
        UpdateActions();
        return;
    }

    snap = s;
    Settings::AddRecent({snap->path, snap->name,
                         QString("%1 subjects · %2").arg(snap->subjectCount).arg(HumanSize(snap->unzippedSize)),
                         QDateTime::currentDateTime()});
    setWindowTitle(QString("%1 — Squirrel").arg(QFileInfo(snap->path).fileName()));
    statusPath->setText(QDir::toNativeSeparators(snap->path));
    statusCounts->setText(snap->StatusSummary());
    statusSize->setText(HumanSize(snap->unzippedSize));
    statusBar()->showMessage("Ready");

    details->SetSnapshot(snap);
    PopulateTree();
    stack->setCurrentWidget(browser);
    SelectNode(0);
    UpdateActions();
}

void MainWindow::OnOpenFailed(const QString &message) {
    PollLog();
    loading = false;
    logTimer->stop();
    if (loadingDialog)
        loadingDialog->accept();
    statusBar()->showMessage("No package open");
    Settings::RemoveRecent(loadingPath);
    startPage->Refresh();
    UpdateActions();
    if (!loadCancelled)
        QMessageBox::warning(this, "Open package", message);
}

void MainWindow::ClosePackage() {
    if (!snap)
        return;
    StopWorker();
    snap.reset();
    model->clear();
    details->SetSnapshot(SnapshotPtr());
    StartWorker();
    filter->clear();
    setWindowTitle("Squirrel");
    statusPath->clear();
    statusCounts->clear();
    statusSize->clear();
    statusBar()->showMessage("No package open");
    startPage->Refresh();
    stack->setCurrentWidget(startPage);
    UpdateActions();
}

/* ------------------------------------------------------------ */
void MainWindow::PopulateTree() {
    model->clear();
    model->setColumnCount(2);
    if (!snap)
        return;
    QList<QStandardItem *> items(snap->nodes.size(), nullptr);
    const QColor muted = Theme::MutedText();
    for (int i = 0; i < snap->nodes.size(); i++) {
        const PackageNode &n = snap->nodes[i];
        auto *item = new QStandardItem(Icons::ForNode(n.kind), n.label);
        item->setData(i, NodeRole);
        item->setToolTip(n.title);
        auto *meta = new QStandardItem(n.meta);
        meta->setData(i, NodeRole);
        meta->setForeground(muted);
        meta->setTextAlignment(Qt::AlignRight | Qt::AlignVCenter);
        items[i] = item;
        if (n.parent < 0)
            model->appendRow({item, meta});
        else
            items[n.parent]->appendRow({item, meta});
    }
    tree->header()->setStretchLastSection(false);
    tree->header()->setSectionResizeMode(0, QHeaderView::Stretch);
    tree->header()->setSectionResizeMode(1, QHeaderView::ResizeToContents);

    /* open the package, Subjects, and the first subject and study */
    const QModelIndex root = proxy->index(0, 0);
    tree->expand(root);
    for (int r = 0; r < proxy->rowCount(root); r++) {
        const QModelIndex child = proxy->index(r, 0, root);
        if (model->data(proxy->mapToSource(child), NodeRole).toInt() == 1) {
            tree->expand(child);
            const QModelIndex firstSubject = proxy->index(0, 0, child);
            tree->expand(firstSubject);
            tree->expand(proxy->index(0, 0, firstSubject));
        }
    }
}

void MainWindow::SelectNode(int node) {
    if (!snap || node < 0)
        return;
    QModelIndexList found = model->match(model->index(0, 0), NodeRole, node, 1, Qt::MatchExactly | Qt::MatchRecursive);
    if (found.isEmpty())
        return;
    QModelIndex idx = proxy->mapFromSource(found.first());
    if (!idx.isValid()) {
        filter->clear();
        idx = proxy->mapFromSource(found.first());
    }
    tree->scrollTo(idx);
    tree->setCurrentIndex(idx);
}

int MainWindow::SelectedNode() const {
    const QModelIndex cur = tree->currentIndex();
    if (!snap || !cur.isValid())
        return -1;
    return model->data(proxy->mapToSource(cur.siblingAtColumn(0)), NodeRole).toInt();
}

void MainWindow::ShowTreeMenu(const QPoint &pos) {
    const QModelIndex idx = tree->indexAt(pos);
    if (!snap || !idx.isValid())
        return;
    const int node = model->data(proxy->mapToSource(idx.siblingAtColumn(0)), NodeRole).toInt();
    tree->setCurrentIndex(idx.siblingAtColumn(0));
    const PackageNode &n = snap->nodes[node];

    QMenu menu(this);
    QAction *title = menu.addAction(n.title);
    title->setEnabled(false);
    menu.addSeparator();
    QAction *extract = menu.addAction(Icons::Extract(), "Extract to…");
    extract->setEnabled(n.extract.possible);
    QAction *showImage = nullptr;
    if (n.kind == NodeKind::Series)
        showImage = menu.addAction("Show image");
    menu.addSeparator();
    QAction *copyName = menu.addAction("Copy name");
    QAction *copyPath = menu.addAction("Copy path in package");
    copyPath->setEnabled(!n.virtualPath.isEmpty());
    QAction *expandAll = nullptr, *collapseAll = nullptr;
    if (!n.children.isEmpty()) {
        menu.addSeparator();
        expandAll = menu.addAction("Expand all below");
        collapseAll = menu.addAction("Collapse all below");
    }

    QAction *chosen = menu.exec(tree->viewport()->mapToGlobal(pos));
    if (!chosen)
        return;
    if (chosen == extract)
        StartExtract(node);
    else if (chosen == showImage) {
        SelectNode(node);
        details->ShowImageTab();
    }
    else if (chosen == copyName)
        QApplication::clipboard()->setText(n.label.trimmed());
    else if (chosen == copyPath)
        QApplication::clipboard()->setText(n.virtualPath);
    else if (chosen == expandAll)
        tree->expandRecursively(idx.siblingAtColumn(0));
    else if (chosen == collapseAll) {
        std::function<void(const QModelIndex &)> collapse = [&](const QModelIndex &i) {
            for (int r = 0; r < proxy->rowCount(i); r++)
                collapse(proxy->index(r, 0, i));
            tree->collapse(i);
        };
        collapse(idx.siblingAtColumn(0));
    }
}

/* ------------------------------------------------------------ */
void MainWindow::StartExtract(int node) {
    if (!snap || node < 0 || node >= snap->nodes.size())
        return;
    const ExtractPlan &plan = snap->nodes[node].extract;
    if (!plan.possible)
        return;

    extractDialog = new ExtractDialog(plan, this);
    extractDialog->setAttribute(Qt::WA_DeleteOnClose);
    connect(worker, &PackageWorker::ExtractProgress, extractDialog, &ExtractDialog::SetProgress);
    connect(worker, &PackageWorker::ExtractFinished, extractDialog, &ExtractDialog::SetFinished);
    connect(extractDialog, &ExtractDialog::CancelRequested, this, [this]() { worker->RequestCancel(); });
    connect(extractDialog, &ExtractDialog::StartRequested, this, [this, plan](const QString &dest) {
        ExtractRequest req{plan, dest};
        logView->appendPlainText(QString("%1  Extracting %2 to %3").arg(QTime::currentTime().toString("HH:mm:ss"), plan.title, QDir::toNativeSeparators(dest)));
        logTimer->start();
        QMetaObject::invokeMethod(worker, "Extract", Qt::QueuedConnection, Q_ARG(ExtractRequest, req));
    });
    extractDialog->show();
}

/* ------------------------------------------------------------ */
void MainWindow::PollLog() {
    if (!worker)
        return;
    const QString text = worker->DrainLog();
    if (text.isEmpty())
        return;
    const QString trimmed = text.endsWith('\n') ? text.left(text.size() - 1) : text;
    logView->appendPlainText(trimmed);
    if (loadingDialog)
        loadingDialog->AppendLog(trimmed);
}

void MainWindow::UpdateRecentMenu() {
    recentMenu->clear();
    const QList<RecentPackage> recent = Settings::Recent();
    for (const RecentPackage &r : recent) {
        QAction *a = recentMenu->addAction(QString("%1  —  %2").arg(r.name, QDir::toNativeSeparators(r.path)));
        connect(a, &QAction::triggered, this, [this, r]() { OpenPackage(r.path); });
    }
    if (recent.isEmpty())
        recentMenu->addAction("No recent packages")->setEnabled(false);
    else {
        recentMenu->addSeparator();
        connect(recentMenu->addAction("Clear list"), &QAction::triggered, this, [this]() {
            Settings::ClearRecent();
            startPage->Refresh();
        });
    }
}

void MainWindow::UpdateActions() {
    const bool open = !snap.isNull();
    closeAction->setEnabled(open);
    validateAction->setEnabled(open);
    infoAction->setEnabled(open);
    filter->setEnabled(open);
    const int node = SelectedNode();
    extractAction->setEnabled(open && node >= 0 && snap->nodes[node].extract.possible);
}

void MainWindow::SetTheme(const QString &name) {
    Settings::SetThemeName(name);
    Theme::Apply(Theme::ModeFromString(name));
    /* icons and muted colours are drawn for one theme; rebuild them */
    for (QWidget *w : QApplication::topLevelWidgets())
        if (auto *mw = qobject_cast<MainWindow *>(w)) {
            for (QAction *a : mw->themeGroup->actions())
                a->setChecked(a->data().toString() == name);
            mw->openAction->setIcon(Icons::Open());
            mw->closeAction->setIcon(Icons::Close());
            mw->validateAction->setIcon(Icons::Validate());
            mw->infoAction->setIcon(Icons::Info());
            mw->extractAction->setIcon(Icons::Extract());
            const int sel = mw->SelectedNode();
            mw->PopulateTree();
            if (sel >= 0)
                mw->SelectNode(sel);
            mw->startPage->Refresh();
        }
}

/* ------------------------------------------------------------ */
void MainWindow::closeEvent(QCloseEvent *e) {
    if (loading) {
        statusBar()->showMessage("Waiting for the package read to finish…");
    }
    e->accept();
}

void MainWindow::dragEnterEvent(QDragEnterEvent *e) {
    if (e->mimeData()->hasUrls()) {
        for (const QUrl &u : e->mimeData()->urls())
            if (u.isLocalFile() && IsPackageFile(u.toLocalFile())) {
                e->acceptProposedAction();
                startPage->SetDragActive(true);
                return;
            }
    }
}

void MainWindow::dragLeaveEvent(QDragLeaveEvent *) {
    startPage->SetDragActive(false);
}

void MainWindow::dropEvent(QDropEvent *e) {
    startPage->SetDragActive(false);
    for (const QUrl &u : e->mimeData()->urls())
        if (u.isLocalFile() && IsPackageFile(u.toLocalFile()))
            OpenPackage(u.toLocalFile());
}
