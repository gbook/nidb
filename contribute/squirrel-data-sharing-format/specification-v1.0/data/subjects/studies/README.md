---
description: JSON array
---

# studies

An array of imaging studies, with information about each study. An imaging study (or imaging session) is defined as a set of related series collected on a piece of equipment during a time period. An example is a research participant receiving an MRI exam. The participant goes into the scanner, has several MR images collected, and comes out. The time spent in the scanner and all of the data collected from it is considered to be a study.

Valid squirrel **modalities** are derived from the DICOM standard and from NiDB modalities. Modality can be any string, but some squirrel readers may not correctly interpret the modality or may convert it to “other” or “unknown”. See full list of modalities.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptkj1rwzAQhv9KUBYFEsjgLip0apdSWqhXQ7la50SNJAt90JiQ\_96TazkljQfdY98jvebsE2t7iUywnQe3X7y8N3ZBl-\_7yJ\_rt9eRVpvNg4QIPC-r-4tCzz8ctAfYIS9w1XfKoVYWA5\_pysCjQ68M2hj4Hy5WziQrpM8vbEkpUPrlPjsxSUVJU71hGISQPCkFbjjSp13g4zp3fw\_MEfR6OWEs\_9tgQQ9BBV5gVsYNeR7gwdAwxlK682imQdcOW17gkjJoLFmLTmktll2Hd9vtOkTfH1Asq6qaePOtZNyLyh3Zmhn0BpSkr3zKRzUs7tFgwwShxA6Sjg1r7JnU5CgWn6SKvWeiAx1wzSDFvh5sy0T0CYv0qIB-GjNZ5x9\_AcdP)

### JSON variables

<mark style="color:red;">\*required</mark>

|        _**Variable**_ | **Type**   | **Description**                                                                                             |
| --------------------: | ---------- | ----------------------------------------------------------------------------------------------------------- |
|        _\***number**_ | number     | Study number. May be sequential or correspond to NiDB assigned study number                                 |
| _\***studyDateTime**_ | datetime   | Date of the study                                                                                           |
|    _\***ageAtStudy**_ | number     | Subject’s age at the time of the study                                                                      |
|      _\***modality**_ | string     | Defines the type of data. See table of supported modalities                                                 |
|   _\***description**_ | string     | Study description                                                                                           |
|           _dayNumber_ | number     | For repeated studies and clinical trials, this indicates the day number of this study in relation to time 0 |
|           _timePoint_ | number     | Similar to day number, but timePoint should be an ordinal number                                            |
|           _equipment_ | string     | Equipment name, on which the imaging session was collected                                                  |
|           _numSeries_ | number     | The number of series for this study                                                                         |
|              _series_ | JSON array |                                                                                                             |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID` and `studyNum` are the actual subject ID and study number, for example `/data/S1234ABC/1`.

> `/data/subjectID/studyNum`
