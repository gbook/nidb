/* ------------------------------------------------------------------------------
  squirrel-gui packageworker.h
  Owns the squirrel object on a dedicated thread and runs every library call.

  Usage (from MainWindow): the worker is moveToThread()'ed onto a QThread and
  driven through queued slot calls. Results come back as signals carrying plain
  copied data (PackageSnapshot, QStringList, ...).

  DrainLog() is the only method called directly from the GUI thread: it uses
  squirrel::GetLogBuffer(), which is thread-safe, so the GUI can show progress
  while a long Read() or extract is running.
  ------------------------------------------------------------------------------ */

#ifndef PACKAGEWORKER_H
#define PACKAGEWORKER_H

#include <QMutex>
#include <QObject>
#include <QTemporaryDir>
#include <atomic>
#include <memory>

#include "packagesnapshot.h"

class squirrel;

struct ExtractRequest {
    ExtractPlan plan;
    QString destination;   /* final directory (already includes the optional new folder) */
};
Q_DECLARE_METATYPE(ExtractRequest)

/* one image to show: a file, and a frame within it for multi-frame DICOM */
struct SliceRef {
    QString file;
    int frame = 0;
};
typedef QList<SliceRef> SliceList;
Q_DECLARE_METATYPE(SliceList)

class PackageWorker : public QObject {
    Q_OBJECT
public:
    explicit PackageWorker(QObject *parent = nullptr);
    ~PackageWorker() override;

    QString DrainLog();                 /* thread-safe */
    void RequestCancel() { cancel = true; }

public slots:
    void OpenPackage(const QString &path);
    void Validate();
    void Extract(const ExtractRequest &req);
    void ListFiles(int node, const QString &virtualPath);
    void PrepareSeriesImages(int node, const QString &virtualPath);

signals:
    void Opened(SnapshotPtr snapshot);
    void OpenFailed(const QString &message);
    void Validated(bool valid, const QString &message);
    void ExtractProgress(int done, int total);
    void ExtractFinished(bool ok, const QString &message);
    void FilesListed(int node, const QStringList &files, const QString &error);
    void SeriesImagesReady(int node, const SliceList &slices, const QString &error);

private:
    QMutex sqrlMutex;                   /* guards the sqrl pointer for DrainLog() */
    std::unique_ptr<squirrel> sqrl;
    std::unique_ptr<QTemporaryDir> tempDir;
    std::atomic<bool> cancel{false};
};

#endif // PACKAGEWORKER_H
