<?
 // ------------------------------------------------------------------------------
 // NiDB viewanalysislogs.php
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
	
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$pipelineid = GetVariable("pipelineid");
	$pipelineversion = GetVariable("pipelineversion");
	$analysisid = GetVariable("analysisid");
	$studyid = GetVariable("studyid");
	$fileviewtype = GetVariable("fileviewtype");

?>
<body style="padding: 10px">
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
		if (!ValidID($analysisid,'Analysis ID - DisplayLogs()')) { return; }

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
		$pipelinedirstructure = $row['pipeline_dirstructure'];

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
		
		/* build the correct path */
		if (($pipeline_level == 1) && ($pipelinedirectory == "")) {
			if ($pipelinedirstructure == "b") {
				$path = $GLOBALS['cfg']['analysisdirb'] . "/$pipelinename/$uid/$studynum/pipeline";
			}
			else {
				$path = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename/pipeline";
			}
		}
		elseif (($pipeline_level == 0) || ($pipelinedirectory != "")) {
			
			if ($pipelinedirstructure == "b") {
				$path = $GLOBALS['cfg']['analysisdirb'] . "/$pipelinename/$uid/$studynum/pipeline";
			}
			else {
				$path = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename/pipeline";
			}
		}
		else {
			$path = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename/pipeline";
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
				$filedate = date ("F d Y H:i:s", filemtime("$path/$log"));
				
				if (substr($log, 0, 4) == "Step") {
					$stepnum = str_replace("Step", "", $log) - 1;
					$command = $commands['reg'][$stepnum];
					$desc = $descriptions['reg'][$stepnum];
				}
				
				?>
				<details>
					<summary><?="<b>$log</b>"?> <span class="tiny"><?=number_format($size)?> bytes - <?=$filedate?></span> &nbsp; <div class="ui basic label"><tt><?=$command?></tt></div> <div class="ui green label"><?=$desc?></div></summary>
					<pre style="font-size:9pt; background-color: #EEEEEE; padding: 6px">
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
		if (!ValidID($analysisid,'Analysis ID - DisplayFiles()')) { return; }
	
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
		$pipelinedirstructure = $row['pipeline_dirstructure'];
		
		/* build the correct path */
		if ($pipeline_level == 1) {
			if ($pipelinedirstructure == "b") {
				$path = $GLOBALS['cfg']['analysisdirb'] . "/$pipelinename/$uid/$studynum";
			}
			else {
				$path = $GLOBALS['cfg']['analysisdir'] . "/$uid/$studynum/$pipelinename";
			}
			#echo "(1) Path is [$path]<br>";
		}
		elseif ($pipeline_level == 0) {
			$path = "$pipelinedirectory/$uid/$studynum/$pipelinename";
			#echo "(2) Path is [$path]<br>";
		}
		else {
			$path = $GLOBALS['cfg']['groupanalysisdir'] . "/$pipelinename";
			#echo "(3) Path is [$path]<br>";
		}
		
		$origfileslog = $path . "origfiles.log";
		$finfo = finfo_open(FILEINFO_MIME);
		if ((!file_exists($origfileslog)) || ($fileviewtype == "filesystem")) {
			$files = find_all_files($path);
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
					$filetype = "";
					if (stristr(strtolower($file),'.nii') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.nii.gz') !== FALSE) { $filetype = 'nifti'; }
					if (stristr(strtolower($file),'.inflated') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.smoothwm') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.sphere') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.pial') !== FALSE) { $filetype = 'mesh'; }
					if (stristr(strtolower($file),'.stats') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.label') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.touch') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.html') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.htm') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.fsf') !== FALSE) { $filetype = 'text'; }
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
					if (stristr(strtolower($file),'.tsv') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.json') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.bval') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.bvec') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),'.job') !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".o") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".e") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".par") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".mat") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".m") !== FALSE) { $filetype = 'text'; }
					if (stristr(strtolower($file),".css") !== FALSE) { $filetype = 'text'; }
					if ($istext) { $filetype = "text"; }
					//echo "[$file $filetype]";
				}
				$filecolor = "black";
				if ($islink2) { $filecolor = "red"; } else { $filecolor = ''; }
				if ($isdir1) { $filecolor = "darkblue"; $fileweight = ''; } else { $filecolor = ''; $fileweight = ''; }
				
				$clusterpath = str_replace($GLOBALS['cfg']['mountdir'],'',$path);
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
					<a href="niiview.php?type=<?=$filetype?>&filename=<?="$file"?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
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
				
				if (file_exists($GLOBALS['cfg']['mountdir'] . "/$file")) {
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
				
				$clusterpath = str_replace($GLOBALS['cfg']['mountdir'],'',$path);
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
						$fullpath = $GLOBALS['cfg']['mountdir'] . "/$file";
						switch ($filetype) {
							case 'text':
					?>
					<a href="viewfile.php?file=<?=$fullpath?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'image':
					?>
					<a href="viewimagefile.php?file=<?=$fullpath?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
					<?
								break;
							case 'nifti':
							case 'mesh':
					?>
					<a href="viewimage.php?type=<?=$filetype?>&filename=<?=$fullpath?>"><span style="color:<?=$filecolor?>; font-weight: <?=$fileweight?>"><?=$displayfile?></span></a>
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
		if (!ValidID($analysisid,'Analysis ID - DisplayResults(a)')) { return; }
		
		$analysispath = GetAnalysisPath($analysisid);
		
		?>
		<script>
			$(document).ready(function(){
				$('#pageloading').hide();
			});
		</script>
		
		<div class="ui yellow message" align="center" id="pageloading">
			<h2 class="ui header">
				<em data-emoji=":chipmunk:" class="loading"></em> Loading...
			</h2>
		</div>
		
		<div class="ui top attached raised segment">
			<div class="ui two column grid">
				<div class="ui column">
					<b>Results for</b> <code><?=$analysispath?></code>
				</div>

				<div class="ui column">
					<div class="ui labeled icon input">
						<div class="ui label">Filter Results</div>
						<input id="resultsnamefilter" type="text" placeholder="Result name"/>
						<i class="filter icon"></i>
					</div>
				</div>
			</div>
		</div>
		<div class="ui attached long scrolling raised segment">

			<script type="text/javascript">
				function filterTable(event) {
					var filter = event.target.value.toUpperCase();
					var rows = document.querySelector("#resultstable tbody").rows;
					
					for (var i = 0; i < rows.length; i++) {
						var firstCol = rows[i].cells[0].textContent.toUpperCase();
						var secondCol = rows[i].cells[1].textContent.toUpperCase();
						if (firstCol.indexOf(filter) > -1 || secondCol.indexOf(filter) > -1) {
							rows[i].style.display = "";
						} else {
							rows[i].style.display = "none";
						}      
					}
				}

				document.querySelector('#resultsnamefilter').addEventListener('keyup', filterTable, false);
			</script>
		
			<table class="smalldisplaytable" id="resultstable">
				<tbody>
		<?
			if ($studyid == "") {
				$sqlstring2 = "select a.*, d.result_name from analysis_results a left join analysis b on a.analysis_id = b.analysis_id left join pipelines c on b.pipeline_id = c.pipeline_id left join analysis_resultnames d on d.resultname_id = a.result_nameid where a.analysis_id = $analysisid order by a.result_type, d.result_name";
			}
			else {
				if (!ValidID($studyid,'Study ID - DisplayResults(b)')) { return; }
				$sqlstring2 = "select a.*, d.result_name from analysis_results a left join analysis b on a.analysis_id = b.analysis_id left join pipelines c on b.pipeline_id = c.pipeline_id left join analysis_resultnames d on d.resultname_id = a.result_nameid where b.study_id = $studyid and a.analysis_id = $analysisid order by a.result_type, d.result_name";
			}
			$result2 = MySQLiQuery($sqlstring2, __FILE__, __LINE__);
			$numresults = mysqli_num_rows($result2);
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
				
				if (!file_exists($filename)) { $filename = $GLOBALS['cfg']['mountdir'] . "/$filename"; }
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
									<img src="preview.php?image=<?=$filename?>" style="max-width:400px">
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
			</tbody>
			</table>
		</div>
		<div class="ui bottom attached inverted vertically fitted raised segment">
			Found <?=$numresults?> results
		</div>
		<?
	}	


	/* -------------------------------------------- */
	/* ------- DisplayHistory --------------------- */
	/* -------------------------------------------- */
	function DisplayHistory($analysisid) {
		if (!ValidID($analysisid,'Analysis ID - DisplayHistory()')) { return; }
		
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
		if (!ValidID($analysisid,'Analysis ID - DisplayGraph()')) { return; }
		
		/* get all information about this analysis, pipeline, parent/child pipelines, and groups */
		$sqlstring = "select * from analysis where analysis_id = $analysisid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipelineid = $row['pipeline_id'];
		$pipelineversion = (int)$row['pipeline_version'];
		$pipelinedependency = $row['pipeline_dependency'];
		$studyid = $row['study_id'];
		$datalog = $row['analysis_datalog'];
		$datatable = $row['analysis_datatable'];
		
		if (!ValidID($pipelineid,'Pipeline ID')) { return; }
		if (!ValidID($studyid,'Study ID')) { return; }
		
		if ($datatable == "") { $datatable = "No record of data download found. Check <tt>data.log</tt> in the pipeline directory"; }
		else { $datatable = "<pre><tt>$datatable</tt></pre>"; }

		$sqlstring = "select a.study_num, c.uid from studies a left join enrollment b on a.enrollment_id = b.enrollment_id left join subjects c on b.subject_id = c.subject_id where a.study_id = '$studyid'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		
		$sqlstring = "select * from pipelines where pipeline_id = $pipelineid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$pipelinename = $row['pipeline_name'];
		$deps = $row['pipeline_dependency'];
		$groupids = $row['pipeline_groupid'];
		
		if ($deps != '') {
			$sqlstringA = "select * from pipelines where pipeline_id in ($deps)";
			$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$depnames[] = $rowA['pipeline_name'];
			}
		}
		$dependencylist = implode2("<br>", $depnames);
		if ($dependencylist == "") { $dependencylist = "None"; }
		
		if ($groupids != '') {
			$sqlstringA = "select * from groups where group_id in ($groupids)";
			$resultA = MySQLiQuery($sqlstringA,__FILE__,__LINE__);
			while ($rowA = mysqli_fetch_array($resultA, MYSQLI_ASSOC)) {
				$groupnames[] = $rowA['group_name'];
			}
		}
		$grouplist = implode2("<br>", $groupnames);
		if ($grouplist == "") { $grouplist = "None"; }

		$datasearchtable = "<table class='ui very compact celled table'>
			<thead>
			<tr>
				<th></th>
				<th>Enabled</th>
				<th>Optional</th>
				<th>Protocol</th>
				<th>Modality</th>
				<th>Image type</th>
				<th>Data format</th>
				<th>Series criteria</th>
				<th>Type</th>
				<th>Level</th>
				<th>Association type</th>
				<th>Num BOLD reps</th>
				<th>gzip</th>
				<th>Directory</th>
				<th>Use series?</th>
				<th>Preserve series?</th>
				<th>Use phase dir?</th>
				<th>Beh format</th>
				<th>Beh dir</th>
			</tr>
			</thead>";
		$sqlstring = "select * from pipeline_data_def where pipeline_id = $pipelineid and pipeline_version = '$pipelineversion' order by pdd_order + 0";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$pipelinedatadef_id = $row['pipelinedatadef_id'];
			$pdd_order = $row['pdd_order'];
			$pdd_seriescriteria = $row['pdd_seriescriteria'];
			$pdd_type = $row['pdd_type'];
			$pdd_level = $row['pdd_level'];
			$pdd_assoctype = $row['pdd_assoctype'];
			$pdd_protocol = $row['pdd_protocol'];
			$pdd_imagetype = $row['pdd_imagetype'];
			$pdd_modality = $row['pdd_modality'];
			$pdd_dataformat = $row['pdd_dataformat'];
			$pdd_gzip = $row['pdd_gzip'];
			$pdd_location = $row['pdd_location'];
			$pdd_useseries = $row['pdd_useseries'];
			$pdd_preserveseries = $row['pdd_preserveseries'];
			$pdd_usephasedir = $row['pdd_usephasedir'];
			$pdd_behformat = $row['pdd_behformat'];
			$pdd_behdir = $row['pdd_behdir'];
			$pdd_enabled = $row['pdd_enabled'];
			$pdd_optional = $row['pdd_optional'];
			$pdd_numboldreps = $row['pdd_numboldreps'];
			
			if ($pdd_enabled) $pdd_enabled = "&#10004;";
			if ($pdd_optional) $pdd_optional = "&#10004;";
			if ($pdd_gzip) $pdd_gzip = "&#10004;";
			if ($pdd_useseries) $pdd_useseries = "&#10004;";
			if ($pdd_preserveseries) $pdd_preserveseries = "&#10004;";
			if ($pdd_usephasedir) $pdd_usephasedir = "&#10004;";
			
			$datasearchtable .= "<tr>
								<td>$pdd_order</td>
								<td>$pdd_enabled</td>
								<td>$pdd_optional</td>
								<td><b><tt>$pdd_protocol</tt></b></td>
								<td>$pdd_modality</td>
								<td><tt>$pdd_imagetype</tt></td>
								<td>$pdd_dataformat</td>
								<td>$pdd_seriescriteria</td>
								<td>$pdd_type</td>
								<td>$pdd_level</td>
								<td>$pdd_assoctype</td>
								<td>$pdd_numboldreps</td>
								<td>$pdd_gzip</td>
								<td><tt>$pdd_location</tt></td>
								<td>$pdd_useseries</td>
								<td>$pdd_preserveseries</td>
								<td>$pdd_usephasedir</td>
								<td>$pdd_behformat</td>
								<td><tt>$pdd_behdir</tt></td>
							</tr>";

		}
		$datasearchtable .= "</table>";

		$imgdata = CreateGraphFromAnalysisID($analysisid);

		?>
		<style>
			td.arrow { vertical-align: middle; text-align: center; }
			td.step { font-weight: bold; background-color: #ddd; vertical-align: middle; text-align: center; padding: 5px; border-radius: 10px 0px 0px 10px; }
			td.stepdetail { border: 1px solid #ddd; padding: 5px; border-radius: 0px 10px 10px 0px; }
		</style>
		
		<div class="ui very compact grid">

			<div class="three wide column">&nbsp;</div>
			<div class="two wide column">&nbsp;</div>
			<div class="eleven wide column">&nbsp;</div>

			<div class="three wide column">&nbsp;</div>
			<div class="two wide column">
				<div class="ui center aligned inverted blue segment" style="height:100%;">
					<h2>Data</h2>
				</div>
			</div>
			<div class="eleven wide column">
				<div class="ui grey segment">
					<div class="ui accordion">
						<div class="title">
							<h3 class="ui header"><i class="dropdown icon"></i>Search criteria</h3>
						</div>
						<div class="content" style="height: 400px; overflow: auto">
							<?=$datasearchtable?>
						</div>
						<div class="title">
							<h3 class="ui header"><i class="dropdown icon"></i>Download summary</h3>
						</div>
						<div class="content" style="height: 400px; overflow: auto">
							<?=DataDownloadTable($studyid, strtolower($pdd_modality), $analysisid); ?>
						</div>
						<div class="title">
							<h3 class="ui header"><i class="dropdown icon"></i>Detailed log</h3>
						</div>
						<div class="content" style="height: 400px; overflow: auto">
							<?=$datatable?>
						</div>
					</div>
				</div>
			</div>

			<div class="three wide column">&nbsp;</div>
			<div class="center aligned two wide column"><i class="big arrow down icon"></i></div>
			<div class="eleven wide column">&nbsp;</div>
			
			<div class="two wide column">
				<div class="ui segment" style="height:100%;">
					<div class="ui header">
						<div class="content">Parent pipeline(s)
							<div class="sub header"><?=$dependencylist?></div>
						</div>
					</div>
					<div class="ui header">
						<div class="content">Groups
							<div class="sub header"><?=$grouplist?></div>
						</div>
					</div>
				</div>
			</div>
			<div class="middle aligned one wide column">
				<div class="ui center aligned basic segment">
					<i class="big arrow right icon"></i>
				</div>
			</div>
			<div class="two wide column">
				<div class="ui center aligned inverted blue segment" style="height:100%;">
					<div class="ui header">
						<div class="content">Analysis graph</div>
					</div>
				</div>
			</div>
			<div class="eleven wide column">
				<div class="ui grey segment" style="height:100%;">
					<img border=1 src="data:image/png;base64,<?=$imgdata?>">
				</div>
			</div>

			<div class="three wide column">&nbsp;</div>
			<div class="center aligned two wide column"><i class="big arrow down icon"></i></div>
			<div class="eleven wide column">&nbsp;</div>

			<div class="three wide column">&nbsp;</div>
			<div class="center aligned two wide column">
				<div class="ui center aligned inverted blue segment" style="height:100%;">
					<h2><em data-emoji=":gear:"></em>Cluster</h2>
				</div>
			</div>
			<div class="eleven wide column">
				<div class="ui grey segment">
					<div class="ui accordion">
						<div class="title">
							<h3 class="ui header"><i class="dropdown icon"></i> Pipeline history/timeline</h3>
						</div>
						<div class="content" style="height: 400px; overflow: auto">
							<table class="ui very compact celled table">
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
						</div>
					
						<div class="title">
							<h3 class="ui header"><i class="dropdown icon"></i> Log files</h3>
						</div>
						<div class="content" style="height: 400px; overflow: auto">
							<? DisplayLogs($analysisid); ?>
						</div>
					</div>
				</div>
			</div>

			<div class="three wide column">&nbsp;</div>
			<div class="center aligned two wide column"><i class="big arrow down icon"></i></div>
			<div class="eleven wide column">&nbsp;</div>

			<div class="three wide column">&nbsp;</div>
			<div class="center aligned two wide column">
				<div class="ui center aligned inverted blue segment" style="height:100%;">
					<h2>Results</h2>
				</div>
			</div>
			<div class="eleven wide column">
				<div class="ui grey segment">
					<?
						$numvalue = $numfile = $numtext = $numhtml = $numimage = 0;
						$sqlstring = "select a.* from analysis_results a left join analysis b on a.analysis_id = b.analysis_id left join pipelines c on b.pipeline_id = c.pipeline_id left join analysis_resultnames d on d.resultname_id = a.result_nameid where a.analysis_id = $analysisid order by d.result_name";
						$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
						while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							$type = $row['result_type'];
							switch($type) {
								case "v": $numvalue++; break;
								case "f": $numfile++; break;
								case "t": $numtext++; break;
								case "h": $numhtml++; break;
								case "i": $numimage++; break;
							}
						}
						if ($numvalue == 0) { $numvalue = "-"; }
						if ($numfile == 0) { $numfile = "-"; }
						if ($numtext == 0) { $numtext = "-"; }
						if ($numhtml == 0) { $numhtml = "-"; }
						if ($numimage == 0) { $numimage = "-"; }
					?>
					<table class="ui very compact collapsing very basic table">
						<tr>
							<td>Values</td>
							<td><?=$numvalue?></td>
						</tr>
						<tr>
							<td>Images</td>
							<td><?=$numimage?></td>
						</tr>
						<tr>
							<td>Files</td>
							<td><?=$numfile?></td>
						</tr>
						<tr>
							<td>Text</td>
							<td><?=$numtext?></td>
						</tr>
						<tr>
							<td>HTML</td>
							<td><?=$numhtml?></td>
						</tr>
					</table>
					<a class="ui basic button" href="viewanalysis.php?action=viewresults&analysisid=<?=$analysisid?>&studyid=<?=$studyid?>" target="_viewresults" title="View analysis results"><em data-emoji=":bar_chart:"></em> View all results</a>
					<a class="ui basic button" href="viewanalysis.php?action=viewfiles&analysisid=<?=$analysisid?>" target="_viewfiles" title="View file listing"><i class="folder open outline icon"></i> View all files</a>
				</div>
			</div>
		</div>
		<?
	}

	
	/* -------------------------------------------- */
	/* ------- CreateGraphFromAnalysisID ---------- */
	/* -------------------------------------------- */
	function CreateGraphFromAnalysisID($analysisid) {

		$dotfile = tempnam("/tmp",'DOTDOT');
		$pngfile = tempnam("/tmp",'DOTPNG');
		
		$d = "digraph G {
		
		rankdir	= LR
		node [shape = plaintext];
		
		";

		list($uid, $studynum, $pipelinename, $pipelineversion) = GetInfoFromAnalysisID($analysisid);
		/* get current analysis info */
		//$sqlstring = "select b.pipeline_name, a.pipeline_version, c.study_num from analysis a left join pipelines b on a.pipeline_id = b.pipeline_id left join studies c on a.study_id = c.study_id where a.analysis_id = '$analysisid'";
		//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		//$pipelinename = $row['pipeline_name'];
		//$pipelineversion = $row['pipeline_version'];
		//$studyid = $row['study_id'];

		/* get parent analysis info, if any */
		$sqlstring = "select event_message from analysis_history where analysis_event = 'analysisdependencyid' and analysis_id = '$analysisid'";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$parentanalysisid = $row['event_message'];

		if ($parentanalysisid == "") {
			$d .= "\"$pipelinename\";\n";
			$d .= "node [shape = box, label=\"$uid$studynum\"] { rank=same; \"$pipelinename\" analysis; }\n";
		}
		else {
			list($parentuid, $parentstudynum, $parentpipelinename, $parentpipelineversion) = GetInfoFromAnalysisID($parentanalysisid);
			
			$d .= "\"$parentpipelinename\" -> \"$pipelinename\";\n";
			$d .= "node [shape = box, label=\"$uid$studynum\"] { rank=same; \"$pipelinename\" analysis; }\n";
			$d .= "node [shape = box, label=\"$parentuid$parentstudynum\"] { rank=same; \"$parentpipelinename\" parentanalysis; }\n";
			$d .= "parentanalysis -> analysis\n";
		}
		
		/*
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
		} */
		
		$d .= "}";
		//$d = array_unique($d);
		//$dot = implode("\n",$d);
		$dot = $d;
		//echo "<pre>$dot</pre>";
		file_put_contents($dotfile,$dot);
		$systemstring = "dot -Tpng $dotfile -o $pngfile";
		exec($systemstring);
		//echo $dot;
		$imdata = base64_encode(file_get_contents($pngfile));
		return $imdata;
	}
	
	
	/* -------------------------------------------- */
	/* ------- GetInfoFromAnalysisID -------------- */
	/* -------------------------------------------- */
	function GetInfoFromAnalysisID($analysisid) {
		
		/* check for valid analysis ID */
		if (!ValidID($analysisid,'Analysis ID - GetInfoFromAnalysisID()')) { return; }
		
		$sqlstring = "select a.pipeline_version, d.uid, b.study_num, e.pipeline_name, e.pipeline_level from analysis a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join pipelines e on a.pipeline_id = e.pipeline_id where a.analysis_id = $analysisid";
		//echo "[$sqlstring]";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
		$uid = $row['uid'];
		$studynum = $row['study_num'];
		$pipelinename = $row['pipeline_name'];
		$pipelineversion = $row['pipeline_version'];

		return array($uid, $studynum, $pipelinename, $pipelineversion);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DataDownloadTable ------------------ */
	/* -------------------------------------------- */
	function DataDownloadTable($studyid, $modality, $analysisid) {
		
		if (trim($modality) == "") {
			echo "Blank modality<br>";
			return 0;
		}
		
		/* get the information about what data was found for this analysis */
		$sqlstring = "select * from pipeline_data where analysis_id = $analysisid";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			$order = $row['pd_step'];
			$dd[$order]['checked'] = $row['pd_checked'];
			$dd[$order]['found'] = $row['pd_found'];
			$dd[$order]['seriesid'] = $row['pd_seriesid'];
			$dd[$order]['downloadpath'] = $row['pd_downloadpath'];
			$dd[$order]['msg'] = $row['pd_msg'];
		}

		?>
		<table class="ui very compact celled table">
			<thead>
				<tr>
					<th>Series</th>
					<th>Protocol</th>
					<th>Image type</th>
					<th>BOLD reps</th>
					<th>Checked?</th>
					<th>Found?</th>
					<th>Download path</th>
					<th>Message</th>
				</tr>
			</thead>
		<?
		/* get all series in the study */
		$sqlstring = "select * from $modality"."_series where study_id = $studyid order by series_num asc";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
		while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			
			if ($modality == "mr") {
				$seriesid = $row['mrseries_id'];
				$protocol = $row['series_desc'];
				$seriesnum = $row['series_num'];
				$imagetype = $row['image_type'];
				$boldreps = $row['bold_reps'];
			}
			else {
				$protocol = $row['series_protocol'];
				$seriesnum = $row['series_num'];
			}
			
			?>
			<tr>
				<td><?=$seriesnum?></td>
				<td><?=$protocol?></td>
				<td><?=$imagetype?></td>
				<td><?=$boldreps?></td>
			<?
			$displayed = false;
			foreach ($dd as $order => $val) {
				$dseriesid = $val['seriesid'];
				$found = $val['found'];
				$checked = $val['checked'];
				$path = $val['downloadpath'];
				$msg = $val['msg'];
				if ($dseriesid == $seriesid) {
					?>
					<td><? if ($checked) echo "&#10004;"; ?></td>
					<td><? if ($found) echo "&#10004;"; ?></td>
					<td><?=$path?></td>
					<td><?=$msg?></td>
					<?
					$displayed = true;
				}
			}
			if (!$displayed) {
				?>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<?
			}
			?></tr><?
		}
		?></table><?
	}
	
?>
</body>