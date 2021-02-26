<?
 // ------------------------------------------------------------------------------
 // NiDB datasetrequests.php
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
	$action = GetVariable("action");
	$datasetrequestid = GetVariable("datasetrequestid");
	$email = GetVariable("email");
	$institution = GetVariable("institution");
	$shortname = GetVariable("shortname");
	$idlist = GetVariable("idlist");
	$dataformat = GetVariable("dataformat");
	$deliverymethod = GetVariable("deliverymethod");
	$notes = GetVariable("notes");
	
	//PrintVariable($_POST);
	
	/* determine action */
	if ($action == "editform") {
		DisplayDatasetRequestForm("edit", $datasetrequestid);
	}
	elseif ($action == "addform") {
		DisplayDatasetRequestForm("add", "");
	}
	elseif ($action == "update") {
		UpdateDatasetRequest($datasetrequestid, $email, $institution, $shortname, $idlist, $dataformat, $deliverymethod, $notes);
		DisplayDatasetRequestList();
	}
	elseif ($action == "add") {
		AddDatasetRequest($email, $institution, $idlist, $shortname, $dataformat, $deliverymethod, $notes);
		DisplayDatasetRequestList();
	}
	elseif ($action == "cancel") {
		CancelDatasetRequest($datasetrequestid);
		DisplayDatasetRequestList();
	}
	elseif ($action == "takeownership") {
		TakeOwnershipOfDatasetRequest($datasetrequestid);
		DisplayDatasetRequestList();
	}
	elseif ($action == "markcomplete") {
		MarkDatasetRequestComplete($datasetrequestid);
		DisplayDatasetRequestList();
	}
	else {
		DisplayDatasetRequestList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- AddDatasetRequest ------------------ */
	/* -------------------------------------------- */
	function AddDatasetRequest($email, $institution, $idlist, $shortname, $dataformat, $deliverymethod, $notes) {
		/* perform data checks */
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		$institution = mysqli_real_escape_string($GLOBALS['linki'], $institution);
		$idlist = mysqli_real_escape_string($GLOBALS['linki'], $idlist);
		$shortname = mysqli_real_escape_string($GLOBALS['linki'], $shortname);
		$dataformat = mysqli_real_escape_string($GLOBALS['linki'], $dataformat);
		$deliverymethod = mysqli_real_escape_string($GLOBALS['linki'], $deliverymethod);
		$notes = mysqli_real_escape_string($GLOBALS['linki'], $notes);
		
		/* insert the new site */
		$sqlstring = "insert into dataset_requests (username, email, institution, shortname, idlist, dataformat, deliverymethod, notes, request_submitdate, request_status) values ('" . $GLOBALS['username'] . "', '$email', '$institution', '$shortname', '$idlist', '$dataformat', '$deliverymethod', '$notes', now(), 'submitted')";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		DisplayNotice("$shortname submitted");
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateDatasetRequest --------------- */
	/* -------------------------------------------- */
	function UpdateDatasetRequest($datasetrequestid, $email, $institution, $shortname, $idlist, $dataformat, $deliverymethod, $notes) {
		/* perform data checks */
		$datasetrequestid = mysqli_real_escape_string($GLOBALS['linki'], $datasetrequestid);
		$email = mysqli_real_escape_string($GLOBALS['linki'], $email);
		$institution = mysqli_real_escape_string($GLOBALS['linki'], $institution);
		$idlist = mysqli_real_escape_string($GLOBALS['linki'], $idlist);
		$shortname = mysqli_real_escape_string($GLOBALS['linki'], $shortname);
		$dataformat = mysqli_real_escape_string($GLOBALS['linki'], $dataformat);
		$deliverymethod = mysqli_real_escape_string($GLOBALS['linki'], $deliverymethod);
		$notes = mysqli_real_escape_string($GLOBALS['linki'], $notes);

		/* insert the new site */
		$sqlstring = "update dataset_requests set email = '$email', institution = '$institution', shortname = '$shortname', idlist = '$idlist', dataformat = '$dataformat', deliverymethod = '$delivermethod', notes = '$notes' where datasetrequest_id = $datasetrequestid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		DisplayNotice("$shortname updated");
	}
	

	/* -------------------------------------------- */
	/* ------- CancelDatasetRequest --------------- */
	/* -------------------------------------------- */
	function CancelDatasetRequest($id) {
		$sqlstring = "update dataset_requests set request_status = 'cancelled' where datasetrequest_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		DisplayNotice("$id cancelled");
	}


	/* -------------------------------------------- */
	/* ------- TakeOwnershipOfDatasetRequest ------ */
	/* -------------------------------------------- */
	function TakeOwnershipOfDatasetRequest($id) {
		$sqlstring = "update dataset_requests set request_status = 'assigned', admin_username = '" . $GLOBALS['username'] . "', request_startdate = now() where datasetrequest_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		DisplayNotice("$id assigned");
	}


	/* -------------------------------------------- */
	/* ------- MarkDatasetRequestComplete --------- */
	/* -------------------------------------------- */
	function MarkDatasetRequestComplete($id) {
		$sqlstring = "update dataset_requests set request_status = 'complete', request_completedate = now() where datasetrequest_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		DisplayNotice("$id marked as complete");
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayDatasetRequestForm ---------- */
	/* -------------------------------------------- */
	function DisplayDatasetRequestForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from dataset_requests where datasetrequest_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$datasetrequestid = $row['datasetrequest_id'];
			$email = $row['email'];
			$institution = $row['institution'];
			$shortname = $row['shortname'];
			$idlist = $row['idlist'];
			$dataformat = $row['dataformat'];
			$deliverymethod = $row['deliverymethod'];
			$notes = $row['notes'];

			$formaction = "update";
			$formtitle = "Updating $sitename";
			$submitbuttonlabel = "Update";
		}
		else {
			/* get email and institution */
			$sqlstring  = "select user_email, user_institution from users where username = '" . $GLOBALS['username'] . "'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$email = $row['user_email'];
			$institution = $row['user_institution'];
			
			$formaction = "add";
			$formtitle = "Submit Dataset Request";
			$submitbuttonlabel = "Submit";
		}
		
	?>
		<div class="ui text container">
			<div class="ui attached visible message">
			  <div class="header">Submit a new data request</div>
			  <p>Fill out the form to submit a new request. Include as much information as possible, but do not include passwords.</p>
			</div>
			<form method="post" action="datasetrequests.php" autocomplete="off" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="datasetrequestid" value="<?=$id?>">
			<h3 class="ui dividing header"><?=$formtitle?></h3>
			
			<div class="two fields">
				<div class="field">
					<label>Email</label>
					<div class="field">
						<input type="email" name="email" value="<?=$email?>" maxlength="255" required>
					</div>
				</div>
				
				<div class="field">
					<label>Institution</label>
					<div class="field">
						<input type="text" name="institution" value="<?=$institution?>" maxlength="255" required>
					</div>
				</div>
			</div>

			<div class="field">
				<label>Short name</label>
				<div class="field">
					<input type="text" name="shortname" value="<?=$shortname?>" maxlength="255" required>
				</div>
			</div>

			<div class="field">
				<label>Data Requested <i class="question circle outline icon" title="IDs/project(s) and data protocol names"></i></label>
				<div class="field">
					<textarea name="idlist" rows="4" required><?=$idlist?></textarea>
				</div>
			</div>

			<div class="field">
				<label>Data Format <i class="question circle outline icon" title="BIDS, Nifti, anonymized DICOM, directory structure, etc"></i></label>
				<div class="field">
					<textarea name="dataformat" rows="4" required><?=$dataformat?></textarea>
				</div>
			</div>

			<div class="field">
				<label>Delivery Method <i class="question circle outline icon" title="include all details of how to get the data to you. Email passwords separately"></i></label>
				<div class="field">
					<textarea name="deliverymethod" rows="4" required><?=$deliverymethod?></textarea>
				</div>
			</div>

			<div class="field">
				<label>Notes</label>
				<div class="field">
					<textarea name="notes" rows="4" required><?=$notes?></textarea>
				</div>
			</div>
			<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
			</form>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayDatasetRequestList ---------- */
	/* -------------------------------------------- */
	function DisplayDatasetRequestList() {
	?>

	<div style="padding: 0px 50px">
	<button class="ui primary large button" onClick="window.location.href='datasetrequests.php?action=addform'; return false;">Submit New Dataset Request</button>
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
				<th>Cancel</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from dataset_requests where username = '" . $GLOBALS['username'] . "' order by request_submitdate asc";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$datasetrequestid = $row['datasetrequest_id'];
					$username = $row['username'];
					$email = $row['email'];
					$institution = $row['institution'];
					$shortname = $row['shortname'];
					$idlist = $row['idlist'];
					$dataformat = $row['dataformat'];
					$deliverymethod = $row['deliverymethod'];
					$notes = $row['notes'];
					$request_submitdate = $row['request_submitdate'];
					$request_startdate = $row['request_startdate'];
					$request_completedate = $row['request_completedate'];
					$status = $row['request_status'];
					$admin_username = $row['admin_username'];

					switch ($status) {
						case 'complete': $statustd = "positive"; break;
						case 'submitted': $statustd = "warning"; break;
						case 'assigned': $statustd = "negative"; break;
						default: $statustd = "";
					}

					if ($shortname == "") { $shortname = "(blank)"; }
					?>
					<tr>
						<td><a href="datasetrequests.php?action=editform&datasetrequestid=<?=$datasetrequestid?>"><?=$shortname?></a></td>
						<td><?=$request_submitdate?></td>
						<td><?=$admin_username?></td>
						<td class="<?=$statustd?>"><?=ucfirst($status)?></td>
						<td><?=$request_startdate?></td>
						<td><?=$request_completedate?></td>
						<td><?=$idlist?></td>
						<td><?=$dataformat?></td>
						<td><?=$deliverymethod?></td>
						<td><a href="datasetrequests.php?action=cancel&datasetrequestid=<?=$datasetrequestid?>" style="color: red">Cancel</a></td>
					</tr>
					<? 
				}
			?>
		</tbody>
	</table>

	<?
		if ($GLOBALS['isadmin']) {
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
				$sqlstring = "select * from dataset_requests where admin_username = '" . $GLOBALS['username'] . "' order by request_submitdate asc";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$datasetrequestid = $row['datasetrequest_id'];
					$username = $row['username'];
					$email = $row['email'];
					$institution = $row['institution'];
					$shortname = $row['shortname'];
					$idlist = $row['idlist'];
					$dataformat = $row['dataformat'];
					$deliverymethod = $row['deliverymethod'];
					$notes = $row['notes'];
					$request_submitdate = $row['request_submitdate'];
					$request_startdate = $row['request_startdate'];
					$request_completedate = $row['request_completedate'];
					$status = $row['request_status'];

					switch ($status) {
						case 'complete': $statustd = "positive"; break;
						case 'submitted': $statustd = "warning"; break;
						case 'assigned': $statustd = "negative"; break;
						default: $statustd = "";
					}
					
					if ($shortname == "") { $shortname = "(shortname is blank)"; }
					?>
					<tr>
						<td><a href="datasetrequests.php?action=editform&datasetrequestid=<?=$datasetrequestid?>"><?=$shortname?></a></td>
						<td><?=$request_submitdate?></td>
						<td><?=$username?></td>
						<td class="<?=$statustd?>"><?=ucfirst($status)?></td>
						<td><?=$request_startdate?></td>
						<td><?=$request_completedate?></td>
						<td><?=$idlist?></td>	
						<td><?=$dataformat?></td>
						<td><?=$deliverymethod?></td>
						<td><a href="datasetrequests.php?action=markcomplete&datasetrequestid=<?=$datasetrequestid?>" style="color: red">Mark as complete</a></td>
					</tr>
					<? 
				}
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
					$username = $row['username'];
					$email = $row['email'];
					$institution = $row['institution'];
					$shortname = $row['shortname'];
					$idlist = $row['idlist'];
					$dataformat = $row['dataformat'];
					$deliverymethod = $row['deliverymethod'];
					$notes = $row['notes'];
					$request_submitdate = $row['request_submitdate'];
					$request_startdate = $row['request_startdate'];
					$request_completedate = $row['request_completedate'];
					$status = $row['request_status'];

					switch ($status) {
						case 'complete': $statustd = "positive"; break;
						case 'submitted': $statustd = "warning"; break;
						case 'assigned': $statustd = "negative"; break;
						default: $statustd = "";
					}
					
					if ($shortname == "") { $shortname = "(blank)"; }
					?>
					<tr>
						<td><a href="datasetrequests.php?action=editform&datasetrequestid=<?=$datasetrequestid?>"><?=$shortname?></a></td>
						<td><?=$request_submitdate?></td>
						<td><?=$username?></td>
						<td class="<?=$statustd?>"><?=ucfirst($status)?></td>
						<td><?=$request_startdate?></td>
						<td><?=$request_completedate?></td>
						<td><?=$idlist?></td>
						<td><?=$dataformat?></td>
						<td><?=$deliverymethod?></td>
						<td><a href="datasetrequests.php?action=takeownership&datasetrequestid=<?=$datasetrequestid?>" style="color: red">Take ownership</a></td>
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
