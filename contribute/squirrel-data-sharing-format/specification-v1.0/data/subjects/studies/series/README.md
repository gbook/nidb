# series

[![](https://mermaid.ink/img/pako:eNptkT9vwyAQxb-KdVlayZYyuAuVOrVbpmZFqq7mbNMARnCosaJ89-L6z-KwAO\_37p0ObtAMikBAF9D3xelTuiKvL4\_NBTuqqjeFjK871WtPRjuKe0RXT0FbchxnNiVkPabvH2p4rVivE-GkND0AljCm8IiokLpNnsunoNyY9jI6NGPUG\_h3Pc3b8zQMBrQr3SZbZj97arbE0dBSXrTaGHFoW3o5HsvIYbiQONR1vZyrX624F7W\_QgmWgkWt8jPfpiQJ3JMlCSIfFbWYDEuQ7p6tyeem9KE0DwFEiyZSCZh4OI-uAcEh0Wp615h\_zS6u-x8\_4pqg)](https://mermaid.live/edit#pako:eNptkT9vwyAQxb-KdVlayZYyuAuVOrVbpmZFqq7mbNMARnCosaJ89-L6z-KwAO\_37p0ObtAMikBAF9D3xelTuiKvL4\_NBTuqqjeFjK871WtPRjuKe0RXT0FbchxnNiVkPabvH2p4rVivE-GkND0AljCm8IiokLpNnsunoNyY9jI6NGPUG\_h3Pc3b8zQMBrQr3SZbZj97arbE0dBSXrTaGHFoW3o5HsvIYbiQONR1vZyrX624F7W\_QgmWgkWt8jPfpiQJ3JMlCSIfFbWYDEuQ7p6tyeem9KE0DwFEiyZSCZh4OI-uAcEh0Wp615h\_zS6u-x8\_4pqg)

An array of series. Basic series information is stored in the main `squirrel.json` file. Extended information including series parameters such as DICOM tags are stored in a `params.json` file in the series directory.

### JSON variables

<mark style="color:red;">\* required</mark>

|         _**Variable**_ | **Type**   | **Description**                                                                                         |
| ---------------------: | ---------- | ------------------------------------------------------------------------------------------------------- |
|         _\***number**_ | number     | Series number. May be sequential, correspond to NiDB assigned series number, or taken from DICOM header |
| _\***seriesDateTime**_ | date       | Date of the series, usually taken from the DICOM header                                                 |
|       _experimentName_ | string     | Links to the _experiments_ section of the squirrel package                                              |
|           _\***size**_ | number     | Size of the data, in bytes                                                                              |
|               _params_ | JSON file  | _/data/subjectID/studyNum/seriesNum/params.json_                                                        |
|             _analysis_ | JSONobject |  __                                                                                                     |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/subjectID/studyNum/seriesNum`
