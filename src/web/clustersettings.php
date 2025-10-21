<?
 // ------------------------------------------------------------------------------
 // NiDB clustersettings.php
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
		<title>NiDB - Compute cluster settings</title>
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

		$clusterid = GetVariable("clusterid");
		$clustername = GetVariable("clustername");
		$clusterdesc = GetVariable("clusterdesc");
		$clustertype = GetVariable("clustertype");
		$clusteruser = GetVariable("clusteruser");
		$clustersubmithost = GetVariable("clustersubmithost");
		$clustersubmithostuser = GetVariable("clustersubmithostuser");
		$clusterqueue = GetVariable("clusterqueue");
		
		PrintVariable($_POST);
		
		//exit(0);
		/* determine action */
		if ($action == "editpathform") {
			DisplayPathForm("edit", $analysisdirid);
		}
		elseif ($action == "addpathform") {
			DisplayPathForm("add", "");
		}
		elseif ($action == "updatepath") {
			UpdatePath($analysisdirid, $nidbpath, $clusterpath, $shortname, $dirformat);
			DisplayClusterSettings();
		}
		elseif ($action == "addpath") {
			AddPath($nidbpath, $clusterpath, $shortname, $dirformat);
			DisplayClusterSettings();
		}
		elseif ($action == "deletepath") {
			DeletePath($analysisdirid);
		}
		elseif ($action == "editclusterform") {
			DisplayClusterForm("edit", $clusterid);
		}
		elseif ($action == "addclusterform") {
			DisplayClusterForm("add", "");
		}
		elseif ($action == "updatecluster") {
			UpdateCluster($clusterid, $clustername, $clusterdesc, $clustertype, $clusteruser, $clustersubmithost, $clustersubmithostuser, $clusterqueue);
			DisplayClusterSettings();
		}
		elseif ($action == "addcluster") {
			AddCluster($clustername, $clusterdesc, $clustertype, $clusteruser, $clustersubmithost, $clustersubmithostuser, $clusterqueue);
			DisplayClusterSettings();
		}
		elseif ($action == "deletecluster") {
			DeleteCluster($clusterid);
		}
		else {
			DisplayClusterSettings();
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
	/* ------- UpdateCluster ---------------------- */
	/* -------------------------------------------- */
	function UpdateCluster($analysisdirid, $nidbpath, $clusterpath, $shortname, $dirformat) {
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
	/* ------- AddCluster ------------------------- */
	/* -------------------------------------------- */
	function AddCluster($clustername, $clusterdesc, $clustertype, $clusteruser, $clustersubmithost, $clustersubmithostuser, $clusterqueue) {

		/* perform data checks */
		$clustername = mysqli_real_escape_string($GLOBALS['linki'], $clustername);
		$clusterdesc = mysqli_real_escape_string($GLOBALS['linki'], $clusterdesc);
		$clustertype = mysqli_real_escape_string($GLOBALS['linki'], $clustertype);
		$clusteruser = mysqli_real_escape_string($GLOBALS['linki'], $clusteruser);
		$clustersubmithost = mysqli_real_escape_string($GLOBALS['linki'], $clustersubmithost);
		$clustersubmithostuser = mysqli_real_escape_string($GLOBALS['linki'], $clustersubmithostuser);
		$clusterqueue = mysqli_real_escape_string($GLOBALS['linki'], $clusterqueue);

		/* insert the new analysis dir */
		$sqlstring = "insert into compute_cluster (cluster_name, cluster_desc, cluster_type, submit_hostname, submithost_username, cluster_username, queues) values ('$clustername', '$clusterdesc', '$clustertype', '$clustersubmithost', '$clustersubmithostuser', '$clusteruser', '$clusterqueue')";
		PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Cluster $clustername added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteCluster ---------------------- */
	/* -------------------------------------------- */
	function DeleteCluster($clusterid) {
		$sqlstring = "delete from analysisdirs where analysisdir_id = $analysisdirid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		Notice("Path added");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayPathForm -------------------- */
	/* -------------------------------------------- */
	function DisplayPathForm($type, $id) {
	
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
			<form method="post" action="clustersettings.php" class="ui form">
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
					<a href="clustersettings.php" class="ui button">Cancel</a>
				</div>
				<div class="right aligned column">
					<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				</div>
			</form>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayClusterForm ----------------- */
	/* -------------------------------------------- */
	function DisplayClusterForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from compute_cluster where computecluster_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$clustername = $row['cluster_name'];
			$clusterdesc = $row['cluster_desc'];
			$clustertype = $row['cluster_type'];
			$submithost = $row['submit_hostname'];
			$submithostuser = $row['submithost_username'];
			$clusteruser = $row['cluster_username'];
			$queue = $row['queues'];
		
			$formaction = "updatecluster";
			$formtitle = "Updating $shortname cluster";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "addcluster";
			$formtitle = "Add new cluster";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<script>
			/* check if the submit host is up (and qsub is accessible via passwordless ssh) */
			$(document).ready(function() {
				CheckHostnameStatus();
			});
		
			function CheckHostnameStatus() {
				var xhttp = new XMLHttpRequest();
				xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						var clustertype = document.getElementById("clustertype").value;
						//console.log(this.responseText);
						var retCode = this.responseText.charAt(0);
						if (retCode == "1") {
							document.getElementById("hostup").innerHTML = "<div class='ui up pointing basic label'><i class='ui green check circle icon'></i> Valid submit host</div>";
							document.getElementById("clustersubmithostinput").classList.remove('error');
						}
						else {
							errMsg = this.responseText;
							document.getElementById("hostup").innerHTML = "<div class='ui up pointing red label'><i class='ui exclamation circle icon'></i> Invalid " + clustertype + " submit host [" + errMsg + "]</div>";
							//document.getElementById("clustersubmithostinput").classList.add('error');
						}
					}
				};
				var hostname = document.getElementById("clustersubmithost").value;
				var clustertype = document.getElementById("clustertype").value;
				var submithostuser = document.getElementById("clustersubmithostuser").value;
				xhttp.open("GET", "ajaxapi.php?action=checksgehost&hostname=" + hostname + "&clustertype=" + clustertype + "&submithostuser=" + submithostuser, true);
				xhttp.send();
			}
		</script>
	
		<div class="ui text container">
			<form method="post" action="clustersettings.php" class="ui fluid form">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="clusterid" value="<?=$id?>">

			<h2 class="ui dividing header"><?=$formtitle?></h2>

			<table width="100%">
				<tr>
					<td class="label" valign="top" align="right">Cluster short name</td>
					<td valign="top">
						<div class="ui fluid input">
							<input type="text" name="clustername" value="<?=$clustername?>" id="clustername" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Cluster description</td>
					<td valign="top">
						<div class="ui fluid input">
							<input type="text" name="clusterdesc" value="<?=$clusterdesc?>" id="clusterdesc" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Cluster type</td>
					<td valign="top">
						<div class="ui selection fluid dropdown">
							<input type="hidden" name="clustertype" id="clustertype" value="<?=$clustertype?>" onChange="CheckHostnameStatus()">
							<i class="dropdown icon"></i>
							<div class="default text">Cluster...</div>
							<div class="scrollhint menu">
								<div class="item" data-value="slurm">slurm</div>
								<div class="item" data-value="sge">sge</div>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Cluster user</td>
					<td valign="top">
						<div class="ui fluid input">
							<input type="text" name="clusteruser" value="<?=$clusteruser?>" id="clusteruser" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Submit hostname</td>
					<td valign="top">
						<div class="ui fluid input" id="clustersubmithostinput">
							<input type="text" name="clustersubmithost" id="clustersubmithost" value="<?=$submithost?>" onChange="CheckHostnameStatus()" onLoad="CheckHostnameStatus()">
						</div>
						<div id="hostup"></div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Submit host username</td>
					<td valign="top">
						<div class="ui fluid input">
							<input type="text" name="clustersubmithostuser" value="<?=$submithostuser?>" id="clustersubmithostuser" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Queue(s)<br><span class="tiny">Comma separated list</span></td>
					<td valign="top">
						<div class="ui fluid input">
							<input type="text" name="clusterqueue" value="<?=$queue?>" required>
						</div>
					</td>
				</tr>
			</table>

			<br><br>
			
			<div class="ui two column grid">
				<div class="column">
					<a href="clustersettings.php" class="ui button">Cancel</a>
				</div>
				<div class="right aligned column">
					<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				</div>
			</form>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayClusterSettings ------------- */
	/* -------------------------------------------- */
	function DisplayClusterSettings() {
	?>
	<div class="ui container">
		<div class="ui two column grid">
			<div class="column">
				<h1 class="ui header">Data storage paths</h1>
			</div>
			<div class="right aligned column">
				<a href="clustersettings.php?action=addpathform" class="ui primary button"><i class="plus square icon"></i>Add storage path</a>
			</div>
			</p>
		</div>
		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th>Name</th>
					<th title="NFS path as mounted on this server">NiDB path <i class="question circle outline icon"></i></th>
					<th title="Path as seen by the compute/cluster servers">Cluster path <i class="question circle outline icon"></i></th>
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
							<td><a href="clustersettings.php?action=editpathform&analysisdirid=<?=$id?>"><?=$shortname?></td>
							<td><tt><?=$nidbpath?></tt></td>
							<td><tt><?=$clusterpath?></tt></td>
							<td><?=$dirformat?></td>
						</tr>
						<? 
					}
				?>
			</tbody>
		</table>
		
		<br><br>
		
		<div class="ui two column grid">
			<div class="column">
				<h1 class="ui header">Cluster job submission</h1>
			</div>
			<div class="right aligned column">
				<a href="clustersettings.php?action=addclusterform" class="ui primary button"><i class="plus square icon"></i>Add compute cluster</a>
			</div>
			</p>
		</div>

		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Type</th>
					<th>Submit hostname</th>
					<th>Submit username</th>
					<th>Cluster username</th>
					<th>Queues/partitions</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from compute_cluster order by cluster_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['computecluster_id'];
						$name = $row['cluster_name'];
						$desc = $row['cluster_desc'];
						$type = $row['cluster_type'];
						$submithostname = $row['submit_hostname'];
						$submithostusername = $row['submithost_username'];
						$clusterusername = $row['cluster_username'];
						$queue = $row['queues'];
						?>
						<tr>
							<td><a href="clustersettings.php?action=editclusterform&clusterid=<?=$id?>"><?=$name?></td>
							<td><?=$type?></td>
							<td><tt><?=$submithostname?></tt></td>
							<td><tt><?=$submithostusername?></tt></td>
							<td><tt><?=$clusterusername?></tt></td>
							<td><tt><?=$queue?></tt></td>
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
