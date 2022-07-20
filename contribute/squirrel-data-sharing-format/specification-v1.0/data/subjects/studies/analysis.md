---
description: JSON array
---

# analysis

Analysis results, run on an imaging study level. Can contain files, directories, and variables.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptks1qwzAQhF\_FKBcFHMjBvajQU3sppYX6aihba52okWyhHxoT8u5duZZT0vigHXk-7Yi1T6wdJDLBdg7svnh5b\_qCHjcMgT\_Xb6-TWm82DxIC8LSs7y8Ivf-w0B5ghzyLK98qi1r16Pmirgg8WnTKYB88\_6MzlTKJ8vHzC1tCssh-3icmRKkoaa43CIPgoyMkixuMdHHn-bQu7m\_DFEHXSwlT-W9DD3r0yvMsFmQ6kOYBDgwNYyrZXUYzD7q22PIsLimjxiI3LjqltVh1Hd5tt6UPbjigWFVVNevNt5JhLyp7ZCUz6AwoSZ\_5lHo1LOzRYMMESYkdRB0a1vRnQqOlXHySKgyOiQ60x5JBDEM99i0TwUXM0KMC-mvMTJ1\_AEy4x7I)

### JSON variables

<mark style="color:red;">\* required</mark>

|          _**Variable**_ | **Type** | **Description**                                                          |
| ----------------------: | -------- | ------------------------------------------------------------------------ |
|    _\***pipelineName**_ | string   | Name of the pipeline used to generate these results                      |
|      _clusterStartDate_ | date     | Datetime the job began running on the cluster                            |
|        _clusterEndDate_ | date     | Datetime the job finished running on the cluster                         |
| _\***pipelineVersion**_ | number   | Version of the pipeline used                                             |
|       _\***startDate**_ | date     | Datetime of the start of the analysis                                    |
|               _endDate_ | date     | Datetime of the end of the analysis                                      |
|             _setupTime_ | number   | Wall time, in seconds, to copy data and set up analysis                  |
|               _runtime_ | number   | Wall time, in seconds, to run the analysis after setup                   |
|             _numSeries_ | number   | Number of series downloaded/used to perform analysis                     |
|                _status_ | string   | Status of the analysis: complete, error, etc                             |
|            _successful_ | number   | Analysis ran to completion without error and expected files were created |
|                  _size_ | number   | Size in bytes of the analysis                                            |
|              _hostname_ | string   | If run on a cluster, the hostname of the node on which the analysis run  |
|                _status_ | string   | Status, should always be ‘complete’                                      |
|         _statusMessage_ | string   | Last running status message                                              |
