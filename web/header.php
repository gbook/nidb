<?
 // ------------------------------------------------------------------------------
 // NIDB header.php
 // Copyright (C) 2004 - 2014
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
?>

<!-- all of the javascripts and style include files -->
<link rel="stylesheet" type="text/css" href="scripts/development-bundle/themes/ui-lightness/jquery-ui-1.8.14.custom.css">
<link rel="stylesheet" type="text/css" href="scripts/development-bundle/themes/ui-lightness/ui.theme.css">
<script type="text/javascript" src="scripts/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="scripts/jquery.flot.js"></script>
<script type="text/javascript" src="scripts/jquery.sparkline.js"></script>
<script type="text/javascript" src="scripts/jquery.validate.js"></script>
<script type="text/javascript" src="scripts/jquery.tablehover.pack.js"></script>
<script type="text/javascript" src="scripts/jquery.editinplace.js"></script>
<script type="text/javascript" src="scripts/jquery.uitableedit.js"></script>
<script type="text/javascript" src="scripts/jquery.table2csv.js"></script>
<script type="text/javascript" src="scripts/jquery.tablesorter.min.js"></script>
<script type="text/javascript" src="scripts/jquery.imagepreview.js"></script>

<script type="text/javascript" src="scripts/jquery.jListbox.js"></script>
<link rel="stylesheet" type="text/css" href="scripts/jquery.jListbox.css">

<!--<script type="text/javascript" src="scripts/fancybox/jquery.fancybox-1.3.1.pack.js"></script>
<link rel="stylesheet" href="scripts/fancybox/jquery.fancybox-1.3.1.css" type="text/css" media="screen" />-->

<script type="text/javascript" src="scripts/cluetip/jquery.cluetip.js"></script>
<link rel="stylesheet" type="text/css" href="scripts/cluetip/jquery.cluetip.css"></link>

<script type="text/javascript" src="scripts/development-bundle/ui/jquery-ui-1.8.14.custom.js"></script>
<script type="text/javascript" src="scripts/wz_tooltip.js"></script>
<!--<script type="text/javascript" src="scripts/editinplace.0.4.js"></script>-->

<!-- menu drop box -->
<script language="javascript" type="text/javascript" src="scripts/jquery.dropmenu.js"></script>
<link type="text/css" rel="stylesheet" href="scripts/dropmenu.css" />

<!-- file uploader -->
<script src="scripts/fileuploader.js" type="text/javascript"></script>
<link href="scripts/fileuploader.css" rel="stylesheet" type="text/css">	

<!-- main style sheet -->
<link rel="stylesheet" type="text/css" href="style.css">

<style>
	div.watermark {
		color: red;
		font-size: 35pt;
		font-weight: bold;
		/*-webkit-transform: rotate(-45deg);
		-moz-transform: rotate(-45deg);*/
		position: absolute;
		width: 100%;
		height: 100%;
		margin: 0;
		opacity: 0.3;
		z-index: -1;
		/*left: 100px;
		top: 100px;*/
		text-align: center;
		vertical-align: middle;
		/*border: 1px solid black*/
	}
</style>
<div class="watermark"><br>Development<br>Server</div>