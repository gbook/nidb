# params

[![](https://mermaid.ink/img/pako:eNptkT9vwyAQxb-KdVlayZYyuAuVOrVbpmZFqq7mbNMARnCosaJ89-L6z-KwAO\_37p0ObtAMikBAF9D3xelTuiKvL4\_NBTuqqjeFjK871WtPRjuKe0RXT0FbchxnNiVkPabvH2p4rVivE-GkND0AljCm8IiokLpNnsunoNyY9jI6NGPUG\_h3TUNgQBuf5u15odtky-xnT82WOBoqZnvRamPEoW3p5XgsI4fhQuJQ1\_Vyrn614l7U\_golWAoWtcrPfJuSJHBPliSIfFTUYjIsQbp7tiafm9KH0jwEEC2aSCVg4uE8ugYEh0Sr6V1j\_jW7uO5\_NWWakg)](https://mermaid.live/edit#pako:eNptkT9vwyAQxb-KdVlayZYyuAuVOrVbpmZFqq7mbNMARnCosaJ89-L6z-KwAO\_37p0ObtAMikBAF9D3xelTuiKvL4\_NBTuqqjeFjK871WtPRjuKe0RXT0FbchxnNiVkPabvH2p4rVivE-GkND0AljCm8IiokLpNnsunoNyY9jI6NGPUG\_h3TUNgQBuf5u15odtky-xnT82WOBoqZnvRamPEoW3p5XgsI4fhQuJQ1\_Vyrn614l7U\_golWAoWtcrPfJuSJHBPliSIfFTUYjIsQbp7tiafm9KH0jwEEC2aSCVg4uE8ugYEh0Sr6V1j\_jW7uO5\_NWWakg)

All DICOM tags are acceptable parameters. See this list for available DICOM tags [https://exiftool.org/TagNames/DICOM.html](https://exiftool.org/TagNames/DICOM.html). Variable keys can be either the hexadecimal format (ID) or string format (Name). For example `0018:1030` or `ProtocolName`. The params object contains any number of key/value pairs.

### JSON variables

| _**Variable**_ | **Description**                                       | **Example** |
| -------------- | ----------------------------------------------------- | ----------- |
| _{Tags}_       | A unique key, sometimes derived from the DICOM header | T1w         |

### Directory structure

Files associated with this section are stored in the following directory. `subjectID`, `studyNum`, `seriesNum` are the actual subject ID, study number, and series number. For example `/data/S1234ABC/1/1`.

> `/data/subjectID/studyNum/seriesNum/params.json`
