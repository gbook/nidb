#include "bids.h"

#include <QDir>
#include <QDirIterator>
#include <QFile>
#include <QFileInfo>
#include <QJsonDocument>
#include <QJsonParseError>
#include <QTextStream>
#include <QStringConverter>
#include <algorithm>

namespace bids {


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
 * @brief Parse a BIDS-style filename into entities, suffix, and extension. This parser is intentionally lightweight and supports indexing/grouping.
 * @param fileName Filename to parse
 * @return a ParseResult object
 */
ParseResult FilenameParser::parse(const QString &fileName) {
    ParseResult out;

    QString stem = fileName;
    if (stem.endsWith(".nii.gz", Qt::CaseInsensitive)) {
        out.extension = ".nii.gz";
        stem.chop(7);
    } else if (stem.endsWith(".tsv.gz", Qt::CaseInsensitive)) {
        out.extension = ".tsv.gz";
        stem.chop(7);
    } else {
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
 * @brief Resolve metadata for every acquisition in the dataset. This is called only after indexing is complete.
 * @param dataset The fully indexed dataset to update with resolved JSON metadata
 */
void MetadataResolver::resolveDatasetMetadata(DatasetIndex &dataset) {
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
 * @param dataset Dataset index containing all candidate JSON sidecar files
 * @param acq Acquisition to resolve metadata for
 */
void MetadataResolver::resolveAcquisitionMetadata(const DatasetIndex &dataset, Acquisition &acq) {
    const QList<FileRecord> candidates = candidateJsonFiles(dataset, acq);
    if (candidates.isEmpty()) {
        return;
    }

    QList<FileRecord> sorted = candidates;
    std::sort(sorted.begin(), sorted.end(), [&acq](const FileRecord &a, const FileRecord &b) {
        const int pa = commonPrefixDepth(a.relativePath, acq.primaryDataPath);
        const int pb = commonPrefixDepth(b.relativePath, acq.primaryDataPath);
        if (pa != pb) return pa < pb;

        const int sa = a.entities.size()*100;
        const int sb = b.entities.size()*100;
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
 * @param dataset Dataset index that provides the pool of JSON sidecar files
 * @param acq Acquisition used to test JSON applicability
 * @return List of JSON files that may contribute inherited metadata
 */
QList<FileRecord> MetadataResolver::candidateJsonFiles(const DatasetIndex &dataset, const Acquisition &acq) {
    QList<FileRecord> out;
    for (const FileRecord &jsonFile : dataset.jsonFiles) {
        if (jsonAppliesToAcquisition(dataset, jsonFile, acq)) {
            out.append(jsonFile);
        }
    }
    return out;
}


/* ---------------------------------------------------------- */
/* --------- MetadataResolver::candidateJsonFiles ----------- */
/* ---------------------------------------------------------- */
/**
 * @brief Return true if one JSON sidecar applies to an acquisition. The sidecar must match by suffix and by entity subset, and must be located in the acquisition directory or one of its parent directories.
 * @param dataset Dataset index used to resolve relative path relationships
 * @param jsonFile JSON file being tested
 * @param acq Acquisition that may receive metadata from the JSON file
 * @return True if the JSON file is an applicable sidecar for the acquisition
 */
bool MetadataResolver::jsonAppliesToAcquisition(const DatasetIndex &dataset, const FileRecord &jsonFile, const Acquisition &acq) {
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

    return acqDirPath.startsWith(jsonDirPath);
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

// Merge overlay into base so later values override earlier values.
// This is the behavior needed for inherited BIDS metadata.
void MetadataResolver::mergeJson(QJsonObject &base, const QJsonObject &overlay) {
    for (auto it = overlay.begin(); it != overlay.end(); ++it) {
        base.insert(it.key(), it.value());
    }
}





/* ---------------------------------------------------------- */
/* --------- Reader::readDataset ---------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Main entry point for reading a BIDS dataset.
     The order is:
     1) read top-level dataset files
     2) walk all files
     3) attach tabular metadata
     4) resolve inherited JSON metadata
 * @param rootPath Root directory of the BIDS dataset
 * @param out Populated dataset index on success
 * @param error Error message if reading fails
 * @return True if the dataset was read successfully
 */
bool Reader::readDataset(const QString &rootPath, DatasetIndex &out, QString &error) const {
    out = DatasetIndex{};
    out.rootPath = QDir(rootPath).absolutePath();

    const QDir root(out.rootPath);
    if (!root.exists()) {
        error = QString("Dataset directory does not exist: %1").arg(out.rootPath);
        return false;
    }

    if (!readDatasetDescription(root.filePath("dataset_description.json"), out, error)) {
        return false;
    }

    readParticipants(root.filePath("participants.tsv"), out);

    QDirIterator it(out.rootPath, QDir::Files, QDirIterator::Subdirectories);
    while (it.hasNext()) {
        const QString absPath = it.next();
        const QString relPath = root.relativeFilePath(absPath);
        if (relPath == "dataset_description.json" || relPath == "participants.tsv") {
            continue;
        }
        insertFile(makeFileRecord(absPath, relPath), out);
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
 * @param out Dataset index that receives the parsed description fields
 * @param error Error message if the file is missing or invalid
 * @return True if the file exists, parses, and contains required fields
 */
bool Reader::readDatasetDescription(const QString &path, DatasetIndex &out, QString &error) {
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
 * @param out Dataset index that receives the participants table
 */
void Reader::readParticipants(const QString &path, DatasetIndex &out) {
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
 * @brief Convert one filesystem entry into a FileRecord. This is where filename parsing and basic path-derived inference happens.
 * @param absPath Absolute filesystem path to the file
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
    for (const QString &part : parts) {
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
        ".nii.gz", ".nii", ".json", ".tsv", ".tsv.gz", ".bval", ".bvec", ".edf",
        ".vhdr", ".eeg", ".vmrk", ".set", ".fdt", ".fif"
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
    } else if (ext == ".nii.gz" || ext == ".nii" || ext == ".edf" || ext == ".set" || ext == ".fif" || ext == ".eeg") {
        acq.primaryDataPath = fr.relativePath;
    }
}


/* ---------------------------------------------------------- */
/* --------- Reader::ensureSubject -------------------------- */
/* ---------------------------------------------------------- */
/**
 * @brief Ensure the subject container exists before inserting data under it.
 * @param out Dataset index that will receive the subject
 * @param subject Subject identifier without the sub- prefix
 */
void Reader::ensureSubject(DatasetIndex &out, const QString &subject) {
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
 * @param out Dataset index whose acquisitions should receive scans row matches
 */
void Reader::attachScansRows(DatasetIndex &out) {
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
 * @brief Insert one FileRecord into the dataset hierarchy.
            This determines whether the file is:
            - top-level
            - a scans.tsv table
            - a loose file
            - part of a grouped acquisition
 * @param fr Parsed file record to insert
 * @param out Dataset index that will receive the file
 */
void Reader::insertFile(const FileRecord &fr, DatasetIndex &out) {
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
 * @param out Dataset index whose subjects should be matched to participants.tsv rows
 */
void Reader::attachParticipantRows(DatasetIndex &out) {
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
 * @brief Run lightweight validation checks on the indexed dataset.
 * @param ds Dataset index to validate
 * @return List of validation messages describing warnings and errors
 */
QList<ValidationMessage> Validator::validate(const DatasetIndex &ds) const {
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
 * @param ds Dataset index to inspect
 * @param out Output list that receives validation messages
 */
void Validator::validateParticipants(const DatasetIndex &ds, QList<ValidationMessage> &out) {
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
 * @param ds Dataset index containing all acquisitions to validate
 * @param out Output list that receives validation messages
 */
void Validator::validateAcquisitions(const DatasetIndex &ds, QList<ValidationMessage> &out) {
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

} // namespace bids
