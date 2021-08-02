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
		case "marktapeAinserted":
			MarkTapeInserted($backupid,'A');
			DisplayBackupList();
			break;
		case "marktapeBinserted":
			MarkTapeInserted($backupid,'B');
			DisplayBackupList();
			break;
		case "marktapeCinserted":
			MarkTapeInserted($backupid,'C');
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
	function MarkTapeInserted($backupid, $tapeletter) {
		$backupid = mysqli_real_escape_string($GLOBALS['linki'], $backupid);

		switch ($tapeletter) {
			case 'A':
				$sqlstring = "update backups set backup_tapestatus = 'readyToWriteTapeA' where backup_id = $backupid";
				break;
			case 'B':
				$sqlstring = "update backups set backup_tapestatus = 'readyToWriteTapeB' where backup_id = $backupid";
				break;
			case 'C':
				$sqlstring = "update backups set backup_tapestatus = 'readyToWriteTapeC' where backup_id = $backupid";
				break;
		}
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		Notice("Tape $tapeletter marked as inserted");
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
				<th>Start date (tape A)</th>
				<th>Status</th>
				<th>Action</th>
				<th>Size</th>
				<th>End date (tape C)</th>
			</thead>
			<tr>
				<?
					$sqlstring = "select backup_id, backup_tapenumber, backup_tapestatus, backup_startdateA, backup_enddateA, backup_tapesizeA, backup_startdateB, backup_enddateB, backup_tapesizeB, backup_startdateC, backup_enddateC, backup_tapesizeC from backups where backup_tapenumber = 0";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					if (mysqli_num_rows($result) > 0) {
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$backupid = $row['backup_id'];
							$tapenumber = $row['backup_tapenumber'];
							//$tapeletter = $row['backup_tapeletter'];
							$status = $row['backup_tapestatus'];
							$startdateA = $row['backup_startdateA'];
							$enddateA = $row['backup_enddateA'];
							$tapesizeA = $row['backup_tapesizeA'];
							$startdateB = $row['backup_startdateB'];
							$enddateB = $row['backup_enddateB'];
							$tapesizeB = $row['backup_tapesizeB'];
							$startdateC = $row['backup_startdateC'];
							$enddateC = $row['backup_enddateC'];
							$tapesizeC = $row['backup_tapesizeC'];
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
		$sqlstring = "select backup_id, backup_id, backup_tapenumber, backup_tapestatus, backup_startdateA, backup_enddateA, backup_tapesizeA, backup_startdateB, backup_enddateB, backup_tapesizeB, backup_startdateC, backup_enddateC, backup_tapesizeC from backups where backup_tapenumber > 0 order by backup_tapenumber desc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				
				$backupid = $row['backup_id'];
				$tapenumber = sprintf('%04d', $row['backup_tapenumber']);
				$tapeletter = $row['backup_tapeletter'];
				$status = $row['backup_tapestatus'];
				$startdateA = $row['backup_startdateA'];
				$enddateA = $row['backup_enddateA'];
				$tapesizeA = $row['backup_tapesizeA'];
				$startdateB = $row['backup_startdateB'];
				$enddateB = $row['backup_enddateB'];
				$tapesizeB = $row['backup_tapesizeB'];
				$startdateC = $row['backup_startdateC'];
				$enddateC = $row['backup_enddateC'];
				$tapesizeC = $row['backup_tapesizeC'];

				?>
				<tr>
					<td><?=$tapenumber?></td>
					<td><?=$startdateA?></td>
					<td>
						<?=$status?>
						<? $action = DisplayStatus($status); ?>
					</td>
					<td>
						<?
							if ($action == "marktapeAinserted") {
								?>
								<b>Step 1)</b> Insert tape labeled <tt>A<?=$tapenumber?></tt> into tape drive<br>
								<b>Step 2)</b> <a href="backup.php?action=marktapeAinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Insert tape A<?=$tapenumber?> in the tape drive, then mark as inserted here">Mark Tape A<?=$tapenumber?> as Inserted</a>
								<?
							}
							elseif ($action == "marktapeBinserted") {
								?>
								<b>Step 1)</b> Insert tape labeled <tt>B<?=$tapenumber?></tt> into tape drive<br>
								<b>Step 2)</b> <a href="backup.php?action=marktapeAinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Insert tape B<?=$tapenumber?> in the tape drive, then mark as inserted here">Mark Tape B<?=$tapenumber?> as Inserted</a>
								<?
							}
							elseif ($action == "marktapeCinserted") {
								?>
								<b>Step 1)</b> Insert tape labeled <tt>C<?=$tapenumber?></tt> into tape drive<br>
								<b>Step 2)</b> <a href="backup.php?action=marktapeAinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Insert tape C<?=$tapenumber?> in the tape drive, then mark as inserted here">Mark Tape C<?=$tapenumber?> as Inserted</a>
								<?
							}
						?>
					</td>
					<td><?=$tapesizeA?></td>
					<td><?=$enddateC?></td>
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
		
		/* possible statuses: 'idle'
		   'waitingForTapeA','readyToWriteTapeA','writingTapeA','completeTapeA'
		   'waitingForTapeB','readyToWriteTapeB','writingTapeB','completeTapeB'
		   'waitingForTapeC','readyToWriteTapeC','writingTapeC','completeTapeC'
		   'complete'
		*/
		
		switch ($status) {
			case 'idle':
				$step1_state = "active"; $step1_title = "Idle"; $step1_desc = "Tape drive is idle";
				$step2_state = "disabled"; $step2_title = "Insert tape A"; $step2_desc = "Manually insert tape A";
				$step3_state = "disabled"; $step3_title = "Writing tape A"; $step3_desc = "Tape A is being written";
				$step4_state = "disabled"; $step4_title = "Insert tape B"; $step4_desc = "Manually insert tape B";
				$step5_state = "disabled"; $step5_title = "Writing tape B"; $step5_desc = "Tape B is being written";
				$step6_state = "disabled"; $step6_title = "Insert tape C"; $step6_desc = "Manually insert tape C";
				$step7_state = "disabled"; $step7_title = "Writing tape C"; $step7_desc = "Tape C is being written";
				$step8_state = "disabled"; $step8_title = "Complete"; $step8_desc = "Tape set is complete";
				break;
			case 'waitingfortapeA':
				$step1_state = "complete"; $step1_title = "Idle"; $step1_desc = "Tape drive is idle";
				$step2_state = "active"; $step2_title = "Insert tape A"; $step2_desc = "Manually insert tape A";
				$step3_state = "disabled"; $step3_title = "Writing tape A"; $step3_desc = "Tape A is being written";
				$step4_state = "disabled"; $step4_title = "Insert tape B"; $step4_desc = "Manually insert tape B";
				$step5_state = "disabled"; $step5_title = "Writing tape B"; $step5_desc = "Tape B is being written";
				$step6_state = "disabled"; $step6_title = "Insert tape C"; $step6_desc = "Manually insert tape C";
				$step7_state = "disabled"; $step7_title = "Writing tape C"; $step7_desc = "Tape C is being written";
				$step8_state = "disabled"; $step8_title = "Complete"; $step8_desc = "Tape set is complete";
				$action = "marktapeAinserted";
				break;
			case 'waitingfortapeB':
				$step1_state = "complete"; $step1_title = "Idle"; $step1_desc = "Tape drive is idle";
				$step2_state = "complete"; $step2_title = "Insert tape A"; $step2_desc = "Manually insert tape A";
				$step3_state = "complete"; $step3_title = "Writing tape A"; $step3_desc = "Tape A is being written";
				$step4_state = "active"; $step4_title = "Insert tape B"; $step4_desc = "Manually insert tape B";
				$step5_state = "disabled"; $step5_title = "Writing tape B"; $step5_desc = "Tape B is being written";
				$step6_state = "disabled"; $step6_title = "Insert tape C"; $step6_desc = "Manually insert tape C";
				$step7_state = "disabled"; $step7_title = "Writing tape C"; $step7_desc = "Tape C is being written";
				$step8_state = "disabled"; $step8_title = "Complete"; $step8_desc = "Tape set is complete";
				$action = "marktapeBinserted";
				break;
			case 'waitingfortapeC':
				$step1_state = "complete"; $step1_title = "Idle"; $step1_desc = "Tape drive is idle";
				$step2_state = "complete"; $step2_title = "Insert tape A"; $step2_desc = "Manually insert tape A";
				$step3_state = "complete"; $step3_title = "Writing tape A"; $step3_desc = "Tape A is being written";
				$step4_state = "complete"; $step4_title = "Insert tape B"; $step4_desc = "Manually insert tape B";
				$step5_state = "complete"; $step5_title = "Writing tape B"; $step5_desc = "Tape B is being written";
				$step6_state = "active"; $step6_title = "Insert tape C"; $step6_desc = "Manually insert tape C";
				$step7_state = "disabled"; $step7_title = "Writing tape C"; $step7_desc = "Tape C is being written";
				$step8_state = "disabled"; $step8_title = "Complete"; $step8_desc = "Tape set is complete";
				$action = "marktapeCinserted";
				break;
			case 'complete':
				$step1_state = "complete"; $step1_title = "Idle"; $step1_desc = "Tape drive is idle";
				$step2_state = "complete"; $step2_title = "Insert tape A"; $step2_desc = "Manually insert tape A";
				$step3_state = "complete"; $step3_title = "Writing tape A"; $step3_desc = "Tape A is being written";
				$step4_state = "complete"; $step4_title = "Insert tape B"; $step4_desc = "Manually insert tape B";
				$step5_state = "complete"; $step5_title = "Writing tape B"; $step5_desc = "Tape B is being written";
				$step6_state = "complete"; $step6_title = "Insert tape C"; $step6_desc = "Manually insert tape C";
				$step7_state = "complete"; $step7_title = "Writing tape C"; $step7_desc = "Tape C is being written";
				$step8_state = "active"; $step8_title = "Complete"; $step8_desc = "Tape set is complete";
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
			<div class="<?=$step5_state?> step">
				<div class="content">
					<div class="title"><?=$step5_title?></div>
					<div class="description"><?=$step5_desc?></div>
				</div>
			</div>
			<div class="<?=$step6_state?> step">
				<div class="content">
					<div class="title"><?=$step6_title?></div>
					<div class="description"><?=$step6_desc?></div>
				</div>
			</div>
			<div class="<?=$step7_state?> step">
				<div class="content">
					<div class="title"><?=$step7_title?></div>
					<div class="description"><?=$step7_desc?></div>
				</div>
			</div>
			<div class="<?=$step8_state?> step">
				<div class="content">
					<div class="title"><?=$step8_title?></div>
					<div class="description"><?=$step8_desc?></div>
				</div>
			</div>
		</div>
		<?
		
		return $action;
	}

?>


<? include("footer.php") ?>
