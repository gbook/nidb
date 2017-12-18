<?
 // ------------------------------------------------------------------------------
 // NiDB includes.php
 // Copyright (C) 2004 - 2017
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
<script type="text/javascript" src="scripts/jquery.jeditable.js"></script>
<script type="text/javascript" src="scripts/jquery.tablesorter.min.js"></script>
<script type="text/javascript" src="scripts/jquery.imagepreview.js"></script>
<script type="text/javascript" src="scripts/jquery.details.js"></script>
<script type="text/javascript" src="scripts/stupidtable.min.js"></script>

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

<!-- for the global tooltip settings -->
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
		
		/* below are the table sorting functions */
		// Helper function to convert a string of the form "Mar 15, 1987" into a Date object.
        var date_from_string = function(str) {
          var months = ["jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec"];
          var pattern = "^([a-zA-Z]{3})\\s*(\\d{1,2}),\\s*(\\d{4})$";
          var re = new RegExp(pattern);
          var DateParts = re.exec(str).slice(1);
          var Year = DateParts[2];
          var Month = $.inArray(DateParts[0].toLowerCase(), months);
          var Day = DateParts[1];
          return new Date(Year, Month, Day);
        }
        var table = $('.sortable').stupidtable({
          "date": function(a,b) {
            // Get these into date objects for comparison.
            aDate = date_from_string(a);
            bDate = date_from_string(b);
            return aDate - bDate;
          }
        });
        table.on("beforetablesort", function (event, data) {
          // Apply a "disabled" look to the table while sorting.
          // Using addClass for "testing" as it takes slightly longer to render.
          $("#msg").text("Sorting...");
          $("table").addClass("disabled");
        });
        table.on("aftertablesort", function (event, data) {
          // Reset loading message.
          $("#msg").html("&nbsp;");
          $("table").removeClass("disabled");
          var th = $(this).find("th");
          th.find(".arrow").remove();
          var dir = $.fn.stupidtable.dir;
          var arrow = data.direction === dir.ASC ? "&uarr;" : "&darr;";
          th.eq(data.column).append('<span class="arrow">' + arrow +'</span>');
        });		
	});
</script>