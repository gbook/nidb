<?
 // ------------------------------------------------------------------------------
 // NiDB publicdownloads.php
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
		<title>NiDB - Manage Public Downloads</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nidbapi.php";
	
	/* ----- setup variables ----- */
	$action = GetSafeAction(array("changepassword", "delete")); /* mutating actions require POST + CSRF token */
	$id = (int)GetVariable("id");
	$password = GetVariable("password");
	
	/* determine action */
	/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-submit. Neither has a form in the UI yet */
	if ($action == "changepassword") {
		ob_start();
		ChangePassword($id, $password);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("publicdownloads.php");
	}
	elseif ($action == "delete") {
		ob_start();
		DeleteDownload($id);
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo("publicdownloads.php");
	}
	else {
		DisplayDownloadList();
	}

	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- ChangePassword --------------------- */
	/* -------------------------------------------- */
	function ChangePassword($id, $password) {
		$id = (int)$id;
		$pwd = sha1((string)$password);
		
		/* only the download's creator can change its password */
		$sqlstring = "update public_downloads set pd_password = ? where pd_id = ? and pd_createdby = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sis', $pwd, $id, $_SESSION['username']);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array('(password)', $id, $_SESSION['username']));
		mysqli_stmt_close($stmt);
		
		Notice("Download $id password updated");
	}


	/* -------------------------------------------- */
	/* ------- DeleteDownload --------------------- */
	/* -------------------------------------------- */
	function DeleteDownload($id) {
		//$sqlstring = "delete from nidb_sites where site_id = $id";
		//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}	
	

	/* -------------------------------------------- */
	/* ------- DisplayDownloadList ---------------- */
	/* -------------------------------------------- */
	function DisplayDownloadList() {
		ShowFlashMessage(); /* show any message from a mutating action that redirected here (PRG) */
	?>
	<script>
		function CopyToClipboard(id) {
			/* Get the text field */
			var copyText = document.getElementById(id);

			/* Select the text field */
			copyText.select();
			copyText.setSelectionRange(0, 99999); /* For mobile devices */

			/* Copy the text inside the text field */
			navigator.clipboard.writeText(copyText.value);

			/* Alert the copied text */
			alert("Link copied!");
		}
	</script>

	<table class="ui small celled selectable grey compact table">
		<thead>
			<tr>
				<th>Description</th>
				<th>Status</th>
				<th>Created</th>
				<th>Expires</th>
				<th>Release notes</th>
				<th>Zip size<br><span class="tiny" style="font-weight: normal">bytes</span></th>
				<th>Unzipped size<br><span class="tiny" style="font-weight: normal">bytes</span></th>
				<th>Creator</th>
				<th>Password</th>
				<th>Download link<br><span class="tiny" style="font-weight: normal">Copy link to use</span></th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select * from public_downloads where pd_createdby = ? or pd_shareinternal = 1 order by pd_createdate desc";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 's', $_SESSION['username']);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($_SESSION['username']));
				mysqli_stmt_close($stmt);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['pd_id'];
					$createdate = $row['pd_createdate'];
					$expiredate = $row['pd_expiredate'];
					$expiredays = $row['pd_expiredays'];
					$createdby = $row['pd_createdby'];
					$zipsize = $row['pd_zippedsize'];
					$unzipsize = $row['pd_unzippedsize'];
					$filename = $row['pd_filename'];
					$desc = $row['pd_desc'];
					$notes = $row['pd_notes'];
					$filecontents = $row['pd_filecontents'];
					$shareinternal = $row['pd_shareinternal'];
					$registerrequired = $row['pd_registerrequired'];
					$password = strtoupper($row['pd_password']);
					$status = $row['pd_status'];
					$key = strtoupper($row['pd_key']);
			?>
			<tr>
				<td><?=htmlspecialchars($desc ?? '')?></td>
				<td><?=htmlspecialchars($status ?? '')?></td>
				<td style="font-size:9pt"><?=htmlspecialchars($createdate ?? '')?></td>
				<td style="font-size:9pt"><?=htmlspecialchars($expiredate ?? '')?></td>
				<td><i class="sticky note outline icon" title="<?=htmlspecialchars($notes ?? '')?>"></i></td>
				<td style="font-size:9pt" align="right"><?=HumanReadableFilesize($zipsize)?></td>
				<td style="font-size:9pt" align="right"><?=HumanReadableFilesize($unzipsize)?></td>
				<td><?=htmlspecialchars($createdby ?? '')?></td>
				<td style="font-size:8pt"><tt><?=htmlspecialchars($password)?></tt></td>
				<td>
					<div class="ui action input">
						<input type="text" size="80" id="linktext<?=$id?>" value="<?=htmlspecialchars($GLOBALS['cfg']['siteurl'] . "/pd.php?k=$key")?>">
						<button class="ui button" onClick="CopyToClipboard('linktext<?=$id?>')" title="Copy only works when HTTPS is enabled :("><i class="copy icon"></i> Copy</button>
					</div>
				</td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}
?>


<? include("footer.php") ?>
