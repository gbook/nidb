<?
 // ------------------------------------------------------------------------------
 // NiDB backup.php
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
		<title>NiDB - Backup</title>
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
	$backupid = GetVariable("backupid");
	
	/* determine action */
	switch($action) {
		case "marktapeinserted":
			MarkTapeInserted($backupid);
			DisplayBackupList();
			break;
		default:
			DisplayBackupList();
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- MarkTapeInserted ------------------- */
	/* -------------------------------------------- */
	function MarkTapeInserted($backupid) {
		$backupid = mysqli_real_escape_string($GLOBALS['linki'], $backupid);

		$sqlstring = "update backups set backup_tapestatus = 'readytowrite' where backup_id = $backupid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Tape marked as inserted");
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayBackupList ------------------ */
	/* -------------------------------------------- */
	function DisplayBackupList() {
		?>
		<h2 class="ui header">Tape Backups</h2>
		
		<table class="ui celled compact table">
			<thead>
				<th>Tape</th>
				<th>Start date</th>
				<th>Status</th>
				<th>Action</th>
				<th>Size</th>
				<th>End date</th>
			</thead>
			<tr>
				<?
					$sqlstring = "select backup_id, backup_tapenumber, backup_tapeletter, backup_tapestatus, backup_startdate, backup_enddate, backup_tapesize from backups where backup_tapenumber = 0";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					if (mysqli_num_rows($result) > 0) {
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$backupid = $row['backup_id'];
							$tapenumber = $row['backup_tapenumber'];
							$tapeletter = $row['backup_tapeletter'];
							$status = $row['backup_tapestatus'];
							$startdate = $row['backup_startdate'];
							$enddate = $row['backup_enddate'];
							$tapesize = $row['backup_tapesize'];
							?>
							<tr>
								<td><?=$tapenumber?><?=$tapeletter?></td>
								<td><?=$startdate?></td>
								<td><?=$status?></td>
								<td></td>
								<td>Backup staging size <?=$tapesize?></td>
								<td><?=$enddate?></td>
							</tr>
							<?
						}
					}
				?>
			</tr>
		<?
		$sqlstring = "select backup_id, backup_tapenumber, backup_tapeletter, backup_tapestatus, backup_startdate, backup_enddate, backup_tapesize from backups where backup_tapenumber > 0 order by backup_tapenumber desc, backup_tapeletter asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				
				$backupid = $row['backup_id'];
				$tapenumber = sprintf('%04d', $row['backup_tapenumber']);
				$tapeletter = $row['backup_tapeletter'];
				$status = $row['backup_tapestatus'];
				$startdate = $row['backup_startdate'];
				$enddate = $row['backup_enddate'];
				$tapesize = $row['backup_tapesize'];
				//backup_tapecontents = $row['backup_tapecontents'];

				?>
				<tr>
					<td><?=$tapeletter?><?=$tapenumber?></td>
					<td><?=$startdate?></td>
					<td>
						<?=$status?>
						<? $action = DisplayStatus($status); ?>
					</td>
					<td>
						<?
							if ($action == "marktapeinserted") {
								?>
								<b>Step 1)</b> Insert tape labeled <tt><?=$tapeletter?><?=$tapenumber?></tt> into tape drive<br>
								<b>Step 2)</b> <a href="backup.php?action=marktapeinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Insert the tape in the tape drive">Mark Tape as Inserted</a>
								<?
							}
						?>
					</td>
					<td><?=$tapesize?></td>
					<td><?=$startdate?></td>
				</tr>
				<?
			}
		}
		?>
		</table>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayStatus ---------------------- */
	/* -------------------------------------------- */
	function DisplayStatus($status) {
		$action = "";
		
		switch ($status) {
			case 'idle':
				$step1_state = "active"; $step1_title = "Idle"; $step1_desc = "Tape drive is idle";
				$step2_state = "disabled"; $step2_title = "Insert tape"; $step2_desc = "Manually insert tape";
				$step3_state = "disabled"; $step3_title = "Writing"; $step3_desc = "Tape is being written";
				$step4_state = "disabled"; $step4_title = "Complete"; $step4_desc = "Tape has been ejected";
				break;
			case 'waitingfortape':
				$step1_state = ""; $step1_title = "Idle"; $step1_desc = "Tape drive is idle";
				$step2_state = "active"; $step2_title = "Insert tape"; $step2_desc = "Manually insert tape";
				$step3_state = "disabled"; $step3_title = "Writing"; $step3_desc = "Tape is being written";
				$step4_state = "disabled"; $step4_title = "Complete"; $step4_desc = "Tape has been ejected";
				$action = "marktapeinserted";
				break;
			case 'readytowrite':
			case 'writing':
			case 'reading':
			case 'rewinding':
				$step1_state = ""; $step1_title = "Idle"; $step1_desc = "Tape drive is idle";
				$step2_state = ""; $step2_title = "Insert tape"; $step2_desc = "Manually insert tape";
				$step3_state = "active"; $step3_title = "Writing"; $step3_desc = "Tape is being written";
				$step4_state = "disabled"; $step4_title = "Complete"; $step4_desc = "Tape has been ejected";
				break;
			case 'staging':
				$step1_state = ""; $step1_title = "Idle"; $step1_desc = "Tape drive is idle";
				$step2_state = ""; $step2_title = "Insert tape"; $step2_desc = "Manually insert tape";
				$step3_state = ""; $step3_title = "Writing"; $step3_desc = "Tape is being written";
				$step4_state = "active"; $step4_title = "Complete"; $step4_desc = "Tape has been ejected";
				break;
		}
		
		?>
		<div class="ui small steps">
			<div class="<?=$step1_state?> step">
				<div class="content">
					<div class="title"><?=$step1_title?></div>
					<div class="description"><?=$step1_desc?></div>
				</div>
			</div>
			<div class="<?=$step2_state?> step">
				<div class="content">
					<div class="title"><?=$step2_title?></div>
					<div class="description"><?=$step2_desc?></div>
				</div>
			</div>
			<div class="<?=$step3_state?> step">
				<div class="content">
					<div class="title"><?=$step3_title?></div>
					<div class="description"><?=$step3_desc?></div>
				</div>
			</div>
			<div class="<?=$step4_state?> step">
				<div class="content">
					<div class="title"><?=$step4_title?></div>
					<div class="description"><?=$step4_desc?></div>
				</div>
			</div>
		</div>
		<?
		
		return $action;
	}

?>


<? include("footer.php") ?>
