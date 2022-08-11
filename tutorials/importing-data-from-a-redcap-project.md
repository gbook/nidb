---
description: Tutorial on how to import data form a Redcap project to NiDB project
---

# Importing Data from a Redcap Project

Redcap is a widely used web-based data management tool to manage clinical research data. NiDB provides an interface to import data from a Redcap project to a NiDB project. This is a very useful option in various scenarios like: migration of a project from Redcap to NiDB, OR managing data for some modalities in Redcap and rest in NiDB then merging the data in NiDB using this option.&#x20;

The following are the steps to import data from Redcap.

### Step 1

Gather the following API information from Redcap administrator to connect with the Redcap project.

* **Redcap Server**
* **Redcap API Token**

### Step 2

Use **Projects** menu in NiDB to get to the project's main page. From **Data Transfer** section on the right side of the project's main page, click **Import from Redcap** link.

![](<../.gitbook/assets/image (1).png>)

### Step 3

Enter the Redcap server address and API token information and press **Update Connection Settings** button on the right as shown below.

Then press **Show Project Info** button to establish the API connection with the Redcap server. If the connection is successful, the information from connected redcap project will be appeared as shown in the figure below.

In case of getting an error, please recheck the **Redcap Server** and **Redcap API Token** information provided above and hit the **Show Project Info** button again. If problem persists, please contact to the the Redcap administrator.

![](<../.gitbook/assets/image (2) (2).png>)

### Step 4

Next step to import the data from Redcap into NiDB is mapping each form and variable / field for redcap and NiDB.  The correct mapping is crucial to accurately import the data.

To start mapping Click on the **Map This Project** button at the end of screen on the right as shown in the above figure. A new page will appear as shown below.

![](<../.gitbook/assets/image (8).png>)

### Step 5

Each Redcap form hosting the data to import is required to map separately. Pick a Redcap **Form** from the drop-down list shown above.

Select an appropriate type of data for the selected Redcap form. In NiDB, Redcap data is defined as the following three types:

* **Measures**: Redcap forms that store measures like cognitive and other measures are defined as **Measures i**n NiDB
* **Vitals**: Redcap forms that contains information like hearth rate, blood pressure, blood test results are stored as this form of data. Also any tests that need to be done multiple times in a day will be recorded as this type.
* **Drug / Dose**: Redcap forms that store information regarding administration of drugs, will be stored as this type in NiDB.

After choosing the Redcap **Form** and its type of data in NiDB, Click on the **Redcap Field Mapping** button as shown in the figure above.

A new section to map the fields from Redcap to NiDB variables will appear as shown in the figure below.

![](<../.gitbook/assets/image (4) (2).png>)

### Step 6

The variable mapping table has two sides: Recap and NiDB.

**Redcap Variable Side**

This side has four columns. Following is the explanation of each column on Redcap side.

* **Event**: A Redcap project can have multiple events. All the events will be listed in this column. Any number of events can be chosen from the list that is needed to map. In our example we chose only one event because the Redcap form selected to map contain only data for that event.
* **Form**: Name of the Redcap form selected in the last step will be displayed here.
* **Field**: A drop-down list will list all the fields related to the selected Redcap form. Choose one field at a time to map.
* **Field Type**: There can be following five types of field:
  * **date**: Fields defined as date in Redcap.
  * **time**: Fields defined as time in Redcap
  * **notes**: Field that stores information regarding the collected data entry
  * **rater**: Field that contains the name of the rater
  * **value**: Fields containing the data value other than date, time, notes, and rater.

![](<../.gitbook/assets/image (10).png>)

In case of repeating measures like blood pressure, heart rate, blood glucose level, some cognitive measure etc. please choose **Vitals** in the **step 5** and define the field type for **time** as **time0**, **time1**, **time2**, ... and **value** as **value0**, **value1**, **value2**, ... as depicted in the figure above.

Defining the correct type of field is very important for the importing data into NiDB. Especially value, time and date are very important to define correctly to create the valid reports based on the information imported into NiDB.

**NiDB Variable Side**

The NiDB variable side contains three columns. These columns will automatically filled with the same **Variable** and **Instrument / Form** names based on the Redcap side. However, one can change these names. These are the names of variables and Redcap forms that will be stored in NiDB for mapping these items for all the later imports.

After defining one variable in a form, hit **Add** button on the right to add this mapping definition.

In case of any mistake, a mapping item can be deleted and later can be added again according to the above stated process.

The mapping of each variable is a painstaking task. However this is very important step in mapping Redcap variables into NiDB and need to be done only once. Once the structure is defined, it will be stored in NiDB and imports for further data can be performed with the click of a button.

**However, this mapping needs to be updated in case the structure of the corresponding redcap project is changed.**

### Step 7

The last step is to recheck all the mapping information. It is important, because the integrity, and accuracy of data transfer is based on accurate mapping. So check, recheck and make sure!

After you have done with your mapping, you are ready to transfer the data from Redcap to NiDB.

You can complete the mapping for the all the Redcap forms to be exported first and then transfer the data one by one OR you can transfer the data of one form and then go to the next to map and transfer.

To transfer the data, press the **Start Transfer** button on the left at the end of the variable mapping table. The data will transfer for the selected form.

You need to transfer the data for each mapped form separately by selecting it as mentioned in the step 4 above.

Reports on data can be generated by using the [Analysis Builder](analysis-builder.md) tool.
