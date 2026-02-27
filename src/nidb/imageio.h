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
#include "dcmtk/config/osconfig.h"
#include "dcmtk/dcmdata/dcfilefo.h"
#include "dcmtk/dcmdata/dcdatset.h"
#include "dcmtk/dcmdata/dcdeftag.h"
#include "dcmtk/dcmdata/dcdict.h"
#include "dcmtk/dcmdata/dcuid.h"

struct CsaElement
{
    QString name;
    QString vr;
    QList<QByteArray> values;
};

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
    bool AnonymizeDicomDir(QString indir, QString outdir, int anonlevel, QString &msg);
    bool AnonymizeDicomFile(QString infile, QString outfile, QString &msg);
//    bool AnonymizeDicomFileInPlace(QString file, QStringList tagsToChange, QString &msg);
    //bool AnonymizeDir(QString indir, QString outdir, int anonlevel, QString &msg);
    bool ConvertDicom(QString filetype, QString indir, QString outdir, QString bindir, bool gzip, bool json, QString uid, QString studynum, QString seriesnum, QString bidsSubject, QString bidsSession, BIDSMapping bidsMapping, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg);
    bool IsDICOMFile(QString f);
    void GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol);
    bool GetImageFileTags(QString f, QHash<QString, QString> &tags, QString &msg);
    bool GetImageTagsDCMTK(QString f, QHash<QString, QString> &tags);

private:
    nidb *n;

    /* exiftool helper */
    QString Exiftool(QString arg);

    /* Siemens CSA header parser functions */
    QMap<QString, CsaElement> ParseSiemensCSA(const QByteArray& csa);
    QString csaToString(const QByteArray& v);
    double csaToDouble(const QByteArray& v);
    int csaToInteger(const QByteArray& v);

};

#endif // IMAGEIO_H
