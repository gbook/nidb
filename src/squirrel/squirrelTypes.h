#ifndef SQUIRRELTYPES_H
#define SQUIRRELTYPES_H

#include <QString>
#include <QList>

enum FileMode { NewPackage, ExistingPackage };
enum PrintFormat { BasicList, FullList, List, Details, CSV, Tree };
enum DatasetType { DatasetID, DatasetBasic, DatasetFull };
enum ObjectType {
    Analysis,
    BehSeries,
    DataDictionary,
    DataDictionaryItem,
    Experiment,
    GroupAnalysis,
    Intervention,
    Observation,
    Package,
    Pipeline,
    Series,
    Study,
    Subject,
    UnknownObjectType
};

typedef QPair<QString, QString> QStringPair;
typedef QList<QStringPair> pairList;
typedef QHash<QString, QString> QStringHash;

struct infoQuery {
    bool debug;
    ObjectType object;
    QString subjectID;
    int studyNum;
    DatasetType dataset;
    PrintFormat printFormat;
};

struct modification {
    QString operation;        /* possible values: add, remove, update, splitbymodality, removephi, renumber */
    ObjectType object;
    QString dataPath;         /* disk path containing the data (most likely for an add operation) */
    QString objectData;       /* the object data, likely in URL style format */
    QString objectID;         /* object identifier string. Example: subject ID, experiment name, pipeline name, etc */
    QString subjectID;        /* parent subject ID, used for study/series/analysis/observation/intervention operations */
    int studyNumber;          /* study number */
    int seriesNumber;         /* series number */
    int renumberDigits;       /* (renumber operation) number of digits in the new subject IDs */
    int renumberStartNum;     /* (renumber operation) starting number for new subject IDs */
    QString renumberPrefix;   /* (renumber operation) prefix string prepended to new subject IDs */
    bool renumberRandomize;   /* (renumber operation) true to randomize order before renumbering */
};

#endif // SQUIRRELTYPES_H
