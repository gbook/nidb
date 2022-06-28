# Package root

```mermaid
graph LR;
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
```

## Description
Meta-data about the squirrel package is stored in the root of the .json document.

## JSON variables
|Variable|Type|Description|Example|Required?|
|---|---|---|---|---|
|`_package`|JSON object|
|`data`|JSON object|

## File Structure
`/`
