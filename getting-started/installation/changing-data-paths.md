# Changing data paths

Imaging data is often large and is stored on a separate NFS mount on the NiDB server. For example if data is stored on `/data1/nidb/data`, change the config variables by going to **Admin** → **NiDB Settings...** → **NiDB Settings**. Find the **Data Directories** section and edit the appropriate data directories. Click **Save Settings** when done.

## `dcmrcv` service

If you change the default `/nidb/data/dicomincoming` path, you must also edit the dcmrcv service. On RHEL compatible systems, perform the following as root

```bash
cd /etc/systemd/system
vim dcmrcv.service   ## edit this file to reflect the new dicomincoming path
systemctl daemon-reload
systemctl restart dcmrcv
systemctl status dcmrcv  ## check if the path was changed
```
