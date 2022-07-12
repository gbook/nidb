# studies

An array of imaging studies, with information about each study. An imaging study (or imaging session) is defined as a set of related series collected on a piece of equipment during a time period. An example is a research participant receiving an MRI exam. The participant goes into the scanner, has several MR images collected, and comes out. The time spent in the scanner and all of the data collected from it is considered to be a study.

Valid squirrel **modalities** are derived from the DICOM standard and from NiDB modalities. Modality can be any string, but some squirrel readers may not correctly interpret the modality or may convert it to “other” or “unknown”. See full list of modalities.

<mark style="color:red;">\*required</mark>

|        _**Variable**_ | **Type**   | **Description**                                                                                             | **Example**         |
| --------------------: | ---------- | ----------------------------------------------------------------------------------------------------------- | ------------------- |
|        _\***number**_ | number     | Study number. May be sequential or correspond to NiDB assigned study number                                 | 5                   |
| _\***studyDateTime**_ | datetime   | Date of the study                                                                                           | 2022-04-23 16:23:44 |
|    _\***ageAtStudy**_ | number     | Subject’s age at the time of the study                                                                      | 34.1                |
|      _\***modality**_ | string     | Defines the type of data. See table of supported modalities                                                 | MR                  |
|   _\***description**_ | string     | Study description                                                                                           | fMRI of Volunteers  |
|           _dayNumber_ | number     | For repeated studies and clinical trials, this indicates the day number of this study in relation to time 0 | 40                  |
|           _timePoint_ | number     | Similar to day number, but timePoint should be an ordinal number                                            | 3                   |
|           _equipment_ | string     | Equipment name, on which the imaging session was collected                                                  | MRI-3T-Basement     |
|           _numSeries_ | number     | The number of series for this study                                                                         | 3                   |
|              _series_ | JSON array |                                                                                                             |                     |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID` and `studyNum` are the actual subject ID and study number, for example `/data/S1234ABC/1`.

> `/data/subjectID/studyNum`
