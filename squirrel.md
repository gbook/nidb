<style
  type="text/css">
h1 { counter-reset: h2counter; }
h2 { counter-reset: h3counter; }
h3 { counter-reset: h4counter; }
h4 { counter-reset: h5counter; }
h5 { counter-reset: h6counter; }
h6 {}

h2:before {
    counter-increment: h2counter;
    content: counter(h2counter) ".\0000a0\0000a0";
}

h3:before {
    counter-increment: h3counter;
    content: counter(h2counter) "." counter(h3counter) ".\0000a0\0000a0";
}

h4:before {
    counter-increment: h4counter;
    content: counter(h2counter) "." counter(h3counter) "." counter(h4counter) ".\0000a0\0000a0";
}

h5:before {
    counter-increment: h5counter;
    content: counter(h2counter) "." counter(h3counter) "." counter(h4counter) "." counter(h5counter) ".\0000a0\0000a0";
}

h6:before {
    counter-increment: h6counter;
    content: counter(h2counter) "." counter(h3counter) "." counter(h4counter) "." counter(h5counter) "." counter(h6counter) ".\0000a0\0000a0";
}
</style>

<a href="index.html">Home</a>

# Squirrel Format

# Overview
A squirrel contains a JSON file with meta-data about all of the data in the package, and a directory structure to store files. While many data items are optional, a squirrel package must contain a JSON file and a data directory.

**JSON File**

JSON is javascript object notation, and many tutorials are available for how to read and write JSON files. Keys are camel-case, for example dayNumber or dateOfBirth, where each word in the key is capitalized except the first word. The JSON file should be manually editable.
JSON resources:
* Tutorial - https://www.w3schools.com/js/js_json_intro.asp
* Wiki - https://en.wikipedia.org/wiki/JSON
* Specification - https://www.json.org/json-en.html

**Squirrel data types**

The JSON specification includes several data types, but squirrel uses some derivative data types: string, number, date, datetime, char. Date, datetime, and char are stored as the JSON string datatype and should be enclosed in double quotes.

|Type|Notes|Example|
|---|---|---|---|
|`string`|Regular string|&quot;My string of text&quot;|
|`number`|Any JSON acceptable number|3.14159 or 1000000|
|`datetime`|Datetime is formatted as `YYYY-MM-DD HH:MI:SS`, where all numbers are zero-padded and use a 24-hour clock. Datetime is stored as a JSON string datatype|“2022-12-03 15:34:56”|
|`date`|Date is formatted as YYYY-MM-DD|“1990-01-05”|
|`char`|A single character|F|
|`bool`|`true` or `false`|`true`|
|JSON array|Item is a JSON array of any data type||
|JSON object|Item is a JSON object||

**Directory Structure**

The JSON file `squirrel.json` is stored in the root directory. A directory called `data` contains any data described in the JSON file. Files can be of any type, with file any extension. Because of the broad range of environments in which squirrel files are used, filenames must only contain alphanumeric characters. Filenames cannot contain special characters or spaces and must be less than 255 characters in length.

**Squirrel Package**

A squirrel package becomes a package once the entire directory structure is combined into a zip file. The compression level does not matter, as long as the file is a .zip archive. Once created, this package can be distributed to other instances of NiDB, squirrel readers, or simply unzipped and manually extracted. Packages can be created manually or exported using NiDB or squirrel converters.

# Package Specification
Links to package section details
- <a href="squirrel-package.html">`_package`</a>
  - <a href="squirrel-data.html">`data`</a>
    - <a href="squirrel-subjects.html">`subjects`</a>
      - <a href="squirrel-studies.html">`studies`</a>
        - <a href="squirrel-series.html">`series`</a>
          - <a href="squirrel-params.html">`params`</a>
        - <a href="squirrel-analysis.html">`analysis`</a>
      - <a href="squirrel-measures.html">`measures`</a>
      - <a href="squirrel-drugs.html">`drugs`</a>
  - <a href="squirrel-pipelines.html">`pipelines`</a>
    - <a href="squirrel-dataspec.html">`dataSpec`</a>
    - <a href="squirrel-primaryscript.html">`primaryScript`</a>
    - <a href="squirrel-secondaryscript.html">`secondaryScript`</a>
  - <a href="squirrel-experiments.html">`experiments`</a>

# Modalities

|Modality|DICOM standard|NiDB support|Description|
|---|---|---|---|
|ASSESSMENT||✓|Paper based assessment|
|AU|✓||Audio ECG|
|AUDIO||✓|Audio files|
|BI|✓||Biomagnetic imaging|
|CD|✓||Color flow Doppler|
|CONSENT||✓|Scanned image of a consent form|
|CR|✓|✓|Computed Radiography|
|CR|✓||Computed radiography (digital x-ray)|
|CT|✓|✓|Computed Tomography|
|DD|✓||Duplex Doppler|
|DG|✓||Diaphanography|
|DOC||✓|Scanned documents|
|DX|✓||Digital Radiography|
|ECG||✓|Electrocardiogram|
|EEG||✓|Electroencephalography|
|EPS|✓||Cardiac Electrophysiology|
|ES|✓||Endoscopy|
|ET||✓|Eye-tracking|
|GM|✓||General Microscopy|
|GSR||✓|Galvanic skin response|
|HC|✓||Hard Copy|
|HD|✓||Hemodynamic Waveform|
|IO|✓||Intra-oral Radiography|
|IVUS|✓||Intravascular Ultrasound|
|LS|✓||Laser surface scan|
|MEG||✓|Magnetoencephalography|
|MG|✓||Mammography|
|MR|✓|✓|MRI - Magnetic Resonance Imaging|
|NM|✓||Nuclear Medicine|
|OP|✓||Ophthalmic Photography|
|OT|✓|✓|Other DICOM|
|PPI||✓|Pre-pulse inhibition|
|PR|✓|✓|Presentation State|
|PT|✓|✓|Positron emission tomography (PET)|
|PX|✓||Panoramic X-Ray|
|RF|✓||Radio Fluoroscopy|
|RG|✓||Radiographic imaging (conventional film/screen)|
|RTDOSE|✓||Radiotherapy Dose|
|RTIMAGE|✓||Radiotherapy Image|
|RTPLAN|✓||Radiotherapy Plan|
|RTRECORD|✓||RT Treatment Record|
|RTSTRUCT|✓||Radiotherapy Structure Set|
|SM|✓||Slide Microscopy|
|SMR|✓||Stereometric Relationship|
|SNP||✓|SNP genetic information|
|SR|✓|✓|Structured reporting document|
|ST|✓||Single-photon emission computed tomography (SPECT)|
|SURGERY||✓|Pre-surgical Mapping|
|TASK||✓|Task|
|TG|✓||Thermography|
|TMS||✓|Transcranial magnetic stimulation|
|US|✓|✓|Ultrasound|
|VIDEO||✓|Video|
|XA|✓|✓|X-Ray Angiography|
|XC|✓||External-camera Photography|
|XRAY||✓|X-ray|

# Example Squirrel Package
Example squirrel package

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
