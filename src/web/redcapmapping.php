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
	$redcapfieldname = GetVariable("redcapfieldname");
	$redcapfieldval = GetVariable("redcapfieldval");
	$redcapfielddate = GetVariable("redcapfielddate");
	$redcapfieldStime = GetVariable("redcapfieldStime");
	$redcapfieldEtime = GetVariable("redcapfieldEtime");
	$redcapfieldrater = GetVariable("redcapfieldrater");
	$redcapfieldnotes = GetVariable("redcapfieldnotes");
	$redcapfieldtype = GetVariable("redcapfieldtype");
	$rcid = GetVariable("rcid");
	$rcmainevent = GetVariable("rcmainevent");
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
			DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype,$jointid);
			break;
		case 'updatemapping':
		      if ($nidbdatatype=='d') {
			      UpdatedrugMapping($projectid, $redcapevent, $inst, $redcapfieldname, $redcapfieldtype, $nidbdatatype, $nidbvariablename, $nidbinstrumentname);
		      	      projectinfo($projectid);
                  	      DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype,$jointid);}
		      else {
			      UpdateMapping($projectid,$redcapevent, $inst, $redcapfieldval, $redcapfielddate, $redcapfieldrater, $redcapfieldnotes, $redcapfieldStime, $redcapfieldEtime, $redcapfieldtype, $nidbdatatype, $nidbvariablename, $nidbinstrumentname);
		      	      projectinfo($projectid);
                              DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype,$jointid);}
			break;
                case 'deletemapping':
			DeleteMapping($mappingid,$projectid,$nidbdatatype);
			projectinfo($projectid);
		        DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype,$jointid);
			break;
		case 'transferdata':
			gettransfervals($projectid);
			TransferData($projectid,$nidbinstrument,$rcmainevent,$rcid,$jointid);
			break;
                default:
			maptransfer($projectid);
        }
        
        
        /* ------------------------------------ functions ------------------------------------ */

	/* -------------------------------------------- */
        /* --------- maptransfer ---------------------- */
        /* -------------------------------------------- */
	function maptransfer($projectid){
	
	if ((trim($projectid) == "") || ($projectid < 0)) {
			?>Invalid or blank project ID [<?=$projectid?>]<?
			return;
		}
	

	
		$sqlstring = "select project_name,redcap_server from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
		$redcapurl = $row['redcap_server'];
		
?>		

		<h1 class="ui header" align="left"> NiDB ===> Redcap (Mapping \  Transfer) 
			<div class="sub header"> Project: <?=$projectname?> &nbsp; &nbsp; Redcap Server: <?=$redcapurl?></div>
		</h1>

	<div class="ui placeholder segment">
	  <div class="ui two column very relaxed stackable grid">
	    <div class="column">
		<form class"=ui form" action="redcapmapping.php" method="post">
		  <input type="hidden" name="action" value="displaymapping">
		  <input type="hidden" name="projectid" value="<?=$projectid?>">
		  <button class="ui floated huge primary button"  type="submit"><i class="map icon"></i>Edit Mapping</button>
		</form>	
	    </div>
	    <div class="middle aligned column">
		<form class"=ui form" action="redcapmapping.php" method="post">
                  <input type="hidden" name="action" value="transferdata">
                  <input type="hidden" name="projectid" value="<?=$projectid?>">
                  <button class="ui floated huge primary button"  type="submit"><i class="level down alternate icon"></i>Transfer Data</button>
                </form>
	    </div>
	  </div>
	  <div class="ui vertical divider">
	    Or
	  </div>
	</div>
<?
	}


	/*---------------------------------------------*/
	/*--------- gettransfervals -------------------*/
	/*---------------------------------------------*/
	function gettransfervals($projectid){
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
		


	list($In_Name,$In_Label)=getrcinstruments($projectid);
	$Event_s = getrcevents($projectid);
	$V_names=getrcvariables($projectid,'','');

?>		
	<h1 class="ui header" align="left"> Redcap ===> NiDB Data Transfer  
			<div class="sub header"> Project: <?=$projectname?> &nbsp; &nbsp; Redcap Server: <?=$redcapurl?></div>
	</h1>

	<div class="ui grid">


	<div class="three column row" align="center">
	<form  class="ui form" action="redcapmapping.php">
	<input type="hidden" name="action" value="transferdata">
        <input type="hidden" name="projectid" value="<?=$projectid?>">
        <input type="hidden" name="rcmainevent" value="<?=$rcmainevent?>">
        <input type="hidden" name="rcid" value="<?=$rcid?>">
        <input type="hidden" name="nidbinstrument" value="<?=$nidbinstrument?>">
        <input type="hidden" name="jointid" value="<?=$jointid?>">
		
		<table class="ui basic table">


			<tr>
				<td style="border-right: 1px solid #bdbdbd; text-align: center">
					 <h4 align="left">NiDB Instrument <i class="small blue question circle outline icon" title="Select the NiDB instrument to transfer data from Redcap"></i></h4>
					<select name="nidbinstrument" id="nidbinstrument" required>
					     <? $sqlnidbinst = "SELECT DISTINCT(`nidb_instrumentname`) as NIDBinst FROM `redcap_import_mapping` WHERE `project_id`=$projectid";
				                $resultnidbinst = MySQLiQuery($sqlnidbinst, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($resultnidbinst, MYSQLI_ASSOC)) {
							$nidbinst=$row['NIDBinst'];?>
							<option value=<?=$nidbinst?>> <?=$nidbinst?> </option>
						<?}?>	
					</select>
					<script type="text/javascript"> document.getElementById('nidbinstrument').value = "<?php echo $_GET['nidbinstrument'];?>";</script>
				</td>
				<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>
				<td>
					<h4 align="left">Redcap Main Event <i class="small blue question circle outline icon" title="Select the Redcap main event"></i></h4>
						<select name="rcmainevent" >
						   <option value=''></option>
						   <?
						      for($Eve=0;$Eve < count($Event_s); $Eve++){ ?>
                                                      <option value=<?=$Event_s[$Eve]?>> <?=$Event_s[$Eve]?> </option>
                                                   <?}?>
						</select>
					<script type="text/javascript"> document.getElementById('rcmainevent').value = "<?php echo $_GET['rcmainevent'];?>";</script>
                                </td>
				<td> 
					<h4>Redcap Unique Id <i class="small blue question circle outline icon" title="Select the Redcap Field containing Unique Record Id "></i></h4>
					<select name="rcid" id="rcid" required>
						<option value=''></option>
                                                <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                 <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>
                                                <?}?>

					</select>
					<script type="text/javascript"> document.getElementById('rcid).value = "<?php echo $_GET['rcid'];?>";</script>
				</td>
				<td>
					<h4> Unique Mapping Id  <i class="small blue question circle outline icon" title="Select the column containg information of Unique Id. <br> It is a column storing NiDB UID values starts with 'S'"></i></h4>
					<select name="jointid"  id="jointid" >
						<option value=''></option>
						<? 
						//$V_names=getrcvariables($projectid,'',$rcmainevent);
						?>
                                                <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                 <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>
                                                <?}?>
					</select>

				</td>
                        </tr>
		</table>
		<br>
                <button class="ui right floated huge primary button"  type="submit"><i class="fill icon"></i>Transfer</button>

	</form>
	</div>
	</div>

	<form>
                <input type="hidden" name="action" value="default">
                <input type="hidden" name="projectid" value="<?=$projectid?>">
                <button class="ui right floated large primary button"  type="submit"><i class="map icon"></i> Mapping / <i class="level down alternate icon"></i> Transfer</button>
	</form>
	

<?}


	/* -------------------------------------------- */
        /* ------- UpdatedrugMapping ---------------------- */
        /* -------------------------------------------- */
        function UpdatedrugMapping($projectid, $redcapevent, $redcapform, $redcapfieldname, $redcapfieldtype, $nidbdatatype, $nidbvariablename, $nidbinstrumentname) {
                $redcapevent = mysqli_real_escape_string($GLOBALS['linki'], $redcapevent);
                $redcapform = mysqli_real_escape_string($GLOBALS['linki'], $redcapform);
		$redcapfielname = mysqli_real_escape_string($GLOBALS['linki'], $redcapfieldname);
		$redcapfieldtype = mysqli_real_escape_string($GLOBALS['linki'], $redcapfieldtype);
                $nidbdatatype = mysqli_real_escape_string($GLOBALS['linki'], $nidbdatatype);
                $nidbvariablename = mysqli_real_escape_string($GLOBALS['linki'], $nidbvariablename);
                $nidbinstrumentname = mysqli_real_escape_string($GLOBALS['linki'], $nidbinstrumentname);

                $sqlstring = "start transaction";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
//		print_r($redcapevent);
		//		print_r($redcapfieldname);

		// Getting the redcap field description from redcap corresponding project
		$rcfielddesc=  getrclabels($projectid,$redcapfieldname);
//		echo $rcfielddesc."<br>";

                $rcfielddesc =  str_replace("'","''",$rcfielddesc);
                $rcfielddesc =  str_replace('"',"''",$rcfielddesc);

		
		foreach ($_POST['redcapevent'] as $Event) {                

			$chquery = "SELECT max(`redcap_fieldgroupid`) as mgid FROM `redcap_import_mapping`";
	                $resultq = MySQLiQuery($chquery, __FILE__, __LINE__);
        	        $rowq = mysqli_fetch_array($resultq, MYSQLI_ASSOC);
			$maxgid = $rowq['mgid'];
			$maxgid = (int)$maxgid +1;


			$sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcapfield_desc, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldname', '$redcapfieldtype' ,'$rcfielddesc', '$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
//        	        PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
        	        $sqlstring = "commit";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		}
                
	}


        /* -------------------------------------------- */
        /* ------- UpdateMapping ---------------------- */
        /* -------------------------------------------- */
        function UpdateMapping($projectid, $redcapevent, $redcapform, $redcapfieldval, $redcapfielddate, $redcapfieldrater, $redcapfieldnotes, $redcapfieldStime, $redcapfieldEtime, $redcapfieldtype, $nidbdatatype, $nidbvariablename, $nidbinstrumentname) {
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
		//  print_r($redcapevent);
		//print_r($redcapfieldval);

		foreach ($_POST['redcapevent'] as $Event) {                

			$chquery = "SELECT max(`redcap_fieldgroupid`) as mgid FROM `redcap_import_mapping`";
	                $resultq = MySQLiQuery($chquery, __FILE__, __LINE__);
        	        $rowq = mysqli_fetch_array($resultq, MYSQLI_ASSOC);
			$maxgid = $rowq['mgid'];
			$maxgid = (int)$maxgid +1;
			
			// Getting the redcap field description from redcap corresponding project
			$rcfielddesc =  getrclabels($projectid,$redcapfieldval);
//			echo $rcfielddesc."<br>";
			$rcfielddesc =  str_replace("'","''",$rcfielddesc);
                        $rcfielddesc =  str_replace('"',"''",$rcfielddesc);


			$sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcapfield_desc, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldval', 'value' ,'$rcfielddesc', '$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
        	        //PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

			if (! empty($redcapfielddate)) {
				// Getting the redcap field description from redcap corresponding project
				$rcfielddesc =  getrclabels($projectid,$redcapfielddate);
//				echo $rcfielddesc."<br>";
				$rcfielddesc =  str_replace("'","''",$rcfielddesc);
		                $rcfielddesc =  str_replace('"',"''",$rcfielddesc);


				$sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcapfield_desc, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfielddate', 'date' ,'$rcfielddesc', '$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}

			if (! empty($redcapfieldrater)) {
				// Getting the redcap field description from redcap corresponding project
                                $rcfielddesc=  getrclabels($projectid,$redcapfieldrater);
//				echo $rcfielddesc."<br>";
				$rcfielddesc =  str_replace("'","''",$rcfielddesc);
                                $rcfielddesc =  str_replace('"',"''",$rcfielddesc);

                                $sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcapfield_desc, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldrater', 'rater' ,'$rcfielddesc', '$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}

			if (! empty($redcapfieldnotes)) {
				// Getting the redcap field description from redcap corresponding project
                                $rcfielddesc=  getrclabels($projectid,$redcapfieldnotes);
//				echo $rcfielddesc."<br>";
				$rcfielddesc =  str_replace("'","''",$rcfielddesc);
                                $rcfielddesc =  str_replace('"',"''",$rcfielddesc);

                                $sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcapfield_desc, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldnotes', 'notes' ,'$rcfielddesc', '$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
                                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}

			if (! empty($redcapfieldStime)) {
				// Getting the redcap field description from redcap corresponding project
                                $rcfielddesc=  getrclabels($projectid,$redcapfieldStime);
//				echo $rcfielddesc."<br>";
				$rcfielddesc =  str_replace("'","''",$rcfielddesc);
                                $rcfielddesc =  str_replace('"',"''",$rcfielddesc);

				$sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcapfield_desc, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldStime', 'time' ,'$rcfielddesc', '$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}

			if (! empty($redcapfieldEtime)) {
				// Getting the redcap field description from redcap corresponding project
                                $rcfielddesc=  getrclabels($projectid,$redcapfieldEtime);
//				echo $rcfielddesc."<br>";
				$rcfielddesc =  str_replace("'","''",$rcfielddesc);
                                $rcfielddesc =  str_replace('"',"''",$rcfielddesc);

                                $sqlstring = "insert ignore into redcap_import_mapping (project_id, redcap_event, redcap_form, redcap_fields, redcap_fieldtype, redcapfield_desc, redcap_fieldgroupid, nidb_datatype, nidb_variablename, nidb_instrumentname) values($projectid, '$Event', '$redcapform', '$redcapfieldEtime', 'etime' ,'$rcfielddesc', '$maxgid', '$nidbdatatype', '$nidbvariablename', '$nidbinstrumentname')";
                                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}


        	        $sqlstring = "commit";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		}
                
        }

        /* -------------------------------------------- */
        /* ------- DeleteMapping ---------------------- */
        /* -------------------------------------------- */
        function DeleteMapping($mappingid,$projectid,$nidbdatatype) {

                MySQLiQuery("start transaction", __FILE__, __LINE__);

                $sqlstring = "delete from redcap_import_mapping where formmap_id = $mappingid";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                
                MySQLiQuery("commit", __FILE__, __LINE__);
                
		

	}



	/*-----------------------------------------------*/
	/* -----------------getprojectinfo---------------*/
	/*----------------------------------------------*/

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
		
		list($In_Name,$In_Label)=getrcinstruments($projectid);?>

	<div class="ui grid">
		<h1 class="ui header" align="left"> NiDB ===> Redcap Mapping 
			<div class="sub header"> Project: <?=$projectname?> &nbsp; &nbsp; Redcap Server: <?=$redcapurl?></div>
		</h1>


	<div class="three column row" align="center">
	<form  class="ui form" action="redcapmapping.php">
        <input type="hidden" name="action" value="displaymapping">
        <input type="hidden" name="projectid" value="<?=$projectid?>">
		
		<table class="ui basic table">

			<tr>
				<td style="border-right: 1px solid #bdbdbd; text-align: center">
					 <h4 align="left">NiDB Data Type <i class="small blue question circle outline icon" title="Select the datatype for data being stored in NiDB.<br> Measures, Vitals and Drug / Dose"></i></h4>
					 <select name="nidbdatatype" id="nidbdatatype" required>
                                                <option value="m" >Measures</option>
                                                <option value="v" >Vitals </option>
						<option value="d" >Drug/dose </option>
					</select>
					<script type="text/javascript"> document.getElementById('nidbdatatype').value = "<?php echo $_GET['nidbdatatype'];?>";</script>
				</td>
				<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>
				<td>
					<h4>Redcap Form <i class="small blue question circle outline icon" title="Select a Redcap Form to map"></i></h4>
					  <select  name="inst" id="inst" required onchange="this.form.submit()">
						 <?for($In=0;$In < count($In_Name); $In++){ ?>
						     <option value=<?=$In_Name[$In]?>> <?=$In_Name[$In]?> </option>
                                               <?}?>
					</select>
					<script type="text/javascript"> document.getElementById('inst').value = "<?php echo $_GET['inst'];?>";</script>

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
        function DisplayRedCapSettings($projectid,$inst,$uinst,$nidbdatatype,$jointid) {
		
		        
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
                
                <form class="ui form" action="redcapmapping.php" method="post">
                <input type="hidden" name="action" value="updatemapping">
                <input type="hidden" name="projectid" value="<?=$projectid?>">
		<input type="hidden" name="inst" value="<?=$inst?>">
		<input type="hidden" name="nidbdatatype" value="<?=$nidbdatatype?>">

<?
		/* Updating for number of columns based on type (drug, measure, Vital) */

		if ($nidbdatatype == 'm') {
			$Cols = "eleven";
			$tp = "Measure";
			$ColSp =8;
?>


	<div class="ui <?=$Cols?> wide column" style="overflow: auto; padding:0px">

	   <h3 class="ui top attached header" align="left"> Mapping variables for "<?=ucfirst($inst)?>" as "<?=$tp?>"<h3>
                <table class="ui graydisplaytable">
                        <thead>
                                <tr>
                                        <th style="text-align: center; border-right: 1px solid #bdbdbd" colspan="2">NiDB</th>
                                        <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 20pt; border-right: 1px solid #bdbdbd; padding: 0px 30px">&#10132;</th>
					<th style="text-align: center" colspan="<?=$ColSp?>" >Redcap</th>
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
					<td style="border-right: 1px solid #bdbdbd"><input type="text" name="nidbinstrumentname" value=<?=$inst?>></td>
					
					<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>

					<td>
						<select name="redcapevent[]"  multiple required size="3">
			                           <?for($Eve=0;$Eve < count($Event_s); $Eve++){ ?>
			                              <option value=<?=$Event_s[$Eve]?>> <?=$Event_s[$Eve]?> </option>
 				                   <?}?>	
                                                </select>
                                        </td>
				
					<? $V_names=getrcvariables($projectid,$inst,$redcapevent);?>

					<td>
						<select name="redcapfieldval" required  onchange="document.getElementById('nidbvariablename').value=this.options[this.selectedIndex].text;">
						    <option value=''> </option>
						   <?for($Fi=0;$Fi < count($V_names); $Fi++){?> 
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>
						   
                                                   <?}?>
                                                </select>
					</td>

					 <td>
                                                <select name="redcapfielddate" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
					</td>

					<td>
                                                <select name="redcapfieldrater" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>
                                                   <?}?>
                                                </select>
					</td>

					<td>
                                                <select name="redcapfieldnotes" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
                                        </td>

					<td>
                                                <select name="redcapfieldStime">
                                                      <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
                                        </td>
					 <td style="border-right: 1px solid #bdbdbd">
						<select name="redcapfieldEtime">
						      <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
					</td>

                                        <td title="Save mapping"><input type="submit" value="Add"> </td>
                                </tr>
                                <?
					$sqlstring = "select * from redcap_import_mapping where project_id = $projectid and redcap_form = '$inst' order by nidb_instrumentname desc";
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
                                                        <td><?=$variable?></td>
							<td style="border-right: 1px solid #bdbdbd"><?=$nidbinstrument?></td>
							<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>
                                                        <td><?=$event?></td>
                                                        <td><?=$form?></td>
							<td> <?=$fields?></td>
							<td> </td>
							<td> </td>
							<td> </td>
                                                        <td style="border-right: 1px solid #bdbdbd"><?=$fieldtype?></td>
							<td title="Delete mapping"><a href="redcapmapping.php?action=deletemapping&mappingid=<?=$formmapid?>&projectid=<?=$projectid?>&inst=<?=$inst?>&uinst=<?=$uinst?>&nidbdatatype=<?=$nidbdatatype?>&jointid=<?=$jointid?>" class="redlinkbutton" style="font-size: smaller">X</a></td>
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

			$sqltabcols="SELECT `COLUMN_NAME`FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='nidb' AND `TABLE_NAME`='drugs'";
			$resultcols = MySQLiQuery($sqltabcols, __FILE__, __LINE__);

?>


	<div class="ui <?=$Cols?> wide column" style="overflow: auto; padding:0px">

	   <h3 class="ui top attached header" align="left"> Mapping variables for "<?=ucfirst($inst)?>" as "<?=$tp?>"<h3>
                <table class="ui graydisplaytable">
                        <thead>
                                <tr>
                                        <th style="text-align: center; border-right: 1px solid #bdbdbd" colspan="2">NiDB</th>
                                        <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 20pt; border-right: 1px solid #bdbdbd; padding: 0px 30px">&#10132;</th>
					<th style="text-align: center" colspan="4" >Redcap</th>
                                </tr>
                                <tr>
                                        <th>Variable</th>
					<th style="border-right: 1px solid #bdbdbd">Instrument</th>
					
					<th> Event </th>
					<th> Variable</th>
					<th> Type </th>
                                        <th></th>
                                </tr>
                        </thead>
                        <tbody>
				<tr>
					
					<td>
						<select name="nidbvariablename" required>
						  <option value='drugname'> Drugname</option>
						  <? while ($row = mysqli_fetch_array($resultcols, MYSQLI_ASSOC)) {
						  $colsname=$row['COLUMN_NAME'];
						  if (strpos($colsname, "_id") == false){
							  if (substr($colsname,5)=='startdate' || substr($colsname,5)=='enddate'){
								  $opt=ucfirst(substr($colsname,5)).'/ time';
							  }  elseif (substr($colsname,5)=='type'){
								  $opt='Class';
							  } else {
								  $opt=ucfirst(substr($colsname,5));
							  }
							
						
						  ?>
						  <option value=<?=$colsname?>> <?=$opt?> </option>
						  <?}}?>
                                                </select>
					</td>

					<td style="border-right: 1px solid #bdbdbd"><input type="text" name="nidbinstrumentname" value=<?=$inst?>></td>
					
					<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>

					<td>
						<select name="redcapevent[]"  multiple required size="3">
			                           <?for($Eve=0;$Eve < count($Event_s); $Eve++){ ?>
			                              <option value=<?=$Event_s[$Eve]?>> <?=$Event_s[$Eve]?> </option>
 				                   <?}?>	
                                                </select>
                                        </td>
				
					<? $V_names=getrcvariables($projectid,$inst,$redcapevent);?>

					<td>
						<select name="redcapfieldname" required >
						    <option value=''> </option>
						   <?for($Fi=0;$Fi < count($V_names); $Fi++){?> 
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>
						   
                                                   <?}?>
                                                </select>
					</td>

					 <td>
                                                <select name="redcapfieldtype" required style="border-right: 1px solid #bdbdbd">
							<option value='value'> Value</option>
							<option value='date'> Date </option>
							<option value='rater'> Rater</option>
							<option value='notes'> Notes</option>
							<option value='time'> Start Time</option>
							<option value='etime'> End Time</option>
                                                </select>
					</td>


                                        <td title="Save mapping"><input type="submit" value="Add"> </td>
				</tr>
					
                                <?      
					$sqlstring = "select * from redcap_import_mapping where project_id = $projectid and redcap_form = '$inst' order by nidb_instrumentname desc";
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
                                                        <td><?=$variable?></td>
							<td style="border-right: 1px solid #bdbdbd"><?=$nidbinstrument?></td>
							<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>
                                                        <td><?=$event?></td>
							<td> <?=$fields?></td>
                                                        <td style="border-right: 1px solid #bdbdbd"><?=$fieldtype?></td>
                                                        <td title="Delete mapping"><a href="redcapmapping.php?action=deletemapping&mappingid=<?=$formmapid?>&projectid=<?=$projectid?>&inst=<?=$inst?>&uinst=<?=$uinst?>&nidbdatatype=<?=$nidbdatatype?>&jointid=<?=$jointid?>" class="redlinkbutton" style="font-size: smaller">X</a></td>
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
                        $Cols ="eleven";
                        $tp = "Vitals";
                        $ColSp =8;

?>

	<div class="ui <?=$Cols?> wide column" style="overflow: auto; padding:0px">
	   <h3 class="ui top attached header" align="left"> Mapping variables for "<?=ucfirst($inst)?>" as "<?=$tp?>"<h3>
                <table class="ui graydisplaytable">
                        <thead>
                                <tr>
                                        <th style="text-align: center; border-right: 1px solid #bdbdbd" colspan="2">NiDB</th>
                                        <th rowspan="2" style="text-align: center; vertical-align: middle; font-size: 20pt; border-right: 1px solid #bdbdbd; padding: 0px 30px">&#10132;</th>
					<th style="text-align: center" colspan="<?=$ColSp?>" >Redcap</th>
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
					<td style="border-right: 1px solid #bdbdbd"><input type="text" name="nidbinstrumentname" value=<?=$inst?>></td>
					
					<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>

					<td>
						<select name="redcapevent[]"  multiple required size="3">
			                           <?for($Eve=0;$Eve < count($Event_s); $Eve++){ ?>
			                              <option value=<?=$Event_s[$Eve]?>> <?=$Event_s[$Eve]?> </option>
 				                   <?}?>	
                                                </select>
                                        </td>
				
					<? 
						$V_names=getrcvariables($projectid,$inst,$redcapevent);
					?>

					<td>
						<select name="redcapfieldval" required  onchange="document.getElementById('nidbvariablename').value=this.options[this.selectedIndex].text;">
						    <option value=''> </option>
						   <?for($Fi=0;$Fi < count($V_names); $Fi++){?> 
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>
						   
                                                   <?}?>
                                                </select>
					</td>

					 <td>
                                                <select name="redcapfielddate" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
					</td>

					<td>
                                                <select name="redcapfieldrater" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>
                                                   <?}?>
                                                </select>
					</td>

					<td>
                                                <select name="redcapfieldnotes" >
                                                       <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
                                        </td>

					<td>
                                                <select name="redcapfieldStime">
                                                      <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
                                        </td>
					 <td style="border-right: 1px solid #bdbdbd">
						<select name="redcapfieldEtime">
						      <option value=''> </option>
                                                   <?for($Fi=0;$Fi < count($V_names); $Fi++){?>
                                                       <option value=<?=$V_names[$Fi]?>> <?=$V_names[$Fi]?> </option>

                                                   <?}?>
                                                </select>
					</td>

                                        <td title="Save mapping"><input type="submit" value="Add"> </td>
                                </tr>
                                <?
					$sqlstring = "select * from redcap_import_mapping where project_id = $projectid and redcap_form = '$inst' order by nidb_instrumentname desc";
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
                                                        <td><?=$variable?></td>
							<td style="border-right: 1px solid #bdbdbd"><?=$nidbinstrument?></td>
							<td style="border-right: 1px solid #bdbdbd; text-align: center">&#10132;</td>
                                                        <td><?=$event?></td>
                                                        <td><?=$form?></td>
							<td> <?=$fields?></td>
							<td> </td>
							<td> </td>
							<td> </td>
                                                        <td style="border-right: 1px solid #bdbdbd"><?=$fieldtype?></td>
                                                        <td title="Delete mapping"><a href="redcapmapping.php?action=deletemapping&mappingid=<?=$formmapid?>&projectid=<?=$projectid?>&inst=<?=$inst?>&uinst=<?=$uinst?>&nidbdatatype=<?=$nidbdatatype?>&jointid=<?=$jointid?>" class="redlinkbutton" style="font-size: smaller">X</a></td>
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

?>

	<form class"=ui form" action="redcapmapping.php" method="post">

                <input type="hidden" name="action" value="default">
                <input type="hidden" name="projectid" value="<?=$projectid?>">
		<br>

		<button class="ui right floated large primary button"  type="submit"><i class="map icon"></i> Mapping / <i class="level down alternate icon"></i> Transfer</button>
	</form>	
<?
  }


	/* -------------------------------------------- */
        /* -- Transfering Data into NiDB from Redcap -- */
        /* -------------------------------------------- */

	function TransferData($projectid,$nidbinstrument,$rcmainevent,$rcid,$jointid)
	{

/*		echo $projectid."<br>";
		echo $rcmainevent."<br>";
		echo $rcid."<br>";
		echo $jointid."<br>";
		echo $nidbinstrument."<br>";
 */
	
		$sqlstringIn = "SELECT DISTINCT(`redcap_form`) as Inst FROM `redcap_import_mapping` WHERE `nidb_instrumentname`='$nidbinstrument' and project_id = '$projectid' ";
                $resultIn = MySQLiQuery($sqlstringIn, __FILE__, __LINE__);
                $rowIn = mysqli_fetch_array($resultIn, MYSQLI_ASSOC);
                $inst = $rowIn['Inst'];

		$Report_Id = getrcrecords($projectid,$inst,$rcmainevent,array($rcid,$jointid),'');

                $Flg = 0;
                foreach ($Report_Id as $block => $info) {
			$sid = $info[$jointid];
			$sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$sid' ) and project_id = '$projectid' ";


	                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
        	        $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                	$enrollmentid = $rowEn['enrollment_id'];

                	if (mysqli_num_rows($resultEn) > 0 && $info[$jointid]!='') {
                        	$RC_Id[$Flg] = $info[$rcid];
                        	$subjectid[$Flg] = $info[$jointid];

		//	echo $RC_Id[$Flg]."&nbsp";	
		//	echo $subjectid[$Flg]."<br>";

                        	$Flg = $Flg + 1;}
			}

	// Find the nidb data type (measure, vitals and dose/drug)
	$sqlstringType = "SELECT DISTINCT(nidb_datatype) as D_Type FROM redcap_import_mapping WHERE project_id = '$projectid'and nidb_instrumentname='$nidbinstrument'";
	$resultType = MySQLiQuery($sqlstringType, __FILE__, __LINE__);
	$rowType = mysqli_fetch_array($resultType, MYSQLI_ASSOC);
        $Dtype = $rowType['D_Type'];		
//	echo $Dtype;

	// 	Transfer Dose / Drug Data
	if ($Dtype=='d'){
		
		
		// Extracting Events from mapping table

		$sqlstringEvents = "SELECT DISTINCT(redcap_event) as RC_Events FROM redcap_import_mapping WHERE project_id = '$projectid'and nidb_instrumentname='$nidbinstrument'";
        	$resultEvents = MySQLiQuery($sqlstringEvents, __FILE__, __LINE__);
		while ($rowEvents = mysqli_fetch_array($resultEvents, MYSQLI_ASSOC)) {
			$redcapevent = $rowEvents['RC_Events'];
		//	echo $redcapevent."<br>";
	
			$sqlstringdrugs = "SELECT `nidb_variablename`,`redcap_fields`, `redcap_fieldtype` FROM `redcap_import_mapping` WHERE `project_id`='$projectid' and `nidb_instrumentname`='$nidbinstrument' and `redcap_event`='$redcapevent' order by `nidb_variablename`";		
			$resultdrugs = MySQLiQuery($sqlstringdrugs, __FILE__, __LINE__);
			$cnt = 0;
		
		while ($rowdrugs = mysqli_fetch_array($resultdrugs, MYSQLI_ASSOC)) {
			$nidbvname[$cnt] = $rowdrugs['nidb_variablename'];
			$rcfields[$cnt] = $rowdrugs['redcap_fields'];
			$rcfieldtype[$cnt] = $rowdrugs['redcap_fieldtype'];
		//	echo $nidbvname[$cnt].' <== '.$rcfields[$cnt].' Type of '.$rcfieldtype[$cnt]."<br>";
			$cnt=$cnt+1;
		}


		$rcallfields=array_merge(array($rcid),$rcfields);

		$Recd = getrcrecords($projectid,'',$redcapevent,$rcallfields,$RC_Id);
//		var_dump($Recd);
		 $Flg = -1;
                foreach ($Recd as $block => $inf) {
			$Flg = $Flg +1;
			$drugdose = [];
			for ($i=0; $i < count($nidbvname); $i++){
				if ($rcfieldtype[$i]=='time' || $rcfieldtype[$i]=='etime'){
					$drugdose[$nidbvname[$i]] = $inf[$rcfields[array_search('date',$rcfieldtype)]].' '.$inf[$rcfields[$i]];
				}
				else{
					$drugdose[$nidbvname[$i]] = $inf[$rcfields[$i]];
				}
			}

			AddDose($subjectid[$Flg],$projectid,$drugdose);
			$varadded = implode(',',$nidbvname);
                        echo "Data for Variables: [".$varadded."] from Redcap Event  [".$redcapevent."] of Subject ".$subjectid[$Flg]." is transferred"."<br>";



		
		}// Loop over records that were extracted above 

	}// Loop of Events 			
	}// Drug / Dose Entering Loop







	// Extracting Events from mapping table

	$sqlstringEvents = "SELECT DISTINCT(redcap_event) as RC_Events FROM redcap_import_mapping WHERE redcap_form = '$inst' and project_id = '$projectid'and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' ";
        $resultEvents = MySQLiQuery($sqlstringEvents, __FILE__, __LINE__);
	while ($rowEvents = mysqli_fetch_array($resultEvents, MYSQLI_ASSOC)) {
                        $redcapevent = $rowEvents['RC_Events'];
//			echo $redcapevent."<br>";	
	
	

			
	// Find the nidb data type (measure, vitals and dose/drug)
	$sqlstringType = "SELECT DISTINCT(nidb_datatype) as D_Type FROM redcap_import_mapping WHERE redcap_form = '$inst' and project_id = '$projectid'and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' and `redcap_event`='$redcapevent'";
	$resultType = MySQLiQuery($sqlstringType, __FILE__, __LINE__);
	$rowType = mysqli_fetch_array($resultType, MYSQLI_ASSOC);
        $Dtype = $rowType['D_Type'];		
///	echo $Dtype."<br>";
	
		
	// Getting Data from Redcap and looping over Records
	$Rec = getrcrecords($projectid,$inst,$redcapevent,array($rcid,$jointid),$RC_Id);
                $Flg = -1;
                foreach ($Rec as $block => $info) {
			$Flg = $Flg +1;




	// Let us start getting the redcap fieldnames from the mapping table
	

	$sqlstringFields = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' and redcap_event='$redcapevent'";
        $resultFields = MySQLiQuery($sqlstringFields, __FILE__, __LINE__);
//        $rowFields = mysqli_fetch_array($resultFields, MYSQLI_ASSOC);
//        $RCFields = $rowFields['redcap_fields'];
	  while ($rowFields = mysqli_fetch_array($resultFields, MYSQLI_ASSOC)) {
             	$redcapfield = $rowFields['redcap_fields'];
///		echo $redcapfield."<br>";
///		echo $info[$redcapfield]."<br>";
	


		
		// Get the date field
		$sqlstringdate = "SELECT redcap_fields FROM `redcap_import_mapping` WHERE `redcap_form`='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='date' and redcap_event='$redcapevent' and redcap_fieldgroupid = (Select distinct(`redcap_fieldgroupid`) from `redcap_import_mapping` WHERE redcap_form='$inst' and project_id=$projectid and nidb_instrumentname='$nidbinstrument' and redcap_fieldtype='value' and redcap_event='$redcapevent' and `redcap_fields`='$redcapfield')";

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

	    
///		echo $info[$RCdate]."<br>";
		//
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
///		 echo $info[$RCrater]."<br>";
		
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
///		echo $info[$RCnotes]."<br>";
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
///		echo $info[$RCtime]."<br>";

	  

		// Transferring the data Now
		  
		  $CID = array();
                  $AddN = 0;
		  
		  switch ($Dtype) {
                                case 'm':

					$instid = MeasureInstr($inst);
					if ($RCtime == ''){ $mdate = $info[$RCdate];} else{
						$mdate = $info[$RCdate].' '.$info[$RCtime];}
                                                $Reg = Addmeasures($subjectid[$Flg],$projectid, $redcapfield, $info[$redcapfield],$inst, $instid, $info[$RCnotes], $info[$RCrater], $mdate,$mdate,'');

						if ($Reg == 0){
							array_push($CID ,$subjectid[$Flg]);
						}
						else{ echo $redcapfield." values  for ".$subjectid[$Flg]." are transferred"."<br>";}

                                                $AddN = $AddN + $Reg;
                                 break;
                                case 'v':
					$vitalStdate = $info[$RCdate].' '.$info[$RCtime];
//					echo $subjectid[$Flg];
                                                $Reg =  Addvitals($subjectid[$Flg],$projectid,$redcapfield,$info[$redcapfield],$inst,$info[$RCnotes], $info[$RCrater], $info[$RCdate], $vitalStdate, '');

					if ($Reg == 0){array_push($CID ,$subjectid[$Flg]);}
					 else{ echo $redcapfield." values  for ".$subjectid[$Flg]." are transferred"."<br>";}

                                                $AddN = $AddN + $Reg;
                                break;

		  }// End of Case statement for nidb datatype

		


	  }//end of Fields While-Loop
			
	//	echo "The following subject/s were not found in NiDB for ".$redcapevent." event";
           //     echo implode(", ",$CID)
         //       echo "Total ".$AddN." records transferred";
		

	 } // end of Redcap records' For-Loop
	}//end of Event While-Loop


	}


 

	/*--------------------------------------------------------*/
	/* ---------------- TRANSFERING MEASURE'S DATA -----------*/
	/*--------------------------------------------------------*/

        function Addmeasures($subjectid,$projectid, $measurename, $measurevalue,$Form_name, $instid, $measurenotes, $measurerater, $measurestdate,$measureenddate,$measuredesc) {

                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";

//              PrintSQL($sqlstringEn);
                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select measurename_id from measurenames where measure_name = '$measurename'";
		

                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $measurenameid = $rowA['measurename_id'];
                }
                else {
			// Getting the redcap field description from redcap corresponding project
			$rcfielddesc=  getrclabels($projectid,$measurename);
	//		echo $rcfielddesc."<br>";

        	        $rcfielddesc =  str_replace("'","''",$rcfielddesc);
                	$rcfielddesc =  str_replace('"',"''",$rcfielddesc);


			$sqlstringA = "insert into measurenames (measure_name, measure_desc) values ('$measurename','$rcfielddesc')";
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

   function  AddDose($subjectid,$projectid,$drugdose){

	   // Decompacting variables
		extract($drugdose, EXTR_OVERWRITE);


                $sqlstringEn = "SELECT enrollment_id FROM `enrollment` WHERE subject_id in (select subject_id from subjects where subjects.uid = '$subjectid' ) and project_id = '$projectid' ";
/*                PrintSQL($sqlstringEn);*/


                $resultEn = MySQLiQuery($sqlstringEn, __FILE__, __LINE__);
                $rowEn = mysqli_fetch_array($resultEn, MYSQLI_ASSOC);
                $enrollmentid = $rowEn['enrollment_id'];

                $sqlstringA = "select drugname_id from drugnames where drug_name = '$drugname'";
                //echo "$sqlstringA\n";
                $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                if (mysqli_num_rows($resultA) > 0) {
                        $rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC);
                        $drugname_id = $rowA['drugname_id'];
                }
		else {
			 $sqlstringA = "insert into drugnames (drug_name) values ('$drugname')";
                        //echo "$sqlstringA\n";
                        $resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
                        $drugname_id = mysqli_insert_id($GLOBALS['linki']);
                        echo 'A new drugname added!';?><br><?
                }


                $drug_notes = str_replace("'","''",$drug_notes);
		$drug_notes = str_replace('"',"''",$drug_notes);

		if ($enrollmentid!=''){
			$sqlstring = "insert ignore into drugs (enrollment_id, drug_startdate, drug_enddate, drug_doseamount, drug_dosefrequency, drug_route, drugname_id, drug_type, drug_dosekey, drug_doseunit, drug_frequencymodifier, drug_frequencyvalue, drug_frequencyunit, drug_dosedesc, drug_rater, drug_notes, drug_entrydate, drug_recordcreatedate, drug_recordmodifydate) values ($enrollmentid,'$drug_startdate',NULLIF('$drug_enddate',''), NULLIF('$drug_doseamount',''), NULLIF('$drug_dosefrequency',''),NULLIF('$drug_route',''), '$drugname_id', NULLIF('$drug_type',''), NULLIF('$drug_dosekey',''), NULLIF('$drug_doseunit',''), NULLIF('$drug_frequencymodifier',''), NULLIF('$drug_frequencyvalue',''), NULLIF('$drug_frequencyunit',''), NULLIF('$drug_dosedesc',''), NULLIF('$drug_rater',''), NULLIF('$drug_notes',''),'$drug_entrydate',now(),now()) on duplicate key update drug_doseunit = '$drug_doseunit', drug_recordmodifydate = now()";
		//	PrintSQL($sqlstring);	
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}
           	else { 
			echo 'Subject '.$subjectid.' was not found in NiDB';?><br><?}


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
			// Getting the redcap field description from redcap corresponding project
			$rcfielddesc=  getrclabels($projectid,$vitalname);
	//		echo $rcfielddesc."<br>";
	
        	        $rcfielddesc =  str_replace("'","''",$rcfielddesc);
                	$rcfielddesc =  str_replace('"',"''",$rcfielddesc);


			$sqlstringA = "insert into vitalnames (vital_name, vital_desc) values ('$vitalname','$rcfielddesc')";
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

