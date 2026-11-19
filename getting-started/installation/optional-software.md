---
description: List of extremely useful, but optional, software to help when using NiDB
---

# Optional software

## HeidiSQL

**HeidiSQL** is not required to use NiDB, but is extremely useful to maintain the SQL database that NiDB uses. It is a free, open-source database client that runs on your own computer and connects to the NiDB server's MariaDB database, so nothing extra is installed on the NiDB server. Download it from [https://www.heidisql.com/](https://www.heidisql.com/)

Connect using one of these methods

* **SSH tunnel (recommended)** - HeidiSQL logs in to the NiDB server over SSH and reaches MariaDB from the server itself. The database port does not need to be opened to the network, and the MariaDB `root` account can be used. Setup is described below.
* **Direct connection** - Choose the network type _MariaDB or MySQL (TCP/IP)_ and connect as the MariaDB `nidb` account. This requires port 3306 on the NiDB server to be reachable from your computer. The MariaDB `root` account only accepts connections from the server itself.

HeidiSQL is one of the ways to change the MariaDB passwords, as described in [How to change passwords](how-to-change-passwords.md).

### Connecting through an SSH tunnel

An SSH tunnel connection uses two different addresses. The **SSH tunnel** tab holds the address of the NiDB server, as seen from your computer. The **Settings** tab holds the address of the database, as seen from the NiDB server. Because MariaDB runs on the NiDB server, the database address is always `127.0.0.1`.

1. In HeidiSQL's Session manager, click **New** and set **Network type** to _MariaDB or MySQL (SSH tunnel)_.
2. On the **Settings** tab, enter the database connection details.
3. On the **SSH tunnel** tab, enter the SSH login details.
4. Go back to the **SSH tunnel** tab and check that **SSH host** is still the NiDB server's hostname (see the warning below), then click **Open**.

| Tab        | Field                        | Value                                           |
| ---------- | ---------------------------- | ----------------------------------------------- |
| Settings   | Hostname / IP                | `127.0.0.1`                                     |
| Settings   | User                         | MariaDB account, `nidb` or `root`               |
| Settings   | Password                     | MariaDB password for that account               |
| Settings   | Port                         | `3306`                                          |
| SSH tunnel | SSH host + port              | NiDB server's hostname or IP address, port `22` |
| SSH tunnel | Username                     | your Linux login on the NiDB server             |
| SSH tunnel | Password or private key file | your Linux password, or your SSH key            |
| SSH tunnel | Local port                   | `3307` (any free port on your computer)         |

{% hint style="warning" %}
Changing **Hostname / IP** on the Settings tab can also change **SSH host** on the SSH tunnel tab to the same value. After setting the hostname to `127.0.0.1`, open the SSH tunnel tab again and set **SSH host** back to the NiDB server's hostname.
{% endhint %}

### Troubleshooting

**Host '**_**servername**_**' is not allowed to connect to the MariaDB server**

The SSH login worked, but **Hostname / IP** on the Settings tab is the NiDB server's own hostname instead of `127.0.0.1`. The server then connects to MariaDB through its network interface, and MariaDB sees the connection as coming from the server's hostname rather than from localhost. Set **Hostname / IP** to `127.0.0.1`.

**SSH exited unexpected**

Check the command line shown in the error, for example `plink.exe -ssh nidb@127.0.0.1 ... -L 3307:127.0.0.1:3306`. The address after `@` is the SSH host. If it is `127.0.0.1`, HeidiSQL is trying to SSH in to your own computer. Set **SSH host** on the SSH tunnel tab to the NiDB server's hostname. If the address is correct, check that you can log in with a normal SSH client (`ssh username@servername`). If that fails too, the problem is the SSH login (password, key or firewall), not MariaDB.

**Access denied for user**

The connection reached MariaDB, but the MariaDB username or password on the Settings tab is wrong. These are the MariaDB account details, not your Linux login.
