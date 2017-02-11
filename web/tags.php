<?
 // ------------------------------------------------------------------------------
 // NiDB tags.php
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
		<title>NiDB - Tags</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$tag = GetVariable("tag");
	$idtype = GetVariable("idtype");
	$tagtype = GetVariable("tagtype");

	/* determine action */
	switch ($action) {
		case 'displaytag':
			DisplayTagList($idtype, $tagtype, $tag);
			break;
		default:
			DisplayTagMenu();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayTagMenu --------------------- */
	/* -------------------------------------------- */
	function DisplayTagMenu() {
		$sqlstring = "select distinct(tag) 'tag' from tags";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQLTable($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tags[] = $row['tag'];
		}
		//PrintVariable($tags);
		echo DisplayTags($tags,'dx','enrollment');
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayTagList --------------------- */
	/* -------------------------------------------- */
	function DisplayTagList($tagtype, $idtype, $tag) {
		if ($tag == "") {
			?><div class="staticmessage">Tag [<?=$tag?>] blank</div><?
			return;
		}
		
		if ($idtype == "") {
			?><div class="staticmessage">Tag type blank</div><?
			return;
		}
		
		$tag = mysqli_real_escape_string($GLOBALS['linki'], $tag);
		
		$urllist['Tags'] = "tags.php";
		NavigationBar("$idtype tags matching '$tag'", $urllist);

		switch ($idtype) {
			case 'series': DisplaySeriesTags($tagtype, $tag); break;
			case 'study': DisplayStudyTags($tagtype, $tag); break;
			case 'enrollment': DisplayEnrollmentTags($tagtype, $tag); break;
			case 'subject': DisplaySubjectTags($tagtype, $tag); break;
			case 'analysis': DisplayAnalysisTags($tagtype, $tag); break;
			case 'pipeline': DisplayPipelineTags($tagtype, $tag); break;
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySeriesTags ------------------ */
	/* -------------------------------------------- */
	function DisplaySeriesTags($tagtype, $tag, $modality) {
		$sqlstring = "select * from $modality"."_series a left join tags b on a.$modality"."series_id = b.series_id where b.tag = '$tag' and b.tagtype = '$tagtype'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}


	/* -------------------------------------------- */
	/* ------- DisplayStudyTags ------------------- */
	/* -------------------------------------------- */
	function DisplayStudyTags($tagtype, $tag) {
		$sqlstring = "select * from studies a left join tags b on a.study_id = b.study_id where b.tag = '$tag' and b.tagtype = '$tagtype' and b.tagtype = '$tagtype'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySubjectTags ----------------- */
	/* -------------------------------------------- */
	function DisplaySubjectTags($tagtype, $tag) {
		$sqlstring = "select * from subjects a left join tags b on a.subject_id = b.subject_id where b.tag = '$tag' and b.tagtype = '$tagtype'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayEnrollmentTags -------------- */
	/* -------------------------------------------- */
	function DisplayEnrollmentTags($tagtype, $tag) {
		$sqlstring = "select * from enrollment a left join tags b on a.enrollment_id = b.enrollment_id where b.tag = '$tag' and b.tagtype = '$tagtype'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAnalysisTags ---------------- */
	/* -------------------------------------------- */
	function DisplayAnalysisTags($tagtype, $tag) {
		$sqlstring = "select * from analysis a left join tags b on a.analysis_id = b.analysis_id where b.tag = '$tag' and b.tagtype = '$tagtype'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}
	

	/* -------------------------------------------- */
	/* ------- DisplayPipelineTags ---------------- */
	/* -------------------------------------------- */
	function DisplayPipelineTags($tagtype, $tag) {
		$sqlstring = "select * from pipelines a left join tags b on a.pipeline_id = b.pipeline_id where b.tag = '$tag' and b.tagtype = '$tagtype'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		PrintSQLTable($result);
	}
	
?>


<? include("footer.php") ?>
