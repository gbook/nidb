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
		
		DisplayNotice("Dataset Request", "$shortname submitted");
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
		
		DisplayNotice("Dataset Request", "$shortname updated");
	}
	

	/* -------------------------------------------- */
	/* ------- CancelDatasetRequest --------------- */
	/* -------------------------------------------- */
	function CancelDatasetRequest($id) {
		$sqlstring = "update dataset_requests set request_status = 'cancelled' where datasetrequest_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		DisplayNotice("Dataset Request", "$id cancelled");
	}


	/* -------------------------------------------- */
	/* ------- TakeOwnershipOfDatasetRequest ------ */
	/* -------------------------------------------- */
	function TakeOwnershipOfDatasetRequest($id) {
		$sqlstring = "update dataset_requests set request_status = 'assigned', admin_username = '" . $GLOBALS['username'] . "', request_startdate = now() where datasetrequest_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		DisplayNotice("Dataset Request", "$id assigned");
	}


	/* -------------------------------------------- */
	/* ------- MarkDatasetRequestComplete --------- */
	/* -------------------------------------------- */
	function MarkDatasetRequestComplete($id) {
		$sqlstring = "update dataset_requests set request_status = 'complete', request_completedate = now() where datasetrequest_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		DisplayNotice("Dataset Request", "$id marked as complete");
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
		<div align="center">
		<table class="entrytable">
			<form method="post" action="datasetrequests.php" autocomplete="off">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="datasetrequestid" value="<?=$id?>">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Email</td>
				<td><input type="email" name="email" value="<?=$email?>" maxlength="255" required></td>
			</tr>
			<tr>
				<td>Institution</td>
				<td><input type="text" name="institution" value="<?=$institution?>" maxlength="255" required></td>
			</tr>
			<tr>
				<td>Short name</td>
				<td><input type="text" name="shortname" value="<?=$shortname?>" maxlength="255" required></td>
			</tr>
			<tr>
				<td>ID list<br><span class="tiny">List of IDs you are requesting. Specifiy the type of ID</span></td>
				<td><textarea name="idlist" required><?=$idlist?></textarea></td>
			</tr>
			<tr>
				<td>Data Format<br><span class="tiny">BIDS, Nifti, anonymized DICOM, directory structure, etc.</span></td>
				<td><textarea name="dataformat" required><?=$dataformat?></textarea></td>
			</tr>
			<tr>
				<td>Delivery method<br><span class="tiny">How do you want to get the data. Email passwords separately</span></td>
				<td><textarea name="deliverymethod" required><?=$deliverymethod?></textarea></td>
			</tr>
			<tr>
				<td>Notes<br><span class="tiny">List of IDs you are requesting. Specifiy the type of ID</span></td>
				<td><textarea name="notes" required><?=$notes?></textarea></td>
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
	/* ------- DisplayDatasetRequestList ---------- */
	/* -------------------------------------------- */
	function DisplayDatasetRequestList() {
	?>

	<a href="datasetrequests.php?action=addform" class="linkbutton">Submit New Dataset Request</a>
	<br><br><br>
	
	My Requests
	<br><br>
	<table class="graydisplaytable">
		<thead>
			<tr>
				<th style="padding: 10px;">Shortname</th>
				<th style="padding: 10px;">Submit Date</th>
				<th style="padding: 10px;">Admin Username</th>
				<th style="padding: 10px;">Status</th>
				<th style="padding: 10px;">Start Date</th>
				<th style="padding: 10px;">Complete Date</th>
				<th style="padding: 10px;">ID List</th>
				<th style="padding: 10px;">Data Format</th>
				<th style="padding: 10px;">Delivery Method</th>
				<th style="padding: 10px;">Cancel</th>
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
					$request_status = $row['request_status'];
					$admin_username = $row['admin_username'];

					if ($shortname == "") { $shortname = "(blank)"; }
					?>
					<tr>
						<td style="padding: 8px;"><a href="datasetrequests.php?action=editform&datasetrequestid=<?=$datasetrequestid?>"><?=$shortname?></a></td>
						<td style="padding: 8px;"><?=$request_submitdate?></td>
						<td style="padding: 8px;"><?=$admin_username?></td>
						<td style="padding: 8px;"><?=$request_status?></td>
						<td style="padding: 8px;"><?=$request_startdate?></td>
						<td style="padding: 8px;"><?=$request_completedate?></td>
						<td style="padding: 8px;"><?=$idlist?></td>
						<td style="padding: 8px;"><?=$dataformat?></td>
						<td style="padding: 8px;"><?=$deliverymethod?></td>
						<td style="padding: 8px;"><a href="datasetrequests.php?action=cancel&datasetrequestid=<?=$datasetrequestid?>" style="color: red">Cancel</a></td>
					</tr>
					<? 
				}
			?>
		</tbody>
	</table>

	<?
		if ($GLOBALS['isadmin']) {
	?>
	<br><br>
	Requests Assigned to Me
	<br><br>
	<table class="graydisplaytable">
		<thead>
			<tr>
				<th style="padding: 10px;">Shortname</th>
				<th style="padding: 10px;">Submit Date</th>
				<th style="padding: 10px;">Username</th>
				<th style="padding: 10px;">Status</th>
				<th style="padding: 10px;">Start Date</th>
				<th style="padding: 10px;">Complete Date</th>
				<th style="padding: 10px;">ID List</th>
				<th style="padding: 10px;">Data Format</th>
				<th style="padding: 10px;">Delivery Method</th>
				<th style="padding: 10px;">Mark Complete</th>
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
					$request_status = $row['request_status'];

					if ($shortname == "") { $shortname = "(blank)"; }
					?>
					<tr>
						<td style="padding: 8px;"><a href="datasetrequests.php?action=editform&datasetrequestid=<?=$datasetrequestid?>"><?=$shortname?></a></td>
						<td style="padding: 8px;"><?=$request_submitdate?></td>
						<td style="padding: 8px;"><?=$username?></td>
						<td style="padding: 8px;"><?=$request_status?></td>
						<td style="padding: 8px;"><?=$request_startdate?></td>
						<td style="padding: 8px;"><?=$request_completedate?></td>
						<td style="padding: 8px;"><?=$idlist?></td>	
						<td style="padding: 8px;"><?=$dataformat?></td>
						<td style="padding: 8px;"><?=$deliverymethod?></td>
						<td style="padding: 8px;"><a href="datasetrequests.php?action=markcomplete&datasetrequestid=<?=$datasetrequestid?>" style="color: red">Mark as complete</a></td>
					</tr>
					<? 
				}
			?>
		</tbody>
	</table>
	
	<br><br>
	Unassigned Requests
	<br><br>
	<table class="graydisplaytable">
		<thead>
			<tr>
				<th style="padding: 10px;">Shortname</th>
				<th style="padding: 10px;">Submit Date</th>
				<th style="padding: 10px;">Username</th>
				<th style="padding: 10px;">Status</th>
				<th style="padding: 10px;">Start Date</th>
				<th style="padding: 10px;">Complete Date</th>
				<th style="padding: 10px;">ID List</th>
				<th style="padding: 10px;">Data Format</th>
				<th style="padding: 10px;">Delivery Method</th>
				<th style="padding: 10px;">Take ownership</th>
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
					$request_status = $row['request_status'];

					if ($shortname == "") { $shortname = "(blank)"; }
					?>
					<tr>
						<td style="padding: 8px;"><a href="datasetrequests.php?action=editform&datasetrequestid=<?=$datasetrequestid?>"><?=$shortname?></a></td>
						<td style="padding: 8px;"><?=$request_submitdate?></td>
						<td style="padding: 8px;"><?=$username?></td>
						<td style="padding: 8px;"><?=$request_status?></td>
						<td style="padding: 8px;"><?=$request_startdate?></td>
						<td style="padding: 8px;"><?=$request_completedate?></td>
						<td style="padding: 8px;"><?=$idlist?></td>
						<td style="padding: 8px;"><?=$dataformat?></td>
						<td style="padding: 8px;"><?=$deliverymethod?></td>
						<td style="padding: 8px;"><a href="datasetrequests.php?action=takeownership&datasetrequestid=<?=$datasetrequestid?>" style="color: red">Take ownership</a></td>
					</tr>
					<? 
				}
			?>
		</tbody>
	</table>
	
	<?
		}
	}
?>

<? include("footer.php") ?>
