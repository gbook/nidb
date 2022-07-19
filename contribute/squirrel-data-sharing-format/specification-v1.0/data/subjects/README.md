# subjects

An array of subjects, with information about each subject

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
