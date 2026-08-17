<?
 // ------------------------------------------------------------------------------
 // NiDB adminstorage.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Manage Storage Tiers</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nidbapi.php";

	/* the 4 archive storage tiers are keyed by these config names (archivedir1..archivedir4) */
	$tierconfignames = array('archivedir1', 'archivedir2', 'archivedir3', 'archivedir4');

	if (!isAdmin()) {
		Error("This account does not have permissions to view this page");
	}
	else {
		/* ----- setup variables ----- */
		$action = GetVariable("action");
		$id = GetVariable("id");
		$storagetype = GetVariable("storagetype");
		$storageusername = GetVariable("storageusername");
		$storagepassword = GetVariable("storagepassword");
		$storagetoken = GetVariable("storagetoken");
		$storagecapacity = GetVariable("storagecapacity");

		/* make sure the 4 tier rows exist before doing anything */
		EnsureStorageTiers($tierconfignames);

		/* determine action */
		if ($action == "editform") {
			DisplayStorageForm($id);
		}
		elseif ($action == "update") {
			UpdateStorageTier($id, $storagetype, $storageusername, $storagepassword, $storagetoken, $storagecapacity);
			DisplayStorageList();
		}
		else {
			DisplayStorageList();
		}
	}

	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- EnsureStorageTiers ----------------- */
	/* -------------------------------------------- */
	/* The storage_tiers table always has exactly 4 rows, one per config name (archivedir1..4).
	   Insert any that are missing so the list/edit forms always have all 4 available.

	   The storage path is authoritative in nidb.cfg (archivedir1..4) and is NOT editable in the
	   database - this is deliberate, so the path is still known from the config file even if the
	   database is inaccessible. We mirror the config value into storage_path on every load so the
	   DB copy stays in sync, but the only way to change a path is to edit the config. */
	function EnsureStorageTiers($confignames) {
		foreach ($confignames as $configname) {
			/* the path always comes from the config, never from user input on this page */
			$configpath = $GLOBALS['cfg'][$configname] ?? '';

			$stmt = mysqli_prepare($GLOBALS['linki'], "select storagetier_id from storage_tiers where storage_configname = ?");
			mysqli_stmt_bind_param($stmt, 's', $configname);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$exists = (mysqli_num_rows($result) > 0);
			mysqli_stmt_close($stmt);

			if (!$exists) {
				$stmt = mysqli_prepare($GLOBALS['linki'], "insert into storage_tiers (storage_configname, storage_type, storage_path, storage_capacity) values (?, 'nfs', ?, 0)");
				mysqli_stmt_bind_param($stmt, 'ss', $configname, $configpath);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				mysqli_stmt_close($stmt);
			}
			else {
				/* keep the DB path mirror in sync with the config */
				$stmt = mysqli_prepare($GLOBALS['linki'], "update storage_tiers set storage_path = ? where storage_configname = ?");
				mysqli_stmt_bind_param($stmt, 'ss', $configpath, $configname);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				mysqli_stmt_close($stmt);
			}
		}
	}


	/* -------------------------------------------- */
	/* ------- UpdateStorageTier ------------------ */
	/* -------------------------------------------- */
	function UpdateStorageTier($id, $storagetype, $storageusername, $storagepassword, $storagetoken, $storagecapacity) {
		$id = (int)$id;

		/* only nfs/s3/gcs are valid types; anything else (incl. the blank "Not configured"
		   option) is stored as NULL, which marks the tier as not configured */
		if (!in_array($storagetype, array('nfs', 's3', 'gcs')))
			$storagetype = null;
		$storagecapacity = (int)$storagecapacity;
		/* GetVariable() returns NULL for blank fields; store empty strings instead */
		$storageusername = ($storageusername ?? '');
		$storagepassword = ($storagepassword ?? '');
		$storagetoken = ($storagetoken ?? '');

		$stmt = mysqli_prepare($GLOBALS['linki'], "update storage_tiers set storage_type = ?, storage_username = ?, storage_password = ?, storage_token = ?, storage_capacity = ? where storagetier_id = ?");
		mysqli_stmt_bind_param($stmt, 'ssssii', $storagetype, $storageusername, $storagepassword, $storagetoken, $storagecapacity, $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		?><div align="center"><span class="message">Storage tier updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DisplayStorageForm ----------------- */
	/* -------------------------------------------- */
	function DisplayStorageForm($id) {
		$id = (int)$id;

		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from storage_tiers where storagetier_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$row) {
			Error("Storage tier not found");
			return;
		}

		$configname = $row['storage_configname'];
		$storagetype = $row['storage_type'];
		$storageusername = $row['storage_username'] ?? '';
		$storagepassword = $row['storage_password'] ?? '';
		$storagetoken = $row['storage_token'] ?? '';
		$storagecapacity = $row['storage_capacity'] ?? 0;
		/* the on-disk path comes from nidb.cfg, edited on the Settings page */
		$configpath = $GLOBALS['cfg'][$configname] ?? '';
	?>
		<div class="ui text container">
			<div class="ui attached visible message">
				<div class="header">Editing storage tier <?=htmlspecialchars($configname)?></div>
			</div>

			<form method="post" action="adminstorage.php" class="ui form attached fluid segment">
				<input type="hidden" name="action" value="update">
				<input type="hidden" name="id" value="<?=$id?>">

				<div class="field">
					<label>Config name</label>
					<div class="ui small grey segment tt"><?=htmlspecialchars($configname)?></div>
				</div>

				<div class="field">
					<label>Path (from nidb.cfg)</label>
					<div class="ui small grey segment tt"><?= ($configpath == '') ? '<span style="color:#999">(not set)</span>' : htmlspecialchars($configpath) ?></div>
					<div class="ui pointing label">The storage path is set on the <a href="settings.php">Settings</a> page (<?=htmlspecialchars($configname)?>).</div>
				</div>

				<div class="field">
					<label>Storage Type</label>
					<select name="storagetype" class="ui dropdown">
						<option value="" <?=($storagetype===null)?'selected':''?>>Not configured</option>
						<option value="nfs" <?=($storagetype=='nfs')?'selected':''?>>NFS / local path</option>
						<option value="s3" <?=($storagetype=='s3')?'selected':''?>>Amazon S3</option>
						<option value="gcs" <?=($storagetype=='gcs')?'selected':''?>>Google Cloud Storage (GCS)</option>
					</select>
				</div>

				<div class="two fields">
					<div class="field">
						<label>Username</label>
						<input type="text" name="storageusername" value="<?=htmlspecialchars($storageusername)?>" placeholder="Access username (if required)" autocomplete="off">
					</div>
					<div class="field">
						<label>Password</label>
						<input type="password" name="storagepassword" value="<?=htmlspecialchars($storagepassword)?>" placeholder="Access password (if required)" autocomplete="new-password">
					</div>
				</div>

				<div class="field">
					<label>Access Token</label>
					<textarea name="storagetoken" rows="3" placeholder="Access token (if required)"><?=htmlspecialchars($storagetoken)?></textarea>
				</div>

				<div class="field">
					<label>Capacity (GB)</label>
					<input type="number" name="storagecapacity" value="<?=(int)$storagecapacity?>" min="0" placeholder="0">
				</div>

				<input type="submit" value="Update" class="ui primary button">
				<a href="adminstorage.php" class="ui button">Cancel</a>
			</form>
		</div>
	<?
	}


	/* -------------------------------------------- */
	/* ------- StoragePathStatusIcon -------------- */
	/* -------------------------------------------- */
	/* Returns a status icon indicating whether a storage path exists on disk:
	   grey dash = not configured, green check = exists, red exclamation = set but missing. */
	function StoragePathStatusIcon($path) {
		if ($path == "")
			return '<i class="large grey minus icon" title="Not configured"></i>';
		elseif (@file_exists($path))
			return '<i class="large green check circle icon" title="Path exists"></i>';
		else
			return '<i class="large red exclamation circle icon" title="Path not found"></i>';
	}


	/* -------------------------------------------- */
	/* ------- StoragePathUsageBar ---------------- */
	/* -------------------------------------------- */
	/* Returns a disk-usage progress bar (like 'df') for a storage path: a bar filled to the used
	   percentage with the percent overlaid, and "X GB free of Y GB" below. Blank if the path is
	   not set or not reachable (e.g. s3/gcs tiers, or a missing directory). */
	function StoragePathUsageBar($path) {
		if (($path == "") || !@file_exists($path))
			return '';
		$total = @disk_total_space($path);
		$free = @disk_free_space($path);
		if (($total === false) || ($total === null) || ($total <= 0))
			return '';
		if (($free === false) || ($free === null) || ($free < 0))
			$free = 0;

		$gb = 1073741824; /* 1 GiB, matching 'df -h' */
		$totalGB = round($total / $gb);
		$freeGB = round($free / $gb);
		$usedpct = round((($total - $free) / $total) * 100);

		/* green normally, orange when getting full, red when nearly full */
		$color = "#21ba45";
		if ($usedpct >= 90) $color = "#db2828";
		elseif ($usedpct >= 75) $color = "#f2711c";

		$out  = '<div style="position:relative; width:150px; max-width:100%; height:16px; background:#e8e8e8; border-radius:3px; overflow:hidden;">';
		$out .= '<div style="position:absolute; left:0; top:0; height:100%; width:' . $usedpct . '%; background:' . $color . ';"></div>';
		$out .= '<div style="position:absolute; width:100%; text-align:center; font-size:11px; line-height:16px; color:#333;">' . $usedpct . '%</div>';
		$out .= '</div>';
		$out .= '<div style="font-size:11px; color:#888; margin-top:2px;">' . number_format($freeGB) . ' GB free of ' . number_format($totalGB) . ' GB</div>';
		return $out;
	}


	/* -------------------------------------------- */
	/* ------- DisplayStorageList ----------------- */
	/* -------------------------------------------- */
	function DisplayStorageList() {
	?>
	<div class="ui container">
		<div class="ui two column grid">
			<div class="column">
				<h1 class="ui header">Storage Tiers</h1>
			</div>
		</div>
		<div class="ui info message">
			These are the 4 archive storage tiers (<span class="tt">archivedir1</span> &ndash; <span class="tt">archivedir4</span>). The storage path for each tier is set on the <a href="settings.php">Settings</a> page; storage type, credentials, and capacity are managed here.
		</div>
		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th>Config name</th>
					<th class="center aligned">Configured</th>
					<th>Path (from nidb.cfg)</th>
					<th class="center aligned">Exists</th>
					<th>Type</th>
					<th>Disk usage (df)</th>
				</tr>
			</thead>
			<tbody>
				<?
					/* the primary/default archive directory (archivedir) is shown read-only for
					   reference - it is not a storage tier and lives only in nidb.cfg */
					$defaultpath = $GLOBALS['cfg']['archivedir'] ?? '';
				?>
				<tr class="disabled">
					<td class="tt">archivedir <span class="ui mini label">default</span></td>
					<td class="center aligned"><i class="large grey minus icon" title="Not applicable - the default archive is not a storage tier"></i></td>
					<td class="tt"><?= ($defaultpath == '') ? '<span style="color:#999">(not set)</span>' : htmlspecialchars($defaultpath) ?></td>
					<td class="center aligned"><?=StoragePathStatusIcon($defaultpath)?></td>
					<td>NFS / local path</td>
					<td><?=StoragePathUsageBar($defaultpath)?></td>
				</tr>
				<?
					/* order by config name so the 4 tiers always appear in the same order */
					$sqlstring = "select * from storage_tiers order by storage_configname";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					$typelabels = array('nfs' => 'NFS / local path', 's3' => 'Amazon S3', 'gcs' => 'Google Cloud Storage');
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = $row['storagetier_id'];
						$configname = $row['storage_configname'];
						$storagetype = $row['storage_type'];
						$configpath = $GLOBALS['cfg'][$configname] ?? '';
						/* a NULL storage_type means the tier has not been configured */
						$isconfigured = ($storagetype !== null);
				?>
				<tr>
					<td><a href="adminstorage.php?action=editform&id=<?=$id?>" class="tt"><?=htmlspecialchars($configname)?></a></td>
					<td class="center aligned"><?= $isconfigured ? '<i class="large green check circle icon" title="Configured"></i>' : '<i class="large grey circle outline icon" title="Not configured"></i>' ?></td>
					<td class="tt"><?= ($configpath == '') ? '<span style="color:#999">(not set)</span>' : htmlspecialchars($configpath) ?></td>
					<td class="center aligned"><?=StoragePathStatusIcon($configpath)?></td>
					<td><?= $isconfigured ? htmlspecialchars($typelabels[$storagetype] ?? $storagetype) : '<span style="color:#999">(not configured)</span>' ?></td>
					<td><?=StoragePathUsageBar($configpath)?></td>
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
