# Quick Upgrade

See [detailed](./) upgrade instructions for a more in-depth explanation of the upgrade.

1. Download latest NiDB release.
2. `yum --nogpgcheck localinstall nidb-xxxx.xx.xx-1.el8.x86_64.rpm`
3. Make sure your IP address is set in the `[setupips]` variable in the config file. This can be done manually by editing `/nidb/nidb.cfg` or by going to **Admin** → **Settings**
4. Go to http://localhost/setup.php (Or within NiDB, go to **Admin** → **Setup/upgrade**)
5. Follow the instructions on the webpages to complete the upgrade
