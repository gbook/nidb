---
description: JSON array
---

# series

An array of series. Basic series information is stored in the main `squirrel.json` file. Extended information including series parameters such as DICOM tags are stored in a `params.json` file in the series directory.

<figure><img src="https://mermaid.ink/img/pako:eNqVlE1vozAQhv9K5CoSkSAiEU2JK_XUXqpVV9reVly8eEi8BYxsow2N8t_XH9gJaQ8tB_sd_Lxje0ZwRCWngDDaCdLtZz9-Fe1MP4JzlSQPHSnfyA6icV7cn1ej59efL1YtNEiJIpEZLhGTgHVQsxZkFNQVAYcOBGugVTK60FeUSU1ZqeweiVGMt0QMC0fZt8mD7P_8hVIn8sJnGdd3gvcdaUk9SCYjGyU-9Ki3mnSqp0wffZw_IRogshca8eIThop-JyM7hlWX0Gyh72t2sNPH5XDWD6e0hmRpWiRIIytWmy4Z6RE3zudn1FTBgHLSqfn8ouwGO4cOPscz-2LhfaGl9hxj4Dw-unL4axiD187go4khXEENNczC8Q1T45uqqmJdK8HfIKFE7okQZMDrqWmyy3eMV1X4jnVSiq8YfdutOfTzK07ncS0OPNymaewc-CbLslEn_xhVe5x1BxSjBkRDGNUf_tFkKpDaQwMFwlpSqEhfqwIV7UmjfacrD0-UKS4QrkgtIUakV_x1aEuElejBQ4-M6P9IEyj9sf3mfBIjfEQHhNMYDQiv081yk2d3-SZf3a3zbZafYvRuHely6578drtabdZ5fvoPBtSTCQ?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required

<table data-full-width="true"><thead><tr><th width="238.99111900532864" align="right">Variable</th><th width="131">Type</th><th width="101">Default</th><th>Description</th></tr></thead><tbody><tr><td align="right"><code>BehavioralFileCount</code></td><td>number</td><td></td><td>Total number of beh files (including files in subdirs)</td></tr><tr><td align="right"><code>BehavioralSize</code></td><td>number</td><td></td><td>Size of beh data, in bytes</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td></td><td>Description of the series</td></tr><tr><td align="right"><code>ExperimentName</code></td><td>string</td><td></td><td>Experiment name associated with this series. Experiments link to the <a href="../../../../experiments.md">experiments</a> section of the squirrel package</td></tr><tr><td align="right"><code>FileCount</code></td><td>number</td><td></td><td>Total number of files (including files in subdirs)</td></tr><tr><td align="right"><code>Protocol</code></td><td>string</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Protocol name</td></tr><tr><td align="right"><code>SeriesDatetime</code></td><td>date</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span></td><td>Date of the series, usually taken from the DICOM header</td></tr><tr><td align="right"><code>SeriesNumber</code></td><td>number</td><td><span data-gb-custom-inline data-tag="emoji" data-code="1f534">🔴</span> <span data-gb-custom-inline data-tag="emoji" data-code="1f535">🔵</span></td><td>Series number. May be sequential, correspond to NiDB assigned series number, or taken from DICOM header</td></tr><tr><td align="right"><code>SeriesUID</code></td><td>string</td><td></td><td>From the SeriesUID DICOM tag</td></tr><tr><td align="right"><code>Size</code></td><td>number</td><td></td><td>Size of the data, in bytes</td></tr><tr><td align="right"><a href="params.md">params</a></td><td>JSON file</td><td></td><td><code>data/subjectID/studyNum/seriesNum/params.json</code></td></tr><tr><td align="right"><a href="../analysis.md">analysis</a></td><td>JSON object</td><td></td><td> </td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/<SubjectID>/<StudyNum>/<SeriesNum>`

Behavioral data is stored in

> `/data/<SubjectID>/<StudyNum>/<SeriesNum>/beh`
