<?
 // ------------------------------------------------------------------------------
 // NiDB includes.php
 // Copyright (C) 2004 - 2015
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
<!--<script type="text/javascript" src="scripts/jquery.sparkline.js"></script>-->
<script type="text/javascript" src="scripts/jquery.validate.js"></script>
<!--<script type="text/javascript" src="scripts/jquery.tablehover.pack.js"></script>-->
<script type="text/javascript" src="scripts/jquery.editinplace.js"></script>
<!--<script type="text/javascript" src="scripts/jquery.uitableedit.js"></script>-->
<!--<script type="text/javascript" src="scripts/jquery.table2csv.js"></script>-->
<script type="text/javascript" src="scripts/jquery.tablesorter.min.js"></script>
<script type="text/javascript" src="scripts/jquery.imagepreview.js"></script>
<script type="text/javascript" src="scripts/jquery.details.js"></script>
<!--<script type="text/javascript" src="scripts/jquery-ui-timepicker-addon.js"></script>-->
<!--<script type="text/javascript" src="scripts/jquery.bpopup.min.js"></script>-->

<!--<link rel="stylesheet" type="text/css" href="scripts/style.min.css">-->

<!--<script type="text/javascript" src="scripts/jquery.jListbox.js"></script>
<link rel="stylesheet" type="text/css" href="scripts/jquery.jListbox.css">-->

<link rel="stylesheet" type="text/css" href="scripts/themes/blue/style.css">

<!--
<script type="text/javascript" src="scripts/fancybox/jquery.fancybox.pack.js"></script>
<link rel="stylesheet" href="scripts/fancybox/jquery.fancybox.css" type="text/css" media="screen" />
-->

<!-- Add mousewheel plugin (this is optional) -->
<!--<script type="text/javascript" src="scripts/fancybox/jquery.mousewheel-3.0.6.pack.js"></script>-->

<!-- Add fancyBox main JS and CSS files -->
<!--<script type="text/javascript" src="scripts/fancybox/jquery.fancybox.pack.js?v=2.1.3"></script>
<link rel="stylesheet" type="text/css" href="scripts/fancybox/jquery.fancybox.css?v=2.1.3" media="screen" />

<!-- Add fancyBox - button helper (this is optional) -->
<!--<link rel="stylesheet" type="text/css" href="scripts/fancybox/helpers/jquery.fancybox-buttons.css?v=2.1.3" />
<script type="text/javascript" src="scripts/fancybox/helpers/jquery.fancybox-buttons.js?v=2.1.3"></script>

<!-- Add fancyBox - thumbnail helper (this is optional) -->
<!--<link rel="stylesheet" type="text/css" href="scripts/fancybox/helpers/jquery.fancybox-thumbs.css?v=2.1.3" />
<script type="text/javascript" src="scripts/fancybox/helpers/jquery.fancybox-thumbs.js?v=2.1.3"></script> -->

<!-- Add fancyBox - media helper (this is optional) -->
<!--<script type="text/javascript" src="scripts/fancybox/helpers/jquery.fancybox-media.js?v=1.0.0"></script>
	
	
	
<!--<script type="text/javascript" src="scripts/cluetip/jquery.cluetip.js"></script>
<link rel="stylesheet" type="text/css" href="scripts/cluetip/jquery.cluetip.css"></link>-->

<!--<script type="text/javascript" src="scripts/wz_tooltip.js"></script>-->
<!--<script type="text/javascript" src="scripts/editinplace.0.4.js"></script>-->

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
		$( document ).tooltip({show:{effect:'appear'}, hide:{duration:0}});
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