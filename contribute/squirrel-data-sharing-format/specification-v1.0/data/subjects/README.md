---
description: JSON array
---

# subjects

This object is an **array** of subjects, with information about each subject.

<figure><img src="https://mermaid.ink/img/pako:eNqVk01r4zAQhv9KmBJwwA5OcFNHhZ7aS1l2YXtbDGU2Gidq_YUks_GG_PeV7EiJsz20OkjvSM-rkcbWATY1J2CwldjsJt9-ZtXENFnXOnh--fG9V7MoeuCoMbDd7P6MmPnXBjfvuKXAiav1RjRUiIpU4NUVQfuGpCip0iq40I6yOQ2l2t9vtDGIE27dxZbRLRcm02n8gCgJVSsN4sQHDJftVgV971eHDW0KczyboR_-X8YKi04JFTjhkd4QzU1BUGKpclFQMEiHTKdnyF7aImpU8un0oj4WO4cDfI4n_cTM-Xzt-xOcgsHjoiuHu4A1OD0YXDQy-CvorqCJP75lCnaT53loqiTrd4o4qh1KiR1bjk2jLF8xXlXhK9ZRKT5jPNn8N_yM59Lp_jPvo9s4Dgcnu0mS5KSjP4LrHUuaPYRQkixRcPNKD3avDPSOSsqAGckpx7bQGWTV0aBtY2pPT1zoWgLLsVAUAra6fumqDTAtW3LQo0Dz6EtPNVj9qutRDOwAe2BxCB2wZbyar9LkLl2li7tluk7SYwh_e0c8Xw8tvV0vFqtlmh7_AfB4dsw?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

|        _**Variable**_ | **Type**   | **Description (acceptable values)**                                                                                   |
| --------------------: | ---------- | --------------------------------------------------------------------------------------------------------------------- |
|            _**\*ID**_ | string     | Unique ID of this subject. It must be unique within the package, ie no other subjects in the package have the same ID |
|        _alternateIDs_ | JSON array | List of alternate IDs                                                                                                 |
|                _GUID_ | string     | Globally unique identifier, from NDA                                                                                  |
|         _dateOfBirth_ | date       | Subject’s date of birth                                                                                               |
|           _**\*sex**_ | char       | Sex at birth (F,M,O,U)                                                                                                |
|              _gender_ | char       | Self-identified gender                                                                                                |
|          _ethnicity1_ | string     | Usually Hispanic/non-hispanic                                                                                         |
|          _ethnicity2_ | string     | NIH defined race                                                                                                      |
|           virtualPath | string     | relative path to the data within the package                                                                          |
| [_studies_](studies/) | JSON array |                                                                                                                       |

### Directory structure

Files associated with this section are stored in the following directory

> `/data/subjectID`
