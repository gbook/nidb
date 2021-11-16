<a href="index.html" style="font-size: larger;">&larr; Home</a>

# Users

##Accessing the users page
Admin page is only accessible if you are logged in as an administrator
> ![image](https://user-images.githubusercontent.com/8302215/141825695-e9636040-0080-45dd-9c44-7ac8f1950b8c.png)

## Creating Users

### NIS Users
NiDB will check by default if an [NIS](https://en.wikipedia.org/wiki/Network_Information_Service) account already exists when a user logs in for the first time. If the user exists in NIS, an account will created within NiDB. NIS must be enabled and able to authenticate to the NIS through the NiDB server.

### Regular Users
To create a regular user, go to **Admin** &rarr; **Users**. Click the **Add User** button. Enter their information, including password and email address. The username can be any field, such as an alphanumeric string, or an email address. If the user is given **NiDB admin** permissions, then they will be able to add/edit users.

### Account Self-registration
On public servers, or systems where users are allowed to register themselves, they can create an account and verify their email address to fully register the account. The account will then exist, but they will have no permissions to any projects within NiDB. After a user registers, they will appear on the **Admin** &rarr; **Users** &rarr; **All Other Users** tab. Click the username to edit their project permissions.
_**Note:** be careful allowing users to self-register, for obvious reasons._

## Managing Users
|![image](https://user-images.githubusercontent.com/8302215/142014954-37b7a2e7-31cf-4cd6-9ce2-7eb6af559ee2.png)|
|---|
|**There are 3 options of where to find users** A) users in the current instance (switch instance by clicking the instance list in the upper left menu) B) users not in the current instance C) deleted users|

To manage project permissions for users, go to **Admin** &rarr; **Users** and click on the username you want to manage. The page can change the name, password, email, admin status, if the account is enabled/disabled, and the projects to which the user has permissions. After changing any information on the page, click the **Save** button at the bottom of the page.
See list of user options and settings below.

|Item|Meaning|
|---|---|
|Enabled|If checked, then the user can login, otherwise they cannot login|
|NiDB Admin|If checked, this user can add/manage users, and various other Admin tasks within NiDB|
|Project admin|The user has permissions to add subjects to the project|
|Data/PHI/PII modify/view|Honestly, just check them all off|
|Instances|To give permissions to a project, the _instance_ that the project is part of must be checked|
