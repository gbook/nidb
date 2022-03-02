<?
 // ------------------------------------------------------------------------------
 // NiDB functions.php
 // Copyright (C) 2004 - 2021
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
			<br>
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
	function MySQLiQuery($sqlstring, $file, $line, $continue=true) {

		$origsql = $sqlstring;
		
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
				$errormsg = mysqli_error($GLOBALS['linki']);
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
			
			if (($GLOBALS['cfg']['hideerrors']) && ($continue == false)) {
				die("<div width='100%' style='border:1px solid red; background-color: #FFC; margin:10px; padding:10px; border-radius:5px; text-align: center'><b>Internal NiDB error.</b><br>The site administrator has been notified. Contact the administrator &lt;".$GLOBALS['cfg']['adminemail']."&gt; if you can provide additional information that may have led to the error<br><br><img src='images/topmen.png'></div>");
			}
			else {
				?>
				<div class="ui inverted yellow segment" style="padding:4px;">
					<div class="ui segment">
						<h1 class="ui header">
							<i class="red exclamation circle icon"></i>
							<div class="content">
								SQL error
								<div class="sub header">Contact your NiDB administrator</div>
							</div>
						</h1>
						<div class="ui grid">
							<div class="four wide right aligned column">
								<h3 class="header">Query</h3>
							</div>
							<div class="twelve wide column"> <code><?=$file?></code> (line <tt><?=$line?></tt>)</div>
							
							<div class="four wide right aligned column">
								<h3 class="header">Datetime</h3>
							</div>
							<div class="twelve wide column"><?=$datetime?></div>
							
							<div class="four wide right aligned column">
								<h3 class="header">Error</h3>
							</div>
							<div class="twelve wide column"><?=$errormsg?></div>
							
							<div class="four wide right aligned column">
								<h3 class="header">Username</h3>
							</div>
							<div class="twelve wide column"><?=$username?></div>

							<div class="four wide right aligned column">
								<h3 class="header">POST</h3>
							</div>
							<div class="twelve wide column" style="max-height: 400px; overflow: auto"><pre><? echo print_r($_POST, true)?></pre></div>

							<div class="four wide right aligned column">
								<h3 class="header">GET</h3>
							</div>
							<div class="twelve wide column" style="max-height: 400px; overflow: auto"><pre><? echo print_r($_GET, true)?></pre></div>

							<div class="four wide right aligned column">
								<h3 class="header">SESSION</h3>
							</div>
							<div class="twelve wide column" style="max-height: 400px; overflow: auto"><pre><? echo print_r($_SESSION, true)?></pre></div>

							<div class="four wide right aligned column">
								<h3 class="header">SERVER</h3>
							</div>
							<div class="twelve wide column" style="max-height: 400px; overflow: auto"><pre><? echo print_r($_SERVER, true)?></pre></div>
						</div>
					</div>
				</div>
				<?
			}
			$ret['error'] = 1;
			$ret['errormsg'] = $errormsg;
			$ret['sql'] = $origsql;
		}
		else {
			$ret = $result;
		}
		
		return $ret;
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
	/* ------- GetUsernameFromID ------------------ */
	/* -------------------------------------------- */
	function GetUsernameFromID($id) {
		$sqlstring = "select username from users where user_id = '$id'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$name = $row['username'];
		return $name;
	}
	
	
	/* -------------------------------------------- */
	/* ------- isAdmin ---------------------------- */
	/* -------------------------------------------- */
	function isAdmin() {
		$username = $GLOBALS['username'];
		$sqlstring = "select user_isadmin from users where username = '$username'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		if ($row['user_isadmin'] == "1")
			return true;
		else
			return false;
	}


	/* -------------------------------------------- */
	/* ------- isSiteAdmin ------------------------ */
	/* -------------------------------------------- */
	function isSiteAdmin() {
		$username = $GLOBALS['username'];
		$sqlstring = "select user_issiteadmin from users where username = '$username'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		if ($row['user_issiteadmin'] == "1")
			return true;
		else
			return false;
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
		$seriespath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum";
		$qapath = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum/qa";
		return array($path, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid);
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
	/* ------- GetSeriesInfo ---------------------- */
	/* -------------------------------------------- */
	function GetSeriesInfo($id, $modality) {
		$sqlstring = "select * from $modality"."_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join projects e on c.project_id = e.project_id where a.$modality"."series_id = '$id'";

		//$sqlstring = "select * from studies b left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join projects e on c.project_id = e.project_id where b.study_id = $id";
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
	/* ------- GetStudyAge ------------------------ */
	/* -------------------------------------------- */
	function GetStudyAge($dob, $studyage, $studydate) {
		
		//$studydate = str_replace(" ", "T", $studydate);

		# calculate study age
		if (($dobUnix = strtotime($dob)) === false) {
			//echo "Bad date/time format [$dob]<br>";
			$calculatedStudyAge = null;
		}
		else {
			if (($studyUnix = strtotime($studydate)) === false) {
				//echo "Bad date/time format [$studydate]<br>";
				$calculatedStudyAge = null;
			}
			else {
				$calculatedStudyAge = ($studyUnix - $dobUnix)/31536000;
				if (($calculatedStudyAge <= 0) || ($calculatedStudyAge > 150))
					$calculatedStudyAge = null;
			}
				
		}

		# check for valid stored study age
		if (($studyage > 0) && ($studyage < 150))
			$storedStudyAge = $studyage;
		else
			$storedStudyAge = null;
		
		return array($storedStudyAge, $calculatedStudyAge);
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
	
		//PrintVariable($subjectid);
		//PrintVariable($projectid);
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
			Notice("Subject [$uid] has more than one enrollment in the same project [$projectname]. Using the first enrollment to get the primary ID.");
		}
		elseif (mysqli_num_rows($result) == 1) {
			$sqlstring = "select * from subject_altuid where subject_id = '$subjectid' and enrollment_id = $enrollmentid order by isprimary desc limit 1";
			$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
			//PrintSQL($sqlstring);
			$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
			return $row['altuid'];
		}
		else
			return "";
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
	function DisplayProjectSelectBox($currentinstanceonly, $varname, $idname, $classname, $multiselect, $selectedids, $width=350, $height=30, $required=false) {

		if (!is_array($selectedids))
			$selectedids = array($selectedids);
		
		if (in_array(0, $selectedids)) { $selected = "selected"; } else { $selected = ""; }
		
		if ($required)
			$required = "required";
		else
			$required = "";
		?>
		<select name="<?=$varname?>" class="<?=$classname?>" style="width:<?=$width?>px;height:<?=$height?>px" <? if ($multiselect) { echo "multiple"; } ?> <?=$required?>>
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
		//$arr = array_filter($arr);
		
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
		
		$errormsgs = array();
		$noticemsgs = array();
		
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
						//list($path, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid,'mr');
						list($path, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid,'mr');
						
						//$qapath = "$path/qa";
						if (($uid == "") || ($studynum == "") || ($studyid == "") || ($subjectid == "")) {
							$errormsgs[] = "Could not delete QA data. One of the following is blank uid[$uid] studynum[$studynum] studyid[$studyid] subjectid[$subjectid]";
						}
						else {
							/* check if the path is valid */
							if (file_exists($qapath)) {
								$systemstring = "rm -rv $qapath";
								$noticemsgs[] = "Deleted <code>$qapath</code>";
							}
							else {
								$noticemsgs[] = "<code>$qapath</code> does not exist";
							}
						}
						
						$noticemsgs[] = "QC deleted for seriesID [$qcmoduleseriesid]";
					}
					else {
						$errormsgs[] = "qcmoduleseries_id was blank";
					}
				}
			}
			else {
				$errormsgs[] = "Invalid MR series ID";
			}
		}

		if (count($errormsgs) > 0) {
			$errormsg = "<ul>";
			foreach ($errormsgs as $m) {
				$errormsg .= "<li>" . $m;
			}
			$errormsg .= "</ul>";
			Error($errormsg);
		}
		
		if (count($noticemsgs) > 0) {
			$noticemsg = "<ul>";
			foreach ($noticemsgs as $m) {
				$noticemsg .= "<li>" . $m;
			}
			$noticemsg .= "</ul>";
			Notice($noticemsg);
		}
	}

	
	/* -------------------------------------------- */
	/* ------- UpdateMostRecent ------------------- */
	/* -------------------------------------------- */
	function UpdateMostRecent($subjectid, $studyid, $projectid) {

		if ((trim($subjectid) == '') || ($subjectid == 0)) { $subjectid = 'NULL'; }
		if ((trim($studyid) == '') || ($studyid == 0)) { $studyid = 'NULL'; }
		if ((trim($projectid) == '') || ($projectid == 0)) { $projectid = 'NULL'; }
		
		/* get userid */
		$username = $_SESSION['username'];
		$sqlstring = "select user_id from users where username = '$username'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$userid = $row['user_id'];
		
		/* insert the new most recent entry */
		$sqlstring = "insert ignore into mostrecent (user_id, subject_id, study_id, project_id, mostrecent_date) values ($userid, $subjectid, $studyid, $projectid, now())";
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
		
		$projectids = array_filter($projectids);
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
	/* ------- DisplayPermissions ----------------- */
	/* -------------------------------------------- */
	function DisplayPermissions($perms) {
		
		$msg = "";
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
				
				if (($admin == '') && ($admin == '') && ($admin == '') && ($admin == '') && ($admin == '')) {
					$msg .= "<div class='item'>No permissions to access $projectname</div>";
				}
				else {
					$msg .= "<div class='item'><b>$projectname</b>";
					
					if ($admin != '')
						$msg .= " <div class='ui mini red label'>$admin</div> ";
					if ($modifyphi != '')
						$msg .= " <div class='ui mini blue label'>$modifyphi</div> ";
					if ($viewphi != '')
						$msg .= " <div class='ui mini blue label'>$viewphi</div> ";
					if ($modifydata != '')
						$msg .= " <div class='ui mini blue label'>$modifydata</div> ";
					if ($viewdata != '')
						$msg .= " <div class='ui mini blue label'>$viewdata</div> ";
					
					$msg .= "</div>";
				}
			}
			
			?>
			<div class="ui accordion">
				<div class="title">
					<span style="font-size: smaller; color: gray"><i class="dropdown icon"></i>Your access permissions for this subject</span>
				</div>
				<div class="content">
					<div class="ui list">
					<?=$msg?>
					</div>
				</div>
			</div>
			<?
		}
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
				Error("Invalid ID", "ID value [$var], named [$varname], was not valid");
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
		?>
		<div class="ui blue segment">
		<?
		echo getFormattedSQL($sql);
		?>
		</div>
		<?
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

		return trim($sql_formatted);
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
			$html .= "<a href='tags.php?action=displaytag&idtype=$idtype&tagtype=$tagtype&tag=$tag' class='ui small basic yellow label' title='Show all $idtype"."s with the <i>$tag</i> tag and are [$tagtype]'>$tag</a></span>";
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
	function StartHTMLTable($cols, $class, $id) {
		?>
		<table class="<?=$class?>" id="<?=$id?>">
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
		$errors = array();
		$warnings = array();
		
		/* check if the nidb executable has execute permissions */
		$nidbexe = $GLOBALS['cfg']['nidbdir'] . "/bin/nidb";
		if (!is_executable($nidbexe))
			array_push($errors, "<tt>$nidbexe</tt> executable does not have execute permissions");
		
		/* check if any disks are full */
		
		/* check if load is above 100% */
		
		/* check if import, export, or fileio modules are disabled */
		$sqlstring = "select * from modules where module_name = 'import' and module_isactive = 0";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0)
			array_push($warnings, "<tt>import</tt> module is disabled. New images will not archived");
		
		$sqlstring = "select * from modules where module_name = 'export' and module_isactive = 0";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0)
			array_push($warnings, "<tt>export</tt> module is disabled. Requested exports will not be processed");
		
		$sqlstring = "select * from modules where module_name = 'fileio' and module_isactive = 0";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0)
			array_push($warnings, "<tt>fileio</tt> module is disabled. Any back-end changes will not be performed");
		
		
		if (count($errors) > 0) {
			echo "<br>";
			Error("System Error. Contact NiDB administrator<br> <ul><li>" . implode2("<li>", $errors) . "</ul>");
		}
		if (count($warnings) > 0) {
			echo "<br>";
			Notice("Warning. Contact NiDB administrator<br> <ul><li>" . implode2("<li>", $warnings) . "</ul>");
		}
		else
			return false;
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetNiDBVersion --------------------- */
	/* -------------------------------------------- */
	function GetNiDBVersion() {
		
		/* check if the nidb executable script exists */
		$nidbsh = $GLOBALS['cfg']['nidbdir'] . "/bin/./nidb -v";
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
	/* ------- DuplicateSQLRow -------------------- */
	/* -------------------------------------------- */
	/* newvals is an associative array with fields to be changed, and their new values */
	function DuplicateSQLRow($table, $pk, $id, $newvals) {
		/* setup: $newvals['column'] = newval */
		
		$tmptable = GenerateRandomString(10);
		StartSQLTransaction();

		$sqlstring = "create temporary table $tmptable select * from $table where $pk = $id";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		$sqlstring = "update $tmptable set $pk = NULL";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		
		foreach ($newvals as $col => $val) {
			$sqlstring = "update $tmptable set $col = $val";
			//PrintSQL($sqlstring);
			$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		}
		
		$sqlstring = "select * from $tmptable";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		//PrintSQLTable($result,$url,$orderby,$size,$text=false);
		
		$sqlstring = "insert ignore into $table select * from $tmptable";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$rowid = mysqli_insert_id($GLOBALS['linki']);
		
		$sqlstring = "drop temporary table if exists $tmptable";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);

		CommitSQLTransaction();
		
		return $rowid;
	}


	/* -------------------------------------------- */
	/* ------- ParseCSV --------------------------- */
	/* -------------------------------------------- */
	function ParseCSV($s) {
		
		$lines = explode( "\n", $s );
		$headers = str_getcsv( array_shift( $lines ) );
		$data = array();
		foreach ( $lines as $line ) {
			$row = array();
			foreach ( str_getcsv( $line ) as $key => $field )
				$row[ trim($headers[ trim($key) ]) ] = $field;
			
			$row = array_filter( $row );
			$data[] = $row;
		}
		//PrintVariable($data);

		return $data;
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


	/* -------------------------------------------- */
	/* ------- Error ------------------------------ */
	/* -------------------------------------------- */
	function Error($msg, $close=true) {
		?>
		<div class="ui text container">
			<div class="ui warning message">
				<? if ($close) { ?><i class="close icon"></i> <? } ?>
				<div class="header">Error</div>
				<p><?=$msg?></p>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- Warning ---------------------------- */
	/* -------------------------------------------- */
	function Warning($msg) {
		?>
		<div class="ui text container">
			<div class="ui orange message">
				<i class="close icon"></i>
				<div class="header">Warning</div>
				<p><?=$msg?></p>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- Notice ----------------------------- */
	/* -------------------------------------------- */
	function Notice($msg) {
		?>
		<div class="ui text container">
			<div class="ui info message">
				<i class="close icon"></i>
				<div class="header">Notice</div>
				<p><?=$msg?></p>
			</div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ValidDOB --------------------------- */
	/* -------------------------------------------- */
	function ValidDOB($dob) {
		if (($dobUnix = strtotime($dob)) === false)
			return false;
		if (in_array($dob, array("0000-00-00", "0000-01-01", "1000-01-01", "1776-07-04", "1900-01-01")))
			return false;
		
		return true;
	}


	/* -------------------------------------------- */
	/* ------- EnablePipeline --------------------- */
	/* -------------------------------------------- */
	function EnablePipeline($id) {
		if (!ValidID($id,'Pipeline ID - H')) { return; }
		
		$sqlstring = "update pipelines set pipeline_enabled = 1 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisablePipeline -------------------- */
	/* -------------------------------------------- */
	function DisablePipeline($id) {
		if (!ValidID($id,'Pipeline ID - I')) { return; }
		
		$sqlstring = "update pipelines set pipeline_enabled = 0 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisplayPipelineStatus -------------- */
	/* -------------------------------------------- */
	function DisplayPipelineStatus($pipelinename, $isenabled, $id, $returnpage, $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck) {
		if (!ValidID($id,'Pipeline ID - M')) { return; }

		?>
		<div class="ui container">
			<div class="ui top attached inverted segment">
				<h1 class="ui inverted header"><?=$pipelinename?></h1>
			</div>
			<div class="ui attached segment">
			<? if ($isenabled) { ?>
				<div class="ui header"><a href="<?=$returnpage?>.php?action=disable&returnpage=<?=$returnpage?>&id=<?=$id?>"><i class="big green toggle on icon" title="Pipeline enabled, click to disable"></i></a> Enabled</div>
			<? } else { ?>
				<div class="ui header"><a href="<?=$returnpage?>.php?action=enable&returnpage=<?=$returnpage?>&id=<?=$id?>"><i class="big red toggle off icon" title="Pipeline disabled, click to enable"></i></a> Disabled</div>
			<? } ?>
			</div>
			<? if ($pipeline_status == "running") { ?>
			<div class="ui three bottom attached steps">
				<div class="step">
					<div class="content">
						<div class="title">Start</div>
						<div class="description">Started <?=$pipeline_laststart?></div>
					</div>
				</div>
				<div class="active step">
					<div class="content">
						<div class="title">Running</div>
						<div class="description">Checked in <?=$pipeline_lastcheck?></div>
						<?=$pipeline_statusmessage?>
						<a href="pipelines.php?action=reset&id=<?=$id?>" class="ui orange basic small button">reset</a>
					</div>
				</div>
				<div class="disabled step">
					<div class="content">
						<div class="title">Finish</div>
						<div class="description"></div>
					</div>
				</div>
			</div>
			<? } else { ?>
			<div class="ui four bottom attached steps">
				<div class="active step">
					<div class="content">
						<div class="title">Idle</div>
						<div class="description"></div>
					</div>
				</div>
				<div class="disabled step">
					<div class="content">
						<div class="title">Start</div>
						<div class="description">Last started <?=$pipeline_laststart?></div>
					</div>
				</div>
				<div class="disabled step">
					<div class="content">
						<div class="title">Running</div>
						<div class="description">Last checked in <?=$pipeline_lastcheck?></div>
						<?=$pipeline_statusmessage?>
					</div>
				</div>
				<div class="disabled step">
					<div class="content">
						<div class="title">Finish</div>
						<div class="description">Last finished <?=$pipeline_lastfinish?></div>
					</div>
				</div>
			</div>
			<? } ?>
		</div>
		<?
	}

	/*-----------Redcap to ADO Utility FUNCTIONS-------------------*/

	/* -------------------------------------------- */
	/* ------- getrcarms -------------------------- */
	/* -------------------------------------------- */
	function getrcarms($projectid) {

		$sqlstring =  "SELECT redcap_token, redcap_server FROM `projects` WHERE  project_id = '$projectid' ";
		$result =  MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$RCtoken = $row['redcap_token'];
		$RCserver = $row['redcap_server'];

		$data = array(
			'token' => $RCtoken,
			'content' => 'arm',
			'format' => 'json',
			'arms' => array('1'),
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
		curl_close($ch);
		$ArmsList = json_decode($output,true);

		for ($Am=0;$Am <= count($ArmsList)-1; $Am++)
		{
				$Arms[$Am]= $ArmsList[$Am]["name"];
		}

		return $Arms;
	}


	/* -------------------------------------------- */
	/* ------- getrcevents ------------------------ */
	/* -------------------------------------------- */
	function getrcevents($projectid) {

		$sqlstring =  "SELECT redcap_token, redcap_server FROM `projects` WHERE  project_id = '$projectid' ";
		$result =  MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$RCtoken = $row['redcap_token'];
		$RCserver = $row['redcap_server'];

		$data = array(
			'token' => $RCtoken,
			'content' => 'event',
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
		//print $output;
		curl_close($ch);
		$EventsList = json_decode($output,true);

		//echo var_dump($EventList["event_name"]);
		for ($Ev=0;$Ev <= count($EventsList)-1; $Ev++)
		{
				$Events[$Ev]= $EventsList[$Ev]["unique_event_name"];
		}

		return $Events;
	}


	/* -------------------------------------------- */
	/* ------- getrcinstruments ------------------- */
	/* -------------------------------------------- */
	function getrcinstruments($projectid) {

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
		$InstList = json_decode($output,true);
		curl_close($ch);

		for ($In=0;$In <= count($InstList)-1; $In++)
		{
			$Inst_Name[$In]=$InstList[$In]["instrument_name"];
			$Inst_Label[$In]=$InstList[$In]["instrument_label"];
		}

		return array($Inst_Name,$Inst_Label);
	}


	/* -------------------------------------------- */
	/* ------- getrcvariables --------------------- */
	/* -------------------------------------------- */
	function getrcvariables($projectid,$IN,$RCEvents) {
		$sqlstring =  "SELECT redcap_token, redcap_server FROM `projects` WHERE  project_id = '$projectid' ";
		$result =  MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$RCtoken = $row['redcap_token'];
		$RCserver = $row['redcap_server'];

		$data = array(
			'token' => $RCtoken,
			'content' => 'record',
			'format' => 'json',
			'type' => 'flat',
			'forms' => $IN,
			'events' => $RCEvents,
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

		$Var_Names = array_keys($report[0]); /* This variable ($Var_Names)contains names of all the variables in selected form */

		return $Var_Names;
	}


	/* -------------------------------------------- */
	/* ------- getrcrecords ----------------------- */
	/* -------------------------------------------- */
	function getrcrecords($projectid,$IN,$RCEvents,$RCID,$JointID) {
		$sqlstring =  "SELECT redcap_token, redcap_server FROM `projects` WHERE  project_id = '$projectid' ";
		$result =  MySQLiQuery($sqlstring, __FILE__, __LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$RCtoken = $row['redcap_token'];
		$RCserver = $row['redcap_server'];

		$data = array(
			'token' => $RCtoken,
			'content' => 'record',
			'format' => 'json',
			'type' => 'flat',
			'fields' => array($RCID,$JointID),
			'forms' => $IN,
			'events' => $RCEvents,
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

	
	/* -------------------------------------------- */
	/* ------- preg_quote2 ------------------------ */
	/* -------------------------------------------- */
	function preg_quote2($s) {
		$s = str_replace('\\', '\\\\', $s);
		$s = str_replace('.', '\\.', $s);
		$s = str_replace('?', '\\?', $s);
		$s = str_replace('[', '\\[', $s);
		$s = str_replace('^', '\\^', $s);
		$s = str_replace(']', '\\]', $s);
		$s = str_replace('$', '\\$', $s);
		$s = str_replace('(', '\\(', $s);
		$s = str_replace(')', '\\)', $s);
		$s = str_replace('{', '\\{', $s);
		$s = str_replace('}', '\\}', $s);
		$s = str_replace('=', '\\=', $s);
		$s = str_replace('!', '\\!', $s);
		$s = str_replace('<', '\\<', $s);
		$s = str_replace('>', '\\>', $s);
		$s = str_replace('|', '\\|', $s);
		$s = str_replace(':', '\\:', $s);
		$s = str_replace('-', '\\-', $s);
		return $s;
	}


	/* -------------------------------------------- */
	/* ------- DisplaySettings -------------------- */
	/* -------------------------------------------- */
	function DisplaySettings($returnpage) {

		/* load the actual .cfg file */
		$GLOBALS['cfg'] = LoadConfig();
	
		$dbconnect = true;
		$devdbconnect = true;
		$L = mysqli_connect($GLOBALS['cfg']['mysqlhost'],$GLOBALS['cfg']['mysqluser'],$GLOBALS['cfg']['mysqlpassword'],$GLOBALS['cfg']['mysqldatabase']) or $dbconnect = false;
		$Ldev = mysqli_connect($GLOBALS['cfg']['mysqldevhost'],$GLOBALS['cfg']['mysqldevuser'],$GLOBALS['cfg']['mysqldevpassword'],$GLOBALS['cfg']['mysqldevdatabase']) or $devdbconnect = false;
		
		?>
		<div class="ui top attached yellow inverted teriary segment">
			<div class="ui two column grid">
				<div class="column">
					<h2>NiDB Settings</h2>
				</div>
				<div class="right aligned column" style="color: #000">
					Reading from config file <div class="ui large label"><tt><?=$GLOBALS['cfg']['cfgpath']?></tt></div>
				</div>
			</div>
		</div>
		<div class="ui bottom attached yellow inverted tertiary segment">
			
		<? if ($returnpage == "settings") { ?>
		<form name="configform" method="post" action="system.php" class="ui form">
		<? } elseif ($returnpage == "setup") { ?>
		<form name="configform" method="post" action="setup.php" class="ui form">
		<input type="hidden" name="step" value="setupcomplete">
		<? } ?>
		<input type="hidden" name="action" value="updateconfig">
		<table class="ui very compact top attached celled table">
			<thead>
				<tr>
					<th class="ui inverted attached header"><h3 class="header">Variable</h3></th>
					<th class="ui inverted attached header"><h3 class="header">Value</h3></th>
					<th class="ui inverted attached header"><h3 class="header">Valid?</h3></th>
					<th class="ui inverted attached header"><h3 class="header">Description</h3></th>
				</tr>
			</thead>
			<tr>
				<td colspan="4" class="active"><h3>Debug</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">debug</td>
				<td><input type="checkbox" name="debug" value="1" <? if ($GLOBALS['cfg']['debug']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Enable debugging for the PHP pages. Will display all SQL statements.</td>
			</tr>
			<tr>
				<td class="right aligned tt">hideerrors</td>
				<td><input type="checkbox" name="hideerrors" value="1" <? if ($GLOBALS['cfg']['hideerrors']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Hide a SQL error if it occurs. Emails are always sent. Always leave checked on production systems for security purposes!</td>
			</tr>
			
			<tr>
				<td colspan="4" class="active"><h3>Database</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqlhost</td>
				<td><input type="text" name="mysqlhost" value="<?=($GLOBALS['cfg']['mysqlhost'] == "") ? $GLOBALS['cfg']['mysqlhost'] : "localhost"; ?>" size="100"></td>
				<td class="center aligned"><? if ($dbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Database hostname (should be <code>localhost</code> or <code>127.0.0.1</code> unless the database is running on a different server than the website)</td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqluser</td>
				<td><input type="text" name="mysqluser" value="<?=($GLOBALS['cfg']['mysqluser'] == "") ? $GLOBALS['cfg']['mysqluser'] : "nidb"; ?>"></td>
				<td class="center aligned"><? if ($dbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Database username</td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqlpassword</td>
				<td><input type="password" name="mysqlpassword" value="<?=($GLOBALS['cfg']['mysqlpassword'] == "") ? $GLOBALS['cfg']['mysqlpassword'] : "password"; ?>"></td>
				<td class="center aligned"><? if ($dbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Database password</td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqldatabase</td>
				<td><input type="text" name="mysqldatabase" value="<?=($GLOBALS['cfg']['mysqldatabase'] == "") ? $GLOBALS['cfg']['mysqlpassword'] : "nidb"; ?>"></td>
				<td class="center aligned"><? if ($dbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Database (default is <tt>nidb</tt>)</td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqldevhost</td>
				<td><input type="text" name="mysqldevhost" value="<?=$GLOBALS['cfg']['mysqldevhost']?>"></td>
				<td class="center aligned"><? if ($devdbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Development database hostname. This database will only be used if the website is accessed from port 8080 instead of 80. Example <code>http://localhost:8080</code></td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqldevuser</td>
				<td><input type="text" name="mysqldevuser" value="<?=$GLOBALS['cfg']['mysqldevuser']?>"></td>
				<td class="center aligned"><? if ($devdbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Development database username</td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqldevpassword</td>
				<td><input type="password" name="mysqldevpassword" value="<?=$GLOBALS['cfg']['mysqldevpassword']?>"></td>
				<td class="center aligned"><? if ($devdbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Development database password</td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqldevdatabase</td>
				<td><input type="text" name="mysqldevdatabase" value="<?=$GLOBALS['cfg']['mysqldevdatabase']?>"></td>
				<td class="center aligned"><? if ($devdbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Development database (default is <tt>nidb</tt>)</td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqlclusteruser</td>
				<td><input type="text" name="mysqlclusteruser" value="<?=$GLOBALS['cfg']['mysqlclusteruser']?>"></td>
				<td class="center aligned"><? if ($dbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Cluster database username -  this user has insert-only permissions for certain pipeline tables</td>
			</tr>
			<tr>
				<td class="right aligned tt">mysqlclusterpassword</td>
				<td><input type="password" name="mysqlclusterpassword" value="<?=$GLOBALS['cfg']['mysqlclusterpassword']?>"></td>
				<td class="center aligned"><? if ($dbconnect) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Cluster database password</td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>Modules<br><span class="tiny">Maximum number of threads allowed. Some modules cannot be multi-threaded</span></h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">modulefileiothreads</td>
				<td><input type="number" name="modulefileiothreads" value="1" disabled></td>
				<td></td>
				<td><b>fileio</b> module. Not multi-threaded</td>
			</tr>
			<tr>
				<td class="right aligned tt">moduleexportthreads</td>
				<td><input type="number" name="moduleexportthreads" value="<?=$GLOBALS['cfg']['moduleexportthreads']?>"></td>
				<td></td>
				<td><b>export</b> module. Recommended is 2</td>
			</tr>
			<tr>
				<td class="right aligned tt">moduleimportthreads</td>
				<td><input type="number" name="moduleimportthreads" value="1" disabled></td>
				<td></td>
				<td><b>import</b> module. Not multi-threaded</td>
			</tr>
			<tr>
				<td class="right aligned tt">modulemriqathreads</td>
				<td><input type="number" name="modulemriqathreads" value="<?=$GLOBALS['cfg']['modulemriqathreads']?>"></td>
				<td></td>
				<td><b>mriqa</b> module. Recommended is 4</td>
			</tr>
			<tr>
				<td class="right aligned tt">modulepipelinethreads</td>
				<td><input type="number" name="modulepipelinethreads" value="<?=$GLOBALS['cfg']['modulepipelinethreads']?>"></td>
				<td></td>
				<td><b>pipeline</b> module. Recommended is 4</td>
			</tr>
			<tr>
				<td class="right aligned tt">moduleimportuploadedthreads</td>
				<td><input type="number" name="moduleimportuploadedthreads" value="1" disabled></td>
				<td></td>
				<td><b>importuploaded</b> module. Not multi-threaded.</td>
			</tr>
			<tr>
				<td class="right aligned tt">moduleqcthreads</td>
				<td><input type="number" name="moduleqcthreads" value="<?=$GLOBALS['cfg']['moduleqcthreads']?>"></td>
				<td></td>
				<td><b>qc</b> module. Recommended is 2</td>
			</tr>
			<tr>
				<td class="right aligned tt">moduleuploadthreads</td>
				<td><input type="number" name="moduleuploadthreads" value="<?=$GLOBALS['cfg']['moduleuploadthreads']?>"></td>
				<td></td>
				<td><b>upload</b> module. Recommended is 1</td>
			</tr>
			<tr>
				<td class="right aligned tt">modulebackupthreads</td>
				<td><input type="number" name="modulebackupthreads" value="1" disabled></td>
				<td></td>
				<td><b>backup</b> module. Not multi-threaded</td>
			</tr>
			<tr>
				<td class="right aligned tt">moduleminipipelinethreads</td>
				<td><input type="number" name="moduleminipipelinethreads" value="<?=$GLOBALS['cfg']['moduleminipipelinethreads']?>"></td>
				<td></td>
				<td><b>minipipeline</b> module. Recommended is 4</td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>Email &nbsp; &nbsp;<a href="system.php?action=testemail" class="ui compact yellow button">Send test email</a></h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">emaillib</td>
				<td><input type="text" name="emaillib" value="<?=$GLOBALS['cfg']['emaillib']?>"></td>
				<td></td>
				<td>Net-SMTP-TLS or Email-Send-SMTP-Gmail</td>
			</tr>
			<tr>
				<td class="right aligned tt">emailusername</td>
				<td><input type="text" name="emailusername" value="<?=$GLOBALS['cfg']['emailusername']?>"></td>
				<td></td>
				<td>Username to login to the gmail account. Used for sending emails only</td>
			</tr>
			<tr>
				<td class="right aligned tt">emailpassword</td>
				<td><input type="password" name="emailpassword" value="<?=$GLOBALS['cfg']['emailpassword']?>"></td>
				<td></td>
				<td>email account password</td>
			</tr>
			<tr>
				<td class="right aligned tt">emailserver</td>
				<td><input type="text" name="emailserver" value="<?=$GLOBALS['cfg']['emailserver']?>"></td>
				<td></td>
				<td>Email server for sending email. For gmail, it should be <code>tls://smtp.gmail.com</code></td>
			</tr>
			<tr>
				<td class="right aligned tt">emailport</td>
				<td><input type="number" name="emailport" value="<?=$GLOBALS['cfg']['emailport']?>"></td>
				<td></td>
				<td>Email server port. For gmail, it should be <tt>587</tt></td>
			</tr>
			<tr>
				<td class="right aligned tt">emailfrom</td>
				<td><input type="email" name="emailfrom" value="<?=$GLOBALS['cfg']['emailfrom']?>"></td>
				<td></td>
				<td>Email return address</td>
			</tr>
			<tr>
				<td colspan="4" class="active"><h3>Site options</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">adminemail</td>
				<td><input type="text" name="adminemail" value="<?=$GLOBALS['cfg']['adminemail']?>"></td>
				<td></td>
				<td>Administrator's email. Displayed for error messages and other system activities</td>
			</tr>
			<tr>
				<td class="right aligned tt">siteurl</td>
				<td><input type="text" name="siteurl" value="<?=$GLOBALS['cfg']['siteurl']?>"></td>
				<td></td>
				<td>Full URL of the NiDB website</td>
			</tr>
			<tr>
				<td class="right aligned tt">version</td>
				<td><input type="text" name="version" value="<?=GetNiDBVersion()?>" readonly></td>
				<td></td>
				<td>NiDB version. Automatically populated</td>
			</tr>
			<tr>
				<td class="right aligned tt">sitename</td>
				<td><input type="text" name="sitename" value="<?=$GLOBALS['cfg']['sitename']?>"></td>
				<td></td>
				<td>Displayed on NiDB main page and some email notifications</td>
			</tr>
			<tr>
				<td class="right aligned tt">sitenamedev</td>
				<td><input type="text" name="sitenamedev" value="<?=$GLOBALS['cfg']['sitenamedev']?>"></td>
				<td></td>
				<td>Development site name</td>
			</tr>
			<tr>
				<td class="right aligned tt">sitecolor</td>
				<td><input type="color" name="sitecolor" value="<?=$GLOBALS['cfg']['sitecolor']?>"></td>
				<td></td>
				<td>Hex code for color in the upper left of the menu</td>
			</tr>
			<tr>
				<td class="right aligned tt">ispublic</td>
				<td><input type="checkbox" name="ispublic" value="1" <? if ($GLOBALS['cfg']['ispublic']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Selected if this installation is on a public server and only has port 80 open</td>
			</tr>
			<tr>
				<td class="right aligned tt">sitetype</td>
				<td><input type="text" name="sitetype" value="<?=$GLOBALS['cfg']['sitetype']?>"></td>
				<td></td>
				<td>Options are local, public, or commercial</td>
			</tr>
			<tr>
				<td class="right aligned tt">allowphi</td>
				<td><input type="checkbox" name="allowphi" value="1" <? if ($GLOBALS['cfg']['allowphi']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Checked to allow PHI (name, DOB) on server. Unchecked to remove all PHI by default (replace name with 'Anonymous' and DOB with only year)</td>
			</tr>
			<tr>
				<td class="right aligned tt">uploadsizelimit</td>
				<td>
					<div class="ui right labeled fluid input">
						<input type="text" name="uploadsizelimit" value="<?=$GLOBALS['cfg']['uploadsizelimit']?>">
						<div class="ui label">MB</div>
					</div>
				</td>
				<td></td>
				<td>Upload size limit in megabytes (MB). Current PHP upload filesize limit [upload_max_filesize] is <?=get_cfg_var('upload_max_filesize')?> and max POST size [post_max_size] is <?=get_cfg_var('post_max_size')?></td>
			</tr>
			<tr>
				<td class="right aligned tt">displayrecentstudies</td>
				<td><input type="checkbox" name="displayrecentstudies" value="1" <? if ($GLOBALS['cfg']['displayrecentstudies']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Display recently collected studies on the Home page</td>
			</tr>
			<tr>
				<td class="right aligned tt">displayrecentstudydays</td>
				<td><input type="text" name="displayrecentstudydays" value="<?=$GLOBALS['cfg']['displayrecentstudydays']?>"></td>
				<td></td>
				<td>Number of days to display of recently collected studies on the Home page</td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>Features</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">enableremoteconn</td>
				<td><input type="checkbox" name="enableremoteconn" value="1" <? if ($GLOBALS['cfg']['enableremoteconn']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Allow this server to send data to remote NiDB servers</td>
			</tr>
			<tr>
				<td class="right aligned tt">enablecalendar</td>
				<td><input type="checkbox" name="enablecalendar" value="1" <? if ($GLOBALS['cfg']['enablecalendar']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Enable the calendar</td>
			</tr>
			<tr>
				<td class="right aligned tt">enablepipelines</td>
				<td><input type="checkbox" name="enablepipelines" value="1" <? if ($GLOBALS['cfg']['enablepipelines']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Enable pipelines</td>
			</tr>
			<tr>
				<td class="right aligned tt">enabledatamenu</td>
				<td><input type="checkbox" name="enabledatamenu" value="1" <? if ($GLOBALS['cfg']['enabledatamenu']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Enable the main Data menu</td>
			</tr>
			<tr>
				<td class="right aligned tt">enablerdoc</td>
				<td><input type="checkbox" name="enablerdoc" value="1" <? if ($GLOBALS['cfg']['enablerdoc']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Enable RDoCdb features</td>
			</tr>
			<tr>
				<td class="right aligned tt">enablepublicdownloads</td>
				<td><input type="checkbox" name="enablepublicdownloads" value="1" <? if ($GLOBALS['cfg']['enablepublicdownloads']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Enable public downloads</td>
			</tr>
			<tr>
				<td class="right aligned tt">enablewebexport</td>
				<td><input type="checkbox" name="enablewebexport" value="1" <? if ($GLOBALS['cfg']['enablewebexport']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Allow this server to send data to remote NiDB servers</td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>Security</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">setupips</td>
				<td><input type="text" name="setupips" value="<?=$GLOBALS['cfg']['setupips']?>"></td>
				<td></td>
				<td>Comma separated list of IP addresses from which the setup and update functionality can be accessed. Example <code>127.0.0.1, 10.24.1.1</code> Your current IP address is <code><?=$_SERVER['REMOTE_ADDR']?></code></td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>Backup</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">backupsize</td>
				<td>
					<div class="ui right labeled fluid input">
						<input type="text" name="backupsize" value="<?=$GLOBALS['cfg']['backupsize']?>">
						<div class="ui label">GB</div>
					</div>
				</td>
				<td></td>
				<td>
					Number of GB in the backup directory before a tape is written. This should be relative to the tape size. [1 GB = 1,000,000,000 bytes]
					<div class="ui green label" title="LTO-10 36000<br>LTO-9 18000<br>LTO-8 12000<br>LTO-7 6000<br>LTO-6 2500<br>LTO-5 1500<br>LTO-4 800<br>LTO-3 400<br>LTO-2 200<br>LTO-1 100"><i class="search icon"></i> LTO tape capacity (in GB)</div>
				</td>
			</tr>
			<tr>
				<td class="right aligned tt">backupstagingdir</td>
				<td><input type="text" name="backupstagingdir" value="<?=$GLOBALS['cfg']['backupstagingdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['backupstagingdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Path where data will be staged prior to writing to tape</td>
			</tr>
			<tr>
				<td class="right aligned tt">backupdevice</td>
				<td><input type="text" name="backupdevice" value="<?=$GLOBALS['cfg']['backupdevice']?>"></td>
				<td></td>
				<td>Tape device through which tar will be used. Usually the default tape device on Linux is <code>/dev/st0</code>.</td>
			</tr>
			<tr>
				<td class="right aligned tt">backupserver</td>
				<td><input type="text" name="backupserver" value="<?=$GLOBALS['cfg']['backupserver']?>"></td>
				<td></td>
				<td>Remote tape server, with username. Passwordless ssh is needed if using a remote tape server. example <code>user@tapeserver</code></td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>Data Import/Export</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">enablecsa</td>
				<td><input type="checkbox" name="enablecsa" value="1" <? if ($GLOBALS['cfg']['enablecsa']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Enable reading of Siemens CSA header in DICOM files. This option allows phase encoding direction to be read, but will make archiving SLOW for non-mosaic images.</td>
			</tr>
			<tr>
				<td class="right aligned tt">importchunksize</td>
				<td><input type="number" name="importchunksize" value="<?=$GLOBALS['cfg']['importchunksize']?>"></td>
				<td></td>
				<td>Number of files checked by the import module before archiving begins. Default is 5000</td>
			</tr>
			<tr>
				<td class="right aligned tt">numretry</td>
				<td><input type="number" name="numretry" value="<?=$GLOBALS['cfg']['numretry']?>"></td>
				<td></td>
				<td>Number of times to retry a failed network operation. Default is 5</td>
			</tr>
			<tr>
				<td class="right aligned tt">enablenfs</td>
				<td><input type="checkbox" name="enablenfs" value="1" <? if ($GLOBALS['cfg']['enablenfs']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Display the NFS export options. Allow NiDB to write to NFS mount points</td>
			</tr>
			<tr>
				<td class="right aligned tt">enableftp</td>
				<td><input type="checkbox" name="enableftp" value="1" <? if ($GLOBALS['cfg']['enableftp']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Display the FTP export options. Uncheck if this site does not have FTP, SCP, or other file transfer services enabled</td>
			</tr>
			<tr>
				<td class="right aligned tt">allowrawdicomexport</td>
				<td><input type="checkbox" name="allowrawdicomexport"  value="1" <? if ($GLOBALS['cfg']['allowrawdicomexport']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Allow DICOM files to be downloaded from this server without being anonymized first. Unchecking this option removes the Download and 3D viewier icons on the study page</td>
			</tr>
			<tr>
				<td class="right aligned tt">redcapurl</td>
				<td><input type="text" name="redcapurl" value="<?=$GLOBALS['cfg']['redcapurl']?>"></td>
				<td></td>
				<td>URL of the RedCap Database API to pull data from RedCap into NiDB</td>
			</tr>
			<tr>
				<td class="right aligned tt">redcaptoken</td>
				<td><input type="text" name="redcaptoken" value="<?=$GLOBALS['cfg']['redcaptoken']?>"></td>
				<td></td>
				<td>Token required to access RedCap</td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>Quality Control</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">fsldir</td>
				<td><input type="text" name="fsldir" value="<?=$GLOBALS['cfg']['fsldir']?>"></td>
				<td></td>
				<td>The value of the FSL_DIR environment variable. Example /opt/fsl/bin</td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>Cluster</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">usecluster</td>
				<td><input type="checkbox" name="usecluster" value="1" <? if ($GLOBALS['cfg']['usecluster']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Use a cluster to perform QC</td>
			</tr>
			<tr>
				<td class="right aligned tt">queuename</td>
				<td><input type="text" name="queuename" value="<?=$GLOBALS['cfg']['queuename']?>"></td>
				<td></td>
				<td>Cluster queue name</td>
			</tr>
			<tr>
				<td class="right aligned tt">queueuser</td>
				<td><input type="text" name="queueuser" value="<?=$GLOBALS['cfg']['queueuser']?>"></td>
				<td></td>
				<td>Linux username under which the QC cluster jobs are submitted</td>
			</tr>
			<tr>
				<td class="right aligned tt">clustersubmithost</td>
				<td><input type="text" name="clustersubmithost" value="<?=$GLOBALS['cfg']['clustersubmithost']?>"></td>
				<td></td>
				<td>Hostname which QC jobs are submitted</td>
			</tr>
			<tr>
				<td class="right aligned tt">qsubpath</td>
				<td><input type="text" name="qsubpath" value="<?=$GLOBALS['cfg']['qsubpath']?>"></td>
				<td></td>
				<td>Path to the <code>qsub</code> program. Use a full path to the executable, or just <code>qsub</code> if its already in the PATH environment tt</td>
			</tr>
			<tr>
				<td class="right aligned tt">clusteruser</td>
				<td><input type="text" name="clusteruser" value="<?=$GLOBALS['cfg']['clusteruser']?>"></td>
				<td></td>
				<td>Username under which jobs will be submitted to the cluster for the pipeline system</td>
			</tr>
			<tr>
				<td class="right aligned tt">clusternidbpath</td>
				<td><input type="text" name="clusternidbpath" value="<?=$GLOBALS['cfg']['clusternidbpath']?>"></td>
				<td></td>
				<td>Path to the directory containing the <i>nidb</i> executable (relative to the cluster itself) on the cluster</td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>CAS Authentication</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">enablecas</td>
				<td><input type="checkbox" name="enablecas" value="1" <? if ($GLOBALS['cfg']['enablecas']) { echo "checked"; } ?>></td>
				<td></td>
				<td>Use CAS authentication</td>
			</tr>
			<tr>
				<td class="right aligned tt">casserver</td>
				<td><input type="text" name="casserver" value="<?=$GLOBALS['cfg']['casserver']?>"></td>
				<td></td>
				<td>CAS server</td>
			</tr>
			<tr>
				<td class="right aligned tt">casport</td>
				<td><input type="number" name="casport" value="<?=$GLOBALS['cfg']['casport']?>"></td>
				<td></td>
				<td>CAS port, usually 443</td>
			</tr>
			<tr>
				<td class="right aligned tt">cascontext</td>
				<td><input type="text" name="cascontext" value="<?=$GLOBALS['cfg']['cascontext']?>"></td>
				<td></td>
				<td>CAS context</td>
			</tr>
			
			<tr>
				<td colspan="4" class="active"><h3>FTP</h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">localftphostname</td>
				<td><input type="text" name="localftphostname" value="<?=$GLOBALS['cfg']['localftphostname']?>"></td>
				<td></td>
				<td>If you allow data to be sent to the local FTP and have configured the FTP site, this will be the information displayed to users on how to access the FTP site.</td>
			</tr>
			<tr>
				<td class="right aligned tt">localftpusername</td>
				<td><input type="text" name="localftpusername" value="<?=$GLOBALS['cfg']['localftpusername']?>"></td>
				<td></td>
				<td>Username for the locall access FTP account</td>
			</tr>
			<tr>
				<td class="right aligned tt">localftppassword</td>
				<td><input type="text" name="localftppassword" value="<?=$GLOBALS['cfg']['localftppassword']?>"></td>
				<td></td>
				<td>Password for local access FTP account. This is displayed to the users in clear text.</td>
			</tr>

			<tr>
				<td colspan="4" class="active"><h3>NiDB Directories<br><span class="tiny">Leave off trailing slashes</span></h3></td>
			</tr>
			<tr>
				<td class="right aligned tt"><b>nidbdir</b></td>
				<td><input type="text" name="nidbdir" value="<?=$GLOBALS['cfg']['nidbdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['nidbdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td><b>Main NiDB installation directory</b></td>
			</tr>
			<tr>
				<td class="right aligned tt"><b>webdir</b></td>
				<td><input type="text" name="webdir" value="<?=$GLOBALS['cfg']['webdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['webdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td><b>Root of the website directory (Frontend)</b></td>
			</tr>
			<tr>
				<td class="right aligned tt">lockdir</td>
				<td><input type="text" name="lockdir" value="<?=$GLOBALS['cfg']['lockdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['lockdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Lock directory for the programs</td>
			</tr>
			<tr>
				<td class="right aligned tt">logdir</td>
				<td><input type="text" name="logdir" value="<?=$GLOBALS['cfg']['logdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['logdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Log directory for the programs</td>
			</tr>
			<tr>
				<td class="right aligned tt">mountdir</td>
				<td><input type="text" name="mountdir" value="<?=$GLOBALS['cfg']['mountdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['mountdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Directory in which user data directories are mounted and any directories which should be accessible from the NFS mount export option of the Search page. For example, if the user enters <code>/home/user1/data/testing</code> the mountdir will be prepended to point to the real mount point of <code>/mount/home/user1/data/testing</code>. This prevents users from writing data to the OS directories.</td>
			</tr>
			<tr>
				<td class="right aligned tt">qcmoduledir</td>
				<td><input type="text" name="qcmoduledir" value="<?=$GLOBALS['cfg']['qcmoduledir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['qcmoduledir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Directory containing QC modules. Usually a subdirectory of the programs directory</td>
			</tr>


			<tr>
				<td colspan="4" class="active"><h3>Data Directories<br><span class="tiny">Leave off trailing slashes</span></h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">archivedir</td>
				<td><input type="text" name="archivedir" value="<?=$GLOBALS['cfg']['archivedir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['archivedir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Directory for archived data. All binary data is stored in this directory.</td>
			</tr>
			<tr>
				<td class="right aligned tt">backupdir</td>
				<td><input type="text" name="backupdir" value="<?=$GLOBALS['cfg']['backupdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['backupdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>All data is copied to this directory at the same time it is added to the archive directory. This can be useful if you want to use a tape backup and only copy out newer files from this directory to fill up a tape.</td>
			</tr>
			<tr>
				<td class="right aligned tt">ftpdir</td>
				<td><input type="text" name="ftpdir" value="<?=$GLOBALS['cfg']['ftpdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['ftpdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Downloaded data to be retreived by FTP is stored here</td>
			</tr>
			<tr>
				<td class="right aligned tt">importdir</td>
				<td><input type="text" name="importdir" value="<?=$GLOBALS['cfg']['importdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['importdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Old method of importing data. Unused</td>
			</tr>
			<tr>
				<td class="right aligned tt">incomingdir</td>
				<td><input type="text" name="incomingdir" value="<?=$GLOBALS['cfg']['incomingdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['incomingdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>All data received from the DICOM receiver is placed in the root of this directory. All non-DICOM data is stored in numbered sub-directories of this directory.</td>
			</tr>
			<tr>
				<td class="right aligned tt">incoming2dir</td>
				<td><input type="text" name="incoming2dir" value="<?=$GLOBALS['cfg']['incoming2dir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['incoming2dir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Unused</td>
			</tr>
			<tr>
				<td class="right aligned tt">packageimportdir</td>
				<td><input type="text" name="packageimportdir" value="<?=$GLOBALS['cfg']['packageimportdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['packageimportdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>If using the data package export/import feature, packages to be imported should be placed here</td>
			</tr>
			<tr>
				<td class="right aligned tt">problemdir</td>
				<td><input type="text" name="problemdir" value="<?=$GLOBALS['cfg']['problemdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['problemdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Files which encounter problems during import/archiving are placed here</td>
			</tr>
			<tr>
				<td class="right aligned tt">webdownloaddir</td>
				<td><input type="text" name="webdownloaddir" value="<?=$GLOBALS['cfg']['webdownloaddir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['webdownloaddir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Directory within the webdir that will link to the physical download directory. Sometimes the downloads can be HUGE, and the default <code>/var/www/html</code> directory may be on a small partition. This directory should point to the real [downloaddir] on a filesystem with enough space to store the large downloads.</td>
			</tr>
			<tr>
				<td class="right aligned tt">downloaddir</td>
				<td><input type="text" name="downloaddir" value="<?=$GLOBALS['cfg']['downloaddir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['downloaddir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Directory which stores downloads available from the website</td>
			</tr>
			<tr>
				<td class="right aligned tt">uploaddir</td>
				<td><input type="text" name="uploaddir" value="<?=$GLOBALS['cfg']['uploaddir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['uploaddir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Uploaded data is placed here</td>
			</tr>
			<tr>
				<td class="right aligned tt">uploadeddir</td>
				<td><input type="text" name="uploadeddir" value="<?=$GLOBALS['cfg']['uploadeddir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['uploadeddir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Data received from the api.php and import pages is placed here</td>
			</tr>
			<tr>
				<td class="right aligned tt">uploadstagingdir</td>
				<td><input type="text" name="uploadstagingdir" value="<?=$GLOBALS['cfg']['uploadstagingdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['uploadstagingdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Data being imported into NiDB is copied here for staging and preparation for archiving. Files are unzipped, parsed, and cataloged prior to import into NiDB so that the user can view the upload contents.</td>
			</tr>
			<tr>
				<td class="right aligned tt">tmpdir</td>
				<td><input type="text" name="tmpdir" value="<?=$GLOBALS['cfg']['tmpdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['tmpdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Directory used for temporary operations. Depending upon data sizes requested or processed, this directory may get very large, and may need to be outside of the OS drive.</td>
			</tr>
			<tr>
				<td class="right aligned tt">deleteddir</td>
				<td><input type="text" name="deleteddir" value="<?=$GLOBALS['cfg']['deleteddir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['deleteddir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Data is not usually deleted. It may be removed from the database and not appear on the website, but the data will end up in this directory.</td>
			</tr>


			<tr>
				<td colspan="4" class="active"><h3>Cluster/pipeline Directories<br><span class="tiny">Leave off trailing slashes</span></h3></td>
			</tr>
			<tr>
				<td class="right aligned tt">analysisdir</td>
				<td><input type="text" name="analysisdir" value="<?=$GLOBALS['cfg']['analysisdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['analysisdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Pipeline analysis directory (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <code>/S1234ABC/<b>PipelineName</b>/1</code> format</td>
			</tr>
			<tr>
				<td class="right aligned tt">analysisdirb</td>
				<td><input type="text" name="analysisdirb" value="<?=$GLOBALS['cfg']['analysisdirb']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['analysisdirb'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Pipeline analysis directory (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <code>/<b>PipelineName</b>/S1234ABC/1</code> format</td>
			</tr>
			<tr>
				<td class="right aligned tt">clusteranalysisdir</td>
				<td><input type="text" name="clusteranalysisdir" value="<?=$GLOBALS['cfg']['clusteranalysisdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['clusteranalysisdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Pipeline analysis directory as seen from the cluster (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <code>/S1234ABC/<b>PipelineName</b>/1</code> format</td>
			</tr>
			<tr>
				<td class="right aligned tt">clusteranalysisdirb</td>
				<td><input type="text" name="clusteranalysisdirb" value="<?=$GLOBALS['cfg']['clusteranalysisdirb']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['clusteranalysisdirb'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Pipeline analysis directory as seen from the cluster (full path, including any /mount prefixes specified in [mountdir]) for data stored in the <code>/<b>PipelineName</b>/S1234ABC/1</code> format</td>
			</tr>
			<tr>
				<td class="right aligned tt">groupanalysisdir</td>
				<td><input type="text" name="groupanalysisdir" value="<?=$GLOBALS['cfg']['groupanalysisdir']?>"></td>
				<td class="center aligned"><? if (file_exists($GLOBALS['cfg']['groupanalysisdir'])) { ?><i class="large green check circle icon"></i><? } else { ?><i class="large red exclamation circle icon"></i><? } ?></td>
				<td>Pipeline directory for group analyses (full path, including any /mount prefixes specified in [mountdir])</td>
			</tr>
		</table>
		
		<? if ($returnpage == "settings") { ?>
		<div class="ui bottom attached center aligned segment">
			<div class="ui huge primary button" onClick="document.configform.submit();">Save Settings</div>
		</div>
		<? } ?>
		</form>
		</div>
		
		<br><br>
		
		<div class="ui top attached grey inverted segment">
			<h2>PHP Variables</h2>
		</div>
		<div class="ui bottom attached segment">
			<table class="ui very compact collapsing celled table">
				<thead>
					<tr>
						<th>PHP variable</th>
						<th>Current value</th>
					</tr>
				</thead>
				<tr>
					<td>max_input_vars</td>
					<td><?=get_cfg_var('max_input_vars')?></td>
				</tr>
				<tr>
					<td>post_max_size</td>
					<td><?=get_cfg_var('post_max_size')?></td>
				</tr>
				<tr>
					<td>upload_max_filesize</td>
					<td><?=get_cfg_var('upload_max_filesize')?></td>
				</tr>
				<tr>
					<td>max_file_uploads</td>
					<td><?=get_cfg_var('max_file_uploads')?></td>
				</tr>
			</table>
		</div>
		<br><br>
		
		<div class="ui top attached grey inverted segment">
			<h2>cron</h2>
		</div>
		<div class="ui bottom attached segment">
			Crontab for <?=system("whoami"); ?><br>
			<pre><?=system("crontab -l"); ?></pre>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- WriteConfig ------------------------ */
	/* -------------------------------------------- */
	function WriteConfig($c) {
		
		?>
		<br><br>
		<div class="ui message"><?
		
		/* escape all the variables and put them back into meaningful variable names */
		foreach ($c as $key => $value) {
			if (is_scalar($value)) {
				$$key = trim($c[$key]);
			}
			else {
				$$key = $c[$key];
			}
		}
		
		$year = date("Y");
		
		$str = "# NiDB configuration file
# ------------------------------------------------------------------------------
# NIDB nidb.cfg
# Copyright (C) 2004-$year
# Gregory A Book (gregory.book@hhchealth.org) (gregory.a.book@gmail.com)
# Olin Neuropsychiatry Research Center, Hartford Hospital
# ------------------------------------------------------------------------------
# GPLv3 License:
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see http://www.gnu.org/licenses/.
# ------------------------------------------------------------------------------

# ----- System availability -----
[offline] = 0

# ----- Debug -----
[debug] = $debug
[hideerrors] = $hideerrors

# ----- Database -----
[mysqlhost] = $mysqlhost
[mysqldatabase] = $mysqldatabase
[mysqluser] = $mysqluser
[mysqlpassword] = $mysqlpassword
[mysqldevhost] = $mysqldevhost
[mysqldevdatabase] = $mysqldevdatabase
[mysqldevuser] = $mysqldevuser
[mysqldevpassword] = $mysqldevpassword
[mysqlclusteruser] = $mysqlclusteruser
[mysqlclusterpassword] = $mysqlclusterpassword

# ----- modules -----
[modulefileiothreads] = $modulefileiothreads
[moduleexportthreads] = $moduleexportthreads
[moduleimportthreads] = $moduleimportthreads
[modulemriqathreads] = $modulemriqathreads
[modulepipelinethreads] = $modulepipelinethreads
[moduleminipipelinethreads] = $moduleminipipelinethreads
[moduleimportuploadedthreads] = $moduleimportuploadedthreads
[moduleqcthreads] = $moduleqcthreads
[moduleuploadthreads] = $moduleuploadthreads
[modulebackupthreads] = $modulebackupthreads

# ----- E-mail -----
# emaillib options (case-sensitive): Net-SMTP-TLS (default), Email-Send-SMTP-Gmail
[emaillib] = $emaillib
[emailusername] = $emailusername
[emailpassword] = $emailpassword
[emailserver] = $emailserver
[emailport] = $emailport
[emailfrom] = $emailfrom
[adminemail] = $adminemail

# ----- Site/server options -----
[siteurl] = $siteurl
[version] = $version
[sitename] = $sitename
[sitenamedev] = $sitenamedev
[sitecolor] = $sitecolor
[ispublic] = $ispublic
[sitetype] = $sitetype
[allowphi] = $allowphi
[uploadsizelimit] = $uploadsizelimit
[displayrecentstudies] = $displayrecentstudies
[displayrecentstudydays] = $displayrecentstudydays

# ----- features -----
[enableremoteconn] = $enableremoteconn
[enablecalendar] = $enablecalendar
[enablepipelines] = $enablepipelines
[enabledatamenu] = $enabledatamenu
[enablerdoc] = $enablerdoc
[enablepublicdownloads] = $enablepublicdownloads
[enablewebexport] = $enablewebexport

# ----- security options -----
[setupips] = $setupips

# ----- backup options -----
[backupsize] = $backupsize
[backupstagingdir] = $backupstagingdir
[backupdevice] = $backupdevice
[backupserver] = $backupserver

# ----- import/export options -----
[enablecsa] = $enablecsa
[importchunksize] = $importchunksize
[numretry] = $numretry
[enablenfs] = $enablenfs
[enableftp] = $enableftp
[allowrawdicomexport] = $allowrawdicomexport
[redcapurl] = $redcapurl
[redcaptoken] = $redcaptoken

# ----- qc -----
#[fslbinpath] = $fslbinpath
[fsldir] = $fsldir

# ----- cluster -----
[usecluster] = $usecluster
[queuename] = $queuename
[queueuser] = $queueuser
[clustersubmithost] = $clustersubmithost
[qsubpath] = $qsubpath
[clusteruser] = $clusteruser
[clusternidbpath] = $clusternidbpath

# ----- CAS authentication -----
[enablecas] = $enablecas
[casserver] = $casserver
[casport] = $casport
[cascontext] = $cascontext

# ----- local FTP info -----
[localftphostname] = $localftphostname
[localftpusername] = $localftpusername
[localftppassword] = $localftppassword

# ----- Directories (alphabetical list) -----
[analysisdir] = $analysisdir
[analysisdirb] = $analysisdirb
[clusteranalysisdir] = $clusteranalysisdir
[clusteranalysisdirb] = $clusteranalysisdirb
[groupanalysisdir] = $groupanalysisdir
[archivedir] = $archivedir
[backupdir] = $backupdir
[deleteddir] = $deleteddir
[downloaddir] = $downloaddir
[ftpdir] = $ftpdir
[importdir] = $importdir
[incomingdir] = $incomingdir
[incoming2dir] = $incoming2dir
[lockdir] = $lockdir
[logdir] = $logdir
[mountdir] = $mountdir
[packageimportdir] = $packageimportdir
[qcmoduledir] = $qcmoduledir
[problemdir] = $problemdir
[nidbdir] = $nidbdir
[tmpdir] = $tmpdir
[uploaddir] = $uploaddir
[uploadeddir] = $uploadeddir
[uploadstagingdir] = $uploadstagingdir
[webdir] = $webdir
[webdownloaddir] = $webdownloaddir
";

		if (($GLOBALS['cfg']['cfgpath'] == null) || ($GLOBALS['cfg']['cfgpath'] == "")) {
			$GLOBALS['cfg']['cfgpath'] = "/nidb/nidb.cfg";
		}
		
		$ret = file_put_contents($GLOBALS['cfg']['cfgpath'], $str);
		if (($ret === false) || ($ret === false) || ($ret == 0)) {
			?><div class="staticmessage">Problem writing [<?=$GLOBALS['cfg']['cfgpath']?>]. Is the file writeable to the [<?=system("whoami"); ?>] account?</div><?
		}
		else {
			?>Config file has been written to <code><?=$GLOBALS['cfg']['cfgpath']?></code><br><?
		}

		/* write a cconfig file for when NiDB is run from a cluster. this only contains basic info, separate DB login, and no paths */
		$str = "# NiDB cluster configuration file (for nidb running on the cluster)
# ------------------------------------------------------------------------------
# NIDB nidb.cfg
# Copyright (C) 2004-$year
# Gregory A Book (gregory.book@hhchealth.org) (gregory.a.book@gmail.com)
# Olin Neuropsychiatry Research Center, Hartford Hospital
# ------------------------------------------------------------------------------
# GPLv3 License:
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see http://www.gnu.org/licenses/.
# ------------------------------------------------------------------------------

# ----- Database -----
[mysqlhost] = $mysqlhost
[mysqldatabase] = $mysqldatabase
[mysqlclusteruser] = $mysqlclusteruser
[mysqlclusterpassword] = $mysqlclusterpassword

# ----- Site/server options -----
[version] = $version
[sitename] = $sitename
[clusteranalysisdir] = $clusteranalysisdir
[clusteranalysisdirb] = $clusteranalysisdirb

# ----- qc -----
[fslclusterbinpath] = $fslclusterbinpath
";
		$clustercfgfile = dirname($GLOBALS['cfg']['cfgpath']) . "/nidb-cluster.cfg";
		$ret = file_put_contents($clustercfgfile, $str);
		if (($ret === false) || ($ret === false) || ($ret == 0)) {
			?><div class="staticmessage">Problem writing [<?=$clustercfgfile?>]. Is the file writeable to the [<?=system("whoami"); ?>] account?</div><?
		}
		else {
			?>Cluster config file has been written to <code><?=$clustercfgfile?></code><?
		}
		
		?></div><?
	
	}

?>
