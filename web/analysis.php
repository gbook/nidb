<?
 // ------------------------------------------------------------------------------
 // NiDB analysis.php
 // Copyright (C) 2004 - 2016
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
		<title>NiDB - Manage Analysis</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$analysisname = GetVariable("analysisname");
	$analysisdesc = GetVariable("analysisdesc");
	$admin = GetVariable("admin");
	
	
	/* determine action */
	if ($action == "editform") {
		DisplayAnalysisForm("edit", $id);
	}
	elseif ($action == "addform") {
		DisplayAnalysisForm("add", "");
	}
	elseif ($action == "update") {
		UpdateAnalysis($id, $analysisname, $analysisdesc, $admin);
		DisplayAnalysisList();
	}
	elseif ($action == "add") {
		AddAnalysis($analysisname, $analysisdesc, $admin);
		DisplayAnalysisList();
	}
	elseif ($action == "delete") {
		DeleteAnalysis($id);
	}
	else {
		DisplayAnalysisList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateAnalysis ---------------------- */
	/* -------------------------------------------- */
	function UpdateAnalysis($id, $analysisname, $analysisdesc, $admin) {
		/* perform data checks */
		$analysisname = mysqli_real_escape_string($analysisname);
		$analysisdesc = mysqli_real_escape_string($analysisdesc);
		
		/* update the analysis */
		$sqlstring = "update analysis set analysis_name = '$analysisname', analysis_desc = '$analysisdesc', analysis_admin = '$admin' where analysis_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$analysisname?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddAnalysis ------------------------- */
	/* -------------------------------------------- */
	function AddAnalysis($analysisname, $analysisdesc, $admin) {
		/* perform data checks */
		$analysisname = mysqli_real_escape_string($analysisname);
		$analysisdesc = mysqli_real_escape_string($analysisdesc);
		
		/* insert the new analysis */
		$sqlstring = "insert into analysis (analysis_name, analysis_desc, analysis_admin, analysis_createdate, analysis_status) values ('$analysisname', '$analysisdesc', '$admin', now(), 'active')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$analysisname?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteAnalysis --------------------- */
	/* -------------------------------------------- */
	function DeleteAnalysis($id) {
		$sqlstring = "delete from analysis where analysis_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAnalysisForm ---------------- */
	/* -------------------------------------------- */
	function DisplayAnalysisForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from analysis where analysis_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			//$id = $row['analysis_id'];
			$name = $row['analysis_name'];
			$admin = $row['analysis_admin'];
			$desc = $row['analysis_desc'];
		
			$formaction = "update";
			$formtitle = "Updating $analysisname";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new analysis";
			$submitbuttonlabel = "Add";
		}
		
		$urllist['Administration'] = "admin.php";
		$urllist['Analysis'] = "analysis.php";
		$urllist[$name] = "analysis.php?action=editform&id=$id";
		NavigationBar("Admin", $urllist);
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="analysis.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Name</td>
				<td><input type="text" name="analysisname" value="<?=$name?>"></td>
			</tr>
			<tr>
				<td>Description</td>
				<td><textarea name="analysisdesc"><?=$desc?></textarea></td>
			</tr>
			<tr>
				<td>Administrator</td>
				<td>
					<select name="admin">
						<?
							$sqlstring = "select * from users where user_enabled = true order by user_fullname";
							$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$userid = $row['user_id'];
								$username = $row['username'];
								$fullname = $row['user_fullname'];
								//echo "[$userid:$admin]";
								if ($userid == $admin) { $selected = "selected"; } else { $selected = ""; }
								?>
								<option value="<?=$userid?>" <?=$selected?>><?=$fullname?></option>
								<?
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" value="<?=$submitbuttonlabel?>">
				</td>
			</tr>
			</form>
		</table>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayAnalysisList ----------------- */
	/* -------------------------------------------- */
	function DisplayAnalysisList() {
	
		$urllist['Analysis'] = "analysis.php";
		$urllist['Pipelines'] = "pipelines.php";
		NavigationBar("Admin", $urllist);
	}
?>


<? include("footer.php") ?>
