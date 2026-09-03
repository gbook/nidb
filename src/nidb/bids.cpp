#include "bids.h"

#include <QDir>
#include <QDirIterator>
#include <QFile>
#include <QFileInfo>
#include <QJsonArray>
#include <QJsonDocument>
#include <QJsonParseError>
#include <QTextStream>
#include <QStringConverter>
#include <algorithm>

namespace bids {


/* ---------------------------------------------------------- */
/* --------- MapBidsDatatypeToModality ---------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Map a list of BIDS datatypes to their NiDB/DICOM equivalent modality(s).
 *        See the declaration in bids.h for behavior details.
 */
QStringList MapBidsDatatypeToModality(QStringList bidsDatatypes) {
    /* Every BIDS datatype (the sub-XX/[ses-YY]/<datatype>/ directory name defined
     * by the BIDS specification) mapped to its NiDB/DICOM equivalent modality.
     * Mappings that are not yet known are left blank ("") to be filled in later;
     * a blank mapping contributes no modality to the result. */
    static const QMap<QString, QString> datatypeToModality = {
        /* --- MR-based ------------------------------------------------ */
        { "anat",   "MR"  },   /* anatomical / structural MRI */
        { "func",   "MR"  },   /* task & resting-state functional MRI */
        { "dwi",    "MR"  },   /* diffusion-weighted MRI */
        { "fmap",   "MR"  },   /* field maps (MRI) */
        { "perf",   "MR"  },   /* perfusion (ASL) MRI */
        { "mrs",    ""    },   /* magnetic resonance spectroscopy */
        /* --- electrophysiology -------------------------------------- */
        { "eeg",    "EEG" },   /* scalp electroencephalography */
        { "ieeg",   "EEG" },   /* intracranial EEG (ECoG / sEEG) */
        { "meg",    "MEG" },   /* magnetoencephalography */
        /* --- nuclear medicine --------------------------------------- */
        { "pet",    "PET"    },   /* positron emission tomography */
        /* --- optical ------------------------------------------------ */
        { "nirs",   "NIRS"    },   /* (functional) near-infrared spectroscopy */
        /* --- microscopy --------------------------------------------- */
        { "micr",   "MICR"    },   /* microscopy */
        /* --- other -------------------------------------------------- */
        { "motion", "OT"    },   /* motion capture */
        { "beh",    "OT"    },   /* behavioral (no imaging data) */
    };

    QStringList modalities;
    for (const QString &dt : bidsDatatypes) {
        /* normalize to lower case so the lookup is case-insensitive (map keys are lower case) */
        const QString key = dt.trimmed().toLower();
        if (!datatypeToModality.contains(key))
            continue;
        const QString modality = datatypeToModality.value(key);
        if (!modality.isEmpty() && !modalities.contains(modality))
            modalities.append(modality);
    }
    return modalities;
}


/* ---------------------------------------------------------- */
/* --------- NormalizeBidsSex ------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Map a BIDS participants.tsv 'sex' value to a NiDB sex code (M/F/O/U).
 *        See the declaration in bids.h for behavior details.
 */
QString NormalizeBidsSex(QString bidsSex) {
    /* normalize to lower case so the lookup is case-insensitive */
    const QString key = bidsSex.trimmed().toLower();

    if (key == "m" || key == "male")
        return "M";
    if (key == "f" || key == "female")
        return "F";
    if (key == "o" || key == "other")
        return "O";

    /* "n/a", blank, or anything unrecognized */
    return "U";
}


/* ---------------------------------------------------------- */
/* --------- Acquisition::ResolvedMetadataAsMap ------------- */
/* ---------------------------------------------------------- */
namespace {

/* Convert a scalar JSON value to its unquoted string form. */
static QString jsonScalarToString(const QJsonValue &v) {
    switch (v.type()) {
        case QJsonValue::String: return v.toString();
        case QJsonValue::Bool:   return v.toBool() ? "true" : "false";
        /* 'g' with 15 significant digits: prints 2.0 as "2" and preserves 0.03 etc. */
        case QJsonValue::Double: return QString::number(v.toDouble(), 'g', 15);
        case QJsonValue::Null:   return QString();
        default:                 return QString();
    }
}

/* Join a JSON array into a DICOM multi-valued string (values separated by '\'). */
static QString jsonArrayToDicomString(const QJsonArray &arr) {
    QStringList parts;
    for (const QJsonValue &v : arr) {
        if (v.isArray())
            parts << jsonArrayToDicomString(v.toArray());
        else if (v.isObject())
            /* non-scalar element: keep it as compact JSON rather than dropping it */
            parts << QString::fromUtf8(QJsonDocument(v.toObject()).toJson(QJsonDocument::Compact)).trimmed();
        else
            parts << jsonScalarToString(v);
    }
    return parts.join('\\');
}

/* Recursively flatten a JSON object into dot-joined keys. */
static void flattenMetadataObject(const QJsonObject &obj, const QString &prefix, QMap<QString, QString> &out) {
    for (auto it = obj.constBegin(); it != obj.constEnd(); ++it) {
        const QString key = prefix.isEmpty() ? it.key() : (prefix + "." + it.key());
        const QJsonValue v = it.value();
        if (v.isObject())
            flattenMetadataObject(v.toObject(), key, out);
        else if (v.isArray())
            out.insert(key, jsonArrayToDicomString(v.toArray()));
        else
            out.insert(key, jsonScalarToString(v));
    }
}

} // anonymous namespace

/**
 * @brief Flatten resolvedMetadata into DICOM-style tag/value pairs.
 *        See the declaration in bids.h for behavior details.
 */
QMap<QString, QString> Acquisition::ResolvedMetadataAsMap() const {
    QMap<QString, QString> out;
    flattenMetadataObject(resolvedMetadata, QString(), out);
    return out;
}


/* ---------------------------------------------------------- */
/* --------- TsvReader::read -------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Read a generic TSV file into a header list and row maps. Used for participants.tsv and *_scans.tsv.
 * @param path Path to the .tsv file
 * @param headers List of headers found in the file
 * @param rows List of rows found in the file
 * @param error Any error messages while parsing the tsv
 * @return true if successful, false otherwise
 */
bool TsvReader::read(const QString &path, QStringList &headers, QList<QVariantMap> &rows, QString &error) {
    QFile file(path);
    if (!file.open(QIODevice::ReadOnly | QIODevice::Text)) {
        error = QString("Unable to open TSV file: %1").arg(path);
        return false;
    }

    QTextStream in(&file);
    in.setEncoding(QStringConverter::Utf8);

    if (in.atEnd()) {
        error = QString("TSV file is empty: %1").arg(path);
        return false;
    }

    headers = in.readLine().split('\t', Qt::KeepEmptyParts);
    if (headers.isEmpty()) {
        error = QString("TSV header row is empty: %1").arg(path);
        return false;
    }

    while (!in.atEnd()) {
        const QString line = in.readLine();
        if (line.trimmed().isEmpty()) {
            continue;
        }

        const QStringList cols = line.split('\t', Qt::KeepEmptyParts);
        QVariantMap row;
        for (int i = 0; i < headers.size(); ++i) {
            row.insert(headers[i], i < cols.size() ? cols[i] : QString());
        }
        rows.append(row);
    }

    return true;
}




/* ---------------------------------------------------------- */
/* --------- FilenameParser::parse -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Parse a BIDS-style filename into entities, suffix, and extension. This parser is intentionally lightweight and supports parsing/grouping.
 * @param fileName Filename to parse
 * @return a ParseResult object
 */
ParseResult FilenameParser::parse(const QString &fileName) {
    ParseResult out;

    QString stem = fileName;

    /* Multi-part extensions must be checked before falling back to the single-part
     * QFileInfo::suffix(), otherwise the leading part leaks into the stem/suffix
     * (eg "sub-01_T1w.ome.tif" would parse a suffix of "T1w.ome"). */
    static const QStringList multiExts = {
        ".nii.gz", ".tsv.gz", ".ome.tif", ".ome.tiff", ".ome.zarr"
    };
    bool matchedMulti = false;
    for (const QString &e : multiExts) {
        if (stem.endsWith(e, Qt::CaseInsensitive)) {
            out.extension = e;
            stem.chop(e.size());
            matchedMulti = true;
            break;
        }
    }
    if (!matchedMulti) {
        const QFileInfo fi(fileName);
        const QString suffix = fi.suffix();
        if (!suffix.isEmpty()) {
            out.extension = "." + suffix;
            stem = fi.completeBaseName();
        } else {
            out.extension.clear();
            stem = fileName;
        }
    }

    QStringList parts = stem.split('_', Qt::SkipEmptyParts);
    if (parts.isEmpty()) {
        return out;
    }

    if (!parts.last().contains('-')) {
        out.suffix = parts.takeLast();
    }

    for (const QString &part : parts) {
        const int dash = part.indexOf('-');
        if (dash <= 0 || dash == part.size() - 1) {
            continue;
        }
        out.entities.insert(part.left(dash), part.mid(dash + 1));
    }

    out.valid = out.entities.contains("sub") && !out.suffix.isEmpty();
    return out;
}


/* ---------------------------------------------------------- */
/* --------- FilenameParser::canonicalStem ------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Build a canonical identifier from ordered BIDS entities plus suffix. Reader uses this key to group related files into one Acquisition.
 * @param entities Parsed BIDS entities such as sub, ses, task, acq, run, and dir
 * @param suffix The acquisition suffix, such as bold, T1w, dwi, or events
 * @return A canonical key string that can be used to group related files
 */
QString FilenameParser::canonicalStem(const QMap<QString, QString> &entities, const QString &suffix) {
    static const QStringList order = {
        "sub", "ses", "sample", "task", "tracksys", "acq", "ce", "trc", "stain",
        "rec", "dir", "run", "mod", "echo", "flip", "inv", "mt", "part", "proc",
        "hemi", "space", "split", "recording", "chunk", "seg", "res", "den",
        "label", "desc"
    };

    QStringList parts;
    for (const QString &key : order) {
        auto it = entities.find(key);
        if (it != entities.end() && !it.value().isEmpty()) {
            parts << QString("%1-%2").arg(key, it.value());
        }
    }

    QStringList extras;
    for (auto it = entities.begin(); it != entities.end(); ++it) {
        if (!order.contains(it.key()) && !it.value().isEmpty()) {
            extras << QString("%1-%2").arg(it.key(), it.value());
        }
    }
    std::sort(extras.begin(), extras.end());
    parts.append(extras);

    if (!suffix.isEmpty()) {
        parts << suffix;
    }

    return parts.join('_');
}




/* ---------------------------------------------------------- */
/* --------- MetadataResolver::resolveDatasetMetadata ------- */
/* ---------------------------------------------------------- */
/**
 * @brief Resolve metadata for every acquisition in the dataset. This is called only after the BidsDataset has been fully read.
 * @param dataset The fully populated BidsDataset to update with resolved JSON metadata
 */
void MetadataResolver::resolveDatasetMetadata(BidsDataset &dataset) {
    for (auto subIt = dataset.subjects.begin(); subIt != dataset.subjects.end(); ++subIt) {
        SubjectRecord &subj = subIt.value();

        for (auto acqIt = subj.acquisitionsWithoutSession.begin(); acqIt != subj.acquisitionsWithoutSession.end(); ++acqIt) {
            resolveAcquisitionMetadata(dataset, acqIt.value());
        }

        for (auto sessIt = subj.sessions.begin(); sessIt != subj.sessions.end(); ++sessIt) {
            for (auto acqIt = sessIt.value().acquisitions.begin(); acqIt != sessIt.value().acquisitions.end(); ++acqIt) {
                resolveAcquisitionMetadata(dataset, acqIt.value());
            }
        }
    }
}


/* ---------------------------------------------------------- */
/* --------- MetadataResolver::resolveAcquisitionMetadata --- */
/* ---------------------------------------------------------- */
/**
 * @brief Resolve inherited JSON metadata for one acquisition. Candidate JSON files are merged from broadest to most specific.
 * @param dataset BidsDataset containing all candidate JSON sidecar files
 * @param acq Acquisition to resolve metadata for
 */
void MetadataResolver::resolveAcquisitionMetadata(const BidsDataset &dataset, Acquisition &acq) {
    const QList<FileRecord> candidates = candidateJsonFiles(dataset, acq);
    if (candidates.isEmpty()) {
        return;
    }

    QList<FileRecord> sorted = candidates;
    std::sort(sorted.begin(), sorted.end(), [&acq](const FileRecord &a, const FileRecord &b) {
        const int pa = commonPrefixDepth(a.relativePath, acq.primaryDataPath);
        const int pb = commonPrefixDepth(b.relativePath, acq.primaryDataPath);
        if (pa != pb) return pa < pb;

        /* within the same directory depth, fewer entities = more general, so it
         * merges first and the more-specific sidecar overrides it */
        const int sa = a.entities.size();
        const int sb = b.entities.size();
        if (sa != sb) return sa < sb;

        return a.relativePath < b.relativePath;
    });

    QJsonObject merged;
    for (const FileRecord &jsonFile : sorted) {
        QJsonObject obj;
        if (readJsonObject(jsonFile.absolutePath, obj)) {
            mergeJson(merged, obj);
        }
    }

    acq.resolvedMetadata = merged;

    for (const FileRecord &jsonFile : sorted) {
        if (jsonFile.relativePath == acq.jsonSidecarPath) {
            return;
        }
    }

    if (!sorted.isEmpty()) {
        acq.inheritedJsonPath = sorted.last().relativePath;
    }
}


/* ---------------------------------------------------------- */
/* --------- MetadataResolver::candidateJsonFiles ----------- */
/* ---------------------------------------------------------- */
/**
 * @brief Collect JSON files that could apply to this acquisition.
 * @param dataset BidsDataset that provides the pool of JSON sidecar files
 * @param acq Acquisition used to test JSON applicability
 * @return List of JSON files that may contribute inherited metadata
 */
QList<FileRecord> MetadataResolver::candidateJsonFiles(const BidsDataset &dataset, const Acquisition &acq) {
    QList<FileRecord> out;
    for (const FileRecord &jsonFile : dataset.jsonFiles) {
        if (jsonAppliesToAcquisition(dataset, jsonFile, acq)) {
            out.append(jsonFile);
        }
    }
    return out;
}


/* ---------------------------------------------------------- */
/* --------- MetadataResolver::jsonAppliesToAcquisition ----- */
/* ---------------------------------------------------------- */
/**
 * @brief Return true if one JSON sidecar applies to an acquisition. The sidecar must match by suffix and by entity subset, and must be located in the acquisition directory or one of its parent directories.
 * @param dataset BidsDataset used to resolve relative path relationships
 * @param jsonFile JSON file being tested
 * @param acq Acquisition that may receive metadata from the JSON file
 * @return True if the JSON file is an applicable sidecar for the acquisition
 */
bool MetadataResolver::jsonAppliesToAcquisition(const BidsDataset &dataset, const FileRecord &jsonFile, const Acquisition &acq) {
    if (jsonFile.extension.toLower() != ".json") {
        return false;
    }

    if (!jsonFile.suffix.isEmpty() && jsonFile.suffix != acq.suffix) {
        return false;
    }

    for (auto it = jsonFile.entities.begin(); it != jsonFile.entities.end(); ++it) {
        auto acqIt = acq.entities.find(it.key());
        if (acqIt == acq.entities.end() || acqIt.value() != it.value()) {
            return false;
        }
    }

    const QString refPath = acq.primaryDataPath.isEmpty() && !acq.files.isEmpty()
        ? acq.files.first().relativePath
        : acq.primaryDataPath;

    if (refPath.isEmpty()) {
        return false;
    }

    const QFileInfo acqInfo(QDir(dataset.rootPath).filePath(refPath));
    const QFileInfo jsonInfo(QDir(dataset.rootPath).filePath(jsonFile.relativePath));

    const QString acqDirPath = acqInfo.dir().absolutePath();
    const QString jsonDirPath = jsonInfo.dir().absolutePath();

    /* the sidecar's directory must BE the acquisition directory or a true ancestor
     * of it. A raw startsWith() would false-match sibling dirs that share a name
     * prefix (eg "/ds/anat" would wrongly apply to "/ds/anat2"), so require an
     * exact match or a path-separator boundary. */
    return (acqDirPath == jsonDirPath) || acqDirPath.startsWith(jsonDirPath + "/");
}


/* ---------------------------------------------------------- */
/* --------- MetadataResolver::commonPrefixDepth ------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Measure shared path-prefix depth. Used to help order broad JSON before specific JSON during merging.
 * @param a First relative path
 * @param b Second relative path
 * @return Number of leading path components shared by both paths
 */
int MetadataResolver::commonPrefixDepth(const QString &a, const QString &b) {
    const QStringList ap = a.split('/', Qt::SkipEmptyParts);
    const QStringList bp = b.split('/', Qt::SkipEmptyParts);
    const int n = std::min(ap.size(), bp.size());

    int i = 0;
    while (i < n && ap[i] == bp[i]) {
        ++i;
    }
    return i;
}


/* ---------------------------------------------------------- */
/* --------- MetadataResolver::readJsonObject --------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Read a JSON object from disk. Returns false if the file cannot be read or is not a JSON object.
 * @param path Path to the JSON file
 * @param obj Parsed JSON object on success
 * @return True if the file could be read and parsed as a JSON object
 */
bool MetadataResolver::readJsonObject(const QString &path, QJsonObject &obj) {
    QFile f(path);
    if (!f.open(QIODevice::ReadOnly)) {
        return false;
    }

    QJsonParseError pe;
    const QJsonDocument doc = QJsonDocument::fromJson(f.readAll(), &pe);
    if (pe.error != QJsonParseError::NoError || !doc.isObject()) {
        return false;
    }

    obj = doc.object();
    return true;
}

/* ---------------------------------------------------------- */
/* --------- MetadataResolver::mergeJson -------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Merge overlay into base so later values override earlier ones. This is a
 *        shallow (top-level key) merge, which is exactly what BIDS metadata
 *        inheritance requires.
 * @param base JSON object to receive the merged values
 * @param overlay JSON object whose values should overwrite base values
 */
void MetadataResolver::mergeJson(QJsonObject &base, const QJsonObject &overlay) {
    for (auto it = overlay.begin(); it != overlay.end(); ++it) {
        base.insert(it.key(), it.value());
    }
}





/* ---------------------------------------------------------- */
/* --------- Reader::readDataset ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Main entry point for reading a BIDS dataset. The order is:
 *   1) read dataset_description.json and participants.tsv
 *   2) walk the tree in two passes: data files, then OME-Zarr store directories
 *      (skipping reserved dirs like derivatives/, and files inside zarr stores)
 *   3) attach tabular metadata (participants.tsv rows, scans.tsv rows)
 *   4) resolve inherited JSON metadata
 * @param rootPath Root directory of the BIDS dataset
 * @param out Populated BidsDataset on success
 * @param error Error message if reading fails
 * @return True if the dataset was read successfully
 */
bool Reader::readDataset(const QString &rootPath, BidsDataset &out, QString &error) const {
    out = BidsDataset{};
    out.rootPath = QDir(rootPath).absolutePath();

    const QDir root(out.rootPath);
    if (!root.exists()) {
        error = QString("Dataset directory does not exist: %1").arg(out.rootPath);
        return false;
    }

    /* dataset_description.json is BIDS-required, but we tolerate its absence so that
     * "BIDS-like" datasets (correct sub-/ses- layout and sidecars, but no root
     * metadata) can still be imported. A failure here marks the dataset non-compliant
     * and is recorded, but does NOT abort the read. */
    QString ddError;
    if (!readDatasetDescription(root.filePath("dataset_description.json"), out, ddError)) {
        out.bidsCompliant = false;
        out.complianceIssues << ddError;
    }

    /* participants.tsv is only RECOMMENDED by BIDS, so its absence is noted but does
     * not by itself make the dataset non-compliant. */
    if (!QFileInfo::exists(root.filePath("participants.tsv")))
        out.complianceIssues << "participants.tsv not found (no subject-level demographics)";
    readParticipants(root.filePath("participants.tsv"), out);

    /* Reserved top-level directories that are NOT raw data and must be excluded
     * from the raw BidsDataset. Otherwise their sub-XX subtrees (eg
     * derivatives/fmriprep/sub-01/...) would be mis-read as raw acquisitions.
     * derivatives/ and phenotype/ are handled by their own passes at integration. */
    static const QSet<QString> reservedTopDirs = {
        "derivatives", "sourcedata", "code", "stimuli", "phenotype"
    };

    /* OME-Zarr / NGFF images are DIRECTORIES (eg sub-01_T1w.ome.zarr/ holding
     * chunk files), not single files. Return the index of the first path
     * component that is a zarr store, or -1 if the path is not inside one. */
    auto zarrComponentIndex = [](const QString &rel) -> int {
        const QStringList comps = rel.split('/', Qt::SkipEmptyParts);
        for (int i = 0; i < comps.size(); ++i)
            if (comps[i].endsWith(".zarr", Qt::CaseInsensitive))
                return i;
        return -1;
    };
    auto isUnderReservedTop = [&](const QString &rel) -> bool {
        const int slash = rel.indexOf('/');
        return (slash > 0) && reservedTopDirs.contains(rel.left(slash));
    };

    /* pass 1: files. Skip reserved dirs and skip files that live inside a zarr
     * store (the store directory itself is recorded in pass 2). */
    QDirIterator it(out.rootPath, QDir::Files, QDirIterator::Subdirectories);
    while (it.hasNext()) {
        const QString absPath = it.next();
        const QString relPath = root.relativeFilePath(absPath);
        if (relPath == "dataset_description.json" || relPath == "participants.tsv") {
            continue;
        }
        if (isUnderReservedTop(relPath)) {
            continue;
        }
        if (zarrComponentIndex(relPath) >= 0) {
            continue;
        }
        insertFile(makeFileRecord(absPath, relPath), out);
    }

    /* pass 2: zarr store directories. Record each store as a single primary data
     * record. A store is a directory whose OWN name ends in .zarr and which is not
     * itself nested inside another zarr store (so only the outermost is recorded). */
    QDirIterator dit(out.rootPath, QDir::Dirs | QDir::NoDotAndDotDot, QDirIterator::Subdirectories);
    while (dit.hasNext()) {
        const QString absPath = dit.next();
        const QString relPath = root.relativeFilePath(absPath);
        if (isUnderReservedTop(relPath)) {
            continue;
        }
        const QStringList comps = relPath.split('/', Qt::SkipEmptyParts);
        if (!comps.isEmpty() && zarrComponentIndex(relPath) == comps.size() - 1) {
            insertFile(makeFileRecord(absPath, relPath), out);
        }
    }

    attachParticipantRows(out);
    attachScansRows(out);
    MetadataResolver::resolveDatasetMetadata(out);
    return true;
}


/* ---------------------------------------------------------- */
/* --------- Reader::readDatasetDescription ----------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Read dataset_description.json and extract required fields.
 * @param path Path to dataset_description.json
 * @param out BidsDataset that receives the parsed description fields
 * @param error Error message if the file is missing or invalid
 * @return True if the file exists, parses, and contains required fields
 */
bool Reader::readDatasetDescription(const QString &path, BidsDataset &out, QString &error) {
    QFile f(path);
    if (!f.open(QIODevice::ReadOnly)) {
        error = "Missing or unreadable dataset_description.json";
        return false;
    }

    QJsonParseError pe;
    const QJsonDocument doc = QJsonDocument::fromJson(f.readAll(), &pe);
    if (pe.error != QJsonParseError::NoError || !doc.isObject()) {
        error = QString("Invalid JSON in dataset_description.json: %1").arg(pe.errorString());
        return false;
    }

    out.datasetDescription = doc.object();
    out.name = out.datasetDescription.value("Name").toString();
    out.bidsVersion = out.datasetDescription.value("BIDSVersion").toString();

    if (out.name.isEmpty() || out.bidsVersion.isEmpty()) {
        error = "dataset_description.json is missing required Name or BIDSVersion";
        return false;
    }

    return true;
}


/* ---------------------------------------------------------- */
/* --------- Reader::readParticipants ----------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Read participants.tsv if present.
 * @param path Path to participants.tsv
 * @param out BidsDataset that receives the participants table
 */
void Reader::readParticipants(const QString &path, BidsDataset &out) {
    if (!QFileInfo::exists(path)) {
        return;
    }

    QStringList headers;
    QList<QVariantMap> rows;
    QString error;
    if (TsvReader::read(path, headers, rows, error)) {
        out.participantColumns = headers;
        out.participantRows = rows;
    }
}


/* ---------------------------------------------------------- */
/* --------- Reader::makeFileRecord ------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Convert one filesystem entry (a file, or an OME-Zarr store directory) into a FileRecord. This is where filename parsing and basic path-derived inference happens.
 * @param absPath Absolute filesystem path to the file or directory
 * @param relPath File path relative to the dataset root
 * @return FileRecord populated with parsed filename and path-derived fields
 */
FileRecord Reader::makeFileRecord(const QString &absPath, const QString &relPath) {
    FileRecord fr;
    fr.absolutePath = absPath;
    fr.relativePath = relPath;
    fr.fileName = QFileInfo(absPath).fileName();

    const ParseResult parsed = FilenameParser::parse(fr.fileName);
    fr.parsedAsBids = parsed.valid;
    fr.suffix = parsed.suffix;
    fr.extension = parsed.extension;
    fr.entities = parsed.entities;

    const QStringList parts = relPath.split('/', Qt::SkipEmptyParts);
    /* subject/session are taken from the DIRECTORY components (sub-XX/ses-YY/), not
     * from the filename (the last part), which also begins with "sub-" and would
     * otherwise overwrite the correct value. Flat datasets with no sub- directory
     * fall back to the filename entities parsed below. */
    for (int p = 0; p < parts.size() - 1; ++p) {
        const QString &part = parts.at(p);
        if (part.startsWith("sub-")) fr.subject = part.mid(4);
        else if (part.startsWith("ses-")) fr.session = part.mid(4);
    }

    if (fr.subject.isEmpty() && fr.entities.contains("sub")) fr.subject = fr.entities.value("sub");
    if (fr.session.isEmpty() && fr.entities.contains("ses")) fr.session = fr.entities.value("ses");

    if (parts.size() >= 2) {
        const QString parentDir = parts.at(parts.size() - 2);
        static const QSet<QString> datatypes = {
            "anat", "func", "dwi", "fmap", "perf", "eeg", "meg", "ieeg", "beh",
            "pet", "micr", "motion", "nirs", "mrs", "emg"
        };
        if (datatypes.contains(parentDir)) {
            fr.datatype = parentDir;
        }
    }

    return fr;
}


/* ---------------------------------------------------------- */
/* --------- Reader::acquisitionKey ------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Compute the canonical acquisition key for a file.
 * @param fr File record whose entities and suffix should be normalized
 * @return Canonical acquisition key derived from the file metadata
 */
QString Reader::acquisitionKey(const FileRecord &fr) {
    QMap<QString, QString> entities = fr.entities;
    if (!fr.subject.isEmpty() && !entities.contains("sub")) entities.insert("sub", fr.subject);
    if (!fr.session.isEmpty() && !entities.contains("ses")) entities.insert("ses", fr.session);
    return FilenameParser::canonicalStem(entities, fr.suffix);
}


/* ---------------------------------------------------------- */
/* --------- Reader::isCompanionOrPrimary ------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Return true for file types that belong in acquisition grouping.
 * @param ext File extension, including the leading dot
 * @return True if the extension should be grouped with other acquisition files
 */
bool Reader::isCompanionOrPrimary(const QString &ext) {
    static const QSet<QString> exts = {
        /* neuroimaging / diffusion */
        ".nii.gz", ".nii", ".json", ".tsv", ".tsv.gz", ".bval", ".bvec",
        /* EEG: European Data Format, BioSemi, BrainVision (.vhdr/.eeg/.vmrk),
         * EEGLAB (.set/.fdt), MEG (.fif) */
        ".edf", ".bdf", ".vhdr", ".eeg", ".vmrk", ".set", ".fdt", ".fif",
        /* NIRS */
        ".snirf",
        /* microscopy */
        ".tif", ".tiff", ".ome.tif", ".ome.tiff", ".ome.zarr"
    };
    return exts.contains(ext.toLower());
}


/* ---------------------------------------------------------- */
/* --------- Reader::classifyIntoAcquisition ---------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Add a file to an acquisition and classify its role.
 * @param fr File being added to the acquisition
 * @param acq Acquisition that will receive the file and derived companion paths
 */
void Reader::classifyIntoAcquisition(const FileRecord &fr, Acquisition &acq) {
    acq.files.append(fr);

    const QString ext = fr.extension.toLower();
    if (ext == ".json") {
        acq.jsonSidecarPath = fr.relativePath;
    } else if (ext == ".bval") {
        acq.bvalPath = fr.relativePath;
    } else if (ext == ".bvec") {
        acq.bvecPath = fr.relativePath;
    } else if ((ext == ".tsv" || ext == ".tsv.gz") && fr.suffix == "events") {
        acq.eventsPath = fr.relativePath;
    } else if (ext == ".nii.gz" || ext == ".nii" ||
               ext == ".edf" || ext == ".bdf" || ext == ".set" || ext == ".fif" || ext == ".eeg" ||
               ext == ".snirf" ||
               ext == ".tif" || ext == ".tiff" || ext == ".ome.tif" || ext == ".ome.tiff" || ext == ".ome.zarr") {
        acq.primaryDataPath = fr.relativePath;
    }
}


/* ---------------------------------------------------------- */
/* --------- Reader::ensureSubject -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Ensure the subject container exists before inserting data under it.
 * @param out BidsDataset that will receive the subject
 * @param subject Subject identifier without the sub- prefix
 */
void Reader::ensureSubject(BidsDataset &out, const QString &subject) {
    if (!out.subjects.contains(subject)) {
        SubjectRecord sr;
        sr.id = subject;
        out.subjects.insert(subject, sr);
    }
}


/* ---------------------------------------------------------- */
/* --------- Reader::isScansTsv ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Identify *_scans.tsv files, which are handled separately from acquisitions.
 * @param fr File record to test
 * @return True if the file is a scans.tsv side table
 */
bool Reader::isScansTsv(const FileRecord &fr) {
    const QString ext = fr.extension.toLower();
    return (ext == ".tsv" || ext == ".tsv.gz") && fr.suffix == "scans";
}


/* ---------------------------------------------------------- */
/* --------- Reader::readScansTable ------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Read a subject-level scans table.
 * @param fr File record pointing to the scans.tsv file
 * @param subj Subject record that will receive the parsed table
 */
void Reader::readScansTable(const FileRecord &fr, SubjectRecord &subj) {
    QStringList headers;
    QList<QVariantMap> rows;
    QString error;
    if (TsvReader::read(fr.absolutePath, headers, rows, error)) {
        subj.scansTable.path = fr.relativePath;
        subj.scansTable.columns = headers;
        subj.scansTable.rows = rows;
    }
}


/* ---------------------------------------------------------- */
/* --------- Reader::readScansTable ------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Read a session-level scans table.
 * @param fr File record pointing to the scans.tsv file
 * @param sess Session record that will receive the parsed table
 */
void Reader::readScansTable(const FileRecord &fr, SessionRecord &sess) {
    QStringList headers;
    QList<QVariantMap> rows;
    QString error;
    if (TsvReader::read(fr.absolutePath, headers, rows, error)) {
        sess.scansTable.path = fr.relativePath;
        sess.scansTable.columns = headers;
        sess.scansTable.rows = rows;
    }
}


/* ---------------------------------------------------------- */
/* --------- Reader::attachScansRowsToAcquisitionMap -------- */
/* ---------------------------------------------------------- */
/**
 * @brief Match scans.tsv rows back to acquisitions. Preferred match is full relative path; fallback is basename-only.
 * @param table Parsed scans.tsv table
 * @param acqs Acquisition map to update with matched scans rows
 */
void Reader::attachScansRowsToAcquisitionMap(const ScansTable &table, QMap<QString, Acquisition> &acqs) {
    if (table.rows.isEmpty()) {
        return;
    }

    for (auto acqIt = acqs.begin(); acqIt != acqs.end(); ++acqIt) {
        Acquisition &acq = acqIt.value();

        QString bestRelative;
        if (!acq.primaryDataPath.isEmpty()) {
            bestRelative = acq.primaryDataPath;
        } else if (!acq.files.isEmpty()) {
            bestRelative = acq.files.first().relativePath;
        }

        if (bestRelative.isEmpty()) {
            continue;
        }

        const QString bestName = QFileInfo(bestRelative).fileName();
        for (const QVariantMap &row : table.rows) {
            QString filename = row.value("filename").toString();
            if (filename.isEmpty()) {
                filename = row.value("file").toString();
            }
            if (filename.isEmpty()) {
                continue;
            }

            if (filename == bestRelative || QFileInfo(filename).fileName() == bestName) {
                acq.scansRow = row;
                break;
            }
        }
    }
}


/* ---------------------------------------------------------- */
/* --------- Reader::attachScansRows ------------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Attach scans.tsv rows across the entire dataset hierarchy.
 * @param out BidsDataset whose acquisitions should receive scans row matches
 */
void Reader::attachScansRows(BidsDataset &out) {
    for (auto subIt = out.subjects.begin(); subIt != out.subjects.end(); ++subIt) {
        SubjectRecord &subj = subIt.value();
        attachScansRowsToAcquisitionMap(subj.scansTable, subj.acquisitionsWithoutSession);
        for (auto sessIt = subj.sessions.begin(); sessIt != subj.sessions.end(); ++sessIt) {
            attachScansRowsToAcquisitionMap(sessIt.value().scansTable, sessIt.value().acquisitions);
        }
    }
}


/* ---------------------------------------------------------- */
/* --------- Reader::insertFile ----------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Insert one FileRecord into the dataset hierarchy. This determines whether
 *        the file is:
 *          - top-level (no subject)
 *          - a scans.tsv table
 *          - a loose file (has a subject but is not groupable)
 *          - part of a grouped acquisition
 * @param fr Parsed file record to insert
 * @param out BidsDataset that will receive the file
 */
void Reader::insertFile(const FileRecord &fr, BidsDataset &out) {
    if (fr.extension.toLower() == ".json") {
        out.jsonFiles.append(fr);
    }

    if (fr.subject.isEmpty()) {
        out.topLevelFiles.append(fr);
        return;
    }

    ensureSubject(out, fr.subject);
    SubjectRecord &subj = out.subjects[fr.subject];

    if (isScansTsv(fr)) {
        if (fr.session.isEmpty()) {
            readScansTable(fr, subj);
        } else {
            if (!subj.sessions.contains(fr.session)) {
                SessionRecord sess;
                sess.id = fr.session;
                subj.sessions.insert(fr.session, sess);
            }
            readScansTable(fr, subj.sessions[fr.session]);
        }
        return;
    }

    const bool eligible = !fr.suffix.isEmpty() && isCompanionOrPrimary(fr.extension);
    if (fr.session.isEmpty()) {
        if (!eligible) {
            subj.looseFiles.append(fr);
            return;
        }

        const QString key = acquisitionKey(fr);
        if (!subj.acquisitionsWithoutSession.contains(key)) {
            Acquisition acq;
            acq.key = key;
            acq.subject = fr.subject;
            acq.session = fr.session;
            acq.datatype = fr.datatype;
            acq.suffix = fr.suffix;
            acq.entities = fr.entities;
            subj.acquisitionsWithoutSession.insert(key, acq);
        }
        classifyIntoAcquisition(fr, subj.acquisitionsWithoutSession[key]);
        return;
    }

    if (!subj.sessions.contains(fr.session)) {
        SessionRecord sess;
        sess.id = fr.session;
        subj.sessions.insert(fr.session, sess);
    }
    SessionRecord &sess = subj.sessions[fr.session];

    if (!eligible) {
        sess.looseFiles.append(fr);
        return;
    }

    const QString key = acquisitionKey(fr);
    if (!sess.acquisitions.contains(key)) {
        Acquisition acq;
        acq.key = key;
        acq.subject = fr.subject;
        acq.session = fr.session;
        acq.datatype = fr.datatype;
        acq.suffix = fr.suffix;
        acq.entities = fr.entities;
        sess.acquisitions.insert(key, acq);
    }
    classifyIntoAcquisition(fr, sess.acquisitions[key]);
}


/* ---------------------------------------------------------- */
/* --------- Reader::attachParticipantRows ------------------ */
/* ---------------------------------------------------------- */
/**
 * @brief Link participants.tsv rows back to subjects by participant_id.
 * @param out BidsDataset whose subjects should be matched to participants.tsv rows
 */
void Reader::attachParticipantRows(BidsDataset &out) {
    for (const QVariantMap &row : out.participantRows) {
        const QString participantId = row.value("participant_id").toString();
        if (!participantId.startsWith("sub-")) {
            continue;
        }
        const QString sub = participantId.mid(4);
        ensureSubject(out, sub);
        out.subjects[sub].participantRow = row;
    }
}




/* ---------------------------------------------------------- */
/* --------- Validator::validate ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Run lightweight validation checks on the BidsDataset.
 * @param ds BidsDataset to validate
 * @return List of validation messages describing warnings and errors
 */
QList<ValidationMessage> Validator::validate(const BidsDataset &ds) const {
    QList<ValidationMessage> out;

    if (ds.name.isEmpty()) {
        out.append({ValidationMessage::Error, "dataset_description.json", "Missing Name"});
    }
    if (ds.bidsVersion.isEmpty()) {
        out.append({ValidationMessage::Error, "dataset_description.json", "Missing BIDSVersion"});
    }

    validateParticipants(ds, out);
    validateAcquisitions(ds, out);
    return out;
}


/* ---------------------------------------------------------- */
/* --------- Validator::validateParticipants ---------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Validate participants.tsv shape and subject linkage.
 * @param ds BidsDataset to inspect
 * @param out Output list that receives validation messages
 */
void Validator::validateParticipants(const BidsDataset &ds, QList<ValidationMessage> &out) {
    if (!ds.participantRows.isEmpty() && !ds.participantColumns.contains("participant_id")) {
        out.append({ValidationMessage::Error, "participants.tsv", "participants.tsv exists but is missing participant_id column"});
    }

    for (auto it = ds.subjects.begin(); it != ds.subjects.end(); ++it) {
        if (it.value().participantRow.isEmpty()) {
            out.append({ValidationMessage::Info, QString("sub-%1").arg(it.key()), "Subject has no participants.tsv row"});
        }
    }
}


/* ---------------------------------------------------------- */
/* --------- Validator::validateOneAcq ---------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Validate one acquisition for common problems.
 * @param acq Acquisition to inspect
 * @param out Output list that receives validation messages
 */
void Validator::validateOneAcq(const Acquisition &acq, QList<ValidationMessage> &out) {
    if (acq.primaryDataPath.isEmpty() && !acq.files.isEmpty()) {
        out.append({ValidationMessage::Info, acq.key, "Acquisition has companion files but no obvious primary data file"});
    }

    if (acq.suffix == "dwi") {
        if (acq.bvalPath.isEmpty()) {
            out.append({ValidationMessage::Warning, acq.key, "DWI acquisition is missing .bval companion"});
        }
        if (acq.bvecPath.isEmpty()) {
            out.append({ValidationMessage::Warning, acq.key, "DWI acquisition is missing .bvec companion"});
        }
    }

    if (!acq.scansRow.isEmpty() && !acq.scansRow.contains("filename") && !acq.scansRow.contains("file")) {
        out.append({ValidationMessage::Info, acq.key, "Matched scans row does not contain filename column"});
    }
}


/* ---------------------------------------------------------- */
/* --------- Validator::validateAcquisitions ---------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Walk all acquisitions and validate each one.
 * @param ds BidsDataset containing all acquisitions to validate
 * @param out Output list that receives validation messages
 */
void Validator::validateAcquisitions(const BidsDataset &ds, QList<ValidationMessage> &out) {
    for (auto sit = ds.subjects.begin(); sit != ds.subjects.end(); ++sit) {
        const SubjectRecord &subj = sit.value();
        for (auto it = subj.acquisitionsWithoutSession.begin(); it != subj.acquisitionsWithoutSession.end(); ++it) {
            validateOneAcq(it.value(), out);
        }
        for (auto sessIt = subj.sessions.begin(); sessIt != subj.sessions.end(); ++sessIt) {
            for (auto acqIt = sessIt.value().acquisitions.begin(); acqIt != sessIt.value().acquisitions.end(); ++acqIt) {
                validateOneAcq(acqIt.value(), out);
            }
        }
    }
}


/* ---------------------------------------------------------- */
/* --------- PrintDataset ----------------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Render a BidsDataset to a human-readable, multi-line string. Every file
 *        is listed with its full (absolute) path.
 * @param ds The BidsDataset to render
 * @return A printable multi-line summary
 */
QString PrintDataset(const BidsDataset &ds) {
    QStringList lines;

    /* resolve a dataset-relative path (eg a primaryDataPath) to a full path */
    auto fullPath = [&](const QString &rel) -> QString {
        return rel.isEmpty() ? QString() : QDir(ds.rootPath).filePath(rel);
    };
    /* format a BIDS entity map as "key-value, key-value" */
    auto formatEntities = [](const QMap<QString, QString> &e) -> QString {
        QStringList parts;
        for (auto it = e.begin(); it != e.end(); ++it)
            parts << QString("%1-%2").arg(it.key(), it.value());
        return parts.join(", ");
    };
    /* render one JSON value as a single line; arrays/objects stay as compact JSON */
    auto jsonValueStr = [](const QJsonValue &v) -> QString {
        switch (v.type()) {
            case QJsonValue::String: return v.toString();
            case QJsonValue::Bool:   return v.toBool() ? "true" : "false";
            case QJsonValue::Double: {
                const double d = v.toDouble();
                if ((d == static_cast<double>(static_cast<qint64>(d))) && (qAbs(d) < 1e15))
                    return QString::number(static_cast<qint64>(d));
                return QString::number(d, 'g', 12);
            }
            case QJsonValue::Array:  return QString::fromUtf8(QJsonDocument(v.toArray()).toJson(QJsonDocument::Compact));
            case QJsonValue::Object: return QString::fromUtf8(QJsonDocument(v.toObject()).toJson(QJsonDocument::Compact));
            case QJsonValue::Null:   return "null";
            default:                 return QString();
        }
    };
    /* print one acquisition and its files (with full paths) at the given indent */
    auto printAcq = [&](const Acquisition &acq, const QString &indent) {
        lines << QString("%1[%2]  datatype=%3 suffix=%4  entities={%5}")
                     .arg(indent, acq.key, acq.datatype, acq.suffix, formatEntities(acq.entities));
        if (!acq.primaryDataPath.isEmpty())
            lines << QString("%1  primary: %2").arg(indent, fullPath(acq.primaryDataPath));
        if (!acq.bvalPath.isEmpty())   lines << QString("%1  bval:   %2").arg(indent, fullPath(acq.bvalPath));
        if (!acq.bvecPath.isEmpty())   lines << QString("%1  bvec:   %2").arg(indent, fullPath(acq.bvecPath));
        if (!acq.eventsPath.isEmpty()) lines << QString("%1  events: %2").arg(indent, fullPath(acq.eventsPath));
        if (acq.resolvedMetadata.isEmpty()) {
            lines << QString("%1  resolvedMetadata: (none)").arg(indent);
        } else {
            lines << QString("%1  resolvedMetadata (%2 key(s)):").arg(indent).arg(acq.resolvedMetadata.size());
            /* QJsonObject::keys() is sorted, so the dump is deterministic */
            for (const QString &k : acq.resolvedMetadata.keys())
                lines << QString("%1    %2 = %3").arg(indent, k, jsonValueStr(acq.resolvedMetadata.value(k)));
        }
        lines << QString("%1  files (%2):").arg(indent).arg(acq.files.size());
        for (const FileRecord &f : acq.files)
            lines << QString("%1    %2").arg(indent, f.absolutePath);
    };

    lines << "===== BidsDataset =====";
    lines << QString("Root:         %1").arg(ds.rootPath);
    lines << QString("Name:         %1").arg(ds.name);
    lines << QString("BIDSVersion:  %1").arg(ds.bidsVersion);
    lines << QString("Participants: %1 row(s), columns: [%2]").arg(ds.participantRows.size()).arg(ds.participantColumns.join(", "));
    lines << QString("JSON pool:    %1 file(s)").arg(ds.jsonFiles.size());
    lines << QString("BIDS compliant: %1").arg(ds.bidsCompliant ? "yes" : "no");
    for (const QString &issue : ds.complianceIssues)
        lines << QString("  - %1").arg(issue);
    lines << "";

    lines << QString("Top-level files (%1):").arg(ds.topLevelFiles.size());
    for (const FileRecord &f : ds.topLevelFiles)
        lines << QString("  %1").arg(f.absolutePath);
    lines << "";

    /* QMap iterates in sorted key order, so subject/session output is stable */
    for (auto sit = ds.subjects.begin(); sit != ds.subjects.end(); ++sit) {
        const SubjectRecord &subj = sit.value();
        lines << QString("Subject [%1]%2").arg(subj.id, subj.participantRow.isEmpty() ? QString() : QString("  (has participants.tsv row)"));

        if (!subj.scansTable.path.isEmpty())
            lines << QString("  scans table: %1 (%2 row(s))").arg(fullPath(subj.scansTable.path)).arg(subj.scansTable.rows.size());

        if (!subj.looseFiles.isEmpty()) {
            lines << QString("  Loose files (%1):").arg(subj.looseFiles.size());
            for (const FileRecord &f : subj.looseFiles)
                lines << QString("    %1").arg(f.absolutePath);
        }

        if (!subj.acquisitionsWithoutSession.isEmpty()) {
            lines << QString("  Acquisitions without session (%1):").arg(subj.acquisitionsWithoutSession.size());
            for (auto ait = subj.acquisitionsWithoutSession.begin(); ait != subj.acquisitionsWithoutSession.end(); ++ait)
                printAcq(ait.value(), "    ");
        }

        for (auto sessIt = subj.sessions.begin(); sessIt != subj.sessions.end(); ++sessIt) {
            const SessionRecord &sess = sessIt.value();
            lines << QString("  Session [%1]").arg(sess.id);
            if (!sess.scansTable.path.isEmpty())
                lines << QString("    scans table: %1 (%2 row(s))").arg(fullPath(sess.scansTable.path)).arg(sess.scansTable.rows.size());
            if (!sess.looseFiles.isEmpty()) {
                lines << QString("    Loose files (%1):").arg(sess.looseFiles.size());
                for (const FileRecord &f : sess.looseFiles)
                    lines << QString("      %1").arg(f.absolutePath);
            }
            lines << QString("    Acquisitions (%1):").arg(sess.acquisitions.size());
            for (auto ait = sess.acquisitions.begin(); ait != sess.acquisitions.end(); ++ait)
                printAcq(ait.value(), "      ");
        }
        lines << "";
    }

    return lines.join("\n");
}

} // namespace bids
