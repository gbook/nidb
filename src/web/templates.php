<?
 // ------------------------------------------------------------------------------
 // NiDB minipipeline.php
 // Copyright (C) 2004 - 2020
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
		<title>NiDB - Behavioral data analysis pipelines</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_POST);

	/* check if this page is being called from itself */
	$referringpage = $_SERVER['HTTP_REFERER'];
	$phpscriptname = pathinfo(__FILE__)['basename'];
	if (contains($referringpage, $phpscriptname))
		$selfcall = true;
	else
		$selfcall = false;

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$templateid = GetVariable("templateid");
	$projectid = GetVariable("projectid");
	$ptid = GetVariable("ptid");
	$newtemplatename = GetVariable("newtemplatename");
	$newtemplatemodality = GetVariable("newtemplatemodality");
	$visittype = GetVariable("visittype");
	$modality = GetVariable("modality");
	$protocols = GetVariable("protocols");
	$studydesc = GetVariable("studydesc");
	$studyoperator = GetVariable("studyoperator");
	$studyphysician = GetVariable("studyphysician");
	$studysite = GetVariable("studysite");
	$studynotes = GetVariable("studynotes");
	
	/* determine action */
	if ($selfcall) {
		switch ($action) {
			case 'displaystudytemplatelist':
				DisplayStudyTemplateList($projectid);
				break;
			case 'createstudytemplate':
				StudyTemplateForm($projectid, 0, $newtemplatename, $newtemplatemodality);
				break;
			case 'createprojecttemplate':
				ProjectTemplateForm($projectid, 0, $newtemplatename);
				break;
			case 'editstudytemplate':
				StudyTemplateForm($projectid, $templateid, '','');
				break;
			case 'editprojecttemplate':
				ProjectTemplateForm($projectid, $ptid, '');
				break;
			case 'updatestudytemplate':
				UpdateStudyTemplate($projectid, $templateid, $itemprotocol);
				StudyTemplateForm($projectid, $templateid, '','');
				break;
			case 'updateprojecttemplate':
				UpdateProjectTemplate($projectid, $ptid, $visittype, $modality, $protocols, $studydesc, $studyoperator, $studyphysician, $studysite, $studynotes);
				ProjectTemplateForm($projectid, $ptid, '');
				break;
			case 'deletestudytemplate':
				DeleteStudyTemplate($templateid);
				DisplayStudyTemplateList($projectid);
				break;
			case 'deleteprojecttemplate':
				DeleteProjectTemplate($ptid);
				DisplayProjectTemplateList($projectid);
				break;
			default:
				DisplayStudyTemplateList($projectid);
				DisplayProjectStudyTemplateList($projectid);
		}
	}
	else {
		DisplayStudyTemplateList($projectid);
		DisplayProjectStudyTemplateList($projectid);
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayStudyTemplateList ----------- */
	/* -------------------------------------------- */
	function DisplayStudyTemplateList($projectid) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
	
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
	
		?>
		<div style="color: #444; font-weight: bold">Study Templates</div>
		<span class="tiny">Creates <b>single</b> studies</span>
		<br><br>
		<table class="graydisplaytable">
			<thead>
				<th>Name</th>
				<th>Modality</th>
				<th></th>
			</thead>
			<form action="templates.php" method="post" name="theform" id="theform">
			<input type="hidden" name="action" value="createstudytemplate">
			<input type="hidden" name="projectid" value="<?=$projectid?>">
			<tr>
				<td><input type="text" name="newtemplatename" placeholder="Enter new template name"></td>
				<td>
					<select name="newtemplatemodality">
						<option value="">Select modality</option>
					<?
						$modalities = GetModalityList();
						foreach ($modalities as $modality) {
							?><option value="<?=$modality?>"><?=$modality?></option><?
						}
					?>
					</select>
				</td>
				<td><input type="submit" value="Create Study Template"></td>
			</tr>
			</form>
		<?
		$sqlstring = "select * from study_template where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$templateid = $row['studytemplate_id'];
			$templatename = $row['template_name'];
			$templatemodality = $row['template_modality'];
			?>
			<tr>
				<td><a href="templates.php?action=editstudytemplate&projectid=<?=$projectid?>&templateid=<?=$templateid?>"><?=$templatename?></td>
				<td><?=$templatemodality?></td>
				<td><a href="templates.php?action=deletestudytemplate&projectid=<?=$projectid?>&templateid=<?=$templateid?>" style="color: red">X</a></td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayProjectStudyTemplateList ---- */
	/* -------------------------------------------- */
	function DisplayProjectStudyTemplateList($projectid) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
	
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
	
		?>
		<br><br><br>
		<div style="color: #444; font-weight: bold">Project Study Templates</div>
		<span class="tiny">Creates <b>groups</b> of studies</span>
		<br>
		<br>
		<table class="graydisplaytable">
			<thead>
				<tr>
					<th></th>
					<th colspan="2" style="text-align: center">Number of...</th>
					<th colspan="3"></th>
				</tr>
				<tr>
					<th>Name</th>
					<th>Studies</th>
					<th>Series</th>
					<th>Create Date</th>
					<th>Modify Date</th>
					<th></th>
				</tr>
			</thead>
			<form action="templates.php" method="post" name="theform" id="theform">
			<input type="hidden" name="action" value="createprojecttemplate">
			<input type="hidden" name="projectid" value="<?=$projectid?>">
			<tr>
				<td><input type="text" name="newtemplatename" placeholder="Enter new template name"></td>
				<td></td>
				<td></td>
				<td><input type="submit" value="Create Project Template"></td>
			</tr>
			</form>
		<?
		$sqlstring = "select * from project_template where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ptid = $row['projecttemplate_id'];
			$name = $row['template_name'];
			$createdate = $row['template_createdate'];
			$modifydate = $row['template_modifydate'];
			
			$sqlstringA = "select count(*) 'count' from project_templatestudies where pt_id = $ptid";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$numstudies = $rowA['count'];
			
			$sqlstringA = "select count(*) 'count' from project_templatestudyitems where pts_id in (select pts_id from project_templatestudies where pt_id = $ptid)";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
			$numseries = $rowA['count'];
			
			?>
			<tr>
				<td><a href="templates.php?action=editprojecttemplate&projectid=<?=$projectid?>&ptid=<?=$ptid?>"><?=$name?></td>
				<td><?=$numstudies?></td>
				<td><?=$numseries?></td>
				<td><?=$createdate?></td>
				<td><?=$modifydate?></td>
				<td><a href="templates.php?action=deleteprojecttemplate&projectid=<?=$projectid?>&ptid=<?=$ptid?>" style="color: red">X</a></td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- StudyTemplateForm ------------------ */
	/* -------------------------------------------- */
	function StudyTemplateForm($projectid, $templateid, $newtemplatename, $newtemplatemodality) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$templateid = mysqli_real_escape_string($GLOBALS['linki'], $templateid);
		$newtemplatename = mysqli_real_escape_string($GLOBALS['linki'], $newtemplatename);
		$newtemplatemodality = mysqli_real_escape_string($GLOBALS['linki'], $newtemplatemodality);
	
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
	
		
		if (($templateid == "") || ($templateid == 0)) {
			$sqlstring = "insert into study_template (project_id, template_name, template_modality) values ($projectid, '$newtemplatename', '$newtemplatemodality')";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$templateid = mysqli_insert_id($GLOBALS['linki']);
			$templatename = $newtemplatename;
			$templatemodality = $newtemplatemodality;
		}
		else {
			$sqlstring = "select * from study_template where studytemplate_id = $templateid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$templatename = $row['template_name'];
			$templatemodality = $row['template_modality'];
		}
		?>
		
		<b><?=$templatename?></b> - <?=$templatemodality?>
		<br><br>
		<form action="templates.php" method="post" name="theform" id="theform">
		<input type="hidden" name="action" value="updatetemplate">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<input type="hidden" name="templateid" value="<?=$templateid?>">
		<span class="tiny">Leave protocol blank to delete</span>
		<table class="graydisplaytable">
			<thead>
				<th>Series protocol</th>
			</thead>
			<tr>
		<?
		$sqlstring = "select * from study_templateitems a left join study_template b on a.studytemplate_id = b.studytemplate_id where a.studytemplate_id = $templateid order by item_order";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$itemprotocol = $row['item_protocol'];
			?>
			<tr>
				<td><input type="text" name="itemprotocol[]" value="<?=$itemprotocol?>" size="50"></td>
			</tr>
			<?
		}
		for($i=0;$i<5;$i++) {
			?>
			<tr>
				<td><input type="text" name="itemprotocol[]" value="" size="50"></td>
			</tr>
			<?
		}
		?>
			<tr>
				<td colspan="2" align="right"><input type="submit" value="Save"></td>
			</tr>
		</table>
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ProjectTemplateForm ---------------- */
	/* -------------------------------------------- */
	function ProjectTemplateForm($projectid, $ptid, $newtemplatename) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$ptid = mysqli_real_escape_string($GLOBALS['linki'], $ptid);
		$newtemplatename = mysqli_real_escape_string($GLOBALS['linki'], $newtemplatename);
	
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['project_name'];
	
		if (($ptid == "") || ($ptid == 0)) {
			$sqlstring = "insert into project_template (project_id, template_name, template_createdate, template_modifydate) values ($projectid, '$newtemplatename', now(), now())";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$ptid = mysqli_insert_id($GLOBALS['linki']);
			$templatename = $newtemplatename;
		}
		else {
			$sqlstring = "select * from project_template where projecttemplate_id = $ptid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$templatename = $row['template_name'];
		}
		?>
		
		<b><?=$templatename?></b>
		<br><br>
		<form action="templates.php" method="post" name="theform" id="theform">
		<input type="hidden" name="action" value="updateprojecttemplate">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<input type="hidden" name="ptid" value="<?=$ptid?>">
		<span class="tiny">Leave protocol blank to delete</span>
		<table class="graydisplaytable">
			<thead>
				<th>Visit Type</th>
				<th>Modality</th>
				<th>Series<br><span class="tiny">Comma separated list of protocol names</span></th>
				<th>Description</th>
				<th>Operator</th>
				<th>Physician</th>
				<th>Site</th>
				<th>Notes</th>
			</thead>
			<tr>
		<?
		/* get a list of the study templates for this project template */
		$sqlstring = "select * from project_templatestudies where pt_id = $ptid order by pts_order asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ptsid = $row['pts_id'];
			$pts_visittype = $row['pts_visittype'];
			$pts_modality = $row['pts_modality'];

			$pts_desc = $row['pts_desc'];
			$pts_operator = $row['pts_operator'];
			$pts_physician = $row['pts_physician'];
			$pts_site = $row['pts_site'];
			$pts_notes = $row['pts_notes'];
			?>
			<tr>
				<td><input type="text" name="visittype[]" value="<?=$pts_visittype?>"></td>
				<td>
					<select name="modality[]">
						<option value="">Select modality</option>
						<?
							$modalities = GetModalityList();
							foreach ($modalities as $modality) {
								if ($modality == $pts_modality) { $checked = "selected"; } else { $checked = ""; }
								?><option value="<?=$modality?>" <?=$checked?>><?=$modality?></option><?
							}
						?>
					</select>
				</td>
				<td>
					<?
						$str = "";
						
						$sqlstringA = "select * from project_templatestudyitems where pts_id = $ptsid order by ptsitem_order asc";
						$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
						$p = array();
						while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
							$p[] = $rowA['ptsitem_protocol'];
						}
						$str = implode2(", ", $p);
					?>
					<input type="text" name="protocols[]" value="<?=$str?>" size="60">
				</td>
				<td><input type="text" name="studydesc[]" value="<?=$pts_desc?>"></td>
				<td><input type="text" name="studyoperator[]" value="<?=$pts_operator?>"></td>
				<td><input type="text" name="studyphysician[]" value="<?=$pts_physician?>"></td>
				<td><input type="text" name="studysite[]" value="<?=$pts_site?>"></td>
				<td><input type="text" name="studynotes[]" value="<?=$pts_notes?>"></td>
			</tr>
			<?
		}
		for($i=0;$i<5;$i++) {
			?>
			<tr>
				<td><input type="text" name="visittype[]" value=""></td>
				<td>
					<select name="modality[]">
						<option value="">Select modality</option>
					<?
						$modalities = GetModalityList();
						foreach ($modalities as $modality) {
							?><option value="<?=$modality?>"><?=$modality?></option><?
						}
					?>
					</select>
				</td>
				<td>
					<input type="text" name="protocols[]" size="60">
				</td>
				<td><input type="text" name="studydesc[]"></td>
				<td><input type="text" name="studyoperator[]"></td>
				<td><input type="text" name="studyphysician[]"></td>
				<td><input type="text" name="studysite[]"></td>
				<td><input type="text" name="studynotes[]"></td>
			</tr>
			<?
		}
		?>
			<tr>
				<td colspan="2" align="right"><input type="submit" value="Save"></td>
			</tr>
		</table>
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudyTemplate ---------------- */
	/* -------------------------------------------- */
	function UpdateStudyTemplate($projectid, $templateid, $itemprotocol) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$templateid = mysqli_real_escape_string($GLOBALS['linki'], $templateid);
		$itemprotocol = mysqli_real_escape_array($itemprotocol);
		
		/* start a transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete existing template items for this templateid */
		$sqlstring = "delete from study_templateitems where studytemplate_id = $templateid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$i = 0;
		foreach ($itemprotocol as $protocol) {
			if (trim($protocol) != "") {
				$sqlstring = "insert into study_templateitems (studytemplate_id, item_order, item_protocol) values ($templateid, $i, '$protocol')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$i++;
			}
		}
		
		/* commit the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- UpdateProjectTemplate -------------- */
	/* -------------------------------------------- */
	function UpdateProjectTemplate($projectid, $ptid, $visittype, $modality, $protocols, $studydesc, $studyoperator, $studyphysician, $studysite, $studynotes) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$ptid = mysqli_real_escape_string($GLOBALS['linki'], $ptid);
		$visittype = mysqli_real_escape_array($visittype);
		$modality = mysqli_real_escape_array($modality);
		$protocols = mysqli_real_escape_array($protocols);
		$studydesc = mysqli_real_escape_array($studydesc);
		$studyoperator = mysqli_real_escape_array($studyoperator);
		$studyphysician = mysqli_real_escape_array($studyphysician);
		$studysite = mysqli_real_escape_array($studysite);
		$studynotes = mysqli_real_escape_array($studynotes);
		
		/* start a transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete existing project template study items */
		$sqlstring = "delete from project_templatestudyitems where pts_id in (select pts_id from project_templatestudies where pt_id = $ptid)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* delete existing project templates studies */
		$sqlstring = "delete from project_templatestudies where pt_id = $ptid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* insert the project-template-studies */
		$i = 0;
		$studyorder = 0;
		foreach ($visittype as $vtype) {
			if (trim($vtype) != "") {
				$v = $vtype;
				$m = $modality[$i];
				$protocollist = $protocols[$i];
				
				$desc = $studydesc[$i];
				$operator = $studyoperator[$i];
				$physician = $studyphysician[$i];
				$site = $studysite[$i];
				$notes = $studynotes[$i];
				
				$sqlstring = "insert into project_templatestudies (pt_id, pts_order, pts_visittype, pts_modality, pts_desc, pts_operator, pts_physician, pts_site, pts_notes) values ($ptid, $studyorder, '$v', '$m', '$desc', '$operator', '$physician', '$site', '$notes')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$ptsid = mysqli_insert_id($GLOBALS['linki']);
				$protocolitems = explode(",", $protocollist);
				$ii = 0;
				foreach ($protocolitems as $item) {
					$item = trim($item);
					/* in case they used quotes */
					$item = str_replace('"','',$item);
					$item = str_replace('\\','',$item);
					$sqlstring = "insert into project_templatestudyitems (pts_id, ptsitem_order, ptsitem_protocol) values ($ptsid, $ii, '$item')";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$ii++;
				}
				
				$studyorder++;
			}
			$i++;
		}
		
		/* commit the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DeleteStudyTemplate ---------------- */
	/* -------------------------------------------- */
	function DeleteStudyTemplate($templateid) {
		$templateid = mysqli_real_escape_string($GLOBALS['linki'], $templateid);
		
		if ($templateid > 0) {
			$sqlstring = "delete from study_templateitems where studytemplate_id = $templateid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

			$sqlstring = "delete from study_template where studytemplate_id = $templateid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			?><span class="message">Template deleted</span><?
		}
		else {
			?><span class="message">Invalid study template ID</span><?
		}
	}
	
?>


<? include("footer.php") ?>
