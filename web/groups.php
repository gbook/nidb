<?
// ------------------------------------------------------------------------------
// NiDB groups.php
// Copyright (C) 2004 - 2018
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
    <title>NiDB - Manage Groups</title>
</head>

<body>
<div id="wrapper">
    <?
    require "functions.php";
    require "includes.php";
    require "menu.php";

	//PrintVariable($_POST);

    /* ----- setup variables ----- */
    $action = GetVariable("action");
    $id = GetVariable("id");
    $groupid = GetVariable("groupid");
    $subjectgroupid = GetVariable("subjectgroupid");
    $studygroupid = GetVariable("studygroupid");
    $seriesgroupid = GetVariable("seriesgroupid");
    $groupname = GetVariable("groupname");
    $grouptype = GetVariable("grouptype");
    $owner = GetVariable("owner");
    $uids = GetVariable("uids");
    $uidids = GetVariable("uidids");
    $seriesids = GetVariable("seriesid");
    $studyids = GetVariable("studyid");
    $modality = GetVariable("modality");
    $itemid = GetVariable("itemid");
    $measures = GetVariable("measures");
    $columns = GetVariable("columns");
    $groupmeasures = GetVariable("groupmeasures");
    $studylist = GetVariable("studylist");


    /* determine action */
    switch ($action) {
        case 'add':
            AddGroup($groupname, $grouptype, $GLOBALS['username']);
            DisplayGroupList();
            break;
        case 'delete': DeleteGroup($id); break;
        case 'addsubjectstogroup':
            AddSubjectsToGroup($subjectgroupid, $uids, $seriesids, $modality);
            ViewGroup($subjectgroupid, $measures, $columns, $groupmeasures);
            break;
        case 'addstudiestogroup':
            AddStudiesToGroup($studygroupid, $seriesids, $studyids, $modality);
            ViewGroup($studygroupid, $measures, $columns, $groupmeasures);
            break;
        case 'addseriestogroup':
            AddSeriesToGroup($seriesgroupid, $seriesids, $modality);
            ViewGroup($seriesgroupid, $measures, $columns, $groupmeasures);
            break;
        case 'removegroupitem':
            RemoveGroupItem($itemid);
            ViewGroup($id, $measures, $columns, $groupmeasures);
            break;
        case 'viewgroup':
            ViewGroup($id, $measures, $columns, $groupmeasures);
            break;
        case 'updatestudygroup':
            UpdateStudyGroup($id, $studylist);
            ViewGroup($id, $measures, $columns, $groupmeasures);
            break;
        default:
            DisplayGroupList();
            break;
    }


    /* ------------------------------------ functions ------------------------------------ */
	
	
    /* -------------------------------------------- */
    /* ------- AddGroup --------------------------- */
    /* -------------------------------------------- */
    function AddGroup($groupname, $grouptype, $owner) {
        /* perform data checks */
        /* get userid */
        $sqlstring = "select * from users where username = '$owner'";
        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $userid = $row['user_id'];

        /* insert the new group */
        $sqlstring = "insert ignore into groups (group_name, group_type, group_owner) values ('$groupname', '$grouptype', '$userid')";
        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

        ?><div align="center"><span class="message"><?=$groupname?> added</span></div><br><br><?
    }

    /* -------------------------------------------- */
    /* ------- AddSubjectsToGroup ----------------- */
    /* -------------------------------------------- */
    function AddSubjectsToGroup($groupid, $uids, $seriesids, $modality) {
        /* if the request came from the subjects.php page */
        if (!empty($uids)) {
            foreach ($uids as $uid) {
                $sqlstring = "select subject_id from subjects where uid = '$uid'";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $uidid = $row['subject_id'];

                /* check if its already in the db */
                $sqlstring  = "select * from group_data where group_id = $groupid and data_id = $uidid and modality = ''";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                if (mysqli_num_rows($result) > 0) {
                    ?><div align="center"><span class="message"><?=$groupid?>-<?=$uid?> already in this group</span></div><?
                }
                else {
                    /* insert the uidids */
                    $sqlstring = "insert into group_data (group_id, data_id) values ($groupid, $uidid)";
                    $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                    ?><div align="center"><span class="message"><?=$groupid?>-<?=$uid?> added</span></div><?
                }
            }
        }
        /* if the request came from the search.php page */
        if (!empty($seriesids)) {
            foreach ($seriesids as $seriesid) {
                /* get the study id for this seriesid/modality */
                $sqlstring = "select c.subject_id from ".$modality."_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id where a.".$modality."series_id = $seriesid";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $uidid = $row['subject_id'];

                /* check if its already in the db */
                $sqlstring  = "select * from group_data where group_id = $groupid and data_id = $uidid and modality = ''";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                if (mysqli_num_rows($result) > 0) {
                    ?><div align="center"><span class="message"><?=$groupid?>-<?=$uid?> already in this group</span></div><?
                }
                else {
                    /* insert the uidids */
                    $sqlstring = "insert into group_data (group_id, data_id) values ($groupid, $uidid)";
                    $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                    ?><div align="center"><span class="message"><?=$groupid?>-<?=$uid?> added</span></div><?
                }
            }
        }
    }

	
    /* -------------------------------------------- */
    /* ------- AddStudiesToGroup ------------------ */
    /* -------------------------------------------- */
    function AddStudiesToGroup($groupid, $seriesids, $studyids, $modality) {
        $modality = strtolower($modality);

        if (is_array($seriesids)) {
            foreach ($seriesids as $seriesid) {
                /* get the study id for this seriesid/modality */
                $sqlstring = "select study_id from $modality" . "_series where $modality" . "series_id = $seriesid";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $studyid = $row['study_id'];

                /* check if its already in the db */
                $sqlstring  = "select * from group_data where group_id = $groupid and data_id = $studyid and modality = '$modality'";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                if (mysqli_num_rows($result) > 0) {
                    ?><div align="center"><span class="message"><?=$groupid?>-<?=$seriesid?> already in this group</span></div><?
                }
                else {
                    /* insert the seriesids */
                    $sqlstring = "insert into group_data (group_id, data_id, modality, date_added) values ($groupid, $studyid, '$modality', '')";
                    $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                    ?><div align="center"><span class="message"><?=$groupid?>-<?=$seriesid?> added</span></div><?
                }
            }
        }

        if (is_array($studyids)) {
            foreach ($studyids as $studyid) {
                /* get the modality for this study */
                $sqlstring = "select study_modality from studies where study_id = $studyid";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $modality = $row['modality'];

                /* check if its already in the db */
                $sqlstring  = "select * from group_data where group_id = $groupid and data_id = $studyid and modality = '$modality'";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                if (mysqli_num_rows($result) > 0) {
                    ?><div align="center"><span class="message"><?=$groupid?>-<?=$studyid?> already in this group</span></div><?
                }
                else {
                    /* insert the studyids */
                    $sqlstring = "insert into group_data (group_id, data_id, modality) values ($groupid, $studyid, '$modality')";
                    $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                    ?><div align="center"><span class="message"><?=$groupid?>-<?=$studyid?> added</span></div><?
                }
            }
        }
    }

    /* -------------------------------------------- */
    /* ------- AddSeriesToGroup ------------------- */
    /* -------------------------------------------- */
    function AddSeriesToGroup($groupid, $seriesids, $modality) {

        foreach ($seriesids as $seriesid) {
            /* check if its already in the db */
            $sqlstring  = "select * from group_data where group_id = $groupid and data_id = $seriesid and modality = '$modality'";
            $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
            if (mysqli_num_rows($result) > 0) {
                ?><div align="center"><span class="message"><?=$groupid?>-<?=$seriesid?> already in this group</span></div><?
            }
            else {
                /* insert the seriesids */
                $sqlstring = "insert into group_data (group_id, data_id, modality) values ($groupid, $seriesid, '$modality')";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                ?><div align="center"><span class="message"><?=$groupid?>-<?=$seriesid?> added</span></div><?
            }
        }
    }

	
    /* -------------------------------------------- */
    /* ------- UpdateStudyGroup ------------------- */
    /* -------------------------------------------- */
    function UpdateStudyGroup($id, $studylist) {
		
		$id = mysqli_real_escape_string($GLOBALS['linki'], $id);

		if (trim($id) == "") {
            ?><div align="center"><span class="message">ID blank</span></div><?
			return;
		}

		/* start transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$studies = preg_split('/\s+/', $studylist);
		$studies = mysqli_real_escape_array($studies);
		$studies = array_unique($studies);

		/* delete all old group entries */
		$sqlstring = "delete from group_data where group_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		/* loop through all the studies and insert them */
		foreach ($studies as $study) {
			if (trim($study) == "") { continue; }
			
			$uid = substr($study,0,8);
			$studynum = substr($study,8);

			$sqlstring = "select b.study_id from studies b left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where d.uid = '$uid' AND b.study_num='$studynum'";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$studyid = $row['study_id'];

			//echo "[$study] --> [$studyid]<br>";
			
			/* insert the studyids */
			$sqlstring = "insert into group_data (group_id, data_id) values ($id, $studyid)";
			//echo "$sqlstring<br>";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}

		/* commit the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
	}
	

    /* -------------------------------------------- */
    /* ------- RemoveGroupItem -------------------- */
    /* -------------------------------------------- */
    function RemoveGroupItem($itemid) {
        PrintVariable($itemid,'ItemID');

        foreach ($itemid as $item) {
            $sqlstring = "delete from group_data where subjectgroup_id = $item";
            $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
            ?><div align="center"><span class="message">Item <?=$item?> deleted</span></div><?
        }
        return;
    }

    /* -------------------------------------------- */
    /* ------- DeleteGroup ------------------------ */
    /* -------------------------------------------- */
    function DeleteGroup($id) {
        $sqlstring = "delete from groups where group_id = $id";
        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
    }

    /* -------------------------------------------- */
    /* ------- ViewGroup -------------------------- */
    /* -------------------------------------------- */
    function ViewGroup($id, $measures, $columns, $groupmeasures) {

        /* get the general group information */
        $sqlstring = "select * from groups where group_id = '$id'";
        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $groupname = $row['group_name'];
        $grouptype = $row['group_type'];

        $urllist['Groups'] = "groups.php";
        NavigationBar("$groupname - <span style='font-weight:normal'>$grouptype<span>", $urllist,0,'','','','');
        ?>
        <script>
            $(document).ready(function()
                {
                    $("#studytable").tablesorter();
                }
            );
        </script>

        <?
        /* (subject level) group statistics */
        $totalage = 0;
        $numage = 0;
        $totalweight = 0;
        $numweight = 0;
        $n = 0;

        //print_r(get_defined_vars());

        /* ------------------ subject group type ------------------- */
        if ($grouptype == "subject") {
        /* get the actual group data (subject level) */
        $sqlstring = "select a.subjectgroup_id, b.*, (datediff(now(), birthdate)/365.25) 'age' from group_data a left join subjects b on a.data_id = b.subject_id where a.group_id = $id";
        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $subjectid = $row['subject_id'];
            $name = $row['name'];
            $birthdate = $row['birthdate'];
            $age = $row['age'];
            $gender = $row['gender'];
            $ethnicity1 = $row['ethnicity1'];
            $ethnicity2 = $row['ethnicity2'];
            $weight = $row['weight'];
            $handedness = $row['handedness'];
            $education = $row['education'];
            $uid = $row['uid'];

            /* do some demographics calculations */
            $n++;
            if ($age > 0) {
                $totalage += $age;
                $numage++;
                $ages[] = $age;
            }
            if ($weight > 0) {
                $totalweight += $weight;
                $numweight++;
                $weights[] = $weight;
            }
            $genders{$gender}++;
            $educations{$education}++;
            $ethnicity1s{$ethnicity1}++;
            $ethnicity2s{$ethnicity2}++;
            $handednesses{$handedness}++;
        }
        if ($numage > 0) { $avgage = $totalage/$numage; } else { $avgage = 0; }
        if (count($ages) > 0) { $varage = sd($ages); } else { $varage = 0; }
        if ($numweight > 0) { $avgweight = $totalweight/$numweight; } else { $avgweight = 0; }
        if (count($weights) > 0) { $varweight = sd($weights); } else { $varweight = 0; }

        ?>
            <table>
                <tr>
                    <td valign="top" style="padding-right:20px">
                        <?
                        DisplayDemographicsTable($n,$numage,$avgage,$varage,$genders,$ethnicity1s,$ethnicity2s,$educations,$handednesses,$avgweight,$varweight);
                        ?>
                    </td>
                </tr>
                <tr>
                    <td valign="top" style="padding-right:20px">
                        <details>
                            <summary>SQL</summary>
                            <?=PrintSQL($sqlstring)?>
                        </details>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        <form action="groups.php" method="post">
                            <input type="hidden" name="id" value="<?=$id?>">
                            <input type="hidden" name="action" value="removegroupitem">
                            <table class="smallgraydisplaytable">
                                <th>Initials</th>
                                <th>UID</th>
                                <th>Age<br><span class="tiny">current</span></th>
                                <th>Sex</th>
                                <th>Ethnicity 1</th>
                                <th>Ethnicity 2</th>
                                <th>Weight</th>
                                <th>Handedness</th>
                                <th>Education</th>
                                <th>Alt UIDs</th>
                                <th>Remove<br>from group</th>
                                <?
                                /* reset the result pointer to 0 to iterate through the results again */
                                mysqli_data_seek($result,0);
                                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                    $itemid = $row['subjectgroup_id'];
                                    $subjectid = $row['subject_id'];
                                    $name = $row['name'];
                                    $birthdate = $row['birthdate'];
                                    $age = $row['age'];
                                    $gender = $row['gender'];
                                    $ethnicity1 = $row['ethnicity1'];
                                    $ethnicity2 = $row['ethnicity2'];
                                    $weight = $row['weight'];
                                    $handedness = $row['handedness'];
                                    $education = $row['education'];
                                    $uid = $row['uid'];

                                    /* get list of alternate subject UIDs */
                                    $altuids = GetAlternateUIDs($subjectid);

                                    $parts = explode("^",$name);
                                    $name = substr($parts[1],0,1) . substr($parts[0],0,1);
                                    ?>
                                    <tr>
                                        <td><?=$name?></td>
                                        <td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
                                        <? if ($age <= 0) {$color = "red";} else {$color="black";} ?>
                                        <td style="color:<?=$color?>"><?=number_format($age,1)?>Y</td>
                                        <? if (!in_array(strtoupper($gender),array('M','F','O'))) {$color = "red";} else {$color="black";} ?>
                                        <td style="color:<?=$color?>"><?=$gender?></td>
                                        <td><?=$ethnicitiy1?></td>
                                        <td><?=$ethnicitiy1?></td>
                                        <td><?=number_format($weight,1)?>kg</td>
                                        <td><?=$handedness?></td>
                                        <td><?=$education?></td>
                                        <td><?=implode(', ',$altuids)?></td>
                                        <!--<td><a href="groups.php?action=removegroupitem&itemid=<?=$itemid?>&id=<?=$id?>" style="color:red">X</a></td>-->
                                        <td><input type="checkbox" name="itemid[]" value="<?=$itemid?>"></td>
                                    </tr>
                                    <?
                                }
                                ?>
                                <tr>
                                    <td colspan="100" align="right">
                                        <input type="submit" value="Remove">
                        </form>
                    </td>
                </tr>
            </table>
            </td>
            </tr>
            </table>
        <?
        }

        /* ------------------ study group type ------------------- */
        if ($grouptype == "study") {
        $csv = "";

        /* get the demographics (study level) */
        $sqlstring = "select c.enroll_subgroup, b.study_id, b.study_ageatscan,d.*, (datediff(b.study_datetime, d.birthdate)/365.25) 'age' from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = $id group by d.uid order by d.uid,b.study_num";
        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $studyid = $row['study_id'];
            $studynum = $row['study_num'];
            $studydesc = $row['study_desc'];
            $studyalternateid = $row['study_alternateid'];
            $studymodality = $row['study_modality'];
            $studyageatscan = $row['study_ageatscan'];
            $studydatetime = $row['study_datetime'];
            $studyoperator = $row['study_operator'];
            $studyperformingphysician = $row['study_performingphysician'];
            $studysite = $row['study_site'];
            $studyinstitution = $row['study_institution'];
            $studynotes = $row['study_notes'];
            $subgroup = $row['enroll_subgroup'];
            $studylist[] = $studyid;

            $subjectid = $row['subject_id'];
            $name = $row['name'];
            $birthdate = $row['birthdate'];
            $age = $row['age'];
            $gender = $row['gender'];
            $ethnicity1 = $row['ethnicity1'];
            $ethnicity2 = $row['ethnicity2'];
            $weight = $row['weight'];
            $handedness = $row['handedness'];
            $education = $row['education'];
            $uid = $row['uid'];
            $subjectids[] = $subjectid;
            /* do some demographics calculations */
            $n++;

            if ($studyageatscan > 0) {
                $age = $studyageatscan;
            }

            if ($age > 0) {
                $totalage += $age;
                $numage++;
                $ages[] = $age;
            }
            if ($weight > 0) {
                $totalweight += $weight;
                $numweight++;
                $weights[] = $weight;
            }
            $genders{$gender}++;
            $educations{$education}++;
            $ethnicity1s{$ethnicity1}++;
            $ethnicity2s{$ethnicity2}++;
            $handednesses{$handedness}++;
        }
        if ($numage > 0) { $avgage = $totalage/$numage; } else { $avgage = 0; }
        if (count($ages) > 0) { $varage = sd($ages); } else { $varage = 0; }
        if ($numweight > 0) { $avgweight = $totalweight/$numweight; } else { $avgweight = 0; }
        if (count($weights) > 0) { $varweight = sd($weights); } else { $varweight = 0; }

        if ($measures == "all") {
            $sqlstringD = "select a.subject_id, b.enrollment_id, c.*, d.measure_name from measures c join measurenames d on c.measurename_id = d.measurename_id left join enrollment b on c.enrollment_id = b.enrollment_id join subjects a on a.subject_id = b.subject_id where a.subject_id in (" . implode2(",", $subjectids) . ")";
            $resultD = MySQLiQuery($sqlstringD,__FILE__,__LINE__);

            if ($groupmeasures == "byvalue") {
                $mnames = array('ANTDX','AVDDX','AX1Com1_Code','AX1Com2_Code','AX1Com3_Code','AX1Com4_Code','AX1Com5_Code','AX1Com6_Code','AX1Com7_Code','AX1Pri_Code','AXIIDX','BRDDX','DPNDX','DSM-Axis','DSM-Axis1','DSM-Axis2','DSM-Axis295.3','DSM-Axis304.3','DSM-AxisV71.09','DSM_IV_TR','DXGROUP_1','DX_GROUP','MiniDxn','MiniDxnFollowUp','NARDX','OBCDX','PARDX','ProbandGroup','Psychosis','relnm1','SAsubtype','SCZDX','status','SubjectType','SZTDX');
                while ($rowD = mysqli_fetch_array($resultD, MYSQLI_ASSOC)) {
                    $subjectid = $rowD['subject_id'];
                    $measurename = $rowD['measure_name'];
                    if (in_array($measurename,$mnames)) {
                        if ($rowD['measure_type'] == 's') {
                            $value = strtolower(trim($rowD['measure_valuestring']));
                        }
                        else {
                            $value = strtolower(trim($rowD['measure_valuenum']));
                        }

                        if (is_numeric(substr($value,0,6))) {
                            $value = substr($value,0,6);
                        }
                        elseif (is_numeric(substr($value,0,5))) {
                            $value = substr($value,0,5);
                        }
                        elseif (is_numeric(substr($value,0,4))) {
                            $value = substr($value,0,4);
                        }
                        elseif (is_numeric(substr($value,0,3))) {
                            $value = substr($value,0,3);
                        }
                        elseif (is_numeric(substr($value,1,5))) {
                            $value = substr($value,1,5);
                        }
                        elseif (substr($value,0,3) == "xxx") {
                            $value = "xxx";
                        }

                        $measuredata[$subjectid][$value] = 1;
                        $measurenames[] = $value;
                    }
                }
                $measurenames = array_unique($measurenames);
                natsort($measurenames);
            }
            else {
                while ($rowD = mysqli_fetch_array($resultD, MYSQLI_ASSOC)) {
                    if ($rowD['measure_type'] == 's') {
                        $measuredata[$rowD['subject_id']][$rowD['measure_name']]['value'][] = $rowD['measure_valuestring'];
                    }
                    else {
                        $measuredata[$rowD['subject_id']][$rowD['measure_name']]['value'][] = $rowD['measure_valuenum'];
                    }
                    $measuredata[$rowD['subject_id']][$rowD['measure_name']]['notes'][] = $rowD['measure_notes'];
                    $measurenames[] = $rowD['measure_name'];
                }
                $measurenames = array_unique($measurenames);
                natcasesort($measurenames);
            }
        }

        /* setup the CSV header */
        if ($columns == "min") {
            $csv = "UID";
        }
        else {
            $csv = "Initials,UID,AgeAtStudy,Sex,Ethnicity,Race,SubGroup,Weight,Handedness,Education,AltUIDs,StudyID,Description,AltStudyID,Modality,StudyDate,Operator,Physician,Site";
        }

        ?>
            <table>
                <tr>
                    <td valign="top" style="padding-right:20px">
                        <?
                        DisplayDemographicsTable($n,$numage,$avgage,$varage,$genders,$ethnicity1s,$ethnicity2s,$educations,$handednesses,$avgweight,$varweight);
                        ?>
                    </td>
                </tr>
                <tr>
                    <td valign="top" style="padding-right:20px">
                        <?
                        DisplayMRProtocolSummary($studylist);
                        ?>
                    </td>
                </tr>
                <tr>
                    <td valign="top" style="padding-right:20px">
                        <?
                        DisplayGroupStudiesSummary($id);
                        ?>
                    </td>
                </tr>
                <tr>
                    <td valign="top" style="padding-right:20px">
                        <details>
                            <summary>SQL</summary>
                            <?=PrintSQL($sqlstring)?>
                        </details>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        <a href="groups.php?action=viewgroup&id=<?=$id?>&measures=all">Include measures</a><br>
                        <a href="groups.php?action=viewgroup&id=<?=$id?>&measures=all&columns=min">Include measures and only UID</a><br>
                        <a href="groups.php?action=viewgroup&id=<?=$id?>&measures=all&columns=min&groupmeasures=byvalue">Include measures and only UID and group measures by value</a>
                        <br><br>
                        <span class="tiny">Click columns to sort. May be slow for large tables</span>

                        <form action="groups.php" method="post">
                            <input type="hidden" name="id" value="<?=$id?>">
                            <input type="hidden" name="action" value="removegroupitem">

                            <table id="studytable" class="tablesorter">
                                <thead>
                                <tr>
                                    <? if ($columns != "min") { ?>
                                        <th>Initials</th>
                                    <? } ?>
                                    <th>UID</th>
                                    <? if ($columns != "min") { ?>
                                        <th>Age<br><span class="tiny">header</span></th>
                                        <th>Age<br><span class="tiny">computed</span></th>
                                        <th>Sex</th>
                                        <th>Ethnicities</th>
                                        <th>SubGroup</th>
                                        <th>VisitType</th>
                                        <th>Weight</th>
                                        <th>Handedness</th>
                                        <th>Education</th>
                                        <th>Alt UIDs</th>
                                        <th>Study ID</th>
                                        <th>Description</th>
                                        <th>Alternate Study ID</th>
                                        <th>Modality</th>
                                        <th>Date/time</th>
                                        <th>Operator</th>
                                        <th>Physician</th>
                                        <th>Site</th>
                                    <? } ?>
                                    <?
                                    if (count($measurenames) > 0) {
                                        foreach ($measurenames as $measurename) {
                                            echo "<th>$measurename</th>";
                                            $csv .= ",\"$measurename\"";
                                        }
                                    }
                                    ?>
                                    <th>Remove<br>from group</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?
                                /* reset the result pointer to 0 to iterate through the results again */
                                $sqlstring = "select a.subjectgroup_id, c.enroll_subgroup, b.*, d.*, (datediff(b.study_datetime, d.birthdate)/365.25) 'age' from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = $id order by d.uid,b.study_num";
                                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                                //mysqli_data_seek($result,0);
                                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                    $studyid = $row['study_id'];
                                    $studynum = $row['study_num'];
                                    $studydesc = $row['study_desc'];
                                    $studyalternateid = $row['study_alternateid'];
                                    $studymodality = $row['study_modality'];
                                    $studyageatscan = $row['study_ageatscan'];
                                    $studydatetime = $row['study_datetime'];
                                    $studyoperator = $row['study_operator'];
                                    $studyperformingphysician = $row['study_performingphysician'];
                                    $studysite = $row['study_site'];
                                    $studyinstitution = $row['study_institution'];
                                    $studynotes = $row['study_notes'];
                                    $studyvisittype = $row['study_type'];
                                    $studyweight = $row['study_weight'];
                                    $subgroup = $row['enroll_subgroup'];

                                    $itemid = $row['subjectgroup_id'];
                                    $subjectid = $row['subject_id'];
                                    $name = $row['name'];
                                    $birthdate = $row['birthdate'];
                                    $age = $row['age'];
                                    $gender = $row['gender'];
                                    $ethnicity1 = $row['ethnicity1'];
                                    $ethnicity2 = $row['ethnicity2'];
                                    $weight = $row['weight'];
                                    $handedness = $row['handedness'];
                                    $education = $row['education'];
                                    $uid = $row['uid'];
                                    if ($age <= 0) {
                                        $age = $studyageatscan;
                                    }
                                    /* get list of alternate subject UIDs */
                                    $altuids = GetAlternateUIDs($subjectid);

                                    $parts = explode("^",$name);
                                    $name = substr($parts[1],0,1) . substr($parts[0],0,1);

                                    if ($columns == "min") {
                                        $csv .= "\n\"$uid\"";
                                    }
                                    else {
                                        $csv .= "\n\"$name\",\"$uid\",\"$age\",\"$gender\",\"$ethnicity1\",\"$ethnicity2\",\"$subgroup\",\"$weight\",\"$handedness\",\"$education\",\"" . implode2(', ',$altuids) . "\",\"$uid$studynum\",\"$studydesc\",\"$studyalternateid\",\"$studymodality\",\"$studydatetime\",\"$studyoperator\",\"$studyperformingphysician\",\"$studysite\"";
                                    }
                                    ?>
                                    <tr>
                                        <? if ($columns != "min") { ?>
                                            <td><?=$name?></td>
                                        <? } ?>
                                        <td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
                                        <? if ($columns != "min") { ?>
                                            <? if ($age <= 0) {$color = "red";} else {$color="black";} ?>
                                            <td style="color:<?=$color?>"><?=number_format($studyageatscan,1)?>Y</td>
                                            <td style="color:<?=$color?>"><?=number_format($age,1)?>Y</td>
                                            <? if (!in_array(strtoupper($gender),array('M','F','O'))) {$color = "red";} else {$color="black";} ?>
                                            <td style="color:<?=$color?>"><?=$gender?></td>
                                            <td style="font-size:8pt"><?=$ethnicity1?> <?=$ethnicity2?></td>
                                            <td style="font-size:8pt"><?=$subgroup?></td>
                                            <td style="font-size:8pt"><?=$studyvisittype?></td>
                                            <? if ($studyweight <= 0) {$color = "red";} else {$color="black";} ?>
                                            <td style="color:<?=$color?>"><?=number_format($studyweight,1)?>kg</td>
                                            <td><?=$handedness?></td>
                                            <td><?=$education?></td>
                                            <td style="font-size:8pt"><?=implode2(', ',$altuids)?></td>
                                            <td><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
                                            <td style="font-size:8pt"><?=$studydesc?></td>
                                            <td><?=$studyalternateid?></td>
                                            <td><?=$studymodality?></td>
                                            <td><?=$studydatetime?></td>
                                            <td><?=$studyoperator?></td>
                                            <td><?=$studyperformingphysician?></td>
                                            <td style="font-size:8pt"><?=$studysite?></td>
                                        <? } ?>
                                        <?
                                        if (count($measurenames) > 0) {
                                            if ($groupmeasures == "byvalue") {
                                                foreach ($measurenames as $measurename) {
                                                    $csv .= ",\"" . $measuredata[$subjectid][$measurename] . "\"";
                                                    ?>
                                                    <td class="seriesrow">
                                                        <?
                                                        if (isset($measuredata[$subjectid][$measurename])) {
                                                            echo $measuredata[$subjectid][$measurename];
                                                        }
                                                        ?>
                                                    </td>
                                                    <?
                                                }
                                            }
                                            else {
                                                foreach ($measurenames as $measure) {
                                                    $csv .= ",\"" . $measuredata[$subjectid][$measure]['value'] . "\"";
                                                    ?>
                                                    <td class="seriesrow">
                                                        <?
                                                        if (isset($measuredata[$subjectid][$measure]['value'])) {
                                                            foreach ($measuredata[$subjectid][$measure]['value'] as $value) {
                                                                echo "$value<br>";
                                                            }
                                                        }
                                                        ?>
                                                    </td>
                                                    <?
                                                }
                                            }
                                        }
                                        ?>
                                        <!--<td><a href="groups.php?action=removegroupitem&itemid=<?=$itemid?>&id=<?=$id?>" style="color:red">X</a></td>-->
                                        <td><input type="checkbox" name="itemid[]" value="<?=$itemid?>"></td>
                                    </tr>
                                    <?
                                }
                                ?>
                                <tr>
                                    <td colspan="100" align="right">
                                        <input type="submit" value="Remove">
                        </form>
                    </td>
                </tr>
                </tbody>
            </table>
            </td>
            </tr>
            </table>
            <?

            /* ---------- generate csv file ---------- */
            $filename = $groupname . "_" . GenerateRandomString(10) . ".csv";
            file_put_contents("/tmp/" . $filename, $csv);
            ?>
            <div width="50%" align="center" style="background-color: #FAF8CC; padding: 5px;">
                Download .csv file <a href="download.php?type=file&filename=<?="/tmp/$filename";?>"><img src="images/download16.png"></a>
            </div>
        <?
        }

        /* ------------------ series group type ------------------- */
        if ($grouptype == "series") {
        /* get a distinct list of modalities... then get a list of series for each modality */
        $sqlstring = "select distinct(modality) from group_data where group_id = $id order by modality";
        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $modalities[] = $row['modality'];
        }

        foreach ($modalities as $modality) {
            $modality = strtolower($modality);
            /* get the demographics (series level) */
            $sqlstring = "select b.*,c.enroll_subgroup, e.*, (datediff(b.series_datetime, e.birthdate)/365.25) 'age' from group_data a left join ".$modality."_series b on a.data_id = b.".$modality."series_id left join studies c on b.study_id = c.study_id left join enrollment d on c.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.group_id = 3 and a.modality = '".$modality."' and e.subject_id is not null group by e.uid";
            $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $studyid = $row['study_id'];
                $studynum = $row['study_num'];
                $studydesc = $row['study_desc'];
                $studyalternateid = $row['study_alternateid'];
                $studymodality = $row['study_modality'];
                $studydatetime = $row['study_datetime'];
                $studyoperator = $row['study_operator'];
                $studyperformingphysician = $row['study_performingphysician'];
                $studysite = $row['study_site'];
                $studyinstitution = $row['study_institution'];
                $studynotes = $row['study_notes'];
                $subgroup = $row['enroll_subgroup'];

                $subjectid = $row['subject_id'];
                $name = $row['name'];
                $birthdate = $row['birthdate'];
                $age = $row['age'];
                $gender = $row['gender'];
                $ethnicity1 = $row['ethnicity1'];
                $ethnicity2 = $row['ethnicity2'];
                $weight = $row['weight'];
                $handedness = $row['handedness'];
                $education = $row['education'];
                $uid = $row['uid'];

                /* do some demographics calculations */
                $n++;
                if ($age > 0) {
                    $totalage += $age;
                    $numage++;
                    $ages[] = $age;
                }
                if ($weight > 0) {
                    $totalweight += $weight;
                    $numweight++;
                    $weights[] = $weight;
                }
                $genders{$gender}++;
                $educations{$education}++;
                $ethnicity1s{$ethnicity1}++;
                $ethnicity2s{$ethnicity2}++;
                $handednesses{$handedness}++;
            }
        }
        /* calculate some stats */
        if ($numage > 0) { $avgage = $totalage/$numage; } else { $avgage = 0; }
        if (count($ages) > 0) { $varage = sd($ages); } else { $varage = 0; }
        if ($numweight > 0) { $avgweight = $totalweight/$numweight; } else { $avgweight = 0; }
        if (count($weights) > 0) { $varweight = sd($weights); } else { $varweight = 0; }

        ?>
            <table>
                <tr>
                    <td valign="top" style="padding-right:20px">
                        <?
                        DisplayDemographicsTable($n,$numage,$avgage,$varage,$genders,$ethnicity1s,$ethnicity2s,$educations,$handednesses,$avgweight,$varweight);
                        ?>
                    </td>
                    <td valign="top" style="padding-right:20px">
                        <details>
                            <summary>SQL</summary>
                            <?=PrintSQL($sqlstring)?>
                        </details>
                    </td>
                    <td valign="top">
                        <table class="smallgraydisplaytable">
                            <th>Initials</th>
                            <th>UID</th>
                            <th>Age<br><span class="tiny">at study</span></th>
                            <th>Sex</th>
                            <th>Ethnicities</th>
                            <th>SubGroup</th>
                            <th>Weight</th>
                            <th>Handedness</th>
                            <th>Education</th>
                            <th>Alt UIDs</th>
                            <th>Study ID</th>
                            <th>Description/Protocol</th>
                            <th>Modality</th>
                            <th>Date/time</th>
                            <th>Series #</th>
                            <th>Remove<br>from group</th>
                            <?
                            /* get a distinct list of modalities... then get a list of series for each modality */

                            /* reset the result pointer to 0 to iterate through the results again */
                            foreach ($modalities as $modality) {
                                $modality = strtolower($modality);
                                /* get the demographics (series level) */
                                $sqlstring = "select b.*, c.study_num, e.*, (datediff(b.series_datetime, e.birthdate)/365.25) 'age' from group_data a left join ".$modality."_series b on a.data_id = b.".$modality."series_id left join studies c on b.study_id = c.study_id left join enrollment d on c.enrollment_id = d.enrollment_id left join subjects e on d.subject_id = e.subject_id where a.group_id = 3 and a.modality = '".$modality."' and e.subject_id is not null";
                                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                                mysqli_data_seek($result,0);
                                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                    $seriesdesc = $row['series_desc'];
                                    $seriesprotocol = $row['series_protocol'];
                                    $seriesdatetime = $row['series_datetime'];
                                    $seriesnum = $row['series_num'];
                                    $studynum = $row['study_num'];
                                    $seriesmodality = strtoupper($modality);

                                    $itemid = $row['subjectgroup_id'];
                                    $subjectid = $row['subject_id'];
                                    $name = $row['name'];
                                    $birthdate = $row['birthdate'];
                                    $age = $row['age'];
                                    $gender = $row['gender'];
                                    $ethnicity1 = $row['ethnicity1'];
                                    $ethnicity2 = $row['ethnicity2'];
                                    $weight = $row['weight'];
                                    $handedness = $row['handedness'];
                                    $education = $row['education'];
                                    $uid = $row['uid'];
                                    /* get list of alternate subject UIDs */
                                    $altuids = GetAlternateUIDs($subjectid);

                                    $parts = explode("^",$name);
                                    $name = substr($parts[1],0,1) . substr($parts[0],0,1);
                                    ?>
                                    <tr>
                                        <td><?=$name?></td>
                                        <td><a href="subjects.php?id=<?=$subjectid?>"><?=$uid?></a></td>
                                        <? if ($age <= 0) {$color = "red";} else {$color="black";} ?>
                                        <td style="color:<?=$color?>"><?=number_format($age,1)?>Y</td>
                                        <? if (!in_array(strtoupper($gender),array('M','F','O'))) {$color = "red";} else {$color="black";} ?>
                                        <td style="color:<?=$color?>"><?=$gender?></td>
                                        <td style="font-size:8pt"><?=$ethnicitiy1?> <?=$ethnicitiy1?></td>
                                        <td style="font-size:8pt"><?=$subgroup?></td>
                                        <? if ($weight <= 0) {$color = "red";} else {$color="black";} ?>
                                        <td style="color:<?=$color?>"><?=number_format($weight,1)?>kg</td>
                                        <td><?=$handedness?></td>
                                        <td><?=$education?></td>
                                        <td style="font-size:8pt"><?=implode2(', ',$altuids)?></td>
                                        <td><a href="studies.php?id=<?=$studyid?>"><?=$uid?><?=$studynum?></a></td>
                                        <td style="font-size:8pt"><?=$seriesdesc?> <?=$seriesprotocol?></td>
                                        <td><?=$seriesmodality?></td>
                                        <td style="font-size:8pt"><?=$seriesdatetime?></td>
                                        <td><?=$seriesnum?></td>
                                        <td><a href="groups.php?action=removegroupitem&itemid=<?=$itemid?>&id=<?=$id?>" style="color:red">X</a></td>
                                    </tr>
                                    <?
                                }
                            }
                            ?>
                        </table>
                    </td>
                </tr>
            </table>
            <?
        }
    }

    /* -------------------------------------------- */
    /* ------- DisplayMRProtocolSummary ----------- */
    /* -------------------------------------------- */
    function DisplayMRProtocolSummary($studylist) {
        $studylist = array_filter($studylist);
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
            <table class="smallgraydisplaytable sortable">
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
                    $img_slices = $row['img_slices'];
                    $count = $row['count'];
                    ?>
                    <tr>
                        <td><?=$seriesdesc?></td>
                        <td><?=$series_tr?></td>
                        <td><?=$series_te?></td>
                        <td><?=$series_flip?></td>
                        <td><?=$phaseencodedir?></td>
                        <td><?=$PhaseEncodingDirectionPositive?></td>
                        <td><?=$series_spacingx?></td>
                        <td><?=$series_spacingy?></td>
                        <td><?=$series_spacingz?></td>
                        <td><?=$img_rows?></td>
                        <td><?=$img_cols?></td>
                        <td><?=$img_slices?></td>
                        <td><?=$count?></td>
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
    /* ------- DisplayDemographicsTable ----------- */
    /* -------------------------------------------- */
    function DisplayDemographicsTable($n,$numage,$avgage,$varage,$genders,$ethnicity1s,$ethnicity2s,$educations,$handednesses,$avgweight,$varweight) {
        ?>
        <details>
            <summary>Demographics</summary>
            <table class="demographicstable">
                <tr>
                    <td colspan="2" class="title">Demographics</td>
                </tr>
                <tr>
                    <td class="label">N</td>
                    <td class="value"><?=$n?></td>
                </tr>
                <tr>
                    <td class="label">Age<br><span class="tiny">computed from<br><?=$numage?> non-zero ages</span></td>
                    <td class="value"><?=number_format($avgage,1)?>Y <span class="small">&plusmn;<?=number_format($varage,1)?>Y</span></td>
                </tr>
                <tr>
                    <td class="label">Sex</td>
                    <td class="value">
                        <?
                        foreach ($genders as $key => $value) {
                            $pct = number_format(($value/$n)*100, 1);
                            echo "$key: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Ethnicity 1</td>
                    <td class="value">
                        <?
                        //print_r($educations);
                        foreach ($ethnicity1s as $key => $value) {
                            $key = "$key";
                            switch ($key) {
                                case "": $ethnicity1 = "Not specified"; break;
                                case "hispanic": $ethnicity1 = "Hispanic/Latino"; break;
                                case "nothispanic": $ethnicity1 = "Not hispanic/Latino"; break;
                            }
                            $pct = number_format(($value/$n)*100, 1);
                            echo "$ethnicity1: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Ethnicity 2</td>
                    <td class="value">
                        <?
                        //print_r($educations);
                        foreach ($ethnicity2s as $key => $value) {
                            $key = "$key";
                            switch ($key) {
                                case "": $ethnicity2 = "Not specified"; break;
                                case "indian": $ethnicity2 = "American Indian/Alaska Native"; break;
                                case "asian": $ethnicity2 = "Asian"; break;
                                case "black": $ethnicity2 = "Black/African American"; break;
                                case "islander": $ethnicity2 = "Hawaiian/Pacific Islander"; break;
                                case "white": $ethnicity2 = "White"; break;
                                case "mixed": $ethnicity2 = "Mixed"; break;
                            }
                            $pct = number_format(($value/$n)*100, 1);
                            echo "$ethnicity2: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Education</td>
                    <td class="value">
                        <?
                        //print_r($educations);
                        foreach ($educations as $key => $value) {
                            $key = "$key";
                            if (trim($key == "")) { $education = "Not specified"; }
                            else {
                                switch ($key) {
                                    case "0": $education = "Unknown"; break;
                                    case "1": $education = "Grade School"; break;
                                    case "2": $education = "Middle School"; break;
                                    case "3": $education = "High School/GED"; break;
                                    case "4": $education = "Trade School"; break;
                                    case "5": $education = "Associates Degree"; break;
                                    case "6": $education = "Bachelors Degree"; break;
                                    case "7": $education = "Masters Degree"; break;
                                    case "8": $education = "Doctoral Degree"; break;
                                }
                            }

                            $pct = number_format(($value/$n)*100, 1);
                            echo "$education: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Handedness</td>
                    <td class="value">
                        <?
                        //print_r($educations);
                        foreach ($handednesses as $key => $value) {
                            $key = "$key";
                            switch ($key) {
                                case "": $handedness = "Not specified"; break;
                                case "U": $handedness = "Unknown"; break;
                                case "R": $handedness = "Right"; break;
                                case "L": $handedness = "Left"; break;
                                case "A": $handedness = "Ambidextrous"; break;
                            }
                            $pct = number_format(($value/$n)*100, 1);
                            echo "$handedness: <b>$value</b> <span class=\"small\">($pct%)</span><br>";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Weight<br><span class="tiny">computed from<br>non-zero weights</span></td>
                    <td class="value"><?=number_format($avgweight,1)?>kg <span class="small">&plusmn;<?=number_format($varweight,1)?>kg</span></td>
                </tr>
            </table>
        </details>
        <?
    }

    /* -------------------------------------------- */
    /* ------- DisplayGroupList ------------------- */
    /* -------------------------------------------- */
    function DisplayGroupList() {

        $urllist['Groups'] = "groups.php";
        $urllist['Add Group'] = "groups.php?action=addform";
        NavigationBar("Groups", $urllist,0,'','','','');

        ?>

        <table class="graydisplaytable">
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
            <form action="groups.php" method="post">
                <input type="hidden" name="action" value="add">
                <tr>
                    <td style="border-bottom: 2pt solid gray"><input type="text" name="groupname"></td>
                    <td style="border-bottom: 2pt solid gray">
                        <select name="grouptype">
                            <option value="subject">Subject
                            <option value="study">Study
                            <option value="series">Series
                        </select>
                    </td>
                    <td style="border-bottom: 2pt solid gray"><?=$GLOBALS['username']?></td>
                    <td style="border-bottom: 2pt solid gray"><input type="submit" value="Create group"></td>
                    <td style="border-bottom: 2pt solid gray">Delete group</td>
                </tr>
            </form>
            <?
            $sqlstring = "select a.*, b.username 'ownerusername', b.user_fullname 'ownerfullname' from groups a left join users b on a.group_owner = b.user_id order by a.group_name";
            $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $id = $row['group_id'];
                $name = $row['group_name'];
                $ownerusername = $row['ownerusername'];
                $grouptype = $row['group_type'];

                $sqlstring2 = "select count(*) 'count' from group_data where group_id = $id";
                $result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
                $row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC);
                $count = $row2['count'];
                ?>
                <tr style="<?=$style?>">
                    <td><a href="groups.php?action=viewgroup&id=<?=$id?>"><?=$name?></a></td>
                    <td><?=$grouptype?></td>
                    <td><?=$ownerusername?></td>
                    <td><?=$count?></td>
                    <td align="right">
                        <? if ($ownerusername == $GLOBALS['username']) { ?>
                            <a href="groups.php?action=delete&id=<?=$id?>" style="color:red">X</a>
                        <? } ?>
                    </td>
                </tr>
                <?
            }
            ?>
            </tbody>
        </table>
        <?
    }

    /* CHANGE MARCH 2017
    /* UPDATE GROUP STUDIES VIA TEXT INPUT
    /* -------------------------------------------- */
    /* ------- DisplayGroupStudiesSummary --------- */
    /* -------------------------------------------- */

    function DisplayGroupStudiesSummary($id) {

		?>
		<i>This is the complete list of the studies in this group. If you delete or add any study, the group will be changed accordingly.</i>
        <form action="groups.php"  method="post">
		<input type="hidden" name="action" value="updatestudygroup">
		<input type="hidden" name="id" value="<?=$id?>">

		<?
            $studies = "";
			
            $sqlstring = "select a.subjectgroup_id, d.uid, b.study_num from group_data a left join studies b on a.data_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.group_id = $id order by d.uid,b.study_num";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $studynum = $row['study_num'];
                $uid = $row['uid'];
                $studies .=  $uid . $studynum . "\n";
            }

            ?>
            <br>
            <textarea name='studylist' style='width:15em; margin-left:1em' rows='10'><?=$studies?></textarea>
            <br>
            <input type="submit" value="Update">
        </form>
        <?
    }
?>
<? include("footer.php") ?>

