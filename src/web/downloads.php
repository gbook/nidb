<?
 // ------------------------------------------------------------------------------
 // NiDB publicdownloads.php
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
	
	$nologin = true;
?>
<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Public Downloads</title>
	</head>

<body>
<?	
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
?>
	<br>
	<div class="ui container">
		<div class="ui top attached inverted styled segment">
			<div class="ui grid">
				<div class="ui eight wide column">
					<h1 class="ui inverted header">
						<div class="content">
							Publicly Available Datasets
							<div class="sub header">Datasets available from <? =$GLOBALS['cfg']['sitename']?></div>
						</div>
					</h1>
				</div>
				<div class="ui eight wide right aligned column">
					<div class="ui styled green compact segment">
						<? if ($_SESSION['username'] == "") { ?>
						<a href="signup.php">Create</a> an account | <a href="login.php">Sign in</a>
						<? } else {?>
						You are logged into NiDB as <? =$_SESSION['username'];?><br>
						<? } ?>
					</div>
				</div>
			</div>
		</div>
		
		<div class="ui bottom attached icon message">
			<i class="yellow info circle icon"></i>
			<div class="content">
				<p><b>Note</b> Some downloads may require registration or an approved application to download data</p>
			</div>
		</div>
		
		<br><br>

<?

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	
	/* determine action */
	if ($action == "changepassword") {
		ChangePassword($id);
	}
	elseif ($action == "delete") {
		DeleteDownload($id);
	}
	else {
		DisplayDatasetList();
	}

	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayDatasetList ----------------- */
	/* -------------------------------------------- */
	function DisplayDatasetList() {
	?>
	
	<div class="ui container">

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
						<div class="ui content">
							<div class="ui two column grid">
								<div class="ui column">
									<a class="ui header"><? =$name?></a>
									<div class="meta">
										<span class="cinema">Created <? =$createdate?></span>
									</div>
									<div class="description">
										<p><? =$desc?></p>
									</div>
									<div class="extra">
										<? if (in_array("REQUIRES_REGISTRATION", $flags)) { ?><div class="ui basic orange label" title="Registration on this NiDB instance is required to download this dataset">Registration required</div><? } ?>
										<? if (in_array("REQUIRES_APPROVFAL", $flags)) { ?><div class="ui basic red label" title="An application must be submitted and approved to access this dataset">Application required</div><? } ?>
									</div>
								</div>
								<div class="right aligned column">
									<? if (isAdmin()) { ?>
									<a class="ui button" href="publicdatasets.php?action=form&id=<? =$id?>"><i class="pencil alternate icon"></i> Edit</a>
									<?} ?>
									<a class="ui button" href="publicdatasets.php?action=view&id=<? =$id?>"><i class="eye icon"></i> View Dataset</a>
								</div>
							</div>
							<div class="ui segment">
								Available downloads for this dataset
							</div>
						</div>
					</div>		
				<?
			}
		?>
		</div>
	</div>
	<?
	}
?>
