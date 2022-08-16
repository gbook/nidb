/* ------------------------------------------------------------------------------
  NIDB imageio.h
  Copyright (C) 2004 - 2022
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

#ifndef SQUIRRELIMAGEIO_H
#define SQUIRRELIMAGEIO_H

#include <QFile>
#include <QString>
#include <QDir>
#include "gdcmReader.h"
#include "gdcmWriter.h"
#include "gdcmAttribute.h"
#include "gdcmStringFilter.h"
#include "gdcmAnonymizer.h"
#include "utils.h"

/**
 * @brief The squirrelImageIO class
 *
 * squirrelImageIO class provides functions to read image headers such as DICOM, PAR/REC, and other formats
 */
class squirrelImageIO
{
public:
	squirrelImageIO();
	~squirrelImageIO();

    /* DICOM & image functions */
    bool ConvertDicom(QString filetype, QString indir, QString outdir, QString bindir, bool gzip, QString uid, QString studynum, QString seriesnum, QString datatype, int &numfilesconv, int &numfilesrenamed, QString &msg);
    bool IsDICOMFile(QString f);
    bool AnonymizeDir(QString dir, int anonlevel, QString randstr1, QString randstr2, QString &msg);
    bool AnonymizeDicomFile(gdcm::Anonymizer &anon, QString infile, QString outfile, std::vector<gdcm::Tag> const &empty_tags, std::vector<gdcm::Tag> const &remove_tags, std::vector< std::pair<gdcm::Tag, std::string> > const & replace_tags, QString &msg);
    QString GetDicomModality(QString f);
    void GetFileType(QString f, QString &fileType, QString &fileModality, QString &filePatientID, QString &fileProtocol);
    bool GetImageFileTags(QString f, QString bindir, bool enablecsa, QHash<QString, QString> &tags, QString &msg);

};

#endif // SQUIRRELIMAGEIO_H
