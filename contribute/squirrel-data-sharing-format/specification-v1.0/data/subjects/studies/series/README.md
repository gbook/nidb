# series

An array of series. Basic series information is stored in the main `squirrel.json` file. Extended information including series parameters such as DICOM tags are stored in a `params.json` file in the series directory.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptks1qwzAQhF\_FKBcFbMjBvajQU3sppYXmaihba-2okWyhH5oQ8u5duZZT0vigHXk-7Yi1T6wdJTLBegd2V7y8N0NBjxvHwJ-3b6-TWlfVg4QAPC3r-wtC7z8stHvokWdx5VtlUasBPV\_UFYEHi04ZHILnf3SmUiZRPn5-YUtIFtnP-8SEKBUlzfUGYRB8dIRkcYORLvaeT-vi\_jZMEXS9lDCV\_zYMoI9eeZ7FgkwH0jzAgaFhTCW7y2jmQW8ttjyLS8pR49yo6JTWYtV1eLfZlD64cY9iVdf1rKtvJcNO1PbASmbQGVCSPvIpdWpY2KHBhgmSEjuIOjSsGc6ERkup-CRVGB0THWiPJYMYxu1xaJkILmKGHhXQP2Nm6vwDpfHG2Q)

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
