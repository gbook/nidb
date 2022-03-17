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
		<title>NiDB- RedCap Import Users</title>
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
	$redcapevent = GetVariable("redcapevent");
	$redcapaltid = GetVariable("redcapaltid");
	$redcaprid = GetVariable("redcaprid");
	$redcapfields = GetVariable("redcapfields");
	$redcapfname = GetVariable("redcapfname");
	$redcaplname = GetVariable("redcaplname");
	$redcapdob = GetVariable("redcapdob");
	$redcapgender = GetVariable("redcapgender");
	$redcapgenderm = GetVariable("redcapgenderm");
	$redcapgenderf = GetVariable("redcapgenderf");
	$redcapgendero = GetVariable("redcapgendero");
	$redcapgenderu = GetVariable("redcapgenderu");
	$rcsexcode = GetVariable("rcsexcode");
	$addsub = GetVariable("addsub");


 switch ($action) 
{
		case 'showrcinfo':
			getredcapinfo($projectid);
			$redcapfields = array($redcaprid,$redcapfname,$redcaplname,$redcapdob,$redcapgender,$redcapaltid);
			$rcsexcode = array($redcapgenderm,$redcapgenderf,$redcapgendero,$redcapgenderu);
			getrcsubjectinfo($projectid,$redcapfields,$redcapurl,$redcaptoken,$redcapevent,$rcsexcode);
			break;
		case 'addsubjs':
			//	echo var_dump($redcaplname)."<br>";
			createsubjects($addsub,$redcaprid,$redcapaltid,$redcapfname,$redcaplname,$redcapdob,$redcapgender,$projectid);
                        getredcapinfo($projectid);
			break;
		default:
			getredcapinfo($projectid);
			break;
}


/* -----------------getredcapinfo---------------*/

function getredcapinfo($projectid)
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


	 <div class="ui grid container">
		<form  action="redcapimportsubjects.php" method="post">
		<input type="hidden" name="action" value="showrcinfo">
		<input type="hidden" name="redcapurl" value="<?=$redcapurl?>">
		<input type="hidden" name="redcaptoken" value="<?=$redcaptoken?>">
		<input type="hidden" name="redcapevent" value="<?=$redcapevent?>">
		<input type="hidden" name="projectid" value="<?=$projectid?>">
		<input type="hidden" name="redcapfields" value="<?=$redcapfields?>">

		<h2 class="ui top attached inverted header" align="center"> Importing Subjects from Redcap </h2>

		<table class="ui basic bottom attached compact collapsing  celled table">
			
			<div class="four wide column">
			     <div class="ui fluid labeled input">
                                                        <div class="ui label">
                                                        Project
                                                        </div>
                                                        <select name="projectid" class="ui search dropdown" required>
                                                                <option value="">Select Project...</option>
                                                                <?
                                                                        $sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '" . $_SESSION['username'] . "') and a.instance_id = '" . $_SESSION['instanceid'] . "' order by project_name";

                                                                        $result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
                                                                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                                                                $project_id = $row['project_id'];
                                                                                $project_name = $row['project_name'];
                                                                                $project_costcenter = $row['project_costcenter'];
                                                                                if ($projectid == $project_id)
                                                                                        $selected = "selected";
                                                                                else
                                                                                        $selected = "";
                                                                                ?>
                                                                                <option value="<?=$project_id?>" <?=$selected?>><?=$project_name?> (<?=$project_costcenter?>)</option>
                                                                                <?
                                                                        }
                                                                ?>
                                                        </select>
			  </div>
		</div>


		<div class="four wide column">
			<div class="ui labeled input">
			  <div class="ui label">
			    *Redcap Server
			  </div>
			  <input type="text"  name="redcapurl" value="<?=$redcapurl?>"  required>
			</div>

			<br>
			 <div class="ui labeled input">
                          <div class="ui label">
                            *Redcap Token
                          </div>
				<input type="text" name="redcaptoken" value="<?=$redcaptoken?>" required>
			</div>

			 <br>
                         <div class="ui labeled input">
                          <div class="ui label">
                            *Redcap Event
                          </div>
                                <input type="text" name="redcapevent" value="<?=$redcapevent?>" required>
                        </div>

		</div>


		<h3 class="ui block inverted header"> Redcap Field Names </h3>

		  <div class="four wide column">	
		      <div class="ui labeled input">
                         <div class="ui label">
                            *Record ID
                          </div>

                                  <input type="text"  name="redcaprid" value="<?=$redcaprid?>" required>
                        </div>

			 <br>

			 <div class="ui labeled input">
                         <div class="ui label">
                            Alternate ID
                          </div>

                                  <input type="text"  name="redcapaltid" value="<?=$redcapaltid?>" >
                        </div>

			 <br>

                        <div class="ui labeled input">
                          <div class="ui label">
                           *First Name
                          </div>

                                  <input type="text"  name="redcapfname" value="<?=$redcapfname?>" required>
                        </div>

			<br>

                        <div class="ui labeled input">
                          <div class="ui label">
                           *Last Name
                          </div>

                                  <input type="text"  name="redcaplname" value="<?=$redcaplname?>" required>
                        </div>			

			 <br>

                        <div class="ui labeled input">
                          <div class="ui label">
                           *Birthdate
                          </div>

                                  <input type="text"  name="redcapdob" value="<?=$redcapdob?>" required>
                        </div>
			

			 <br>

                        <div class="ui labeled input">
                          <div class="ui label">
                           *Sex
                          </div>

				  <input type="text"  name="redcapgender" value="<?=$redcapgender?>" required>
			</div>
			

		     <div class="ui form">
			<div class="fields">
			  <div class="one wide field">
			      <label align="center" >*M</label>
			      <input type="text" placeholder="1" name="redcapgenderm" value="<?=$redcapgenderm?>" required>
			  </div>
			  <div class="one wide field">
			      <label align="center" >*F</label>
			      <input type="text" placeholder="2" name="redcapgenderf" value="<?=$redcapgenderf?>" required>
			  </div>
			  <div class="one wide field">
			      <label align="center" >O</label>
			      <input type="text" placeholder="3" name="redcapgendero" value="<?=$redcapgendero?>">
			  </div>
			   <div class="one wide field">
			      <label align="center" >U</label>
                              <input type="text" placeholder="4" name="redcapgenderu" value="<?=$redcapgenderu?>">
			  </div>
			 <label>Provide Sex Data Coding</label>
			</div>
		    </div>

		</div>

		<br><br>


                        <button class="fluid ui button" type="submit">
			  <i class="users icon"></i> 
		          Subjects Information
		       </button>

		</table>
		</form>

	</div>

	<br>


<?}



/* --------------------MapRCSubjectInfo--------------*/

function getrcsubjectinfo($projectid,$redcapfields,$redcapurl,$redcaptoken,$redcapevent,$rcsexcode)
{

//	populating $rcsexcode if did not define by user
	if ($rcsexcode[0]==""){$rcsexcode[0]=1;}
	if ($rcsexcode[1]==""){$rcsexcode[1]=2;}
	if ($rcsexcode[2]==""){$rcsexcode[2]=3;}
	if ($rcsexcode[3]==""){$rcsexcode[3]=4;}
	


	$namedata=getrcnames($redcapfields,$redcapurl,$redcaptoken,$redcapevent);

//	var_dump($namedata);
//	echo $namedata[0]["redcap_event_name"]; 
	if ($namedata["error"]!=""){ 	
		?><div align="center"><span class="message"><?=$namedata["error"]?></span></div><br><br><?}
	
	else {
?>

<table class="ui very basic collapsing celled table" align="right">
<tbody>
        <tr class="green"><td class="center aligned">Ready to Import</td></tr>
        <tr class="blue"><td  class="center aligned">Processing</td></tr>
        <tr class="grey"><td  class="center aligned">Already Exist in NiDB</td></tr>

</tbody>
</table>

<form action="redcapimportsubjects.php" method="post">
<input type="hidden" name="action" value="addsubjs">
<input type="hidden" name="projectid" value="<?=$projectid?>">


<table class="ui compact celled definition table">
  <thead>
    <tr>
      <th></th>
      <th>ID</th>
      <th>First Name</th>
      <th>Last Name</th>
      <th>Birthdate</th>
      <th>Sex</th>
      <th>Alternate ID</th>
     </tr>
  </thead>
  <tbody>

<? foreach ($namedata as $block => $info){
	

	$flg=checksubjects($info[$redcapfields[1]],$info[$redcapfields[2]],$info[$redcapfields[3]]);
      if ($flg[0]!="") {
	      $clr='grey';}
      elseif ($flg[1]!=""){
      	      $clr='blue';}	      
           else {
                $clr='green';}
        ?>
	
    <tr class="<?=$clr?>">
      <td class="ui collapsing">
        <div class="ui toggle checkbox">
	   <? if ($flg[0]!="") {
		?> <input type="checkbox" name="addsub[]" value="<?=$info[$redcapfields[0]]?>"> <label></label><?}
	      elseif ($flg[1]!="") {
                ?> <input type="checkbox"  name="addsub[]"  value="<?=$info[$redcapfields[0]]?>" disabled> <label></label><?}
              else {
                ?> <input type="checkbox"  name="addsub[]"  value="<?=$info[$redcapfields[0]]?>" checked> <label></label><?}
        ?>
        </div>
      </td>

	<? for($Fd=0;$Fd < count($redcapfields); $Fd++){

		$valfield=$info[$redcapfields[$Fd]];

	    if ($Fd==4){
		    $sexfield=$valfield;
		    switch ($sexfield)
		    { 
		      case $rcsexcode[0]:
		    	$valfield = 'M';
		    	break;
		      case $rcsexcode[1]:
                        $valfield = 'F';
			break;
		      case $rcsexcode[2]:
                        $valfield = 'O';
			break;
		      case $rcsexcode[3]:
                        $valfield = 'U';
			break;
		  }}
	   else {	$valfield=$info[$redcapfields[$Fd]];}

	
	?>
	

	
      <td><?=$valfield?>

<?      if ($Fd==0) {?>
                <input type="hidden" name="redcaprid[]" value="<?=$valfield?>">
<?}     if ($Fd==1) {?>
                <input type="hidden" name="redcapfname[]" value="<?=$valfield?>">
<?}     if ($Fd==2) {?>
                <input type="hidden" name="redcaplname[]" value="<?=$valfield?>">
<?}     if ($Fd==3) {?>
                <input type="hidden" name="redcapdob[]" value="<?=$valfield?>">
<?}     if ($Fd==4) {?>
		<input type="hidden" name="redcapgender[]" value="<?=$valfield?>">
<?}     if ($Fd==5) {?>
                <input type="hidden" name="redcapaltid[]" value="<?=$valfield?>">
<?}?>
	

      </td>
	<?}}?>
    </tr>

  </tbody>

</table>

	<button class="big ui primary right floated button" type="submit">
                          <i class="people arrows icon"></i>
                          Import Selected Subjects
       </button>	


</form>

<?}


}



	/* -------------------------------------------- */
        /* ------- getrcnames ----------------------- */
        /* -------------------------------------------- */
        function getrcnames($RCFields,$RCserver,$RCtoken,$RCEvent) {

// 	For the projects with no redcap event catching NA from user

	if (strtoupper($RCEvent=='NA') || strtoupper($RCEvent)=='N/A'){$RCEvent="";}

                $data = array(
                        'token' => $RCtoken,
                        'content' => 'record',
                        'format' => 'json',
                        'type' => 'flat',
			'fields' => $RCFields,
			'events' => $RCEvent,
                        'rawOrLabel' => 'raw',
                        'rawOrLabelHeaders' => 'raw',
                        'exportCheckboxLabel' => 'false',
                        'exportSurveyFields' => 'false',
                        'exportDataAccessGroups' => 'false',
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
                //print $output;
                curl_close($ch);

                $report = json_decode($output,true);

                //$Var_Names = array_keys($report[0]); /* This variable ($Var_Names)contains names of all the variables in selected form */

                return $report;

        }

	/*----------------------------------------------------*/
	/*---------------checksubjects-------------------------*/
	/*----------------------------------------------------*/

	function checksubjects($sfname,$slname,$sdob){

		 if (($sfname == "") || ($sdob =="") || ($slname == "")) {
//                        print 'Provide the name and birthdate';
                        return;
                }


		// Getting subjectID based on name and birthdata
                $sqlstring = "SELECT subject_id FROM `subjects` WHERE (name ='$slname $sfname'  or name='$slname^$sfname') and birthdate='$sdob'";
                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $subjectid = $row['subject_id'];
		
		// Getting temsubjectID based on name and birthdate from subject import pending
                $sqlstring1 = "SELECT temp_sid FROM `subjectsimport_pending` WHERE (name ='$slname $sfname'  or name='$slname^$sfname') and birthdate='$sdob'";
                $result1 = MySQLiQuery($sqlstring1, __FILE__, __LINE__);
                $row = mysqli_fetch_array($result1, MYSQLI_ASSOC);
                $tempsid = $row['temp_sid'];		



		return array($subjectid,$tempsid);              
		
	}

	/*----------------------------------------------------*/
        /*---------------createsubjects-------------------------*/
        /*----------------------------------------------------*/

	function createsubjects($addsub,$redcaprid,$redcapaltid,$redcapfname,$redcaplname,$redcapdob,$redcapgender,$projectid){
		
		if (count($addsub)==0){
			?><div align="center"><span class="message">No Subject to Create</span></div><br><br><?
			return;}

		foreach ($addsub as $val) {
			
			$idx=array_search($val,$redcaprid);

			$rcid= $redcaprid[$idx];
			$altid= $redcapaltid[$idx];
			$rcname=$redcaplname[$idx]." ".$redcapfname[$idx];
			$rcdob=date($redcapdob[$idx]);
			$rcsex=$redcapgender[$idx];


			$sqlstring =  $sqlstring = "insert ignore into subjectsimport_pending (redcapid,altuid,project_id,name,birthdate,gender) values ($rcid,'$altid',$projectid,'$rcname','$rcdob','$rcsex') on duplicate key update redcapid=$rcid,altuid='$altid',project_id=$projectid,name='$rcname',birthdate='$rcdob',gender='$rcsex'";


	                $result = MySQLiQuery($sqlstring, __FILE__, __LINE__);


		}

		?><div align="center"><span class="message">Request Sent to Create Subjects</span></div><br><br><?				

		return;


	}


?>

