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

#endif // SQUIRRELTYPES_H
