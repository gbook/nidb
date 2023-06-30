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

/* read the squirrel package and check for success */
if (sqrl->read("/home/squirrel.zip"))
    cout << "Successfuly read squirrel package. Log: " << m << endl;
else
    cout << "Error reading squirrel package. Log: " << m << endl;

/* print the entire package */
sqrl->print();

/* access individual package meta-data */
cout << sqrl->name;
```

### Subject data

All imaging data is stored in a Subject->Study(session)->Series hierarchy. Subjects are stored in the root of the squirrel object.

```cpp
/* iterate by list to access copies of the subjects (read only) */
foreach (squirrelSubject subj, sqrl->subjectList) {
    cout << subj.ID << endl;
}

/* iterate by index to change the original subject (read/write) */
for (int i=0; i < sqrl->subjectList.size(); i++) {
    sqrl->subjectList[i].ID = i;
}

/* get a list of subjects (copy) */
QList<squirrelSubject> subjects;
if (sqrl->GetSubjectList(subjects))
    cout << "Retrieved " << subjects.size() << " subjects" << endl;
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
