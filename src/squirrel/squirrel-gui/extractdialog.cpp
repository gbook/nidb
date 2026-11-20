/* ------------------------------------------------------------------------------
  squirrel-gui extractdialog.cpp
  ------------------------------------------------------------------------------ */

#include "extractdialog.h"

#include <QCheckBox>
#include <QDesktopServices>
#include <QDir>
#include <QFileDialog>
#include <QFrame>
#include <QHBoxLayout>
#include <QLabel>
#include <QLineEdit>
#include <QProgressBar>
#include <QPushButton>
#include <QUrl>
#include <QVBoxLayout>

#include "settings.h"
#include "theme.h"

namespace {
QLabel *Muted(const QString &text) {
    auto *l = new QLabel(text);
    l->setForegroundRole(QPalette::PlaceholderText);
    return l;
}
QFont Mono() {
    QFont f("monospace");
    f.setStyleHint(QFont::Monospace);
    return f;
}
} // namespace

ExtractDialog::ExtractDialog(const ExtractPlan &p, QWidget *parent) : QDialog(parent), plan(p) {
    setWindowTitle("Extract");
    setModal(true);
    resize(580, 360);

    auto *v = new QVBoxLayout(this);
    v->setSpacing(14);

    auto *title = new QLabel("Extract " + plan.title);
    QFont tf = title->font();
    tf.setPointSizeF(tf.pointSizeF() * 1.2);
    tf.setWeight(QFont::DemiBold);
    title->setFont(tf);
    title->setWordWrap(true);
    v->addWidget(title);

    auto *summary = new QFrame;
    summary->setAutoFillBackground(true);
    summary->setBackgroundRole(QPalette::AlternateBase);
    auto *sv = new QVBoxLayout(summary);
    sv->setContentsMargins(12, 10, 12, 10);
    sv->setSpacing(2);
    sv->addWidget(Muted("Will write"));
    auto *detail = new QLabel(plan.detail);
    detail->setWordWrap(true);
    sv->addWidget(detail);
    v->addWidget(summary);

    /* destination form */
    form = new QWidget;
    auto *fv = new QVBoxLayout(form);
    fv->setContentsMargins(0, 0, 0, 0);
    fv->setSpacing(8);
    auto *destLabel = new QLabel("Destination folder");
    QFont lf = destLabel->font();
    lf.setWeight(QFont::Medium);
    destLabel->setFont(lf);
    auto *row = new QHBoxLayout;
    dest = new QLineEdit(QDir::toNativeSeparators(Settings::LastExtractDir()));
    dest->setFont(Mono());
    destLabel->setBuddy(dest);
    auto *browse = new QPushButton("Browse…");
    row->addWidget(dest, 1);
    row->addWidget(browse);
    subfolder = new QCheckBox(QString("Put it in a new folder named %1").arg(plan.folderName));
    subfolder->setChecked(true);
    result = Muted(QString());
    result->setFont(Mono());
    result->setWordWrap(true);
    result->setTextInteractionFlags(Qt::TextSelectableByMouse);
    fv->addWidget(destLabel);
    fv->addLayout(row);
    fv->addWidget(subfolder);
    fv->addWidget(result);
    v->addWidget(form);

    /* progress */
    progressBox = new QWidget;
    auto *pv = new QVBoxLayout(progressBox);
    pv->setContentsMargins(0, 0, 0, 0);
    status = new QLabel("Extracting files from the archive…");
    bar = new QProgressBar;
    bar->setRange(0, 0);
    progressPath = Muted(QString());
    progressPath->setFont(Mono());
    progressPath->setWordWrap(true);
    pv->addWidget(status);
    pv->addWidget(bar);
    pv->addWidget(progressPath);
    progressBox->setVisible(false);
    v->addWidget(progressBox);

    v->addStretch(1);

    auto *buttons = new QHBoxLayout;
    buttons->addStretch(1);
    openFolder = new QPushButton("Open folder");
    openFolder->setVisible(false);
    closeButton = new QPushButton("Cancel");
    extractButton = new QPushButton("Extract");
    extractButton->setDefault(true);
    buttons->addWidget(openFolder);
    buttons->addWidget(closeButton);
    buttons->addWidget(extractButton);
    v->addLayout(buttons);

    connect(dest, &QLineEdit::textChanged, this, &ExtractDialog::UpdateResult);
    connect(subfolder, &QCheckBox::toggled, this, &ExtractDialog::UpdateResult);
    connect(browse, &QPushButton::clicked, this, [this]() {
        QString d = QFileDialog::getExistingDirectory(this, "Choose a destination folder", dest->text());
        if (!d.isEmpty())
            dest->setText(QDir::toNativeSeparators(d));
    });
    connect(extractButton, &QPushButton::clicked, this, [this]() {
        if (dest->text().trimmed().isEmpty())
            return;
        Settings::SetLastExtractDir(QDir::fromNativeSeparators(dest->text().trimmed()));
        running = true;
        form->setEnabled(false);
        form->setVisible(false);
        progressBox->setVisible(true);
        progressPath->setText(QDir::toNativeSeparators(Destination()));
        extractButton->setVisible(false);
        emit StartRequested(Destination());
    });
    connect(closeButton, &QPushButton::clicked, this, [this]() {
        if (running && !finished) {
            /* stops after the current object; a single object cannot be interrupted */
            emit CancelRequested();
            closeButton->setEnabled(false);
            status->setText("Cancelling after the current item…");
            return;
        }
        reject();
    });
    connect(openFolder, &QPushButton::clicked, this, [this]() {
        QDesktopServices::openUrl(QUrl::fromLocalFile(Destination()));
    });

    UpdateResult();
}

QString ExtractDialog::Destination() const {
    QString d = QDir::fromNativeSeparators(dest->text().trimmed());
    while (d.size() > 1 && d.endsWith('/'))
        d.chop(1);
    if (subfolder->isChecked())
        d += "/" + plan.folderName;
    return d;
}

void ExtractDialog::UpdateResult() {
    result->setText("Result: " + QDir::toNativeSeparators(Destination() + "/"));
}

void ExtractDialog::SetProgress(int done, int total) {
    if (total <= 1) {
        bar->setRange(0, 0);
        return;
    }
    bar->setRange(0, total);
    bar->setValue(done);
    status->setText(QString("Extracting %1 of %2…").arg(qMin(done + 1, total)).arg(total));
}

void ExtractDialog::SetFinished(bool ok, const QString &message) {
    finished = true;
    running = false;
    bar->setRange(0, 1);
    bar->setValue(1);
    status->setText(ok ? "Done" : "Extract failed");
    if (!ok)
        progressPath->setText(message);
    openFolder->setVisible(ok);
    closeButton->setEnabled(true);
    closeButton->setText("Close");
}
