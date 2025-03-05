#ifndef SQUIRRELTYPES_H
#define SQUIRRELTYPES_H

#include <QString>
#include <QList>

enum FileMode { NewPackage, ExistingPackage };
enum PrintFormat { List, Details, CSV, Tree };
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

#endif // SQUIRRELTYPES_H
