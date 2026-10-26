<?
 // ------------------------------------------------------------------------------
 // NiDB clustersettings.php
 // Copyright (C) 2004 - 2026
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
	ob_start(); /* buffer output for POST/Redirect/GET (see functions.php RedirectTo/ShowFlashMessage) */
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
		$action = GetSafeAction(array("addpath", "updatepath", "deletepath", "addcluster", "updatecluster", "deletecluster")); /* mutating actions require POST + CSRF token */
		$analysisdirid = (int)GetVariable("analysisdirid");
		$nidbpath = GetVariable("nidbpath");
		$clusterpath = GetVariable("clusterpath");
		$shortname = GetVariable("shortname");
		$dirformat = GetVariable("dirformat");

		$clusterid = (int)GetVariable("clusterid");
		$clustername = GetVariable("clustername");
		$clusterdesc = GetVariable("clusterdesc");
		$clustertype = GetVariable("clustertype");
		$clusteruser = GetVariable("clusteruser");
		$clustersubmithost = GetVariable("clustersubmithost");
		$clustersubmithostuser = GetVariable("clustersubmithostuser");
		$clusterqueue = GetVariable("clusterqueue");
		
		/* determine action */
		/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-submit */
		if ($action == "editpathform") {
			DisplayPathForm("edit", $analysisdirid);
		}
		elseif ($action == "addpathform") {
			DisplayPathForm("add", "");
		}
		elseif ($action == "updatepath") {
			ob_start();
			UpdatePath($analysisdirid, $nidbpath, $clusterpath, $shortname, $dirformat);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("clustersettings.php");
		}
		elseif ($action == "addpath") {
			ob_start();
			AddPath($nidbpath, $clusterpath, $shortname, $dirformat);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("clustersettings.php");
		}
		elseif ($action == "deletepath") {
			ob_start();
			DeletePath($analysisdirid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("clustersettings.php");
		}
		elseif ($action == "editclusterform") {
			DisplayClusterForm("edit", $clusterid);
		}
		elseif ($action == "addclusterform") {
			DisplayClusterForm("add", "");
		}
		elseif ($action == "updatecluster") {
			ob_start();
			UpdateCluster($clusterid, $clustername, $clusterdesc, $clustertype, $clusteruser, $clustersubmithost, $clustersubmithostuser, $clusterqueue);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("clustersettings.php");
		}
		elseif ($action == "addcluster") {
			ob_start();
			AddCluster($clustername, $clusterdesc, $clustertype, $clusteruser, $clustersubmithost, $clustersubmithostuser, $clusterqueue);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("clustersettings.php");
		}
		elseif ($action == "deletecluster") {
			ob_start();
			DeleteCluster($clusterid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("clustersettings.php");
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
		$analysisdirid = (int)$analysisdirid;
		if ($dirformat != "uidfirst")
			$dirformat = "pipelinefirst";
		
		$sqlstring = "update analysisdirs set nidbpath = ?, clusterpath = ?, shortname = ?, dirformat = ? where analysisdir_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssi', $nidbpath, $clusterpath, $shortname, $dirformat, $analysisdirid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nidbpath, $clusterpath, $shortname, $dirformat, $analysisdirid));
		mysqli_stmt_close($stmt);
		
		Notice("Path " . htmlspecialchars($shortname ?? '') . " updated");
	}


	/* -------------------------------------------- */
	/* ------- AddPath ---------------------------- */
	/* -------------------------------------------- */
	function AddPath($nidbpath, $clusterpath, $shortname, $dirformat) {
		if ($dirformat != "uidfirst")
			$dirformat = "pipelinefirst";
		
		$sqlstring = "insert into analysisdirs (nidbpath, clusterpath, shortname, dirformat) values (?, ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssss', $nidbpath, $clusterpath, $shortname, $dirformat);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nidbpath, $clusterpath, $shortname, $dirformat));
		mysqli_stmt_close($stmt);
		
		Notice("Path " . htmlspecialchars($shortname ?? '') . " added");
	}


	/* -------------------------------------------- */
	/* ------- DeletePath ------------------------- */
	/* -------------------------------------------- */
	function DeletePath($analysisdirid) {
		$analysisdirid = (int)$analysisdirid;
		$sqlstring = "delete from analysisdirs where analysisdir_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $analysisdirid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($analysisdirid));
		mysqli_stmt_close($stmt);

		Notice("Path deleted");
	}	


	/* -------------------------------------------- */
	/* ------- UpdateCluster ---------------------- */
	/* -------------------------------------------- */
	function UpdateCluster($clusterid, $clustername, $clusterdesc, $clustertype, $clusteruser, $clustersubmithost, $clustersubmithostuser, $clusterqueue) {
		$clusterid = (int)$clusterid;
		
		$sqlstring = "update compute_cluster set cluster_name = ?, cluster_desc = ?, cluster_type = ?, submit_hostname = ?, submithost_username = ?, cluster_username = ?, queues = ? where computecluster_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssssssi', $clustername, $clusterdesc, $clustertype, $clustersubmithost, $clustersubmithostuser, $clusteruser, $clusterqueue, $clusterid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($clustername, $clusterdesc, $clustertype, $clustersubmithost, $clustersubmithostuser, $clusteruser, $clusterqueue, $clusterid));
		mysqli_stmt_close($stmt);
		
		Notice("Cluster " . htmlspecialchars($clustername ?? '') . " updated");
	}


	/* -------------------------------------------- */
	/* ------- AddCluster ------------------------- */
	/* -------------------------------------------- */
	function AddCluster($clustername, $clusterdesc, $clustertype, $clusteruser, $clustersubmithost, $clustersubmithostuser, $clusterqueue) {
		$sqlstring = "insert into compute_cluster (cluster_name, cluster_desc, cluster_type, submit_hostname, submithost_username, cluster_username, queues) values (?, ?, ?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssssss', $clustername, $clusterdesc, $clustertype, $clustersubmithost, $clustersubmithostuser, $clusteruser, $clusterqueue);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($clustername, $clusterdesc, $clustertype, $clustersubmithost, $clustersubmithostuser, $clusteruser, $clusterqueue));
		mysqli_stmt_close($stmt);
		
		Notice("Cluster " . htmlspecialchars($clustername ?? '') . " added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteCluster ---------------------- */
	/* -------------------------------------------- */
	function DeleteCluster($clusterid) {
		$clusterid = (int)$clusterid;
		$sqlstring = "delete from compute_cluster where computecluster_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $clusterid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($clusterid));
		mysqli_stmt_close($stmt);

		Notice("Cluster deleted");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayPathForm -------------------- */
	/* -------------------------------------------- */
	function DisplayPathForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$id = (int)$id;
			$sqlstring = "select * from analysisdirs where analysisdir_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$shortname = $row['shortname'] ?? '';
			$nidbpath = $row['nidbpath'] ?? '';
			$clusterpath = $row['clusterpath'] ?? '';
			$dirformat = $row['dirformat'] ?? '';
		
			$formaction = "updatepath";
			$formtitle = "Updating " . htmlspecialchars($shortname);
			$submitbuttonlabel = "Update";
		}
		else {
			$shortname = $nidbpath = $clusterpath = $dirformat = "";
			$formaction = "addpath";
			$formtitle = "Add new path";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div class="ui text container">
			<form method="post" action="clustersettings.php" class="ui form">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="analysisdirid" value="<?=(int)$id?>">
			<?=CSRFTokenField()?>

			<h2 class="ui dividing header"><?=$formtitle?></h2>

			<div class="field">
				<label>Name</label>
				<input name="shortname" value="<?=htmlspecialchars($shortname)?>">
			</div>

			<div class="field">
				<label>NiDB path <i class="question circle outline icon" title="As the path appears on the NiDB server"></i></label>
				<input type="text" id="nidbpath" name="nidbpath" value="<?=htmlspecialchars($nidbpath)?>" style="font-family: monospace">
			</div>
			
			<div class="field">
				<label>Cluster path <i class="question circle outline icon" title="As the path appears on the compute cluster"></i></label>
				<input name="clusterpath" value="<?=htmlspecialchars($clusterpath)?>" style="font-family: monospace">
			</div>

			<div class="field">
				<label>Directory structure</label>
				<div class="ui selection dropdown">
					<input type="hidden" name="dirformat" value="<?=htmlspecialchars($dirformat)?>">
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
			$id = (int)$id;
			$sqlstring = "select * from compute_cluster where computecluster_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$clustername = $row['cluster_name'] ?? '';
			$clusterdesc = $row['cluster_desc'] ?? '';
			$clustertype = $row['cluster_type'] ?? '';
			$submithost = $row['submit_hostname'] ?? '';
			$submithostuser = $row['submithost_username'] ?? '';
			$clusteruser = $row['cluster_username'] ?? '';
			$queue = $row['queues'] ?? '';
		
			$formaction = "updatecluster";
			$formtitle = "Updating " . htmlspecialchars($clustername) . " cluster";
			$submitbuttonlabel = "Update";
		}
		else {
			$clustername = $clusterdesc = $clustertype = $submithost = $submithostuser = $clusteruser = $queue = "";
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
			<input type="hidden" name="clusterid" value="<?=(int)$id?>">
			<?=CSRFTokenField()?>

			<h2 class="ui dividing header"><?=$formtitle?></h2>

			<table width="100%">
				<tr>
					<td class="label" valign="top" align="right">Cluster short name</td>
					<td valign="top">
						<div class="ui fluid input">
							<input type="text" name="clustername" value="<?=htmlspecialchars($clustername)?>" id="clustername" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Cluster description</td>
					<td valign="top">
						<div class="ui fluid input">
							<input type="text" name="clusterdesc" value="<?=htmlspecialchars($clusterdesc)?>" id="clusterdesc" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Cluster type</td>
					<td valign="top">
						<div class="ui selection fluid dropdown">
							<input type="hidden" name="clustertype" id="clustertype" value="<?=htmlspecialchars($clustertype)?>" onChange="CheckHostnameStatus()">
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
							<input type="text" name="clusteruser" value="<?=htmlspecialchars($clusteruser)?>" id="clusteruser" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Submit hostname</td>
					<td valign="top">
						<div class="ui fluid input" id="clustersubmithostinput">
							<input type="text" name="clustersubmithost" id="clustersubmithost" value="<?=htmlspecialchars($submithost)?>" onChange="CheckHostnameStatus()" onLoad="CheckHostnameStatus()">
						</div>
						<div id="hostup"></div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Submit host username</td>
					<td valign="top">
						<div class="ui fluid input">
							<input type="text" name="clustersubmithostuser" value="<?=htmlspecialchars($submithostuser)?>" id="clustersubmithostuser" onChange="CheckHostnameStatus()">
						</div>
					</td>
				</tr>
				<tr>
					<td class="label" valign="top" align="right">Queue(s)<br><span class="tiny">Comma separated list</span></td>
					<td valign="top">
						<div class="ui fluid input">
							<input type="text" name="clusterqueue" value="<?=htmlspecialchars($queue)?>" required>
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
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
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
							<td><a href="clustersettings.php?action=editpathform&analysisdirid=<?=$id?>"><?=htmlspecialchars($shortname ?? '')?></a></td>
							<td><tt><?=htmlspecialchars($nidbpath ?? '')?></tt></td>
							<td><tt><?=htmlspecialchars($clusterpath ?? '')?></tt></td>
							<td><?=htmlspecialchars($dirformat ?? '')?></td>
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
					function blankCell($val, $mono = false) {
						if ($val === '' || $val === null)
							return '<span style="color:#bbb">&mdash;</span>';
						return $mono ? '<tt>' . htmlspecialchars($val) . '</tt>' : htmlspecialchars($val);
					}
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
							<td><a href="clustersettings.php?action=editclusterform&clusterid=<?=$id?>"><?=htmlspecialchars($name)?></a><?php if ($desc !== '') echo ' <span style="color:#999;font-size:0.85em">' . htmlspecialchars($desc) . '</span>'; ?></td>
							<td><?=blankCell($type)?></td>
							<td><?=blankCell($submithostname, true)?></td>
							<td><?=blankCell($submithostusername, true)?></td>
							<td><?=blankCell($clusterusername, true)?></td>
							<td><?=blankCell($queue, true)?></td>
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
