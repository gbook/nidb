---
description: JSON array
---

# pipelines

Pipelines are the methods used to analyze data after it has been collected. In other words, the experiment provides the methods to collect the data and the pipelines provide the methods to analyze the data once it has been collected.

Basic pipeline information is stored in the main `squirrel.json` file, and complete pipeline information is stored in the pipeline subdirectory in the `pipeline.json` file.

<figure><img src="https://mermaid.ink/img/pako:eNqVk01r4zAQhv9KmBJwwA5OcFNHhT1tL6XswvZWDGU2Gidq_YUk03hD_nslO1LqbA-tDtI70vNqpBE6wKbmBAy2Epvd5OFPVk1Mk3Wtg_vH3796NYuiHxw1Brab3Z4RM__c4OYVtxQ4cbHeiIYKUZEKvLogaN-QFCVVWgUftKNsTkOp9u8LbQzihFt3sWV0y4XJdBo_IUpC1UqDOPEJw2W7VUHf-9VhQ5vCHM9m6If_l7HColNCBU54pDdEc1MQlFiqXBQUDNIh0-kZspe2iBqVfDr9UB-LncMBPseTfmLmfL72_QlOweBx0YXDXcAanB4MLhoZ_BV0V9DEH98yBbvK8zw0VZL1K0Uc1Q6lxI4tx6ZRlu8YL6rwHeuoFF8xnmz-Db_iGTndQ3gjXcdxOFjZVZIkJx29Ca53LGn2EEJJskTBzTc92M0y0DsqKQNmJKcc20JnkFVHg7aNKT7dcaFrCSzHQlEI2Or6sas2wLRsyUE_BZpfX3qqweqprkcxsAPsgcUhdMCW8Wq-SpObdJUubpbpOkmPIfzrHfF8PbT0er1YrJZpenwHeqt3Mg?type=png" alt=""><figcaption></figcaption></figure>

### JSON Variables

\*required

**Basic JSON variables in** `squirrel.json`

|  _**Variable**_ | **Type** | **Description**                                        |
| --------------: | -------- | ------------------------------------------------------ |
|  _\*createDate_ | datetime | Date the pipeline was created                          |
| _\*description_ | string   | Longer description of the pipeline                     |
|       _\*level_ | number   | subject-level analysis (1) or group-level analysis (2) |
|        _\*name_ | string   | Pipeline name, only alphanumeric characters            |

**Extended JSON variables** in `pipelines/pipelinename/pipeline.json`

|          _**Variable**_ | **Type**   | **Description**                                                                                                                  |
| ----------------------: | ---------- | -------------------------------------------------------------------------------------------------------------------------------- |
|           _clusterType_ | string     | Compute cluster engine (sge or slurm)                                                                                            |
|         _completeFiles_ | JSON array | JSON array of complete files, with relative paths to analysisroot                                                                |
|            _createDate_ | datetime   | Date the pipeline was created                                                                                                    |
|        _dataCopyMethod_ | string     |                                                                                                                                  |
|                _depDir_ | string     |                                                                                                                                  |
|              _depLevel_ | string     |                                                                                                                                  |
|           _depLinkType_ | string     |                                                                                                                                  |
|           _description_ | string     | Longer pipeline description                                                                                                      |
|          _dirStructure_ | string     |                                                                                                                                  |
|             _directory_ | string     |                                                                                                                                  |
|                 _group_ | string     | ID or name of a group on which this pipeline will run                                                                            |
|             _groupType_ | string     | Either subject or study                                                                                                          |
|               _\*level_ | number     | subject-level analysis (1) or group-level analysis (2)                                                                           |
|           _maxWallTime_ | number     | Maximum allowed clock (wall) time in minutes for the analysis to run                                                             |
|                _\*name_ | string     | Pipeline name                                                                                                                    |
|                 _notes_ | string     | Extended notes about the pipeline                                                                                                |
| _numConcurrentAnalyses_ | number     | Number of analyses allowed to run at the same time. This number if managed by NiDB and is different than grid engine queue size. |
|          _resultScript_ | string     | Executable script to be run at completion of the analysis to find and insert results back into NiDB                              |
|           _submitDelay_ | number     |                                                                                                                                  |
|            _submitHost_ | string     | Hostname of the SGE or slurm submit node                                                                                         |
|                _tmpDir_ | string     |                                                                                                                                  |
|            _useProfile_ | bool       |                                                                                                                                  |
|             _useTmpDir_ | bool       |                                                                                                                                  |
|               _version_ | number     | Version of the pipeline                                                                                                          |
|              _dataSpec_ | JSON array |                                                                                                                                  |
|         _primaryScript_ | string     | See details of [pipeline scripts](pipeline-scripts.md)                                                                           |
|       _secondaryScript_ | string     | See details of [pipeline scripts](pipeline-scripts.md)                                                                           |

### Directory structure

Files associated with this section are stored in the following directory. `pipelineName` is the unique name of the pipeline.

> `/pipelines/pipelineName`
