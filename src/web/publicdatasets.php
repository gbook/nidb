<?
 // ------------------------------------------------------------------------------
 // NiDB publicdatasets.php
 // Copyright (C) 2004 - 2025
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
			<div class="ui top attached blue segment">
				This dataset has <?=$numdownloads?> downloads available
			</div>
			<div class="ui bottom attached segment">
				<div class="ui compact fluid accordion"><?
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
							<table class="ui compact table">
								<tr><td><b>Description</b></td><td><?=$downloaddesc?></td></tr>
								<tr><td><b>Zip size</b></td><td><?=HumanReadableFileSize($downloadzipsize)?></td></tr>
								<tr><td><b>Unzip size</b></td><td><?=HumanReadableFileSize($downloadunzipsize)?></td></tr>
								<tr><td><b>Number of files in package</b></td><td><?=number_format($downloadnumfiles)?></td></tr>
								<tr><td><b>Package format</b></td><td><?=$downloadpackageformat?></td></tr>
								<tr><td><b>Image format</b></td><td><?=$downloadimageformat?></td></tr>
							</table>
						</div>
					<?
				}
				?>
				</div>
			</div>
			<?
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
