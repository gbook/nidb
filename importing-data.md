<a href="index.html">Home</a>
  
# Importing Data

## Data Heirarchy
Data within NiDB is stored in a heirarchy.

|Level|Project|&rarr;|Enrollment|&rarr;|Subject|&rarr;|Study|&rarr;|Series|
|---|---|---|---|---|---|---|---|---|---|
|Examples &darr;|Brains & Behavior||Enrolled 2018-10-30||S1234ABC||S1234ABC1 (MR)||1 - T1w|
||Brains & Behavior||Enrolled 2018-10-30||S1234ABC||S1234ABC2 (EEG)||1 - GoNoGo|
||Brains & Behavior Part 2||Enrolled 2021-11-30||S1234ABC||S1234ABC3 (MR)||1 - T2w|
||Brains & Behavior Part 2||Enrolled 2021-11-30||S1234ABC||S1234ABC4 (ET)||1 - AntiSaccade|

## Create Subject
On the main menu, find the **Subjects** tab. A page will be displayed in which you can search for existing subjects, and a button to create a new subject

1. **Subjects** page menu item
2. **Create Subject** button
3. **Olibterate subjects** button: an intimidating sounding button that only appears for NiDB admins

<div align="center"><img src="https://user-images.githubusercontent.com/8302215/144305883-6007dc32-2284-4223-978f-975842bb0250.png" width="50%"></div>

Fill out as much information as you need. Name, Sex, DOB are required to ensure a unique subject. Most other information is optional. While fields for contact information are available, be mindful and consider whether you really need to fill those out. Chances are that contact information for research participants is already stored in a more temporary location and does not need to exist for as long as the imaging data does.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/144306665-afec15f7-bd67-41e3-b68c-665d3d5ea3fc.png" width="50%"></div>

The subject will now be assigned a UID, but will not be enrolled in any projects. Enroll the subject in the next section.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/144307513-607a92d3-6546-44b2-a97a-e1cd786a0e75.png" width="50%"></div>

## Enroll in Project
In the enrollments section, select the project you want to enroll in, and click Enroll. The subject will now be enrolled in the project. Permissions within NiDB are determined by the project, which is in theory associated with an IRB approved protocol. If a subject is not enrolled in a project, the default is to have no permissions to view or edit the subject. Now that the subject is part of a project, you will have permissions to edit the subject's details.
Once enrolled, you can edit the enrollment details and create studies.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/144307819-ad893e5b-e68d-4f10-a184-a3e37947f7c3.png" width="50%"></div>

## Create Imaging Study
There are three options for creating studies
1. Create a single empty study for a specific modality
2. Create a single study prefilled with empty series, from a template
3. Create a group of studies with empty series, from a template

Click **Create new imaging studies** to see these options. To create study templates or project templates, see Study Templates.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/144504221-08222370-8e7a-4b05-b045-cb575510bc46.png" width="50%"></div>

Once the study is created, it will appear in the list of imaging studies. Studies are given a unique number starting at 1 in order in which they are created. The studies are sorted by date in this list. While studies will often appear sequential by date and study number, this is because study numbers are incremented by each new study date added, and each new study often occurs at a later date. However, studies may be numbered in any order, regardless of date. If you create several studies for previous dates, if importing older data, if deleting or merging studies, this will cause study numbers to appear random. This is the normal behavior.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/144616105-237e31c5-d909-4679-bf24-50379b41cdad.png" width="50%"></div>

## Create Single Series/Upload data
MRI and non-MRI data are handled differently, because of the substantial amount of information contained in MRI headers. MRI series are created automatically during import, while all other imaging data can be imported automatically or manually.
### MRI
MRI series cannot be created manually, they must be imported as part of a dataset. See [Bulk Import of Large Datasets](#bulk-import-of-large-datasets) or [Automatic Import via DICOM receiver](#automatic-import-via-dicom-receiver). MRI series can be managed individually after automatic importing has occured.
### Non-MRI
Non-MRI data be imported automatically or manually. To manually import non-MRI data, first go into the imaging study. Then fill out the series number, protocol, date, notes. Series number and date are automatically filled, so change these if you need to. When done filling out the fields, click **Create Series**.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/144630122-41f9489b-9dde-41a0-b8a5-ff77e11ccf87.png" width="50%"></div>

The series will be created, with an option to create another series below it. Upload files by clicking the **Upload** button, or by dragging and dropping onto the **Upload** button. If you need to delete or rename files, click the **Manage files** button. This will display a list of files in that series, and you can rename the file by typing in the filename box.
<div align="center"><img src="https://user-images.githubusercontent.com/8302215/144631332-5f49c4ca-6c7f-472b-ae22-a3f977cc610c.png" width="50%"></div>

## Bulk Import of Large Datasets

## Automatic Import via DICOM receiver

## Bulk Upload of non-MRI data
