<?
 // ------------------------------------------------------------------------------
 // NiDB functions.php
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

	/* this file includes the database connection, cookies, global functions, and loads the configuration file */
	require_once "Mail.php";
	require_once "Mail/mime.php";

	/* load the configuration info [[these two lines should be the only config variables specific to the website]] */
 	$cfg = LoadConfig();
	date_default_timezone_set("America/New_York");

	if (stristr($_SERVER['HTTP_HOST'],":8080") != false) { $isdevserver = true; }
	else { $isdevserver = false; }

 	/* this is the first include file loaded by all pages, so... we'll put the page load start time in here */
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$pagestart = $time;
	
	/* database connection */
	if ($isdevserver) {
		/* php-mysql */
		$link = mysql_connect($cfg['mysqldevhost'],$cfg['mysqldevuser'],$cfg['mysqldevpassword']) or die ("Could not connect: " . mysql_error());
		mysql_select_db($cfg['mysqldevdatabase']) or die ("Could not select database<br>");
		/* php-mysqli */
		$linki = mysqli_connect($cfg['mysqldevhost'],$cfg['mysqldevuser'],$cfg['mysqldevpassword'],$cfg['mysqldevdatabase']) or die ("Could not connect: " . mysqli_connect_error());
		
		$sitename = $cfg['sitenamedev'];
	}
	else {
		/* php-mysql */
		$link = mysql_connect($cfg['mysqlhost'],$cfg['mysqluser'],$cfg['mysqlpassword']) or die ("Could not connect: " . mysql_error());
		mysql_select_db($cfg['mysqldatabase']) or die ("Could not select database<br>");
		/* php-mysqli */
		$linki = mysqli_connect($cfg['mysqlhost'],$cfg['mysqluser'],$cfg['mysqlpassword'],$cfg['mysqldatabase']) or die ("Could not connect: " . mysqli_connect_error());
		
		$sitename = $cfg['sitename'];
	}

	/* disable the login checking, if its the signup page or if authentication is done in the page (such as api.php) */
	if (!$nologin) {
		/* cookie info */
		$username = $_SESSION['username'];
		if ($_SESSION['validlogin'] != "true") {
			header("Location: login.php");
		}
	}
	else {
		/* no login */
	}

	$instanceid = $_SESSION['instanceid'];
	
	/* get info if they are an admin (wouldn't want to store this in a cookie... if they're logged in for 3 months, they may no longer be an admin during that time */
	$sqlstring = "select user_isadmin, user_issiteadmin, login_type, user_enablebeta from users where username = '$username'";
	$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	$isadmin = $row['user_isadmin'];
	$issiteadmin = $row['user_issiteadmin'];
	$enablebeta = $row['user_enablebeta'];
	$_SESSION['enablebeta'] = $enablebeta;
	if (strtolower($row['login_type']) == "guest") {
		$isguest = 1;
	}
	else {
		$isguest = 0;
	}
	
	/* each user can only be associated with 1 instance, so display that instance name at the top of the page */
	$sqlstring = "select instance_name from instance where instance_id in (select instance_id from users where username = '$username')";
	$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
	$row = mysql_fetch_array($result, MYSQL_ASSOC);
	$instancename = $row['instance_name'];
	

	/* -------------------------------------------- */
	/* ------- LoadConfig ------------------------- */
	/* -------------------------------------------- */
	// this function loads the config file into a hash called $cfg
	// ----------------------------------------------------------
	function LoadConfig() {
		$file = "";
		/* check some possible config file locations */
		if (file_exists('nidb.cfg')) {
			$file = 'nidb.cfg';
		}
		elseif (file_exists('../nidb.cfg')) {
			$file = '../nidb.cfg';
		}
		elseif (file_exists('../../prod/programs/nidb.cfg')) {
			$file = '../../prod/programs/nidb.cfg';
		}
		elseif (file_exists('../../../../prod/programs/nidb.cfg')) {
			$file = '../../../../prod/programs/nidb.cfg';
		}
		elseif (file_exists('../programs/nidb.cfg')) {
			$file = '../programs/nidb.cfg';
		}
		elseif (file_exists('/home/nidb/programs/nidb.cfg')) {
			$file = '/home/nidb/programs/nidb.cfg';
		}
		elseif (file_exists('/nidb/programs/nidb.cfg')) {
			$file = '/nidb/programs/nidb.cfg';
		}
		else {
			?><tt>nidb.cfg</tt> not found in the usual places.<br>
			Perhaps you need to edit the <tt>nidb.cfg.sample</tt> file and rename it to <tt>nidb.cfg</tt>? Make sure <tt>nidb.cfg</tt> exists and is in one of the following locations<br>
			<ul>
				<li><?=getcwd()?>/nidb.cfg
				<li><?=getcwd()?>/../nidb.cfg
				<li><?=getcwd()?>/../../prod/programs/nidb.cfg
				<li><?=getcwd()?>/../../../../prod/programs/nidb.cfg
				<li><?=getcwd()?>/../programs/nidb.cfg
				<li>/home/nidb/programs/nidb.cfg
				<li>/nidb/programs/nidb.cfg
			</ul>
			<?
			exit(0);
		}
		$cfg['cfgpath'] = $file;
		
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
	function SendGmail($to,$subject,$body,$debug, $usebcc) {
	
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

		$smtp = Mail::factory('smtp', array(
				'host' => "ssl://" . $GLOBALS['cfg']['emailserver'],
				'port' => 465,
				'auth' => true,
				'username' => $GLOBALS['cfg']['emailusername'],
				'password' => $GLOBALS['cfg']['emailpassword']
			));
	
		/* wrap the body in an HTML mime type and copywrite footer */
		//$body = "MIME-Version: 1.0\nContent-Type: multipart/mixed; BOUNDARY=\"$boundry\"\n\n--$boundry\nContent-Type: text/html\n"
		$body = "<html><body style=\"font-family: arial, helvetica, sans-serif\">" . $body . "<br><br><br><hr><small style='font-size:8pt; color: #666'>Email sent from " . $GLOBALS['cfg']['siteurl'] . ". If you received this email in error, please disregard it.<br><br>&copy; 2004-" . date("Y") . $GLOBALS['cfg']['sitename'] . ", powered by NiDB http://github.com/gbook/nidb</small></body></html>";
		$mail = $smtp->send($to, $headers, $body);

		if ($debug) {
			?>
			<table>
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
	/* ------- PrintSQLTable ---------------------- */
	/* -------------------------------------------- */
	function PrintSQLTable($result,$url,$orderby,$size) {
		$fields_num = mysql_num_fields($result);

		?>
		<table cellspacing="0" cellpadding="4" style="border-collapse:collapse; font-size:<?=$size?>pt; white-space:nowrap;">
			<tr>
		<?
		// printing table headers
		for($i=0; $i<$fields_num; $i++)
		{
			$field = mysql_fetch_field($result);
			$fieldname = $field->name;
			?>
			<td style="border: 1px solid black; background-color: #DDDDDD; padding-left:5px; padding-right:5px; font-weight:bold"><a href="<?=$url?>&orderby=<?=$fieldname?>"><?=$fieldname?></td>
			<?
		}
		echo "</tr>\n";
		if (mysql_num_rows($result) > 0) {
			// printing table rows
			while($row = mysql_fetch_row($result))
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
			mysql_data_seek($result, 0);
		}
		else {
			echo "</table>";
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- MySQLQuery ------------------------- */
	/* -------------------------------------------- */
	function MySQLQuery($sqlstring,$file,$line,$error="") {
		Debug($file, $line,"Running MySQL Query [$sqlstring]");
		$result = mysql_query($sqlstring);
		if ($result == false) {
			$datetime = date('r');
			$username = $GLOBALS['username'];
			$body = "<b>Query failed on [$datetime]:</b> $file (line $line)<br>
			<b>Error:</b> " . mysql_error() . "<br>
			<b>SQL:</b> $sqlstring<br><b>Username:</b> $username<br>
			<b>SESSION</b> <pre>" . print_r($_SESSION,true) . "</pre><br>
			<b>SERVER</b> <pre>" . print_r($_SERVER,true) . "</pre><br>
			<b>POST</b> <pre>" . print_r($_POST,true) . "</pre><br>
			<b>GET</b> <pre>" . print_r($_GET,true) . "</pre>";
			SendGmail($GLOBALS['cfg']['adminemail'],"User encountered error in $file",$body, 0);
			die("<div width='100%' style='border:1px solid red; background-color: #FFC; margin:10px; padding:10px; border-radius:5px; text-align: center'><b>Internal NiDB error.</b><br>The site administrator has been notified. Contact the administrator &lt;".$GLOBALS['cfg']['adminemail']."&gt; if you can provide additional information that may have led to the error<br><br><img src='images/topmen.gif'></div>");
		}
		else {
			return $result;
		}
	}
	
	/* -------------------------------------------- */
	/* ------- MySQLiQuery ------------------------ */
	/* -------------------------------------------- */
	function MySQLiQuery($sqlstring,$file,$line,$error="") {
		$result = mysqli_query($GLOBALS['linki'],$sqlstring) or die("<div width='100%' style='border:1px solid red; background-color: #FFC; margin:10px; padding:10px; border-radius:5px'><b>Query failed</b> $file (line $line) <b>" . mysqli_error($GLOBALS['linki']) . "</b><br><br><tt>$sqlstring</tt><br></div>");
		return $result;
	}
	
	
	/* -------------------------------------------- */
	/* ------- FormatUID -------------------------- */
	/* -------------------------------------------- */
	function FormatUID($uid) {
		//$str = substr($uid,0,1) . "&sdot;" . substr($uid,1,3) . "&sdot;" . substr($uid,4,3);
		//return $str;
		return $uid;
	}

	
	/* -------------------------------------------- */
	/* ------- GetInstanceID ---------------------- */
	/* -------------------------------------------- */
	function GetInstanceID() {
		$sqlstring = "select user_instanceid from users where username = '" . $GLOBALS['username'] . "'";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$instanceid = $row['user_instanceid'];
		return $instanceid;
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetInstanceName -------------------- */
	/* -------------------------------------------- */
	function GetInstanceName($id) {
		$sqlstring = "select instance_name from instance where instance_id = '$id'";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$n = $row['instance_name'];
		return $n;
	}	


	/* -------------------------------------------- */
	/* ------- GetDataPathFromSeriesID ------------ */
	/* -------------------------------------------- */
	function GetDataPathFromSeriesID($id, $modality) {
		$modality = strtolower($modality);
		
		$sqlstring = "select * from $modality"."_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality"."series_id = $id";
		$result = MySQLQuery($sqlstring, __FILE__, __LINE__);
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$seriesnum = $row['series_num'];
		$subjectid = $row['subject_id'];
		$studyid = $row['study_id'];
		
		$path = $GLOBALS['cfg']['archivedir'] . "/$uid/$studynum/$seriesnum";
		return array($path, $uid, $studynum, $studyid, $subjectid);
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetAlternateUIDs ------------------- */
	/* -------------------------------------------- */
	function GetAlternateUIDs($subjectid, $enrollmentid) {
	
		$sqlstring = "select * from subject_altuid where subject_id = '$subjectid' and enrollment_id = '$enrollmentid' order by altuid";
		//$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		$result = MySQLQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$isprimary = $row['isprimary'];
			if ($isprimary) {
				$altuids[] = '*'. $row['altuid'];
			}
			else {
				$altuids[] = $row['altuid'];
			}
		}
		
		return $altuids;
	}

	
	/* -------------------------------------------- */
	/* ------- implode2 --------------------------- */
	/* -------------------------------------------- */
	/* special implode which checks for empty array */
	function implode2($chr, $arr) {
		if (count($arr) > 1) {
			return implode($chr,$arr);
		}
		else {
			return $arr[0];
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
		//PrintSQL($sqlstring);
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");
		
		/* delete rows other than the most recent 15 items */
		$sqlstring = "DELETE FROM `mostrecent` WHERE mostrecent_id NOT IN ( SELECT mostrecent_id FROM ( SELECT mostrecent_id FROM `mostrecent` where user_id = $userid ORDER BY mostrecent_date DESC LIMIT 15) foo) and user_id = $userid";
		//PrintSQL($sqlstring);
		$result = mysql_query($sqlstring) or die("Query failed: " . mysql_error() . "<br><i>$sqlstring</i><br>");

	}
	
	
	/* -------------------------------------------- */
	/* ------- NavigationBar ---------------------- */
	/* -------------------------------------------- */
	function NavigationBar($title, $urllist, $displayaccess = 0, $phiaccess = 1, $dataaccess = 1, $phiprojectlist = array(), $dataprojectlist = array()) {
		?>
		<table width="100%" cellspacing="0">
			<tr>
				<td><span class="headertable1"><?=$title?></span>
				<br>
				<span class="headertable2">
				<?
				foreach ($urllist as $label => $url) {
					?>
					<a href="<?=$url?>"><?=$label?></a> &gt; 
					<?
				}
				?>
				</span>
				</td>
			</tr>
		</table>

		<?
		if ($displayaccess) {
			if ($phiaccess) {
				if ($dataaccess) {
					$accessmessage = "<b>Data</b> and <b>PHI</b> access";
				}
				else {
					$accessmessage = "<b>PHI</b> access";
				}
			}
			else {
				if ($dataaccess) {
					$accessmessage = "<b>Data</b> access";
				}
				else {
					$accessmessage = "No <b>data</b> or <b>PHI</b> access";
				}
			}
			
			if (($phiaccess) || ($dataaccess)) {
				$projectlist = "<ul>";
				if (!empty($phiprojectlist)) {
					foreach ($phiprojectlist as $phiproject) {
						$projectlist .= "<li>[PHI] $phiproject\n";
					}
				}
				if (!empty($dataprojectlist)) {
					foreach ($dataprojectlist as $dataproject) {
						$projectlist .= "<li>[Data] $dataproject\n";
					}
				}
				$projectlist .= "</ul>";
			}
			
			//print_r($phiprojectlist);
		?>
		<details style="font-size:8pt; margin-left:15px; color: #666666">
		<summary><?=$accessmessage?></summary>
		<?=$projectlist?>
		</details>
		<?
		}
		?>
		<br><br>
		<?
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
?>
