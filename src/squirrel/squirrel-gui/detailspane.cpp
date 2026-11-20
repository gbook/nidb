/* ------------------------------------------------------------------------------
  squirrel-gui detailspane.cpp
  ------------------------------------------------------------------------------ */

#include "detailspane.h"

#include <QFileInfo>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QLabel>
#include <QLineEdit>
#include <QPushButton>
#include <QTabBar>
#include <QTabWidget>
#include <QTableWidget>
#include <QTextBrowser>
#include <QVBoxLayout>

#include "icons.h"
#include "imageviewer.h"
#include "propertygrid.h"
#include "theme.h"

namespace {

QLabel *MutedLabel(const QString &text = QString()) {
    auto *l = new QLabel(text);
    l->setForegroundRole(QPalette::PlaceholderText);
    return l;
}

QTableWidget *NewTable() {
    auto *t = new QTableWidget;
    t->verticalHeader()->setVisible(false);
    t->setEditTriggers(QAbstractItemView::NoEditTriggers);
    t->setSelectionBehavior(QAbstractItemView::SelectRows);
    t->setSelectionMode(QAbstractItemView::SingleSelection);
    t->setAlternatingRowColors(false);
    t->setShowGrid(false);
    t->horizontalHeader()->setHighlightSections(false);
    t->horizontalHeader()->setDefaultAlignment(Qt::AlignLeft | Qt::AlignVCenter);
    t->setWordWrap(false);
    return t;
}

QLabel *EmptyLabel(const QString &text) {
    auto *l = MutedLabel(text);
    l->setAlignment(Qt::AlignCenter);
    l->setMinimumHeight(120);
    return l;
}

QFont MonoFont() {
    QFont f("monospace");
    f.setStyleHint(QFont::Monospace);
    return f;
}

} // namespace

/* ------------------------------------------------------------ */
DetailsPane::DetailsPane(QWidget *parent) : QWidget(parent) {
    auto *v = new QVBoxLayout(this);
    v->setContentsMargins(0, 0, 0, 0);
    v->setSpacing(0);

    /* ----- header ----- */
    auto *header = new QWidget;
    header->setAutoFillBackground(true);
    header->setBackgroundRole(QPalette::Base);
    auto *hv = new QVBoxLayout(header);
    hv->setContentsMargins(20, 14, 20, 0);
    hv->setSpacing(4);
    auto *hrow = new QHBoxLayout;
    auto *texts = new QVBoxLayout;
    texts->setSpacing(4);
    crumbs = MutedLabel();
    QFont cf = crumbs->font();
    cf.setPointSizeF(cf.pointSizeF() * 0.92);
    crumbs->setFont(cf);
    auto *titleRow = new QHBoxLayout;
    titleRow->setSpacing(10);
    title = new QLabel;
    QFont tf = title->font();
    tf.setPointSizeF(tf.pointSizeF() * 1.5);
    tf.setWeight(QFont::DemiBold);
    title->setFont(tf);
    title->setTextInteractionFlags(Qt::TextSelectableByMouse);
    subtitle = MutedLabel();
    titleRow->addWidget(title);
    titleRow->addWidget(subtitle, 1, Qt::AlignBottom);
    texts->addWidget(crumbs);
    texts->addLayout(titleRow);
    extractButton = new QPushButton(Icons::Extract(), "Extract to…");
    extractButton->setToolTip("Extract this object to a folder (Ctrl+E)");
    hrow->addLayout(texts, 1);
    hrow->addWidget(extractButton, 0, Qt::AlignVCenter);
    hv->addLayout(hrow);
    hv->addSpacing(6);
    v->addWidget(header);

    tabs = new QTabWidget;
    tabs->setDocumentMode(true);
    v->addWidget(tabs, 1);

    /* ----- overview ----- */
    overviewPage = new QWidget;
    auto *ov = new QVBoxLayout(overviewPage);
    ov->setContentsMargins(20, 18, 20, 18);
    ov->setSpacing(8);
    grid = new PropertyGrid;
    childTitle = new QLabel;
    QFont ctf = childTitle->font();
    ctf.setWeight(QFont::DemiBold);
    childTitle->setFont(ctf);
    childTable = NewTable();
    childTable->setCursor(Qt::PointingHandCursor);
    childEmpty = EmptyLabel(QString());
    ov->addWidget(grid);
    ov->addSpacing(12);
    ov->addWidget(childTitle);
    ov->addWidget(childTable, 1);
    ov->addWidget(childEmpty);
    ov->addStretch(0);
    connect(childTable, &QTableWidget::cellClicked, this, [this](int row, int) {
        if (row >= 0 && row < childRowNodes.size() && childRowNodes[row] >= 0)
            emit NodeActivated(childRowNodes[row]);
    });

    /* ----- parameters ----- */
    paramsPage = new QWidget;
    auto *pv = new QVBoxLayout(paramsPage);
    pv->setContentsMargins(20, 18, 20, 18);
    auto *prow = new QHBoxLayout;
    paramsFilter = new QLineEdit;
    paramsFilter->setPlaceholderText("Filter, e.g. Echo");
    paramsFilter->setClearButtonEnabled(true);
    paramsFilter->setMaximumWidth(320);
    paramsCount = MutedLabel();
    prow->addWidget(paramsFilter);
    prow->addWidget(paramsCount, 1);
    paramsTable = NewTable();
    paramsTable->setColumnCount(2);
    paramsTable->setHorizontalHeaderLabels({"Parameter", "Value"});
    paramsTable->horizontalHeader()->setStretchLastSection(true);
    pv->addLayout(prow);
    pv->addWidget(paramsTable, 1);
    connect(paramsFilter, &QLineEdit::textChanged, this, &DetailsPane::FillParams);

    /* ----- files ----- */
    filesPage = new QWidget;
    auto *fv = new QVBoxLayout(filesPage);
    fv->setContentsMargins(20, 18, 20, 18);
    filesInfo = MutedLabel();
    filesTable = NewTable();
    filesTable->setColumnCount(2);
    filesTable->setHorizontalHeaderLabels({"File", "Type"});
    filesTable->horizontalHeader()->setSectionResizeMode(0, QHeaderView::Stretch);
    filesTable->setFont(MonoFont());
    fv->addWidget(filesInfo);
    fv->addWidget(filesTable, 1);

    /* ----- image ----- */
    viewer = new ImageViewer;
    connect(viewer, &ImageViewer::SeriesRequested, this, &DetailsPane::NodeActivated);

    connect(tabs, &QTabWidget::currentChanged, this, &DetailsPane::OnTabChanged);
    connect(extractButton, &QPushButton::clicked, this, [this]() {
        if (node >= 0)
            emit ExtractRequested(node);
    });
}

/* ------------------------------------------------------------ */
void DetailsPane::SetSnapshot(SnapshotPtr s) {
    snap = s;
    node = -1;
    filesNode = -1;
    imagesNode = -1;
    lastSeriesTab = "overview";
    viewer->ShowMessage("Select a series to see its images.");
    ClearTabs();
}

void DetailsPane::ClearTabs() {
    tabs->blockSignals(true);
    while (tabs->count() > 0)
        tabs->removeTab(0);
    tabs->blockSignals(false);
    qDeleteAll(dynamicPages);
    dynamicPages.clear();
}

void DetailsPane::AddTab(QWidget *page, const QString &label, const QString &id, bool dynamic) {
    const int i = tabs->addTab(page, label);
    tabs->tabBar()->setTabData(i, id);
    if (dynamic)
        dynamicPages.append(page);
}

QString DetailsPane::CurrentTabId() const {
    const int i = tabs->currentIndex();
    return i >= 0 ? tabs->tabBar()->tabData(i).toString() : QString();
}

void DetailsPane::FillTable(QTableWidget *table, const NodeTable &t) {
    table->clear();
    table->setColumnCount(t.columns.size());
    table->setHorizontalHeaderLabels(t.columns);
    table->setRowCount(t.rows.size());
    for (int r = 0; r < t.rows.size(); r++)
        for (int c = 0; c < t.rows[r].size() && c < t.columns.size(); c++)
            table->setItem(r, c, new QTableWidgetItem(t.rows[r][c]));
    table->resizeColumnsToContents();
    table->horizontalHeader()->setStretchLastSection(true);
}

QWidget *DetailsPane::TablePage(const NodeTable &t) {
    auto *page = new QWidget;
    auto *v = new QVBoxLayout(page);
    v->setContentsMargins(20, 18, 20, 18);
    if (t.rows.isEmpty()) {
        v->addWidget(EmptyLabel(t.emptyText));
        v->addStretch(1);
        return page;
    }
    auto *table = NewTable();
    FillTable(table, t);
    v->addWidget(table, 1);
    return page;
}

QWidget *DetailsPane::TextPage(const QString &text, const QString &emptyText) {
    auto *page = new QWidget;
    auto *v = new QVBoxLayout(page);
    v->setContentsMargins(20, 18, 20, 18);
    if (text.trimmed().isEmpty()) {
        v->addWidget(EmptyLabel(emptyText));
        v->addStretch(1);
        return page;
    }
    auto *browser = new QTextBrowser;
    browser->setOpenExternalLinks(true);
    if (text.contains("# ") || text.contains("**"))
        browser->setMarkdown(text);
    else
        browser->setPlainText(text);
    v->addWidget(browser, 1);
    return page;
}

/* ------------------------------------------------------------ */
void DetailsPane::ShowNode(int n) {
    if (!snap || n < 0 || n >= snap->nodes.size())
        return;
    const QString previousTab = CurrentTabId();
    if (node >= 0 && snap->nodes[node].kind == NodeKind::Series && !previousTab.isEmpty())
        lastSeriesTab = previousTab;
    node = n;
    const PackageNode &pn = snap->nodes[n];

    crumbs->setText(pn.crumbs.join("  /  "));
    title->setText(pn.title);
    subtitle->setText(pn.subtitle);
    extractButton->setEnabled(pn.extract.possible);

    ClearTabs();
    tabs->blockSignals(true);

    /* overview */
    grid->SetProperties(pn.props);
    const NodeTable &ct = pn.childTable;
    childRowNodes = ct.rowNodes;
    const bool hasTable = !ct.columns.isEmpty();
    childTitle->setText(ct.title);
    childTitle->setVisible(hasTable);
    childTable->setVisible(hasTable && !ct.rows.isEmpty());
    childEmpty->setVisible(hasTable && ct.rows.isEmpty());
    childEmpty->setText(ct.emptyText);
    if (hasTable)
        FillTable(childTable, ct);
    AddTab(overviewPage, "Overview", "overview", false);

    switch (pn.kind) {
        case NodeKind::Package:
            AddTab(TextPage(pn.readme, "This package has no README."), "README", "readme", true);
            AddTab(TextPage(pn.changes, "No changes recorded."), "Changes", "changes", true);
            break;
        case NodeKind::Series: {
            AddTab(viewer, "Image", "image", false);
            AddTab(paramsPage, "Parameters", "params", false);
            AddTab(filesPage, "Files", "files", false);
            FillParams();
            if (filesNode != n) {
                filesTable->setRowCount(0);
                filesInfo->setText(QString("%1 · %2 · inside the archive at %3/").arg(QString::number(pn.fileCount) + " files", HumanSize(pn.size), pn.virtualPath));
            }
            QList<ImageViewer::SeriesEntry> siblings;
            if (pn.studyNode >= 0)
                for (int c : snap->nodes[pn.studyNode].children)
                    siblings.append({c, snap->nodes[c].label, snap->nodes[c].fileCount});
            viewer->SetSeriesList(siblings, n);
            break;
        }
        default:
            break;
    }
    for (const NodeTable &t : pn.extraTabs)
        AddTab(TablePage(t), t.title, "extra:" + t.title, true);

    /* keep the same tab when moving between series */
    int restore = 0;
    if (pn.kind == NodeKind::Series)
        for (int i = 0; i < tabs->count(); i++)
            if (tabs->tabBar()->tabData(i).toString() == lastSeriesTab)
                restore = i;
    tabs->setCurrentIndex(restore);
    tabs->blockSignals(false);
    OnTabChanged(restore);
}

void DetailsPane::ShowImageTab() {
    for (int i = 0; i < tabs->count(); i++)
        if (tabs->tabBar()->tabData(i).toString() == "image")
            tabs->setCurrentIndex(i);
}

void DetailsPane::OnTabChanged(int index) {
    if (!snap || node < 0 || index < 0)
        return;
    const QString id = tabs->tabBar()->tabData(index).toString();
    const PackageNode &pn = snap->nodes[node];
    if (pn.kind == NodeKind::Series)
        lastSeriesTab = id;

    if (id != "image")
        viewer->Stop();

    if (id == "files" && filesNode != node) {
        filesInfo->setText("Listing files…");
        emit FilesNeeded(node, pn.virtualPath);
    }
    if (id == "image" && imagesNode != node) {
        imagesNode = node;
        viewer->ShowMessage("Extracting images from the package…");
        emit ImagesNeeded(node, pn.virtualPath);
    }
}

void DetailsPane::FillParams() {
    if (!snap || node < 0)
        return;
    const PackageNode &pn = snap->nodes[node];
    const QString f = paramsFilter->text().trimmed();
    paramsTable->setRowCount(0);
    int shown = 0;
    for (const auto &kv : pn.params) {
        if (!f.isEmpty() && !kv.first.contains(f, Qt::CaseInsensitive))
            continue;
        paramsTable->insertRow(shown);
        paramsTable->setItem(shown, 0, new QTableWidgetItem(kv.first));
        auto *val = new QTableWidgetItem(kv.second);
        val->setFont(MonoFont());
        val->setToolTip(kv.second);
        paramsTable->setItem(shown, 1, val);
        shown++;
    }
    paramsTable->resizeColumnToContents(0);
    if (pn.params.isEmpty())
        paramsCount->setText("This series has no params.json.");
    else
        paramsCount->setText(QString("%1 of %2 parameters · from params.json").arg(shown).arg(pn.params.size()));
}

/* ------------------------------------------------------------ */
void DetailsPane::SetFiles(int n, const QStringList &files, const QString &error) {
    if (!snap || n != node)
        return;
    filesNode = n;
    const PackageNode &pn = snap->nodes[n];
    filesTable->setRowCount(0);
    if (!error.isEmpty()) {
        filesInfo->setText("Could not list the files: " + error);
        filesNode = -1;
        return;
    }
    filesInfo->setText(QString("%1 files · %2 · inside the archive at %3/").arg(files.size()).arg(HumanSize(pn.size), pn.virtualPath));
    filesTable->setRowCount(files.size());
    for (int i = 0; i < files.size(); i++) {
        QString type = QFileInfo(files[i]).suffix().toLower();
        const QString lower = files[i].toLower();
        if (lower.endsWith(".dcm") || type.isEmpty())
            type = type.isEmpty() ? "file" : "DICOM";
        else if (lower.endsWith(".nii") || lower.endsWith(".nii.gz"))
            type = "NIfTI";
        else if (type == "json")
            type = "JSON";
        filesTable->setItem(i, 0, new QTableWidgetItem(files[i]));
        filesTable->setItem(i, 1, new QTableWidgetItem(type));
    }
}

void DetailsPane::SetImages(int n, const SliceList &slices, const QString &error) {
    if (!snap || n != node)
        return;
    if (!error.isEmpty()) {
        viewer->ShowMessage(error);
        return;
    }
    viewer->SetSlices(n, slices);
}
