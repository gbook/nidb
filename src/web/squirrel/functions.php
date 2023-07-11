<?
 // ------------------------------------------------------------------------------
 // NiDB squirrel/functions.php
 // Copyright (C) 2004 - 2023
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
		<br>
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
		<br>
		<?
	}


	/* -------------------------------------------- */
	/* ------- Notice ----------------------------- */
	/* -------------------------------------------- */
	function Notice($msg, $title="Notice") {
		?>
		<div class="ui text container">
			<div class="ui info message">
				<i class="close icon"></i>
				<div class="header"><?=$title?></div>
				<p><?=$msg?></p>
			</div>
		</div>
		<br>
		<?
	}
?>
