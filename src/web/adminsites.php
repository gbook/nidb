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
	ob_start(); /* buffer output for POST/Redirect/GET (see functions.php RedirectTo/ShowFlashMessage) */
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
		/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-run them */
		if ($action == "editform") {
			DisplaySiteForm("edit", $id);
		}
		elseif ($action == "addform") {
			DisplaySiteForm("add", "");
		}
		elseif ($action == "update") {
			ob_start();
			UpdateSite($id, $sitename, $siteaddress, $sitecontact);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("adminsites.php");
		}
		elseif ($action == "add") {
			ob_start();
			AddSite($sitename, $siteaddress, $sitecontact);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("adminsites.php");
		}
		elseif ($action == "delete") {
			ob_start();
			DeleteSite($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("adminsites.php");
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
		$sqlstring = "update nidb_sites set site_name = ?, site_contact = ?, site_address = ? where site_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssi', $sitename, $sitecontact, $siteaddress, $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$sitename, $sitecontact, $siteaddress, $id]);
		mysqli_stmt_close($stmt);

		?><div align="center"><span class="message"><?=htmlspecialchars($sitename)?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddSite ---------------------------- */
	/* -------------------------------------------- */
	function AddSite($sitename, $siteaddress, $sitecontact) {
		$siteuid = NIDB\CreateUID('T',4);
		
		/* insert the new site */
		$sqlstring = "insert into nidb_sites (site_uid, site_uuid, site_name, site_address, site_contact) values (?, uuid(), ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssss', $siteuid, $sitename, $siteaddress, $sitecontact);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$siteuid, $sitename, $siteaddress, $sitecontact]);
		mysqli_stmt_close($stmt);

		?><div align="center"><span class="message"><?=htmlspecialchars($sitename)?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteSite ------------------------- */
	/* -------------------------------------------- */
	function DeleteSite($id) {
		$id = (int)$id;
		$sqlstring = "delete from nidb_sites where site_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);
		Notice("Site deleted");
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplaySiteForm -------------------- */
	/* -------------------------------------------- */
	function DisplaySiteForm($type, $id) {

		/* defaults so the "add" form doesn't reference undefined vars */
		$name = $address = $contact = $uuid = "";
		$siteid = "";

		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$id = (int)$id;
			$sqlstring = "select * from nidb_sites where site_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) { Error("Site not found"); return; }
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
				<div class="header"><?=htmlspecialchars($formtitle)?></div>
			</div>

			<form method="post" action="adminsites.php" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="<?=$formaction?>">
				<input type="hidden" name="id" value="<?=$id?>">

				<div class="required field">
					<label>Site Name</label>
					<input type="text" name="sitename" value="<?=htmlspecialchars($name)?>" placeholder="Site name" required autofocus="autofocus">
				</div>

				<div class="field">
					<label>Address</label>
					<textarea name="siteaddress" rows="3" placeholder="Site address"><?=htmlspecialchars($address)?></textarea>
				</div>

				<div class="field">
					<label>Contact Info</label>
					<textarea name="sitecontact" rows="3" placeholder="Contact information"><?=htmlspecialchars($contact)?></textarea>
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
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
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
					<td><a href="adminsites.php?action=editform&id=<?=$id?>"><?=htmlspecialchars($name)?></a></td>
					<td><?=$id?></td>
					<td><?=htmlspecialchars($address ?? '')?></td>
					<td><?=htmlspecialchars($contact ?? '')?></td>
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



