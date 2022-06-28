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
|`datetime`|Datetime is formatted as `YYYY-MM-DD HH:MI:SS` …where all numbers are zero-padded and use a 24-hour clock. Datetime is stored as a JSON string datatype|
“2022-12-03 15:34:56”|
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
|AU|✓|Audio ECG|
|AUDIO|✓|Audio files|
|BI|✓|Biomagnetic imaging|
|CD|✓|Color flow Doppler|
|CONSENT|✓|Scanned image of a consent form|
|CR|✓|✓|Computed Radiography|
CR
✓
Computed radiography (digital x-ray)
CT
✓
✓
Computed Tomography
DD
✓
Duplex Doppler
DG
✓
Diaphanography
DOC
✓
Scanned documents
DX
✓
Digital Radiography
ECG
✓
Electrocardiogram
EEG
✓
Electroencephalography
EPS
✓
Cardiac Electrophysiology
ES
✓
Endoscopy
ET
✓
Eye-tracking
GM
✓
General Microscopy
GSR
✓
Galvanic skin response
HC
✓
Hard Copy
HD
✓
Hemodynamic Waveform
IO
✓
Intra-oral Radiography
IVUS
✓
Intravascular Ultrasound
LS
✓
Laser surface scan
MEG
✓
Magnetoencephalography
MG
✓
Mammography
MR
✓
✓
MRI - Magnetic Resonance Imaging
NM
✓
Nuclear Medicine
OP
✓
Ophthalmic Photography
OT
✓
✓
Other DICOM
PPI
✓
Pre-pulse inhibition
PR
✓
✓
Presentation State
PT
✓
✓
Positron emission tomography (PET)
PX
✓
Panoramic X-Ray
RF
✓
Radio Fluoroscopy
RG
✓
Radiographic imaging (conventional film/screen)
RTDOSE
✓
Radiotherapy Dose
RTIMAGE
✓
Radiotherapy Image
RTPLAN
✓
Radiotherapy Plan
RTRECORD
✓
RT Treatment Record
RTSTRUCT
✓
Radiotherapy Structure Set
SM
✓
Slide Microscopy
SMR
✓
Stereometric Relationship
Squirrel Data Sharing Format – v1.0 23
SNP
✓
SNP genetic information
SR
✓
✓
Structured reporting document
ST
✓
Single-photon emission computed tomography (SPECT)
SURGERY
✓
Pre-surgical Mapping
TASK
✓
Task
TG
✓
Thermography
TMS
✓
Transcranial magnetic stimulation
US
✓
✓
Ultrasound
US
✓
Ultrasound
VIDEO
✓
Video
XA
✓
✓
X-Ray Angiography
XC
✓
External-camera Photography
XRAY
✓
X-ray
