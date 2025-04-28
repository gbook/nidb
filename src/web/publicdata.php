<?
 // ------------------------------------------------------------------------------
 // NiDB publicdatasets.php
 // Copyright (C) 2004 - 2022
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
		<title>NiDB - Publicly Shared Datasets</title>
	</head>

<body>
	<div class="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nidbapi.php";

?>	<h2 class="ui center aligned header"> Shared Datasets</h2> <?
	
	// Data Storage Path
	$datapath = 'publicdownload/';
		
	// Group Array
	$groups = [];

	// Get the files available in the given location
	$files = array_filter(array_diff(scandir($datapath),['.', '..']), function($file) use ($datapath) {
		return is_file($datapath . $file); // Ensure it is file not a directory
	});

	foreach ($files as $file) {
		// Naming convention is Group-filename (Splittig filename to get the group names)
		$parts  = explode('-',$file);
		if (count($parts) > 1 && !empty($parts[0])) {
			$groupName = $parts[0]; // group name should be before - in the filename

			// Adding file to the correesponding group
			if (!isset($groups[$groupName])) {
				$groups[$gorupName] = [];
			}

			$groups[$groupName][]= $file;
		}
	}

	// Dispalying the files in tabulate format
?>	<table class="ui celled table">
	<thead><tr><th>Project</th><th>Data Files</th></tr></thead>
	<tr>
		<td colspan="2"> 
			<div class='item'>Download squirrel utilities to unpack the data from squirel format:  <a href='https://github.com/gbook/squirrel/releases'>Squirrel Utilities</a></div>
			<div><br></div>
			<div class='item'>Please read the <a href='https://docs.neuroinfodb.org/nidb/contribute/squirrel-data-sharing-format'>Squirrel data sharing format documentation</a> for more details.</div>
		</td>
	</tr>
	<tbody>
<?

	foreach ($groups as $groupName => $files) {
	  if (!empty($files)){		// Ensures the groups are not Empty
?>		<tr>
		<td><? =$groupName?></td>
		<td>
		<div class="ui list">
<?
		// Display Files for each group
		foreach ($files as $file) {
?>			
			<div class='item'><a href="<? =$datapath?><? =$file?>" download><? =$file?></a></div>
<?		}
?>
		</div>
		</td>
		</tr>
<?	}
	}	?>
	
	</tbody>
	</table>


	</div>


<? include("footer.php") ?>
