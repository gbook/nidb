<?
 // ------------------------------------------------------------------------------
 // NiDB minipipeline.php
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
		<title>NiDB - Behavioral data analysis pipelines</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_FILES);
	//PrintVariable($_POST);

	/* check if this page is being called from itself */
	$referringpage = $_SERVER['HTTP_REFERER'];
	$phpscriptname = pathinfo(__FILE__)['basename'];
	if (contains($referringpage, $phpscriptname))
		$selfcall = true;
	else
		$selfcall = false;
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$mpid = GetVariable("mpid");
	$projectid = GetVariable("projectid");
	$minipipelinename = GetVariable("minipipelinename");
	$scriptexecutableids = GetVariable("scriptexecutableids");
	$scriptentrypointid = GetVariable("scriptentrypointid");
	$scriptdeleteids = GetVariable("scriptdeleteids");
	$scriptparams = GetVariable("scriptparams");
	$scriptid = GetVariable("scriptid");
	
	/* determine action */
	if (($action == "editform") && ($selfcall))  {
		DisplayMiniPipelineForm($projectid, "edit", $mpid);
	}
	elseif (($action == "addform") && ($selfcall)) {
		DisplayMiniPipelineForm($projectid, "add", "");
	}
	elseif (($action == "update") && ($selfcall)) {
		if (UpdateMiniPipeline($mpid, $minipipelinename, $scriptexecutableids, $scriptentrypointid, $scriptdeleteids, $scriptparams))
			DisplayMiniPipelineList($projectid);
		else
			DisplayMiniPipelineForm($projectid, "edit", $mpid);
	}
	elseif (($action == "viewjobs") && ($selfcall)) {
		DisplayMiniPipelineJobs($mpid, $projectid);
	}
	elseif (($action == "add") && ($selfcall)) {
		AddMiniPipeline($projectid, $minipipelinename);
		DisplayMiniPipelineList($projectid);
	}
	elseif (($action == "delete") && ($selfcall)) {
		DeleteMiniPipeline($mpid);
		DisplayMiniPipelineList($projectid);
	}
	elseif (($action == "viewscript") && ($selfcall)) {
		ViewScript($projectid, $scriptid);
	}
	elseif (($action == "rerun") && ($selfcall)) {
		ReRun($projectid, $scriptid);
	}
	else {
		DisplayMiniPipelineList($projectid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateMiniPipeline ----------------- */
	/* -------------------------------------------- */
	function UpdateMiniPipeline($mpid, $minipipelinename, $scriptexecutableids, $scriptentrypointid, $scriptdeleteids, $scriptparams) {
		/* perform data checks */
		$minipipelinename = mysqli_real_escape_string($GLOBALS['linki'], $minipipelinename);
		
		if (is_null($scriptentrypointid) || ($scriptentrypointid == "")) {
			echo "No entry point set. An entry point script must be set.<br><br>";
			//return false;
		}
			
		/* update the minipipeline */
		$sqlstring = "update minipipelines set mp_version = mp_version + 1, mp_name = '$minipipelinename', mp_modifydate = now() where minipipeline_id = $mpid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* update executable flags */
		$sqlstring = "update minipipeline_scripts set mp_executable = 0 where minipipeline_id = $mpid";
		if (count($scriptexecutableids) > 0) {
			$executablelist = implode2(",", $scriptexecutableids);
			$sqlstring = "update minipipeline_scripts set mp_executable = 1 where minipipelinescript_id in ($executablelist)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* update entry point flag */
		if ((!is_null($scriptentrypointid)) && ($scriptentrypointid > -1)) {
			/* remove previous flags */
			$sqlstring = "update minipipeline_scripts set mp_entrypoint = 0 where minipipeline_id = $mpid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

			/* set new flag */
			$sqlstring = "update minipipeline_scripts set mp_entrypoint = 1 where minipipelinescript_id = $scriptentrypointid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* update parameter lists */
		foreach ($scriptparams as $scriptid => $param) {
			$param = mysqli_real_escape_string($GLOBALS['linki'], $param);
			$sqlstring = "update minipipeline_scripts set mp_parameterlist = '$param' where minipipelinescript_id = $scriptid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* remove files to be removed last... in case the user updated an option and wanted to delete it as well */
		if (count($scriptdeleteids) > 0) {
			$deletelist = implode2(",", $scriptdeleteids);
			$sqlstring = "delete from minipipeline_scripts where minipipelinescript_id in ($deletelist)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* add new files */
		if( isset($_FILES['scripts']['name'])) {
			$total_files = count($_FILES['scripts']['name']);
			for($key = 0; $key < $total_files; $key++) {
				// Check if file is selected
				if(isset($_FILES['scripts']['name'][$key]) && $_FILES['scripts']['size'][$key] > 0) {
					$scriptFilename = $_FILES['scripts']['name'][$key];
					$scriptData = base64_encode(file_get_contents($_FILES['scripts']['tmp_name'][$key]));
					$scriptSize = $_FILES['scripts']['size'][$key];

					/* insert the new minipipeline scripts */
					$sqlstring = "insert into minipipeline_scripts (minipipeline_id, mp_version, mp_executable, mp_scriptname, mp_script, mp_scriptsize,
mp_scriptmodifydate, mp_scriptcreatedate) values($mpid, 1, 0, '$scriptFilename', '$scriptData', $scriptSize, now(), now())";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}
		
		?><div align="center"><span class="message"><?=$minipipelinename?> updated</span></div><br><br><?
		return true;
	}

	/* -------------------------------------------- */
	/* ------- DisplayMiniPipelineJobs ------------ */
	/* -------------------------------------------- */
	function DisplayMiniPipelineJobs($mpid, $projectid) {
		$sqlstring = "select a.*, b.mp_name from minipipeline_jobs a left join minipipelines b on a.minipipeline_id = b.minipipeline_id where a.minipipeline_id = $mpid order by a.mp_queuedate desc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//StartHTMLTable(array('Mini-pipeline', 'Queue date', 'Start date', 'Complete date', 'Status', 'Logs', 'Study', 'Modality', 'Rows inserted'), "ui celled selectable grey compact table");
		?>
		<table class="ui celled selectable grey compact table">
			<thead>
				<th>Mini-pipeline</th>
				<th>Queue date</th>
				<th>Start date</th>
				<th>Complete date</th>
				<th>Status</th>
				<th>Logs</th>
				<th>Study</th>
				<th>Modality</th>
				<th>Rows inserted</th>
			</thead>
			<tbody>
		<?
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$mpname = $row['mp_name'];
			$queuedate = $row['mp_queuedate'];
			$startdate = $row['mp_startdate'];
			$enddate = $row['mp_enddate'];
			$status = $row['mp_status'];
			$logs = $row['mp_log'];
			$modality = $row['mp_modality'];
			$seriesid = $row['mp_seriesid'];
			$numinserts = $row['mp_numinserts'];
			$logs = str_replace("<", "&lt;", $logs);
			$logs = str_replace(">", "&gt;", $logs);
			
			list($path, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality)
			?>
			<tr>
				<td valign="top"><?=$mpname?></td>
				<td valign="top"><?=$queuedate?></td>
				<td valign="top"><?=$startdate?></td>
				<td valign="top"><?=$enddate?></td>
				<td valign="top"><?=$status?></td>
				<td valign="top"><details><summary>View</summary><tt><pre><?=$logs?></pre></tt></details></td>
				<td valign="top"><a href="studies.php?studyid=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
				<td valign="top"><?=$modality?></td>
				<td valign="top"><?=$numinserts?></td>
			</tr>
			<?
		}
		?>
			</tbody>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- AddMiniPipeline -------------------- */
	/* -------------------------------------------- */
	function AddMiniPipeline($projectid, $minipipelinename) {
		/* perform data checks */
		$minipipelinename = mysqli_real_escape_string($GLOBALS['linki'], $minipipelinename);
		
		/* insert the new minipipeline */
		$sqlstring = "insert into minipipelines (project_id, mp_version, mp_name, mp_modifydate, mp_createdate) values ($projectid, 1, '$minipipelinename', now(), now())";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$mpid = mysqli_insert_id($GLOBALS['linki']);

		if( isset($_FILES['scripts']['name'])) {
			$total_files = count($_FILES['scripts']['name']);
			for($key = 0; $key < $total_files; $key++) {
				// Check if file is selected
				if(isset($_FILES['scripts']['name'][$key]) && $_FILES['scripts']['size'][$key] > 0) {
					$scriptFilename = $_FILES['scripts']['name'][$key];
					$scriptData = base64_encode(file_get_contents($_FILES['scripts']['tmp_name'][$key]));
					$scriptSize = filesize($_FILES['scripts']['tmp_name'][$key]) + 0;

					/* insert the new minipipeline scripts */
					$sqlstring = "insert into minipipeline_scripts (minipipeline_id, mp_version, mp_executable, mp_scriptname, mp_script, mp_scriptsize,
mp_scriptmodifydate, mp_scriptcreatedate) values($mpid, 1, 0, '$scriptFilename', '$scriptData', $scriptSize, now(), now())";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				}
			}
		}

		?><div align="center"><span class="message"><?=$minipipelinename?> added</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DeleteMiniPipeline ----------------- */
	/* -------------------------------------------- */
	function DeleteMiniPipeline($mpid) {
		if (!ValidID($mpid,'MiniPipeline ID')) { return; }
		
		$sqlstring = "delete from minipipelines where minipipeline_id = $mpid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$sqlstring = "delete from minipipeline_scripts where minipipeline_id = $mpid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?>Minipipeline deleted<br><br><?
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayMiniPipelineForm ------------ */
	/* -------------------------------------------- */
	function DisplayMiniPipelineForm($projectid, $type, $mpid) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			if (!ValidID($mpid,'MiniPipeline ID'))
				return;
			
			$sqlstring = "select * from minipipelines where minipipeline_id = $mpid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$minipipelineid = $row['minipipeline_id'];
			$name = $row['mp_name'];
		
			$formaction = "update";
			$formtitle = "$name";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "New mini-pipeline";
			$submitbuttonlabel = "Add";
		}
		
	?>
		<div class="ui container">
			<div class="ui attached visible message">
				<div class="header"><?=$formtitle?></div>
			</div>
			<form method="post" action="minipipeline.php" enctype="multipart/form-data" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="mpid" value="<?=$mpid?>">
			<input type="hidden" name="projectid" value="<?=$projectid?>">

			<div class="field">
				<label>Name</label>
				<div class="field">
					<input type="text" name="minipipelinename" value="<?=$name?>" maxlength="255" required>
				</div>
			</div>
			
			<div class="field">
				<label>Script(s)</label>
				<div class="field">
					<? if ($type == "edit") { ?>
					<table class="ui celled selectable small very compact table">
						<thead>
							<th>Script</th>
							<th>Size</th>
							<th>Executable?</th>
							<th>Entry point?</th>
							<th>Create date</th>
							<th>Modify date</th>
							<th>Remove?</th>
						</thead>
						<?
						$sqlstring = "select * from minipipeline_scripts where minipipeline_id = $mpid order by mp_script asc";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$scriptid = $row['minipipelinescript_id'];
							$script = $row['mp_scriptname'];
							$executable = $row['mp_executable'];
							$entrypoint = $row['mp_entrypoint'];
							$scriptsize = $row['mp_scriptsize'];
							$createdate = date('M j, Y h:ia',strtotime($row['mp_scriptcreatedate']));
							$modifydate = date('M j, Y h:ia',strtotime($row['mp_scriptmodifydate']));
							?>
							<tr>
								<td><tt style="font-size: larger"><a href="minipipeline.php?action=viewscript&projectid=<?=$projectid?>&scriptid=<?=$scriptid?>"><?=$script?></a></tt></td>
								<td><?=$scriptsize?></td>
								<td><input type="checkbox" name="scriptexecutableids[]" value="<?=$scriptid?>" <? if ($executable) { echo "checked"; }?>></td>
								<td><input type="radio" name="scriptentrypointid" value="<?=$scriptid?>" <? if ($entrypoint) { echo "checked"; }?>></td>
								<td style="font-size: smaller"><?=$createdate?></td>
								<td style="font-size: smaller"><?=$modifydate?></td>
								<td><input type="checkbox" name="scriptdeleteids[]" value="<?=$scriptid?>"></td>
							</tr>
							<?
						}
						?>
					</table>
					<br>
					<? } ?>
					Add script(s) <input type="file" name="scripts[]" multiple>
					<span class="tiny">Max individual file size 4GB. Max filename length 255 characters</span>
				</div>
			</div>
			<div class="ui two column grid">
				<div class="column">
					<a class="ui red button" href="minipipeline.php?mpid=1&projectid=1&action=delete" onclick="return confirm('Are you sure you want to delete?')"><i class="trash icon"></i>Delete</a>
				</div>
				<div class="column" align="right">
					<a class="ui button" href="minipipeline.php?projectid=<?=$projectid?>">Cancel</a>
					<input type="submit" class="ui primary button" value="<?=$submitbuttonlabel?>">
				</div>
			</form>
			<div class="ui attached segment">
				<br>
				<div class="ui accordion">
					<div class="title">
						<i class="dropdown icon"></i>
						How to make a mini-pipeline
					</div>
					<div class="content">
						<p>A mini-pipeline is meant to be a small script that extracts values from behavioral data files. Because it not meant to process images, the execution time of the mini-pipeline is limited to 5 minutes. It also does not have access to the cluster or any shared network resources.</p>
						<p>One script is identified as the entry point into the mini-pipeline. This script must accept as input through the command line a list of all of the files in the data or beh directory. The filenames will be sorted alphabetically. This is the script that will be called, and it can call any of the other scripts/files that are part of the mini-pipeline.
						<p>All scripts and behavioral files are copied to a temporary directory for the mini-pipeline to run. The output of the mini-pipeline must be in the following format, output to the console</p>
						<div style="border: 1px dashed #777; padding-left: 15px">
						<tt><pre>Type, VariableName, StartDate, EndDate, Duration, Value, Units, Notes, Instrument
measure, EyeContact, 2012-10-22, , 3000, 34.9, , "Sneezed at minute 3", ADOS
vital, BloodPressure, 2019-11-06 09:23, , , "122/70", , ,
drug, Ketamine, 2018-03-17 19:56, 2018-03-17 19:58, 120, 2.2, ml, "Fine", 
... </pre></tt>
						</div>
						<p>Notes about the output formats
						<ul>
							<li>Format must be in .csv, blank values still need a comma even if no values
							<li>Header must be on the first row
							<li>Possible types are <b>measure</b>, <b>vital</b>, <b>drug</b>
							<li>Dates must be in <tt>YYYY-MM-DD</tt> format, with leading zeros (ex, <tt>03</tt> for March)
							<li>Dates can include times, but time must be 24hr format, with leading zeros (<tt>14:03</tt>), with or without seconds (<tt>12:45:11</tt>)
							<li>Duration is in seconds
							<li>Any strings with spaces must be enclosed in double quotes
							<li>If <i>VariableName</i> already exists for a study, it will be updated to the value from the latest run of the mini-pipeline
							<li><tt>Type</tt>, <tt>VariableName</tt>, <tt>Value</tt> are the only required columns
						</ul>
					</div>
				</div>
			</div>
		</div>
	<?
	}

	/* -------------------------------------------- */
	/* ------- DisplayMiniPipelineList ------------ */
	/* -------------------------------------------- */
	function DisplayMiniPipelineList($projectid) {
		if (!ValidID($projectid,'Project ID')) { return; }
		
		?>
		<div class="ui container">
		<button class="ui primary large button" onClick="window.location.href='minipipeline.php?projectid=<?=$projectid?>&action=addform'; return false;"><i class="plus square outline icon"></i> Add mini-pipeline</button>
		<br><br>
		<table class="ui celled selectable grey compact table">
			<thead>
				<tr>
					<th style="border: 1px solid #999; ">Name</th>
					<th style="border: 1px solid #999; ">Logs</th>
					<th style="border: 1px solid #999; ">Version</th>
					<th style="border: 1px solid #999; ">Create date</th>
					<th style="border: 1px solid #999; ">Modify date</th>
					<th style="border: 1px solid #999; ">Script(s)</th>
					<th style="border: 1px solid #999; ">Delete</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from minipipelines where project_id = $projectid order by mp_name asc";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$mpid = $row['minipipeline_id'];
						$name = $row['mp_name'];
						$version = $row['mp_version'];
						$mpcreatedate = date('M j, Y h:ia',strtotime($row['mp_createdate']));
						$mpmodifydate = date('M j, Y h:ia',strtotime($row['mp_modifydate']));
						
						?>
						<tr>
							<td valign="top" style="border-bottom: 1px solid #999"><a href="minipipeline.php?action=editform&mpid=<?=$mpid?>&projectid=<?=$projectid?>"><?=$name?></td>
							<td valign="top" style="border-bottom: 1px solid #999"><a href="minipipeline.php?action=viewjobs&mpid=<?=$mpid?>&projectid=<?=$projectid?>">View</td>
							<td valign="top" style="border-bottom: 1px solid #999"><?=$version?></td>
							<td valign="top" style="border-bottom: 1px solid #999"><?=$mpcreatedate?></td>
							<td valign="top" style="border-bottom: 1px solid #999"><?=$mpmodifydate?></td>
							<td valign="top" align="center" style="border-bottom: 1px solid #999">
							<?
								$sqlstringA = "select * from minipipeline_scripts where minipipeline_id = $mpid order by mp_script asc";
								$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
								if (mysqli_num_rows($resultA) > 0) {
							?>
								<table style="font-size: 9pt" width="100%" class="ui very small very compact celled selectable grey table">
									<thead>
										<tr>
											<th>Script</th>
											<th>Size</th>
											<th>Executable</th>
											<th>Entry point</th>
										</tr>
									</thead>
								<?
								while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
									$script = $rowA['mp_scriptname'];
									$params = $rowA['mp_parameterlist'];
									$executable = $rowA['mp_executable'];
									$entrypoint = $rowA['mp_entrypoint'];
									$scriptsize = $rowA['mp_scriptsize'];
									$createdate = date('M j, Y h:ia',strtotime($rowA['mp_scriptcreatedate']));
									$modifydate = date('M j, Y h:ia',strtotime($rowA['mp_scriptmodifydate']));
									?>
									<tr>
										<td><tt><?=$script?> <i><?=$params?></i></tt></td>
										<td><?=$scriptsize?></td>
										<td><? if ($executable) { echo "&#10004;"; }?></td>
										<td><? if ($entrypoint) { echo "&#10004;"; }?></td>
									</tr>
									<?
								}
								?>
								</table>
								<?
								}
								else {
									echo "None";
								}
								?>
							</td>
							<td valign="top" align="center" style="border-bottom: 1px solid #999; font-size: smaller;"><a href="minipipeline.php?mpid=<?=$mpid?>&projectid=<?=$projectid?>&action=delete" class="ui red button" onclick="return confirm('********** STOP!! **********\n<?=$GLOBALS['username']?>, are you sure you want to COMPLETELY DELETE this mini-pipeline? Click Ok ONLY if you want to DELETE the mini-pipeline. This cannot be undone. But any variables created using this pipeline will remain in the database.')"><i class="trash icon"></i></a></td>
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
	/* ------- ViewScript ------------------------- */
	/* -------------------------------------------- */
	function ViewScript($projectid, $scriptid) {

		/* perform data checks */
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$scriptid = mysqli_real_escape_string($GLOBALS['linki'], $scriptid);
		
		if (!ValidID($projectid,'Project ID')) { return; }
		if (!ValidID($scriptid,'Script ID')) { return; }
		
		$sqlstring = "select * from minipipeline_scripts where minipipelinescript_id = $scriptid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$mpid = $row['minipipeline_id'];
			$scriptid = $row['minipipelinescript_id'];
			$scriptname = $row['mp_scriptname'];
			$contents = base64_decode($row['mp_script']);
			$executable = $row['mp_executable'];
			$entrypoint = $row['mp_entrypoint'];
			$scriptsize = $row['mp_scriptsize'];
			$createdate = date('M j, Y h:ia',strtotime($row['mp_scriptcreatedate']));
			$modifydate = date('M j, Y h:ia',strtotime($row['mp_scriptmodifydate']));
			?>
			
			&nbsp; &nbsp; <a href="minipipeline.php?action=editform&mpid=<?=$mpid?>&projectid=<?=$projectid?>" class="ui button"><b>&larr; Back</b></a>
			
			<div style="padding: 20px">
				<table class="twocoltablesimple">
					<tr>
						<td>Script name</td>
						<td><?=$scriptname?></td>
					</tr>
					<tr>
						<td>Executable?</td>
						<td><? if ($executable) { echo "&#10004;"; } ?></td>
					</tr>
					<tr>
						<td>Entry point?</td>
						<td><? if ($entrypoint) { echo "&#10004;"; } ?></td>
					</tr>
					<tr>
						<td>Script size</td>
						<td><?=$scriptsize?> <span class="tiny">bytes</span></td>
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
				<br>
				Displaying entire file
				<tt><pre style="text-align: left; padding: 15px; border: 1px solid gray"><?=$contents?></pre></tt>
			</div>
			<?
		}
	}
	
?>


<? include("footer.php") ?>
