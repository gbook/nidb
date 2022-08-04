# How to change passwords

### Default Usernames and Passwords

|  System | Username | Default Password |
| ------: | -------- | ---------------- |
|   Linux | `nidb`   | `password`       |
| MariaDB | `root`   | `password`       |
| MariaDB | `nidb`   | `password`       |
|    NiDB | `admin`  | `password`       |

### How to change Linux password

As the **root** user, run

`passwd nidb`

\-or- as the **nidb** user, run

`passwd`

### How to change MariaDB passwords

Login to http://localhost/phpMyAdmin using the root MySQL account and password. Go to the **User Accounts** menu option. Then click **Edit privileges** for the `root` (or `nidb`) account that has a `‘%’` as the hostname. Then click **Change password** button at the top of the page. Enter a new password and click **Go**

{% hint style="info" %}
Changed MariaDB passwords must also be updated in the config file. Use one of the following methods to edit the password

* Edit `/nidb/nidb.cfg` to reflect the new password
* Go to **Admin** --> **Settings** in the NiDB website to edit the config variables
{% endhint %}

### How to change NiDB `admin` password

When logged in to NiDB as `admin`, go to **My Account**. Enter a new password in the password field(s). Click **Save** to change the password.
