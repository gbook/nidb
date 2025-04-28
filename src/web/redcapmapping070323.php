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
	$inst = GetVariable("inst");
	$uinst = GetVariable("uinst");
	$mappingid = GetVariable("mappingid");
        $redcapevent = GetVariable("redcapevent");
        $redcapform = GetVariable("redcapform");
	$redcapfieldval = GetVariable("redcapfieldval");
	$redcapfielddate = GetVariable("redcapfielddate");
	$redcapfieldStime = GetVariable("redcapfieldStime");
	$redcapfieldEtime = GetVariable("redcapfieldEtime");
	$redcapfieldrater = GetVariable("redcapfieldrater");
	$redcapfieldnotes = GetVariable("redcapfieldnotes");
	$redcapfieldtype = GetVariable("redcapfieldtype");
        $nidbdatatype = GetVariable("nidbdatatype");
        $nidbvariablename = GetVariable("nidbvariablename");
        $nidbinstrumentname = GetVariable("nidbinstrumentname");
	$jointid = GetVariable("jointid");
	$subjectid = GetVariable("subjectid");
	$nidbinstrument = GetVariable("nidbinstrument");

	


 switch ($action) 
{
		case 'displaymapping':
			projectinfo($projectid);
//			print_r($inst);
//			echo $nidbdatatype;
			DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype);
			break;
		case 'updatemapping':
                       UpdateMapping($projectid,$inst,$uinst,$redcapevent, $inst, $redcapfieldval, $redcapfielddate, $redcapfieldrater, $redcapfieldnotes, $redcapfieldStime, $redcapfieldEtime, $redcapfieldtype, $nidbdatatype, $nidbvariablename, $nidbinstrumentname);
//		       echo $redcapfieldStime; echo $redcapfieldEtime;
//                       projectinfo($projectid);
//                       DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype);
			break;
                case 'deletemapping':
                        DeleteMapping($mappingid,$projectid,$inst,$uinst,$nidbdatatype);
			break;
		case 'transferdata':
			projectinfo($projectid);
			TransferData($inst,$projectid,$nidbinstrument,$jointid);
			break;
                default:
			projectinfo($projectid);
        }
        
        
        /* ------------------------------------ functions ------------------------------------ */

        /* -------------------------------------------- */
        /* ------- UpdateMapping ---------------------- */
        /* -------------------------------------------- */
        function UpdateMapping($projectid, $inst, $uinst, $redcapevent, $redcapform, $redcapfieldval, $redcapfielddate, $redcapfieldrater, $redcapfieldnotes, $redcapfieldStime, $redcapfieldEtime, $redcapfieldtype, $nidbdatatype, $nidbvariablename, $nidbinstrumentname) {
                $redcapevent = mysqli_real_escape_string($GLOBALS['linki'], $redcapevent);
                $redcapform = mysqli_real_escape_string($GLOBALS['linki'], $redcapform);
		$redcapfielval = mysqli_real_escape_string($GLOBALS['linki'], $redcapfieldval);
		$redcapfielddate = mysqli_real_escape_string($GLOBALS['linki'], $redcapfielddate);
		$redcapfieldrater = mysqli_real_escape_string($GLOBALS['linki'], $redcapfieldrater);
		$redcapfieldnotes = mysqli_real_escape_string($GLOBALS['linki'], $redcapfieldnotes);
		$redcapfieldStime = mysqli_real_escape_string($GLOBALS['linki'], $redcapfieldStime);
		$redcapfieldEtime = mysqli_real_escape_string($GLOBALS['linki'], $redcapfieldEtime);
		$redcapfieldtype = mysqli_real_escape_string($GLOBALS['linki'], $redcapfieldtype);
                $nidbdatatype = mysqli_real_escape_string($GLOBALS['linki'], $nidbdatatype);
                $nidbvariablename = mysqli_real_escape_string($GLOBALS['linki'], $nidbvariablename);
                $nidbinstrumentname = mysqli_real_escape_string($GLOBALS['linki'], $nidbinstrumentname);

                $sqlstring = "start transaction";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
//		print_r($redcapevent);
//		print_r($redcapfieldval);
		
		foreach ($_POST['redcapevent'] as $Event) {                

			$chquery = "SELECT max(`redcap_fieldgroupid`) as mgid FROM `redcap_import_mapping`";
	                $resultq = MySQLiQuery($chquery, __FILE__, __LINE__);
        	        $rowq = mysqli_fetch_array($resultq, MYSQLI_ASSOC);
			$maxgid = $rowq['mgid'];
			$maxgid = (int)$maxgid +1;


			$sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldval', 'value' ,'$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
        	        //PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

			 if (! empty($redcapfielddate)) {
				$sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfielddate', 'date' ,'$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}

			 if (! empty($redcapfieldrater)) {
                                $sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldrater', 'rater' ,'$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}

			 if (! empty($redcapfieldnotes)) {
                                $sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldnotes', 'notes' ,'$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
                                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}

			if (! empty($redcapfieldStime)) {
				$sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldStime', 'time' ,'$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}

			if (! empty($redcapfieldEtime)) {
                                $sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldEtime', 'etime' ,'$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
                                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}


        	        $sqlstring = "commit";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		}
		projectinfo($projectid);
		DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype);
                
        }

        /* -------------------------------------------- */
        /* ------- DeleteMapping ---------------------- */
        /* -------------------------------------------- */
        function DeleteMapping($mappingid,$projectid,$inst,$uinst,$nidbdatatype) {

                MySQLiQuery("start transaction", __FILE__, __LINE__);

                $sqlstring = "delete from redcap_import_mapping where formmap_id = $mappingid";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                
                MySQLiQuery("commit", __FILE__, __LINE__);
                
		?><div align="center"><span class="message">Mapping deleted</span>
		  </div>
		  <br><br>
		<?
		
		projectinfo($projectid);
	        DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype);

	}







/* -----------------getprojectinfo---------------*/

	function projectinfo($projectid){
	if ((trim($projectid) == "") || ($projectid < 0)) {
			?>Invalid or blank project ID [<? =$projectid?>]<?
			return;
		}
	

	
		$sqlstring = "select project_name,redcap_server, redcap_token from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
		$redcapurl = $row['redcap_server'];
		$redcaptoken = $row['redcap_token'];
		
?>		

	<h1 class="ui top attached inverted header" align="center"> Redcap ===> NiDB Transfer (Mapping) </h1>
	<div class="ui grid">
		<div class="three column row">
			<div class="left floated column">
				<h3 class="ui top attached inverted header" align="left">Project Name: <? =$projectname?> </h3>
			</div>
			<div class="right floated column">
				<h3 class="ui top attached inverted header" align="left">Redcap Server: <? =$redcapurl?>  </h3>
			</div>
		</div>

	<?list($In_Name,$In_Label)=getrcinstruments($projectid);?>

	<div class="three column row" align="center">
	<form  class="ui form" action="redcapmapping.php">
        <input type="hidden" name="action" value="displaymapping">
        <input type="hidden" name="projectid" value="<? =$projectid?>">
		
		<table class="ui green large centered table" align="center">
                        <tr>
				<td>
					<h4> Select Redcap Form to map (List follows Redcap Sequence)</h4>
					<div class="ui dropdown labeled search icon button">
					  <input type="hidden" name="inst">
					  <i class="redhat icon"></i>
					  <span class="text">Redcap Form to map</span>
					  <div class="menu" required>
						 <?for($In=0;$In < count($In_Name); $In++){ ?>
						     <div class="item"> <? =$In_Name[$In]?> </div>
                                               <?}?>
					  </div>
					</div>

				</td>
				<td> 
					<h4> Select the Redcap Form Containg Unique Id to join data from Redcap and NiDB</h4>
					<div class="ui dropdown labeled search icon button">
					  <input type="hidden" name="uinst">
                                          <i class="redhat icon"></i>
                                          <span class="text">Redcap Form for Unique ID</span>
                                          <div class="menu" required>
                                                 <?for($In=0;$In < count($In_Name); $In++){ ?>
                                                     <div class="item"> <? =$In_Name[$In]?> </div>
                                               <?}?>
                                          </div>
					</div>
				</td>
				<td>
					 <h4>Select the type of NiDB data </h4>
					 <div class="ui dropdown labeled search icon button">
					  <input type="hidden" name="nidbdatatype" onchange="this.form.submit()">
                                          <i class="database icon"> </i>
                                          <span class="text">NiDB data type</span>
					  <div class="menu" required>
						<div class="item" data-value="m" >Measures (Forms containing various cognitive measures)</div>
						<div class="item" data-value="v" >Vitals (Like:BP,HR, ...)</div>
						<div class="item" data-value="d" >Drug/dose (Dose Time and date information)</div>
                                          </div>
                                         </div>
				</td>
                        </tr>

		</table>
	</form>
	</div>
	</div>

<?}


	 /* -------------------------------------------- */
        /* ------- DisplayRedCapSettings -------------- */
        /* -------------------------------------------- */
        function DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype) {
		
		        
                if ((trim($projectid) == "") || ($projectid < 0)) {
                        ?>Invalid or blank project ID [<? =$projectid?>]<?
                        return;
                }
                
                $sqlstring = "select redcap_server, redcap_token from projects where project_id = $projectid";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $redcapurl = $row['redcap_server'];
                $redcaptoken = $row['redcap_token'];

		$Event_s=getrcevents($projectid);
                list($In_Name,$In_Label)=getrcinstruments($projectid);
                $V_names=getrcvariables($projectid,$inst,$Event_s[0]);
                ?>

                <br>
                
                <form class="ui form" action="redcapmapping.php" method="post">
                <input type="hidden" name="action" value="updatemapping">
                <input type="hidden" name="projectid" value="<? =$projectid?>">
		<input type="hidden" name="inst" value="<? =$inst?>">
		<input type="hidden" name="nidbdatatype" value="<? =$nidbdatatype?>">

<?
		/* Updating for number of columns based on type (drug, measure, Vital) */

		if ($nidbdatatype == 'm') {
			$Cols = "eleven";
			$tp = "Measure";
			$ColSp =8;
?>


	<div class="ui <? =$Cols?> wide column" style="overflow: auto; padding:0px">

	   <h2 class="ui top attached inverted header" align="center"> Mapping variables for "<? =$inst?>" as type "<? =$tp?>"<h2>
                <table class="ui graydisplaytable">
                        <thead>
                                <tr>
                                        <th style="text-align: center; border-right: 1px solid #bdbdbd" colspan="2">NiDB</th>
                                        <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 20pt; border-right: 1px solid #bdbdbd; padding: 0px 30px">&#10132;</th>
					<th style="text-align: center" colspan="<? =$ColSp?>" >Redcap</th>
                                </tr>
                                <tr>
                                        <th>Variable</th>
					<th style="border-right: 1px solid #bdbdbd">Instrument</th>
					
					<th>Event</th>
					<th> Value </th>
					<th> Date </th>
					<th> Rater </th>
					<th> Notes </th>
					<th> Start Time </th>
					<th> End Time (If Any) </th>
                                        <th></th>
                                </tr>
                        </thead>
                        <tbody>
				<tr>
					

                                        <td><input type="text" name="nidbvariablename" id="nidbvariablename"></td>
					<td style="border-right: 1px solid #bdbdbd"><input type="text" name="nidbinstrumentname" value=<? =$inst?>></td>
					
					<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>

					<td>
						<select name="redcapevent[]"  multiple required size="3">
			                           <?for($Eve=0;$Eve < count($Event_s); $Eve++){ ?>
			                              <option value=<? =$Event_s[$Eve]?>> <? =$Event_s[$Eve]?> </option>
 				                   <?}?>	
                                                </select>
                                        </td>
				
					<? $V_names=getrcvariables($projectid,$inst,$redcapevent);?>

					<td>
						<select name="redcapfieldval" required  onchange="document.getElementById('nidbvariablename').value=this.options[this.selectedIndex].text;">
						    <option value=''> </option>
						   <?for($Fi=0;$Fi < count($V_names); $Fi++){?> 
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>
						   
                                                   <?}?>
                                                </select>
					</td>

					 <td>
                                                <select name="redcapfielddate" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
					</td>

					<td>
                                                <select name="redcapfieldrater" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>
                                                   <?}?>
                                                </select>
					</td>

					<td>
                                                <select name="redcapfieldnotes" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
                                        </td>

					<td>
                                                <select name="redcapfieldStime">
                                                      <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
                                        </td>
					 <td style="border-right: 1px solid #bdbdbd">
						<select name="redcapfieldEtime">
						      <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
					</td>

                                        <td title="Save mapping"><input type="submit" value="Add"> </td>
                                </tr>
                                <?
					$sqlstring = "select * from redcap_import_mapping where project_id = $projectid and redcap_form = '$inst' order by formmap_id desc";
                                        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                                $formmapid = $row['formmap_id'];
                                                $event = $row['redcap_event'];
                                                $form = $row['redcap_form'];
                                                $fields = $row['redcap_fields'];
						$fieldtype = $row['redcap_fieldtype'];
                                                $type = $row['nidb_datatype'];
                                                $variable = $row['nidb_variablename'];
                                                $nidbinstrument = $row['nidb_instrumentname'];
                                                
                                                switch ($type) {
                                                        case 'm': $typeStr = "Measure"; break;
                                                        case 'v': $typeStr = "Vital"; break;
                                                        case 'd': $typeStr = "Drug/dose"; break;
						 }
                                                ?>
						<tr>
                                                        <td><? =$variable?></td>
							<td style="border-right: 1px solid #bdbdbd"><? =$nidbinstrument?></td>
							<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>
                                                        <td><? =$event?></td>
                                                        <td><? =$form?></td>
							<td> <? =$fields?></td>
							<td> </td>
							<td> </td>
							<td> </td>
                                                        <td style="border-right: 1px solid #bdbdbd"><? =$fieldtype?></td>
                                                        <td title="Delete mapping"><a href="redcapmapping.php?action=deletemapping&mappingid=<? =$formmapid?>&projectid=<? =$projectid?>&inst=<? =$inst?>" class="redlinkbutton" style="font-size: smaller">X</a></td>
                                                </tr>
                                        <?
                                        }
?>                                        
                        </tbody>
		</table>
		</form>
	   </div>
<?
}
                 elseif ($nidbdatatype == 'd') {
                        $Cols ="seventeen";
                        $tp = "Drug";
                        $ColSp =14;
			print_r("Working on Features");
?>


	<div class="ui <? =$Cols?> wide column" style="overflow: auto; padding:0px">

	   <h2 class="ui top attached inverted header" align="center"> Mapping variables for "<? =$inst?>" as type "<? =$tp?>"<h2>
                <table class="ui graydisplaytable">
                        <thead>
                                <tr>
                                        <th style="text-align: center; border-right: 1px solid #bdbdbd" colspan="2">NiDB</th>
                                        <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 20pt; border-right: 1px solid #bdbdbd; padding: 0px 30px">&#10132;</th>
					<th style="text-align: center" colspan="<? =$ColSp?>" >Redcap</th>
                                </tr>
                                <tr>
                                        <th>Variable</th>
					<th style="border-right: 1px solid #bdbdbd">Instrument</th>
					
					<th>Event</th>
					<th> Value </th>
					<th> Date </th>
					<th> Rater </th>
					<th> Notes </th>
					<th> Start Time </th>
					<th> End Time (If Any) </th>
                                        <th></th>
                                </tr>
                        </thead>
                        <tbody>
				<tr>
					

                                        <td><input type="text" name="nidbvariablename" id="nidbvariablename"></td>
					<td style="border-right: 1px solid #bdbdbd"><input type="text" name="nidbinstrumentname" value=<? =$inst?>></td>
					
					<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>

					<td>
						<select name="redcapevent[]"  multiple required size="3">
			                           <?for($Eve=0;$Eve < count($Event_s); $Eve++){ ?>
			                              <option value=<? =$Event_s[$Eve]?>> <? =$Event_s[$Eve]?> </option>
 				                   <?}?>	
                                                </select>
                                        </td>
				
					<? $V_names=getrcvariables($projectid,$inst,$redcapevent);?>

					<td>
						<select name="redcapfieldval" required  onchange="document.getElementById('nidbvariablename').value=this.options[this.selectedIndex].text;">
						    <option value=''> </option>
						   <?for($Fi=0;$Fi < count($V_names); $Fi++){?> 
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>
						   
                                                   <?}?>
                                                </select>
					</td>

					 <td>
                                                <select name="redcapfielddate" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
					</td>

					<td>
                                                <select name="redcapfieldrater" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>
                                                   <?}?>
                                                </select>
					</td>

					<td>
                                                <select name="redcapfieldnotes" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
                                        </td>

					<td>
                                                <select name="redcapfieldStime">
                                                      <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
                                        </td>
					 <td style="border-right: 1px solid #bdbdbd">
						<select name="redcapfieldEtime">
						      <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
					</td>

                                        <td title="Save mapping"><input type="submit" value="Add"> </td>
                                </tr>
                                <?
					$sqlstring = "select * from redcap_import_mapping where project_id = $projectid and redcap_form = '$inst' order by formmap_id desc";
                                        $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                                $formmapid = $row['formmap_id'];
                                                $event = $row['redcap_event'];
                                                $form = $row['redcap_form'];
                                                $fields = $row['redcap_fields'];
						$fieldtype = $row['redcap_fieldtype'];
                                                $type = $row['nidb_datatype'];
                                                $variable = $row['nidb_variablename'];
                                                $nidbinstrument = $row['nidb_instrumentname'];
                                                
                                                switch ($type) {
                                                        case 'm': $typeStr = "Measure"; break;
                                                        case 'v': $typeStr = "Vital"; break;
                                                        case 'd': $typeStr = "Drug/dose"; break;
						 }
                                                ?>
						<tr>
                                                        <td><? =$variable?></td>
							<td style="border-right: 1px solid #bdbdbd"><? =$nidbinstrument?></td>
							<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>
                                                        <td><? =$event?></td>
                                                        <td><? =$form?></td>
							<td> <? =$fields?></td>
							<td> </td>
							<td> </td>
							<td> </td>
                                                        <td style="border-right: 1px solid #bdbdbd"><? =$fieldtype?></td>
                                                        <td title="Delete mapping"><a href="redcapmapping.php?action=deletemapping&mappingid=<? =$formmapid?>&projectid=<? =$projectid?>&inst=<? =$inst?>" class="redlinkbutton" style="font-size: smaller">X</a></td>
                                                </tr>
                                        <?
                                        }
?>                                        
                        </tbody>
		</table>
		</form>
	   </div>
<?

                 }
                 elseif ($nidbdatatype == 'v') {
                        $Cols ="seventeen";
                        $tp = "Vitals";
                        $ColSp =14;
                        print_r("This feature is under development");
                 }


?>

		<br><br>


	<form class"=ui form" action="redcapmapping.php" method="post">

                <input type="hidden" name="action" value="transferdata">
                <input type="hidden" name="projectid" value="<? =$projectid?>">
		<input type="hidden" name="inst" value="<? =$inst?>">
		<input type="hidden" name="nidbinstrument" value="<? =$nidbinstrument?>">
		
		<br>
		<style>
                        .button { border: none; background-color: #4CAF50; color: white; padding: 15px 32px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer;}
	       </style>
		
	       <?list($In_Name,$In_Label)=getrcinstruments($projectid);?>
			<h3>Select the Redcap Field name containing a unique ID to join Redcap and NiDB</h3>

			<select  class="ui search dropdown" name="jointid" required >
                           <option value=''> </option>
                                <? $V_names=getrcvariables($projectid,$uinst,$redcapevent);?>
                                <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                 <option value=<? =$V_names[$Fi]?>> <? =$V_names[$Fi]?> </option>
                                <?}?>
                        </select>
	<br><br>	
		<button class="button" onclick="window.location.href='redcapmapping.php?action=transferdata&projectid=<? =$projectid?>&jointid=<? =$jointid?>&nidbinstrument=<? =$nidbinstrument?>'" style="float:right">Start Transfer ---></button>
	</form>	
<?
  }


	/* -------------------------------------------- */
        /* -- Transfering Data into NiDB from Redcap -- */
        /* -------------------------------------------- */

	function TransferData($inst,$projectid,$nidbinstrument,$jointid)
	{

//		echo $inst;
//		echo $jointid;
//		echo $nidbinstrument;


		$Report_Id = getrcrecords($projectid,$inst,'screening_day_arm_1','record_id',$jointid);

                $Flg = 0;
                foreach ($Report_Id as $block => $info) {

                  if ($info[$jointid]!='') {
                        $RC_Id[$Flg] = $info['record_id'];
                        $subjectid[$Flg] = $info[$jointid];

                        $Flg = $Flg + 1;}
                }






	// Extracting Events from mapping table

	$sqlstringEvents = "SELECT DISTINCT(redcap_event) as RC_Events FROM redcap_import_mapping WHERE redcap_form = '$inst' and project_id = '$projectid'and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' ";
        $resultEvents = MySQLiQuery($sqlstringEvents, __FILE__, __LINE__);
	while ($rowEvents = mysqli_fetch_array($resultEvents, MYSQLI_ASSOC)) {
                        $redcapevent = $rowEvents['RC_Events'];
//			echo $redcapevent;	

			
	// Find the nidb data type (measure, vitals and dose/drug)
	$sqlstringType = "SELECT DISTINCT(nidb_datatype) as RC_Type FROM redcap_import_mapping WHERE redcap_form = '$inst' and project_id = '$projectid'and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' and `redcap_event`='$redcapevent'";
	$resultType = MySQLiQuery($sqlstringType, __FILE__, __LINE__);
	$rowType = mysqli_fetch_array($resultType, MYSQLI_ASSOC);
        $RCtype = $rowType['RC_Type'];		
	echo $RCtype;

	// Getting Data from Redcap and looping over Records
	$Rec = getrcrecords($projectid,$inst,$redcapevent,'record_id',$jointid);

                $Flg = 0;
                foreach ($Rec as $block => $info) {
			$Flg = $Flg +1;




	// Let us start getting the redcap fieldnames from the mapping table
	

	$sqlstringFields = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' and redcap_event='$redcapevent'";
        $resultFields = MySQLiQuery($sqlstringFields, __FILE__, __LINE__);
//        $rowFields = mysqli_fetch_array($resultFields, MYSQLI_ASSOC);
//        $RCFields = $rowFields['redcap_fields'];
	  while ($rowFields = mysqli_fetch_array($resultFields, MYSQLI_ASSOC)) {
             	$redcapfield = $rowFields['redcap_fields'];
//		echo $redcapfield;?><br><?
//			echo $info[$redcapfield];

		// Get the date field
		$sqlstringdate = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='date' and redcap_event='$redcapevent' and redcap_fieldgroupid = (Select `redcap_fieldgroupid` from `redcap_import_mapping` WHERE redcap_form='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' and redcap_event='$redcapevent' and `redcap_fields`='$redcapfield')";

		$resultdate = MySQLiQuery($sqlstringdate, __FILE__, __LINE__);
		if (mysqli_num_rows($resultdate) > 0) {
			$rowdate = mysqli_fetch_array($resultdate, MYSQLI_ASSOC);
			$RCdate = $rowdate['redcap_fields'];
//			echo $RCdate;
		}
                else {
			$sqlstringdate = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='date' and redcap_event='$redcapevent'";
			$resultdate = MySQLiQuery($sqlstringdate, __FILE__, __LINE__);
			$rowdate = mysqli_fetch_array($resultdate, MYSQLI_ASSOC);
			$RCdate = $rowdate['redcap_fields'];
//			echo $RCdate;
		}
		//		echo $info[$RCdate];
		//
		//

		// Let us get the rater field
                $sqlstringrater = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='rater' and redcap_event='$redcapevent' and redcap_fieldgroupid = (Select `redcap_fieldgroupid` from `redcap_import_mapping` WHERE redcap_form='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' and redcap_event='$redcapevent' and `redcap_fields`='$redcapfield')";

                $resultrater = MySQLiQuery($sqlstringrater, __FILE__, __LINE__);
                if (mysqli_num_rows($resultrater) > 0) {
                        $rowrater = mysqli_fetch_array($resultrater, MYSQLI_ASSOC);
                        $RCrater = $rowrater['redcap_fields'];
//                        echo $RCrater;
                }
                else {
                        $sqlstringrater = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='rater' and redcap_event='$redcapevent'";
                        $resultrater = MySQLiQuery($sqlstringrater, __FILE__, __LINE__);
                        $rowrater = mysqli_fetch_array($resultrater, MYSQLI_ASSOC);
                        $RCrater = $rowrater['redcap_fields'];
//                        echo $RCrater;
		}
//		 echo $info[$RCrater];
		
		// Let us get the notes field
                $sqlstringnotes = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='notes' and redcap_event='$redcapevent' and redcap_fieldgroupid = (Select `redcap_fieldgroupid` from `redcap_import_mapping` WHERE redcap_form='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' and redcap_event='$redcapevent' and `redcap_fields`='$redcapfield')";

                $resultnotes = MySQLiQuery($sqlstringnotes, __FILE__, __LINE__);
                if (mysqli_num_rows($resultnotes) > 0) {
                        $rownotes = mysqli_fetch_array($resultnotes, MYSQLI_ASSOC);
                        $RCnotes = $rownotes['redcap_fields'];
//                        echo $RCnotes;
                }
                else {
                        $sqlstringnotes = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='notes' and redcap_event='$redcapevent'";
                        $resultnotes = MySQLiQuery($sqlstringnotes, __FILE__, __LINE__);
                        $rownotes = mysqli_fetch_array($resultnotes, MYSQLI_ASSOC);
                        $RCnotes = $rownotes['redcap_fields'];
//                        echo $RCnotes;
                }
//		echo $info[$RCnotes];
		// Let us get the time field
                $sqlstringtime = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='time' and redcap_event='$redcapevent' and redcap_fieldgroupid = (Select `redcap_fieldgroupid` from `redcap_import_mapping` WHERE redcap_form='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' and redcap_event='$redcapevent' and `redcap_fields`='$redcapfield')";

                $resulttime = MySQLiQuery($sqlstringtime, __FILE__, __LINE__);
                if (mysqli_num_rows($resulttime) > 0) {
                        $rowtime = mysqli_fetch_array($resulttime, MYSQLI_ASSOC);
                        $RCtime = $rowtime['redcap_fields'];
//                        echo $RCtime;
                }
                else {
                        $sqlstringtime = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='time' and redcap_event='$redcapevent'";
                        $resulttime = MySQLiQuery($sqlstringtime, __FILE__, __LINE__);
                        $rowtime = mysqli_fetch_array($resulttime, MYSQLI_ASSOC);
                        $RCtime = $rowtime['redcap_fields'];
//                        echo $RCtime;
                }
//		echo $info[$RCtime];

		// Transferring the data Now
		  
		  $CID = array();
                  $AddN = 0;
		  
		  switch ($RCtype) {
                                case 'm':

					$instid = MeasureInstr($inst);
					if ($RCtime == ''){ $mdate = $info[$RCdate];} else{
						$mdate = $info[$RCdate].' '.$info[$RCtime];}
                                                $Reg = Addmeasures($subjectid[$Flg],$projectid, $redcapfield, $info[$redcapfield],$inst, $instid, $info[$RCnotes], $info[$RCrater], $mdate,$mdate,'');

                                                if ($Reg == 0){array_push($CID ,$subjectid[$Flg]);}

                                                $AddN = $AddN + $Reg;
                                 break;
                                case 'v':
					$vitalStdate = $info[$RCdate].' '.$info[$RCtime];
//					echo $subjectid[$Flg];
                                                $Reg =  Addvitals($subjectid[$Flg],$projectid,$redcapfield,$info[$redcapfield],$inst,$info[$RCnotes], $info[$RCrater], $info[$RCdate], $vitalStdate, '');

                                                if ($Reg == 0){array_push($CID ,$subjectid[$Flg]);}

                                                $AddN = $AddN + $Reg;
                                break;

                                case 'd':

                                        $dtime = $info[$RCtime];
                                        $dosename = $redcapevent;
                                        $dosestdate = $info[$RCdate].' '.$info[$RCtime];
                                        $doseenddate = $info[$RCdate].' '.$info[$RCtime];

//                                      echo $mvdDate;
//                                      echo $mvdTime;


                                                if ($redcapfield == 'dosedelivered') {

                                                        AddDose($subjectid[$Flg],$projectid,$dosename,$dosestdate,$doseenddate, $info[$redcapfield],$info[$RCrater],$info[$RCnotes], $info['dosedayinfo_dosekey'],$info['doseweight']);
                                                        echo 'Dose Information Transfered';?><br><?
                                               }
				break;
		  }// End of Case statement for nidb datatype




	  }//end of Fields While-Loop
			
	//	echo "The following subject/s were not found in NiDB for ".$redcapevent." event";?><br><?
           //     echo implode(", ",$CID);?><br><?
         //       echo "Total ".$AddN." records transferred";;?><br><br><?
		

	 } // end of Redcap records' For-Loop
	}//end of Event While-Loop
	











	}






// Mapping the Redcap Ids with NiDB
		// Need to change the hardcode Screening day

/*		
		$Report_Id = getrcrecords($projectid,$inst,'screening_day_arm_1','record_id',$jointid);
		
		$Flg = 0;
		foreach ($Report_Id as $block => $info) {

		  if ($info[$jointid]!='') {
			$RC_Id[$Flg] = $info['record_id'];
			$subjectid[$Flg] = $info[$jointid];
		
//			echo $RC_Id[$Flg], ' ';
//			echo $subjectid[$Flg];
//			echo ' ', $Flg;
//			echo "<br>";
			$Flg = $Flg + 1;}
		}

		$sqlstringEvents = "SELECT DISTINCT(redcap_event) as RC_Events FROM redcap_import_mapping WHERE redcap_form = '$inst' and project_id = '$projectid' ";
		$resultEvents = MySQLiQuery($sqlstringEvents, __FILE__, __LINE__);

		$sqlstringIntype = "SELECT DISTINCT(`nidb_datatype`) as Intype FROM `redcap_import_mapping` WHERE `redcap_form`='$inst'";
		$resultIntype = MySQLiQuery($sqlstringIntype, __FILE__, __LINE__);
		$rowIntype = mysqli_fetch_array($resultIntype, MYSQLI_ASSOC);
		$intype = $rowIntype['Intype'];

		while ($rowEvents = mysqli_fetch_array($resultEvents, MYSQLI_ASSOC)) {
			$redcapevent = $rowEvents['RC_Events'];

			if($intype =='d'){
				$sqlstringInst = "SELECT DISTINCT(`redcap_form`) as INST FROM `redcap_import_mapping` WHERE `nidb_datatype`='$intype'";
				$resultInst = MySQLiQuery($sqlstringInst, __FILE__, __LINE__);
				$inst_arr = array();
                		while($rowInst = mysqli_fetch_array($resultInst, MYSQLI_ASSOC)){
				$inst_arr[] = $rowInst['INST'];}
//				print_r($inst_arr);
//                                $inst_arr = array('dose_information','dose_day_cover_sheet');
				$RC_Record = getrcrecords($projectid,$inst_arr,$redcapevent,'record_id',$jointid);
				var_dump($RC_Record);
                                $inst = 'dose_day_cover_sheet';}
			 else {  $RC_Record = getrcrecords($projectid,$inst,$redcapevent,'record_id',$jointid);}

			if ($inst == 'dose_day_cover_sheet'){$inst='dose_information';}


//  CHECK $RC_Record **********************************

// THIS IS LATEST Code for data transfer with new mapping
///////////////////////////////////////////////////////////////////////////////
//

			 $sqlstringgrp = "SELECT DISTINCT(redcap_fieldgroupid) as grp FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' ";
                         $resultgrp = MySQLiQuery($sqlstringgrp, __FILE__, __LINE__);

			 while ($rowgrp = mysqli_fetch_array($resultgrp, MYSQLI_ASSOC)) {

				 $rcgrp = $rowgrp['grp'];


				 $sqlstringVal = "SELECT redcap_fields,nidb_datatype,redcap_fieldtype FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype = 'value' and redcap_fieldgroupid = '$rcgrp'";
                                $resultVal = MySQLiQuery($sqlstringVal, __FILE__, __LINE__);
                                $rowVal = mysqli_fetch_array($resultVal, MYSQLI_ASSOC);
				$mvdVal = $rowVal['redcap_fields'];
				$nidbdatatype = $rowVal['nidb_datatype'];
				$redcapfieldtype = $rowVal ['redcap_fieldtype'];

				if ($inst == 'dose_information'){$inst='dose_day_cover_sheet';}

				$sqlstringDate = "SELECT redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype = 'date' and redcap_fieldgroupid = '$rcgrp'";
				$resultDate = MySQLiQuery($sqlstringDate, __FILE__, __LINE__);
				if (mysqli_num_rows($resultDate) > 0) {
	                	        $rowDate = mysqli_fetch_array($resultDate, MYSQLI_ASSOC);
					$mvdDate = $rowDate['redcap_fields'];}
				else	{$mvdDate = '';}


				 $sqlstringTime = "SELECT redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype = 'time' and redcap_fieldgroupid = '$rcgrp'";
				 $resultTime = MySQLiQuery($sqlstringTime, __FILE__, __LINE__);
                                if (mysqli_num_rows($resultTime) > 0) {
                                        $rowTime = mysqli_fetch_array($resultTime, MYSQLI_ASSOC);
					$mvdTime = $rowTime['redcap_fields'];}
				else	{$mvdTime = '';}

				
				 // Measure / Vital rater
	                        $sqlstringRater = "SELECT redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype = 'rater' ";
        	                $resultRater = MySQLiQuery($sqlstringRater, __FILE__, __LINE__);
                	        $rowRater = mysqli_fetch_array($resultRater, MYSQLI_ASSOC);
                        	$mvdRater = $rowRater['redcap_fields'];
	//                      echo $mvdRater;

	// Measure / Vital notes
        	                $sqlstringNotes = "SELECT redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype = 'notes' ";
                	        $resultNotes = MySQLiQuery($sqlstringNotes, __FILE__, __LINE__);
                        	$rowNotes = mysqli_fetch_array($resultNotes, MYSQLI_ASSOC);
	                        $mvdNotes = $rowNotes['redcap_fields'];
	//                      echo $mvdNotes;

			 if ($inst == 'dose_day_cover_sheet'){$inst='dose_information';} 

			$CID = array();
			$AddN = 0;
			switch ($nidbdatatype) {
				case 'm':
					$Flg = 0;
					foreach ($RC_Record as $block => $info){

						$Flg = $Flg +1;
//						echo $mvdDate;	echo $mvdRater;	echo $mvdNotes;	echo $subjectid[$Flg];	echo $mvdVal;	echo $info[$mvdVal];	echo "<br>";
						$instid = MeasureInstr($inst);
						$Reg = Addmeasures($subjectid[$Flg],$projectid, $mvdVal, $info[$mvdVal],$inst, $instid, $info[$mvdNotes], $info[$mvdRater], $info[$mvdDate],$info[$mvdDate],'');

						if ($Reg == 0){array_push($CID ,$subjectid[$Flg]);}

						$AddN = $AddN + $Reg;
					}
				 break;	
				case 'v': 
					$Flg = 0;
					foreach ($RC_Record as $block => $info){
						$Flg = $Flg +1;
						$vitalStdate = $info[$mvdDate].' '.$info[$mvdTime];
						$Reg =  Addvitals($subjectid[$Flg],$projectid,$mvdVal,$info[$mvdVal],$inst,$info[$mvdNotes], $info[$mvdRater], $info[$mvdDate], $vitalStdate, '');

						if ($Reg == 0){array_push($CID ,$subjectid[$Flg]);}

                                                $AddN = $AddN + $Reg;
					}
				break;

				case 'd': 
				
//					$inst = 'dose_day_cover_sheet';
					$sqlstringtime = "SELECT redcap_event,redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form LIKE  '%dose%' and project_id = '$projectid' and redcap_fieldtype LIKE  '%time%' ";
                                        $resulttime = MySQLiQuery($sqlstringtime, __FILE__, __LINE__);
                                        $rowtime = mysqli_fetch_array($resulttime, MYSQLI_ASSOC);
                                        $dtime = $rowtime['redcap_fields'];
					$dosename = $rowtime['redcap_event'];
					$dosestdate = $info[$mvdDate].' '.$info[$mvdTime];
					$doseenddate = $info[$mvdDate].' '.$info[$mvdTime];

//					echo $mvdDate;
//					echo $mvdTime;
					
					$Flg = 0;
					foreach ($RC_Record as $block => $info){

						if ($mvdVal == 'dosedelivered') {

                                        		AddDose($subjectid[$Flg],$projectid,$dosename,$dosestdate,$doseenddate, $info[$mvdVal],$info[$mvdRater],$info[$mvdNotes], $info['dosedayinfo_dosekey'],$info['doseweight']);
	                                                $Flg = $Flg +1;
							echo 'Dose Information Transfered';?><br><?
                                               }

                                        }

			      break;

			}

			 }
			 echo "The following subject/s were not found in NiDB for ".$redcapevent." event";?><br><?
			 echo implode(", ",$CID);?><br><?
			 echo "Total ".$AddN." records transferred";;?><br><br><?
		}
	}

	
 */

	/*--------------------------------------------------------*/
	/* ---------------- TRANSFERING MEASURE'S DATA -----------*/
	/*--------------------------------------------------------*/

        function Addmeasures($subjectid,$projectid, $measurename, $measurevalue,$Form_name, $instid, $measurenotes, $measurerater, $measurestdate,$measureenddate,$measuredesc) {

                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";

//                PrintSQL($sqlstringEn);
                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select measurename_id from measurenames where measure_name = '$measurename'";
                //echo "$sqlstringA\n";
                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $measurenameid = $rowA['measurename_id'];
                }
                else {
                        $sqlstringA = "insert into measurenames (measure_name) values ('$measurename')";
                        //echo "$sqlstringA\n";
                        $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                        $measurenameid = mysqli_insert_id($GLOBALS['linki']);
                }

                 $measurenotes = str_replace("'","''",$measurenotes);
                 $measurenotes = str_replace('"',"''",$measurenotes);
                 $measuredesc =  str_replace("'","''",$measuredesc);
                 $measuredesc =  str_replace('"',"''",$measuredesc);


                 if ($enrollmentid!=''){
                $sqlstring = "insert ignore into measures (enrollment_id, measure_dateentered,instrumentname_id, measurename_id, measure_notes,measure_desc,  measure_rater,measure_value,measure_startdate,measure_enddate,measure_entrydate,measure_createdate,measure_modifydate) values ($enrollmentid, now(),$instid,$measurenameid, '$measurenotes','$measuredesc','$measurerater','$measurevalue',NULLIF('$measurestdate',''),NULLIF('$measureenddate',''),now(),now(),now()) on duplicate key update measure_value='$measurevalue', measure_modifydate=now()";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		 return 1;}
		 else{  return 0;}
        }


	/* -------------------------------------------- */
        /* ------ Transferring Dose info into NiDB------ */
        /* -------------------------------------------- */

   function  AddDose($subjectid,$projectid,$dosename,$dosestdate,$doseenddate,$dosedelivered,$doserater,$dosenotes,$dosekey,$doseinhaled){


                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";
/*                PrintSQL($sqlstringEn);*/


                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select drugname_id from drugnames where drug_name = '$dosename'";
                //echo "$sqlstringA\n";
                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $dosenameid = $rowA['drugname_id'];
                }
		else {
			 $sqlstringA = "insert into drugnames (drug_name) values ('$dosename')";
                        //echo "$sqlstringA\n";
                        $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                        $dosenameid = mysqli_insert_id($GLOBALS['linki']);
                        echo 'A new drugname added!';?><br><?
                }


                $dosenotes = str_replace("'","''",$dosenotes);
                $dosenotes = str_replace('"',"''",$dosenotes);
           if ($enrollmentid!=''){
                $sqlstring = "insert ignore into drugs (enrollment_id, drug_startdate, drug_enddate, drug_doseamount, drugname_id, drug_dosekey, drug_doseunit, drug_rater, drug_notes, drug_entrydate, drug_recordcreatedate, drug_recordmodifydate) values ($enrollmentid, '$dosestdate', '$doseenddate','$dosedelivered','$dosenameid','$dosekey','$doseinhaled','$doserater','$dosenotes',now(),now(),now() ) on duplicate key update drug_doseunit = '$doseinhaled', drug_recordmodifydate = now()";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}
           else { echo 'Subject '.$subjectid.' was not found in NiDB';?><br><?}


        }



	/* -------------------------------------------- */
        /* ---- Transfering vitals into NiDB --------- */
        /* -------------------------------------------- */

        function Addvitals($subjectid, $projectid, $vitalname, $vitalvalue, $Form_name, $vitalnotes, $vitalrater, $vitaldate, $vitalStdate,$vitaltdesc) {

                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";

        //      PrintSQL($sqlstringEn);
                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select vitalname_id from vitalnames where vital_name = '$vitalname'";
                //echo "$sqlstringA\n";
                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $vitalnameid = $rowA['vitalname_id'];
                }
                else {
                        $sqlstringA = "insert into vitalnames (vital_name) values ('$vitalname')";
                        //echo "$sqlstringA\n";
                        $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                        $vitalnameid = mysqli_insert_id($GLOBALS['linki']);
                }

                 $vitalnotes = str_replace("'","''",$vitalnotes);
                 $vitalnotes = str_replace('"',"''",$vitalnotes);
                 $vitaldesc = str_replace("'","''",$vitaldesc);
                 $vitaldesc = str_replace('"',"''",$vitaldesc);
                 $vitalvalue = str_replace("'","''",$vitalvalue);
                 $vitalvalue = str_replace('"',"''",$vitalvalue);


                 if ($enrollmentid!=''){
                $sqlstring = "insert ignore into vitals (enrollment_id, vital_date,vital_value,vital_notes,vital_desc,vital_rater,vitalname_id,vital_type,vital_startdate,vital_enddate,vital_entrydate,vital_recordcreatedate,vital_recordmodifydate) values ($enrollmentid, '$vitaldate','$vitalvalue','$vitalnotes','$vitaldesc','$vitalrater','$vitalnameid','$Form_name',NULLIF('$vitalStdate',''),NULLIF('$vitalStdate',''),now(),now(),now()) on duplicate key update vital_value='$vitalvalue', vital_recordmodifydate=now()";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		 return 1;}
		 else{  return 0;}
        }



	
	/*-----------------------------------------------------------*/
	/*-------Returning Measure Foorm IDs and updating then-------*/
	/*-----------------------------------------------------------*/


	function MeasureInstr($Formname){

                 $sqlstringinst = "SELECT measureinstrument_id FROM measureinstruments WHERE instrument_name ='$Formname'";
                 $resultinst = MySQLiQuery($sqlstringinst,__FILE__,__LINE__);
                 if (mysqli_num_rows($resultinst) > 0) {
                           $row = mysqli_fetch_array($resultinst, MYSQLI_ASSOC);
                           $instid = $row['measureinstrument_id'];
                 }
                 else {
                           $sqlstringinst = "insert ignore into measureinstruments (instrument_name) values ('$Formname')";
                           $result = MySQLiQuery($sqlstringinst, __FILE__, __LINE__);
                           $instid = mysqli_insert_id($GLOBALS['linki']);
                              }
                return $instid;

        }




 ?>


<? include("footer.php") ?>
