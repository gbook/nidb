# \_package

Information about the package. The first letter is an underscore, so the package details appear at the top of the JSON file to make it more readable.

### JSON variables

| _**Variable**_ | **Type**   | **Description**                          | **Example**         | **Required?** |
| -------------: | ---------- | ---------------------------------------- | ------------------- | ------------- |
|       _format_ | string     | Defines the package format               | Squirrel            | Yes           |
|      _version_ | string     | squirrel format version                  | 1.0                 | Yes           |
|  _NiDBVersion_ | string     | The NiDB version which wrote the package | 2022.4.780          |               |
|         _name_ | string     | Short name of the package                | MRI data export     | Yes           |
|  _description_ | string     | Longer description of the package        |                     |               |
|         _date_ | datetime   | Date the package was created             | 2022-04-30 13:34:12 | Yes           |
|     _subjects_ | JSON array |                                          |                     |               |

### Directory structure

Files associated with this section are stored in the following directory

> `/`
