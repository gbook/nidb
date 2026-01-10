---
description: How to migrate an existing NiDB installation to a new server
---

# Migrate Existing Installation

Sometimes you need to move your installation to a new server. Maybe you were testing in a virtual machine and want to move to a full server, or vice-versa. Maybe your server needs to be upgraded. Follow these steps to migrate an installation from one server to another.

### Migration steps

1. On the _**old server**_, export the SQL database
   * `mysqldump -uroot -ppassword nidb > nidb-backup.sql`
2. Copy the exported `nidb-backup.sql` file from the _**old server**_ to the _**new server**_.
3. Copy the archive data from the _**old server**_ to the _**new server**_. The default archive directory is `/nidb/data/archive`. Copy additional data from the other /nidb/data directories.\
   Example copy command `rsync /nidb/data/archive/* user@newhost:/nidb/data/archive/`
4. On the _**new server**_, install NiDB as a new installation
5. On the _**new server**_, import the new database
   * `mysql -uroot -ppassword nidb < nidb-backup.sql`
6. Verify that the database table row counts are the same in the new server and old server using phpMyAdmin.
7. Verify that the /nidb/data directory sizes match between the old server and new server.\
   Example command `du -sb /nidb/data/`
8. Finish upgrade on the _**new server**_, by going to http://localhost/setup.php . Follow the instructions to continue the upgrade.
