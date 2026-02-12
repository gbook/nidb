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
				<tr>
					<td><b>Boston</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Boston-MR.sqrl"><i class="ui download icon"></i> 68 subjects</a><div class="ui basic grey label">6.7 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Boston-EEG.sqrl"><i class="ui download icon"></i> 76 subjects</a><div class="ui basic grey label">35 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Boston-ET.sqrl"><i class="ui download icon"></i> 63 subjects</a><div class="ui basic grey label">467 MB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Boston-NonImaging.sqrl"><i class="ui download icon"></i> 64 subjects</a><div class="ui basic grey label">116 KB</div></div></td>
					<td class="right aligned">42 GB</td>
				</tr>
				<tr>
					<td><b>Dallas</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Dallas-MR.sqrl"><i class="ui download icon"></i> 79 subjects</a><div class="ui basic grey label">9.3 GB</div></div></td>
					<td>-</td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Dallas-ET.sqrl"><i class="ui download icon"></i> 96 subjects</a><div class="ui basic grey label">2.0 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Dallas-NonImaging.sqrl"><i class="ui download icon"></i> 70 subjects</a><div class="ui basic grey label">395 KB</div></div></td>
					<td class="right aligned">54 GB</td>
				</tr>
				<tr>
					<td><b>Georgia</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Georgia-MR.sqrl"><i class="ui download icon"></i> 4 subjects</a><div class="ui basic grey label">369 MB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Georgia-EEG.sqrl"><i class="ui download icon"></i> 4 subjects</a><div class="ui basic grey label">2.6 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Georgia-ET.sqrl"><i class="ui download icon"></i> 4 subjects</a><div class="ui basic grey label">20 MB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Georgia-NonImaging.sqrl"><i class="ui download icon"></i> 6 subjects</a><div class="ui basic grey label">12 KB</div></div></td>
					<td class="right aligned">3.0 GB</td>
				</tr>
				<tr>
					<td><b>Hartford</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Hartford-MR.sqrl"><i class="ui download icon"></i> 121 subjects</a><div class="ui basic grey label">70 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Hartford-EEG.sqrl"><i class="ui download icon"></i> 121 subjects</a><div class="ui basic grey label">885 MB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Hartford-ET.sqrl"><i class="ui download icon"></i> 106 subjects</a><div class="ui basic grey label">3.3 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/PARDIP-Hartford-NonImaging.sqrl"><i class="ui download icon"></i> 120 subjects</a><div class="ui basic grey label">216 KB</div></div></td>
					<td class="right aligned">126 GB</td>
				</tr>
				<tr>
					<td>Total</td>
					<td class="right aligned">272 subjects 89 GB</td>
					<td class="right aligned">201 subjects, 93 GB</td>
					<td class="right aligned">269 subjects, 2.2 GB</td>
					<td class="right aligned">260 subjects 476 KB</td>
					<td class="right aligned">183 GB</td>
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
				<tr>
					<td><b>Boston</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Boston-MR.sqrl"><i class="ui download icon"></i> 179 subjects</a><div class="ui basic grey label">24 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Boston-EEG.sqrl"><i class="ui download icon"></i> 206 subjects</a><div class="ui basic grey label">97 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Boston-ET.sqrl"><i class="ui download icon"></i> 205 subjects</a><div class="ui basic grey label">1.1 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Boston-NonImaging.sqrl"><i class="ui download icon"></i> 342 subjects</a><div class="ui basic grey label">608 KB</div></div></td>
					<td class="right aligned">121 GB</td>
				</tr>
				<tr>
					<td><b>Chicago</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Chicago-MR.sqrl"><i class="ui download icon"></i> 313 subjects</a><div class="ui basic grey label">29 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Chicago-EEG.sqrl"><i class="ui download icon"></i> 327 subjects</a><div class="ui basic grey label">158 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Chicago-ET.sqrl"><i class="ui download icon"></i> 390 subjects</a><div class="ui basic grey label">1.9 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Chicago-NonImaging.sqrl"><i class="ui download icon"></i> 662 subjects</a><div class="ui basic grey label">1.2 MB</div></div></td>
					<td class="right aligned">189 GB</td>
				</tr>
				<tr>
					<td><b>Dallas</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Dallas-MR.sqrl"><i class="ui download icon"></i> 171 subjects</a><div class="ui basic grey label">15 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Dallas-EEG.sqrl"><i class="ui download icon"></i> 249 subjects</a><div class="ui basic grey label">118 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Dallas-ET.sqrl"><i class="ui download icon"></i> 247 subjects</a><div class="ui basic grey label">1.4 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Dallas-NonImaging.sqrl"><i class="ui download icon"></i> 562 subjects</a><div class="ui basic grey label">900 KB</div></div></td>
					<td class="right aligned">134 GB</td>
				</tr>
				<tr>
					<td><b>Georgia</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Georgia-MR.sqrl"><i class="ui download icon"></i> 243 subjects</a><div class="ui basic grey label">22 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Georgia-EEG.sqrl"><i class="ui download icon"></i> 336 subjects</a><div class="ui basic grey label">215 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Georgia-ET.sqrl"><i class="ui download icon"></i> 327 subjects</a><div class="ui basic grey label">1.8 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Georgia-NonImaging.sqrl"><i class="ui download icon"></i> 580 subjects</a><div class="ui basic grey label">992 KB</div></div></td>
					<td class="right aligned">239 GB</td>
				</tr>
				<tr>
					<td><b>Hartford 1</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Hartford1-MR.sqrl"><i class="ui download icon"></i> 210 subjects</a><div class="ui basic grey label">213 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Hartford1-EEG.sqrl"><i class="ui download icon"></i> 23 subjects</a><div class="ui basic grey label">12 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Hartford1-ET.sqrl"><i class="ui download icon"></i> 240 subjects</a><div class="ui basic grey label">1.2 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Hartford1-NonImaging.sqrl"><i class="ui download icon"></i> 269 subjects</a><div class="ui basic grey label">528 KB</div></div></td>
					<td class="right aligned">225 GB</td>
				</tr>
				<tr>
					<td><b>Hartford 2</b></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Hartford2-MR.sqrl"><i class="ui download icon"></i> 149 subjects</a><div class="ui basic grey label">146 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Hartford2-EEG.sqrl"><i class="ui download icon"></i> 34 subjects</a><div class="ui basic grey label">17 GB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Hartford2-ET.sqrl"><i class="ui download icon"></i> 131 subjects</a><div class="ui basic grey label">696 MB</div></div></td>
					<td><div class="ui fluid labeled button"><a class="ui compact basic blue button" href="bsnip/BSNIP2-Hartford2-NonImaging.sqrl"><i class="ui download icon"></i> 278 subjects</a><div class="ui basic grey label">500 KB</div></div></td>
					<td class="right aligned">163 GB</td>
				</tr>
				<tr>
					<td>Total</td>
					<td class="right aligned">1265 subjects 446 GB</td>
					<td class="right aligned">1175 subjects, 615 GB</td>
					<td class="right aligned">1540 subjects, 7.9 GB</td>
					<td class="right aligned">2693 subjects 4.7 MB</td>
					<td class="right aligned">1.1 TB</td>
				</tr>
			</table>
			
			<br><br><br>
		</div>
		<?
	}
?>
