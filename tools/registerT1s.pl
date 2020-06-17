# program to average a group of T1 images and output a mean image

use Data::Dumper;
use strict;

my $numargs = $#ARGV + 1;

if ($numargs < 3) {
	print "ERROR: This program requires at least 3 arguments\n\nUsage: registerT1s.pl outfileName img1.nii.gz img2.nii.gz img3.nii.gz ...\n\n";
	exit(0);
}
my $outimage = shift(@ARGV);
my @t1s = @ARGV;

RegisterSimple(".",\@t1s,1,$outimage);

print "\nDone.\n";

# ----------------------------------------------------------
# --------- RegisterSimple ---------------------------------
# ----------------------------------------------------------
sub RegisterSimple() {
	my ($workdir, $t1s, $refvol, $meanimagename) = @_;

	my $systemstring;
	my @t1images = @{$t1s};
	my $numcompimages = $#t1images+1;
	
	print "Beginning register of $numcompimages total images\n";

	# run flirt to register each volume to the ref
	foreach my $i (1..$#t1images) {
		my $inimg = $t1images[$i];
		my $refimg = $t1images[0];
		print "Registering $inimg to $refimg \n";
		
		$systemstring = "flirt -in $inimg -ref $refimg -omat $workdir/transform_$i.txt -o $workdir/realigned$i.nii.gz";
		print "$systemstring (" . `$systemstring` . ")";

		print "Done registering $inimg to $refimg\n";
	}
	
	# concatenate the images into 1 4D volume
	$systemstring = "fslmerge -t $workdir/4DT1.nii.gz $t1images[0] ";
	foreach my $i (1..$#t1images) {
		$systemstring .= "$workdir/realigned$i.nii.gz ";
	}
	print "$systemstring (" . `$systemstring` . ")";
	
	# get the mean image
	$systemstring = "fslmaths $workdir/4DT1.nii.gz -Tmean $workdir/$meanimagename.nii.gz";
	print "$systemstring (" . `$systemstring` . ")";

	# clean up
	$systemstring = "rm realigned*.nii.gz 4DT1.nii.gz";
	print "$systemstring (" . `$systemstring` . ")";
}
