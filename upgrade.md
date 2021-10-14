# Upgrade NiDB steps

## Install NiDB .rpm

Get the most recent .rpm from github. The latest version may be different than the example below. You can also download the latest release .rpm from https://github.com/gbook/nidb/releases/latest

    > wget https://github.com/gbook/nidb/releases/download/v2021.10.699/nidb-2021.10.699-1.el8.x86_64.rpm
    > sudo yum localinstall --nogpgcheck nidb-2021.10.699-1.el8.x86_64.rpm

    Last metadata expiration check: 0:28:21 ago on Thu 14 Oct 2021 10:01:28 AM EDT.
    Dependencies resolved.
    ============================================================================================================================================
     Package                    Architecture                 Version                                   Repository                          Size
    ============================================================================================================================================
    Upgrading:
     nidb                       x86_64                       2021.10.699-1.el8                         @commandline                        56 M

    Transaction Summary
    ============================================================================================================================================
    Upgrade  1 Package

    Total size: 56 M
    Is this ok [y/N]:


## Complete setup on Website

Visit http://localhost/setup.php and follow the pages.

**Entry page** - Turning off access to the website and disabling all modules can help prevent errors during the upgrade. Always remember to backup the database! Click **Next** to continue.
> ![image](https://user-images.githubusercontent.com/8302215/137331276-17cd180c-91ec-4220-9c5f-fc55888dfebb.png)

<br>

**Pre-requisites** - This page will check for CentOS packages and display an error if a package is missing or the wrong version. If missing any packages, check the output from the NiDB rpm installation or manually install the missing packages. After packages are installed, then refresh this page. Once all pre-requisities are met, click **Next** to continue.

<kbd>
    
![image](https://user-images.githubusercontent.com/8302215/137331530-3d1f31f3-8f96-480f-a5d7-42be7f382adc.png)
    
</kbd>

<br>

**SQL database connection** Enter the root SQL password in this screen. If you want to check what tables will be updated, without updating them, select the Debug checkbox. If you encounter issues upgrading large tables, you can choose to limit the size of the tables that are upgraded and you can then update those manually. This is not recommended however. Click **Configure Database** to continue.
> ![image](https://user-images.githubusercontent.com/8302215/137331692-45946205-1ace-4789-875b-55851b43f440.png)

<br>

**Schema upgrade** The details of the schema upgrade will be displayed. Any errors will be indicated. Click **Next** to continue.
> ![image](https://user-images.githubusercontent.com/8302215/137331838-e4ed780e-52b8-4872-b392-fc4eeed71ac4.png)

<br>

 **Configuration** Any changes (paths, settings, options, etc) can be changed here. Click **Write Config** to continue.
> ![image](https://user-images.githubusercontent.com/8302215/137332401-3d0588f7-3225-49bd-b04a-26fb205f99cc.png)

<br>

All finished! Click **Done** to complete the upgrade.
> ![image](https://user-images.githubusercontent.com/8302215/137332036-d85cc1e9-c669-4777-bb84-47cf0081be12.png)
