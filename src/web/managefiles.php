<?
 // ------------------------------------------------------------------------------
 // NiDB managefiles.php
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
	
	ob_start();
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Manage Files</title>
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
	$seriesid = (int)GetVariable("seriesid");
	$modality = strtolower(trim((string)GetVariable("modality")));
	$datatype = (string)GetVariable("datatype");
	$filename = (string)GetVariable("filename");
	$newfilename = (string)GetVariable("newfilename");

	/* modality is used in a table name and datatype in a path, so both must be plain identifiers */
	if ((GetSeriesTableName($modality) == '') || (!IsNiDBModality($modality))) {
		Error("Invalid modality");
	}
	elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $datatype)) {
		Error("Invalid datatype");
	}
	/* determine action. delete and rename change raw data, so they must be POSTed with this
	   session's CSRF token, then redirect back to the file list (Post/Redirect/GET) */
	elseif (($action == "delete") || ($action == "rename")) {
		$returnurl = "managefiles.php?seriesid=$seriesid&modality=" . urlencode($modality) . "&datatype=" . urlencode($datatype);
		ob_start();
		if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
			Error("This action must be submitted from the file list");
		}
		elseif (VerifyCSRFToken()) {
			if ($action == "delete")
				DeleteFile($seriesid, $modality, $datatype, $filename);
			else
				RenameFile($seriesid, $modality, $datatype, $filename, $newfilename);
		}
		$_SESSION['flash'] = ob_get_clean();
		RedirectTo($returnurl);
	}
	else {
		ShowFlashMessage();
		DisplayFileList($seriesid, $modality, $datatype);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- GetSeries -------------------------- */
	/* -------------------------------------------- */
	/* the series row plus the study/subject/project it belongs to, or null. $modality must already be
	   validated (it is used in a table name) */
	function GetSeries($seriesid, $modality) {
		$tablename = GetSeriesTableName($modality);
		$seriesid = (int)$seriesid;
		if (($tablename == '') || ($seriesid < 1))
			return null;

		$sqlstring = "select a.*, b.study_num, b.study_id, c.project_id, d.uid, d.subject_id from $tablename a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality" . "series_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $seriesid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($seriesid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		return ($row) ? $row : null;
	}


	/* -------------------------------------------- */
	/* ------- UserHasSeriesPerm ------------------ */
	/* -------------------------------------------- */
	/* $perm is 'viewdata' to list files, 'modifydata' to delete or rename them */
	function UserHasSeriesPerm($series, $perm) {
		$projectid = (int)($series['project_id'] ?? 0);
		if ($projectid < 1)
			return false;

		$perms = GetCurrentUserProjectPermissions(array($projectid));
		return (GetPerm($perms, $perm, $projectid) == 1);
	}


	/* -------------------------------------------- */
	/* ------- GetDataDir ------------------------- */
	/* -------------------------------------------- */
	/* the series' data directory, resolved with realpath(), or '' if it doesn't exist or isn't inside
	   the archive. A blank UID, study, or series number would point at a parent directory */
	function GetDataDir($series, $datatype) {
		$uid = trim((string)($series['uid'] ?? ''));
		$study_num = trim((string)($series['study_num'] ?? ''));
		$series_num = trim((string)($series['series_num'] ?? ''));
		if (($uid == '') || ($study_num == '') || ($series_num == '') || (!preg_match('/^[A-Za-z0-9_-]+$/', $datatype)))
			return '';

		$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$datatype";
		if (!file_exists($path)) {
			$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/" . strtolower($datatype);
		}

		$realpath = realpath($path);
		$archivedir = realpath($GLOBALS['cfg']['archivedir']);
		if (($realpath === false) || ($archivedir === false) || ($archivedir == '/') || (!is_dir($realpath)))
			return '';
		if (substr($realpath, 0, strlen($archivedir) + 1) !== $archivedir . '/')
			return '';

		return $realpath;
	}


	/* -------------------------------------------- */
	/* ------- GetFileInDir ----------------------- */
	/* -------------------------------------------- */
	/* full path of $filename if it is a regular file directly inside $datadir, otherwise ''. Rejects
	   directory components, ../, and symlinks that point elsewhere */
	function GetFileInDir($datadir, $filename) {
		if (($datadir == '') || ($filename == '') || ($filename !== basename($filename)) || ($filename == '.') || ($filename == '..'))
			return '';

		$realfile = realpath("$datadir/$filename");
		if (($realfile === false) || (dirname($realfile) !== $datadir) || (!is_file($realfile)))
			return '';

		return $realfile;
	}


	/* -------------------------------------------- */
	/* ------- DeleteFile ------------------------- */
	/* -------------------------------------------- */
	function DeleteFile($seriesid, $modality, $datatype, $filename) {
		$series = GetSeries($seriesid, $modality);
		if (!$series) {
			Error("Series not found");
			return;
		}
		if (!UserHasSeriesPerm($series, 'modifydata')) {
			Error("You do not have permission to change the files in this series");
			return;
		}

		$filepath = GetFileInDir(GetDataDir($series, $datatype), $filename);
		if ($filepath == '') {
			Error("File [" . htmlspecialchars($filename) . "] was not found in this series");
			return;
		}

		unlink($filepath);
		if (file_exists($filepath)) {
			Error("Could not delete " . htmlspecialchars($filepath));
		}
		else {
			Notice("Deleted " . htmlspecialchars($filepath));
		}
	}


	/* -------------------------------------------- */
	/* ------- RenameFile ------------------------- */
	/* -------------------------------------------- */
	function RenameFile($seriesid, $modality, $datatype, $filename, $newfilename) {
		$newfilename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $newfilename);
		if (($newfilename == '') || ($newfilename == '.') || ($newfilename == '..')) {
			Error("Invalid new filename");
			return;
		}

		$series = GetSeries($seriesid, $modality);
		if (!$series) {
			Error("Series not found");
			return;
		}
		if (!UserHasSeriesPerm($series, 'modifydata')) {
			Error("You do not have permission to change the files in this series");
			return;
		}

		$datadir = GetDataDir($series, $datatype);
		$oldfilepath = GetFileInDir($datadir, $filename);
		if ($oldfilepath == '') {
			Error("File [" . htmlspecialchars($filename) . "] was not found in this series");
			return;
		}
		$newfilepath = "$datadir/$newfilename";
		if (file_exists($newfilepath)) {
			Error("A file named [" . htmlspecialchars($newfilename) . "] already exists in this series");
			return;
		}

		if (rename($oldfilepath, $newfilepath)) {
			Notice("Successfully renamed [" . htmlspecialchars($oldfilepath) . "] to [" . htmlspecialchars($newfilepath) . "]");
		}
		else {
			Notice("Problem renaming [" . htmlspecialchars($oldfilepath) . "] to [" . htmlspecialchars($newfilepath) . "]");
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFileList -------------------- */
	/* -------------------------------------------- */
	function DisplayFileList($seriesid, $modality, $datatype) {
		$seriesid = (int)$seriesid;

		$row = GetSeries($seriesid, $modality);
		if (!$row) {
			Error("Series not found");
			return;
		}
		if (!UserHasSeriesPerm($row, 'viewdata')) {
			Error("You do not have permission to view the files in this series");
			return;
		}

		$subjectid = $row['subject_id'];
		$uid = $row['uid'];
		$study_num = $row['study_num'];
		$studyid = $row['study_id'];
		$protocol = $row['series_desc'] ?? '';
		$series_num = $row['series_num'];

		$datadir = GetDataDir($row, $datatype);
		if ($datadir == '') {
			Error("The data directory for this series does not exist");
			return;
		}
		
		$files = scandir($datadir);
		if ($files === false) $files = array();

		/* update the DB with the files that actually exist */
		$filecount = count(glob("$datadir/*"));
		$filesize = GetDirectorySize($datadir);
		if ($modality == "mr") {
			$sqlstring = "update mr_series set numfiles_beh = ?, beh_size = ? where mrseries_id = ?";
		}
		else {
			$sqlstring = "update $modality" . "_series set series_numfiles = ?, series_size = ? where $modality" . "series_id = ?";
		}
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'idi', $filecount, $filesize, $seriesid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($filecount, $filesize, $seriesid));
		mysqli_stmt_close($stmt);

		?>
		<b>Displaying files for</b>
		<br><br>
		<div style="padding: 1px 10px">
		<b>Subject ID:</b> <?=htmlspecialchars($uid ?? '')?><br>
		<b>Study #:</b> <?=htmlspecialchars($study_num ?? '')?><br>
		<b>Series #:</b> <?=htmlspecialchars($series_num ?? '')?><br>
		<b>Datatype:</b> <?=htmlspecialchars($datatype)?><br>
		<b>Protocol:</b> <?=htmlspecialchars($protocol)?>
		</div>
		<br><br>
		<table class="ui very compact celled grey table">
			<thead>
				<tr>
					<th>Filename</th>
					<th>Rename <span class="tiny">press Enter to rename</span></th>
					<th>Type</th>
					<th>Size <span class="tiny">(bytes)</span></th>
					<th>Date created</th>
					<th>Date accessed</th>
					<th>Date modified</th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody>
				<?
					foreach ($files as $file) {
						if (($file != ".") && ($file != "..")) {
							$filehtml = htmlspecialchars($file);
							$fileurl = urlencode("$datadir/$file");
							$size = number_format(filesize("$datadir/$file"),0);
							$pathparts = pathinfo("$datadir/$file");
							$atime = date('M j, Y g:ia', fileatime("$datadir/$file"));
							$ctime = date('M j, Y g:ia', filectime("$datadir/$file"));
							$mtime = date('M j, Y g:ia', filemtime("$datadir/$file"));
							
							switch (strtolower($pathparts['extension'])) {
								case "edat2": $filetype = "ePrime 2 data file"; break;
								case "edat": $filetype = "ePrime data file"; break;
								case "txt": $filetype = "Text file"; break;
								case "cir": $filetype = "CIRC experiment file"; break;
								case "vap": $filetype = "VAPP experiment file"; break;
								case "csv": $filetype = "CSV file"; break;
								case "log": $filetype = "CIRC binary (or other) log file"; break;
								case "cnt": $filetype = "Neuroscan file"; break;
								case "3dd": $filetype = "Polhemus file"; break;
								case "dat": $filetype = "Polhemus file"; break;
								case "flv": $filetype = "Flash Video"; break;
								case "ogg": $filetype = "Ogg Theora Video"; break;
								case "mp4": $filetype = "MPEG4 Video"; break;
								case "ogv": $filetype = "Ogg Theora Video"; break;
								case "wmv": $filetype = "Windows Media Video"; break;
								default: $filetype = "";
							}
							
							?>
							<tr style="font-size: 10pt">
								<td><a href="getfile.php?action=download&file=<?=$fileurl?>" style="color: darkblue; font-weight: bold"><?=$filehtml?></a>
								<?
									if ($datatype == "VIDEO") {
										if ($filetype == "Flash Video") {
										?>
										<object type="application/x-shockwave-flash" width="320" height="260" wmode="transparent" data="flvplayer.swf?file=getfile.php%3Ffile%3D<?=urlencode($fileurl)?>&autoStart=false">
											<param name="movie" value="flvplayer.swf?file=getfile.php%3Ffile%3D<?=urlencode($fileurl)?>&autoStart=false" />
											<param name="wmode" value="transparent" />

										</object>
										<?
										}
										else {
										?>
										<video controls="controls">
											<source src="getfile.php?file=<?=$fileurl?>">
										</video>
										<?
										}
									}
								?>
								</td>
								<form action="managefiles.php" method="post" class="ui form">
									<?=CSRFTokenField()?>
									<input type="hidden" name="action" value="rename">
									<input type="hidden" name="seriesid" value="<?=$seriesid?>">
									<input type="hidden" name="modality" value="<?=htmlspecialchars($modality)?>">
									<input type="hidden" name="datatype" value="<?=htmlspecialchars($datatype)?>">
									<input type="hidden" name="filename" value="<?=$filehtml?>">
									<td><div class="ui fluid input"><input type="text" maxlength="255" name="newfilename" value="<?=$filehtml?>"></div></td>
									<input type="submit" style="display:none">
								</form>
								<td><?=$filetype?></td>
								<td><?=$size?></td>
								<td><?=$ctime?></td>
								<td><?=$atime?></td>
								<td><?=$mtime?></td>
								<td>
									<form action="managefiles.php" method="post" style="margin:0" onsubmit="return confirm('Delete this file?')">
										<?=CSRFTokenField()?>
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="seriesid" value="<?=$seriesid?>">
										<input type="hidden" name="modality" value="<?=htmlspecialchars($modality)?>">
										<input type="hidden" name="datatype" value="<?=htmlspecialchars($datatype)?>">
										<input type="hidden" name="filename" value="<?=$filehtml?>">
										<button type="submit" class="ui basic icon button" style="box-shadow:none; padding:0" title="Delete"><i class="ui large red trash alternate icon"></i></button>
									</form>
								</td>
							</tr>
							<?
						}
					}
				?>
			</tbody>
		</table>
		<?
	}
	
	/* functions must be at the end of the script, classes at the beginning, eh? */
	function GetDirectorySize($dirname) {

		//echo "$dirname<br>";
		// open the directory, if the script cannot open the directory then return folderSize = 0
		$dir_handle = opendir($dirname);
		if (!$dir_handle) return 0;

		$folderSize = 0;
		// traversal for every entry in the directory
		while ($file = readdir($dir_handle)){

			//echo "$file<br>";
			// ignore '.' and '..' directory
			if  ($file  !=  "."  &&  $file  !=  "..")  {

				// if entry is directory then go recursive !
				if  (is_dir($dirname."/".$file)){
						  $folderSize += GetDirectorySize($dirname.'/'.$file);

				// if file then accumulate the size
				} else {
					  $folderSize += filesize($dirname."/".$file);
				}
			}
		}
		// chose the directory
		closedir($dir_handle);

		// return $dirname folder size
		return $folderSize ;
	}	
?>


<? include("footer.php") ?>
