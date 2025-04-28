<?
 // ------------------------------------------------------------------------------
 // NiDB minipipeline.php
 // Copyright (C) 2004 - 2022
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
	//PrintVariable($_GET);

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
	$itemprotocol = GetVariable("itemprotocol");
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
				DisplayProjectStudyTemplateList($projectid);
				break;
			case 'deleteprojecttemplate':
				DeleteProjectTemplate($ptid);
				DisplayStudyTemplateList($projectid);
				DisplayProjectStudyTemplateList($projectid);
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
		<div class="ui container">

			<div class="ui grid">
				<div class="six wide column">
					<h2 class="ui header">
						Study Templates
						<div class="sub header">Creates <b>single</b> studies</div>
					</h2>
				</div>
				<div class="ten wide column" align="right">
					<form action="templates.php" method="post" name="theform" id="theform">
					<input type="hidden" name="action" value="createstudytemplate">
					<input type="hidden" name="projectid" value="<?=$projectid?>">
					<div class="ui labeled action input">
						<input type="text" name="newtemplatename" placeholder="New template name" required>
						<select name="newtemplatemodality" class="ui selection dropdown" required>
							<option value="">Modality...</option>
						<?
							$modalities = GetModalityList();
							foreach ($modalities as $modality) {
								?><option value="<?=$modality?>"><?=$modality?></option><?
							}
						?>
						</select>
						<button type="submit" class="ui button primary"><i class="plus square outline icon"></i> Create Study Template</button>
					</div>
					</form>
				</div>
			</div>

			<table class="ui celled selectable grey table">
				<thead>
					<th>Name</th>
					<th>Modality</th>
				</thead>
			<?
			$sqlstring = "select * from study_template where project_id = $projectid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$templateid = $row['studytemplate_id'];
				$templatename = $row['template_name'];
				$templatemodality = $row['template_modality'];
				
				if ($templatename == "")
					$templatename = "(blank)";
				
				if ($templatemodality == "")
					$templatemodality = "(blank)";
				?>
				<tr>
					<td><a href="templates.php?action=editstudytemplate&projectid=<?=$projectid?>&templateid=<?=$templateid?>"><?=$templatename?></td>
					<td><?=$templatemodality?></td>
				</tr>
				<?
			}
			?>
			</table>
		</div>
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
		<div class="ui container">
			<div class="ui two column grid">
				<div class="column">
					<h2 class="ui header">
						Project Study Templates
						<div class="sub header">Creates <b>groups</b> of studies</div>
					</h2>
				</div>
				<div class="column" align="right">
					<form action="templates.php" method="post" name="theform" id="theform">
					<input type="hidden" name="action" value="createprojecttemplate">
					<input type="hidden" name="projectid" value="<?=$projectid?>">
					<div class="ui labeled action input">
						<input type="text" name="newtemplatename" placeholder="New template name" required>
						<button type="submit" value="Create Project Template" class="ui button primary"><i class="plus square outline icon"></i> Create Project Template</button>
					</div>
					</form>
				</div>
			</div>
			<table class="ui celled selectable grey table">
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
					</tr>
				</thead>
			<?
			$sqlstring = "select * from project_template where project_id = $projectid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$ptid = $row['projecttemplate_id'];
				$name = $row['template_name'];
				$createdate = $row['template_createdate'];
				$modifydate = $row['template_modifydate'];

				if ($name == "")
					$name = "(blank)";
				
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
				</tr>
				<?
			}
			?>
			</table>
		</div>
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
	
		//echo "function StudyTemplateForm($projectid, $templateid, $newtemplatename, $newtemplatemodality)";
		
		if (($templateid == "") || ($templateid == 0)) {
			/* check if this item name already exists */
			$sqlstring = "select * from study_template where template_name = '$newtemplatename' and project_id = $projectid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 1) {
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$templateid = $row['studytemplate_id'];
				$templatename = $row['template_name'];
				$templatemodality = $row['template_modality'];
				Notice("Template named <b>$templatename</b> already exists. Displaying that template");
			}
			else {
				$sqlstring = "insert into study_template (project_id, template_name, template_modality) values ($projectid, '$newtemplatename', '$newtemplatemodality')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$templateid = mysqli_insert_id($GLOBALS['linki']);
				$templatename = $newtemplatename;
				$templatemodality = $newtemplatemodality;
			}
		}
		else {
			$sqlstring = "select * from study_template where studytemplate_id = $templateid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$templatename = $row['template_name'];
			$templatemodality = $row['template_modality'];
		}
		?>
		
		<div class="ui text container">
			<div class="ui attached visible message">
				<div class="header"><b><?=$templatename?></b> - <?=$templatemodality?></div>
			</div>
			<form action="templates.php" method="post" name="theform" id="theform" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="updatestudytemplate">
			<input type="hidden" name="projectid" value="<?=$projectid?>">
			<input type="hidden" name="templateid" value="<?=$templateid?>">
			<span class="tiny">Leave protocol blank to delete</span>
			<table class="ui selectable grey compact table">
				<thead>
					<th>Series</th>
					<th>Protocol</th>
				</thead>
				<tr>
			<?
			$n=1;
			$sqlstring = "select * from study_templateitems a left join study_template b on a.studytemplate_id = b.studytemplate_id where a.studytemplate_id = $templateid order by item_order";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			//PrintSQLTable($result);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$itemprotocol = $row['item_protocol'];
				?>
				<tr>
					<td><?=$n++?></td>
					<td><input type="text" name="itemprotocol[]" value="<?=$itemprotocol?>" size="50"></td>
				</tr>
				<?
			}
			/* add 5 blank rows */
			for($i=0;$i<5;$i++) {
				?>
				<tr>
					<td><?=$n++?></td>
					<td><input type="text" name="itemprotocol[]" value="" size="50"></td>
				</tr>
				<?
			}
			?>
			</table>
			
			<div class="ui two column grid">
				<div class="column">
					<input type="hidden" name="username" value="<?=$username?>">
					<a class="ui red button" href="templates.php?action=deletestudytemplate&projectid=<?=$projectid?>&templateid=<?=$templateid?>"><i class="trash icon"></i>Delete</a>
				</div>
				<div class="column" align="right">
					<a class="ui button" href="templates.php?projectid=<?=$projectid?>">Back</a>
					<input type="submit" value="Save" class="ui button primary">
				</div>
			</div>
			</form>
		</div>
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
		
		<div class="ui container">
			<div class="ui top attached visible message">
			  <div class="header"><?=$templatename?></div>
			</div>

		<form action="templates.php" method="post" name="theform" id="theform" class="ui form attached fluid segment">
		<input type="hidden" name="action" value="updateprojecttemplate">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<input type="hidden" name="ptid" value="<?=$ptid?>">
		<span class="tiny">Leave protocol blank to delete</span>
		<table class="ui selectable grey very compact table">
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
		</table>
			<div class="ui two column grid">
				<div class="column">
					<input type="hidden" name="username" value="<?=$username?>">
					<a class="ui red button" href="templates.php?action=deleteprojecttemplate&projectid=<?=$projectid?>&ptid=<?=$ptid?>" onclick="return confirm('Are you sure you want to delete this template?')"><i class="trash icon"></i>Delete</a>
				</div>
				<div class="column" align="right">
					<a class="ui button" href="templates.php?projectid=<?=$projectid?>">Back</a>
					<input type="submit" value="Save" class="ui button primary">
				</div>
			</div>
		</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudyTemplate ---------------- */
	/* -------------------------------------------- */
	function UpdateStudyTemplate($projectid, $templateid, $itemprotocol) {
		//PrintVariable($itemprotocol);
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$templateid = mysqli_real_escape_string($GLOBALS['linki'], $templateid);
		$itemprotocol = mysqli_real_escape_array($GLOBALS['linki'], $itemprotocol);
		
		/* start a transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		/* delete existing template items for this templateid */
		$sqlstring = "delete from study_templateitems where studytemplate_id = $templateid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		//PrintVariable($itemprotocol);
		$i = 0;
		foreach ($itemprotocol as $protocol) {
			if (trim($protocol) != "") {
				$sqlstring = "insert into study_templateitems (studytemplate_id, item_order, item_protocol) values ($templateid, $i, '$protocol')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				$i++;
				//PrintSQL($sqlstring);
			}
		}
		
		/* commit the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		
		Notice("Study template updated");
	}


	/* -------------------------------------------- */
	/* ------- UpdateProjectTemplate -------------- */
	/* -------------------------------------------- */
	function UpdateProjectTemplate($projectid, $ptid, $visittype, $modality, $protocols, $studydesc, $studyoperator, $studyphysician, $studysite, $studynotes) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$ptid = mysqli_real_escape_string($GLOBALS['linki'], $ptid);
		$visittype = mysqli_real_escape_array($GLOBALS['linki'], $visittype);
		$modality = mysqli_real_escape_array($GLOBALS['linki'], $modality);
		$protocols = mysqli_real_escape_array($GLOBALS['linki'], $protocols);
		$studydesc = mysqli_real_escape_array($GLOBALS['linki'], $studydesc);
		$studyoperator = mysqli_real_escape_array($GLOBALS['linki'], $studyoperator);
		$studyphysician = mysqli_real_escape_array($GLOBALS['linki'], $studyphysician);
		$studysite = mysqli_real_escape_array($GLOBALS['linki'], $studysite);
		$studynotes = mysqli_real_escape_array($GLOBALS['linki'], $studynotes);
		
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

		Notice("Study project template updated");
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
			
			Notice("Template deleted");
		}
		else {
			Error("Invalid study template ID");
		}
	}


	/* -------------------------------------------- */
	/* ------- DeleteProjectTemplate -------------- */
	/* -------------------------------------------- */
	function DeleteProjectTemplate($templateid) {
		$templateid = mysqli_real_escape_string($GLOBALS['linki'], $templateid);
		
		if ($templateid > 0) {
			$sqlstring = "delete from project_templatestudyitems where pts_id = $templateid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

			$sqlstring = "delete from project_template where projecttemplate_id = $templateid";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			
			Notice("Project Template deleted");
		}
		else {
			Error("Invalid project template ID");
		}
	}
	
?>


<? include("footer.php") ?>
