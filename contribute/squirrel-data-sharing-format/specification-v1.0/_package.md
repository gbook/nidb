---
description: JSON object
---

# \_package

This object contains information about the package. The first letter is an underscore so that the package details appear at the top of the JSON file. All other objects are listed in alphabetical order in the text file, which follows the JSON standard.

<figure><img src="https://mermaid.ink/img/pako:eNqVk01r4zAQhv9KmBJwwA5OcFNHhZ7aS1l2YXtbDGU2Gidq_YUks_GG_PeV7EiJsz20PkjvaJ5XI43RATY1J2CwldjsJt9-ZtXEfLKudfD88uN7r2ZR9MBRY2CH2f0ZMeuvDW7ecUuBE1f5RjRUiIpU4NUVQfuGpCip0iq40I6yNQ2l2t9vtDGIEy7vYsvolgtT6TR_QJSEqpUGceIDhst2q4J-9NlhQ1vCHM9W6Kf_01hh0SmhAic80huiuWkISixVLgoKBumQ6fQM2UtbRI1aPp1e9Mdi53CAz_GkX5g5n-99f4JTMHhcdOVwF7AGpweDi0YGfwXdFTTxx7dMwW7yPA9Nl2T9ThFHtUMpsWPLsWlU5SvGqy58xTpqxWeMJ5v_h5_xXDrdK_E-uo3jcHCymyRJTjr6I7jesaTZQwglyRIFN6_0YPfKQO-opAyYkZxybAudQVYdDdo2pvf0xIWuJbAcC0UhYKvrl67aANOyJQc9CjSPvvRUg9Wvuh7FwA6wBxaH0AFbxqv5Kk3u0lW6uFum6yQ9hvC3d8Tz9fClt-vFYrVM0-M_vj52lA?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

|  _**Variable**_ | **Type**   | **Description**                                                                      |
| --------------: | ---------- | ------------------------------------------------------------------------------------ |
|  _**\*format**_ | string     | Defines the package format                                                           |
| _**\*version**_ | string     | squirrel format version                                                              |
|   _NiDBVersion_ | string     | The NiDB version which wrote the package                                             |
|    _**\*name**_ | string     | Short name of the package                                                            |
|   _description_ | string     | Longer description of the package                                                    |
|    _**\*date**_ | datetime   | Date the package was created                                                         |
|      _subjects_ | JSON array |                                                                                      |
|       dirFormat | string     | orig, seq (**see details below**)                                                    |
|      dataFormat | string     | orig, anon, anonfull, nifti3d, nifti3dgz, nifti4d, nifti4dgz (**see details below**) |

### Variable options

#### dirFormat

* `orig` - Original subject, study, series directory structure format. Example `S1234ABC/1/1`
* `seq` - Sequential. Zero-padded sequential numbers. Example `1/1/1`

#### dataFormat

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
