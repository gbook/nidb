<?
 // ------------------------------------------------------------------------------
 // NiDB functions.php
 // Copyright (C) 2004 - 2020
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

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");
	
	require_once "Mail.php";
	require_once "Mail/mime.php";

	/* this file includes the global functions */
	
	/* -------------------------------------------- */
	/* ------- LoadConfig ------------------------- */
	/* -------------------------------------------- */
	// this function loads the config file into a GLOBAL variable called $cfg
	// ----------------------------------------------------------
	function LoadConfig(bool $quiet=false) {
		$file = "";
		$possiblefiles = array('nidb.cfg', '../nidb.cfg', '../programs/nidb.cfg', '../bin/nidb.cfg', '/home/nidb/programs/nidb.cfg', '/nidb/programs/nidb.cfg', '/nidb/nidb.cfg', '/nidb/bin/nidb.cfg');
		
		$found = false;
		foreach ($possiblefiles as $f) {
			if (file_exists($f)) {
				$found = true;
				$file = $f;
				break;
			}
		}
		
		if (!$found) {
			
			if (!$quiet) {
				?><b><tt>nidb.cfg</tt> not found</b><br>
				Perhaps you need to run the <a href="setup.php">setup</a>?
				Or - edit the <tt>nidb.cfg.sample</tt> file and rename it to <tt>nidb.cfg</tt>?<br>
				Or - check if <tt>nidb.cfg</tt> exists and is in one of the following locations<br>
				<ul>
				<?
					foreach ($possiblefiles as $file) {
						echo "<li>$file\n";
					}
				?>
				</ul>
				<?
			}
			
			return null;
		}
		$cfg['cfgpath'] = $file;

		if (file_exists('nidb-cluster.cfg')) {
			$cfg['clustercfgpath'] = 'nidb-cluster.cfg';
		}
		
		/* if the config file exists, parse it into an array */
		$lines = file($file);
		foreach ($lines as $line) {
			if ((substr($line,0,1) != '#') && (trim($line) != "")) {
				list($var, $value) = explode(' = ', trim($line));
				$var = str_replace(array('[',']'),'',$var);
				$cfg{$var} = $value;
			}
		}
		return $cfg;
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetVariable ------------------------ */
	/* -------------------------------------------- */
	function GetVariable($var) {
		/* function to check for a global variable passed in by either GET or POST methods */
		if (isset($_POST[$var])) {
			if ($_POST[$var] != "")
				return $_POST[$var];
		}
		elseif (isset($_GET[$var])) {
			if ($_GET[$var] != "")
				return $_GET[$var];
		}
		else
			return NULL;
	}


	/* -------------------------------------------- */
	/* ------- GetVariables ----------------------- */
	/* -------------------------------------------- */
	function GetVariables($var) {
	
		/* get all variables from POST that begin with $var */
		$postexists = false;
		foreach ($_POST as $key => $value) {
			if (preg_match("/^$var/", $key)) {
				$id = str_replace("$var-","",$key);
				$retvars[$id] = $_POST[$key];
				$postexists = true;
			}
		}

		/* get all variables from GET that begin with $var */
		$getexists = false;
		foreach ($_GET as $key => $value) {
			if (preg_match("/^$var/", $key)) {
				$id = str_replace("$var-","",$key);
				$retvars[$id] = $_GET[$key];
				$getexists = true;
			}
		}
		
		if ($postexists || $getexists)
			return $retvars;
		else
			return NULL;
		
	}
	
	
	/* -------------------------------------------- */
	/* ------- PrintVariable ---------------------- */
	/* -------------------------------------------- */
	function PrintVariable($v, $vname = 'Var') {
		echo "<pre>";
		echo "<b><u>$vname</u></b><br>";
		$v = str_replace("<", "&lt;", $v);
		$v = str_replace(">", "&gt;", $v);
		print_r($v);
		echo "</pre>";
	}


	/* -------------------------------------------- */
	/* ------- Debug ------------------------------ */
	/* -------------------------------------------- */
	function Debug($F, $L, $msg) {
		if ($GLOBALS['cfg']['debug'] == 1) {
		?>
		<tt style="color:#444; font-size:8pt"><b>[<?=$F?> @ line <?=$L?>]</b> <?=$msg?></tt><br>
		<?
		}
	}


	/* -------------------------------------------- */
	/* ------- SendGmail -------------------------- */
	/* -------------------------------------------- */
	function SendGmail($to,$subject,$body,$debug,$usebcc=0) {
	
		$from = $GLOBALS['cfg']['emailfrom'];

		if ($usebcc) {
			$headers = array(
				'From' => $from,
				'To' => $from,
				'Subject' => $subject,
				'Bcc' => $to
			);
		}
		else {
			$headers = array(
				'From' => $from,
				'To' => $to,
				'Subject' => $subject
			);
		}
		
		$mime = new Mail_mime();
		$mime->setHTMLBody($body);

		$body = $mime->get();
        $headers = $mime->headers($headers);

		$host = $GLOBALS['cfg']['emailserver'];
		if (substr($host,0,6) == "tls://")
			$host = str_replace("tls://","",$host);
		if (substr($host,0,6) != "ssl://")
			$host = "ssl://$host";

		$smtp = Mail::factory('smtp', array(
				//'host' => "ssl://" . $GLOBALS['cfg']['emailserver'],
				'host' => $host,
				'port' => 465,
				'debug' => false,
				'auth' => true,
				'username' => $GLOBALS['cfg']['emailusername'],
				'password' => $GLOBALS['cfg']['emailpassword']
			));
	
		/* wrap the body in an HTML mime type and copywrite footer */
		//$body = "MIME-Version: 1.0\nContent-Type: multipart/mixed; BOUNDARY=\"$boundry\"\n\n--$boundry\nContent-Type: text/html\n"
		$body = "<html><body style=\"font-family: arial, helvetica, sans-serif\">" . $body . "<br><br><br><hr><small style='font-size:8pt; color: #666'>Email sent from " . $GLOBALS['cfg']['siteurl'] . ". If you received this email in error, please disregard it.<br><br>&copy; 2004-" . date("Y") . " " . $GLOBALS['cfg']['sitename'] . ", powered by NiDB http://github.com/gbook/nidb</small></body></html>";
		$mail = $smtp->send($to, $headers, $body);

		if ($debug) {
			?>
			<table border="1">
				<tr>
					<td>To</td>
					<td><?=$to?></td>
				</tr>
				<tr>
					<td>From</td>
					<td><?=$from?></td>
				</tr>
				<tr>
					<td>Subject</td>
					<td><?=$subject?></td>
				</tr>
				<tr>
					<td>Body</td>
					<td><?=$body?></td>
				</tr>
			</table>
			<?
		}
		
		if (PEAR::isError($mail)) {
			if ($debug) {
				echo('<p>' . $mail->getMessage() . ' | ' . $mail->getUserInfo() . '</p>');
			}
			return 0;
		} else {
			if ($debug) {
				echo('<p>Message successfully sent!</p>');
			}
			return 1;
		}	
	}
	

	/* -------------------------------------------- */
	/* ------- FormatCountdown -------------------- */
	/* -------------------------------------------- */
	function FormatCountdown($diff) {
		$days = floor($diff/86400);
		$diff = $diff - ($days*86400);
		$hours = floor($diff/3600);
		$diff = $diff - ($hours*3600);
		$minutes = floor($diff/60);
		$diff = $diff - ($minutes*60);
		$seconds = number_format($diff,0);
		$time = "";
		if ($days > 0) { $time = $days . "d $hours" . "h $minutes" . "m $seconds" . "s"; }
		else {
			if ($hours > 0) { $time = $hours . "h $minutes" . "m $seconds" . "s"; }
			else {
				if ($minutes > 0) { $time = $minutes . "m $seconds" . "s"; }
				else {
					if ($seconds > 0) { $time = $seconds . "s"; }
					else { $time = "0"; }
				}
			}
		}

		return $time;
	}


	/* -------------------------------------------- */
	/* ------- MakeSQLList ------------------------ */
	/* -------------------------------------------- */
	function MakeSQLList($str) {
		$parts = preg_split('/[\^,;\-\'\s\t\n\f\r]+/', $str);
		foreach ($parts as $part) {
			$newparts[] = "'" . trim($part) . "'";
		}
		return implode2(",", $newparts);
	}


	/* -------------------------------------------- */
	/* ------- MakeSQLListFromArray --------------- */
	/* -------------------------------------------- */
	function MakeSQLListFromArray($parts) {
		foreach ($parts as $part) {
			$newparts[] = "'" . trim($part) . "'";
		}
		return implode2(",", $newparts);
	}

	
	/* -------------------------------------------- */
	/* ------- PrintSQLTable ---------------------- */
	/* -------------------------------------------- */
	function PrintSQLTable($result,$url,$orderby,$size,$text=false) {
		$fields_num = mysqli_num_fields($result);
		$numrows = mysqli_num_rows($result);

		$fieldnames = array();
		
		if ($text) {
			// printing table headers
			
			for($i=0; $i<$fields_num; $i++)
			{
				$field = mysqli_fetch_field($result);
				$fieldnames[] = $field->name;
			}
			/*
			$str = implode(",",$fieldnames) . "\n";

			while($row = mysqli_fetch_row($result))
			{
				foreach($row as $cell)
					$cells[] = $cell;

				$str .= implode(",",$cells) . "\n";
				unset($cells);
			}*/
			
			$table = array();
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$tablerow = array();
				foreach($fieldnames as $col) {
					$tablerow["$col"] = $row["$col"];
				}
				$table[] = $tablerow;
			}
			
			$renderer = new ArrayToTextTable($table);
			$renderer->showHeaders(true);
			$str = $renderer->render(true);
			
			/* reset the pointer so not to confuse any subsequent data access */
			mysqli_data_seek($result, 0);
			return $str;
		}
		else {
			?>
			Displaying [<?=$numrows?>] rows<br><br>
			<table cellspacing="0" cellpadding="4" style="border-collapse:collapse; font-size:<?=$size?>pt; white-space:nowrap;">
				<tr>
			<?
			// printing table headers
			for($i=0; $i<$fields_num; $i++)
			{
				$field = mysqli_fetch_field($result);
				$fieldname = $field->name;
				?>
				<td style="border: 1px solid black; background-color: #DDDDDD; padding-left:5px; padding-right:5px; font-weight:bold"><a href="<?=$url?>&orderby=<?=$fieldname?>"><?=$fieldname?></td>
				<?
			}
			echo "</tr>\n";
			if (mysqli_num_rows($result) > 0) {
				// printing table rows
				while($row = mysqli_fetch_row($result))
				{
					echo "<tr>";

					// $row is array... foreach( .. ) puts every element
					// of $row to $cell variable
					foreach($row as $cell)
						echo "<td style='border: 1px solid #DDDDDD;'>$cell</td>";

					echo "</tr>\n";
				}
				echo "</table>";
				
				/* reset the pointer so not to confuse any subsequent data access */
				mysqli_data_seek($result, 0);
			}
			else {
				echo "</table>";
			}
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- MySQLiQuery ------------------------ */
	/* -------------------------------------------- */
	function MySQLiQuery($sqlstring,$file,$line,$error="") {

		Debug($file, $line,"Running MySQL Query [$sqlstring]");
		$result = mysqli_query($GLOBALS['linki'], $sqlstring);
		if ($result == false) {
			$datetime = date('r');
			$username = $GLOBALS['username'];
			if ($GLOBALS['linki']) {
				$body = "<b>Query failed on [$datetime]:</b> $file (line $line)<br>
				<b>Error:</b> " . mysqli_error($GLOBALS['linki']) . "<br>
				<b>SQL:</b> $sqlstring<br><b>Username:</b> $username<br>
				<b>SESSION</b> <pre>" . print_r($_SESSION,true) . "</pre><br>
				<b>SERVER</b> <pre>" . print_r($_SERVER,true) . "</pre><br>
				<b>POST</b> <pre>" . print_r($_POST,true) . "</pre><br>
				<b>GET</b> <pre>" . print_r($_GET,true) . "</pre>";
			}
			else {
				$body = "<b>Query failed on [$datetime]:</b> $file (line $line)<br>
				<b>Error:</b> <span style='font-size: 18pt'>NOT CONNECTED TO DATABASE</span><br>
				<b>SQL:</b> $sqlstring<br><b>Username:</b> $username<br>
				<b>SESSION</b> <pre>" . print_r($_SESSION,true) . "</pre><br>
				<b>SERVER</b> <pre>" . print_r($_SERVER,true) . "</pre><br>
				<b>POST</b> <pre>" . print_r($_POST,true) . "</pre><br>
				<b>GET</b> <pre>" . print_r($_GET,true) . "</pre>";
			}
			$gm = SendGmail($GLOBALS['cfg']['adminemail'],"User encountered error in $file",$body, 0);
			
			$file = mysqli_real_escape_string($GLOBALS['linki'], $file);
			$msg = mysqli_real_escape_string($GLOBALS['linki'], $body);
			
			$sqlstring = "insert into error_log (error_hostname, error_type, error_source, error_module, error_date, error_message) values ('localhost', 'sql', 'web', '$file', now(), '$msg')";
			$result = mysqli_query($GLOBALS['linki'], $sqlstring);
			
			if ($GLOBALS['cfg']['hideerrors']) {
				die("<div width='100%' style='border:1px solid red; background-color: #FFC; margin:10px; padding:10px; border-radius:5px; text-align: center'><b>Internal NiDB error.</b><br>The site administrator has been notified. Contact the administrator &lt;".$GLOBALS['cfg']['adminemail']."&gt; if you can provide additional information that may have led to the error<br><br><img src='images/topmen.png'></div>");
			}
			else {
				?>
				<div style="border: 2px solid orange" width="100%">
					<h2>SQL error occured</h2>
					<?=$body?>
				</div>
				<?
			}
		}
		else {
			return $result;
		}
	}


	/* -------------------------------------------- */
	/* ------- MySQLiBoundQuery ------------------- */
	/* -------------------------------------------- */
	function MySQLiBoundQuery($q,$file,$line,$error="") {
		Debug($file, $line,"Running MySQL Query [$sqlstring]");
		
		if (!mysqli_stmt_execute($q)) {
			$datetime = date('r');
			$username = $GLOBALS['username'];
			$body = "<b>Query failed on [$datetime]:</b> $file (line $line)<br>
			<b>Error:</b> " . mysqli_error($GLOBALS['linki']) . "<br>
			<b>SQL:</b> $sqlstring<br><b>Username:</b> $username<br>
			<b>SESSION</b> <pre>" . print_r($_SESSION,true) . "</pre><br>
			<b>SERVER</b> <pre>" . print_r($_SERVER,true) . "</pre><br>
			<b>POST</b> <pre>" . print_r($_POST,true) . "</pre><br>
			<b>GET</b> <pre>" . print_r($_GET,true) . "</pre>";
			SendGmail($GLOBALS['cfg']['adminemail'],"User encountered error in $file",$body, 0);
			
			if ($GLOBALS['cfg']['hideerrors']) {
				die("<div width='100%' style='border:1px solid red; background-color: #FFC; margin:10px; padding:10px; border-radius:5px; text-align: center'><b>Internal NiDB error.</b><br>The site administrator has been notified. Contact the administrator &lt;".$GLOBALS['cfg']['adminemail']."&gt; if you can provide additional information that may have led to the error<br><br><img src='images/topmen.png'></div>");
			}
			else {
				?>
				<div style="border: 2px solid orange" width="100%">
					<h2>SQL error occured</h2>
					<?=$body?>
				</div>
				<?
			}
		}
		else {
			return mysqli_stmt_get_result($q);
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetInstanceID ---------------------- */
	/* -------------------------------------------- */
	function GetInstanceID() {
		$sqlstring = "select user_instanceid from users where username = '" . $GLOBALS['username'] . "'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$instanceid = $row['user_instanceid'];
		return $instanceid;
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetInstanceName -------------------- */
	/* -------------------------------------------- */
	function GetInstanceName($id) {
		$sqlstring = "select instance_name from instance where instance_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$n = $row['instance_name'];
		return $n;
	}	


	/* -------------------------------------------- */
	/* ------- GetDataPathFromSeriesID ------------ */
	/* -------------------------------------------- */
	function GetDataPathFromSeriesID($id, $modality) {
		$modality = strtolower($modality);
		
		if (($id <= 0) || ($id == "")) {
			return array("error - invalid ID","","","","");
		}
		if ($modality == "") {
			return array("error - blank modality","","","","");
		}
		
		$sqlstring = "select * from $modality"."_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality"."series_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$seriesnum = $row['series_num'];
		$subjectid = $row['subject_id'];
		$studyid = $row['study_id'];
		$datatype = $row['data_type'];
		if ($datatype == "") {
			$datatype = $modality;
		}
		
		$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/$datatype";
		$qapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/qa";
		return array($path, $qapath, $uid, $studynum, $studyid, $subjectid);
	}


	/* -------------------------------------------- */
	/* ------- GetDataPathFromStudyID ------------- */
	/* -------------------------------------------- */
	function GetDataPathFromStudyID($id) {
		$sqlstring = "select * from studies b left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where b.study_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$subjectid = $row['subject_id'];
		$studyid = $row['study_id'];
		$modality = $row['study_modality'];
		
		$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";
		return array($path, $uid, $studynum, $studyid, $subjectid, $modality);
	}


	/* -------------------------------------------- */
	/* ------- GetStudyInfo ----------------------- */
	/* -------------------------------------------- */
	function GetStudyInfo($id) {
		$sqlstring = "select * from studies b left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join projects e on c.project_id = e.project_id where b.study_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$subjectid = $row['subject_id'];
		$studyid = $row['study_id'];
		$modality = $row['study_modality'];
		$type = $row['study_type'];
		$studydatetime = $row['study_datetime'];
		$enrollmentid = $row['enrollment_id'];
		$projectname = $row['project_name'];
		$projectid = $row['project_id'];
		
		$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";
		return array($path, $uid, $studynum, $studyid, $subjectid, $modality, $type, $studydatetime, $enrollmentid, $projectname, $projectid);
	}


	/* -------------------------------------------- */
	/* ------- GetEnrollmentInfo ------------------ */
	/* -------------------------------------------- */
	function GetEnrollmentInfo($id) {
		$sqlstring = "select * from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.enrollment_id = $id";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$subjectid = $row['subject_id'];
		$projectname = $row['project_name'];
		$projectid = $row['project_id'];
		
		$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum";
		return array($uid, $subjectid, $projectname, $projectid);
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetAlternateUIDs ------------------- */
	/* -------------------------------------------- */
	function GetAlternateUIDs($subjectid, $enrollmentid) {

		//echo "<br><br>GetAlternateUIDs($subjectid, $enrollmentid)<br><br>";
		
		if ($subjectid == "") {
			return "";
		}
	
		$altuids = array();
		/* need the type equality too because '' evaluates to 0... */
		if ($enrollmentid === '') {
			$sqlstring = "select * from subject_altuid where subject_id = '$subjectid' order by altuid";
			//echo "<br><br>Point A<br><br>";
		}
		else {
			$sqlstring = "select * from subject_altuid where subject_id = '$subjectid' and enrollment_id = '$enrollmentid' order by altuid";
			//echo "<br><br>Point B<br><br>";
		}
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$altuid = trim($row['altuid']);
			
			if (($altuid != '') && (strtolower($altuid) != 'null')) {
				$isprimary = $row['isprimary'];
				if ($isprimary) {
					$altuids[] = '*'. $altuid;
				}
				else {
					$altuids[] = $altuid;
				}
			}
		}
		
		return $altuids;
	}


	/* -------------------------------------------- */
	/* ------- GetPrimaryProjectID ---------------- */
	/* -------------------------------------------- */
	function GetPrimaryProjectID($subjectid, $projectid) {
		if (!ValidID($subjectid,'Subject ID')) { return; }
		if (!ValidID($projectid,'Project ID')) { return; }
	
		//if (($subjectid == "") || ($projectid == "")) {
		//	return "";
		//}
		
		$sqlstring = "select a.enrollment_id, b.uid, c.project_name from enrollment a left join subjects b on a.subject_id = b.subject_id left join projects c on a.project_id = c.project_id where a.subject_id = $subjectid and a.project_id = $projectid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$enrollmentid = $row['enrollment_id'];
		$uid = $row['uid'];
		$projectname = $row['project_name'];
		if (mysqli_num_rows($result) > 1) {
			?><span class="staticmessage">Subject [<?=$uid?>] has more than one enrollment in the same project [<?=$projectname?>]. Using the first enrollment to get the primary ID.</span><br><br><?
		}

		$sqlstring = "select * from subject_altuid where subject_id = '$subjectid' and enrollment_id = $enrollmentid order by isprimary desc limit 1";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//PrintSQL($sqlstring);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		return $row['altuid'];
	}

	
	/* -------------------------------------------- */
	/* ------- GetModalityList -------------------- */
	/* -------------------------------------------- */
	function GetModalityList($withdesc = false) {
		$sqlstring = "select * from modalities order by mod_desc";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$modalities[] = $row['mod_code'];
			$descriptions[] = $row['mod_code'];
		}
			
		if ($withdesc) {
			return array($modalities,$descriptions);
		}
		else {
			return $modalities;
		}
	}


	/* -------------------------------------------- */
	/* ------- IsNiDBModality --------------------- */
	/* -------------------------------------------- */
	function IsNiDBModality($modality) {
		$modality = mysqli_real_escape_string($GLOBALS['linki'], $modality);

		$valid = false;
		
		$sqlstring = "select * from modalities where mod_code = '$modality'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		if (mysqli_num_rows($result) > 0) {
			$sqlstringA = "show tables from " . $GLOBALS['cfg']['mysqldatabase'] . " like '" . strtolower($modality) . "'";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			if (mysqli_num_rows($result) > 0) {
				$valid = true;
			}
		}
			
		return $valid;
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayProjectSelectBox ------------ */
	/* -------------------------------------------- */
	/* display project <select> box with only projects
	   to which the user has permissions and belongs the
	   parent instance. Also highlight selected IDs.
	   width and height are in px
	*/
	function DisplayProjectSelectBox($currentinstanceonly,$varname,$idname,$classname,$multiselect,$selectedids,$width=350,$height=30) {

		if (!is_array($selectedids))
			$selectedids = array($selectedids);
		
		if (in_array(0, $selectedids)) { $selected = "selected"; } else { $selected = ""; }
		?>
		<select name="<?=$varname?>" class="<?=$classname?>" style="width:<?=$width?>px;height:<?=$height?>px" <? if ($multiselect) { echo "multiple"; } ?>>
			<option value="0" <?=$selected?>>All Projects</option>
			<?
				if ($currentinstanceonly) {
					$sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '" . $_SESSION['username'] . "') and a.instance_id = '" . $_SESSION['instanceid'] . "' order by project_name";
				}
				else {
					$sqlstring = "select * from projects a left join user_project b on a.project_id = b.project_id where b.user_id = (select user_id from users where username = '" . $_SESSION['username'] . "') order by project_name";
				}
				
				$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$project_id = $row['project_id'];
					$project_name = $row['project_name'];
					$project_costcenter = $row['project_costcenter'];
					if (in_array($project_id, $selectedids)) { $selected = "selected"; } else { $selected = ""; }
					?>
					<option value="<?=$project_id?>" <?=$selected?>><?=$project_name?> (<?=$project_costcenter?>)</option>
					<?
				}
			?>
		</select><? if ($multiselect) { echo "<br><span class='tiny'>Ctrl + click to select multiple</span>"; } ?>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- implode2 --------------------------- */
	/* -------------------------------------------- */
	/* special implode which checks for empty array */
	function implode2($chr, $arr) {
		if (count($arr) > 1) {
			return implode($chr,$arr);
		}
		elseif (count($arr) == 1) {
			return $arr[0];
		}
		else {
			return "";
		}
		
	}

	
	/* -------------------------------------------- */
	/* ------- mysqli_real_escape_array ----------- */
	/* -------------------------------------------- */
	function mysqli_real_escape_array ($a) {
		if (is_array($a)) {
			foreach ($a as $i => $val) {
				$a[$i] = mysqli_real_escape_string($GLOBALS['linki'], $val);
			}
			return $a;
		}
		else {
			return mysqli_real_escape_string($GLOBALS['linki'], $a);
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- isInteger -------------------------- */
	/* -------------------------------------------- */
	function isInteger($input){
		return(ctype_digit(strval($input)));
	}


	/* -------------------------------------------- */
	/* ------- arraystats ------------------------- */
	/* -------------------------------------------- */
	function arraystats ($a) {
		$n = count($a);
		$min = min($a);
		$max = max($a);
		$mean = array_sum($a)/$n;
		$stdev = sd($a);
		
		return array($n, $min, $max, $mean, $stdev);
	}
	
	
	/* -------------------------------------------- */
	/* ------- ResetQA ---------------------------- */
	/* -------------------------------------------- */
	function ResetQA($seriesids) {
		
		if (is_array($seriesids)) {
			$seriesids = mysqli_real_escape_array($seriesids);
		}
		else {
			$seriesids = array(mysqli_real_escape_string($GLOBALS['linki'], $seriesids));
		}

		foreach ($seriesids as $seriesid) {
			if ((is_numeric($seriesid)) && ($seriesid != "")) {
				/* delete from the mr_qa table */
				$sqlstring = "delete from mr_qa where mrseries_id = $seriesid";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				
				/* delete from the qc* tables */
				$sqlstring = "select qcmoduleseries_id from qc_moduleseries where series_id = $seriesid and modality = 'mr'";
				$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$qcmoduleseriesid = $row['qcmoduleseries_id'];

					if ($qcmoduleseriesid != "") {
						$sqlstringA = "delete from qc_results where qcmoduleseries_id = $qcmoduleseriesid";
						$resultA = MySQLiQuery($sqlstringA, __FILE__, __LINE__);
						
						$sqlstringB = "delete from qc_moduleseries where qcmoduleseries_id = $qcmoduleseriesid";
						$resultB = MySQLiQuery($sqlstringB, __FILE__, __LINE__);
						
						/* delete the qa directory */
						list($path, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid,'mr');
						
						//$qapath = "$path/qa";
						if (($uid == "") || ($studynum == "") || ($studyid == "") || ($subjectid == "")) {
							echo "Could not delete QA data. One of the following is blank uid[$uid] studynum[$studynum] studyid[$studyid] subjectid[$subjectid]<br>";
						}
						else {
							/* check if the path is valid */
							if (file_exists($qapath)) {
								$systemstring = "rm -rv $qapath";
								`$systemstring`;
							}
							else {
								echo "[$qapath] does not exist<br>";
							}
						}
						
						?><div align="center"><span class="message">QC deleted [<?=$qcmoduleseriesid?>]</span></div><br><br><?
					}
					else {
						echo "qcmoduleseries_id was blank<br>";
					}
				}
			}
			else {
				?><div align="center"><span class="message">Invalid MR series</span></div><br><br><?
			}
		}
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateMostRecent ------------------- */
	/* -------------------------------------------- */
	function UpdateMostRecent($userid, $subjectid, $studyid) {

		if ((trim($subjectid) == '') || ($subjectid == 0)) { $subjectid = 'NULL'; }
		if ((trim($studyid) == '') || ($studyid == 0)) { $studyid = 'NULL'; }
		
		/* insert the new most recent entry */
		$sqlstring = "insert ignore into mostrecent (user_id, subject_id, study_id, mostrecent_date) values ($userid, $subjectid, $studyid, now())";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* delete rows other than the most recent 15 items */
		$sqlstring = "DELETE FROM `mostrecent` WHERE mostrecent_id NOT IN ( SELECT mostrecent_id FROM ( SELECT mostrecent_id FROM `mostrecent` where user_id = $userid ORDER BY mostrecent_date DESC LIMIT 15) foo) and user_id = $userid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

	}

	
	/* -------------------------------------------- */
	/* ------- GetPerm ---------------------------- */
	/* -------------------------------------------- */
	function GetPerm($perms, $perm, $projectid) {
		
		//echo "Inside GetPerm() A<br>";
		//PrintVariable($perms);
		//PrintVariable($projectid);
		//echo "Inside GetPerm() B<br>";
		
		$hasperm = 0;
		foreach ($perms as $pid => $p) {
			if ($p[$perm] == 1) {
				$hasperm = 1;
				break;
			}
		}
		return $hasperm;
	}

	
	/* -------------------------------------------- */
	/* ------- GetCurrentUserProjectPermissions --- */
	/* -------------------------------------------- */
	function GetCurrentUserProjectPermissions($projectids) {
		$perms = array();
		$userid = $_SESSION['userid'];
		
		$projectidlist = implode2(',', $projectids);
		
		if ($projectidlist != "") {
			$sqlstring = "select a.*, b.project_name from user_project a left join projects b on a.project_id = b.project_id where a.user_id = '$userid' and a.project_id in ($projectidlist)";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			if (mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$projectid = $row['project_id'];
					$perms[$projectid]['projectname'] = $row['project_name'];
					$perms[$projectid]['projectadmin'] = $row['project_admin'] + 0;
					$perms[$projectid]['viewdata'] = $row['view_data'] + 0;
					$perms[$projectid]['viewphi'] = $row['view_phi'] + 0;
					$perms[$projectid]['modifydata'] = $row['write_data'] + 0;
					$perms[$projectid]['modifyphi'] = $row['write_phi'] + 0;
					
					/* fill in the implied permissions */
					if ($perms[$projectid]['projectadmin']) {
						$perms[$projectid]['modifyphi'] = 1;
						$perms[$projectid]['viewphi'] = 1;
						$perms[$projectid]['modifydata'] = 1;
						$perms[$projectid]['viewdata'] = 1;
					}
					if ($perms[$projectid]['modifyphi']) {
						$perms[$projectid]['viewphi'] = 1;
						$perms[$projectid]['modifydata'] = 1;
						$perms[$projectid]['viewdata'] = 1;
					}
					if ($perms[$projectid]['viewphi']) {
						$perms[$projectid]['viewphi'] = 1;
						$perms[$projectid]['viewdata'] = 1;
					}
					if ($perms[$projectid]['modifydata']) {
						$perms[$projectid]['viewdata'] = 1;
					}
				}
			}
		}
		
		return $perms;
	}
	
	
	/* -------------------------------------------- */
	/* ------- NavigationBar ---------------------- */
	/* -------------------------------------------- */
	function NavigationBar($title, $urllist, $perms=array()) {
		
		$msg = "";
		?>
		<style>
			.adminperms { background-color: #fc8171; padding: 1px 8px; color: #000; }
			.perms { background-color: #91b7ff; padding: 1px 8px; color: #000; }
		</style>
		<table width="100%" cellspacing="0">
			<tr>
				<td>
				<span class="headertable2">
				<?
				foreach ($urllist as $label => $url) {
					?>
					<a href="<?=$url?>"><?=$label?></a> <span style="color: #ccc">&gt;</span> 
					<?
				}
				?>
				</span>
				<?
				if (count($perms) > 0) {
					foreach ($perms as $projectid => $data) {
						$admin = $data['projectadmin'];
						$projectname = $data['projectname'];
						$viewdata = $perms[$projectid]['viewdata'];
						$viewphi = $perms[$projectid]['viewphi'];
						$modifydata = $perms[$projectid]['modifydata'];
						$modifyphi = $perms[$projectid]['modifyphi'];
						
						if ($admin) { $admin = "Admin"; }
						if ($modifyphi) { $modifyphi = "Modify PHI"; }
						if ($modifydata) { $modifydata = "Modify data"; }
						if ($viewphi) { $viewphi = "View PHI"; }
						if ($viewdata) { $viewdata = "View data"; }
						
						//echo "$projectname - $admin, $modifyphi, $modifydata, $viewphi, $viewdata<br>";
						if (($admin == '') && ($admin == '') && ($admin == '') && ($admin == '') && ($admin == '')) {
							$msg .= "<li>No permissions to access $projectname";
						}
						else {
							$msg .= "<li><b>$projectname</b>";
							
							if ($admin != '')
								$msg .= " <span class='adminperms'>$admin</span> ";
							if ($modifyphi != '')
								$msg .= " <span class='perms'>$modifyphi</span> ";
							if ($viewphi != '')
								$msg .= " <span class='perms'>$viewphi</span> ";
							if ($modifydata != '')
								$msg .= " <span class='perms'>$modifydata</span> ";
							if ($viewdata != '')
								$msg .= " <span class='perms'>$viewdata</span> ";
						}
					}
					
					?>
					<details style="font-size:8pt; margin-left:15px;">
					<summary>Permissions summary</summary>
					<div style="border: 1px solid #aaa; padding:5px; margin: 2px">
					Your access permissions for this subject
					<ul>
					<?=$msg?>
					</ul>
					</div>
					</details>
					<?
				}
				if (trim($title != "")) {
				?>
				<div align="center" class="headertable1"><?=$title?></div>
				</td>
				<?}?>
			</tr>
		</table>

		<br><br>
		<?
	}
	

	/* -------------------------------------------- */
	/* ------- ValidID ---------------------------- */
	/* -------------------------------------------- */
	function ValidID($var, $varname="") {
		if (isInteger($var) && ($var >= 0)) {
			return 1;
		}
		else {
			if (trim($varname) != "") {
				?><div class="error"><b>Error</b> - ID [<?=$var?>] named [<?=$varname?>] was not valid</div><?
			}
			return 0;
		}
	}
	

	/* -------------------------------------------- */
	/* ------- HumanReadableFilesize -------------- */
	/* -------------------------------------------- */
	function HumanReadableFilesize($size) {
		$mod = 1024;
	 
		$units = explode(' ','B KB MB GB TB PB');
		for ($i = 0; $size > $mod; $i++) {
			$size /= $mod;
		}
	 
		$size += 0.0;
		return number_format($size, 1) . '&nbsp;' . $units[$i];
	}

	
	/* -------------------------------------------- */
	/* ------- GenerateRandomString --------------- */
	/* -------------------------------------------- */
	function GenerateRandomString($length)
	{
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890_';
		$chars_length = (strlen($chars) - 1);
		$string = $chars{rand(0, $chars_length)};
		
		// Generate random string
		for ($i = 1; $i < $length; $i = strlen($string))
		{
			// Grab a random character from our list
			$r = $chars{rand(0, $chars_length)};
			$string .=  $r;
		}
		
		return $string;
	}


	/* -------------------------------------------- */
	/* ------- PrintSQL --------------------------- */
	/* -------------------------------------------- */
	function PrintSQL($sql) {
		?><div style="border:1px solid #CCCCCC"><?
		echo getFormattedSQL($sql);
		echo "</div>";
	}
	
	
	/* -------------------------------------------- */
	/* ------- getFormattedSQL -------------------- */
	/* -------------------------------------------- */
	function getFormattedSQL($sql_raw)
	{
		if( empty($sql_raw) || !is_string($sql_raw) ) {
			return false;
		}

		$sql_reserved_all = array (
		'ACCESSIBLE', 'ACTION', 'ADD', 'AFTER', 'AGAINST', 'AGGREGATE', 'ALGORITHM', 'ALL', 'ALTER', 'ANALYSE', 'ANALYZE', 'AND', 'AS', 'ASC',
		'AUTOCOMMIT', 'AUTO_INCREMENT', 'AVG_ROW_LENGTH', 'BACKUP', 'BEGIN', 'BETWEEN', 'BINLOG', 'BOTH', 'BY', 'CASCADE', 'CASE', 'CHANGE', 'CHANGED',
		'CHARSET', 'CHECK', 'CHECKSUM', 'COLLATE', 'COLLATION', 'COLUMN', 'COLUMNS', 'COMMENT', 'COMMIT', 'COMMITTED', 'COMPRESSED', 'CONCURRENT', 
		'CONSTRAINT', 'CONTAINS', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_TIMESTAMP', 'DATABASE', 'DATABASES', 'DAY', 'DAY_HOUR', 'DAY_MINUTE', 
		'DAY_SECOND', 'DEFINER', 'DELAYED', 'DELAY_KEY_WRITE', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV',
		'DO', 'DROP', 'DUMPFILE', 'DUPLICATE', 'DYNAMIC', 'ELSE', 'ENCLOSED', 'END', 'ENGINE', 'ENGINES', 'ESCAPE', 'ESCAPED', 'EVENTS', 'EXECUTE',
		'EXISTS', 'EXPLAIN', 'EXTENDED', 'FAST', 'FIELDS', 'FILE', 'FIRST', 'FIXED', 'FLUSH', 'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULL', 'FULLTEXT',
		'FUNCTION', 'GEMINI', 'GEMINI_SPIN_RETRIES', 'GLOBAL', 'GRANT', 'GRANTS', 'GROUP', 'HAVING', 'HEAP', 'HIGH_PRIORITY', 'HOSTS', 'HOUR', 'HOUR_MINUTE',
		'HOUR_SECOND', 'IDENTIFIED', 'IF', 'IGNORE', 'IN', 'INDEX', 'INDEXES', 'INFILE', 'INNER', 'INSERT', 'INSERT_ID', 'INSERT_METHOD', 'INTERVAL',
		'INTO', 'INVOKER', 'IS', 'ISOLATION', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LAST_INSERT_ID', 'LEADING', 'LEFT', 'LEVEL', 'LIKE', 'LIMIT', 'LINEAR',               
		'LINES', 'LOAD', 'LOCAL', 'LOCK', 'LOCKS', 'LOGS', 'LOW_PRIORITY', 'MARIA', 'MASTER', 'MASTER_CONNECT_RETRY', 'MASTER_HOST', 'MASTER_LOG_FILE',
		'MASTER_LOG_POS', 'MASTER_PASSWORD', 'MASTER_PORT', 'MASTER_USER', 'MATCH', 'MAX_CONNECTIONS_PER_HOUR', 'MAX_QUERIES_PER_HOUR',
		'MAX_ROWS', 'MAX_UPDATES_PER_HOUR', 'MAX_USER_CONNECTIONS', 'MEDIUM', 'MERGE', 'MINUTE', 'MINUTE_SECOND', 'MIN_ROWS', 'MODE', 'MODIFY',
		'MONTH', 'MRG_MYISAM', 'MYISAM', 'NAMES', 'NATURAL', 'NOT', 'NULL', 'OFFSET', 'ON', 'OPEN', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR',
		'ORDER', 'OUTER', 'OUTFILE', 'PACK_KEYS', 'PAGE', 'PARTIAL', 'PARTITION', 'PARTITIONS', 'PASSWORD', 'PRIMARY', 'PRIVILEGES', 'PROCEDURE',
		'PROCESS', 'PROCESSLIST', 'PURGE', 'QUICK', 'RAID0', 'RAID_CHUNKS', 'RAID_CHUNKSIZE', 'RAID_TYPE', 'RANGE', 'READ', 'READ_ONLY',            
		'READ_WRITE', 'REFERENCES', 'REGEXP', 'RELOAD', 'RENAME', 'REPAIR', 'REPEATABLE', 'REPLACE', 'REPLICATION', 'RESET', 'RESTORE', 'RESTRICT',
		'RETURN', 'RETURNS', 'REVOKE', 'RIGHT', 'RLIKE', 'ROLLBACK', 'ROW', 'ROWS', 'ROW_FORMAT', 'SECOND', 'SECURITY', 'SELECT', 'SEPARATOR',
		'SERIALIZABLE', 'SESSION', 'SET', 'SHARE', 'SHOW', 'SHUTDOWN', 'SLAVE', 'SONAME', 'SOUNDS', 'SQL', 'SQL_AUTO_IS_NULL', 'SQL_BIG_RESULT',
		'SQL_BIG_SELECTS', 'SQL_BIG_TABLES', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_CALC_FOUND_ROWS', 'SQL_LOG_BIN', 'SQL_LOG_OFF',
		'SQL_LOG_UPDATE', 'SQL_LOW_PRIORITY_UPDATES', 'SQL_MAX_JOIN_SIZE', 'SQL_NO_CACHE', 'SQL_QUOTE_SHOW_CREATE', 'SQL_SAFE_UPDATES',
		'SQL_SELECT_LIMIT', 'SQL_SLAVE_SKIP_COUNTER', 'SQL_SMALL_RESULT', 'SQL_WARNINGS', 'START', 'STARTING', 'STATUS', 'STOP', 'STORAGE',
		'STRAIGHT_JOIN', 'STRING', 'STRIPED', 'SUPER', 'TABLE', 'TABLES', 'TEMPORARY', 'TERMINATED', 'THEN', 'TO', 'TRAILING', 'TRANSACTIONAL',    
		'TRUNCATE', 'TYPE', 'TYPES', 'UNCOMMITTED', 'UNION', 'UNIQUE', 'UNLOCK', 'UPDATE', 'USAGE', 'USE', 'USING', 'VALUES', 'VARIABLES',
		'VIEW', 'WHEN', 'WHERE', 'WITH', 'WORK', 'WRITE', 'XOR', 'YEAR_MONTH'
		);

		$sql_skip_reserved_words = array('AS', 'ON', 'USING');
		$sql_special_reserved_words = array('(', ')');

		$sql_raw = str_replace("\n", " ", $sql_raw);

		$sql_formatted = "";

		$prev_word = "";
		$word = "";

		for( $i=0, $j = strlen($sql_raw); $i < $j; $i++ ) {
			$word .= $sql_raw[$i];

			$word_trimmed = trim($word);

			if($sql_raw[$i] == " " || in_array($sql_raw[$i], $sql_special_reserved_words))
			{
				$word_trimmed = trim($word);

				$trimmed_special = false;

				if( in_array($sql_raw[$i], $sql_special_reserved_words) )
				{
					$word_trimmed = substr($word_trimmed, 0, -1);
					$trimmed_special = true;
				}

				$word_trimmed = strtoupper($word_trimmed);

				if( in_array($word_trimmed, $sql_reserved_all) && !in_array($word_trimmed, $sql_skip_reserved_words) )
				{
					if(in_array($prev_word, $sql_reserved_all))
					{
					$sql_formatted .= '<b style="color:darkblue">'.strtoupper(trim($word)).'</b>'.'&nbsp;';
					}
					else
					{
					$sql_formatted .= '<br/>&nbsp;';
					$sql_formatted .= '<b style="color:darkblue">'.strtoupper(trim($word)).'</b>'.'&nbsp;';
					}

					$prev_word = $word_trimmed;
					$word = "";
				}
				else
				{
					$sql_formatted .= trim($word).'&nbsp;';

					$prev_word = $word_trimmed;
					$word = "";
				}
			}
		}

		$sql_formatted .= trim($word);

		return $sql_formatted;
	}


	/* Correlation related functions */
	function Correlation($arr1, $arr2)
	{        
		$correlation = 0;
		
		$k = SumProductMeanDeviation($arr1, $arr2);
		$ssmd1 = SumSquareMeanDeviation($arr1);
		$ssmd2 = SumSquareMeanDeviation($arr2);
		
		$product = $ssmd1 * $ssmd2;
		
		$res = sqrt($product);
		
		$correlation = $k / $res;
		
		return $correlation;
	}

	function SumProductMeanDeviation($arr1, $arr2)
	{
		$sum = 0;
		
		$num = count($arr1);
		
		for($i=0; $i<$num; $i++)
		{
			$sum = $sum + ProductMeanDeviation($arr1, $arr2, $i);
		}
		
		return $sum;
	}

	function ProductMeanDeviation($arr1, $arr2, $item)
	{
		return (MeanDeviation($arr1, $item) * MeanDeviation($arr2, $item));
	}

	function SumSquareMeanDeviation($arr)
	{
		$sum = 0;
		
		$num = count($arr);
		
		for($i=0; $i<$num; $i++)
		{
			$sum = $sum + SquareMeanDeviation($arr, $i);
		}
		
		return $sum;
	}

	function SquareMeanDeviation($arr, $item)
	{
		return MeanDeviation($arr, $item) * MeanDeviation($arr, $item);
	}

	function SumMeanDeviation($arr)
	{
		$sum = 0;
		
		$num = count($arr);
		
		for($i=0; $i<$num; $i++)
		{
			$sum = $sum + MeanDeviation($arr, $i);
		}
		
		return $sum;
	}

	function MeanDeviation($arr, $item)
	{
		$average = array_sum($arr)/count($arr);
		
		return $arr[$item] - $average;
	}    

	/* -------------------------------------------- */
	/* ------- GenerateColorGradient -------------- */
	/* -------------------------------------------- */
	function GenerateColorGradient() {
		/* generate a color gradient in an array (green to yellow) */
		$startR = 0xFF; $startG = 0xFF; $startB = 0x66;
		$endR = 0x66; $endG = 0xFF; $endB = 0x66;
		$total = 50;

		for ($i=0; $i<=$total; $i++) {
			$percentSR = ($i/$total)*$startR;
			$percentER = (1-($i/$total))*$endR;
			$colorR = $percentSR + $percentER;

			$percentSG = ($i/$total)*$startG;
			$percentEG = (1-($i/$total))*$endG;
			$colorG = $percentSG + $percentEG;

			$percentSB = ($i/$total)*$startB;
			$percentEB = (1-($i/$total))*$endB;
			$colorB = $percentSB + $percentEB;

			$color = sprintf("%02X%02X%02X", $colorR, $colorG, $colorB);
			$colors[] = $color;
		}

		/* generate gradient from yellow to red */
		$startR = 0xFF; $startG = 0x66; $startB = 0x66;
		$endR = 0xFF; $endG = 0xFF; $endB = 0x66;

		for ($i=0; $i<=$total; $i++) {
			$percentSR = ($i/$total)*$startR;
			$percentER = (1-($i/$total))*$endR;
			$colorR = $percentSR + $percentER;

			$percentSG = ($i/$total)*$startG;
			$percentEG = (1-($i/$total))*$endG;
			$colorG = $percentSG + $percentEG;

			$percentSB = ($i/$total)*$startB;
			$percentEB = (1-($i/$total))*$endB;
			$colorB = $percentSB + $percentEB;

			$color = sprintf("%02X%02X%02X", $colorR, $colorG, $colorB);
			$colors[$i+50] = $color;
		}
		return $colors;
	}


	/* -------------------------------------------- */
	/* ------- GenerateColorGradient2 ------------- */
	/* -------------------------------------------- */
	function GenerateColorGradient2() {
		/* generate a color gradient in an array (green --> blue) */
		$startR = 0x33; $startG = 0xFF; $startB = 0x33;
		$endR = 0x33; $endG = 0x33; $endB = 0xFF;
		$total = 33;

		for ($i=0; $i<=$total; $i++) {
			$percentSR = ($i/$total)*$startR;
			$percentER = (1-($i/$total))*$endR;
			$colorR = $percentSR + $percentER;

			$percentSG = ($i/$total)*$startG;
			$percentEG = (1-($i/$total))*$endG;
			$colorG = $percentSG + $percentEG;

			$percentSB = ($i/$total)*$startB;
			$percentEB = (1-($i/$total))*$endB;
			$colorB = $percentSB + $percentEB;

			$color = sprintf("%02X%02X%02X", $colorR, $colorG, $colorB);
			$colors[] = $color;
		}
		
		/* generate a color gradient in an array (yellow --> green) */
		$startR = 0xFF; $startG = 0xFF; $startB = 0x33;
		$endR = 0x33; $endG = 0xFF; $endB = 0x33;
		$total = 66;

		for ($i=0; $i<=$total; $i++) {
			$percentSR = ($i/$total)*$startR;
			$percentER = (1-($i/$total))*$endR;
			$colorR = $percentSR + $percentER;

			$percentSG = ($i/$total)*$startG;
			$percentEG = (1-($i/$total))*$endG;
			$colorG = $percentSG + $percentEG;

			$percentSB = ($i/$total)*$startB;
			$percentEB = (1-($i/$total))*$endB;
			$colorB = $percentSB + $percentEB;

			$color = sprintf("%02X%02X%02X", $colorR, $colorG, $colorB);
			$colors[] = $color;
		}

		/* generate gradient (red --> yellow) */
		$startR = 0xFF; $startG = 0x33; $startB = 0x33;
		$endR = 0xFF; $endG = 0xFF; $endB = 0x33;

		for ($i=0; $i<=$total; $i++) {
			$percentSR = ($i/$total)*$startR;
			$percentER = (1-($i/$total))*$endR;
			$colorR = $percentSR + $percentER;

			$percentSG = ($i/$total)*$startG;
			$percentEG = (1-($i/$total))*$endG;
			$colorG = $percentSG + $percentEG;

			$percentSB = ($i/$total)*$startB;
			$percentEB = (1-($i/$total))*$endB;
			$colorB = $percentSB + $percentEB;

			$color = sprintf("%02X%02X%02X", $colorR, $colorG, $colorB);
			$colors[$i+66] = $color;
		}
		return $colors;
	}

	
	/* -------------------------------------------- */
	/* ------- GetTags ---------------------------- */
	/* -------------------------------------------- */
	function GetTags($idtype, $tagtype, $id, $modality='') {
		
		$sqlstring = "";
		$tags = array();
		
		switch ($idtype) {
			case 'series': $sqlstring = "select tag from tags where series_id = '$id' and modality = '$modality' and tagtype = '$tagtype'"; break;
			case 'study': $sqlstring = "select tag from tags where study_id = '$id' and tagtype = '$tagtype'"; break;
			case 'enrollment': $sqlstring = "select tag from tags where enrollment_id = '$id' and tagtype = '$tagtype'"; break;
			case 'subject': $sqlstring = "select tag from tags where subject_id = '$id' and tagtype = '$tagtype'"; break;
			case 'analysis': $sqlstring = "select tag from tags where analysis_id = '$id' and tagtype = '$tagtype'"; break;
			case 'pipeline': $sqlstring = "select tag from tags where pipeline_id = '$id' and tagtype = '$tagtype'"; break;
		}
		
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQLTable($result);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$tags[] = $row['tag'];
		}
		
		return array_unique($tags);
	}


	/* -------------------------------------------- */
	/* ------- SetTags ---------------------------- */
	/* -------------------------------------------- */
	function SetTags($idtype, $tagtype, $id, $tags, $modality='') {
		
		if (count($tags) > 1) {
			/* trim all the tags */
			$tags = array_map("trim", $tags);
		
			/* remove duplicates */
			$tags = array_unique($tags, SORT_STRING);
		
			/* remove tags that are NULL, FALSE, or empty strings */
			$tags = array_filter($tags, 'strlen');
		}
		
		/* start a transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* delete any old tags */
		switch ($idtype) {
			case 'series': $sqlstring = "delete from tags where series_id = '$id' and modality = '$modality' and tagtype = '$tagtype'"; break;
			case 'study': $sqlstring = "delete from tags where study_id = '$id' and tagtype = '$tagtype'"; break;
			case 'enrollment': $sqlstring = "delete from tags where enrollment_id = '$id' and tagtype = '$tagtype'"; break;
			case 'subject': $sqlstring = "delete from tags where subject_id = '$id' and tagtype = '$tagtype'"; break;
			case 'analysis': $sqlstring = "delete from tags where analysis_id = '$id' and tagtype = '$tagtype'"; break;
			case 'pipeline': $sqlstring = "delete from tags where pipeline_id = '$id' and tagtype = '$tagtype'"; break;
		}
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		foreach ($tags as $tag) {
			$tag = mysqli_real_escape_string($GLOBALS['linki'], $tag);
			switch ($idtype) {
				case 'series': $sqlstring = "insert ignore into tags (tagtype, series_id, modality, tag) values ('$tagtype', '$id', '$modality', '$tag')"; break;
				case 'study': $sqlstring = "insert ignore into tags (tagtype, study_id, tag) values ('$tagtype', '$id', '$tag')"; break;
				case 'enrollment': $sqlstring = "insert ignore into tags (tagtype, enrollment_id, tag) values ('$tagtype', '$id', '$tag')"; break;
				case 'subject': $sqlstring = "insert ignore into tags (tagtype, subject_id, tag) values ('$tagtype', '$id', '$tag')"; break;
				case 'analysis': $sqlstring = "insert ignore into tags (tagtype, analysis_id, tag) values ('$tagtype', '$id', '$tag')"; break;
				case 'pipeline': $sqlstring = "insert ignore into tags (tagtype, pipeline_id, tag) values ('$tagtype', '$id', '$tag')"; break;
			}
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		/* commit the transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayTags ------------------------ */
	/* -------------------------------------------- */
	function DisplayTags($tags, $idtype, $tagtype) {
		$html = "";
		foreach ($tags as $tag) {
			$html .= "<span class='tag'><a href='tags.php?action=displaytag&idtype=$idtype&tagtype=$tagtype&tag=$tag' title='Show all $idtype"."s with the <i>$tag</i> tag and are [$tagtype]'>$tag</a></span>";
		}
		return $html;
	}

	
	/* -------------------------------------------- */
	/* ------- GetAnalysisPath -------------------- */
	/* -------------------------------------------- */
	function GetAnalysisPath($analysisid) {
		
		/* check for valid analysis ID */
		if (!ValidID($analysisid,'Analysis ID')) { return; }
		
		$sqlstring = "select d.uid, b.study_num, e.pipeline_name, e.pipeline_level from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = $analysisid";
		//echo "[$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$pipelinename = $row['pipeline_name'];
		$pipelinelevel = $row['pipeline_level'];

		if ($pipelinelevel == 1) {
			$datapath = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
		}
		elseif ($pipelinelevel == 2) {
			$datapath = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename";
		}
		
		return $datapath;
	}

	
	/* -------------------------------------------- */
	/* ------- MoveStudyToSubject ----------------- */
	/* -------------------------------------------- */
	function MoveStudyToSubject($studyid, $newuid) {
		$studyid = mysqli_real_escape_string($GLOBALS['linki'], $studyid);
		$newuid = trim(mysqli_real_escape_string($GLOBALS['linki'], $newuid));
	
		echo "<ol>";
		echo "<li>Inside MoveStudyToSubject($studyid, $newuid)</li>";
		
		/* get the enrollment_id, subject_id, project_id, and uid from the current subject/study */
		$sqlstring = "select a.uid, a.subject_id, b.enrollment_id, b.project_id, c.study_num, c.study_datetime from subjects a left join enrollment b on a.subject_id = b.subject_id left join studies c on b.enrollment_id = c.enrollment_id where c.study_id = $studyid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$olduid = $row['uid'];
		$oldenrollmentid = $row['enrollment_id'];
		$oldsubjectid = $row['subject_id'];
		$oldprojectid = $row['project_id'];
		$oldstudynum = $row['study_num'];
		$oldstudydatetime = $row['study_datetime'];
		
		$now = time();
		$studytime = strtotime($oldstudydatetime);
		
		if (($now - $studytime) < 86400) {
			?>
			<li><b style="color: red">This study was collected in the past 24 hours<br>The study may not be completely archived, so no changes can be made until 1 day after the study's start time</b>
			<?
			return;
		}
		
		echo "<li>Got rowIDs from current subject/study: [$sqlstring]<br>";
		
		//PrintVariable($row);
	
		/* get subjectid from UID */
		$sqlstring = "select subject_id from subjects where uid = '$newuid'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$newsubjectid = $row['subject_id'];
		
		if ($newsubjectid == '') {
			?><li><b style="color: red">The destination UID [<?=$newuid?>] was not found</b><?
			return;
		}
		
		echo "<li>Got new subjectid: $newsubjectid [$sqlstring]<br>";
		
		/* check if the new subject is enrolled in the project, if not, enroll them */
		$sqlstring = "select * from enrollment where subject_id = $newsubjectid and project_id = '$oldprojectid'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$newenrollmentid = $row['enrollment_id'];
			$enrollgroup = $row['enroll_subgroup'];
			echo "<li>Selected existing row to get new enrollment id: $newenrollmentid [$sqlstring]<br>";
		}
		else {
			$sqlstring = "insert into enrollment (subject_id, project_id, enroll_startdate) values ($newsubjectid, $oldprojectid, now())";
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
			$newenrollmentid = mysqli_insert_id($GLOBALS['linki']);
			echo "<li>Inserted row to get new enrollment id: $newenrollmentid [$sqlstring]<br>";
		}
		
		/* get the next study number for the new subject */
		$sqlstring = "SELECT max(a.study_num) 'maxstudynum' FROM studies a left join enrollment b on a.enrollment_id = b.enrollment_id  WHERE b.subject_id = $newsubjectid";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$newstudynum = $row['maxstudynum'] + 1;
		//$newstudynum = mysqli_num_rows($result) + 1;
		echo "<li>Got new study number: $newstudynum [$sqlstring]<br>";
		
		/* change the enrollment_id associated with the studyid */
		$sqlstring = "update studies set enrollment_id = $newenrollmentid, study_num = $newstudynum where study_id = $studyid";
		echo "<li>Change enrollment ID of the study [$sqlstring]<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* move the alternate IDs from the old to new enrollment */
		//$sqlstring = "update ignore subject_altuid set enrollment_id = $newenrollmentid, subject_id = $newsubjectid where enrollment_id = $oldenrollmentid and subject_id = $oldsubjectid";
		//echo "<li>Move alternate IDs from old to new enrollment [$sqlstring]<br>";
		//$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		/* copy the data, don't move in case there is a problem */
		$oldpath = $GLOBALS['cfg']['archivedir'] . "/$olduid/$oldstudynum";
		$newpath = $GLOBALS['cfg']['archivedir'] . "/$newuid/$newstudynum";
		
		$systemstring = "mkdir -pv $newpath 2>&1";
		$copyresults = "[$systemstring] " . shell_exec($systemstring) . "\n";
		
		$systemstring = "mkdir -pv $oldpath 2>&1";
		$copyresults .= "[$systemstring] " . shell_exec($systemstring) . "\n";
		
		if (!file_exists($oldpath)) {
			?><li><b style="color: red">The original path [<?=$oldpath?>] does not exist</b><?
		}
		if (!file_exists($newpath)) {
			?><li><b style="color: red">The new path [<?=$newpath?>] does not exist</b><?
		}
		
		//$systemstring = "mv -vuf $oldpath/* $newpath/ 2>&1";
		echo "<li>Moving data within archive directory (may take a while): <tt>$systemstring</tt>";
		$systemstring = "rsync -rtuv $oldpath/* $newpath 2>&1";
		$copyresults .= "[Running $systemstring] " . shell_exec($systemstring) . "\n";
		$systemstring = "rsync -rtuv $newpath/* $oldpath 2>&1";
		$copyresults .= "[Running $systemstring] " . shell_exec($systemstring) . "\n";
		echo "<pre><tt>$copyresults</tt></pre>";

		$copyresults = mysqli_real_escape_string($GLOBALS['linki'], $copyresults);
		/* insert a changelog */
		$instanceid = $GLOBALS['instanceid'];
		$userid = $GLOBALS['userid'];
		$sqlstring = "insert into changelog (performing_userid, affected_userid, affected_instanceid1, affected_instanceid2, affected_siteid1, affected_siteid2, affected_projectid1, affected_projectid2, affected_subjectid1, affected_subjectid2, affected_enrollmentid1, affected_enrollmentid2, affected_studyid1, affected_studyid2, affected_seriesid1, affected_seriesid2, change_datetime, change_event, change_desc) values ('$userid', '', '$instanceid', '', '', '', '$oldprojectid', '$oldprojectid', '$oldsubjectid', '$newsubjectid', '$oldenrollmentid', '$newenrollmentid', '$studyid', '$studyid', '', '', now(), 'MoveStudyFromSubject1toSubject2', 'Moved study [$olduid$oldstudynum] to [$newuid$newstudynum]. Results [$copyresults]')";
		echo "<li>Insert changelog [$sqlstring]<br>";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		echo "<li><b style='color: red'>Study [$olduid$oldstudynum] moved to subject [$newuid]</b>";
		
		echo "</ol>";
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayProjectsMenu ---------------- */
	/* -------------------------------------------- */
	function DisplayProjectsMenu($menuitem, $id) {
		switch ($menuitem) {
			case "assessments":
				break;
			case "info":
				break;
			case "subjects":
				?>
				<b>Options:</b> <a href="projects.php?action=displaydemographics&id=<?=$id?>" style="font-weight: normal">View table</a>
				<?
				break;
			case "studies":
				break;
			case "checklist":
				?>
				<b>Options:</b> <a href="projectchecklist.php?action=editchecklist&projectid=<?=$id?>" style="font-weight: normal">Edit checklist</a>
				<?
				break;
			case "mrqc":
				?>
				<?
				break;
		}
	}

	
	/* -------------------------------------------- */
	/* ------- between ---------------------------- */
	/* -------------------------------------------- */
	/* returns true if val is between min and max   */
	/* returns unknown (-1) if val, min, or max     */
	/* are blank                                    */
	function between($val, $min, $max) {
		//echo "between($min - $val - $max)<br>";
		
		if ($val == "") return -1;
		if ($min == "") return -1;
		if ($max == "") return -1;
		
		if (($val >= $min) && ($val <= $max))
			return 1;
		else
			return 0;
	}
	
	
	/* -------------------------------------------- */
	/* ------- median ----------------------------- */
	/* -------------------------------------------- */
	function median()
	{
		$args = func_get_args();

		switch(func_num_args())
		{
			case 0:
				trigger_error('median() requires at least one parameter',E_USER_WARNING);
				return false;
				break;

			case 1:
				$args = array_pop($args);
				// fallthrough

			default:
				if(!is_array($args)) {
					trigger_error('median() requires a list of numbers to operate on or an array of numbers',E_USER_NOTICE);
					return false;
				}

				sort($args);
				
				$n = count($args);
				$h = intval($n / 2);

				if($n % 2 == 0) { 
					$median = ($args[$h] + $args[$h-1]) / 2; 
				} else { 
					$median = $args[$h]; 
				}

				break;
		}
		
		return $median;
	}

	
	/* -------------------------------------------- */
	/* ------- mean ------------------------------- */
	/* -------------------------------------------- */
	function mean($arr)
	{
	   if (!is_array($arr)) return false;

	   return array_sum($arr)/count($arr);
	}
	
	
	/* -------------------------------------------- */
	/* ------- PrintBeta -------------------------- */
	/* -------------------------------------------- */
	function PrintBeta() {
		?>
		<span style="color: gray; font-size:8pt; padding: 0px 3px; font-weight: normal;">BETA</span>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- find_all_files --------------------- */
	/* -------------------------------------------- */
	function find_all_files($dir) 
	{ 
		$root = scandir($dir);
		foreach($root as $value) 
		{ 
			if($value === '.' || $value === '..') {continue;} 
			if(is_file("$dir/$value")) {$result[]="$dir/$value";continue;}
			if (is_array(find_all_files("$dir/$value"))) {
				foreach(find_all_files("$dir/$value") as $value)
				{
					$result[]=$value; 
				}
			}
		} 
		return $result; 
	}

	
	// Function to calculate square of value - mean
	function sd_square($x, $mean) { return pow($x - $mean,2); }

	
	// Function to calculate standard deviation (uses sd_square)    
	function sd($array) {
		// square root of sum of squares devided by N-1
		return sqrt(array_sum(array_map("sd_square", $array, array_fill(0,count($array), (array_sum($array) / count($array)) ) ) ) / (count($array)-1) );
	}
	
	
	function natksort($array) {
		// Like ksort but uses natural sort instead
		$keys = array_keys($array);
		natsort($keys);
		foreach ($keys as $k)
			$new_array[$k] = $array[$k];
		
		return $new_array;
	}	

	/**
	 * Error handler, passes flow over the exception logger with new ErrorException.
	 */
	function log_error( $num, $str, $file, $line, $context = null )
	{
		log_exception( new ErrorException( $str, 0, $num, $file, $line ) );
	}

	/**
	 * Uncaught exception handler.
	 */
	function log_exception( Exception $e )
	{
		//global $config;
		
		//if ( $config["debug"] == true )
		//{
			print "<div style='text-align: center;'>";
			print "<h2 style='color: rgb(190, 50, 50);'>Exception Occured:</h2>";
			print "<table style='width: 800px; display: inline-block;'>";
			print "<tr style='background-color:rgb(230,230,230);'><th style='width: 80px;'>Type</th><td>" . get_class( $e ) . "</td></tr>";
			print "<tr style='background-color:rgb(240,240,240);'><th>Message</th><td>{$e->getMessage()}</td></tr>";
			print "<tr style='background-color:rgb(230,230,230);'><th>File</th><td>{$e->getFile()}</td></tr>";
			print "<tr style='background-color:rgb(240,240,240);'><th>Line</th><td>{$e->getLine()}</td></tr>";
			print "</table></div>";
		//}
		//else
		//{
			//$message = "Type: " . get_class( $e ) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()};";
			//file_put_contents( $config["app_dir"] . "/tmp/logs/exceptions.log", $message . PHP_EOL, FILE_APPEND );
			//header( "Location: {$config["error_page"]}" );
		//}
		
		//exit();
	}
	
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }

    switch ($errno) {
    case E_USER_ERROR:
        echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        echo "  Fatal error on line $errline in file $errfile";
        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
        echo "Aborting...<br />\n";
        exit(1);
        break;

    case E_USER_WARNING:
        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
        break;

    case E_USER_NOTICE:
        echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
        break;

    //default:
    //   echo "Unknown error type: [$errno] $errstr<br />\n";
    //    break;
    }

    /* Don't execute PHP internal error handler */
    return true;
}

	/**
	 * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
	 */
	function check_for_fatal()
	{
		$error = error_get_last();
		if ( $error["type"] == E_ERROR )
			log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
	}

	register_shutdown_function( "check_for_fatal" );
	set_error_handler( "myErrorHandler" );
	//set_exception_handler( "log_exception" );
	//ini_set( "display_errors", "off" );
	//error_reporting( E_ALL );	
	
	
	/* -------------------------------------------- */
	/* ------- ArrayToTextTable ------------------- */
	/* -------------------------------------------- */
	/**
	 * Array to Text Table Generation Class
	 *
	 * @author Tony Landis <tony@tonylandis.com>
	 * @link http://www.tonylandis.com/
	 * @copyright Copyright (C) 2006-2009 Tony Landis
	 * @license http://www.opensource.org/licenses/bsd-license.php
	 */
	class ArrayToTextTable
	{
		/** 
		 * @var array The array for processing
		 */
		private $rows;

		/** 
		 * @var int The column width settings
		 */
		private $cs = array();

		/**
		 * @var int The Row lines settings
		 */
		private $rs = array();

		/**
		 * @var int The Column index of keys
		 */
		private $keys = array();

		/**
		 * @var int Max Column Height (returns)
		 */
		private $mH = 1;

		/**
		 * @var int Max Row Width (chars)
		 */
		private $mW = 60;

		private $head  = false;
		private $pcen  = "+";
		private $prow  = "-";
		private $pcol  = "|";
		
		
		/** Prepare array into textual format
		 *
		 * @param array $rows The input array
		 * @param bool $head Show heading
		 * @param int $maxWidth Max Column Height (returns)
		 * @param int $maxHeight Max Row Width (chars)
		 */
		public function ArrayToTextTable($rows)
		{
			$this->rows =& $rows;
			$this->cs=array();
			$this->rs=array();
	 
			if(!$xc = count($this->rows)) return false; 
			$this->keys = array_keys($this->rows[0]);
			$columns = count($this->keys);
			
			for($x=0; $x<$xc; $x++)
				for($y=0; $y<$columns; $y++)    
					$this->setMax($x, $y, $this->rows[$x][$this->keys[$y]]);
		}
		
		/**
		 * Show the headers using the key values of the array for the titles
		 * 
		 * @param bool $bool
		 */
		public function showHeaders($bool)
		{
		   if($bool) $this->setHeading(); 
		} 
		
		/**
		 * Set the maximum width (number of characters) per column before truncating
		 * 
		 * @param int $maxWidth
		 */
		public function setMaxWidth($maxWidth)
		{
			$this->mW = (int) $maxWidth;
		}
		
		/**
		 * Set the maximum height (number of lines) per row before truncating
		 * 
		 * @param int $maxHeight
		 */
		public function setMaxHeight($maxHeight)
		{
			$this->mH = (int) $maxHeight;
		}
		
		/**
		 * Prints the data to a text table
		 *
		 * @param bool $return Set to 'true' to return text rather than printing
		 * @return mixed
		 */
		public function render($return=false)
		{
			#if($return) ob_start(null, 0, true); 
	  
			$s = "";
			
			$s .= $this->printLine();
			$s .= $this->printHeading();
			
			$rc = count($this->rows);
			for($i=0; $i<$rc; $i++) $s .= $this->printRow($i);
			
			$s .= $this->printLine(false);

			return $s;
			
			#if($return) {
			#	$contents = ob_get_contents();
			#	ob_end_clean();
			#	return $contents;
			#}
		}

		private function setHeading()
		{
			$data = array();  
			foreach($this->keys as $colKey => $value)
			{ 
				$this->setMax(false, $colKey, $value);
				$data[$colKey] = strtoupper($value);
			}
			if(!is_array($data)) return false;
			$this->head = $data;
		}

		private function printLine($nl=true)
		{
			$s = "";
			
			$s .= $this->pcen;
			foreach($this->cs as $key => $val)
				$s .= $this->prow .
					str_pad('', $val, $this->prow, STR_PAD_RIGHT) .
					$this->prow .
					$this->pcen;
			if($nl) $s .= "\n";
			return $s;
		}

		private function printHeading()
		{
			$s = "";
			
			if(!is_array($this->head)) return false;

			$s = $this->pcol;
			foreach($this->cs as $key => $val)
				$s .= ' '.
					str_pad($this->head[$key], $val, ' ', STR_PAD_BOTH) .
					' ' .
					$this->pcol;

			$s .= "\n";
			$s .= $this->printLine();
		}

		private function printRow($rowKey)
		{
			$s = "";
			
			// loop through each line
			for($line=1; $line <= $this->rs[$rowKey]; $line++)
			{
				$s .= $this->pcol;  
				for($colKey=0; $colKey < count($this->keys); $colKey++)
				{ 
					$s .= " ";
					$s .= str_pad(substr($this->rows[$rowKey][$this->keys[$colKey]], ($this->mW * ($line-1)), $this->mW), $this->cs[$colKey], ' ', STR_PAD_RIGHT);
					$s .= " " . $this->pcol;          
				}  
				$s .= "\n";
			}
			return $s;
		}

		private function setMax($rowKey, $colKey, &$colVal)
		{ 
			$w = strlen($colVal);
			$h = 1;
			if($w > $this->mW)
			{
				$h = ceil($w % $this->mW);
				if($h > $this->mH) $h=$this->mH;
				$w = $this->mW;
			}
	 
			if(!isset($this->cs[$colKey]) || $this->cs[$colKey] < $w)
				$this->cs[$colKey] = $w;

			if($rowKey !== false && (!isset($this->rs[$rowKey]) || $this->rs[$rowKey] < $h))
				$this->rs[$rowKey] = $h;
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- diff ------------------------------- */
	/* -------------------------------------------- */
	function diff($old, $new){
		$matrix = array();
		$maxlen = 0;
		foreach($old as $oindex => $ovalue){
			$nkeys = array_keys($new, $ovalue);
			foreach($nkeys as $nindex){
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
					$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
				if($matrix[$oindex][$nindex] > $maxlen){
					$maxlen = $matrix[$oindex][$nindex];
					$omax = $oindex + 1 - $maxlen;
					$nmax = $nindex + 1 - $maxlen;
				}
			}   
		}
		if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
		return array_merge(
			diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
			array_slice($new, $nmax, $maxlen),
			diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
	}

	
	/* -------------------------------------------- */
	/* ------- GetStyledDiff ---------------------- */
	/* -------------------------------------------- */
	function GetStyledDiff($old, $new){
		$ret = '';
		$diff = diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
		foreach($diff as $k){
			if(is_array($k))
				$ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
					(!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
			else $ret .= $k . ' ';
		}
		return $ret;
	}
	

	/* -------------------------------------------- */
	/* ------- array_msort ------------------------ */
	/* -------------------------------------------- */
	function array_msort($array, $cols)
	{
		$colarr = array();
		foreach ($cols as $col => $order) {
			$colarr[$col] = array();
			foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
		}
		$eval = 'array_multisort(';
		foreach ($cols as $col => $order) {
			$eval .= '$colarr[\''.$col.'\'],'.$order.',';
		}
		$eval = substr($eval,0,-1).');';
		eval($eval);
		$ret = array();
		foreach ($colarr as $col => $arr) {
			foreach ($arr as $k => $v) {
				$k = substr($k,1);
				if (!isset($ret[$k])) $ret[$k] = $array[$k];
				$ret[$k][$col] = $array[$k][$col];
			}
		}
		return $ret;

	}


	/* -------------------------------------------- */
	/* ------- startsWith ------------------------- */
	/* -------------------------------------------- */
	function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}


	/* -------------------------------------------- */
	/* ------- endsWith --------------------------- */
	/* -------------------------------------------- */
	function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0)
			return true;

		return (substr($haystack, -$length) === $needle);
	}

	/* -------------------------------------------- */
	/* ------- contains --------------------------- */
	/* -------------------------------------------- */
	function contains($haystack, $needle) {
		if (strpos($haystack, $needle) !== false)
			return true;
		else
			return false;
	}
	
	
	/* -------------------------------------------- */
	/* ------- StartHTMLTable --------------------- */
	/* -------------------------------------------- */
	function StartHTMLTable($cols, $class) {
		?>
		<table class="<?=$class?>">
			<thead>
				<tr>
				<?
				foreach ($cols as $col) {
					?>
					<th><?=$col?></th>
					<?
				}
				?>
				</tr>
			</thead>
			<tbody>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- EndHTMLTable ----------------------- */
	/* -------------------------------------------- */
	function EndHTMLTable() {
		?>
			</tbody>
		</table>
		<?
	}


	/* -------------------------------------------- */
	/* ------- RunSystemChecks -------------------- */
	/* -------------------------------------------- */
	function RunSystemChecks() {
		$problems = array();
		
		/* check if the nidb executable has execute permissions */
		$nidbexe = $GLOBALS['cfg']['scriptdir'] . "/bin/nidb";
		if (!is_executable($nidbexe))
			array_push($problems, "<tt>$nidbexe</tt> executable does not have execute permissions");
		
		/* check if any disks are full */
		
		/* check if load is above 100% */
		
		/* check if import, export, or fileio modules are disabled */
		
		if (count($problems) > 0) {
			echo "<ul><li><b>System error(s). Contact NiDB administrator";
			foreach ($problems as $errmsg) {
				?><li style="padding: 5px"><span style="background-color: red; color: white; padding: 3px;"><?=$errmsg?></span><?
			}
			echo "</ul>";
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetNiDBVersion --------------------- */
	/* -------------------------------------------- */
	function GetNiDBVersion() {
		
		/* check if the nidb executable script exists */
		$nidbsh = "cd /nidb/programs/bin; LD_LIBRARY_PATH=\$PWD; export LD_LIBRARY_PATH; " . $GLOBALS['cfg']['scriptdir'] . "/bin/./nidb.sh -v";
		$nidbver = shell_exec($nidbsh);
		$nidbver = trim(str_replace("Neuroinformatics Database (NiDB) ", "", $nidbver));
		
		//echo "Running [$nidbsh] returned [$nidbver]<br>";
		return $nidbver;
	}

	/* -------------------------------------------- */
	/* ------- isEmpty ---------------------------- */
	/* -------------------------------------------- */
	function isEmpty($s) {
		if (trim($s) == "")
			return true;
		else
			return false;
	}
	
	
	/* -------------------------------------------- */
	/* ------- StartSQLTransaction ---------------- */
	/* -------------------------------------------- */
	function StartSQLTransaction() {
		/* start a transaction */
		$sqlstring = "start transaction";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}


	/* -------------------------------------------- */
	/* ------- CommitSQLTransaction --------------- */
	/* -------------------------------------------- */
	function CommitSQLTransaction() {
		/* commit transaction */
		$sqlstring = "commit";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
	}
	
	
	/* -------------------------------------------- */
	/* ------- ShellWords ------------------------- */
	/* -------------------------------------------- */
	function ShellWords($line) {
        $line .= ' ';

		//PrintVariable($line);
		
        $pattern = '/\G\s*(?>([^\s\\\'\"]+)|\'([^\']*)\'|"((?:[^\"\\\\]|\\.)*)"|(\\.?)|(\S))(\s|\z)?/m';
        preg_match_all($pattern, $line, $matches, PREG_SET_ORDER);
		//PrintVariable($matches);

        $words = array();
        $field = '';

        foreach ($matches as $set) {
            # Index #0 is the full match.
            array_shift($set);

			//echo "$word, $sq, $dq, $esc, $garbage, $sep<br>";
            @list($word, $sq, $dq, $esc, $garbage, $sep) = $set;

            if ($garbage) {
				//echo "[$garbage]<br>";
                throw new \UnexpectedValueException("Unmatched double quote: '$line'");
            }

			if (trim($dq) != "")
				$field = $dq;
			elseif (trim($sq) != "")
				$field = $sq;
			elseif (trim($word) != "")
				$field = $word;
            //$field .= ($dq ?: $sq ?: $word);
			//echo "[$field]<br>";
            //if (strlen($sep) > 0) {
				if (trim($field) > "") {
					$words[] = $field;
					$field = '';
				}
            //}
        }

        return $words;
    }


	/* -------------------------------------------- */
	/* ------- GetSQLComparison ------------------- */
	/* -------------------------------------------- */
	function GetSQLComparison($c) {
		$comp = "";
		$num = 0;
		
		$c = preg_replace('/\s/', '', $c);

		if (substr($c,0,2) == '>=') {
			$comp = ">=";
			$num = substr($c,2);
		}
		elseif (substr($c,0,2) == '<=') {
			$comp = "<=";
			$num = substr($c,2);
		}
		elseif (substr($c,0,1) == '>') {
			$comp = ">";
			$num = substr($c,1);
		}
		elseif (substr($c,0,1) == '<') {
			$comp = "<";
			$num = substr($c,1);
		}
		elseif (substr($c,0,1) == '~') {
			$comp = "<>";
			$num = substr($c,1);
		}
		else {
			$comp = "=";
			$num = $c;
		}
		
		return array($comp, $num);
	}
	
?>
