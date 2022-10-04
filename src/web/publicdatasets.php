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
	
	//PrintVariable($_POST);
	
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
		case 'delete':
			DeleteDataset($id);
			break;
		case 'view':
			ViewDataset($id);
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

		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		$formtitle = "Create New Dataset";
		$formaction = "add";
		$buttonlabel = "Create Dataset";
		
		if ($id != "") {
			$sqlstring = "select * from public_datasets where publicdataset_id = $id";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			
			$name = $row['publicdataset_name'];
			$desc = $row['publicdataset_desc'];
			$startdate = $row['publicdataset_startdate'];
			$enddate = $row['publicdataset_enddate'];
			$flags = explode(",", $row['publicdataset_flags']);
			$createdate = $row['publicdataset_createdate'];
			$createdby = $row['publicdataset_createdby'];

			if (in_array("REQUIRES_REGISTRATION", $flags)) { $requiresregistration = "checked"; }
			if (in_array("REQUIRES_APPROVAL", $flags)) { $requiresapproval = "checked"; }
			
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
					<textarea name="desc"><?=$desc?>"</textarea>
				</div>
			</div>

			<div class="two fields">
				<div class="field">
					<label>Start date <span class="tiny">(Date dataset is available)</span></label>
					<div class="field">
						<input type="text" name="startdate" value="<?=$startdate?>">
					</div>
				</div>
				<div class="field">
					<label>End date <span class="tiny">(Date the dataset will become unavailable)</span></label>
					<div class="field">
						<input type="text" name="enddate" value="<?=$enddate?>">
					</div>
				</div>
			</div>

			<div class="field">
				<label>Options</label>
				<div class="field">
					<div class="ui checkbox">
						<input type="checkbox" name="flag_registration" value="1" <?=$requiresregistration?>>
						<label>Require registration</label>
					</div>
				</div>
				<div class="field">
					<div class="ui checkbox">
						<input type="checkbox" name="flag_application" value="1" <?=$requiresapproval?>>
						<label>Require application & approval</label>
					</div>
				</div>
			</div>
			
			<input class="ui primary button" type="submit" value="<?=$buttonlabel?>">
			
			</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ViewDataset ------------------------ */
	/* -------------------------------------------- */
	function ViewDataset($id) {

		$publicdatasetid = mysqli_real_escape_string($GLOBALS['linki'], $id);
		
		$sqlstring = "select * from public_datasets where publicdataset_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		
		$name = $row['publicdataset_name'];
		$desc = $row['publicdataset_desc'];
		$startdate = $row['publicdataset_startdate'];
		$enddate = $row['publicdataset_enddate'];
		$flags = explode(",", $row['publicdataset_flags']);
		$createdate = $row['publicdataset_createdate'];
		$createdby = $row['publicdataset_createdby'];

		?>
		<div class="ui text container">
			<h1 class="ui header">
				<?=$name?>
				<div class="sub header">
				<?=$desc?>
				</div>
			</h1>
			Available <?=$startdate?> to <?=$enddate?>
			<br>
			<? if (in_array("REQUIRES_REGISTRATION", $flags)) { ?><div class="ui label" title="Registration on this NiDB instance is required to download this dataset">Registration required</div><? } ?>
			<? if (in_array("REQUIRES_APPROVFAL", $flags)) { ?><div class="ui label" title="An application must be submitted and approved to access this dataset">Application required</div><? } ?>
			<br><br>
		<?
		
		$sqlstring = "select * from publicdataset_downloads where dataset_id $publicdataset_id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$numdownloads = mysqli_num_rows($result);
		if ($numdownloads > 0) {
			?>
			This dataset has <?=$numdownloads?> downloads available
			<div class="ui accordion"><?
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$downloadname = $row['download_name'];
				$downloaddesc = $row['download_desc'];
				$downloadzipsize = $row['download_zipsize'];
				$downloadunzipsize = $row['download_unzipsize'];
				$downloadnumfiles = $row['download_numfiles'];
				$downloadfilelist = $row['download_filelist'];
				$downloadpackageformat = $row['download_packageformat'];
				$downloadimageformat = $row['download_imageformat'];
				$downloadkey = $row['download_key'];
				$downloadnumdownloads = $row['download_numdownloads'];
				?>
					<div class="title">
						<i class="dropdown icon"></i>
						<?=$downloadname?>
					</div>
					<div class="content">
						<p><?=$downloaddesc?></p>
					</div>
				<?
			}
			?></div><?
		}
		else {
			?>No downloads available<?
		}
		?>
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
				<? if (isAdmin()) { ?>
				<a class="ui primary button" href="publicdatasets.php?action=form"><i class="plus icon"></i> New Dataset</a>
				<? } ?>
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
							<i class="huge copy outline icon"></i>
						</div>
						<div class="ui content">
							<div class="ui two column grid">
								<div class="ui column">
									<a class="ui header"><?=$name?></a>
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
								<div class="right aligned column">
									<? if (isAdmin()) { ?>
									<a class="ui button" href="publicdatasets.php?action=form&id=<?=$id?>"><i class="pencil alternate icon"></i> Edit</a>
									<?} ?>
									<a class="ui button" href="publicdatasets.php?action=view&id=<?=$id?>"><i class="eye icon"></i> View Dataset</a>
								</div>
							</div>
							<div class="ui fitted segment">
								Available downloads for this dataset
							</div>
						</div>
					</div>		
				<?
			}
		?>
	</div>
	<?
	}
?>


<? include("footer.php") ?>
