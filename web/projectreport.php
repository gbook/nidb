<?
 // ------------------------------------------------------------------------------
 // NiDB projectreport.php
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
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Enrollment report</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";

	/* setup variables */
	$action = GetVariable("action");
	$enrollmentid = GetVariable("enrollmentid");
	$projectid = GetVariable("projectid");
	
	switch ($action) {
		case 'viewreport':
			ViewReport($enrollmentid);
			break;
		case 'viewprojectreport':
			ViewProjectReport($projectid);
			break;
		default:
			DisplayMenu();
			break;
	}
	
	
	/* ----------------------------------------------- */
	/* --------- ViewReport -------------------------- */
	/* ----------------------------------------------- */
	function ViewReport($enrollmentid) {
		
		if (($enrollmentid == "") || (!isInteger($enrollmentid))) {
			?><div class="staticmessage">Invalid or blank enrollment ID</div><?
		}
		
		/* get enrollment information */
		$sqlstring = "select a.project_id 'projectid', a.*, b.*, c.*, enroll_startdate, enroll_enddate from enrollment a left join projects b on a.project_id = b.project_id left join subjects c on a.subject_id = c.subject_id where a.enrollment_id = $enrollmentid";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$enrollmentid = $row['enrollment_id'];
		$enroll_startdate = $row['enroll_startdate'];
		$enroll_enddate = $row['enroll_enddate'];
		$enrollgroup = $row['enroll_subgroup'];
		$projectid = $row['projectid'];
		$project_name = $row['project_name'];
		$costcenter = $row['project_costcenter'];
		$project_enddate = $row['project_enddate'];
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
			
		/* check if this user has data access to this project */
		$projectaccess = 1;
		$sqlstring2 = "select view_data from user_project where project_id = $projectid and view_data = 1 and user_id in (select user_id from users where username = '" . $_SESSION['username'] . "')";
		//PrintSQL($sqlstring2);
		$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
		if (mysqli_num_rows($result2) < 1) {
			$projectaccess = 0;
			echo "You do not have permissions to view this project";
			//return;
		}
		
		?>
		<table>
			<thead>
				<tr>
					<th>Protocol Group</th>
					<th>Status</th>
				</tr>
			</thead>
		<?
		/* get the project protocol info about this project */
		$sqlstring = "select * from project_protocol a left join protocol_group b on a.protocolgroup_id = b.protocolgroup_id where a.project_id = $projectid";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projectprotocolid = $row['projectprotocol_id'];
			//$projectid = $row['project_id'];
			$protocolgroupid = $row['protocolgroup_id'];
			$criteria = $row['pp_criteria'];
			$numpersession = $row['pp_perstudyquantity'];
			$numtotal = $row['pp_perprojectquantity'];
			$name = $row['protocolgroup_name'];
			$modality = strtoupper($row['protocolgroup_modality']);
			
			?>
			<tr>
				<td valign="top"><?=$modality?> - <?=$name?></td>
			<?
			$found = 0;
			$foundinenroll = 0;
			$sqlstringA = "select * from protocolgroup_items where protocolgroup_id = $protocolgroupid";
			$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
			$count = mysqli_num_rows($resultA);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$p = $rowA['pgitem_protocol'];
				$pgitemid = $rowA['pgitem_id'];
				$modality = strtolower($modality);
				
				$sqlstringB = "select a.*, b.enrollment_id from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where a.series_desc = '$p' and c.subject_id = $subjectid";
				$resultB = MySQLiQuery($sqlstringB,__FILE__,__LINE__);
				//$count = mysqli_num_rows($resultB);
				//PrintSQL($sqlstringB);
				//echo "[count] [$count]<br>";
				while ($rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC)) {
					$seriesid = $rowB['series_id'];
					$enrollid = $rowB['enrollment_id'];
					$found = 1;
					if ($enrollid == $enrollmentid) {
						$foundinenroll = 1;
					}
					$foundlocations[$p]['enrollid'] = $enrollid;
				}
			}
			?>
				<td valign="top">
					<?
					if ($found) {
						if ($foundinenroll) {
							?><span style="color:green">&#9679;</span><?
						}
						else {
							?>
							<details>
								<summary style="color:yellow">Found</summary>
								<?
									echo "<pre>";
									print_r($foundlocations);
									echo "</pre>";
								?>
							</details>
							<?
						}
					}
					else {
						?><span style="color:red">&#9679;</span><?
					}
					?>
				</td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}

	
	/* ----------------------------------------------- */
	/* --------- ViewProjectReport ------------------- */
	/* ----------------------------------------------- */
	function ViewProjectReport($projectid) {
		
		if (($projectid == "") || (!isInteger($projectid))) {
			?><div class="staticmessage">Invalid or blank project ID</div><?
		}

		$sqlstring = "select project_name from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
		
		$urllist['Project List'] = "projects.php";
		$urllist['Project Report'] = "projectreport.php?action=viewprojectreport&projectid=$projectid";
		NavigationBar("Project report for $projectname", $urllist);
		?>
		<table class="graydisplaytable">
			<thead>
				<tr>
					<th></th>
		<?
		/* get the project protocol info about this project */
		$sqlstring2 = "select * from project_protocol a left join protocol_group b on a.protocolgroup_id = b.protocolgroup_id where a.project_id = $projectid order by protocolgroup_modality, protocolgroup_name";
		//PrintSQL($sqlstring);
		$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
		while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
			$projectprotocolid = $row2['projectprotocol_id'];
			$protocolgroupid = $row2['protocolgroup_id'];
			$name = $row2['protocolgroup_name'];
			$modality = strtoupper($row2['protocolgroup_modality']);
			?>
			<th valign="top" align="left"><?=$name?> <span style="font-weight:normal;font-size:8pt;color:gray"><?=$modality?></span></th>
			<?
		}
		?>
				</tr>
			</thead>
		<?
	
		/* get enrollment list for this project */
		$sqlstring = "select a.enrollment_id, b.* from enrollment a left join subjects b on a.subject_id = b.subject_id where a.project_id = $projectid order by b.uid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentid = $row['enrollment_id'];
			$uid = $row['uid'];
			$subjectid = $row['subject_id'];
			
			?>
				<tr>
					<td class="label"><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
			<?
			/* get enrollment information */
			$sqlstring1 = "select a.project_id 'projectid', a.*, b.*, c.*, enroll_startdate, enroll_enddate from enrollment a left join projects b on a.project_id = b.project_id left join subjects c on a.subject_id = c.subject_id where a.enrollment_id = $enrollmentid";
			//PrintSQL($sqlstring);
			$result1 = MySQLiQuery($sqlstring1, __FILE__, __LINE__);
			$row1 = mysqli_fetch_array($result1, MYSQLI_ASSOC);
			$enrollmentid = $row1['enrollment_id'];
			$enroll_startdate = $row1['enroll_startdate'];
			$enroll_enddate = $row1['enroll_enddate'];
			$enrollgroup = $row1['enroll_subgroup'];
			$projectid = $row1['projectid'];
			$project_name = $row1['project_name'];
			$costcenter = $row1['project_costcenter'];
			$project_enddate = $row1['project_enddate'];
			$uid = $row1['uid'];
			$subjectid = $row1['subject_id'];
				
			/* check if this user has data access to this project */
			$projectaccess = 1;
			$sqlstring2 = "select view_data from user_project where project_id = $projectid and view_data = 1 and user_id in (select user_id from users where username = '" . $_SESSION['username'] . "')";
			//PrintSQL($sqlstring2);
			$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
			if (mysqli_num_rows($result2) < 1) {
				$projectaccess = 0;
				echo "You do not have permissions to view this project";
				return;
			}
			/* get the project protocol info about this project */
			$sqlstring2 = "select * from project_protocol a left join protocol_group b on a.protocolgroup_id = b.protocolgroup_id where a.project_id = $projectid order by protocolgroup_modality, protocolgroup_name";
			//PrintSQL($sqlstring);
			$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
			while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
				$projectprotocolid = $row2['projectprotocol_id'];
				//$projectid = $row2['project_id'];
				$protocolgroupid = $row2['protocolgroup_id'];
				$criteria = $row2['pp_criteria'];
				$numpersession = $row2['pp_perstudyquantity'];
				$numtotal = $row2['pp_perprojectquantity'];
				$name = $row2['protocolgroup_name'];
				$modality = strtoupper($row2['protocolgroup_modality']);
				
				?>
					<!--<td valign="top"><?=$modality?> - <?=$name?></td>-->
				<?
				$found = 0;
				$foundinenroll = 0;
				$foundlocations = array();
				$rowcount = 0;
				$sqlstringA = "select * from protocolgroup_items where protocolgroup_id = $protocolgroupid";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				$count = mysqli_num_rows($resultA);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$p = $rowA['pgitem_protocol'];
					$pgitemid = $rowA['pgitem_id'];
					$modality = strtolower($modality);
					
					//$count = 0;
					$sqlstringB = "select a.*, b.enrollment_id, b.study_id, b.study_num, d.project_name from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join projects d on c.project_id = d.project_id where a.series_desc = '$p' and c.subject_id = $subjectid";
					$resultB = MySQLiQuery($sqlstringB,__FILE__,__LINE__);
					//$count = mysqli_num_rows($resultB);
					//PrintSQL($sqlstringB);
					//echo "[count] [$count]<br>";
					while ($rowB = mysqli_fetch_array($resultB, MYSQLI_ASSOC)) {
						$seriesid = $rowB['series_id'];
						$enrollid = $rowB['enrollment_id'];
						$studyid = $rowB['study_id'];
						$studynum = $rowB['study_num'];
						$projectname = $rowB['project_name'];
						$found = 1;
						if ($enrollid == $enrollmentid) {
							$foundinenroll = 1;
							$rowcount++;
						}
						$foundlocations[$p]['studyid'] = $studyid;
						$foundlocations[$p]['projectname'] = $projectname;
					}
				}

				if ($found) {
					if (($rowcount < $numtotal) && ($foundinenroll)) {
						?>
						<td valign="top" align="left" style="border-left: solid 1px #ddd; background-color: lightyellow">
						<span style="color:#D57100"><?=$rowcount?></span>
						</td>
						<?
					}
					elseif ($foundinenroll) {
						?>
						<td valign="top" align="left" style="border-left: solid 1px #ddd; background-color: #E3F7E6">
						<span style="color:green"><?=$rowcount?></span>
						</td>
						<?
					}
					else {
						?>
						<td valign="top" align="left" style="border-left: solid 1px #ddd; background-color: lightyellow">
						<details>
							<summary style="color:#D57100; font-size:8pt">Found elsewhere</summary>
								<span style="font-size:9pt">
							<?
								foreach ($foundlocations as $p => $enrollid) {
									?><?=$p?> - <?=$foundlocations['projectname']?> <a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a><?
								}
							?>
								</span>
						</details>
						</td>
						<?
					}
				}
				else {
					?>
					<td valign="top" align="left" style="border-left: solid 1px #ddd;">
					<span style="color:red"><big>&#9679;</big></span>
					</td>
					<?
				}
			}
			?></tr><?
		}
		?></table><?
	}
?>
	
<? include("footer.php") ?>
