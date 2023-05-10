---
description: Separate JSON file - params.json
---

# params

Series collection parameters are stored in a separate JSON file called `params.json` stored in the series directory. The JSON object is an array of key-value pairs. This can be used for MRI sequence parameters.

All DICOM tags are acceptable parameters. See this list for available DICOM tags [https://exiftool.org/TagNames/DICOM.html](https://exiftool.org/TagNames/DICOM.html). Variable keys can be either the hexadecimal format (ID) or string format (Name). For example `0018:1030` or `ProtocolName`. The params object contains any number of key/value pairs.

<figure><img src="https://mermaid.ink/img/pako:eNqVlN1q4zAQhV8lTAk44AQnuKmjhb3avVlKC-1dMZTZaJxo6z8kmcYNefdKdqTE2RZaX9hnpO_MSCPkPawrTsBgI7Hejm4f0nJkHllVOvjzeH_Xqcl0-pOjxsC-Jj9OiBl_rnH9ghsKnLiYr0VNuShJBV5dELSrSYqCSq2CM-0oW9NQqvn7j9YGccLNu9gyuuHCVDp-PyAKQtVIgzjxAcNls1FB9_azfUJbwizPVug-_09jiXmrhAqc8EhnmM5MQ1BioTKRU9BLh4zHJ8hu2iJq0PLx-Kw_FjuFPXyKR93AxPl877sVHIPe46ILh9uANTjdG1w0MPgt6DankV--ZXJ2lWVZaLokqxeaclRblBJbthiaBlW-Y7zownesg1Z8xXi0-TP0HrqOImdjV3Ecf55juAR7C4ZZzpMcc7wKrrcsrncQQkGyQMHNnd3bTCnoLRWUAjOSU4ZNrlNIy4NBm9qcBP3mQlcSWIa5ohCw0dVjW66BadmQg34JNL-AwlM1lk9VNYiB7WEHLAqhBbaIlrNlEt8ky2R-s0hWcXII4a1zRLNV_yTXq_l8uUiSwzs6rHno?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

| _**Variable**_ | **Description**                                       | **Example**                                |
| -------------- | ----------------------------------------------------- | ------------------------------------------ |
| _{Tags}_       | A unique key, sometimes derived from the DICOM header | <p>Protocol, T1w<br>FieldStrength, 3.0</p> |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/subjectID/studyNum/seriesNum/params.json`
