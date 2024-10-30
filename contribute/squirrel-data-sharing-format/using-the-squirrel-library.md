---
description: Overview of how to use the squirrel C++ library
---

# Using the squirrel library

The squirrel library is built using the Qt framework and gdcm. Both are available as open-source, and make development of the squirrel library much more efficient.

{% hint style="info" %}
The Qt and gdcm libraries (or DLLs on Windows) will need to be redistributed along with any programs that use the squirrel library.
{% endhint %}

## Including squirrel

The squirrel library can be included at the top of your program. Make sure the path to the squirrel library is in the INCLUDE path for your compiler.

```cpp
#include "squirrel.h"
```

## Reading

Create an object and read an existing squirrel package

```cpp
squirrel *sqrl = new squirrel();
sqrl->SetPackagePath("/path/to/data.sqrl");
if (sqrl->Read()) {
    cout << "Successfuly read squirrel package" << endl;
else
    cout << "Error reading squirrel package. Log [" << sqrl->GetLog() << "]" << endl;

/* print the entire package */
sqrl->Print();

/* access individual package meta-data */
cout << sqrl->name;

/* delete squirrel object */
delete sqrl;
```

### Iterating subject/study/series data

Functions are provided to retrieve lists of objects.

```cpp
/* iterate through the subjects */
QList<squirrelSubject> subjects = sqrl->GetSubjectList();
foreach (squirrelSubject subject, subjects) {
    cout <<"Found subject [" + subject.ID + "]");

    /* get studies */
    QList<squirrelStudy> studies = sqrl->GetStudyList(subject.GetObjectID());
    foreach (squirrelStudy study, studies) {
        n->Log(QString("Found study [%1]").arg(study.StudyNumber));

        /* get series */
        QList<squirrelSeries> serieses = sqrl->GetSeriesList(study.GetObjectID());
        foreach (squirrelSeries series, serieses) {
            n->Log(QString("Found series [%1]").arg(series.SeriesNumber));
            int numfiles = series.files.size();
        }
    }
}
```

### Finding data

How to get a **copy** of an object, for reading or searching a squirrel package.

```cpp
/* get a subject by ID, the returned object is read-only */
qint64 subjectObjectID = FindSubject("12345");
squirrelSubject subject = GetSubject(subjectObjectID);
QString guid = subject.GUID;
subject.PrintDetails();

/* get a subject by SubjectUID (DICOM field) */
squirrelStudy study = GetStudy(FindSubjectByUID("08.03.21-17:51:10-STD-1.3.12.2.1107.5.3.7.20207"));
QString studyDesc = study.Description;
study.PrintDetails();

/* get study by subject ID, and study number */
squirrelStudy study = GetStudy(FindStudy("12345", 2));
QString studyEquipment = study.Equipment;
study.PrintDetails();

/* get series by seriesUID (DICOM field) */
squirrelSeries series = GetStudy(FindSeriesByUID("09.03.21-17:51:10-STD-1.3.12.2.1107.5.3.7.20207"));
QDateTime seriesDate = series.DateTime;
series.PrintDetails();

/* get series by subjectID 12345, study number 2, and series number 15 */
squirrelSeries series = GetStudy(FindSeries("12345", 2, 15));
QString seriesProtocol = series.Protocol;
series.PrintDetails();

/* get an analysis by subject ID 12345, study 2, and analysis 'freesurfer' */
squirrelAnalysis GetAnalysis(FindAnalysis("12345", 2, "freesurfer"));

/* get other objects by their names */
squirrelDataDictionary dataDictionary = GetDataDictionary(FindDataDictionary("MyDataDict"));
squirrelExperiment experiment = GetExperiment(FindExperiment("MyExperiment"));
squirrelGroupAnalysis groupAnalysis = GetGroupAnalysis(FindGroupAnalysis("MyGroupAnalysis"));
squirrelPipeline pipeline = GetPipeline(FindPipeline("MyPipeline"));

```

How to **modify** existing objects in a package.

```cpp
/* find a subject */

```

### Experiments and Pipelines

Access to these objects is similar to accessing subjects

```cpp
/* iterate by list to access copies of the objects(read only) */
foreach (squirrelExperiment exp, sqrl->experimentList) {
    cout << exp.experimentName << endl;
}
foreach (squirrelPipeline pipe, sqrl->pipelineList) {
    cout << pipe.pipelineName << endl;
}

/* iterate by index to change the original object (read/write) */
for (int i=0; i < sqrl->experimentList.size(); i++) {
    sqrl->experimentList[i].numFiles = 0;
}
for (int i=0; i < sqrl->pipelineList.size(); i++) {
    sqrl->pipelineList[i].numFiles = 0;
}
```

## Writing

### Create a new squirrel package and add a subject

```cpp
squirrel *sqrl = new squirrel();

/* set the package details */
sqrl->name = "LotsOfData";
sqrl->description = "My First squirrel package;
sqrl->datetime = QDateTime()::currentDateTime();
sqrl->subjectDirFormat = "orig";
sqrl->studyDirFormat = "orig";
sqrl->seriesDirFormat = "orig;
sqrl->dataFormat = "nifti";

/* create a subject */
squirrelSubject sqrlSubject;
sqrlSubject.ID = "123456";
sqrlSubject.alternateIDs = QString("Alt1, 023043").split(",");
sqrlSubject.GUID = "NDAR12345678";
sqrlSubject.dateOfBirth.fromString("2000-01-01", "yyyy-MM-dd");
sqrlSubject.sex = "O";
sqrlSubject.gender = "O";
sqrlSubject.ethnicity1 = subjectInfo->GetValue("ethnicity1");
sqrlSubject.ethnicity2 = subjectInfo->GetValue("ethnicity2");

/* add the subject. This subject has only demographics, there are no studies or  */
sqrl->addSubject(sqrlSubject);
```

### Add a study to existing subject

```cpp
/* see if we can find a subject by ID */
int subjIndex = sqrl->GetSubjectIndex("123456");
if (subjIndex >= 0) {

    /* build the study object */
    squirrelStudy sqrlStudy;
    sqrlStudy.number = 1;
    sqrlStudy.dateTime.fromString("2023-06-19 15:34:56", "yyyy-MM-dd hh:mm:ss");
    sqrlStudy.ageAtStudy = 34.5;
    sqrlStudy.height = 1.5; // meters
    sqrlStudy.weight = 75.9; // kg
    sqrlStudy.modality = "MR";
    sqrlStudy.description = "MJ and driving";
    sqrlStudy.studyUID = "";
    sqrlStudy.visitType = "FirstVisit";
    sqrlStudy.dayNumber = 1;
    sqrlStudy.timePoint = 1;
    sqrlStudy.equipment = "Siemens 3T Prisma;
    
    sqrl->subjectList[subjIndex].addStudy(sqrlStudy);
}
else {
    cout << "Unable to find subject by ID [123456]" << endl;
}
```

### Write package

```cpp
QString outdir = "/home/squirrel/thedata" /* output directory of the squirrel package */
QString zippath; /* the full filepath of the written zip file */

sqrl->write(outdir, zippath);++
```
