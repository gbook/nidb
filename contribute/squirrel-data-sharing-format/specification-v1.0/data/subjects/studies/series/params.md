# params

All DICOM tags are acceptable parameters. See this list for available DICOM tags [https://exiftool.org/TagNames/DICOM.html](https://exiftool.org/TagNames/DICOM.html). Variable keys can be either the hexadecimal format (ID) or string format (Name). For example “0018:1030” or “ProtocolName”. The params object contains any number of key/value pairs.

### JSON variables

| _**Variable**_ | **Description**                                       | **Example** |
| -------------- | ----------------------------------------------------- | ----------- |
| _{Tags}_       | A unique key, sometimes derived from the DICOM header | T1w         |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/subjectID/studyNum/seriesNum/params.json`
