<?
 // ------------------------------------------------------------------------------
 // NiDB pipelinesettings.php
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
		<title>NiDB - Pipeline settings</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nidbapi.php";
	
	if (!isAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$analysisdirid = GetVariable("analysisdirid");
		$nidbpath = GetVariable("nidbpath");
		$clusterpath = GetVariable("clusterpath");
		$shortname = GetVariable("shortname");
		$dirformat = GetVariable("dirformat");
		
		//PrintVariable($_POST);
		//exit(0);
		/* determine action */
		if ($action == "editform") {
			DisplayPathForm("edit", $analysisdirid);
		}
		elseif ($action == "addform") {
			DisplayPathForm("add", "");
		}
		elseif ($action == "update") {
			UpdatePath($analysisdirid, $nidbpath, $clusterpath, $shortname, $dirformat);
			DisplayPathList();
		}
		elseif ($action == "add") {
			AddPath($nidbpath, $clusterpath, $shortname, $dirformat);
			DisplayPathList();
		}
		elseif ($action == "delete") {
			DeletePath($analysisdirid);
		}
		else {
			DisplayPathList();
		}
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdatePath ------------------------- */
	/* -------------------------------------------- */
	function UpdatePath($analysisdirid, $nidbpath, $clusterpath, $shortname, $dirformat) {
		/* perform data checks */
		$analysisdirid = mysqli_real_escape_string($GLOBALS['linki'], $analysisdirid);
		$nidbpath = mysqli_real_escape_string($GLOBALS['linki'], $nidbpath);
		$clusterpath = mysqli_real_escape_string($GLOBALS['linki'], $clusterpath);
		$shortname = mysqli_real_escape_string($GLOBALS['linki'], $shortname);
		$dirformat = mysqli_real_escape_string($GLOBALS['linki'], $dirformat);
		
		/* update the site */
		$sqlstring = "update analysisdirs set nidbpath = '$nidbpath', clusterpath = '$clusterpath', shortname = '$shortname', dirformat = '$dirformat' where analysisdir_id = $analysisdirid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Path $pathname updated");
	}


	/* -------------------------------------------- */
	/* ------- AddPath ---------------------------- */
	/* -------------------------------------------- */
	function AddPath($nidbpath, $clusterpath, $shortname, $dirformat) {
		/* perform data checks */
		$nidbpath = mysqli_real_escape_string($GLOBALS['linki'], $nidbpath);
		$clusterpath = mysqli_real_escape_string($GLOBALS['linki'], $clusterpath);
		$shortname = mysqli_real_escape_string($GLOBALS['linki'], $shortname);
		$dirformat = mysqli_real_escape_string($GLOBALS['linki'], $dirformat);

		if ($dirformat != "uidfirst")
			$dirformat = "pipelinefirst";
		
		/* insert the new analysis dir */
		$sqlstring = "insert into analysisdirs (nidbpath, clusterpath, shortname, dirformat) values ('$nidbpath', '$clusterpath', '$shortname', '$dirformat')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Path $pathname added");
	}


	/* -------------------------------------------- */
	/* ------- DeletePath ------------------------- */
	/* -------------------------------------------- */
	function DeletePath($analysisdirid) {
		$sqlstring = "delete from analysisdirs where analysisdir_id = $analysisdirid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		Notice("Path added");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayPathForm -------------------- */
	/* -------------------------------------------- */
	function DisplayPathForm($type, $id) {
	
		//PrintVariable($type);
		//PrintVariable($id);
		
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from analysisdirs where analysisdir_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$shortname = $row['shortname'];
			$nidbpath = $row['nidbpath'];
			$clusterpath = $row['clusterpath'];
			$dirformat = $row['dirformat'];
		
			$formaction = "update";
			$formtitle = "Updating $shortname";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new path";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div class="ui text container">
			<form method="post" action="pipelinesettings.php" class="ui form">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="analysisdirid" value="<?=$id?>">

			<h2 class="ui dividing header"><?=$formtitle?></h2>

			<div class="field">
				<label>Name</label>
				<input name="shortname" value="<?=$shortname?>">
			</div>

			<div class="field">
				<label>NiDB path <i class="question circle outline icon" title="As the path appears on the NiDB server"></i></label>
				<input type="text" id="nidbpath" name="nidbpath" value="<?=$nidbpath?>" style="font-family: monospace">
			</div>
			
			<div class="field">
				<label>Cluster path <i class="question circle outline icon" title="As the path appears on the compute cluster"></i></label>
				<input name="clusterpath" value="<?=$clusterpath?>" style="font-family: monospace">
			</div>

			<div class="field">
				<label>Directory structure</label>
				<div class="ui selection dropdown">
					<input type="hidden" name="dirformat" value="<?=$dirformat?>">
					<i class="dropdown icon"></i>
					<div class="default text">Dir format</div>
					<div class="scrollhint menu">
						<div class="item" data-value="pipelinefirst">pipeline/S1234ABC/1</div>
						<div class="item" data-value="uidfirst">S1234ABC/1/pipeline</div>
					</div>
				</div>
			</div>

			<div class="ui two column grid">
				<div class="column">
					<a href="pipelinesettings.php" class="ui button">Cancel</a>
				</div>
				<div class="right aligned column">
					<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				</div>
			</form>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayPathList -------------------- */
	/* -------------------------------------------- */
	function DisplayPathList() {
	?>
	<div class="ui container">
		<div class="ui two column grid">
			<div class="column">
				<h1 class="ui header">Pipeline paths</h1>
			</div>
			<div class="right aligned column">
				<a href="pipelinesettings.php?action=addform" class="ui primary button"><i class="plus square icon"></i>Add Path</a>
			</div>
		</div>
		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th>Name</th>
					<th>NiDB path</th>
					<th>Cluster path</th>
					<th>Directory format</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from analysisdirs order by shortname";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['analysisdir_id'];
						$shortname = $row['shortname'];
						$nidbpath = $row['nidbpath'];
						$clusterpath = $row['clusterpath'];
						$dirformat = $row['dirformat'];
				?>
				<tr>
					<td><a href="pipelinesettings.php?action=editform&analysisdirid=<?=$id?>"><?=$shortname?></td>
					<td><tt><?=$nidbpath?></tt></td>
					<td><tt><?=$clusterpath?></tt></td>
					<td><?=$dirformat?></td>
				</tr>
				<? 
					}
				?>
			</tbody>
		</table>
	</div>
	<?
	}
?>


<? include("footer.php") ?>
