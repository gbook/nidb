<?
 // ------------------------------------------------------------------------------
 // NiDB adminsites.php
 // Copyright (C) 2004 - 2016
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
	require "includes.php";
	require "menu.php";
	require "nidbapi.php";
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$sitename = GetVariable("sitename");
	$siteaddress = GetVariable("siteaddress");
	$sitecontact = GetVariable("sitecontact");
	
	//print_r($_POST);
	
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
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateSite ------------------------- */
	/* -------------------------------------------- */
	function UpdateSite($id, $sitename, $siteaddress, $sitecontact) {
		/* perform data checks */
		$sitename = mysql_real_escape_string($sitename);
		$siteaddress = mysql_real_escape_string($siteaddress);
		$sitecontact = mysql_real_escape_string($sitecontact);
		
		/* update the site */
		$sqlstring = "update nidb_sites set site_name = '$sitename', site_contact = '$sitecontact', site_address = '$siteaddress' where site_id = $id";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		?><div align="center"><span class="message"><?=$sitename?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddSite ---------------------------- */
	/* -------------------------------------------- */
	function AddSite($sitename, $siteaddress, $sitecontact) {
		/* perform data checks */
		$sitename = mysql_real_escape_string($sitename);
		$siteaddress = mysql_real_escape_string($siteaddress);
		$sitecontact = mysql_real_escape_string($sitecontact);

		$siteuid = NIDB\CreateUID('T',4);
		
		/* insert the new site */
		$sqlstring = "insert into nidb_sites (site_uid, site_uuid, site_name, site_address, site_contact) values ('$siteuid', uuid(), '$sitename', '$siteaddress', '$sitecontact')";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		?><div align="center"><span class="message"><?=$sitename?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteSite ------------------------- */
	/* -------------------------------------------- */
	function DeleteSite($id) {
		$sqlstring = "delete from nidb_sites where site_id = $id";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplaySiteForm -------------------- */
	/* -------------------------------------------- */
	function DisplaySiteForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from nidb_sites where site_id = $id";
			$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
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
		
		$urllist['Administration'] = "admin.php";
		$urllist['Sites'] = "adminsites.php";
		$urllist[$name] = "adminsites.php?action=editform&id=$id";
		NavigationBar("Admin", $urllist);
		
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
					<input type="submit" value="<?=$submitbuttonlabel?>">
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
	
		$urllist['Administration'] = "admin.php";
		$urllist['Sites'] = "adminsites.php";
		$urllist['Add Site'] = "adminsites.php?action=addform";
		NavigationBar("Admin", $urllist);
		
	?>

	<table class="graydisplaytable">
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
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
	<?
	}
?>


<? include("footer.php") ?>
