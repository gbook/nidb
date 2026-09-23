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
		$aehostname = trim(GetVariable("aehostname") ?? '');
		$aeip = trim(GetVariable("aeip") ?? '');
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
		elseif ($action == "test") {
			TestAE($id);
		}
		elseif (($action == "update") && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
			ob_start();
			UpdateAE($id, $aetitle, $aehostname, $aeip, $aeport, $aetls);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("admindicomae.php");
		}
		elseif (($action == "add") && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
			ob_start();
			AddAE($aetitle, $aehostname, $aeip, $aeport, $aetls);
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
	/* returns an error message, or empty string if the AE fields are valid.
	   DICOM AE titles are 1-16 characters of the default character repertoire,
	   excluding backslash and control characters. At least one of hostname or
	   IP address is required to reach the remote AE */
	function ValidateAE($aetitle, $aehostname, $aeip, $aeport) {
		if ($aetitle == "")
			return "AE title is required";
		if (strlen($aetitle) > 16)
			return "AE title must be 16 characters or fewer";
		if (!preg_match('/^[\x20-\x5B\x5D-\x7E]+$/', $aetitle))
			return "AE title may contain only printable ASCII characters, excluding backslash";
		if (($aehostname == "") && ($aeip == ""))
			return "A hostname or IP address is required";
		if (($aehostname != "") && (filter_var($aehostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false))
			return "Hostname is not valid";
		if (($aeip != "") && (filter_var($aeip, FILTER_VALIDATE_IP) === false))
			return "IP address is not a valid IPv4 or IPv6 address";
		if (!ctype_digit((string)$aeport) || ((int)$aeport < 1) || ((int)$aeport > 65535))
			return "Port must be a number between 1 and 65535";

		return "";
	}


	/* -------------------------------------------- */
	/* ------- UpdateAE --------------------------- */
	/* -------------------------------------------- */
	function UpdateAE($id, $aetitle, $aehostname, $aeip, $aeport, $aetls) {
		$id = (int)$id;

		$err = ValidateAE($aetitle, $aehostname, $aeip, $aeport);
		if ($err != "") { Error(htmlspecialchars($err)); return; }

		$aeport = (int)$aeport;
		$aetls = ($aetls == "1") ? 1 : 0;

		$sqlstring = "update dicom_ae set ae_title = ?, ae_hostname = ?, ae_ip = ?, ae_port = ?, ae_tls = ? where dicomae_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssiii', $aetitle, $aehostname, $aeip, $aeport, $aetls, $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$aetitle, $aehostname, $aeip, $aeport, $aetls, $id]);
		mysqli_stmt_close($stmt);

		Notice(htmlspecialchars($aetitle) . " updated");
	}


	/* -------------------------------------------- */
	/* ------- AddAE ------------------------------ */
	/* -------------------------------------------- */
	function AddAE($aetitle, $aehostname, $aeip, $aeport, $aetls) {
		$err = ValidateAE($aetitle, $aehostname, $aeip, $aeport);
		if ($err != "") { Error(htmlspecialchars($err)); return; }

		$aeport = (int)$aeport;
		$aetls = ($aetls == "1") ? 1 : 0;

		$sqlstring = "insert into dicom_ae (ae_title, ae_hostname, ae_ip, ae_port, ae_tls) values (?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssii', $aetitle, $aehostname, $aeip, $aeport, $aetls);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$aetitle, $aehostname, $aeip, $aeport, $aetls]);
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
	/* ------- TestAE ----------------------------- */
	/* -------------------------------------------- */
	/* check whether the remote AE is reachable from this server: DNS lookup of the
	   hostname, then a TCP connection and a DICOM C-ECHO (dcmtk echoscu) against the
	   hostname and the IP address separately. Read-only, so it's a plain GET */
	function TestAE($id) {
		$id = (int)$id;
		$sqlstring = "select * from dicom_ae where dicomae_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) { Error("DICOM AE end point not found"); return; }
		$aetitle = $row['ae_title'];
		$aehostname = $row['ae_hostname'] ?? '';
		$aeip = $row['ae_ip'] ?? '';
		$aeport = (int)$row['ae_port'];
		$aetls = $row['ae_tls'];

		set_time_limit(120); /* each C-ECHO can take up to ~25 seconds to time out */

		/* each check is [target, check, status (pass|fail|warn|skip), details] */
		$checks = array();
		$targets = array();

		/* resolve the hostname, and compare against the stored IP */
		if ($aehostname != "") {
			$resolved = gethostbynamel($aehostname);
			if ($resolved === false) $resolved = array();
			$aaaa = @dns_get_record($aehostname, DNS_AAAA);
			if (is_array($aaaa)) {
				foreach ($aaaa as $rec) { $resolved[] = $rec['ipv6']; }
			}
			$resolved = array_values(array_unique($resolved));

			if (count($resolved) > 0) {
				$checks[] = array($aehostname, "DNS lookup", "pass", "Resolves to " . implode(", ", $resolved));
				$targets[] = $aehostname;

				if ($aeip != "") {
					$match = false;
					foreach ($resolved as $addr) {
						if (inet_pton($addr) === inet_pton($aeip)) { $match = true; break; }
					}
					if (!$match)
						$checks[] = array($aehostname, "Hostname/IP match", "warn", "Hostname does not resolve to the stored IP address $aeip");
				}
			}
			else {
				$checks[] = array($aehostname, "DNS lookup", "fail", "Hostname could not be resolved");
			}
		}
		if ($aeip != "")
			$targets[] = $aeip;

		/* find echoscu (dcmtk) for the C-ECHO */
		$echoscu = "";
		exec("command -v echoscu 2>/dev/null", $echoscupath, $rc);
		if (($rc == 0) && (count($echoscupath) > 0))
			$echoscu = trim($echoscupath[0]);
		elseif (is_executable("/usr/local/bin/echoscu"))
			$echoscu = "/usr/local/bin/echoscu";

		foreach ($targets as $target) {
			/* TCP connection to the port. IPv6 literals need brackets for fsockopen */
			$sockhost = (filter_var($target, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) ? "[$target]" : $target;
			$start = microtime(true);
			$fp = @fsockopen($sockhost, $aeport, $errno, $errstr, 5);
			$ms = round((microtime(true) - $start) * 1000);
			if ($fp) {
				fclose($fp);
				$checks[] = array($target, "TCP connect to port $aeport", "pass", "Connected in $ms ms");
			}
			else {
				$checks[] = array($target, "TCP connect to port $aeport", "fail", "Could not connect: $errstr ($errno)");
				$checks[] = array($target, "DICOM C-ECHO", "skip", "Skipped because the TCP connection failed");
				continue;
			}

			/* DICOM C-ECHO */
			if ($echoscu == "") {
				$checks[] = array($target, "DICOM C-ECHO", "warn", "echoscu (dcmtk) was not found on this server, so C-ECHO was not tested");
				continue;
			}
			$systemstring = escapeshellarg($echoscu) . " -aet NIDB -aec " . escapeshellarg($aetitle) . " -to 5 -ta 10 -td 10";
			if ($aetls)
				$systemstring .= " +tla -ic"; /* anonymous TLS, peer certificate not verified */
			$systemstring .= " " . escapeshellarg($target) . " " . $aeport . " 2>&1";
			$output = array();
			$start = microtime(true);
			exec($systemstring, $output, $rc);
			$ms = round((microtime(true) - $start) * 1000);
			if ($rc == 0)
				$checks[] = array($target, "DICOM C-ECHO", "pass", "C-ECHO succeeded in $ms ms");
			else
				$checks[] = array($target, "DICOM C-ECHO", "fail", "C-ECHO failed (exit code $rc)\n" . implode("\n", $output));
		}

		$icons = array("pass" => "green check circle", "fail" => "red times circle", "warn" => "yellow exclamation triangle", "skip" => "grey minus circle");
	?>
		<div class="ui container">
			<h1 class="ui header">
				Connectivity test: <?=htmlspecialchars($aetitle)?>
				<div class="sub header">Port <?=$aeport?><? if ($aetls) { ?>, TLS (anonymous, peer certificate not verified)<? } ?>. Calling AE title NIDB</div>
			</h1>
			<table class="ui very compact celled grey table">
				<thead>
					<tr>
						<th>Target</th>
						<th>Check</th>
						<th>Result</th>
						<th>Details</th>
					</tr>
				</thead>
				<tbody>
					<? foreach ($checks as $check) { list($target, $checkname, $status, $details) = $check; ?>
					<tr class="<? if ($status == "pass") { echo "positive"; } elseif ($status == "fail") { echo "negative"; } elseif ($status == "warn") { echo "warning"; } ?>">
						<td><?=htmlspecialchars($target)?></td>
						<td><?=htmlspecialchars($checkname)?></td>
						<td><i class="<?=$icons[$status]?> icon"></i><?=ucfirst($status)?></td>
						<td><pre style="margin: 0; white-space: pre-wrap"><?=htmlspecialchars($details)?></pre></td>
					</tr>
					<? } ?>
				</tbody>
			</table>
			<a href="admindicomae.php?action=test&id=<?=$id?>" class="ui primary button"><i class="redo icon"></i>Test again</a>
			<a href="admindicomae.php?action=editform&id=<?=$id?>" class="ui button">Edit</a>
			<a href="admindicomae.php" class="ui button">Back to list</a>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAEForm ---------------------- */
	/* -------------------------------------------- */
	function DisplayAEForm($type, $id) {

		/* defaults for the "add" form */
		$aetitle = "";
		$aehostname = "";
		$aeip = "";
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
			$aehostname = $row['ae_hostname'] ?? '';
			$aeip = $row['ae_ip'] ?? '';
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

				<div class="two fields">
					<div class="field">
						<label>Hostname</label>
						<input type="text" name="aehostname" value="<?=htmlspecialchars($aehostname)?>" maxlength="255" placeholder="pacs.example.org">
					</div>
					<div class="field">
						<label>IP Address</label>
						<input type="text" name="aeip" value="<?=htmlspecialchars($aeip)?>" maxlength="255" placeholder="IPv4 or IPv6 address">
					</div>
				</div>
				<div class="ui small info message">A hostname or IP address (or both) is required</div>

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
				<a href="admindicomae.php?action=test&id=<?=$id?>" class="ui basic button"><i class="plug icon"></i>Test connectivity</a>
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
					<th>Hostname</th>
					<th>IP Address</th>
					<th>Port</th>
					<th>TLS</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?
					$sqlstring = "select * from dicom_ae order by ae_title";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					if (mysqli_num_rows($result) < 1) {
						?><tr><td colspan="6" class="center aligned">No DICOM AE end points defined</td></tr><?
					}
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['dicomae_id'];
						$aetitle = $row['ae_title'];
						$aehostname = $row['ae_hostname'] ?? '';
						$aeip = $row['ae_ip'] ?? '';
						$aeport = $row['ae_port'];
						$aetls = $row['ae_tls'];
				?>
				<tr>
					<td><a href="admindicomae.php?action=editform&id=<?=$id?>"><?=htmlspecialchars($aetitle)?></a></td>
					<td><?=htmlspecialchars($aehostname)?></td>
					<td><?=htmlspecialchars($aeip)?></td>
					<td><?=(int)$aeport?></td>
					<td><? if ($aetls) { ?><i class="green lock icon"></i> Yes<? } else { ?>No<? } ?></td>
					<td><a href="admindicomae.php?action=test&id=<?=$id?>" class="ui mini basic button"><i class="plug icon"></i>Test</a></td>
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
