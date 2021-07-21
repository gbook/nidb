<?
 // ------------------------------------------------------------------------------
 // NiDB adminsites.php
 // Copyright (C) 2004 - 2019
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
		<title>NiDB- RedCap Instruments</title>
	</head>

	<div id="wrapper">

<?
//	require "config.php";
	require "functions.php";
//	require "includes.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	$action = GetVariable("action");
        $projectid = GetVariable("projectid");
	$redcapurl = GetVariable("redcapurl");
	$redcaptoken = GetVariable("redcaptoken");
	


 switch ($action) 
{
                case 'rcarms':
                        $Ar_m=getrcarms($projectid);
			echo var_dump($Ar_m);
			break;
		case 'rcevents':
                        $Event_s=getrcEvents($projectid);
			echo var_dump($Event_s);
                        break;
		case 'rcinst':
                        list($In_Name,$In_Label)=getrcinstruments($projectid);
			echo var_dump($In_Name);
			echo "<br>";
			echo var_dump($In_Label);
                        break;		
		case 'rcrecords':
			$Event_s=getrcevents($projectid);
			list($In_Name,$In_Label)=getrcinstruments($projectid);
			list($V_names,$RCrecords)=getrcrecords($projectid,$In_Name[7],$Event_s[0]);
			echo var_dump($V_names);
			echo "<br>"; echo "<br>";
			echo var_dump($RCrecords);
			//echo $RCrecords[0][$V_names[1]];
			break;
		case 'showrcinfo':
			getprojectinfo($projectid);
			Showprojectinfo($projectid);
			break;
		case 'updatercconnect':
			setprojectinfo($projectid, $redcapurl, $redcaptoken);
			getprojectinfo($projectid);
			break;
		default:
			getprojectinfo($projectid);
			break;
}


/* -----------------getprojectinfo---------------*/

function getprojectinfo($projectid)
{
	if ((trim($projectid) == "") || ($projectid < 0)) {
			?>Invalid or blank project ID [<?=$projectid?>]<?
			return;
		}
	

	
		$sqlstring = "select project_name,redcap_server, redcap_token from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
		$redcapurl = $row['redcap_server'];
		$redcaptoken = $row['redcap_token'];
		
		?>

		<span style="font-size: larger; font-weight: bold">RedCap ====> NiDB Transfer </span><br><br>
		<form action="redcaptonidb.php" method="post">
		<input type="hidden" name="action" value="updatercconnect">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<table>
			<tr>			
                                <td>Project Name: </td>
                                <td> <b> <?=$projectname?> </td>
                        </tr>
			<tr></tr> <tr></tr>
							
			<tr>
				<td>RedCap Server: </td>
				<td><input type="url" name="redcapurl" value="<?=$redcapurl?>" size=75 required></td>
			</tr>
			<tr></tr> <tr></tr>

			<tr>
				<td>RedCap Token: </td>
				<td><input type="text" name="redcaptoken" value="<?=$redcaptoken?>" size=75 required></td>
			</tr>
			<tr></tr> <tr></tr>

			<tr>
				<td colspan="2" align="right"><input type="submit" value="Update" ></td>
			</tr>
		</table>
		</form>

	<br>
	<button onclick="window.location.href='redcaptonidb.php?action=showrcinfo&projectid=<?=$projectid?>'">Show Project Info</button> 

<?}


/*-----------------setprojectinfo-----------------*/


function setprojectinfo($projectid, $redcapurl, $redcaptoken)
{

	$redcapurl = mysqli_real_escape_string($GLOBALS['linki'], $redcapurl);
	$redcaptoken = mysqli_real_escape_string($GLOBALS['linki'], $redcaptoken);
		
	if ((trim($projectid) == "") || ($projectid < 0)) {
	?><b> Invalid or blank project ID [<?=$projectid?>]<?
	return;
	}
	
	$sqlstring = "update projects set redcap_server = '$redcapurl', redcap_token = '$redcaptoken' where project_id = '$projectid'";
	$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

}


/* --------------------ShowRCProjectInfo--------------*/

function Showprojectinfo($projectid)
{
	$Ar_m=getrcarms($projectid);

	$Event_s=getrcEvents($projectid);
                        
	list($In_Name,$In_Label)=getrcinstruments($projectid);

?>	<table>

		<tr>
			<td> <b> Arm: </td>
			<td> <?echo $Ar_m[0]?></td>
		</tr>
		
		<br><br>
                <tr>
                        <td> <b> Events: </td>
                        <td> <?for($Ev=0;$Ev < count($Event_s);$Ev++){ echo $Event_s[$Ev];echo ","; }?></td>
                </tr>	

		<br><br>
                <tr>
                        <td> <b> Instruments: </td>
                        <td> <?for($In=0;$In < count($In_Name); $In++){ 
				echo $In_Name[$In];echo ",";
				 }?>
			</td>
                </tr>
	
	</table>

	<br>
	<button onclick="window.location.href='redcaptonidb.php?action=default&projectid=<?=$projectid?>'">Hide Project Info</button>
	<br><br>
	<b> To establish mapping to NiDB click "Map The Project      " <b> 
	<br><br>

	<style>
                .button { border: none; background-color: #4CAF50; color: white; padding: 15px 32px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer;}

        </style>

        <button class="button" onclick="window.location.href='redcapmaping.php?action=default&projectid=<?=$projectid?>'" style="float:left">Map The Project</button>

<?

}






?>





