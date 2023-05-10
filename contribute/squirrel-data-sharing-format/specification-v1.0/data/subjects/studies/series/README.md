---
description: JSON array
---

# series

An array of series. Basic series information is stored in the main `squirrel.json` file. Extended information including series parameters such as DICOM tags are stored in a `params.json` file in the series directory.

<figure><img src="https://mermaid.ink/img/pako:eNqVk01r4zAQhv9KmBJwwA5OcFNHhZ7aS1l2YXtbDGU2Gidq_YUks_GG_PeV7EiJsz20OkjvSM-rkUboAJuaEzDYSmx2k28_s2pimqxrHTy__Pjeq1kUPXDUGNhudn9GzPxrg5t33FLgxNV6IxoqREUq8OqKoH1DUpRUaRVcaEfZnIZS7e832hjECbfuYsvolguT6TR-QJSEqpUGceIDhst2q4K-96vDhjaFOZ7N0A__L2OFRaeECpzwSG-I5qYgKLFUuSgoGKRDptMzZC9tETUq-XR6UR-LncMBPseTfmLmfL72_QlOweBx0ZXDXcAanB4MLhoZ_BV0V9DEH98yBbvJ8zw0VZL1O0Uc1Q6lxI4tx6ZRlq8Yr6rwFeuoFJ8xnmz-DT_juXQOT-xddBvH4eBjN0mSnHT0R3C9Y0mzhxBKkiUKbv7owe6Ugd5RSRkwIznl2BY6g6w6GrRtTOXpiQtdS2A5FopCwFbXL121AaZlSw56FGi-fOmpBqtfdT2KgR1gDywOoQO2jFfzVZrcpat0cbdM10l6DOFv74jn66Glt-vFYrVM0-M_0pd19A?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\* required</mark>

|        _**Variable**_ | **Type**   | **Description**                                                                                         |
| --------------------: | ---------- | ------------------------------------------------------------------------------------------------------- |
|        _\***number**_ | number     | Series number. May be sequential, correspond to NiDB assigned series number, or taken from DICOM header |
|      _\***dateTime**_ | date       | Date of the series, usually taken from the DICOM header                                                 |
|           _seriesUID_ | string     | From the SeriesUID DICOM tag                                                                            |
|         _description_ | string     | Description of the series                                                                               |
|            _protocol_ | string     | Protocol name                                                                                           |
|      _experimentName_ | string     | Links to the _experiments_ section of the squirrel package                                              |
|          _\***size**_ | number     | Size of the data, in bytes                                                                              |
|              numFiles | number     | Total number of files (including files in subdirs)                                                      |
|               behSize | number     | Size of beh data, in bytes                                                                              |
|           numBehFiles | number     | Total number of beh files (including files in subdirs)                                                  |
| [_params_](params.md) | JSON file  | _/data/subjectID/studyNum/seriesNum/params.json_                                                        |
|            _analysis_ | JSONobject |                                                                                                         |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/subjectID/studyNum/seriesNum`

Behavioral data is stored in

> `/data/subjectID/studyNum/seriesNum/beh`
