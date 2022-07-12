# \_package

[![](https://mermaid.ink/img/pako:eNptUT1rwzAU\_CvmZWnBhgzuokKnduvUrIbyap1tNZIs9EFjQv575cY2lETT6e7ePU46UztKkKDesxuK94\_GFvl8Om6P3ONhBY9V9SI58vN\_PbNOOWhlEW4lnBy8MrAxXLU5IfMhfX2jjevEep2VmKTCHcGAQ\_L3FOlTv9HX8TkoL8YtzZb1FNQm\_LnmEuzZrOzWaOl8cGi3pEljq1h0Smux6zo87fdliH48Quzqul5w9aNkHETtTlSSgTesZH7q85zVUBxg0JDIUKLjpGNDjb1ka3J5Ld6kiqMn0bEOKIlTHA-TbUlEn7CaXhXnnzOL6\_ILthab4A)](https://mermaid.live/edit#pako:eNptUT1rwzAU\_CvmZWnBhgzuokKnduvUrIbyap1tNZIs9EFjQv575cY2lETT6e7ePU46UztKkKDesxuK94\_GFvl8Om6P3ONhBY9V9SI58vN\_PbNOOWhlEW4lnBy8MrAxXLU5IfMhfX2jjevEep2VmKTCHcGAQ\_L3FOlTv9HX8TkoL8YtzZb1FNQm\_LnmEuzZrOzWaOl8cGi3pEljq1h0Smux6zo87fdliH48Quzqul5w9aNkHETtTlSSgTesZH7q85zVUBxg0JDIUKLjpGNDjb1ka3J5Ld6kiqMn0bEOKIlTHA-TbUlEn7CaXhXnnzOL6\_ILthab4A)

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
