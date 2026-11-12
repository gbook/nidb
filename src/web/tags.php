<?
 // ------------------------------------------------------------------------------
 // NiDB tags.php
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
		<title>NiDB - Tags</title>
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
	$tag = GetVariable("tag");
	$idtype = GetVariable("idtype");

	/* determine action */
	switch ($action) {
		case 'displaytag':
			DisplayTagList($idtype, $tag);
			break;
		default:
			DisplayTagMenu();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayTagMenu --------------------- */
	/* -------------------------------------------- */
	function DisplayTagMenu() {
		/* Series tags */
		$tags = array();
		$sqlstring = "select distinct(tag) 'tag' from tags where series_id is not null";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tags[] = $row['tag'];
		}
		?>
		<h2 class="ui header">Series</h2>
			<p>
		<?
		echo DisplayTags($tags, 'series');
		?>
		</p>
		<?
		
		/* Study tags */
		$tags = array();
		$sqlstring = "select distinct(tag) 'tag' from tags where study_id is not null";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tags[] = $row['tag'];
		}
		?>
		<h2 class="ui header">Study</h2>
			<p>
		<?
		echo DisplayTags($tags, 'study');
		?>
		</p>
		<?

		/* Subject tags */
		$tags = array();
		$sqlstring = "select distinct(tag) 'tag' from tags where subject_id is not null";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tags[] = $row['tag'];
		}
		?>
		<h2 class="ui header">Subject</h2>
			<p>
		<?
		echo DisplayTags($tags, 'subject');
		?>
		</p>
		<?

		/* Enrollment tags */
		$tags = array();
		$sqlstring = "select distinct(tag) 'tag' from tags where enrollment_id is not null";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tags[] = $row['tag'];
		}
		?>
		<h2 class="ui header">Enrollment</h2>
			<p>
		<?
		echo DisplayTags($tags, 'enrollment');
		?>
		</p>
		<?
		
		/* Analysis tags */
		$tags = array();
		$sqlstring = "select distinct(tag) 'tag' from tags where analysis_id is not null";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tags[] = $row['tag'];
		}
		?>
		<h2 class="ui header">Analysis</h2>
			<p>
		<?
		echo DisplayTags($tags, 'analysis');
		?>
		</p>
		<?

		/* Pipeline tags */
		$tags = array();
		$sqlstring = "select distinct(tag) 'tag' from tags where pipeline_id is not null";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tags[] = $row['tag'];
		}
		?>
		<h2 class="ui header">Pipeline</h2>
			<p>
		<?
		echo DisplayTags($tags, 'pipeline');
		?>
		</p>
		<?
		
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayTagList --------------------- */
	/* -------------------------------------------- */
	/* Lists everything with the given tag. Permission rules (doc/permissions.md):
	   - subjects: UID only, which anyone may see
	   - enrollments, studies, series: details need View Data on the row's project; otherwise only
	     the UID is shown and the other cells read "no view permissions"
	   - analyses and pipelines: not restricted */
	function DisplayTagList($idtype, $tag) {
		$tag = (string)$tag;
		$idtype = (string)$idtype;

		if (trim($tag) == "") {
			?><div class="ui message">Tag is blank</div><?
			return;
		}
		if (TagIDColumn($idtype) == "") {
			?><div class="ui message">Unknown ID type</div><?
			return;
		}
		?>
		<h2 class="ui header">
			<?=htmlspecialchars(ucfirst($idtype))?> tagged <span class="ui yellow label"><?=htmlspecialchars($tag)?></span>
			<div class="sub header"><a href="tags.php">All tags</a></div>
		</h2>
		<?
		switch ($idtype) {
			case 'series': DisplaySeriesTags($tag); break;
			case 'study': DisplayStudyTags($tag); break;
			case 'enrollment': DisplayEnrollmentTags($tag); break;
			case 'subject': DisplaySubjectTags($tag); break;
			case 'analysis': DisplayAnalysisTags($tag); break;
			case 'pipeline': DisplayPipelineTags($tag); break;
		}
	}


	/* -------------------------------------------- */
	/* ------- RunTagQuery ------------------------ */
	/* -------------------------------------------- */
	/* runs a query bound to the tag and returns all rows */
	function RunTagQuery($sqlstring, $tag) {
		$rows = array();
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $tag);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$tag]);
		mysqli_stmt_close($stmt);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$rows[] = $row;
		}
		return $rows;
	}


	/* -------------------------------------------- */
	/* ------- TagRowPerms ------------------------ */
	/* -------------------------------------------- */
	/* current user's permissions for every project_id in $rows */
	function TagRowPerms($rows) {
		$projectids = array();
		foreach ($rows as $row) {
			$projectids[] = (int)($row['project_id'] ?? 0);
		}
		return GetCurrentUserProjectPermissions($projectids);
	}


	/* -------------------------------------------- */
	/* ------- SubjectLink ------------------------ */
	/* -------------------------------------------- */
	function SubjectLink($row) {
		return "<a href='subjects.php?id=" . (int)($row['subject_id'] ?? 0) . "'>" . htmlspecialchars($row['uid'] ?? '') . "</a>";
	}


	/* -------------------------------------------- */
	/* ------- NoTaggedItems ---------------------- */
	/* -------------------------------------------- */
	function NoTaggedItems() {
		?><div class="ui message">Nothing has this tag</div><?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySeriesTags ------------------ */
	/* -------------------------------------------- */
	function DisplaySeriesTags($tag) {
		/* series tags store their modality; each modality has its own series table */
		$modalities = array();
		foreach (RunTagQuery("select distinct modality from tags where tag = ? and series_id is not null", $tag) as $row) {
			$modality = strtolower($row['modality'] ?? '');
			if ((GetSeriesTableName($modality) != '') && IsNiDBModality($modality))
				$modalities[] = $modality;
		}

		$rows = array();
		foreach ($modalities as $modality) {
			$table = GetSeriesTableName($modality);
			$sqlstring = "select '$modality' 'modality', a.series_num, a.series_desc, a.series_datetime, st.study_id, st.study_num, e.project_id, p.project_name, s.subject_id, s.uid from tags t join `$table` a on a.`{$modality}series_id` = t.series_id left join studies st on a.study_id = st.study_id left join enrollment e on st.enrollment_id = e.enrollment_id left join projects p on e.project_id = p.project_id left join subjects s on e.subject_id = s.subject_id where t.tag = ? and t.modality = '$modality' order by s.uid, st.study_num, a.series_num";
			$rows = array_merge($rows, RunTagQuery($sqlstring, $tag));
		}
		if (count($rows) == 0) { NoTaggedItems(); return; }

		$perms = TagRowPerms($rows);
		?>
		<table class="ui very compact celled table">
			<thead><tr><th>UID</th><th>Project</th><th>Study</th><th>Modality</th><th>Series</th><th>Description</th><th>Date</th></tr></thead>
			<? foreach ($rows as $row) {
				$view = GetPerm($perms, 'viewdata', $row['project_id'] ?? 0); ?>
			<tr>
				<td><?=SubjectLink($row)?></td>
				<? if ($view) { ?>
				<td><?=htmlspecialchars($row['project_name'] ?? '')?></td>
				<td><a href="studies.php?id=<?=(int)$row['study_id']?>"><?=htmlspecialchars(($row['uid'] ?? '') . ($row['study_num'] ?? ''))?></a></td>
				<td><?=htmlspecialchars(strtoupper($row['modality']))?></td>
				<td><?=htmlspecialchars($row['series_num'] ?? '')?></td>
				<td><?=htmlspecialchars($row['series_desc'] ?? '')?></td>
				<td><?=htmlspecialchars($row['series_datetime'] ?? '')?></td>
				<? } else { ?>
				<td colspan="6"><?=NoViewPermission()?></td>
				<? } ?>
			</tr>
			<? } ?>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayStudyTags ------------------- */
	/* -------------------------------------------- */
	function DisplayStudyTags($tag) {
		$sqlstring = "select st.study_id, st.study_num, st.study_modality, st.study_desc, st.study_datetime, e.project_id, p.project_name, s.subject_id, s.uid from tags t join studies st on st.study_id = t.study_id left join enrollment e on st.enrollment_id = e.enrollment_id left join projects p on e.project_id = p.project_id left join subjects s on e.subject_id = s.subject_id where t.tag = ? order by s.uid, st.study_num";
		$rows = RunTagQuery($sqlstring, $tag);
		if (count($rows) == 0) { NoTaggedItems(); return; }

		$perms = TagRowPerms($rows);
		?>
		<table class="ui very compact celled table">
			<thead><tr><th>UID</th><th>Project</th><th>Study</th><th>Modality</th><th>Description</th><th>Date</th></tr></thead>
			<? foreach ($rows as $row) {
				$view = GetPerm($perms, 'viewdata', $row['project_id'] ?? 0); ?>
			<tr>
				<td><?=SubjectLink($row)?></td>
				<? if ($view) { ?>
				<td><?=htmlspecialchars($row['project_name'] ?? '')?></td>
				<td><a href="studies.php?id=<?=(int)$row['study_id']?>"><?=htmlspecialchars(($row['uid'] ?? '') . ($row['study_num'] ?? ''))?></a></td>
				<td><?=htmlspecialchars($row['study_modality'] ?? '')?></td>
				<td><?=htmlspecialchars($row['study_desc'] ?? '')?></td>
				<td><?=htmlspecialchars($row['study_datetime'] ?? '')?></td>
				<? } else { ?>
				<td colspan="5"><?=NoViewPermission()?></td>
				<? } ?>
			</tr>
			<? } ?>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySubjectTags ----------------- */
	/* -------------------------------------------- */
	function DisplaySubjectTags($tag) {
		/* UID only: anyone may see that a subject exists by UID */
		$sqlstring = "select s.subject_id, s.uid from tags t join subjects s on s.subject_id = t.subject_id where t.tag = ? and s.isactive = 1 order by s.uid";
		$rows = RunTagQuery($sqlstring, $tag);
		if (count($rows) == 0) { NoTaggedItems(); return; }
		?>
		<table class="ui very compact celled collapsing table">
			<thead><tr><th>UID</th></tr></thead>
			<? foreach ($rows as $row) { ?>
			<tr><td><?=SubjectLink($row)?></td></tr>
			<? } ?>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayEnrollmentTags -------------- */
	/* -------------------------------------------- */
	function DisplayEnrollmentTags($tag) {
		$sqlstring = "select e.enrollment_id, e.project_id, p.project_name, s.subject_id, s.uid from tags t join enrollment e on e.enrollment_id = t.enrollment_id left join projects p on e.project_id = p.project_id left join subjects s on e.subject_id = s.subject_id where t.tag = ? order by s.uid, p.project_name";
		$rows = RunTagQuery($sqlstring, $tag);
		if (count($rows) == 0) { NoTaggedItems(); return; }

		$perms = TagRowPerms($rows);
		?>
		<table class="ui very compact celled collapsing table">
			<thead><tr><th>UID</th><th>Project</th><th></th></tr></thead>
			<? foreach ($rows as $row) {
				$view = GetPerm($perms, 'viewdata', $row['project_id'] ?? 0); ?>
			<tr>
				<td><?=SubjectLink($row)?></td>
				<? if ($view) { ?>
				<td><?=htmlspecialchars($row['project_name'] ?? '')?></td>
				<td><a href="enrollment.php?enrollmentid=<?=(int)$row['enrollment_id']?>">View enrollment</a></td>
				<? } else { ?>
				<td colspan="2"><?=NoViewPermission()?></td>
				<? } ?>
			</tr>
			<? } ?>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayAnalysisTags ---------------- */
	/* -------------------------------------------- */
	function DisplayAnalysisTags($tag) {
		/* analyses are not restricted (doc/permissions.md) */
		$sqlstring = "select a.analysis_id, a.analysis_status, a.study_id, pl.pipeline_id, pl.pipeline_name, st.study_num, s.subject_id, s.uid from tags t join analysis a on a.analysis_id = t.analysis_id left join pipelines pl on a.pipeline_id = pl.pipeline_id left join studies st on a.study_id = st.study_id left join enrollment e on st.enrollment_id = e.enrollment_id left join subjects s on e.subject_id = s.subject_id where t.tag = ? order by pl.pipeline_name, s.uid, st.study_num";
		$rows = RunTagQuery($sqlstring, $tag);
		if (count($rows) == 0) { NoTaggedItems(); return; }
		?>
		<table class="ui very compact celled collapsing table">
			<thead><tr><th>Pipeline</th><th>Study</th><th>Status</th><th></th></tr></thead>
			<? foreach ($rows as $row) { ?>
			<tr>
				<td><a href="pipelines.php?action=editpipeline&id=<?=(int)$row['pipeline_id']?>"><?=htmlspecialchars($row['pipeline_name'] ?? '')?></a></td>
				<td><?=htmlspecialchars(($row['uid'] ?? '') . ($row['study_num'] ?? ''))?></td>
				<td><?=htmlspecialchars($row['analysis_status'] ?? '')?></td>
				<td><a href="viewanalysis.php?action=viewresults&analysisid=<?=(int)$row['analysis_id']?>&studyid=<?=(int)$row['study_id']?>">View results</a></td>
			</tr>
			<? } ?>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayPipelineTags ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineTags($tag) {
		/* pipelines are not restricted (doc/permissions.md) */
		$sqlstring = "select pl.pipeline_id, pl.pipeline_name, pl.pipeline_desc from tags t join pipelines pl on pl.pipeline_id = t.pipeline_id where t.tag = ? order by pl.pipeline_name";
		$rows = RunTagQuery($sqlstring, $tag);
		if (count($rows) == 0) { NoTaggedItems(); return; }
		?>
		<table class="ui very compact celled collapsing table">
			<thead><tr><th>Pipeline</th><th>Description</th></tr></thead>
			<? foreach ($rows as $row) { ?>
			<tr>
				<td><a href="pipelines.php?action=editpipeline&id=<?=(int)$row['pipeline_id']?>"><?=htmlspecialchars($row['pipeline_name'] ?? '')?></a></td>
				<td><?=htmlspecialchars($row['pipeline_desc'] ?? '')?></td>
			</tr>
			<? } ?>
		</table>
		<?
	}
	
?>


<? include("footer.php") ?>
