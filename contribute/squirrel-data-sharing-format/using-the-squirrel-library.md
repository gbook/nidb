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

## Reading a squirrel package

Create an object and read an existing squirrel package

```cpp
squirrel *sqrl = new squirrel();

QString filename = "/home/squirrel.zip";
QString m;

/* read the squirrel package and check for success */
bool success = sqrl->read(filename, m);

if (success) {
    cout << "Successfuly read squirrel package. Log: " << m << endl;
}
else {
    cout << "Error reading squirrel package. Log: " << m << endl;
}
```

## Build a new squirrel package and add a subject

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

## Add study to existing subject

```cpp
/* see if we can find a subject by ID */
squirrelSubject sqrlSubject;
if (sqrl->GetSubject("123456", sqrlSubject)) {

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
    
    sqrlSubject.addStudy(sqrlStudy);
}
else {
    cout << "Unable to find subject by ID [123456]" << endl;
}
```
