---
description: JSON array
---

# subjects

This object is an **array** of subjects, with information about each subject.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptkj1rwzAQhv-KURYFbMjgLip0apdSWmhWQ7laZ0eNZAt90ISQ\_96TazkljQfdY99jveLsE2tHiUyw3oHdFS\_vzVDQ5cYx8Oft2-tE66p6kBCAp2V9f1Ho-YeFdg898gxXfassajWg5wtdGXiw6JTBIXj-h7OVMsny8fMLW1Iy5H6-T06IUlHSXG8YBsFHR0qGG450sfd8Wpfu74Ypgo6XEqbyvw0D6KNXnmdYlOmFNA9wYGgYU8ndZTTzoLcWW57hknLUuBy06JTWYtV1eLfZlD64cY9iVdf1zNW3kmEnantgJTPoDChJn\_mU9mpY2KHBhglCiR1EHRrWDGdSo6VcfJIqjI6JDrTHkkEM4\_Y4tEwEFzFLjwrorzGzdf4BTHnHsQ)

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
