/* ------------------------------------------------------------------------------
  NIDB imageio.h
  Copyright (C) 2004 - 2025
  Gregory A Book <gregory.book@hhchealth.org> <gregory.a.book@gmail.com>
  Olin Neuropsychiatry Research Center, Hartford Hospital
  ------------------------------------------------------------------------------
  GPLv3 License:

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
  ------------------------------------------------------------------------------ */

#ifndef IMAGEIO_H
#define IMAGEIO_H

#include "nidb.h"
#include <QFile>
#include <QString>
#include <QDir>
#include <QDebug>
#include "utils.h"

/**
 * @brief The imageIO class
 *
 * imageIO class provides functions to read image headers such as DICOM, PAR/REC, and other formats
 */
class imageIO
{
public:
    imageIO(nidb *n);
    ~imageIO();

    /* DICOM & image functions */
    //QString GetDicomModality(QString f);
    bool AnonymizeDicomDirInPlace(QString dir, int anonlevel, QString &msg);
    bool AnonymizeDicomFile(QString infile, QString outfile, QStringList tagsToChange, QString &msg);
    bool AnonymizeDicomFileInPlace(QString file, QStringList tagsToChange, QString &msg);
    bool AnonymizeDir(QString indir, QString outdir, int anonlevel, QString &msg);
    bool ConvertDicom(QString filetype, QString indir, QString outdir, QString bindir, bool gzip, bool json, QString uid, QString studynum, QString seriesnum, QString bidsSubject, QString bidsSession, BIDSMapping bidsMapping, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg);
    bool GetImageFileTags(QString f, QString bindir, bool enablecsa, QHash<QString, QString> &tags, QString &msg);
    bool IsDICOMFile(QString f);
    void GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol);

private:
    nidb *n;

    /* functions to allow exiftool to run 'interactively' */
    bool StartExiftool();
    bool TerminateExiftool();
    QString RunExiftool(QString arg);
    QProcess *exifProcess;
    QElapsedTimer *exifTimer;

    bool exifPendingTerminate;
    bool exifCmdRunning;
    quint32 exifNextCmdID;
    QByteArrayList exifCmdQueue;
    QProcess::ProcessError exifProcessError;
    QString exifErrorString;
    int exifCmdIDLength;
    qsizetype  _readyBeginPos[2];  // [0] StandardOutput | [1] ErrorOutput
    qsizetype  _readyEndPos[2];    // [0] StandardOutput | [1] ErrorOutput
    QByteArray _outBuff[2];        // [0] StandardOutput | [1] ErrorOutput
};

#endif // IMAGEIO_H
