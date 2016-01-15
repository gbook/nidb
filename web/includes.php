<?
 // ------------------------------------------------------------------------------
 // NiDB includes.php
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
?>

<!-- all of the javascripts and style include files -->
<script type="text/javascript" src="scripts/js/jquery-2.1.1.min.js"></script>
<script type="text/javascript" src="scripts/js/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript" src="scripts/js/jquery-ui-1.10.3.custom.min.js"></script>
<script type="text/javascript" src="scripts/flot/jquery.flot.min.js"></script>
<script type="text/javascript" src="scripts/flot/jquery.flot.time.min.js"></script>
<script type="text/javascript" src="scripts/jquery.validate.js"></script>
<script type="text/javascript" src="scripts/jquery.editinplace.js"></script>
<script type="text/javascript" src="scripts/jquery.tablesorter.min.js"></script>
<script type="text/javascript" src="scripts/jquery.imagepreview.js"></script>
<script type="text/javascript" src="scripts/jquery.details.js"></script>

<link rel="stylesheet" type="text/css" href="scripts/themes/blue/style.css">

<!-- menu drop box -->
<script language="javascript" type="text/javascript" src="scripts/jquery.dropmenu.js"></script>
<link type="text/css" rel="stylesheet" href="scripts/dropmenu.css" />

<!-- file uploader -->
<script src="scripts/fileuploader.js" type="text/javascript"></script>
<link href="scripts/fileuploader.css" rel="stylesheet" type="text/css">	

<link rel="stylesheet" type="text/css" href="scripts/development-bundle/themes/smoothness/jquery-ui.css">

<!-- main style sheet -->
<link rel="stylesheet" type="text/css" href="style.css">


<!-- the following are for emulating the <details> and <summary> tags -->
<script>
	window.console || (window.console = { 'log': alert });
	$(function() {

		// Add conditional classname based on support
		$('html').addClass($.fn.details.support ? 'details' : 'no-details');

		// Emulate <details> where necessary and enable open/close event handlers
		$('details').details();

	});
	
	/* odd fix necessary to make the HTML'd jQuery tooltips work */
	$(function () {
		$(document).tooltip({
			content: function () {
				return $(this).prop('title');
			}
		});
	});
  
</script>

<script>
	$(function() {
		$( document ).tooltip({show:{effect:'appear', delay: 500}, hide:{duration:0}});
	});
</script>

<style>
	.ui-tooltip {
		padding: 10px 8px;
		border-radius: 5px;
		font-size: 11px;
		border: 1px solid black;
		box-shadow: 3px 3px 3px #888;
		background: #222;
		color: white;
	}
</style>

<script>
	$(document).ready(function() {
		$('.message').fadeOut(7500);
	});
</script>