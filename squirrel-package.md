# _package

```mermaid
graph LR;
    _package(_package)
    _package-->data;
    _package-->pipelines;
    _package-->experiments;
    data-->subjects;
    subjects-->studies;
    subjects-->measures;
    subjects-->drugs;
    studies-->series;
    series-->params;
    studies-->analysis;
    pipelines-->dataSpec;
    pipelines-->primaryScript;
    pipelines-->secondaryScript;
    style _package fill:#8c1212,color:#fff,stroke:#444
```

## Description
Information about the package. The first letter is an underscore, so the package details appear at the top of the JSON file to make it more readable.

## JSON variables

|Variable|Type|Description|Example|Required?|
|---:|---|---|---|---|
|`format`|string|Defines the package format|Squirrel|Yes|
|`version`|string|squirrel format version|1.0|Yes|
|`iDBVersion`|string|The NiDB version which wrote the package|2022.4.780||
|`name`|string|Short name of the package	MRI data export	Yes|
|`description`|string|Longer description of the package||
|`date`|datetime|Date the package was created|2022-04-30 13:34:12|Yes|
|`subjects`|JSON array|

## File Structure
`/`
