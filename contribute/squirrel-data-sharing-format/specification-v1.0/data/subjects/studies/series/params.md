---
description: Separate JSON file - params.json
---

# params

Series collection parameters are stored in a separate JSON file called `params.json` stored in the series directory. The JSON object is an array of key-value pairs. This can be used for MRI sequence parameters.

All DICOM tags are acceptable parameters. See this list for available DICOM tags [https://exiftool.org/TagNames/DICOM.html](https://exiftool.org/TagNames/DICOM.html). Variable keys can be either the hexadecimal format (ID) or string format (Name). For example `0018:1030` or `ProtocolName`. The params object contains any number of key/value pairs.

![Optional params object](https://mermaid.ink/img/pako:eNptkj1rwzAQhv-KURYFHMjgLip0apdSWmhWQ7laZ0eJZAt90ISQ\_96TYzkljQfdY98jvebsE2sGiUywzoHdFm-fdV\_Q5YYh8NfNx\_tIy9XqSUIAnpbl41Wh518Wmj10yDPc9K2yqFWPns90Y-DBolMG--D5H85WyiTLx-8dNqRkyP18n5wQpaKkqd4xDIKPjpQMdxzpYuf5uM7dy4Epgl4vJYzlfxt60EevPM8wK-OGNA9wYGgYY8ndeTTToDcWG57hmnLUWFw2Fq3SWizaFh\_W69IHN-xRLKqqmnj1o2TYisoeWMkMOgNK0kc-pZNqFrZosGaCUGILUYea1f2Z1GgpFV-kCoNjogXtsWQQw7A59g0TwUXM0rMC-mfMZJ1\_AaHFxtI)

### JSON variables

| _**Variable**_ | **Description**                                       | **Example** |
| -------------- | ----------------------------------------------------- | ----------- |
| _{Tags}_       | A unique key, sometimes derived from the DICOM header | T1w         |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/subjectID/studyNum/seriesNum/params.json`
