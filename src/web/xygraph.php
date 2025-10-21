<?
 // ------------------------------------------------------------------------------
 // NiDB xygraph.php
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

	require_once ('scripts/jpgraph/jpgraph.php');
	require_once ('scripts/jpgraph/jpgraph_line.php');
	require_once ('scripts/jpgraph/jpgraph_date.php');
	require_once ('scripts/jpgraph/jpgraph_utils.inc.php');

	$t = $_REQUEST['t']; /* title */
	$w = $_REQUEST['w']; /* width */
	$h = $_REQUEST['h']; /* height */
	$x = $_REQUEST['x']; /* x-axis data (probably time) comma separated */
	$y = $_REQUEST['y']; /* y-axis data (the values) comma separated */
	$xlabel = $_REQUEST['xlabel']; /* x-axis label */
	$ylabel = $_REQUEST['ylabel']; /* y-axis label */
	$xtype = $_REQUEST['xtype']; /* x-axis datatype: int, lin, dat */
	$ytype = $_REQUEST['ytype']; /* y-axis datatype: int, lin, dat */

	$xdata = explode(',',$x);
	$ydata = explode(',',$y);
	
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