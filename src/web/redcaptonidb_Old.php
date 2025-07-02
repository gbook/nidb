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

	<div class="ui four column centered container">
		<form action="redcaptonidb.php" method="post">
		<input type="hidden" name="action" value="updateconnection">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		
		<h2 class="ui top attached inverted header" align="center"> Redcap ===> NiDB Transfer </h2>
		<br> 

			
		<div class="four row column">
                        <div class="ui labeled input">
                          <div class="ui  label">
                            *Redcap Server
                          </div>
                          <input type="text"  name="redcapurl" value="<?=$redcapurl?>"  size="50" required>
                        </div>

                        <br>
                         <div class="ui labeled input">
                          <div class="ui  label">
                            *Redcap Token  
                          </div>
                                <input type="text" name="redcaptoken" value="<?=$redcaptoken?>" size="50" required>
                        </div>

		</div>

			<button class="ui primary right floated button" type="submit">
                          <i class="linkify icon"></i>
                          Update Connection Settings
		       </button>
		</form>
	</div>
	<br><br>
	<div onclick="window.location.href='redcaptonidb.php?action=showrcinfo&projectid=<?=$projectid?>'" class="ui bottom attached button">Show Project Info</div> 

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

	$Event_s=getrcevents($projectid);
                        
	list($In_Name,$In_Label)=getrcinstruments($projectid);

?>

	<table class="ui celled padded definition table">
	  <thead>
	    <tr><th></th>
	    <th> Project Information</th>
	  </tr></thead>
	  <tbody>
	    <tr>
              <td>Arms</td>
              <td> <?echo $Ar_m[0]?></td>
            </tr>
	    <tr>
	      <td>Events</td>
	      <td><?for($Ev=0;$Ev < count($Event_s);$Ev++){ echo $Event_s[$Ev];echo ","; }?></td>
	    </tr>
	    <tr>
	      <td>Instruments</td>
	      <td><?for($In=0;$In < count($In_Name); $In++){if(($In+1)%8==0){  echo $In_Name[$In];echo ","."<br>"; }else {echo $In_Name[$In];echo ",";}}?></td>
	    </tr>
	</tbody></table>




	<br>
	
	<div onclick="window.location.href='redcaptonidb.php?action=default&projectid=<?=$projectid?>'" class="ui bottom attached button">Hide Project Info</div>
	<br><br>


	<b> Establish mapping to NiDB click "Map This Project" ==========> <b> 
	<br><br>


	<button class="ui primary right floated button" onclick="window.location.href='redcapmaping.php?action=default&projectid=<?=$projectid?>'">
          <i class="map icon"></i>
            Map This Project
        </button>


<?

}






?>





