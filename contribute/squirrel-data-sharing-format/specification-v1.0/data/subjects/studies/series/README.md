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

{% include "../../../../../../../.gitbook/includes/series.md" %}

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/<SubjectID>/<StudyNum>/<SeriesNum>`

Behavioral data is stored in

> `/data/<SubjectID>/<StudyNum>/<SeriesNum>/beh`
