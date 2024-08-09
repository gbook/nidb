---
description: JSON array
---

# series

An array of series. Basic series information is stored in the main `squirrel.json` file. Extended information including series parameters such as DICOM tags are stored in a `params.json` file in the series directory.

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFrgzAUx7-KZBQU6ijDXRz0tF3G2GC9DS-v5tlmVSNJ3Cql332JMWl1PbQezPsnv39e8h56IDmnSFKyEdBsg7fPrA70IzhX4evq472PojheUlAQmlf0dEL0fAP5DjYYDuN0lTVYshpl6KMJgfsGBauwVjI8iyeUSRxTlivGaxBdONGRhfvZeLkRvG2ghrKTOnGvAifdvgMq2_U35jq1C9y604ZRLWV6o2G8QPC1RPED5jAyPBcXWFYrvayv2MMj5WmbyKTW5TCZ--H_sr0Uk6ELPNIb4nvTHgGVLFhpOmRCh9j3bHZCTUkMKEdtns3OumKwk7TwSQf9ROR8vuP9OQZhPU5NHO4axuBia3BqZPBXUF2JgT--Ycr0riiKua6V4DuMKcgtCAFd-jA2jbLcYpxU4RbrqBTXGCd239FrvNZjm-x5fFws5taR3iVJMsTxL6NqmybNnsxJhaICRvWf4WB2yojaYoUZSXVIsYC2VBnJ6qNG20bXHl8oU1yQtIBS4pxAq_iqq3OSKtGig54Z6B9N5Sn9mX5x7vTxD8AkkTU?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required\
:yellow\_circle: Computed (squirrel writer/reader should handle these variables)

<table data-full-width="true"><thead><tr><th width="238.99111900532864" align="right">Variable</th><th width="131">Type</th><th width="101">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>BidsType</code></td><td>string</td><td></td><td><a href="https://bids.neuroimaging.io/">BIDS</a> type (anat, fmri, motion, etc)</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td></td><td>Description of the series</td></tr><tr><td align="right"><code>ExperimentName</code></td><td>string</td><td></td><td>Experiment name associated with this series. Experiments link to the <a href="../../../../experiments.md">experiments</a> section of the squirrel package</td></tr><tr><td align="right"><code>Protocol</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Protocol name</td></tr><tr><td align="right"><code>SeriesDatetime</code></td><td>date</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Date of the series, usually taken from the DICOM header</td></tr><tr><td align="right"><code>SeriesNumber</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> <span data-gb-custom-inline data-tag="emoji" data-code="1f535">🔵</span></td><td>Series number. May be sequential, correspond to NiDB assigned series number, or taken from DICOM header</td></tr><tr><td align="right"><code>SeriesUID</code></td><td>string</td><td></td><td>From the SeriesUID DICOM tag</td></tr><tr><td align="right"><code>BehavioralFileCount</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Total number of beh files (including files in subdirs)</td></tr><tr><td align="right"><code>BehavioralSize</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Size of beh data, in bytes</td></tr><tr><td align="right"><code>FileCount</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Total number of files (including files in subdirs)</td></tr><tr><td align="right"><code>Size</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f7e1">🟡</span></td><td>Size of the data, in bytes</td></tr><tr><td align="right"><a href="params.md">params</a></td><td>JSON file</td><td></td><td><code>data/subjectID/studyNum/seriesNum/params.json</code></td></tr><tr><td align="right"><a href="../analysis.md">analysis</a></td><td>JSON object</td><td></td><td> </td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/<SubjectID>/<StudyNum>/<SeriesNum>`

Behavioral data is stored in

> `/data/<SubjectID>/<StudyNum>/<SeriesNum>/beh`
