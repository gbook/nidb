---
description: >-
  This tutorial describes how to find subjects by ID, and how to map multiple
  IDs.
---

# Working with subject IDs

## Why do subjects have more than one ID?

A few possible reasons

* Subject can be enrolled in more than one project, and assigned a different ID for each enrollment
* Subjects are assigned more than one ID within a project
* Data are imported from other databases. The subjects retain the original ID and assigned a new ID
* Imaging studies are assigned unique IDs, regardless of subject

## Mapping subject IDs

The simplest way to find a subject by any ID is to use the ID mapper. Go to **Data** --> **ID Mapper**. Enter your ID(s) in textbox and click **Map IDs**. There are some options available to filter by project, instance, only matches, and only active subjects.

![We're searching for six IDs: 2310, 50, 13, 529, 401, S1234ABC](<../.gitbook/assets/image (6) (1).png>)

The next page will show any matching subjects.

![4 of 6 IDs were found!](<../.gitbook/assets/image (7) (1).png>)

The first column **Foreign ID** is the ID you searched for. If that ID is found anywhere in the system, there will be details about it in the **Local** columns to the right.

**Deleted?** - indicates if this subject has been deleted or not. Deleted subjects are not actually deleted from the system, they are just marked inactive

**Alt Subject ID** - If the foreign ID was found under this field, it will show up in this column.

**Alt Study ID** - If the foreign ID was found under this field, it will be show in this column.

**UID** - If a subject was found, the UID will be displayed in this column

**Enrollment** - There may be more than one row found for each foreign ID, and more than one ID for the enrollment in each row. The enrollment will be displayed in this column.

Click on the UID to see your subject.
