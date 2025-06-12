---
description: JSON array
---

# studies

An array of imaging studies, with information about each study. An imaging study (or imaging session) is defined as a set of related series collected on a piece of equipment during a time period. An example is a research participant receiving an MRI exam. The participant goes into the scanner, has several MR images collected, and comes out. The time spent in the scanner and all of the data collected from it is considered to be a study.

Valid squirrel **modalities** are derived from the DICOM standard and from NiDB modalities. Modality can be any string, but some squirrel readers may not correctly interpret the modality or may convert it to “other” or “unknown”. See full list of [modalities](../../../modalities.md).

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFvgjAUx78KqSHBRBazsAtLPG2XZdmSeVu4POlDOoGStmwS43dfSykKelAO9P3b37-vfU85kJRTJDHZCqhz7_0rqTz9CM5V8Lb-_OiieRiuKCgIzGv-fEL0fA3pDrYY9ON0ldVYsAplMEQTAvc1ClZipWRwFk8okzikLFWMVyDaYKLnFu5mw9VW8KaGCopW6sSd8px0-_aobDY_mOrULnDrThtGNZTpjfrxCsE3EsUvmMPI4FxcYVml9LK-YgeP1EDbRCa1LofJ3A2Xy_ZSTAYucIjvW0v4YBokoJQZK0yPTOigS9QUxYBy1GjfP-uLwU7SwiftdRNz5xt63p2jF9bj1MThLmIMLrYGp0aG4QqqLdAbjm-YIp5lWbbQ1RJ8hyEFmYMQ0MaPY9Moyz3GSRXusY5KcYtxYh86eovXevofzWDAp-VyYS3xLIqiPg7_GFV5HNV7siAlihIY1R-Hg9kqISrHEhMS65BiBk2hEpJUR402tS4-vlKmuCBxBoXEBYFG8XVbpSRWokEHvTDQ35rSTeo_6jfnZW86_gPjwJGq?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required\
:yellow\_circle: Computed (squirrel writer/reader should handle these variables)

{% include "../../../../../../.gitbook/includes/studies.md" %}

### Directory structure

Files associated with this section are stored in the following directory. `SubjectID` and `StudyNum` are the actual subject ID and study number, for example `/data/S1234ABC/1`.

> `/data/<SubjectID>/<StudyNum>`
