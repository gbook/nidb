<?
 // ------------------------------------------------------------------------------
 // NiDB series.php
 // Copyright (C) 2004 - 2026
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
		<title>NiDB - Series parameters</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nanodicom.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$modality = GetVariable("modality");
	$mrseriesid = GetVariable("seriesid");
	
	/* determine action */
	switch($action) {
		case "scanparams":
			DisplayScanParamaters($mrseriesid, $modality);
			break;
		case "allscanparams":
			DisplayAllScanParamaters($mrseriesid, $modality);
			break;
		default:
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayScanParamaters -------------- */
	/* -------------------------------------------- */
	function DisplayScanParamaters($mrseriesid, $modality) {
		$mrseriesid = mysqli_real_escape_string($GLOBALS['linki'], $mrseriesid);
		
		echo "seriesid [$mrseriesid] modality [$modality]<br>";
		list($path, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($mrseriesid, $modality);
		echo "$path, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid<br>";
		
		if (substr($path,-6) == "parrec") {
			$files = glob("$path/*.par");
			$filename = $files[0];

			if (file_exists($filename)) {
				?>
		<div class="ui styled segment" style="border: 1px solid #BBB; margin:10px; padding:10px; background-color: white; font-family: monospace; white-space: pre;">
		<div style="padding:5px; background-color: 393939; color:white; font-size:11pt"><?=$filename?></div>

<? readfile($filename); ?>
		</div>
				<?
			}
			else { echo "file [$filename] does not exist"; }
		}
		else {
			$files = glob("$path/*.dcm");
			$filename = $files[0];
			
			$dicom = Nanodicom::factory($filename, 'dumper');
			$tags = $dicom->dump();
			//print_r($tags);
			$lines = explode("\n",$tags);
			?>
			<div class="ui container">
				<div class="ui top attached secondary inverted segment">
					<h2 class="ui header">
						DICOM tags
						<div class="sub header"><?=$filename?></div>
					</h2>
					<div class="ui large right ribbon orange label">Limited tag set</div>
				</div>
				<table class="ui bottom attached celled small very compact table">
				<?
				$lasttag1 = '';
				foreach ($lines as $line) {
					if (trim($line) == '') {
						continue;
					}
					preg_match('/.*(\w{4}):(\w{4})\s+(\w+)\s+([A-Z]{2})\s+(\d+)\s+\[(.*)\].*/',$line,$matches);
					//PrintVariable($matches,'matches');
					$tag1 = $matches[1];
					$tag2 = $matches[2];
					$name = $matches[3];
					$value = $matches[6];
					
					preg_match_all('/((?:^|[A-Z])[a-z]+)/',$name,$matches2);
					$pieces = $matches2[0];
					$name = implode(' ', $pieces);
					
					if ($tag1 == "0008") {
						$subtags = array('0070','0080','0081','1010','1030','103E','1090');
						if (in_array($tag2, $subtags)) {
							if ($lasttag1 != '0008') {
								$style = "border-top: solid 2px #666666";
							}
							else {
								$style = "";
							}
							?>
							<tr>
								<td style="color:darkblue; <?=$style?>" class="tt"><?=$tag1?>:<?=$tag2?></td>
								<td style="<?=$style?>"><?=$name?></td>
								<td style="<?=$style?>" class="<? if (is_numeric($value)) echo "tt"; ?>"><?=$value?></td>
							</tr>
							<?
						}
						$lasttag1 = '0008';
					}
					if ($tag1 == "0018") {
						switch ($tag2) {
							case '0050': $units = 'mm'; break;
							case '0080': $units = 'ms'; break;
							case '0081': $units = 'ms'; break;
							case '0082': $units = 'ms'; break;
							case '0084': $units = 'Hz'; break;
							case '0087': $units = 'T'; break;
							case '0093': $units = '%'; break;
							case '0094': $units = '%'; break;
							case '1314': $units = '&deg;'; break;
							default: $units = '';
						}
						if ($lasttag1 != '0018') {
							$style = "border-top: solid 2px #666666";
						}
						else {
							$style = "";
						}
						?>
						<tr>
							<td style="color:darkblue; <?=$style?>" class="tt"><?=$tag1?>:<?=$tag2?></td>
							<td style="<?=$style?>"><?=$name?></td>
							<td style="<?=$style?>" class="<? if (is_numeric($value)) echo "tt"; ?>"><?=$value?> <?=$units?></td>
						</tr>
						<?
						$lasttag1 = '0018';
					}
					if ($tag1 == "0020") {
						if ($lasttag1 != '0020') {
							$style = "border-top: solid 2px #666666";
						}
						else {
							$style = "";
						}
						?>
						<tr>
							<td style="color:darkblue; <?=$style?>" class="tt"><?=$tag1?>:<?=$tag2?></td>
							<td style="<?=$style?>"><?=$name?></td>
							<td style="<?=$style?>"><?=$value?></td>
						</tr>
						<?
						$lasttag1 = '0020';
					}
					if ($tag1 == "0028") {
						$subtags = array('0002','0004','0010','0011','0030','0100','0101','0102','0103');
						if (in_array($tag2, $subtags)) {
							if ($lasttag1 != '0028') {
								$style = "border-top: solid 2px #666666";
							}
							else {
								$style = "";
							}
							?>
							<tr>
								<td style="color:darkblue; <?=$style?>" class="tt"><?=$tag1?>:<?=$tag2?></td>
								<td style="<?=$style?>"><?=$name?></td>
								<td style="<?=$style?>"><?=$value?></td>
							</tr>
							<?
						}
						$lasttag1 = '0028';
					}
				}
				?>
					<tr>
						<td colspan="3" style="font-size:8pt; border-top: solid 2px #666666; padding:8px">Generated by NIDB <? echo date("D M j, Y g:i a T"); ?>
						<br>Based on DICOM file [<?=$filename?>]</td>
					</tr>
				</table>
				<a href="series.php?action=allscanparams&seriesid=<?=$mrseriesid?>&modality=<?=$modality?>" style="font-family:times new roman">&pi;</a>
			</div>
			<?
			
			unset($dicom);
		}
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAllScanParamaters ----------- */
	/* -------------------------------------------- */
	function DisplayAllScanParamaters($mrseriesid, $modality) {
		$mrseriesid = mysqli_real_escape_string($GLOBALS['linki'], $mrseriesid);
		list($path, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($mrseriesid, $modality);
		$files = glob("$path/*.dcm");
		$filename = $files[0];

		$dicom = Nanodicom::factory($filename, 'dumper');
		$tags = $dicom->dump();
		//print_r($tags);
		$lines = explode("\n",$tags);
		?>
		<div class="ui container">
			<div class="ui top attached yellow inverted segment">
				<h2 class="ui header">
					DICOM header - Contains PHI, do not share
					<div class="sub header"><?=$filename?></div>
				</h2>
				<div class="ui large right ribbon red label">Full tag set</div>
			</div>
			
			<table class="ui bottom attached celled very compact selectable table">
			<?
			$lasttag1 = '';
			foreach ($lines as $line) {
				preg_match('/.*(\w{4}):(\w{4})\s+(\w+)\s+([A-Z]{2})\s+(\d+)\s+\[(.*)\].*/',$line,$matches);
				//print_r($matches);
				$tag1 = $matches[1];
				$tag2 = $matches[2];
				$name = $matches[3];
				$value = $matches[6];
				
				if (trim($tag1) == "") { continue; }
				
				preg_match_all('/((?:^|[A-Z])[a-z]+)/',$name,$matches2);
				$pieces = $matches2[0];
				$name = implode(' ', $pieces);
				
				if ($tag1 == "0018") {
					switch ($tag2) {
						case '0050': $units = 'mm'; break;
						case '0080': $units = 'ms'; break;
						case '0081': $units = 'ms'; break;
						case '0082': $units = 'ms'; break;
						case '0084': $units = 'Hz'; break;
						case '0087': $units = 'T'; break;
						case '0093': $units = '%'; break;
						case '0094': $units = '%'; break;
						case '1314': $units = '&deg;'; break;
						default: $units = '';
					}
				}
				if ($lasttag1 != $tag1) {
					$style = "border-top: solid 2px #666666";
				}
				else {
					$style = "";
				}
				?>
				<tr>
					<td style="color:darkblue; <?=$style?>" class="top aligned tt"><?=$tag1?>:<?=$tag2?></td>
					<td style="<?=$style?>; white-space: nowrap"><?=$name?></td>
					<td style="<?=$style?>; word-break: break-all"><?=$value?> <span class="tiny"><?=$units?></span></td>
				</tr>
				<?
				$lasttag1 = $tag1;
			}
			?>
				<tr>
					<td colspan="3" style="font-size:8pt; border-top: solid 2px #666666; padding:8px">Generated by NiDB <? echo date("D M j, Y g:i a T"); ?>. Tags extracted using <a href="http://www.nanodicom.org">Nanodicom</a>
					<br>Based on DICOM file [<?=$filename?>]</td>
				</tr>
			</table>
		</div>
		<?
		
		unset($dicom);
	}
	
?>


<? include("footer.php") ?>
