<?
 // ------------------------------------------------------------------------------
 // NiDB remoteconnections.php
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
	$id = (int)GetVariable("id");
	$connname = GetVariable("connname");
	$remoteserver = GetVariable("remoteserver");
	$remoteusername = GetVariable("remoteusername");
	$remotepassword = GetVariable("remotepassword");
	$remoteinstanceid = (int)GetVariable("remoteinstanceid");
	$remoteprojectid = (int)GetVariable("remoteprojectid");
	$remotesiteid = (int)GetVariable("remotesiteid");
	
	if (empty($nomenu)) {
		require "menu.php";
	}
	
	$action = GetSafeAction(array("add", "update", "delete")); /* mutating actions require POST + CSRF token */
	
	/* determine action */
	if ($action == "editform") {
		DisplayConnectionForm("edit", $id);
	}
	elseif ($action == "addform") {
		DisplayConnectionForm("add", "");
	}
	/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-submit */
	elseif ($action == "update") {
		ob_start();
		UpdateConnection($id, $connname, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("remoteconnections.php");
	}
	elseif ($action == "add") {
		ob_start();
		AddConnection($connname, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("remoteconnections.php");
	}
	elseif ($action == "delete") {
		ob_start();
		DeleteConnection($id);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("remoteconnections.php");
	}
	else {
		DisplayConnectionList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetCurrentUserIDForConnections ----- */
	/* -------------------------------------------- */
	/* remote connections belong to the user who created them */
	function GetCurrentUserIDForConnections() {
		$sqlstring = "select user_id from users where username = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $GLOBALS['username']);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($GLOBALS['username']));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return (int)($row['user_id'] ?? 0);
	}


	/* -------------------------------------------- */
	/* ------- ValidConnectionFields -------------- */
	/* -------------------------------------------- */
	/* the server and username are later placed on a curl command line (ajaxapi.php RemoteExportStatus),
	   so only allow plain http(s) URLs and simple usernames. Displays an error and returns false if invalid */
	function ValidConnectionFields($remoteserver, $remoteusername) {
		if (!preg_match('#^https?://[A-Za-z0-9.-]+(:[0-9]+)?(/[A-Za-z0-9._~/-]*)?$#', (string)$remoteserver)) {
			Error("Server must be a plain http:// or https:// URL (letters, numbers, <tt>. - _ ~ /</tt> and an optional port). Connection was not saved.");
			return false;
		}
		if (!preg_match('/^[A-Za-z0-9._@-]+$/', (string)$remoteusername)) {
			Error("Remote username may only contain letters, numbers, and <tt>. _ @ -</tt>. Connection was not saved.");
			return false;
		}
		return true;
	}


	/* -------------------------------------------- */
	/* ------- AddConnection ---------------------- */
	/* -------------------------------------------- */
	function AddConnection($connname, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid) {
		if (!ValidConnectionFields($remoteserver, $remoteusername)) { return; }
		$userid = GetCurrentUserIDForConnections();
		if ($userid < 1) { return; }

		/* the remote password is stored as sha1(), which is what the remote api.php is sent */
		$sqlstring = "insert into remote_connections (conn_name, user_id, remote_server, remote_username, remote_password, remote_instanceid, remote_projectid, remote_siteid) values (?, ?, ?, ?, sha1(?), ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sisssiii', $connname, $userid, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($connname, $userid, $remoteserver, $remoteusername, '(password)', $remoteinstanceid, $remoteprojectid, $remotesiteid));
		mysqli_stmt_close($stmt);
		
		Notice("Connection " . htmlspecialchars($connname ?? '') . " added");
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateConnection ------------------- */
	/* -------------------------------------------- */
	function UpdateConnection($id, $connname, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid) {
		if (!ValidConnectionFields($remoteserver, $remoteusername)) { return; }
		$id = (int)$id;
		$userid = GetCurrentUserIDForConnections();

		/* only the owner's own connection can be updated */
		$sqlstring = "update remote_connections set conn_name = ?, remote_server = ?, remote_username = ?, remote_password = sha1(?), remote_instanceid = ?, remote_projectid = ?, remote_siteid = ? where remoteconn_id = ? and user_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssiiiii', $connname, $remoteserver, $remoteusername, $remotepassword, $remoteinstanceid, $remoteprojectid, $remotesiteid, $id, $userid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($connname, $remoteserver, $remoteusername, '(password)', $remoteinstanceid, $remoteprojectid, $remotesiteid, $id, $userid));
		mysqli_stmt_close($stmt);
		
		Notice("Connection " . htmlspecialchars($connname ?? '') . " updated");
	}
	

	/* -------------------------------------------- */
	/* ------- DeleteConnection ------------------- */
	/* -------------------------------------------- */
	function DeleteConnection($id) {
		$id = (int)$id;
		$userid = GetCurrentUserIDForConnections();

		/* only the owner's own connection can be deleted */
		$sqlstring = "delete from remote_connections where remoteconn_id = ? and user_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $id, $userid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id, $userid));
		mysqli_stmt_close($stmt);
		
		Notice("Connection deleted");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayConnectionForm -------------- */
	/* -------------------------------------------- */
	function DisplayConnectionForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			/* only the owner's own connection can be viewed */
			$id = (int)$id;
			$userid = GetCurrentUserIDForConnections();
			$sqlstring = "select * from remote_connections where remoteconn_id = ? and user_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $id, $userid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id, $userid));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if ($row === null) {
				Error("Connection not found");
				return;
			}
			$connname = $row['conn_name'];
			$remoteserver = $row['remote_server'];
			$remoteusername = $row['remote_username'];
			$remoteinstanceid = $row['remote_instanceid'];
			$remoteprojectid = $row['remote_projectid'];
			$remotesiteid = $row['remote_siteid'];
		
			$formaction = "update";
			$formtitle = "Updating " . htmlspecialchars($connname ?? '');
			$submitbuttonlabel = "Update";
		}
		else {
			$connname = $remoteserver = $remoteusername = $remoteinstanceid = $remoteprojectid = $remotesiteid = "";
			$formaction = "add";
			$formtitle = "Add new connection";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="remoteconnections.php" autocomplete="off">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=(int)$id?>">
			<?=CSRFTokenField()?>
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Name</td>
				<td><input type="text" name="connname" value="<?=htmlspecialchars($connname ?? '')?>" maxlength="255" required></td>
			</tr>
			<tr>
				<td>Server</td>
				<td><input type="url" name="remoteserver" required value="<?=htmlspecialchars($remoteserver ?? '')?>"></td>
			</tr>
			<tr>
				<td>Remote username</td>
				<td><input type="text" name="remoteusername" autocomplete="off" required value="<?=htmlspecialchars($remoteusername ?? '')?>"></td>
			</tr>
			<tr>
				<td>Remote password</td>
				<td><input type="password" name="remotepassword" autocomplete="off" required></td>
			</tr>
			<tr>
				<td>Remote instance</td>
				<td><input type="text" name="remoteinstanceid" required value="<?=htmlspecialchars($remoteinstanceid ?? '')?>"></td>
			</tr>
			<tr>
				<td>Remote project</td>
				<td><input type="text" name="remoteprojectid" required value="<?=htmlspecialchars($remoteprojectid ?? '')?>"></td>
			</tr>
			<tr>
				<td>Remote site</td>
				<td><input type="text" name="remotesiteid" required value="<?=htmlspecialchars($remotesiteid ?? '')?>"></td>
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
	/* ------- DisplayConnectionList -------------- */
	/* -------------------------------------------- */
	function DisplayConnectionList() {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
		$userid = GetCurrentUserIDForConnections();
	?>

	<div class="ui container">
		<a class="ui primary button" href="remoteconnections.php?action=addform"><i class="ui plus icon"></i> Create Remote Connection</a>
		<table class="ui grey compact celled table">
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
					$sqlstring = "select * from remote_connections where user_id = ? order by conn_name";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'i', $userid);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($userid));
					mysqli_stmt_close($stmt);
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
					<td><a href="remoteconnections.php?action=editform&id=<?=$id?>"><?=htmlspecialchars($connname ?? '')?></a></td>
					<td><?=htmlspecialchars($remoteserver ?? '')?></td>
					<td><?=htmlspecialchars($remoteusername ?? '')?></td>
					<td style="font-size:10pt"><?=htmlspecialchars(strtoupper($remotepassword ?? ''))?></td>
					<td><?=htmlspecialchars($remoteinstanceid ?? '')?></td>
					<td><?=htmlspecialchars($remoteprojectid ?? '')?></td>
					<td><?=htmlspecialchars($remotesiteid ?? '')?></td>
					<td>
						<form method="post" action="remoteconnections.php" style="display: inline" onsubmit="return confirm('Are you sure you want to delete this connection?')">
							<input type="hidden" name="action" value="delete">
							<input type="hidden" name="id" value="<?=(int)$id?>">
							<?=CSRFTokenField()?>
							<button type="submit" class="ui red icon button" title="Delete connection"><i class="trash icon"></i></button>
						</form>
					</td>
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
