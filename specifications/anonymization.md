---
description: DICOM Anonymization Levels
---

# Anonymization

DICOM files store lots of protected health information (PHI) and personally identifiable information (PII) by default. This is great for radiologists, but bad for researchers. Any PHI/PII left in your DICOM files when sharing them with collaborators could be a big issue for you. Your IRB might shut down your project, shoot you into space, who knows. Make sure your data is anonymized, and anonymized in the way that your IRB wants.

{% hint style="info" %}
Always anonymize your data before sharing!
{% endhint %}

NiDB offers 3 ways to export, and otherwise handle, DICOM data which are described below

**Original** - This means there is no anonymization at all. All DICOM tags in the original file will be retained. No tags are added, removed, or changed.

**Anonymize** - This is the default anonymization method, where most obvious PHI/PII is removed, such as name, DOB, etc. However, dates and locations are retained. The following tags are anonymized

* `0008,0090` ReferringPhysiciansName
* `0008,1050` PerformingPhysiciansName
* `0008,1070` OperatorsName
* `0010,0010` PatientName
* `0010,0030` PatientBirthDate

**Anonymize Full** - This method removes all PHI/PII, but also removes identifiers that are used by NiDB to accurately archive data by subject/study/series. If most of the tags used to uniquely identify data are removed... it's hard to group the DICOM files into series. So be aware that full anonymization might make it hard to archive the data later on.

* `0008,0090` ReferringPhysiciansName
* `0008,1050` PerformingPhysiciansName
* `0008,1070` OperatorsName
* `0010,0010` PatientName
* `0010,0030` PatientBirthDate
* `0008,0080` InstitutionName
* `0008,0081` InstitutionAddress
* `0008,1010` StationName
* `0008,1030` StudyDescription
* `0008,0020` StudyDate
* `0008,0021` SeriesDate
* `0008,0022` AcquisitionDate
* `0008,0023` ContentDate
* `0008,0030` StudyTime
* `0008,0031` SeriesTime
* `0008,0032` AcquisitionTime
* `0008,0033` ContentTime
* `0010,0020` PatientID
* `0010,1030` PatientWeight
