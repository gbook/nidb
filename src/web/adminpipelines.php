<?
 // ------------------------------------------------------------------------------
 // NiDB adminpipelines.php
 // Copyright (C) 2004 - 2025
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
		<title>NiDB - Manage Pipelines</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	
	if (!isAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$id = GetVariable("id");
		$pipelinename = GetVariable("pipelinename");
		$pipelinedesc = GetVariable("pipelinedesc");
		$admin = GetVariable("admin");
		
		
		/* determine action */
		if ($action == "editform") {
			DisplayPipelineForm("edit", $id);
		}
		elseif ($action == "addform") {
			DisplayPipelineForm("add", "");
		}
		elseif ($action == "update") {
			UpdatePipeline($id, $pipelinename, $pipelinedesc, $admin);
			DisplayPipelineList();
		}
		elseif ($action == "add") {
			AddPipeline($pipelinename, $pipelinedesc, $admin);
			DisplayPipelineList();
		}
		elseif ($action == "delete") {
			DeletePipeline($id);
		}
		else {
			DisplayPipelineList();
		}
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdatePipeline ---------------------- */
	/* -------------------------------------------- */
	function UpdatePipeline($id, $pipelinename, $pipelinedesc, $admin) {
		/* perform data checks */
		$pipelinename = mysqli_real_escape_string($GLOBALS['linki'], $pipelinename);
		$pipelinedesc = mysqli_real_escape_string($GLOBALS['linki'], $pipelinedesc);
		
		/* update the pipeline */
		$sqlstring = "update pipelines set pipeline_name = '$pipelinename', pipeline_desc = '$pipelinedesc', pipeline_admin = '$admin' where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$pipelinename?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddPipeline ------------------------- */
	/* -------------------------------------------- */
	function AddPipeline($pipelinename, $pipelinedesc, $admin) {
		/* perform data checks */
		$pipelinename = mysqli_real_escape_string($GLOBALS['linki'], $pipelinename);
		$pipelinedesc = mysqli_real_escape_string($GLOBALS['linki'], $pipelinedesc);
		
		/* insert the new pipeline */
		$sqlstring = "insert into pipelines (pipeline_name, pipeline_desc, pipeline_admin, pipeline_createdate, pipeline_status) values ('$pipelinename', '$pipelinedesc', '$admin', now(), 'active')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$pipelinename?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeletePipeline --------------------- */
	/* -------------------------------------------- */
	function DeletePipeline($id) {
		$sqlstring = "delete from pipelines where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayPipelineForm ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from pipelines where pipeline_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			//$id = $row['pipeline_id'];
			$name = $row['pipeline_name'];
			$admin = $row['pipeline_admin'];
			$desc = $row['pipeline_desc'];
		
			$formaction = "update";
			$formtitle = "Updating $pipelinename";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new pipeline";
			$submitbuttonlabel = "Add";
		}
		
		//$urllist['Administration'] = "admin.php";
		//$urllist['Pipelines'] = "adminpipelines.php";
		//$urllist[$name] = "adminpipelines.php?action=editform&id=$id";
		//NavigationBar("Admin", $urllist);
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="adminpipelines.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Name</td>
				<td><input type="text" name="pipelinename" value="<?=$name?>"></td>
			</tr>
			<tr>
				<td>Description</td>
				<td><textarea name="pipelinedesc"><?=$desc?></textarea></td>
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
					<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				</td>
			</tr>
			</form>
		</table>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayPipelineList ----------------- */
	/* -------------------------------------------- */
	function DisplayPipelineList() {
	
		//$urllist['Administration'] = "admin.php";
		//$urllist['Pipelines'] = "adminpipelines.php";
		//$urllist['Add Pipeline'] = "adminpipelines.php?action=addform";
		//NavigationBar("Admin", $urllist);
		
	?>

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Name</th>
				<th>Creator</th>
				<th>Create Date</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select a.*, b.username 'adminusername', b.user_fullname 'adminfullname' from pipelines a left join users b on a.pipeline_admin = b.user_id where a.pipeline_status = 'active' order by a.pipeline_name";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['pipeline_id'];
					$name = $row['pipeline_name'];
					$adminusername = $row['adminusername'];
					$adminfullname = $row['adminfullname'];
					$pipeline_createdate = $row['pipeline_createdate'];
			?>
			<tr>
				<td><a href="adminpipelines.php?action=editform&id=<?=$id?>"><?=$name?></td>
				<td><?=$adminfullname?></td>
				<td><?=$pipeline_createdate?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}
?>


<? include("footer.php") ?>
