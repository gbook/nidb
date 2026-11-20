/* ------------------------------------------------------------------------------
  squirrel-gui extractdialog.h
  "Extract to…": choose a destination, then show progress. The extraction
  itself runs on the package worker; MainWindow wires the two together.
  ------------------------------------------------------------------------------ */

#ifndef EXTRACTDIALOG_H
#define EXTRACTDIALOG_H

#include <QDialog>

#include "packagesnapshot.h"

class QCheckBox;
class QLabel;
class QLineEdit;
class QProgressBar;
class QPushButton;
class QWidget;

class ExtractDialog : public QDialog {
    Q_OBJECT
public:
    ExtractDialog(const ExtractPlan &plan, QWidget *parent = nullptr);
    QString Destination() const;      /* final folder, including the optional new folder */

public slots:
    void SetProgress(int done, int total);
    void SetFinished(bool ok, const QString &message);

signals:
    void StartRequested(const QString &destination);
    void CancelRequested();

private:
    void UpdateResult();

    ExtractPlan plan;
    QWidget *form;
    QWidget *progressBox;
    QLineEdit *dest;
    QCheckBox *subfolder;
    QLabel *result;
    QLabel *status;
    QLabel *progressPath;
    QProgressBar *bar;
    QPushButton *openFolder;
    QPushButton *closeButton;
    QPushButton *extractButton;
    bool running = false;
    bool finished = false;
};

#endif // EXTRACTDIALOG_H
