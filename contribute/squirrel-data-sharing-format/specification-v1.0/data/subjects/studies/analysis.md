# analysis

Analysis results, run on an imaging study level. Can contain files, directories, and variables.

JSON variables



|     _**Variable**_ | Type   | Description                                                              | Example             | Required? |
| -----------------: | ------ | ------------------------------------------------------------------------ | ------------------- | --------- |
|     _pipelineName_ | string | Name of the pipeline used to generate these results                      | MyPipeline          | Yes       |
| _clusterStartDate_ | date   | Datetime the job began running on the cluster                            | 2022-04-23 16:23:44 |           |
|   _clusterEndDate_ | date   | Datetime the job finished running on the cluster                         | 2022-04-23 16:23:44 |           |
|  _pipelineVersion_ | number | Version of the pipeline used                                             | 3                   | Yes       |
|        _startDate_ | date   | Datetime of the start of the analysis                                    | 2022-04-23 16:23:44 | Yes       |
|          _endDate_ | date   | Datetime of the end of the analysis                                      | 2022-04-24 04:41:34 |           |
|        _setupTime_ | number | Wall time, in seconds, to copy data and set up analysis                  | 24                  |           |
|          _runtime_ | number | Wall time, in seconds, to run the analysis after setup                   | 4756                |           |
|        _numSeries_ | number | Number of series downloaded/used to perform analysis                     | 2                   |           |
|           _status_ | string | Status of the analysis: complete, error, etc                             | complete            |           |
|       _successful_ | number | Analysis ran to completion without error and expected files were created | 1                   |           |
|             _size_ | number | Size in bytes of the analysis                                            | 52730923            |           |
|         _hostname_ | string | If run on a cluster, the hostname of the node on which the analysis run  | compute01           |           |
|           _status_ | string | Status, should always be ‘complete’                                      | complete            |           |
|    _statusMessage_ | string | Last running status message                                              |                     |           |
