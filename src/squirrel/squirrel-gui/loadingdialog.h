/* ------------------------------------------------------------------------------
  squirrel-gui loadingdialog.h
  Progress while a package is read. The read cannot be interrupted, so Cancel
  closes the dialog and the window discards the result when it arrives.
  ------------------------------------------------------------------------------ */

#ifndef LOADINGDIALOG_H
#define LOADINGDIALOG_H

#include <QDialog>

class QLabel;
class QPlainTextEdit;

class LoadingDialog : public QDialog {
    Q_OBJECT
public:
    LoadingDialog(const QString &path, QWidget *parent = nullptr);
    void AppendLog(const QString &text);

private:
    QLabel *status;
    QPlainTextEdit *log;
};

#endif // LOADINGDIALOG_H
