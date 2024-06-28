---
description: Separate JSON file - params.json
---

# params

Series collection parameters are stored in a separate JSON file called `params.json` stored in the series directory. The JSON object is an array of key-value pairs. This can be used for MRI sequence parameters.

All DICOM tags are acceptable parameters. See this list for available DICOM tags [https://exiftool.org/TagNames/DICOM.html](https://exiftool.org/TagNames/DICOM.html). Variable keys can be either the hexadecimal format (ID) or string format (Name). For example `0018:1030` or `ProtocolName`. The params object contains any number of key/value pairs.

<figure><img src="https://mermaid.ink/img/pako:eNqVlFFvmzAQx79K5CoSkSAiiKbEk_q0vUzTJq1vEy9XfCReASPbaGFRvvtsg0lIO6nlwf4f_v3P9p3gRArBkFCyl9AeFt9-5s3CPFIIHUWPLRQvsMdgnFefLqvB16cf351aGZCBhsAO14hNwFuseIMqmNQNgccWJa-x0Sq40jeUTc14od0ekVVcNCD71UC5t9Gj6p5_Y2ESeeGzjOt7KboWGqh6xVXgosiHHvVWm053jJujj_MbRI2gOmkQL95gmOz2KnDjtDoktFuY-9od3PR6eTrrq1M6Q7S2LZJQq5JXtktWemQYl8sLaqtgQTXr1HJ5VXaLXcIBvsQL92LlfVNL3TnGYPD46Mbhr2ENXg8GH80M0xV0X-FiOr5lKnpXlmVoaiXFC0YM1AGkhJ4mc9Nsl48Yb6rwEeusFO8x-rY789TP9zj_68H7OA4HF71L03TU0R_O9IEm7ZGEpEZZA2fm4z_ZbDnRB6wxJ9RIhiV0lc5J3pwN2rWm-viFcS0koSVUCkMCnRZPfVMQqmWHHvrMwfxL6okyH9wvIWYxoSdyJDQOSU9oEm_X2yx9yLbZ5iHJdml2Dslf54jXu-HJ7nebzTbJsvM_ooWUoA?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<table data-full-width="true"><thead><tr><th width="137.4">Variable</th><th width="345">Description</th><th width="243">Example</th></tr></thead><tbody><tr><td><em>{Key:Value}</em></td><td>A unique key, sometimes derived from the DICOM header</td><td>Protocol, T1w<br>FieldStrength, 3.0</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/<SubjectID>/<StudyNum>/<SeriesNum>/params.json`
