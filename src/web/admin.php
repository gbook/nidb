<?
 // ------------------------------------------------------------------------------
 // NiDB admin.php
 // Copyright (C) 2004 - 2021
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
		<title>NiDB - Administration</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$linkid = GetVariable("linkid");
	$linkurl = GetVariable("linkurl");
	$linktext = GetVariable("linktext");
	$linkdesc = GetVariable("linkdesc");

	/* determine action */
	if ($action == "") {
		DisplayAdminList();
	}
	elseif ($action == "addlink") {
		AddLink($linkurl, $linktext, $linkdesc);
		DisplayAdminList();
	}
	elseif ($action == "deletelink") {
		DeleteLink($linkid);
		DisplayAdminList();
	}
	else {
		DisplayAdminList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayAdminList ------------------- */
	/* -------------------------------------------- */
	function DisplayAdminList() {
		?>
		<div class="ui text container grid">
			<div class="ui eight wide column">
				<div class="ui vertical basic big buttons">
					<a href="adminusers.php" class="ui button"><i class="black users icon"></i> Users</a>
					<a href="adminprojects.php" class="ui button"><i class="black clipboard list icon"></i> Projects</a>
					<a href="reports.php" class="ui button"><i class="black clipboard icon"></i> Reports</a>
					<a href="adminaudits.php" class="ui button"><i class="black clipboard check icon"></i> Audits</a>
					<a href="cleanup.php" class="ui button"><i class="black eraser icon"></i> Clean-up data</a>
					<a href="longqc.php" class="ui button"><i class="black check circle icon"></i> Longitudinal QC</a>
					<a href="stats.php" class="ui button"><i class="black thermometer half icon"></i> System Usage Statistics</a>
					<a href="backup.php" class="ui button"><i class="black archive icon"></i> Backup</a>
				</div>
			</div>
			<div class="ui eight wide column">
				<i class="large info circle icon"></i><a href="status.php"><b>System status</b></a>
				<br><br>
				<li><i class="list alternate icon"></i> <a href="adminmodules.php">Modules</a>
				<li><i class="list alternate icon"></i> <a href="adminmodalities.php">Modalities</a>
				<li><i class="list alternate icon"></i> <a href="adminsites.php">Sites</a>
				<li><i class="list alternate icon"></i> <a href="adminqc.php">QC Modules</a>
				<!--<li><a href="importlog.php">Import Logs</a>-->
				<li><i class="list alternate icon"></i> <a href="admininstances.php">Instances</a>
				<li><i class="envelope icon"></i> <a href="adminemail.php">Mass email</a>
				<br><br><br>
				<i class="large cog icon"></i><a href="system.php"><b>NiDB Settings</b></a>
				<br><br>
				<i class="large wrench icon"></i><a href="setup.php"><b>Setup/Upgrade</b></a>
			</div>
		</div>
		<br><br>
		<div class="ui container">
			<h3 class="ui header">
				<div class="content">
				Informational Links
				<div class="sub header">
					Links to local network resources. Example: cluster status, license servers, documentation, external links
				</div>
			</h3>
			
			<form method="post" action="admin.php" class="ui form">
			<input type="hidden" name="action" value="addlink">
				<div class="fields">
					<div class="four wide field">
						<input type="url" name="linkurl" placeholder="URL">
					</div>
					<div class="four wide field">
						<input type="text" name="linktext" placeholder="Link text">
					</div>
					<div class="eight wide field">
						<input type="text" name="linkdesc" placeholder="Description...">
					</div>
					<input type="submit" class="ui primary button" value="Add">
				</div>
			</form>
			
			<br><br>
			<div class="ui large relaxed divided list">
			<?
			$sqlstring = "select * from links";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$linkid = $row['link_id'];
				$linktext = $row['link_text'];
				$linkurl = $row['link_url'];
				$linkdesc = $row['link_desc'];
				?>
				<div class="item">
					<div class="right floated content"><a href="admin.php?action=deletelink&linkid=<?=$linkid?>"><i class="red trash icon"></i></div>
					<div class="content">
						<h3 class="header"><a href="<?=$linkurl?>"><?=$linktext?></a></h3>
						<div class="description"><?=$linkdesc?></div>
					</div>
				</div>
				<?
			}
			?>
			</div>
		</div>
		<?
	}

	/* -------------------------------------------- */
	/* ------- AddLink ---------------------------- */
	/* -------------------------------------------- */
	function AddLink($linkurl, $linktext, $linkdesc) {
		$linkurl = mysqli_real_escape_string($GLOBALS['linki'], $linkurl);
		$linktext = mysqli_real_escape_string($GLOBALS['linki'], $linktext);
		$linkdesc = mysqli_real_escape_string($GLOBALS['linki'], $linkdesc);
		
		$sqlstring = "insert into links (link_url, link_text, link_desc) values ('$linkurl', '$linktext', '$linkdesc')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Link Added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteLink ------------------------- */
	/* -------------------------------------------- */
	function DeleteLink($linkid) {
		if (!ValidID($linkid,'Link ID')) { return; }
		
		$sqlstring = "delete from links where link_id = $linkid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Link deleted");
	}
	
?>

<? include("footer.php") ?>
