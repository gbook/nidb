/* ------------------------------------------------------------------------------
  squirrel-gui propertygrid.cpp
  ------------------------------------------------------------------------------ */

#include "propertygrid.h"

#include <QGridLayout>
#include <QLabel>
#include <QResizeEvent>
#include <QVBoxLayout>

#include "theme.h"

namespace {
const int cellWidth = 260;
}

PropertyGrid::PropertyGrid(QWidget *parent) : QWidget(parent) {
    /* 1 px gaps over a darker background draw the cell borders */
    setAutoFillBackground(true);
    setBackgroundRole(QPalette::Midlight);
    grid = new QGridLayout(this);
    grid->setContentsMargins(1, 1, 1, 1);
    grid->setSpacing(1);
}

void PropertyGrid::SetProperties(const PropList &props) {
    qDeleteAll(cells);
    cells.clear();

    for (const auto &kv : props) {
        auto *cell = new QWidget(this);
        cell->setAutoFillBackground(true);
        cell->setBackgroundRole(QPalette::Base);
        auto *v = new QVBoxLayout(cell);
        v->setContentsMargins(14, 10, 14, 10);
        v->setSpacing(2);
        auto *key = new QLabel(kv.first);
        QFont kf = key->font();
        kf.setPointSizeF(kf.pointSizeF() * 0.92);
        key->setFont(kf);
        key->setForegroundRole(QPalette::PlaceholderText);
        auto *value = new QLabel(kv.second.isEmpty() ? QString("—") : kv.second);
        value->setWordWrap(true);
        value->setTextInteractionFlags(Qt::TextSelectableByMouse);
        value->setMinimumWidth(cellWidth - 60);
        v->addWidget(key);
        v->addWidget(value);
        v->addStretch(1);
        cells.append(cell);
    }
    columns = 0;
    Reflow();
    setVisible(!props.isEmpty());
}

void PropertyGrid::resizeEvent(QResizeEvent *e) {
    QWidget::resizeEvent(e);
    Reflow();
}

void PropertyGrid::Reflow() {
    const int cols = qMax(1, width() / cellWidth);
    if (cols == columns && grid->count() == cells.size() + fillers.size())
        return;
    columns = cols;
    for (QWidget *c : cells)
        grid->removeWidget(c);
    for (int i = 0; i < grid->columnCount(); i++)
        grid->setColumnStretch(i, 0);
    for (int i = 0; i < cells.size(); i++)
        grid->addWidget(cells[i], i / cols, i % cols);
    /* blank cells finish the last row so the border colour does not show through */
    qDeleteAll(fillers);
    fillers.clear();
    const int rem = cells.isEmpty() ? 0 : (cols - cells.size() % cols) % cols;
    for (int i = 0; i < rem; i++) {
        auto *f = new QWidget(this);
        f->setAutoFillBackground(true);
        f->setBackgroundRole(QPalette::Base);
        grid->addWidget(f, (cells.size() + i) / cols, (cells.size() + i) % cols);
        fillers.append(f);
    }
    /* fill the last row so the background does not show through */
    for (int i = 0; i < cols; i++)
        grid->setColumnStretch(i, 1);
}
