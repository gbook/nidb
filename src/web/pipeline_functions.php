<?
 // ------------------------------------------------------------------------------
 // NiDB pipeline_functions.php
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

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");
	
	/* -------------------------------------------- */
	/* ------- PrintAceSearchHighlight ------------ */
	/* -------------------------------------------- */
	/* style and behavior for the Ace editor's Ctrl+F search, shared by the script editors and the version view.
	   Print this once per page, before the script that creates the editors */
	function PrintAceSearchHighlight() {
		?>
		<style>
			/* Ace gives search matches the class ace_selected-word, but neither the base style nor the
			   xcode theme colors it, so matches are nearly invisible. The current match stays blue (ace_selection) */
			.ace_marker-layer .ace_selected-word {
				background-color: #ffe97f !important;
				border: 1px solid #e0a800 !important;
			}
		</style>
		<script>
			/* Ace clears the search matches on every selection change (Editor.onSelectionChange calls
			   session.highlight(false)), so clicking in the text loses them while the search box is still
			   open. Re-apply the search regexp after the selection changes, whenever the box is open */
			function keepAceSearchHighlight(editor) {
				editor.on('changeSelection', function() {
					var searchbox = editor.searchBox;
					if (searchbox && searchbox.active && searchbox.searchInput && searchbox.searchInput.value) {
						editor.session.highlight(editor.$search.$options.re);
					}
				});
			}
		</script>
		<?
	}


	/* -------------------------------------------- */
	/* ------- PipelineFavoriteStar --------------- */
	/* -------------------------------------------- */
	/* print a star that toggles the pipeline in the current user's favorites. The click handler is
	   printed once, with the first star on the page, and updates every star for that pipeline */
	function PipelineFavoriteStar($id, $isfavorite) {
		static $scriptprinted = false;

		if ($isfavorite) {
			$class = "yellow star";
			$title = "Click to remove this pipeline from your favorites";
		}
		else {
			$class = "grey star outline";
			$title = "Click to add this pipeline to your favorites";
		}
		?><i class="<?=$class?> link icon pipelinefavorite" data-pipelineid="<?=(int)$id?>" data-favorite="<?=($isfavorite ? 1 : 0)?>" title="<?=$title?>"></i><?

		if ($scriptprinted) { return; }
		$scriptprinted = true;
		?>
		<script>
			$(document).on('click', '.pipelinefavorite', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var star = $(this);
				var favorite = (star.attr('data-favorite') == '1') ? 0 : 1;
				$.post("ajaxapi.php", { action: "setpipelinefavorite", pipelineid: star.attr('data-pipelineid'), favorite: favorite }, null, "json")
				.done(function(r) {
					$('.pipelinefavorite[data-pipelineid="' + r.pipelineid + '"]').each(function() {
						$(this).attr('data-favorite', r.favorite).removeClass('yellow grey outline');
						if (r.favorite == 1) {
							$(this).addClass('yellow').attr('title', 'Click to remove this pipeline from your favorites');
						}
						else {
							$(this).addClass('grey outline').attr('title', 'Click to add this pipeline to your favorites');
						}
					});
				})
				.fail(function(xhr) {
					alert("Unable to update favorite: " + ((xhr.responseJSON && xhr.responseJSON.error) || xhr.statusText));
				});
			});
		</script>
		<?
	}


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
				<div class="ui three column grid">
					<div class="column">
						<h1 class="ui header">
							<!--<i class="small grey settings icon"></i>-->
							<div class="content">
								<? PipelineFavoriteStar($id, IsUserFavorite('pipeline', $id)); ?>
								<a href="pipelines.php?action=editpipeline&id=<?=$id?>"><span style="font-size: larger"><?=$pipelinename?><span></a>
								<div class="sub header"><?=$pipelinedesc?></div>
							</div>
						</h1>
					</div>
					<!--<div class="center aligned column">
						<?
							//$sqlstring = "select sum(analysis_disksize) 'disksize' from analysis where pipeline_id = $id";
							//$result = MySQLiQuery($sqlstring,__FILE__,__LINE__);
							//$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
							//$diskusage = $row['disksize'];
						?>
						Disk usage <?=HumanReadableFilesize($diskusage)?> <i class="question circle outline icon" title="Disk usage may not be accurate if this pipeline depends on other pipelines and hard links are used.
						<p>Check the parent pipeline for its usage</p>"></i>
					</div> -->
					<div class="right aligned column">
						<? if ($isenabled) { ?>
							Enable <a href="<?=$returnpage?>.php?action=disable&returnpage=<?=$returnpage?>&id=<?=$id?>"><i class="big green toggle on icon" title="Pipeline enabled, click to disable"></i></a>
						<? } else { ?>
							<i class="question circle icon" title="Why is my pipeline disabled? Pipelines are automatically disabled if they have not successfully run a study in the last 60 days."></i>
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
