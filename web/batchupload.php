<?
 // ------------------------------------------------------------------------------
 // NiDB minipipeline.php
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
	$s['subjectuids'] = GetVariable("s_subjectuids");
	$s['subjectaltuids'] = GetVariable("s_subjectaltuids");
	$s['modality'] = GetVariable("s_modality");
	$s['seriesdesc'] = GetVariable("s_seriesdesc");
	
	/* determine action */
	if ($selfcall) {
		switch ($action) {
			case 'displaystudytemplatelist':
				DisplayStudyTemplateList($projectid);
				break;
			default:
				DisplayStudyTemplateList($projectid);
				DisplayProjectStudyTemplateList($projectid);
		}
	}
	else {
		DisplayBatchUploadSearchForm($projectid);
		DisplayBatchUploadResults($projectid, $s);
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
?>


<? include("footer.php") ?>
