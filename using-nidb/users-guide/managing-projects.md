# Managing projects

## Projects

NiDB is a multi-project database. Data from multiple projects can be managed in one database instance. Each project can have different attributes according to the needs of the project.

### Creating a Project

A user with admin rights can create, and manage a project in NiDB. A user with Admin rights will have an extra menu option "Admin". To create a new project in NiDB, click "Admin" from the main menu and then click "Projects" as shown in the figure below.

![](https://user-images.githubusercontent.com/24811295/153896038-9f2f374b-eaba-4333-890c-62af47ece2cd.png)

The following page with the option "Create Project" will appear. This page also contains a list of all the current projects. To create a new project, click on the "Create Project" button on the left corner of the screen as shown in the figure below.

![](https://user-images.githubusercontent.com/24811295/153897601-4cef74fd-f0a7-40ac-9e8f-a7f9a2fbdbb1.png)

On the next page, fill the following form related to the new project. Name the new project, fill the project number. Select the option "Use Custom IDs" if project need to use its own ID system. Select the Principal Investigator (PI) and project administrator (PA) from the existing NiDB users. The PI and PA can be the same subject. Mention the start and end date if they are known. Also there is an option if you want to copy an existing setting from one of your projects.

![](https://user-images.githubusercontent.com/24811295/154157184-0ba2665d-b014-44ce-b231-dfdd27b77d40.png)

After clicking "Add" button, a new project will be added to the project list and it will be shown in the list of existing projects as shown in the figure below.

![](https://user-images.githubusercontent.com/24811295/154159860-50dc0c2e-f7b3-4f0d-91d1-62ce667a0c51.png)

### Project Setup

To setup the project for collecting data click the name of the project on the above page and the following page can be used to add the right set of protocols.

![](https://user-images.githubusercontent.com/24811295/154161565-d6bd03bc-b0d1-4f21-9757-b0b202a62b7a.png)

After adding the required set of protocols, a list of protocols will be shown as follow. A protocol can be deleted by clicking the "delete" button in front of an added protocol as shown in the figure below.

![](https://user-images.githubusercontent.com/24811295/154162653-484e7016-2ea7-4422-8e6a-c991b6463780.png)

To define a protocol, click on the name of a protocol in the above list. For example if we click on EEG-Rest, the following page will appear with the existing list of EEG-series being already used in various projects. You can pick any of those to add to your own protocol group. A group name can be assigned by using the "Protocol group name" box at the end of the page as shown. After clicking the "Add" button the selected series in the group will be added to the group and will be shown on the right.

![](https://user-images.githubusercontent.com/24811295/154166600-05780538-b187-4da1-8a46-5022ed3b6779.png)

### Working With a Project

After setting up project accordingly, the project can be accessed by users having its rights. A user can access a project via "Projects" menu from the main menu. A list of existing projects will be displayed. TO search a specific project, type the name of a project and the list will reduced to the projects containing the search phrase.

![](https://user-images.githubusercontent.com/24811295/154300643-1715e992-a15e-4866-a960-2da70145fdd5.png)

Click the name of the project from the list as shown above. A project specific page will appear as seen below.

![](https://user-images.githubusercontent.com/24811295/154773657-6225f1e8-9a2b-4a8a-9e53-185bba020d25.png)

A project page consists of some information regarding the current project. Under the project name there is total number of subjects and studies. Undeneath that is a message box consists of number of studies. One can dismiss this message box by clicking "dismiss" button or view all the studies inside the message box.

![](https://user-images.githubusercontent.com/24811295/155009550-45c4937e-001f-49f7-b93b-021ffffdf5d2.png)

In the middle of a project page is "Data Views" for subjects, studies, checklist for subjects and an option to QC the MR scans.

![](https://user-images.githubusercontent.com/24811295/155004114-15ac4229-09a7-436e-bdd1-7d4f953cd3dc.png)

To update information regarding the subjects in the current project, click on the "Subjects" button in the data view, a page will appear where the information can be updated for all the subjects and can be saved at once by clicking "Save" button at the end.

![](https://user-images.githubusercontent.com/24811295/155018127-f55d837c-02cd-4f17-b5c4-fb8c81fde42c.png)

By clicking the **Studies** button from **Data Views** section, following page will appear. The studies can be selected to perform various operations like adding enrollment tags, moving studies to another project.

{% hint style="info" %}
If you are an NiDB system admin, you may see the Powerful Tools box at the bottom of the page. This allows you to perform maintenance on the data in batches. Select the studies, and then click one of the options. **This is a powerful tool, so use with caution!**
{% endhint %}

![](https://user-images.githubusercontent.com/24811295/155020201-ce11cf60-0b50-4c57-8276-04ddc9b122a6.png)

Checklist provides a brief summary on the subjects, studies and their status as shown below.

![](https://user-images.githubusercontent.com/24811295/155039537-8b9f50f7-223f-4759-a8c1-a3dfbeba72d1.png)

On the right side of the project page is a star that can be selected to make this project "favorite" that will show this project on the main page of NiDB to access it easily from there. Also there are links to the project related tools and their settings. This section is named as "Project tools & settings". This section includes:

* Data Dictionary
* Analysis Builder
* Study Templates
* BIDS Protocol Mapping
* NDA Mapping
* Behavioral Minipipelines
* Recap-> NiDB Transfer
* Reset MRI QA

It also possess the parameters required to connect this project remotely.

![](https://user-images.githubusercontent.com/24811295/155005253-83266b98-f05b-4d39-8731-2c39a6dcc68b.png)

The last section of a project page consists of a list of subjects registtered, with its alternate Ids, GUID, DOB, Sex, and status as shown below:

![](https://user-images.githubusercontent.com/24811295/155011486-ce33311f-e3c2-40f0-855e-3a064b2307a3.png)

The projects main-menu also has a sub-menu to navigate through various project related tools. The sub-menu includes links to Data Dictionary, Assessments, Subjects, Studies, Checklist, MR Scan QC, Behavioral pipeline and Templates. Also "Project List" can navigate back to the list of all the projects in the current database instance.