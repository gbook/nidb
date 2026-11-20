/* ------------------------------------------------------------------------------
  squirrel-gui loadingdialog.cpp
  ------------------------------------------------------------------------------ */

#include "loadingdialog.h"

#include <QDialogButtonBox>
#include <QLabel>
#include <QPlainTextEdit>
#include <QProgressBar>
#include <QPushButton>
#include <QVBoxLayout>

#include "theme.h"

LoadingDialog::LoadingDialog(const QString &path, QWidget *parent) : QDialog(parent) {
    setWindowTitle("Opening package");
    setModal(true);
    resize(560, 300);

    auto *v = new QVBoxLayout(this);
    v->setSpacing(12);
    auto *title = new QLabel("Opening package");
    QFont tf = title->font();
    tf.setPointSizeF(tf.pointSizeF() * 1.2);
    tf.setWeight(QFont::DemiBold);
    title->setFont(tf);
    auto *pathLabel = new QLabel(path);
    pathLabel->setTextInteractionFlags(Qt::TextSelectableByMouse);
    pathLabel->setForegroundRole(QPalette::PlaceholderText);

    status = new QLabel("Reading…");
    auto *bar = new QProgressBar;
    bar->setRange(0, 0);   /* busy: Read() reports no fraction */
    bar->setTextVisible(false);
    bar->setMaximumHeight(8);

    log = new QPlainTextEdit;
    log->setReadOnly(true);
    log->setMaximumBlockCount(200);
    QFont mono("monospace");
    mono.setStyleHint(QFont::Monospace);
    log->setFont(mono);

    auto *buttons = new QDialogButtonBox(QDialogButtonBox::Cancel);
    connect(buttons, &QDialogButtonBox::rejected, this, &QDialog::reject);

    v->addWidget(title);
    v->addWidget(pathLabel);
    v->addWidget(status);
    v->addWidget(bar);
    v->addWidget(log, 1);
    v->addWidget(buttons);
}

void LoadingDialog::AppendLog(const QString &text) {
    const QStringList lines = text.split('\n', Qt::SkipEmptyParts);
    for (const QString &l : lines) {
        const QString t = l.trimmed();
        if (t.isEmpty())
            continue;
        log->appendPlainText(t);
        status->setText(t.left(120));
    }
}
