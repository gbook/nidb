# BIDS Reader Usage Example

This directory contains a lightweight BIDS reader in `bids.h` and `bids.cpp`.
The typical workflow is:

1. Create a `bids::Reader`
2. Call `readDataset()` with the path to the BIDS root
3. Inspect the returned `bids::DatasetIndex`
4. Optionally run `bids::Validator` to collect warnings and errors

## Example

```cpp
#include "bids.h"

#include <QDebug>

int main(int argc, char *argv[])
{
    QCoreApplication app(argc, argv);

    const QString bidsRoot = "/data/projects/example-bids";

    bids::Reader reader;
    bids::DatasetIndex dataset;
    QString error;

    if (!reader.readDataset(bidsRoot, dataset, error)) {
        qWarning() << "Failed to read BIDS dataset:" << error;
        return 1;
    }

    qDebug() << "Dataset name:" << dataset.name;
    qDebug() << "BIDS version:" << dataset.bidsVersion;
    qDebug() << "Subjects discovered:" << dataset.subjects.size();

    for (auto subIt = dataset.subjects.begin(); subIt != dataset.subjects.end(); ++subIt) {
        const QString subjectId = subIt.key();
        const bids::SubjectRecord &subject = subIt.value();

        qDebug() << "Subject:" << subjectId;
        qDebug() << "  Sessions:" << subject.sessions.size();
        qDebug() << "  Subject-level acquisitions:" << subject.acquisitionsWithoutSession.size();

        for (auto sessIt = subject.sessions.begin(); sessIt != subject.sessions.end(); ++sessIt) {
            const QString sessionId = sessIt.key();
            const bids::SessionRecord &session = sessIt.value();

            qDebug() << "  Session:" << sessionId;
            qDebug() << "    Acquisitions:" << session.acquisitions.size();

            for (auto acqIt = session.acquisitions.begin(); acqIt != session.acquisitions.end(); ++acqIt) {
                const bids::Acquisition &acq = acqIt.value();
                qDebug() << "    Acquisition:" << acq.key;
                qDebug() << "      Primary file:" << acq.primaryDataPath;
                qDebug() << "      JSON sidecar:" << acq.jsonSidecarPath;
                qDebug() << "      Resolved metadata keys:" << acq.resolvedMetadata.keys();
            }
        }
    }

    bids::Validator validator;
    const QList<bids::ValidationMessage> messages = validator.validate(dataset);

    for (const bids::ValidationMessage &msg : messages) {
        const char *level = "INFO";
        if (msg.severity == bids::ValidationMessage::Warning) level = "WARN";
        if (msg.severity == bids::ValidationMessage::Error) level = "ERROR";

        qDebug().noquote() << QString("[%1] %2: %3").arg(level, msg.subject, msg.message);
    }

    return 0;
}
```

## Notes

- `readDataset()` expects a BIDS root directory that contains `dataset_description.json`.
- `participants.tsv` is optional, but if present it is read and attached to subjects when possible.
- The reader groups files into acquisitions using parsed BIDS entities such as `sub`, `ses`, `task`, `run`, and `suffix`.
- JSON sidecars are merged using BIDS-style inheritance, from broader paths to more specific ones.
- `resolvedMetadata` contains the final merged metadata for an acquisition after inheritance resolution.

## Common Access Patterns

- `dataset.subjects` holds all discovered subjects.
- `subject.sessions` holds session-scoped acquisitions.
- `subject.acquisitionsWithoutSession` holds subject-level acquisitions without a session directory.
- `dataset.topLevelFiles` holds files that do not belong to a subject directory.
- `acq.files` holds all files that were grouped together for one acquisition.

## Iterate After Validation

After reading the dataset and checking validation messages, you can traverse the in-memory tree directly. A typical pattern is:

```cpp
bids::Validator validator;
const QList<bids::ValidationMessage> messages = validator.validate(dataset);

if (messages.isEmpty()) {
    qDebug() << "Dataset passed validation checks.";
}

for (auto subIt = dataset.subjects.begin(); subIt != dataset.subjects.end(); ++subIt) {
    const QString subjectId = QString("sub-%1").arg(subIt.key());
    const bids::SubjectRecord &subject = subIt.value();

    qDebug() << "Subject:" << subjectId;

    for (auto acqIt = subject.acquisitionsWithoutSession.begin(); acqIt != subject.acquisitionsWithoutSession.end(); ++acqIt) {
        const bids::Acquisition &acq = acqIt.value();
        qDebug() << "  Subject-level acquisition:" << acq.key;
        qDebug() << "    Data:" << acq.primaryDataPath;
    }

    for (auto sessIt = subject.sessions.begin(); sessIt != subject.sessions.end(); ++sessIt) {
        const QString sessionId = QString("ses-%1").arg(sessIt.key());
        const bids::SessionRecord &session = sessIt.value();

        qDebug() << "  Session:" << sessionId;

        for (auto acqIt = session.acquisitions.begin(); acqIt != session.acquisitions.end(); ++acqIt) {
            const bids::Acquisition &acq = acqIt.value();
            qDebug() << "    Acquisition:" << acq.key;
            qDebug() << "      Data:" << acq.primaryDataPath;
            qDebug() << "      Metadata keys:" << acq.resolvedMetadata.keys();

            if (!acq.scansRow.isEmpty()) {
                qDebug() << "      scans.tsv row found for acquisition";
            }
        }
    }
}
```

This is the most direct way to work with the indexed dataset:

- Use `dataset.subjects` to reach each subject.
- Use `subject.sessions` for session-scoped data.
- Use `subject.acquisitionsWithoutSession` for subject-level acquisitions.
- Use each acquisition's `files`, `primaryDataPath`, `resolvedMetadata`, and `scansRow` fields for downstream processing.

## One Subject And Session

If you only need one subject and one session, pull them out explicitly and then iterate the acquisitions in that scope:

```cpp
const QString subjectKey = "01";
const QString sessionKey = "02";

if (!dataset.subjects.contains(subjectKey)) {
    qWarning() << "Subject not found:" << subjectKey;
    return;
}

const bids::SubjectRecord &subject = dataset.subjects.value(subjectKey);

if (!subject.sessions.contains(sessionKey)) {
    qWarning() << "Session not found:" << sessionKey;
    return;
}

const bids::SessionRecord &session = subject.sessions.value(sessionKey);

qDebug() << "Processing subject" << subjectKey << "session" << sessionKey;

for (auto acqIt = session.acquisitions.begin(); acqIt != session.acquisitions.end(); ++acqIt) {
    const bids::Acquisition &acq = acqIt.value();

    qDebug() << "Acquisition:" << acq.key;
    qDebug() << "  Data file:" << acq.primaryDataPath;
    qDebug() << "  Files in group:" << acq.files.size();

    if (!acq.resolvedMetadata.isEmpty()) {
        qDebug() << "  Metadata keys:" << acq.resolvedMetadata.keys();
    }
}
```

This pattern is useful when downstream code only needs one participant's data, one scan session, or a narrow slice of the dataset tree.

## Minimal Integration Example

If you only want to know whether the dataset could be read, the smallest useful form is:

```cpp
bids::Reader reader;
bids::DatasetIndex dataset;
QString error;

if (!reader.readDataset("/path/to/bids", dataset, error)) {
    qWarning() << error;
}
```
