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
		$action = GetSafeAction(array("addpath", "updatepath", "deletepath", "addcluster", "updatecluster", "deletecluster", "addnode", "updatenode", "deletenode", "setnodeenabled")); /* mutating actions require POST + CSRF token */
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

		$nodeid = (int)GetVariable("nodeid");
		$nodehostname = trim(GetVariable("nodehostname") ?? '');
		$nodesshuser = trim(GetVariable("nodesshuser") ?? '');
		$nodedisplayname = trim(GetVariable("nodedisplayname") ?? '');
		$nodedesc = trim(GetVariable("nodedesc") ?? '');
		$nodeenabled = (GetVariable("nodeenabled") == "1") ? 1 : 0;
		
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
		elseif ($action == "managenodes") {
			DisplayManageNodes($clusterid);
		}
		elseif ($action == "editnodeform") {
			DisplayNodeForm("edit", $nodeid, $clusterid);
		}
		elseif ($action == "addnodeform") {
			DisplayNodeForm("add", 0, $clusterid);
		}
		elseif ($action == "updatenode") {
			ob_start();
			UpdateNode($nodeid, $nodehostname, $nodesshuser, $nodedisplayname, $nodedesc);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(ManageNodesURL(GetNodeClusterID($nodeid)));
		}
		elseif ($action == "addnode") {
			ob_start();
			AddNode($clusterid, $nodehostname, $nodesshuser, $nodedisplayname, $nodedesc, $nodeenabled);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(ManageNodesURL($clusterid));
		}
		elseif ($action == "setnodeenabled") {
			ob_start();
			SetNodeEnabled($nodeid, $nodeenabled);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(ManageNodesURL(GetNodeClusterID($nodeid)));
		}
		elseif ($action == "deletenode") {
			$nodeclusterid = GetNodeClusterID($nodeid); /* before the node is deleted */
			ob_start();
			DeleteNode($nodeid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(ManageNodesURL($nodeclusterid));
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

		/* a cluster with monitored nodes can't be deleted. The nodes (and their history) must be deleted first */
		$sqlstring = "select count(*) 'count' from cluster_nodes where computecluster_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $clusterid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($clusterid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$numnodes = (int)($row['count'] ?? 0);
		if ($numnodes > 0) {
			Error("This cluster has $numnodes monitored node(s). Delete the nodes before deleting the cluster.");
			return;
		}

		$sqlstring = "delete from compute_cluster where computecluster_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $clusterid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($clusterid));
		mysqli_stmt_close($stmt);

		Notice("Cluster deleted");
	}	


	/* -------------------------------------------- */
	/* ------- ClusterDisplayName ----------------- */
	/* -------------------------------------------- */
	/* escaped cluster name for display. Blank names (left from old testing) are shown as (blank) so they can still be clicked and managed */
	function ClusterDisplayName($name) {
		if (trim($name ?? '') == '')
			return '<span style="color:#999; font-style:italic">(blank)</span>';
		return htmlspecialchars($name);
	}


	/* -------------------------------------------- */
	/* ------- GetNodeClusterID ------------------- */
	/* -------------------------------------------- */
	/* cluster ID of a node, or 0 if not found */
	function GetNodeClusterID($nodeid) {
		$nodeid = (int)$nodeid;
		$sqlstring = "select computecluster_id from cluster_nodes where clusternode_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $nodeid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nodeid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return (int)($row['computecluster_id'] ?? 0);
	}


	/* -------------------------------------------- */
	/* ------- ManageNodesURL --------------------- */
	/* -------------------------------------------- */
	/* the node list for a cluster, or the main page if there's no cluster */
	function ManageNodesURL($clusterid) {
		$clusterid = (int)$clusterid;
		return ($clusterid > 0) ? "clustersettings.php?action=managenodes&clusterid=$clusterid" : "clustersettings.php";
	}


	/* -------------------------------------------- */
	/* ------- ValidateNode ----------------------- */
	/* -------------------------------------------- */
	/* hostnames and usernames are passed to ssh by the cluster module, so they must not look like ssh options.
	   Returns an error message, or "" if valid */
	function ValidateNode($hostname, $sshuser) {
		if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9.\-]*$/', $hostname))
			return "Invalid hostname. Use letters, numbers, dots and dashes, starting with a letter or number";
		if (strlen($hostname) > 255)
			return "Hostname is too long";
		if (($sshuser != "") && !preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.\-]*$/', $sshuser))
			return "Invalid ssh username";
		return "";
	}


	/* -------------------------------------------- */
	/* ------- NodeHostExists --------------------- */
	/* -------------------------------------------- */
	/* true if another node in the same cluster already has this hostname */
	function NodeHostExists($clusterid, $hostname, $excludenodeid) {
		$sqlstring = "select clusternode_id from cluster_nodes where computecluster_id = ? and hostname = ? and clusternode_id <> ?";
		$params = array((int)$clusterid, $hostname, (int)$excludenodeid);
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'isi', ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$exists = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		return $exists;
	}


	/* -------------------------------------------- */
	/* ------- AddNode ---------------------------- */
	/* -------------------------------------------- */
	function AddNode($clusterid, $hostname, $sshuser, $displayname, $desc, $enabled) {
		$clusterid = (int)$clusterid;

		$err = ValidateNode($hostname, $sshuser);
		if ($err != "") { Error(htmlspecialchars($err)); return; }

		$sqlstring = "select computecluster_id from compute_cluster where computecluster_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $clusterid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($clusterid));
		$found = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		if (!$found) { Error("Cluster not found"); return; }

		if (NodeHostExists($clusterid, $hostname, 0)) { Error("Node " . htmlspecialchars($hostname) . " is already in this cluster"); return; }

		$sshuser = ($sshuser == "") ? null : $sshuser;
		$displayname = ($displayname == "") ? null : $displayname;
		$sqlstring = "insert into cluster_nodes (computecluster_id, hostname, ssh_username, display_name, node_desc, enabled) values (?, ?, ?, ?, ?, ?)";
		$params = array($clusterid, $hostname, $sshuser, $displayname, $desc, (int)$enabled);
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'issssi', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice("Node " . htmlspecialchars($hostname) . " added. Its status will show after the next check (within a minute)");
	}


	/* -------------------------------------------- */
	/* ------- UpdateNode ------------------------- */
	/* -------------------------------------------- */
	/* enabled/disabled is set separately, by SetNodeEnabled() */
	function UpdateNode($nodeid, $hostname, $sshuser, $displayname, $desc) {
		$nodeid = (int)$nodeid;

		$err = ValidateNode($hostname, $sshuser);
		if ($err != "") { Error(htmlspecialchars($err)); return; }

		$sqlstring = "select computecluster_id, hostname, ssh_username from cluster_nodes where clusternode_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $nodeid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nodeid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) { Error("Node not found"); return; }

		if (NodeHostExists($row['computecluster_id'], $hostname, $nodeid)) { Error("Node " . htmlspecialchars($hostname) . " is already in this cluster"); return; }

		$sshuser = ($sshuser == "") ? null : $sshuser;
		$displayname = ($displayname == "") ? null : $displayname;

		/* a different host or user is a different machine (or login), so its counters can't be compared with the old ones */
		if (($row['hostname'] != $hostname) || (($row['ssh_username'] ?? null) !== $sshuser))
			$sqlstring = "update cluster_nodes set hostname = ?, ssh_username = ?, display_name = ?, node_desc = ?, last_counters = null, last_status = null, last_error = null where clusternode_id = ?";
		else
			$sqlstring = "update cluster_nodes set hostname = ?, ssh_username = ?, display_name = ?, node_desc = ? where clusternode_id = ?";
		$params = array($hostname, $sshuser, $displayname, $desc, $nodeid);
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssi', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		Notice("Node " . htmlspecialchars($hostname) . " updated");
	}


	/* -------------------------------------------- */
	/* ------- SetNodeEnabled --------------------- */
	/* -------------------------------------------- */
	/* a disabled node is skipped by the cluster module (no ssh checks). Its history is kept */
	function SetNodeEnabled($nodeid, $enabled) {
		$nodeid = (int)$nodeid;
		$enabled = ($enabled) ? 1 : 0;

		$sqlstring = "select hostname from cluster_nodes where clusternode_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $nodeid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nodeid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) { Error("Node not found"); return; }

		$sqlstring = "update cluster_nodes set enabled = ? where clusternode_id = ?";
		$params = array($enabled, $nodeid);
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		if ($enabled)
			Notice("Node " . htmlspecialchars($row['hostname']) . " enabled. It will be checked within a minute");
		else
			Notice("Node " . htmlspecialchars($row['hostname']) . " disabled. It will no longer be checked; its history is kept");
	}


	/* -------------------------------------------- */
	/* ------- DeleteNode ------------------------- */
	/* -------------------------------------------- */
	/* deletes the node and its monitoring history */
	function DeleteNode($nodeid) {
		$nodeid = (int)$nodeid;

		$sqlstring = "delete from cluster_node_samples where clusternode_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $nodeid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nodeid));
		mysqli_stmt_close($stmt);

		$sqlstring = "delete from cluster_nodes where clusternode_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $nodeid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nodeid));
		mysqli_stmt_close($stmt);

		Notice("Node deleted");
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
		
			/* number of monitored nodes, since a cluster with nodes can't be deleted */
			$sqlstring = "select count(*) 'count' from cluster_nodes where computecluster_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
			$crow = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$numnodes = (int)($crow['count'] ?? 0);

			$formaction = "updatecluster";
			$formtitle = "Updating " . ClusterDisplayName($clustername) . " cluster";
			$submitbuttonlabel = "Update";
		}
		else {
			$clustername = $clusterdesc = $clustertype = $submithost = $submithostuser = $clusteruser = $queue = "";
			$numnodes = 0;
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
					<? if ($type == "edit") { ?>
						<? if ($numnodes > 0) { ?>
							<!-- a cluster with monitored nodes can't be deleted (DeleteCluster() also checks) -->
							<span title="This cluster has <?=$numnodes?> monitored node(s). Delete them before deleting the cluster" style="display:inline-block">
								<button type="button" class="ui red basic disabled button"><i class="trash alternate icon"></i> Delete cluster</button>
							</span>
						<? } else { ?>
							<button type="submit" form="deleteclusterform" class="ui red basic button"><i class="trash alternate icon"></i> Delete cluster</button>
						<? } ?>
					<? } ?>
				</div>
				<div class="right aligned column">
					<a href="clustersettings.php" class="ui button">Cancel</a>
					<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				</div>
			</div>
			</form>

			<? if (($type == "edit") && ($numnodes == 0)) { ?>
			<!-- separate form, since forms can't be nested. The Delete button above submits it with its form attribute -->
			<form method="post" action="clustersettings.php" id="deleteclusterform" onSubmit="return confirm('Delete this cluster?')">
				<input type="hidden" name="action" value="deletecluster">
				<input type="hidden" name="clusterid" value="<?=(int)$id?>">
				<?=CSRFTokenField()?>
			</form>
			<? } ?>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayNodeForm -------------------- */
	/* -------------------------------------------- */
	function DisplayNodeForm($type, $nodeid, $clusterid) {
		$nodeid = (int)$nodeid;
		$clusterid = (int)$clusterid;

		$hostname = $sshuser = $displayname = $desc = "";
		$enabled = 1;
		if ($type == "edit") {
			$sqlstring = "select * from cluster_nodes where clusternode_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $nodeid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($nodeid));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) { Error("Node not found"); return; }
			$clusterid = (int)$row['computecluster_id'];
			$hostname = $row['hostname'] ?? '';
			$sshuser = $row['ssh_username'] ?? '';
			$displayname = $row['display_name'] ?? '';
			$desc = $row['node_desc'] ?? '';
			$enabled = (int)$row['enabled'];
		}

		/* the cluster, for its name and default ssh username */
		$sqlstring = "select cluster_name, submithost_username from compute_cluster where computecluster_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $clusterid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($clusterid));
		$crow = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$crow) { Error("Cluster not found"); return; }
		$clustername = $crow['cluster_name'] ?? '';
		$defaultuser = $crow['submithost_username'] ?? '';

		if ($type == "edit") {
			$formaction = "updatenode";
			$formtitle = "Updating node " . htmlspecialchars($hostname);
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "addnode";
			$formtitle = "Add node to " . ClusterDisplayName($clustername);
			$submitbuttonlabel = "Add";
		}
		$exampleuser = ($sshuser != "") ? $sshuser : (($defaultuser != "") ? $defaultuser : "user");
		$examplehost = ($hostname != "") ? $hostname : "node01";
	?>
		<div class="ui text container">
			<form method="post" action="clustersettings.php" class="ui form">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="nodeid" value="<?=$nodeid?>">
			<input type="hidden" name="clusterid" value="<?=$clusterid?>">
			<?=CSRFTokenField()?>

			<h2 class="ui dividing header"><?=$formtitle?><div class="sub header">Cluster <?=ClusterDisplayName($clustername)?></div></h2>

			<div class="required field">
				<label>Hostname</label>
				<input type="text" name="nodehostname" value="<?=htmlspecialchars($hostname)?>" style="font-family: monospace" required pattern="[A-Za-z0-9][A-Za-z0-9.\-]*" maxlength="255">
			</div>

			<div class="field">
				<label>ssh username</label>
				<input type="text" name="nodesshuser" value="<?=htmlspecialchars($sshuser)?>" style="font-family: monospace" pattern="[A-Za-z0-9_][A-Za-z0-9_.\-]*" placeholder="<?=htmlspecialchars($defaultuser)?>">
				<div class="ui small grey text">Leave blank to use the cluster's submit host username<?=($defaultuser != "") ? " (<tt>" . htmlspecialchars($defaultuser) . "</tt>)" : ""?></div>
			</div>

			<div class="field">
				<label>Display name</label>
				<input type="text" name="nodedisplayname" value="<?=htmlspecialchars($displayname)?>" maxlength="255" placeholder="Same as hostname">
			</div>

			<div class="field">
				<label>Description</label>
				<input type="text" name="nodedesc" value="<?=htmlspecialchars($desc)?>">
			</div>

			<? if ($type == "add") { ?>
			<div class="field">
				<div class="ui checkbox">
					<input type="checkbox" name="nodeenabled" value="1" <?=($enabled) ? "checked" : ""?>>
					<label>Enabled (checked every minute)</label>
				</div>
			</div>
			<? } else { ?>
			<div class="field">
				<label>Monitoring</label>
				<? if ($enabled) { ?>
					<i class="check circle icon" style="color:#0ca30c"></i>Enabled. This node is checked every minute
				<? } else { ?>
					<i class="minus circle icon" style="color:#9e9d98"></i>Disabled. This node is not being checked. Use the Enable button below to resume checking
				<? } ?>
			</div>
			<? } ?>

			<div class="ui info message">
				<div class="header">Passwordless ssh is required</div>
				<p>The <tt>cluster</tt> module connects to the node every minute as the <tt>nidb</tt> user on this server. To set it up, log in to this server as <tt>nidb</tt> and run:</p>
				<pre style="margin: 0">ssh-copy-id <?=htmlspecialchars($exampleuser)?>@<?=htmlspecialchars($examplehost)?>
ssh -o BatchMode=yes <?=htmlspecialchars($exampleuser)?>@<?=htmlspecialchars($examplehost)?> true</pre>
				<p>The second command should finish without asking for a password. Only <tt>/proc</tt> is read on the node; nothing is installed.</p>
			</div>

			<div class="ui two column grid">
				<div class="column">
					<a href="<?=ManageNodesURL($clusterid)?>" class="ui button">Cancel</a>
				</div>
				<div class="right aligned column">
					<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				</div>
			</div>
			</form>

			<? if ($type == "edit") { ?>
			<div class="ui divider"></div>
			<form method="post" action="clustersettings.php" style="display:inline-block">
				<input type="hidden" name="action" value="setnodeenabled">
				<input type="hidden" name="nodeid" value="<?=$nodeid?>">
				<input type="hidden" name="nodeenabled" value="<?=($enabled) ? "0" : "1"?>">
				<?=CSRFTokenField()?>
				<? if ($enabled) { ?>
				<button type="submit" class="ui basic button" title="Stop checking this node. Its settings and monitoring history are kept"><i class="pause circle icon"></i> Disable node</button>
				<? } else { ?>
				<button type="submit" class="ui green basic button" title="Resume checking this node every minute"><i class="play circle icon"></i> Enable node</button>
				<? } ?>
			</form>
			<form method="post" action="clustersettings.php" style="display:inline-block" onSubmit="return confirm('Delete node <?=htmlspecialchars(addslashes($hostname))?> and all of its monitoring history?')">
				<input type="hidden" name="action" value="deletenode">
				<input type="hidden" name="nodeid" value="<?=$nodeid?>">
				<?=CSRFTokenField()?>
				<button type="submit" class="ui red basic button"><i class="trash alternate icon"></i> Delete node</button>
			</form>
			<? } ?>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayManageNodes ----------------- */
	/* -------------------------------------------- */
	/* the monitored nodes of one cluster */
	function DisplayManageNodes($clusterid) {
		$clusterid = (int)$clusterid;

		$sqlstring = "select cluster_name, submithost_username from compute_cluster where computecluster_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $clusterid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($clusterid));
		$crow = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$crow) { Error("Cluster not found"); return; }
		$defaultuser = $crow['submithost_username'] ?? '';

		$nodes = array();
		$sqlstring = "select * from cluster_nodes where computecluster_id = ? order by hostname";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $clusterid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($clusterid));
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			$nodes[] = $row;
		mysqli_stmt_close($stmt);

		ShowFlashMessage(); /* message from an add/update/delete node action that redirected here (PRG) */
	?>
	<div class="ui container">
		<a href="clustersettings.php"><i class="arrow left icon"></i>Compute clusters</a>
		<div class="ui two column grid" style="margin-top: 4px">
			<div class="column">
				<h1 class="ui header">Monitored nodes<div class="sub header">Cluster <?=ClusterDisplayName($crow['cluster_name'] ?? '')?>. Load, CPU and memory are recorded every minute and shown on the <a href="cluster.php?action=viewnodes">Nodes</a> page</div></h1>
			</div>
			<div class="right aligned column">
				<a href="clustersettings.php?action=addnodeform&clusterid=<?=$clusterid?>" class="ui primary button"><i class="plus square icon"></i>Add node</a>
			</div>
		</div>

		<? if (count($nodes) == 0) { ?>
			<p style="color:#999">No monitored nodes</p>
		<? } else { ?>
		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th>Hostname</th>
					<th>Display name</th>
					<th>ssh user</th>
					<th>Enabled</th>
					<th>Status</th>
					<th>Last seen</th>
				</tr>
			</thead>
			<tbody>
			<? foreach ($nodes as $n) {
				/* status palette color, always with an icon and label */
				$status = $n['last_status'] ?? '';
				if (!$n['enabled']) $statuslabel = '<i class="minus circle icon" style="color:#9e9d98"></i>Disabled';
				elseif ($status == 'ok') $statuslabel = '<i class="check circle icon" style="color:#0ca30c"></i>OK';
				elseif ($status == '') $statuslabel = '<i class="circle outline icon" style="color:#9e9d98"></i>Not checked yet';
				else $statuslabel = '<i class="times circle icon" style="color:#d03b3b"></i>' . htmlspecialchars(ucfirst($status)) . '<div style="color:#52514e; font-size:0.85em; white-space:pre-wrap; max-width:500px"><tt>' . htmlspecialchars($n['last_error'] ?? '') . '</tt></div>';
				$sshuser = $n['ssh_username'] ?? '';
				?>
				<tr>
					<td><a href="clustersettings.php?action=editnodeform&nodeid=<?=(int)$n['clusternode_id']?>"><tt><?=htmlspecialchars($n['hostname'])?></tt></a></td>
					<td><?=htmlspecialchars($n['display_name'] ?? '')?></td>
					<td><?=($sshuser != '') ? '<tt>' . htmlspecialchars($sshuser) . '</tt>' : '<span style="color:#999"><tt>' . htmlspecialchars($defaultuser) . '</tt> (cluster default)</span>'?></td>
					<td><?=($n['enabled']) ? 'Yes' : 'No'?></td>
					<td><?=$statuslabel?></td>
					<td><?=htmlspecialchars($n['last_seen'] ?? '')?></td>
				</tr>
			<? } ?>
			</tbody>
		</table>
		<? } ?>
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
				<h1 class="ui header">Compute clusters</h1>
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
					<th title="Monitored nodes. Click to manage">Nodes</th>
				</tr>
			</thead>
			<tbody>
				<?
					function blankCell($val, $mono = false) {
						if ($val === '' || $val === null)
							return '<span style="color:#bbb">&mdash;</span>';
						return $mono ? '<tt>' . htmlspecialchars($val) . '</tt>' : htmlspecialchars($val);
					}
					/* number of monitored nodes per cluster */
					$nodecounts = array();
					$sqlstring = "select computecluster_id, count(*) 'count' from cluster_nodes group by computecluster_id";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
						$nodecounts[(int)$row['computecluster_id']] = (int)$row['count'];

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
							<td><a href="clustersettings.php?action=editclusterform&clusterid=<?=$id?>"><?=ClusterDisplayName($name)?></a><?php if ($desc !== '') echo ' <span style="color:#999;font-size:0.85em">' . htmlspecialchars($desc) . '</span>'; ?></td>
							<td><?=blankCell($type)?></td>
							<td><?=blankCell($submithostname, true)?></td>
							<td><?=blankCell($submithostusername, true)?></td>
							<td><?=blankCell($clusterusername, true)?></td>
							<td><?=blankCell($queue, true)?></td>
							<td style="white-space:nowrap"><a href="<?=ManageNodesURL($id)?>" title="Manage monitored nodes"><i class="server icon"></i><?=$nodecounts[(int)$id] ?? 0?></a></td>
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
