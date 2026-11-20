/* ------------------------------------------------------------------------------
  squirrel-gui imageviewer.cpp
  ------------------------------------------------------------------------------ */

#include "imageviewer.h"

#include <QAction>
#include <QActionGroup>
#include <QHBoxLayout>
#include <QHeaderView>
#include <QLabel>
#include <QLineEdit>
#include <QListWidget>
#include <QPushButton>
#include <QSlider>
#include <QSplitter>
#include <QStackedWidget>
#include <QTableWidget>
#include <QTimer>
#include <QToolBar>
#include <QToolButton>
#include <QVBoxLayout>
#include <algorithm>

#include "theme.h"

ImageViewer::ImageViewer(QWidget *parent) : QWidget(parent) {
    setPalette(Theme::ViewerPalette());
    setAutoFillBackground(true);

    auto *v = new QVBoxLayout(this);
    v->setContentsMargins(0, 0, 0, 0);
    v->setSpacing(0);

    /* ----- toolbar ----- */
    auto *tb = new QToolBar;
    tb->setToolButtonStyle(Qt::ToolButtonTextOnly);
    tb->setIconSize(QSize(16, 16));

    toolGroup = new QActionGroup(this);
    auto addTool = [&](const QString &text, ImageView::Tool t, const QString &tip) {
        QAction *a = tb->addAction(text);
        a->setCheckable(true);
        a->setToolTip(tip);
        toolGroup->addAction(a);
        connect(a, &QAction::triggered, this, [this, t]() { view->SetTool(t); });
        return a;
    };
    addTool("Window / level", ImageView::Tool::WindowLevel, "Drag to change window (left/right) and level (up/down). Right-drag always does this.")->setChecked(true);
    addTool("Pan", ImageView::Tool::Pan, "Drag to move the image. Middle-drag always pans.");
    addTool("Zoom", ImageView::Tool::Zoom, "Drag up/down to zoom. Ctrl+wheel also zooms.");
    tb->addSeparator();
    QAction *fit = tb->addAction("Fit");
    fit->setToolTip("Fit the image to the window (double-click)");
    QAction *actual = tb->addAction("1:1");
    actual->setToolTip("One screen pixel per image pixel");
    QAction *reset = tb->addAction("Reset");
    reset->setToolTip("Fit, window from file, not inverted");
    invertAction = tb->addAction("Invert");
    invertAction->setCheckable(true);
    tb->addSeparator();
    tb->addWidget(new QLabel("  Window preset  "));
    presetGroup = new QActionGroup(this);
    presetGroup->setExclusionPolicy(QActionGroup::ExclusionPolicy::ExclusiveOptional);
    presetFile = tb->addAction("From file");
    presetFile->setToolTip("Window center/width stored in the image (falls back to Auto)");
    presetAuto = tb->addAction("Auto");
    presetAuto->setToolTip("Minimum to maximum pixel value of this image");
    presetFull = tb->addAction("Full range");
    presetFull->setToolTip("The whole range the pixel data can hold");
    for (QAction *a : {presetFile, presetAuto, presetFull}) {
        a->setCheckable(true);
        presetGroup->addAction(a);
    }
    presetFile->setChecked(true);

    auto *spacer = new QWidget;
    spacer->setSizePolicy(QSizePolicy::Expanding, QSizePolicy::Preferred);
    tb->addWidget(spacer);
    for (const char *plane : {"Axial", "Coronal", "Sagittal"}) {
        QAction *a = tb->addAction(plane);
        a->setEnabled(false);
        a->setToolTip("Needs a 3D volume (NIfTI support is planned)");
    }
    tb->addSeparator();
    overlayAction = tb->addAction("Overlay text");
    overlayAction->setCheckable(true);
    overlayAction->setChecked(true);
    headerAction = tb->addAction("DICOM header");
    headerAction->setCheckable(true);
    headerAction->setChecked(true);
    v->addWidget(tb);

    /* ----- series strip | viewport | header ----- */
    auto *split = new QSplitter(Qt::Horizontal);
    split->setChildrenCollapsible(false);

    seriesList = new QListWidget;
    seriesList->setMinimumWidth(150);
    seriesList->setMaximumWidth(240);
    seriesList->setWordWrap(true);
    seriesList->setToolTip("Series in this study");
    split->addWidget(seriesList);

    auto *centerBox = new QWidget;
    auto *cv = new QVBoxLayout(centerBox);
    cv->setContentsMargins(0, 0, 0, 0);
    cv->setSpacing(0);
    stack = new QStackedWidget;
    message = new QLabel;
    message->setAlignment(Qt::AlignCenter);
    message->setWordWrap(true);
    message->setMargin(24);
    stack->addWidget(message);
    view = new ImageView;
    stack->addWidget(view);
    cv->addWidget(stack, 1);

    auto *controls = new QWidget;
    controls->setAutoFillBackground(true);
    controls->setBackgroundRole(QPalette::Window);
    auto *ctl = new QVBoxLayout(controls);
    ctl->setContentsMargins(12, 8, 12, 8);
    ctl->setSpacing(6);
    auto *row1 = new QHBoxLayout;
    prevButton = new QPushButton("‹");
    prevButton->setToolTip("Previous image (Up)");
    prevButton->setFixedWidth(32);
    nextButton = new QPushButton("›");
    nextButton->setToolTip("Next image (Down)");
    nextButton->setFixedWidth(32);
    slider = new QSlider(Qt::Horizontal);
    slider->setMinimum(1);
    position = new QLabel("0 / 0");
    position->setMinimumWidth(80);
    position->setAlignment(Qt::AlignRight | Qt::AlignVCenter);
    playButton = new QPushButton("Play");
    playButton->setCheckable(true);
    row1->addWidget(prevButton);
    row1->addWidget(nextButton);
    row1->addWidget(new QLabel("Image"));
    row1->addWidget(slider, 1);
    row1->addWidget(position);
    row1->addWidget(playButton);
    auto *row2 = new QHBoxLayout;
    auto *volLabel = new QLabel("Volume");
    auto *volSlider = new QSlider(Qt::Horizontal);
    volSlider->setEnabled(false);
    auto *volNote = new QLabel("Only for 4D data (NIfTI, later)");
    volLabel->setEnabled(false);
    volNote->setEnabled(false);
    row2->addSpacing(70);
    row2->addWidget(volLabel);
    row2->addWidget(volSlider, 1);
    row2->addWidget(volNote);
    ctl->addLayout(row1);
    ctl->addLayout(row2);
    cv->addWidget(controls);
    split->addWidget(centerBox);

    headerPanel = new QWidget;
    auto *hv = new QVBoxLayout(headerPanel);
    hv->setContentsMargins(10, 8, 10, 8);
    hv->setSpacing(8);
    headerTitle = new QLabel("DICOM header");
    QFont hf = headerTitle->font();
    hf.setWeight(QFont::DemiBold);
    headerTitle->setFont(hf);
    headerFilter = new QLineEdit;
    headerFilter->setPlaceholderText("Filter by tag or name");
    headerFilter->setClearButtonEnabled(true);
    headerTable = new QTableWidget(0, 3);
    headerTable->setHorizontalHeaderLabels({"Tag", "Name", "Value"});
    headerTable->verticalHeader()->setVisible(false);
    headerTable->horizontalHeader()->setSectionResizeMode(0, QHeaderView::ResizeToContents);
    headerTable->horizontalHeader()->setSectionResizeMode(1, QHeaderView::Interactive);
    headerTable->horizontalHeader()->setSectionResizeMode(2, QHeaderView::Stretch);
    headerTable->setColumnWidth(1, 170);
    headerTable->setHorizontalScrollBarPolicy(Qt::ScrollBarAlwaysOff);
    headerTable->setTextElideMode(Qt::ElideRight);
    headerTable->setEditTriggers(QAbstractItemView::NoEditTriggers);
    headerTable->setSelectionBehavior(QAbstractItemView::SelectRows);
    headerTable->setWordWrap(false);
    QFont mono("monospace");
    mono.setStyleHint(QFont::Monospace);
    headerTable->setFont(mono);
    hv->addWidget(headerTitle);
    hv->addWidget(headerFilter);
    hv->addWidget(headerTable, 1);
    headerPanel->setMinimumWidth(300);
    split->addWidget(headerPanel);
    split->setStretchFactor(0, 0);
    split->setStretchFactor(1, 1);
    split->setStretchFactor(2, 0);
    split->setSizes({170, 800, 380});
    v->addWidget(split, 1);

    cine = new QTimer(this);
    cine->setInterval(100);

    /* ----- wiring ----- */
    connect(fit, &QAction::triggered, view, &ImageView::Fit);
    connect(actual, &QAction::triggered, view, &ImageView::ActualSize);
    connect(reset, &QAction::triggered, this, [this]() {
        invertAction->setChecked(false);
        view->SetInverted(false);
        view->Fit();
        presetFile->setChecked(true);
        SetWindowMode(WindowMode::FromFile);
    });
    connect(invertAction, &QAction::toggled, this, [this](bool on) {
        view->SetInverted(on);
        Render();
    });
    connect(presetFile, &QAction::triggered, this, [this]() { SetWindowMode(WindowMode::FromFile); });
    connect(presetAuto, &QAction::triggered, this, [this]() { SetWindowMode(WindowMode::Auto); });
    connect(presetFull, &QAction::triggered, this, [this]() { SetWindowMode(WindowMode::Full); });
    connect(overlayAction, &QAction::toggled, view, &ImageView::SetOverlayVisible);
    connect(headerAction, &QAction::toggled, this, [this](bool on) {
        headerPanel->setVisible(on);
        if (on)
            PopulateHeader();
    });
    connect(headerFilter, &QLineEdit::textChanged, this, &ImageViewer::PopulateHeader);

    connect(prevButton, &QPushButton::clicked, this, [this]() { GoTo(current - 1); });
    connect(nextButton, &QPushButton::clicked, this, [this]() { GoTo(current + 1); });
    connect(slider, &QSlider::valueChanged, this, [this](int value) {
        if (value - 1 != current)
            GoTo(value - 1);
    });
    connect(view, &ImageView::SliceStep, this, [this](int d) { GoTo(current + d); });
    connect(view, &ImageView::SliceJump, this, [this](bool end) { GoTo(end ? int(slices.size()) - 1 : 0); });
    connect(view, &ImageView::ZoomChanged, this, &ImageViewer::UpdateOverlay);
    connect(view, &ImageView::WindowDrag, this, [this](int dx, int dy) {
        if (!sliceOk || !slice.IsMonochrome())
            return;
        const double sens = std::max((sliceMax - sliceMin) / 400.0, 0.01);
        width = std::max(1.0, width + dx * sens);
        center += dy * sens;
        if (windowMode != WindowMode::Manual) {
            windowMode = WindowMode::Manual;
            if (QAction *a = presetGroup->checkedAction())
                a->setChecked(false);
        }
        Render();
    });
    connect(playButton, &QPushButton::toggled, this, [this](bool on) {
        playButton->setText(on ? "Stop" : "Play");
        if (on && slices.size() > 1)
            cine->start();
        else
            cine->stop();
    });
    connect(cine, &QTimer::timeout, this, [this]() {
        if (slices.size() < 2)
            return;
        GoTo((current + 1) % int(slices.size()));
    });
    connect(seriesList, &QListWidget::itemClicked, this, [this](QListWidgetItem *item) {
        const int n = item->data(Qt::UserRole).toInt();
        if (n != node)
            emit SeriesRequested(n);
    });

    ShowMessage("Select a series to see its images.");
}

/* ------------------------------------------------------------ */
void ImageViewer::SetSeriesList(const QList<SeriesEntry> &entries, int currentNode) {
    seriesList->clear();
    for (const SeriesEntry &e : entries) {
        auto *item = new QListWidgetItem(QString("%1\n%2 images").arg(e.label).arg(e.count), seriesList);
        item->setData(Qt::UserRole, e.node);
        if (e.node == currentNode)
            item->setSelected(true);
    }
}

void ImageViewer::ShowMessage(const QString &text) {
    Stop();
    message->setText(text);
    stack->setCurrentWidget(message);
    headerTable->setRowCount(0);
    slider->setEnabled(false);
    prevButton->setEnabled(false);
    nextButton->setEnabled(false);
    playButton->setEnabled(false);
    position->setText("0 / 0");
}

void ImageViewer::Stop() {
    cine->stop();
    playButton->setChecked(false);
}

void ImageViewer::SetSlices(int n, const SliceList &s) {
    Stop();
    node = n;
    slices = s;
    current = std::max(0, int(slices.size()) / 2);
    if (slices.isEmpty()) {
        ShowMessage("This series has no images to show.");
        return;
    }
    stack->setCurrentWidget(view);
    const bool many = slices.size() > 1;
    slider->blockSignals(true);
    slider->setRange(1, int(slices.size()));
    slider->setValue(current + 1);
    slider->blockSignals(false);
    slider->setEnabled(many);
    prevButton->setEnabled(many);
    nextButton->setEnabled(many);
    playButton->setEnabled(many);
    view->Fit();
    LoadCurrent();
}

void ImageViewer::GoTo(int index) {
    if (slices.isEmpty())
        return;
    index = std::clamp(index, 0, int(slices.size()) - 1);
    if (index == current && sliceOk)
        return;
    current = index;
    slider->blockSignals(true);
    slider->setValue(current + 1);
    slider->blockSignals(false);
    LoadCurrent();
}

void ImageViewer::LoadCurrent() {
    QString err;
    sliceOk = slice.Load(slices[current], err);
    position->setText(QString("%1 / %2").arg(current + 1).arg(slices.size()));
    if (!sliceOk) {
        view->ClearImage();
        view->SetOverlay({err}, {}, {}, {}, {}, {}, {}, {});
        headerTable->setRowCount(0);
        return;
    }
    slice.MinMax(sliceMin, sliceMax);
    if (windowMode != WindowMode::Manual)
        SetWindowMode(windowMode);
    else
        Render();
    if (headerAction->isChecked())
        PopulateHeader();
}

void ImageViewer::SetWindowMode(WindowMode m) {
    windowMode = m;
    if (!sliceOk)
        return;
    double lo = sliceMin, hi = sliceMax;
    switch (m) {
        case WindowMode::FromFile:
            if (slice.HasFileWindow()) {
                center = slice.FileCenter();
                width = slice.FileWidth();
                break;
            }
            [[fallthrough]];
        case WindowMode::Auto:
            center = (lo + hi) / 2.0;
            width = std::max(1.0, hi - lo);
            break;
        case WindowMode::Full:
            slice.FullRange(lo, hi);
            center = (lo + hi) / 2.0;
            width = std::max(1.0, hi - lo);
            break;
        case WindowMode::Manual:
            break;
    }
    Render();
}

void ImageViewer::Render() {
    if (!sliceOk)
        return;
    double aspect = 1.0;
    if (slice.pixelSpacingRow > 0 && slice.pixelSpacingCol > 0)
        aspect = slice.pixelSpacingRow / slice.pixelSpacingCol;
    view->SetImage(slice.Render(center, width, invertAction->isChecked()), aspect);
    UpdateOverlay();
}

void ImageViewer::UpdateOverlay() {
    if (!sliceOk)
        return;
    QStringList tl, tr, bl, br;
    tl << slice.patientID << slice.studyDate << QString("%1 %2").arg(slice.manufacturer, slice.model).trimmed();
    tr << QString("Series %1 · %2").arg(slice.seriesNumber, slice.seriesDescription)
       << QString("Im %1 / %2").arg(current + 1).arg(slices.size());
    if (!slice.sliceLocation.isEmpty())
        tr << QString("Loc %1 mm").arg(slice.sliceLocation.toDouble(), 0, 'f', 1);
    if (slice.IsMonochrome())
        bl << QString("W %1  L %2").arg(width, 0, 'f', 0).arg(center, 0, 'f', 0);
    bl << QString("Zoom %1%").arg(view->ZoomPercent(), 0, 'f', 0);
    br << QString("%1 × %2").arg(slice.columns).arg(slice.rows);
    if (slice.pixelSpacingRow > 0) {
        QString px = QString("%1 × %2 mm").arg(slice.pixelSpacingCol, 0, 'f', 2).arg(slice.pixelSpacingRow, 0, 'f', 2);
        if (slice.sliceThickness > 0)
            px += QString(" · %1 mm thick").arg(slice.sliceThickness, 0, 'f', 1);
        br << px;
    }
    tl.removeAll(QString());

    QString left, right, top, bottom;
    if (slice.hasOrientation) {
        right = OrientationLabel(slice.rowDir);
        left = OrientationLabel(-slice.rowDir);
        bottom = OrientationLabel(slice.colDir);
        top = OrientationLabel(-slice.colDir);
    }
    view->SetOverlay(tl, tr, bl, br, left, right, top, bottom);
}

void ImageViewer::PopulateHeader() {
    headerTable->setRowCount(0);
    if (!sliceOk)
        return;
    headerTitle->setText(QString("DICOM header · image %1").arg(current + 1));
    const QString f = headerFilter->text().trimmed();
    const QList<HeaderRow> rows = slice.Header();
    headerTable->setUpdatesEnabled(false);
    for (const HeaderRow &r : rows) {
        if (!f.isEmpty() && !r.tag.contains(f, Qt::CaseInsensitive) && !r.name.contains(f, Qt::CaseInsensitive))
            continue;
        const int i = headerTable->rowCount();
        headerTable->insertRow(i);
        headerTable->setItem(i, 0, new QTableWidgetItem(r.tag));
        headerTable->setItem(i, 1, new QTableWidgetItem(r.name));
        auto *val = new QTableWidgetItem(r.value);
        val->setToolTip(r.value);
        headerTable->setItem(i, 2, val);
    }
    headerTable->setUpdatesEnabled(true);
}
