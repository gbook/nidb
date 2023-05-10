---
description: JSON array
---

# studies

An array of imaging studies, with information about each study. An imaging study (or imaging session) is defined as a set of related series collected on a piece of equipment during a time period. An example is a research participant receiving an MRI exam. The participant goes into the scanner, has several MR images collected, and comes out. The time spent in the scanner and all of the data collected from it is considered to be a study.

Valid squirrel **modalities** are derived from the DICOM standard and from NiDB modalities. Modality can be any string, but some squirrel readers may not correctly interpret the modality or may convert it to “other” or “unknown”. See full list of modalities.

<figure><img src="https://mermaid.ink/img/pako:eNqVk02L2zAQhv9KmCXggB2c4M06WuipvZTSQvdWDMs0Gifa9ReSTOOG_PdKtqXE6R52dZDekZ5XI42tE-xqTsBgL7E5zL79zKqZabKudfD16cf3Xi2i6BNHjYHtFo8XxMw_N7h7xT0FTtysN6KhQlSkAq9uCDo2JEVJlVbBlXaUzWko1f5-oZ1BnHDrLraMbrkwmcbxDaIkVK00iBNvMFy2exX0vV8dNrQpzPFshn74fxkrLDolVOCER3pDtDQFQYmlykVBwSAdMp9fIHtpi6hJyefzq_pY7BIO8CWe9RML5_O1708wBoPHRTcOdwFrcHowuGhi8FfQXUEzf3zLFOwuz_PQVEnWrxRxVAeUEju2npomWT5ivKnCR6yTUrzHONr8N3yP59o5_ireRvdxHA5GdpckyaijP4LrA0uaI4RQkixRcPNIT3arDPSBSsqAGckpx7bQGWTV2aBtY0pPX7jQtQSWY6EoBGx1_dRVO2BatuSgzwLNmy891WD1q64nMbATHIHFIXTA1vFmuUmTh3STrh7W6TZJzyH87R3xcju09H67Wm3WaXr-B2pxdmo?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

|      _**Variable**_ | **Type**   | **Description**                                                                                             |
| ------------------: | ---------- | ----------------------------------------------------------------------------------------------------------- |
|      _\***number**_ | number     | Study number. May be sequential or correspond to NiDB assigned study number                                 |
|    _**\*dateTime**_ | datetime   | Date of the study                                                                                           |
|  _\***ageAtStudy**_ | number     | Subject’s age at the time of the study                                                                      |
|              height | number     | Height in **m** of the subject at the time of the study                                                     |
|              weight | number     | Weight in **kg** of the subject at the time of the study                                                    |
|    _\***modality**_ | string     | Defines the type of data. See table of supported modalities                                                 |
| _\***description**_ | string     | Study description                                                                                           |
|          _studyUID_ | string     | DICOM field StudyUID                                                                                        |
|         _visitType_ | string     | Type of visit. ex: Pre, Post                                                                                |
|         _dayNumber_ | number     | For repeated studies and clinical trials, this indicates the day number of this study in relation to time 0 |
|         _timePoint_ | number     | Similar to day number, but timePoint should be an ordinal number                                            |
|         _equipment_ | string     | Equipment name, on which the imaging session was collected                                                  |
|         virtualPath | string     | Relative path to the data within the package                                                                |
| [_series_](series/) | JSON array |                                                                                                             |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID` and `studyNum` are the actual subject ID and study number, for example `/data/S1234ABC/1`.

> `/data/subjectID/studyNum`
