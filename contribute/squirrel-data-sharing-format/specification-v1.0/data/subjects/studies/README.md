---
description: JSON array
---

# studies

An array of imaging studies, with information about each study. An imaging study (or imaging session) is defined as a set of related series collected on a piece of equipment during a time period. An example is a research participant receiving an MRI exam. The participant goes into the scanner, has several MR images collected, and comes out. The time spent in the scanner and all of the data collected from it is considered to be a study.

Valid squirrel **modalities** are derived from the DICOM standard and from NiDB modalities. Modality can be any string, but some squirrel readers may not correctly interpret the modality or may convert it to “other” or “unknown”. See full list of modalities.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8TFwRiWngDA6CNIdF99-Fu3CXIJzlSRPHSlfyQGicVw9Xmejry8_vju1MiAlikT2dovYBKyDmrUgo6BmBJw6EKyBVsnoRs8om5qyUrk1EqsYb4noVwPlniZPUv_-A6VJ5IXPMs4fBNcdaUndSyYjFyU-9Ki32nRKU2ZKH8d3iAaI1MIgXrzDUKEPMnL3MDsktEuY_doV3PB2OtQ6r3K5HCzJ2h6SII2sWG3PyUoPvUVtHywoJ2e1XN403mLXcICv8cI9WHlfOFRXxxgMHh_NHH4j1uD1YPDRxBC2oPoaFqF8y9T4rqqq2HRL8FdIKJFHIgTp8XZqmqzyGeOsC5-xTlrxEePMHk70I97BM740wQD3aRoPFnyXZdmok7-MqiPOuhOKUQOiIYyar_9sUxVIHaGBAmEjKVRE16pARXsxqO5M8-ELZYoLhCtSS4gR0Yq_9G2JsBIaPPTMiPmZNIEyX9wvzicxwmd0QjiNUY_wNt2td3n2kO_yzcM232f5JUb_nCNd74crv99vNrttnl_-A4GhlBM?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th align="right"></th><th width="150"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><em>*<strong>number</strong></em></td><td>number</td><td>Study number. May be sequential or correspond to NiDB assigned study number</td></tr><tr><td align="right"><em><strong>*dateTime</strong></em></td><td>datetime</td><td>Date of the study</td></tr><tr><td align="right"><em>*<strong>ageAtStudy</strong></em></td><td>number</td><td>Subject’s age at the time of the study</td></tr><tr><td align="right">height</td><td>number</td><td>Height in <strong>m</strong> of the subject at the time of the study</td></tr><tr><td align="right">weight</td><td>number</td><td>Weight in <strong>kg</strong> of the subject at the time of the study</td></tr><tr><td align="right"><em>*<strong>modality</strong></em></td><td>string</td><td>Defines the type of data. See table of supported modalities</td></tr><tr><td align="right"><em>*<strong>description</strong></em></td><td>string</td><td>Study description</td></tr><tr><td align="right"><em>studyUID</em></td><td>string</td><td>DICOM field StudyUID</td></tr><tr><td align="right"><em>visitType</em></td><td>string</td><td>Type of visit. ex: Pre, Post</td></tr><tr><td align="right"><em>dayNumber</em></td><td>number</td><td>For repeated studies and clinical trials, this indicates the day number of this study in relation to time 0</td></tr><tr><td align="right"><em>timePoint</em></td><td>number</td><td>Similar to day number, but timePoint should be an ordinal number</td></tr><tr><td align="right"><em>equipment</em></td><td>string</td><td>Equipment name, on which the imaging session was collected</td></tr><tr><td align="right">virtualPath</td><td>string</td><td>Relative path to the data within the package</td></tr><tr><td align="right"><a href="series/"><em>series</em></a></td><td>JSON array</td><td></td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory. `subjectID` and `studyNum` are the actual subject ID and study number, for example `/data/S1234ABC/1`.

> `/data/subjectID/studyNum`
