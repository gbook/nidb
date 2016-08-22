<?
 // ------------------------------------------------------------------------------
 // NiDB adminforms.php
 // Copyright (C) 2004 - 2016
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
		<title>NiDB - Manage Forms</title>
	</head>

<body>
	<div id="wrapper">
<?
	//require "config.php";
	require "functions.php";
	require "includes.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$formtitle = GetVariable("formtitle");
	$formdesc = GetVariable("formdesc");

	//$formfieldid = GetVariable("formfieldid");
	$datatype = GetVariable("datatype");
	$field = GetVariable("field");
	$order = GetVariable("order");
	$values = GetVariable("values");
	$linebreaks = GetVariable("linebreaks");
	$scored = GetVariable("scored");
	
	
	/* determine action */
	switch ($action) {
		case 'editform': DisplayFormForm("edit", $id); break;
		case 'viewform': DisplayForm($id); break;
		case 'addform': DisplayFormForm("add", ""); break;
		case 'updatefields':
			UpdateFields($id, $datatype, $field, $order, $values, $linebreaks, $scored);
			DisplayFormForm("edit", $id);
			break;
		case 'update':
			UpdateForm($id, $formtitle, $formdesc, $username);
			DisplayFormList();
			break;
		case 'add':
			AddForm($formtitle, $formdesc, $username);
			DisplayFormList();
			break;
		case 'delete':
			DeleteForm($id);
			DisplayFormList();
			break;
		case 'publish':
			PublishForm($id);
			DisplayFormList();
			break;
		default:
			DisplayFormList();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- UpdateForm ------------------------ */
	/* -------------------------------------------- */
	function UpdateForm($id, $formtitle, $formdesc, $username) {
		/* perform data checks */
		$formtitle = mysqli_real_escape_string($formtitle);
		$formdesc = mysqli_real_escape_string($formdesc);
		$username = mysqli_real_escape_string($username);
		
		/* update the form */
		$sqlstring = "update forms set form_title = '$formtitle', form_desc = '$formdesc' where form_id = $id";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		?><div align="center"><span class="message"><?=$formtitle?> updated</span></div><br><br><?
	}


	/* -------------------------------------------- */
	/* ------- AddForm ---------------------------- */
	/* -------------------------------------------- */
	function AddForm($formtitle, $formdesc, $username) {
		/* perform data checks */
		$formtitle = mysqli_real_escape_string($formtitle);
		$formdesc = mysqli_real_escape_string($formdesc);
		$username = mysqli_real_escape_string($username);
		
		/* insert the new form */
		$sqlstring = "insert into forms (form_title, form_desc, form_creator, form_createdate) values ('$formtitle', '$formdesc', '$username', now())";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		?><div align="center"><span class="message"><?=$formtitle?> added</span></div><br><br><?
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateFields ----------------------- */
	/* -------------------------------------------- */
	function UpdateFields($id, $datatype, $field, $order, $values, $linebreaks, $scored) {
		/* perform data checks */
		
		/* check to see if any data has been entered for this form */
		
		/* delete all previous formfields */
		$sqlstring = "delete from formfields where form_id = $id";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		/* insert all the new fields */
		for($i=0; $i<count($datatype); $i++) {
			if (trim($field[$i]) != "") {
				$field[$i] = mysqli_real_escape_string($field[$i]);
				$values[$i] = mysqli_real_escape_string($values[$i]);
				$order[$i] = mysqli_real_escape_string($order[$i]);
			
				$sqlstring = "insert into formfields (form_id, formfield_desc, formfield_values, formfield_datatype, formfield_haslinebreak, formfield_scored, formfield_order) values ($id, '$field[$i]', '$values[$i]', '$datatype[$i]', '$linebreaks[$i]', '$scored[$i]', '$order[$i]')";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			}
		}
		
		?><div align="center"><span class="message"><?=$formtitle?> updated</span></div><br><br><?
	}
	

	/* -------------------------------------------- */
	/* ------- DeleteForm ------------------------- */
	/* -------------------------------------------- */
	function DeleteForm($id) {
		$sqlstring = "delete from forms where form_id = $id";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		?><div align="center"><span class="message"><?=$id?> deleted</span></div><br><br><?
	}	


	/* -------------------------------------------- */
	/* ------- PublishForm ------------------------ */
	/* -------------------------------------------- */
	function PublishForm($id) {
		$sqlstring = "update forms set form_ispublished = 1 where form_id = $id";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		?><div align="center"><span class="message"><?=$id?> published</span></div><br><br><?
	}	
	
	
	/* -------------------------------------------- */
	/* ------- DisplayFormForm -------------------- */
	/* -------------------------------------------- */
	function DisplayFormForm($type, $id) {
	
		/* populate the fields if this is an edit */
		if ($type == "edit") {
			$sqlstring = "select * from forms where form_id = $id";
			$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$title = $row['form_title'];
			$desc = $row['form_desc'];
		
			$formaction = "update";
			$formtitle = "Updating $formname";
			$submitbuttonlabel = "Update";
		}
		else {
			$formaction = "add";
			$formtitle = "Add new form";
			$submitbuttonlabel = "Add";
		}
		
		$urllist['Administration'] = "admin.php";
		$urllist['Forms'] = "adminforms.php";
		$urllist[$title] = "adminforms.php?action=editform&id=$id";
		NavigationBar("Admin", $urllist);
		
	?>
		<div align="center">
		<table class="entrytable">
			<form method="post" action="adminforms.php">
			<input type="hidden" name="action" value="<?=$formaction?>">
			<input type="hidden" name="id" value="<?=$id?>">
			<tr>
				<td colspan="2" align="center">
					<b><?=$formtitle?></b>
				</td>
			</tr>
			<tr>
				<td>Title</td>
				<td><input type="text" name="formtitle" value="<?=$title?>"></td>
			</tr>
			<tr>
				<td>Description</td>
				<td><input type="text" name="formdesc" value="<?=$desc?>" size="50"></td>
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
				<form method="post" action="adminforms.php">
				<input type="hidden" name="action" value="updatefields">
				<input type="hidden" name="id" value="<?=$id?>">
				<?
					$neworder = 1;
					/* display all other rows, sorted by order */
					$sqlstring = "select * from formfields where form_id = $id order by formfield_order + 0";
					$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
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
				<tr>
					<td colspan="3"><input type="submit" value="Add/update all"></td>
				</tr>
				</form>
			</tbody>
		</table>
		<br><br>
		
		<div style="border: 1px solid #DDDDDD; padding: 10px">
			<a href="adminforms.php?id=<?=$id?>&action=publish" style="color:darkred; font-weight: bold; font-size: 14pt; background-color: #FFDDDD; padding: 3px">Publish</a>
			<br><br>
			<span style="color: #666666; font-size:10pt">This cannot be undone. Once it is published, people may start using it, so you can't go mucking with it</span>
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
	function DisplayForm($id) {
	
		$sqlstring = "select * from forms where form_id = $id";
		$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$title = $row['form_title'];
		$desc = $row['form_desc'];
		
		$urllist['Administration'] = "admin.php";
		$urllist['Forms'] = "adminforms.php";
		$urllist[$title] = "adminforms.php?action=editform&id=$id";
		NavigationBar("Admin", $urllist);
		
	?>
		<div align="center">

		<br><br>
		<table class="formentrytable">
			<tr>
				<td class="title" colspan="3"><?=$title?></td>
			</tr>
			<tr>
				<td class="desc" colspan="3"><?=$desc?></td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
				<td style="font-size:8pt; color: darkblue">Question #</td>
				<td style="font-size:8pt; color: darkblue">Question ID</td>
			</tr>
			<?
				/* display all other rows, sorted by order */
				$sqlstring = "select * from formfields where form_id = $id order by formfield_order + 0";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
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
												natsort($values);
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
						</td>
						<? if ($formfield_scored) {?>
						<td><input type="text" size="2"></td>
						<? } ?>
						<td class="order"><?=$formfield_order?></td>
						<td class="order"><?=$formfield_id?></td>
					</tr>
					<?
				}
			?>
		</table>
		<br><br>
		
		</div>
	<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayFormList -------------------- */
	/* -------------------------------------------- */
	function DisplayFormList() {
	
		$urllist['Administration'] = "admin.php";
		$urllist['Forms'] = "adminforms.php";
		$urllist['Add Form'] = "adminforms.php?action=addform";
		NavigationBar("Admin", $urllist);
		
	?>

	<table class="graydisplaytable">
		<thead>
			<tr>
				<th>Title</th>
				<th>Description</th>
				<th>Creator</th>
				<th>Create Date</th>
				<th>Published</th>
			</tr>
		</thead>
		<tbody>
			<?
				$sqlstring = "select a.*, b.username 'creatorusername', b.user_fullname 'creatorfullname' from forms a left join users b on a.form_creator = b.user_id order by a.form_title";
				$result = MySQLiQuery($sqlstring) or die("Query failed [" . __FILE__ . "(line " . __LINE__ . ")]: " . mysql_error() . "<br><i>$sqlstring</i><br>");
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$id = $row['form_id'];
					$title = $row['form_title'];
					$desc = $row['form_desc'];
					$creatorusername = $row['creatorusername'];
					$creatorfullname = $row['creatorfullname'];
					$createdate = $row['form_createdate'];
					$ispublished = $row['form_ispublished'];
			?>
			<tr>
				<td>
					<? if ($ispublished) { ?>
					<a href="adminforms.php?action=viewform&id=<?=$id?>"><?=$title?></a>
					<? } else { ?>
					<a href="adminforms.php?action=editform&id=<?=$id?>"><?=$title?></a>
					<? } ?>
				</td>
				<td><?=$desc?></td>
				<td><?=$creatorfullname?></td>
				<td><?=$createdate?></td>
				<td><? if ($ispublished) { echo "&#10004;"; } ?></td>
			</tr>
			<? 
				}
			?>
		</tbody>
	</table>
	<?
	}
?>


<? include("footer.php") ?>
