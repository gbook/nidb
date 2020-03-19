<?
 // ------------------------------------------------------------------------------
 // NiDB managefiles.php
 // Copyright (C) 2004 - 2020
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
	$seriesid = GetVariable("seriesid");
	$modality = GetVariable("modality");
	$datatype = GetVariable("datatype");
	$filename = GetVariable("filename");
	$newfilename = GetVariable("newfilename");
	
	/* determine action */
	if ($action == "delete") {
		DeleteFile($seriesid, $modality, $datatype, $filename);
		DisplayFileList($seriesid, $modality, $datatype);
	}
	elseif ($action == "rename") {
		RenameFile($seriesid, $modality, $datatype, $filename, $newfilename);
		DisplayFileList($seriesid, $modality, $datatype);
	}
	else {
		DisplayFileList($seriesid, $modality, $datatype);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- GetDataDir ------------------------- */
	/* -------------------------------------------- */
	function GetDataDir($seriesid, $modality, $datatype) {
		$modality = strtolower($modality);
		
		$sqlstring = "select a.series_num, b.study_num, d.uid from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join projects e on c.project_id = e.project_id where a.$modality" . "series_id = $seriesid";
		
		$result2 = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result2, MYSQLI_ASSOC);
		$study_num = $row['study_num'];
		$uid = $row['uid'];
		$series_num = $row['series_num'];
		$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$datatype";
		if (!file_exists($path)) {
			$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/" . strtolower($datatype);
		}
		
		return $path;
	}
	
	
	/* -------------------------------------------- */
	/* ------- DeleteFile ------------------------- */
	/* -------------------------------------------- */
	function DeleteFile($seriesid, $modality, $datatype, $filename) {
		$filepath = GetDataDir($seriesid, $modality, $datatype) . "/$filename";
		
		unlink($filepath);
		if (file_exists($filepath)) {
			echo "Could not delete $filepath";
		}
		else {
			echo "Deleted $filepath";
		}
	}


	/* -------------------------------------------- */
	/* ------- RenameFile ------------------------- */
	/* -------------------------------------------- */
	function RenameFile($seriesid, $modality, $datatype, $filename, $newfilename) {
		$newfilename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $newfilename);
		
		$datadir = GetDataDir($seriesid, $modality, $datatype);
		$oldfilepath = "$datadir/$filename";
		$newfilepath = "$datadir/$newfilename";

		if (rename($oldfilepath, $newfilepath)) {
			?><div align="center"><span class="staticmessage">Successfully renamed [<?=$oldfilepath?>] to [<?=$newfilepath?>]</span></div><br><br><?
		}
		else {
			?><div align="center"><span class="staticmessage">Problem renaming [<?=$oldfilepath?>] to [<?=$newfilepath?>]</span></div><br><br><?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayFileList -------------------- */
	/* -------------------------------------------- */
	function DisplayFileList($seriesid, $modality, $datatype) {
	
		$modality = strtolower($modality);
		
		$sqlstring = "select a.*, b.study_num, b.study_id, d.uid, d.subject_id from $modality" . "_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join projects e on c.project_id = e.project_id where a.$modality" . "series_id = $seriesid";
		
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$subjectid = $row['subject_id'];
		$uid = $row['uid'];
		$study_num = $row['study_num'];
		$studyid = $row['study_id'];
		$series_datetime = date('g:ia',strtotime($row['series_datetime']));
		$protocol = $row['series_desc'];
		$sequence = $row['series_sequencename'];
		$series_num = $row['series_num'];
		$series_tr = $row['series_tr'];
		$series_te = $row['series_te'];
		$series_flip = $row['series_flip'];
		$series_spacingx = $row['series_spacingx'];
		$series_spacingy = $row['series_spacingy'];
		$series_spacingz = $row['series_spacingz'];
		$series_fieldstrength = $row['series_fieldstrength'];
		$img_rows = $row['img_rows'];
		$img_cols = $row['img_cols'];
		$img_slices = $row['img_slices'];
		$bold_reps = $row['bold_reps'];
		$numfiles = $row['numfiles'];
		$series_size = $row['series_size'];
		$series_status = $row['series_status'];
		$series_notes = $row['series_notes'];

		$urllist['Subject List'] = "subjects.php";
		$urllist[$uid] = "subjects.php?action=display&id=$subjectid";
		$urllist["Study " . $study_num] = "studies.php?id=$studyid";
		NavigationBar("Manage files", $urllist);

		$datadir = GetDataDir($seriesid, $modality, $datatype);
		
		$files = scandir($datadir);

		/* update the DB with the files that actually exist */
		$filecount = count(glob("$datadir/*"));
		$filesize = GetDirectorySize($datadir);
		if ($modality == "mr") {
			$sqlstring = "update mr_series set numfiles_beh = $filecount, beh_size = $filesize where mrseries_id = $seriesid";
		}
		else {
			$sqlstring = "update $modality"."_series set series_numfiles = $filecount, series_size = $filesize where $modality"."series_id = $seriesid";
		}
		//echo "$sqlstring";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);

		?>
		<b>Displaying files for</b>
		<br><br>
		<div style="padding: 1px 10px">
		<b>Subject ID:</b> <?=$uid?><br>
		<b>Study #:</b> <?=$study_num?><br>
		<b>Series #:</b> <?=$series_num?><br>
		<b>Datatype:</b> <?=$datatype?><br>
		<b>Protocol:</b> <?=$protocol?>
		</div>
		<br><br>
		<table class="graydisplaytable" width="100%">
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
								<td><a href="getfile.php?action=download&file=<? echo "$datadir/$file"; ?>" style="color: darkblue; font-weight: bold"><?=$file?></a>
								<?
									if ($datatype == "VIDEO") {
										if ($filetype == "Flash Video") {
										?>
										<object type="application/x-shockwave-flash" width="320" height="260" wmode="transparent" data="flvplayer.swf?file=getfile.php%3Ffile%3D<? echo "$datadir/$file"; ?>&autoStart=false">
											<param name="movie" value="flvplayer.swf?file=getfile.php%3Ffile%3D<? echo "$datadir/$file"; ?>&autoStart=false" />
											<param name="wmode" value="transparent" />

										</object>
										<?
										}
										else {
										?>
										<video controls="controls">
											<source src="getfile.php?file=<? echo "$datadir/$file"; ?>">
										</video>
										<?
										}
									}
								?>
								</td>
								<form action="managefiles.php">
									<input type="hidden" name="action" value="rename">
									<input type="hidden" name="seriesid" value="<?=$seriesid?>">
									<input type="hidden" name="modality" value="<?=$modality?>">
									<input type="hidden" name="datatype" value="<?=$datatype?>">
									<input type="hidden" name="filename" value="<?=$file?>">
									<td><input type="text" size="50" maxlength="255" name="newfilename" value="<?=$file?>"></td>
									<input type="submit" style="display:none">
								</form>
								<td><?=$filetype?></td>
								<td><?=$size?></td>
								<td><?=$ctime?></td>
								<td><?=$atime?></td>
								<td><?=$mtime?></td>
								<td><a href="managefiles.php?action=delete&seriesid=<?=$seriesid?>&modality=<?=$modality?>&datatype=<?=$datatype?>&filename=<?=$file?>" style="color: white; background-color: darkred; padding: 1px 5px; font-weight: bold">X</a></td>
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
						  $folderSize += GetFolderSize($dirname.'/'.$file);

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
