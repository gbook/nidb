<?
 // ------------------------------------------------------------------------------
 // NiDB filesio.php
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
		<title>NiDB - File I/O  status</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* get variables */
	$action = GetVariable("action");
	$page = GetVariable("page");
	$fileioid = GetVariable("fileioid");
	$requestid = GetVariable("requestid");
	$viewall = GetVariable("viewall");
	
	switch ($action) {
		case 'cancelfileio':
			CancelFileIO($fileioid);
			ShowList($viewall);
			break;
		case 'deletefileio':
			DeleteFileIO($fileioid);
			ShowList($viewall);
			break;
		default:
			ShowList($viewall);
	}

	
	/* --------------------------------------------------- */
	/* ------- CancelFileIO ------------------------------ */
	/* --------------------------------------------------- */
	function CancelFileIO($fileioid) {
		$sqlstring = "update fileio_requests set request_status = 'cancelled' where fileiorequest_id = $fileioid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		Notice("File I/O $fileioid has been cancelled");
	}

	/* --------------------------------------------------- */
	/* ------- DeleteFileIO ------------------------------ */
	/* --------------------------------------------------- */
	function DeleteFileIO($fileioid) {
		$sqlstring = "delete from fileio_requests where fileiorequest_id = $fileioid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		Notice("File I/O $fileioid has been deleted");
	}


	/* --------------------------------------------------- */
	/* ------- ShowList ---------------------------------- */
	/* --------------------------------------------------- */
	function ShowList($viewall) {
		?>
		<a href="filesio.php?viewall=<?=1?>">Show all file I/O</a>
		<br><br>
		<SCRIPT LANGUAGE="Javascript">
			function decision(message, url){
					if(confirm(message)) location.href = url;
			}
		</SCRIPT>
		<table class="ui very compact celled grey table">
			<thead>
				<th align="left">I/O Id</th>
				<th align="left">Requested By</th>
				<th align="left">Request Time</th>
				<th align="left">Operation</th>
				<th align="left">Type</th>
				<th align="left">Status</th>
				<th align="left">Time Left</th>
				<th align="left">Message</th>
				<? if ($iostatus!='complete'){ ?>
				<th align="center">Action</th>
				<?}?>
			</thead>
		<?	
		
		$completecolor = "66AAFF";
		$processingcolor = "AAAAFF";
		$errorcolor = "FF6666";
		$othercolor = "EFEFFF";

		if ($GLOBALS['issiteadmin']) {
			if ($viewall) {
				$sqlstring = "SELECT `fileiorequest_id`, `fileio_operation`,`data_type`,`request_status`, `request_message`, `username`,`requestdate` FROM `fileio_requests` order by fileiorequest_id desc limit 100"; }
			else {
				$sqlstring = "SELECT `fileiorequest_id`, `fileio_operation`,`data_type`,`request_status`, `request_message`, `username`,`requestdate` FROM `fileio_requests`  order by fileiorequest_id desc"; }
			}
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$fileioid = $row['fileiorequest_id'];
				$rquser = $row['username'];
				$rtime = $row['requestdate'];
				$iooperation = $row['fileio_operation'];
				$iotype = $row['data_type'];
				$iostatus = $row['request_status'];
				$iomessage = $row['request_message'];
			?>                        

			<tr>
				<td><?=$fileioid?></td>
				<td><?=$rquser?></td>
				<td><?=$rtime?></td>
				<td><?=$iooperation?></td>
				<td><?=$iotype?></td>
				<td><?=$iostatus?></td>
				<td><?=$iomessage?></td>
				<?
				$now = strtotime($rtime);
				$Five_minutes = $now + (5 * 60);
				$startDate = date('Y-m-y H:i:s', $now);
				$endDate = date('Y-m-y H:i:s', $Five_minutes);
				   
				if ($endDate > $startDate) {
					$D2 = date('d',$endDate);
					$D1 = date('d',$startDate);
					$Ttime =$D2-$D1;
				}
				else {
					$Ttime = 2;
				}
				?>
				<td><?=$endDate?></td>
				<? if ($iostatus=='pending'){ ?>
				<td align="center" class="cancel">
					<a class="ui red button" href="filesio.php?action=cancelfileio&fileioid=<?=$fileioid?>" onclick="return confirm('Are you sure?')">Cancel Operation</a>
				</td>
				<? }?>
				<? if ($iostatus=='error' || $iostatus=='cancelled'){ ?>
				<td>
					<a class="ui red button" href="filesio.php?action=deletefileio&fileioid=<?=$fileioid?>" onclick="return confirm('Are you sure?')">Remove</a>
				</td>
				<? } ?>

			</tr>
			<?
			}
		?>
		</table>
		<?
	}
	
require "footer.php";
?>