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


?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - NDAR Submission</title>
	</head>

	<div id="wrapper">

<?
//	require "config.php";
	require "functions.php";
//	require "includes.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$exportid = GetVariable("exportid");
	


 switch ($action) 
{
		case 'showndainfo':
			showndauploads($projectid);
			break;
		default:
			showndauploads($projectid);
			break;
}

/* ---------------------------------------------- */
/* -----------------showndauploads--------------- */
/* ---------------------------------------------- */

function showndauploads($projectid)
{
	if ((trim($projectid) == "") || ($projectid < 0)) {
		?>Invalid or blank Project ID [<?=$projectid?>]<?
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
			<div class="column">
				<div class="ui header"><?=date("D M j, Y h:ia",strtotime($submitdate))?></div>
				<div class="ui meta">
					<p><b>Requested By:</b> <?=$username?></p>
					<button class="circular ui icon button"  id='<?=$b_name?>'>
					  <i class="angle double down icon"></i>
					</button>
				</div>
			</div>
		</div>
		<table class="ui bottom attached celled grey table" id='<?=$t_name?>' hidden>
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
                            <td><?=$uid?></td>
			    <td><?="$uid$studynum"?></td>
                            <td><?=$seriesnum?> - <?=$seriesdesc?></td>
                            <td class="right aligned"><?=number_format($seriessize)?></td>
                            <td class="<?=$class?>"> <?=ucfirst($status)?></td>
                          </tr>
			 <?}?>			
		</table>
			<script>
			        // JavaScript for toggling the table
			        const <?=$b_name?> = document.getElementById('<?=$b_name?>');
			        const <?=$t_name?>= document.getElementById('<?=$t_name?>');

			        <?=$b_name?>.addEventListener('click', function() {
			            if (<?=$t_name?>.style.display === 'none' || <?=$t_name?>.style.display === '') {
			                <?=$t_name?>.style.display = 'table'; // Show the table
			            } else {
			                <?=$t_name?>.style.display = 'none'; // Hide the table
			            }
				        });
		    </script>
	<?	

	 }
}


/* --------------------------------------------- */
/* -----------------getexportid----------------- */
/* --------------------------------------------- */

function getexportid($projectid,$desttype)
{

	if ((trim($projectid) == "") || ($projectid < 0)) {
		?>Invalid or blank Project ID [<?=$projectid?>]<?
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


<? include("footer.php") ?>
