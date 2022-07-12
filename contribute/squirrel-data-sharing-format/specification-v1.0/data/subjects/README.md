# subjects

An array of subjects, with information about each subject

[![](https://mermaid.ink/img/pako:eNptUbFuwyAU\_BWLLK1kSxnchUqd2i1Ts1qqXs3ZpgGM4KEmivLvxY3toS7TcXfv3ntwFe2oIKToA\_mhOLw3rsjnw1N7oh5V9aKI6XnDeu1htEPcSjh7BG3hON61KSHzMX1-oeWlYrk-LOBx8nBSGn8tWbCgmMJ\_igqpX-l7-RSUR8CWJkfmEvUq\_LqmdSiQXdh1t3n7o0e7Jl0M1u5Fp42Ru67D035fRg7jCXJX1\_WMq2-teJC1P4tSWARLWuWnvk5ZjeABFo2QGSp0lAw3onG3bE0-t8Wb0jwGITsyEaWgxOPx4lohOSQspldN-efs7Lr9ABBBnFA)](https://mermaid.live/edit#pako:eNptUbFuwyAU\_BWLLK1kSxnchUqd2i1Ts1qqXs3ZpgGM4KEmivLvxY3toS7TcXfv3ntwFe2oIKToA\_mhOLw3rsjnw1N7oh5V9aKI6XnDeu1htEPcSjh7BG3hON61KSHzMX1-oeWlYrk-LOBx8nBSGn8tWbCgmMJ\_igqpX-l7-RSUR8CWJkfmEvUq\_LqmdSiQXdh1t3n7o0e7Jl0M1u5Fp42Ru67D035fRg7jCXJX1\_WMq2-teJC1P4tSWARLWuWnvk5ZjeABFo2QGSp0lAw3onG3bE0-t8Wb0jwGITsyEaWgxOPx4lohOSQspldN-efs7Lr9ABBBnFA)

### JSON variables

| _**Variable**_ | **Type**   | **Description (acceptable values)**                                                                                   | **Example**          | **Required?** |
| -------------- | ---------- | --------------------------------------------------------------------------------------------------------------------- | -------------------- | ------------- |
| _ID_           | string     | Unique ID of this subject. It must be unique within the package, ie no other subjects in the package have the same ID | S1234ABC             | Yes           |
| _alternateIDs_ | JSON array | List of alternate IDs                                                                                                 | “altuid1”, “altuid2” |               |
| _dateOfBirth_  | date       | Subject’s date of birth                                                                                               | 1990-01-01           |               |
| _sex_          | char       | Sex at birth (F,M,O,U)                                                                                                | M                    | Yes           |
| _gender_       | char       | Self-identified gender                                                                                                | F                    |               |
| _ethnicity1_   | string     | Usually Hispanic/non-hispanic                                                                                         | Hispanic             |               |
| _ethnicity2_   | string     | NIH defined race                                                                                                      | Caucasian            |               |
| _numStudies_   | number     | The number of imaging studies for this subject                                                                        | 3                    |               |
| _studies_      | JSON array |                                                                                                                       |                      |               |

### Directory structure

Files associated with this section are stored in the following directory

> `/data/subjectID`
