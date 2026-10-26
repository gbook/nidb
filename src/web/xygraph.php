<?
 // ------------------------------------------------------------------------------
 // NiDB xygraph.php
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
	require "functions.php";
	require "includes_php.php"; /* login required */

	require_once ('scripts/jpgraph/jpgraph.php');
	require_once ('scripts/jpgraph/jpgraph_line.php');
	require_once ('scripts/jpgraph/jpgraph_date.php');
	require_once ('scripts/jpgraph/jpgraph_utils.inc.php');

	$t = (string)($_REQUEST['t'] ?? ''); /* title */
	/* clamp the canvas size: an unbounded w/h lets one request exhaust the server's memory */
	$w = max(50, min(2000, (int)($_REQUEST['w'] ?? 420))); /* width */
	$h = max(50, min(2000, (int)($_REQUEST['h'] ?? 200))); /* height */
	$x = (string)($_REQUEST['x'] ?? ''); /* x-axis data (probably time) comma separated */
	$y = (string)($_REQUEST['y'] ?? ''); /* y-axis data (the values) comma separated */
	$xlabel = (string)($_REQUEST['xlabel'] ?? ''); /* x-axis label */
	$ylabel = (string)($_REQUEST['ylabel'] ?? ''); /* y-axis label */
	/* axis types are passed to jpgraph's SetScale(): only allow its known scale types */
	$xtype = in_array($_REQUEST['xtype'] ?? '', array('int', 'lin', 'dat', 'text', 'log'), true) ? $_REQUEST['xtype'] : 'lin'; /* x-axis datatype */
	$ytype = in_array($_REQUEST['ytype'] ?? '', array('int', 'lin', 'dat', 'text', 'log'), true) ? $_REQUEST['ytype'] : 'lin'; /* y-axis datatype */

	/* at most 10000 points; y values must be numeric */
	$xdata = array_slice(explode(',',$x), 0, 10000);
	$ydata = array_slice(array_map('floatval', explode(',',$y)), 0, 10000);
	
	// Create a graph instance
	$graph = new Graph($w,$h);
	$graph->SetMargin(50,20,30,80);
	 
	// Specify what scale we want to use,
	// dat = date scale for the X-axis
	// lin = floating/linear scale for the Y-axis
	$graph->SetScale("$xtype$ytype");
	 
	// Setup a title for the graph
	$graph->title->Set($t);
	 
	// Setup titles and X-axis labels
	$graph->xaxis->title->Set($xlabel);
	$graph->xaxis->SetTickLabels($xdata);
	$graph->xaxis->SetLabelAngle(90);
	 
	// Setup Y-axis title
	$graph->yaxis->title->Set($ylabel);
	 
	// Create the linear plot
	$lineplot=new LinePlot($ydata);
	$lineplot->SetFillColor('lightblue@0.3');
	 
	// Add the plot to the graph
	$graph->Add($lineplot);
	 
	// Display the graph
	$graph->Stroke();
?>