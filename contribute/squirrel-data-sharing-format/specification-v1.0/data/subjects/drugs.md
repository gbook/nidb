---
description: JSON array
---

# drugs

‘Drugs’ represents any substances administered to a participant, either through a clinical trial or the participant’s use of prescription or recreational drugs.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptkj1rwzAQhv-KURYFHMjgLip0apdSWqhXQ7la50SNZAt90JiQ\_96TazkljQfdY99jveLsE2sHiUywnQO7L17em76gyw1D4M\_12-tE683mQUIAnpb1\_UWh5x8W2gPskGe46ltlUasePV\_oysCjRacM9sHzP5ytlEmWj59f2JKSIffzfXJClIqS5nrDMAg-OlIy3HCkizvPp3Xp\_m6YIuh4KWEq\_9vQgx698jzDokwvpHmAA0PDmEruLqOZB11bbHmGS8qosZjOVXRKa7HqOrzbbksf3HBAsaqqaubNt5JhLyp7ZCUz6AwoSd\_4lDZqWNijwYYJQokdRB0a1vRnUqOlUHySKgyOiQ60x5JBDEM99i0TwUXM0qMC-mXMbJ1\_ANa1xnM)

### JSON variables

<mark style="color:red;">\*required</mark>

|        _**Variable**_ | **Type** | **Description**                                                    |
| --------------------: | -------- | ------------------------------------------------------------------ |
|      _\***drugName**_ | string   | Name of the drug                                                   |
|     _\***dateStart**_ | datetime | Date the drug was started                                          |
|             _dateEnd_ | datetime | Date the drug was stopped                                          |
|    _\***doseAmount**_ | number   | In combination with other dose variables, the quantity of the drug |
| _\***doseFrequency**_ | string   |                                                                    |
|         _\***route**_ | string   | Drug entry route (oral, IV, unknown, etc)                          |
|                _type_ | string   |                                                                    |
|             _doseKey_ | string   | For clinical trials, the dose key                                  |
|            _doseUnit_ | string   | mg, g, ml, tablets, capsules, etc                                  |
|   _frequencyModifier_ | string   | (every, times)                                                     |
|      _frequencyValue_ | number   | Number                                                             |
|       _frequencyUnit_ | string   | (bolus, dose, second, minute, hour, day, week, month, year)        |
|         _description_ | string   | Longer description                                                 |
|               _rater_ | string   | Rater/experimenter                                                 |
|               _notes_ | string   |                                                                    |
|           _dateEntry_ | string   |                                                                    |
