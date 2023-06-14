---
description: JSON object
---

# package

This object contains information about the package. The first letter is an underscore so that the package details appear at the top of the JSON file. All other objects are listed in alphabetical order in the text file, which follows the JSON standard.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8bHMGZWcAsLoIEh3XHz7WbQL8wjOVZI8daR8JQeIxnH1eJ2Nvr78-O7UyoCUKBLZ1y1iE7AOataCjIKaEXDqQLAGWiWjGz2jbGrKSuXWSKxivCWiXw2U-5o8Sf37D5QmkRc-yzh_EFx3pCV1L5mMXJT40KPeatMpTZkpfRzfIRogUguDePEOQ4U-yMi9w-yQ0C5h9mtXcMPb6VDrvMrlcrAka3tIgjSyYrU9Jys99Ba1fbCgnJzVcnnTeItdwwG-xgv3YeV94VBdHWMweHw0c_iNWIPXg8FHE0PYguprWITyLVPju6qqYtMtwV8hoUQeiRCkx9upabLKZ4yzLnzGOmnFR4wzezjRj3i9x13RYID7NI0HC77LsmzUyV9G1RFn3QnFqAHREEbN7T_bVAVSR2igQNhIChXRtSpQ0V4MqjvTfPhCmeIC4YrUEmJEtOIvfVsirIQGDz0zYn4mTaDMjfvF-SRG-IxOCKcx6hHeprv1Ls8e8l2-edjm-yy_xOifc6Tr_fDk9_vNZrfN88t_UiWT3g?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th align="right"></th><th width="150"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><em><strong>*format</strong></em></td><td>string</td><td>Defines the package format</td></tr><tr><td align="right"><em><strong>*version</strong></em></td><td>string</td><td>squirrel format version</td></tr><tr><td align="right"><em>NiDBVersion</em></td><td>string</td><td>The NiDB version which wrote the package</td></tr><tr><td align="right"><em><strong>*name</strong></em></td><td>string</td><td>Short name of the package</td></tr><tr><td align="right"><em>description</em></td><td>string</td><td>Longer description of the package</td></tr><tr><td align="right"><em><strong>*date</strong></em></td><td>datetime</td><td>Date the package was created</td></tr><tr><td align="right"><em>subjects</em></td><td>JSON array</td><td></td></tr><tr><td align="right">dirFormat</td><td>string</td><td>orig, seq (<strong>see details below</strong>)</td></tr><tr><td align="right">dataFormat</td><td>string</td><td>orig, anon, anonfull, nifti3d, nifti3dgz, nifti4d, nifti4dgz (<strong>see details below</strong>)</td></tr></tbody></table>

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
