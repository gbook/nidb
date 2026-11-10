/* ------------------------------------------------------------------------------
  Squirrel parrec.cpp
  Copyright (C) 2004 - 2026
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

#include "parrec.h"
#include <QFile>
#include <QFileInfo>
#include <QTextStream>
#include <QStringList>
#include <QSet>
#include <QList>
#include <QRegularExpression>

namespace parrec {

    /* ---------------------------------------------------------- */
    /* --------- NormalizeLabel --------------------------------- */
    /* ---------------------------------------------------------- */
    /* Lowercase a PAR label and strip the parts that vary between PAR versions
       or carry no meaning for matching: <...> value hints, [...] units, (...)
       qualifiers and '?'. e.g. "Repetition time [msec]" -> "repetition time" */
    static QString NormalizeLabel(QString label) {
        static const QRegularExpression reAngle("<[^>]*>");
        static const QRegularExpression reSquare("\\[[^\\]]*\\]");
        static const QRegularExpression reParen("\\([^)]*\\)");
        label.remove(reAngle);
        label.remove(reSquare);
        label.remove(reParen);
        label.remove('?');
        return label.simplified().toLower();
    }


    /* ---------------------------------------------------------- */
    /* --------- CamelCase -------------------------------------- */
    /* ---------------------------------------------------------- */
    /* Fallback tag name for labels not in the maps below. "max. number of foo" -> "MaxNumberOfFoo" */
    static QString CamelCase(const QString &label) {
        static const QRegularExpression reNonAlnum("[^A-Za-z0-9]+");
        QString out;
        foreach (QString w, label.split(reNonAlnum, Qt::SkipEmptyParts))
            out += w.left(1).toUpper() + w.mid(1);
        return out;
    }


    /* ---------------------------------------------------------- */
    /* --------- FindParFile ------------------------------------ */
    /* ---------------------------------------------------------- */
    /**
     * @brief Get the .par file for a .par or .rec file path. The .rec file holds
     * only pixel data, so the header comes from its sibling .par file.
     * @param f path to a .par or .rec file
     * @return path to the .par file, or empty string if not found
     */
    QString FindParFile(QString f) {
        if (f.endsWith(".par", Qt::CaseInsensitive))
            return QFileInfo::exists(f) ? f : "";

        if (f.endsWith(".rec", Qt::CaseInsensitive)) {
            QString base = f.left(f.size() - 4);
            foreach (QString ext, QStringList() << ".par" << ".PAR" << ".Par") {
                if (QFileInfo::exists(base + ext))
                    return base + ext;
            }
        }
        return "";
    }


    /* ---------------------------------------------------------- */
    /* --------- ReadParFileTags -------------------------------- */
    /* ---------------------------------------------------------- */
    /**
     * @brief Read a Philips PAR header into tags. Reads every general-information
     * field, and summarizes the per-image table (one value if constant across all
     * images, otherwise the list of distinct values in order of appearance).
     * DICOM-equivalent tags (EchoTime, FlipAngle, SliceThickness, PixelSpacing,
     * Rows, Columns, ...) are also set so PAR series look like DICOM series to
     * consumers of the tags.
     *
     * PHI (patient name, examination name, exam date/time) is stored ONLY under
     * the standard tag names (PatientName, PatientID, StudyDescription, Study/Series
     * Date/Time) so squirrelSeries::AnonymizeParams() removes it. The "Dataset name"
     * comment (an export path that often contains the patient name) is not read.
     *
     * Does not set FileType/Modality other than Modality=MR for an MRSERIES.
     *
     * @param f path to a .par file, or a .rec file with a sibling .par file
     * @param tags [out] tags to add to (existing tags are kept unless overwritten)
     * @param msg [out] log messages
     * @return true if a .par header was read
     */
    bool ReadParFileTags(QString f, QHash<QString, QString> &tags, QString &msg) {

        QString parFile = FindParFile(f);
        if (parFile == "") {
            msg += QString("No .par file found for [%1]\n").arg(f);
            return false;
        }

        QFile inputFile(parFile);
        if (!inputFile.open(QIODevice::ReadOnly | QIODevice::Text)) {
            msg += QString("Unable to open .par file [%1]\n").arg(parFile);
            return false;
        }

        /* general information labels (normalized) -> tag name */
        static const QHash<QString, QString> headerMap = {
            {"series type", "SeriesType"},
            {"series_data_type", "SeriesType"},
            {"acquisition nr", "AcquisitionNumber"},
            {"reconstruction nr", "ReconstructionNumber"},
            {"scan duration", "ScanDurationSeconds"},
            {"max. number of cardiac phases", "MaxNumberCardiacPhases"},
            {"max. number of echoes", "MaxNumberEchoes"},
            {"max. number of slices/locations", "MaxNumberSlices"},
            {"max. number of dynamics", "MaxNumberDynamics"},
            {"max. number of mixes", "MaxNumberMixes"},
            {"patient position", "PatientPosition"},
            {"preparation direction", "PreparationDirection"},
            {"technique", "Technique"},
            {"scan resolution", "ScanResolution"},
            {"scan mode", "ScanMode"},
            {"repetition time", "RepetitionTime"},
            {"fov", "FieldOfView"},
            {"water fat shift", "WaterFatShift"},
            {"angulation midslice", "AngulationMidslice"},
            {"off centre midslice", "OffCentreMidslice"},
            {"flow compensation", "FlowCompensation"},
            {"presaturation", "Presaturation"},
            {"phase encoding velocity", "PhaseEncodingVelocity"},
            {"mtc", "MTC"},
            {"spir", "SPIR"},
            {"epi factor", "EPIFactor"},
            {"dynamic scan", "DynamicScan"},
            {"diffusion", "Diffusion"},
            {"diffusion echo time", "DiffusionEchoTime"},
            {"max. number of diffusion values", "MaxNumberDiffusionValues"},
            {"max. number of gradient orients", "MaxNumberGradientOrients"},
            {"number of label types", "NumberLabelTypes"}
        };

        /* image information columns (normalized) -> tag name. Empty name = not exported
           (per-image indices, or values that differ for nearly every image) */
        static const QHash<QString, QString> imageMap = {
            {"slice number", ""},
            {"echo number", "EchoNumbers"},
            {"dynamic scan number", ""},
            {"cardiac phase number", ""},
            {"image_type_mr", "ImageTypeMR"},
            {"scanning sequence", "ScanningSequence"},
            {"index in rec file", ""},
            {"image pixel size", "BitsAllocated"},
            {"scan percentage", "ScanPercentage"},
            {"recon resolution", "ReconResolution"},
            {"rescale intercept", "RescaleIntercept"},
            {"rescale slope", "RescaleSlope"},
            {"scale slope", "ScaleSlope"},
            {"window center", ""},
            {"window width", ""},
            {"image angulation", "ImageAngulation"},
            {"image offcentre", ""},
            {"slice thickness", "SliceThickness"},
            {"slice gap", "SliceGap"},
            {"image_display_orientation", "ImageDisplayOrientation"},
            {"slice orientation", "SliceOrientation"},
            {"fmri_status_indication", "FMRIStatusIndication"},
            {"image_type_ed_es", "ImageTypeEDES"},
            {"pixel spacing", "ReconPixelSpacing"},
            {"echo_time", "EchoTime"},
            {"dyn_scan_begin_time", ""},
            {"trigger_time", "TriggerTime"},
            {"diffusion_b_factor", "DiffusionBValues"},
            {"number of averages", "NumberOfAverages"},
            {"image_flip_angle", "FlipAngle"},
            {"cardiac frequency", "CardiacFrequency"},
            {"minimum rr-interval", "MinimumRRInterval"},
            {"maximum rr-interval", "MaximumRRInterval"},
            {"turbo factor", "TurboFactor"},
            {"inversion delay", "InversionTime"},
            {"diffusion b value number", ""},
            {"gradient orientation number", ""},
            {"contrast type", "ContrastType"},
            {"diffusion anisotropy type", "DiffusionAnisotropyType"},
            {"diffusion", "DiffusionGradientOrientations"},
            {"label type", "ASLLabelType"}
        };

        /* labels that carry PHI. Handled explicitly below, never through the generic path */
        static const QSet<QString> phiLabels = {"patient name", "examination name", "examination date/time"};

        /* above this many distinct values a column is considered per-image, and is not exported */
        const int maxDistinct = 256;

        static const QRegularExpression reVersion("image export tool\\s+V(\\S+)", QRegularExpression::CaseInsensitiveOption);
        static const QRegularExpression reColumnDef("^#\\s+(.*\\S)\\s+\\((?:(\\d+)\\*)?(integer|float|string)\\)\\s*$", QRegularExpression::CaseInsensitiveOption);
        static const QRegularExpression reWhitespace("\\s+");

        struct Column {
            QString name;   /* normalized definition name */
            int count;      /* number of values (tokens) for this column */
            QStringList distinct;
            QSet<QString> seen;
            bool tooMany = false;
        };
        QList<Column> columns;
        int numTokens(0);
        int numImages(0);
        bool inDefinition(false);

        tags["Manufacturer"] = "Philips";

        QTextStream in(&inputFile);
        while (!in.atEnd()) {
            QString line = in.readLine();
            QString trimmed = line.trimmed();
            if (trimmed.isEmpty())
                continue;

            /* ----- comment lines: version and image column definitions ----- */
            if (trimmed.startsWith("#")) {
                QRegularExpressionMatch vm = reVersion.match(trimmed);
                if (vm.hasMatch())
                    tags["ParRecVersion"] = vm.captured(1);

                if (trimmed.contains("IMAGE INFORMATION DEFINITION", Qt::CaseInsensitive)) {
                    inDefinition = true;
                    continue;
                }
                if (inDefinition && trimmed.contains("=== IMAGE INFORMATION ", Qt::CaseInsensitive)) {
                    inDefinition = false;
                    continue;
                }
                if (inDefinition) {
                    QRegularExpressionMatch cm = reColumnDef.match(trimmed);
                    if (cm.hasMatch()) {
                        Column c;
                        c.name = NormalizeLabel(cm.captured(1));
                        c.count = cm.captured(2).isEmpty() ? 1 : cm.captured(2).toInt();
                        if (c.count < 1) c.count = 1;
                        numTokens += c.count;
                        columns.append(c);
                    }
                }
                continue;
            }

            /* ----- general information lines: ".    Label    :   value" ----- */
            if (trimmed.startsWith(".")) {
                int colon = trimmed.indexOf(':');
                if (colon < 0)
                    continue;
                QString rawLabel = trimmed.mid(1, colon - 1);
                QString value = trimmed.mid(colon + 1).simplified();
                QString label = NormalizeLabel(rawLabel);
                if (label.isEmpty())
                    continue;

                if (phiLabels.contains(label)) {
                    if (label == "patient name") {
                        tags["PatientName"] = value;
                        tags["PatientID"] = value;
                    }
                    else if (label == "examination name") {
                        tags["StudyDescription"] = value;
                    }
                    else if (label == "examination date/time") {
                        /* "2014.08.05 / 11:27:34" */
                        QStringList dt = value.split("/");
                        if (dt.size() >= 2) {
                            tags["StudyDate"] = tags["SeriesDate"] = dt[0].trimmed().replace(".", "-");
                            tags["StudyTime"] = tags["SeriesTime"] = dt[1].trimmed();
                        }
                    }
                    continue;
                }

                if (label == "protocol name") {
                    tags["ProtocolName"] = value;
                    tags["SeriesDescription"] = value;
                    continue;
                }

                QString key = headerMap.value(label, "");
                if (key.isEmpty()) {
                    key = CamelCase(label);
                    if (key.isEmpty() || tags.contains(key))
                        continue;
                }
                tags[key] = value;

                if ((key == "SeriesType") && value.contains("MRSERIES", Qt::CaseInsensitive))
                    tags["Modality"] = "MR";
                continue;
            }

            /* ----- image information lines (one per image) ----- */
            if ((numTokens == 0) || !trimmed.at(0).isDigit())
                continue;
            QStringList tokens = trimmed.split(reWhitespace, Qt::SkipEmptyParts);
            if (tokens.size() < numTokens)
                continue;

            numImages++;
            int pos(0);
            for (int i = 0; i < columns.size(); i++) {
                Column &c = columns[i];
                QString v = tokens.mid(pos, c.count).join(" ");
                pos += c.count;
                if (c.tooMany || c.seen.contains(v))
                    continue;
                if (c.distinct.size() >= maxDistinct) {
                    c.tooMany = true;
                    c.distinct.clear();
                    c.seen.clear();
                    continue;
                }
                c.seen.insert(v);
                c.distinct.append(v);
            }
        }
        inputFile.close();

        /* ----- summarize the image table ----- */
        if (numImages > 0)
            tags["NumberOfImages"] = QString::number(numImages);

        QHash<QString, QStringList> col; /* tag name -> distinct values */
        foreach (const Column &c, columns) {
            QString key;
            if (imageMap.contains(c.name))
                key = imageMap.value(c.name);
            else
                key = CamelCase(c.name);

            if (key.isEmpty() || c.tooMany || c.distinct.isEmpty())
                continue;

            /* image table values are per-image, and more precise than any header value with the same name */
            col[key] = c.distinct;
            tags[key] = c.distinct.join(", ");
        }

        /* slice orientation as text (1=TRA, 2=SAG, 3=COR) */
        if (col.contains("SliceOrientation")) {
            QStringList o;
            foreach (QString v, col["SliceOrientation"]) {
                if (v == "1") o << "TRA";
                else if (v == "2") o << "SAG";
                else if (v == "3") o << "COR";
                else o << v;
            }
            tags["SliceOrientation"] = o.join(", ");
        }

        /* ----- DICOM-equivalent tags ----- */
        if (tags.contains("AcquisitionNumber"))
            tags["SeriesNumber"] = tags["AcquisitionNumber"];
        if (tags.contains("Technique"))
            tags["SequenceName"] = tags["Technique"];
        if (tags.contains("MaxNumberSlices"))
            tags["zsize"] = tags["MaxNumberSlices"];
        if (tags.contains("MaxNumberDynamics"))
            tags["NumberOfTemporalPositions"] = tags["MaxNumberDynamics"];

        if (col.value("ReconResolution").size() == 1) {
            QStringList r = col["ReconResolution"][0].split(" ");
            if (r.size() == 2) {
                tags["Columns"] = r[0];
                tags["Rows"] = r[1];
            }
        }
        if (col.value("ReconPixelSpacing").size() == 1) {
            QStringList p = col["ReconPixelSpacing"][0].split(" ");
            if (p.size() == 2)
                tags["PixelSpacing"] = p[0] + "\\" + p[1];
        }
        if ((col.value("SliceThickness").size() == 1) && (col.value("SliceGap").size() == 1)) {
            bool ok1(false), ok2(false);
            double thick = col["SliceThickness"][0].toDouble(&ok1);
            double gap = col["SliceGap"][0].toDouble(&ok2);
            if (ok1 && ok2)
                tags["SpacingBetweenSlices"] = QString::number(thick + gap);
        }

        msg += QString("Read %1 tags and %2 image lines from .par file [%3]\n").arg(tags.size()).arg(numImages).arg(parFile);
        return true;
    }

}
