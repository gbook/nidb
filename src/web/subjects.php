<?
 // ------------------------------------------------------------------------------
 // NiDB subjects.php
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
	
	$debug = false;
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Subjects</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "nidbapi.php";
	require "menu.php";

	//PrintVariable($_POST);
	//PrintVariable($GLOBALS);
	
	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = (int)GetVariable("id");
	$subjectid = (int)GetVariable("subjectid");
	$selectedid = (int)GetVariable("selectedid");
	$projectid = (int)GetVariable("projectid");
	$newprojectid = (int)GetVariable("newprojectid");
	$enrollmentid = (int)GetVariable("enrollmentid");
	$encrypt = GetVariable("encrypt");
	$name = GetVariable("name");
	$lastname = GetVariable("lastname");
	$firstname = GetVariable("firstname");
	$fullname = GetVariable("fullname");
	$dob = GetVariable("dob");
	$gender = GetVariable("gender");
	$ethnicity1 = GetVariable("ethnicity1");
	$ethnicity2 = GetVariable("ethnicity2");
	$handedness = GetVariable("handedness");
	$education = GetVariable("education");
	$phone = GetVariable("phone");
	$email = GetVariable("email");
	$maritalstatus = GetVariable("maritalstatus");
	$smokingstatus = GetVariable("smokingstatus");
	$cancontact = GetVariable("cancontact");
	$tags = GetVariable("tags");
	$enrollgroup = GetVariable("enrollgroup");
	$uid = GetVariable("uid");
	$altuids = GetVariable("altuids");
	$enrollmentids = GetVariable("enrollmentids");
	$guid = GetVariable("guid");
	$searchuid = trim(GetVariable("searchuid"));
	$searchaltuid = trim(GetVariable("searchaltuid"));
	$searchname = trim(GetVariable("searchname"));
	$searchgender = trim(GetVariable("searchgender"));
	$searchdob = trim(GetVariable("searchdob"));
	$searchactive = GetVariable("searchactive");
	$uid2 = GetVariable("uid2");
	$relation = GetVariable("relation");
	$makesymmetric = GetVariable("makesymmetric");
	$ids = GetVariable("ids");
	$modality = GetVariable("modality");
	$returnpage = GetVariable("returnpage");
	$templateid = (int)GetVariable("templateid");
	$grouptemplateid = (int)GetVariable("grouptemplateid");

	/* fix the 'active' search */
	if (($searchactive == '') && ($action != '')) {
		$searchactive = 0;
	}
	else {
		$searchactive = 1;
	}
	
	if ($id == 0) $id = $subjectid;
	
	/* determine action. Actions that change data run inside an output buffer, stash their messages
	   in $_SESSION['flash'], and redirect to a GET (Post/Redirect/GET) so a refresh can't re-submit */
	switch ($action) {
		case 'editform':
			DisplaySubjectForm("edit", $id);
			break;
		case 'addrelation':
			ob_start();
			AddRelation($id, $uid2, $relation, $makesymmetric);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php?id=$id");
			break;
		case 'changeproject':
			ob_start();
			ChangeProject($id, $enrollmentid, $newprojectid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php?id=$id");
			break;
		case 'setcurrentproject':
			SetCurrentSubjectProject($id, $projectid);
			DisplaySubject($id, $projectid);
			break;
		case 'addform':
			DisplaySubjectForm("add", "");
			break;
		case 'display':
			DisplaySubject($id, $projectid);
			break;
		case 'print':
			PrintEnrollment($id, $enrollmentid);
			break;
		case 'newstudy':
			ob_start();
			CreateNewStudy($modality, $enrollmentid, $id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php?id=$id");
			break;
		case 'newstudyfromtemplate':
			ob_start();
			CreateStudyFromTemplate($modality, $enrollmentid, $id, $templateid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php?id=$id");
			break;
		case 'newstudygroupfromtemplate':
			ob_start();
			CreateStudyGroupFromTemplate($modality, $enrollmentid, $id, $grouptemplateid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php?id=$id");
			break;
		case 'deleteconfirm':
			DeleteConfirm($id);
			break;
		case 'delete':
			ob_start();
			Delete($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php?id=$id");
			break;
		case 'undelete':
			ob_start();
			UnDelete($id);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php?id=$id");
			break;
		case 'obliterate':
			ob_start();
			Obliterate($ids);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php");
			break;
		case 'enroll':
			ob_start();
			EnrollSubject($id, $projectid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php?id=$id");
			break;
		case 'confirmupdate':
			Confirm("update", $id, $encrypt, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, $uid, $altuids, $enrollmentids, $guid);
			break;
		case 'confirmadd':
			Confirm("add", "", $encrypt, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, "", $altuids, $enrollmentids, $guid);
			break;
		case 'update':
			ob_start();
			UpdateSubject($id, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, $uid, $altuids, $enrollmentids, $guid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo("subjects.php?id=$id");
			break;
		case 'add':
			ob_start();
			/* the add form has a single "all projects" alternate UID field (altuids[0]) */
			$newid = AddSubject($lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, (is_array($altuids) ? ($altuids[0] ?? '') : ''), $guid);
			$_SESSION['flash'] = ob_get_clean();
			RedirectTo(($newid > 0) ? "subjects.php?id=$newid" : "subjects.php");
			break;
		default:
			if ($id == 0) {
				DisplaySubjectList($searchuid, $searchaltuid, $searchname, $searchgender, $searchdob, $searchactive);
			}
			else {
				DisplaySubject($id, $projectid);
			}
	}
	

	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- GetCurrentSubjectProject ----------- */
	/* -------------------------------------------- */
	function GetCurrentSubjectProject($subjectRowID, $projectRowID="") {
		$subjectRowID = (int)$subjectRowID;
		$projectRowID = (int)$projectRowID;
		$project = array("projectRowID" => "", "project_name" => "");

		if ($projectRowID > 0) {
			$stmt = mysqli_prepare($GLOBALS['linki'], "select a.project_id, b.project_name from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = ? and a.project_id = ? limit 1");
			mysqli_stmt_bind_param($stmt, 'ii', $subjectRowID, $projectRowID);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if ($row) {
				$_SESSION['currentProjectID'] = $projectRowID;
				$project["projectRowID"] = $row['project_id'];
				$project["project_name"] = $row['project_name'];
				return $project;
			}
		}

		if (isset($_SESSION['currentProjectID'])) {
			$projectRowID = (int)$_SESSION['currentProjectID'];
			if ($projectRowID > 0) {
				$stmt = mysqli_prepare($GLOBALS['linki'], "select a.project_id, b.project_name from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = ? and a.project_id = ? limit 1");
				mysqli_stmt_bind_param($stmt, 'ii', $subjectRowID, $projectRowID);
				$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				mysqli_stmt_close($stmt);
				if ($row) {
					$project["projectRowID"] = $row['project_id'];
					$project["project_name"] = $row['project_name'];
					return $project;
				}
			}
		}

		$stmt = mysqli_prepare($GLOBALS['linki'], "select a.project_id, b.project_name from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = ? limit 1");
		mysqli_stmt_bind_param($stmt, 'i', $subjectRowID);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if ($row) {
			$_SESSION['currentProjectID'] = $row['project_id'];
			$project["projectRowID"] = $row['project_id'];
			$project["project_name"] = $row['project_name'];
			return $project;
		}

		return $project;
	}


	/* -------------------------------------------- */
	/* ------- SetCurrentSubjectProject ----------- */
	/* -------------------------------------------- */
	function SetCurrentSubjectProject($subjectRowID, $projectRowID) {
		$subjectRowID = (int)$subjectRowID;
		$projectRowID = (int)$projectRowID;

		if (($subjectRowID < 1) || ($projectRowID < 1)) {
			Error("Invalid subject or project ID");
			return;
		}

		$stmt = mysqli_prepare($GLOBALS['linki'], "select project_id from enrollment where subject_id = ? and project_id = ? limit 1");
		mysqli_stmt_bind_param($stmt, 'ii', $subjectRowID, $projectRowID);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		if (!$row) {
			Error("Subject is not enrolled in the selected project");
			return;
		}

		$_SESSION['currentProjectID'] = $projectRowID;
	}


	/* -------------------------------------------- */
	/* ------- GetAdjacentSubjectInProject -------- */
	/* -------------------------------------------- */
	function GetAdjacentSubjectInProject($subjectRowID, $projectRowID, $direction) {
		$subjectRowID = (int)$subjectRowID;
		$projectRowID = (int)$projectRowID;
		$direction = strtolower(trim($direction));

		if (($subjectRowID < 1) || ($projectRowID < 1)) {
			return false;
		}

		$stmt = mysqli_prepare($GLOBALS['linki'], "select uid from subjects where subject_id = ?");
		mysqli_stmt_bind_param($stmt, 'i', $subjectRowID);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			return false;
		}

		$uid = $row['uid'];
		if ($direction == "previous") {
			$stmt = mysqli_prepare($GLOBALS['linki'], "select a.subject_id 'subjectRowID', a.uid from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = ? and (a.uid < ? or (a.uid = ? and a.subject_id < ?)) order by a.uid desc, a.subject_id desc limit 1");
		}
		else {
			$stmt = mysqli_prepare($GLOBALS['linki'], "select a.subject_id 'subjectRowID', a.uid from subjects a left join enrollment b on a.subject_id = b.subject_id where b.project_id = ? and (a.uid > ? or (a.uid = ? and a.subject_id > ?)) order by a.uid asc, a.subject_id asc limit 1");
		}
		mysqli_stmt_bind_param($stmt, 'issi', $projectRowID, $uid, $uid, $subjectRowID);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);

		return $row;
	}


	/* -------------------------------------------- */
	/* ------- CanCreateStudy --------------------- */
	/* -------------------------------------------- */
	/* checks that $enrollmentid belongs to subject $subjectid and that the user has Edit Data on the
	   enrollment's project. Displays an error and returns 0 if not, otherwise returns the project ID */
	function CanCreateStudy($enrollmentid, $subjectid) {
		$enrollment = GetEnrollment($enrollmentid);
		if (($enrollment == null) || ($enrollment['subject_id'] != $subjectid)) {
			Error("Invalid enrollment");
			return 0;
		}
		$projectid = (int)$enrollment['project_id'];
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (!GetPerm($perms, 'modifydata', $projectid)) {
			Error("You do not have permission to create studies in this project");
			return 0;
		}
		return $projectid;
	}


	/* -------------------------------------------- */
	/* ------- GetNextStudyNum -------------------- */
	/* -------------------------------------------- */
	function GetNextStudyNum($subjectid) {
		$subjectid = (int)$subjectid;
		$sqlstring = "select max(a.study_num) 'max' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id where b.subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $subjectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		return (int)($row['max'] ?? 0) + 1;
	}


	/* -------------------------------------------- */
	/* ------- StudyCreatedNotice ----------------- */
	/* -------------------------------------------- */
	function StudyCreatedNotice($subjectid, $projectid, $studynum, $studyRowID) {
		$sqlstring = "select (select uid from subjects where subject_id = ?) 'uid', project_name, project_costcenter from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $subjectid, $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid, $projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$uid = htmlspecialchars($row['uid'] ?? '');
		$projectname = htmlspecialchars($row['project_name'] ?? '');
		$projectcostcenter = htmlspecialchars($row['project_costcenter'] ?? '');

		Notice("Study $studynum has been created for subject $uid in $projectname ($projectcostcenter)<br><a href='studies.php?id=$studyRowID'>View Study</a>");
	}


	/* -------------------------------------------- */
	/* ------- UpdateSubject ---------------------- */
	/* -------------------------------------------- */
	/* $altuids is keyed by enrollment ID; key 0 is the "all projects" list. $enrollmentids is no longer used */
	function UpdateSubject($id, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email,$maritalstatus,$smokingstatus, $cancontact, $tags, $uid, $altuids, $enrollmentids, $guid) {
		if (!ValidID($id,'Subject ID')) { return; }
		$id = (int)$id;

		$sp = GetSubjectPermissions($id);
		if (!$sp['canedit']) {
			Error("You do not have permission to edit this subject");
			return;
		}

		/* non-PHI subject information: Edit permission (data or PHI) on any of the subject's projects */
		$cols = array("gender = ?", "guid = ?");
		$params = array($gender, $guid);

		/* PHI/demographics: only with Edit PHI. Otherwise the existing values are left unchanged */
		if ($sp['modifyphi']) {
			$cols[] = "name = ?";           $params[] = "$lastname^$firstname";
			$cols[] = "birthdate = ?";      $params[] = $dob;
			$cols[] = "ethnicity1 = ?";     $params[] = $ethnicity1;
			$cols[] = "ethnicity2 = ?";     $params[] = $ethnicity2;
			$cols[] = "handedness = ?";     $params[] = $handedness;
			$cols[] = "education = ?";      $params[] = $education;
			$cols[] = "phone1 = ?";         $params[] = $phone;
			$cols[] = "email = ?";          $params[] = $email;
			$cols[] = "marital_status = ?"; $params[] = $maritalstatus;
			$cols[] = "cancontact = ?";     $params[] = GetMySQLTinyInt($cancontact);
		}
		$params[] = $id;

		$sqlstring = "update subjects set " . implode(', ', $cols) . " where subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$types = str_repeat('s', count($params) - 1) . 'i';
		mysqli_stmt_bind_param($stmt, $types, ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		mysqli_stmt_close($stmt);

		/* update the tags */
		SetTags('subject', $id, $tags);

		/* get this subject's enrollments, to check permissions on the per-enrollment alternate UIDs */
		$enrollmentprojects = array();
		$sqlstring = "select enrollment_id, project_id from enrollment where subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$enrollmentprojects[$row['enrollment_id']] = $row['project_id'];
		}
		mysqli_stmt_close($stmt);

		StartSQLTransaction();

		/* replace the alternate UIDs for each list the user is allowed to edit. The "all projects" list
		   (key 0) needs subject edit permission, a per-enrollment list needs Edit Data on that project */
		if (is_array($altuids)) {
			foreach ($altuids as $enrollmentid => $altuidlist) {
				$enrollmentid = (int)$enrollmentid;
				if ($enrollmentid > 0) {
					if (!isset($enrollmentprojects[$enrollmentid])) continue;
					if (!GetPerm($sp['projects'], 'modifydata', $enrollmentprojects[$enrollmentid])) continue;

					$sqlstring = "delete from subject_altuid where subject_id = ? and enrollment_id = ?";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'ii', $id, $enrollmentid);
					MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id, $enrollmentid]);
					mysqli_stmt_close($stmt);
				}
				else {
					$sqlstring = "delete from subject_altuid where subject_id = ? and (enrollment_id = 0 or enrollment_id is null)";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'i', $id);
					MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
					mysqli_stmt_close($stmt);
				}

				foreach (explode(',', (string)$altuidlist) as $altuid) {
					$altuid = trim($altuid);
					if ($altuid == "") continue;

					/* an asterisk marks the primary ID */
					$isprimary = 0;
					if (strpos($altuid, '*') !== false) {
						$altuid = str_replace('*', '', $altuid);
						$isprimary = 1;
					}
					$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, isprimary, enrollment_id) values (?, ?, ?, ?)";
					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					mysqli_stmt_bind_param($stmt, 'isii', $id, $altuid, $isprimary, $enrollmentid);
					MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id, $altuid, $isprimary, $enrollmentid]);
					mysqli_stmt_close($stmt);
				}
			}
		}

		CommitSQLTransaction();

		Notice(htmlspecialchars($uid) . " updated");
	}


	/* -------------------------------------------- */
	/* ------- AddSubject ------------------------- */
	/* -------------------------------------------- */
	function AddSubject($lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email, $maritalstatus, $smokingstatus, $cancontact, $tags, $altuid, $guid) {
	
		if ($GLOBALS['debug']) {
			print "$lastname $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email, $maritalstatus, $smokingstatus, $cancontact, $altuid, $guid";
		}
		$name = "$lastname^$firstname";
		$cancontact = GetMySQLTinyInt($cancontact);
		$altuids = explode(',', (string)$altuid);

		# create a new uid
		do {
			$uid = NIDB\CreateUID('S',3);
			$sqlstring = "select subject_id from subjects where uid = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $uid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$uid]);
			$count = mysqli_num_rows($result);
			mysqli_stmt_close($stmt);
		} while ($count > 0);
		
		# create a new family uid
		do {
			$familyuid = NIDB\CreateUID('F');
			$sqlstring = "select family_id from families where family_uid = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 's', $familyuid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$familyuid]);
			$count = mysqli_num_rows($result);
			mysqli_stmt_close($stmt);
		} while ($count > 0);
		
		/* insert the new subject */
		$sqlstring = "insert into subjects (name, birthdate, gender, ethnicity1, ethnicity2, handedness, education, phone1, email, marital_status, smoking_status, uid, uuid, guid, cancontact) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, uuid(), ?, ?)";
		$params = [$name, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email, $maritalstatus, $smokingstatus, $uid, $guid, $cancontact];
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'sssssssssssssi', ...$params);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
		$SubjectRowID = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		
		# create familyRowID if it doesn't exist
		$familyname = "Proband-$uid";
		$sqlstring = "insert into families (family_uid, family_createdate, family_name) values (?, now(), ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ss', $familyuid, $familyname);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$familyuid, $familyname]);
		$familyRowID = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
	
		$sqlstring = "insert into family_members (family_id, subject_id, fm_createdate) values (?, ?, now())";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $familyRowID, $SubjectRowID);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$familyRowID, $SubjectRowID]);
		mysqli_stmt_close($stmt);
		
		SetTags('subject', $SubjectRowID, $tags);
		
		foreach ($altuids as $altuid) {
			$altuid = trim($altuid);
			if ($altuid == "") continue;
			$sqlstring = "insert ignore into subject_altuid (subject_id, altuid, enrollment_id) values (?, ?, 0)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'is', $SubjectRowID, $altuid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$SubjectRowID, $altuid]);
			mysqli_stmt_close($stmt);
		}

		Notice(htmlspecialchars("$firstname $lastname") . " added $uid");
		
		return $SubjectRowID;
	}

	
	/* -------------------------------------------- */
	/* ------- AddRelation ------------------------ */
	/* -------------------------------------------- */
	function AddRelation($id, $uid2, $relation, $makesymmetric) {
		if (!ValidID($id,'Subject ID')) { return; }
		$id = (int)$id;

		$sp = GetSubjectPermissions($id);
		if (!$sp['canedit']) {
			Error("You do not have permission to edit this subject's family relations");
			return;
		}

		/* valid relations and their symmetric counterpart */
		$symrelations = array("siblingf" => "siblingf", "siblingm" => "siblingm", "sibling" => "sibling", "parent" => "child", "child" => "parent");
		if (!array_key_exists($relation, $symrelations)) {
			Error("Invalid relation");
			return;
		}

		/* get the row id from the UID for subject 2 */
		$sqlstring = "select subject_id from subjects where uid = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 's', $uid2);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$uid2]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$id2 = (int)($row['subject_id'] ?? 0);
		
		if ($id2 == 0) {
			Notice("Subject " . htmlspecialchars($uid2) . " could not be found");
		}
		elseif ($id == $id2) {
			Notice("Subject cannot be related to him/herself");
		}
		elseif (!GetSubjectPermissions($id2)['canedit']) {
			/* the relation is added to both subjects, so the user needs edit permission on both */
			Error("You do not have permission to edit subject " . htmlspecialchars($uid2));
		}
		else {
			/* insert the primary relation */
			$sqlstring = "insert into subject_relation (subjectid1, subjectid2, relation) values (?, ?, ?)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'iis', $id, $id2, $relation);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id, $id2, $relation]);
			mysqli_stmt_close($stmt);
			
			if ($makesymmetric) {
				/* insert the corresponding relation */
				$symrelation = $symrelations[$relation];
				$sqlstring = "insert into subject_relation (subjectid1, subjectid2, relation) values (?, ?, ?)";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'iis', $id2, $id, $symrelation);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id2, $id, $symrelation]);
				mysqli_stmt_close($stmt);
			}
			Notice("Relation added");
		}
	}

	
	/* -------------------------------------------- */
	/* ------- CreateNewStudy --------------------- */
	/* -------------------------------------------- */
	function CreateNewStudy($modality, $enrollmentid, $id) {
		$enrollmentid = (int)$enrollmentid;
		$projectid = CanCreateStudy($enrollmentid, $id);
		if ($projectid == 0) { return; }

		/* insert a new row into the studies table. parsedicom or the user will populate the info later */
		$study_num = GetNextStudyNum($id);
		$desc = "New $modality study";
		$sqlstring = "insert into studies (enrollment_id, study_num, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_status) values (?, ?, ?, now(), ?, '', '', '', 'pending')";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iiss', $enrollmentid, $study_num, $modality, $desc);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentid, $study_num, $modality, $desc]);
		$studyRowID = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		
		StudyCreatedNotice($id, $projectid, $study_num, $studyRowID);
	}	

	
	/* -------------------------------------------- */
	/* ------- CreateStudyFromTemplate ------------ */
	/* -------------------------------------------- */
	function CreateStudyFromTemplate($modality, $enrollmentid, $id, $templateid) {
		$enrollmentid = (int)$enrollmentid;
		$templateid = (int)$templateid;
		$projectid = CanCreateStudy($enrollmentid, $id);
		if ($projectid == 0) { return; }

		/* the template must belong to the enrollment's project */
		$sqlstring = "select template_modality, template_name, template_visitlabel from study_template where studytemplate_id = ? and project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $templateid, $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$templateid, $projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			Error("Invalid template ID [$templateid]");
			return;
		}
		$templatemodality = strtolower($row['template_modality']);
		$templatevisit = ($row['template_visitlabel'] == "") ? $row['template_name'] : $row['template_visitlabel'];

		/* the modality is used as part of a table name */
		if (!IsNiDBModality($templatemodality)) {
			Error("Template has an invalid modality [" . htmlspecialchars($templatemodality) . "]");
			return;
		}

		/* get the protocol names for this template */
		$itemprotocols = array();
		$sqlstring = "select item_protocol from study_templateitems where studytemplate_id = ? order by item_order";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $templateid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$templateid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$itemprotocols[] = $row['item_protocol'];
		}
		mysqli_stmt_close($stmt);

		/* insert a new row into the studies table. parsedicom or the user will populate the info later */
		$study_num = GetNextStudyNum($id);
		$studymodality = strtoupper($templatemodality);
		$sqlstring = "insert into studies (enrollment_id, study_num, study_modality, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_status, study_type) values (?, ?, ?, now(), '', '', '', '', 'pending', ?)";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'iiss', $enrollmentid, $study_num, $studymodality, $templatevisit);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentid, $study_num, $studymodality, $templatevisit]);
		$studyRowID = mysqli_insert_id($GLOBALS['linki']);
		mysqli_stmt_close($stmt);
		
		/* create the series */
		$i = 0;
		foreach ($itemprotocols as $protocol) {
			$i++;
			$sqlstring = "insert into `" . $templatemodality . "_series` (study_id, series_num, series_datetime, series_protocol) values (?, ?, now(), ?)";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'iis', $studyRowID, $i, $protocol);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyRowID, $i, $protocol]);
			mysqli_stmt_close($stmt);
		}
		
		StudyCreatedNotice($id, $projectid, $study_num, $studyRowID);
	}	


	/* -------------------------------------------- */
	/* ------- CreateStudyGroupFromTemplate ------- */
	/* -------------------------------------------- */
	function CreateStudyGroupFromTemplate($modality, $enrollmentid, $subjectid, $grouptemplateid) {
		$enrollmentid = (int)$enrollmentid;
		$grouptemplateid = (int)$grouptemplateid;
		$projectid = CanCreateStudy($enrollmentid, $subjectid);
		if ($projectid == 0) { return; }

		/* the group template must belong to the enrollment's project */
		$sqlstring = "select projecttemplate_id from project_template where projecttemplate_id = ? and project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $grouptemplateid, $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$grouptemplateid, $projectid]);
		$found = (mysqli_num_rows($result) > 0);
		mysqli_stmt_close($stmt);
		if (!$found) {
			Error("Invalid group template ID [$grouptemplateid]");
			return;
		}
		
		/* get the list of study templates */
		$templates = array();
		$sqlstring = "select * from project_templatestudies where pt_id = ? order by pts_order asc";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $grouptemplateid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$grouptemplateid]);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ptsid = (int)$row['pts_id'];

			$items = array();
			$sqlstringA = "select ptsitem_protocol from project_templatestudyitems where pts_id = ? order by ptsitem_order asc";
			$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
			mysqli_stmt_bind_param($stmtA, 'i', $ptsid);
			$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$ptsid]);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$items[] = $rowA['ptsitem_protocol'];
			}
			mysqli_stmt_close($stmtA);
			
			$templates[] = array('modality' => $row['pts_modality'], 'visittype' => $row['pts_visittype'], 'series' => $items, 'desc' => $row['pts_desc'], 'operator' => $row['pts_operator'], 'physician' => $row['pts_physician'], 'site' => $row['pts_site'], 'notes' => $row['pts_notes']);
		}
		mysqli_stmt_close($stmt);

		/* start a transaction */
		StartSQLTransaction();
		
		$numcreated = 0;
		foreach ($templates as $study) {
			$modality = strtolower(trim($study['modality']));

			/* the modality is used as part of a table name */
			if (!IsNiDBModality($modality)) {
				echo "Modality was not valid [" . htmlspecialchars($modality) . "]<br>";
				continue;
			}
			
			$visit = strtolower(trim($study['visittype']));
			$desc = trim($study['desc']);
			$operator = trim($study['operator']);
			$physician = trim($study['physician']);
			$site = trim($study['site']);
			$notes = trim($study['notes']);
			$studymodality = strtoupper($modality);
			$username = $_SESSION['username'];
			
			$studynum = GetNextStudyNum($subjectid);
			$sqlstring = "insert into studies (enrollment_id, study_num, study_modality, study_type, study_datetime, study_desc, study_operator, study_performingphysician, study_site, study_notes, study_status, study_createdby, study_createdate) values (?, ?, ?, ?, now(), ?, ?, ?, ?, ?, 'complete', ?, now())";
			$params = [$enrollmentid, $studynum, $studymodality, $visit, $desc, $operator, $physician, $site, $notes, $username];
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'iissssssss', ...$params);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
			$studyRowID = mysqli_insert_id($GLOBALS['linki']);
			mysqli_stmt_close($stmt);
		
			/* create the series */
			$seriesnum = 1;
			foreach ($study['series'] as $series) {
				$series = trim($series);
				$sqlstring = "insert into `" . $modality . "_series` (study_id, series_num, series_datetime, series_protocol) values (?, ?, now(), ?)";
				$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
				mysqli_stmt_bind_param($stmt, 'iis', $studyRowID, $seriesnum, $series);
				MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$studyRowID, $seriesnum, $series]);
				mysqli_stmt_close($stmt);
				$seriesnum++;
			}
			$numcreated++;
		}
		
		/* commit a transaction */
		CommitSQLTransaction();
		
		Notice("$numcreated studies created from the group template");
	}	
	
	
	/* -------------------------------------------- */
	/* ------- EnrollSubject ---------------------- */
	/* -------------------------------------------- */
	function EnrollSubject($subjectid, $projectid) {
		if (!ValidID($subjectid,'Subject ID')) { return; }
		$subjectid = (int)$subjectid;
		$projectid = (int)$projectid;
		if ($projectid == 0) {
			Error("Project not specified");
			return;
		}

		$perms = GetCurrentUserProjectPermissions(array($projectid));
		if (!GetPerm($perms, 'modifydata', $projectid)) {
			Error("You do not have Edit Data permission in the selected project");
			return;
		}

		/* enrolling a subject gives the project's users access to it, so a subject already enrolled in
		   other projects may only be enrolled by a user who already has access to it */
		$sp = GetSubjectPermissions($subjectid);
		if ((count($sp['projectids']) > 0) && (!$sp['hasaccess'])) {
			Error("You do not have permission to access this subject");
			return;
		}

		$sqlstring = "select project_name from projects where project_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $projectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$projectname = htmlspecialchars($row['project_name'] ?? '');
		
		$sqlstring = "select enrollment_id from enrollment where project_id = ? and subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $projectid, $subjectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $subjectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values (?, ?, now())";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $projectid, $subjectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid, $subjectid]);
			mysqli_stmt_close($stmt);
			
			Notice("Subject enrolled in <b>$projectname</b>");
		}
		else {
			$enrollmentid = (int)$row['enrollment_id'];
			$sqlstring = "update enrollment set enroll_enddate = '0000-00-00 00:00:00' where enrollment_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentid]);
			mysqli_stmt_close($stmt);
			
			Notice("Subject re-enrolled in <b>$projectname</b>");
		}
	}


	/* -------------------------------------------- */
	/* ------- ChangeProject ---------------------- */
	/* -------------------------------------------- */
	/* this function moves studies from enrollment
		in one project to enrollment in another
		all within a subject, not across subjects.
		Requires project admin on the current project
		and Edit Data on the new project
	   -------------------------------------------- */
	function ChangeProject($subjectid, $enrollmentid, $newprojectid) {
		$subjectid = (int)$subjectid;
		$enrollmentid = (int)$enrollmentid;
		$newprojectid = (int)$newprojectid;

		$enrollment = GetEnrollment($enrollmentid);
		if (($enrollment == null) || ($enrollment['subject_id'] != $subjectid)) {
			Error("Invalid enrollment");
			return;
		}
		$oldprojectid = (int)$enrollment['project_id'];
		if (($newprojectid == 0) || ($newprojectid == $oldprojectid)) {
			Error("Invalid new project");
			return;
		}

		$perms = GetCurrentUserProjectPermissions(array($oldprojectid, $newprojectid));
		if (!GetPerm($perms, 'projectadmin', $oldprojectid)) {
			Error("You must be a project admin of the current project to move this subject");
			return;
		}
		if (!GetPerm($perms, 'modifydata', $newprojectid)) {
			Error("You do not have Edit Data permission in the new project");
			return;
		}
	
		?>
		<ol>
		<?
		echo "<li>Checking if enrollment in new project already exists";
		$sqlstring = "select enrollment_id from enrollment where project_id = ? and subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $newprojectid, $subjectid);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newprojectid, $subjectid]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			/* un-enroll from previous project */
			echo "<li>Ending enrollment in current project";
			$sqlstring = "update enrollment set enroll_enddate = now() where enrollment_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentid]);
			mysqli_stmt_close($stmt);
			
			/* enroll in new project */
			echo "<li>Creating enrollment in new project";
			$sqlstring = "insert into enrollment (project_id, subject_id, enroll_startdate) values (?, ?, now())";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'ii', $newprojectid, $subjectid);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$newprojectid, $subjectid]);
			$new_spid = mysqli_insert_id($GLOBALS['linki']);
			mysqli_stmt_close($stmt);
			$msg = "Subject moved to new project";
		}
		else {
			$new_spid = (int)$row['enrollment_id'];
			$msg = "Subject already enrolled in this project. Studies moved to new project";
		}

		/* change all old enrollmentids to the new id in the studies table */
		echo "<li>Moving existing studies to the new enrollment";
		$sqlstring = "update studies set enrollment_id = ? where enrollment_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'ii', $new_spid, $enrollmentid);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$new_spid, $enrollmentid]);
		mysqli_stmt_close($stmt);
		?></ol><?

		Notice($msg);
	}
	
	
	/* -------------------------------------------- */
	/* ------- Delete ----------------------------- */
	/* -------------------------------------------- */
	function Delete($id) {
		if (!ValidID($id,'Subject ID')) { return; }
		if (!IsAdminUser()) {
			Error("Only admins can delete subjects");
			return;
		}

		$id = (int)$id;
		$sqlstring = "update subjects set isactive = 0 where subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);

		Notice("Subject deleted (marked as inactive)");
	}

	
	/* -------------------------------------------- */
	/* ------- UnDelete --------------------------- */
	/* -------------------------------------------- */
	function UnDelete($id) {
		if (!ValidID($id,'Subject ID')) { return; }
		if (!IsAdminUser()) {
			Error("Only admins can undelete subjects");
			return;
		}
		
		$id = (int)$id;
		$sqlstring = "update subjects set isactive = 1 where subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		mysqli_stmt_close($stmt);

		Notice("Subject undeleted (marked as active)");
	}
	

	/* -------------------------------------------- */
	/* ------- Obliterate ------------------------- */
	/* -------------------------------------------- */
	function Obliterate($ids) {
		if (!($GLOBALS['issiteadmin'] ?? false)) {
			Error("Only site admins can obliterate subjects");
			return;
		}
		if (!is_array($ids)) {
			$ids = array();
		}

		/* delete all information about this subject from the database */
		$username = $_SESSION['username'];
		$sqlstring = "insert into fileio_requests (fileio_operation, data_type, data_id, username, requestdate) values ('delete', 'subject', ?, ?, now())";
		foreach ($ids as $id) {
			$id = (int)$id;
			if ($id < 1) continue;
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'is', $id, $username);
			MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id, $username]);
			mysqli_stmt_close($stmt);
		}

		Notice("Subject(s) queued for obliteration");
	}
	
	
	/* -------------------------------------------- */
	/* ------- DeleteConfirm ---------------------- */
	/* -------------------------------------------- */
	function DeleteConfirm($id) {
		if (!ValidID($id,'Subject ID')) { return; }
		if (!IsAdminUser()) {
			Error("Only admins can delete subjects");
			return;
		}
		$id = (int)$id;
		
		/* get all existing info about this subject */
		$sqlstring = "select * from subjects where subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			Error("Subject not found");
			return;
		}
		$name = $row['name'];
		$dob = $row['birthdate'];
		$gender = $row['gender'];
		$ethnicity1 = $row['ethnicity1'];
		$ethnicity2 = $row['ethnicity2'];
		$handedness = $row['handedness'];
		$education = $row['education'];
		$phone1 = $row['phone1'];
		$email = $row['email'];
		$maritalstatus = $row['marital_status'];
		$smokingstatus = $row['smoking_status'];
		$uid = $row['uid'];
		$guid = $row['guid'];
		$cancontact = $row['cancontact'];

		$tags = GetTags('subject', $id);
		$altuids = GetAlternateUIDs($id,0);
		
		$nameparts = explode("^", $name);
		$lastname  = $nameparts[0] ?? '';
		$firstname = $nameparts[1] ?? '';
		$lname = $lastname; $fname = $firstname;
		$name = strtoupper(substr($fname,0,1)) . strtoupper(substr($lname,0,1));

		?>

		<div class="ui text container">
			<div class="ui inverted red segment">
				<h2 class="ui header">
					<i class="exclamation circle icon"></i>
					<div class="content">
						Are you absolutely sure you want to delete this subject?
						<div class="sub header">
							This will delete all of the subject demographics and studies listed below
						</div>
					</div>
				</h2>
			</div>
				
			<div class="ui center aligned blue raised top attached segment"><span style="font-size:24pt; font-weight: bold"><?=htmlspecialchars($uid)?></span></div>
			
			<div class="ui attached raised segment">
				<h3 class="ui header">Demographics</h3>
				
				<table class="ui very compact very simple table">
					<tr>
						<td>Subject initials</td>
						<td><?=htmlspecialchars($name)?></td>
					</tr>
					<tr>
						<td>Alternate UID 1</td>
						<td><?=htmlspecialchars(implode2(', ',$altuids))?></td>
					</tr>
					<tr>
						<td>Date of birth</td>
						<td><span <? if (!ValidDOB($dob)) { echo "class='invalid' title='Invalid birthdate'"; } ?> ><?=htmlspecialchars($dob ?? '')?></span></td>
					</tr>
					<tr>
						<td>Gender</td>
						<td><?=htmlspecialchars($gender ?? '')?></td>
					</tr>
					<tr>
						<td>Ethnicity1&2</td>
						<td><?=htmlspecialchars($ethnicity1 ?? '')?>, <?=htmlspecialchars($ethnicity2 ?? '')?></td>
					</tr>
					<tr>
						<td>Handedness</td>
						<td><?=htmlspecialchars($handedness ?? '')?></td>
					</tr>
					<tr>
						<td>Education</td>
						<td><?=htmlspecialchars($education ?? '')?></td>
					</tr>
					<tr>
						<td>Phone</td>
						<td><?=htmlspecialchars($phone1 ?? '')?></td>
					</tr>
					<tr>
						<td>E-mail</td>
						<td><?=htmlspecialchars($email ?? '')?></td>
					</tr>
					<tr>
						<td>Marital Status</td>
						<td><?=htmlspecialchars($maritalstatus ?? '')?></td>
					</tr>
					<tr>
						<td>Smoking Status</td>
						<td><?=htmlspecialchars($smokingstatus ?? '')?></td>
					</tr>
					<tr>
						<td>GUID</td>
						<td><?=htmlspecialchars($guid ?? '')?></td>
					</tr>
					<tr>
						<td>Can contact?</td>
						<td><?=htmlspecialchars($cancontact ?? '')?></td>
					</tr>
					<tr>
						<td>Tags</td>
						<td><?=htmlspecialchars(implode2(', ',$tags))?></td>
					</tr>
				</table>
			</div>
			
			<div class="ui bottom attached raised blue segment">
				<h3 class="ui header">Enrollments</h3>
		<?
			$sqlstring = "select a.*, b.*, date(enroll_startdate) 'enroll_startdate', date(enroll_enddate) 'enroll_enddate' from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			mysqli_stmt_close($stmt);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$enrollmentid = (int)$row['enrollment_id'];
				$enroll_startdate = $row['enroll_startdate'];
				$enroll_enddate = $row['enroll_enddate'];
				$project_name = $row['project_name'];
				$costcenter = $row['project_costcenter'];
				$project_enddate = $row['project_enddate'];
				?>
				<div class="ui gray segment">
					<?=htmlspecialchars($project_name ?? '')?> (<?=htmlspecialchars($costcenter ?? '')?>)<br><br>
					Enroll date: <?=$enroll_startdate?><br>
					Un-enroll date: <?=$enroll_enddate?><br>
					Project end date: <?=$project_enddate;?>
					Imaging Studies
					<table class="ui small very compact basic table">
						<thead>
							<th>#</th>
							<th>Modality</th>
							<th>Date</th>
							<th>Physician</th>
							<th>Operator</th>
							<th>Site</th>
							<th>Status</th>
							<th>Study ID</th>
						</thead>
						<tbody>
						<?
						$sqlstring2 = "select * from studies where enrollment_id = ?";
						$stmt2 = mysqli_prepare($GLOBALS['linki'], $sqlstring2);
						mysqli_stmt_bind_param($stmt2, 'i', $enrollmentid);
						$result2 = MySQLiBoundQuery($stmt2, __FILE__, __LINE__, $sqlstring2, [$enrollmentid]);
						mysqli_stmt_close($stmt2);
						if (mysqli_num_rows($result2) > 0) {
							while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
								?>
								<tr>
									<td><?=$row2['study_num']?></td>
									<td><?=htmlspecialchars($row2['study_modality'] ?? '')?></td>
									<td><?=$row2['study_datetime']?></td>
									<td><?=htmlspecialchars($row2['study_performingphysician'] ?? '')?></td>
									<td><?=htmlspecialchars($row2['study_operator'] ?? '')?></td>
									<td><?=htmlspecialchars($row2['study_site'] ?? '')?></td>
									<td><?=htmlspecialchars($row2['study_status'] ?? '')?></td>
									<td><tt><?=htmlspecialchars($uid)?><?=$row2['study_num']?></tt></td>
								</tr>
								<?
							}
						}
						else {
							?>
							<tr>
								<td align="center">
									None
								</td>
							</tr>
							<?
						}
						?>
					</table>
				</div>
			<?
			}
			?>
			</div>
			
			<br>
			<div class="ui two column grid">

				<div class="ui column">
					<a href="subjects.php?id=<?=$id?>" class="ui button">Cancel</a>
				</div>
				
				<div class="ui right aligned column">
					<form method="post" action="subjects.php">
						<input type="hidden" name="action" value="delete">
						<input type="hidden" name="id" value="<?=$id?>">
						<input type="submit" class="ui red button" value="Yes, delete it">
					</form>
				</div>
			</div>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- Confirm ---------------------------- */
	/* -------------------------------------------- */
	/* $altuids is keyed by enrollment ID (0 = all projects). $enrollmentids is no longer used */
	function Confirm($type, $id, $encrypt, $lastname, $firstname, $dob, $gender, $ethnicity1, $ethnicity2, $handedness, $education, $phone, $email, $maritalstatus, $smokingstatus, $cancontact, $tags, $uid, $altuids, $enrollmentids, $guid) {

		if (!is_array($altuids)) {
			$altuids = array(0 => (string)$altuids);
		}

		/* PHI fields are only submitted when adding, or updating with Edit PHI */
		$editphi = true;
		if ($type == "update") {
			if (!ValidID($id,'Subject ID')) { return; }
			$sp = GetSubjectPermissions($id);
			if (!$sp['canedit']) {
				Error("You do not have permission to edit this subject");
				return;
			}
			$editphi = (bool)$sp['modifyphi'];
		}
		$unchanged = NoViewPermission("unchanged - no edit permission");
		
		$encdob = $dob;
		$encname = "";
		$encuids = array();
		if (($encrypt) && ($type != 'update')) {
			$fullname = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $lastname) . '^' . preg_replace('/[^A-Za-z0-9]/', '', $firstname));
			$encname = strtoupper(sha1($fullname));
			foreach (explode(',', $altuids[0] ?? '') as $alt) {
				$alt = preg_replace('/[^A-Za-z0-9\_\-]/', '', $alt);
				$encuids[$alt] = strtoupper(sha1($alt));
			}
			$altuids[0] = implode(',', $encuids);
			$encdob = substr($dob,0,4) . '-00-00';
		}

		/* project names for the per-enrollment alternate UID lists */
		$enrollmentnames = array();
		if (($type == "update") && (count($altuids) > 1)) {
			$sqlstring = "select a.enrollment_id, b.project_name from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			$subjectid = (int)$id;
			mysqli_stmt_bind_param($stmt, 'i', $subjectid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$subjectid]);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$enrollmentnames[$row['enrollment_id']] = $row['project_name'];
			}
			mysqli_stmt_close($stmt);
		}

		$h = function($v) { return htmlspecialchars((string)$v); };

		if ($type == "update") { ?>
		<?=$h($uid)?><br><br>
		<? } ?>
		
		<div class="ui text container">
		<table class="reviewtable">
			<? if (($encrypt) && ($type != 'update')) { ?>
			<tr>
				<td colspan="2" style="color:#444; border: orange solid 1px">This subject's information will be encrypted. <b>You will only be able to search for this subject using the bolded values below.</b> Print this page or record the UID on the following page<br><br>
					[NAME] <?=$h($firstname)?> <?=$h($lastname)?> &rarr; <b><?=$h($fullname)?></b> &rarr; <b><?=$h($encname)?></b><br>
					[DOB] <?=$h($dob)?> &rarr; <b><?=$h($encdob)?></b><br>
					<?
					$i=1;
					foreach ($encuids as $alt => $encid) {
						echo "[ALT UID $i] <b>" . $h($alt) . "</b> &rarr; <b>" . $h($encid) . "</b><br>";
						$i++;
					}
					?>
					<br>
				</td>
			</tr>
			<? } ?>
			<tr>
				<td class="label">First name</td>
				<td class="value"><?=($editphi ? $h($firstname) : $unchanged)?></td>
			</tr>
			<tr>
				<td class="label">Last name</td>
				<td class="value"><?=($editphi ? $h($lastname) : $unchanged)?></td>
			</tr>
			<tr>
				<td class="label">Date of birth</td>
				<td class="value"><? if ($editphi) { ?><span <? if (!ValidDOB($dob)) { echo "class='invalid' title='Invalid birthdate'"; } ?> ><?=$h($dob)?></span><? } else { echo $unchanged; } ?></td>
			</tr>
			<tr>
				<td class="label">Sex</td>
				<td class="value"><?=$h($gender)?></td>
			</tr>
			<tr>
				<td class="label">IDs</td>
				<td class="value">
				<?
					foreach ($altuids as $enrollmentid => $altuidlist) {
						$label = ($enrollmentid == 0) ? "All projects" : ($enrollmentnames[$enrollmentid] ?? "Enrollment $enrollmentid");
						echo $h($label) . ": <tt>" . $h($altuidlist) . "</tt><br>";
					}
				?>
				</td>
			</tr>
			<tr>
				<td class="label">Ethnicity1&2</td>
				<td class="value"><?=($editphi ? $h($ethnicity1) . ", " . $h($ethnicity2) : $unchanged)?></td>
			</tr>
			<tr>
				<td class="label">Handedness</td>
				<td class="value"><?=($editphi ? $h($handedness) : $unchanged)?></td>
			</tr>
			<tr>
				<td class="label">Education</td>
				<td class="value"><?=($editphi ? $h($education) : $unchanged)?></td>
			</tr>
			<tr>
				<td class="label">Phone</td>
				<td class="value"><?=($editphi ? $h($phone) : $unchanged)?></td>
			</tr>
			<tr>
				<td class="label">E-mail</td>
				<td class="value"><?=($editphi ? $h($email) : $unchanged)?></td>
			</tr>
			<tr>
				<td class="label">Marital Status</td>
				<td class="value"><?=($editphi ? $h($maritalstatus) : $unchanged)?></td>
			</tr>
			<tr>
				<td class="label">GUID</td>
				<td class="value"><?=$h($guid)?></td>
			</tr>
			<tr>
				<td class="label">Can contact?</td>
				<td class="value"><?=($editphi ? $h($cancontact) : $unchanged)?></td>
			</tr>
			<tr>
				<td class="label">Tags</td>
				<td class="value"><?=$h($tags)?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<br>
					<? Warning("Are you sure this subject's information is correct and not a duplicate?"); ?>
					<br><br>
				</td>
			</tr>
			<tr>
				<td align="left"><button class="ui button" OnClick="history.go(-1)">Back</button></td>
				
				<form method="post" action="subjects.php">
				<input type="hidden" name="action" value="<?=$h($type)?>">
				<input type="hidden" name="id" value="<?=$h($id)?>">
				<input type="hidden" name="encrypt" value="<?=$h($encrypt)?>">
				<? if ($editphi) { ?>
				<input type="hidden" name="lastname" value="<?=$h($lastname)?>">
				<input type="hidden" name="firstname" value="<?=$h($firstname)?>">
				<input type="hidden" name="fullname" value="<?=$h($encname)?>">
				<input type="hidden" name="dob" value="<?=$h($encdob)?>">
				<input type="hidden" name="ethnicity1" value="<?=$h($ethnicity1)?>">
				<input type="hidden" name="ethnicity2" value="<?=$h($ethnicity2)?>">
				<input type="hidden" name="handedness" value="<?=$h($handedness)?>">
				<input type="hidden" name="education" value="<?=$h($education)?>">
				<input type="hidden" name="phone" value="<?=$h($phone)?>">
				<input type="hidden" name="email" value="<?=$h($email)?>">
				<input type="hidden" name="maritalstatus" value="<?=$h($maritalstatus)?>">
				<input type="hidden" name="cancontact" value="<?=$h($cancontact)?>">
				<? } ?>
				<input type="hidden" name="gender" value="<?=$h($gender)?>">
				<input type="hidden" name="smokingstatus" value="<?=$h($smokingstatus)?>">
				<input type="hidden" name="tags" value="<?=$h($tags)?>">
				<input type="hidden" name="uid" value="<?=$h($uid)?>">
				<? foreach ($altuids as $enrollmentid => $altuidlist) { ?>
				<input type="hidden" name="altuids[<?=(int)$enrollmentid?>]" value="<?=$h($altuidlist)?>">
				<? } ?>
				<input type="hidden" name="guid" value="<?=$h($guid)?>">
				<input type="hidden" name="returnpage" value="subject">
				<td align="right"><input type="submit" class="ui primary button" value="Yes, <?=$h($type)?> it"></td>
				</form>
			</tr>
		</table>
		</div>
		<?
	}	


	/* -------------------------------------------- */
	/* ------- GetActiveProjects ------------------ */
	/* -------------------------------------------- */
	/* list of active projects [projectid => row], optionally limited to one instance */
	function GetActiveProjects($instanceid = 0) {
		$projects = array();
		$instanceid = (int)$instanceid;
		if ($instanceid > 0) {
			$sqlstring = "select project_id, project_name, project_costcenter from projects where project_status = 'active' and instance_id = ? order by project_name";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $instanceid);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$instanceid]);
			mysqli_stmt_close($stmt);
		}
		else {
			$sqlstring = "select project_id, project_name, project_costcenter from projects where project_status = 'active' order by project_name";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$projects[$row['project_id']] = $row;
		}
		return $projects;
	}


	/* -------------------------------------------- */
	/* ------- DisplayEnrollForm ------------------ */
	/* -------------------------------------------- */
	/* "Enroll in project" form. Only projects the user has Edit Data on can be selected */
	function DisplayEnrollForm($id) {
		$projects = GetActiveProjects($_SESSION['instanceid']);
		$perms = GetCurrentUserProjectPermissions(array_keys($projects));
		?>
		<form class="ui" action="subjects.php" method="post" style="margin: 0px">
		<input type="hidden" name="id" value="<?=$id?>">
		<input type="hidden" name="action" value="enroll">
		<div class="ui labeled action input">
		<label for="projectid" class="ui label grey">Enroll in Project</label>
		<select class="ui dropdown" name="projectid" required>
			<option value="">Select project...</option>
		<?
			foreach ($projects as $projectid => $project) {
				$disabled = GetPerm($perms, 'modifydata', $projectid) ? "" : "disabled";
				?>
				<option value="<?=$projectid?>" <?=$disabled?>><?=htmlspecialchars($project['project_name'])?> (<?=htmlspecialchars($project['project_costcenter'] ?? '')?>)</option>
				<?
			}
		?>
		</select>
		<button class="ui primary button" type="submit" value="Enroll">Enroll</button>
		</div>
		</form>
		<?
	}


	/* -------------------------------------------- */
	/* ------- DisplaySubject --------------------- */
	/* -------------------------------------------- */
	function DisplaySubject($id, $projectRowID) {
		if (!ValidID($id,'Subject ID')) { return; }
		$id = (int)$id;

		ShowFlashMessage();

		/* get all existing info about this subject */
		$sqlstring = "select * from subjects where subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		if (!$row) {
			Error("Subject not found");
			return;
		}
		$name = $row['name'];
		$dob = $row['birthdate'];
		$gender = $row['gender'];
		$ethnicity1 = $row['ethnicity1'];
		$ethnicity2 = $row['ethnicity2'];
		$handedness = $row['handedness'];
		$education = $row['education'];
		$uid = $row['uid'];
		$guid = $row['guid'];
		$cancontact = $row['cancontact'];
		$isactive = $row['isactive'];

		/* subject-level permissions. PHI (demographics) applies to the subject from any of its projects,
		   data permissions are per-project */
		$sp = GetSubjectPermissions($id);
		$noperm = NoViewPermission();

		/* users with no permissions on any of the subject's projects may only see that the subject exists */
		if (!$sp['hasaccess']) {
			?>
			<div class="ui text container">
				<h1 class="ui top attached header center aligned black segment" style="background-color: #ffffaa"><span class="tt"><?=htmlspecialchars($uid)?></span></h1>
				<div class="ui bottom attached segment">
					<? if (count($sp['projectids']) > 0) { ?>
					You do not have permissions on any of the projects this subject is enrolled in.
					<? } else { ?>
					This subject is not enrolled in any projects.<br><br>
					<? DisplayEnrollForm($id); ?>
					<? } ?>
				</div>
			</div>
			<?
			return;
		}

		DisplayPermissions($sp['projects']);
		$currentproject = GetCurrentSubjectProject($id, $projectRowID);
		$currentprojectid = $currentproject['projectRowID'];
		$currentprojectname = htmlspecialchars($currentproject['project_name'] ?? '');

		/* update the mostrecent table */
		UpdateMostRecent($id, '', '');

		$previoussubject = GetAdjacentSubjectInProject($id, $currentprojectid, "previous");
		$nextsubject = GetAdjacentSubjectInProject($id, $currentprojectid, "next");

		$tags = GetTags('subject', $id);
		
		/* get the family UID */
		$sqlstring = "select b.family_uid, b.family_name from family_members a left join families b on a.family_id = b.family_id where a.subject_id = ?";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $id);
		$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		mysqli_stmt_close($stmt);
		$familyuid = $row['family_uid'] ?? '';
		$familyname = $row['family_name'] ?? '';
		
		/* get list of alternate subject UIDs */
		$altuids = GetAlternateUIDs($id,0);

		$nameparts = explode("^", $name);
		$lastname  = $nameparts[0] ?? '';
		$firstname = $nameparts[1] ?? '';
		$name = strtoupper(substr($firstname,0,1)) . strtoupper(substr($lastname,0,1));

		switch ($gender) {
			case "U": $gender = "Unknown"; break;
			case "F": $gender = "Female"; break;
			case "M": $gender = "Male"; break;
			case "O": $gender = "Other"; break;
		}
					
		switch ($ethnicity1) {
			case "": $ethnicity1 = "Unknown"; break;
			case "hispanic": $ethnicity1 = "Hispanic/Latino"; break;
			case "nothispanic": $ethnicity1 = "Not hispanic/Latino"; break;
		}

		switch ($ethnicity2) {
			case "": $ethnicity2 = "Unknown"; break;
			case "indian": $ethnicity2 = "American Indian/Alaska Native"; break;
			case "asian": $ethnicity2 = "Asian"; break;
			case "black": $ethnicity2 = "Black/African American"; break;
			case "islander": $ethnicity2 = "Hawaiian/Pacific Islander"; break;
			case "white": $ethnicity2 = "White"; break;
		}
		
		switch ($handedness) {
			case "U": $handedness = "Unknown"; break;
			case "R": $handedness = "Right"; break;
			case "L": $handedness = "Left"; break;
			case "A": $handedness = "Ambidextrous"; break;
		}
		
		switch ($education) {
			case 0: $education = "Unknown"; break;
			case 1: $education = "Grade School"; break;
			case 2: $education = "Middle School"; break;
			case 3: $education = "High School/GED"; break;
			case 4: $education = "Trade School"; break;
			case 5: $education = "Associates Degree"; break;
			case 6: $education = "Bachelors Degree"; break;
			case 7: $education = "Masters Degree"; break;
			case 8: $education = "Doctoral Degree"; break;
		}

		/* PHI/demographic values are replaced by the 'no view permissions' box */
		$phi = function($value) use ($sp, $noperm) { return $sp['viewphi'] ? htmlspecialchars((string)$value) : $noperm; };

		/* display a message if this subject has been deleted */
		if (!$isactive) {
			?>
				<div class="ui text container">
					<div class="ui negative message">
						<div class="header">
						Subject Inactive
						</div>
						<p>This subject has been deleted and marked inactive</p>
					</div>
				</div>
				<br>
			<?
		}

		/* all active projects and the user's permissions on them, for the "move to project" lists */
		$activeprojects = GetActiveProjects();
		$activeprojectperms = GetCurrentUserProjectPermissions(array_keys($activeprojects));

		/* modalities that have a series table, for counting series */
		$seriestables = array();
		$result = MySQLiQuery("select table_name 'table_name' from information_schema.tables where table_schema = database() and table_name like '%\_series'", __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$seriestables[strtolower($row['table_name'])] = 1;
		}
		
		?>

		<div class="ui grid">
			<div class="four wide column">
				<h1 class="ui top attached header center aligned black segment" style="background-color: #ffffaa">
					<div class="ui three column grid">
						<div class="left aligned column">
							<? if ($previoussubject) { ?>
							<a class="ui compact basic yellow icon button" href="subjects.php?id=<?=$previoussubject['subjectRowID']?>&projectid=<?=$currentprojectid?>" title="Previous subject <?=htmlspecialchars($previoussubject['uid'])?> in <?=$currentprojectname?>"><i class="chevron left icon"></i></a>
							<? } else { ?>
							<div class="ui compact basic yellow disabled icon button" title="No previous subject in <?=$currentprojectname?>"><i class="chevron left icon"></i></div>
							<? } ?>
						</div>
						<div class="center aligned column">
							<span class="tt"><?=htmlspecialchars($uid)?></span>
						</div>
						<div class="right aligned column">
							<? if ($nextsubject) { ?>
							<a class="ui compact basic yellow icon button" href="subjects.php?id=<?=$nextsubject['subjectRowID']?>&projectid=<?=$currentprojectid?>" title="Next subject <?=htmlspecialchars($nextsubject['uid'])?> in <?=$currentprojectname?>"><i class="chevron right icon"></i></a>
							<? } else { ?>
							<div class="ui compact basic yellow disabled icon button" title="No next subject in <?=$currentprojectname?>"><i class="chevron right icon"></i></div>
							<? } ?>
						</div>
					</div>
				</h1>
				<div class="ui bottom attached styled segment">
					<div class="ui accordion">
							
						<div class="active title">
							<h3 class="ui header"><i class="dropdown icon"></i>Demographics</h3>
						</div>
						<div class="active content">
							<table class="ui very basic celled collapsing very compact table">
								<tr>
									<td class="right aligned"><b>Subject initials</b></td>
									<td><?=$phi($name)?></td>
								</tr>
								<tr>
									<td class="right aligned"><b>Date of birth</b></td>
									<td><? if ($sp['viewphi']) { ?><span <? if (!ValidDOB($dob)) { echo "class='invalid' title='Invalid birthdate'"; } ?> ><?=htmlspecialchars($dob ?? '')?></span><? } else { echo $noperm; } ?></td>
								</tr>
								<tr>
									<td class="right aligned"><b>Sex</b></td>
									<td><?=htmlspecialchars($gender ?? '')?></td>
								</tr>
								<tr>
									<td class="right aligned"><b style="white-space:nowrap;">Alternate UIDs</b></td>
									<td class="value tt">
									<?
										foreach ($altuids as $altid) {
											if (strlen($altid) > 20) {
												echo "<span title='" . htmlspecialchars($altid, ENT_QUOTES) . "'>" . htmlspecialchars(substr($altid,0,20)) . "...</span> ";
											}
											else {
												echo htmlspecialchars($altid) . " ";
											}
										}
									?>
									</td>
								</tr>
								<tr>
									<td class="right aligned"><b>Ethnicity 1,2</b> </td>
									<td><?=($sp['viewphi'] ? htmlspecialchars("$ethnicity1, $ethnicity2") : $noperm)?></td>
								</tr>
								<tr>
									<td class="right aligned"><b>Handedness</b></td>
									<td><?=$phi($handedness)?></td>
								</tr>
								<tr>
									<td class="right aligned"><b>Education</b></td>
									<td><?=$phi($education)?></td>
								</tr>
								<tr>
									<td class="right aligned"><b>GUID</b></td>
									<td><?=htmlspecialchars($guid ?? '')?></td>
								</tr>
								<tr>
									<td class="right aligned"><b>Can contact?</b></td>
									<td><?=$phi($cancontact)?></td>
								</tr>
								<tr>
									<td class="right aligned"><b>Subject tags</b></td>
									<td><?=DisplayTags($tags, 'subject')?></td>
								</tr>
							</table>
							<? if ($sp['canedit']) { ?>
							<button class="ui primary button" onClick="window.location.href='subjects.php?action=editform&id=<?=$id?>'; return false;" style="width: 200px"> <i class="edit icon"></i>Edit subject</button>
							<? } ?>
						</div>
					
						<!--<a href="packages.php?action=addobject&objecttype=subject&objectids[]=<?=$id?>" class="ui basic brown button" style="width: 200px"><img src="images/squirrel-icon-64.png" height="15"></img> &nbsp; Add to Package</a>-->

						<div class="title">
							<h3 class="ui header"><i class="dropdown icon"></i>Family</h3>
						</div>
						<div class="content">
							<table class="ui very basic very compact table">
							<?
								/* display existing subject relations */
								$sqlstring = "select a.*, b.uid from subject_relation a left join subjects b on a.subjectid2 = b.subject_id where a.subjectid1 = ?";
								$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
								mysqli_stmt_bind_param($stmt, 'i', $id);
								$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
								mysqli_stmt_close($stmt);
								while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
									$subjectid2 = (int)$row['subjectid2'];
									$relation = $row['relation'];
									$uid2 = $row['uid'];
									
									switch ($relation) {
										case "siblingf": $relation = "half-sibling (same father)"; break;
										case "siblingm": $relation = "half-sibling (same mother)"; break;
										case "sibling": $relation = "sibling"; break;
										case "parent": $relation = "parent"; break;
										case "child": $relation = "child"; break;
									}
									
									?>
									<tr>
										<td><?=htmlspecialchars($uid)?> is the <b><?=htmlspecialchars($relation)?></b> of <a href="subjects.php?id=<?=$subjectid2?>"><?=htmlspecialchars($uid2 ?? '')?></a></td>
									</tr>
									<?
								}
							?>
							</table>
							<? if ($sp['canedit']) { ?>
								<form action="subjects.php" method="post">
									<input type="hidden" name="id" value="<?=$id?>">
									<input type="hidden" name="action" value="addrelation">
									<input type="hidden" name="makesymmetric" value="1">
									<?=htmlspecialchars($uid)?> is the
									<select class="ui selection dropdown" name="relation" id="relation">
										<option value="siblingm">Half-sibling (same mother)</option>
										<option value="siblingf">Half-sibling (same father)</option>
										<option value="sibling">Sibling</option>
										<option value="parent">Parent</option>
										<option value="child">Child</option>
									</select> &hellip;
									<br>
									&hellip; of
									<div class="ui input">
										<input type="text" size="10" name="uid2" id="uid2" placeholder="UID">
									</div>
									<button class="ui button" type="submit" value="Enroll">Add relation</button>
									<br>
								</form>
							<? } ?>
							<table class="ui very basic celled collapsing very compact table">
								<tr>
									<td class="right aligned"><b>Family UID</b></td>
									<td class="value"><?=htmlspecialchars($familyuid)?></td>
								</tr>
								<tr>
									<td class="right aligned">Family name</td>
									<td class="value"><?=htmlspecialchars($familyname)?></td>
								</tr>
							</table>
						</div>
						<div class="title">
							<h3 class="ui header"><i class="dropdown icon"></i>Admin Operations</h3>
						</div>
						<div class="content">
							<div style="padding:5px; font-size:11pt">
							<?
								$hasadminops = false;
								if ($sp['modifyphi']) {
									$hasadminops = true;
									?>
									<button class="ui primary button" onClick="window.location.href='merge.php?action=mergesubjectform&subjectuid=<?=urlencode($uid)?>'; return false;">Merge with...</button>
									<br><br><br>
									<?
								}
								if (IsAdminUser()) {
									$hasadminops = true;
									if ($isactive) {
									?>
										<div class="ui red button" onclick="$('#deleteSubjectModal').modal('show')">Delete</div>

										<div class="ui modal" id="deleteSubjectModal">
											<div class="header">Deleting is not recommended</div>
											<div class="content">
												<p>NiDB does not delete subjects, they are instead marked as inactive. Deleted/inactive subjects can cause problems later when matching IDs. It is recommended that you edit an existing subject rather than deleting.</p>
												<p>Are you absolutely sure you want to delete/inactivate this subject?</p>
											</div>
											<div class="actions">
												<div class="ui cancel button">Cancel</div>
												<a class="ui red button" href="subjects.php?action=deleteconfirm&id=<?=$id?>">Yes, delete it</a>
											</div>
										</div>
									<? } else { ?>
										<form method="post" action="subjects.php" style="display: inline" onsubmit="return confirm('Are you sure you want to undelete this subject?')">
											<input type="hidden" name="action" value="undelete">
											<input type="hidden" name="id" value="<?=$id?>">
											<button class="ui red button" type="submit">Undelete</button>
										</form>
									<?
									}
								}
								if (!$hasadminops) {
									?><span style="color: gray">No admin operations available</span><?
								}
							?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="twelve wide column">
				<div class="ui top attached styled secondary black segment">
					<div class="ui two column grid">
						<div class="column header">
							<h2 class="ui header">Enrollments</h2>
						</div>
						<div class="right aligned column">
							<? DisplayEnrollForm($id); ?>
						</div>
					</div>
				</div>

				<?
					$sqlstringA = "select a.project_id 'projectid', a.*, b.*, enroll_startdate, enroll_enddate from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = ?";
					$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
					mysqli_stmt_bind_param($stmtA, 'i', $id);
					$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$id]);
					mysqli_stmt_close($stmtA);
					$numenrollments = mysqli_num_rows($resultA);
					while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
						$enrollmentid = (int)$rowA['enrollment_id'];
						$enroll_startdate = $rowA['enroll_startdate'];
						$enroll_enddate = $rowA['enroll_enddate'];
						$enrollgroup = $rowA['enroll_subgroup'];
						$projectid = (int)$rowA['projectid'];
						$project_name = $rowA['project_name'];
						$costcenter = $rowA['project_costcenter'];
						
						/* data permissions are per-project */
						$projectadmin = GetPerm($sp['projects'], 'projectadmin', $projectid);
						$viewdata = GetPerm($sp['projects'], 'viewdata', $projectid);
						$modifydata = GetPerm($sp['projects'], 'modifydata', $projectid);

						$ts = strtotime($enroll_startdate ?? ''); $enrolldate = $ts !== false ? date('M j, Y g:ia', $ts) : '';
					
						if (($enroll_enddate > date("Y-m-d H:i:s")) || ($enroll_enddate == "0000-00-00 00:00:00") || ($enroll_enddate == "") || ($enroll_enddate == strtolower("null"))) {
							$enrolled = true;
						}
						else {
							$enrolled = false;
						}
						
						if ($project_name == "") {
							$project_name = "Project Name is BLANK";
						}
						
						$subjectaltids = implode2(', ',GetAlternateUIDs($id, $enrollmentid));
						
						?>
						<div class="ui attached styled grey segment">
							<div class="ui large inverted center aligned segment" style="padding:6px">
								<a href="projects.php?id=<?=$projectid?>" style="color: #fff"><i class="external alternate icon"></i><?=htmlspecialchars($project_name)?> (<?=htmlspecialchars($costcenter ?? '')?>)</a>
								&nbsp;
								<? if ($projectid == $currentprojectid) { ?>
								<i class="large yellow check circle icon" title="Current project for subject navigation"></i>
								<? } else { ?>
								<a href="subjects.php?action=setcurrentproject&id=<?=$id?>&projectid=<?=$projectid?>" title="Use this project for subject navigation"><i class="large inverted check circle outline icon"></i></a>
								<? } ?>
								
							</div>
							
							<div class="ui grid">
								<div class="three wide column">
									<div style="padding: 10px;">
										<table class="ui very basic celled compact table">
											<tr>
												<td class="right aligned"><b>ID(s)</b></td>
												<td>
													<? if (!$viewdata) { echo $noperm; } elseif ($subjectaltids != "") { ?>
													<div class="ui basic yellow label"><?=htmlspecialchars($subjectaltids)?></div>
													<? } ?>
												</td>
											</tr>
											<tr>
												<td class="right aligned"><b>Group</b></td>
												<td><?=($viewdata ? htmlspecialchars($enrollgroup ?? '') : $noperm)?></td>
											</tr>
											<tr>
												<td class="right aligned"><b>Enroll date</b></td>
												<td><?=($viewdata ? $enrolldate : $noperm)?></td>
											</tr>
											<tr>
												<td class="right aligned"><b>Tags</b></td>
												<td><?=($viewdata ? DisplayTags(GetTags('enrollment', $enrollmentid), 'enrollment') : $noperm)?></td>
											</tr>
											<? if (($enroll_enddate != "0000-00-00 00:00:00") && ($enroll_enddate != "")) { ?>
											<tr>
												<td class="right aligned" style="color: darkred"><b>Un-enroll date</b></td>
												<td style="color: darkred"><?=($viewdata ? htmlspecialchars($enroll_enddate) : $noperm)?></td>
											</tr>
											<? } ?>
										</table>
										
										<? if ($viewdata) { ?>
										<a class="ui fluid primary button" href="enrollment.php?enrollmentid=<?=$enrollmentid?>">View Enrollment</a>
										<br>
										<a href="packages.php?action=addobject&objecttype=enrollment&objectids[]=<?=$enrollmentid?>" class="ui basic fluid brown button"><img src="images/squirrel-icon-64.png" height="15"></img> &nbsp; Add to Package</a>
										<a class="ui fluid basic button" href="timeline.php?enrollmentid=<?=$enrollmentid?>"><i class="clock icon"></i> View Timeline</a>
										<a class="ui fluid basic button" href="subjects.php?action=print&id=<?=$id?>&enrollmentid=<?=$enrollmentid?>"><i class="clipboard list icon"></i> View Imaging Summary</a>
										<? } else { ?>
										<div class="ui fluid disabled primary button" title="No view permissions">View Enrollment</div>
										<br>
										<div class="ui basic fluid disabled brown button" title="No view permissions"><img src="images/squirrel-icon-64.png" height="15"></img> &nbsp; Add to Package</div>
										<div class="ui fluid basic disabled button" title="No view permissions"><i class="clock icon"></i> View Timeline</div>
										<div class="ui fluid basic disabled button" title="No view permissions"><i class="clipboard list icon"></i> View Imaging Summary</div>
										<? } ?>
										<br><br>
										<?
										if (($enrolled) && ($projectadmin)) { ?>
										<div class="ui accordion">
											<div class="title">
												<i class="dropdown icon"></i>
												Enroll in different project
											</div>
											<div class="content">
												<form action="subjects.php" method="post" style="margin:0px; padding:0px; display:inline;">
												<input type="hidden" name="id" value="<?=$id?>">
												<input type="hidden" name="action" value="changeproject">
												<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
												<br>
													Un-enroll subject from this project and enroll in this project. Moves all imaging, observations, and interventions.
													<select class="ui dropdown" name="newprojectid" required>
														<option value="">Select new project...</option>
													<?
														foreach ($activeprojects as $pid => $project) {
															$disabled = (GetPerm($activeprojectperms, 'modifydata', $pid) && ($pid != $projectid)) ? "" : "disabled";
															?>
															<option value="<?=$pid?>" <?=$disabled?>><?=htmlspecialchars($project['project_name'])?> (<?=htmlspecialchars($project['project_costcenter'] ?? '')?>)</option>
															<?
														}
													?>
													</select>
													<input type="submit" value="Move" class="ui primary button">
												</form>
											</div>
										</div>
										<?
										} /* end if project admin */
										?>
									</div>
								</div>
								<div class="thirteen wide column">
									
									<!-- ----------------------------------------------------- -->
									<!-- -------------------- Imaging section ---------------- -->
									<!-- ----------------------------------------------------- -->
									<div class="ui top attached blue segment">
										<div class="ui two column grid">
											<div class="column">
												<h3 class="header"><i class="grey file image icon"></i> Imaging Studies</h3>
											</div>
											<div class="right aligned column">
												<? if (!$modifydata) {
													/* no create options without Edit Data */
												} elseif (!$enrolled) { ?>
												<span style="color: #666">Subject is un-enrolled. Cannot create new studies</span>
												<? } else { ?>

												<div class="ui accordion">
													<div class="title">
														<i class="dropdown icon"></i>
														Create new imaging studies
													</div>
													<div class="content">
														<form action="subjects.php" method="post">
														<input type="hidden" name="id" value="<?=$id?>">
														<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
														<input type="hidden" name="action" value="newstudy">
														<div class="ui small labeled action input">
															<label for="modality" class="ui label grey">New <u>empty</u> study</label>
															<select class="ui selection dropdown" name="modality" required>
																<option value="">(Select modality)</option>
																<?
																$sqlstring = "select * from modalities order by mod_code";
																$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
																while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
																	$mod_code = htmlspecialchars($row['mod_code']);
																	$mod_desc = htmlspecialchars($row['mod_desc'] ?? '');
																	?>
																	<option value="<?=$mod_code?>"><b><?=$mod_code?></b> <?=$mod_desc?></option>
																	<?
																}
															?>
															</select>
															<button class="ui small primary button" type="submit">Create</button>
														</div>
														</form>

														<form action="subjects.php" method="post">
														<input type="hidden" name="id" value="<?=$id?>">
														<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
														<input type="hidden" name="action" value="newstudyfromtemplate">
														<div class="ui small labeled action input">
															<label for="templateid" class="ui label grey">New study from <u>template</u></label>
															<select class="ui selection dropdown" name="templateid" required>
																<option value="">(Select template)</option>
																<?
																$sqlstring = "select * from study_template where project_id = ? order by template_name asc";
																$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
																mysqli_stmt_bind_param($stmt, 'i', $projectid);
																$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
																mysqli_stmt_close($stmt);
																while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
																	$templateid = (int)$row['studytemplate_id'];
																	?>
																	<option value="<?=$templateid?>"><?=htmlspecialchars($row['template_name'])?> (<?=htmlspecialchars($row['template_modality'] ?? '')?>)</option>
																	<?
																}
															?>
															</select>
															<button class="ui small primary button" type="submit">Create</button>
														</div>
														</form>

														<form action="subjects.php" method="post">
														<input type="hidden" name="id" value="<?=$id?>">
														<input type="hidden" name="enrollmentid" value="<?=$enrollmentid?>">
														<input type="hidden" name="action" value="newstudygroupfromtemplate">
														<div class="ui small labeled action input">
															<label for="grouptemplateid" class="ui label grey">New study group from <u>template</u></label>
															<select class="ui selection dropdown" name="grouptemplateid" required>
																<option value="">(Select group template)</option>
																<?
																$sqlstring = "select a.projecttemplate_id, a.template_name, (select count(*) from project_templatestudies where pt_id = a.projecttemplate_id) 'numstudies', (select count(*) from project_templatestudyitems where pts_id in (select pts_id from project_templatestudies where pt_id = a.projecttemplate_id)) 'numseries' from project_template a where a.project_id = ? order by a.template_name asc";
																$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
																mysqli_stmt_bind_param($stmt, 'i', $projectid);
																$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$projectid]);
																mysqli_stmt_close($stmt);
																while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
																	$ptid = (int)$row['projecttemplate_id'];
																	?>
																	<option value="<?=$ptid?>"><?=htmlspecialchars($row['template_name'])?> (<?=$row['numstudies']?> studies, <?=$row['numseries']?> total series)</option>
																	<?
																}
															?>
															</select>
															<button class="ui small primary button" type="submit">Create</button>
														</div>
														</form>
														
													</div>
												</div>
												<? } ?>
											</div>
										</div>
									</div>
									<?
									if (!$viewdata) {
										?>
										<div class="ui bottom attached center aligned segment">
											<?=$noperm?>
										</div>
										<?
									}
									else {
										$sqlstring = "select a.*, datediff(a.study_datetime, c.birthdate) 'ageatscan' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.enrollment_id = ? order by a.study_datetime desc";
										$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
										mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
										$result2 = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentid]);
										mysqli_stmt_close($stmt);
										if (mysqli_num_rows($result2) > 0) {
										?>
										<table width="100%" class="ui bottom attached small very compact selectable celled grey table">
											<thead>
												<th>Study</th>
												<th>Modality</th>
												<th>Date <i class="arrow circle down icon"></i></th>
												<th># Series</th>
												<th>Age</th>
												<th>Site</th>
												<th>Study ID</th>
												<th>Visit</th>
												<th>Day</th>
												<th>Timepoint</th>
												<th>Rad Read</th>
											</thead>
											<tbody>
											<?
											while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
												
												$study_id = (int)$row2['study_id'];
												$study_num = $row2['study_num'];
												$study_modality = $row2['study_modality'];
												$study_datetime = $row2['study_datetime'];
												$study_ageatscan = $row2['study_ageatscan'];
												$calcage = number_format((float)$row2['ageatscan']/365.25,1);
												$study_site = $row2['study_site'];
												$study_type = $row2['study_type'];
												$study_daynum = $row2['study_daynum'];
												$study_timepoint = $row2['study_timepoint'];
												$study_doradread = $row2['study_doradread'];
												
												if (trim($study_ageatscan ?? '') != 0) {
													$age = $study_ageatscan;
												}
												else {
													$age = $calcage;
												}
												/* normalize to a plain number: $calcage is already number_format()'d (comma thousands
												   separator) and $study_ageatscan may also contain a comma, both of which make
												   number_format() throw a TypeError on PHP 8. Strip commas and cast to float. */
												$age = (float)str_replace(',', '', $age);

												$seriescount = "";
												if ($study_modality != "") {
													$seriestable = strtolower($study_modality) . "_series";
													if (isset($seriestables[$seriestable])) {
														$sqlstring3 = "select count(*) 'seriescount' from `$seriestable` where study_id = ?";
														$stmt3 = mysqli_prepare($GLOBALS['linki'], $sqlstring3);
														mysqli_stmt_bind_param($stmt3, 'i', $study_id);
														$result3 = MySQLiBoundQuery($stmt3, __FILE__, __LINE__, $sqlstring3, [$study_id]);
														$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);
														mysqli_stmt_close($stmt3);
														$seriescount = $row3['seriescount'];
													}
													else {
														$seriescount = "<span style='color:red'>Invalid modality [" . htmlspecialchars($study_modality) . "]</span>";
													}
												}
												?>
												<tr onMouseOver="this.style.backgroundColor='#9EBDFF'; this.style.cursor='pointer';" onMouseOut="this.style.backgroundColor=''; this.style.cursor='auto';" onClick="window.location='studies.php?id=<?=$study_id?>'">
													<td style="text-align: center;"><a href="studies.php?id=<?=$study_id?>" style="font-size: larger; font-weight: bold"><?=$study_num?></a></td>
													<td><?
													 if ($study_modality == "") { ?><div class="ui tiny basic red label">Blank</div><? }
													 else { echo htmlspecialchars($study_modality); }
													?></td>
													<td><?=$study_datetime?></td>
													<td><?=$seriescount?></td>
													<td><?=number_format($age,1)?> <span class="tiny">&nbsp;y</span></td>
													<td><?=htmlspecialchars($study_site ?? '')?></td>
													<td><tt><?=htmlspecialchars($uid)?><?=$study_num?></tt></td>
													<td><?=htmlspecialchars($study_type ?? '')?></td>
													<td><?=htmlspecialchars($study_daynum ?? '')?></td>
													<td><?=htmlspecialchars($study_timepoint ?? '')?></td>
													<td><? if ($study_doradread) { echo "&#x2713;"; } ?></td>
												</tr>
												<?
											}
											?>
											</tbody>
										</table>
										<?
										}
										else {
											?>
											<div class="ui bottom attached center aligned segment">
												No imaging studies
											</div>
											<?
										}
									}

									/* non-imaging data sections: observations, interventions, diagnosis */
									$sections = array(
										array('table' => 'observations', 'title' => 'Observations', 'icon' => 'clipboard list', 'page' => 'observations.php', 'single' => 'observations', 'none' => 'No observations'),
										array('table' => 'interventions', 'title' => 'Interventions', 'icon' => 'file prescription', 'page' => 'interventions.php', 'single' => 'interventions', 'none' => 'No interventions'),
										array('table' => 'diagnosis', 'title' => 'Diagnosis', 'icon' => 'clipboard list', 'page' => 'diagnosis.php', 'single' => 'diagnoses', 'none' => 'No diagnosis'),
									);
									foreach ($sections as $section) {
										?>
										<!-- -------------------- <?=$section['title']?> -------------------- -->
										<div class="ui blue segment">
											<div class="ui three column grid">
												<div class="column">
													<h3 class="header"><i class="grey <?=$section['icon']?> icon"></i> <?=$section['title']?></h3>
												</div>
												<div class="center aligned column">
													<?
														if (!$viewdata) {
															echo $noperm;
														}
														else {
															$sqlstring3 = "select count(*) 'count' from `" . $section['table'] . "` where enrollment_id = ?";
															$stmt3 = mysqli_prepare($GLOBALS['linki'], $sqlstring3);
															mysqli_stmt_bind_param($stmt3, 'i', $enrollmentid);
															$result3 = MySQLiBoundQuery($stmt3, __FILE__, __LINE__, $sqlstring3, [$enrollmentid]);
															$row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC);
															mysqli_stmt_close($stmt3);
															$numrows = $row3['count'];
															if ($numrows > 0) {
																?><span style="font-size: larger;"><b><?=$numrows?></b> <?=$section['single']?></span><?
															}
															else {
																echo $section['none'];
															}
														}
													?>
												</div>
												<div class="right aligned column">
													<? if ($modifydata) { ?>
													<a class="ui basic compact button" href="<?=$section['page']?>?enrollmentid=<?=$enrollmentid?>"><i class="edit icon"></i> Edit <?=strtolower($section['title'])?></a>
													<? } elseif ($viewdata) { ?>
													<a class="ui basic compact button" href="<?=$section['page']?>?enrollmentid=<?=$enrollmentid?>"><i class="eye icon"></i> View <?=strtolower($section['title'])?></a>
													<? } ?>
												</div>
											</div>
										</div>
										<?
									}
									?>
									
								</div>
							</div> <!-- end the layout grid within the enrollment -->
						</div>
						<?
					} /* end while loop for enrollments */
				?>
				<div class="ui bottom attached styled blue segment">
					Displayed <?=$numenrollments?> enrollments
				</div>
			</div> <!-- end the 12-wide right column (list of enrollments) -->
		</div> <!-- end the overall grid -->

		<?
	}


	/* -------------------------------------------- */
	/* ------- PrintEnrollment -------------------- */
	/* -------------------------------------------- */
	function PrintEnrollment($id, $enrollmentid) {
		if (!ValidID($id,'Subject ID')) { return; }
		if (!ValidID($enrollmentid,'Enrollment ID')) { return; }
		$id = (int)$id;
		$enrollmentid = (int)$enrollmentid;

		/* the enrollment must belong to this subject, and the user needs View Data on its project */
		$enrollment = GetEnrollment($enrollmentid);
		if (($enrollment == null) || ($enrollment['subject_id'] != $id)) {
			Error("Invalid enrollment");
			return;
		}
		$projectid = (int)$enrollment['project_id'];
		$perms = GetCurrentUserProjectPermissions(array($projectid));
		DisplayPermissions($perms);
		if (!GetPerm($perms, 'viewdata', $projectid)) {
			Error("You do not have permission to view data in this project");
			return;
		}

		/* modalities that have a series table */
		$seriestables = array();
		$result = MySQLiQuery("select table_name 'table_name' from information_schema.tables where table_schema = database() and table_name like '%\_series'", __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$seriestables[strtolower($row['table_name'])] = 1;
		}
		
		$sqlstring = "select a.*, datediff(a.study_datetime, c.birthdate) 'ageatscan' from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.enrollment_id = ? order by a.study_num asc";
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		mysqli_stmt_bind_param($stmt, 'i', $enrollmentid);
		$result2 = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$enrollmentid]);
		mysqli_stmt_close($stmt);
		if (mysqli_num_rows($result2) > 0) {
		?>
		<table class="ui very compact celled small table">
			<thead>
				<tr>
					<th>#</th>
					<th>Modality</th>
					<th>Date</th>
					<th>Age (y)</th>
					<th>Site</th>
					<th>Visit</th>
					<th>Series</th>
				</tr>
			</thead>
			<tbody>
			<?
			while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
				$study_id = (int)$row2['study_id'];
				$study_num = $row2['study_num'];
				$study_modality = $row2['study_modality'];
				$study_datetime = $row2['study_datetime'];
				$study_ageatscan = $row2['study_ageatscan'];
				$calcage = number_format((float)$row2['ageatscan']/365.25,1);
				$study_site = $row2['study_site'];
				$study_type = $row2['study_type'];

				$age = (trim($study_ageatscan ?? '') != 0) ? $study_ageatscan : $calcage;
				$age = (float)str_replace(',', '', $age);
				?>
				<tr>
					<td><b><?=$study_num?></b></td>
					<td>
						<? if ($study_modality == "") { ?>
						<div class="ui red label">blank</div>
						<? } else { echo htmlspecialchars($study_modality); } ?>
					</td>
					<td><?=$study_datetime?></td>
					<td><?=number_format($age,1)?></td>
					<td><?=htmlspecialchars($study_site ?? '')?></td>
					<td><?=htmlspecialchars($study_type ?? '')?></td>
					<td>
					<?
					if ($study_modality != "") {
						$seriestable = strtolower($study_modality) . "_series";
						if (isset($seriestables[$seriestable])) {
							$sqlstring3 = "select * from `$seriestable` where study_id = ? order by series_num asc";
							$stmt3 = mysqli_prepare($GLOBALS['linki'], $sqlstring3);
							mysqli_stmt_bind_param($stmt3, 'i', $study_id);
							$result3 = MySQLiBoundQuery($stmt3, __FILE__, __LINE__, $sqlstring3, [$study_id]);
							mysqli_stmt_close($stmt3);
							while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
								$protocol = (($row3['series_desc'] ?? '') != "") ? $row3['series_desc'] : $row3['series_protocol'];
								echo htmlspecialchars($row3['series_num'] . " - " . $protocol) . "<br>";
							}
						}
						else {
							?><span class="ui red text">Invalid modality [<?=htmlspecialchars($study_modality)?>]</span><?
						}
					}
					?>
					</td>
				</tr>
				<?
			}
			?>
			</tbody>
		</table>
		<?
		}
		else {
			?>
			<div class="ui message">No imaging studies</div>
			<?
		}
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySubjectForm ----------------- */
	/* -------------------------------------------- */
	/* PHI fields are editable with Edit PHI, read-only with View PHI, and otherwise replaced by the
	   'no view permissions' box. Per-enrollment IDs follow the Data permissions of that project */
	function DisplaySubjectForm($type, $id) {
		$uid = $firstname = $lastname = $dob = $gender = $ethnicity1 = $ethnicity2 = $handedness = $education = $phone1 = $email = $maritalstatus = $guid = "";
		$cancontact = 0;
		$enrollments = array();
		$noperm = NoViewPermission();

		/* populate the fields if this is an edit */
		if ($type == "edit") {
			/* check for valid subject ID */
			if (!ValidID($id,'Subject ID')) { return; }
			$id = (int)$id;

			$sp = GetSubjectPermissions($id);
			if (!$sp['canedit']) {
				Error("You do not have permission to edit this subject");
				return;
			}
			$viewphi = $sp['viewphi'];
			$modifyphi = $sp['modifyphi'];
			
			$sqlstring = "select * from subjects where subject_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			mysqli_stmt_close($stmt);
			if (!$row) {
				Error("Subject not found");
				return;
			}
			$uid = $row['uid'];
			$gender = $row['gender'];
			$guid = $row['guid'];
			/* don't load PHI the user can't see, so it can't end up in the page */
			if ($viewphi) {
				$nameparts = explode("^", $row['name']);
				$lastname  = $nameparts[0] ?? '';
				$firstname = $nameparts[1] ?? '';
				$dob = $row['birthdate'];
				$ethnicity1 = $row['ethnicity1'];
				$ethnicity2 = $row['ethnicity2'];
				$handedness = $row['handedness'];
				$education = $row['education'];
				$phone1 = $row['phone1'];
				$email = $row['email'];
				$maritalstatus = $row['marital_status'];
				$cancontact = $row['cancontact'];
			}

			/* the subject's enrollments, for the per-project IDs */
			$sqlstring = "select a.enrollment_id, a.project_id, b.project_name from enrollment a left join projects b on a.project_id = b.project_id where a.subject_id = ?";
			$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
			mysqli_stmt_bind_param($stmt, 'i', $id);
			$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$id]);
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$enrollments[] = $row;
			}
			mysqli_stmt_close($stmt);

			DisplayPermissions($sp['projects']);

			$formaction = "confirmupdate";
			$formtitle = "Updating &nbsp;<span class='uid'>" . htmlspecialchars($uid) . "</span>";
			$submitbuttonlabel = "Update";
		}
		else {
			$id = 0;
			$sp = null;
			$viewphi = $modifyphi = 1;
			$formaction = "confirmadd";
			$formtitle = "Add new subject";
			$submitbuttonlabel = "Add";
			$dob = "1900-01-01";
		}

		$h = function($v) { return htmlspecialchars((string)$v); };
		/* attributes for a PHI select/checkbox: named if editable, otherwise disabled (not submitted) */
		$phiattr = function($name) use ($modifyphi) { return $modifyphi ? "name='$name'" : "disabled"; };
		
	?>
		<div class="ui text container">
			<div class="ui attached visible message">
				<div class="header"><?=$formtitle?></div>
			</div>
			<form method="post" action="subjects.php" class="ui form attached fluid segment">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<input type="hidden" name="uid" value="<?=$h($uid)?>">
			<? if ($type == "add") { ?>
			<!--<tr title="This will encrypt the name and alternate UIDs.<br>It will also change the DOB to year only (ex. 1980-00-00)">
				<td class="label">Encrypt</td>
				<td><input type="checkbox" name="encrypt" value="1"></td>
			</tr>-->
			<? } ?>
			
			<h3 class="ui dividing header">Basic Information</h3>
			<div class="two fields">
				<div class="required field">
					<label>First name</label>
					<div class="field">
						<? if ($modifyphi) { ?>
						<input class="ui input focus" type="text" name="firstname" value="<?=$h($firstname)?>">
						<? } elseif ($viewphi) { ?>
						<input type="text" value="<?=$h($firstname)?>" disabled>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
				
				<div class="required field">
					<label>Last name</label>
					<div class="field">
						<? if ($modifyphi) { ?>
						<input type="text" name="lastname" value="<?=$h($lastname)?>" required>
						<? } elseif ($viewphi) { ?>
						<input type="text" value="<?=$h($lastname)?>" disabled>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
			</div>

			<div class="two fields">
				<div class="required field">
					<label>Sex</label>
					<div class="field">
						<select name="gender">
							<option value="" <? if ($gender == "") echo "selected"; ?>>(Select sex)</option>
							<option value="U" <? if ($gender == "U") echo "selected"; ?>>Unknown</option>
							<option value="F" <? if ($gender == "F") echo "selected"; ?>>Female</option>
							<option value="M" <? if ($gender == "M") echo "selected"; ?>>Male</option>
							<option value="O" <? if ($gender == "O") echo "selected"; ?>>Other</option>
						</select>
					</div>
				</div>
				
				<div class="field">
					<label>Date of Birth</label>
					<div class="field">
						<? if ($modifyphi) { ?>
						<input type="date" name="dob" value="<?=$h($dob)?>" required>
						<? } elseif ($viewphi) { ?>
						<input type="text" value="<?=$h($dob)?>" disabled>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
			</div>
			
			<h3 class="ui dividing header">IDs</h3>
			<div class="field">
				<div class="field">
					<table class="ui very compact table">
						<thead>
							<tr>
								<th><b>Project</b></th>
								<th title="Use asterisk next to primary ID (Example *PrimaryID1, otherID1, otherID23)">Comma separated list of <b>IDs</b></th>
							</tr>
						</thead>
						<tr>
							<td>All projects</td>
							<td><div class="ui input"><input type="text" size="50" name="altuids[0]" value="<?=$h(implode2(', ',GetAlternateUIDs($id,0)))?>"></div></td>
						</tr>
						<?
						foreach ($enrollments as $enrollment) {
							$enrollmentid = (int)$enrollment['enrollment_id'];
							$projectid = (int)$enrollment['project_id'];
							?>
							<tr>
								<td><?=$h($enrollment['project_name'])?></td>
								<td>
									<? if (GetPerm($sp['projects'], 'modifydata', $projectid)) { ?>
									<div class="ui input"><input type="text" size="50" name="altuids[<?=$enrollmentid?>]" value="<?=$h(implode2(', ',GetAlternateUIDs($id,$enrollmentid)))?>"></div>
									<? } elseif (GetPerm($sp['projects'], 'viewdata', $projectid)) { ?>
									<div class="ui disabled input"><input type="text" size="50" value="<?=$h(implode2(', ',GetAlternateUIDs($id,$enrollmentid)))?>" disabled></div>
									<? } else { echo $noperm; } ?>
								</td>
							</tr>
							<?
						}
						?>
					</table>
				</div>
				<div class="field">
					<label>GUID</label>
					<div class="field">
						<input type="text" name="guid" value="<?=$h($guid)?>">
					</div>
				</div>
			</div>

			<h3 class="ui dividing header">Extra Information</h3>
  
			<div class="two fields">
				<div class="field">
					<label>Race</label>
					<div class="field">
						<? if ($viewphi) { ?>
						<select <?=$phiattr('ethnicity2')?>>
							<option value="" <? if ($ethnicity2 == "") echo "selected"; ?>>(Select race)</option>
							<option value="indian" <? if ($ethnicity2 == "indian") echo "selected"; ?>>American Indian/Alaska Native</option>
							<option value="asian" <? if ($ethnicity2 == "asian") echo "selected"; ?>>Asian</option>
							<option value="black" <? if ($ethnicity2 == "black") echo "selected"; ?>>Black/African American</option>
							<option value="islander" <? if ($ethnicity2 == "islander") echo "selected"; ?>>Hawaiian/Pacific Islander</option>
							<option value="white" <? if ($ethnicity2 == "white") echo "selected"; ?>>White</option>
						</select>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
				<div class="field">
					<label>Ethnicity</label>
					<div class="field">
						<? if ($viewphi) { ?>
						<select <?=$phiattr('ethnicity1')?>>
							<option value="" <? if ($ethnicity1 == "") echo "selected"; ?>>(Select ethnicity)</option>
							<option value="hispanic" <? if ($ethnicity1 == "hispanic") echo "selected"; ?>>Hispanic/Latino</option>
							<option value="nothispanic" <? if ($ethnicity1 == "nothispanic") echo "selected"; ?>>Not hispanic/latino</option>
						</select>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
			</div>

			<div class="three fields">
				<div class="field">
					<label>Handedness</label>
					<div class="field">
						<? if ($viewphi) { ?>
						<select <?=$phiattr('handedness')?>>
							<option value="" <? if ($handedness == "") echo "selected"; ?>>(Select a status)</option>
							<option value="U" <? if ($handedness == "U") echo "selected"; ?>>Unknown</option>
							<option value="R" <? if ($handedness == "R") echo "selected"; ?>>Right</option>
							<option value="L" <? if ($handedness == "L") echo "selected"; ?>>Left</option>
							<option value="A" <? if ($handedness == "A") echo "selected"; ?>>Ambidextrous</option>
						</select>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
				<div class="field">
					<label>Education</label>
					<div class="field">
						<? if ($viewphi) { ?>
						<select <?=$phiattr('education')?>>
							<option value="" <? if ($education == "") echo "selected"; ?>>(Select a status)</option>
							<option value="0" <? if ($education == "0") echo "selected"; ?>>Unknown</option>
							<option value="1" <? if ($education == "1") echo "selected"; ?>>Grade School</option>
							<option value="2" <? if ($education == "2") echo "selected"; ?>>Middle School</option>
							<option value="3" <? if ($education == "3") echo "selected"; ?>>High School/GED</option>
							<option value="4" <? if ($education == "4") echo "selected"; ?>>Trade School</option>
							<option value="5" <? if ($education == "5") echo "selected"; ?>>Associates Degree</option>
							<option value="6" <? if ($education == "6") echo "selected"; ?>>Bachelors Degree</option>
							<option value="7" <? if ($education == "7") echo "selected"; ?>>Masters Degree</option>
							<option value="8" <? if ($education == "8") echo "selected"; ?>>Doctoral Degree</option>
						</select>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
				<div class="field">
					<label>Marital Status</label>
					<div class="field">
						<? if ($viewphi) { ?>
						<select <?=$phiattr('maritalstatus')?>>
							<option value="" <? if ($maritalstatus == "") echo "selected"; ?>>(Select a status)</option>
							<option value="unknown" <? if ($maritalstatus == "unknown") echo "selected"; ?>>Unknown</option>
							<option value="single" <? if ($maritalstatus == "single") echo "selected"; ?>>Single</option>
							<option value="married" <? if ($maritalstatus == "married") echo "selected"; ?>>Married</option>
							<option value="divorced" <? if ($maritalstatus == "divorced") echo "selected"; ?>>Divorced</option>
							<option value="separated" <? if ($maritalstatus == "separated") echo "selected"; ?>>Separated</option>
							<option value="civilunion" <? if ($maritalstatus == "civilunion") echo "selected"; ?>>Civil Union</option>
							<option value="cohabitating" <? if ($maritalstatus == "cohabitating") echo "selected"; ?>>Cohabitating</option>
							<option value="widowed" <? if ($maritalstatus == "widowed") echo "selected"; ?>>Widowed</option>
						</select>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
			</div>

			<div class="three fields">
				<div class="field">
					<label>Phone</label>
					<div class="field">
						<? if ($modifyphi) { ?>
						<input type="tel" name="phone" value="<?=$h($phone1)?>">
						<? } elseif ($viewphi) { ?>
						<input type="tel" value="<?=$h($phone1)?>" disabled>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
				<div class="field">
					<label>Email</label>
					<div class="field">
						<? if ($modifyphi) { ?>
						<input type="email" name="email" value="<?=$h($email)?>">
						<? } elseif ($viewphi) { ?>
						<input type="email" value="<?=$h($email)?>" disabled>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
				<div class="field">
					<label>Can Contact?</label>
					<div class="field">
						<? if ($viewphi) { ?>
						<input type="checkbox" <?=$phiattr('cancontact')?> value="1" <? if ($cancontact) echo "checked"; ?>>
						<? } else { echo $noperm; } ?>
					</div>
				</div>
			</div>
			
			<div class="field">
				<label>Tags</label>
				<div class="field">
					<input type="text" size="50" name="tags" value="<?=$h(implode2(', ',GetTags('subject', $id)))?>" placeholder="comma separated list">
				</div>
			</div>
			
			<br><br>
			<div class="column" align="right">
				<button class="ui button" onClick="window.location.href='subjects.php<?=($id > 0 ? "?id=$id" : "")?>'; return false;">Cancel</button>
				<input class="ui primary button" type="submit" id="submit" value="<?=$submitbuttonlabel?>">
			</div>

			</form>
		</div>
	<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- MakeSQLorList ---------------------- */
	/* -------------------------------------------- */
	/* builds an OR'd list of conditions matching any word in $str against $field, either as a substring
	   or as a sha1 hash (encrypted values). Returns [sql, params] for a prepared statement */
	function MakeSQLorList($str, $field) {
		$str = str_ireplace(array('^',',','-'), " ", $str);
		$conditions = array();
		$params = array();
		foreach (explode(" ", $str) as $part) {
			$part = trim($part);
			if ($part == "") continue;
			$conditions[] = "`$field` like ?";               $params[] = "%$part%";
			$conditions[] = "`$field` = sha1(?)";            $params[] = $part;
			$conditions[] = "`$field` = sha1(upper(?))";     $params[] = $part;
			$conditions[] = "`$field` = sha1(lower(?))";     $params[] = $part;
		}
		return array(implode2(" or ", $conditions), $params);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplaySubjectList ----------------- */
	/* -------------------------------------------- */
	/* Any user can find a subject by UID. Other values are only shown (and only searchable) with
	   permissions: PHI (name, DOB) needs View PHI, other subject information needs access to at least
	   one of the subject's projects */
	function DisplaySubjectList($searchuid, $searchaltuid, $searchname, $searchgender, $searchdob, $searchactive) {
		ShowFlashMessage();
		$noperm = NoViewPermission();
		$h = function($v) { return htmlspecialchars((string)$v); };
	?>
	<div class="ui two column grid">
		<div class="column">
			<h2 class="ui header">Subjects</h2>
		</div>
		<div class="column" align="right">
			<button class="ui primary button" onClick="window.location.href='subjects.php?action=addform'; return false;" title="Search on this page before creating a new subject to make sure they don't already exist!"><i class="plus square outline icon"></i> Create Subject</button>
		</div>
	</div>
	
	<table class="ui celled selectable grey compact top attached table">
		<thead>
			<tr>
				<th align="left">&nbsp;</th>
				<th>UID<br><span class="tiny">S1234ABC</span></th>
				<th>Alternate UID</th>
				<th>Name</th>
				<th>Sex<br><span class="tiny">M,F,O,U</span></th>
				<th>DOB<br><span class="tiny">YYYY-MM-DD</span></th>
				<th>Projects</th>
				<th>Active?</th>
				<th>Activity date</th>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<script type="text/javascript">
		$(document).ready(function() {
			$("#rightcheckall").click(function() {
				var checked_status = this.checked;
				$(".rightcheck").find("input[type='checkbox']").each(function() {
					this.checked = checked_status;
				});
			});
		});
		</script>
		<tbody>
			<form method="post" action="subjects.php" name="subjectlist" class="ui form">
			<input type="hidden" name="action" value="search">
			<tr>
				<td>&nbsp;</td>
				<td>
					<div class="ui fluid input">
						<input type="text" placeholder="UID" name="searchuid" id="searchuid" value="<?=$h($searchuid)?>" autofocus="autofocus">
					</div>
				</td>
				<td>
					<div class="ui fluid input">
						<input type="text" placeholder="Alternate UID" name="searchaltuid" value="<?=$h($searchaltuid)?>">
					</div>
				</td>
				<td>
					<div class="ui fluid input">
						<input type="text" placeholder="Name" name="searchname" value="<?=$h($searchname)?>">
					</div>
				</td>
				<td>
					<div class="ui input">
						<input type="text" placeholder="Sex" name="searchgender" value="<?=$h($searchgender)?>" size="2" maxlength="2">
					</div>
				</td>
				<td>
					<div class="ui input">
						<input type="text" placeholder="YYYY-MM-DD" name="searchdob" value="<?=$h($searchdob)?>">
					</div>
				</td>
				<td> - </td>
				<td>
					<div class="ui checkbox">
						<input type="checkbox" name="searchactive" <? if ($searchactive == '1') { echo "checked"; } ?> value="1">
					</div>
				</td>
				<td> - </td>
				<td>
					<div class="ui primary button" onclick="document.subjectlist.action='subjects.php';document.subjectlist.action.value='search'; document.subjectlist.submit();">Search</div>
				</td>
			</tr>
			
			<?
				$subjectsfound = 0;
				/* if all the fields are blank, don't search */
				if ( ($searchuid == "") && ($searchaltuid == "") && ($searchname == "") && ($searchgender == "") && ($searchdob == "") ) {
					?>
						<tr>
							<td colspan="11" align="center" style="color: #555555; padding:8px; font-size:10pt">
								No search criteria specified
							</td>
						</tr>
					<?
				}
				else {
					$sqlstring = "select a.* from subjects a left join subject_altuid b on a.subject_id = b.subject_id left join enrollment c on a.subject_id = c.subject_id left join studies f on c.enrollment_id = f.enrollment_id where a.uid like ?";
					$params = array("%$searchuid%");
					if ($searchaltuid != "") {
						$sqlstring .= " and (b.altuid like ? or b.altuid = sha1(?) or b.altuid = sha1(upper(?)) or b.altuid = sha1(lower(?)) or f.study_alternateid = ? or f.study_alternateid like ? or f.study_alternateid = sha1(?) or f.study_alternateid = sha1(upper(?)) or f.study_alternateid = sha1(lower(?)))";
						array_push($params, "%$searchaltuid%", $searchaltuid, $searchaltuid, $searchaltuid, $searchaltuid, "%$searchaltuid%", $searchaltuid, $searchaltuid, $searchaltuid);
					}
					if ($searchname != "") {
						list($namesql, $nameparams) = MakeSQLorList($searchname, 'name');
						if ($namesql != "") {
							$sqlstring .= " and (a.$namesql)";
							$params = array_merge($params, $nameparams);
						}
					}
					if ($searchgender != "") { $sqlstring .= " and a.`gender` like ?"; $params[] = "%$searchgender%"; }
					if ($searchdob != "") { $sqlstring .= " and a.`birthdate` like ?"; $params[] = "%$searchdob%"; }
					$sqlstring .= " and a.isactive = ? group by a.uid order by a.name asc";
					$params[] = (int)$searchactive;

					$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
					$types = str_repeat('s', count($params) - 1) . 'i';
					mysqli_stmt_bind_param($stmt, $types, ...$params);
					$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, $params);
					mysqli_stmt_close($stmt);
					$numhidden = 0;
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$id = (int)$row['subject_id'];
						$sp = GetSubjectPermissions($id);

						/* don't let a search on a field the user can't see reveal its value */
						if ((($searchname != "") || ($searchdob != "")) && (!$sp['viewphi'])) { $numhidden++; continue; }
						if ((($searchaltuid != "") || ($searchgender != "")) && (!$sp['hasaccess'])) { $numhidden++; continue; }

						$name = $row['name'];
						$uid = $row['uid'];
						$isactive = $row['isactive'];
						$ts = strtotime($row['lastupdate'] ?? ''); $lastupdate = $ts !== false ? date('M j, Y g:ia', $ts) : '';

						if (strpos($name,'^') !== false) {
							list($lname, $fname) = explode("^",$name);
							$name = strtoupper(substr($fname,0,1)) . strtoupper(substr($lname,0,1));
						}
						
						/* get project enrollment list */
						$enrolllist = array();
						if ($sp['hasaccess']) {
							$sqlstringA = "select distinct d.project_id, d.project_name, d.project_costcenter from enrollment b left join projects d on d.project_id = b.project_id where b.subject_id = ?";
							$stmtA = mysqli_prepare($GLOBALS['linki'], $sqlstringA);
							mysqli_stmt_bind_param($stmtA, 'i', $id);
							$resultA = MySQLiBoundQuery($stmtA, __FILE__, __LINE__, $sqlstringA, [$id]);
							while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
								if ($rowA['project_id'] > 0) {
									$enrolllist[$rowA['project_id']] = $rowA['project_name'] . " (" . $rowA['project_costcenter'] . ")";
								}
							}
							mysqli_stmt_close($stmtA);
						}
						
						if ($isactive == 0) { ?><tr style="background-image:url('images/deleted.png')"><? } else { ?><tr><? } ?>
						
							<td style="background-color: lightyellow"><input type="checkbox" name="uids[]" value="<?=$h($uid)?>"></td>
							<td><a href="subjects.php?action=display&id=<?=$id?>"><?=$h($uid)?></a></td>
							<td><?=($sp['hasaccess'] ? $h(implode2(', ',GetAlternateUIDs($id,0))) : $noperm)?></td>
							<td><?=($sp['viewphi'] ? $h($name) : $noperm)?></td>
							<td><?=($sp['hasaccess'] ? $h($row['gender']) : $noperm)?></td>
							<td><?=($sp['viewphi'] ? $h($row['birthdate']) : $noperm)?></td>
							<td>
								<?
								if (!$sp['hasaccess']) {
									echo $noperm;
								}
								elseif (count($enrolllist) > 0) { ?>
								<details style="font-size:8pt; color: gray">
								<summary>Enrolled projects</summary>
								<?
									foreach ($enrolllist as $projectid => $val) {
										if (GetPerm($sp['projects'], 'viewdata', $projectid) || GetPerm($sp['projects'], 'viewphi', $projectid)) {
											?><span style="color:#238217; white-space:nowrap;" title="You have access to <?=$h($val)?>">&#8226; <?=$h($val)?></span><br><?
										}
										else {
											?><span style="color:#8b0000; white-space:nowrap;" title="You <b>do not</b> have access to <?=$h($val)?>">&#8226; <?=$h($val)?></span><br><?
										}
									}
								?>
								</details>
								<?
								}
								else {
									?><span style="font-size:8pt; color: darkred">Not enrolled</span><?
								}
								?>
							</td>
							<td><?=($sp['hasaccess'] ? ($isactive ? "&#x2713;" : "") : $noperm)?></td>
							<td><?=($sp['hasaccess'] ? $lastupdate : $noperm)?></td>
							<td></td>
							<? if ($GLOBALS['issiteadmin']) { ?>
							<td style="background-color: Lavender">
								<input type="checkbox" name="ids[]" value="<?=$id?>">
							</td>
							<? } ?>
						</tr>
						<? 
						$subjectsfound++;
					}
					if ($numhidden > 0) {
						?>
						<tr>
							<td colspan="11" align="center" style="color: #555555; padding:8px; font-size:10pt">
								<?=$numhidden?> matching subject(s) not shown because you do not have permission to view the searched fields
							</td>
						</tr>
						<?
					}
				}
				?>
		</tbody>
	</table>
		<?
		if ($subjectsfound > 0) {
		?>
		<div class="ui bottom attached menu">
			<div class="item" style="background-color: lightyellow">
				<div class="ui action input">
					<div class="ui selection dropdown">
						<input type="hidden" name="subjectgroupid">
						<i class="dropdown icon"></i>
						<div class="default text">Select subject group</div>
						<div class="scrollhint menu">
						<?
							$userid = (int)$_SESSION['userid'];
							$sqlstring = "select group_id, group_name from groups where group_type = 'subject' and group_owner = ?";
							$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
							mysqli_stmt_bind_param($stmt, 'i', $userid);
							$result = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$userid]);
							mysqli_stmt_close($stmt);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								?>
								<div class="item" data-value="<?=(int)$row['group_id']?>"><?=$h($row['group_name'])?></div>
								<?
							}
						?>
						</div>
					</div>
					<div class="ui button" value="Add to group" onclick="document.subjectlist.action='groups.php'; document.subjectlist.action.value='addsubjectstogroup'; document.subjectlist.submit();">Add to Group</div>
				</div>
			</div>
			</form>
			
			<div class="right menu">
				<? if ($GLOBALS['issiteadmin']) {?>
				<a class="item" style="background-color: Lavender" title="Remove all database entries for the subject and move their data to a /deleted directory" onclick="document.subjectlist.action='subjects.php';document.subjectlist.action.value='obliterate'; document.subjectlist.submit();">Obliterate Subjects</a>
				<? } ?>
			</div>
		</div>
		<?
		}
		else {
			?></form><?
		}
	}
	?>
	<br><br><br><br>
	<?
?>

<? include("footer.php") ?>
