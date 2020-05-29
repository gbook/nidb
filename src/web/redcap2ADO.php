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
		<title>NiDB- Transfer RedCap Data</title>
	</head>

	<div id="wrapper">

<?
	//require "config.php";
	require "functions.php";
	//require "includes.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	//PrintVariable($_POST,'POST');



	/*** Variables SETUP **/
	$action = GetVariable("action");
	$subjectid = GetVariable("subject_id");
	$projectid = GetVariable("project_id");
	$measurename= GetVariable("measure_name");
	$measurevalue = GetVariable("measure_value");
	$instid = GetVariable("measureinstrument_id");
	$measurenotes = GetVariable("measure_notes");
	$measurerater = GetVariable("measure_rater");
	$measurestdate = GetVariable("measure_startdate");
	$measureenddate = GetVariable("measure_enddata");
	$measureentrydate = GetVariable("measure_entrydate");
	$vitalid = GetVariable("vitalid");
        $enrollmentid = GetVariable("enrollmentid");
        $vitalname = GetVariable('vital_name');
        $vitalvalue = GetVariable('vital_value');
        $vitalnotes = GetVariable('vital_notes');
        $vitaldate = GetVariable('vital_date');
        $vitaltype = GetVariable('vital_type');

	/* Lets do some action */
/*        switch ($action) {
                case 'addvital':
                        AddVital($enrollmentid, $vitalname, $vitalvalue, $vitalnotes, $vitaldate, $vitaltype);
                        DisplayVitalList($enrollmentid);
                        break;
                case 'deletevital':
                        DeleteVital($vitalid);
                        DisplayVitalList($enrollmentid);
                        break;
                default:
                        $projectid=ShowProjects();
		        ShowRCdata($projectid);
        }*/

$projectid=ShowProjects();
$Pid = $projectid;
list($Pid,$F_name,$INSTR,$Subt) = ShowRCInst($projectid);
ShowRCdata($Pid,$F_name,$INSTR,$Subt);

/*-------------------------------------functions--------------------------------*/
	/********************************/
	/*******ShowProjects*************/
	/********************************/

function ShowProjects(){

 if (isset($_REQUEST['project'])){
	$selected_choice = $_REQUEST['project'];}
   else { $selected_choice = "";}

?>

<h2 align="left">RedCap to NiDB</h2>



 <form method="post" action="">
   <b>Select Project: </b>
   <select name = "project">
	
	<option value="0" >Projects ...</option>	

<?   $sqlstringpro = "select * from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '" . $_SESSION['username'] . "') order by project_name";

   $result = MySQLiQuery($sqlstringpro,__FILE__,__LINE__);
  while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $project_id = $row['project_id'];
        $project_name = $row['project_name'];
	$project_costcenter = $row['project_costcenter'];
        if ($selected_choice==$project_id) { $selected = "selected"; } else { $selected = ""; }
        ?>
        <option value="<?=$project_id?>"<?=$selected?>> <?=$project_name?> (<?=$project_costcenter?>)</option>
        <?
        }

?>
   </select>

   <input type="submit" value="Use selected project">
 </form>
<?

$projectid = $_POST["project"];

return $projectid;


?>
<br><br>



<?}

 	/********************************/
        /**** Show redcap instruments****/
        /********************************/

function ShowRCInst($projectid){

$sqlstringtok = "select redcap_token,redcap_server from projects where project_id = '$projectid'";

   $resulttok = MySQLiQuery($sqlstringtok,__FILE__,__LINE__);
   $row = mysqli_fetch_array($resulttok, MYSQLI_ASSOC);
   $rctoken = $row['redcap_token'];
   $rcserver = $row['redcap_server'];

if ($rctoken !='')
{
//	echo $rctoken; echo "<br>"; echo $rcserver;


$data = array(
//    'token' => '189D730D43630D398EF600B2FC5D59EF',
    'token' => $rctoken,
    'content' => 'instrument',
    'format' => 'json',
    'returnFormat' => 'json'
);
$ch = curl_init();
//curl_setopt($ch, CURLOPT_URL, 'https://redcap.hhchealth.org/api/');
curl_setopt($ch, CURLOPT_URL,$rcserver );
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



$FormList = json_decode($output,true);
curl_close($ch);


?>
<table class="smalldisplaytable">
<form method="post" action="">
<td valign="top">
        <b> Redcap Instruments: </b>
        <select name="form_name" >

        <?
        for ($fl=0;$fl <= count($FormList); $fl++){
                $FL = array_values($FormList[$fl]);

/*      echo $FL{0}; echo ": "; echo $FL{1}; echo "<br>";*/
		/* $FL{0} : Name of the form in redcap; $FL{1]: Title of the form in Redcap */


                ?><option value ="<? echo $FL{0}?>"> <? echo $FL{1}?> </option><?
                }?>

        </select>
</td>

<td valign="top">
        <input type="checkbox" name = "instr[]" value="screening_day_arm_1" checked > Screening Day<br>
	<input type="checkbox" name = "instr[]" value="dose_day_1_arm_1" > Dose Day 1<br>
        <input type="checkbox" name = "instr[]" value="dose_day_2_arm_1"> Dose Day 2<br>
        <input type="checkbox" name = "instr[]" value="dose_day_3_arm_1"> Dose Day 3<br><br>

</td>



<td></td>
<td valign="bottom">
<button type="disp" name="disp"><b>Show Data</b></button>
</td>


<td></td>
<td valign="bottom">
<button type="move" name="move"><b>Move Data</b></button>
</td>
</form>
</table>

<?

}
else if ($projectid == 0){  echo "Select a Project";}
else
{
        echo "No Redcap instument is attached to this project";
}



$F_name = $_POST["form_name"];


foreach($_POST["instr"] as $Instr){
        $INSTR[] = $Instr;}

if (isset($_POST['disp'])){
$Subt = 'D';}
else if (isset($_POST['move'])){
$Subt = 'M';}
return array($projectid,$F_name,$INSTR,$Subt);

}

	/********************************/
        /*******Show redcap data*********/
        /********************************/





function ShowRCdata($projectid, $F_name, $INSTR, $Subt){

if ($Subt=='D'){
 
     if($F_name=='dose_information' || $F_name=='dose_day_cover_sheet'){
         $Form_name = array('dose_information','dose_day_cover_sheet');}
     else {   $Form_name = array($F_name);}
	DispForm($Form_name,$INSTR);}
else if ($Subt=='M'){

       if($F_name=='dose_information' || $F_name=='dose_day_cover_sheet'){
	 $Form_name = array('dose_information','dose_day_cover_sheet');} 		
       else {  	$Form_name = array($F_name);}

	TransferData($Form_name, $INSTR,$projectid);
	DispForm($Form_name,$INSTR);}
echo $projectid;
}



        /*-------------------------------------------*/
        /*-------------Report-------------------*/
        /*-------------------------------------------*/


function DispForm($Form_name,$INSTR){

    $data = array(
    'token' => '189D730D43630D398EF600B2FC5D59EF',
    'content' => 'record',
    'format' => 'json',
    'type' => 'flat',
#    'records' => array('001','002','003','004','005','006','007','008'),
    'fields' => array('record_id','scan_id','age'),
    'forms' => $Form_name,
/*    'events' => array('screening_day_arm_1','dose_day_1_arm_1','dose_day_2_arm_1','dose_day_3_arm_1'),*/

    'events' => $INSTR,	
    'rawOrLabel' => 'raw',
    'rawOrLabelHeaders' => 'raw',
    'exportCheckboxLabel' => 'false',
    'exportSurveyFields' => 'false',
    'exportDataAccessGroups' => 'false',
    'returnFormat' => 'json');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://redcap.hhchealth.org/api/');
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

        $report1 = json_decode($output,true);


        curl_close($ch);



/*	echo var_dump(array_values($report1[0]));
	echo "Hi";*/
	



	$Var_Names = array_keys($report1[0]); /* This variable ($Var_Names)contains names of all the variables in selected form */

        $Sp = ' ';?>

	<h2 align="left"><?=ucfirst($Form_name[0])?></h2>
	
	<table class="smalldisplaytable">
	 <thead align ="left">
	  <tr><?
        for ($xe=0; $xe <= count($Var_Names); $xe++) {
              ?><th><?  echo $Var_Names[$xe];?> </th> <?
                echo $Sp; }
	?></tr> 
	   </thead>
	   <tbody><?
    for ($vl=0; $vl <= count($report1); $vl++){	
	$Var_Values =  array_values($report1[$vl]);
		?><tr><?
	for ($xe=0; $xe <= count($Var_Values); $xe++) {
               ?><td><? echo $Var_Values[$xe]; ?></td><?
                echo $Sp; }
		?></tr><?}

?></tbody> </table>
<?}


	/*--------------------------------------------*/
        /*-----Save / Update (Transfer to Redcap)-----*/
        /*--------------------------------------------*/


function TransferData($Form_name,$INSTR,$projectid){

/* Infusing "Screening Day" information to get scanid */
   $projectid=175;

   if ($INSTR[0]!= 'screening_day_arm_1'){
        $Temp = $INSTR;
        $INSTR[0]= 'screening_day_arm_1';

        for ($Eye = 1; $Eye <= Count($Temp); $Eye++)
        {
                $INSTR[$Eye] = $Temp[$Eye-1];
        }}

	
    $data = array(
    'token' => '189D730D43630D398EF600B2FC5D59EF',
    'content' => 'record',
    'format' => 'json',
    'type' => 'flat',
#    'records' => array('001','002','003','004','005','006','007','008'),
    'fields' => array('record_id','scan_id','age'),
    'forms' => $Form_name,
/*    'events' => array('screening_day_arm_1','dose_day_1_arm_1','dose_day_2_arm_1','dose_day_3_arm_1'),*/


    'events' =>$INSTR,
    'rawOrLabel' => 'raw',
    'rawOrLabelHeaders' => 'raw',
    'exportCheckboxLabel' => 'false',
    'exportSurveyFields' => 'false',
    'exportDataAccessGroups' => 'false',
    'returnFormat' => 'json');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://redcap.hhchealth.org/api/');
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

        $report1 = json_decode($output,true);


        curl_close($ch);


        $Var_Names = array_keys($report1[0]); /* This variable ($Var_Names)contains names of all the variables in selected form */
	
//	$projectid = 175; 


	$Runner = 0;
	foreach ($report1 as $block => $info) 
	{
		$RecID = $info['record_id'];
		if ($info[$Var_Names[1]]=='screening_day_arm_1'){
			$Runner = $Runner +1;	
			$subjectid[$Runner] = $info['scan_id'];}
		$measurestdate = $info[$Var_Names[3]];
		$measureenddate = $info[$Var_Names[3]];
		$measurerater = $info[$Var_Names[4]];
		$measurenotes = $info[$Var_Names[5]];

		
		/* Inserting measures*/
		
		if (($Form_name[0] == 'discharge_checklist')  &&  $subjectid[$Runner]!='' && $INSTR[1] != 'screening_day_arm_1')
		{
			
			$measurestdate = $info[$Var_Names[4]];
                        $measureenddate = $info[$Var_Names[4]];
                        $measurerater = $info[$Var_Names[5]];
                        $measurenotes = $info[$Var_Names[6]];
			$measuredesc = '';
			$strt = 7;
			$instid = MeasureInstr($Form_name[0]);

			for ($jay=$strt; $jay < count($Var_Names)-1;$jay++){
				if ( $Var_Names[$jay]== 'discharge_dose')
                                {
					$measuredesc = $info[$Var_Names[$jay]];
					$jay = $jay +1;
					$Mname = $Var_Names[$jay];
                                	$MVal = $info[$Var_Names[$jay]];
				}
				else
				{	
                                	$Mname = $Var_Names[$jay];
					$MVal = $info[$Var_Names[$jay]];
					$jay = $jay+1;
					$measuredesc = $info[$Var_Names[$jay]];
				}

                        Addmeasures($subjectid[$Runner],$projectid,$Mname,$MVal,$Form_name[0],$instid,$measurenotes, $measurerater, $measurestdate,$measureenddate,$measuredesc);}



		}
		
		
		if (($Form_name[0] == 'audit' || $Form_name[0] == 'cuditr' ||  $Form_name[0] =='neuropsychology_data' ||  $Form_name[0] == 'scid_diagnoses' || $Form_name[0] == 'antisocial_personality_disorder' || $Form_name[0] == 'timeline_of_mj_use' || $Form_name[0] == 'dfaqcu_inventory' || $Form_name[0] == 'brief_marijuana_consequences_questionnaire_bmacq' || $Form_name[0] == 'protective_behavioral_strategies_for_marijuana_sca' || $Form_name[0] == 'sensation_seeking_scale' || $Form_name[0] == 'scl90' || $Form_name[0] =='handedness_questionnaire' || $Form_name[0] == 'asionrc_health_questionnaire') &&  $subjectid[$Runner]!='' && $INSTR[0] == 'screening_day_arm_1')
		{
			$measurestdate = $info[$Var_Names[3]];
	                $measureenddate = $info[$Var_Names[3]];
        	        $measurerater = $info[$Var_Names[4]];
                	$measurenotes = $info[$Var_Names[5]];
			$measuredesc = '';


			if ($Form_name[0] == 'audit')
			{
				$strt = 6;
				$instid = MeasureInstr($Form_name[0]);
			} 
			else if ($Form_name[0] == 'cuditr')
			{ 
				$strt = 7;
				$instid = MeasureInstr($Form_name[0]);
			}
			else if ($Form_name[0] == 'neuropsychology_data' )
                        {
                                $strt = 6;
				$instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'scid_diagnoses' )
                        {
                                $strt = 6;
				$instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'antisocial_personality_disorder' )
                        {
                                $strt = 6;
				$instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'timeline_of_mj_use' )
                        {
                                $strt = 6;
				$instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'dfaqcu_inventory' )
                        {
                                $strt = 6;
                                $instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'brief_marijuana_consequences_questionnaire_bmacq' )
                        {
                                $strt = 6;
                                $instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'protective_behavioral_strategies_for_marijuana_sca' )
                        {
                                $strt = 6;
                                $instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'sensation_seeking_scale' )
                        {
                                $strt = 6;
                                $instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'scl90' )
                        {
                                $strt = 6;
                                $instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'handedness_questionnaire' )
                        {
                                $strt = 5;
                                $instid = MeasureInstr($Form_name[0]);
                        }
			else if ($Form_name[0] == 'asionrc_health_questionnaire' )
                        {
                                $strt = 6;
                                $instid = MeasureInstr($Form_name[0]);
                        }


			for ($jay=$strt; $jay < count($Var_Names)-2;$jay++){		
				$Mname = $Var_Names[$jay];
				if (strstr($Mname,'notes'))
				{
				   $measurenotes = $info[$Var_Names[$jay]];
				   $jay = $jay+1;
				   $Mname = $Var_Names[$jay];
				}
/*				echo substr($Mname,-3);
				echo "<br>";
				
				echo substr($Mname,0,-4);				
				echo "<br>"; }*/

		  	Addmeasures($subjectid[$Runner],$projectid,$Mname,$info[$Var_Names[$jay]],$Form_name[0],$instid,$measurenotes, $measurerater, $measurestdate,$measureenddate,$measuredesc);}
		}
		/* Inserting Dose informationm*/	
		 /* var_dump($info[$Var_Names[1]]);*/

		if ($Form_name[0] == 'dose_information'  &&  $subjectid[$Runner]!='' && $info[$Var_Names[1]]!='screening_day_arm_1' )
                {

			$dosename =  $info[$Var_Names[1]];
			$dosestdate = $info[$Var_Names[4]].' '.$info[$Var_Names[8]];
	                $doseenddate = $info[$Var_Names[4]].' '.$info[$Var_Names[8]];
        	        $doserater = $info[$Var_Names[5]];
                	$dosenotes = $info[$Var_Names[6]];
		    	$dosekey = $info[$Var_Names[11]];
			$dosetime = $info[$Var_Names[8]];
			$doseinhaled = $info[$Var_Names[9]];
			$dosedelivered = $$info[$Var_Names[12]];

/*			echo $dosekey;
			echo ' ';
			echo $dosestdate;
			echo "<br>";
			echo $subjectid[$Runner];
			echo ' ';
			echo $projectid;
			echo "<br>";*/
			
		    if ($info[$Var_Names[4]]!=''){
                        AddDose($subjectid[$Runner],$projectid,$dosename,$dosestdate,$doseenddate,$dosedelivered,$doserater,$dosenotes,$dosekey,$doseinhaled);
			}
		    else { echo $info[$subjectid[$Runner]].' for '.$info[$Var_Names[1]].' Not Saved, Dose date / time cannot be left blank ...'; echo "<br>"; }
                }
		
		/* Inserting Vitals*/

		if (($Form_name[0] == 'urine_screenbreathalyzer') &&  $subjectid[$Runner]!='')
		{
			 $vitaldate = $info[$Var_Names[3]];
                         $vitalrater = $info[$Var_Names[4]];
                         $vitalnotes = $info[$Var_Names[5]];

                         $strt =6;

                   for ($jay=$strt;$jay < count($Var_Names)-2;$jay++){

                         $Vname = $Var_Names[$jay];
                         $vitalStdate = $vitaldate;

/*                         echo $subjectid[$Runner];echo "<br>";
                         echo $vitaldate; echo "<br>";
                         echo $Vname.'::'.$info[$Var_Names[$jay]];echo "<br>";}*/

                      Addvitals($subjectid[$Runner],$projectid,$Vname,$info[$Var_Names[$jay]],$Form_name[0],$vitalnotes, $vitalrater, $vitaldate, $vitalStdate, $vitaldesc);}

		} 
		
		
		if (($Form_name[0] == 'bphr' ||$Form_name[0] == 'computer_assessments_dose_day' || $Form_name[0] == 'blood_collection' || $Form_name[0] == 'quantisal_8b46' || $Form_name[0] == 'draeger' || $Form_name[0] == 'driving_impairment_questions' || $Form_name[0] == 'verbal_analog_scale')  &&  $subjectid[$Runner]!='' && $info[$Var_Names[1]]!='screening_day_arm_1')
                {
		
			if ($Form_name[0] == 'verbal_analog_scale')
                        {
                                $vitaldate = $info[$Var_Names[4]];
                                $vitalrater = $info[$Var_Names[5]];
                                $vitalnotes = $info[$Var_Names[6]];
				$vitaldesc  = $info[$Var_Names[14]]; // This is used for vas_post in redcap VAS table

                                $strt =7;

                          for ($jay=$strt;$jay < count($Var_Names)-1;$jay++){
                            if (strstr($Var_Names[$jay],'vas_time_')){
                                $Vtime = $info[$Var_Names[$jay]];
                                $jay = $jay + 1;}
				
			    if ($jay==13){$jay=$jay+2;}
                                $Vname = $Var_Names[$jay];
                                $vitalStdate = $vitaldate.' '.$Vtime;

/*                                echo $subjectid[$Runner];echo "<br>";
                                echo $vitaldate.' '.$Vtime;echo "<br>";
                                echo $Vname.':::'.$info[$Var_Names[$jay]];echo "<br>";}*/
				

                          Addvitals($subjectid[$Runner],$projectid,$Vname,$info[$Var_Names[$jay]],$Form_name[0],$vitalnotes, $vitalrater, $vitaldate, $vitalStdate, $vitaldesc);}
                        }

			

			if ($Form_name[0] == 'driving_impairment_questions')
                        {
                                $vitaldate = $info[$Var_Names[4]];
                                $vitalrater = $info[$Var_Names[5]];
                                $vitalnotes = $info[$Var_Names[6]];

                                $strt =9;

                          for ($jay=$strt;$jay < count($Var_Names)-1;$jay++){
                            if (strstr($Var_Names[$jay],'drivequestions_time')){
                                $Vtime = $info[$Var_Names[$jay]];
                                $jay = $jay + 1;}

                                $Vname = $Var_Names[$jay];
                                $vitalStdate = $vitaldate.' '.$Vtime;

 /*                               echo $subjectid[$Runner];echo "<br>";
                                echo $vitaldate.' '.$Vtime;echo "<br>";
                                echo $Vname.':::'.$info[$Var_Names[$jay]];echo "<br>";}*/

                          Addvitals($subjectid[$Runner],$projectid,$Vname,$info[$Var_Names[$jay]],$Form_name[0],$vitalnotes, $vitalrater, $vitaldate, $vitalStdate, $vitaldesc);}
                        }
	

			if ($Form_name[0] == 'draeger')
                        {
                                $vitaldate = $info[$Var_Names[4]];
                                $vitalrater = $info[$Var_Names[5]];
                                $vitalnotes = $info[$Var_Names[6]];

                                $strt =8;

                          for ($jay=$strt;$jay < count($Var_Names)-1;$jay++){
                            if (strstr($Var_Names[$jay],'time_base_drae') || strstr($Var_Names[$jay],'time_1_drae') || strstr($Var_Names[$jay],'time_2_drae') || strstr($Var_Names[$jay],'time_3_drae') || strstr($Var_Names[$jay],'time_4_drae') || strstr($Var_Names[$jay],'time_5_drae') || strstr($Var_Names[$jay],'time_6_drae') || strstr($Var_Names[$jay],'time_7_drae') || strstr($Var_Names[$jay],'time_8_drae')){
                                $Vtime = $info[$Var_Names[$jay]];
                                $jay = $jay + 1;}

                                $Vname = $Var_Names[$jay];
                                $vitalStdate = $vitaldate.' '.$Vtime;

/*                                echo $subjectid[$Runner];echo "<br>";
                                echo $vitaldate.' '.$Vtime;echo "<br>";
                                echo $Vname.':::'.$info[$Var_Names[$jay]];echo "<br>";}*/

                          Addvitals($subjectid[$Runner],$projectid,$Vname,$info[$Var_Names[$jay]],$Form_name[0],$vitalnotes, $vitalrater, $vitaldate, $vitalStdate, $vitaldesc);}
                        }


			if ($Form_name[0] == 'quantisal_8b46')
                        {
                                $vitaldate = $info[$Var_Names[4]];
                                $vitalrater = $info[$Var_Names[5]];
                                $vitalnotes = $info[$Var_Names[6]];

                                $strt =7;

                          for ($jay=$strt;$jay < count($Var_Names)-1;$jay++){
                            if (strstr($Var_Names[$jay],'quant_time_')){
                                $Vtime = $info[$Var_Names[$jay]];
                                $jay = $jay + 1;}

                                $Vname = $Var_Names[$jay];
                                $vitalStdate = $vitaldate.' '.$Vtime;

 /*                               echo $subjectid[$Runner];echo "<br>";
                                echo $vitaldate.' '.$Vtime;echo "<br>";
                                echo $Vname.':::'.$info[$Var_Names[$jay]];echo "<br>";}*/

                          Addvitals($subjectid[$Runner],$projectid,$Vname,$info[$Var_Names[$jay]],$Form_name[0],$vitalnotes, $vitalrater, $vitaldate, $vitalStdate, $vitaldesc);}
                        }


			if ($Form_name[0] == 'blood_collection')
                        {
                                $vitaldate = $info[$Var_Names[4]];
                                $vitalrater = $info[$Var_Names[5]];
				$vitalnotes = $info[$Var_Names[6]];

                                $strt = 8;

                          for ($jay=$strt;$jay < count($Var_Names)-1;$jay++){
                            if (strstr($Var_Names[$jay],'blood_time_')){
                                $Vtime = $info[$Var_Names[$jay]];
                                $jay = $jay + 1;}

                                $Vname = $Var_Names[$jay];
                                $vitalStdate = $vitaldate.' '.$Vtime;

/*                              echo $subjectid[$Runner];echo "<br>";
                                echo $vitaldate.' '.$Vtime;echo "<br>";
                                echo $Vname.':::'.$info[$Var_Names[$jay]];echo "<br>";}*/

                          Addvitals($subjectid[$Runner],$projectid,$Vname,$info[$Var_Names[$jay]],$Form_name[0],$vitalnotes, $vitalrater, $vitaldate, $vitalStdate,$vitaldesc);}
                        }


		
			if ($Form_name[0] == 'computer_assessments_dose_day' )
                        {
                                $vitaldate = $info[$Var_Names[4]];
                                $vitalrater = $info[$Var_Names[5]];
				$vitalnotes = $info[$Var_Names[6]];

                                $strt = 9;

                          for ($jay=$strt;$jay < count($Var_Names)-4;$jay++){
                            if ($jay==9 || $jay==16 || $jay==24 || $jay==33 || $jay==43 || $jay==50){
                                $Vtime = $info[$Var_Names[$jay]];
                                $jay = $jay + 1;}

                                $Vname = $Var_Names[$jay];
                                $vitalStdate = $vitaldate.' '.$Vtime;
/*
                              	echo $subjectid[$Runner];echo "<br>";
                                echo $vitaldate.' '.$Vtime;echo "<br>";
                                echo $Vname.':::'.$info[$Var_Names[$jay]];echo "<br>";*/

                          Addvitals($subjectid[$Runner],$projectid,$Vname,$info[$Var_Names[$jay]],$Form_name[0],$vitalnotes, $vitalrater, $vitaldate, $vitalStdate,$vitaldesc);

			    if ($jay==12){ $jay=15;}
                            if ($jay==19){ $jay=23;}
                            if ($jay==29){ $jay=32;}
                            if ($jay==38){ $jay=42;}
                            if ($jay==46){ $jay=49;}}
                        }
				
	
			if ($Form_name[0] == 'bphr' )
                        {
				$vitaldate = $info[$Var_Names[4]];
	                        $vitalrater = $info[$Var_Names[5]];
				
                                $strt = 8;
                       				
			  for ($jay=$strt;$jay < count($Var_Names)-1;$jay++){
			    if (strstr($Var_Names[$jay],'time')){	
				$Vtime = $info[$Var_Names[$jay]];
				$jay = $jay + 1;}

                                $Vname = $Var_Names[$jay];
				$vitalStdate = $vitaldate.' '.$Vtime;

/*				echo $subjectid[$Runner];echo "<br>";
				echo $vitaldate.' '.$Vtime;echo "<br>";
				echo $Vname.':::'.$info[$Var_Names[$jay]];echo "<br>";}*/

                          Addvitals($subjectid[$Runner],$projectid,$Vname,$info[$Var_Names[$jay]],$Form_name[0],$vitalnotes, $vitalrater, $vitaldate, $vitalStdate, $vitaldesc);}	
			}
		}
	}


?>


<?}
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


	/* -------------------------------------------- */
        /* ---- Transfering measures into ADO --------- */
        /* -------------------------------------------- */

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
                $sqlstring = "insert ignore into drugs (enrollment_id, drug_startdate, drug_enddate, drug_doseamount, drugname_id, drug_dosekey, drug_dosedesc, drug_rater, drug_notes, drug_entrydate, drug_recordcreatedate, drug_recordmodifydate) values ($enrollmentid, '$dosestdate', '$doseenddate','$dosedelivered','$dosenameid','$dosekey','$doseinhaled','$doserater','$dosenotes',now(),now(),now() ) on duplicate key update drug_dosedesc = '$doseinhaled', drug_recordmodifydate = now()";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);}
	   else { echo 'Subject '.$subjectid.' was not found in ADO';} 


	}


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

