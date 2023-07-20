---
description: How to upload data into a sample project
---

# Uploading data for a sample project

## Overview

A project will often need imaging data of different modalities uploaded to an instance of NiDB. All of the data must be associated with the correct subject, and each modality must have it's own study.

Follow this order of operations when uploading data

1. **Create the subject**(s) - Subjects must exist in NiDB and be enrolled in a project before uploading any imaging data
2. **Upload EEG and ET data before MRI data** - MRI data is automatically sorted into subject/session during import which is different than how EEG and ET are imported. Uploading the EEG and ET first makes sure that all of the subjects and associated IDs exist before attempting to upload the MRI data
3. **Upload small MRI imaging sessions** (less than 1GB in size) using the NiDB website - This is useful to upload data for a single subject.
4. **Upload large MRI imaging sessions** (greater than 1GB in size, or dozens of subjects), or data that must be anonymized, using the NiDBUploader - This is useful if you need to upload thousands of MRI files. Sometimes a single session might generate 10,000 files, and maybe you have 20 subjects. Might be easier to use the NiDBUploader.

## Create a Subject

{% hint style="info" %}
Make sure you have permissions to the instance and project into which you are uploading data.
{% endhint %}

1. Select the correct instance.
2. On the top menu click **Subjects** --> **Create Subject**
3. Fill out the information. First name, Last name, Sex, Date of Birth are required
4. Click **Add** and confirm on the next screen. The subject is now created
5. On the subject's page, select a project from the **Enroll in Project** dropdown (you might need to scroll down in the dropdown), and click **Enroll**.

### Updating IDs

1. On the subject's page, click the **Edit Subject** button on the left.
2. In the IDs section, enter the extra ID(s) in the specific project line. Separate more than one ID with commas, and put a \* next to the primary ID. Such as `*423, P523, 3543`
3. Click **Update** and confirm at the next screen.
4. If demographic data are stored in a redcap (or other) database and NiDB is storing imaging data, make sure to put each ID in each database. In other words, put the redcap ID into the the NiDB ID field and store the S1234ABC ID in redcap.

## Upload EEG and ET data

1. On the subject's page, find the **Create New Imaging Studies** dropdown. Expand that and find the **New empty study** dropdown. Select the ET or EEG modality, and click **Create**. Your study will be created.
2. On the subject's page, click the Study number that was just created, and it will show the study.
3. Fill out the protocol name, the date/time of the series, and any notes, then click **Create series**.
4. Drag and drop your file(s) onto the **Upload** button. The status of the upload will be shown below the button.
5. Don't click Refresh or press ctrl+R to reload the page. Instead, click the **Study n** link at the top of the page.
6. If you need to rename or delete files, click the **Manage N file(s)** button on the study page.

## Upload MRI data through the website (small datasets)

Upload the data

1. On the top menu, click **Data**. Then click the **Import** imaging button.
2. Click the **New Import** button.
3. Choose the files you want to upload. These can indivudual files, or zip files containing the DICOM or par/rec files.
4. Data modality should be **Automatically detect**.
5. Select the destination project
6. Leave the other matching criteria as the defaults
7. Click **Upload**.

A new row will be created with your upload. MRI data can contain just about anything, so NiDB needs to read through all the files and see what's there.

Once NiDB has parsed the data you uploaded, you'll need to decide which data to actually import.

1. Click the yellow **Choose Data to Import** button
2. Details about the import will be displayed. On the bottom will be a list of subjects, studies, and series. You can deselect certain series if you don't want to import them, but likely you'll just want to import all of the series, so click the **Archive** button.
3. Click on the Back button on the page to go back to the import list.
4. Refresh this page and eventually your import should change to a status of _Archived_.

## Upload MRI data through NiDBUploader (large datasets)

Download the NiDBUploader from github: [https://github.com/gbook/nidbuploader/releases](https://github.com/gbook/nidbuploader/releases)

Install it and run the program.

<figure><img src="../../.gitbook/assets/image (1).png" alt=""><figcaption><p>The NiDBUploader</p></figcaption></figure>

**Create a connection**

1. Fill in the server: https://yourserver.com, and enter your username/password. Click **Add Connection**.
2. Click on the connection and click **Test Connection**. It should say Welcome to NiDB after a little while.

**Select the data**

1. Select a **Data Directory** at the top of the program. This should be the parent directory of your data.
2. Change the **Modality** to MR. Uncheck the **Calculate MD5 hash...**
3. Click **Search**. This will slowly populate that list with DICOM/ParRec files that it finds.
4. Once it is done loading files, you can select multiple files and click **Remove Selected** if you need to.

**Set anonymization options**

1. Make sure **Replace PatientName** is checked.

**Set the destination**

1. Click the `...` button for the **Instance**, which will populate the list of instances. Select your instance. Then select the **Project**.
2. Click the `...` for the Site and Equipment to load the lists. Select the **Site** and **Equipment**.

**Upload the data**

1. Click **Upload**.
2. It will take a while. Like a long time. Be prepared for that. Depending on the number of files, it could take hours to upload.
3. If any files fail, it will be displayed along with a reason. If you fix the errors, then you can click **Resend Failed Objects**.
