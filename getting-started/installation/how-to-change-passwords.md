# How to change passwords

The default usernames and passwords are as follows, change them using the method listed. Changed MariaDB passwords must also be updated in the config file (Edit `/nidb/nidb.cfg` or use **Admin** --> **Settings**)

|         Username | Default password | How to change password                                                                                                                                                                                                                                                                                             |
| ---------------: | ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
|   (Linux) `nidb` | `password`       | <p>as root, run <code>passwd nidb</code><br>as nidb, run <code>passwd</code></p>                                                                                                                                                                                                                                   |
| (MariaDB) `root` | `password`       | Login to http://localhost/phpMyAdmin using the root MySQL account and password. Go to the **User Accounts** menu option. Then click **Edit privileges** for the root account that has a `‘%’` as the hostname. Then click **Change password** button at the top of the page. Enter a new password and click **Go** |
| (MariaDB) `nidb` | `password`       | See above                                                                                                                                                                                                                                                                                                          |
|   (NiDB) `admin` | `password`       | When logged in as `admin`, go to **My Account**. Enter a new password in the password field(s). Click **Save** to change the password.                                                                                                                                                                             |
