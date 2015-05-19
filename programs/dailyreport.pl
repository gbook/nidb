#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB dailyreport.pl
# Copyright (C) 2004 - 2015
# Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
# Olin Neuropsychiatry Research Center, Hartford Hospital
# ------------------------------------------------------------------------------
# GPLv3 License:
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
# ------------------------------------------------------------------------------

# -----------------------------------------------------------------------------
# This program automatically downloads data that fits pipeline download criteria
# 
# [6/14/2011] - Greg Book
#		* Wrote initial program.
# -----------------------------------------------------------------------------

use strict;
use warnings;
use Mysql;
use Image::ExifTool;
use Net::SMTP::TLS;
use Data::Dumper;
use File::Path;
use File::Copy;
use Switch;
use Sort::Naturally;
use POSIX;
use Math::Round;
require 'nidbroutines.pl';

#my %config = do 'config.pl';
our %cfg;
LoadConfig();

# database variables
our $db;

# script specific information
our $scriptname = "dailyreport";
our $lockfileprefix = "dailyreport";	# lock files will be numbered lock.1, lock.2 ...
our $lockfile = "";					# lockfile name created for this instance of the program
our $log;							# logfile handle created for this instance of the program
our $numinstances = 1;				# number of times this program can be run concurrently

# debugging
our $debug = 0;


# ------------- end variable declaration --------------------------------------
# -----------------------------------------------------------------------------


# check if this program can run or not
if (CheckNumLockFiles($lockfileprefix, $cfg{'lockdir'}) >= $numinstances) {
	print "Can't run, too many of me already running\n";
	exit(0);
}
else {
	my $logfilename;
	($lockfile, $logfilename) = CreateLockFile($lockfileprefix, $cfg{'lockdir'}, $numinstances);
	#my $logfilename = "$lockfile";
	$logfilename = "$cfg{'logdir'}/$scriptname" . CreateLogDate() . ".log";
	open $log, '> ', $logfilename;
	my $x = DoReport();
	close $log;
	if (!$x) { unlink $logfilename; } # delete the logfile if nothing was actually done
	print "Done. Deleting $lockfile\n";
	unlink $lockfile;
}

exit(0);


# ----------------------------------------------------------
# --------- DoReport ---------------------------------------
# ----------------------------------------------------------
sub DoReport {
	my $time = CreateCurrentDate();
	WriteLog("$scriptname Running... Current Time is $time");

	my %dicomfiles;
	my $ret = 0;
	
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	WriteLog("Connected to database");
	
	# check if this module should be running now or not
	my $sqlstring = "select * from modules where module_name = '$scriptname' and module_isactive = 1";
	my $result = $db->query($sqlstring);
	if ($result->numrows < 1) {
		return 0;
	}
	# update the start time
	$sqlstring = "update modules set module_laststart = now(), module_status = 'running' where module_name = '$scriptname'";
	$result = $db->query($sqlstring);
	
	
	# get series updated in the last 24 hours
	$sqlstring = "select * from `enrollment` join `projects` on `enrollment`.project_id = `projects`.project_id join `subjects` on `subjects`.subject_id = `enrollment`.subject_id join studies on studies.enrollment_id = enrollment.enrollment_id join `mr_series` on `mr_series`.study_id = `studies`.study_id left join `mr_qa` on `mr_qa`.mrseries_id = `mr_series`.mrseries_id where `subjects`.isactive = 1 and `projects`.project_sharing in ('F','V') and `studies`.study_modality = 'mr' and `mr_series`.series_datetime > date_add( now( ) , interval -1 day )  and `mr_series`.series_datetime < now() order by `studies`.study_datetime, `mr_series`.series_num";	
	$result = $db->query($sqlstring) || SQLError($sqlstring, $db->errmsg());
	my $numseries = $result->numrows;

	# get the start/end time of the report from the database (which may have a different time than Perl)
	my $sqlstringA = "SELECT now( ) 'endtime', date_add( now( ) , INTERVAL -1 DAY ) 'starttime'";
	my $resultA = $db->query($sqlstringA) || SQLError($sqlstringA, $db->errmsg());
	my %rowA = $resultA->fetchhash;
	my $starttime = $rowA{'starttime'};
	my $endtime = $rowA{'endtime'};
	
	my $email = "";
	my $str = "";
	
	my $uptime = `uptime`;
	my $df = `df -h`;
	
	my @colors = GenerateColorGradient();
	my %pstats = GetGlobalQAStats();
	
	# start creating the email body
	$email = qq^
	<body style='font-family: arial, helvetica, sans-serif'>
	<div align='center' width='100%' style="background-color: lightyellow; border: solid 1pt orange">
		<b><span style='font-size: 14pt;'>ADO2 MRI daily report</span></b>
	</div>
	<br>
	<div align="center">$starttime <i>to</i> $endtime</div><br><br>
	The following studies were performed in the past 24 hours:
	<ul>
	<li>$numseries series
	</ul>
	<br>
	<table border="1">
		<tr>
			<td align="right"><b>System uptime</b> </td>
			<td>$uptime</td>
		</tr>
		<tr>
			<td align="right"><b>Disk space usage</b> </td>
			<td><pre>$df</pre></td>
		</tr>
	</table>
	<br>

	<table cellspacing="0">
		<tr>
			<td><b>Key:</b> &nbsp;</td>
			<td>good&nbsp;</td>
			^;
			for (my $i=0; $i<50; $i++) {
				my $percent = sprintf("%.0f",(($i/50)*100));
				my $color = $colors[$percent];
				
				$email .= "<td bgcolor='$color' style='font-size: 8pt'>&nbsp;</td>\n";
			}
			
	$email .= qq^
			<td>&nbsp;bad</td>
		</tr>
	</table>
	<br>
	
	<table width="100%" cellspacing="0" cellpadding="1">
	^;
	#<tr><td colspan=6 align="center"><b>Series</b></td></tr>
	#<tr style='color: white'>
	#	<td bgcolor='darkblue'>UID</td>
	#	<td bgcolor='darkblue'>Study #</td>
	#	<td bgcolor='darkblue'>Gender</td>
	#	<td bgcolor='darkblue'>Project</td>
	#	<td bgcolor='darkblue'>Study date</td>
	#	<td bgcolor='darkblue'>Operator</td>
	#	<td bgcolor='darkblue'>Site</td>
	#	<td bgcolor='darkblue'>Age</td>
	#</tr>
	#^;

	my $study_id = "0";
	my $laststudy_id = "0";
	while (my %row = $result->fetchhash) {
		$study_id = $row{'study_id'};
		my $uid = $row{'uid'};
		my $study_num = $row{'study_num'};
		my $birthdate = $row{'birthdate'};
		my $gender = $row{'gender'};
		my $project_name = $row{'project_name'};
		my $project_costcenter = $row{'project_costcenter'};
		my $study_datetime = $row{'study_datetime'};
		my $study_operator = $row{'study_operator'};
		my $study_performingphysician = $row{'study_performingphysician'};
		my $study_site = $row{'study_site'};
		
		# calculate age at scan
		my ($year, $month, $day) = split(/-/, $birthdate);
		my $d1 = mktime(0,0,0,$month,$day,$year);
		my ($year2, $month2, $day2, $extra2) = split(/-/, $study_datetime);
		print "[$year2] [$month2] [$day2] [$extra2]\n";
		my $d2 = mktime(0,0,0,$month2,$day2,$year2);
		my $ageatscan = floor(($d2-$d1)/31536000);
		
		# series specific variables
		my $series_datetime = $row{'series_datetime'};
		my $series_desc = $row{'series_desc'};
		my $sequence = $row{'series_sequencename'};
		my $series_num = $row{'series_num'};
		my $series_tr = $row{'series_tr'};
		my $series_spacingx = $row{'series_spacingx'};
		my $series_spacingy = $row{'series_spacingy'};
		my $series_spacingz = $row{'series_spacingz'};
		my $img_rows = $row{'img_rows'};
		my $img_cols = $row{'img_cols'};
		my $img_slices = $row{'img_slices'};
		my $bold_reps = $row{'bold_reps'};
		my $numfiles = $row{'numfiles'};
		my $series_size = $row{'series_size'};
		my $numfiles_beh = $row{'numfiles_beh'};
		my $beh_size = $row{'beh_size'};
		my $move_minx = $row{'move_minx'};
		my $move_miny = $row{'move_miny'};
		my $move_minz = $row{'move_minz'};
		my $move_maxx = $row{'move_maxx'};
		my $move_maxy = $row{'move_maxy'};
		my $move_maxz = $row{'move_maxz'};
		my $rot_maxp = $row{'rot_maxp'};
		my $rot_maxr = $row{'rot_maxr'};
		my $rot_maxy = $row{'rot_maxy'};
		my $rot_minp = $row{'rot_minp'};
		my $rot_minr = $row{'rot_minr'};
		my $rot_miny = $row{'rot_miny'};
		my $iosnr = $row{'io_snr'};
		my $pvsnr = $row{'pv_snr'};
		
		#$series_datetime = date("g:ia",strtotime($series_datetime));
		#$series_size = HumanReadableFilesize($series_size);
		#$beh_size = HumanReadableFilesize($beh_size);
		my $behcolor = "white";
		
		if (($sequence eq "epfid2d1_64") && ($numfiles_beh < 1)) {
			$behcolor = "red";
		}
		
		# format the colors for realignment and SNR
		my $rangex = abs($move_minx) + abs($move_maxx);
		my $rangey = abs($move_miny) + abs($move_maxy);
		my $rangez = abs($move_minz) + abs($move_maxz);
		
		my ($xindex, $yindex, $zindex);
		if ($pstats{$sequence}{'rangex'} > 0) {
			$xindex = round(($rangex/$pstats{$sequence}{'rangex'})*100); if ($xindex > 100) { $xindex = 100; }
		}
		if ($pstats{$sequence}{'rangey'} > 0) {
			$yindex = round(($rangey/$pstats{$sequence}{'rangey'})*100); if ($yindex > 100) { $yindex = 100; }
		}
		if ($pstats{$sequence}{'rangez'} > 0) {
			$zindex = round(($rangez/$pstats{$sequence}{'rangez'})*100); if ($zindex > 100) { $zindex = 100; }
		}

		my ($stdsiosnr, $stdspvsnr);
		# get standard deviations from the mean for SNR
		if ($pstats{$sequence}{'stdiosnr'} != 0) {
			if ($iosnr > $pstats{$sequence}{'avgiosnr'}) {
				$stdsiosnr = 0;
			}
			else {
				$stdsiosnr = (($iosnr - $pstats{$sequence}{'avgiosnr'})/$pstats{$sequence}{'stdiosnr'});
			}
		}
		if ($pstats{$sequence}{'stdpvsnr'} != 0) {
			if ($pvsnr > $pstats{$sequence}{'avgpvsnr'}) {
				$stdspvsnr = 0;
			}
			else {
				$stdspvsnr = (($pvsnr - $pstats{$sequence}{'avgpvsnr'})/$pstats{$sequence}{'stdpvsnr'});
			}
		}
		
		my ($pvindex, $ioindex);
		if ($pstats{$sequence}{'maxstdpvsnr'} == 0) { $pvindex = 100; }
		else { $pvindex = round(($stdspvsnr/$pstats{$sequence}{'maxstdpvsnr'})*100); }
		$pvindex = 100 + $pvindex;
		if ($pvindex > 100) { $pvindex = 100; }
		
		if ($pstats{$sequence}{'maxstdiosnr'} == 0) { $ioindex = 100; }
		else { $ioindex = round(($stdsiosnr/$pstats{$sequence}{'maxstdiosnr'})*100); }
		$ioindex = 100 + $ioindex;
		if ($ioindex > 100) { $ioindex = 100; }
		
		my $maxpvsnrcolor = $colors[100-$pvindex];
		my $maxiosnrcolor = $colors[100-$ioindex];
		if ($pvsnr <= 0.0001) { $pvsnr = "-"; $maxpvsnrcolor = "#FFFFFF"; }
		else { $pvsnr = sprintf("%.2f", $pvsnr); }
		if ($iosnr <= 0.0001) { $iosnr = "-"; $maxiosnrcolor = "#FFFFFF"; }
		else { $iosnr = sprintf("%.2f", $iosnr); }
		
		# setup movement colors
		my $maxxcolor = "#" . $colors[$xindex];
		my $maxycolor = "#" . $colors[$yindex];
		my $maxzcolor = "#" . $colors[$zindex];
		if ($rangex <= 0.0001) { $rangex = "-"; $maxxcolor = "#FFFFFF"; }
		else { $rangex = sprintf("%.2f", $rangex); }
		if ($rangey <= 0.0001) { $rangey = "-"; $maxycolor = "#FFFFFF"; }
		else { $rangey = sprintf("%.2f", $rangey); }
		if ($rangez <= 0.0001) { $rangez = "-"; $maxzcolor = "#FFFFFF"; }
		else { $rangez = sprintf("%.2f", $rangez); }
		
		# display study header ...
		if ($study_id ne $laststudy_id) {
			$email .= qq^
				<tr>
					<td colspan='14'>
						<br>
						<table width='100%' cellspacing="1" cellpadding="3">
							<tr style="font-weight: bold; background-color: darkblue; color: white">
								<td align="center">$uid</td>
								<td align="center">$study_num</td>
								<td align="center">$gender</td>
								<td align="center">$project_name ($project_costcenter)</td>
								<td align="center">$study_datetime</td>
								<td align="center">$study_operator</td>
								<td align="center">$study_site</td>
								<td align="center">$ageatscan Y</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr style="font-weight: bold">
					<td>Series #</td>
					<td>Protocol</td>
					<td>Time</td>
					<td>X</td>
					<td>Y</td>
					<td>Z</td>
					<td title="Per Voxel SNR (timeseries) - Calculated from the fslstats command">PV SNR</td>
					<td title="Inside-Outside SNR - This calculates the brain signal (center of brain-extracted volume) compared to the average of the volume corners">IO SNR</td>
					<td>Size <span class="tiny">(x y)</span></td>
					<td># files</td>
					<td>Size</td>
					<td>Sequence</td>
					<td>TR</td>
					<td># beh <span class="tiny">(size)</span></td>
				</tr>
			^;
		}
		$email .= qq^
			<tr style="font-size: 10pt;">
				<td style="border-bottom: 1px solid #999999;">$series_num</td>
				<td style="border-bottom: 1px solid #999999;">$series_desc</td>
				<td style="border-bottom: 1px solid #999999;">$series_datetime</td>
				<td style="background-color: $maxxcolor; border-bottom: 1px solid #999999; padding: 1px 5px;">$rangex</td>
				<td style="background-color: $maxycolor; border-bottom: 1px solid #999999; padding: 1px 5px;">$rangey</td>
				<td style="background-color: $maxzcolor; border-bottom: 1px solid #999999; padding: 1px 5px;">$rangez</td>
				<td style="background-color: $maxpvsnrcolor; border-bottom: 1px solid #999999; padding: 1px 5px;">$pvsnr</td>
				<td style="background-color: $maxiosnrcolor; border-bottom: 1px solid #999999; padding: 1px 5px;">$iosnr</td>
				<td style="border-bottom: 1px solid #999999;">$img_cols &times; $img_rows</td>
				<td style="border-bottom: 1px solid #999999;">$numfiles</td>
				<td style="border-bottom: 1px solid #999999;">$series_size</td>
				<td style="border-bottom: 1px solid #999999;">$sequence</td>
				<td style="border-bottom: 1px solid #999999;">$series_tr</td>
				<td style="border-bottom: 1px solid #999999; background-color: $behcolor; ">$numfiles_beh ($beh_size)</td>
			</tr>
		^;
		
		#$str = sprintf("%-24s%-25s%-5s%-7s%-10s%-15s\n", $studyscannerid, $seriesdesc, $seriesnumber, $numfiles_total, $img_format, $zipfile_unzipsize);
		#$email .= $str;
		
		$laststudy_id = $study_id;
	}
	
	$email .= "</table></body>";
	
	print $email;
	#exit(0);
	
	$sqlstring = "SELECT user_email FROM `users` WHERE sendmail_dailysummary = 1";
	$result = $db->query($sqlstring) || SQLError($sqlstring, $db->errmsg());
	while (my %row = $result->fetchhash) {
		my $toemail = $row{'user_email'};
		WriteLog("Calling SendHTMLEmail($toemail, 'ADO Server daily report', $email)");
		SendHTMLEmail($toemail, 'ADO Server daily report', $email);
		WriteLog("Done calling SendHTMLEmail($toemail, 'ADO Server daily report', $email)");
	}
	
	# update the stop time
	$sqlstring = "update modules set module_laststop = now(), module_status = 'stopped' where module_name = '$scriptname'";
	$result = $db->query($sqlstring);

	return $ret;
}


# ----------------------------------------------------------
# --------- GenerateColorGradient --------------------------
# ----------------------------------------------------------
sub GenerateColorGradient {
	# generate a color gradient in an array (green to yellow)
	my $startR = 0xFF;
	my $startG = 0xFF;
	my $startB = 0x66;
	my $endR = 0x66;
	my $endG = 0xFF;
	my $endB = 0x66;
	
	my $total = 50; # number of gradations
	
	my @colors;

	for (my $i=0; $i<=$total; $i++) {
		my $percentSR = ($i/$total)*$startR;
		my $percentER = (1-($i/$total))*$endR;
		my $colorR = $percentSR + $percentER;

		my $percentSG = ($i/$total)*$startG;
		my $percentEG = (1-($i/$total))*$endG;
		my $colorG = $percentSG + $percentEG;

		my $percentSB = ($i/$total)*$startB;
		my $percentEB = (1-($i/$total))*$endB;
		my $colorB = $percentSB + $percentEB;

		my $color = sprintf("%02X%02X%02X", $colorR, $colorG, $colorB);
		$colors[$i] = $color;
	}

	# generate gradient from yellow to red
	$startR = 0xFF;
	$startG = 0x66;
	$startB = 0x66;
	$endR = 0xFF;
	$endG = 0xFF;
	$endB = 0x66;

	for (my $i=0; $i<=$total; $i++) {
		my $percentSR = ($i/$total)*$startR;
		my $percentER = (1-($i/$total))*$endR;
		my $colorR = $percentSR + $percentER;

		my $percentSG = ($i/$total)*$startG;
		my $percentEG = (1-($i/$total))*$endG;
		my $colorG = $percentSG + $percentEG;

		my $percentSB = ($i/$total)*$startB;
		my $percentEB = (1-($i/$total))*$endB;
		my $colorB = $percentSB + $percentEB;

		my $color = sprintf("%02X%02X%02X", $colorR, $colorG, $colorB);
		$colors[$i+$total] = $color;
	}

	return @colors;
}


# ----------------------------------------------------------
# --------- GetGlobalQAStats -------------------------------
# ----------------------------------------------------------
sub GetGlobalQAStats {

	my %pstats;
	
	# get the movement & SNR stats by sequence name
	my $sqlstring = "SELECT b.series_sequencename, max(a.move_maxx) 'maxx', min(a.move_minx) 'minx', max(a.move_maxy) 'maxy', min(a.move_miny) 'miny', max(a.move_maxz) 'maxz', min(a.move_minz) 'minz', avg(a.pv_snr) 'avgpvsnr', avg(a.io_snr) 'avgiosnr', std(a.pv_snr) 'stdpvsnr', std(a.io_snr) 'stdiosnr', min(a.pv_snr) 'minpvsnr', min(a.io_snr) 'miniosnr', max(a.pv_snr) 'maxpvsnr', max(a.io_snr) 'maxiosnr' FROM mr_qa a left join mr_series b on a.mrseries_id = b.mrseries_id where a.io_snr > 0 group by b.series_sequencename";
	
	#echo "$sqlstring2<br>";
	my $result = $db->query($sqlstring) || SQLError($sqlstring, $db->errmsg());
	while (my %row = $result->fetchhash) {
		my $sequence = $row{'series_sequencename'};
		$pstats{$sequence}{'rangex'} = abs($row{'minx'}) + abs($row{'maxx'});
		$pstats{$sequence}{'rangey'} = abs($row{'miny'}) + abs($row{'maxy'});
		$pstats{$sequence}{'rangez'} = abs($row{'minz'}) + abs($row{'maxz'});
		$pstats{$sequence}{'avgpvsnr'} = $row{'avgpvsnr'};
		$pstats{$sequence}{'stdpvsnr'} = $row{'stdpvsnr'};
		$pstats{$sequence}{'minpvsnr'} = $row{'minpvsnr'};
		$pstats{$sequence}{'maxpvsnr'} = $row{'maxpvsnr'};
		
		$pstats{$sequence}{'avgiosnr'} = $row{'avgiosnr'};
		$pstats{$sequence}{'stdiosnr'} = $row{'stdiosnr'};
		$pstats{$sequence}{'miniosnr'} = $row{'miniosnr'};
		$pstats{$sequence}{'maxiosnr'} = $row{'maxiosnr'};
		
		if ($row{'stdiosnr'} != 0) {
			$pstats{$sequence}{'maxstdiosnr'} = ($row{'avgiosnr'} - $row{'miniosnr'})/$row{'stdiosnr'};
		} else { $pstats{$sequence}{'maxstdiosnr'} = 0; }
		if ($row{'stdpvsnr'} != 0) {
			$pstats{$sequence}{'maxstdpvsnr'} = ($row{'avgpvsnr'} - $row{'minpvsnr'})/$row{'stdpvsnr'};
		} else { $pstats{$sequence}{'maxstdpvsnr'} = 0; }
	}
	
	return %pstats;
}
