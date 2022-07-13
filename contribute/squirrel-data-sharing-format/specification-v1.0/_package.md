---
description: JSON object
---

# \_package

This object contains information about the package. The first letter is an underscore so that the package details appear at the top of the JSON file. All other objects are listed in alphabetical order in the text file, which follows the JSON standard.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptks1qwzAQhF\_FKBcFHMjBvajQU3sppYX6aihba52okWyhHxoT8u5duZZT0vigHXs-aczYJ9YOEplgOwd2X7y8N31BlxuGwJ\_rt9dJrTebBwkBeFrW9xeEnn9YaA-wQ57FlW-VRa169HxRVwQeLTplsA-e\_9GZSplE-fj5hS0hWWQ\_3ycmRKkoaZ43CIPgoyMkixuMdHHn-bQu7u-BKYJeLyVM478NPejRK8-zWJBpQ-oDHBgqYxrZXaqZi64ttjyLS8qoscg1F53SWqy6Du-229IHNxxQrKqqmvXmW8mwF5U9spIZdAaUpM98Smc1LOzRYMMESYkdRB0a1vRnQqOlXHySKgyOiQ60x5JBDEM99i0TwUXM0KMC-mvMTJ1\_ACuHx3k)

### JSON variables

<mark style="color:red;">\*required</mark>

| _**Variable**_ | **Type**   | **Description**                          |
| -------------: | ---------- | ---------------------------------------- |
|     _\*format_ | string     | Defines the package format               |
|    _\*version_ | string     | squirrel format version                  |
|  _NiDBVersion_ | string     | The NiDB version which wrote the package |
|       _\*name_ | string     | Short name of the package                |
|  _description_ | string     | Longer description of the package        |
|       _\*date_ | datetime   | Date the package was created             |
|     _subjects_ | JSON array |                                          |

### Directory structure

Files associated with this section are stored in the following directory

> `/`
