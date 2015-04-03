<?
 // ------------------------------------------------------------------------------
 // NiDB ratings.php
 // Copyright (C) 2004 - 2014
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
		<title>Rating</title>
	</head>

<body>
	<div id="wrapper">
<?
	//require "config.php";
	require "functions.php";
	require "includes.php";

	//PrintVariable($_POST, "POST");
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$type = GetVariable("type");
	$modality = GetVariable("modality");
	$value = GetVariable("rating_value");
	$notes = GetVariable("rating_notes");
	
	if ($action == "addrating") {
		//echo "Username: [$username]";
		AddRating($username, $modality, $type, $id, $value, $notes);
		DisplayRatings($id, $type, $modality, $username);
	}
	else {
		DisplayRatings($id, $type, $modality, $username);
	}

	
	/* -------------------------------------------- */
	/* ------- AddRating -------------------------- */
	/* -------------------------------------------- */
	function AddRating($username, $modality, $type, $id, $value, $notes) {
		/* get user_id */
		$sqlstring = "select user_id from users where username = '$username'";
		$result = mysql_query($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$userid = $row['user_id'];

		$notes = mysql_real_escape_string($notes);
		
		$sqlstring = "insert into ratings (rater_id, data_id, data_modality, rating_type, rating_value, rating_notes, rating_date) values ($userid, $id, '$modality', '$type', $value, '$notes', now())";
		//echo "$sqlstring<br>";
		$result = mysql_query($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayRatings --------------------- */
	/* -------------------------------------------- */
	function DisplayRatings($id, $type, $modality, $username) {
	
		?>
		<table class="displaytable" style="background-color: white">
			<thead>
				<th>Rater</th>
				<th>Date</th>
				<th>Avoidance level</th>
				<th>Notes</th>
				<th></th>
			</thead>
			<tbody>
				<form action="ratings.php" method="post">
				<input type="hidden" name="action" value="addrating">
				<input type="hidden" name="id" value="<?=$id?>">
				<input type="hidden" name="type" value="<?=$type?>">
				<input type="hidden" name="modality" value="<?=$modality?>">
				<tr>
				<td><?=$username?></td>
				<td></td>
				<td>
					<select name="rating_value">
						<option value=""></option>
						<option value="0">None</option>
						<option value="1" style="background-color: palegreen">No worries, its good</option>
						<option value="2" style="background-color: skyblue">Guarded (cautious pessimism)</option>
						<option value="3" style="background-color: #FFFF44">Elevated (hide your wife)</option>
						<option value="4" style="background-color: #FFC533">High (hide your kids)</option>
						<option value="5" style="background-color: red; color:white; font-weight:bold">Severe (no hope)</option>
						<option value="6" style="background-color: #CCCCCC;">Test scan (not real data)</option>
					</select>
				</td>
				<td>
					<textarea name="rating_notes"></textarea>
				</td>
				<td>
					<input type="submit" value="Add" onClick="alert('Hi')">
				</td>
				</tr>
				</form>
			<?
			if ($type == "series") {
				$sqlstring = "select * from ratings a left join users b on a.rater_id = b.user_id where a.data_id = $id";
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$username = $row['username'];
					$rating_value = $row['rating_value'];
					$rating_notes = $row['rating_notes'];
					$rating_date = $row['rating_date'];
					
					switch ($rating_value) {
						case 1: $rating_bcolor = "palegreen"; $rating_fcolor = "black"; break;
						case 2: $rating_bcolor = "skyblue"; $rating_fcolor = "black"; break;
						case 3: $rating_bcolor = "#FFFF44"; $rating_fcolor = "black"; break;
						case 4: $rating_bcolor = "#FFC533"; $rating_fcolor = "black"; break;
						case 5: $rating_bcolor = "red"; $rating_fcolor = "white"; break;
						case 6: $rating_bcolor = "#CCCCCC"; $rating_fcolor = "black"; break;
					}
					?>
					<tr>
						<td><?=$username?></td>
						<td><?=$rating_date?></td>
						<td style="color: <?=$rating_fcolor;?>; background-color: <?=$rating_bcolor;?>"><?=$rating_value?></td>
						<td><?=$rating_notes?></td>
					</tr>
					<?
				}
			}
			?>
			</tbody>
		</table>
		<?
	}

?>