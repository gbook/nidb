<?
 // ------------------------------------------------------------------------------
 // NiDB series.php
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
		<title>NiDB - Series parameters</title>
	</head>

<body>
	<div id="wrapper">
<?
	//require "config.php";
	require "functions.php";
	require "includes.php";
	require "menu.php";
	require "nanodicom.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$dcmfile = GetVariable("dcmfile");

	
	/* determine action */
	switch($action) {
		case "scanparams":
			DisplayScanParamaters($dcmfile);
			break;
		case "allscanparams":
			DisplayAllScanParamaters($dcmfile);
			break;
		default:
			//DisplayAbout();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayScanParamaters -------------- */
	/* -------------------------------------------- */
	function DisplayScanParamaters($filename) {
	
		$urllist['Home'] = "index.php";
		$urllist['Scan Params'] = "series.php";
		NavigationBar("MR Scan Parameters", $urllist);

		$dicom = Nanodicom::factory($filename, 'dumper');
		$tags = $dicom->dump();
		//print_r($tags);
		$lines = explode("\n",$tags);
		?>
		<div align="center">
		<table style="font-size: 10pt; border: 1px solid #DDDDDD" cellspacing="0" cellpadding="2">
			<tr>
				<td colspan="3" style="font-weight: bold; font-size: 14pt; text-align: center; padding:10px; border-bottom: 2px solid #666666">MR Sequence Parameters</td>
			</tr>
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
				if (in_array($tag2,$subtags)) {
					if ($lasttag1 != '0008') {
						$style = "border-top: solid 2px #666666";
					}
					else {
						$style = "border-top: solid 1px #CCCCCC";
					}
					?>
					<tr>
						<td style="color:darkblue; <?=$style?>"><?=$tag1?>:<?=$tag2?></td>
						<td style="<?=$style?>"><?=$name?></td>
						<td style="<?=$style?>"><?=$value?></td>
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
					$style = "border-top: solid 1px #CCCCCC";
				}
				?>
				<tr>
					<td style="color:darkblue; <?=$style?>"><?=$tag1?>:<?=$tag2?></td>
					<td style="<?=$style?>"><?=$name?></td>
					<td style="<?=$style?>"><?=$value?> <span class="tiny"><?=$units?></span></td>
				</tr>
				<?
				$lasttag1 = '0018';
			}
			if ($tag1 == "0020") {
				if ($lasttag1 != '0020') {
					$style = "border-top: solid 2px #666666";
				}
				else {
					$style = "border-top: solid 1px #CCCCCC";
				}
				?>
				<tr>
					<td style="color:darkblue; <?=$style?>"><?=$tag1?>:<?=$tag2?></td>
					<td style="<?=$style?>"><?=$name?></td>
					<td style="<?=$style?>"><?=$value?></td>
				</tr>
				<?
				$lasttag1 = '0020';
			}
			if ($tag1 == "0028") {
				$subtags = array('0002','0004','0010','0011','0030','0100','0101','0102','0103');
				if (in_array($tag2,$subtags)) {
					if ($lasttag1 != '0028') {
						$style = "border-top: solid 2px #666666";
					}
					else {
						$style = "border-top: solid 1px #CCCCCC";
					}
					?>
					<tr>
						<td style="color:darkblue; <?=$style?>"><?=$tag1?>:<?=$tag2?></td>
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
		<a href="series.php?action=allscanparams&dcmfile=<?=$filename?>" style="font-family:times new roman">&pi;</a>
		</div>
		<?
		
		unset($dicom);
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAllScanParamaters ----------- */
	/* -------------------------------------------- */
	function DisplayAllScanParamaters($filename) {
	
		$urllist['Home'] = "index.php";
		$urllist['Scan Params'] = "series.php";
		NavigationBar("MR Scan Parameters", $urllist);

		$dicom = Nanodicom::factory($filename, 'dumper');
		$tags = $dicom->dump();
		//print_r($tags);
		$lines = explode("\n",$tags);
		?>
		<div align="center">
		<table style="font-size: 10pt; border: 1px solid #DDDDDD" cellspacing="0" cellpadding="2">
			<tr>
				<td colspan="3" style="font-weight: bold; font-size: 14pt; text-align: center; padding:10px; border-bottom: 2px solid #666666">MR Sequence Parameters <span style="color:darkred">(CONTAINS PHI - DO NOT SHARE)</span></td>
			</tr>
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
				$style = "border-top: solid 1px #CCCCCC";
			}
			?>
			<tr>
				<td style="color:darkblue; <?=$style?>"><?=$tag1?>:<?=$tag2?></td>
				<td style="<?=$style?>"><?=$name?></td>
				<td style="<?=$style?>"><?=$value?> <span class="tiny"><?=$units?></span></td>
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
