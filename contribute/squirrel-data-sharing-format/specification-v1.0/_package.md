---
description: JSON object
---

# \_package

This object contains information about the package. The first letter is an underscore so that the package details appear at the top of the JSON file. All other objects are listed in alphabetical order in the text file, which follows the JSON standard.

![JSON object hierarchy](https://mermaid.ink/img/pako:eNptks1qwzAQhF\_FKBcFHMjBvajQU3sppYX6aihba52okWyhHxoT8u5duZZT0vigHXs-aczYJ9YOEplgOwd2X7y8N31BlxuGwJ\_rt9dJrTebBwkBeFrW9xeEnn9YaA-wQ57FlW-VRa169HxRVwQeLTplsA-e\_9GZSplE-fj5hS0hWWQ\_3ycmRKkoaZ43CIPgoyMkixuMdHHn-bQu7u-BKYJeLyVM478NPejRK8-zWJBpQ-oDHBgqYxrZXaqZi64ttjyLS8qoscg1F53SWqy6Du-229IHNxxQrKqqmvXmW8mwF5U9spIZdAaUpM98Smc1LOzRYMMESYkdRB0a1vRnQqOlXHySKgyOiQ60x5JBDEM99i0TwUXM0KMC-mvMTJ1\_ACuHx3k)

### JSON variables

<mark style="color:red;">\*required</mark>

| _**Variable**_ | **Type**   | **Description**                                                                      |
| -------------: | ---------- | ------------------------------------------------------------------------------------ |
|     _\*format_ | string     | Defines the package format                                                           |
|    _\*version_ | string     | squirrel format version                                                              |
|  _NiDBVersion_ | string     | The NiDB version which wrote the package                                             |
|       _\*name_ | string     | Short name of the package                                                            |
|  _description_ | string     | Longer description of the package                                                    |
|       _\*date_ | datetime   | Date the package was created                                                         |
|     _subjects_ | JSON array |                                                                                      |
|      dirFormat | string     | orig, seq (**see details below**)                                                    |
|     dataFormat | string     | orig, anon, anonfull, nifti3d, nifti3dgz, nifti4d, nifti4dgz (**see details below**) |

### dirFormat

* `orig` - Original subject, study, series directory structure format. Example `S1234ABC/1/1`
* `seq` - Sequential. Zero-padded sequential numbers. Example `1/1/1`

### dataFormat

* `orig` - Original, raw data format. If the original format was DICOM, the output format should be DICOM. See [DICOM anonymization levels](../../../specifications/dicom-anonymization.md) for details.
* `anon` - If original format is DICOM, write anonymized DICOM, removing most PHI, except dates. See [DICOM anonymization levels](../../../specifications/dicom-anonymization.md) for details.
* `anonfull` - If original format is DICOM, the files will be fully anonymized, by removing dates, times, locations in addition to PHI. See [DICOM anonymization levels](../../../specifications/dicom-anonymization.md) for details.
* `nifti3d` - Nifti 3D format
  * Example `file001.nii`, `file002.nii`, `file003.nii`
* `nifti3dgz` - gzipped Nifti 3D format
  * Example `file001.nii.gz`, `file002.nii.gz`, `file003.nii.gz`
* `nifti4d` - Nifti 4D format
  * Example `file.nii`
* `nifti4dgz` - gzipped Nifti 4D format
  * Example `file.nii.gz`

### Directory structure

Files associated with this section are stored in the following directory

> `/`
