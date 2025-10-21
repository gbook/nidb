<?
 // ------------------------------------------------------------------------------
 // NiDB backup.php
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
		<title>NiDB - Backup</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* check if they have permissions to this view page */
	if (!isSiteAdmin()) {
		Warning("You do not have permissions to view this page");
		exit(0);
	}

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
								<td><?=$tapenumber?></td>
								<td><?=$startdateA?></td>
								<td><?=$status?></td>
								<td></td>
								<td>Backup staging size<br><?=$tapesizeA?></td>
								<td><?=$enddate?></td>
							</tr>
							<?
						}
					}
				?>
			</tr>
		<?
		$sqlstring = "select backup_id, backup_errormsg, backup_tapenumber, backup_tapestatus, backup_startdateA, backup_enddateA, backup_tapesizeA, backup_startdateB, backup_enddateB, backup_tapesizeB, backup_startdateC, backup_enddateC, backup_tapesizeC from backups where backup_tapenumber > 0 order by backup_tapenumber desc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				
				$backupid = $row['backup_id'];
				$tapenum = $row['backup_tapenumber'];
				$tapenumber = sprintf('%04d', $row['backup_tapenumber']);
				$tapeletter = $row['backup_tapeletter'];
				$status = $row['backup_tapestatus'];
				$errormsg = $row['backup_errormsg'];
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
					<td class="top aligned"><?=$tapenum?></td>
					<td class="top aligned"><?=$startdateA?></td>
					<td class="top aligned">
						<? $action = DisplayStatus($status, $tapenumber, $errormsg); ?>
					</td>
					<td class="top aligned">
						<?
							if ($action == "marktapeAinserted") {
								?>
								<a href="backup.php?action=marktapeAinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Insert tape A<?=$tapenumber?> in the tape drive, then mark as inserted here">Mark Tape A<?=$tapenumber?> as Inserted</a>
								<?
							}
							elseif ($action == "marktapeBinserted") {
								?>
								<a href="backup.php?action=marktapeBinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Insert tape B<?=$tapenumber?> in the tape drive, then mark as inserted here">Mark Tape B<?=$tapenumber?> as Inserted</a>
								<?
							}
							elseif ($action == "marktapeCinserted") {
								?>
								<a href="backup.php?action=marktapeCinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Insert tape C<?=$tapenumber?> in the tape drive, then mark as inserted here">Mark Tape C<?=$tapenumber?> as Inserted</a>
								<?
							}
							elseif ($status == "errorTapeA") {
								?>
								<a href="backup.php?action=marktapeAinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Reset tape A<?=$tapenumber?>">Reset Tape A<?=$tapenumber?></a>
								<?
							}
							elseif ($status == "errorTapeB") {
								?>
								<a href="backup.php?action=marktapeBinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Reset tape B<?=$tapenumber?>">Reset Tape B<?=$tapenumber?></a>
								<?
							}
							elseif ($status == "errorTapeC") {
								?>
								<a href="backup.php?action=marktapeCinserted&backupid=<?=$backupid?>" class="ui small orange button" title="Reset tape C<?=$tapenumber?>">Reset Tape C<?=$tapenumber?></a>
								<?
							}
						?>
					</td>
					<td class="top aligned"><?=$tapesizeA?></td>
					<td class="top aligned"><?=$enddateC?></td>
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
	function DisplayStatus($status, $t, $error) {
		$action = "";
		$color = "";
		
		/* possible statuses: 'idle'
		   'waitingForTapeA','readyToWriteTapeA','writingTapeA','completeTapeA'
		   'waitingForTapeB','readyToWriteTapeB','writingTapeB','completeTapeB'
		   'waitingForTapeC','readyToWriteTapeC','writingTapeC','completeTapeC'
		   'complete'
		*/
		
		switch ($status) {
			case 'idle':
				$step1_state = "active"; $step1_title = "Idle";
				$step2_state = "disabled"; $step2_title = "Insert A";
				$step3_state = "disabled"; $step3_title = "Writing A";
				$step4_state = "disabled"; $step4_title = "Insert B";
				$step5_state = "disabled"; $step5_title = "Writing B";
				$step6_state = "disabled"; $step6_title = "Insert C";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Idle";
				$desc = "Nothing to do";
				break;
			case 'waitingForTapeA':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "active"; $step2_title = "Insert A$t";
				$step3_state = "disabled"; $step3_title = "Writing A";
				$step4_state = "disabled"; $step4_title = "Insert B";
				$step5_state = "disabled"; $step5_title = "Writing B";
				$step6_state = "disabled"; $step6_title = "Insert C";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$action = "marktapeAinserted";
				$desc_header = "Waiting for tape A";
				$desc = "Insert tape <tt>A$t</tt> and click button to mark as inserted";
				$color = "warning";
				break;
			case 'readyToWriteTapeA':
			case 'writingTapeA':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "active"; $step3_title = "Writing A$t";
				$step4_state = "disabled"; $step4_title = "Insert B";
				$step5_state = "disabled"; $step5_title = "Writing B";
				$step6_state = "disabled"; $step6_title = "Insert C";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Writing Tape A";
				$desc = "Tape <tt>A$t</tt> is being written";
				break;
			case 'completeTapeA':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "active"; $step3_title = "Writing A$t";
				$step4_state = "disabled"; $step4_title = "Insert B";
				$step5_state = "disabled"; $step5_title = "Writing B";
				$step6_state = "disabled"; $step6_title = "Insert C";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Tape A is complete";
				$desc = "Insert tape <tt>A$t</tt> and click button to mark as inserted";
				break;
			case 'errorTapeA':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "active"; $step3_title = "Writing A$t";
				$step4_state = "disabled"; $step4_title = "Insert B";
				$step5_state = "disabled"; $step5_title = "Writing B";
				$step6_state = "disabled"; $step6_title = "Insert C";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Error writing Tape A";
				$desc = "An error [$error] occured writing tape <tt>A$t</tt>. Fix the error, and click button to reset this backup";
				$color = "error";
				break;
			case 'waitingForTapeB':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "complete"; $step3_title = "Writing A";
				$step4_state = "active"; $step4_title = "Insert B$t";
				$step5_state = "disabled"; $step5_title = "Writing B";
				$step6_state = "disabled"; $step6_title = "Insert C";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$action = "marktapeBinserted";
				$desc_header = "Waiting for tape B";
				$desc = "Insert tape <tt>B$t</tt> and click button to mark as inserted";
				$color = "warning";
				break;
			case 'readyToWriteTapeB':
			case 'writingTapeB':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "complete"; $step3_title = "Writing A";
				$step4_state = "complete"; $step4_title = "Insert B";
				$step5_state = "active"; $step5_title = "Writing B$t";
				$step6_state = "disabled"; $step6_title = "Insert C";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Writing Tape B";
				$desc = "Writing tape <tt>B$t</tt>";
				break;
			case 'completeTapeB':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "complete"; $step3_title = "Writing A";
				$step4_state = "complete"; $step4_title = "Insert B";
				$step5_state = "active"; $step5_title = "Writing B$t";
				$step6_state = "disabled"; $step6_title = "Insert C";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Tape B is complete";
				$desc = "Insert tape <tt>B$t</tt> and click button to mark as inserted";
				break;
			case 'errorTapeB':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "complete"; $step3_title = "Writing A";
				$step4_state = "complete"; $step4_title = "Insert B";
				$step5_state = "active"; $step5_title = "Writing B$t";
				$step6_state = "disabled"; $step6_title = "Insert C";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Error writing Tape B";
				$desc = "An error [$error] occured writing tape <tt>B$t</tt>. Fix the error, and click button to reset this backup";
				$color = "error";
				break;
			case 'waitingForTapeC':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "complete"; $step3_title = "Writing A";
				$step4_state = "complete"; $step4_title = "Insert B";
				$step5_state = "complete"; $step5_title = "Writing B";
				$step6_state = "active"; $step6_title = "Insert C$t";
				$step7_state = "disabled"; $step7_title = "Writing C";
				$step8_state = "disabled"; $step8_title = "Complete";
				$action = "marktapeCinserted";
				$desc_header = "Waiting for tape C";
				$desc = "Insert tape <tt>C$t</tt> and click button to mark as inserted";
				$color = "warning";
				break;
			case 'readyToWriteTapeC':
			case 'writingTapeC':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "complete"; $step3_title = "Writing A";
				$step4_state = "complete"; $step4_title = "Insert B";
				$step5_state = "complete"; $step5_title = "Writing B";
				$step6_state = "complete"; $step6_title = "Insert C";
				$step7_state = "active"; $step7_title = "Writing C$t";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Writing Tape C";
				$desc = "Tape <tt>C$t</tt> is being written";
				break;
			case 'completeTapeC':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "complete"; $step3_title = "Writing A";
				$step4_state = "complete"; $step4_title = "Insert B";
				$step5_state = "complete"; $step5_title = "Writing B";
				$step6_state = "complete"; $step6_title = "Insert C";
				$step7_state = "active"; $step7_title = "Writing C$t";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Tape C is complete";
				$desc = "Insert tape <tt>C$t</tt> and click button to mark as inserted";
				break;
			case 'errorTapeC':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "complete"; $step3_title = "Writing A";
				$step4_state = "complete"; $step4_title = "Insert B";
				$step5_state = "complete"; $step5_title = "Writing B";
				$step6_state = "complete"; $step6_title = "Insert C";
				$step7_state = "active"; $step7_title = "Writing C$t";
				$step8_state = "disabled"; $step8_title = "Complete";
				$desc_header = "Error writing Tape C";
				$desc = "An error [$error] occured writing tape <tt>C$t</tt>. Fix the error, and click button to reset this backup";
				$color = "error";
				break;
			case 'complete':
				$step1_state = "complete"; $step1_title = "Idle";
				$step2_state = "complete"; $step2_title = "Insert A";
				$step3_state = "complete"; $step3_title = "Writing A";
				$step4_state = "complete"; $step4_title = "Insert B";
				$step5_state = "complete"; $step5_title = "Writing B";
				$step6_state = "complete"; $step6_title = "Insert C";
				$step7_state = "complete"; $step7_title = "Writing C";
				$step8_state = "active"; $step8_title = "Complete";
				$desc_header = "Tape set <tt>$t</tt> complete";
				$desc = "Tape set is complete. Remove tape <tt>C$t</tt> from drive";
				$color = "success";
				break;
		}
		
		?>
		<div class="ui top attached fluid steps">
			<div class="<?=$step1_state?> step" style="padding: 10px">
				<div class="content">
					<div class="title"><?=$step1_title?></div>
				</div>
			</div>
			<div class="<?=$step2_state?> step" style="padding: 10px">
				<div class="content">
					<div class="title"><?=$step2_title?></div>
				</div>
			</div>
			<div class="<?=$step3_state?> step" style="padding: 10px">
				<div class="content">
					<div class="title"><?=$step3_title?></div>
				</div>
			</div>
			<div class="<?=$step4_state?> step" style="padding: 10px">
				<div class="content">
					<div class="title"><?=$step4_title?></div>
				</div>
			</div>
			<div class="<?=$step5_state?> step" style="padding: 10px">
				<div class="content">
					<div class="title"><?=$step5_title?></div>
				</div>
			</div>
			<div class="<?=$step6_state?> step" style="padding: 10px">
				<div class="content">
					<div class="title"><?=$step6_title?></div>
				</div>
			</div>
			<div class="<?=$step7_state?> step" style="padding: 10px">
				<div class="content">
					<div class="title"><?=$step7_title?></div>
				</div>
			</div>
			<div class="<?=$step8_state?> step" style="padding: 10px">
				<div class="content">
					<div class="title"><?=$step8_title?></div>
				</div>
			</div>
		</div>
		<div class="ui small bottom attached <?=$color?> message">
			<div class="header"><?=$desc_header?></div>
			<?=$desc?>
		</div>
		<?
		
		return $action;
	}

?>


<? include("footer.php") ?>
