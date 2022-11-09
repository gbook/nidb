---
description: >-
  This tutorial describes how to completely erase all data from an NiDB
  installation
---

# Deleting all the data

## Why would anyone want to do this?

There exists the possibility that you may need to completely erase all data from an NiDB installation. Maybe you were importing a bunch of test data and now you want to wipe it clean without reinstalling NiDB. Whatever your reason, you want to make an existing installation clean.

This procedure is not part of NiDB and there are no scripts or automated ways to do this because of the possibility of accidents. You may want to completely empty your refrigerator and toss all food in the trash, but you don't want a button available on the side of the fridge to do it.

## How to Clean the System

### Database

Truncate all tables except the following

* instance
* modalities
* modules
* users

### Filesystem

Clear the **contents** of the following directories. **Only delete the files in the directories, do not delete the directories.**

```
/nidb/data/archive
/nidb/data/backup
/nidb/data/backupupstaging
/nidb/data/deleted
/nidb/data/dicomincoming
/nidb/data/download
/nidb/data/ftp
/nidb/data/problem
/nidb/data/tmp
/nidb/data/upload
/nidb/data/uploaded
/nidb/data/uploadstaging
```

There is no need to clear the log files or lock files or any other directories.

NiDB should now be ready to import new data.
