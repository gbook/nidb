<?
 // ------------------------------------------------------------------------------
 // NiDB tags.php
 // Copyright (C) 2004 - 2022
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
	$id = GetVariable("id");
	$tag = GetVariable("tag");
	$idtype = GetVariable("idtype");
	//$tagtype = GetVariable("tagtype");

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
	function DisplayTagList($idtype, $tag) {
		if ($tag == "") {
			?><div class="staticmessage">Tag [<?=$tag?>] blank</div><?
			return;
		}
		
		if ($idtype == "") {
			?><div class="staticmessage">ID type blank</div><?
			return;
		}
		
		$tag = mysqli_real_escape_string($GLOBALS['linki'], $tag);
		
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
	/* ------- DisplaySeriesTags ------------------ */
	/* -------------------------------------------- */
	function DisplaySeriesTags($tag, $modality) {
		$sqlstring = "select * from $modality"."_series a left join tags b on a.$modality"."series_id = b.series_id where b.tag = '$tag'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}


	/* -------------------------------------------- */
	/* ------- DisplayStudyTags ------------------- */
	/* -------------------------------------------- */
	function DisplayStudyTags($tag) {
		$sqlstring = "select * from studies a left join tags b on a.study_id = b.study_id where b.tag = '$tag'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySubjectTags ----------------- */
	/* -------------------------------------------- */
	function DisplaySubjectTags($tag) {
		$sqlstring = "select * from subjects a left join tags b on a.subject_id = b.subject_id where b.tag = '$tag'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintVariable($sqlstring);
		PrintVariable($result);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayEnrollmentTags -------------- */
	/* -------------------------------------------- */
	function DisplayEnrollmentTags($tag) {
		$sqlstring = "select * from enrollment a left join tags b on a.enrollment_id = b.enrollment_id where b.tag = '$tag'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAnalysisTags ---------------- */
	/* -------------------------------------------- */
	function DisplayAnalysisTags($tag) {
		$sqlstring = "select * from analysis a left join tags b on a.analysis_id = b.analysis_id where b.tag = '$tag'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayPipelineTags ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineTags($tag) {
		$sqlstring = "select * from pipelines a left join tags b on a.pipeline_id = b.pipeline_id where b.tag = '$tag'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}
	
?>


<? include("footer.php") ?>
