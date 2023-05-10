---
description: JSON array
---

# analysis

Analysis results, run on an imaging study level. Can contain files, directories, and variables.

<figure><img src="https://mermaid.ink/img/pako:eNqVk81q6zAQhV8lTAk4YAcnuImjQlftplxauN0VQ5lG40St_5BkbnxD3r2SHTlx2kXrhXVG-o5HOkZ7WJecgMFGYrUd_fmbFCPzyLLU3sPz02OrJkFwy1GjZ1-TmxNi5l8rXH_ghjwnLtYrUVEmClJery4I2lUkRU6FVt6ZdpTtaShVv73T2iBOuHVXW0bXXJhOx_EbIidUtTSIE98wXNYb5bXvfrX7oG1htmc7tMPXZSwwa5RQnhM90hqCqQkEJeYqFRl5nXTIeHyC7KEtogaRj8dn-VjsVHbwqR61ExPn67Nvd3AsOo-rLhzuANbgdGdw1cDQH0E3GY367VsmY1dpmvomJVl-UMBRbVFKbNh8aBp0-Y3xIoXfWAdR_MR4tPX_8Ceec-d5eK2PrsPQ75zsKoqiow7-Ca63LKp24ENOMkfBzS3d228loLeUUwLMSE4p1plOICkOBq0rkz3dc6FLCSzFTJEPWOvyuSnWwLSsyUF3As2lz3uqwuKlLAc1sD3sgIU-NMDm4WK6iKNlvIhny3m8iuKDD_9bRzhddU98vZrNFvM4PnwC8QZ2zQ?type=png" alt=""><figcaption></figcaption></figure>

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
|                    path | string   | Relative path to the data within the package                             |
|         _statusMessage_ | string   | Last running status message                                              |
