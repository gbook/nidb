# Settings

## Config variables
The NiDB Settings page contains all configuration variables for the system. These variables can be edited on the Settings page, or by editing the `nidb.cfg` file. The default path for this file should be /nidb/nidb.cfg. The exact location of the config file is specified on the NiDB Settings page.
> ![image](https://user-images.githubusercontent.com/8302215/143916672-6c8a5db7-c7f7-4591-af5b-07b1ed85d0e6.png)

## PHP Variables
PHP has default resource limits, which may cause issues with NiDB. Limits are increased during the installation/upgrade of NiDB. The current limits are listed on the bottom of the Settings page as a reference if your NiDB installation is not working as expected.

## `cron`
NiDB replaces the crontab for the nidb account with a list of modules required to run NiDB. This crontab is cleared and re-setup with the default nidb crontab each time NiDB is setup/upgraded. Any items you add to the crontab will be erased during an upgrade and need to be setup again.

## System messages
At the top of the Settings page, you can specify messages which are displayed system-wide when a user logs in. These can be messages related to planned system down time or other notifications.
