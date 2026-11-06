/* ------------------------------------------------------------------------------
  Squirrel convert.cpp
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

#include "convert.h"
#include "dicom.h"
#include "bids.h"
#include "squirrel.h"
#include "utils.h"
#include <QDir>
#include <QFileInfo>

convert::convert()
{

}


/* ---------------------------------------------------------------------------- */
/* ----- DoConvert ------------------------------------------------------------ */
/* ---------------------------------------------------------------------------- */
/**
 * @brief Convert an input dataset into an output squirrel package
 * @param inputPath path to the input data directory (DICOM or BIDS)
 * @param outputPath path for the output squirrel package
 * @param inputFormat input data format [bids  dicom]
 * @param outputFormat output data format [squirrel]
 * @param dataFormat output data format for DICOM conversion (anon, nifti3d, nifti4d, ...). Ignored for BIDS input
 * @param dirFormat output directory structure (orig, seq). Empty leaves the squirrel default
 * @param overwrite overwrite an existing output package
 * @param debug enable debug logging
 * @param debugSQL enable SQL statement logging
 * @param quiet suppress output
 * @param m output message describing failure
 * @return true if successful
 */
bool convert::DoConvert(QString inputPath, QString outputPath, QString inputFormat, QString outputFormat, QString dataFormat, QString dirFormat, bool overwrite, bool debug, bool debugSQL, bool quiet, QString &m) {

    inputFormat = inputFormat.trimmed().toLower();
    outputFormat = outputFormat.trimmed().toLower();

    /* validate the input/output formats */
    if ((inputFormat != "bids") && (inputFormat != "dicom")) {
        m = QString("Invalid input format [%1]. Valid input formats: bids, dicom").arg(inputFormat);
        return false;
    }
    if (outputFormat != "squirrel") {
        m = QString("Invalid output format [%1]. Valid output formats: squirrel").arg(outputFormat);
        return false;
    }

    /* check the input directory exists */
    QDir indir(inputPath);
    if (!indir.exists()) {
        m = QString("Input directory [%1] does not exist").arg(indir.absolutePath());
        return false;
    }

    /* check the output package's parent directory exists */
    QFileInfo outinfo(outputPath);
    QDir outdir = outinfo.absolutePath();
    if (!outdir.exists()) {
        m = QString("Output directory [%1] does not exist").arg(outdir.absolutePath());
        return false;
    }

    /* create the squirrel object */
    squirrel *sqrl = new squirrel(debug, quiet);
    sqrl->SetCommandLineExecution(true);
    sqrl->SetDebugSQL(debugSQL);
    sqrl->SetOverwritePackage(overwrite);

    /* apply the output directory format, if specified */
    if (dirFormat != "") {
        sqrl->SubjectDirFormat = dirFormat;
        sqrl->StudyDirFormat = dirFormat;
        sqrl->SeriesDirFormat = dirFormat;
    }

    /* load the input data into the squirrel object */
    if (inputFormat == "dicom") {
        if (dataFormat != "")
            sqrl->DataFormat = dataFormat;

        dicom *dcm = new dicom();
        dcm->LoadToSquirrel(inputPath, sqrl);
        delete dcm;
    }
    else if (inputFormat == "bids") {
        sqrl->DataFormat = "orig";

        bids *bds = new bids();
        bds->LoadToSquirrel(indir.path(), sqrl);
        delete bds;
    }

    /* write the squirrel package */
    sqrl->SetPackagePath(outputPath);
    sqrl->SetWriteLog(true);
    bool writeOK = sqrl->Write();
    delete sqrl;

    if (!writeOK) {
        m = QString("Failed to write squirrel package [%1]").arg(outputPath);
        return false;
    }

    return true;
}
