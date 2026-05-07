<?
 // ------------------------------------------------------------------------------
 // NiDB dicom.php
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
	require "includes_php.php";

	/* ----- setup variables ----- */
	$action = GetVariable("action");
	$seriesid = GetVariable("seriesid");
	$modality = GetVariable("modality");
	$instance = GetVariable("instance");

	if ($action == "list") {
		OutputDicomSeriesList($seriesid, $modality);
		exit;
	}
	if ($action == "file") {
		OutputDicomFile($seriesid, $modality, $instance);
		exit;
	}
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - DICOM Viewer</title>
		<style>
			#dicomViewport {
				width: 100%;
				height: 72vh;
				min-height: 480px;
				background: #000;
				color: #DDD;
				outline: none;
				touch-action: none;
			}
				#dicomStatus {
					font-family: monospace;
				}
				#dicomViewport.windowing {
					cursor: crosshair;
				}
			</style>
		<script type="text/javascript" src="https://unpkg.com/cornerstone-core@2.6.1/dist/cornerstone.min.js"></script>
		<script type="text/javascript" src="https://unpkg.com/dicom-parser@1.8.21/dist/dicomParser.min.js"></script>
		<script type="text/javascript" src="https://unpkg.com/cornerstone-wado-image-loader@4.13.2/dist/cornerstoneWADOImageLoaderNoWebWorkers.bundle.min.js"></script>
	</head>

<body>
	<div id="wrapper">
<?
	require "includes_html.php";
	require "menu.php";
	
	/* determine action */
	switch($action) {
		default:
			DisplayDicomViewer($seriesid, $modality);
			break;
	}
	
	
	/* ------------------------------------ functions ------------------------------------ */

	
	/* -------------------------------------------- */
	/* ------- ValidateDicomRequest --------------- */
	/* -------------------------------------------- */
	function ValidateDicomRequest($seriesid, $modality) {
		$seriesid = (int)$seriesid;
		$modality = strtolower(trim($modality));
		
		if ($seriesid < 1) {
			return array(false, "Invalid series ID", "", 0, "");
		}
		if (!preg_match('/^[a-z0-9]+$/', $modality)) {
			return array(false, "Invalid modality", "", 0, "");
		}
		
		list($path, $seriespath, $qapath, $uid, $studynum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, $modality);
		$realpath = realpath($path);
		if (($realpath === false) || !is_dir($realpath)) {
			return array(false, "DICOM path does not exist for this series", "", $seriesid, $modality);
		}
		
		$archivepath = realpath($GLOBALS['cfg']['archivedir']);
		if (($archivepath !== false) && (substr($realpath, 0, strlen($archivepath)) !== $archivepath)) {
			return array(false, "DICOM path is outside the archive directory", "", $seriesid, $modality);
		}
		
		return array(true, "", $realpath, $seriesid, $modality);
	}

	
	/* -------------------------------------------- */
	/* ------- GetDicomFiles ---------------------- */
	/* -------------------------------------------- */
	function GetDicomFiles($path) {
		$files = array();
		foreach (scandir($path) as $file) {
			if (($file == ".") || ($file == "..") || (substr($file, 0, 1) == ".")) {
				continue;
			}
			
			$fullpath = $path . "/" . $file;
			if (!is_file($fullpath)) {
				continue;
			}
			
			$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			if (($extension == "") || in_array($extension, array("dcm", "dicom", "ima"))) {
				$files[] = $file;
			}
		}
		
		natsort($files);
		return array_values($files);
	}

	
	/* -------------------------------------------- */
	/* ------- OutputJson ------------------------- */
	/* -------------------------------------------- */
	function OutputJson($data, $statuscode = 200) {
		http_response_code($statuscode);
		header("Content-Type: application/json");
		echo json_encode($data);
	}

	
	/* -------------------------------------------- */
	/* ------- OutputDicomSeriesList -------------- */
	/* -------------------------------------------- */
	function OutputDicomSeriesList($seriesid, $modality) {
		list($valid, $message, $path, $seriesid, $modality) = ValidateDicomRequest($seriesid, $modality);
		if (!$valid) {
			OutputJson(array("error" => $message), 400);
			return;
		}
		
		$files = GetDicomFiles($path);
		$imageids = array();
		foreach ($files as $index => $file) {
			$imageids[] = "wadouri:" . "dicom.php?action=file&seriesid=$seriesid&modality=$modality&instance=$index";
		}
		
		OutputJson(array(
			"seriesid" => $seriesid,
			"modality" => $modality,
			"numfiles" => count($files),
			"imageIds" => $imageids
		));
	}

	
	/* -------------------------------------------- */
	/* ------- OutputDicomFile -------------------- */
	/* -------------------------------------------- */
	function OutputDicomFile($seriesid, $modality, $instance) {
		list($valid, $message, $path, $seriesid, $modality) = ValidateDicomRequest($seriesid, $modality);
		if (!$valid) {
			http_response_code(400);
			echo $message;
			return;
		}
		
		$files = GetDicomFiles($path);
		$instance = (int)$instance;
		if (($instance < 0) || ($instance >= count($files))) {
			http_response_code(404);
			echo "DICOM instance not found";
			return;
		}
		
		$file = $path . "/" . $files[$instance];
		header("Content-Type: application/dicom");
		header("Content-Length: " . filesize($file));
		header("Content-Disposition: inline; filename=\"" . basename($file) . "\"");
		readfile($file);
	}

	
	/* -------------------------------------------- */
	/* ------- DisplayDicomViewer ----------------- */
	/* -------------------------------------------- */
	function DisplayDicomViewer($seriesid, $modality) {
		list($valid, $message, $path, $seriesid, $modality) = ValidateDicomRequest($seriesid, $modality);
		$filecount = 0;
		if ($valid) {
			$filecount = count(GetDicomFiles($path));
		}
		?>
		<div class="ui container">
			<div class="ui top attached secondary inverted segment">
				<h2 class="ui header">
					DICOM Viewer
					<div class="sub header">Series <?=$seriesid?>, <?=strtoupper($modality)?></div>
				</h2>
			</div>
			
			<? if (!$valid) { ?>
				<div class="ui bottom attached negative message"><?=$message?></div>
			<? } elseif ($filecount < 1) { ?>
				<div class="ui bottom attached warning message">No DICOM files were found in this series directory.</div>
			<? } else { ?>
				<div class="ui attached segment">
					<div id="dicomViewport" tabindex="0"></div>
				</div>
					<div class="ui bottom attached segment">
						<div class="ui grid">
							<div class="twelve wide column">
								<input id="sliceSlider" type="range" min="1" max="<?=$filecount?>" value="1" style="width:100%">
							</div>
							<div class="four wide right aligned column">
								<span id="dicomStatus">Loading <?=$filecount?> images...</span>
							</div>
						</div>
						<div class="ui tiny form" style="margin-top: 12px">
							<div class="four fields">
								<div class="field">
									<label>Window width</label>
									<input id="windowWidthInput" type="number" step="1">
								</div>
								<div class="field">
									<label>Window center</label>
									<input id="windowCenterInput" type="number" step="1">
								</div>
								<div class="field">
									<label>&nbsp;</label>
									<button id="resetWindowButton" class="ui fluid basic button" type="button"><i class="undo icon"></i> Reset</button>
								</div>
								<div class="field">
									<label>&nbsp;</label>
									<div class="ui basic label">Drag image to adjust</div>
								</div>
							</div>
						</div>
					</div>

					<script type="text/javascript">
						const element = document.getElementById("dicomViewport");
						const slider = document.getElementById("sliceSlider");
						const status = document.getElementById("dicomStatus");
						const windowWidthInput = document.getElementById("windowWidthInput");
						const windowCenterInput = document.getElementById("windowCenterInput");
						const resetWindowButton = document.getElementById("resetWindowButton");
						let imageIds = [];
						let currentIndex = 0;
						let defaultVoi = null;
						let customVoi = null;
						let isWindowing = false;
						let windowStart = null;
						let voiStart = null;

						function setStatus(text) {
							status.textContent = text;
						}
						
						function updateWindowInputs(voi) {
							windowWidthInput.value = Math.round(voi.windowWidth);
							windowCenterInput.value = Math.round(voi.windowCenter);
						}
						
						function applyWindow(windowWidth, windowCenter) {
							const viewport = cornerstone.getViewport(element);
							viewport.voi.windowWidth = Math.max(1, Number(windowWidth));
							viewport.voi.windowCenter = Number(windowCenter);
							customVoi = {
								windowWidth: viewport.voi.windowWidth,
								windowCenter: viewport.voi.windowCenter
							};
							cornerstone.setViewport(element, viewport);
							updateWindowInputs(customVoi);
						}

						function showSlice(index) {
							if (imageIds.length < 1) {
								return;
						}
						currentIndex = Math.max(0, Math.min(index, imageIds.length - 1));
						slider.value = currentIndex + 1;
						setStatus("Loading " + (currentIndex + 1) + " / " + imageIds.length + "...");
							
							cornerstone.loadAndCacheImage(imageIds[currentIndex]).then((image) => {
								cornerstone.displayImage(element, image);
								const viewport = cornerstone.getViewport(element);
								if (!defaultVoi) {
									defaultVoi = {
										windowWidth: viewport.voi.windowWidth,
										windowCenter: viewport.voi.windowCenter
									};
								}
								if (customVoi) {
									viewport.voi.windowWidth = customVoi.windowWidth;
									viewport.voi.windowCenter = customVoi.windowCenter;
									cornerstone.setViewport(element, viewport);
								}
								updateWindowInputs(customVoi || viewport.voi);
								setStatus((currentIndex + 1) + " / " + imageIds.length);
							}).catch((error) => {
								console.error(error);
							setStatus(error.message);
						});
					}

					async function run() {
						try {
							if (!window.cornerstone || !window.cornerstoneWADOImageLoader) {
								throw new Error("Cornerstone libraries did not load");
							}
							
							cornerstoneWADOImageLoader.external.cornerstone = cornerstone;
							cornerstoneWADOImageLoader.external.dicomParser = dicomParser;
							if (typeof cornerstoneWADOImageLoader.configure == "function") {
								cornerstoneWADOImageLoader.configure({
									useWebWorkers: false
								});
							}
							cornerstone.enable(element);

							const response = await fetch("dicom.php?action=list&seriesid=<?=$seriesid?>&modality=<?=$modality?>");
							const data = await response.json();
							if (!response.ok || data.error) {
								throw new Error(data.error || "Could not load DICOM file list");
							}
							imageIds = data.imageIds;
							if (imageIds.length < 1) {
								throw new Error("No DICOM images were returned by the server");
							}

							element.focus();
							showSlice(0);
						}
						catch (error) {
							console.error(error);
							setStatus(error.message);
						}
					}

						slider.addEventListener("input", () => {
							showSlice(parseInt(slider.value, 10) - 1);
						});
						
						windowWidthInput.addEventListener("change", () => {
							applyWindow(windowWidthInput.value, windowCenterInput.value);
						});
						
						windowCenterInput.addEventListener("change", () => {
							applyWindow(windowWidthInput.value, windowCenterInput.value);
						});
						
						resetWindowButton.addEventListener("click", () => {
							if (defaultVoi) {
								const viewport = cornerstone.getViewport(element);
								viewport.voi.windowWidth = defaultVoi.windowWidth;
								viewport.voi.windowCenter = defaultVoi.windowCenter;
								customVoi = null;
								cornerstone.setViewport(element, viewport);
								updateWindowInputs(defaultVoi);
							}
						});

						element.addEventListener("wheel", (event) => {
							event.preventDefault();
							showSlice(currentIndex + (event.deltaY > 0 ? 1 : -1));
						}, { passive: false });
						
						element.addEventListener("mousedown", (event) => {
							if (event.button != 0) {
								return;
							}
							const viewport = cornerstone.getViewport(element);
							isWindowing = true;
							windowStart = { x: event.clientX, y: event.clientY };
							voiStart = {
								windowWidth: viewport.voi.windowWidth,
								windowCenter: viewport.voi.windowCenter
							};
							element.classList.add("windowing");
							event.preventDefault();
						});
						
						window.addEventListener("mousemove", (event) => {
							if (!isWindowing) {
								return;
							}
							const width = voiStart.windowWidth + ((event.clientX - windowStart.x) * 4);
							const center = voiStart.windowCenter - ((event.clientY - windowStart.y) * 4);
							applyWindow(width, center);
						});
						
						window.addEventListener("mouseup", () => {
							isWindowing = false;
							element.classList.remove("windowing");
						});

						window.addEventListener("resize", () => {
							cornerstone.resize(element, true);
					});

					run();
				</script>
			<? } ?>
		</div>
		<?
	}
	
?>


<? include("footer.php") ?>
