<?
 //A SIM Created This FILE ------------------------------------------------------------------------------
 // NiDB projectassessments.php
 // Copyright (C) 2004 - 2025
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
		<title>NiDB - Project Assessment Forms</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$projectid = GetVariable("projectid");
	$formid = GetVariable("formid");
	$formtitle = GetVariable("formtitle");
	$formdesc = GetVariable("formdesc");

	$datatype = GetVariable("datatype");
	$field = GetVariable("field");
	$order = GetVariable("order");
	$values = GetVariable("values");
	$linebreaks = GetVariable("linebreaks");
	$scored = GetVariable("scored");
	
	/* determine action */
	switch ($action) {
		case 'editform': 
			DisplayFormForm("edit",$projectid, $formid);
			break;
		case 'viewform':
			DisplayForm($projectid,$formid); 
			break;
		case 'addform':
			DisplayNlinkForms($projectid);
			break;
		case 'updatefields':
			UpdateFields($formid, $datatype, $field, $order, $values, $linebreaks, $scored);
			DisplayFormForm("edit", $projectid, $formid);
			break;
		case 'update':
			UpdateForm($projectid, $formid, $formtitle, $formdesc, $username);
			DisplayFormList($projectid);
			break;
		case 'add':
			AddForm($projectid, $formtitle, $formdesc, $username);
			DisplayFormList($projectid);
			break;
		case 'delete':
			DeleteForm($projectid,$formid);
			DisplayFormList($projectid);
			break;
		case 'publish':
			PublishForm($formid,$projectid);
			DisplayFormList($projectid);
			break;
		case 'link':

			LinkForms($projectid,$_POST[Link]);
			DisplayFormList($projectid);
			break;
		default:
			DisplayFormList($projectid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
        /* ------- LinkForms ------------------------ */
        /* -------------------------------------------- */
	function LinkForms($projectid,$Link) {

		/* Linking the requested form/s to the project */
		for($i=0; $i<count($Link); $i++) {	
//			print_r($Link[$i]); }

			 $sqlstring = "insert into assessment_forms (`form_id`,`project_id`,`form_title`,`form_desc`,`form_creator`,`form_createdate`,`form_ispublished`,`lastupdate`) values ($Link[$i],$projectid, (select  distinct `form_title` from (select * from assessment_forms) as X WHERE form_id = $Link[$i]),(select  distinct `form_desc` from (select * from assessment_forms) as X WHERE form_id = $Link[$i]),(select  distinct `form_creator` from (select * from assessment_forms) as X WHERE form_id = $Link[$i]),(select  distinct `form_createdate` from (select * from assessment_forms) as X WHERE form_id = $Link[$i]),0 ,(select  max(`lastupdate`) from (select * from assessment_forms) as X WHERE form_id = $Link[$i]))";

      	          $result = MySQLiQuery($sqlstring, __FILE__, __LINE__); }

        	  ?><div align="center"><span class="message"><?=$formtitle?> Copied </span></div><br><br><?
	
	}



	/* -------------------------------------------- */
	/* ------- UpdateForm ------------------------ */
	/* -------------------------------------------- */
	function UpdateForm($projectid, $formid, $formtitle, $formdesc, $username) {
		/* perform data checks */
		$formtitle = mysqli_real_escape_string($GLOBALS['linki'], $formtitle);
		$formdesc = mysqli_real_escape_string($GLOBALS['linki'], $formdesc);
		$username = mysqli_real_escape_string($GLOBALS['linki'], $username);
		
		/* update the form */
		$sqlstring = "update assessment_forms set form_title = '$formtitle', form_desc = '$formdesc' where form_id = $formid and project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		

		?><div align="center"><span class="message"><?=$formtitle?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddForm ---------------------------- */
	/* -------------------------------------------- */
	function AddForm($projectid, $formtitle, $formdesc, $username) {
	
#		PrintVariable($projectid);
		/* perform data checks */
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);
		$formtitle = mysqli_real_escape_string($GLOBALS['linki'], $formtitle);
		$formdesc = mysqli_real_escape_string($GLOBALS['linki'], $formdesc);
		$username = mysqli_real_escape_string($GLOBALS['linki'], $username);
		
		/* insert the new form */
		$sqlstring = "insert into assessment_forms (project_id, form_title, form_desc, form_creator, form_createdate) values ('$projectid', '$formtitle', '$formdesc', '$username', now())";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		?><div align="center"><span class="message"><?=$formtitle?> added</span></div><br><br><?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateFields ----------------------- */
	/* -------------------------------------------- */
	function UpdateFields($formid, $datatype, $field, $mrder, $values, $linebreaks, $scored) {
		/* perform data checks */
		
		/* check to see if any data has been entered for this form */
		
		/* delete all previous assessment_formfields */
		$sqlstring = "delete from assessment_formfields where form_id = $formid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* insert all the new fields */
		for($i=1; $i<=count($datatype); $i++) {
			if (trim($field[$i]) != "") {
				$field[$i] = mysqli_real_escape_string($GLOBALS['linki'], $field[$i]);
				$values[$i] = mysqli_real_escape_string($GLOBALS['linki'], $values[$i]);
				$order[$i] = mysqli_real_escape_string($GLOBALS['linki'], $order[$i]);
		

				/* If $linebreak and $scored are NOT checked Asim*/
					
				if ($linebreaks[$i]!=1){ $linebreaks[$i]="0"; }
				if ($scored[$i]!=1){ $scored[$i]="0";}	

				$sqlstring = "insert into assessment_formfields (form_id, formfield_desc, formfield_values, formfield_datatype, formfield_haslinebreak, formfield_scored, formfield_order) values ($formid, '$field[$i]', '$values[$i]', '$datatype[$i]', '$linebreaks[$i]', '$scored[$i]', '$order[$i]')";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			}
		}
		
		?><div align="center"><span class="message"><?=$formtitle?> updated</span></div><br><br><?
	}
	

	/* -------------------------------------------- */
	/* ------- DeleteForm ------------------------- */
	/* -------------------------------------------- */
	function DeleteForm($formid) {
		$sqlstring = "delete from assessment_forms where form_id = $formid and project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?><div align="center"><span class="message"><?=$formid?> deleted</span></div><br><br><?
	}	


	/* -------------------------------------------- */
	/* ------- PublishForm ------------------------ */
	/* -------------------------------------------- */
	function PublishForm($formid,$projectid) {
		$sqlstring = "update assessment_forms set form_ispublished = 1 where form_id = $formid and project_id =$projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		?><div align="center"><span class="message"><?=$formid?> published</span></div><br><br><?
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayFormForm -------------------- */
	/* -------------------------------------------- */
	function DisplayFormForm($type, $projectid, $formid) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select a.*,b.project_name from assessment_forms a left join projects b on a.project_id = b.project_id where a.form_id = $formid and b.project_id = $projectid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$title = $row['form_title'];
			$desc = $row['form_desc'];
			$projectname = $row['project_name'];

		
			$formaction = "update";
			$formtitle = "Updating $formname";
			$submitbuttonlabel = "Update";
		}
		elseif ($type == "add") {
			$sqlstring = "select project_name from  projects where project_id = $projectid";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$title = $row['form_title'];
			$desc = $row['form_desc'];
			$projectname = $row['project_name'];


			$formaction = "add";
			$formtitle = "Create a new assessment form";
			$submitbuttonlabel = "Add";
		}
	?>
	
		<div align="center">
		<table class="entrytable">
			<form method="post" action="projectassessments.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="formid" value="<?=$formid?>">
			<input type="hidden" name="projectid" value="<?=$projectid?>">

			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Title</td>
				<td><input type="text" name="formtitle" value="<?=$title?>" required></td> <td align = "right"><font color="red">Required Field</font></td>
			</tr>
			<tr>
				<td>Description</td>
				<td><input type="text" name="formdesc" value="<?=$desc?>" size="50" required></td> <td align = "right"><font color="red">Required Field</font></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" value="<?=$submitbuttonlabel?>">
				</td>
			</tr>
			</form>
		</table>
		<?
		if ($type == "edit") {
		?>
		<br><br>
		<table class="entrytable">
			<thead>
				<tr>
					<th>Order</th>
					<th>Field description</th>
					<th>Data type</th>
					<th>Values<br><span class="tiny">Comma separated list</span></th>
					<th>Use line breaks<br><span class="tiny">Single-choice list</span></th>
					<th>Scored</th>
				</tr>
			</thead>
			<tbody>
				<form method="post" action="projectassessments.php">
				<input type="hidden" name="action" value="updatefields">
				<input type="hidden" name="formid" value="<?=$formid?>">
				<input type="hidden" name="projectid" value="<?=$projectid?>">
				<?
					$neworder = 1;
					/* display all other rows, sorted by order */
					$sqlstring = "select * from assessment_formfields where form_id = $formid order by formfield_order + 0";
					$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
					while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
						$formfield_id = $row['formfield_id'];
						$formfield_desc = $row['formfield_desc'];
						$formfield_datatype = $row['formfield_datatype'];
						$formfield_order = $row['formfield_order'];
						$formfield_values = $row['formfield_values'];
						$formfield_scored = $row['formfield_scored'];
						$formfield_haslinebreak = $row['formfield_haslinebreak'];
						?>
						<tr>
							<td><input type="text" name="order[<?=$neworder?>]" size="2" maxlength="5" value="<?=$neworder?>"></td>
							<td><input type="text" name="field[<?=$neworder?>]" size="50" value="<?=$formfield_desc?>"></td>
							<td>
							<select name="datatype[<?=$neworder?>]">
								<option value="string" <? if ($formfield_datatype == "string") { echo "selected"; } ?>>String</option>
								<option value="number" <? if ($formfield_datatype == "number") { echo "selected"; } ?>>Number</option>
								<option value="multichoice" <? if ($formfield_datatype == "multichoice") { echo "selected"; } ?>>Multi-choice</option>
								<option value="singlechoice" <? if ($formfield_datatype == "singlechoice") { echo "selected"; } ?>>Single-choice</option>
								<option value="text" <? if ($formfield_datatype == "text") { echo "selected"; } ?>>Text</option>
								<option value="date" <? if ($formfield_datatype == "date") { echo "selected"; } ?>>Date</option>
								<option value="binary" <? if ($formfield_datatype == "binary") { echo "selected"; } ?>>File/image</option>
								<option value="header" <? if ($formfield_datatype == "header") { echo "selected"; } ?>>Section header</option>
							</select>
							</td>
							<td><input type="text" name="values[<?=$neworder?>]" size="40" value="<?=$formfield_values?>"></td>
							<td><input type="checkbox" name="linebreaks[<?=$neworder?>]" <? if ($formfield_haslinebreak) {echo "checked";} ?> value="1"></td>
							<td><input type="checkbox" name="scored[<?=$neworder?>]" <? if ($formfield_scored) {echo "checked";} ?> value="1"></td>
						</tr>
						<?
						$neworder++;
					}
					
				for ($i=0;$i<5;$i++) {
				?>
				<tr>
					<td><input type="text" name="order[<?=$neworder?>]" size="2" maxlength="5" value="<?=$neworder?>"></td>
					<td><input type="text" name="field[<?=$neworder?>]" size="50"></td>
					<td>
					<select name="datatype[<?=$neworder?>]">
						<option value="string">String</option>
						<option value="number">Number</option>
						<option value="multichoice">Multi-choice</option>
						<option value="singlechoice">Single-choice</option>
						<option value="text">Text</option>
						<option value="date">Date</option>
						<option value="binary">File/image</option>
						<option value="header">Section header</option>
					</select>
					</td>
					<td><input type="text" name="values[<?=$neworder?>]" size="40"></td>
					<td><input type="checkbox" name="linebreaks[<?=$neworder?>]" value="1"></td>
					<td><input type="checkbox" name="scored[<?=$neworder?>]" value="1"></td>
					<? $neworder++; ?>
				</tr>
				<? } ?>
				<tr>
					<td colspan="3"><input type="submit" value="Add/update all"></td>
				</tr>
				</form>
			</tbody>
		</table>

		 <div align="right">
                 <button><h3> <b> <a class="ui button" href="projectassessments.php?action=viewform&formid=<?=$formid?>&projectid=<?=$projectid?>"> Preview Form </a></b></h3></button>

		 <div align="center">	
		<br><br>
		<div style="border: 1px solid #DDDDDD; padding: 10px">
			<a href="projectassessments.php?formid=<?=$formid?>&projectid=<?=$projectid?>&action=publish" style="color:darkred; font-weight: bold; font-size: 14pt; background-color: #FFDDDD; padding: 3px">Publish</a>
			<br><br>
			<span style="color: #666666; font-size:15pt">This cannot be undone. Once it is published, people may start using it.</span>
		</div>
		<?
		}
		?>
		
		</div>
	<?
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayForm ------------------------ */
	/* -------------------------------------------- */
	function DisplayForm($projectid,$formid) {

//		PrintVariable($formid);
	
		$sqlstring = "select distinct a.*,b.project_name from assessment_forms a left join projects b on a.project_id = b.project_id where a.form_id = $formid AND b.project_id = $projectid";

		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$title = $row['form_title'];
		$desc = $row['form_desc'];
		$projectname = $row['project_name'];
		$formpublish = $row['form_ispublished'];
		
		// Counting query
//		$sqlstr ="SELECT COUNT(*) as CNT FROM `subjects` a left join enrollment b on a.subject_id = b.subject_id WHERE b.project_id=$projectid ";
		$sqlstr ="SELECT count(*) as CNT from assessments a left join enrollment b on a.enrollment_id=b.enrollment_id where a.form_id=$formid and b.project_id=$projectid ";
		$res= MySQLiQuery($sqlstr, __FILE__, __LINE__);
		$row = mysqli_fetch_array($res, MYSQLI_ASSOC);
                $Cnt = $row['CNT'];
		
		
	?>
		<h4>Form Preview </h4>
		<div align="center">

		<table class="formentrytable">
			<tr>
				<td  class="title" colspan="2"><?=$title?></td>
			</tr>
			<tr>
				<td  class="desc" colspan="2"><?=$desc?></td>
			</tr>
			<tr>
                                <td style="font-size:12pt; color: blue">Title</td>
                                <td style="font-size:12pt; color: blue">Field</td>
                        </tr>
			<?
				/* display all other rows, sorted by order */
				$sqlstring = "select * from assessment_formfields where form_id = $formid order by formfield_order + 0";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$formfield_id = $row['formfield_id'];
					$formfield_desc = $row['formfield_desc'];
					$formfield_values = $row['formfield_values'];
					$formfield_datatype = $row['formfield_datatype'];
					$formfield_order = $row['formfield_order'];
					$formfield_scored = $row['formfield_scored'];
					$formfield_haslinebreak = $row['formfield_haslinebreak'];
					
					?>
					<tr>
						<? if ($formfield_datatype == "header") { ?>
							<td colspan="2" class="sectionheader"><?=$formfield_desc?></td>
						<? } else { ?>
							<td class="field"><?=$formfield_desc?></td>
							<td class="value">
							<?
								switch ($formfield_datatype) {
									case "binary": ?><input type="file" name="value[]"><? break;
									case "multichoice": ?>
										<select multiple name="<?=$formfield_id?>-multichoice" style="height: 150px">
											<?
												$values = explode(",", $formfield_values);
												natsort($values);
												foreach ($values as $value) {
													$value = trim($value);
												?>
													<option value="<?=$value?>"><?=$value?></option>
												<?
												}
											?>
										</select>
										<br>
										<span class="tiny">Hold <b>Ctrl</b>+click to select multiple items</span>
									<? break;
									case "singlechoice": ?>
											<?
												$values = explode(",", $formfield_values);
												//natsort($values);
												foreach ($values as $value) {
													$value = trim($value);
												?>
													<input type="radio"  name="<?=$formfield_id?>-singlechoice" value="<?=$value?>"><?=$value?>
												<?
													if ($formfield_haslinebreak) { echo "<br>"; } else { echo "&nbsp;"; }
												}
											?>
									<? break;
									case "date": ?><input type="date" name="<?=$formfield_id?>-date"><? break;
									case "number": ?><input type="number" name="<?=$formfield_id?>-number"><? break;
									case "string": ?><input type="text" name="<?=$formfield_id?>-string"><? break;
									case "text": ?><textarea name="<?=$formfield_id?>-text"></textarea><? break;
								}
							?>
						<? } ?>
					</tr>
					<?
				}
			?>
		</table>
		
		<div align="left"> 
		<h4>Number of current assessments stored in <?=$title?>=   <?=$Cnt?> </h4>
		<? if (($formpublish==0) || ($Cnt==0)) { ?>
			<br>
	 		<div align="right">
                	<button disabled ><h3> <b> <a class="ui button" href="projectassessments.php?action=editform&formid=<?=$formid?>&projectid=<?=$projectid?>"> Edit Form </a></b></h3></button>
		<? } else  {?>
			<br><br><br>
			<div align="center">
			<font size="4" color="red">Contact system administrator if you want to edit this form.</font>
		<? } ?>
	
		</div>
	<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayFormList -------------------- */
	/* -------------------------------------------- */
	function DisplayFormList($projectid) {

		
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);

		if (($projectid == '') || ($projectid == 0)) {
			Error("Project ID blank");
			return;
		}
		
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
		$usecustomid = $row['project_usecustomid'];
	
		/* get the main checklist items */
		$i = 0;
		$sqlstring = "select * from assessment_forms where project_id = $projectid order by form_id asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$form[$i]['id'] = $row['form_id'];
			$form[$i]['title'] = $row['form_title'];
			$form[$i]['desc'] = $row['form_desc'];
			$form[$i]['createdate'] = $row['form_createdate'];
			$form[$i]['published'] = $row['form_published'];
			$i++;
		}
	?>
	
	<div align="center">
	<h2> <b> Current Assessment Forms  </a>  </h2> </b> 

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Title</th>
				<th>Description</th>
				<th>Create Date</th>
				<th>Published</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select a.*, b.username 'creatorusername', b.user_fullname 'creatorfullname' from assessment_forms a left join users b on a.form_creator = b.user_id where a.project_id = $projectid order by a.form_title";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$formid = $row['form_id'];
					$title = $row['form_title'];
					$desc = $row['form_desc'];
					$creatorusername = $row['creatorusername'];
					$createdate = $row['form_createdate'];
					$ispublished = $row['form_ispublished'];
			?>
			<tr>
				<td>
					<? if ($ispublished) { ?>
					<a href="projectassessments.php?action=viewform&formid=<?=$formid?>&projectid=<?=$projectid?>"><?=$title?></a>
					<? } else { ?>
					<a href="projectassessments.php?action=editform&formid=<?=$formid?>&projectid=<?=$projectid?>"><?=$title?></a>
					<? } ?>
				</td>
				<td><?=$desc?></td>
				<td><?=$createdate?></td>
				<td align="center"><? if ($ispublished) { echo "&#10004;"; } ?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<br>
	<div align="center">
	<h3> <b> <a href="projectassessments.php?action=addform&projectid=<?=$projectid?>"> Add Form </a></b></h3>
	<?
	}


	/* -------------------------------------------- */
	/* ---------- DisplayNlinkForms --------------- */
	/* -------------------------------------------- */
	function DisplayNlinkForms($projectid) {

		
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);

		if (($projectid == '') || ($projectid == 0)) {
			Error("Project ID blank");
			return;
		}
		
		$sqlstring = "select * from projects where project_id = $projectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$projectname = $row['project_name'];
		$usecustomid = $row['project_usecustomid'];
	
		/* get the main checklist items */
		$i = 0;
		$sqlstring = "select distinct * from assessment_forms where form_id not in (select form_id from  assessment_forms where project_id = $projectid) order by form_id asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$form[$i]['id'] = $row['form_id'];
			$form[$i]['title'] = $row['form_title'];
			$form[$i]['desc'] = $row['form_desc'];
			$form[$i]['creator'] = $row['form_creator'];
			$form[$i]['createdate'] = $row['form_createdate'];
			$form[$i]['published'] = $row['form_published'];
			$i++;
		}
		
//PrintVariable($projectid);
		
	 DisplayFormForm("add",$projectid, "");
	
	?>

	
	<div align="center">
	<br><br> <h2>  <b> Forms available to Copy </b> </h2> <br><br>	

	


	

	<table class="formentrytable">

		<thead>
			<tr>
				<th align="left">Form Title  <font><?=" -   "?> </font>  Project Name</th>
				<th align="left">Description</th>
				<th>Form Id</th>
				<th align="left">Create Date</th>
				<th>Published  </th>
				<th>Copy</th>
			</tr>
		</thead>
		<tbody>

 			<form method="post" action="projectassessments.php">
                        <input type="hidden" name="action" value="link">
                        <input type="hidden" name="formid" value="<?=$formid?>">
                        <input type="hidden" name="projectid" value="<?=$projectid?>">
			

			<?
				$sqlstring = "select  a.form_id, a.form_title, a.form_desc,a.form_createdate,  a.form_ispublished,a.project_id, b.username 'creatorusername', b.user_fullname 'creatorfullname' from assessment_forms a left join users b on a.form_creator = b.user_id where a.form_id not in (select form_id from assessment_forms where project_id = $projectid) order by a.form_title";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$formid = $row['form_id'];
//					PrintVariable($formid);
					$title = $row['form_title'];
					$desc = $row['form_desc'];
					$creatorusername = $row['creatorusername'];
					$creatorfullname = $row['creatorfullname'];
					$createdate = $row['form_createdate'];
					$ispublished = $row['form_ispublished'];
					$projectid = $row['project_id'];
					$sqlstr = "select project_name from projects where project_id = $projectid";
		                	$res = MySQLiQuery($sqlstr, __FILE__, __LINE__);
               				$row = mysqli_fetch_array($res, MYSQLI_ASSOC);
			                $projname = $row['project_name'];
			?>
			<tr>
			 	<td><a href="projectassessments.php?action=viewform&formid=<?=$formid?>&projectid=<?=$projectid?>"><?=$title?><font><?=" -   "?> </font><b><?=$projname?></b></a></td>
				<td align=""><?=$desc?></td>	
				<td align="center"><?=$formid?></td>
				<td><?=$createdate?></td>
				<td align="center"><? if ($ispublished) { echo "&#10004;"; } ?></td>
				<td align="center"><input type="checkbox" name="Link[]"  value=<?=$formid?>></td>

			</tr>


			<? 
				}
			?>

			
			 <tr>
                           <td colspan="7" align="right"><input type="submit" value="Copy Selected Forms"></td>
                        </tr>

			</form>


		</tbody>
	</table>


	<?
	}







?>


<? include("footer.php") ?>
