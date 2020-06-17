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
	//require "config.php";
	require "functions.php";
	require "includes.php";
	require "includes_php.php";
//	require "includes_html.php";
	require "menu.php";
	//PrintVariable($_POST,'POST');

/* Initiating the projectid, We can get the projectid from user */

$projectid = 175;

$sqlstring =  "SELECT redcap_token, redcap_server FROM `projects` WHERE  project_id = '$projectid' ";
$result =  MySQLiQuery($sqlstring, __FILE__, __LINE__);
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
$RCtoken = $row['redcap_token'];
$RCserver = $row['redcap_server'];




$data = array(
    'token' => $RCtoken,
    'content' => 'instrument',
    'format' => 'json',
    'returnFormat' => 'json'
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $RCserver);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
$output = curl_exec($ch);
/*print $output;*/
$InstList = json_decode($output,true);
curl_close($ch);


?>
<h2 align="left">Redcap Instruments</h2> 

<table class="graydisplaytable" width="50%">

  <thead>
	<tr> <th align="left">Redcap Instrument Name </th>
	     <th align="left"> Redcap Instrument Label </th>
	</tr>
  </thead>


        <?
        for ($In=0;$In <= count($InstList); $In++){
                $IN = array_values($InstList[$In]);
        ?><tr>
		<td><? echo $IN{0}?></td> <td> <? echo $IN{1}?></td>
	  </tr>
	<?}?>



</table>


<?
	/*************************************************************************/
	/*************************************************************************/
	/***************************FUNCTIONS*************************************/
	/*************************************************************************/
	/*************************************************************************/



?>	


