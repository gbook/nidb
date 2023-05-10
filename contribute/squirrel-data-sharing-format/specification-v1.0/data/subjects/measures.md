---
description: JSON array
---

# measures

Measures are observations collected from a participant in response to an experiment.

<figure><img src="https://mermaid.ink/img/pako:eNqVk01r4zAQhv9KmBJwwA5OcFNHhZ7aS1l2YXtbDGU2Gidq_YUks_GG_PeV7EiJsz20OkjvSM-rkcbWATY1J2CwldjsJt9-ZtXENFnXOnh--fG9V7MoeuCoMbDd7P6MmPnXBjfvuKXAiav1RjRUiIpU4NUVQfuGpCip0iq40I6yOQ2l2t9vtDGIE27dxZbRLRcm02n8gCgJVSsN4sQHDJftVgV971eHDW0KczyboR_-X8YKi04JFTjhkd4QzU1BUGKpclFQMEiHTKdnyF7aImpU8un0oj4WO4cDfI4n_cTM-Xzt-xOcgsHjoiuHu4A1OD0YXDQy-CvorqCJP75lCnaT53loqiTrd4o4qh1KiR1bjk2jLF8xXlXhK9ZRKT5jPNn8N_yM59Lp_jvvo9s4Dgcnu0mS5KSjP4LrHUuaPYRQkixRcPNKD3avDPSOSsqAGckpx7bQGWTV0aBtY2pPT1zoWgLLsVAUAra6fumqDTAtW3LQo0Dz6EtPNVj9qutRDOwAe2BxCB2wZbyar9LkLl2li7tluk7SYwh_e0c8Xw8tvV0vFqtlmh7_AfIQds4?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

|      _**Variable**_ | **Type** | **Description**                                     |
| ------------------: | -------- | --------------------------------------------------- |
| _\***measureName**_ | string   | Name of the measure                                 |
|   _\***dateStart**_ | datetime | Start date/time of the measurement                  |
|           _dateEnd_ | datetime | End date/time of the measurement                    |
|    _instrumentName_ | string   | Name of the instrument associated with this measure |
|             _rater_ | string   | Name of the rater                                   |
|             _notes_ | string   |                                                     |
|       _\***value**_ | string   | Value (string or number)                            |
|       _description_ | string   | Longer description of the measure                   |

