<?
 // ------------------------------------------------------------------------------
 // NiDB adminsites.php
 // Copyright (C) 2004 - 2016
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
	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Common files</title>
	</head>

<body>
	<div id="wrapper">
<?
	//require "config.php";
	require "functions.php";
	require "includes.php";
	require "menu.php";
	
	//PrintVariable($_POST,'POST');
	//PrintVariable($_FILES,'FILES');
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$type = GetVariable('common_type');
	$group = GetVariable('common_group');
	$name = GetVariable('common_name');
	$desc = GetVariable('common_desc');
	$value = GetVariable('common_value');
						
	/* determine action */
	switch ($action) {
		case 'addobject':
			AddCommonObject($type, $group, $name, $desc, $value);
			DisplayCommonList();
			break;
		case 'deleteobject':
			DeleteCommonObject($id);
			DisplayCommonList();
			break;
		default:
			DisplayCommonList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- AddCommonObject -------------------- */
	/* -------------------------------------------- */
	function AddCommonObject($type, $group, $name, $desc, $value) {
		$group = mysqli_real_escape_string($GLOBALS['linki'], $group);
		$name = mysqli_real_escape_string($GLOBALS['linki'], $name);
		$desc = mysqli_real_escape_string($GLOBALS['linki'], $desc);
		$value = mysqli_real_escape_string($GLOBALS['linki'], $value);
		
		$size = strlen($value);
		
		if ($type == "text") { $text = $value; }
		elseif ($type == "number") { $number = $value; }
		
		if ($_FILES['file']['name'] != "") {
			$savepath = $GLOBALS['commonfilepath'] . "/$group/$name";
			mkdir($savepath,0777,true);
			/* go through all the files and save them */
			$filename = $_FILES['file']['name'];
			$size = $_FILES['file']['size'];
			$filetmpname = $_FILES['file']['tmp_name'];
			$fileerror = $_FILES['file']['error'];
			
			if (move_uploaded_file($filetmpname, "$savepath/$filename")) {
				echo "Received [" . $filetmpname ." --> $savepath/$filename] " . $size . " bytes<br>";
				chmod("$savepath/$filename", 0777);
			}
			else {
				echo "<br>An error occured moving " . $filetmpname . " to [" . $fileerror . "]<br>";
			}
		}
		
		$sqlstring = "insert into common (common_type, common_group, common_name, common_desc, common_number, common_text, common_file, common_size) values ('$type', '$group', '$name', '$desc', '$number', '$text', '$filename', $size)";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);	
	}


	/* -------------------------------------------- */
	/* ------- DeleteCommonObject ----------------- */
	/* -------------------------------------------- */
	function DeleteCommonObject($id) {
		/* get information to figure out the path */
		$sqlstring = "select * from common where common_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$type = $row['common_type'];
		$group = $row['common_group'];
		$name = $row['common_name'];
		$desc = $row['common_desc'];
		$number = $row['common_number'];
		$text = $row['common_text'];
		$file = $row['common_file'];
		$size = $row['common_size'];
		
		if ($type == "file") {
			/* reconstruct the series path and delete */
			$filepath = $GLOBALS['commonfilepath'] . "/$group/$name/$file";
			if (file_exists($filepath)) {
				echo "Deleting [$filepath]";
				unlink($filepath);
			}
		}
		
		$sqlstring = "delete from common where common_id = $id";
		//echo "[$sqlstring]";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message">Series deleted</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- DisplayCommonList ------------------ */
	/* -------------------------------------------- */
	function DisplayCommonList() {
		$urllist['Common objects'] = "common.php";
		NavigationBar("Common objects", $urllist);
		
		?>
		<SCRIPT LANGUAGE="Javascript">
		<!---
			function decision(message, url){
				if(confirm(message)) location.href = url;
			}
		// --->
		</SCRIPT>
		
		<table class="smalldisplaytable">
			<thead>
				<tr>
					<th>Group</th>
					<th>Name</th>
					<th>Description</th>
					<th>Type</th>
					<th>Value</th>
					<th>Size</th>
					<th>Upload <?=strtoupper($modality)?> file<br><span class="tiny">Click button or Drag & Drop</span></th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody>
				<form action="common.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="addobject">
				<input type="hidden" name="modality" value="<?=strtoupper($modality)?>">
				<input type="hidden" name="id" value="<?=$id?>">
				<tr>
					<td><input type="text" name="common_group" size="15"></td>
					<td><input type="text" name="common_name"></td>
					<td><input type="text" name="common_desc"></td>
					<td>
						<select name="common_type">
							<option value="file">File</option>
							<option value="text">Text</option>
							<option value="number">Number</option>
						</select>
					</td>
					<td><input type="text" name="common_value"></td>
					<td></td>
					<td>
						<input type="file" name="file">
					</td>
					<td><input type="submit" value="Create"></td>
				</tr>
				</form>
				<?
					$sqlstring = "select * from common order by common_group, common_name";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$common_id = $row['common_id'];
						$type = $row['common_type'];
						$group = $row['common_group'];
						$name = $row['common_name'];
						$desc = $row['common_desc'];
						$number = $row['common_number'];
						$text = $row['common_text'];
						$file = $row['common_file'];
						$size = $row['common_size'];
						
						switch ($type) {
							case 'number': $value = $number; break;
							case 'file': $value = $file; break;
							case 'text': $value = $text; break;
						}
						?>
						<script type="text/javascript">
							$(document).ready(function(){
								$(".edit_inline<? echo $common_id; ?>").editInPlace({
									url: "common_inlineupdate.php",
									params: "action=editinplace&id=<? echo $series_id; ?>",
									default_text: "<i style='color:#AAAAAA'>Edit here...</i>",
									bg_over: "white",
									bg_out: "lightyellow",
								});
							});
						</script>
						<tr>
							<td><?=$group?></td>
							<td><?=$name?></td>
							<td><?=$desc?></td>
							<td><?=$type?></td>
							<td><?=$value?></td>
							<td><?=HumanReadableFilesize($size)?></td>
							<td nowrap><?=$series_size?> <a href="download.php?modality=<?=$modality?>&seriesid=<?=$series_id?>" border="0"><img src="images/download16.png" title="Download <?=$modality?> data"></a></td>
							<td align="right">
								<a href="javascript:decision('Are you sure you want to delete this object?', 'common.php?action=deleteobject&id=<?=$common_id?>')" style="color: red">X</a>
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
