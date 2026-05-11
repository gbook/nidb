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
				height: 70vh;
				min-height: 480px;
				background: #000;
				color: #DDD;
				outline: none;
				touch-action: none;
				position: relative;
			}
			#dicomOverlay {
				position: absolute;
				inset: 0;
				pointer-events: none;
				padding: 8px 10px;
				font-family: monospace;
				font-size: 13px;
				color: #fff;
				text-shadow: 1px 1px 3px #000, -1px -1px 3px #000;
				z-index: 10;
				display: flex;
				flex-direction: column;
				justify-content: space-between;
			}
			#dicomOverlayTop, #dicomOverlayBottom {
				display: flex;
				justify-content: space-between;
			}
			#dicomOverlayRight {
				text-align: right;
			}
			#dicomStatus {
				font-family: monospace;
			}
		</style>
	<script type="module" src="scripts/cs3d.bundle.js"></script>
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
			
			<? if (!$valid) { ?>
				<div class="ui bottom attached negative message"><?=$message?></div>
			<? } elseif ($filecount < 1) { ?>
				<div class="ui bottom attached warning message">No DICOM files were found in this series directory.</div>
			<? } else { ?>
				<div class="ui fitted top attached segment">
					<div id="dicomViewport" tabindex="0">
						<div id="dicomOverlay">
							<div id="dicomOverlayTop">
								<div id="dicomOverlayLeft">
									<div id="overlayPatientName"></div>
									<div id="overlayPatientID"></div>
									<div id="overlayPatientAgeSex"></div>
									<div id="overlayStudyDescription"></div>
								</div>
								<div id="dicomOverlayRight">
									<div id="overlayProtocolName"></div>
									<div id="overlaySequenceName"></div>
									<div id="overlayRepetitionTime"></div>
									<div id="overlayEchoTime"></div>
									<div id="overlayFlipAngle"></div>
								</div>
							</div>
							<div id="dicomOverlayBottom">
								<div id="dicomOverlayBottomLeft">
									<div id="overlayPatientPosition"></div>
									<div id="overlayDimensions"></div>
									<div id="overlayPixelSpacing"></div>
									<div id="overlaySliceThickness"></div>
								</div>
								<div id="dicomOverlayBottomRight" style="text-align:right">
									<div id="overlayStationName"></div>
									<div id="overlayManufacturerModelName"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ui vertically fitted bottom attached segment">
					<div class="ui grid">
						<div class="twelve wide column">
							<input id="sliceSlider" type="range" min="1" max="<?=$filecount?>" value="1" style="width:100%">
						</div>
						<div class="four wide right aligned column">
							<span id="dicomStatus">Loading <?=$filecount?> images...</span>
						</div>
					</div>
					<div class="ui tiny form" style="margin-top: 6px">
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

					<script type="module">
						/* Cornerstone3D globals are set by cs3d.bundle.js (loaded as a module in <head>) */
						const cs             = window.cs3d;
						const csTools        = window.cs3dTools;
						const dicomImageLoader = window.cs3dDicomLoader;

						/* Cornerstone3D IDs used to retrieve the engine and viewport later */
						const renderingEngineId = "nidbDicomEngine";
						const viewportId = "nidbDicomViewport";

						/* DOM references */
						const element = document.getElementById("dicomViewport");
						const slider = document.getElementById("sliceSlider");
						const status = document.getElementById("dicomStatus");
						const windowWidthInput = document.getElementById("windowWidthInput");
						const windowCenterInput = document.getElementById("windowCenterInput");
						const resetWindowButton = document.getElementById("resetWindowButton");

						/* Overlay text elements — upper-left corner */
						const overlayPatientName      = document.getElementById("overlayPatientName");
						const overlayPatientID        = document.getElementById("overlayPatientID");
						const overlayPatientAgeSex    = document.getElementById("overlayPatientAgeSex");
						const overlayStudyDescription = document.getElementById("overlayStudyDescription");

						/* Overlay text elements — upper-right corner */
						const overlayProtocolName    = document.getElementById("overlayProtocolName");
						const overlaySequenceName   = document.getElementById("overlaySequenceName");
						const overlayRepetitionTime = document.getElementById("overlayRepetitionTime");
						const overlayEchoTime       = document.getElementById("overlayEchoTime");
						const overlayFlipAngle      = document.getElementById("overlayFlipAngle");

						/* Overlay text elements — lower-left corner */
						const overlayPatientPosition  = document.getElementById("overlayPatientPosition");
						const overlayDimensions       = document.getElementById("overlayDimensions");
						const overlayPixelSpacing     = document.getElementById("overlayPixelSpacing");
						const overlaySliceThickness   = document.getElementById("overlaySliceThickness");

						/* Overlay text elements — lower-right corner */
						const overlayStationName           = document.getElementById("overlayStationName");
						const overlayManufacturerModelName = document.getElementById("overlayManufacturerModelName");

						/* Viewer state */
						let imageIds = [];
						let renderingEngine = null;
						let defaultVoiRange = null;  /* captured from the first render; used by the Reset button */

						function setStatus(text) { status.textContent = text; }

						function updateWindowInputs(ww, wc) {
							windowWidthInput.value = Math.round(ww);
							windowCenterInput.value = Math.round(wc);
						}

						async function run() {
							try {
								/* Initialize Cornerstone3D libraries */
								await cs.init();
								await csTools.init();
								dicomImageLoader.init();

								/* Create a stack viewport and attach it to the DOM element */
								renderingEngine = new cs.RenderingEngine(renderingEngineId);
								renderingEngine.enableElement({
									viewportId,
									type: cs.Enums.ViewportType.STACK,
									element,
								});

								/* Register tools and bind them to the viewport:
								   - Left mouse drag → window / level
								   - Mouse wheel       → scroll through slices */
								const { WindowLevelTool, StackScrollTool } = csTools;
								csTools.addTool(WindowLevelTool);
								csTools.addTool(StackScrollTool);

								const toolGroup = csTools.ToolGroupManager.createToolGroup("nidbDicomTools");
								toolGroup.addTool(WindowLevelTool.toolName);
								toolGroup.addTool(StackScrollTool.toolName);
								toolGroup.addViewport(viewportId, renderingEngineId);
								toolGroup.setToolActive(WindowLevelTool.toolName, {
									bindings: [{ mouseButton: csTools.Enums.MouseBindings.Primary }]
								});
								toolGroup.setToolActive(StackScrollTool.toolName, {
									bindings: [{ mouseButton: csTools.Enums.MouseBindings.Wheel }]
								});

								/* Fetch the ordered list of wadouri image IDs for this series from the server */
								const response = await fetch("dicom.php?action=list&seriesid=<?=$seriesid?>&modality=<?=$modality?>");
								const data = await response.json();
								if (!response.ok || data.error) {
									throw new Error(data.error || "Could not load DICOM file list");
								}
								imageIds = data.imageIds;
								if (imageIds.length < 1) {
									throw new Error("No DICOM images were returned by the server");
								}

								/* Load the stack and render the first frame */
								const viewport = renderingEngine.getViewport(viewportId);
								await viewport.setStack(imageIds, 0);
								viewport.render();
								element.focus();

								/* Populate the overlay once from the first image's DICOM metadata.
								   Most tags come from Cornerstone3D's registered metadata modules,
								   but MR-specific tags (protocol, sequence, TR/TE, etc.) are not
								   registered, so they are read directly from the raw dicom-parser
								   dataset via the image cache. */
								element.addEventListener(cs.Enums.Events.IMAGE_RENDERED, () => {
									const patient      = cs.metaData.get("patientModule", imageIds[0]);
									const patientStudy = cs.metaData.get("patientStudyModule", imageIds[0]);

									/* DICOM stores name components separated by ^; replace with spaces */
									overlayPatientName.textContent  = (patient?.patientName ?? "").replace(/\^/g, " ").trim();
									overlayPatientID.textContent    = patient?.patientID ?? "";

									const age = patientStudy?.patientAge ?? "";
									const sex = patientStudy?.patientSex ?? "";
									overlayPatientAgeSex.textContent = [age && age + "Y", sex].filter(Boolean).join(" ");

									const generalStudy = cs.metaData.get("generalStudyModule", imageIds[0]);
									overlayStudyDescription.textContent = generalStudy?.studyDescription ?? "";

									const imagePlane = cs.metaData.get("imagePlaneModule", imageIds[0]);
									const ps = imagePlane?.pixelSpacing;
									overlayDimensions.textContent   = (imagePlane?.rows && imagePlane?.columns) ? imagePlane.rows + " x " + imagePlane.columns : "";
									overlayPixelSpacing.textContent = ps ? ps[0].toFixed(2) + "x" + ps[1].toFixed(2) + " mm" : "";
									const st = imagePlane?.sliceThickness;
									overlaySliceThickness.textContent = st != null ? "Thickness " + parseFloat(st).toFixed(2) + " mm" : "";

									/* Read MR tags directly from the raw dicom-parser dataset */
									const image = cs.cache.getImage(imageIds[0]);
									if (image?.data) {
										const ds = image.data;
										overlayProtocolName.textContent  = ds.string("x00181030") ?? "";  /* (0018,1030) ProtocolName */
										overlaySequenceName.textContent  = ds.string("x00180024") ?? "";  /* (0018,0024) SequenceName */
										const tr = ds.floatString("x00180080");                           /* (0018,0080) RepetitionTime */
										overlayRepetitionTime.textContent = tr != null ? "TR " + Math.round(tr) + " ms" : "";
										const te = ds.floatString("x00180081");                           /* (0018,0081) EchoTime */
										overlayEchoTime.textContent = te != null ? "TE " + te + " ms" : "";
										const fa = ds.floatString("x00181314");                           /* (0018,1314) FlipAngle */
										overlayFlipAngle.textContent = fa != null ? "FA " + fa + "°" : "";
										overlayPatientPosition.textContent       = ds.string("x00185100") ?? "";  /* (0018,5100) PatientPosition */
										overlayStationName.textContent           = ds.string("x00081010") ?? "";  /* (0008,1010) StationName */
										overlayManufacturerModelName.textContent = ds.string("x00081090") ?? "";  /* (0008,1090) ManufacturerModelName */
									}
								}, { once: true });

								/* After every render, sync the slice slider, status text, and window inputs */
								element.addEventListener(cs.Enums.Events.IMAGE_RENDERED, () => {
									const vp = renderingEngine.getViewport(viewportId);
									const index = vp.getCurrentImageIdIndex();
									slider.value = index + 1;
									setStatus((index + 1) + " / " + imageIds.length);
									const props = vp.getProperties();
									if (props.voiRange) {
										const ww = props.voiRange.upper - props.voiRange.lower;
										const wc = (props.voiRange.upper + props.voiRange.lower) / 2;
										updateWindowInputs(ww, wc);
										/* Capture the DICOM-native window on the first render */
										if (!defaultVoiRange) {
											defaultVoiRange = { ...props.voiRange };
										}
									}
								});

								/* Slider → jump to selected slice */
								slider.addEventListener("input", async () => {
									const vp = renderingEngine.getViewport(viewportId);
									await vp.setImageIdIndex(parseInt(slider.value, 10) - 1);
								});

								/* Manual window / level inputs → re-render with new VOI range */
								function applyManualWindow() {
									const ww = Math.max(1, Number(windowWidthInput.value));
									const wc = Number(windowCenterInput.value);
									const vp = renderingEngine.getViewport(viewportId);
									vp.setProperties({ voiRange: { lower: wc - ww / 2, upper: wc + ww / 2 } });
									vp.render();
								}
								windowWidthInput.addEventListener("change", applyManualWindow);
								windowCenterInput.addEventListener("change", applyManualWindow);

								/* Reset button → restore the original DICOM window */
								resetWindowButton.addEventListener("click", () => {
									if (!defaultVoiRange) return;
									const vp = renderingEngine.getViewport(viewportId);
									vp.setProperties({ voiRange: { ...defaultVoiRange } });
									vp.render();
								});

								/* Re-layout the canvas when the browser window is resized */
								window.addEventListener("resize", () => renderingEngine.resize(true));

							} catch (error) {
								console.error(error);
								setStatus(error.message);
							}
						}

						run();
					</script>
			<? } ?>
		<?
	}
	
?>


<? include("footer.php") ?>
