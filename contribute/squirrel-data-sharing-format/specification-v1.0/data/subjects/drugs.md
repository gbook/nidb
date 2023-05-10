---
description: JSON array
---

# drugs

‘Drugs’ represents any substances administered to a participant; through a clinical trial or the participant’s use of prescription or recreational drugs. Detailed variables are available to record exactly how much and when a drug is administered. This allows searching by dose amount, or other variable.

<figure><img src="https://mermaid.ink/img/pako:eNqVk01r4zAQhv9KmBJwwA5OcFNHhZ7aS1l2YXtbDGU2Gidq_YUks_GG_PeV7EiJsz20OkjvSM-rkcbWATY1J2CwldjsJt9-ZtXENFnXOnh--fG9V7MoeuCoMbDd7P6MmPnXBjfvuKXAiav1RjRUiIpU4NUVQfuGpCip0iq40I6yOQ2l2t9vtDGIE27dxZbRLRcm02n8gCgJVSsN4sQHDJftVgV971eHDW0KczyboR_-X8YKi04JFTjhkd4QzU1BUGKpclFQMEiHTKdnyF7aImpU8un0oj4WO4cDfI4n_cTM-Xzt-xOcgsHjoiuHu4A1OD0YXDQy-CvorqCJP75lCnaT53loqiTrd4o4qh1KiR1bjk2jLF8xXlXhK9ZRKT5jPNn8N_yM59LZ_1beRLdxHA42dpMkyUlHfwTXO5Y0ewihJFmi4OaJHuxGGegdlZQBM5JTjm2hM8iqo0HbxhSenrjQtQSWY6EoBGx1_dJVG2BatuSgR4HmxZeearD6VdejGNgB9sDiEDpgy3g1X6XJXbpKF3fLdJ2kxxD-9o54vh5aerteLFbLND3-A0mQdY4?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

|        _**Variable**_ | **Type** | **Description**                                                    |
| --------------------: | -------- | ------------------------------------------------------------------ |
|      _\***drugName**_ | string   | Name of the drug                                                   |
|     _\***dateStart**_ | datetime | Date the drug was started                                          |
|             _dateEnd_ | datetime | Date the drug was stopped                                          |
|    _\***doseAmount**_ | number   | In combination with other dose variables, the quantity of the drug |
| _\***doseFrequency**_ | string   | Description of the frequency of administration                     |
| _administrationRoute_ | string   | Drug entry route (oral, IV, unknown, etc)                          |
|           _drugClass_ | string   | Drug class                                                         |
|             _doseKey_ | string   | For clinical trials, the dose key                                  |
|            _doseUnit_ | string   | mg, g, ml, tablets, capsules, etc                                  |
|   _frequencyModifier_ | string   | (every, times)                                                     |
|      _frequencyValue_ | number   | Number                                                             |
|       _frequencyUnit_ | string   | (bolus, dose, second, minute, hour, day, week, month, year)        |
|         _description_ | string   | Longer description                                                 |
|               _rater_ | string   | Rater/experimenter                                                 |
|               _notes_ | string   |                                                                    |
|           _dateEntry_ | string   |  date for the data-entry                                           |

### Recording drug administration

The following examples convert between common language and the squirrel storage format

> esomeprazole 20mg capsule by mouth daily

| Variable          | Value        |
| ----------------- | ------------ |
| drugClass         | PPI          |
| drugName          | esomeprazole |
| doseAmount        | 20mg         |
| doseFrequency     | daily        |
| route             | oral         |
| doseUnit          | mg           |
| frequencyModifier | every        |
| frequencyValue    | 1            |
| frequencyUnit     | day          |

> 2 puffs atrovent inhaler every 6 hours

| Variable            | Value          |
| ------------------- | -------------- |
| drugName            | ipratropium    |
| drugClass           | bronchodilator |
| doseAmount          | 2              |
| doseFrequency       | every 6 hours  |
| administrationRoute | inhaled        |
| doseUnit            | puffs          |
| frequencyModifier   | every          |
| frequencyValue      | 6              |
| frequencyUnit       | hours          |

