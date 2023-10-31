---
description: JSON object
---

# data

This data object contains information about the subjects, and potential future data.

<figure><img src="https://mermaid.ink/img/pako:eNqVlF1vmzAUhv9K5CoSkSAiEU2JK_Wqu5mmTVrvJm48fEi8Akb-0MKi_PfZBjuB9qLlAr8HP-_x8bHMGZWcAsLoIEh3XHz7WbQL8wjOVZI8daR8JQeIxnH1eJ2Nvr78-O7UyoCUKBLZ1y1iE7AOataCjIKaEXDqQLAGWiWjGz2jbGrKSuXWSKxivCWiXw2U-5o8Sf37D5QmkRc-yzh_EFx3pCV1L5mMXJT40KPeatMpTZkpfRzfIRogUguDePEOQ4U-yMi9w-yQ0C5h9mtXcMPb6VDrvMrlcrAka3tIgjSyYrU9Jys99Ba1fbCgnJzVcnnTeItdwwG-xgv3YeV94VBdHWMweHw0c_iNWIPXg8FHE0PYguprWITyLVPju6qqYtMtwV8hoUQeiRCkx9upabLKZ4yzLnzGOmnFR4wzezjRj3ivvQk03KdpPPD4LsuyUSd_GVVHnHUnFKMGREMYNVf_bPMUSB2hgQJhIylURNeqQEV7MajuTHb4QpniAuGK1BJiRLTiL31bIqyEBg89M2L-JE2gzHX7xfkkRviMTginMeoR3qa79S7PHvJdvnnY5vssv8Ton3Ok6_3w5Pf7zWa3zfPLf15Zkqw?type=png" alt=""><figcaption></figcaption></figure>

### JSON variables

<mark style="color:red;">\*required</mark>

<table data-header-hidden><thead><tr><th align="right"></th><th width="168.33333333333331"></th><th></th></tr></thead><tbody><tr><td align="right"><em><strong>Variable</strong></em></td><td><strong>Type</strong></td><td><strong>Description</strong></td></tr><tr><td align="right"><code>NumSubjects</code></td><td>number</td><td>Number of subjects in the package.</td></tr><tr><td align="right">NumGroupAnalyses</td><td>number</td><td>Number of group analyses.</td></tr><tr><td align="right"><a href="subjects/">subjects</a></td><td>JSON array</td><td>Array containing the subjects.</td></tr><tr><td align="right"><a href="group-analysis.md">group-analysis</a></td><td>JSON array</td><td>Array containing group analyses.</td></tr></tbody></table>

### Directory structure

Files associated with this section are stored in the following directory, but actual binary data should be stored in the subjects or group-analysis sub directories.

> `/data`
