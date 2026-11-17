---
description: List of extremely useful, but optional, software to help when using NiDB
---

# Optional software

## HeidiSQL

**HeidiSQL** is not required to use NiDB, but is extremely useful to maintain the SQL database that NiDB uses. It is a free, open-source database client that runs on your own computer and connects to the NiDB server's MariaDB database, so nothing extra is installed on the NiDB server. Download it from [https://www.heidisql.com/](https://www.heidisql.com/)

Connect using one of these methods

* **SSH tunnel (recommended)** - Choose the network type _MariaDB or MySQL (SSH tunnel)_. On the **SSH tunnel** tab, enter the NiDB server's hostname and a Linux login. On the **Settings** tab, enter the hostname `127.0.0.1` and the MariaDB `root` or `nidb` account. The database port does not need to be opened to the network.
* **Direct connection** - Choose the network type _MariaDB or MySQL (TCP/IP)_ and connect as the MariaDB `nidb` account. This requires port 3306 on the NiDB server to be reachable from your computer. The MariaDB `root` account only accepts connections from the server itself.

HeidiSQL is one of the ways to change the MariaDB passwords, as described in [How to change passwords](how-to-change-passwords.md).
