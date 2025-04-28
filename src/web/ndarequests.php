<?
 // ------------------------------------------------------------------------------
 // NiDB adminsites.php
 // Copyright (C) 2004 - 2019
 // Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
 // Olin Neuropsychiatry Research Center, Hartford Hospital
 // ------------------------------------------------------------------------------
 // GPLv3 License:

 // This program is free software: you can redistribute it and/or modify
 // it under the terms of the GNU General Public License as published by
 // the Free Software Foundation, either version 3 of the License, or
 // (at your option) any later version.

 // This program is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 // GNU General Public License for more details.

 // You should have received a copy of the GNU General Public License
 // along with this program.  If not, see <http://www.gnu.org/licenses/>.
 // ------------------------------------------------------------------------------
	define("LEGIT_REQUEST", true);
	session_start();


//	require "config.php";
	require "functions.php";
//	require "includes.php";
	require "includes_php.php";

	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$exportid = GetVariable("exportid");
	$csvfile = GetVariable("csvfile");
	$ndaprojectnumber = GetVariable("ndaprojectnumber");
	$ndasubmissionid = GetVariable("ndasubmissionid");


 switch ($action) 
{
 case 'updatendainfo':
?>
<html>
        <head>
                <link rel="icon" type="image/png" href="images/squirrel.png">
                <title>NiDB - NDAR Submission</title>
        </head>
<body>
        <div id="wrapper">

<?
                require "includes_html.php";
                require "menu.php";
		UpdateNdaSubmission($projectid,$exportid,$ndaprojectnumber,$ndasubmissionid,$csvfile);
		showndauploads($projectid);
		break;
 case 'downloadcsv':
		DownloadCsvfile($projectid,$exportid);
                showndauploads($projectid);
                break;
 default:
?>
<html>
        <head>
                <link rel="icon" type="image/png" href="images/squirrel.png">
                <title>NiDB - NDAR Submission</title>
        </head>
<body>
        <div id="wrapper">

<?
		require "includes_html.php";
	        require "menu.php";		
		showndauploads($projectid);
		break;
}

/* ---------------------------------------------- */
/* -----------------showndauploads--------------- */
/* ---------------------------------------------- */

function showndauploads($projectid)
{
	if ((trim($projectid) == "") || ($projectid < 0)) {
		?>Invalid or blank Project ID [<? =$projectid?>]<?
		return;
	}
?>
	<div class="column">
                 <h2 class="ui header"> NDAR Submissions </h2><br>
        </div>
<?
	$Exid = getexportid($projectid,'ndar');
	$in_var = implode(',',$Exid);
	$sqlstring = "select * from exports where export_id IN ($in_var) order by export_id" ;
	$indx = 0;
        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$exportid = $row['export_id'];
                $status = ucfirst($row['status']);
                $submitdate = $row['submitdate'];
                $username = $row['username'];
                $destinationtype = $row['destinationtype'];
		$exportstatus = $row['status'];

		$indx = $indx +1;
		$b_name = 'showhide'.$indx;
		$t_name = 'details'.$indx;
?>
		<div class="ui black top attached segment">
			<div class="ui two column very compact grid">
				<div class="column">
					<div class="ui header"><? =date("D M j, Y h:ia",strtotime($submitdate))?></div>
					<div class="ui meta">
						<p><b>Requested By:</b> <? =$username?></p>
						<button class="circular ui icon button"  id='<? =$b_name?>'>
						  <i class="angle double down icon"></i>
						</button>
					</div>
				
				</div>
				<div class="right aligned column">
<?					$sqlstrsub = "SELECT ndaprojectnum, ndasubmission_id FROM project_nda_uploads WHERE project_id=$projectid and export_id=$exportid";
					$resultsub = MySQLiQuery($sqlstrsub, __FILE__, __LINE__);
					$rowsub =  mysqli_fetch_array($resultsub, MYSQLI_ASSOC);
					$ndaprojectnumber = $rowsub['ndaprojectnum'];
					$ndasubmissionid = $rowsub['ndasubmission_id'];
?>
					<form class="ui form" action="ndarequests.php" enctype="multipart/form-data" method="POST">
						<input type="hidden" name="action" value="updatendainfo">
						<input type="hidden" name="projectid" value="<? =$projectid?>">
						<input type="hidden" name="exportid" value="<? =$exportid?>">
						<div class="inline field">
							<label><b>NDA Project Number:</b></label>
							<input type="text" name="ndaprojectnumber" value="<? =$ndaprojectnumber?>">
						</div>
                	                	<div class="inline field">
						<label><b>NDA Submission Id:</b></label>
	                                       		<input type="text"  name="ndasubmissionid" value="<? =$ndasubmissionid?>">
						</div>
<?                                      $sqlstrcsv = "SELECT csv_file  FROM project_nda_uploads WHERE project_id=$projectid and export_id=$exportid";
					$resultcsv = MySQLiQuery($sqlstrcsv, __FILE__, __LINE__);
					$rowcsv =  mysqli_fetch_array($resultcsv, MYSQLI_ASSOC);
					if (!(is_null($rowcsv['csv_file']))){
?>						<div class="inline field">
							<label><b>NDA CSV File:</b></label>
							<div class="ui file action input">
							<input type="file" name="csvfile" id="csvfile" accept=".csv" >
							<label for="csvfile" class="ui button">
								 Overwrite
								<i class="file alternate"></i>
							</label>
							</div>
						</div>
						<div class="inline field">
							 <label><b>Download existing NDA CSV File:</b></label>
							<a href="ndarequests.php?action=downloadcsv&projectid=<? =$projectid?>&exportid=<? =$exportid?>">
							Download >>><i class="download icon"></i>
							</a>
						</div>
<?					}

					elseif (is_null($rowcsv['csv_file'])){
?>						<div class="inline field">
                                                        <label><b>NDA CSV File:</b></label>
							<input type="file" name="csvfile" accept=".csv">
						</div>
<?					}
?>						<div>
							<button class="ui icon large button"  type="submit" onclick="return confirm('Are you sure you want to save the changes?');"> 
		                        	                <i class="save icon"></i>
							</button>
						</div>
					</form>
                                </div>
			</div>
		</div>
		<table class="ui bottom attached celled grey table" id='<? =$t_name?>' hidden>
                  <thead>
                      <th align="left">Subject ID</th>
                      <th align="left">Study ID</th>
                      <th align="left">Series</th>
                      <th align="left">Size</th>
                      <th align="left">Status</th>
		  </thead>
<?
		$sqlstringA = "select * from exportseries where export_id = $exportid";
		$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
       		while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
          		$modality = strtolower($rowA['modality']);
              		$seriesid = $rowA['series_id'];
			$status = $rowA['status'];


       		if ($modality != "") {
			$sqlstringB = "select a.*, b.*, d.project_name, e.uid, e.subject_id from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id where a.$modality" . "series_id = $seriesid and d.project_id = $projectid order by uid, study_num, series_num";

                       	$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
                      	$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
                      	$seriesdesc = $rowB['series_desc'];
                      	if ($modality != "mr") {
                              	$seriesdesc = $rowB['series_protocol'];
                               	}
                       	$subjectid = $rowB['subject_id'];
     	              	$studyid = $rowB['study_id'];
                      	$uid = $rowB['uid'];
                 	$seriesnum = $rowB['series_num'];
                       	$studynum = $rowB['study_num'];
                      	$seriessize = $rowB['series_size'];
	      		$totalbytes += $rowB['series_size'];

               }

                        $total++;
                          switch ($status) {
                              case 'submitted': $totals['submitted']++; $class=""; break;
                              case 'processing': $totals['processing']++; $class="blue"; $bgcolor = "#526FAA"; $color="#fff"; break;
                              case 'complete': $totals['complete']++; $class="green"; $bgcolor = "#229320"; $color="#fff"; break;
                              case 'error': $totals['error']++; $class="red"; $bgcolor = "#8E3023"; $color="#fff"; break;
				}
?>
                          <tr>
                            <td><? =$uid?></td>
			    <td><? ="$uid$studynum"?></td>
                            <td><? =$seriesnum?> - <? =$seriesdesc?></td>
                            <td class="right aligned"><? =number_format($seriessize)?></td>
                            <td class="<? =$class?>"> <? =ucfirst($status)?></td>
                          </tr>
			 <?}?>			
		</table>
			<script>
			        // JavaScript for toggling the table
			        const <? =$b_name?> = document.getElementById('<? =$b_name?>');
			        const <? =$t_name?>= document.getElementById('<? =$t_name?>');

			        <? =$b_name?>.addEventListener('click', function() {
			            if (<? =$t_name?>.style.display === 'none' || <? =$t_name?>.style.display === '') {
					<? =$t_name?>.style.display = 'table'; // Show the table
			            } else {
			                <? =$t_name?>.style.display = 'none'; // Hide the table
			            }
				});

		    </script>
	<?	

	 }
}



/* -------------------------------------------- */
/* ------- Update ---------------------- */
/* -------------------------------------------- */
function UpdateNdaSubmission($projectid,$exportid,$ndaprojectnumber,$ndasubmissionid,$file)
{
	if ((trim($projectid) == "") || ($projectid < 0)) {
                ?>Invalid or blank Project ID [<? =$projectid?>]<?
                return;
	}

	if ($ndaprojectnumber == "" || $ndaprojectnumber== " "){
		$ndaprojectnumber='NULL';
	}

	if ($ndasubmissionid == "" || $ndasubmissionid == " "){
                $ndasubmissionid='NULL';
	}

	$sqltest = "select * from  project_nda_uploads WHERE project_id=$projectid and export_id=$exportid ";
	$resulttest = MySQLiQuery($sqltest, __FILE__, __LINE__);
	if (mysqli_num_rows($resulttest)>0){
		if ($_FILES["file"]["error"]== 0){
			$filecontent = base64_encode(file_get_contents($_FILES["csvfile"]["tmp_name"]));
			$sqlstringUp = "UPDATE project_nda_uploads SET ndaprojectnum=$ndaprojectnumber, ndasubmission_id =$ndasubmissionid, csv_file=NULLIF('$filecontent','')  WHERE project_id=$projectid and export_id=$exportid ";
			$resultUp = MySQLiQuery($sqlstringUp, __FILE__, __LINE__);
		}
		else{
			$sqlstringUp = "UPDATE project_nda_uploads SET ndaprojectnum=$ndaprojectnumber, ndasubmission_id =$ndasubmissionid  WHERE project_id=$projectid and export_id=$exportid ";
			$resultUp = MySQLiQuery($sqlstringUp, __FILE__, __LINE__);
		}
	}
	else {
		if ($_FILES["csvfile"]["error"]== 0){
                        $filecontent = base64_encode(file_get_contents($_FILES["csvfile"]["temp_name"]));
                        $sqlstringIn = "insert into project_nda_uploads (project_id,export_id,csv_file,ndaprojectnum,ndasubmission_id) values ($projectid, $exportid, NULLIF($filecontent,''), NULLIF('$ndaprojectnumber',''), NULLIF('$ndasubmissionid','')) on duplicate key update ndaprojectnum=$ndaprojectnumber, ndasubmission_id =$ndasubmissionid ";
                        $resultIn = MySQLiQuery($sqlstringIn, __FILE__, __LINE__);
                }
                else{
		$sqlstringIn = "insert into project_nda_uploads (project_id,export_id,ndaprojectnum,ndasubmission_id) values ($projectid, $exportid, NULLIF('$ndaprojectnumber',''), NULLIF('$ndasubmissionid','')) on duplicate key update ndaprojectnum=$ndaprojectnumber, ndasubmission_id =$ndasubmissionid ";
		$resultIn = MySQLiQuery($sqlstringIn, __FILE__, __LINE__);
		}
	}


}

/* --------------------------------------------- */
/* ------------- DownloadCsvfile---------------- */
/* --------------------------------------------- */
function DownloadCsvfile($projectid,$exportid){
	$sqltest = "select csv_file  from  project_nda_uploads WHERE project_id=$projectid and export_id=$exportid ";
	$resulttest = MySQLiQuery($sqltest, __FILE__, __LINE__);
	$rowtest =  mysqli_fetch_array($resulttest, MYSQLI_ASSOC);
	if (mysqli_num_rows($resulttest)==0 || is_null($rowtest['csv_file'])){
		$message = "CSV File does not exist";
		echo "<script type='text/javascript'>alert('$message');</script>";
	}
	else{
		$csv_contents = base64_decode($rowtest['csv_file']);
		// Extracting Project Name
		$sqlproject = "select project_name  from  projects WHERE project_id=$projectid";
	        $resultproject = MySQLiQuery($sqlproject, __FILE__, __LINE__);
		$rowproject =  mysqli_fetch_array($resultproject, MYSQLI_ASSOC);
		$projectname = $rowproject['project_name'];
		 // Extracting 
                $sqlexp = "select username,submitdate  from  exports WHERE export_id=$exportid";
                $resultexp = MySQLiQuery($sqlexp, __FILE__, __LINE__);
		$rowexp =  mysqli_fetch_array($resultexp, MYSQLI_ASSOC);
		$submitdate = $rowexp['submitdate'];
		$username = $rowexp['username'];

		$filename = $projectname.$username.$submitdate.$exportid;
		$filename = preg_replace('/[^\w]/', '', $filename);


//		echo "<script type='text/javascript'>downloadCSVFile('$filename','$csv_contents');</script>";
		// Method I to write csv file
//		file_put_contents($filename.".csv",$csv_contents);
		//echo $csv_contents;
		// MEthod 2 to write .csv file
//		$flp = fopen($filename,'w');
//		fputs($flp,$csv_contents);
//		fclose($flp);
		
		// Method 3 to write .csv file
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=$filename.csv");
		header("Content-Type: text/csv");
		header("Content-length: " . strlen($csv_contents) . "\n\n");
		header("Content-Transfer-Encoding: text");
                // Show csv Contents to the browser
                echo $csv_contents;
		

	}


}
/* --------------------------------------------- */
/* -----------------getexportid----------------- */
/* --------------------------------------------- */

function getexportid($projectid,$desttype)
{

	if ((trim($projectid) == "") || ($projectid < 0)) {
		?>Invalid or blank Project ID [<? =$projectid?>]<?
		return;
	}

	$sqlstring = "select * from exports where destinationtype = '$desttype'" ;
	$eid = []; 
        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$exportid = $row['export_id'];
                $status = ucfirst($row['status']);
                $submitdate = $row['submitdate'];
                $username = $row['username'];
                $destinationtype = $row['destinationtype'];
		$exportstatus = $row['status'];
		
		$sqlstringA = "select * from exportseries where export_id = $exportid";
		$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
       		while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
          		$modality = strtolower($rowA['modality']);
              		$seriesid = $rowA['series_id'];
			$status = $rowA['status'];


       		if ($modality != "") {
			$sqlstringB = "select a.*, b.*, d.project_name, e.uid, e.subject_id from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id left join subjects e on e.subject_id = c.subject_id where a.$modality" . "series_id = $seriesid and d.project_id = $projectid order by uid, study_num, series_num";

                       	$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
                      	$rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC);
                      	$seriesdesc = $rowB['series_desc'];
                      	if ($modality != "mr") {
                              	$seriesdesc = $rowB['series_protocol'];
                               	}
                       	$subjectid = $rowB['subject_id'];
     	              	$studyid = $rowB['study_id'];
                      	$uid = $rowB['uid'];
                 	$seriesnum = $rowB['series_num'];
                       	$studynum = $rowB['study_num'];
                      	$seriessize = $rowB['series_size'];
	      		$totalbytes += $rowB['series_size'];
			
		}
		}
			if (mysqli_num_rows($resultB) > 0 ){
                                array_push($eid, $exportid);
                        }

	}
	
        return $eid;

}

?>
 <script type="text/javascript">
	function downloadCSVFile(filename,csv_data) {

                        	    // Create CSV file object and feed
	                            // our csv_data into it
        	                   CSVFile = new Blob(['\ufeff' + csv_data], {
                	           type: "text/csv"
				});
				   alert('Working');

	                            // Create to temporary link to initiate
        	                    // download process
                	            var temp_link = document.createElement('a');

                        	    // Download csv file
	                            const d = new Date();
        	                    tt = d.getTime();
                	            temp_link.download = filename+".csv";
                        	    var url = window.URL.createObjectURL(CSVFile);
	                            temp_link.href = url;

        	                    // This link should not be displayed
                	            temp_link.style.display = "none";
                        	    document.body.appendChild(temp_link);

	                            // Automatically click the link to
        	                    // trigger download
                	            temp_link.click();
	                       	    document.body.removeChild(temp_link);
        	            }

</script>
<? include("footer.php") ?>
