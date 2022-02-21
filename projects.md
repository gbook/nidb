<a href="index.html">Home</a>

# Projects
NiDB is a multi-project database. Data from multiple projects can be managed in one database instance. Each project can have different attributes according to the needs of the project.

## Creating a Project
A user with admin rights can create, and manage a project in NiDB. A user with Admin rights will have an extra menu option "Admin". To create a new project in NiDB, click "Admin" from the main menu and then click "Projects" as shown in the figure below.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/153896038-9f2f374b-eaba-4333-890c-62af47ece2cd.png width="80%"></div>

The following page with the option "Create Project" will appear. This page also contains a list of all the current projects. To create a new project, click on the "Create Project" button on the left corner of the screen as shown in the figure below.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/153897601-4cef74fd-f0a7-40ac-9e8f-a7f9a2fbdbb1.png width="80%"></div>

On the next page, fill the following form related to the new project. Name the new project, fill the project number. Select the option "Use Custom IDs" if project need to use its own ID system. Select the Principal Investigator (PI) and project administrator (PA) from the existing NiDB users. The PI and PA can be the same subject. Mention the start and end date if they are known. Also there is an option if you want to copy an existing setting from one of your projects. 

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/154157184-0ba2665d-b014-44ce-b231-dfdd27b77d40.png width="80%"></div>

After clicking "Add" button, a new project will be added to the project list and it will be shown in the list of existing projects as shown in the figure below. 

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/154159860-50dc0c2e-f7b3-4f0d-91d1-62ce667a0c51.png width="80%"></div>

## Project Setup
To setup the project for collecting data click the name of the project on the above page and the following page can be used to add the right set of protocols.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/154161565-d6bd03bc-b0d1-4f21-9757-b0b202a62b7a.png width="80%"></div>

After adding the required set of protocols, a list of protocols will be shown as follow. A protocol can be deleted by clicking the "delete" button in front of an added protocol as shown in the figure below.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/154162653-484e7016-2ea7-4422-8e6a-c991b6463780.png  width="80%"></div>

To define a protocol, click on the name of a protocol in the above list. For example if we click on EEG-Rest, the following page will appear with the existing list of EEG-series being already used in various projects. You can pick any of those to add to your own protocol group. A group name can be assigned by using the "Protocol group name" box at the end of the page as shown. After clicking the "Add" button the selected series in the group will be added to the group and will be shown on the right.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/154166600-05780538-b187-4da1-8a46-5022ed3b6779.png width="80%"></div>

## Working With a Project
After setting up project accordingly, the project can be accessed by users having its rights. A user can access a project via "Projects" menu from the main menu. A list of existing projects will be displayed. TO search a specific project, type the name of a project and the list will reduced to the projects containing the search phrase.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/154300643-1715e992-a15e-4866-a960-2da70145fdd5.png width="80%"></div>

Click the name of the project from the list as shown above. A project specific page will appear as seen below.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/154773657-6225f1e8-9a2b-4a8a-9e53-185bba020d25.png width="80%"></div>

A project page consists of some information regarding the current project. Under the project name there is total number of subjects and studies. Undeneath that is a message box consists of number of studies. One can dismiss this message box by clicking "dismiss" button or view all the studies inside the message box. 

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/155009550-45c4937e-001f-49f7-b93b-021ffffdf5d2.png width="50%"></div>

In the middle of a project page is "Data Views" for subjects, studies, checklist for subjects and an option to QC the MR scans. 

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/155004114-15ac4229-09a7-436e-bdd1-7d4f953cd3dc.png width="30%"></div>

On the right side of the project page is a star that can be selected to make this project "favorite" that will show this project on the main page of NiDB to access it easily from there. Also there are links to the project related tools and their settings. This section is named as "Project tools & settings". This section includes:

- Data Dictionary
- Analysis Builder
- Study Templates
- BIDS Protocol Mapping
- NDA Mapping
- Behavioral Minipipelines
- Recap-> NiDB Transfer
- Reset MRI QA 

It also possess the parameters required to connect this project remotely. 

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/155005253-83266b98-f05b-4d39-8731-2c39a6dcc68b.png width="25%"></div>

The last section of a project page consists of a list of subjects registtered, with its alternate Ids, GUID, DOB, Sex, and status as shown below:

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/155011486-ce33311f-e3c2-40f0-855e-3a064b2307a3.png width="80%"></div>



The projects main-menu also has a sub-menu to navigate through various project related tools. The sub-menu includes links to Data Dictionary, Assessments, Subjects, Studies, Checklist, MR Scan QC, Behavioral pipeline and Templates. Also "Project List" can navigate back to the list of all the projects in the current database instance.

