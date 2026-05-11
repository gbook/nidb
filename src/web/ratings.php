<?
 // ------------------------------------------------------------------------------
 // NiDB ratings.php
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
		<title>NiDB - Ratings</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$ratingid = GetVariable("ratingid");
	$type = GetVariable("type");
	$modality = GetVariable("modality");
	$value = GetVariable("rating_value");
	$notes = GetVariable("rating_notes");

	if ($action == "addrating") {
		AddRating($username, $modality, $type, $id, $value, $notes);
		DisplayRatings($id, $type, $modality, $username);
	}
	elseif ($action == "delete") {
		DeleteRating($ratingid);
		DisplayRatings($id, $type, $modality, $username);
	}
	else {
		DisplayRatings($id, $type, $modality, $username);
	}


	/* -------------------------------------------- */
	/* ------- AddRating -------------------------- */
	/* -------------------------------------------- */
	function AddRating($username, $modality, $type, $id, $value, $notes) {
		$sqlstring = "select user_id from users where username = '$username'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];

		$notes = mysqli_real_escape_string($GLOBALS['linki'], $notes);

		$sqlstring = "insert into ratings (rater_id, data_id, data_modality, rating_type, rating_value, rating_notes, rating_date) values ($userid, $id, '$modality', '$type', $value, '$notes', now())";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DeleteRating ----------------------- */
	/* -------------------------------------------- */
	function DeleteRating($ratingid) {
		$sqlstring = "delete from ratings where rating_id = '$ratingid'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- RatingLabel ----------------------- */
	/* -------------------------------------------- */
	function RatingLabel($value) {
		switch ((int)$value) {
			case 1: return array("green",  "Good data");
			case 2: return array("blue",   "Minor problems");
			case 3: return array("yellow", "Moderate problems");
			case 4: return array("orange", "Major problems");
			case 5: return array("red",    "Severe problems, unusable");
			case 6: return array("grey",   "Test scan");
			default: return array("",      "No rating");
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayRatings --------------------- */
	/* -------------------------------------------- */
	function DisplayRatings($id, $type, $modality, $username) {
		?>
		<div class="ui container">

			<div class="ui top attached secondary inverted segment">
				<h2 class="ui header">
					Ratings
					<div class="sub header">Series <?=htmlspecialchars($id)?></div>
				</h2>
			</div>

			<!-- Add rating form -->
			<div class="ui attached segment">
				<h4 class="ui header">Add Rating</h4>
				<form class="ui form" action="ratings.php" method="post">
					<input type="hidden" name="action" value="addrating">
					<input type="hidden" name="id" value="<?=htmlspecialchars($id)?>">
					<input type="hidden" name="type" value="<?=htmlspecialchars($type)?>">
					<input type="hidden" name="modality" value="<?=htmlspecialchars($modality)?>">
					<div class="fields">
						<div class="six wide field">
							<label>Rating</label>
							<select name="rating_value" required class="ui dropdown">
								<option value="">(Select a rating)</option>
								<option value="0">No rating</option>
								<option value="1">Good data</option>
								<option value="2">Minor problems</option>
								<option value="3">Moderate problems</option>
								<option value="4">Major problems</option>
								<option value="5">Severe problems, unusable</option>
								<option value="6">Test scan (not real data)</option>
							</select>
						</div>
						<div class="eight wide field">
							<label>Notes</label>
							<textarea name="rating_notes" rows="2" style="resize:vertical"></textarea>
						</div>
						<div class="two wide field">
							<label>&nbsp;</label>
							<button class="ui fluid primary button" type="submit">
								<i class="plus icon"></i> Add
							</button>
						</div>
					</div>
				</form>
			</div>

			<!-- Existing ratings table -->
			<div class="ui bottom attached segment">
				<? if ($type == "series") {
					$sqlstring = "select * from ratings a left join users b on a.rater_id = b.user_id where a.data_id = $id order by a.rating_date desc";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					if (mysqli_num_rows($result) < 1) { ?>
						<div class="ui message">No ratings have been added yet.</div>
					<? } else { ?>
						<table class="ui small celled selectable very compact table">
							<thead>
								<tr>
									<th>Rater</th>
									<th>Date</th>
									<th>Rating</th>
									<th>Notes</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
							<?
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$rater        = $row['username'];
								$rating_id    = $row['rating_id'];
								$rating_value = $row['rating_value'];
								$rating_notes = htmlspecialchars($row['rating_notes']);
								$rating_date  = $row['rating_date'];

								list($label_color, $label_text) = RatingLabel($rating_value);
								$label_class = $label_color ? "ui $label_color label" : "ui label";
								?>
								<tr>
									<td><?=htmlspecialchars($rater)?></td>
									<td><?=htmlspecialchars($rating_date)?></td>
									<td><div class="<?=$label_class?>"><?=$label_text?></div></td>
									<td><?=$rating_notes?></td>
									<td style="text-align:center">
										<a class="ui mini red basic icon button" href="ratings.php?action=delete&ratingid=<?=$rating_id?>&id=<?=$id?>&type=<?=$type?>&modality=<?=$modality?>" title="Delete rating">
											<i class="trash icon"></i>
										</a>
									</td>
								</tr>
								<?
							}
							?>
							</tbody>
						</table>
					<? } ?>
				<? } ?>
			</div>

		</div>
		<?
	}

?>
