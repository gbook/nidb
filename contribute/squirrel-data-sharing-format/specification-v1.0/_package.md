---
description: JSON object
---

# package

This object contains information about the squirrel package.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8bHMGZWcAsLoIEh3XHz7WbQL8wjOVZI8daR8JQeIxnH1eJ2Nvr78-O7UyoCUKBLZ1y1iE7AOataCjIKaEXDqQLAGWiWjGz2jbGrKSuXWSKxivCWiXw2U-5o8Sf37D5QmkRc-yzh_EFx3pCV1L5mMXJT40KPeatMpTZkpfRzfIRogUguDePEOQ4U-yMi9w-yQ0C5h9mtXcMPb6VDrvMrlcrAka3tIgjSyYrU9Jys99Ba1fbCgnJzVcnnTeItdwwG-xgv3YeV94VBdHWMweHw0c_iNWIPXg8FHE0PYguprWITyLVPju6qqYtMtwV8hoUQeiRCkx9upabLKZ4yzLnzGOmnFR4wzezjRj3i9x13RYID7NI0HC77LsmzUyV9G1RFn3QnFqAHREEbN7T_bVAVSR2igQNhIChXRtSpQ0V4MqjvTfPhCmeIC4YrUEmJEtOIvfVsirIQGDz0zYn4mTaDMjfvF-SRG-IxOCKcx6hHeprv1Ls8e8l2-edjm-yy_xOifc6Tr_fDk9_vNZrfN88t_UiWT3g?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th width="274" align="right"></th><th width="128.00000000000003"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><code>PackageFormat</code></td><td>string</td><td>Always <code>squirrel</code>.</td></tr><tr><td align="right"><code>SquirrelVersion</code></td><td>string</td><td>Squirrel format version.</td></tr><tr><td align="right"><code>SquirrelBuild</code></td><td>string</td><td>Build version of the squirrel library and utilities.</td></tr><tr><td align="right"><code>NiDBVersion</code></td><td>string</td><td>The NiDB version which wrote the package.</td></tr><tr><td align="right"><code>PackageName</code></td><td>string</td><td>Short name of the package.</td></tr><tr><td align="right"><code>Description</code></td><td>string</td><td>Longer description of the package.</td></tr><tr><td align="right"><code>Datetime</code></td><td>datetime</td><td>Datetime the package was created.</td></tr><tr><td align="right"><code>SubjectDirectoryFormat</code></td><td>string</td><td><code>orig</code>, <code>seq</code> (<strong>see details below</strong>).</td></tr><tr><td align="right"><code>StudyDirectoryFormat</code></td><td>string</td><td><code>orig</code>, <code>seq</code> (<strong>see details below</strong>).</td></tr><tr><td align="right"><code>SeriesDirectoryFormat</code></td><td>string</td><td><code>orig</code>, <code>seq</code> (<strong>see details below</strong>).</td></tr><tr><td align="right"><code>DataFormat</code></td><td>string</td><td>Data format for imaging data to be written. Squirrel should attempt to convert to the specified format if possible. <code>orig</code>, <code>anon</code>, <code>anonfull</code>, <code>nifti3d</code>, <code>nifti3dgz</code>, <code>nifti4d</code>, <code>nifti4dgz</code> (<strong>see details below</strong>).</td></tr><tr><td align="right"><code>License</code></td><td>string</td><td>Any sharing or license notes, or LICENSE files.</td></tr><tr><td align="right"><code>Readme</code></td><td>string</td><td>Any README files.</td></tr><tr><td align="right"><code>Changes</code></td><td>string</td><td>Any CHANGE files.</td></tr><tr><td align="right"><code>Notes</code></td><td>JSON object</td><td>See details below.</td></tr></tbody></table>

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
