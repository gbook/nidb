<?
 // ------------------------------------------------------------------------------
 // NiDB publicdatasets.php
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
		<title>NiDB - Manage Public Datasets</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nidbapi.php";
	
	PrintVariable($_POST);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
    $name = GetVariable("name");
    $desc = GetVariable("desc");
    $startdate = GetVariable("startdate");
    $enddate = GetVariable("enddate");
    $flag_registration = GetVariable("flag_registration");
    $flag_application = GetVariable("flag_application");
	
	/* determine action */
	switch ($action) {
		//case 'changepassword':
		//	ChangePassword($id);
		//	break;
		case 'delete':
			DeleteDataset($id);
			break;
		case 'form':
			DisplayDatasetForm($id);
			break;
		case 'add':
			AddDataset($name, $desc, $startdate, $enddate, $flag_registration, $flag_application);
			DisplayDatasetList();
			break;
		case 'update':
			UpdateDataset($id, $name, $desc, $startdate, $enddate, $flag_registration, $flag_application);
			break;
		default:
			DisplayDatasetList();
	}

	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- ChangePassword --------------------- */
	/* -------------------------------------------- */
	function ChangePassword($id, $password) {
		/* perform data checks */
		$pwd = sha1($password);
		
		/* update the site */
		//$sqlstring = "update public_datasets set pd_password = '$pwd' where pd_id = $id";
		//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$id?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteDataset ---------------------- */
	/* -------------------------------------------- */
	function DeleteDataset($id) {
		//$sqlstring = "delete from nidb_sites where site_id = $id";
		//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- AddDataset ------------------------- */
	/* -------------------------------------------- */
	function AddDataset($name, $desc, $startdate, $enddate, $flag_registration, $flag_application) {
		$name = mysqli_real_escape_string($GLOBALS['linki'], $name);
		$desc = mysqli_real_escape_string($GLOBALS['linki'], $desc);
		$startdate = mysqli_real_escape_string($GLOBALS['linki'], $startdate);
		$enddate = mysqli_real_escape_string($GLOBALS['linki'], $enddate);
		$flag_registration = mysqli_real_escape_string($GLOBALS['linki'], $flag_registration);
		$flag_application = mysqli_real_escape_string($GLOBALS['linki'], $flag_application);

		if ($startdate == "")
			$startdate = "now()";
		else
			$startdate = "'$startdate'";
		
		if ($enddate == "")
			$enddate = "null";
		else
			$enddate = "'$enddate'";
		
		$flags = array();
		if ($flag_registration) $flags[] = "REQUIRES_REGISTRATION";
		if ($flag_application) $flags[] = "REQUIRES_APPROVAL";
		if (count($flags) > 0) {
			$flagstr = "'" . implode(",", $flags) . "'";
		}
		else
			$flagstr = "null";
		
		$username = $_SESSION['username'];

		$sqlstring = "insert into public_datasets (publicdataset_name, publicdataset_desc, publicdataset_startdate, publicdataset_enddate, publicdataset_flags, publicdataset_createdate, publicdataset_createdby) values ('$name', '$desc', $startdate, $enddate, $flagstr, now(), '$username')";
		PrintSQL($sqlstring);
		
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisplayDatasetForm ----------------- */
	/* -------------------------------------------- */
	function DisplayDatasetForm($id) {
		
		$formtitle = "Create New Dataset";
		$formaction = "add";
		$buttonlabel = "Create Dataset";
		
		if ($id != "") {
			$sqlstring = "select * from public_datasets where publicdataset_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$name = $row['publicdataset_name'];
			$desc = $row['publicdataset_desc'];
			$startdate = $row['publicdataset_startdate'];
			$enddate = $row['publicdataset_enddate'];
			$flags = explode(",", $row['publicdataset_flags']);
			$createdate = $row['publicdataset_createdate'];
			$createdby = $row['publicdataset_createdby'];
			
			$formtitle = "Editing $name";
			$formaction = "edit";
			$buttonlabel = "Update";
		}
		?>
		<div class="ui text container">
			<div class="ui attached visible message">
			  <div class="header"><?=$formtitle?></div>
			</div>

			<form method="post" action="publicdatasets.php" autocomplete="off" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">

			<!--<h3 class="ui header">Dataset information</h3>-->
			
			<div class="field">
				<label>Name</label>
				<div class="field">
					<input type="text" name="name" value="<?=$name?>">
				</div>
			</div>
			
			<div class="field">
				<label>Description</label>
				<div class="field">
					<input type="text" name="desc" value="<?=$desc?>">
				</div>
			</div>

			<div class="two fields">
				<div class="field">
					<label>Start date</label>
					<div class="field">
						<input type="text" name="startdate" value="<?=$startdate?>">
					</div>
				</div>
				<div class="field">
					<label>End date</label>
					<div class="field">
						<input type="text" name="enddate" value="<?=$enddate?>">
					</div>
				</div>
			</div>

			<div class="field">
				<label>Options</label>
				<div class="field">
					<div class="ui checkbox">
						<input type="checkbox" name="flag_registration" value="1" <?=$requireregistration?>>
						<label>Require registration</label>
					</div>
				</div>
				<div class="field">
					<div class="ui checkbox">
						<input type="checkbox" name="flag_application" value="1" <?=$requireapplication?>>
						<label>Require application</label>
					</div>
				</div>
			</div>
			
			<input class="ui primary button" type="submit" value="<?=$buttonlabel?>">
			
			</form>
		</div>
		<?
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayDatasetList ----------------- */
	/* -------------------------------------------- */
	function DisplayDatasetList() {
	?>
	<script>
		function CopyToClipboard(id) {
			/* Get the text field */
			var copyText = document.getElementById(id);

			/* Select the text field */
			copyText.select();
			copyText.setSelectionRange(0, 99999); /* For mobile devices */

			/* Copy the text inside the text field */
			navigator.clipboard.writeText(copyText.value);

			/* Alert the copied text */
			alert("Link copied!");
		}
	</script>

	<div class="ui container">
		<div class="ui segment">
			<h1 class="ui header">
				Public Datasets
				<div class="sub header">
				Publicly available datasets
				</div>
			</h1>
		</div>
		<div class="ui two column grid">
			<div class="ui column">
				<a class="ui button" href="">Show all datasets</a>
			</div>
			<div class="right aligned column">
				<a class="ui primary button" href="publicdatasets.php?action=form"><i class="plus icon"></i> New Dataset</a>
			</div>
		</div>
		
		<br><br>
		<div class="ui horizontal divider">Available datasets</div>
		
		<div class="ui divided items">
		<?
			$sqlstring = "select * from public_datasets where publicdataset_createdby = '" . $_SESSION['username'] . "' order by publicdataset_createdate desc";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$id = $row['publicdataset_id'];
				$name = $row['publicdataset_name'];
				$desc = $row['publicdataset_desc'];
				$startdate = $row['publicdataset_startdate'];
				$enddate = $row['publicdataset_enddate'];
				$flags = explode(",", $row['publicdataset_flags']);
				$createdate = $row['publicdataset_createdate'];
				$createdby = $row['publicdataset_createdby'];
				?>
					<div class="item">
						<div class="image">
							<i class="huge box open icon"></i>
						</div>
						<div class="content">
							<a class="header"><?=$name?></a>
							<div class="meta">
								<span class="cinema">Created <?=$createdate?></span>
							</div>
							<div class="description">
								<p><?=$desc?></p>
							</div>
							<div class="extra">
								<? if (in_array("REQUIRES_REGISTRATION", $flags)) { ?><div class="ui label" title="Registration on this NiDB instance is required to download this dataset">Registration required</div><? } ?>
								<? if (in_array("REQUIRES_APPROVFAL", $flags)) { ?><div class="ui label" title="An application must be submitted and approved to access this dataset">Application required</div><? } ?>
							</div>
						</div>
					</div>		
				<?
			}
		?>
	</div>
	
	<table class="ui small celled selectable grey compact table">
		<thead>
			<tr>
				<th>Description</th>
				<th>Status</th>
				<th>Created</th>
				<th>Expires</th>
				<th>Release notes</th>
				<th>Zip size<br><span class="tiny" style="font-weight: normal">bytes</span></th>
				<th>Unzipped size<br><span class="tiny" style="font-weight: normal">bytes</span></th>
				<th>Creator</th>
				<th>Password</th>
				<th>Download link<br><span class="tiny" style="font-weight: normal">Copy link to use</span></th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from public_downloads where pd_createdby = '" . $_SESSION['username'] . "' or pd_shareinternal = 1 order by pd_createdate desc";
				//PrintSQL($sqlstring);
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['pd_id'];
					$createdate = $row['pd_createdate'];
					$expiredate = $row['pd_expiredate'];
					$expiredays = $row['pd_expiredays'];
					$createdby = $row['pd_createdby'];
					$zipsize = $row['pd_zippedsize'];
					$unzipsize = $row['pd_unzippedsize'];
					$filename = $row['pd_filename'];
					$desc = $row['pd_desc'];
					$notes = $row['pd_notes'];
					$filecontents = $row['pd_filecontents'];
					$shareinternal = $row['pd_shareinternal'];
					$registerrequired = $row['pd_registerrequired'];
					$password = strtoupper($row['pd_password']);
					$status = $row['pd_status'];
					$key = strtoupper($row['pd_key']);
			?>
			<tr>
				<td><?=$desc?></td>
				<td><?=$status?></td>
				<td style="font-size:9pt"><?=$createdate?></td>
				<td style="font-size:9pt"><?=$expiredate?></td>
				<td><i class="sticky note outline icon" title="<?=$notes?>"></i></td>
				<td style="font-size:9pt" align="right"><?=HumanReadableFilesize($zipsize)?></td>
				<td style="font-size:9pt" align="right"><?=HumanReadableFilesize($unzipsize)?></td>
				<td><?=$createdby?></td>
				<td style="font-size:8pt"><tt><?=$password?></tt></td>
				<td>
					<div class="ui action input">
						<input type="text" size="80" id="linktext<?=$id?>" value="<?=$GLOBALS['cfg']['siteurl'] . "/pd.php?k=$key"?>">
						<button class="ui button" onClick="CopyToClipboard('linktext<?=$id?>')" title="Copy only works when HTTPS is enabled :("><i class="copy icon"></i> Copy</button>
					</div>
				</td>
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
