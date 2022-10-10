---
description: It's not supposed to happen... but it can. Here's how to fix it.
---

# Troublshooting Missing Data

### Why is my data missing??

Sometimes you go to download data from a subject, and it's not there. I don't mean the series are missing from the NiDB website, but the data is actually missing from the disk.

This can happen for a lot of reasons, usually because studies are moved from one subject to another before they are completely archived. Also for the following reasons

* Subjects are merged, but data is not completely copied over on disk
* Subject ID is incorrectly entered on the MR scanner console. This causes a new ID to be generated. If the study is later moved to the correct ID, some data might not be moved over on disk
* A subject is deleted. But since data is never really deleted from NiDB, it's possible that a study was moved to that subject and not all data on disk is copied over

### Example

Suppose we have subject S1234ABC. This subject has one study, and ten series in that study. We'd expect to see the following on the website for subject S1234BC study 2.

```
S1234ABC/2

1  Localizer
2  T1w
3  T2w
4  Fieldmap
5  SE_AP
6  SE_PA
7  Task 1
8  Task 2
9  Task 3
10 Task 4
```

But, we go to export the data through the search page or through a pipeline, and not all of the series have data! If we look on the disk, we see there are series missing.

```
> cd /nidb/data/archive/S1234ABC/2
> ls
1
2
3
4
5
>
```

That's not good. This could also appear as though all series directories do exist, but if we dig deeper, we find that the `dicom` directory for each series is missing or empty. So, where's the data? We have to do some detective work.

Let's look around the subject's directory on the disk.

```
> cd /nidb/data/archive/S1234ABC
> ls
1
2
```

That's interesting, there appears to be another directory. Our study is 2, but there's also a study 1, and it doesn't show up on the NiDB website. Maybe our data is in there? Let's look.

```
> cd /nidb/data/archive/S1234ABC/1
> ls
1
2
3
4
5
6
7
8
9
10
```

That looks like our data! We can verify by doing a diff between directories that exist in both studies.

```
> cd /nidb/data/archive/S1234ABC
> diff 1/1/dicom 2/1/dicom
>
```

If this is the data we are looking for, we can copy all of the data from study 1 to study 2.

```
> cd /nidb/data/archive/S1234ABC
> cp -ruv 1/* 2/
```

After the copying is done, you should be able to go back to the study page, and click the **View file list** button at the bottom and see all of expected series.
