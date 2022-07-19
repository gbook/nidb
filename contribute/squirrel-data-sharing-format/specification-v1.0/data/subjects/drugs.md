# drugs

‘Drugs’ represents any substances administered to a participant, either through a clinical trial or the participant’s use of prescription or recreational drugs.

### JSON variables

<mark style="color:red;">\*required</mark>

|        _**Variable**_ | **Type**   | **Description**                                                    | **Example** |
| --------------------: | ---------- | ------------------------------------------------------------------ | ----------- |
|      _\***drugName**_ | string     | Name of the drug                                                   | Aleve       |
|     _\***dateStart**_ | datetime   | Date the drug was started                                          |             |
|             _dateEnd_ | datetime   | Date the drug was stopped                                          |             |
|    _\***doseAmount**_ | number     | In combination with other dose variables, the quantity of the drug |             |
| _\***doseFrequency**_ | string     |                                                                    |             |
|         _\***route**_ | string     | Drug entry route (oral, IV, unknown, etc)                          | oral        |
|                _type_ | string     |                                                                    |             |
|             _doseKey_ | string     | For clinical trials, the dose key                                  | CA213       |
|            _doseUnit_ | string     | mg, g, ml, tablets, capsules, etc                                  |             |
|   _frequencyModifier_ | string     | (every, times)                                                     | every       |
|      _frequencyValue_ | number     | Number                                                             | 2           |
|       _frequencyUnit_ | string     | (bolus, dose, second, minute, hour, day, week, month, year)        | day         |
|         _description_ | string     | Longer description                                                 |             |
|               _rater_ | string     | Rater/experimenter                                                 |             |
|               _notes_ | string     |                                                                    |             |
|           _dateEntry_ | string     |                                                                    |             |
|          _experiment_ | JSON array |                                                                    |             |
