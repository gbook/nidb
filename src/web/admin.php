<?
 // ------------------------------------------------------------------------------
 // NiDB admin.php
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
		<title>NiDB - Administration</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	if (!isAdmin() && !isSiteAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
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
	}	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayAdminList ------------------- */
	/* -------------------------------------------- */
	function DisplayAdminList() {

		if (file_exists("/nidb/setup/dbupgrade")) {
			?>
			<div class="ui container">
				<div class="ui warning icon message">
					<i class="exclamation circle icon"></i>
					<div class="content">
						<div class="header">Installation/upgrade not complete</div>
						
						Click here to <a href="setup.php"> finish setup/upgrade</a>
					</div>
				</div>
			</div>
			<br>
			<?
		} else {
		
			$systemstring = "curl --silent \"https://api.github.com/repos/gbook/nidb/releases/latest\" | grep '\"tag_name\":'";
			$latestnidb = shell_exec($systemstring);
			$latestnidb = str_replace("\"tag_name\": \"","", $latestnidb);
			$latestnidb = str_replace("\",","", $latestnidb);
			$latestnidb = trim(str_replace("v","", $latestnidb));
			
			$currentnidb = trim(GetNiDBVersion());
			if ($currentnidb != $latestnidb) {
			?>
				<div class="ui text container">
					<div class="ui warning icon message">
						<i class="cloud download alternate icon"></i>
						<div class="content">
							<div class="header">Update available</div>
							Current version [v<? =GetNiDBVersion();?>]<br>
							Latest version [v<? =$latestnidb;?>]<br>
							<br>
							To upgrade, install .rpm from <a href="http://github.com/gbook/nidb"><i class="github icon"></i> github</a>, then return here to <a href="setup.php" class="ui basic button"><i class="wrench icon"></i> Setup/Upgrade</a>
						</div>
					</div>
				</div>
			<? }
			else {
			?>
				<div class="ui text container">
					<div class="ui positive icon message">
						<i class="check circle icon"></i>
						<div class="content">
							<div class="header">NiDB is up to date</div>
							Current NiDB version <b>v<? =GetNiDBVersion();?></b><br>
							<br>
						</div>
					</div>
				</div>
				<br>
				<?
			}
		}
		?>
		
		<br>
		<div class="ui text container grid">
			<div class="ui eight wide column">
				<div class="ui header">
					Front end
					<div class="sub header">User facing options</div>
				</div>

				<a href="adminusers.php" class="ui big basic fluid button"><i class="black users icon"></i> Users</a>
				<a href="adminprojects.php" class="ui big basic fluid button"><i class="black clipboard list icon"></i> Projects</a>
				<!--<a href="longqc.php" class="ui button"><i class="black check circle icon"></i> Longitudinal QC</a>-->
				<a href="projects.php?action=editbidsmapping&id=null" class="ui basic fluid button"><i class="map signs icon"></i> Edit Global BIDS Protocol Mapping</a>

				<? if (isSiteAdmin()) { ?>
				<br>
				<a href="reports.php" class="ui big basic fluid button"><i class="black clipboard icon"></i> Reports</a>
				<a href="adminaudits.php" class="ui big basic fluid button"><i class="black clipboard check icon"></i> Audits</a>
				<a href="cleanup.php" class="ui big basic fluid button"><i class="black eraser icon"></i> Clean-up data</a>
				<a href="adminsites.php" class="ui big basic fluid button"><i class="black list alternate icon"></i> Sites</a>
				<a href="admininstances.php" class="ui big basic fluid button"><i class="black list alternate icon"></i> Instances</a>
				<a href="adminmodalities.php" class="ui big basic fluid button"><i class="black list alternate icon"></i> Modalities</a>
				<? } ?>
			</div>
			<div class="ui eight wide column">
				<div class="ui header">
					Back end
					<div class="sub header">System Administration</div>
				</div>

				<? if (isSiteAdmin()) { ?>
				<a href="settings.php" class="ui big fluid button"><i class="cog icon"></i> NiDB Settings...</a>
				<br>
				<a href="pipelinesettings.php" class="ui big basic fluid button"><i class="black cog icon"></i> Pipeline/cluster settings</a>
				<a href="status.php" class="ui big basic fluid button"><i class="black info circle icon"></i> System status</a>
				<a href="adminmodules.php" class="ui big basic fluid button"><i class="black list alternate icon"></i> Modules</a>
				<a href="adminqc.php" class="ui big basic fluid button"><i class="black list alternate icon"></i> QC Modules</a>
				<!--<li><a href="importlog.php">Import Logs</a>-->
				<a href="adminemail.php" class="ui big basic fluid button"><i class="black envelope icon"></i> Mass email</a>
				<a href="stats.php" class="ui big basic fluid button"><i class="black thermometer half icon"></i> System Usage</a>
				<a href="backup.php" class="ui big basic fluid button"><i class="black archive icon"></i> Backup</a>
				<a href="adminerrorlogs.php" class="ui big basic fluid button"><i class="bug icon"></i> View Error Logs</a>
				<? } ?>
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
					<div class="right floated content"><a href="admin.php?action=deletelink&linkid=<? =$linkid?>"><i class="red trash icon"></i></div>
					<div class="content">
						<h3 class="header"><a href="<? =$linkurl?>"><? =$linktext?></a></h3>
						<div class="description"><? =$linkdesc?></div>
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
