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

### Subject IDs

In this example, a subject is enrolled in 3 projects, where each project has a different ID scheme.&#x20;

* Project 1 has an ID range of `400` to `499`
* Project 2 a range of `A100` to `A200` and `B100` to `200`
* Project 3 a range of `10000` to `10100`

[![](https://mermaid.ink/img/pako:eNqFkF9rwyAUxb-K3KcU0qLWpcbBoPvztoexvo28uHi7ZjQxWAPrQr77TELa9aHbAa\_36u\_gwRZyaxAUfDhd78jza1aRoA3jS7G-f4imZkbm8zvCKGUsGurs9k9yzXga9eUfTlARhTVRh-Z9DPLi7CfmnizH817Ds-OIlem3Kx5-9vQJTpYrODvjIckvGmIo0ZW6MOF\_2v4iA7\_DEjNQoTW41c3eZ5BVXUCb2miPT6bw1oHa6v0BY9CNt5tjlYPyrsEJeix0iFCeqFpXb9ZezKBa-AJFYziC4jRZJFKsZCLZistUyC6G78FBF-koeZMylnApux91Sn-P?type=png)](https://mermaid.live/edit#pako:eNqFkF9rwyAUxb-K3KcU0qLWpcbBoPvztoexvo28uHi7ZjQxWAPrQr77TELa9aHbAa\_36u\_gwRZyaxAUfDhd78jza1aRoA3jS7G-f4imZkbm8zvCKGUsGurs9k9yzXga9eUfTlARhTVRh-Z9DPLi7CfmnizH817Ds-OIlem3Kx5-9vQJTpYrODvjIckvGmIo0ZW6MOF\_2v4iA7\_DEjNQoTW41c3eZ5BVXUCb2miPT6bw1oHa6v0BY9CNt5tjlYPyrsEJeix0iFCeqFpXb9ZezKBa-AJFYziC4jRZJFKsZCLZistUyC6G78FBF-koeZMylnApux91Sn-P)

These IDs can be managed within the subject demographics page. On the left hand side of the Subject's page, edit the subject by clicking the Edit Subject button.

<figure><img src="../.gitbook/assets/image (2).png" alt=""><figcaption></figcaption></figure>

Then scroll down part way on the page and you'll see the ID section, where you can enter all IDs, for all projects/enrollments for this subject. This is a list of **Alternate Subject IDs**. The asterisk \* indicates this is the **Primary Alternate Subject ID**.

In this example, the Testing project has more than one ID. This can happen if a subject is assigned more than one ID, for example the subject was collected under 2 different IDs and merged, or collected at a different site with different ID scheme, or there is more than one ID format for the project.

<figure><img src="../.gitbook/assets/image (11).png" alt=""><figcaption></figcaption></figure>

### Study IDs

Some imaging centers give a unique ID every time the participant comes in (yes, this can be a nightmare to organize later on). Imagine subject comes in on 3 different occasions and receives a different subject ID each time. If you are able to associate these IDs back with the same subject, you can treat these as the **Study IDs**. The default study is the study number appended to the UID, for example S1234ABC1. In NiDB, all other study IDs are considered **Alternate Study IDs**.

<figure><img src="../.gitbook/assets/image (5).png" alt=""><figcaption><p>Alternate Study IDs can be edited by clicking the Edit Study button</p></figcaption></figure>

## Mapping subject IDs

The simplest way to find a subject by any ID is to use the ID mapper. Go to **Data** --> **ID Mapper**. Enter your ID(s) in textbox and click **Map IDs**. There are some options available to filter by project, instance, only matches, and only active subjects.

![We're searching for six IDs: 2310, 50, 13, 529, 401, S1234ABC](<../.gitbook/assets/image (6) (1) (1).png>)

The next page will show any matching subjects.

![4 of 6 IDs were found!](<../.gitbook/assets/image (7) (1).png>)

The first column **Foreign ID** is the ID you searched for. If that ID is found anywhere in the system, there will be details about it in the **Local** columns to the right.

**Deleted?** - indicates if this subject has been deleted or not. Deleted subjects are not actually deleted from the system, they are just marked inactive

**Alt Subject ID** - If the foreign ID was found under this field, it will show up in this column.

**Alt Study ID** - If the foreign ID was found under this field, it will be show in this column.

**UID** - If a subject was found, the UID will be displayed in this column

**Enrollment** - There may be more than one row found for each foreign ID, and more than one ID for the enrollment in each row. The enrollment will be displayed in this column.

Click on the UID to see your subject.
