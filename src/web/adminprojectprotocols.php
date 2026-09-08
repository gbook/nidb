<?
 // ------------------------------------------------------------------------------
 // NiDB adminprojectprotocols.php
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
	ob_start(); /* buffer output for POST/Redirect/GET (see functions.php RedirectTo/ShowFlashMessage) */
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Manage Project Protocols</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";

	if (!isAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$projectid = GetVariable("projectid");
		$protocolgroupid = GetVariable("protocolgroupid");
		$projectprotocolid = GetVariable("projectprotocolid");
		$criteria = GetVariable("criteria");
		$numpersession = GetVariable("numpersession");
		$numtotal = GetVariable("numtotal");
		
		/* determine action */
		switch ($action) {
			/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-run them */
			case 'addprotocol':
				ob_start();
				AddProjectProtocol($projectid, $protocolgroupid, $criteria, $numpersession, $numtotal);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminprojectprotocols.php?projectid=" . urlencode($projectid));
				break;
			case 'deleteprotocol':
				ob_start();
				DeleteProjectProtocol($projectprotocolid);
				$_SESSION['flash'] = ob_get_clean();
				RedirectTo("adminprojectprotocols.php?projectid=" . urlencode($projectid));
				break;
			default:
				DisplayProjectProtocolList($projectid);
		}
	}
	
	/* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
	/* ------- AddProjectProtocol ----------------- */
	/* -------------------------------------------- */
	function AddProjectProtocol($projectid, $protocolgroupid, $criteria, $numpersession, $numtotal) {
		/* insert the new project protocol */
		$sqlstring = "insert ignore into project_protocol (project_id, protocolgroup_id, pp_criteria, pp_perstudyquantity, pp_perprojectquantity) values (?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iisss', $projectid, $protocolgroupid, $criteria, $numpersession, $numtotal);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $protocolgroupid, $criteria, $numpersession, $numtotal]);
		mysqli_stmt_close($stmt);
		Notice("Protocol added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteProjectProtocol -------------- */
	/* -------------------------------------------- */
	function DeleteProjectProtocol($projectprotocolid) {
		$sqlstring = "delete from project_protocol where projectprotocol_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectprotocolid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectprotocolid]);
		mysqli_stmt_close($stmt);
		Notice("Protocol deleted");
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayProjectProtocolList --------- */
	/* -------------------------------------------- */
	function DisplayProjectProtocolList($projectid) {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
	?>

	<table class="ui table" width="100%">
		<thead>
			<tr>
				<th>Protocol group<br><span class="tiny">MODALITY - group name</span></th>
				<th>Criteria</th>
				<!--<th>Per session quantity</th>-->
				<th>Total quantity</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<form action="adminprojectprotocols.php" method="post">
			<input type="hidden" name="action" value="addprotocol">
			<input type="hidden" name="projectid" value="<?=$projectid?>">
			<tr>
				<td style="border-bottom: solid 2px gray"><select name="protocolgroupid" required>
					<option value="">(Select protocol group)</option>
				<?
					$sqlstring = "select * from protocol_group order by protocolgroup_modality, protocolgroup_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$pgid = $row['protocolgroup_id'];
						$name = $row['protocolgroup_name'];
						$modality = strtoupper($row['protocolgroup_modality']);
						echo "<option value='$pgid'>$modality - $name</option>";
					}
				?>
				</select></td>
				<td style="border-bottom: solid 2px gray">
					<input type="radio" name="criteria" value="required" checked>Required<br>
					<!--<input type="radio" name="criteria" value="recommended">Recommended<br>
					<input type="radio" name="criteria" value="conditional">Conditional-->
				</td>
				<!--<td style="border-bottom: solid 2px gray"><input type="number" name="numpersession" style="width: 50px"></td>-->
				<td style="border-bottom: solid 2px gray"><input type="number" name="numtotal" style="width: 50px"></td>
				<td style="border-bottom: solid 2px gray"><input type="submit" value="Add"></td>
			</tr>
			</form>
			<?
				$sqlstring = "select * from project_protocol a left join protocol_group b on a.protocolgroup_id = b.protocolgroup_id where a.project_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $projectid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
				mysqli_stmt_close($stmt);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$projectprotocolid = $row['projectprotocol_id'];
					//$projectid = $row['project_id'];
					$protocolgroupid = $row['protocolgroup_id'];
					$criteria = $row['pp_criteria'];
					$numpersession = $row['pp_perstudyquantity'];
					$numtotal = $row['pp_perprojectquantity'];
					$name = $row['protocolgroup_name'];
					$modality = strtoupper($row['protocolgroup_modality']);
				?>
				<tr>
					<td><a href="adminmodalities.php?action=editprotocolgroups&id=<?=$protocolgroupid?>" target="_top"><?=$modality?></a> - <?=$name?></td>
					<td><?=$criteria?></td>
					<td><?=$numpersession?></td>
					<td><?=$numtotal?></td>
					<td><a class="ui red button" href="adminprojectprotocols.php?action=deleteprotocol&projectprotocolid=<?=$projectprotocolid?>&projectid=<?=$projectid?>" style="color: darkred" onclick="return confirm('Are you sure you want to delete this?')"><i class="trash icon"></i></a></td>
				</tr>
				<? 
				}
			?>
		</tbody>
	</table>
	<?
	}
?>
