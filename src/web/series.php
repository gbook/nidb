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
	$seriesid = GetVariable("seriesid");
	
	/* determine action */
	switch($action) {
		case "scanparams":
			DisplayScanParameters($seriesid, $modality);
			break;
		case "allscanparams":
			DisplayAllScanParameters($seriesid, $modality);
			break;
		default:
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplaySeriesError ----------------- */
	/* -------------------------------------------- */
	function DisplaySeriesError($message) {
		?>
		<div class="ui container">
			<div class="ui error message"><?=htmlspecialchars($message)?></div>
		</div>
		<?
	}


	/* -------------------------------------------- */
	/* ------- ParseDicomDumpLine ----------------- */
	/* -------------------------------------------- */
	/* splits one line of Nanodicom's dump into [group, element, name, value]. Returns null if the
	   line isn't a tag */
	function ParseDicomDumpLine($line) {
		if (!preg_match('/.*(\w{4}):(\w{4})\s+(\w+)\s+([A-Z]{2})\s+(\d+)\s+\[(.*)\].*/', $line, $matches))
			return null;
		if (trim($matches[1]) == "")
			return null;

		/* "PatientBirthDate" -> "Patient Birth Date" */
		preg_match_all('/((?:^|[A-Z])[a-z]+)/', $matches[3], $matches2);
		$name = implode(' ', $matches2[0]);

		return array($matches[1], $matches[2], $name, $matches[6]);
	}


	/* -------------------------------------------- */
	/* ------- GetDicomTagUnits ------------------- */
	/* -------------------------------------------- */
	/* units for the (0018,xxxx) acquisition tags. Returns trusted HTML */
	function GetDicomTagUnits($tag1, $tag2) {
		if ($tag1 != "0018")
			return '';
		switch ($tag2) {
			case '0050': return 'mm';
			case '0080': return 'ms';
			case '0081': return 'ms';
			case '0082': return 'ms';
			case '0084': return 'Hz';
			case '0087': return 'T';
			case '0093': return '%';
			case '0094': return '%';
			case '1314': return '&deg;';
		}
		return '';
	}


	/* -------------------------------------------- */
	/* ------- GetFirstDicomFile ------------------ */
	/* -------------------------------------------- */
	/* returns the first .dcm file in $path, or '' if there are none */
	function GetFirstDicomFile($path) {
		$files = glob("$path/*.dcm");
		if (($files === false) || (count($files) < 1))
			return '';
		return $files[0];
	}


	/* -------------------------------------------- */
	/* ------- DisplayScanParameters -------------- */
	/* -------------------------------------------- */
	/* basic scan parameters from the DICOM header (groups 0008/0018/0020/0028, no patient tags).
	   Requires View Data on the series' project */
	function DisplayScanParameters($seriesid, $modality) {
		list($valid, $message, $path, $seriesid, $modality, $httpstatus, $viewphi) = ValidateDicomRequest($seriesid, $modality);
		if (!$valid) {
			DisplaySeriesError($message);
			return;
		}
		list($datapath, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
		$linkparams = "seriesid=$seriesid&modality=" . urlencode($modality);
		
		?>
		<a href="dicom.php?<?=htmlspecialchars($linkparams)?>" class="ui green button">View DICOM images</a>
		<?
		if (substr($path,-6) == "parrec") {
			$files = glob("$path/*.par");
			$filename = (($files !== false) && (count($files) > 0)) ? $files[0] : '';

			if (($filename != '') && file_exists($filename)) {
				?>
				<div class="ui styled segment" style="border: 1px solid #BBB; margin:10px; padding:10px; background-color: white; font-family: monospace; white-space: pre;">
					<div style="padding:5px; background-color: #393939; color:white; font-size:11pt"><?=htmlspecialchars($filename)?></div>
				</div>
				<?
			}
			else {
				DisplaySeriesError("No .par file found for this series");
			}
			return;
		}

		$filename = GetFirstDicomFile($path);
		if ($filename == '') {
			DisplaySeriesError("No DICOM files found for this series");
			return;
		}
		
		$dicom = Nanodicom::factory($filename, 'dumper');
		$lines = explode("\n", $dicom->dump());
		unset($dicom);

		/* only these tags are shown from groups 0008 and 0028. All of 0018 and 0020 are shown. None of
		   these groups contain patient tags (group 0010) */
		$shown = array(
			'0008' => array('0070','0080','0081','1010','1030','103E','1090'),
			'0018' => null,
			'0020' => null,
			'0028' => array('0002','0004','0010','0011','0030','0100','0101','0102','0103'));
		?>
		<div class="ui container">
			<div class="ui top attached inverted segment">
				<h2 class="ui header">
					<div class="content">
						DICOM tags
						<div class="sub header"><?=htmlspecialchars($uid ?? '')?> <?=htmlspecialchars($studynum ?? '')?></div>
					</div>
				</h2>
			</div>
			<table class="ui bottom attached celled small very compact table">
			<?
			$lasttag1 = '';
			foreach ($lines as $line) {
				$tag = ParseDicomDumpLine($line);
				if ($tag === null)
					continue;
				list($tag1, $tag2, $name, $value) = $tag;

				if (!array_key_exists($tag1, $shown))
					continue;
				if (($shown[$tag1] !== null) && !in_array($tag2, $shown[$tag1]))
					continue;

				$style = ($lasttag1 != $tag1) ? "border-top: solid 2px #666666" : "";
				$units = GetDicomTagUnits($tag1, $tag2);
				?>
				<tr>
					<td style="color:darkblue; <?=$style?>" class="tt"><?=htmlspecialchars($tag1)?>:<?=htmlspecialchars($tag2)?></td>
					<td style="<?=$style?>"><?=htmlspecialchars($name)?></td>
					<td style="<?=$style?>" class="<? if (is_numeric($value)) echo "tt"; ?>"><?=htmlspecialchars($value)?> <?=$units?></td>
				</tr>
				<?
				$lasttag1 = $tag1;
			}
			?>
				<tr>
					<td colspan="3" style="font-size:8pt; border-top: solid 2px #666666; padding:8px">Generated by NiDB <? echo date("D M j, Y g:i a T"); ?>
					<br>Based on DICOM file [<?=htmlspecialchars($filename)?>]</td>
				</tr>
			</table>
			<? if ($viewphi) { ?>
			<a href="series.php?action=allscanparams&<?=htmlspecialchars($linkparams)?>" style="font-family:times new roman" title="Full DICOM header (contains PHI)">&pi;</a>
			<? } ?>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayAllScanParameters ----------- */
	/* -------------------------------------------- */
	/* the full DICOM header, including patient tags. Requires View Data on the series' project and
	   View PHI on the subject */
	function DisplayAllScanParameters($seriesid, $modality) {
		list($valid, $message, $path, $seriesid, $modality, $httpstatus, $viewphi) = ValidateDicomRequest($seriesid, $modality);
		if (!$valid) {
			DisplaySeriesError($message);
			return;
		}
		if (!$viewphi) {
			DisplaySeriesError("The full DICOM header contains PHI. You need View PHI permissions on this subject to view it");
			return;
		}

		$filename = GetFirstDicomFile($path);
		if ($filename == '') {
			DisplaySeriesError("No DICOM files found for this series");
			return;
		}

		$dicom = Nanodicom::factory($filename, 'dumper');
		$lines = explode("\n", $dicom->dump());
		unset($dicom);
		?>
		<div class="ui container">
			<div class="ui top attached yellow inverted segment">
				<h2 class="ui header">
					DICOM header - Contains PHI, do not share
					<div class="sub header"><?=htmlspecialchars($filename)?></div>
				</h2>
				<div class="ui large right ribbon red label">Full tag set</div>
			</div>
			
			<table class="ui bottom attached celled very compact selectable table">
			<?
			$lasttag1 = '';
			foreach ($lines as $line) {
				$tag = ParseDicomDumpLine($line);
				if ($tag === null)
					continue;
				list($tag1, $tag2, $name, $value) = $tag;

				$style = ($lasttag1 != $tag1) ? "border-top: solid 2px #666666" : "";
				$units = GetDicomTagUnits($tag1, $tag2);
				?>
				<tr>
					<td style="color:darkblue; <?=$style?>" class="top aligned tt"><?=htmlspecialchars($tag1)?>:<?=htmlspecialchars($tag2)?></td>
					<td style="<?=$style?>; white-space: nowrap"><?=htmlspecialchars($name)?></td>
					<td style="<?=$style?>; word-break: break-all"><?=htmlspecialchars($value)?> <span class="tiny"><?=$units?></span></td>
				</tr>
				<?
				$lasttag1 = $tag1;
			}
			?>
				<tr>
					<td colspan="3" style="font-size:8pt; border-top: solid 2px #666666; padding:8px">Generated by NiDB <? echo date("D M j, Y g:i a T"); ?>. Tags extracted using <a href="http://www.nanodicom.org">Nanodicom</a>
					<br>Based on DICOM file [<?=htmlspecialchars($filename)?>]</td>
				</tr>
			</table>
		</div>
		<?
	}
	
?>


<? include("footer.php") ?>
