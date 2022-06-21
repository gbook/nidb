<a href="index.html">Home</a>

# Finding & Exporting Data

## Search

### Finding Imaging Data
The search page helps to find the imaging data. The following are the parts of the search page that can be used to define and refine the search.

#### Subject

There are various subsections on the search screen, those are self-explanatory. The first section is “Subject” as shown in the following figure. A search in this section can be defined based on:
1.	Subject Ids (UIDs or Alternate UIDs)
2.	Name (First or Last)
3.	Range on date of birth
4.	Range on age
5.	Sex-based 
6.	Subject group

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145576115-99ce214b-e57f-4d4a-b626-898fd94c97c1.png width="80%"></div>

#### Enrollment

The next section is enrollment where a search can be restricted based on projects. One can choose a single project or a list of projects from the drop down menu. Also a sub-group if defined can be specified.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145578915-0f5981c9-3de6-4cab-b3ba-2b1a92ab5b42.png width="80%"></div>

#### Study
In this part search parameters / variables in a project / study can be defined to refine the search. A search can be restricted based on, study Ids, Alternative study IDs, range of study date, modality (MRI,EEG, etc.), Institution (In case of multiple institutions), equipment, Physician name, Operator name, visit type, and study group

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145579800-50d85d4c-b5e3-4ee3-9e9d-0ea4ac157a68.png width="80%"></div>

#### Series
A more specific search based on protocol, MR sequence, image type. MR TR value, series number (if a specific series of images is needed) and series group can be defined. 

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145579904-3d617111-53f7-44d3-840a-318ca6bd9568.png width="80%"></div>

#### Output
In this section, the structure of the search output can be defined. The output can be grouped based on study or all the series together. The output can be stored in “.csv” file using the summary tab. The Analysis tab is used to structure the pipeline analysis results. 

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145580133-d64c64ac-1022-4a52-900f-421bcbeb506b.png width="80%"></div>

### Other Data Queries

Other than imaging data can also be quried using the similar way as mentioned above for the imaging data above. However the required non-imaging data modality can be selected from the modality dropdown menu in the study section as shown below

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145583549-1aed8be2-5c93-4523-9709-a35ac05af262.png width="80%"></div>


### ID Mapping
The Ids can be mapped using the "Data" menu from the main menu. One can go to the Id-maper page by clicking on the "ID mapper" link as shown below or by selection the ID mapper sub-menu.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145585529-ca7b6a76-8dfe-4567-96d8-c3aa0260697f.png width="80%"></div>

The following page will appear that is used to map various Ids.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145586351-09223fb0-fffe-4f3d-b1bf-bc21d3b5061e.png width="80%"></div>

A list of Ids to be mapped separated by space, tab, period, semicolon, colon, comma and newline can be typed in the box above.
the mapping can be restricted to a certain project by selecting the project name from the dropdown menu. The search can only be restricted to the current instance, undeleted subjects and exact matches  by selecting the approprriate selection box shown above. 

## Export

After searching the required data, it can be exported to various destinations. 

For this purpose a section named "Transfer & Export Data" will appear at the end of a search as shown in a fiigure below.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145590743-93e211ed-6b21-4ead-8f1a-69c29633228d.png width="80%"></div>

Following are some destinations where the searched data can be exported:

  ### Export to NFS
  To export the data to a NFS location, you can select the "Linux NFS Mount" option and type the NFS path where you want to download the data.
  <div align="center"><img src=https://user-images.githubusercontent.com/24811295/146384128-f6216f12-1cb1-44ed-b2cf-59feb3137e78.png width="80%"></div>
  
 ### Export to Remote FTP Site
  To export the data to a remote FTP location, you can select the "Remote FTP Site" option and type the FTP information where you want to download the data.
  <div align="center"><img src=https://user-images.githubusercontent.com/24811295/146385080-19df7d53-20c9-43e9-957c-ba9733bfaf4d.png width="80%"></div>
  
 ### Export to Remote NiDB Site
  To export the data to a remote NiDB site, you can select the "Remote NiDB Site" option and select the NiDB site from a drop down menue where you want to download the data.
 <div align="center"><img src=https://user-images.githubusercontent.com/24811295/146385394-22b524db-b3b3-462f-a69e-ce111adf2d82.png width="80%"></div>
 
 ### Export via Web Download
  You can select the data to be downloased to the local http location. you can select "Web http download" option for this purpose as shown below.
  <div align="center"><img src=https://user-images.githubusercontent.com/24811295/146386455-eeba0c02-6f96-46de-aa41-822a3d94f740.png width="80%"></div>
  
 ### Export to NDAR/ RDoCdb/NDA
  NiDB has a unique ability to download the data that is required to submit to NDAR/RDoC/NDA. It automatically prepares the data according to the NDAR submission requirnments. Also one can download the data inforamation in terms of .csv that is required to submit NDAR data. The following the the two options to download the data accordigly. 
  <div align="center"><img src=https://user-images.githubusercontent.com/24811295/146388142-7a8f8c02-a6a9-4d36-a74e-efbcd337fa3b.png width="80%"></div>

 ### Export status
 After starting the transfer by clicking the transfer button at the end of the search, a transfer request will be send to NiDB. The status of a request can be seen via Search-->Export Status page as shown below. The status of 30 most recent serches will be shown by default. All the previoius searches can be seen by clicking on the "Show all" button on the left corner of the screen as shown below.
 
<div align="center"><img src=https://user-images.githubusercontent.com/24811295/145592630-d61eeeb0-308d-4811-8f2c-0ad7c546522d.png width="80%"></div>


 ## Public Downloads
This is another option in the "Transfer and Export" section to transfer "searched data" and to make it as public downloadable. There are options to describe briefly about the dataset, setting up a passowrd for secure tranmitability and making the public download updateable to the users having rights on the data. One can select "Required Registration" option to restrict the dowload to NiDB users only. An expiration date for the download can be set to 7, 30 and 90 days. One should select "No Expiration" if the public data should be available for longer than 90 days or for indefinite period.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/153459944-187a7c6b-2988-4584-a9bd-f71ab9dc6fe0.png width="80%"></div>

The "public Download" will be created after pressing the "Transfer" button at the end of search page. The public downloads can be accessed via Data --> Public Download menue. The following is a a page with Public download information:

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/153461845-983a82f0-7e7b-4790-b017-49c432b81ba8.png width="80%"></div>

## Request a Dataset
To request a dataset from NiDB-based database, select data --> Request a Dataset. The following page will appear. 
<div align="center"><img src=https://user-images.githubusercontent.com/24811295/153469796-8e6069af-6b64-4deb-a9d4-79af536bc249.png width="80%"></div>

click "Submit a New Dataset Request" button, and fill the following form to request a dataset from the NiDB-databse.

<div align="center"><img src=https://user-images.githubusercontent.com/24811295/153470699-c8c34e01-cbf7-4316-9d9a-d339c01304ab.png width="80%"></div>


 
## Analysis Builder
Analysis builder is a report generating tool that can be used to generate various types of reports using the stored data in NiDB. This report generating tool builds on the base of various types of data variables that is being stored in the NiDB. This is different than the search tool where you can search different types of data and download it. In this tool you can search the variables those are generated and stored / imported in the NiDB (Example: You can query the variables generated from a task using MRI / EEG data, but not the actual EEG and MRI data).
Analysis builder can be reached via "ado2/analysisbuilder.php" or you can go to a specific project front page and select the option (Analysis Builder) on the right from "Project tool and settings" and you will land on the following screen.

<!-- wp:image {"id":286,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://neuroinfodb.org/wp-content/uploads/2021/10/AnalysisBuilderMain-1024x497.png" alt="" class="wp-image-286"/></figure>
<!-- /wp:image -->


### Usage 
Analysis builder is designed to create reports based on various types of parameters from different types of measures. It has been categorized in the various types of measures like MR, EEG, ET, etc. as shown below.

<!-- wp:image {"id":287,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="http://neuroinfodb.org/wp-content/uploads/2021/10/AnalysisBuilderFrontEnd.png" alt="" class="wp-image-287"/></figure>
<!-- /wp:image -->

After selecting the project from the drop down menu "Select Project", click "Use Project" button. Now the project  just selected will be the current project for data retrieval.

<!-- wp:image {"id":302,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://neuroinfodb.org/wp-content/uploads/2021/11/ReportLongData-1024x881.png" alt="" class="wp-image-302"/></figure>
<!-- /wp:image -->

