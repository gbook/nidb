<?
 // ------------------------------------------------------------------------------
 // NiDB admindicomae.php
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
		<title>NiDB - Manage DICOM AE End Points</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	if (!isSiteAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$id = GetVariable("id");
		$aetitle = trim(GetVariable("aetitle") ?? '');
		$aeport = GetVariable("aeport");
		$aetls = GetVariable("aetls");

		/* determine action */
		/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-run them */
		if ($action == "editform") {
			DisplayAEForm("edit", $id);
		}
		elseif ($action == "addform") {
			DisplayAEForm("add", "");
		}
		elseif (($action == "update") && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
			ob_start();
			UpdateAE($id, $aetitle, $aeport, $aetls);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("admindicomae.php");
		}
		elseif (($action == "add") && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
			ob_start();
			AddAE($aetitle, $aeport, $aetls);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("admindicomae.php");
		}
		elseif (($action == "delete") && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
			ob_start();
			DeleteAE($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("admindicomae.php");
		}
		else {
			DisplayAEList();
		}
	}

	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- ValidateAE ------------------------- */
	/* -------------------------------------------- */
	/* returns an error message, or empty string if the AE title/port are valid.
	   DICOM AE titles are 1-16 characters of the default character repertoire,
	   excluding backslash and control characters */
	function ValidateAE($aetitle, $aeport) {
		if ($aetitle == "")
			return "AE title is required";
		if (strlen($aetitle) > 16)
			return "AE title must be 16 characters or fewer";
		if (!preg_match('/^[\x20-\x5B\x5D-\x7E]+$/', $aetitle))
			return "AE title may contain only printable ASCII characters, excluding backslash";
		if (!ctype_digit((string)$aeport) || ((int)$aeport < 1) || ((int)$aeport > 65535))
			return "Port must be a number between 1 and 65535";

		return "";
	}


	/* -------------------------------------------- */
	/* ------- UpdateAE --------------------------- */
	/* -------------------------------------------- */
	function UpdateAE($id, $aetitle, $aeport, $aetls) {
		$id = (int)$id;

		$err = ValidateAE($aetitle, $aeport);
		if ($err != "") { Error(htmlspecialchars($err)); return; }

		$aeport = (int)$aeport;
		$aetls = ($aetls == "1") ? 1 : 0;

		$sqlstring = "update dicom_ae set ae_title = ?, ae_port = ?, ae_tls = ? where dicomae_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'siii', $aetitle, $aeport, $aetls, $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$aetitle, $aeport, $aetls, $id]);
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars($aetitle) . " updated");
	}


	/* -------------------------------------------- */
	/* ------- AddAE ------------------------------ */
	/* -------------------------------------------- */
	function AddAE($aetitle, $aeport, $aetls) {
		$err = ValidateAE($aetitle, $aeport);
		if ($err != "") { Error(htmlspecialchars($err)); return; }

		$aeport = (int)$aeport;
		$aetls = ($aetls == "1") ? 1 : 0;

		$sqlstring = "insert into dicom_ae (ae_title, ae_port, ae_tls) values (?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sii', $aetitle, $aeport, $aetls);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$aetitle, $aeport, $aetls]);
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars($aetitle) . " added");
	}


	/* -------------------------------------------- */
	/* ------- DeleteAE --------------------------- */
	/* -------------------------------------------- */
	function DeleteAE($id) {
		$id = (int)$id;
		$sqlstring = "delete from dicom_ae where dicomae_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);
		Notice("DICOM AE end point deleted");
	}


	/* -------------------------------------------- */
	/* ------- DisplayAEForm ---------------------- */
	/* -------------------------------------------- */
	function DisplayAEForm($type, $id) {

		/* defaults for the "add" form */
		$aetitle = "";
		$aeport = 104;
		$aetls = 0;

		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$id = (int)$id;
			$sqlstring = "select * from dicom_ae where dicomae_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) { Error("DICOM AE end point not found"); return; }
			$aetitle = $row['ae_title'];
			$aeport = $row['ae_port'];
			$aetls = $row['ae_tls'];

			$formaction = "update";
			$formtitle = "Updating $aetitle";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add DICOM AE end point";
			$submitbuttonlabel = "Add";
		}

	?>
		<div class="ui text container">
			<div class="ui attached visible message">
				<div class="header"><?=htmlspecialchars($formtitle)?></div>
			</div>

			<form method="post" action="admindicomae.php" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="<?=$formaction?>">
				<input type="hidden" name="id" value="<?=$id?>">

				<div class="two fields">
					<div class="required field">
						<label>AE Title</label>
						<input type="text" name="aetitle" value="<?=htmlspecialchars($aetitle)?>" maxlength="16" placeholder="Application Entity title" required autofocus="autofocus">
					</div>
					<div class="required field">
						<label>Port</label>
						<input type="number" name="aeport" value="<?=(int)$aeport?>" min="1" max="65535" required>
					</div>
				</div>

				<div class="field">
					<div class="ui checkbox">
						<input type="checkbox" name="aetls" value="1" <? if ($aetls) { echo "checked"; } ?>>
						<label>Use TLS</label>
					</div>
				</div>

				<input type="submit" value="<?=$submitbuttonlabel?>" class="ui primary button">
				<a href="admindicomae.php" class="ui button">Cancel</a>
			</form>

			<? if ($type == 'edit') { ?>
			<br>
			<form method="post" action="admindicomae.php" onSubmit="return confirm('Delete DICOM AE end point <?=htmlspecialchars(addslashes($aetitle))?>?')">
				<input type="hidden" name="action" value="delete">
				<input type="hidden" name="id" value="<?=$id?>">
				<button type="submit" class="ui red basic button"><i class="trash icon"></i>Delete</button>
			</form>
			<? } ?>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAEList ---------------------- */
	/* -------------------------------------------- */
	function DisplayAEList() {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
	?>
	<div class="ui container">
		<div class="ui two column grid">
			<div class="column">
				<h1 class="ui header">DICOM AE End Points</h1>
			</div>
			<div class="right aligned column">
				<a href="admindicomae.php?action=addform" class="ui primary button"><i class="plus square icon"></i>Add AE End Point</a>
			</div>
		</div>
		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th>AE Title</th>
					<th>Port</th>
					<th>TLS</th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from dicom_ae order by ae_title";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					if (mysqli_num_rows($result) < 1) {
						?><tr><td colspan="3" class="center aligned">No DICOM AE end points defined</td></tr><?
					}
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['dicomae_id'];
						$aetitle = $row['ae_title'];
						$aeport = $row['ae_port'];
						$aetls = $row['ae_tls'];
				?>
				<tr>
					<td><a href="admindicomae.php?action=editform&id=<?=$id?>"><?=htmlspecialchars($aetitle)?></a></td>
					<td><?=(int)$aeport?></td>
					<td><? if ($aetls) { ?><i class="green lock icon"></i> Yes<? } else { ?>No<? } ?></td>
				</tr>
				<?
					}
				?>
			</tbody>
		</table>
	</div>
	<?
	}
?>


<? include("footer.php") ?>
