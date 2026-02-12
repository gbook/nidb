<?
 // ------------------------------------------------------------------------------
 // NiDB experiment.php
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

	require "functions.php";
	require "includes_php.php";

	//PrintVariable($_FILES);
	//PrintVariable($_POST);

	/* check if this page is being called from itself */
	$referringpage = $_SERVER['HTTP_REFERER'];
	$phpfilename = pathinfo(__FILE__)['basename'];
	if (contains($referringpage, $phpfilename))
		$selfcall = true;
	else
		$selfcall = false;
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$expid = GetVariable("expid");
	$projectid = GetVariable("projectid");
	$experimentname = GetVariable("experimentname");
	$experimentdesc = GetVariable("experimentdesc");
	$filedeleteids = GetVariable("filedeleteids");
	$fileid = GetVariable("fileid");
	
	/* determine action */
	if (($action == "download") && ($selfcall)) {
		DownloadExperiment($expid);
	}
	else {
?>
<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Experiments</title>
	</head>
<body>
	<div id="wrapper">
<?
		require "includes_html.php";
		require "menu.php";

		if (($action == "editform") && ($selfcall))  {
			DisplayExperimentForm($projectid, "edit", $expid);
		}
		elseif (($action == "addform") && ($selfcall)) {
			DisplayExperimentForm($projectid, "add", "");
		}
		elseif (($action == "update") && ($selfcall)) {
			if (UpdateExperiment($expid, $experimentname, $experimentdesc, $filedeleteids))
				DisplayExperimentList($projectid);
			else
				DisplayExperimentForm($projectid, "edit", $expid);
		}
		elseif (($action == "add") && ($selfcall)) {
			AddExperiment($projectid, $experimentname, $experimentdesc);
			DisplayExperimentList($projectid);
		}
		elseif (($action == "delete") && ($selfcall)) {
			DeleteExperiment($expid);
			DisplayExperimentList($projectid);
		}
		elseif (($action == "viewfile") && ($selfcall)) {
			ViewFile($projectid, $fileid);
		}
		else {
			DisplayExperimentList($projectid);
		}
	}	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateExperiment ------------------- */
	/* -------------------------------------------- */
	function UpdateExperiment($expid, $experimentname, $experimentdesc, $filedeleteids) {
		/* perform data checks */
		$experimentname = mysqli_real_escape_string($GLOBALS['linki'], $experimentname);
		$experimentdesc = mysqli_real_escape_string($GLOBALS['linki'], $experimentdesc);
		
		/* update the experiment */
		$sqlstring = "update experiments set exp_version = exp_version + 1, exp_name = '$experimentname', exp_desc = '$experimentdesc', exp_modifydate = now() where experiment_id = $expid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* remove files to be removed last... in case the user updated an option and wanted to delete it as well */
		if (count($filedeleteids) > 0) {
			$deletelist = implode2(",", $filedeleteids);
			$sqlstring = "delete from experiment_files where experimentfile_id in ($deletelist)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* add new files */
		if( isset($_FILES['files']['name'])) {
			$total_files = count($_FILES['files']['name']);
			for($key = 0; $key < $total_files; $key++) {
				// Check if file is selected
				if(isset($_FILES['files']['name'][$key]) && $_FILES['files']['size'][$key] > 0) {
					$fileFilename = $_FILES['files']['name'][$key];
					$fileData = base64_encode(file_get_contents($_FILES['files']['tmp_name'][$key]));
					$fileSize = $_FILES['files']['size'][$key];

					/* insert the new experiment files */
					$sqlstring = "insert into experiment_files (experiment_id, file_name, file, file_size,
file_createdate, file_modifydate) values($expid, '$fileFilename', '$fileData', $fileSize, now(), now())";
					//PrintSQL($sqlstring);
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		Notice("$experimentname updated");
		return true;
	}


	/* -------------------------------------------- */
	/* ------- AddExperiment ---------------------- */
	/* -------------------------------------------- */
	function AddExperiment($projectid, $experimentname, $experimentdesc) {
		/* perform data checks */
		$experimentname = mysqli_real_escape_string($GLOBALS['linki'], $experimentname);
		$experimentdesc = mysqli_real_escape_string($GLOBALS['linki'], $experimentdesc);

		/* insert the new experiment */
		$sqlstring = "insert into experiments (project_id, exp_version, exp_name, exp_desc, exp_modifydate, exp_createdate) values ($projectid, 1, '$experimentname', '$experimentdesc', now(), now())";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$expid = mysqli_insert_id($GLOBALS['linki']);

		if( isset($_FILES['files']['name'])) {
			$total_files = count($_FILES['files']['name']);
			for($key = 0; $key < $total_files; $key++) {
				// Check if file is selected
				if(isset($_FILES['files']['name'][$key]) && $_FILES['files']['size'][$key] > 0) {
					$fileFilename = $_FILES['files']['name'][$key];
					$fileData = base64_encode(file_get_contents($_FILES['files']['tmp_name'][$key]));
					$fileSize = filesize($_FILES['files']['tmp_name'][$key]) + 0;

					/* insert the new experiment files */
					$sqlstring = "insert into experiment_files (experiment_id, file_name, file, file_size,
file_modifydate, file_createdate) values($expid, '$fileFilename', '$fileData', $fileSize, now(), now())";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}

		Notice("$experimentname added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteExperiment ------------------- */
	/* -------------------------------------------- */
	function DeleteExperiment($expid) {
		if (!ValidID($expid,'Experiment ID')) { return; }
		
		$sqlstring = "delete from experiments where experiment_id = $expid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$sqlstring = "delete from experiment_files where experiment_id = $expid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Experiment deleted");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayExperimentForm -------------- */
	/* -------------------------------------------- */
	function DisplayExperimentForm($projectid, $type, $expid) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			if (!ValidID($expid,'Experiment ID'))
				return;
			
			$sqlstring = "select * from experiments where experiment_id = $expid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$experimentid = $row['experiment_id'];
			$name = $row['exp_name'];
			$desc = $row['exp_desc'];
		
			$formaction = "update";
			$formtitle = "$name";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "New Experiment";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div class="ui container">
			<div class="ui attached visible message">
				<div class="header"><?=$formtitle?></div>
			</div>
			<form method="post" action="experiment.php" enctype="multipart/form-data" class="ui form attached fluid raised segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="expid" value="<?=$expid?>">
			<input type="hidden" name="projectid" value="<?=$projectid?>">

			<div class="field">
				<label>Name</label>
				<div class="field">
					<input type="text" name="experimentname" value="<?=$name?>" maxlength="255" required>
				</div>
			</div>

			<div class="field">
				<label>Description (Task instructions, README, how to analyze, etc)</label>
				<div class="field">
					<textarea name="experimentdesc"><?=$desc?></textarea>
				</div>
			</div>
			
			<div class="field">
				<label>File(s)</label>
				<div class="field">
					<? if ($type == "edit") { ?>
					<table class="ui celled selectable small very compact table">
						<thead>
							<th>File</th>
							<th>Size</th>
							<th>Create date</th>
							<th>Modify date</th>
							<th>Remove?</th>
						</thead>
						<?
						$sqlstring = "select * from experiment_files where experiment_id = $expid order by file_name asc";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$fileid = $row['experimentfile_id'];
							$file = $row['file_name'];
							$filesize = $row['file_size'];
							$createdate = date('M j, Y h:ia',strtotime($row['file_createdate']));
							$modifydate = date('M j, Y h:ia',strtotime($row['file_modifydate']));
							?>
							<tr>
								<td><tt style="font-size: larger"><a href="experiment.php?action=viewfile&projectid=<?=$projectid?>&fileid=<?=$fileid?>"><?=$file?></a></tt></td>
								<td><?=$filesize?></td>
								<td style="font-size: smaller"><?=$createdate?></td>
								<td style="font-size: smaller"><?=$modifydate?></td>
								<td><input type="checkbox" name="filedeleteids[]" value="<?=$fileid?>"></td>
							</tr>
							<?
						}
						?>
					</table>
					<br>
					<? } ?>
					Add file(s) <input type="file" name="files[]" multiple>
					<span class="tiny">Max individual file size 4GB. Max filename length 255 characters</span>
				</div>
			</div>
			<div class="ui two column grid">
				<div class="column">
					<a class="ui red button" href="experiment.php?expid=1&projectid=1&action=delete" onclick="return confirm('Are you sure you want to delete?')"><i class="trash icon"></i>Delete</a>
				</div>
				<div class="column" align="right">
					<a class="ui button" href="experiment.php?projectid=<?=$projectid?>">Cancel</a>
					<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
				</div>
			</form>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayExperimentList -------------- */
	/* -------------------------------------------- */
	function DisplayExperimentList($projectid) {
		if (!ValidID($projectid,'Project ID')) { return; }
		
		?>
		<div class="ui container">
			<button class="ui primary large button" onClick="window.location.href='experiment.php?projectid=<?=$projectid?>&action=addform'; return false;"><i class="plus square outline icon"></i> Create experiment</button>
			<br><br>
			<table class="ui celled selectable grey compact table">
				<thead>
					<tr>
						<th>Name</th>
						<th>File(s)</th>
						<th>Download</th>
						<th>Version</th>
						<th>Create date</th>
						<th>Modify date</th>
						<th>Add to package</th>
					</tr>
				</thead>
				<tbody>
					<?
						$sqlstring = "select * from experiments where project_id = $projectid order by exp_name asc";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$expid = $row['experiment_id'];
							$name = $row['exp_name'];
							$version = $row['exp_version'];
							$createdate = date('M j, Y h:ia',strtotime($row['exp_createdate']));
							$modifydate = date('M j, Y h:ia',strtotime($row['exp_modifydate']));
							
							?>
							<tr>
								<td valign="top">
									<a href="experiment.php?action=editform&expid=<?=$expid?>&projectid=<?=$projectid?>"><b><?=$name?></b></a>
								</td>
								<td valign="top" align="center">
								<?
									$sqlstringA = "select sum(file_size) total_size, count(file_name) file_count from experiment_files where experiment_id = $expid";
									$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
									if (mysqli_num_rows($resultA) > 0) {
										$rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
										$size = HumanReadableFilesize($rowA['total_size']);
										$count = $rowA['file_count'];
										
										if ($count > 0)
											echo "$count files, $size\n";
										else
											echo "-";
									}
									?>
								</td>
								<td valign="top" class="ui center aligned"><a href="experiment.php?action=download&expid=<?=$expid?>" title="Download <?=$name?> experiment"><i class="large download icon"></i></a></td>
								<td valign="top"><?=$version?></td>
								<td valign="top"><?=$createdate?></td>
								<td valign="top"><?=$modifydate?></td>
								<td valign="top" class="ui center aligned"><a href="packages.php?action=addobject&objecttype=experiment&objectids[]=<?=$expid?>"><img src="images/squirrel-icon-64.png" height="24"></img></a></td>
							</tr>
							<?
						}
					?>
				</tbody>
			</table>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- ViewFile --------------------------- */
	/* -------------------------------------------- */
	function ViewFile($projectid, $fileid) {

		/* perform data checks */
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$fileid = mysqli_real_escape_string($GLOBALS['linki'], $fileid);
		
		if (!ValidID($projectid,'Project ID')) { return; }
		if (!ValidID($fileid,'File ID')) { return; }
		
		$sqlstring = "select * from experiment_files where experimentfile_id = $fileid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$expid = $row['experiment_id'];
			$fileid = $row['experimentfile_id'];
			$filename = $row['file_name'];
			$contents = base64_decode($row['file']);
			$filesize = $row['file_size'];
			$createdate = date('M j, Y h:ia',strtotime($row['file_createdate']));
			$modifydate = date('M j, Y h:ia',strtotime($row['file_modifydate']));
			?>
			
			<div class="ui text container">
				<a href="experiment.php?action=editform&expid=<?=$expid?>&projectid=<?=$projectid?>" class="ui primary button"><b><i class="arrow alternate circle left icon"></i> Back</b></a>
				<br>
				<table class="ui very simple very compact small celled table">
					<tr>
						<td>File name</td>
						<td><h3 class="ui header"><?=$filename?></h3></td>
					</tr>
					<tr>
						<td>File size</td>
						<td><?=$filesize?> <span class="tiny">bytes</span></td>
					</tr>
					<tr>
						<td>Create date</td>
						<td><?=$createdate?></td>
					</tr>
					<tr>
						<td>Modify date</td>
						<td><?=$modifydate?></td>
					</tr>
				</table>
			</div>
			<div style="padding: 20px">
				Displaying entire file
				<tt><pre style="text-align: left; padding: 15px; border: 1px solid gray"><?=$contents?></pre></tt>
			</div>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- DownloadExperiment ----------------- */
	/* -------------------------------------------- */
	function DownloadExperiment($experimentid) {
		$experimentid = mysqli_real_escape_string($GLOBALS['linki'], $experimentid);

		if (!ValidID($experimentid,'Experiment ID')) { return; }
		
		/* get list of files, and get the files */
		$sqlstring = "select * from experiment_files where experiment_id = $experimentid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			
			/* create temp dir */
			$tmppath = $GLOBALS['cfg']['tmpdir'] . '/' . GenerateRandomString(20);
			
			/* create the directory in which the files are stored until the import module takes them */
			mkdir($tmppath, 0, true);
			chmod($tmppath, 0777);
			
			$sqlstring = "select exp_name from experiments where experiment_id = $experimentid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$expname = $row['exp_name'];
				$expname = preg_replace("/[^A-Za-z0-9]/", '', $expname);
			}
			else {
				$expname = "experiment";
			}
			
			$zipfilename = "$tmppath/$expname.zip";
			
			$sqlstringA = "select * from experiment_files where experiment_id = $experimentid";
			$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$fileid = $rowA['experimentfile_id'];
				$filename = $rowA['file_name'];
				$contents = base64_decode($rowA['file']);
				$filesize = $rowA['file_size'];
				$createdate = date('M j, Y h:ia',strtotime($rowA['file_createdate']));
				$modifydate = date('M j, Y h:ia',strtotime($rowA['file_modifydate']));
				
				/* write file to temp dir */
				//echo "Writing to $tmppath/$filename<br><br>$contents<br><br>";
				if (file_put_contents("$tmppath/$filename", $contents) === false){
					//echo "$tmppath/$filename file not written<br>";
				}
				
				if (file_exists("$tmppath/$filename")) {
					//echo "$tmppath/$filename exists. Size " . filesize("$tmppath/$filename") . "<br>";
				}
				else {
					//echo "$tmppath/$filename does not exist.<br>";
				}
			}
			
			$systemstring = "cd $tmppath; zip $zipfilename *";
			//PrintVariable($systemstring);
			shell_exec($systemstring);
			
			/* send the .zip file to the browser */
			header("Content-Description: File Transfer");
			header("Content-Disposition: attachment; filename=$expname.zip");
			header("Content-Type: application/zip");
			header("Content-length: " . filesize($zipfilename) . "\n\n");
			header("Content-Transfer-Encoding: binary");
			// output data to the browser
			readfile($zipfilename);
			unlink($zipfilename);
			rmdir($tmppath);
			exit(0);
		}
	}
	
?>


<? include("footer.php") ?>
