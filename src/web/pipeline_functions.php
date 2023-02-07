<?
 // ------------------------------------------------------------------------------
 // NiDB pipeline_functions.php
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

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");
	
	/* -------------------------------------------- */
	/* ------- EnablePipeline --------------------- */
	/* -------------------------------------------- */
	function EnablePipeline($id) {
		if (!ValidID($id,'Pipeline ID - H')) { return; }
		
		$sqlstring = "update pipelines set pipeline_enabled = 1 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisablePipeline -------------------- */
	/* -------------------------------------------- */
	function DisablePipeline($id) {
		if (!ValidID($id,'Pipeline ID - I')) { return; }
		
		$sqlstring = "update pipelines set pipeline_enabled = 0 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- EnablePipelineDebug ---------------- */
	/* -------------------------------------------- */
	function EnablePipelineDebug($id) {
		if (!ValidID($id,'Pipeline ID - H')) { return; }
		
		$sqlstring = "update pipelines set pipeline_debug = 1 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisablePipelineDebug --------------- */
	/* -------------------------------------------- */
	function DisablePipelineDebug($id) {
		if (!ValidID($id,'Pipeline ID - I')) { return; }
		
		$sqlstring = "update pipelines set pipeline_debug = 0 where pipeline_id = $id";
		$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
	}


	/* -------------------------------------------- */
	/* ------- DisplayPipelineStatus -------------- */
	/* -------------------------------------------- */
	function DisplayPipelineStatus($pipelinename, $pipelinedesc, $isenabled, $isdebug, $id, $returnpage, $pipeline_status, $pipeline_statusmessage, $pipeline_laststart, $pipeline_lastfinish, $pipeline_lastcheck) {
		if (!ValidID($id,'Pipeline ID - M')) { return; }

		?>
		<div class="ui container">
			<div class="ui top attached black segment">
				<div class="ui two column grid">
					<div class="column">
						<h1 class="ui header">
							<!--<i class="small grey settings icon"></i>-->
							<div class="content">
								<a href="pipelines.php?action=editpipeline&id=<?=$id?>"><span style="font-size: larger"><?=$pipelinename?><span></a>
								<div class="sub header"><?=$pipelinedesc?></div>
							</div>
						</h1>
						<?
							$sqlstring = "select sum(analysis_disksize) 'disksize' from analysis where pipeline_id = $id";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							$diskusage = $row['disksize'];
						?>
						Disk usage <?=HumanReadableFilesize($diskusage)?> <i class="question circle outline icon" title="May not be accurate if this pipeline depends on other pipelines and hard links are used. Check the parent pipeline for its usage."></i>
					</div>
					<div class="right aligned column">
						<? if ($isenabled) { ?>
							Enable <a href="<?=$returnpage?>.php?action=disable&returnpage=<?=$returnpage?>&id=<?=$id?>"><i class="big green toggle on icon" title="Pipeline enabled, click to disable"></i></a>
						<? } else { ?>
							Enable <a href="<?=$returnpage?>.php?action=enable&returnpage=<?=$returnpage?>&id=<?=$id?>"><i class="big grey flipped toggle on icon" title="Pipeline disabled, click to enable"></i></a>
						<? } ?>
						<br>
						<? if ($isdebug) { ?>
							Debug <a href="<?=$returnpage?>.php?action=disabledebug&returnpage=<?=$returnpage?>&id=<?=$id?>"><i class="big green toggle on icon" title="Pipeline in debug mode, click to return to normal mode"></i></a>
						<? } else { ?>
							Debug <a href="<?=$returnpage?>.php?action=enabledebug&returnpage=<?=$returnpage?>&id=<?=$id?>"><i class="big grey flipped toggle on icon" title="Pipeline in normal mode, click to enter debug mode"></i></a>
						<? } ?>
					</div>
				</div>
			</div>
			<div class="ui vertically fitted attached very short scrolling segment">
				<div class="ui accordion" style="font-size:smaller">
					<div class="title">
						<i class="dropdown icon"></i>
						Pipeline history
					</div>
					<div class="content">
						Displaying last 25 entries
						<table class="ui very compact small table">
							<thead>
								<th>Version</th>
								<th>AnalysisID</th>
								<th>Event</th>
								<th>Date</th>
								<th>Message</th>
							</thead>
						<?
							$sqlstring = "select * from pipeline_history where pipeline_id = $id order by run_num desc, event_datetime asc limit 25";
							$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
								$version = $row['pipeline_version'];
								$analysisid = $row['analysis_id'];
								$event = $row['pipeline_event'];
								$date = $row['event_datetime'];
								$msg = $row['event_message'];
								
								if ($event == "pipeline_started") {
									$newrun = true;
									$rowstyle = "font-weight: bold";
									$cellclass = "green";
								}
								else {
									$newrun = false;
									$rowstyle = "";
									$cellclass = "";
								}
								
								if (contains($event,"error")) {
									$rowstyle = "color: red";
								}
								?>
								<tr style="<?=$rowstyle?>">
									<td><?=$version?></td>
									<td><?=$analysisid?></td>
									<td class="<?=$cellclass?>"><?=$event?></td>
									<td><?=$date?></td>
									<td><?=$msg?></td>
								</tr>
								<?
							}
						?>
						</table>
					</div>
				</div>
			</div>
			<? if ($pipeline_status == "running") { ?>
			<div class="ui three bottom attached mini steps">
				<div class="step" style="padding: 5px">
					<div class="content">
						<div class="title">Start</div>
						<div class="description">Started <?=$pipeline_laststart?></div>
					</div>
				</div>
				<div class="active step" style="padding: 5px">
					<div class="content">
						<div class="title">Running</div>
						<div class="description">Checked in <?=$pipeline_lastcheck?></div>
						<?=$pipeline_statusmessage?>
						<a href="pipelines.php?action=reset&id=<?=$id?>" class="ui orange basic small button">reset</a>
					</div>
				</div>
				<div class="disabled step" style="padding: 5px">
					<div class="content">
						<div class="title">Finish</div>
						<div class="description"></div>
					</div>
				</div>
			</div>
			<? } else { ?>
			<div class="ui four bottom attached mini steps">
				<div class="active step" style="padding: 5px">
					<div class="content">
						<div class="title">Idle</div>
					</div>
				</div>
				<div class="disabled step" style="padding: 5px">
					<div class="content">
						<div class="title">Start</div>
						<span style="font-size: smaller">Last start <?=$pipeline_laststart?></span>
					</div>
				</div>
				<div class="disabled step" style="padding: 5px">
					<div class="content">
						<div class="title">Running</div>
						<span style="font-size: smaller">Last check-in <?=$pipeline_lastcheck?></span>
					</div>
				</div>
				<div class="disabled step" style="padding: 5px">
					<div class="content">
						<div class="title">Finish</div>
						<span style="font-size: smaller">Last finish <?=$pipeline_lastfinish?></span>
					</div>
				</div>
			</div>
			<? } ?>
		</div>
		<br>
		<?
	}
?>
