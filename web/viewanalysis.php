<?
 // ------------------------------------------------------------------------------
 // NiDB viewanalysislogs.php
 // Copyright (C) 2004 - 2018
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
	
	require "functions.php";
	require "includes.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$pipelineid = GetVariable("pipelineid");
	$pipelineversion = GetVariable("pipelineversion");
	$analysisid = GetVariable("analysisid");
	$studyid = GetVariable("studyid");
	$fileviewtype = GetVariable("fileviewtype");

?>
<body>
<div style="font-size:10pt">
<?	
	/* determine action */
	switch ($action) {
		case 'viewlogs': DisplayLogs($analysisid); break;
		case 'viewfiles': DisplayFiles($analysisid, $fileviewtype); break;
		case 'viewresults': DisplayResults($analysisid, $studyid); break;
		case 'viewhistory': DisplayHistory($analysisid); break;
		case 'viewgraph': DisplayGraph($analysisid); break;
		default:
	}
?></div><?
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayLogs ------------------------ */
	/* -------------------------------------------- */
	function DisplayLogs($analysisid) {
		if (!ValidID($analysisid,'Analysis ID')) { return; }

		$sqlstring = "select * from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on e.pipeline_id = a.pipeline_id where a.analysis_id = '$analysisid'";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$pipelinename = $row['pipeline_name'];
		$pipelineid = $row['pipeline_id'];
		$pipelineversion = $row['pipeline_version'];
		$pipeline_level = $row['pipeline_level'];
		$pipelinedirectory = $row['pipeline_directory'];

		if (($pipelineid == "") || ($pipelineid == 0)) { echo "Invalid pipeline ID<Br>"; return; }
		if (($pipelineversion == "") || ($pipelineversion == 0)) { echo "Invalid pipeline version<Br>"; return; }
		
		/* get list of steps for the appropriate version */
		$sqlstring = "select * from pipeline_steps where pipeline_id = $pipelineid and pipeline_version = $pipelineversion";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$ps_command = $row['ps_command'];
			$ps_supplement = $row['ps_supplement'];
			$ps_description = $row['ps_description'];
			$ps_order = $row['ps_order'] - 1;
			if ($ps_supplement) {
				$descriptions['supp'][$ps_order] = $ps_description;
				$commands['supp'][$ps_order] = $ps_command;
			}
			else {
				$descriptions['reg'][$ps_order] = $ps_description;
				$commands['reg'][$ps_order] = $ps_command;
			}
		}
		//PrintVariable($descriptions);
		
		/* build the correct path */
		if (($pipeline_level == 1) && ($pipelinedirectory == "")) {
			$path = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename/pipeline";
			//echo "(1) Path is [$path]<br>";
		}
		elseif (($pipeline_level == 0) || ($pipelinedirectory != "")) {
			$path = $GLOBALS['cfg']['mountdir'] . "$pipelinedirectory/$uid/$studynum/$pipelinename/pipeline";
			//echo "(2) Path is [$path]<br>";
		}
		else {
			$path = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename/pipeline";
			//echo "(3) Path is [$path]<br>";
		}
		
		/* check if the path exists */
		if (file_exists($path)) {
			?>
			Showing log files from <b><?=$path?></b>
			<br><br>
			<?
			$files = scandir($path);
			$logs = array_diff($files, array('..', '.'));
			natsort($logs);
			foreach ($logs as $log) {
				$file = file_get_contents("$path/$log");
				$size = filesize("$path/$log");
				$filedate = date ("F d Y H:i:s.", filemtime("$path/$log"));
				
				if (preg_match('/^step(\d*)\.log/', $log, $matches)) {
					//echo "<pre>";
					//print_r($matches);
					//echo "</pre>";
					$step = $matches[1];
					$command = $commands['reg'][$step];
					$desc = $descriptions['reg'][$step];
				}
				elseif (preg_match('/^supplement-step(\d*)\.log/', $log, $matches)) {
					//echo "<pre>";
					//print_r($matches);
					//echo "</pre>";
					$step = $matches[1];
					$command = $commands['supp'][$step];
					$desc = $descriptions['supp'][$step];
				}
				?>
				<details>
					<summary><?="<b>$log</b>"?> <span class="tiny"><?=number_format($size)?> bytes - <?=$filedate?></style> &nbsp; <span style="color: darkred;"><?=$desc?></span></span></summary>
					<pre style="font-size:9pt; background-color: #EEEEEE">
<?=$file?>
					</pre>
				</details>
				<?
			}
		}
		else {
			echo "<b>$path does not exist</b><br><br>Perhaps data is still being downloaded by the pipeline.pl program?<br>";
		}
	}


	/* -------------------------------------------- */
	/* ------- DisplayFiles ----------------------- */
	/* -------------------------------------------- */
	function DisplayFiles($analysisid, $fileviewtype) {
		if (!ValidID($analysisid,'Analysis ID')) { return; }
	
		$sqlstring = "select * from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on e.pipeline_id = a.pipeline_id where a.analysis_id = $analysisid";
		//echo $sqlstring;
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$pipelinename = $row['pipeline_name'];
		$pipelineid = $row['pipeline_id'];
		$pipeline_level = $row['pipeline_level'];
		$pipelinedirectory = $row['pipeline_directory'];
		
		//$path = $GLOBALS['pipelinedatapath'] . "/$uid/$studynum/$pipelinename/";
		/* build the correct path */
		//if (($pipeline_level == 1) && ($pipelinedirectory == "")) {
		if ($pipeline_level == 1) {
			$path = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
			echo "(1) Path is [$path]<br>";
		}
		//elseif (($pipeline_level == 0) || ($pipelinedirectory != "")) {
		elseif ($pipeline_level == 0) {
			$path = $GLOBALS['cfg']['mountdir'] . "$pipelinedirectory/$uid/$studynum/$pipelinename/pipeline";
			echo "(2) Path is [$path]<br>";
		}
		else {
			$path = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename";
			echo "(3) Path is [$path]<br>";
		}
		
		$origfileslog = $path . "origfiles.log";
		$finfo = finfo_open(FILEINFO_MIME);
		if ((!file_exists($origfileslog)) || ($fileviewtype == "filesystem")) {
			$files = find_all_files($path);
			//print_r($files);
			?>
			Showing files from <b><?=$path?></b> (<?=count($files)?> files) <span class="tiny">Reading from filesystem</span>
			<br><br>
			<table cellspacing="0" cellpadding="2">
				<tr>
					<td style="font-weight: bold; border-bottom:2px solid #999999">File</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Timestamp</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Permissions</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Size <span class="tiny">bytes</span></td>
				</tr>
			<?
			foreach ($files as $line) {
				//$file\t$mtime\t$perm\t$isdir\t$islink\t$size
				
				$timestamp2 = "N/A";
				$perm2 = 'N/A';
				$islink2 = '';
				$isdir2 = '';
				$size2 = 0;
				list($file,$timestamp1,$perm1,$isdir1,$islink1,$size1) = explode("\t",$line);
				
				if (is_link($file)) { $islink2 = 1; }
				if (is_dir($file)) { $isdir2 = 1; }
				if (file_exists($file)) {
					$timestamp2 = filemtime($file);
					$perm2 = substr(sprintf('%o', fileperms($file)), -4);
					$size2 = filesize($file);
					//if (substr(finfo_file($finfo, "/mount$file"), 0, 4) == 'text') {
					//	$istext = true;
					//}
					//else {
					//	$istext = false;
					//}
					$filetype = "";
					if (stristr(strtolower($file),'.nii') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.nii.gz') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.inflated') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.smoothwm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.sphere') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.pial') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.fsm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.orig') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.png') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.ppm') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpeg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.gif') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.txt') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.log') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.sh') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.job') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".o") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".e") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".par") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".mat") !== FALSE) { $filetype = 'text'; }
					if ($istext) { $filetype = "text"; }
					//echo "[$file $filetype]";
				}
				$filecolor = "black";
				if ($islink2) { $filecolor = "red"; } else { $filecolor = ''; }
				if ($isdir1) { $filecolor = "darkblue"; $fileweight = ''; } else { $filecolor = ''; $fileweight = ''; }
				
				$clusterpath = str_replace('/mount','',$path);
				$displayfile = str_replace($clusterpath,'',$file);
				$lastslash = strrpos($displayfile,'/');
				$displayfile = substr($displayfile,0,$lastslash) . '<b>' . substr($displayfile,$lastslash) . '</b>';
				
				$displayperms = '';
				for ($i=1;$i<=3;$i++) {
					switch (substr($perm2,$i,1)) {
						case 0: $displayperms .= '---'; break;
						case 1: $displayperms .= '--x'; break;
						case 2: $displayperms .= '-w-'; break;
						case 3: $displayperms .= '-wx'; break;
						case 4: $displayperms .= 'r--'; break;
						case 5: $displayperms .= 'r-x'; break;
						case 6: $displayperms .= 'rw-'; break;
						case 7: $displayperms .= 'rwx'; break;
					}
				}
				?>
				<tr>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD; color:<?=$filecolor?>; font-weight: <?=$fileweight?>">
					<?
						switch ($filetype) {
							case 'text':
					?>
					<a href="viewfile.php?file=<?="$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'image':
					?>
					<a href="viewimagefile.php?file=<?="$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'nifti':
							case 'mesh':
					?>
					<a href="viewimage.php?type=<?=$filetype?>&filename=<?="$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							default:
					?>
					<?=$displayfile?>
					<? } ?>
					</td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=date("M j, Y H:i:s",$timestamp2)?></span></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=$displayperms?></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=number_format($size2)?></td>
				</tr>
				<?
			}
		}
		else {
			$origfiles = file_get_contents($path . "origfiles.log");
			
			$files = explode("\n",trim($origfiles));
			?>
			Showing files from <b><?=$path?></b> (<?=count($files)?> files) <span class="tiny">Reading from origfiles.log</span> Read from <a href="pipelines.php?action=viewfiles&analysisid=<?=$analysisid?>&fileviewtype=filesystem">filesystem</a>
			<br><br>
			<table cellspacing="0" cellpadding="2">
				<tr>
					<td style="font-weight: bold; border-bottom:2px solid #999999">File</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Timestamp</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Permissions</td>
					<td style="font-weight: bold; border-bottom:2px solid #999999">Size <span class="tiny">bytes</span></td>
				</tr>
			<?
			foreach ($files as $line) {
				//$file\t$mtime\t$perm\t$isdir\t$islink\t$size
				
				$timestamp2 = "N/A";
				$perm2 = 'N/A';
				$islink2 = '';
				$isdir2 = '';
				$size2 = 0;
				list($file,$timestamp1,$perm1,$isdir1,$islink1,$size1) = explode("\t",$line);
				
				//if (is_link('/mount' . $file)) { $islink2 = 1; }
				//if (is_dir('/mount' . $file)) { $isdir2 = 1; }
				if (file_exists('/mount' . $file)) {
					#$timestamp2 = filemtime('/mount' . $file);
					#$perm2 = substr(sprintf('%o', fileperms('/mount' . $file)), -4);
					#$size2 = filesize('/mount' . $file);
					//if (substr(finfo_file($finfo, "/mount$file"), 0, 4) == 'text') {
					//	$istext = true;
					//}
					//else {
					//	$istext = false;
					//}
					$filetype = "";
					if (stristr(strtolower($file),'.nii') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.nii.gz') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.inflated') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.smoothwm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.sphere') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.pial') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.fsm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.orig') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.png') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.ppm') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.jpeg') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.gif') !== FALSE) { $filetype = 'image'; }
					if (stristr(strtolower($file),'.txt') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.log') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.sh') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.job') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".o") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".e") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".par") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".mat") !== FALSE) { $filetype = 'text'; }
					if ($istext) { $filetype = "text"; }
					if ($size1 < 1) { $filetype = ""; }
					
				}
				$filecolor = "black";
				if ($islink2) { $filecolor = "red"; } else { $filecolor = ''; }
				if ($isdir1) { $filecolor = "darkblue"; $fileweight = ''; } else { $filecolor = ''; $fileweight = ''; }
				
				$clusterpath = str_replace('/mount','',$path);
				$displayfile = str_replace($clusterpath,'',$file);
				$lastslash = strrpos($displayfile,'/');
				$displayfile = substr($displayfile,0,$lastslash) . '<b>' . substr($displayfile,$lastslash) . '</b>';
				
				$displayperms1 = '';
				for ($i=1;$i<=3;$i++) {
					switch (substr($perm1,$i,1)) {
						case 0: $displayperms1 .= '---'; break;
						case 1: $displayperms1 .= '--x'; break;
						case 2: $displayperms1 .= '-w-'; break;
						case 3: $displayperms1 .= '-wx'; break;
						case 4: $displayperms1 .= 'r--'; break;
						case 5: $displayperms1 .= 'r-x'; break;
						case 6: $displayperms1 .= 'rw-'; break;
						case 7: $displayperms1 .= 'rwx'; break;
					}
				}
				?>
				<tr>
					<td style="font-size:9pt; border-bottom: solid 1px #DDDDDD; color:<?=$filecolor?>; font-weight: <?=$fileweight?>">
					<?
						switch ($filetype) {
							case 'text':
					?>
					<a href="viewfile.php?file=<?="/mount$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'image':
					?>
					<a href="viewimagefile.php?file=<?="/mount$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'nifti':
							case 'mesh':
					?>
					<a href="viewimage.php?type=<?=$filetype?>&filename=<?="/mount$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							default:
					?>
					<?=$displayfile?>
					<? } ?>
					</td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=date("M j, Y H:i:s",$timestamp1)?><? //if ($timestamp1 != $timestamp2) { echo "&nbsp;<span class='smalldiff'>$timestamp2</span>"; } ?></span></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=$displayperms1?><? //if ($perm1 != $perm2) { echo "&nbsp;<span class='smalldiff'>$perm2</span>"; } ?></td>
					<td style="font-size:10pt; border-bottom: solid 1px #DDDDDD"><?=number_format($size1)?><? //if ($size1 != $size2) { echo "&nbsp;<span class='smalldiff'>" . number_format($size2) . "</span>"; } ?></td>
				</tr>
				<?
			}
			?>
			</table>
			<?
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayResults --------------------- */
	/* -------------------------------------------- */
	function DisplayResults($analysisid, $studyid) {
		if (!ValidID($analysisid,'Analysis ID')) { return; }
		
		?>
		Results for this analysis<br><br>
		<table class="smalldisplaytable">
		<?
			if ($studyid == "") {
				$sqlstring2 = "select a.*, d.result_name from analysis_results a left join analysis b on a.analysis_id = b.analysis_id left join pipelines c on b.pipeline_id = c.pipeline_id left join analysis_resultnames d on d.resultname_id = a.result_nameid where a.analysis_id = $analysisid order by d.result_name";
			}
			else {
				if (!ValidID($studyid,'Study ID')) { return; }
				$sqlstring2 = "select a.*, d.result_name from analysis_results a left join analysis b on a.analysis_id = b.analysis_id left join pipelines c on b.pipeline_id = c.pipeline_id left join analysis_resultnames d on d.resultname_id = a.result_nameid where b.study_id = $studyid and a.analysis_id = $analysisid order by d.result_name";
			}
			$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
			while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
				//$step = $row['analysis_step'];
				$pipelinename = $row2['pipeline_name'];
				$type = $row2['result_type'];
				$size = $row2['result_size'];
				$name = $row2['result_name'];
				$text = $row2['result_text'];
				$value = $row2['result_value'];
				$units = $row2['result_units'];
				$filename = $row2['result_filename'];
				$swversion = $row2['result_softwareversion'];
				$important = $row2['result_isimportant'];
				$lastupdate = $row2['result_lastupdate'];
							
				if (strpos($units,'^') !== false) {
					$units = str_replace('^','<sup>',$units);
					$units .= '</sup>';
				}
				if ($important) { $bold = 'bold'; } else { $bold = 'normal'; }
				?>
				<tr style="font-weight: <?=$bold?>">
					<td><?=$name?></td>
					<td align="right">
						<?
							switch($type) {
								case "v":
									echo "$value";
									break;
								case "f":
									echo $filename;
									break;
								case "t":
									echo $text;
									break;
								case "h":
									echo $filename;
									break;
								case "i":
									?>
									<img src="preview.php?image=/mount<?=$filename?>" style="max-width:800px">
									<?
									break;
							}
						?>
					</td>
					<td style="padding-left:0px"><?=$units?></td>
					<!--<td><?=$size?></td>-->
					<td><?=$swversion?></td>
					<td nowrap><?=$lastupdate?></td>
				</tr>
				<?
			}
		?>
		</table>
		<?
	}	


	/* -------------------------------------------- */
	/* ------- DisplayHistory --------------------- */
	/* -------------------------------------------- */
	function DisplayHistory($analysisid) {
		if (!ValidID($analysisid,'Analysis ID')) { return; }
		
		?>
		<table class="smalldisplaytable">
			<thead>
				<tr>
					<th>Cumulative time</th>
					<th>Date/time</th>
					<th>Pipeline version</th>
					<th>Hostname</th>
					<th>Event</th>
					<th>Message</th>
				</tr>
			</thead>
		<?
		$sqlstring = "select pipeline_version, analysis_event, analysis_hostname, event_message, unix_timestamp(event_datetime) 'event_datetime' from analysis_history where analysis_id = '$analysisid' order by event_datetime asc";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		/* get the first event to get the starting time */
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipeline_version = $row['pipeline_version'];
		$analysis_event = $row['analysis_event'];
		$analysis_hostname = $row['analysis_hostname'];
		$event_message = $row['event_message'];
		$startdatetime = $row['event_datetime'];
		$event_datetime = date('D, Y-m-d H:i:s',$startdatetime);
		?>
		<tr>
			<td>0</td>
			<td nowrap><?=$event_datetime?></td>
			<td><?=$pipeline_version?></td>
			<td><?=$analysis_hostname?></td>
			<td><?=$analysis_event?></td>
			<td><?=$event_message?></td>
		</tr>
		<?
		/* continue on with the rest of the events */
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$pipeline_version = $row['pipeline_version'];
			$analysis_event = $row['analysis_event'];
			$analysis_hostname = $row['analysis_hostname'];
			$event_message = $row['event_message'];
			$event_datetime = $row['event_datetime'];
			$cumtime = FormatCountdown($event_datetime - $startdatetime);
			?>
			<tr>
				<td><?=$cumtime?></td>
				<td nowrap"><?=date('D, Y-m-d H:i:s',$event_datetime)?></td>
				<td><?=$pipeline_version?></td>
				<td><?=$analysis_hostname?></td>
				<td><?=$analysis_event?></td>
				<td><?=$event_message?></td>
			</tr>
			<?
		}
		?>
		</table>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayGraph ----------------------- */
	/* -------------------------------------------- */
	function DisplayGraph($analysisid) {
		if (!ValidID($analysisid,'Analysis ID')) { return; }
		
		$imgdata = CreateGraphFromAnalysisID($analysisid);
		
		?>
		Graph for [<?=$analysisid?>]
		<img border=1 src='data:image/png;base64,<?=$imgdata?>'>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- CreateGraphFromAnalysisID ---------- */
	/* -------------------------------------------- */
	function CreateGraphFromAnalysisID($analysisid) {

		$dotfile = tempnam("/tmp",'DOTDOT');
		$pngfile = tempnam("/tmp",'DOTPNG');
		
		$d[] = "digraph G {";
		$sqlstring = "select * from pipelines where pipeline_id in (select pipeline_id from analysis where analysis_id = $analysisid)";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$pipelinename = $row['pipeline_name'];
			$deps = $row['pipeline_dependency'];
			$groupids = $row['pipeline_groupid'];
			
			if ($deps != '') {
				$sqlstringA = "select * from pipelines where pipeline_id in ($deps)";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$depname = $rowA['pipeline_name'];
					$d[] = " \"$depname\" -> \"$pipelinename\";";
				}
			}
			
			if ($groupids != '') {
				$sqlstringA = "select * from groups where group_id in ($groupids)";
				$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
				while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
					$groupname = $rowA['group_name'];
					$d[] = " \"$groupname\" -> \"$pipelinename\";";
					$d[] = " \"$groupname\" [shape=box,style=filled,color=\"lightblue\"];";
				}
			}
		}
		$d[] = "}";
		$d = array_unique($d);
		$dot = implode("\n",$d);
		echo "<pre>$dot</pre>";
		file_put_contents($dotfile,$dot);
		$systemstring = "dot -Tpng $dotfile -o $pngfile";
		exec($systemstring);
		//echo $dot;
		$imdata = base64_encode(file_get_contents($pngfile));
		return $imdata;
	}
?>
</body>