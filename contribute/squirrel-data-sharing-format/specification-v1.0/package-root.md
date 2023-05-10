---
description: JSON object
---

# Package root

The package root contains all data and files for the package. The JSON root contains all JSON objects for the package.

<figure><img src="https://mermaid.ink/img/pako:eNqVk01r4zAQhv9KmBJwwA5OcFNHhZ7aS1l2YXtbDGU2Gidq_YUks_GG_PeV7EiJsz20OkjvSM-rkcbWATY1J2CwldjsJt9-ZtXENFnXOnh--fG9V7MoeuCoMbDd7P6MmPnXBjfvuKXAiav1RjRUiIpU4NUVQfuGpCip0iq40I6yOQ2l2t9vtDGIE27dxZbRLRcm02n8gCgJVSsN4sQHDJftVgV971eHDW0KczyboR_-X8YKi04JFTjhkd4QzU1BUGKpclFQMEiHTKdnyF7aImpU8un0oj4WO4cDfI4n_cTM-Xzt-xOcgsHjoiuHu4A1OD0YXDQy-CvorqCJP75lCnaT53loqiTrd4o4qh1KiR1bjk2jLF8xXlXhK9ZRKT5jPNn8N_yM59Jp_3nvods4DgcXu0mS5KSjP4LrHUuaPYRQkixRcPNCD3afDPSOSsqAGckpx7bQGWTV0aBtY-pOT1zoWgLLsVAUAra6fumqDTAtW3LQo0Dz4EtPNVj9qutRDOwAe2BxCB2wZbyar9LkLl2li7tluk7SYwh_e0c8Xw8tvV0vFqtlmh7_AcV5dS0?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

|    _**Variable**_ | **Type**    | **Description**                               |
| ----------------: | ----------- | --------------------------------------------- |
| _**\*\_package**_ | JSON object | Package information                           |
|            _data_ | JSON object | Raw and analyzed data                         |
|         pipelines | JSON object | Methods used to analyze the data              |
|       experiments | JSON object | Experimental methods used to collect the data |

### Directory structure

Files associated with this object are stored in the following directory.

> `/`
