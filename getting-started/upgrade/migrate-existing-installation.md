---
description: How to migrate an existing NiDB installation to a new server
---

# Migrate Existing Installation

Sometimes you need to move your installation to a new server. Maybe you were testing in a virtual machine and want to move to a full server, or vice-versa. Maybe your server needs to be upgraded. Follow these steps to migrate an installation from one server to another.

### Migration steps

1. On the _old server_, export the SQL database
   * `mysqldump -uroot -ppassword nidb > nidb-backup.sql`
2. Copy the exported .sql file to the _new server_.
3. On the _new server_, install NiDB as a new installation
4. On the _new server_, import the new database
   * `mysql -uroot -ppassword nidb < nidb-backup.sql`
5. Finish upgrade, by going to http://localhost/setup.php . Follow the instructions to continue the upgrade.
