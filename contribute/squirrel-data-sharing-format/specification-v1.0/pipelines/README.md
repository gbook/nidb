---
description: JSON array
---

# pipelines

Pipelines are the methods used to analyze data after it has been collected. In other words, the experiment provides the methods to collect the data and the pipelines provide the methods to analyze the data once it has been collected.

Basic pipeline information is stored in the main `squirrel.json` file, and complete pipeline information is stored in the pipeline subdirectory in the `pipeline.json` file.

![](https://mermaid.ink/img/pako:eNptkj1rwzAQhv-KURYFHMjgLip0apdSWmhWQ7laZ0eNZAt90ISQ\_96Ta9kljQfdY98jvebsM2sGiUywzoHdFy\_vdV\_Q5YYh8Ofd2-tI683mQUIAnpb1\_aLQ8w8LzQE65Bmu-lZZ1KpHz2e6MvBo0SmDffD8D2crZZLl4-cXNqRkyP18n5wQpaKkqd4wDIKPjpQMNxzpYuf5uM7d3wNTBL1eShjL\_zb0oE9eeZ5hVsYNaR7gwNAwxpK782imQe8sNjzDknLSuKhFq7QWq7bFu-229MENBxSrqqom3nwrGfaiskdWMoPOgJL0nc\_psJqFPRqsmSCU2ELUoWZ1fyE1WgrGJ6nC4JhoQXssGcQw7E59w0RwEbP0qIB-GzNZlx8c0sgX)

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
|         _completeFiles_ | string     | JSON array of complete files, with relative paths to analysisroot                                                                |
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
|         _primaryScript_ | string     | See details of pipeline scripts                                                                                                  |
|       _secondaryScript_ | string     | See details of pipeline scripts                                                                                                  |

### Directory structure

Files associated with this section are stored in the following directory. `pipelineName` is the unique name of the pipeline.

> `/pipelines/pipelineName`
