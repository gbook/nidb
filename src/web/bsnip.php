<?
 // ------------------------------------------------------------------------------
 // NiDB bsnip.php
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
	$nologin = true;
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - BSNIP downloads</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	
	/* determine action */
	if ($action == "") {
		DisplayDownloads();
	}
	else {
		DisplayDownloads();
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayDownloads ------------------- */
	/* -------------------------------------------- */
	function DisplayDownloads() {

		$bsnip2 = [];
		$bsnip2['Boston']['EEG']['subjectCount'] = 215;
		$bsnip2['Boston']['EEG']['unzippedSize'] = 279827856753;
		$bsnip2['Boston']['ET']['subjectCount'] = 205;
		$bsnip2['Boston']['ET']['unzippedSize'] = 3672197038;
		$bsnip2['Boston']['MR']['subjectCount'] = 179;
		$bsnip2['Boston']['MR']['unzippedSize'] = 24492051005;
		$bsnip2['Boston']['NonImaging']['subjectCount'] = 275;
		$bsnip2['Boston']['NonImaging']['unzippedSize'] = 0;
		$bsnip2['Chicago']['EEG']['subjectCount'] = 327;
		$bsnip2['Chicago']['EEG']['unzippedSize'] = 423193865093;
		$bsnip2['Chicago']['ET']['subjectCount'] = 390;
		$bsnip2['Chicago']['ET']['unzippedSize'] = 6871947674;
		$bsnip2['Chicago']['MR']['subjectCount'] = 311;
		$bsnip2['Chicago']['MR']['unzippedSize'] = 28894392484;
		$bsnip2['Chicago']['NonImaging']['subjectCount'] = 436;
		$bsnip2['Chicago']['NonImaging']['unzippedSize'] = 0;
		$bsnip2['Dallas']['EEG']['subjectCount'] = 249;
		$bsnip2['Dallas']['EEG']['unzippedSize'] = 311363654124;
		$bsnip2['Dallas']['ET']['subjectCount'] = 247;
		$bsnip2['Dallas']['ET']['unzippedSize'] = 4724464026;
		$bsnip2['Dallas']['MR']['subjectCount'] = 171;
		$bsnip2['Dallas']['MR']['unzippedSize'] = 14270028841;
		$bsnip2['Dallas']['NonImaging']['subjectCount'] = 440;
		$bsnip2['Dallas']['NonImaging']['unzippedSize'] = 0;
		$bsnip2['Georgia']['EEG']['subjectCount'] = 336;
		$bsnip2['Georgia']['EEG']['unzippedSize'] = 440244885258;
		$bsnip2['Georgia']['ET']['subjectCount'] = 326;
		$bsnip2['Georgia']['ET']['unzippedSize'] = 6227702579;
		$bsnip2['Georgia']['MR']['subjectCount'] = 243;
		$bsnip2['Georgia']['MR']['unzippedSize'] = 22419729285;
		$bsnip2['Georgia']['NonImaging']['subjectCount'] = 431;
		$bsnip2['Georgia']['NonImaging']['unzippedSize'] = 0;
		$bsnip2['Hartford']['EEG']['subjectCount'] = 392;
		$bsnip2['Hartford']['EEG']['unzippedSize'] = 511852727501;
		$bsnip2['Hartford']['ET']['subjectCount'] = 393;
		$bsnip2['Hartford']['ET']['unzippedSize'] = 8396661064;
		$bsnip2['Hartford']['MR']['subjectCount'] = 357;
		$bsnip2['Hartford']['MR']['unzippedSize'] = 380577052099;
		$bsnip2['Hartford']['NonImaging']['subjectCount'] = 442;
		$bsnip2['Hartford']['NonImaging']['unzippedSize'] = 0;
		
		$pardip = [];
		$pardip['Boston']['EEG']['subjectCount'] = 76;
		$pardip['Boston']['EEG']['unzippedSize'] = 94038308946;
		$pardip['Boston']['ET']['subjectCount'] = 63;
		$pardip['Boston']['ET']['unzippedSize'] = 1471026299;
		$pardip['Boston']['MR']['subjectCount'] = 68;
		$pardip['Boston']['MR']['unzippedSize'] = 7011534111;
		$pardip['Boston']['NonImaging']['subjectCount'] = 65;
		$pardip['Boston']['NonImaging']['unzippedSize'] = 0;
		$pardip['Dallas']['ET']['subjectCount'] = 96;
		$pardip['Dallas']['ET']['unzippedSize'] = 2630667469;
		$pardip['Dallas']['MR']['subjectCount'] = 79;
		$pardip['Dallas']['MR']['unzippedSize'] = 11972221338;
		$pardip['Dallas']['NonImaging']['subjectCount'] = 75;
		$pardip['Dallas']['NonImaging']['unzippedSize'] = 22523412;
		$pardip['Georgia']['EEG']['subjectCount'] = 4;
		$pardip['Georgia']['EEG']['unzippedSize'] = 5325759447;
		$pardip['Georgia']['ET']['subjectCount'] = 4;
		$pardip['Georgia']['ET']['unzippedSize'] = 71963771;
		$pardip['Georgia']['MR']['subjectCount'] = 4;
		$pardip['Georgia']['MR']['unzippedSize'] = 373848801;
		$pardip['Georgia']['NonImaging']['subjectCount'] = 6;
		$pardip['Georgia']['NonImaging']['unzippedSize'] = 0;
		$pardip['Hartford']['EEG']['subjectCount'] = 121;
		$pardip['Hartford']['EEG']['unzippedSize'] = 153985314980;
		$pardip['Hartford']['ET']['subjectCount'] = 106;
		$pardip['Hartford']['ET']['unzippedSize'] = 2748779069;
		$pardip['Hartford']['MR']['subjectCount'] = 121;
		$pardip['Hartford']['MR']['unzippedSize'] = 73422465925;
		$pardip['Hartford']['NonImaging']['subjectCount'] = 116;
		$pardip['Hartford']['NonImaging']['unzippedSize'] = 0;
		
		/* PARDIP */
		$pardip_boston_mr = filesize("/nidb/data/bsnip/PARDIP-Boston-MR.sqrl");
		$pardip_boston_eeg = filesize("/nidb/data/bsnip/PARDIP-Boston-EEG.sqrl");
		$pardip_boston_et = filesize("/nidb/data/bsnip/PARDIP-Boston-ET.sqrl");
		$pardip_boston_nonimaging = filesize("/nidb/data/bsnip/PARDIP-Boston-NonImaging.sqrl");

		$pardip_dallas_mr = filesize("/nidb/data/bsnip/PARDIP-Dallas-MR.sqrl");
		$pardip_dallas_eeg = filesize("/nidb/data/bsnip/PARDIP-Dallas-EEG.sqrl");
		$pardip_dallas_et = filesize("/nidb/data/bsnip/PARDIP-Dallas-ET.sqrl");
		$pardip_dallas_nonimaging = filesize("/nidb/data/bsnip/PARDIP-Dallas-NonImaging.sqrl");

		$pardip_georgia_mr = filesize("/nidb/data/bsnip/PARDIP-Georgia-MR.sqrl");
		$pardip_georgia_eeg = filesize("/nidb/data/bsnip/PARDIP-Georgia-EEG.sqrl");
		$pardip_georgia_et = filesize("/nidb/data/bsnip/PARDIP-Georgia-ET.sqrl");
		$pardip_georgia_nonimaging = filesize("/nidb/data/bsnip/PARDIP-Georgia-NonImaging.sqrl");

		$pardip_hartford_mr = filesize("/nidb/data/bsnip/PARDIP-Hartford-MR.sqrl");
		$pardip_hartford_eeg = filesize("/nidb/data/bsnip/PARDIP-Hartford-EEG.sqrl");
		$pardip_hartford_et = filesize("/nidb/data/bsnip/PARDIP-Hartford-ET.sqrl");
		$pardip_hartford_nonimaging = filesize("/nidb/data/bsnip/PARDIP-Hartford-NonImaging.sqrl");
		
		$pardip_total = ($pardip_boston_mr + $pardip_boston_eeg + $pardip_boston_et + $pardip_boston_nonimaging) + ($pardip_dallas_mr + $pardip_dallas_eeg + $pardip_dallas_et + $pardip_dallas_nonimaging) + ($pardip_georgia_mr + $pardip_georgia_eeg + $pardip_georgia_et + $pardip_georgia_nonimaging) + ($pardip_hartford_mr + $pardip_hartford_eeg + $pardip_hartford_et + $pardip_hartford_nonimaging);

		/* BSNIP2 */
		$bsnip2_boston_mr = filesize("/nidb/data/bsnip/BSNIP2-Boston-MR.sqrl");
		$bsnip2_boston_eeg = filesize("/nidb/data/bsnip/BSNIP2-Boston-EEG.sqrl");
		$bsnip2_boston_et = filesize("/nidb/data/bsnip/BSNIP2-Boston-ET.sqrl");
		$bsnip2_boston_nonimaging = filesize("/nidb/data/bsnip/BSNIP2-Boston-NonImaging.sqrl");

		$bsnip2_chicago_mr = filesize("/nidb/data/bsnip/BSNIP2-Chicago-MR.sqrl");
		$bsnip2_chicago_eeg = filesize("/nidb/data/bsnip/BSNIP2-Chicago-EEG.sqrl");
		$bsnip2_chicago_et = filesize("/nidb/data/bsnip/BSNIP2-Chicago-ET.sqrl");
		$bsnip2_chicago_nonimaging = filesize("/nidb/data/bsnip/BSNIP2-Chicago-NonImaging.sqrl");

		$bsnip2_dallas_mr = filesize("/nidb/data/bsnip/BSNIP2-Dallas-MR.sqrl");
		$bsnip2_dallas_eeg = filesize("/nidb/data/bsnip/BSNIP2-Dallas-EEG.sqrl");
		$bsnip2_dallas_et = filesize("/nidb/data/bsnip/BSNIP2-Dallas-ET.sqrl");
		$bsnip2_dallas_nonimaging = filesize("/nidb/data/bsnip/BSNIP2-Dallas-NonImaging.sqrl");

		$bsnip2_georgia_mr = filesize("/nidb/data/bsnip/BSNIP2-Georgia-MR.sqrl");
		$bsnip2_georgia_eeg = filesize("/nidb/data/bsnip/BSNIP2-Georgia-EEG.sqrl");
		$bsnip2_georgia_et = filesize("/nidb/data/bsnip/BSNIP2-Georgia-ET.sqrl");
		$bsnip2_georgia_nonimaging = filesize("/nidb/data/bsnip/BSNIP2-Georgia-NonImaging.sqrl");

		$bsnip2_hartford_mr = filesize("/nidb/data/bsnip/BSNIP2-Hartford-MR.sqrl");
		$bsnip2_hartford_eeg = filesize("/nidb/data/bsnip/BSNIP2-Hartford-EEG.sqrl");
		$bsnip2_hartford_et = filesize("/nidb/data/bsnip/BSNIP2-Hartford-ET.sqrl");
		$bsnip2_hartford_nonimaging = filesize("/nidb/data/bsnip/BSNIP2-Hartford-NonImaging.sqrl");
		
		$bsnip2_total = ($bsnip2_boston_mr + $bsnip2_boston_eeg + $bsnip2_boston_et + $bsnip2_boston_nonimaging) + ($bsnip2_chicago_mr + $bsnip2_chicago_eeg + $bsnip2_chicago_et + $bsnip2_chicago_nonimaging) + ($bsnip2_dallas_mr + $bsnip2_dallas_eeg + $bsnip2_dallas_et + $bsnip2_dallas_nonimaging) + ($bsnip2_georgia_mr + $bsnip2_georgia_eeg + $bsnip2_georgia_et + $bsnip2_georgia_nonimaging) + ($bsnip2_hartford_mr + $bsnip2_hartford_eeg + $bsnip2_hartford_et + $bsnip2_hartford_nonimaging);
		
		?>
		<br><br><br>
		<div class="ui container">
			
			<div class="ui black segment">
				<h1 class="ui header">
					BSNIP Downloads
					<div class="sub header">
						Publicly available data from the BSNIP consortium projects
					</div>
				</h1>
				<p>Data are packaged in the squirrel format and are separated by modality and site. More information about the squirrel format can be found here: <a href="https://docs.neuroinfodb.org/squirrel/">https://docs.neuroinfodb.org/squirrel/</a></p>
				
				<p>squirrel packages can be unzipped using 7-zip (7z command on Linux). Or use the squirrel command line <a href="https://github.com/gbook/squirrel/releases">utilities</a> to view package contents and extract data</p>

			</div>
			<br><br>
			
			<div class="ui top attached black segment">
				<div class="ui two column grid">
					<div class="column">
						<h1 class="ui header">
							BSNIP1
							<div class="sub header">
								Bipolar and Schizophrenia Network for Intermediate Phenotypes
							</div>
						</h1>
					</div>
					<div class="right aligned column">
						<a class="ui blue button" href="https://pmc.ncbi.nlm.nih.gov/articles/PMC3934403/">Cite 1 <i class="quote right icon"></i></a> &nbsp; 
						<a class="ui blue button" href="https://pubmed.ncbi.nlm.nih.gov/26651391/">Cite 2 <i class="quote right icon"></i></a>
					</div>
				</div>
			</div>
			<table class="ui bottom attached selectable celled table">
				<thead>
					<th>Site</th>
					<th>MRI</th>
					<th>EEG</th>
					<th>ET</th>
					<th>Non-imaging</th>
					<th>Total</th>
				</thead>
				<tr>
					<td><b>Baltimore</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Baltimore-MR.sqrl"><i class="ui download icon"></i> 358 subjects</a><div class="ui basic grey label"> 40 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Baltimore-EEG.sqrl"><i class="ui download icon"></i> 322 subjects</a><div class="ui basic grey label">70 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Baltimore-ET.sqrl"><i class="ui download icon"></i> 353 subjects</a><div class="ui basic grey label">2.7 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Baltimore-NonImaging.sqrl"><i class="ui download icon"></i> 299 subjects</a><div class="ui basic grey label">456 KB</div></div></td>
					<td class="right aligned">112 GB</td>
				</tr>
				<tr>
					<td><b>Boston</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Boston-MR.sqrl"><i class="ui download icon"></i> 136 subjects</a><div class="ui basic grey label">9.0 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Boston-EEG.sqrl"><i class="ui download icon"></i> 117 subjects</a><div class="ui basic grey label">28 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Boston-ET.sqrl"><i class="ui download icon"></i> 150 subjects</a><div class="ui basic grey label">1.3 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Boston-NonImaging.sqrl"><i class="ui download icon"></i> 101 subjects</a><div class="ui basic grey label">152 KB</div></div></td>
					<td class="right aligned">38 GB</td>
				</tr>
				<tr>
					<td><b>Chicago</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Chicago-MR.sqrl"><i class="ui download icon"></i> 389 subjects</a><div class="ui basic grey label">13 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Chicago-EEG.sqrl"><i class="ui download icon"></i> 361 subjects</a><div class="ui basic grey label">92 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Chicago-ET.sqrl"><i class="ui download icon"></i> 328 subjects</a><div class="ui basic grey label">2.6 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Chicago-NonImaging.sqrl"><i class="ui download icon"></i> 248 subjects</a><div class="ui basic grey label">380 KB</div></div></td>
					<td class="right aligned">107 GB</td>
				</tr>
				<tr>
					<td><b>Dallas</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Dallas-MR.sqrl"><i class="ui download icon"></i> 261 subjects</a><div class="ui basic grey label">9.3 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Dallas-EEG.sqrl"><i class="ui download icon"></i> 179 subjects</a><div class="ui basic grey label">43 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Dallas-ET.sqrl"><i class="ui download icon"></i> 247 subjects</a><div class="ui basic grey label">2.0 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Dallas-NonImaging.sqrl"><i class="ui download icon"></i> 265 subjects</a><div class="ui basic grey label">395 KB</div></div></td>
					<td class="right aligned">54 GB</td>
				</tr>
				<tr>
					<td><b>Detroit</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Detroit-MR.sqrl"><i class="ui download icon"></i> 217 subjects</a><div class="ui basic grey label">24 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Detroit-EEG.sqrl"><i class="ui download icon"></i> 204 subjects</a><div class="ui basic grey label">56 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Detroit-ET.sqrl"><i class="ui download icon"></i> 190 subjects</a><div class="ui basic grey label">1.4 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Detroit-NonImaging.sqrl"><i class="ui download icon"></i> 195 subjects</a><div class="ui basic grey label">300 KB</div></div></td>
					<td class="right aligned">81 GB</td>
				</tr>
				<tr>
					<td><b>Hartford</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Hartford-MR.sqrl"><i class="ui download icon"></i> 483 subjects</a><div class="ui basic grey label">53 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Hartford-EEG.sqrl"><i class="ui download icon"></i> 424 subjects</a><div class="ui basic grey label">121 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Hartford-ET.sqrl"><i class="ui download icon"></i> 405 subjects</a><div class="ui basic grey label">3.3 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP1-Hartford-NonImaging.sqrl"><i class="ui download icon"></i> 269 subjects</a><div class="ui basic grey label">428 KB</div></div></td>
					<td class="right aligned">177 GB</td>
				</tr>
				<tr>
					<td>Total</td>
					<td class="right aligned">1844 subjects 147 GB</td>
					<td class="right aligned">1607 subjects, 407 GB</td>
					<td class="right aligned">1673 subjects, 14 GB</td>
					<td class="right aligned">1377 subjects 2.1 MB</td>
					<td class="right aligned">567 GB</td>
				</tr>
			</table>
			
			<br><br>
			<div class="ui top attached black segment">
				<div class="ui two column grid">
					<div class="column">
						<h1 class="ui header">
							PARDIP
							<div class="sub header">
								Psychosis Affective Research Domain Intermediate Phenotypes
							</div>
						</h1>
					</div>
					<div class="right aligned column">
						<a class="ui blue button" href="https://www.biologicalpsychiatryjournal.com/article/S0006-3223(17)30813-2/abstract">Cite <i class="quote right icon"></i></a>
					</div>
				</div>
			</div>
			<table class="ui bottom attached selectable celled table">
				<thead>
					<th>Site</th>
					<th>MRI</th>
					<th>EEG</th>
					<th>ET</th>
					<th>Non-imaging</th>
					<th>Total</th>
				</thead>
				
				<?
					$totalSubjects = array();
					$totalSizes = array();
					$totalPardipSize = 0;
					/* iterate over the sites */
					foreach ($pardip as $site => $mod) {
						$totalsitesize = 0;
						?>
						<tr>
							<td><b><?=$site?></b></td>
						<?
						ksort($mod);
						/* iterate over the modalities */
						foreach (array('MR','EEG','ET','NonImaging') as $modality) {
							if (array_key_exists($modality, $pardip[$site])) {
								$subjectCount = $pardip[$site][$modality]['subjectCount'];
								$unzippedSize = $pardip[$site][$modality]['unzippedSize'];
								$webpath = "bsnip/PARDIP-$site-$modality.sqrl";
								$filepath = "/nidb/data/$webpath";
								$filesize = filesize($filepath);
								$totalsitesize += $filesize;
								$totalPardipSize += $filesize;
								
								$totalSizes[$modality] += $filesize;
								$totalSubjects[$modality] += $subjectCount;
								?>
								<td>
									<div class="ui fluid labeled button">
										<a class="ui compact basic blue button" href="<?=$webpath?>"><i class="ui download icon"></i> <?=$subjectCount?> subjects</a>
										<div class="ui basic grey label"><?=HumanReadableFilesize($filesize);?></div>
									</div>
								</td>
								<?
							}
							else {
								?>
								<td>-</td>
								<?
							}
						}
						?>
							<td class="right aligned"><?=HumanReadableFilesize($totalsitesize);?></td>
						</tr>
						<?
					}
				
				?>
				<tr>
					<td>Total</td>
					<?
						foreach (array('MR','EEG','ET','NonImaging') as $modality) {
							?><td class="right aligned"><?=$totalSubjects[$modality]?> subjects, <?=HumanReadableFilesize($totalSizes[$modality])?></td><?
						}
					?>
					<td class="right aligned"><?=HumanReadableFilesize($totalPardipSize)?></td>
				</tr>
			</table>

			<br><br>
			<div class="ui top attached black segment">
				<div class="ui two column grid">
					<div class="column">
						<h1 class="ui header">
							BSNIP2
							<div class="sub header">
								Bipolar and Schizophrenia Network for Intermediate Phenotypes 2
							</div>
						</h1>
						Number of subjects (zipped file size)
					</div>
					<div class="right aligned column">
						<a class="ui blue button" href="https://pubmed.ncbi.nlm.nih.gov/33622437/">Cite <i class="quote right icon"></i></a>
					</div>
				</div>
			</div>
			<table class="ui bottom attached selectable celled table">
				<thead>
					<th>Site</th>
					<th>MRI</th>
					<th>EEG</th>
					<th>ET</th>
					<th>Non-imaging</th>
					<th>Total</th>
				</thead>
				
				<?
					$totalSubjects = array();
					$totalSizes = array();
					$totalUnzippedSizes = array();
					$totalPardipSize = 0;
					/* iterate over the sites */
					foreach ($bsnip2 as $site => $mod) {
						$totalsitesize = 0;
						$totalsiteunzipsize = 0;
						?>
						<tr>
							<td><b><?=$site?></b></td>
						<?
						ksort($mod);
						/* iterate over the modalities */
						foreach (array('MR','EEG','ET','NonImaging') as $modality) {
							if (array_key_exists($modality, $bsnip2[$site])) {
								$subjectCount = $bsnip2[$site][$modality]['subjectCount'];
								$unzippedSize = $bsnip2[$site][$modality]['unzippedSize'];
								$webpath = "bsnip/BSNIP2-$site-$modality.sqrl";
								$filepath = "/nidb/data/$webpath";
								$filesize = filesize($filepath);
								$totalsitesize += $filesize;
								$totalsiteunzipsize += $unzippedSize;
								$totalBsnip2Size += $filesize;
								
								$totalUnzippedSizes[$modality] += $unzippedSize;
								$totalSizes[$modality] += $filesize;
								$totalSubjects[$modality] += $subjectCount;
								?>
								<td>
									<div class="ui fluid labeled button">
										<a class="ui compact basic blue button" href="<?=$webpath?>"><i class="ui download icon"></i> <?=$subjectCount?> subjects</a>
										<div class="ui basic grey label"><?=HumanReadableFilesize($filesize);?></div>
									</div>
								</td>
								<?
							}
							else {
								?>
								<td>-</td>
								<?
							}
						}
						?>
							<td class="right aligned"><?=HumanReadableFilesize($totalsitesize);?></td>
						</tr>
						<?
					}
				
				?>
				<tr>
					<td>Total</td>
					<?
						foreach (array('MR','EEG','ET','NonImaging') as $modality) {
							?><td class="right aligned"><?=$totalSubjects[$modality]?> subjects, <?=HumanReadableFilesize($totalSizes[$modality])?></td><?
						}
					?>
					<td class="right aligned"><?=HumanReadableFilesize($totalBsnip2Size)?></td>
				</tr>
			</table>
			
			<br><br><br>
		</div>
		<?
	}
?>
