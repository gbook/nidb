<?
 // ------------------------------------------------------------------------------
 // NiDB remoteconnections.php
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
		<title>NiDB - Manage Remote Connections</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$connname = GetVariable("connname");
	$remoteserver = GetVariable("remoteserver");
	$remoteusername = GetVariable("remoteusername");
	$remotepassword = GetVariable("remotepassword");
	$remoteinstanceid = GetVariable("remoteinstanceid");
	$remoteprojectid = GetVariable("remoteprojectid");
	$remotesiteid = GetVariable("remotesiteid");
	
	if (!$nomenu) {
		require "menu.php";
	}
	
	/* determine action */
	if ($action == "editform") {
		DisplayConnectionForm("edit", $id);
	}
	elseif ($action == "addform") {
		DisplayConnectionForm("add", "");
	}
	elseif ($action == "update") {
		UpdateConnection($id, $connname, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid);
		DisplayConnectionList();
	}
	elseif ($action == "add") {
		AddConnection($connname, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid);
		DisplayConnectionList();
	}
	elseif ($action == "delete") {
		DeleteConnection($id);
		DisplayConnectionList();
	}
	else {
		DisplayConnectionList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- AddConnection ---------------------- */
	/* -------------------------------------------- */
	function AddConnection($connname, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid) {
		/* perform data checks */
		$connname = mysqli_real_escape_string($GLOBALS['linki'], $connname);
		$remoteserver = mysqli_real_escape_string($GLOBALS['linki'], $remoteserver);
		$remoteusername = mysqli_real_escape_string($GLOBALS['linki'], $remoteusername);
		$remotepassword = mysqli_real_escape_string($GLOBALS['linki'], $remotepassword);
		$remoteinstanceid = mysqli_real_escape_string($GLOBALS['linki'], $remoteinstanceid);
		$remoteprojectid = mysqli_real_escape_string($GLOBALS['linki'], $remoteprojectid);
		$remotesiteid = mysqli_real_escape_string($GLOBALS['linki'], $remotesiteid);

		/* insert the new site */
		$sqlstring = "insert into remote_connections (conn_name, user_id, remote_server, remote_username, remote_password, remote_instanceid, remote_projectid, remote_siteid) values ('$connname', (select user_id from users where username = '" . $GLOBALS['username'] . "'), '$remoteserver', '$remoteusername', sha1('$remotepassword'), '$remoteinstanceid', '$remoteprojectid', '$remotesiteid')";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$sitename?> added</span></div><br><br><?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateConnection ------------------- */
	/* -------------------------------------------- */
	function UpdateConnection($id, $connname, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid) {
		/* perform data checks */
		$connname = mysqli_real_escape_string($GLOBALS['linki'], $connname);
		$remoteserver = mysqli_real_escape_string($GLOBALS['linki'], $remoteserver);
		$remoteusername = mysqli_real_escape_string($GLOBALS['linki'], $remoteusername);
		$remotepassword = mysqli_real_escape_string($GLOBALS['linki'], $remotepassword);
		$remoteinstanceid = mysqli_real_escape_string($GLOBALS['linki'], $remoteinstanceid);
		$remoteprojectid = mysqli_real_escape_string($GLOBALS['linki'], $remoteprojectid);
		$remotesiteid = mysqli_real_escape_string($GLOBALS['linki'], $remotesiteid);

		/* insert the new site */
		$sqlstring = "update remote_connections set conn_name = '$connname', remote_server = '$remoteserver', remote_username = '$remoteusername', remote_password = sha1('$remotepassword'), remote_instanceid = '$remoteinstanceid', remote_projectid = '$remoteprojectid', remote_siteid = '$remotesiteid' where remoteconn_id = $id";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$sitename?> added</span></div><br><br><?
	}
	

	/* -------------------------------------------- */
	/* ------- DeleteConnection ------------------- */
	/* -------------------------------------------- */
	function DeleteConnection($id) {
		$sqlstring = "delete from remote_connections where remoteconn_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message">Connection <?=$id?> deleted</span></div><br><br><?
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayConnectionForm -------------- */
	/* -------------------------------------------- */
	function DisplayConnectionForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from remote_connections where remoteconn_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$connname = $row['conn_name'];
			$remoteserver = $row['remote_server'];
			$remoteusername = $row['remote_username'];
			$remotepassword = $row['remote_password'];
			$remoteinstanceid = $row['remote_instanceid'];
			$remoteprojectid = $row['remote_projectid'];
			$remotesiteid = $row['remote_siteid'];
		
			$formaction = "update";
			$formtitle = "Updating $sitename";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new connection";
			$submitbuttonlabel = "Add";
		}
		
		$urllist['Back'] = "remoteconnections.php";
		NavigationBar("Remote Connections", $urllist);
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="remoteconnections.php" autocomplete="off">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Name</td>
				<td><input type="text" name="connname" value="<?=$connname?>" maxlength="255" required></td>
			</tr>
			<tr>
				<td>Server</td>
				<td><input type="url" name="remoteserver" required value="<?=$remoteserver?>"></td>
			</tr>
			<tr>
				<td>Remote username</td>
				<td><input type="text" name="remoteusername" autocomplete="off" required value="<?=$remoteusername?>"></td>
			</tr>
			<tr>
				<td>Remote password</td>
				<td><input type="password" name="remotepassword" autocomplete="off" required></td>
			</tr>
			<tr>
				<td>Remote instance</td>
				<td><input type="text" name="remoteinstanceid" required value="<?=$remoteinstanceid?>"></td>
			</tr>
			<tr>
				<td>Remote project</td>
				<td><input type="text" name="remoteprojectid" required value="<?=$remoteprojectid?>"></td>
			</tr>
			<tr>
				<td>Remote site</td>
				<td><input type="text" name="remotesiteid" required value="<?=$remotesiteid?>"></td>
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
	/* ------- DisplayConnectionList -------------- */
	/* -------------------------------------------- */
	function DisplayConnectionList() {
	
		$urllist['Remote Connections'] = "remoteconnections.php";
		$urllist['Add Connection'] = "remoteconnections.php?action=addform";
		NavigationBar("Remote Connections", $urllist);
	?>

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Name</th>
				<th>Server</th>
				<th>Username</th>
				<th>Password <span class="tiny">SHA1</span></th>
				<th>Instance</th>
				<th>Project</th>
				<th>Site</th>
				<th>Delete</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from remote_connections where user_id = (select user_id from users where username = '" . $GLOBALS['username'] . "') order by conn_name";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['remoteconn_id'];
					$connname = $row['conn_name'];
					$remoteserver = $row['remote_server'];
					$remoteusername = $row['remote_username'];
					$remotepassword = $row['remote_password'];
					$remoteinstanceid = $row['remote_instanceid'];
					$remoteprojectid = $row['remote_projectid'];
					$remotesiteid = $row['remote_siteid'];
			?>
			<tr>
				<td><a href="remoteconnections.php?action=editform&id=<?=$id?>"><?=$connname?></a></td>
				<td><?=$remoteserver?></td>
				<td><?=$remoteusername?></td>
				<td style="font-size:10pt"><?=strtoupper($remotepassword)?></td>
				<td><?=$remoteinstanceid?></td>
				<td><?=$remoteprojectid?></td>
				<td><?=$remotesiteid?></td>
				<td><a href="remoteconnections.php?action=delete&id=<?=$id?>" style="color: red">X</a></td>
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
