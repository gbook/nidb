<?
 // ------------------------------------------------------------------------------
 // NiDB longqc.php
 // Copyright (C) 2004 - 2015
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
		<title>NiDB - Longitudinal QC</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes.php";
	require "menu.php";

	//echo "<pre>";
	//print_r($_POST);
	//echo "</pre>";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$groupid = GetVariable("groupid");
	$protocol = GetVariable("protocol");
	
	/* determine action */
	switch ($action) {
		case 'viewlongqc':
			DisplayLonitudinalQC($groupid, $protocol);
			break;
		case 'viewprotocols':
			DisplayProtocolList($groupid);
			break;
		case 'viewgroups':
			DisplayGroupList();
			break;
		default: DisplayGroupList(); break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- DisplayGroupList ------------------- */
	/* -------------------------------------------- */
	function DisplayGroupList() {
	
		$urllist['groups'] = "longqc.php";
		NavigationBar("Longitudinal QC", $urllist,0,'','','','');
		
	?>

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Name</th>
				<th>Type</th>
				<th>Owner</th>
				<th>Group size</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select a.*, b.username 'ownerusername', b.user_fullname 'ownerfullname' from groups a left join users b on a.group_owner = b.user_id order by a.group_name";
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$id = $row['group_id'];
					$name = $row['group_name'];
					$ownerusername = $row['ownerusername'];
					$grouptype = $row['group_type'];
					
					$sqlstring2 = "select count(*) 'count' from group_data where group_id = $id";
					$result2 = mysql_query($sqlstring2) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring2</i><br>");
					$row2 = mysql_fetch_array($result2, MYSQL_ASSOC);
					$count = $row2['count'];
			?>
			<tr>
				<td><a href="longqc.php?action=viewprotocols&groupid=<?=$id?>"><?=$name?></a></td>
				<td><?=$grouptype?></td>
				<td><?=$ownerusername?></td>
				<td><?=$count?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayProtocolList ---------------- */
	/* -------------------------------------------- */
	function DisplayProtocolList($groupid) {
	
		$urllist['Groups'] = "longqc.php";
		NavigationBar("Longitudinal QC", $urllist,0,'','','','');
		
		$sqlstring = "select a.*, b.* from groups a left join group_data b on a.group_id = b.group_id where a.group_id = $groupid";
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$name = $row['group_name'];
			$ownerusername = $row['ownerusername'];
			$grouptype = $row['group_type'];
			$itemids[] = "'".$row['data_id']."'";
			$modalities[$row['modality']] = $row['modality'];
		}
		?>
		<table class="smallgraydisplaytable">
			<thead>
			<tr>
				<th>Desc</th>
				<th>Alt Desc</th>
				<th>Protocol</th>
				<th>Sequence name</th>
			</tr>
			</thead>
		<?
		//PrintVariable($itemids);
		//PrintVariable($modalities);
		$studyids = implode(",",$itemids);
		if ($grouptype == "study") {
			foreach ($modalities as $key => $value) {
				$sqlstring = "select distinct series_desc, series_altdesc, series_protocol, series_sequencename from $key" . "_series where study_id in ($studyids)";
				$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$desc = $row['series_desc'];
					$altdesc = $row['series_altdesc'];
					$protocol = $row['series_protocol'];
					$sequencename = $row['series_sequencename'];
					?>
					<tr>
						<td><?=$desc?></td>
						<td><?=$altdesc?></td>
						<td><?=$protocol?></td>
						<td><?=$sequencename?></td>
					</tr>
					<?
				}
			}
		}
		?>
		</table>
		<?
	}

	
?>
<? include("footer.php") ?>