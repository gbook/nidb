#!/usr/bin/perl

# ------------------------------------------------------------------------------
# NIDB updatePhaseEncoding.pl
# Copyright (C) 2004 - 2018
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
# This program reads all mr_series and updates the phase encoding dir in the DB
# -----------------------------------------------------------------------------

use strict;
use warnings;
use Mysql;
use Image::ExifTool;
use Net::SMTP::TLS;
use Data::Dumper;
use File::Path;
use File::Copy;
use Cwd;
use String::CRC32;
use Date::Manip;
use Scalar::Util qw(looks_like_number);

require 'nidbroutines.pl';
our %cfg;
LoadConfig();

our $db;

# turn on auto flushing (for flushing the console and file buffers)
$|++;
use IO::Handle;

# ------------- end variable declaration --------------------------------------
# -----------------------------------------------------------------------------
	
# no idea why, but perl is buffering output to the screen, and these 3 statements turn off buffering
my $old_fh = select(STDOUT);
$| = 1;
select($old_fh);

DoUpdate();

exit(0);


# ----------------------------------------------------------
# --------- DoUpdate ---------------------------------------
# ----------------------------------------------------------
sub DoUpdate {
	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	# loop through all mr_series
	my $sqlstring = "select mrseries_id from mr_series";
	my $resultC = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($resultC->numrows > 0) {
		my $totalrows = $resultC->numrows;
		my $i = 0;
		while (my %rowC = $resultC->fetchhash) {
			my $seriesid = $rowC{'mrseries_id'};
			my ($path, $uid, $studynum, $seriesnum, $studyid, $subjectid) = GetDataPathFromSeriesID($seriesid, 'mr');
			#print "$path/dicom\n";
			my $percent = (($i+0.0)/$totalrows)*100.0;
			print "Completed [$i of $totalrows] %" . sprintf("%.2f",$percent) . "\n";
			UpdateMRScanParameters("$path/dicom", $seriesid);
			$i++;
		}
	}
}


# ----------------------------------------------------------
# --------- UpdateMRScanParameters -------------------
# ----------------------------------------------------------
sub UpdateMRScanParameters {
	my ($dicomdir, $seriesid) = @_;

	#print "Inside UpdateMRScanParameters() with [$dicomdir]\n";
	
	my $sqlstring;
	my $subjectRowID;
	my $subjectRealUID;
	my $familyRealUID;
	my $familyRowID;
	my $projectRowID;
	my $enrollmentRowID;
	my $studyRowID;
	my $seriesRowID;
	my $costcenter;
	my $study_num;
	
	my $dicomfile;
	
	chdir($dicomdir);
	my @files = glob("*.dcm");
	if (scalar @files > 0) {
		$dicomfile = $files[0];
	}
	else {
		print "[ EMPTY ] ";
		return;
	}
	
	#print "Working on [$dicomdir/$dicomfile]\n";
	if (-e $dicomfile) {
		#print "$dicomfile exists\n";
	}
	else {
		print "[$dicomfile] does not exist!\n";
		return;
	}
	
	# get DICOM tags from first file of this series
	my $type = Image::ExifTool::GetFileType($dicomfile);
	if (($type ne "DICM") && ($type ne "ACR") && ($type ne "DICOM")) {
		print "This is not a DICM, ACR, or DICOM file. It is [$type]\n";
	}
	my $exifTool = new Image::ExifTool;
	my $info = $exifTool->ImageInfo($dicomfile);

	# MR specific tags
	my $Rows = trim($info->{'Rows'});
	my $Columns = trim($info->{'Columns'});
	my $AccessionNumber = trim($info->{'AccessionNumber'});
	my $SliceThickness = trim($info->{'SliceThickness'});
	my $PixelSpacing = trim($info->{'PixelSpacing'});
	my $NumberOfTemporalPositions = trim($info->{'NumberOfTemporalPositions'});
	my $ImagesInAcquisition = trim($info->{'ImagesInAcquisition'});
	my $ImageType = EscapeMySQLString(trim($info->{'ImageType'}));
	my $ImageComments = EscapeMySQLString(trim($info->{'ImageComments'}));
	my $MagneticFieldStrength = trim($info->{'MagneticFieldStrength'});
	my $RepetitionTime = trim($info->{'RepetitionTime'});
	my $FlipAngle = trim($info->{'FlipAngle'});
	my $EchoTime = trim($info->{'EchoTime'});
	my $AcquisitionMatrix = trim($info->{'AcquisitionMatrix'});
	my $InPlanePhaseEncodingDirection = EscapeMySQLString(trim($info->{'InPlanePhaseEncodingDirection'}));
	my $InversionTime = trim($info->{'InversionTime'});
	my $PercentSampling = trim($info->{'PercentSampling'});
	my $PercentPhaseFieldOfView = trim($info->{'PercentPhaseFieldOfView'});
	my $PixelBandwidth = trim($info->{'PixelBandwidth'});
	my $SpacingBetweenSlices = trim($info->{'SpacingBetweenSlices'});
	my $EchoTrainLength = trim($info->{'EchoTrainLength'});
	my ($pixelX, $pixelY) = split(/\\/, $PixelSpacing);
	
	# attempt to get the phase encode angle (In Plane Rotation) from the siemens CSA header
	my $PhaseEncodeAngle = "";
	my $PhaseEncodingDirectionPositive = "";
	open(F, $dicomfile); # open the dicom file as a text file, since part of the CSA header is stored as text, not binary
	my @dcmlines=<F>;
	close(F);
	foreach my $line(@dcmlines) {	
		if ($line =~ /\]\.dInPlaneRot/i) {
			if (length($line) > 150) {
				my $idx = index($line, '.dInPlaneRot');
				#print "[$line]\n";
				$line = substr($line,$idx,23);
			}
			my @values = split /\s*=\s*/, $line;
			my $value = trim($values[-1]);
			if (looks_like_number($value) && ($value ne "")) {
				if ($value > 3.5) { $value = ""; }
				if ($value < -3.5) { $value = ""; }
			}
			else {
				$value = "";
			}
			#print "[$line]: [$value]\n";
			$PhaseEncodeAngle = substr($value,0,8);
			last;
		}
	}
	#print "PhaseEncodeAngle = [$PhaseEncodeAngle]\n";
	
	# get the other part of the CSA header, the PhaseEncodingDirectionPositive value
	chdir($cfg{'scriptdir'});
	my $systemstring = "./gdcmdump -C $dicomdir/$dicomfile | grep PhaseEncodingDirectionPositive";
	#print "Running [$systemstring]\n";
	my $header = trim(`$systemstring`);
	#print "$header\n";
	my @parts = split(',', $header);
	my $val = $parts[4];
	$val =~ s/Data '//g;
	$val =~ s/'//g;
	$val = trim($val);
	#print "PhaseEncodingDirectionPositive = [$val]\n";
	$PhaseEncodingDirectionPositive = $val;
	
	$PhaseEncodeAngle = EscapeMySQLString(trim($PhaseEncodeAngle));
	$PhaseEncodingDirectionPositive = EscapeMySQLString(trim($PhaseEncodingDirectionPositive));
	
	$sqlstring = "update mr_series set phaseencodedir = '$InPlanePhaseEncodingDirection', phaseencodeangle = '$PhaseEncodeAngle', PhaseEncodingDirectionPositive = '$PhaseEncodingDirectionPositive' where mrseries_id = $seriesid";
	
	$sqlstring = "update mr_series set series_tr = '$RepetitionTime', series_te = '$EchoTime', series_flip = '$FlipAngle', phaseencodedir = '$InPlanePhaseEncodingDirection', phaseencodeangle = '$PhaseEncodeAngle', PhaseEncodingDirectionPositive = '$PhaseEncodingDirectionPositive', series_spacingx = '$pixelX',series_spacingy = '$pixelY', series_spacingz = '$SliceThickness', series_fieldstrength = '$MagneticFieldStrength', img_rows = '$Rows', img_cols = '$Columns', series_ti = '$InversionTime', percent_sampling = '$PercentSampling', percent_phasefov = '$PercentPhaseFieldOfView', acq_matrix = '$AcquisitionMatrix', slicethickness = '$SliceThickness', slicespacing = '$SpacingBetweenSlices', bandwidth = '$PixelBandwidth', image_type = '$ImageType', image_comments = '$ImageComments'";
	if (looks_like_number($NumberOfTemporalPositions) && ($NumberOfTemporalPositions > 0)) {
		$sqlstring .= ", bold_reps = '$NumberOfTemporalPositions', dimT = '$NumberOfTemporalPositions', dimN = 4";
	}
	if (looks_like_number($ImagesInAcquisition) && ($ImagesInAcquisition > 0)) {
		$sqlstring .= ", dimZ = '$ImagesInAcquisition'";
	}
	$sqlstring .= " where mrseries_id = $seriesid";
	
	#print "$sqlstring\n";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
	print "[ DONE  ] ";
}
