/* ------------------------------------------------------------------------------
  squirrel-gui startpage.cpp
  ------------------------------------------------------------------------------ */

#include "startpage.h"

#include <QDate>
#include <QFileInfo>
#include <QHBoxLayout>
#include <QLabel>
#include <QListWidget>
#include <QPainter>
#include <QPainterPath>
#include <QPushButton>
#include <QVBoxLayout>

#include "icons.h"
#include "settings.h"
#include "theme.h"

/* ------------------------------------------------------------ */
DropArea::DropArea(QWidget *parent) : QFrame(parent) {
    setMinimumHeight(220);
}

void DropArea::SetActive(bool a) {
    active = a;
    update();
}

void DropArea::paintEvent(QPaintEvent *) {
    QPainter p(this);
    p.setRenderHint(QPainter::Antialiasing);
    const QPalette pal = palette();
    QRectF r = QRectF(rect()).adjusted(1, 1, -1, -1);
    QColor fill = active ? pal.color(QPalette::Highlight) : pal.color(QPalette::Base);
    if (active)
        fill.setAlpha(28);
    p.setBrush(fill);
    QPen pen(active ? pal.color(QPalette::Highlight) : pal.color(QPalette::Mid), 2, Qt::DashLine);
    p.setPen(pen);
    p.drawRoundedRect(r, 12, 12);
}

/* ------------------------------------------------------------ */
StartPage::StartPage(QWidget *parent) : QWidget(parent) {
    auto *outer = new QHBoxLayout(this);
    outer->setContentsMargins(24, 48, 24, 24);
    auto *column = new QWidget;
    column->setMaximumWidth(720);
    outer->addStretch(1);
    outer->addWidget(column, 4);
    outer->addStretch(1);

    auto *v = new QVBoxLayout(column);
    v->setContentsMargins(0, 0, 0, 0);
    v->setSpacing(24);

    auto *title = new QLabel("Squirrel");
    QFont tf = title->font();
    tf.setPointSizeF(tf.pointSizeF() * 2.0);
    tf.setWeight(QFont::DemiBold);
    title->setFont(tf);
    auto *subtitle = new QLabel("Browse the subjects, studies, series and images in a squirrel package.");
    subtitle->setWordWrap(true);
    {
        subtitle->setForegroundRole(QPalette::PlaceholderText);
    }
    auto *head = new QVBoxLayout;
    head->setSpacing(4);
    head->addWidget(title);
    head->addWidget(subtitle);
    v->addLayout(head);

    /* drop area with the Open button inside */
    dropArea = new DropArea;
    auto *dv = new QVBoxLayout(dropArea);
    dv->setAlignment(Qt::AlignCenter);
    dv->setSpacing(10);
    auto *icon = new QLabel;
    icon->setPixmap(Icons::ForNode(NodeKind::Package).pixmap(40, 40));
    icon->setAlignment(Qt::AlignCenter);
    auto *dropText = new QLabel("Drop a .sqrl file here");
    QFont df = dropText->font();
    df.setPointSizeF(df.pointSizeF() * 1.15);
    df.setWeight(QFont::Medium);
    dropText->setFont(df);
    dropText->setAlignment(Qt::AlignCenter);
    auto *orLabel = new QLabel("or");
    orLabel->setAlignment(Qt::AlignCenter);
    auto *openButton = new QPushButton("Open package…");
    openButton->setDefault(true);
    openButton->setMinimumHeight(34);
    openButton->setIcon(Icons::Open());
    auto *shortcut = new QLabel("Ctrl+O");
    shortcut->setAlignment(Qt::AlignCenter);
    for (QLabel *l : {orLabel, shortcut}) {
        l->setForegroundRole(QPalette::PlaceholderText);
    }
    dv->addWidget(icon);
    dv->addWidget(dropText);
    dv->addWidget(orLabel);
    dv->addWidget(openButton, 0, Qt::AlignCenter);
    dv->addWidget(shortcut);
    v->addWidget(dropArea);
    connect(openButton, &QPushButton::clicked, this, &StartPage::OpenRequested);

    /* recent packages */
    recentSection = new QWidget;
    auto *rv = new QVBoxLayout(recentSection);
    rv->setContentsMargins(0, 0, 0, 0);
    rv->setSpacing(8);
    auto *rh = new QHBoxLayout;
    auto *recentTitle = new QLabel("Recent packages");
    QFont rf = recentTitle->font();
    rf.setWeight(QFont::DemiBold);
    recentTitle->setFont(rf);
    clearButton = new QPushButton("Clear list");
    clearButton->setFlat(true);
    rh->addWidget(recentTitle);
    rh->addStretch(1);
    rh->addWidget(clearButton);
    rv->addLayout(rh);
    recentList = new QListWidget;
    recentList->setSelectionMode(QAbstractItemView::NoSelection);
    recentList->setCursor(Qt::PointingHandCursor);
    recentList->setSpacing(0);
    rv->addWidget(recentList, 1);
    v->addWidget(recentSection, 3);
    v->addStretch(1);

    connect(recentList, &QListWidget::itemClicked, this, [this](QListWidgetItem *item) {
        emit RecentRequested(item->data(Qt::UserRole).toString());
    });
    connect(recentList, &QListWidget::itemActivated, this, [this](QListWidgetItem *item) {
        emit RecentRequested(item->data(Qt::UserRole).toString());
    });
    connect(clearButton, &QPushButton::clicked, this, [this]() {
        Settings::ClearRecent();
        Refresh();
    });

    Refresh();
}

void StartPage::SetDragActive(bool a) {
    dropArea->SetActive(a);
}

void StartPage::Refresh() {
    recentList->clear();
    const QList<RecentPackage> recent = Settings::Recent();
    recentSection->setVisible(!recent.isEmpty());

    for (const RecentPackage &r : recent) {
        auto *item = new QListWidgetItem(recentList);
        item->setData(Qt::UserRole, r.path);
        item->setToolTip(r.path);

        auto *w = new QWidget;
        auto *h = new QHBoxLayout(w);
        h->setContentsMargins(12, 8, 12, 8);
        h->setSpacing(16);
        auto *texts = new QVBoxLayout;
        texts->setSpacing(2);
        auto *name = new QLabel(r.name.isEmpty() ? QFileInfo(r.path).fileName() : r.name);
        QFont nf = name->font();
        nf.setWeight(QFont::Medium);
        name->setFont(nf);
        auto *path = new QLabel(r.path);
        QFont pf("monospace");
        pf.setStyleHint(QFont::Monospace);
        pf.setPointSizeF(name->font().pointSizeF() * 0.9);
        path->setFont(pf);
        texts->addWidget(name);
        texts->addWidget(path);
        h->addLayout(texts, 1);

        QString when;
        if (r.opened.isValid()) {
            const QDate d = r.opened.date();
            if (d == QDate::currentDate())
                when = "Today " + r.opened.toString("HH:mm");
            else if (d == QDate::currentDate().addDays(-1))
                when = "Yesterday";
            else
                when = r.opened.toString("MMM d");
        }
        auto *summary = new QLabel(r.summary);
        auto *whenLabel = new QLabel(when);
        whenLabel->setMinimumWidth(100);
        whenLabel->setAlignment(Qt::AlignRight | Qt::AlignVCenter);
        for (QLabel *l : {path, summary, whenLabel}) {
            l->setForegroundRole(QPalette::PlaceholderText);
        }
        if (!QFileInfo::exists(r.path))
            summary->setText("File not found");
        h->addWidget(summary);
        h->addWidget(whenLabel);

        item->setSizeHint(w->sizeHint());
        recentList->setItemWidget(item, w);
    }
}
