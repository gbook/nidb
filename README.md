# NeuroInformatics Database

## Overview
The Neuroinformatics Database (NiDB) is designed to store, retrieve, analyze, and share neuroimaging data. Modalities include MR, EEG, ET, video, genetics, assessment data, and any binary data. Subject demographics, family relationships, and data imported from RedCap can be stored and queried in the database.

### Features
* .rpm based installation for CentOS 8 and CentOS 8 Stream
* Store any neuroimaging data, including MR, CT, EEG, ET, Video, Task, GSR, Consent, MEG, TMS, and more
* Store any assessment data (paper-based tasks)
* Store clinical trial information (manage data across multiple days & dose times, etc)
* Built-in DICOM receiver. Send DICOM data from PACS or MRI directly to NiDB
* Bulk import of imaging data
* User and project based permissions, with project admin roles
* Search and manipulate data from subjects across projects
* Automated imaging analysis pipeline system
* "Mini-pipeline" module to process behavioral data files (extract timings)
* All stored data is searchable. Combine results from pipelines, QC output, behavioral data, and more in one searchable
* Export data to NFS, FTP, Web download, NDA (NIMH Data Archive format), or export to a remote NiDB server
* Project level checklists for imaging data
* Automated motion correction and other QC for MRI data
* Calendar for scheduling equipment and rooms
* Usage reports, audits, tape backup module
* Intuitive, modern UI. Easy to use

## Download and Install
Current release is here: https://github.com/gbook/nidb/releases
Follow installation instructions here: http://gbook.github.io/nidb/#new-installation

## Users Manual
http://gbook.github.io/nidb/user-manual.html

## Admin Manual
http://gbook.github.io/nidb/administration.html

## Support
Visit the NiDB's github <a href="https://github.com/gbook/nidb/issues">issues</a> page for more support.

## Publications
* Book GA, Anderson BM, Stevens MC, Glahn DC, Assaf M, Pearlson GD. Neuroinformatics Database (NiDB)--a modular, portable database for the storage, analysis, and sharing of neuroimaging data. Neuroinformatics. 2013 Oct;11(4):495-505. doi: 10.1007/s12021-013-9194-1. PMID: 23912507; PMCID: PMC3864015. https://pubmed.ncbi.nlm.nih.gov/23912507/
* Book GA, Stevens MC, Assaf M, Glahn DC, Pearlson GD. Neuroimaging data sharing on the neuroinformatics database platform. Neuroimage. 2016 Jan 1;124(Pt B):1089-1092. doi: 10.1016/j.neuroimage.2015.04.022. Epub 2015 Apr 16. PMID: 25888923; PMCID: PMC4608854. https://pubmed.ncbi.nlm.nih.gov/25888923/

**Outdated information**
Watch an overview of the main features of NiDB (recorded 2015, so it's a little outdated): <a href="https://youtu.be/tOX7VamHGvM">Part 1</a> | <a href="https://youtu.be/dX11HRj_kEs">Part 2</a> | <a href="https://youtu.be/aovrq-oKO-M">Part 3</a>
