---
description: JSON object
---

# package

This object contains information about the squirrel package.

<figure><img src="https://mermaid.ink/img/pako:eNqVlEFrgzAUx7-KpAgKOspwFwc9bZcxNlhvw8uredasaiSJW6X0uy9RY6vtofVg3j_5_fOS99ADSTlFEpOtgDp33r-SytGP4Fx5b-vPjy7yw3BFQYFnXv7zCdHzNaQ72KI3jPNVVmPBKpTeGM0I3NcoWImVkt5ZPKNM4pCyVDFegWi9mfZ7uJsNV1vBmxoqKFqpE3fKsdLuO6Cy2fxgqlPbwK5bbRjVUKY3GsYrBN9IFL9gDiO9c3GFZZXSy_qKHTxRI90nMql1OUzmbrhc7i_FpGcDi7hubwkfTIMElDJjhemRCS10iZqiGFBOGu26Z30x2En28Ek73YRvfWPPu3MMovdYNXPYixiDjXuDVRPDeAXVFuiMxzdMES-yLAt0tQTfYUhB5iAEtPHj1DTJco9xVoV7rJNS3GKc2ceO3uK1nu7zHA34tFwGvSVeRFE0xOEfoyqPo3pPAlKiKIFR_XM4mK0SonIsMSGxDilm0BQqIUl11GhT6-LjK2WKCxJnUEgMCDSKr9sqJbESDVrohYH-15Qjpb_Ub86tPv4DUcmRwA?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

:blue\_circle: Primary key\
:red\_circle: Required

{% include "../../../.gitbook/includes/package.md" %}

### Variable options

#### subjectDirFormat, studyDirFormat, seriesDirFormat

* `orig` - Original subject, study, series directory structure format. Example `S1234ABC/1/1`
* `seq` - Sequential. Zero-padded sequential numbers. Example `00001/0001/00001`

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

#### Notes

Notes about the package are stored here. This includes import and export logs, and notes from imported files. This is generally a freeform object, but notes can be divided into sections.

<table><thead><tr><th width="163">Section</th><th>Description</th></tr></thead><tbody><tr><td><code>import</code></td><td>Any notes related to import. BIDS files such as README and CHANGES are stored here.</td></tr><tr><td><code>merge</code></td><td>Any notes related to the merging of datasets. Such as information about renumbering of subject IDs</td></tr><tr><td><code>export</code></td><td>Any notes related to the export process</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory

> `/`
