---
description: Separate JSON file - params.json
---

# params

Series collection parameters are stored in a separate JSON file called `params.json` stored in the series directory. The JSON object is an array of key-value pairs. This can be used to store data collection parameters.

All DICOM tags are acceptable parameters. See this list for available DICOM tags [https://exiftool.org/TagNames/DICOM.html](https://exiftool.org/TagNames/DICOM.html). Variable keys can be either the hexadecimal format (ID) or string format (Name). For example `0018:1030` or `ProtocolName`. The params object contains any number of key/value pairs.

<figure><img src="https://mermaid.ink/img/pako:eNqVlFFrgzAQx7-KpAgKOrrRvTjo0_Yyxgbr2_Dlas6aVY0kcauUfvclamy1HbR9MPfP_f5eckfdk4RTJBHZCKgy5-0zLh39E5wr73X18d5GfhguKSjwzMN_OiJ6v4JkCxv0-nWaZRXmrETpDdGEwF2FghVYKumdxBPKFA4pSxTjJYjGm2i_g9vdcLkRvK6ghLyRunCrHCvte3tU1utvTHRpG9i81YZRNWX6Rf16geBrieIHzGGkdyousKxUOq2v2MIjNdBdIVNat8NUbpfzdHcpJj0bDEhrCO_MeAQUMmW5mZAJLdI9XfeImpYYUI7G7LonUzHYUXbwUTvthm99w8Tbc_Si81g1cdhrGIONO4NVI8NwBdXk6AzHN0wezdI0DXSvBN9iSEFmIAQ00cPYNKpyi3HShVuso1ZcY5zYh4le4_3Xg4_zeXDu6rei2WKxsOlfRlUW3Vc7EpACRQGM6g_G3hSIicqwwJhEOqSYQp2rmMTlQaN1pUeCL5QpLkiUQi4xIFArvmrKhERK1GihZwb6-1MMlP73fnFu9eEPqquZ5w?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<table data-full-width="true"><thead><tr><th width="137.4">Variable</th><th width="345">Description</th><th width="366">Example</th></tr></thead><tbody><tr><td><em>{Key:Value}</em></td><td>A unique key, sometimes derived from the DICOM header</td><td>Protocol, T1w<br>FieldStrength, 3.0</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/<SubjectID>/<StudyNum>/<SeriesNum>/params.json`
