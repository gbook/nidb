# How to change passwords

### Default Usernames and Passwords

<table><thead><tr><th width="150" align="right">System</th><th width="150">Username</th><th>Default Password</th></tr></thead><tbody><tr><td align="right">Linux</td><td><code>nidb</code></td><td><code>password</code></td></tr><tr><td align="right">MariaDB</td><td><code>root</code></td><td><code>password</code></td></tr><tr><td align="right">MariaDB</td><td><code>nidb</code></td><td><code>password</code></td></tr><tr><td align="right">NiDB</td><td><code>admin</code></td><td><code>password</code></td></tr></tbody></table>

### How to change Linux password

As the **root** user, run

`passwd nidb`

-or- as the **nidb** user, run

`passwd`

### How to change MariaDB passwords

The MariaDB `root` account has the hostname `localhost`. The MariaDB `nidb` account has the hostname `%`.

{% tabs %}
{% tab title="Command line" %}
Log in to MariaDB as root

```bash
mysql -uroot -p
```

Then change the password(s)

```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'newpassword';
ALTER USER 'nidb'@'%' IDENTIFIED BY 'newpassword';
```
{% endtab %}

{% tab title="phpMyAdmin" %}
If you have installed [phpMyAdmin](optional-software.md), login to http://localhost/phpMyAdmin using the root MariaDB account and password. Go to the **User Accounts** menu option. Then click **Edit privileges** for the `root` account with the hostname `localhost`, or the `nidb` account with the hostname `%`. Then click **Change password** button at the top of the page. Enter a new password and click **Go**
{% endtab %}
{% endtabs %}

{% hint style="info" %}
If you change the MariaDB `nidb` password, it must also be updated in the config file. Use one of the following methods to change the `[mysqlpassword]` config variable

* Edit `/nidb/nidb.cfg` to reflect the new password
* Go to **Admin** --> **Settings** in the NiDB website to edit the config variables

The MariaDB `root` password is not stored in the config file. It is entered on the setup page when installing or upgrading NiDB.
{% endhint %}

### How to change NiDB `admin` password

When logged in to NiDB as `admin`, go to **My Account**. Enter a new password in the password field(s). Click **Save** to change the password.
