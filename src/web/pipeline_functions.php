<?
 // ------------------------------------------------------------------------------
 // NiDB pipeline_functions.php
 // Copyright (C) 2004 - 2021
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
			<div class="ui top attached segment">
				<div class="ui two column grid">
					<div class="column">
						<h1 class="ui header">
							<i class="small grey settings icon"></i>
							<div class="content">
								<?=$pipelinename?>
								<div class="sub header"><?=$pipelinedesc?></div>
							</div>
						</h1>
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
			<div class="ui attached segment">
				<table class="ui very compact table">
					<thead>
						<th>Version</th>
						<th>AnalysisID</th>
						<th>Event</th>
						<th>Date</th>
						<th>Message</th>
					</thead>
				<?
					$sqlstring = "select * from pipeline_history where pipeline_id = $id and event_datetime > date_add(now(), interval -1 hour) order by event_datetime desc";
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
			<? if ($pipeline_status == "running") { ?>
			<div class="ui three bottom attached steps">
				<div class="step" style="padding: 5px">
					<div class="content">
						<div class="title">Start</div>
						<div class="description">Started <?=$pipeline_laststart?></div>
					</div>
				</div>
				<div class="active step">
					<div class="content">
						<div class="title">Running</div>
						<div class="description">Checked in <?=$pipeline_lastcheck?></div>
						<?=$pipeline_statusmessage?>
						<a href="pipelines.php?action=reset&id=<?=$id?>" class="ui orange basic small button">reset</a>
					</div>
				</div>
				<div class="disabled step">
					<div class="content">
						<div class="title">Finish</div>
						<div class="description"></div>
					</div>
				</div>
			</div>
			<? } else { ?>
			<div class="ui four bottom attached steps">
				<div class="active step" style="padding: 10px 5px">
					<div class="content">
						<div class="title">Idle</div>
						<div class="description"></div>
					</div>
				</div>
				<div class="disabled step" style="padding: 10px 5px">
					<div class="content">
						<div class="title">Start</div>
						<div class="description">Last started <?=$pipeline_laststart?></div>
					</div>
				</div>
				<div class="disabled step" style="padding: 10px 5px">
					<div class="content">
						<div class="title">Running</div>
						<div class="description">Last checked in <?=$pipeline_lastcheck?></div>
						<!--<?=$pipeline_statusmessage?>-->
					</div>
				</div>
				<div class="disabled step" style="padding: 10px 5px">
					<div class="content">
						<div class="title">Finish</div>
						<div class="description">Last finished <?=$pipeline_lastfinish?></div>
					</div>
				</div>
			</div>
			<? } ?>
		</div>
		<?
	}
?>
