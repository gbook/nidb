---
description: JSON array
---

# dataSpec

dataSpec describes the criteria to find data if searching a database (NiDB for example, since this pipeline is usually connected to a database). The dataSpec is a JSON array of the following variables.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptkj1rwzAQhv-KURYFHMjgLip0apdSWqhXQ7la50SNZAt90JiQ\_96TazkljQfdY91jvebsE2sHiUywnQO7L17em76gyw1D4M\_12-tE683mQUIAnpb1\_UWh\_Q8L7QF2yDNc9a2yqFWPni90ZeDRolMG--D5H85WyiTLx88vbEnJkPv5PjkhSkVJc71hGAQfHSkZbjjSxZ3n07p0fw9MEfR6KWEq\_9vQgx698jzDokwPpHmAA0PDmEruLqOZB11bbHmGS8qosci7Rae0Fquuw7vttvTBDQcUq6qqZt58Kxn2orJHVjKDzoCS9JlP6ayGhT0abJgglNhB1KFhTX8mNVpKwCepwuCY6EB7LBnEMNRj3zIRXMQsPSqgv8bM1vkHKAnHcw)

### JSON variables

<mark style="color:red;">\*required</mark>

|      _**Variable**_ | **Type** | **Description**                                                                                                              |
| ------------------: | -------- | ---------------------------------------------------------------------------------------------------------------------------- |
| _\*associationType_ | string   | study, or subject                                                                                                            |
|            _behDir_ | string   | if behFormat writes data to a sub directory, the directory should be named this                                              |
|         _behFormat_ | string   | nobeh, behroot, behseries, behseriesdir                                                                                      |
|      _\*dataFormat_ | string   | native, dicom, nift3d, nift4d, analyze3d, analyze4d, bids                                                                    |
|           _enabled_ | bool     | Whether the step is enabled or not                                                                                           |
|              _gzip_ | bool     | Whether to gzip data if converted to Nifti                                                                                   |
|         _imageType_ | string   | Comma separated list of image types, often derived from the DICOM ImageType tag, (0008:0008)                                 |
|       _\*datalevel_ | string   | nearestintime, samestudy                                                                                                     |
|          _location_ | string   | Directory, relative to the analysisroot, where this data will be written                                                     |
|        _\*modality_ | string   | Modality to search for                                                                                                       |
|       _numBOLDreps_ | string   | If seriesCriteria is set to usecriteria, then search based on this option                                                    |
| _numImagesCriteria_ | string   |                                                                                                                              |
|        _\*optional_ | bool     | Whether this step is optional or not. If not optional, the analysis will not run if the data step is not found               |
|           _\*order_ | number   | The numerical order of this particular step                                                                                  |
|    _preserveSeries_ | bool     | Whether to preserve series numbers or to assign new ordinal numbers                                                          |
|   _primaryProtocol_ | bool     | This data step determines the primary study, from which subsequent analyses are run                                          |
|          _protocol_ | string   | Protocol name(s)                                                                                                             |
|    _seriesCriteria_ | string   | Criteria for which series are downloaded if more than one matches criteria: all, first, last, largest, smallest, usecriteria |
|       _usePhaseDir_ | bool     | Write data to a sub directory based on the phase encoding direction                                                          |
|         _useSeries_ | bool     | Write each series to an individually numbered directory                                                                      |

### Directory structure

Files associated with this section are stored in the following directory. Where `pipelineName` is the unique name of the pipeline.

> `/pipelines/pipelineName`
