/* ------------------------------------------------------------------------------
  squirrel-gui packageworker.cpp
  ------------------------------------------------------------------------------ */

#include "packageworker.h"

#include <QDir>
#include <QDirIterator>
#include <QFileInfo>
#include <QMutexLocker>

#include "dicomslice.h"
#include "squirrel.h"

PackageWorker::PackageWorker(QObject *parent) : QObject(parent) {}

PackageWorker::~PackageWorker() {
    QMutexLocker lock(&sqrlMutex);
    sqrl.reset();
}

/* ------------------------------------------------------------ */
QString PackageWorker::DrainLog() {
    QMutexLocker lock(&sqrlMutex);
    if (!sqrl)
        return QString();
    return sqrl->GetLogBuffer();
}

/* ------------------------------------------------------------ */
void PackageWorker::OpenPackage(const QString &path) {
    const QString abs = QFileInfo(path).absoluteFilePath();
    if (!QFileInfo::exists(abs)) {
        emit OpenFailed(QString("The file %1 does not exist.").arg(abs));
        return;
    }

    /* quiet: the library log goes to the log buffer (shown in the log panel),
       not to stdout. Full read (not quick read) so params.json is loaded for
       the Parameters tab. */
    {
        QMutexLocker lock(&sqrlMutex);
        sqrl = std::make_unique<squirrel>(false, true);
        sqrl->SetPackagePath(abs);
        sqrl->SetFileMode(FileMode::ExistingPackage);
        sqrl->SetQuickRead(false);
    }

    /* Read() runs without the mutex held, so DrainLog() can follow its progress.
       sqrl is only replaced on this thread, so the pointer stays valid. */
    if (!sqrl->Read()) {
        emit OpenFailed(QString("%1 could not be read. Is it a squirrel package?").arg(abs));
        return;
    }

    tempDir = std::make_unique<QTemporaryDir>(QDir::tempPath() + "/squirrel-gui-XXXXXX");
    emit Opened(BuildSnapshot(sqrl.get()));
}

/* ------------------------------------------------------------ */
void PackageWorker::Validate() {
    if (!sqrl) {
        emit Validated(false, "No package is open.");
        return;
    }
    const bool ok = sqrl->Validate();
    emit Validated(ok, ok ? "The package is valid." : "The package is not valid. The log panel lists the problems.");
}

/* ------------------------------------------------------------ */
void PackageWorker::Extract(const ExtractRequest &req) {
    cancel = false;
    if (!sqrl) {
        emit ExtractFinished(false, "No package is open.");
        return;
    }
    QString m;
    if (!QDir().mkpath(req.destination)) {
        emit ExtractFinished(false, QString("Could not create %1").arg(req.destination));
        return;
    }

    if (req.plan.wholePackage) {
        emit ExtractProgress(0, 1);
        const bool ok = sqrl->Extract(req.destination, m);
        emit ExtractProgress(1, 1);
        emit ExtractFinished(ok, ok ? QString("Extracted to %1").arg(req.destination) : m);
        return;
    }

    const int total = req.plan.rowIDs.size();
    QStringList failures;
    for (int i = 0; i < total; i++) {
        if (cancel) {
            emit ExtractFinished(false, QString("Cancelled after %1 of %2").arg(i).arg(total));
            return;
        }
        emit ExtractProgress(i, total);
        QString outDir = req.destination;
        if (i < req.plan.subdirs.size())
            outDir += "/" + req.plan.subdirs[i];
        QDir().mkpath(outDir);
        if (!sqrl->ExtractObject(ObjectType(req.plan.objectType), req.plan.rowIDs[i], outDir, m))
            failures << m;
    }
    emit ExtractProgress(total, total);
    if (failures.isEmpty())
        emit ExtractFinished(true, QString("Extracted to %1").arg(req.destination));
    else
        emit ExtractFinished(false, failures.join("\n"));
}

/* ------------------------------------------------------------ */
void PackageWorker::ListFiles(int node, const QString &virtualPath) {
    QStringList files;
    QString m;
    if (!sqrl) {
        emit FilesListed(node, files, "No package is open.");
        return;
    }
    const QString sep = virtualPath.contains('\\') ? "\\" : "/";
    const QString prefix = virtualPath + sep;
    if (!sqrl->GetArchiveFileListing(sqrl->GetPackagePath(), prefix, files, m)) {
        emit FilesListed(node, QStringList(), m);
        return;
    }
    QStringList relative;
    for (const QString &f : files) {
        QString r = f.mid(prefix.size());
        if (!r.isEmpty())
            relative << r;
    }
    relative.sort(Qt::CaseInsensitive);
    emit FilesListed(node, relative, QString());
}

/* ------------------------------------------------------------ */
void PackageWorker::PrepareSeriesImages(int node, const QString &virtualPath) {
    if (!sqrl || !tempDir || !tempDir->isValid()) {
        emit SeriesImagesReady(node, SliceList(), "No package is open.");
        return;
    }

    /* each series is extracted once per session into the window's temp dir */
    const QString outDir = tempDir->filePath(QString("series_%1").arg(node));
    if (!QDir(outDir).exists()) {
        QDir().mkpath(outDir);
        const QString sep = virtualPath.contains('\\') ? "\\" : "/";
        QString m;
        if (!sqrl->ExtractArchiveFilesToDirectory(sqrl->GetPackagePath(), virtualPath + sep + "*", outDir, m)) {
            QDir(outDir).removeRecursively();
            emit SeriesImagesReady(node, SliceList(), m);
            return;
        }
    }

    QStringList files;
    QDirIterator it(outDir, QDir::Files, QDirIterator::Subdirectories);
    while (it.hasNext())
        files << it.next();

    for (const QString &f : files) {
        const QString lower = f.toLower();
        if (lower.endsWith(".nii") || lower.endsWith(".nii.gz")) {
            emit SeriesImagesReady(node, SliceList(), "This series is stored as NIfTI. The NIfTI viewer is planned for a later version.");
            return;
        }
    }

    QStringList candidates;
    for (const QString &f : files) {
        const QString lower = f.toLower();
        if (!lower.endsWith(".json") && !lower.endsWith(".txt") && !lower.endsWith(".csv"))
            candidates << f;
    }
    SliceList slices = ScanDicomSeries(candidates);
    if (slices.isEmpty())
        emit SeriesImagesReady(node, slices, "This series has no DICOM images to show.");
    else
        emit SeriesImagesReady(node, slices, QString());
}
