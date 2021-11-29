# Settings

## Config variables
The NiDB Settings page contains all configuration variables for the system. These variables can be edited on the Settings page, or by editing the `nidb.cfg` file. The default path for this file should be /nidb/nidb.cfg. The exact location of the config file is specified on the NiDB Settings page.
> ![image](https://user-images.githubusercontent.com/8302215/143916672-6c8a5db7-c7f7-4591-af5b-07b1ed85d0e6.png)

## PHP Variables
PHP has default resource limits, which may cause issues with NiDB. Limits are increased during the installation/upgrade of NiDB. The current limits are listed on the bottom of the Settings page as a reference if your NiDB installation is not working as expected.

## cron
NiDB replaces the crontab for the nidb account with a list of modules required to run NiDB. This crontab is cleared and re-setup with the default nidb crontab each time NiDB is setup/upgraded. Any items you add to the crontab will be erased during an upgrade and need to be setup again.

## System messages
At the top of the Settings page, you can specify messages which are displayed system-wide when a user logs in. These can be messages related to planned system down time or other notifications.

# Informational Links
NiDB is often run on a network with many other websites such as compute node status, internal Wikis, and project documentation. Links to websites can be specified on the Admin page directly.
> ![image](https://user-images.githubusercontent.com/8302215/143920327-03da93b3-b65b-4f07-9839-9c52d2591667.png)

# Backup
Depending on the size or importance of your data, you may want to backup your data in an off-line format rather than simply mirroring the hard drives onto another server. A backup system is available to permanently archive imaging data onto magnetic tape. LTO tapes are written in triplicate to prevent loss of data. Each tape can be stored in a separate location and data integrity ensured with a majority rules approach to data validation.
> ![image](https://user-images.githubusercontent.com/8302215/143921079-9b6e4a0c-d833-4a87-9076-7e78a5ff69d4.png)

## Backup process
Backup directory paths are specified in the config file. See the [Config variables] section.
> ![image](https://user-images.githubusercontent.com/8302215/143923934-46c02b3e-3d5a-4110-a7c2-7259cb507ed1.png)
Data is automatically copied to the `backupdir` when it is written to the `archivedir`. Data older than 24 hours is moved from `backupdir` to `backupstagingdir`. When `backupstagingdir` is at least the size of `backupsize`, then a tape is ready to be written.

|`archivedir`|&rarr;|`backupdir`|&rarr;|`backupstaging`|&rarr;|LTO tape|
|---|---|---|---|---|---|---|
||automatic||data older than 24hrs is moved||when large enough to fill a tape||

Tape 0 lists the current size of the `backupstagingdir`.

# Modules
NiDB has several modules that control backend operations. These can be enabled, disabled, put into debug mode, and the logs viewed.
![image](https://user-images.githubusercontent.com/8302215/143927610-962ffd79-73cb-4ded-bda3-1b85b208140d.png)

Enabled modules are listed in green. Running modules will list the process id of the instance of the module. Some modules can have multiple instances running, ie multithreaded, while some modules can only run 1 instance. Each running instance is color-coded with green having checked in recently and red having checked in 2 hours.

Each module has lock file(s) stored in `/nidb/lock` and log files in `/nidb/logs`

## Module manager
The module manager monitors modules to see if they have crashed, and restarts them if they have. If a module does not checkin within 2 hours (except for the backup module) it is assumed that it has crashed, and the module manager will reset the module by deleting the lock file and removing the database entry.

# Modalities

# Sites

# Instances

# Mass email

# DICOM receiver
