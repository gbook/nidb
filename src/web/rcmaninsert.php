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
	$mappingid = GetVariable("mappingid");
        $redcapevent = GetVariable("redcapevent");
        $redcapform = GetVariable("redcapform");
        $redcapfields = GetVariable("redcapfields");
	$redcapfieldtype = GetVariable("redcapfieldtype");
        $nidbdatatype = GetVariable("nidbdatatype");
        $nidbvariablename = GetVariable("nidbvariablename");
        $nidbinstrumentname = GetVariable("nidbinstrumentname");
	$jointid = GetVariable("jointid");
	$subjectid = GetVariable("subjectid");

	


 switch ($action) 
{
		case 'displaymapping':
			projectinfo($projectid);
			DisplayRedCapSettings($projectid,$inst,$nidbdatatype);
			break;
		case 'updatemapping':
                        UpdateMapping($projectid, $redcapevent, $inst, $redcapfields, $redcapfieldtype, $nidbdatatype, $nidbvariablename, $nidbinstrumentname);
                        projectinfo($projectid);
                        DisplayRedCapSettings($projectid,$inst,$nidbdatatype);
			break;
                case 'deletemapping':
                        DeleteMapping($mappingid);
			projectinfo($projectid);
                        DisplayRedCapSettings($projectid,$inst,$nidbdatatype);
                        break;

		case 'transferdata':
/*			echo $inst;
			echo $rcnidbuniqueid;
			projectinfo($projectid);*/
			TransferData($inst,$projectid,$jointid);
			break;
                default:
			projectinfo($projectid);
        }
        
        
        /* ------------------------------------ functions ------------------------------------ */

        /* -------------------------------------------- */
        /* ------- UpdateMapping ---------------------- */
        /* -------------------------------------------- */
        function UpdateMapping($projectid, $redcapevent, $redcapform, $redcapfields, $redcapfieldtype, $nidbdatatype, $nidbvariablename, $nidbinstrumentname) {
                $redcapevent = mysqli_real_escape_string($GLOBALS['linki'], $redcapevent);
                $redcapform = mysqli_real_escape_string($GLOBALS['linki'], $redcapform);
                $redcapfields = mysqli_real_escape_string($GLOBALS['linki'], $redcapfields);
		$redcapfieldtype = mysqli_real_escape_string($GLOBALS['linki'], $redcapfieldtype);
                $nidbdatatype = mysqli_real_escape_string($GLOBALS['linki'], $nidbdatatype);
                $nidbvariablename = mysqli_real_escape_string($GLOBALS['linki'], $nidbvariablename);
                $nidbinstrumentname = mysqli_real_escape_string($GLOBALS['linki'], $nidbinstrumentname);

                $sqlstring = "start transaction";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		print_r($redcapevent);
		
		foreach ($_POST['redcapevent'] as $Event) {                

			$sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfields', '$redcapfieldtype' ,'$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
                //PrintSQL($sqlstring);
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

                $sqlstring = "commit";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		}
                
        }

        /* -------------------------------------------- */
        /* ------- DeleteMapping ---------------------- */
        /* -------------------------------------------- */
        function DeleteMapping($mappingid) {

                MySQLiQuery("start transaction", __FILE__, __LINE__);

                $sqlstring = "delete from redcap_import_mapping where formmap_id = $mappingid";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                
                MySQLiQuery("commit", __FILE__, __LINE__);
                
                ?><div align="center"><span class="message">Mapping deleted</span></div><br><br><?


        }






/* -----------------getprojectinfo---------------*/

	function projectinfo($projectid){
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


		<h2 class="ui top attached inverted header" align="center"> Redcap ===> NiDB Transfer (Mapping) </h2>
		<table class="ui collapsing green large centered table" align="center">
                        <tr>
                                <td class="grey">Project Name</td>
                                <td> <b> <?=$projectname?> </td>
                        </tr>
                        <tr></tr> <tr></tr>

                        <tr>
                                <td class="grey">RedCap Server</td>
                                <td> <b><?=$redcapurl?> </td>
                        </tr>
                        <tr></tr> <tr></tr>

                        <tr>

		</table>


	<?
		// This section separated two methods of mapping
			
	?>
		
		<?list($In_Name,$In_Label)=getrcinstruments($projectid);?>

		<h3 class="ui top attached inverted header"> RedCap to NiDB form / variable mapping  </h3>

	<form  class="ui form" action="redcapmaping.php" >
	<input type="hidden" name="action" value="displaymapping">
        <input type="hidden" name="projectid" value="<?=$projectid?>">
	
		<div class="ui form">
		  <div class="field">
		      <label>Select a Redcap Form:</label>
		      <div class="ui selection dropdown">
		          <input type="hidden" name="inst">
		          <i class="dropdown icon"></i>
		          <div class="default text">Redcap Forms</div>
		          <div class="menu">
			     <?for($In=0;$In < count($In_Name); $In++){ ?>
	                        <div class="item" data-value=<?=$In_Name[$In]?>> <?=$In_Name[$In]?> </div>
	                    <?}?>
		          </div>
		      </div>
		  </div>

		 <div class="field">
                      <label>Select the type of NiDB data</label>
                      <div class="ui selection dropdown">
                          <input type="hidden" name="nidbdatatype">
                          <i class="dropdown icon"></i>
                          <div class="default text">NiDB Type</div>
			  <div class="menu">
                                <div class="item" data-value="m" selected>Measure (Forms containing various cognitive measures)</div>
                                <div class="item" data-value="v">Vital (Like:BP,HR, ...)</div>
                                <div class="item" data-value="d">Drug/dose (Dose Time and date information)</div>
                          </div>
		</div>

	 <br><br>

                 <button class="fluid ui button" type="submit">
                   <i class="buffer icon"></i>
                     Redcap Fields Mapping
                  </button>

        </form>




	<br>

<?}


	 /* -------------------------------------------- */
        /* ------- DisplayRedCapSettings -------------- */
        /* -------------------------------------------- */
        function DisplayRedCapSettings($projectid,$inst,$nidbdatatype) {
		
		        
                if ((trim($projectid) == "") || ($projectid < 0)) {
                        ?>Invalid or blank project ID [<?=$projectid?>]<?
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
                
                <form action="redcapmaping.php" method="post">
                <input type="hidden" name="action" value="updatemapping">
                <input type="hidden" name="projectid" value="<?=$projectid?>">
		<input type="hidden" name="inst" value="<?=$inst?>">
		<input type="hidden" name="nidbdatatype" value="<?=$nidbdatatype?>">
                
                <h4 class="ui top attached inverted header"> Map the variable in the RedCap system to the NiDB variable type<h4>
                <table class="ui graydisplaytable">
                        <thead>
                                <tr>
                                        <th style="text-align: center; border-right: 1px solid #bdbdbd" colspan="4">RedCap</th>
                                        <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 20pt; border-right: 1px solid #bdbdbd; padding: 0px 30px">&#10132;</th>
                                        <th style="text-align: center" colspan="4">NiDB</th>
                                </tr>
                                <tr>
                                        <th>Event</th>
                                        <th>Form</th>
					<th> Field </th>
                                        <th style="border-right: 1px solid #bdbdbd">Field Type</th>
                                        <th>Type</th>
                                        <th>Variable</th>
                                        <th>Instrument</th>
                                        <th></th>
                                </tr>
                        </thead>
                        <tbody>
                                <tr>
					<td>
						<select name="redcapevent[]"  multiple required size="3">
			                           <?for($Eve=0;$Eve < count($Event_s); $Eve++){ ?>
			                              <option value=<?=$Event_s[$Eve]?>> <?=$Event_s[$Eve]?> </option>
 				                   <?}?>	
                                                </select>
                                        </td>
                                        <td><input type="text" name="inst" value=<?=$inst?>></td>
				
					<? $V_names=getrcvariables($projectid,$inst,$redcapevent);?>

					<td>
                                                <select name="redcapfields" required  onchange="document.getElementById('nidbvariablename').value=this.options[this.selectedIndex].text;">
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){ 
						      if ($Fi==0){?>
						        <option value=<?=$V_names[$Fi]?> selected> <?=$V_names[$Fi]?> </option>	 <?}
						else {?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>
						   
                                                   <?}}?>
                                                </select>
                                        </td>

					<td style="border-right: 1px solid #bdbdbd"><input type="text" name="redcapfieldtype"></td>
					


					<td style="border-right: 1px solid #bdbdbd"></td>

					<?if ($nidbdatatype=='m'){?><td> <input type="text" disabled value="Measure"</td>
					<?}elseif ($nidbdatatype=='v'){?><td><input type="text" disabled value="Vital"</td>
					<?}elseif ($nidbdatatype=='d'){?><td><input type="text" disabled value="Drug /Dose"</td><?}?>

                                        <td><input type="text" name="nidbvariablename" id="nidbvariablename"></td>
                                        <td><input type="text" name="nidbinstrumentname" value=<?=$inst?>></td>
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
                                                $instrument = $row['nidb_instrumentname'];
                                                
                                                switch ($type) {
                                                        case 'm': $typeStr = "Measure"; break;
                                                        case 'v': $typeStr = "Vital"; break;
                                                        case 'd': $typeStr = "Drug/dose"; break;
						 }
                                                ?>
                                                <tr>
                                                        <td><?=$event?></td>
                                                        <td><?=$form?></td>
							<td> <?=$fields?></td>
                                                        <td style="border-right: 1px solid #bdbdbd"><?=$fieldtype?></td>
                                                        <td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>
                                                        <td><?=$typeStr?></td>
                                                        <td><?=$variable?></td>
                                                        <td><?=$instrument?></td>
                                                        <td title="Delete mapping"><a href="redcapmaping.php?action=deletemapping&mappingid=<?=$formmapid?>&projectid=<?=$projectid?>&inst=<?=$inst?>" class="redlinkbutton" style="font-size: smaller">X</a></td>
                                                </tr>
                                        <?
                                        }
                                        ?>
                        </tbody>
                </table>
                </form>
                <br><br>

		
		<form action="redcapmaping.php" method="post">
                <input type="hidden" name="action" value="transferdata">
                <input type="hidden" name="projectid" value="<?=$projectid?>">
                <input type="hidden" name="inst" value="<?=$inst?>">


		<label> Enter the Redcap Field name containing a unique ID to join Redcap and NiDB</label>
		<input type="text" name="jointid" value="<?=$jointid?>">
		<br>
		<style>
		.button { border: none; background-color: #4CAF50; color: white; padding: 15px 32px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 4px 2px; cursor: pointer;}		

	       </style>
		
		<button class="button" onclick="window.location.href='redcapmaping.php?action=transferdata&projectid=<?=$projectid?>&jointid=<?=$jointid?>'" style="float:left">Start Transfer ---></button>
		</form>

<?
  }


	/* -------------------------------------------- */
        /* ---- Transfering measures into ADO --------- */
        /* -------------------------------------------- */

	function TransferData($inst,$projectid,$jointid)
	{

//		echo $inst;
//		echo $jointid;

	// Mapping the Redcap Ids with NiDB
	// Need to chaneg the hardcode Screening day
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

		while ($rowEvents = mysqli_fetch_array($resultEvents, MYSQLI_ASSOC)) {
			$redcapevent = $rowEvents['RC_Events'];

			 if($inst=='dose_information' || $inst=='dose_day_cover_sheet'){
                                $inst_arr = array('dose_information','dose_day_cover_sheet');
                                $RC_Record = getrcrecords($projectid,$inst_arr,$redcapevent,'record_id',$jointid);
                                $inst = 'dose_day_cover_sheet';}
                        else {  $RC_Record = getrcrecords($projectid,$inst,$redcapevent,'record_id',$jointid);}

		    
	// Measure / Vital date
			$sqlstringDate = "SELECT redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype = 'date' ";
                        $resultDate = MySQLiQuery($sqlstringDate, __FILE__, __LINE__);
			$rowDate = mysqli_fetch_array($resultDate, MYSQLI_ASSOC);
			$mvdDate = $rowDate['redcap_fields'];			
//			echo $mvdDate;

        // Measure / Vital rater
                        $sqlstringRater = "SELECT redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype = 'rater' ";
                        $resultRater = MySQLiQuery($sqlstringRater, __FILE__, __LINE__);
                        $rowRater = mysqli_fetch_array($resultRater, MYSQLI_ASSOC);
                        $mvdRater = $rowRater['redcap_fields'];
//			echo $mvdRater;

// Measure / Vital notes
                        $sqlstringNotes = "SELECT redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype = 'notes' ";
                        $resultNotes = MySQLiQuery($sqlstringNotes, __FILE__, __LINE__);
                        $rowNotes = mysqli_fetch_array($resultNotes, MYSQLI_ASSOC);
                        $mvdNotes = $rowNotes['redcap_fields'];
//			echo $mvdNotes;

// Measure / Vital Value

			if ($inst == 'dose_day_cover_sheet'){$inst='dose_information';}

			$sqlstringVal = "SELECT redcap_fields,nidb_datatype,redcap_fieldtype FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype LIKE  '%value%' ";
//		PrintSQL($sqlstringFm);
                	$resultVal = MySQLiQuery($sqlstringVal, __FILE__, __LINE__);

			while ($rowVal = mysqli_fetch_array($resultVal, MYSQLI_ASSOC)) {
        	              $redcapfields = $rowVal['redcap_fields'];
			      $nidbdatatype = $rowVal['nidb_datatype'];
			      $redcapfieldtype = $rowVal ['redcap_fieldtype'];
                      	
			switch ($nidbdatatype) {
                	         case 'm': 
                        	 	$Flg = 0;
					foreach ($RC_Record as $block => $info){
						echo $mvdDate;
						echo $mvdRater;
						echo $mvdNotes;
						echo $subjectid[$Flg];
						echo $redcapfields;
						echo $info[$redcapfields];
						echo "<br>";
					
						$instid = MeasureInstr($inst);
						Addmeasures($subjectid[$Flg],$projectid, $redcapfields, $info[$redcapfields],$inst, $instid, $info[$mvdNotes], $info[$mvdRater], $info[$mvdDate],$info[$mvdDate],'');
						
						$Flg = $Flg +1;
					
					}

				 break;	
				
				 case 'v': 

				// Getting number of accurances in a day (NEED TO UPDATE HERE)
					$sqlstringtime = "SELECT COUNT(DISTINCT(redcap_fieldtype)) as Num_times FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype LIKE  'value%' ";
	                         	$resulttime = MySQLiQuery($sqlstringtime, __FILE__, __LINE__);
				 	$rowtime = mysqli_fetch_array($resulttime, MYSQLI_ASSOC);
	                         	$vnum = $rowtime['Num_times'];
//					echo $vnum;
				
					$Flg = 0;
                                        foreach ($RC_Record as $block => $info){
	
						for ($tee = 0; $tee <= $vnum; $tee++) {
							$sqlstringvtime = "SELECT redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype = 'time$tee' ";
				                        $resultvtime = MySQLiQuery($sqlstringvtime, __FILE__, __LINE__);
				                        $rowvtime = mysqli_fetch_array($resultvtime, MYSQLI_ASSOC);
				                        $vtime = $rowvtime['redcap_fields'];
					//		echo $vtime;
					//		echo "<br>";
							
							if ($redcapfieldtype == 'value'.$tee){
							
  							 $vitalStdate = $info[$mvdDate].' '.$info[$vtime];
							 Addvitals($subjectid[$Flg],$projectid,$redcapfields,$info[$redcapfields],$inst,$info[$mvdNotes], $info[$mvdRater], $info[$mvdDate], $vitalStdate, '');
							 $Flg = $Flg +1;
							
							}
					
						}
					}

				 break;

				 case 'd': 
				
				$inst = 'dose_day_cover_sheet';
				$sqlstringtime = "SELECT redcap_event,redcap_fields FROM redcap_import_mapping WHERE redcap_event = '$redcapevent' and  redcap_form = '$inst' and project_id = '$projectid' and redcap_fieldtype LIKE  '%time%' ";
                                        $resulttime = MySQLiQuery($sqlstringtime, __FILE__, __LINE__);
                                        $rowtime = mysqli_fetch_array($resulttime, MYSQLI_ASSOC);
                                        $dtime = $rowtime['redcap_fields'];
					$dosename = $rowtime['redcap_event'];
					$dosestdate = $info[$mvdDate].' '.$info[$dtime];
					$doseenddate = $info[$mvdDate].' '.$info[$dtime];
					
				
				        $Flg = 0;
					foreach ($RC_Record as $block => $info){

						if ($redcapfields == 'dosedelivered') {

                                        	AddDose($subjectid[$Flg],$projectid,$dosename,$dosestdate,$doseenddate, $info[$redcapfields],$info[$mvdRater],$info[$mvdNotes], $info['dosedayinfo_dosekey'],$info['doseweight']);

                                                $Flg = $Flg +1;
                                                echo 'Dose Information Transfered';
                                               }


                                        }

			      break;

        	              }

		//	     echo $redcapfields;
		//	     echo $nidbdatatype;
			}
		//	echo var_dump($RC_Record);
		

		}

	}

	


	/*--------------------------------------------------------*/
	/* ---------------- TRANSFERING MEASURE'S DATA -----------*/
	/*--------------------------------------------------------*/

        function Addmeasures($subjectid,$projectid, $measurename, $measurevalue,$Form_name, $instid, $measurenotes, $measurerater, $measurestdate,$measureenddate,$measuredesc) {

                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";

                PrintSQL($sqlstringEn);
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
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}
                else {echo 'Subject '.$subjectid.' was not found in ADO';}
        }


	/* -------------------------------------------- */
        /* ------ Transferring Dose info into ADO------ */
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
                        echo 'Drugname not found!';
                }


                $dosenotes = str_replace("'","''",$dosenotes);
                $dosenotes = str_replace('"',"''",$dosenotes);
           if ($enrollmentid!=''){
                $sqlstring = "insert ignore into drugs (enrollment_id, drug_startdate, drug_enddate, drug_doseamount, drugname_id, drug_dosekey, drug_doseunit, drug_rater, drug_notes, drug_entrydate, drug_recordcreatedate, drug_recordmodifydate) values ($enrollmentid, '$dosestdate', '$doseenddate','$dosedelivered','$dosenameid','$dosekey','$doseinhaled','$doserater','$dosenotes',now(),now(),now() ) on duplicate key update drug_doseunit = '$doseinhaled', drug_recordmodifydate = now()";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}
           else { echo 'Subject '.$subjectid.' was not found in ADO';}


        }



	/* -------------------------------------------- */
        /* ---- Transfering vitals into ADO --------- */
        /* -------------------------------------------- */

        function Addvitals($subjectid, $projectid, $vitalname, $vitalvalue, $Form_name, $vitalnotes, $vitalrater, $vitaldate, $vitalStdate,$vitaltdesc) {

                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";

                PrintSQL($sqlstringEn);
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
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}
                else {echo 'Subject '.$subjectid.' was not found in ADO';}
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
