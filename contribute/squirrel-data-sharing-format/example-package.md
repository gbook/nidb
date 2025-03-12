# Example package

**Package contents (file and directory structure)**

```
/
/squirrel.json
/data
/data/6028
/data/6028/1
/data/6028/1/1
/data/6028/1/1/6028_1_1_00001.nii.gz
/data/6028/1/2
/data/6028/1/2/6028_1_2_00001.nii.gz
/data/6028/1/3
/data/6028/1/3/6028_1_3_00001.nii.gz
/data/6028/1/4
/data/6028/1/4/6028_1_4_00001.nii.gz

... <break> ...

/data/7998/1/11
/data/7998/1/11/7998_1_11_00001.nii.gz
/data/7998/1/12
/data/7998/1/12/7998_1_12_00001.nii.gz
```

**squirrel.json**

```json
{
    "TotalFileCount": 3342,
    "TotalSize": 25072523595,
    "data": {
        "SubjectCount": 217,
        "subjects": [
            {
                "AlternateIDs": [
                    ""
                ],
                "DateOfBirth": "",
                "Ethnicity1": "nothispanic",
                "Ethnicity2": "black",
                "GUID": "",
                "Gender": "F",
                "Notes": "",
                "Sex": "F",
                "StudyCount": 1,
                "SubjectID": "6028",
                "VirtualPath": "data/6028",
                "studies": [
                    {
                        "AgeAtStudy": 0,
                        "DayNumber": 0,
                        "Description": "Scan",
                        "Equipment": "MR-101",
                        "Height": 0,
                        "Modality": "MR",
                        "Notes": "",
                        "SeriesCount": 11,
                        "StudyDatetime": "2012-02-13 12:54:05",
                        "StudyNumber": 1,
                        "StudyUID": "",
                        "TimePoint": 0,
                        "VirtualPath": "data/6028/1",
                        "VisitType": "",
                        "Weight": 96.6151871001,
                        "series": [
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "localizer",
                                "FileCount": 2,
                                "Protocol": "localizer",
                                "Run": 1,
                                "SequenceNumber": 1,
                                "SeriesDatetime": "2012-02-13 12:54:37",
                                "SeriesNumber": 1,
                                "SeriesUID": "",
                                "Size": 57512,
                                "VirtualPath": "data/6028/1/1"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "ep2d_REST210",
                                "FileCount": 1,
                                "Protocol": "ep2d_REST210",
                                "Run": 1,
                                "SequenceNumber": 2,
                                "SeriesDatetime": "2012-02-13 12:55:47",
                                "SeriesNumber": 3,
                                "SeriesUID": "",
                                "Size": 27891631,
                                "VirtualPath": "data/6028/1/3"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "bas_MoCoSeries",
                                "FileCount": 1,
                                "Protocol": "ep2d_REST210",
                                "Run": 1,
                                "SequenceNumber": 3,
                                "SeriesDatetime": "2012-02-13 12:55:47",
                                "SeriesNumber": 4,
                                "SeriesUID": "",
                                "Size": 27951359,
                                "VirtualPath": "data/6028/1/4"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "intermediate t-Map",
                                "FileCount": 1,
                                "Protocol": "ep2d_REST210",
                                "Run": 1,
                                "SequenceNumber": 4,
                                "SeriesDatetime": "2012-02-13 12:56:20",
                                "SeriesNumber": 5,
                                "SeriesUID": "",
                                "Size": 28907911,
                                "VirtualPath": "data/6028/1/5"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "Mean_&_t-Maps",
                                "FileCount": 1,
                                "Protocol": "ep2d_REST210",
                                "Run": 1,
                                "SequenceNumber": 5,
                                "SeriesDatetime": "2012-02-13 13:01:47",
                                "SeriesNumber": 8,
                                "SeriesUID": "",
                                "Size": 234775,
                                "VirtualPath": "data/6028/1/8"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "MPRAGE",
                                "FileCount": 2,
                                "Protocol": "MPRAGE",
                                "Run": 1,
                                "SequenceNumber": 6,
                                "SeriesDatetime": "2012-02-13 13:11:32",
                                "SeriesNumber": 9,
                                "SeriesUID": "",
                                "Size": 21844580,
                                "VirtualPath": "data/6028/1/9"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "MPRAGE_repeat",
                                "FileCount": 2,
                                "Protocol": "MPRAGE_repeat",
                                "Run": 1,
                                "SequenceNumber": 7,
                                "SeriesDatetime": "2012-02-13 13:21:35",
                                "SeriesNumber": 10,
                                "SeriesUID": "",
                                "Size": 21587804,
                                "VirtualPath": "data/6028/1/10"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "MPRAGE",
                                "FileCount": 2,
                                "Protocol": "MPRAGE",
                                "Run": 1,
                                "SequenceNumber": 8,
                                "SeriesDatetime": "2012-02-13 13:31:08",
                                "SeriesNumber": 11,
                                "SeriesUID": "",
                                "Size": 21621118,
                                "VirtualPath": "data/6028/1/11"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "B1-Callibration Head",
                                "FileCount": 2,
                                "Protocol": "B1-Callibration Head",
                                "Run": 1,
                                "SequenceNumber": 9,
                                "SeriesDatetime": "2012-02-13 13:32:00",
                                "SeriesNumber": 12,
                                "SeriesUID": "",
                                "Size": 2223871,
                                "VirtualPath": "data/6028/1/12"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "B1-Calibration Body",
                                "FileCount": 2,
                                "Protocol": "B1-Calibration Body",
                                "Run": 1,
                                "SequenceNumber": 10,
                                "SeriesDatetime": "2012-02-13 13:33:32",
                                "SeriesNumber": 13,
                                "SeriesUID": "",
                                "Size": 3048390,
                                "VirtualPath": "data/6028/1/13"
                            },
                            {
                                "BIDSEntity": "",
                                "BIDSPhaseEncodingDirection": "",
                                "BIDSRun": "",
                                "BIDSSuffix": "",
                                "BIDSTask": "",
                                "BehavioralFileCount": 0,
                                "BehavioralSize": 0,
                                "Description": "Axial PD-T2 TSE",
                                "FileCount": 3,
                                "Protocol": "Axial PD-T2 TSE",
                                "Run": 1,
                                "SequenceNumber": 11,
                                "SeriesDatetime": "2012-02-13 13:35:29",
                                "SeriesNumber": 14,
                                "SeriesUID": "",
                                "Size": 9712437,
                                "VirtualPath": "data/6028/1/14"
                            }
                        ]
                    }
                ]
            },
            
... <break> ...

    },
    "package": {
        "Changes": "",
        "DataFormat": "nifti4dgz",
        "Datetime": "2025-03-11 17:24:26",
        "Description": "MR data from the major city site for the large project",
        "License": "",
        "Notes": "",
        "PackageFormat": "nifti4dgz",
        "PackageName": "Large dataset from major city",
        "Readme": "",
        "SeriesDirectoryFormat": "orig",
        "SquirrelBuild": "2025.2.350",
        "SquirrelVersion": "1.0",
        "StudyDirectoryFormat": "orig",
        "SubjectDirectoryFormat": "orig"
    }
}


```
