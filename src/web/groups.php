<?
// ------------------------------------------------------------------------------
// NiDB groups.php
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
	ob_start(); /* buffer output so POST/Redirect/GET (a header('Location') redirect) works despite the HTML rendered below */
?>

<html>
<head>
	<link rel="icon" type="image/png" href="images/squirrel.png">
	<title>NiDB - Manage Groups</title>
</head>

<body>
<div id="wrapper">
	<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	//PrintVariable($_POST);

	/* ----- setup variables ----- */
	$action = GetSafeAction(array("add", "delete", "addsubjectstogroup", "addstudiestogroup", "removegroupitem", "renamegroup", "updatestudygroup", "updatesubjectgroup")); /* mutating actions require POST + CSRF token */
	$id = (int)GetVariable("id");
	$groupid = (int)GetVariable("groupid");
	$subjectgroupid = (int)GetVariable("subjectgroupid");
	$studygroupid = (int)GetVariable("studygroupid");
	$groupname = GetVariable("groupname");
	$grouptype = GetVariable("grouptype");
	$owner = GetVariable("owner");
	$uids = GetVariable("uids");
	$uidids = GetVariable("uidids");
	$seriesids = GetVariable("seriesid");
	$studyids = GetVariable("studyid");
	$modality = GetVariable("modality");
	$itemid = GetVariable("itemid"); /* array of ids (itemid[] from the member grid); sanitized in RemoveGroupItem */
	$studylist = GetVariable("studylist");
	$uidlist = GetVariable("uidlist");
	$displaytype = GetVariable("displaytype");

	/* determine action */
	switch ($action) {
		/* mutating actions use POST/Redirect/GET: run the handler, stash its message,
		   then redirect to a GET so a refresh/Back doesn't re-submit */
		case 'add':
			ob_start();
			AddGroup($groupname, $grouptype, $GLOBALS['username']);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("groups.php?action=viewgrouplist&displaytype=" . urlencode($displaytype ?? ''));
			break;
		case 'delete':
			ob_start();
			DeleteGroup($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("groups.php");
			break;
		case 'addsubjectstogroup':
			ob_start();
			AddSubjectsToGroup($subjectgroupid, $uids, $seriesids, $modality);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("groups.php?action=viewgroup&id=$subjectgroupid");
			break;
		case 'addstudiestogroup':
			ob_start();
			AddStudiesToGroup($studygroupid, $seriesids, $studyids, $modality);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("groups.php?action=viewgroup&id=$studygroupid");
			break;
		case 'removegroupitem':
			ob_start();
			RemoveGroupItem($id, $itemid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("groups.php?action=viewgroup&id=$id");
			break;
		case 'renamegroup':
			ob_start();
			RenameGroup($id, $groupname);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("groups.php?action=viewgroup&id=$id");
			break;
		case 'updatestudygroup':
			ob_start();
			UpdateStudyGroup($id, $studylist);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("groups.php?action=viewgroup&id=$id");
			break;
		case 'updatesubjectgroup':
			ob_start();
			UpdateSubjectGroup($id, $uidlist);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("groups.php?action=viewgroup&id=$id");
			break;
		case 'viewgroup':
			ShowFlashMessage();
			ViewGroup($id);
			break;
		case 'viewgrouplist':
		default:
			ShowFlashMessage();
			DisplayGroupList($displaytype);
			break;
	}


	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetGroup --------------------------- */
	/* -------------------------------------------- */
	/* returns the groups row for $id, or null if there is no such group */
	function GetGroup($id) {
		$id = (int)$id;
		$sqlstring = "select * from groups where group_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return $row ? $row : null;
	}


	/* -------------------------------------------- */
	/* ------- CanEditGroup ----------------------- */
	/* -------------------------------------------- */
	/* true if the current user may delete, rename, or remove members from the group ($group is a groups row):
	   its owner, or an admin. Adding members is open to everyone, since search.php, analysis.php, and
	   studies.php list every group */
	function CanEditGroup($group) {
		if (!$group) { return false; }
		return (((int)($group['group_owner'] ?? 0) == (int)($GLOBALS['userid'] ?? 0)) || IsAdminUser());
	}


	/* -------------------------------------------- */
	/* ------- CheckGroupType --------------------- */
	/* -------------------------------------------- */
	/* true if group $id exists and is of type $type; displays an error otherwise */
	function CheckGroupType($id, $type) {
		$group = GetGroup($id);
		if (!$group) { Error("Group not found"); return false; }
		if ($group['group_type'] != $type) { Error("This is not a $type group"); return false; }
		return true;
	}


	/* -------------------------------------------- */
	/* ------- AddGroup --------------------------- */
	/* -------------------------------------------- */
	function AddGroup($groupname, $grouptype, $owner) {
		/* perform data checks */
		$groupname = trim($groupname ?? '');
		$hgroupname = htmlspecialchars($groupname);
		if ($groupname == "") { Error("Group name cannot be blank"); return; }
		if (mb_strlen($groupname) > 255) { Error("Group name must be 255 characters or less"); return; }
		if (!in_array($grouptype, array('nda', 'study', 'subject'), true)) { Error("Invalid group type"); return; }

		/* get userid */
		$stmt = mysqli_prepare($GLOBALS['linki'], "select * from users where username = ?");
		mysqli_stmt_bind_param($stmt, 's', $owner);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) { Error("User not found"); return; }
		$userid = (int)$row['user_id'];

		/* insert the new group. insert ignore skips a duplicate name for this owner (unique key group_name, group_owner) */
		$stmt = mysqli_prepare($GLOBALS['linki'], "insert ignore into groups (group_name, group_type, group_owner) values (?, ?, ?)");
		mysqli_stmt_bind_param($stmt, 'ssi', $groupname, $grouptype, $userid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$numinserted = mysqli_affected_rows($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		if ($numinserted > 0) { Notice("<b>$hgroupname</b> added"); } else { Error("A group named <b>$hgroupname</b> already exists for this owner"); }
	}

	/* -------------------------------------------- */
	/* ------- AddSubjectsToGroup ----------------- */
	/* -------------------------------------------- */
	function AddSubjectsToGroup($groupid, $uids, $seriesids, $modality) {
		if (!CheckGroupType($groupid, 'subject')) { return; }

		$numadded = 0;
		$numexisting = 0;
		$numnotfound = 0;
		/* subject rows are stored with modality ''. The duplicate check ignores modality, since older rows may have NULL */
		/* if the request came from the subjects.php page */
		if (is_array($uids) && !empty($uids)) {
			foreach ($uids as $uid) {
				$stmt = mysqli_prepare($GLOBALS['linki'], "select subject_id from subjects where uid = ?");
				mysqli_stmt_bind_param($stmt, 's', $uid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$uidid = (int)($row['subject_id'] ?? 0);
				mysqli_stmt_close($stmt);
				if ($uidid <= 0) { $numnotfound++; continue; }

				/* check if its already in the group */
				$stmt = mysqli_prepare($GLOBALS['linki'], "select * from group_data where group_id = ? and data_id = ?");
				mysqli_stmt_bind_param($stmt, 'ii', $groupid, $uidid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numexisting++;
				}
				else {
					/* insert the uidids */
					$stmtInsert = mysqli_prepare($GLOBALS['linki'], "insert into group_data (group_id, data_id, modality, date_added) values (?, ?, '', now())");
					mysqli_stmt_bind_param($stmtInsert, 'ii', $groupid, $uidid);
					MySQLiBoundQuery($stmtInsert, __FILE__, __LINE__);
					mysqli_stmt_close($stmtInsert);
					$numadded++;
				}
				mysqli_stmt_close($stmt);
			}
		}
		/* if the request came from the search.php page */
		if (is_array($seriesids) && !empty($seriesids)) {
			$seriesTable = GetSeriesTableName($modality);
			if ($seriesTable == '') {
				Notice("Invalid modality");
				return;
			}
			$seriesIdColumn = strtolower($modality) . "series_id";
			foreach ($seriesids as $seriesid) {
				/* get the study id for this seriesid/modality */
				$stmt = mysqli_prepare($GLOBALS['linki'], "select c.subject_id from $seriesTable a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where a.$seriesIdColumn = ?");
				mysqli_stmt_bind_param($stmt, 'i', $seriesid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$uidid = (int)($row['subject_id'] ?? 0);
				mysqli_stmt_close($stmt);
				if ($uidid <= 0) { $numnotfound++; continue; }

				/* check if its already in the group */
				$stmt = mysqli_prepare($GLOBALS['linki'], "select * from group_data where group_id = ? and data_id = ?");
				mysqli_stmt_bind_param($stmt, 'ii', $groupid, $uidid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numexisting++;
				}
				else {
					/* insert the uidids */
					$stmtInsert = mysqli_prepare($GLOBALS['linki'], "insert into group_data (group_id, data_id, modality, date_added) values (?, ?, '', now())");
					mysqli_stmt_bind_param($stmtInsert, 'ii', $groupid, $uidid);
					MySQLiBoundQuery($stmtInsert, __FILE__, __LINE__);
					mysqli_stmt_close($stmtInsert);
					$numadded++;
				}
				mysqli_stmt_close($stmt);
			}
		}
		$msg = "<b>$numadded</b> subjects added<br><b>$numexisting</b> subjects already in group";
		if ($numnotfound > 0) { $msg .= "<br><b>$numnotfound</b> subjects not found"; }
		Notice($msg);
	}

	
	/* -------------------------------------------- */
	/* ------- AddStudiesToGroup ------------------ */
	/* -------------------------------------------- */
	function AddStudiesToGroup($groupid, $seriesids, $studyids, $modality) {
		if (!CheckGroupType($groupid, 'study')) { return; }
		$modality = strtolower($modality);
		$seriesTable = GetSeriesTableName($modality);
		$seriesIdColumn = $modality . "series_id";

		$numadded = 0;
		$numexisting = 0;
		$numnotfound = 0;
		if (is_array($seriesids)) {
			if ($seriesTable == '') {
				Notice("Invalid modality");
				return;
			}
			foreach ($seriesids as $seriesid) {
				/* get the study id for this seriesid/modality */
				$stmt = mysqli_prepare($GLOBALS['linki'], "select study_id from $seriesTable where $seriesIdColumn = ?");
				mysqli_stmt_bind_param($stmt, 'i', $seriesid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				$studyid = (int)($row['study_id'] ?? 0);
				mysqli_stmt_close($stmt);
				if ($studyid <= 0) { $numnotfound++; continue; }

				/* check if its already in the group. Not by modality: the same study can arrive with a different modality string from different pages */
				$stmt = mysqli_prepare($GLOBALS['linki'], "select * from group_data where group_id = ? and data_id = ?");
				mysqli_stmt_bind_param($stmt, 'ii', $groupid, $studyid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numexisting++;
				}
				else {
					/* insert the seriesids */
					$stmtInsert = mysqli_prepare($GLOBALS['linki'], "insert into group_data (group_id, data_id, modality, date_added) values (?, ?, ?, now())");
					mysqli_stmt_bind_param($stmtInsert, 'iis', $groupid, $studyid, $modality);
					MySQLiBoundQuery($stmtInsert, __FILE__, __LINE__);
					mysqli_stmt_close($stmtInsert);
					$numadded++;
				}
				mysqli_stmt_close($stmt);
			}
		}

		if (is_array($studyids)) {
			foreach ($studyids as $studyid) {
				/* get the modality for this study */
				$stmt = mysqli_prepare($GLOBALS['linki'], "select study_modality from studies where study_id = ?");
				mysqli_stmt_bind_param($stmt, 'i', $studyid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				mysqli_stmt_close($stmt);
				if (!$row) { $numnotfound++; continue; }
				$studyModality = strtolower($row['study_modality'] ?? '');

				/* check if its already in the group (any modality) */
				$stmt = mysqli_prepare($GLOBALS['linki'], "select * from group_data where group_id = ? and data_id = ?");
				mysqli_stmt_bind_param($stmt, 'ii', $groupid, $studyid);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				if (mysqli_num_rows($result) > 0) {
					$numexisting++;
				}
				else {
					/* insert the studyids */
					$stmtInsert = mysqli_prepare($GLOBALS['linki'], "insert into group_data (group_id, data_id, modality) values (?, ?, ?)");
					mysqli_stmt_bind_param($stmtInsert, 'iis', $groupid, $studyid, $studyModality);
					MySQLiBoundQuery($stmtInsert, __FILE__, __LINE__);
					mysqli_stmt_close($stmtInsert);
					$numadded++;
				}
				mysqli_stmt_close($stmt);
			}
		}
		$msg = "<b>$numadded</b> studies added<br><b>$numexisting</b> studies already in group";
		if ($numnotfound > 0) { $msg .= "<br><b>$numnotfound</b> studies not found"; }
		Notice($msg);
	}


	/* -------------------------------------------- */
	/* ------- UpdateStudyGroup ------------------- */
	/* -------------------------------------------- */
	function UpdateStudyGroup($id, $studylist) {

		$id = (int)$id;
		if ($id <= 0) {
			Error("ID blank");
			return;
		}

		/* only study groups are edited by study list (the delete below would empty any group type) */
		$group = GetGroup($id);
		if (!$group) { Error("Group not found"); return; }
		if (!CanEditGroup($group)) { Error("Only the group's owner or an admin can edit its members"); return; }
		if ($group['group_type'] != 'study') { Error("Only study groups can be updated from a study list"); return; }

		$studies = preg_split('/[\s,]+/', $studylist ?? '', -1, PREG_SPLIT_NO_EMPTY); /* one per line; commas also accepted */
		$studies = array_unique($studies);

		/* look up all the studies first, so a failure part way through can't leave the group half emptied.
		   group_data is an Aria table, so the transaction below doesn't make the delete + inserts atomic */
		$found = array();
		$notfound = array();
		foreach ($studies as $study) {
			if (trim($study) == "") { continue; }

			$uid = substr($study,0,8);
			$studynum = substr($study,8);

			$stmt = mysqli_prepare($GLOBALS['linki'], "select b.study_id, b.study_modality from studies b left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where d.uid = ? AND b.study_num = ?");
			mysqli_stmt_bind_param($stmt, 'ss', $uid, $studynum);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if ($row) {
				/* stored with the study's modality, same as AddStudiesToGroup, so membership checks match */
				$found[(int)$row['study_id']] = strtolower($row['study_modality'] ?? '');
			}
			else {
				$notfound[] = htmlspecialchars($study);
			}
		}

		/* start transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		/* delete all old group entries */
		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from group_data where group_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		/* insert the studyids */
		$numadded = 0;
		foreach ($found as $studyid => $studymodality) {
			$stmt = mysqli_prepare($GLOBALS['linki'], "insert into group_data (group_id, data_id, modality, date_added) values (?, ?, ?, now())");
			mysqli_stmt_bind_param($stmt, 'iis', $id, $studyid, $studymodality);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			$numadded++;
		}

		/* commit the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		Notice("Group updated: <b>$numadded</b> studies in group");
		if (count($notfound) > 0) {
			Warning("Studies not found (possibly invalid study number?): <tt>" . implode(", ", $notfound) . "</tt>");
		}

	}


	/* -------------------------------------------- */
	/* ------- UpdateSubjectGroup ----------------- */
	/* -------------------------------------------- */
	/* replace a subject group's members with the subjects in $uidlist (UIDs, one per line) */
	function UpdateSubjectGroup($id, $uidlist) {

		$id = (int)$id;
		if ($id <= 0) {
			Error("ID blank");
			return;
		}

		/* only subject groups are edited by UID list (the delete below would empty any group type) */
		$group = GetGroup($id);
		if (!$group) { Error("Group not found"); return; }
		if (!CanEditGroup($group)) { Error("Only the group's owner or an admin can edit its members"); return; }
		if ($group['group_type'] != 'subject') { Error("Only subject groups can be updated from a UID list"); return; }

		$uids = preg_split('/[\s,]+/', $uidlist ?? '', -1, PREG_SPLIT_NO_EMPTY); /* one per line; commas also accepted */
		$uids = array_unique($uids);

		/* look up all the subjects first, so a failure part way through can't leave the group half emptied.
		   group_data is an Aria table, so the transaction below doesn't make the delete + inserts atomic */
		$found = array();
		$notfound = array();
		foreach ($uids as $uid) {
			$stmt = mysqli_prepare($GLOBALS['linki'], "select subject_id from subjects where uid = ?");
			mysqli_stmt_bind_param($stmt, 's', $uid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if ($row) {
				$found[(int)$row['subject_id']] = 1;
			}
			else {
				$notfound[] = htmlspecialchars($uid);
			}
		}

		/* start transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		/* delete all old group entries */
		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from group_data where group_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);

		/* insert the subjectids. Stored with modality '', same as AddSubjectsToGroup */
		$numadded = 0;
		foreach ($found as $subjectid => $unused) {
			$stmt = mysqli_prepare($GLOBALS['linki'], "insert into group_data (group_id, data_id, modality, date_added) values (?, ?, '', now())");
			mysqli_stmt_bind_param($stmt, 'ii', $id, $subjectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			mysqli_stmt_close($stmt);
			$numadded++;
		}

		/* commit the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		Notice("Group updated: <b>$numadded</b> subjects in group");
		if (count($notfound) > 0) {
			Warning("Subjects not found (possibly invalid UID?): <tt>" . implode(", ", $notfound) . "</tt>");
		}
	}


	/* -------------------------------------------- */
	/* ------- RemoveGroupItem -------------------- */
	/* -------------------------------------------- */
	function RemoveGroupItem($id, $itemid) {
		//PrintVariable($itemid,'ItemID');
		$id = (int)$id;
		$group = GetGroup($id);
		if (!$group) { Error("Group not found"); return; }
		if (!CanEditGroup($group)) { Error("Only the group's owner or an admin can remove members"); return; }

		$numremoved = 0;
		$items = is_array($itemid) ? $itemid : array($itemid);
		foreach ($items as $item) {
			$item = (int)$item;
			if ($item <= 0) { continue; }
			/* only items that belong to this group */
			$stmt = mysqli_prepare($GLOBALS['linki'], "delete from group_data where subjectgroup_id = ? and group_id = ?");
			mysqli_stmt_bind_param($stmt, 'ii', $item, $id);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$numremoved += mysqli_affected_rows($GLOBALS['linki']);
			mysqli_stmt_close($stmt);
		}
		Notice("<b>$numremoved</b> item(s) removed from group");
		return;
	}


	/* -------------------------------------------- */
	/* ------- RemoveGroupDuplicates -------------- */
	/* -------------------------------------------- */
	/* removes repeated members (same data_id) from a subject or study group, keeping the earliest row.
	   Not for series groups, where the same data_id can be different series in different modalities.
	   Returns the number of rows removed */
	function RemoveGroupDuplicates($id) {
		$id = (int)$id;
		$sqlstring = "delete a from group_data a join group_data b on a.group_id = b.group_id and a.data_id = b.data_id and a.subjectgroup_id > b.subjectgroup_id where a.group_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$numremoved = mysqli_affected_rows($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		return max(0, $numremoved);
	}


	/* -------------------------------------------- */
	/* ------- DeleteGroup ------------------------ */
	/* -------------------------------------------- */
	function DeleteGroup($id) {
		$id = (int)$id;
		$group = GetGroup($id);
		if (!$group) { Error("Group not found"); return; }
		if (!CanEditGroup($group)) { Error("Only the group's owner or an admin can delete it"); return; }

		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from groups where group_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$numdeleted = mysqli_affected_rows($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		if ($numdeleted <= 0) { Error("Group not found"); return; }

		/* remove the group's members too, so they aren't left orphaned */
		$stmt = mysqli_prepare($GLOBALS['linki'], "delete from group_data where group_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		mysqli_stmt_close($stmt);
		Notice("Group deleted");
	}


	/* -------------------------------------------- */
	/* ------- DeleteGroupButton ------------------ */
	/* -------------------------------------------- */
	/* "Delete Group" button shown on the group views, to those who can edit the group ($group is a groups row) */
	function DeleteGroupButton($id, $group) {
		if (!CanEditGroup($group)) { return; }
		?>
		<form action="groups.php" method="post" style="display: inline" onsubmit="return confirm('Delete this group?')">
			<?=CSRFTokenField()?>
			<input type="hidden" name="action" value="delete">
			<input type="hidden" name="id" value="<?=(int)$id?>">
			<button type="submit" class="ui tiny red button">Delete Group</button>
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- RenameGroup ------------------------ */
	/* -------------------------------------------- */
	function RenameGroup($id, $newname) {
		$id = (int)$id;
		$newname = trim($newname ?? '');
		$hnewname = htmlspecialchars($newname);

		if ($newname == "") { Error("Group name cannot be blank"); return; }
		if (mb_strlen($newname) > 255) { Error("Group name must be 255 characters or less"); return; }

		/* get the current group */
		$sqlstring = "select group_name, group_owner from groups where group_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) { Error("Group not found"); return; }
		if (!CanEditGroup($row)) { Error("Only the group's owner or an admin can rename it"); return; }
		$oldname = $row['group_name'];
		$owner = $row['group_owner'];

		if ($newname === $oldname) { return; }

		/* group names are unique per owner (unique key group_name, group_owner) */
		$sqlstring = "select group_id from groups where group_name = ? and group_owner <=> ? and group_id <> ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sii', $newname, $owner, $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newname, $owner, $id]);
		$duplicate = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		if ($duplicate) { Error("A group named <b>$hnewname</b> already exists for this owner"); return; }

		$sqlstring = "update groups set group_name = ? where group_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'si', $newname, $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newname, $id]);
		mysqli_stmt_close($stmt);

		Notice("Renamed group <b>" . htmlspecialchars($oldname) . "</b> to <b>$hnewname</b>");
	}


	/* -------------------------------------------- */
	/* ------- DisplayRenameGroupModal ------------ */
	/* -------------------------------------------- */
	/* modal opened by the edit icon next to the group name (see RenameGroupIcon) */
	function DisplayRenameGroupModal($id, $groupname) {
		?>
		<div class="ui small modal" id="renameGroupModal">
			<div class="header">Rename group</div>
			<div class="content">
				<form action="groups.php" method="post" id="renameGroupForm" class="ui form">
					<?=CSRFTokenField()?>
					<input type="hidden" name="action" value="renamegroup">
					<input type="hidden" name="id" value="<?=(int)$id?>">
					<div class="field">
						<label>Group name</label>
						<input type="text" name="groupname" value="<?=htmlspecialchars($groupname ?? '')?>" maxlength="255" required>
					</div>
				</form>
			</div>
			<div class="actions">
				<div class="ui cancel button">Cancel</div>
				<button type="submit" form="renameGroupForm" class="ui primary button">Rename</button>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- RenameGroupIcon -------------------- */
	/* -------------------------------------------- */
	function RenameGroupIcon($group) {
		if (!CanEditGroup($group)) { return; }
		?><a href="#" onclick="$('#renameGroupModal').modal('show'); return false;" title="Rename group"><i class="grey edit outline icon" style="font-size: 0.6em"></i></a><?
	}


	/* -------------------------------------------- */
	/* ------- ViewGroup -------------------------- */
	/* -------------------------------------------- */
	function ViewGroup($id) {

		/* get the general group information */
		$row = GetGroup($id);
		if (!$row) { Error("Group not found"); return; }
		$groupname = $row['group_name'] ?? '';
		$grouptype = $row['group_type'] ?? '';

		if (CanEditGroup($row)) {
			DisplayRenameGroupModal($id, $groupname);
		}

		/* clean up duplicate members (added before the duplicate checks matched on data_id alone) */
		if (($grouptype == 'study') || ($grouptype == 'subject')) {
			$numremoved = RemoveGroupDuplicates($id);
			if ($numremoved > 0) {
				Notice("Removed <b>$numremoved</b> duplicate " . (($grouptype == 'study') ? "studies" : "subjects") . " from this group");
			}
		}

		if ($grouptype == 'study') {
			ViewStudyGroup($id);
		}
		elseif ($grouptype == 'subject') {
			ViewSubjectGroup($id);
		}
		else {
			/* no member view for other types (nda, and series groups, which are no longer supported) */
			?>
			<div class="ui text container">
				<? DisplayGroupTitle($row, ucfirst($grouptype) . " group", "users"); ?>
				<div class="ui message">Viewing members of <b><?=htmlspecialchars($grouptype)?></b> groups is not supported</div>
			</div>
			<?
		}
	}


	/* -------------------------------------------- */
	/* ------- ViewStudyGroup --------------------- */
	/* -------------------------------------------- */
	function ViewStudyGroup($id) {
		$id = (int)$id;
		$group = GetGroup($id);
		$canedit = CanEditGroup($group);

		/* one row per study in the group */
		$sqlstring = "select a.subjectgroup_id, c.enroll_subgroup, b.*, d.* from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = ? order by d.uid, b.study_num";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);

		$rows = array();     /* grid rows */
		$ids = array();      /* UIDStudyNum list for the edit box */
		$studylist = array();
		$subjects = array(); /* subject_id => subjects row, for the subject stats */
		$ages = array();     /* age at each study */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = (int)($row['subject_id'] ?? 0);
			$studyid = (int)($row['study_id'] ?? 0);
			/* the study (or its subject) was deleted; still listed so it can be removed from the group */
			if (($studyid <= 0) || ($subjectid <= 0)) {
				$rows[] = array('itemid' => (int)$row['subjectgroup_id'], 'uid' => '(deleted)');
				continue;
			}
			$uid = $row['uid'];
			$studynum = $row['study_num'];

			list($studyAge, $calcStudyAge) = GetStudyAge($row['birthdate'], $row['study_ageatscan'], $row['study_datetime']);
			$age = $studyAge ?? $calcStudyAge;
			if ($age !== null) { $ages[] = (float)$age; }

			$subjects[$subjectid] = $row;
			$ids[] = $uid . $studynum;
			$studylist[] = $studyid;

			$rows[] = array(
				'itemid' => (int)$row['subjectgroup_id'],
				'subjectid' => $subjectid,
				'studyid' => $studyid,
				'initials' => NameInitials($row['name']),
				'uid' => $uid,
				'dob' => $row['birthdate'],
				'studydate' => $row['study_datetime'],
				'studyage' => ($studyAge === null) ? null : round((float)$studyAge, 1),
				'calcstudyage' => ($calcStudyAge === null) ? null : round((float)$calcStudyAge, 1),
				'sex' => $row['gender'],
				'ethnicity' => trim(($row['ethnicity1'] ?? '') . ' ' . ($row['ethnicity2'] ?? '')),
				'subgroup' => $row['enroll_subgroup'],
				'visittype' => $row['study_type'],
				'weight' => ((float)($row['study_weight'] ?? 0) > 0) ? (float)$row['study_weight'] : null,
				'handedness' => $row['handedness'],
				'education' => $row['education'],
				'altuids' => implode2(', ', GetAlternateUIDs($subjectid, '')),
				'studyuid' => $uid . $studynum,
				'description' => $row['study_desc'],
				'altstudyid' => $row['study_alternateid'],
				'modality' => $row['study_modality'],
				'operator' => $row['study_operator'],
				'physician' => $row['study_performingphysician'],
				'site' => $row['study_site'],
			);
		}

		$columns = array(
			array('field' => 'initials', 'headerName' => 'Initials', 'width' => 90),
			array('field' => 'uid', 'headerName' => 'UID', 'kind' => 'subject', 'width' => 120),
			array('field' => 'dob', 'headerName' => 'DOB', 'width' => 110),
			array('field' => 'studydate', 'headerName' => 'Study date', 'width' => 160),
			array('field' => 'studyage', 'headerName' => 'Age (header)', 'kind' => 'number', 'width' => 110),
			array('field' => 'calcstudyage', 'headerName' => 'Age (computed)', 'kind' => 'number', 'width' => 120),
			array('field' => 'sex', 'headerName' => 'Sex', 'kind' => 'sex', 'width' => 80),
			array('field' => 'ethnicity', 'headerName' => 'Ethnicities', 'width' => 130),
			array('field' => 'subgroup', 'headerName' => 'Sub-group', 'width' => 110),
			array('field' => 'visittype', 'headerName' => 'Visit type', 'width' => 110),
			array('field' => 'weight', 'headerName' => 'Weight (kg)', 'kind' => 'number', 'width' => 110),
			array('field' => 'handedness', 'headerName' => 'Handedness', 'width' => 110),
			array('field' => 'education', 'headerName' => 'Education', 'width' => 110),
			array('field' => 'altuids', 'headerName' => 'Alt UIDs', 'width' => 150, 'tooltipField' => 'altuids'),
			array('field' => 'studyuid', 'headerName' => 'Study ID', 'kind' => 'study', 'width' => 130),
			array('field' => 'description', 'headerName' => 'Description', 'width' => 180, 'tooltipField' => 'description'),
			array('field' => 'altstudyid', 'headerName' => 'Alt study ID', 'width' => 120),
			array('field' => 'modality', 'headerName' => 'Modality', 'width' => 100),
			array('field' => 'operator', 'headerName' => 'Operator', 'width' => 110),
			array('field' => 'physician', 'headerName' => 'Physician', 'width' => 110),
			array('field' => 'site', 'headerName' => 'Site', 'width' => 120),
		);

		?>
		<div class="ui stackable two column grid">
			<div class="column">
				<? DisplayGroupTitle($group, "Study group", "clipboard list"); ?>
				<? DisplaySubjectStats($subjects, $ages, "at study", count($studylist)); ?>
				<? if (count($studylist) > 0) { ?>
				<div class="ui segment">
					<? DisplayMRProtocolSummary($studylist); ?>
				</div>
				<? } ?>
			</div>
			<div class="column">
				<? DisplayGroupIDList($id, "updatestudygroup", "studylist", "Studies", "One UID + study number per line (e.g. S1234ABC1)", $ids, $canedit); ?>
			</div>
		</div>
		<?
		DisplayGroupMemberGrid($columns, $rows, $id, $canedit, $group['group_name'] ?? '', "studies");
	}


	/* -------------------------------------------- */
	/* ------- ViewSubjectGroup ------------------- */
	/* -------------------------------------------- */
	function ViewSubjectGroup($id) {
		$id = (int)$id;
		$group = GetGroup($id);
		$canedit = CanEditGroup($group);

		/* one row per subject in the group */
		$sqlstring = "select a.subjectgroup_id, b.* from group_data a left join subjects b on a.data_id = b.subject_id where a.group_id = ? order by b.uid";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);

		$rows = array();     /* grid rows */
		$ids = array();      /* UID list for the edit box */
		$subjects = array(); /* subject_id => subjects row, for the subject stats */
		$ages = array();     /* current age of each subject */
		$today = date('Y-m-d');
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$subjectid = (int)($row['subject_id'] ?? 0);
			/* the subject was deleted; still listed so it can be removed from the group */
			if ($subjectid <= 0) {
				$rows[] = array('itemid' => (int)$row['subjectgroup_id'], 'uid' => '(deleted)');
				continue;
			}

			list($unused, $age) = GetStudyAge($row['birthdate'], 0, $today);
			if (($age !== null) && !isset($subjects[$subjectid])) { $ages[] = (float)$age; }

			$subjects[$subjectid] = $row;
			$ids[] = $row['uid'];

			$rows[] = array(
				'itemid' => (int)$row['subjectgroup_id'],
				'subjectid' => $subjectid,
				'initials' => NameInitials($row['name']),
				'uid' => $row['uid'],
				'age' => ($age === null) ? null : round((float)$age, 1),
				'sex' => $row['gender'],
				'ethnicity1' => $row['ethnicity1'],
				'ethnicity2' => $row['ethnicity2'],
				'weight' => ((float)($row['weight'] ?? 0) > 0) ? (float)$row['weight'] : null,
				'handedness' => $row['handedness'],
				'education' => $row['education'],
				'altuids' => implode2(', ', GetAlternateUIDs($subjectid, '')),
			);
		}

		$columns = array(
			array('field' => 'initials', 'headerName' => 'Initials', 'width' => 90),
			array('field' => 'uid', 'headerName' => 'UID', 'kind' => 'subject', 'width' => 120),
			array('field' => 'age', 'headerName' => 'Age (current)', 'kind' => 'number', 'width' => 120),
			array('field' => 'sex', 'headerName' => 'Sex', 'kind' => 'sex', 'width' => 80),
			array('field' => 'ethnicity1', 'headerName' => 'Ethnicity 1', 'width' => 120),
			array('field' => 'ethnicity2', 'headerName' => 'Ethnicity 2', 'width' => 120),
			array('field' => 'weight', 'headerName' => 'Weight (kg)', 'kind' => 'number', 'width' => 110),
			array('field' => 'handedness', 'headerName' => 'Handedness', 'width' => 110),
			array('field' => 'education', 'headerName' => 'Education', 'width' => 110),
			array('field' => 'altuids', 'headerName' => 'Alt UIDs', 'flex' => 1, 'minWidth' => 150, 'tooltipField' => 'altuids'),
		);

		?>
		<div class="ui stackable two column grid">
			<div class="column">
				<? DisplayGroupTitle($group, "Subject group", "user friends"); ?>
				<? DisplaySubjectStats($subjects, $ages, "current"); ?>
			</div>
			<div class="column">
				<? DisplayGroupIDList($id, "updatesubjectgroup", "uidlist", "Subjects", "One UID per line (e.g. S1234ABC)", $ids, $canedit); ?>
			</div>
		</div>
		<?
		DisplayGroupMemberGrid($columns, $rows, $id, $canedit, $group['group_name'] ?? '', "subjects");
	}


	/* -------------------------------------------- */
	/* ------- NameInitials ----------------------- */
	/* -------------------------------------------- */
	/* initials from a "Last^First" name */
	function NameInitials($name) {
		$parts = explode("^", $name ?? "");
		return substr($parts[1] ?? "", 0, 1) . substr($parts[0] ?? "", 0, 1);
	}


	/* -------------------------------------------- */
	/* ------- DisplayGroupTitle ------------------ */
	/* -------------------------------------------- */
	/* title segment shown on the group views: name (with rename icon), group type, and the delete button */
	function DisplayGroupTitle($group, $subheader, $icon) {
		?>
		<div class="ui grey segment">
			<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1em">
				<h2 class="ui header" style="margin: 0">
					<i class="<?=htmlspecialchars($icon)?> icon"></i>
					<div class="content">
						<?=htmlspecialchars($group['group_name'] ?? '')?> <? RenameGroupIcon($group); ?>
						<div class="sub header"><?=htmlspecialchars($subheader)?></div>
					</div>
				</h2>
				<? DeleteGroupButton((int)$group['group_id'], $group); ?>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySubjectStats ---------------- */
	/* -------------------------------------------- */
	/* subject-level summary: $subjects is subject_id => subjects row, $ages the ages to average.
	   $numstudies is shown for study groups */
	function DisplaySubjectStats($subjects, $ages, $agelabel, $numstudies = null) {
		$n = count($subjects);
		$sexes = array();
		foreach ($subjects as $subject) {
			$sex = strtoupper(trim($subject['gender'] ?? ''));
			if ($sex == '') { $sex = 'Not specified'; }
			$sexes[$sex] = ($sexes[$sex] ?? 0) + 1;
		}
		ksort($sexes);
		$numage = count($ages);
		$avgage = ($numage > 0) ? array_sum($ages)/$numage : 0;
		?>
		<div class="ui segment">
			<h3 class="ui header">Subjects</h3>
			<table class="ui very basic very compact collapsing table">
				<tr>
					<td class="right aligned">Subjects</td>
					<td><b><?=$n?></b></td>
				</tr>
				<? if ($numstudies !== null) { ?>
				<tr>
					<td class="right aligned">Studies</td>
					<td><b><?=(int)$numstudies?></b></td>
				</tr>
				<? } ?>
				<tr>
					<td class="right aligned">Age <?=htmlspecialchars($agelabel)?><br><span class="tiny">from <?=$numage?> valid ages</span></td>
					<td><? if ($numage > 0) { ?><?=number_format($avgage,1)?>Y <span class="small">&plusmn;<?=number_format(sd($ages),1)?>Y</span><? } else { echo "-"; } ?></td>
				</tr>
				<tr>
					<td class="right aligned">Sex</td>
					<td>
						<?
						foreach ($sexes as $sex => $count) {
							$pct = number_format(($count/$n)*100, 1);
							echo htmlspecialchars((string)$sex) . ": <b>$count</b> <span class=\"small\">($pct%)</span><br>";
						}
						?>
					</td>
				</tr>
			</table>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayGroupIDList ----------------- */
	/* -------------------------------------------- */
	/* editable list of the group's member IDs; submitting replaces the group's members with the list */
	function DisplayGroupIDList($id, $action, $fieldname, $title, $help, $ids, $canedit) {
		?>
		<div class="ui segment">
			<h3 class="ui header">
				<?=htmlspecialchars($title)?>
				<div class="sub header"><?=htmlspecialchars($help)?><? if ($canedit) { ?>. Edit or paste the list, then click Update; IDs removed from the list are removed from the group<? } ?></div>
			</h3>
			<form action="groups.php" method="post" class="ui form">
				<?=CSRFTokenField()?>
				<input type="hidden" name="action" value="<?=htmlspecialchars($action)?>">
				<input type="hidden" name="id" value="<?=(int)$id?>">
				<textarea name="<?=htmlspecialchars($fieldname)?>" rows="14" style="font-family: monospace; font-size: larger;" <?=($canedit ? "" : "readonly")?>><?=htmlspecialchars(implode("\n", $ids))?></textarea>
				<? if ($canedit) { ?>
				<div style="margin-top: 1em; text-align: right">
					<button class="ui primary button" type="submit">Update</button>
				</div>
				<? } ?>
			</form>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayGroupMemberGrid ------------- */
	/* -------------------------------------------- */
	/* ag-grid listing of the group members. $columns are ag-grid column defs, plus an optional 'kind'
	   (subject/study link, sex, number). Each row needs 'itemid' (the group_data row), which the
	   "Remove selected" button posts to removegroupitem */
	function DisplayGroupMemberGrid($columns, $rows, $id, $canedit, $groupname, $noun) {
		$jsonflags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE;
		$csvname = preg_replace('/[^A-Za-z0-9_-]/', '_', $groupname) . ".csv";
		$autoheight = (count($rows) <= 30); /* small groups size the grid to fit, large ones scroll */
		?>
		<div class="ui segment">
			<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap">
				<input type="text" placeholder="Search..." oninput="memberGridApi.setGridOption('quickFilterText', this.value)" style="padding: 5px 8px; width: 250px; border: 1px solid #ccc; border-radius: 4px">
				<span style="color: #888">Displaying <span id="memberCount"><?=count($rows)?></span> <?=htmlspecialchars($noun)?></span>
				<? if ($canedit) { ?>
				<span style="color: #888">&middot; Select rows with the checkboxes: shift-click selects a range, or drag across rows</span>
				<? } ?>
				<div style="margin-left: auto">
					<? if ($canedit) { ?>
					<form action="groups.php" method="post" style="display: inline" onsubmit="return SubmitRemoveMembers(this)">
						<?=CSRFTokenField()?>
						<input type="hidden" name="action" value="removegroupitem">
						<input type="hidden" name="id" value="<?=(int)$id?>">
						<button type="submit" id="removeMembersButton" class="ui small red button" disabled><i class="trash icon"></i>Remove selected (<span id="selectedCount">0</span>)</button>
					</form>
					<? } ?>
					<button class="ui small basic button" onclick="ExportMembers()"><i class="download icon"></i>Export CSV</button>
				</div>
			</div>
			<div id="memberGrid" style="width: 100%;<?=($autoheight ? "" : " height: 65vh;")?>"></div>
		</div>

		<!-- ag-grid v36 uses the Theming API (styles injected by the JS); no CSS import or theme class needed -->
		<script src="//cdn.jsdelivr.net/npm/ag-grid-community@36/dist/ag-grid-community.min.js"></script>
		<script>
		let memberGridApi;
		const memberCsvName = <?=json_encode($csvname, $jsonflags)?>;

		/* post the selected rows' itemids to removegroupitem */
		function SubmitRemoveMembers(form) {
			const rows = memberGridApi.getSelectedRows();
			if (rows.length == 0) { return false; }
			if (!confirm('Remove ' + rows.length + ' item(s) from the group?')) { return false; }
			form.querySelectorAll('input[name="itemid[]"]').forEach(function(el) { el.remove(); });
			rows.forEach(function(row) {
				const input = document.createElement('input');
				input.type = 'hidden';
				input.name = 'itemid[]';
				input.value = row.itemid;
				form.appendChild(input);
			});
			return true;
		}

		function ExportMembers() {
			memberGridApi.exportDataAsCsv({ fileName: memberCsvName });
		}

		(function() {
			const rowData = <?=json_encode($rows, $jsonflags)?>;
			const canEdit = <?=($canedit ? "true" : "false")?>;

			function Link(href, text) {
				const a = document.createElement('a');
				a.href = href;
				a.textContent = text;
				return a;
			}

			/* extra column options by 'kind'. Renderers return DOM nodes, never HTML strings */
			const kinds = {
				subject: { cellRenderer: function(p) { return p.data.subjectid ? Link('subjects.php?id=' + encodeURIComponent(p.data.subjectid), p.value) : document.createTextNode(p.value || ''); } },
				study: { cellRenderer: function(p) { return p.data.studyid ? Link('studies.php?id=' + encodeURIComponent(p.data.studyid), p.value) : document.createTextNode(p.value || ''); } },
				sex: { cellStyle: function(p) { return (p.data.subjectid && ['M', 'F', 'O'].indexOf(String(p.value || '').toUpperCase()) < 0) ? { color: 'red' } : null; } },
				number: { filter: 'agNumberColumnFilter', valueFormatter: function(p) { return (p.value === null || p.value === undefined) ? '-' : Number(p.value).toFixed(1); } },
			};
			const columnDefs = <?=json_encode($columns, $jsonflags)?>.map(function(c) {
				const def = Object.assign({}, c, kinds[c.kind] || {});
				delete def.kind;
				return def;
			});

			const gridOptions = {
				columnDefs: columnDefs,
				rowData: rowData,
				defaultColDef: { sortable: true, filter: true, resizable: true },
				domLayout: <?=($autoheight ? "'autoHeight'" : "'normal'")?>,
				getRowId: function(p) { return String(p.data.itemid); },
				tooltipShowDelay: 500,
				overlayNoRowsTemplate: '<span style="color: #888">This group has no members</span>',
				/* keep the "Displaying X" count in sync with the quick filter / column filters */
				onModelUpdated: function(p) {
					const el = document.getElementById('memberCount');
					if (el) { el.textContent = p.api.getDisplayedRowCount().toLocaleString(); }
				},
			};

			if (canEdit) {
				/* checkbox selection: click toggles a row, shift-click selects a range, the header checkbox selects all (filtered) rows */
				gridOptions.rowSelection = { mode: 'multiRow', checkboxes: true, headerCheckbox: true, selectAll: 'filtered', enableClickSelection: false };
				gridOptions.onSelectionChanged = function(p) {
					const n = p.api.getSelectedRows().length;
					document.getElementById('selectedCount').textContent = n;
					document.getElementById('removeMembersButton').disabled = (n == 0);
				};

				/* drag-select: press on a row and drag across others to select the rows in between
				   (or deselect them, if the first row was already selected). A plain click is unaffected */
				let dragStart = null;
				let dragValue = true;
				gridOptions.onCellMouseDown = function(p) {
					const e = p.event;
					if (!e || e.button !== 0 || e.shiftKey || e.ctrlKey || e.metaKey) { dragStart = null; return; }
					dragStart = p.node.rowIndex;
					dragValue = !p.node.isSelected();
				};
				gridOptions.onCellMouseOver = function(p) {
					if (dragStart === null) { return; }
					if (!p.event || !(p.event.buttons & 1)) { dragStart = null; return; }
					if (p.node.rowIndex === dragStart) { return; }
					const lo = Math.min(dragStart, p.node.rowIndex);
					const hi = Math.max(dragStart, p.node.rowIndex);
					const nodes = [];
					for (let i = lo; i <= hi; i++) {
						const node = p.api.getDisplayedRowAtIndex(i);
						if (node) { nodes.push(node); }
					}
					p.api.setNodesSelected({ nodes: nodes, newValue: dragValue });
				};
				document.addEventListener('mouseup', function() { dragStart = null; });
			}

			memberGridApi = agGrid.createGrid(document.getElementById('memberGrid'), gridOptions);
		})();
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayMRProtocolSummary ----------- */
	/* -------------------------------------------- */
	function DisplayMRProtocolSummary($studylist) {
		$studylist = array_map('intval', array_filter((array)$studylist));
		$studies = implode(",",$studylist);

		if (trim($studies) == "") {
			return;
		}
		$sqlstring = "SELECT series_altdesc, series_tr, series_te, series_flip, phaseencodedir, PhaseEncodingDirectionPositive, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols, count(*) 'count' FROM `mr_series` where study_id in ($studies) and is_derived <> 1 group by series_altdesc, series_tr, series_te, series_flip, phaseencodedir, PhaseEncodingDirectionPositive, series_spacingx, series_spacingy, series_spacingz, img_rows, img_cols";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?>
		<details>
			<summary>MR protocol summary</summary>
			<table class="ui very compact small celled grey table">
				<thead>
				<th>Desc</th>
				<th>TR</th>
				<th>TE</th>
				<th>Flip</th>
				<th>Phase dir</th>
				<th>Phase dir pos.</th>
				<th>Spacing X</th>
				<th>Spacing Y</th>
				<th>Spacing Z (center of slices)</th>
				<th>Rows</th>
				<th>Cols</th>
				<th>Slices</th>
				<th>Count</th>
				</thead>
				<tbody>
				<?
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$seriesdesc = $row['series_altdesc'];
					$series_tr = $row['series_tr'];
					$series_te = $row['series_te'];
					$series_flip = $row['series_flip'];
					$phaseencodedir = $row['phaseencodedir'];
					$PhaseEncodingDirectionPositive = $row['PhaseEncodingDirectionPositive'];
					$series_spacingx = $row['series_spacingx'];
					$series_spacingy = $row['series_spacingy'];
					$series_spacingz = $row['series_spacingz'];
					$img_rows = $row['img_rows'];
					$img_cols = $row['img_cols'];
					$img_slices = $row["img_slices"] ?? "";
					$count = $row['count'];
					?>
					<tr>
						<td><?=htmlspecialchars((string)$seriesdesc)?></td>
						<td><?=htmlspecialchars((string)$series_tr)?></td>
						<td><?=htmlspecialchars((string)$series_te)?></td>
						<td><?=htmlspecialchars((string)$series_flip)?></td>
						<td><?=htmlspecialchars((string)$phaseencodedir)?></td>
						<td><?=htmlspecialchars((string)$PhaseEncodingDirectionPositive)?></td>
						<td><?=htmlspecialchars((string)$series_spacingx)?></td>
						<td><?=htmlspecialchars((string)$series_spacingy)?></td>
						<td><?=htmlspecialchars((string)$series_spacingz)?></td>
						<td><?=htmlspecialchars((string)$img_rows)?></td>
						<td><?=htmlspecialchars((string)$img_cols)?></td>
						<td><?=htmlspecialchars((string)$img_slices)?></td>
						<td><?=htmlspecialchars((string)$count)?></td>
					</tr>
					<?
				}
				?>
				</tbody>
			</table>
		</details>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplayGroupList ------------------- */
	/* -------------------------------------------- */
	function DisplayGroupList($type) {

		?>
		<div class="ui container">

			<div class="ui grid">
				<div class="six wide column">
					<h1 class="ui header">Groups</h1>
				</div>
				<div class="ten wide column" align="right">
					<form action="groups.php" method="post" name="theform" id="theform">
					<?=CSRFTokenField()?>
					<input type="hidden" name="action" value="add">
					<div class="ui labeled action input">
						<input type="text" name="groupname" placeholder="Group name" required>
						<select name="grouptype" class="ui selection dropdown" required>
							<option value="">(select group type)
							<option value="nda">NDA
							<option value="study">Study
							<option value="subject">Subject
						</select>
						<button type="submit" class="ui button primary"><i class="plus square outline icon"></i> Create Group</button>
					</div>
					</form>
				</div>
			</div>
			
			View
			<a class="ui <? if ($type == "all" || $type == "") { echo "green"; } ?> button" href="groups.php">All</a>
			<a class="ui <? if ($type == "subject") { echo "green"; } ?> button" href="groups.php?action=viewgrouplist&displaytype=subject">Subject</a>
			<a class="ui <? if ($type == "study") { echo "green"; } ?> button" href="groups.php?action=viewgrouplist&displaytype=study">Study</a>
			<a class="ui <? if ($type == "nda") { echo "green"; } ?> button" href="groups.php?action=viewgrouplist&displaytype=nda">NDA</a>
			
			<table class="ui small celled selectable grey very compact table">
				<thead>
				<tr>
					<th>Name</th>
					<th>Type</th>
					<th>Owner</th>
					<th>Group size</th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				<?
				
				$sqlstring = "select a.*, b.username 'ownerusername', b.user_fullname 'ownerfullname' from groups a left join users b on a.group_owner = b.user_id";
				switch ($type) {
					case 'subject': $sqlstring .= " where a.group_type = 'subject'"; break;
					case 'study': $sqlstring .= " where a.group_type = 'study'"; break;
					case 'nda': $sqlstring .= " where a.group_type = 'nda'"; break;
				}
				$sqlstring .= " order by a.group_name";
				
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['group_id'];
					$name = $row['group_name'];
					$ownerusername = $row['ownerusername'];
					$grouptype = $row['group_type'];
					$canedit = CanEditGroup($row);

					$id = (int)$id;
					$sqlstring2 = "select count(*) 'count' from group_data where group_id = $id"; /* int from db, safe to inline */
					$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
					$row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
					$count = $row2['count'];
					?>
					<tr>
						<td><a href="groups.php?action=viewgroup&id=<?=$id?>"><?=htmlspecialchars($name ?? '')?></a></td>
						<td><?=htmlspecialchars($grouptype ?? '')?></td>
						<td><?=htmlspecialchars($ownerusername ?? '')?></td>
						<td><?=(int)$count?></td>
						<td align="right">
							<? if ($canedit) { ?>
								<form action="groups.php" method="post" style="display: inline" onsubmit="return confirm('Delete this group?')">
									<?=CSRFTokenField()?>
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id" value="<?=$id?>">
									<button type="submit" class="ui basic icon button" style="box-shadow: none; padding: 0; color: red" title="Delete group"><i class="trash icon"></i></button>
								</form>
							<? } ?>
						</td>
					</tr>
					<?
				}
				?>
				</tbody>
			</table>
		</div>
		<?
	}

	
?>
<? include("footer.php") ?>

