---
description: Tutorial on how to import data form a Redcap project to a NiDB project
---

# Importing Data from a Redcap Project

## Step 1

Gather the following information from Redcap administrator for API connection .&#x20;

* Redcap Server
* Redcap API Token

## Step 2

Use 'Projects' menu in NiDB to get to the desired project's main page. From Data Transfer section of the links on the right, click "Global Redcap Settings" link.

<figure><img src="../../.gitbook/assets/image (18) (1).png" alt=""><figcaption></figcaption></figure>



## Step 3

* Enter the Redcap server address and API token information and press "Update Connection Settings" button on the right as shown below.

![](<../../.gitbook/assets/image (19).png>)

## Step 4

Next steps to import the data correctly from redcap into NiDB is testing the connection, mapping each variable / field from redcap to NiDB and transferring the data. To test and established the connection with Redcap follow the following steps:



* Go to the project page and click the "Import from Redcap" link as shown below.

<figure><img src="../../.gitbook/assets/image (20).png" alt=""><figcaption></figcaption></figure>



* Click on the "Connect To Redcap" button on the right. If the connection is successful, a table with the Redcap Project information as shown below will appear.&#x20;
* Once the connection is tested, click on the<img src="../../.gitbook/assets/image (31).png" alt="" data-size="line">button to start the mapping and /or transfer data process.

<figure><img src="../../.gitbook/assets/image (21).png" alt=""><figcaption></figcaption></figure>

## Step 5

The Mapping / Transfer page will appear as shown below. This page is used to map variables or transfer data according to established mapping.

<figure><img src="../../.gitbook/assets/image (22).png" alt=""><figcaption></figcaption></figure>

To start new or edit existing mapping Click on the "Edit Mapping" button on the left as shown in the above figure. A new page will appear as shown below.



<div>

<figure><img src="../../.gitbook/assets/image (23).png" alt=""><figcaption></figcaption></figure>

 

<figure><img src="../../.gitbook/assets/image (24).png" alt=""><figcaption></figcaption></figure>

</div>

Each Redcap form is required to map separately. Pick NiDB data type and  "Redcap Form" from the drop-down list shown above.

Select a type of data that redcap form contains. NiDB handles this in three types of data, which are following:

* Measures: Redcap forms storing cognitive measures and similar other measures are stored as this data form in NiDB
* Vitals: Redcap forms  that contains information of vitals like hearth rate, blood pressure, blood test results are stored as this form of data. Also any tests that need to be done multiple times in a day can be recorded as this form.
* Drug / dose: If your project have information related to administrating drugs, this type of Redcap form is stored as Drugs / Dose in NiDB.

After choosing the Redcap "Form", a new section to map the variables from Redcap to NiDB will appear as shown in the figure below.

![](<../../.gitbook/assets/image (25).png>)

A variable mapping table has two sides: NiDB and Recap.&#x20;

#### NiDB Variable Side

The NiDB variable side contains two columns. These columns will automatically filled with the same variable and instrument names based on the Redcap choices of the form and variables. However, one can change these names. These are the names that will be stored in NiDB for corresponding Redcap Form and variable names.

#### Redcap Variable Side

This side has seven columns. Following is the explanation of each column on Redcap side.

* Event: A Redcap project can have multiple events. All the events will be listed in this column. Any number of events can be chosen from the list that is needed to map. In our example we chose only one event because the Redcap form selected tp map contain only data for that event.
* Value: Pick the Redcap variable to map from a dropdown menu list.
* Date: Pick the Redcap variable storing "date" information of the redcap form from a dropdown menu list.
* Rater: Pick the Redcap variable storing "rater" information from a dropdown menu list.
* Notes: Pick the Redcap variable storing "notes" information from a dropdown menu list.
* Start / End Time: Pick the Redcap variable storing  "start and end time" information form from a dropdown menu list.&#x20;

Defining the correct type of field is very crucial for the mapping in NiDB. Especially time and date are very important to create reports based on the information stored in NiDB.

#### Add the Mapping

After defining one variable in a form, hit "Add" button on the right to add this mapping definition.&#x20;

In case of any mistake, a mapping item can be deleted and later can be added again according to the above stated process.

After completing the mapping for a redcap form. Complete mapping the other redcap forms similarly. &#x20;

## Step 6

Before the last step it is critical to recheck all the mapping information. It is important, because the integrity, and accuracy of data transfer is based on accurate mapping. So check, recheck and make sure!

After you have done with your recheck, you are ready to transfer the data from Redcap to NiDB.&#x20;



* Go to the following Mapping / Transfer page by clicking on the<img src="../../.gitbook/assets/image (26).png" alt="" data-size="line">button from the mapping page or connection page.

<figure><img src="../../.gitbook/assets/image (27).png" alt=""><figcaption></figcaption></figure>



* Click on the "Transfer Data" button, the following screen will appear.

<figure><img src="../../.gitbook/assets/image (28).png" alt=""><figcaption></figcaption></figure>



* First select the NiDB instrument (mapped in the mapping step) to transfer the data for.
* Choose the recap event that holds the subject identification information. ![](<../../.gitbook/assets/image (29).png>)
* Next, select the Redcap variable storing the redcap unique id.![](<../../.gitbook/assets/image (30).png>)
* Select the redcap variable storing the NiDB unique id of the subjects. This is the variable that connects the records in the Redcap and NiDB.
* Click the "Transfer" button to transfer data. This may take some time and the data transfer information will be displayed after data is transferred.

#### Tips / Information&#x20;

You can complete all the mapping for the Redcap forms to be exported at once and then transfer the data one by one OR you can transfer the data of one Redcap form mapped and then go to the next forms to map and transfer.

To transfer / synchronized the data, Just press the "Transfer" button on the right  The data will be transferred / synchronized for the selected NiDB instrument.

You need to transfer the data for each mapped instrument separately by selecting them one by one.&#x20;

Reports on data can be generated by using the "Analysis Builder" tool, selection from a project's main page from "Tools" section on the right.
