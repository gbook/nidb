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
		$id = (int)$id;
		
		/* update the site */
		$stmt = mysqli_prepare($GLOBALS['linki'], "update nidb_sites set site_name = ?, site_contact = ?, site_address = ? where site_id = ?");
		mysqli_stmt_bind_param($stmt, 'sssi', $sitename, $sitecontact, $siteaddress, $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		
		?><div align="center"><span class="message"><?=$sitename?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddSite ---------------------------- */
	/* -------------------------------------------- */
	function AddSite($sitename, $siteaddress, $sitecontact) {
		$siteuid = NIDB\CreateUID('T',4);
		
		/* insert the new site */
		$stmt = mysqli_prepare($GLOBALS['linki'], "insert into nidb_sites (site_uid, site_uuid, site_name, site_address, site_contact) values (?, uuid(), ?, ?, ?)");
		mysqli_stmt_bind_param($stmt, 'ssss', $siteuid, $sitename, $siteaddress, $sitecontact);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		
		?><div align="center"><span class="message"><?=$sitename?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteSite ------------------------- */
	/* -------------------------------------------- */
	function DeleteSite($id) {
		$id = (int)$id;
		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from nidb_sites where site_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplaySiteForm -------------------- */
	/* -------------------------------------------- */
	function DisplaySiteForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$id = (int)$id;
			$stmt = mysqli_prepare($GLOBALS['linki'], "select * from nidb_sites where site_id = ?");
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$siteid = $row['site_id'];
			$uuid = $row['site_uuid'];
			$name = $row['site_name'];
			$address = $row['site_address'];
			$contact = $row['site_contact'];
		
			$formaction = "update";
			$formtitle = "Updating $name";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new site";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div class="ui text container">
			<div class="ui attached visible message">
				<div class="header"><?=$formtitle?></div>
			</div>

			<form method="post" action="adminsites.php" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="<?=$formaction?>">
				<input type="hidden" name="id" value="<?=$id?>">

				<div class="required field">
					<label>Site Name</label>
					<input type="text" name="sitename" value="<?=$name?>" placeholder="Site name" required autofocus="autofocus">
				</div>

				<div class="field">
					<label>Address</label>
					<textarea name="siteaddress" rows="3" placeholder="Site address"><?=$address?></textarea>
				</div>

				<div class="field">
					<label>Contact Info</label>
					<textarea name="sitecontact" rows="3" placeholder="Contact information"><?=$contact?></textarea>
				</div>

				<? if ($type == 'edit') { ?>
				<div class="two fields">
					<div class="field">
						<label>Site UUID</label>
						<div class="ui small grey segment tiny"><?=strtoupper($uuid)?></div>
					</div>
					<div class="field">
						<label>Site ID</label>
						<div class="ui small grey segment tiny"><?=$siteid?></div>
					</div>
				</div>
				<? } ?>

				<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				<a href="adminsites.php" class="ui button">Cancel</a>
			</form>
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



