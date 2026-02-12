<?
 // ------------------------------------------------------------------------------
 // NiDB adminsites.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Manage Sites</title>
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
		$id = GetVariable("id");
		$sitename = GetVariable("sitename");
		$siteaddress = GetVariable("siteaddress");
		$sitecontact = GetVariable("sitecontact");
		
		/* determine action */
		if ($action == "editform") {
			DisplaySiteForm("edit", $id);
		}
		elseif ($action == "addform") {
			DisplaySiteForm("add", "");
		}
		elseif ($action == "update") {
			UpdateSite($id, $sitename, $siteaddress, $sitecontact);
			DisplaySiteList();
		}
		elseif ($action == "add") {
			AddSite($sitename, $siteaddress, $sitecontact);
			DisplaySiteList();
		}
		elseif ($action == "delete") {
			DeleteSite($id);
		}
		else {
			DisplaySiteList();
		}
	}
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateSite ------------------------- */
	/* -------------------------------------------- */
	function UpdateSite($id, $sitename, $siteaddress, $sitecontact) {
		/* perform data checks */
		$sitename = mysqli_real_escape_string($GLOBALS['linki'], $sitename);
		$siteaddress = mysqli_real_escape_string($GLOBALS['linki'], $siteaddress);
		$sitecontact = mysqli_real_escape_string($GLOBALS['linki'], $sitecontact);
		
		/* update the site */
		$sqlstring = "update nidb_sites set site_name = '$sitename', site_contact = '$sitecontact', site_address = '$siteaddress' where site_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$sitename?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddSite ---------------------------- */
	/* -------------------------------------------- */
	function AddSite($sitename, $siteaddress, $sitecontact) {
		/* perform data checks */
		$sitename = mysqli_real_escape_string($GLOBALS['linki'], $sitename);
		$siteaddress = mysqli_real_escape_string($GLOBALS['linki'], $siteaddress);
		$sitecontact = mysqli_real_escape_string($GLOBALS['linki'], $sitecontact);

		$siteuid = NIDB\CreateUID('T',4);
		
		/* insert the new site */
		$sqlstring = "insert into nidb_sites (site_uid, site_uuid, site_name, site_address, site_contact) values ('$siteuid', uuid(), '$sitename', '$siteaddress', '$sitecontact')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$sitename?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteSite ------------------------- */
	/* -------------------------------------------- */
	function DeleteSite($id) {
		$sqlstring = "delete from nidb_sites where site_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplaySiteForm -------------------- */
	/* -------------------------------------------- */
	function DisplaySiteForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from nidb_sites where site_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$siteid = $row['site_id'];
			$uuid = $row['site_uuid'];
			$name = $row['site_name'];
			$address = $row['site_address'];
			$contact = $row['site_contact'];
		
			$formaction = "update";
			$formtitle = "Updating $sitename";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new site";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="adminsites.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Name</td>
				<td><input type="text" name="sitename" value="<?=$name?>"></td>
			</tr>
			<tr>
				<td>Address</td>
				<td><textarea name="siteaddress"><?=$address?></textarea></td>
			</tr>
			<tr>
				<td>Contact Info</td>
				<td><textarea name="sitecontact"><?=$contact?></textarea></td>
			</tr>
			<? if ($type == 'edit') { ?>
			<tr>
				<td>Site UUID</td>
				<td class="tiny"><?=strtoupper($uuid)?></td>
			</tr>
			<tr>
				<td>Site ID</td>
				<td class="tiny"><?=$siteid?></td>
			</tr>
			<? } ?>
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
	/* ------- DisplaySiteList -------------------- */
	/* -------------------------------------------- */
	function DisplaySiteList() {
	?>
	<div class="ui container">
		<div class="ui two column grid">
			<div class="column">
				<h1 class="ui header">Sites</h1>
			</div>
			<div class="right aligned column">
				<a href="adminsites.php?action=addform" class="ui primary button"><i class="plus square icon"></i>Add Site</a>
			</div>
		</div>
		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Site ID</th>
					<th>Address</th>
					<th>Contact Info</th>
					<th>UUID</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from nidb_sites order by site_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['site_id'];
						$uuid = $row['site_uuid'];
						$name = $row['site_name'];
						$address = $row['site_address'];
						$contact = $row['site_contact'];
				?>
				<tr>
					<td><a href="adminsites.php?action=editform&id=<?=$id?>"><?=$name?></td>
					<td><?=$id?></td>
					<td><?=$address?></td>
					<td><?=$contact?></td>
					<td class="tiny"><?=strtoupper($uuid)?></td>
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
