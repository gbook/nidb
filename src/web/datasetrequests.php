<?
 // ------------------------------------------------------------------------------
 // NiDB datasetrequests.php
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
		<title>NiDB - Dataset Requests</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetSafeAction(array("add", "update", "cancel", "takeownership", "markcomplete")); /* mutating actions require POST + CSRF token */
	$datasetrequestid = (int)GetVariable("datasetrequestid");
	$email = GetVariable("email");
	$institution = GetVariable("institution");
	$shortname = GetVariable("shortname");
	$idlist = GetVariable("idlist");
	$dataformat = GetVariable("dataformat");
	$deliverymethod = GetVariable("deliverymethod");
	$notes = GetVariable("notes");

	/* determine action */
	if ($action == "editform") {
		if (CanEditDatasetRequest($datasetrequestid))
			DisplayDatasetRequestForm("edit", $datasetrequestid);
		else {
			Error("You do not have permission to edit this dataset request");
			DisplayDatasetRequestList();
		}
	}
	elseif ($action == "addform") {
		DisplayDatasetRequestForm("add", "");
	}
	/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-submit */
	elseif ($action == "update") {
		ob_start();
		UpdateDatasetRequest($datasetrequestid, $email, $institution, $shortname, $idlist, $dataformat, $deliverymethod, $notes);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("datasetrequests.php");
	}
	elseif ($action == "add") {
		ob_start();
		AddDatasetRequest($email, $institution, $idlist, $shortname, $dataformat, $deliverymethod, $notes);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("datasetrequests.php");
	}
	elseif ($action == "cancel") {
		ob_start();
		CancelDatasetRequest($datasetrequestid);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("datasetrequests.php");
	}
	elseif ($action == "takeownership") {
		ob_start();
		TakeOwnershipOfDatasetRequest($datasetrequestid);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("datasetrequests.php");
	}
	elseif ($action == "markcomplete") {
		ob_start();
		MarkDatasetRequestComplete($datasetrequestid);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("datasetrequests.php");
	}
	else {
		DisplayDatasetRequestList();
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- CanEditDatasetRequest -------------- */
	/* -------------------------------------------- */
	/* the user who submitted the request, or an admin, may view/edit/cancel it */
	function CanEditDatasetRequest($id) {
		$id = (int)$id;
		if ($id < 1)
			return false;

		if (IsAdminUser())
			return true;

		$sqlstring = "select username from dataset_requests where datasetrequest_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		return (($row !== null) && ((string)$row['username'] !== '') && ((string)$row['username'] === (string)$GLOBALS['username']));
	}


	/* -------------------------------------------- */
	/* ------- GetUploadedDUA --------------------- */
	/* -------------------------------------------- */
	/* store an uploaded DUA file, if one was submitted. Returns the new file id, or null */
	function GetUploadedDUA() {
		if ((isset($_FILES["duafile"])) && ($_FILES["duafile"]["error"] == 0) && ($_FILES["duafile"]["name"] != "")) {
			$fileblob = base64_encode(file_get_contents($_FILES["duafile"]["tmp_name"]));
			$filename = basename($_FILES["duafile"]["name"]);
			$filecontenttype = $_FILES["duafile"]["type"];
			$filesize = $_FILES["duafile"]["size"];

			return StoreFileToSQL($filename, $filecontenttype, $fileblob, $filesize);
		}
		return null;
	}


	/* -------------------------------------------- */
	/* ------- AddDatasetRequest ------------------ */
	/* -------------------------------------------- */
	function AddDatasetRequest($email, $institution, $idlist, $shortname, $dataformat, $deliverymethod, $notes) {
		$fileid = GetUploadedDUA();
		$username = $GLOBALS['username'];

		$sqlstring = "insert into dataset_requests (username, email, institution, shortname, idlist, dataformat, deliverymethod, notes, dua_fileid, request_submitdate, request_status) values (?, ?, ?, ?, ?, ?, ?, ?, ?, now(), 'submitted')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ssssssssi', $username, $email, $institution, $shortname, $idlist, $dataformat, $deliverymethod, $notes, $fileid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($username, $email, $institution, $shortname, $idlist, $dataformat, $deliverymethod, $notes, $fileid));
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars($shortname ?? '') . " submitted");
	}


	/* -------------------------------------------- */
	/* ------- UpdateDatasetRequest --------------- */
	/* -------------------------------------------- */
	function UpdateDatasetRequest($datasetrequestid, $email, $institution, $shortname, $idlist, $dataformat, $deliverymethod, $notes) {
		$datasetrequestid = (int)$datasetrequestid;
		if (!CanEditDatasetRequest($datasetrequestid)) {
			Error("You do not have permission to edit this dataset request");
			return;
		}

		$sqlstring = "select dua_fileid from dataset_requests where datasetrequest_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $datasetrequestid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($datasetrequestid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$duafileid = $row['dua_fileid'] ?? null;

		/* a newly uploaded DUA replaces the existing one; otherwise keep the existing one */
		$fileid = GetUploadedDUA();
		if ($fileid > 0) {
			if ($duafileid > 0)
				DeleteFileFromSQL($duafileid);
		}
		else {
			$fileid = $duafileid;
		}

		$sqlstring = "update dataset_requests set email = ?, institution = ?, shortname = ?, idlist = ?, dataformat = ?, deliverymethod = ?, notes = ?, dua_fileid = ? where datasetrequest_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssssssii', $email, $institution, $shortname, $idlist, $dataformat, $deliverymethod, $notes, $fileid, $datasetrequestid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($email, $institution, $shortname, $idlist, $dataformat, $deliverymethod, $notes, $fileid, $datasetrequestid));
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars($shortname ?? '') . " updated");
	}


	/* -------------------------------------------- */
	/* ------- CancelDatasetRequest --------------- */
	/* -------------------------------------------- */
	function CancelDatasetRequest($id) {
		$id = (int)$id;
		if (!CanEditDatasetRequest($id)) {
			Error("You do not have permission to cancel this dataset request");
			return;
		}

		$sqlstring = "update dataset_requests set request_status = 'cancelled' where datasetrequest_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
		mysqli_stmt_close($stmt);

		Notice("Request $id cancelled");
	}


	/* -------------------------------------------- */
	/* ------- TakeOwnershipOfDatasetRequest ------ */
	/* -------------------------------------------- */
	function TakeOwnershipOfDatasetRequest($id) {
		$id = (int)$id;
		if (!IsAdminUser()) {
			Error("Only administrators can take ownership of dataset requests");
			return;
		}

		$username = $GLOBALS['username'];
		$sqlstring = "update dataset_requests set request_status = 'assigned', admin_username = ?, request_startdate = now() where datasetrequest_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'si', $username, $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($username, $id));
		mysqli_stmt_close($stmt);

		Notice("Request $id assigned");
	}


	/* -------------------------------------------- */
	/* ------- MarkDatasetRequestComplete --------- */
	/* -------------------------------------------- */
	function MarkDatasetRequestComplete($id) {
		$id = (int)$id;
		if (!IsAdminUser()) {
			Error("Only administrators can mark dataset requests complete");
			return;
		}

		$sqlstring = "update dataset_requests set request_status = 'complete', request_completedate = now() where datasetrequest_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
		mysqli_stmt_close($stmt);

		Notice("Request $id marked as complete");
	}


	/* -------------------------------------------- */
	/* ------- DisplayDatasetRequestForm ---------- */
	/* -------------------------------------------- */
	function DisplayDatasetRequestForm($type, $id) {
		$email = $institution = $shortname = $idlist = $dataformat = $deliverymethod = $notes = "";
		$fileid = 0;
		$filename = $filesize = $filedate = "";

		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$id = (int)$id;
			$sqlstring = "select * from dataset_requests where datasetrequest_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$email = $row['email'] ?? '';
			$institution = $row['institution'] ?? '';
			$shortname = $row['shortname'] ?? '';
			$idlist = $row['idlist'] ?? '';
			$dataformat = $row['dataformat'] ?? '';
			$deliverymethod = $row['deliverymethod'] ?? '';
			$notes = $row['notes'] ?? '';
			$fileid = (int)($row['dua_fileid'] ?? 0);

			if ($fileid > 0) {
				list($filename, $filecontenttype, $filesize, $filedate) = GetFileInfoFromSQL($fileid);
			}

			$formaction = "update";
			$formtitle = "Updating " . htmlspecialchars($shortname);
			$submitbuttonlabel = "Update";
		}
		else {
			/* get email and institution */
			$sqlstring = "select user_email, user_institution from users where username = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $GLOBALS['username']);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($GLOBALS['username']));
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			$email = $row['user_email'] ?? '';
			$institution = $row['user_institution'] ?? '';

			$formaction = "add";
			$formtitle = "Submit Dataset Request";
			$submitbuttonlabel = "Submit";
		}

	?>
		<div class="ui text container">
			<div class="ui attached visible message">
			  <div class="header">Manage dataset requests</div>
			  <p>Include as much information as possible, but do not include passwords.</p>
			</div>
			<form method="post" action="datasetrequests.php" autocomplete="off" class="ui form attached fluid segment" enctype="multipart/form-data">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="datasetrequestid" value="<?=(int)$id?>">
			<?=CSRFTokenField()?>
			<h3 class="ui dividing header"><?=$formtitle?></h3>

			<div class="two fields">
				<div class="field">
					<label>Email</label>
					<div class="field">
						<input type="email" name="email" value="<?=htmlspecialchars($email)?>" maxlength="255" required>
					</div>
				</div>

				<div class="field">
					<label>Institution</label>
					<div class="field">
						<input type="text" name="institution" value="<?=htmlspecialchars($institution)?>" maxlength="255" required>
					</div>
				</div>
			</div>

			<div class="field">
				<label>Short name</label>
				<div class="field">
					<input type="text" name="shortname" value="<?=htmlspecialchars($shortname)?>" maxlength="255" required>
				</div>
			</div>

			<div class="field">
				<label>Data Requested <i class="question circle outline icon" title="IDs/project(s) and data protocol names"></i></label>
				<div class="field">
					<textarea name="idlist" rows="4" required><?=htmlspecialchars($idlist)?></textarea>
				</div>
			</div>

			<div class="field">
				<label>Data Format <i class="question circle outline icon" title="BIDS, Nifti, anonymized DICOM, directory structure, etc"></i></label>
				<div class="field">
					<textarea name="dataformat" rows="4" required><?=htmlspecialchars($dataformat)?></textarea>
				</div>
			</div>

			<div class="field">
				<label>Delivery Method <i class="question circle outline icon" title="include all details of how to get the data to you. Email passwords separately"></i></label>
				<div class="field">
					<textarea name="deliverymethod" rows="4" required><?=htmlspecialchars($deliverymethod)?></textarea>
				</div>
			</div>

			<div class="field">
				<label>Notes</label>
				<div class="field">
					<textarea name="notes" rows="4" required><?=htmlspecialchars($notes)?></textarea>
				</div>
			</div>
			<div class="field">
				<label>Data Use Agreement (DUA). Upload new file to replace original file</label>
				<div class="ui file input">
					<input type="file" name="duafile">
				</div>
				<? if ($filename != "") { ?>
					<small>Existing file: <tt><a href="getfile.php?fileid=<?=$fileid?>"><?=htmlspecialchars($filename)?></a></tt>, <?=htmlspecialchars($filesize)?> bytes, <?=htmlspecialchars($filedate)?></small>
				<? } ?>
			</div>
			<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
			</form>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DatasetRequestActionButton --------- */
	/* -------------------------------------------- */
	/* a small inline POST form for one of the list's per-row actions (cancel/markcomplete/takeownership) */
	function DatasetRequestActionButton($action, $datasetrequestid, $label, $confirm) {
		?>
		<form method="post" action="datasetrequests.php" style="display: inline" onsubmit="return confirm('<?=$confirm?>')">
			<input type="hidden" name="action" value="<?=$action?>">
			<input type="hidden" name="datasetrequestid" value="<?=(int)$datasetrequestid?>">
			<?=CSRFTokenField()?>
			<button type="submit" class="ui mini basic red button"><?=$label?></button>
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DatasetRequestRowCells ------------- */
	/* -------------------------------------------- */
	/* the cells common to all three request tables, escaped */
	function DatasetRequestRowCells($row, $usercolumn) {
		$datasetrequestid = (int)$row['datasetrequest_id'];
		$shortname = $row['shortname'] ?? '';
		$idlist = $row['idlist'] ?? '';
		$dataformat = $row['dataformat'] ?? '';
		$deliverymethod = $row['deliverymethod'] ?? '';
		$status = $row['request_status'] ?? '';

		if (strlen($idlist) > 200) { $idlist = substr($idlist, 0, 200) . "..."; }
		if (strlen($dataformat) > 200) { $dataformat = substr($dataformat, 0, 200) . "..."; }
		if (strlen($deliverymethod) > 200) { $deliverymethod = substr($deliverymethod, 0, 200) . "..."; }

		switch ($status) {
			case 'complete': $statustd = "positive"; break;
			case 'submitted': $statustd = "warning"; break;
			case 'assigned': $statustd = "negative"; break;
			default: $statustd = "";
		}

		if ($shortname == "") { $shortname = "(blank)"; }
		?>
		<td><a href="datasetrequests.php?action=editform&datasetrequestid=<?=$datasetrequestid?>"><?=htmlspecialchars($shortname)?></a></td>
		<td><?=htmlspecialchars($row['request_submitdate'] ?? '')?></td>
		<td><?=htmlspecialchars($row[$usercolumn] ?? '')?></td>
		<td class="<?=$statustd?>"><?=htmlspecialchars(ucfirst($status))?></td>
		<td><?=htmlspecialchars($row['request_startdate'] ?? '')?></td>
		<td><?=htmlspecialchars($row['request_completedate'] ?? '')?></td>
		<td><pre><?=htmlspecialchars($idlist)?></pre></td>
		<td><pre><?=htmlspecialchars($dataformat)?></pre></td>
		<td><pre><?=htmlspecialchars($deliverymethod)?></pre></td>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayDatasetRequestList ---------- */
	/* -------------------------------------------- */
	function DisplayDatasetRequestList() {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
		$username = $GLOBALS['username'];
	?>

	<div style="padding: 0px 50px">
	<button class="ui primary large button" onClick="window.location.href='datasetrequests.php?action=addform'; return false;">New Dataset Request</button>
	<br><br><br>

	<h3 class="ui header">My Requests</h3>
	<table class="ui small celled selectable grey compact table">
		<thead>
			<tr>
				<th>Short Name</th>
				<th>Submit Date</th>
				<th>Admin Username</th>
				<th>Status</th>
				<th>Start Date</th>
				<th>Complete Date</th>
				<th>ID List</th>
				<th>Data Format</th>
				<th>Delivery Method</th>
				<th>DUA</th>
				<th>Cancel</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from dataset_requests where username = ? order by request_submitdate asc";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 's', $username);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($username));
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$datasetrequestid = $row['datasetrequest_id'];
					$duafileid = (int)($row['dua_fileid'] ?? 0);
					?>
					<tr>
						<? DatasetRequestRowCells($row, 'admin_username'); ?>
						<td><? if ($duafileid > 0) { ?><a href="getfile.php?fileid=<?=$duafileid?>"><i class="file alternate icon"></i></a> <? } ?></td>
						<td><? DatasetRequestActionButton("cancel", $datasetrequestid, "Cancel", "Cancel this dataset request?"); ?></td>
					</tr>
					<?
				}
				mysqli_stmt_close($stmt);
			?>
		</tbody>
	</table>

	<?
		if (IsAdminUser()) {
	?>
	<br>
	<h3 class="ui header">Requests Assigned to Me</h3>
	<table class="ui small celled selectable grey compact table">
		<thead>
			<tr>
				<th>Short Name</th>
				<th>Submit Date</th>
				<th>Username</th>
				<th>Status</th>
				<th>Start Date</th>
				<th>Complete Date</th>
				<th>ID List</th>
				<th>Data Format</th>
				<th>Delivery Method</th>
				<th>Mark Complete</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from dataset_requests where admin_username = ? order by request_submitdate asc";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 's', $username);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($username));
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$datasetrequestid = $row['datasetrequest_id'];
					?>
					<tr>
						<? DatasetRequestRowCells($row, 'username'); ?>
						<td><? DatasetRequestActionButton("markcomplete", $datasetrequestid, "Mark as complete", "Mark this dataset request complete?"); ?></td>
					</tr>
					<?
				}
				mysqli_stmt_close($stmt);
			?>
		</tbody>
	</table>

	<br><br>
	<h3 class="ui header">Unassigned Requests</h3>
	<table class="ui small celled selectable grey compact table">
		<thead>
			<tr>
				<th>Short Name</th>
				<th>Submit Date</th>
				<th>Username</th>
				<th>Status</th>
				<th>Start Date</th>
				<th>Complete Date</th>
				<th>ID List</th>
				<th>Data Format</th>
				<th>Delivery Method</th>
				<th>Take ownership</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from dataset_requests where admin_username is null or admin_username = '' order by request_submitdate asc";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$datasetrequestid = $row['datasetrequest_id'];
					?>
					<tr>
						<? DatasetRequestRowCells($row, 'username'); ?>
						<td><? DatasetRequestActionButton("takeownership", $datasetrequestid, "Take ownership", "Take ownership of this dataset request?"); ?></td>
					</tr>
					<?
				}
			?>
		</tbody>
	</table>
	</div>
	<?
		}
	}
?>

<? include("footer.php") ?>
