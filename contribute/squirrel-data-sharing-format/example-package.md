# Example package

**Package contents**

```
/
/squirrel.json
/data
/data/S1234ABC
/data/S1234ABC/5
/data/S1234ABC/5/1
/data/S1234ABC/5/1/S1234ABC_5_1_00001.nii.gz
/data/S1234ABC/5/2
/data/S1234ABC/5/2/S1234ABC_5_1_00001.nii.gz
/data/S1234ABC/5/3
/data/S1234ABC/5/3/S1234ABC_5_1_00001.nii.gz
/pipelines
/pipelines/freesurfer
/pipelines/freesurfer/pipeline.json
```

**squirrel.json**

```
{
    "_package": {
        "NiDBversion": "version2022.5.805",
        "datetime": "2022-05-10 14:04:02",
        "description": "more details...",
        "format": "squirrel",
        "name": "pineapples",
        "version": "1.0"
    },
    "pipelines": [
        {
            "createDate": "Mon Apr 6 14:26:18 2020",
            "desc": "freesurfer for all structural T1s",
            "level": 1,
            "name": "freesurferUnified6"
        }
    ],
    "subjects": [
        {
            "ID": "S1234ABC",
            "alternateIDs": [ "ID_001", "ID_009" ],
            "dateOfBirth": "1990-04-29",
            "ethnicity1": "",
            "ethnicity2": "",
            "gender": "U",
            "sex": "U",
            "studies": [
                {
                    "ageAtStudy": 0,
                    "analysis": [
                        {
                            "clusterEndDate": "2018-02-28 02:19:22",
                            "clusterStartDate": "2018-02-26 15:37:32",
                            "diskSize": 312055410,
                            "endDate": "2018-03-09 11:53:36",
                            "hostname": "compute19",
                            "isBad": false,
                            "isComplete": true,
                            "notes": "",
                            "numSeries": 1,
                            "pipelineName": "freesurfer",
                            "pipelineVersion": 14,
                            "startDate": "2018-02-26 15:36:35",
                            "status": "complete",
                            "statusmessage": "Supplement processing complete"
                        }
                    ],
                    "dayNumber": "",
                    "description": "",
                    "modality": "MR",
                    "series": [
                        {
                            "number": 1,
                            "numfiles": 3,
                            "path": "S1234ABC/5/1",
                            "size": 303300
                        },
                        {
                            "number": 2,
                            "numfiles": 3,
                            "path": "S1234ABC/5/2",
                            "size": 302113
                        },
                        {
                            "number": 3,
                            "numfiles": 1,
                            "path": "S1234ABC/5/3",
                            "size": 23720011
                        }
                    ],
                    "studyDateTime": "Tue Mar 28 14:20:18 2017",
                    "studyNumber": 5,
                    "timePoint": "",
                    "visit": ""
                }
            ]
        }
    ]
}
```

**pipeline.json**

```
{
    "clusterType": "sge",
    "clusterUser": "",
    "completeFiles": [
        "{analysisroot}/complete.txt"
    ],
    "createDate": "Mon Apr 6 14:26:18 2020",
    "dataCopyMethod": "nfs",
    "dataSpec": [
        {
            "associatonType": "nearestintime",
            "behDir": "",
            "behFormat": "behnone",
            "dataFormat": "nifti3d",
            "enabled": true,
            "gzip": true,
            "imageType": "",
            "level": "study",
            "location": "data",
            "modality": "MR",
            "numBOLDreps": 0,
            "numImagesCriteria": 0,
            "optional": true,
            "order": 1,
            "preserveSeries": false,
            "primaryProtocol": false,
            "protocol": "T1w",
            "seriesCriteria": "all",
            "usePhaseDir": false,
            "useSeries": false
        }
    ],
    "depDir": "root",
    "depLevel": "study",
    "depLinkType": "hardlink",
    "desc": "freesurfer for structural T1-weighted images",
    "dirStructure": "",
    "directory": "",
    "group": "",
    "groupType": "",
    "level": 1,
    "maxWallTime": 2880,
    "name": "freesurfer",
    "notes": "",
    "numConcurrentAnalysis": 30,
    "primaryScript": [
        {
            "command": "export FREESURFER_HOME=/opt/freesurfer",
            "desc": "The Freesurfer home directory (version) you want to use",
            "enabled": true,
            "logged": true,
            "order": 1,
            "workingdir": ""
        },
        {
            "command": "export FSFAST_HOME=/opt/freesurfer/fsfast",
            "desc": "Not sure if these next two are needed but keep them just in case",
            "enabled": true,
            "logged": true,
            "order": 2,
            "workingdir": ""
        },
        {
            "command": "export MNI_DIR=/opt/freesurfer/mni",
            "desc": "Not sure if these next two are needed but keep them just in case",
            "enabled": true,
            "logged": true,
            "order": 3,
            "workingdir": ""
        },
        {
            "command": "source $FREESURFER_HOME/SetUpFreeSurfer.sh",
            "desc": "MGH's shell script that sets up Freesurfer to run",
            "enabled": true,
            "logged": true,
            "order": 4,
            "workingdir": ""
        },
        {
            "command": "export SUBJECTS_DIR={analysisrootdir}",
            "desc": "Point to the subject directory you plan to use - all FS data will go there",
            "enabled": true,
            "logged": true,
            "order": 5,
            "workingdir": ""
        },
        {
            "command": "recon-all -notal-check -no-isrunning -all -subjid analysis",
            "desc": "Autorecon all {PROFILE}",
            "enabled": true,
            "logged": true,
            "order": 6,
            "workingdir": ""
        }
    ],
    "queue": "slow.*.q",
    "resultScript": "",
    "submitDelay": 0,
    "submitHost": "compute11",
    "tmpDir": "",
    "useProfile": true,
    "useTmpDir": false,
    "version": 1
}
```
