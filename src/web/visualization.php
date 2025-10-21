<?
 // ------------------------------------------------------------------------------
 // NiDB series.php
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
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Visualization</title>
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
	$type = GetVariable("type");
	$filepath = GetVariable("filepath");
	$feature = GetVariable("feature");
	$component = GetVariable("component");
	
	/* determine action */
	switch($action) {
		case "visualize":
			DisplayMenu();
			switch ($type) {
				case "ica":
					DisplayICA($filepath, $feature, $component);
					break;
				case "bar":
					DisplayBar($filepath, $feature, $component);
					break;
			}
			break;
		default:
			DisplayMenu();
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- DisplayMenu ------------------------ */
	/* -------------------------------------------- */
	function DisplayMenu() {
		?>
		<div class="ui two column grid">
			<div class="column">
				<h2 class="ui header">Available visualizations</h2>
				<a href="visualization.php?action=visualize&type=ica" class="ui button">ICA</a>
				<!--<a href="visualization.php?action=visualize&type=line" class="ui button">I</a>-->
			</div>
			<div class="right aligned column">
				<a href="pipelines.php" class="ui button"><i class="arrow alternate circle left icon"></i> Back to pipelines</a>
			</div>
		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- DisplayICA ------------------------- */
	/* -------------------------------------------- */
	function DisplayICA($filepath, $featurenum, $componentnum) {
		$filepath = mysqli_real_escape_string($GLOBALS['linki'], $filepath);
		$featurenum = mysqli_real_escape_string($GLOBALS['linki'], $featurenum);
		$componentnum = mysqli_real_escape_string($GLOBALS['linki'], $componentnum);
	
		?>
		<script>
			$(document).ready(function(){
				$('#pageloading').hide();
			});
		</script>
		
		<div class="ui text container" id="pageloading">
			<div class="ui segment" align="center">
				<h2 class="ui header">
					<i class="large spinner loading icon"></i> Loading...
				</h2>
			</div>
			<br>
		</div>
		
		<script src="https://d3js.org/d3.v7.min.js"></script>
		
		<script>
			function CheckNFSPath() {
				var xhttp = new XMLHttpRequest();
				xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						document.getElementById("pathcheckresult").innerHTML = this.responseText;
					}
				};
				var filepath = document.getElementById("filepath").value;
				//alert(filepath);
				xhttp.open("GET", "ajaxapi.php?action=validatepath&nfspath=" + filepath, true);
				xhttp.send();
			}
		</script>
		
		<div class="ui container">
			<h2 class="ui header">ICA Visualization</h2>
			<form class="ui form" method="post" action="visualization.php">
				<input type="hidden" name="action" value="visualize">
				<input type="hidden" name="type" value="ica">
				<div class="field">
					<label>Path to file(s)</label>
					<input type="text" name="filepath" id="filepath" value="<?=$filepath?>" onKeyUp="CheckNFSPath()"> <span id="pathcheckresult"></span>
				</div>
		<?
		
		if ($filepath != "") {
			$filepath = $GLOBALS['cfg']['mountdir'] . "/" . $filepath;
			if (file_exists($filepath)) {
				
				/* valid path, let's check for valid files */
				chdir($filepath);
				foreach (glob("*.asc") as $filename) {
					
					/* parse the filename */
					$parts = explode('_', $filename);
					$feature = $parts[5];
					$comp = substr($parts[6], 0, 3);
					$components[$feature][$comp] = $filename;
					$feats[] = $feature;
					$comps[] = $comp;
					
					//echo "$filename size " . filesize($filename) . "<br>";
					/* if valid files, load the files into memory */
					//ReadSpaceDelimitedFile($filename);
					
				}
				
				$feats = array_unique($feats);
				$comps = array_unique($comps);
				
				//PrintVariable($components);
				/* visualize */
				?>
				<div class="two fields">
					<div class="field">
						<label>Feature</label>
						<select name="feature">
							<?
							foreach ($feats as $f) {
								if ($f == $featurenum) { $selected = "selected"; } else { $selected = ""; }
								?><option value="<?=$f?>" <?=$selected?>><?=$f?><?
							}
							?>
						</select>
					</div>
					<div class="field">
						<label>Component</label>
						<select name="component">
							<?
							foreach ($comps as $c) {
								if ($c == $componentnum) { $selected = "selected"; } else { $selected = ""; }
								?><option value="<?=$c?>" <?=$selected?>><?=$c?><?
							}
							?>
						</select>
					</div>
				</div>

				<?
					/* read in the selected file */
					$filename = "d20_joint_comp_ica_feature_" . $featurenum . "_" . $componentnum . ".asc";
					if (file_exists("$filepath/$filename")) {
						echo "$filepath/$filename exists<br>";
					}
					else {
						echo "[$filepath/$filename] does not exist<br>";
					}
					
					$data = ReadSpaceDelimitedFile("$filepath/$filename");
					//PrintVariable($data);
					
					foreach ($data as $d) {
						$datums[] = "{\"x\":$d[0], \"y\":$d[1]}";
					}
					$datum = implode(",", $datums);
				?>
				
				<div id="my_dataviz"></div>
				<div id="my_dataviz3"></div>
				
				<script>
					// create the svg area
					const svg = d3.select("#my_dataviz")
						.append("svg")
						.attr("width", 400)
						.attr("height", 400)
						.append("g")
						.attr("transform", "translate(200,200)")

					// create input data: a square matrix that provides flow between entities
					const matrix = [
						[1,  2, 3, 4, 5],
						[ 6, 7, 8, 9, 10],
						[ 11, 12, 13, 14, 15],
						[ 16, 17, 18, 19, 20],
						[ 21, 22, 23, 24, 25]
					];

					// give this matrix to d3.chord(): it will calculates all the info we need to draw arc and ribbon
					const res = d3.chord()
						.padAngle(0.05)     // padding between entities (black arc)
						.sortSubgroups(d3.descending)
					(matrix)

					// add the groups on the inner part of the circle
					svg
						.datum(res)
						.append("g")
						.selectAll("g")
						.data(d => d.groups)
						.join("g")
						.append("path")
						.style("fill", "grey")
						.style("stroke", "black")
						.attr("d", d3.arc()
							.innerRadius(195)
							.outerRadius(200)
						)

					// Add the links between groups
					svg
						.datum(res)
						.append("g")
						.selectAll("path")
						.data(d => d)
						.join("path")
						.attr("d", d3.ribbon()
							.radius(190)
						)
						.style("fill", "#69b3a2")
						.style("stroke", "black");

				</script>

				<script>
					// set the dimensions and margins of the graph
					var margin3 = {top: 10, right: 30, bottom: 30, left: 60},
						width = 1500 - margin3.left - margin3.right,
						height = 400 - margin3.top - margin3.bottom;
					
					var data3 = [<?=$datum?>];

					x = d3.scaleLinear()
						.domain(d3.extent(data3, d => d.x)).nice()
						.range([margin3.left, width - margin3.right]);
						
					y = d3.scaleLinear()
						.domain(d3.extent(data3, d => d.y)).nice()
						.range([height - margin3.bottom, margin3.top]);
					
					grid = g => g
						.attr("stroke", "currentColor")
						.attr("stroke-opacity", 0.1)
						.call(g => g.append("g")
						.selectAll("line")
						.data(x.ticks())
						.join("line")
						.attr("x1", d => 0.5 + x(d))
						.attr("x2", d => 0.5 + x(d))
						.attr("y1", margin3.top)
						.attr("y2", height - margin3.bottom))
						.call(g => g.append("g")
						.selectAll("line")
						.data(y.ticks())
						.join("line")
						.attr("y1", d => 0.5 + y(d))
						.attr("y2", d => 0.5 + y(d))
						.attr("x1", margin3.left)
						.attr("x2", width - margin3.right));
					
					xAxis = g => g
						.attr("transform", `translate(0,${height - margin3.bottom})`)
						.call(d3.axisBottom(x).ticks(width / 80))
						.call(g => g.select(".domain").remove())
						.call(g => g.append("text")
						.attr("x", width)
						.attr("y", margin3.bottom - 4)
						.attr("fill", "currentColor")
						.attr("text-anchor", "end")
						.text(data3.x));
						
					yAxis = g => g
						.attr("transform", `translate(${margin3.left},0)`)
						.call(d3.axisLeft(y))
						.call(g => g.select(".domain").remove())
						.call(g => g.append("text")
						.attr("x", -margin3.left)
						.attr("y", 10)
						.attr("fill", "currentColor")
						.attr("text-anchor", "start")
						.text(data3.y));
				
					const svg3 = d3.select("#my_dataviz3")
						.append("svg")
						.attr("width", width + margin3.left + margin3.right)
						.attr("height", height + margin3.top + margin3.bottom)
						.append("g")
						.attr("transform", "translate(" + margin3.left + "," + margin3.top + ")");

					svg3.append("g")
						.call(xAxis);

					svg3.append("g")
						.call(yAxis);

					svg3.append("g")
						.call(grid);

					svg3.append("g")
						//.attr("stroke", "steelblue")
						//.attr("stroke-width", 1.5)
						.attr("fill", "steelblue")
						.selectAll("circle")
						.data(data3)
						.join("circle")
						.attr("cx", d => x(d.x))
						.attr("cy", d => y(d.y))
						.attr("r", 1);

					svg3.append("g")
						.attr("font-family", "sans-serif")
						.attr("font-size", 10)
						.selectAll("text")
						.data(data3)
						.join("text")
						.attr("dy", "0.35em")
						.attr("x", d => x(d.x) + 7)
						.attr("y", d => y(d.y))
						.text(d => d.name);

				</script>
				<?
				
			}
			else {
				Error("File path [$filepath] does not exist");
			}
		}
		?>
				<button class="ui primary button">Visualize</button>
			</form>

		</div>
		<?
	}
	
	
	/* -------------------------------------------- */
	/* ------- ReadSpaceDelimitedFile ------------- */
	/* -------------------------------------------- */
	function ReadSpaceDelimitedFile($f) {
		
		$data = array();
		
		$fh = fopen($f, "r");
		if ($fh) {
			while (($line = fgets($fh)) !== false) {
				/* split on whitespace */
				$parts = preg_split('/\s+/', trim($line));
				$parts = array_filter($parts);
				$data[] = $parts;
			}
			fclose($fh);
		}
		else {
			// error opening the file.
		} 

		return $data;
	}
	
?>


<? include("footer.php") ?>
