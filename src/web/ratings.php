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
	ob_start(); /* buffer output for POST/Redirect/GET (see functions.php RedirectTo/ShowFlashMessage) */
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
	$action = GetSafeAction(array("addrating", "delete")); /* mutating actions require POST + CSRF token */
	$id = (int)GetVariable("id");
	$ratingid = (int)GetVariable("ratingid");
	$type = GetVariable("type");
	$modality = strtolower(trim((string)GetVariable("modality")));
	$value = GetVariable("rating_value");
	$notes = GetVariable("rating_notes");

	/* ratings are only ever made on series ('series' is the only rating_type in use). Viewing or adding them
	   needs View Data on the series' project; the project is looked up from the series, not the request */
	if ($type != "series") $type = "series";
	$canview = ((GetSeriesTableName($modality) != '') && (IsNiDBModality($modality)) && (UserCanViewSeries($id, $modality)));

	/* mutating actions use POST/Redirect/GET so a refresh/Back doesn't re-submit */
	$thispage = "ratings.php?id=$id&type=" . urlencode($type) . "&modality=" . urlencode($modality);
	if (!$canview) {
		Error("You do not have permission to view ratings for this series");
	}
	elseif ($action == "addrating") {
		AddRating($username, $modality, $type, $id, $value, $notes);
		RedirectTo($thispage);
	}
	elseif ($action == "delete") {
		DeleteRating($ratingid);
		RedirectTo($thispage);
	}
	else {
		DisplayRatings($id, $type, $modality, $username);
	}


	/* -------------------------------------------- */
	/* ------- UserCanViewSeries ------------------ */
	/* -------------------------------------------- */
	/* true if the current user has View Data on the project the series belongs to.
	   $modality must already be validated (it is used in a table name) */
	function UserCanViewSeries($seriesid, $modality) {
		$tablename = GetSeriesTableName($modality);
		$seriesid = (int)$seriesid;
		if (($tablename == '') || ($seriesid < 1))
			return false;

		$sqlstring = "select c.project_id from $tablename a join studies b on a.study_id = b.study_id join enrollment c on b.enrollment_id = c.enrollment_id where a.$modality" . "series_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $seriesid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($seriesid));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row)
			return false;

		$projectid = (int)$row['project_id'];
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		return (GetPerm($perms, 'viewdata', $projectid) == 1);
	}


	/* -------------------------------------------- */
	/* ------- GetCurrentUserIDForRatings --------- */
	/* -------------------------------------------- */
	function GetCurrentUserIDForRatings() {
		$sqlstring = "select user_id from users where username = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $GLOBALS['username']);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($GLOBALS['username']));
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return (int)($row['user_id'] ?? 0);
	}


	/* -------------------------------------------- */
	/* ------- AddRating -------------------------- */
	/* -------------------------------------------- */
	function AddRating($username, $modality, $type, $id, $value, $notes) {
		$userid = GetCurrentUserIDForRatings();
		if ($userid < 1) { return; }

		$id = (int)$id;
		$value = (int)$value;
		$sqlstring = "insert into ratings (rater_id, data_id, data_modality, rating_type, rating_value, rating_notes, rating_date) values (?, ?, ?, ?, ?, ?, now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iissis', $userid, $id, $modality, $type, $value, $notes);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($userid, $id, $modality, $type, $value, $notes));
		mysqli_stmt_close($stmt);
	}


	/* -------------------------------------------- */
	/* ------- DeleteRating ----------------------- */
	/* -------------------------------------------- */
	/* a rating can be deleted by the user who made it, or by an admin */
	function DeleteRating($ratingid) {
		$ratingid = (int)$ratingid;
		if (IsAdminUser()) {
			$sqlstring = "delete from ratings where rating_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $ratingid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($ratingid));
		}
		else {
			$userid = GetCurrentUserIDForRatings();
			$sqlstring = "delete from ratings where rating_id = ? and rater_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $ratingid, $userid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($ratingid, $userid));
		}
		mysqli_stmt_close($stmt);
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
					<div class="sub header">Series <?=(int)$id?></div>
				</h2>
			</div>

			<!-- Add rating form -->
			<div class="ui attached segment">
				<h4 class="ui header">Add Rating</h4>
				<form class="ui form" action="ratings.php" method="post">
					<input type="hidden" name="action" value="addrating">
					<input type="hidden" name="id" value="<?=(int)$id?>">
					<input type="hidden" name="type" value="<?=htmlspecialchars($type ?? '')?>">
					<input type="hidden" name="modality" value="<?=htmlspecialchars($modality ?? '')?>">
					<?=CSRFTokenField()?>
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
					$id = (int)$id;
					$myuserid = GetCurrentUserIDForRatings();
					$isadmin = IsAdminUser();
					/* data_id is only unique within a modality, so filter on modality and type too */
					$sqlstring = "select * from ratings a left join users b on a.rater_id = b.user_id where a.data_id = ? and a.data_modality = ? and a.rating_type = ? order by a.rating_date desc";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'iss', $id, $modality, $type);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, array($id, $modality, $type));
					mysqli_stmt_close($stmt);
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
								$rating_notes = htmlspecialchars($row['rating_notes'] ?? '');
								$rating_date  = $row['rating_date'];
								$candelete    = ($isadmin || ((int)$row['rater_id'] == $myuserid));

								list($label_color, $label_text) = RatingLabel($rating_value);
								$label_class = $label_color ? "ui $label_color label" : "ui label";
								?>
								<tr>
									<td><?=htmlspecialchars($rater ?? '')?></td>
									<td><?=htmlspecialchars($rating_date ?? '')?></td>
									<td><div class="<?=$label_class?>"><?=$label_text?></div></td>
									<td><?=$rating_notes?></td>
									<td style="text-align:center">
										<? if ($candelete) { ?>
										<form method="post" action="ratings.php" style="display: inline" onsubmit="return confirm('Delete this rating?')">
											<input type="hidden" name="action" value="delete">
											<input type="hidden" name="ratingid" value="<?=(int)$rating_id?>">
											<input type="hidden" name="id" value="<?=$id?>">
											<input type="hidden" name="type" value="<?=htmlspecialchars($type ?? '')?>">
											<input type="hidden" name="modality" value="<?=htmlspecialchars($modality ?? '')?>">
											<?=CSRFTokenField()?>
											<button type="submit" class="ui mini red basic icon button" title="Delete rating"><i class="trash icon"></i></button>
										</form>
										<? } ?>
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
