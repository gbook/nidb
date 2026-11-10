<?
 // ------------------------------------------------------------------------------
 // NiDB import.php
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
		<title><?=htmlspecialchars($_SERVER['HTTP_HOST'] ?? '')?> - NiDB Import Data</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* The deprecated web-import section (action=import and its uploaddicom, uploadnondicom, updatedemographics,
	   updateageatscan and importobservations handlers) was removed: imaging import is importimaging.php /
	   importwizard.php, and observations are importnonimaging.php. What remains is the Data landing page and
	   the ID mapper, neither of which changes anything. */

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = (int)GetVariable("projectid"); /* "all" (all projects) becomes 0 */
	$idlist = GetVariable("idlist");
	$displayonlymatches = GetVariable("displayonlymatches");
	$searchthisinstance = GetVariable("searchthisinstance");
	$ignoredeleted = GetVariable("ignoredeleted");
	
	/* determine action */
	switch($action) {
		case 'idmapper':
			DisplayIDMapperForm();
			break;
		case 'mapids':
			MapIDs($idlist, $projectid, $displayonlymatches, $searchthisinstance, $ignoredeleted);
			break;
		default:
			DisplayMenu();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {
	
		?>

			<div class="ui container" style="text-align: center">
				<div class="ui spaced buttons">
					<a href="requeststatus.php" class="ui large green button"><i class="cloud download alternate icon"></i> Data Exports & Downloads</a>
					<a href="search.php" class="ui large green button"><i class="search icon"></i> Search for Data</a>
				</div>
			</div>

			<style>
				.importtiles .ui.card.tile { width: 260px; height: 175px; margin: 0.6em; color: inherit; transition: box-shadow 0.15s ease; }
				.importtiles .ui.card.tile > .content { overflow: hidden; }
				.importtiles .ui.card.tile:hover { box-shadow: 0 0 0 3px var(--tilecolor), 0 2px 8px rgba(0,0,0,0.15); transform: none; }
				.importtiles.imports { --tilecolor: #2185d0; }
				.importtiles.exports { --tilecolor: #21ba45; }
			</style>

			<br><br>
			
			<div class="ui two column stackable grid">
			<div class="column">

			<h2 class="ui center aligned header">
				<div class="content">Imports
					<div class="sub header">Tools to bring data into NiDB</div>
				</div>
			</h2>

			<div class="ui centered cards importtiles imports">
				<a class="ui card tile" href="import.php?action=idmapper">
					<div class="content">
						<div class="header">ID Mapping</div>
						<div class="description">Subjects and studies can having multiple IDs. This tool will match a list of IDs to existing objects.</div>
					</div>
				</a>

				<a class="ui card tile" href="importimaging.php">
					<div class="content">
						<div class="header">Import Imaging</div>
						<div class="description">Upload imaging data and track existing uploads.</div>
					</div>
				</a>

				<a class="ui card tile" href="importnonimaging.php">
					<div class="content">
						<div class="header">Import non-imaging</div>
						<div class="description">Import observations and interventions.</div>
					</div>
				</a>

				<a class="ui card tile" href="importremote.php">
					<div class="content">
						<div class="header">Import remote</div>
						<div class="description">Import data from remote server.</div>
					</div>
				</a>

				<a class="ui card tile" href="importimaging.php?action=viewdcmrcvlogs">
					<div class="content">
						<div class="header">Import Logs</div>
						<div class="description">View <tt>dcmrcv</tt> DICOM receiver import logs.</div>
					</div>
				</a>

				<a class="ui card tile" href="https://github.com/gbook/nidbuploader/releases" target="_blank">
					<div class="content">
						<div class="header">Uploader <i class="small grey external alternate icon"></i></div>
						<div class="description">Download the <b>NiDB Uploader</b> from github.</div>
					</div>
				</a>
			</div>

			</div>
			<div class="column">

			<h2 class="ui center aligned header">
				<div class="content">Exports
					<div class="sub header">Tools to get data out of NiDB</div>
				</div>
			</h2>

			<div class="ui centered cards importtiles exports">
				<a class="ui card tile" href="packages.php">
					<div class="content">
						<div class="header"><img src="images/squirrel-icon-64.png" style="height: 1.2em; vertical-align: middle"> Squirrel Packages</div>
						<div class="description">Share data packages containing any data elements stored in NiDB. Exported in <b>squirrel</b> format.</div>
					</div>
				</a>

				<a class="ui card tile" href="publicdownloads.php">
					<div class="content">
						<div class="header">Public Downloads</div>
						<div class="description">Single public downloads. Available <b>temporarily</b> up to 90 days before being removed.</div>
					</div>
				</a>

				<a class="ui card tile" href="publicdatasets.php">
					<div class="content">
						<div class="header">Public Datasets</div>
						<div class="description">Groups of downloads, available <b>permanently</b>. Example: a multi-site project, where data from each site is available as a separate download.</div>
					</div>
				</a>

				<a class="ui card tile" href="datasetrequests.php">
					<div class="content">
						<div class="header">Request a Dataset</div>
						<div class="description">Your NiDB admin may allow you to submit a description of a requested dataset and destination. Check with your admin.</div>
					</div>
				</a>
			</div>

			</div>
			</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayIDMapperForm ---------------- */
	/* -------------------------------------------- */
	function DisplayIDMapperForm() {
		$instanceid = (int)($_SESSION['instanceid'] ?? 0);
		?>
		<div class="ui container">
		<form action="import.php" method="post" class="ui form">
		<input type="hidden" name="action" value="mapids">
		<table width="90%" height="90%">
			<tr>
				<td>
					<div class="ui header">
						Enter list of IDs
						<div class="sub header">Acceptable delimeters: space tab comma period semicolon colon newline</div>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<textarea cols="100" rows="25" name="idlist"></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span style="font-weight: bold; color: #444">Search only this project</span><br>
					<select name="projectid" class="ui dropdown">
						<option value="all">All Projects</option>
						<?
							/* all project names are listed regardless of permissions (by decision) */
							$sqlstring = "select project_id, project_name, project_costcenter from projects where instance_id = ? order by project_name";
							$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
							mysqli_stmt_bind_param($stmt, 'i', $instanceid);
							$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instanceid]);
							while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
								?>
								<option value="<?=(int)$row['project_id']?>"><?=htmlspecialchars($row['project_name'] ?? '')?> (<?=htmlspecialchars($row['project_costcenter'] ?? '')?>)</option>
								<?
							}
							mysqli_stmt_close($stmt);
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<div class="ui checkbox">
						<input type="checkbox" value="1" name="searchthisinstance">
						<label>Search only this instance (<?=htmlspecialchars($_SESSION['instancename'] ?? '')?>)</label>
					</div>
					<br>
					<div class="ui checkbox">
						<input type="checkbox" value="1" name="displayonlymatches">
						<label>Show only matches</label>
					</div>
					<br>
					<div class="ui checkbox">
						<input type="checkbox" value="1" name="ignoredeleted">
						<label>Do not show deleted subjects</label>
					</div>
					<br><br>
					<input type="submit" value="Map IDs" class="ui primary button">
				</td>
			</tr>
		</table>
		</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- IDMapperCandidates ----------------- */
	/* -------------------------------------------- */
	/* the stored forms an ID may take: as entered, or the SHA-1 of it (also with a leading/trailing
	   space, and upper/lower-cased), matching how alternate IDs may have been hashed on import */
	function IDMapperCandidates($altid) {
		return array($altid, sha1($altid), sha1("$altid "), sha1(" $altid"), sha1(" $altid "), sha1(strtoupper($altid)), sha1(strtolower($altid)));
	}


	/* -------------------------------------------- */
	/* ------- MapIDs ----------------------------- */
	/* -------------------------------------------- */
	/* maps foreign IDs to subjects across all projects (UIDs, alternate IDs and project names only;
	   global by decision, like the quick UID search). Read-only */
	function MapIDs($idlist, $projectid, $displayonlymatches, $searchthisinstance, $ignoredeleted) {

		$projectid = (int)$projectid;
		$instanceid = (int)($_SESSION['instanceid'] ?? 0);

		$idlist = preg_replace('~(*BSR_ANYCRLF)\R~', "\n", (string)$idlist);
		$parts = preg_split('/[\^,;\'\s\t\n\f\r]+/', $idlist);
		$ids = array();
		foreach ($parts as $part) {
			$ids[] = trim($part);
		}
		$ids = array_unique($ids);
		
		/* row cell style */
		$td = function($style, $bgcolor, $extra = "") {
			return 'style="' . $extra . $style . '; background-color: ' . $bgcolor . '; border-right: solid 1px #888"';
		};
		?>
		A <span style="color: red"> red ID</span> means the foreign ID is contained in the alternate <b>Study</b> ID, not alternate <b>Subject</b> ID<br>
		A <span style="color: green"> green ID</span> means the foreign ID was found as a local UID<br>
		<br><br>
		<table class="ui very compact celled collapsing grey table">
			<thead>
				<th style="border-right: solid 1px #888"></th>
				<th colspan="5">Local</th>
			</thead>
			<thead>
				<th style="border-right: solid 1px #888">Foreign ID</th>
				<th>Deleted?</th>
				<th>Alt Subject ID</th>
				<th>Alt Study ID</th>
				<th>UID</th>
				<th>Enrollment</th>
			</thead>
		<?
		$numFound = 0;
		$numNotFound = 0;
		foreach ($ids as $altid) {
			$newid = 1;
			if ($altid == "") continue;
			$haltid = htmlspecialchars($altid);
			$candidates = IDMapperCandidates($altid);

			/* 1) alternate subject IDs */
			$sqlstring = "select b.subject_id, b.uid, b.isactive, a.altuid, d.project_name from subject_altuid a left join subjects b on a.subject_id = b.subject_id left join enrollment c on b.subject_id = c.subject_id left join projects d on c.project_id = d.project_id where a.altuid in (?, ?, ?, ?, ?, ?, ?)";
			$types = 'sssssss';
			$params = $candidates;
			if ($searchthisinstance) { $sqlstring .= " and d.instance_id = ?"; $types .= 'i'; $params[] = $instanceid; }
			if ($projectid > 0) { $sqlstring .= " and c.project_id = ?"; $types .= 'i'; $params[] = $projectid; }
			if ($ignoredeleted) { $sqlstring .= " and b.isactive = 1"; }
			$sqlstring .= " group by c.enrollment_id";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, $types, ...$params);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
			mysqli_stmt_close($stmt);

			if ($result && (mysqli_num_rows($result) > 0)) {
				$numFound++;
				$bgcolor = (mysqli_num_rows($result) > 1) ? "#FFFFD4" : "";
				
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$subjectid = (int)$row['subject_id'];
					$deleted = ($row['isactive']) ? "" : "&#10004;";
					$style = ($newid) ? "border-top: solid 1pt #888;" : "";

					if ($subjectid == 0) {
						?>
						<tr>
							<td <?=$td($style, $bgcolor)?>><?=$haltid?></td>
							<td <?=$td($style, $bgcolor)?>><?=$deleted?></td>
							<td <?=$td($style, $bgcolor)?>><?=htmlspecialchars($row['altuid'] ?? '')?></td>
							<td <?=$td($style, $bgcolor)?>></td>
							<td colspan="2" <?=$td($style, $bgcolor)?>>Subject ID was blank</td>
						</tr>
						<?
					}
					else {
						?>
						<tr>
							<td <?=$td($style, $bgcolor)?>><?=$haltid?></td>
							<td <?=$td($style, $bgcolor)?>><?=$deleted?></td>
							<td <?=$td($style, $bgcolor)?>><?=htmlspecialchars($row['altuid'] ?? '')?></td>
							<td <?=$td($style, $bgcolor)?>></td>
							<td <?=$td($style, $bgcolor)?>><a href="subjects.php?id=<?=$subjectid?>"><?=htmlspecialchars($row['uid'] ?? '')?></a></td>
							<td <?=$td($style, $bgcolor)?>><?=htmlspecialchars($row['project_name'] ?? '')?></td>
						</tr>
						<?
					}
					$newid = 0;
				}
				continue;
			}

			/* 2) alternate study IDs */
			$sqlstring = "select a.study_id, c.subject_id, c.isactive, c.uid, a.study_alternateid, d.project_name from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id left join projects d on d.project_id = b.project_id where a.study_alternateid in (?, ?, ?, ?, ?, ?, ?)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'sssssss', ...$candidates);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $candidates);
			mysqli_stmt_close($stmt);

			if ($result && (mysqli_num_rows($result) > 0)) {
				$numFound++;
				$bgcolor = (mysqli_num_rows($result) > 1) ? "#FFFFD4" : "";
				
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$subjectid = (int)$row['subject_id'];
					$deleted = ($row['isactive']) ? "" : "&#10004;";
					$style = ($newid) ? "border-top: solid 1pt #888;" : "";
					?>
					<tr>
						<td <?=$td($style, $bgcolor)?>><?=$haltid?></td>
						<td <?=$td($style, $bgcolor)?>><?=$deleted?></td>
						<td <?=$td($style, $bgcolor)?>></td>
						<td <?=$td($style, $bgcolor, "color:red; ")?>><?=htmlspecialchars($row['study_alternateid'] ?? '')?></td>
						<td <?=$td($style, $bgcolor)?>><a href="subjects.php?id=<?=$subjectid?>"><?=htmlspecialchars($row['uid'] ?? '')?></a></td>
						<td <?=$td($style, $bgcolor)?>><?=htmlspecialchars($row['project_name'] ?? '')?></td>
					</tr>
					<?
					$newid = 0;
				}
				continue;
			}

			/* 3) the ID may be a local UID */
			$sqlstring = "select subject_id, uid, isactive from subjects where uid = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $altid);
			$resultA = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$altid]);
			$rowA = ($resultA) ? mysqli_fetch_array($resultA, MYSQLI_ASSOC) : null;
			mysqli_stmt_close($stmt);

			if ($rowA) {
				$numFound++;
				$subjectid = (int)$rowA['subject_id'];
				$deleted = ($rowA['isactive']) ? "" : "&#10004;";
				
				$sqlstring = "select count(*) 'count' from enrollment where subject_id = ?";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'i', $subjectid);
				$resultA = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid]);
				$enrollcount = (int)(mysqli_fetch_array($resultA, MYSQLI_ASSOC)['count'] ?? 0);
				mysqli_stmt_close($stmt);
				?>
				<tr>
					<td style="border-top: solid 1pt #888; border-right: solid 1px #888"><?=$haltid?></td>
					<td style="border-top: solid 1pt #888; border-right: solid 1px #888"><?=$deleted?></td>
					<td style="color:green; border-top: solid 1pt #888; border-right: solid 1px #888"> </td>
					<td style="color:green; border-top: solid 1pt #888; border-right: solid 1px #888"> </td>
					<td style="border-top: solid 1pt #888; border-right: solid 1px #888"><a href="subjects.php?id=<?=$subjectid?>"><?=htmlspecialchars($rowA['uid'] ?? '')?></a></td>
					<td style="border-top: solid 1pt #888; border-right: solid 1px #888"><?=$enrollcount?></td>
				</tr>
				<?
			}
			else {
				$numNotFound++;
				if (!$displayonlymatches) {
					?>
					<tr>
						<td style="border-top: solid 1pt #888; border-right: solid 1px #888"><?=$haltid?></td>
						<td style="border-top: solid 1pt #888; border-right: solid 1px #888" colspan="5" align="center"> </td>
					</tr>
					<?
				}
			}
		}
		?>
		</table>
		<br>
		Found <?=$numFound?> of <?=$numNotFound+$numFound?>
		<?
	}
?>


<? include("footer.php") ?>
