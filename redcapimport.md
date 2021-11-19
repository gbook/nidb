# Importing Data From RedCap
You can import data from a RedCap project into a related NiDB project. Follow the following steps to import data from RedCap into NiDB.
## Pre-import Steps
To start the import data process
* RedCap Server name : Name of the RedCap server where your data to import resides. Example: https://redcap.hhchealth.org/api/
* Redcap Token: Your RedCap administrator can generate an API token to a specific RedCap project. You need this token before you start importing data from RedCap.
## Connecting to RedCap
You can import data into a particular NiDB project. To import data into a NiDb project, follow the steps below:
* Go to the project main page as shown below by selecting a project from the project list.

<!-- wp:image {"id":270,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://neuroinfodb.org/wp-content/uploads/2021/09/ProjectScreen-1024x478.png" alt="" class="wp-image-270"/></figure>
<!-- /wp:image -->

* Click on to "RedCap to NiDB Transfer" link encircle in red-oval as above. This will take you to the following page:
<!-- wp:image {"id":271,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://neuroinfodb.org/wp-content/uploads/2021/09/RedCap2NiDBConnectScreen-1024x498.png" alt="" class="wp-image-271"/></figure>
<!-- /wp:image -->

* Fill the information you gathered at the Pre-import steps and press update button.
* Click "Show Project Info" button to established RedCap to NiDB connection. If information providedis correct then you will land to the following screen

<!-- wp:image {"id":300,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="http://neuroinfodb.org/wp-content/uploads/2021/11/RedCap2NiDBConnEstablished-1024x332-1.png" alt="" class="wp-image-300"/></figure>
<!-- /wp:image -->

<!-- wp:image {"id":272,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="http://neuroinfodb.org/wp-content/uploads/2021/09/RedCap2NiDBConnEstablished-1024x332.png" alt="" class="wp-image-272"/></figure>
<!-- /wp:image -->

It will show lists of RedCap arms, events, and instruments related to the project that you connected. Please check the contents to make sure that the connected project is correct. In case the information is not correct then update the correct RedCap server and RedCap token information above. If the information is correct then:

* Click "Map The Project" button to start Mapping the Project
