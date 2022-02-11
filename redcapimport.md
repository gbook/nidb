# Importing Data From RedCap
You can import data from a RedCap project into a related NiDB project. Follow the following steps to import data from RedCap into NiDB.
## Pre-import Steps
To start the import data process
* RedCap Server name : Name of the RedCap server where your data to import resides. Example: https://redcap.hhchealth.org/api/
* Redcap Token: Your RedCap administrator can generate an API token to a specific RedCap project. You need this token before you start importing data from RedCap.
## Connecting to RedCap
You can import data into a particular NiDB project. To import data into a NiDb project, follow the steps below:
* Go to the project main page as shown below by selecting a project from the project list.

<div align="center"><img src="http://neuroinfodb.org/wp-content/uploads/2021/09/ProjectScreen-1024x478.png" width="80%"></div>

* Click on to "RedCap to NiDB Transfer" link encircle in red-oval as above. This will take you to the following page:

<div align="center"><img src="http://neuroinfodb.org/wp-content/uploads/2021/09/RedCap2NiDBConnectScreen-1024x498.png" width="80%"></div>

* Fill the information you gathered at the Pre-import steps and press update button.
* Click "Show Project Info" button to established RedCap to NiDB connection. If information providedis correct then you will land to the following screen

<div align="center"><img src="http://neuroinfodb.org/wp-content/uploads/2021/11/RedCap2NiDBConnEstablished-1024x332-1.png" width="80%"></div>

<div align="center"><img src="http://neuroinfodb.org/wp-content/uploads/2021/09/RedCap2NiDBConnEstablished-1024x332.png" width="80%"></div>

It will show lists of RedCap arms, events, and instruments related to the project that you connected. Please check the contents to make sure that the connected project is correct. In case the information is not correct then update the correct RedCap server and RedCap token information above. If the information is correct then:

* Click "Map The Project" button to start Mapping the Project

## Mapping
Mapping the data structures in the RedCap to NiDB is the most important step before importing the data into NiDB. Integrity and accuracy of mapping defines how accurate the data is being imported to the desired format. Map each Redcap form separately that is desired to import into NiDB.  Also define mapping for each field of RedCap form to a variable in NiDB instrument. The following is the mapping guide.

* Select a RedCap form to map from the dropdown menu and hit submit button. A new section to define the mapping of the fields of the selected form will appear. 
* The mapping section has two main parts, RedCap and NiDB. The RedCap part includes event, form, fields and field type. The NiDB side consists of type of instrument, variable name corresponding to the filed of the RedCap form and Instrument name.
* Select event, field, and define the field type (date, value, notes, rater) in the RedCap side.
* On NiDB side, select the type of NiDB data (Measure, Vital, Drug/dose), define variable name, and instrument name in the next boxes and hit add.
* Repeat the above two steps to define all the fields required to import.
* All the mapping will be saved and can be deleted later.
* If required an extra field item can be added later.
* Mapping structure need to define only once. If mapping structure is already define, go to the next step to transfer the data.

<div align="center"><img src="http://neuroinfodb.org/wp-content/uploads/2021/09/Mapping-1-1024x441.png" width="80%"></div>

## Transfer

Once the mapping data structure is defined for all the RedCap forms, start importing the data form-wise. TO start the transfer process, a unique identifier that is common in RedCap and NiDB is required. After entering the unique identifier RedCap field name, hit the "Start Transfer" button. The data will be transferred. Check the imported data for the accuracy. 

Note: The mapping step is required only once. However any defined mapping structure can be update accordingly. The data transfer can be performed anytime it is required.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/142661201-65df6319-cf6d-4a1c-9007-d5b51e794d18.png width="80%"></div>

