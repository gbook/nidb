# series

An array of series. Basic series information is stored in the main squirrel.json file. Extended information including series parameters such as DICOM tags are stored in a params.json file in the series directory.

### JSON variables

<mark style="color:red;">\* required</mark>

|         _**Variable**_ | **Type**   | **Description**                                                                                         | **Example**         |
| ---------------------: | ---------- | ------------------------------------------------------------------------------------------------------- | ------------------- |
|         _\***number**_ | number     | Series number. May be sequential, correspond to NiDB assigned series number, or taken from DICOM header | 2                   |
| _\***seriesDateTime**_ | date       | Date of the series, usually taken from the DICOM header                                                 | 2022-04-23 16:23:44 |
|       _experimentName_ | string     | Links to the _experiments_ section of the squirrel package                                              | MyExperiment        |
|           _\***size**_ | number     | Size of the data, in bytes                                                                              | 523851              |
|               _params_ | JSON file  | _/data/subjectID/studyNum/seriesNum/params.json_                                                        |                     |
|             _analysis_ | JSONobject | \_\_                                                                                                    |                     |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/subjectID/studyNum/seriesNum`
