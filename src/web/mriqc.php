<?
 // ------------------------------------------------------------------------------
 // NiDB mrqcchecklist.php
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
		<title>NiDB - Advanced mriqc</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$id = GetVariable("id");
	$projectid = GetVariable("projectid");

	/* determine action */
	switch ($action) {
		case 'viewmriqc':
			Viewmriqc($projectid);
			break;
		default:
			Viewmriqc($projectid);
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */
	
	/* -------------------------------------------- */
	/* ------- Viewmriqc -------------------------- */
	/* -------------------------------------------- */
	function Viewmriqc($projectid) {
		$projectid = mysqli_real_escape_string($GLOBALS['linki'], $projectid);

		$rowdata = array();

		/* get list of qc metrics */
		$resultNames = array();
		$sqlstring = "select distinct(g.qcresult_name) from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join qc_moduleseries e on a.mrseries_id = e.series_id join qc_results f on e.qcmoduleseries_id = f.qcmoduleseries_id left join qc_resultnames g on f.qcresultname_id = g.qcresultname_id where c.project_id = $projectid and e.modality = 'mr' and g.qcresult_type = 'number'";
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$resultNames[] = $row['qcresult_name'];
			}
		}
		$itemsToRemove = array("provenance", "bids_meta");
		foreach ($itemsToRemove as $item) {
			if (($key = array_search($item, $resultNames)) !== false) { unset($resultNames[$key]); }
		}
		
		$resultNames = array_values($resultNames);
		natsort($resultNames);
		
		/* get list of series associated with this project - populate a template table containing all subject/study/series */
		$sqlstring = "select a.*, b.study_num, d.uid, d.subject_id from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where c.project_id = $projectid";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$seriesRowID = $row['mrseries_id'];
				$studyRowID = $row['study_id'];
				$subjectRowID = $row['subject_id'];
				$seriesnum = $row['series_num'];
				$studynum = $row['study_num'];
				$uid = $row['uid'];
				$desc = $row['series_desc'];
				
				//$alldata[$uid][$studynum][$seriesnum][''] = "";
				$alldata[$uid][$studynum][$seriesnum]['desc'] = $desc;
				$alldata[$uid][$studynum][$seriesnum]['subjectid'] = $subjectRowID;
				$alldata[$uid][$studynum][$seriesnum]['studyid'] = $studyRowID;
			}
		}
		
		$sqlstring = "select a.*, b.study_num, d.uid, d.subject_id, e.*, f.*, g.* from mr_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id left join qc_moduleseries e on a.mrseries_id = e.series_id join qc_results f on e.qcmoduleseries_id = f.qcmoduleseries_id left join qc_resultnames g on f.qcresultname_id = g.qcresultname_id where c.project_id = $projectid and e.modality = 'mr' and g.qcresult_type = 'number'";
		//PrintSQL($sqlstring);
		$result = MySQLiQuery($sqlstring, __FILE__, __LINE__);
		if (mysqli_num_rows($result) > 0){
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				$seriesRowID = $row['mrseries_id'];
				$studyRowID = $row['study_id'];
				$subjectRowID = $row['subject_id'];
				$seriesnum = $row['series_num'];
				$studynum = $row['study_num'];
				$uid = $row['uid'];
				$resultname = $row['qcresult_name'];
				$resulttype = $row['qcresult_type'];
				if ($resulttype == "number")
					$value = $row['qcresults_valuenumber'];
				else
					$value = $row['qcresults_valuetext'];
				
				//if ($uid != $lastuid) { $rowhighlight = 1; }
				//else { $rowhighlight = 0; }
				
				$alldata[$uid][$studynum][$seriesnum][$resultname] = $value;
				
				//$rowdata[] = "{ subjectRowID: $subjectRowID, studyRowID: $studyRowID, rowhighlight: $rowhighlight, uid: \"$uid\", studynum: $studynum }";

				//$lastuid = $uid;
			}
			
			//PrintVariable($alldata);

			/* transform the data into 1 row for each series, and 1 column for each qc metric */
			foreach ($alldata as $uid => $studies) {
				foreach ($studies as $studynum => $series) {
					foreach ($series as $seriesnum => $metrics) {
						$seriesdesc = $alldata[$uid][$studynum][$seriesnum]['desc'];
						$subjectRowID = $alldata[$uid][$studynum][$seriesnum]['subjectid'];
						$studyRowID = $alldata[$uid][$studynum][$seriesnum]['studyid'];
						
						$rowstr = "{ subjectid: \"$subjectRowID\", studyid: \"$studyRowID\", uid: \"$uid\", studynum: \"$studynum\", seriesnum: \"$seriesnum\", seriesdesc: \"$seriesdesc\"";
						foreach ($resultNames as $metric) {
							$value = $alldata[$uid][$studynum][$seriesnum][$metric];
							
							if ((!is_null($value)) && ($value != ""))
								$vals[$metric][] = $value;

							if (is_numeric($value)) { $value = number_format($value, 3, '.', ''); } else { $value = "null"; }
							$metric = str_replace("-", "_", $metric);
							$rowstr .= ", $metric: $value";
							//$rowstr .= ", $metric: \"$value\"";
						}
						$rowstr .= " }";
						$rowdata[] = $rowstr;
					}
				}
			}
			
			$data = "";
			if (count($rowdata) > 0)
				$data = implode(",\n", $rowdata);
			
			//PrintVariable($vals);
			
			?>
			<!-- Include the JS for AG Grid -->
			<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.noStyle.js"></script>

			<br>
			<div class="ui text container">
				<span id="updateresult"></span>
			</div>
		  
			<div class="ui top attached yellow segment">
				<div class="ui grid">
					<div class="eight wide column">
						<h2 class="ui header">
							Advanced MR quality metrics
							<div class="sub header">Derived from <tt>mriqc</tt></div>
						</h2>
					</div>
					<div class="right aligned seven wide column">
						<div class="ui small basic primary compact button" onClick="onBtnExport()"><i class="file excel outline icon"></i> Export table as .csv</div> &nbsp;
					</div>
				</div>
			</div>
			<div id="myGrid" class="ag-theme-alpine" style="height: 60vh"></div>
			<style>
				.rowhighlight {
					border-top: 2px solid #888;
				}
			</style>
			<script type="text/javascript">
				const myTheme = agGrid.themeQuartz.withParams({
					borderColor: "#ccc",
					wrapperBorder: false,
					headerRowBorder: false,
					rowBorder: { style: "solid" },
					columnBorder: { style: "solid" },
				});
			
				let gridApi;

				// Function to demonstrate calling grid's API
				function deselect(){
					gridOptions.api.deselectAll()
				}
				
				function onBtnExport() {
					gridOptions.api.exportDataAsCsv( {allColumns: false} );
				}			

				// Grid Options are properties passed to the grid
				const gridOptions = {
					theme: myTheme,

					// each entry here represents one column
					columnDefs: [
						{ field: 'subjectid', hide: true },
						{ field: 'studyid', hide: true },
						{ field: 'rowhighlight', hide: true },
						{
							headerName: "UID",
							field: "uid",
							pinned: 'left',
							width: 150,
							cellRenderer: function(params) {
								return '<a href="subjects.php?id=' + params.data.subjectid + '">' + params.value + '</a>'
							}
						},
						{
							headerName: "Study",
							field: "studynum",
							editable: false,
							cellRenderer: function(params) {
								return '<a href="studies.php?id=' + params.data.studyid + '">' + params.value + '</a>'
							}
						},
						{ headerName: "Series", field: "seriesnum", editable: false },
						{ headerName: "SeriesDesc", field: "seriesdesc", editable: false },
						<?
							foreach ($resultNames as $metric) {
								$min = min($vals[$metric]);
								$max = max($vals[$metric]);
								echo "{
									headerName: \"$metric\",
									field: \"$metric\",
									editable: false,
									type: 'rightAligned',
									cellStyle: function(params) {
										/* weight is 0 when min (green); weight is 1 when max (red) */
										let weight = (params.value - $min)/($max - $min);

										/* start at Green, end at Yellow */
										let startRed = 102.0;
										let startGreen = 255.0;
										let startBlue = 102.0;
										
										let endRed = 255.0;
										let endGreen = 255.0;
										let endBlue = 102.0;
										
										let scaledWeight = 1.0 - ((weight - 0.0)/(0.5 - 0.0));
										
										/* start at Yellow, end at Red */
										if (weight >= 0.5) {
											startRed = 255.0;
											startGreen = 255.0;
											startBlue = 102.0;
											
											endRed = 255.0;
											endGreen = 102.0;
											endBlue = 102.0;
											
											scaledWeight = 1.0 - ((weight - 0.5)/(1.0 - 0.5));
										}

										let colorRed = (scaledWeight)*startRed + (1.0 - scaledWeight)*endRed;
										let colorGreen = (scaledWeight)*startGreen + (1.0 - scaledWeight)*endGreen;
										let colorBlue = (scaledWeight)*startBlue + (1.0 - scaledWeight)*endBlue;

										if ((params.value == '') || (params.value == null)) {
											return { backgroundColor: `rgb(255, 255, 255)` };
										}
										else {
											return { backgroundColor: `rgb(\${colorRed}, \${colorGreen}, \${colorBlue})` };
										}
									}
								},";
							}
						?>
					],

					rowData: [ <?=$data?> ],
					
					// default col def properties get applied to all columns
					defaultColDef: {sortable: true, filter: true, resizable: true, cellStyle: {fontSize: '12px'}},
					rowClassRules: {
						// row style expression
						'rowhighlight': 'data.rowhighlight == 1',
					},

					//rowSelection: { mode: 'multiRow' }, // allow rows to be selected
					//rowMultiSelectWithClick: true,
					animateRows: false, // have rows animate to new positions when sorted
					//onFirstDataRendered: onFirstDataRendered,
					//stopEditingWhenCellsLoseFocus: true,
					undoRedoCellEditing: true,
					suppressMovableColumns: true,
					autoSizeStrategy: { type: 'fitCellContents' }
				};

				$( document ).ready(function() {
					// get div to host the grid
					const eGridDiv = document.getElementById("myGrid");
					
					// new grid instance, passing in the hosting DIV and Grid Options
					//new agGrid.Grid(eGridDiv, gridOptions);
					gridApi = agGrid.createGrid(eGridDiv, gridOptions);
					
					autoSizeAll(false);
				});
				
				function autoSizeAll(skipHeader) {
					const allColumnIds = [];
					//gridOptions.columnApi.getColumns().forEach((column) => {
					gridApi.getColumns().forEach((column) => {
						allColumnIds.push(column.getId());
					});

					//gridOptions.columnApi.autoSizeColumns(allColumnIds, skipHeader);
					gridApi.autoSizeColumns(allColumnIds, skipHeader);
				}

				/* condense the spacing */
				document.documentElement.style.setProperty("--ag-spacing", `2.0px`);
				document.getElementById("spacing").innerText = "2.0";
				
			</script>
			<?
		}
		else {
			?>
			No MR series for this project
			<?
		}
	}	
	
?>

<? include("footer.php") ?>
