# ------------------------------------------------------------------------------
# NIDB nidbroutines.pl
# Copyright (C) 2004 - 2017
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


# ----------------------------------------------------------
# --------- LoadConfig -------------------------------------
# ----------------------------------------------------------
# this function loads the config file into a hash called $cfg
# ----------------------------------------------------------
sub LoadConfig {
	my $file;
	if (-e 'nidb.cfg') {
		$file = 'nidb.cfg';
	}
	elsif (-e '../nidb.cfg') {
		$file = '../nidb.cfg';
	}
	elsif (-e '../../prod/programs/nidb.cfg') {
		$file = '../../prod/programs/nidb.cfg';
	}
	elsif (-e '../../../../prod/programs/nidb.cfg') {
		$file = '../../../../prod/programs/nidb.cfg';
	}
	elsif (-e '../programs/nidb.cfg') {
		$file = '../programs/nidb.cfg';
	}
	elsif (-e '/home/nidb/programs/nidb.cfg') {
		$file = '/home/nidb/programs/nidb.cfg';
	}
	elsif (-e '/nidb/programs/nidb.cfg') {
		$file = '/nidb/programs/nidb.cfg';
	}
	print "Using config file [$file]\n";
	open(CFG, $file) || die "Can't open $file: $!\n";
	while ($line = <CFG>) {
		if ((substr($line,0,1) ne "#") && (trim($line) ne "")) {
			my ($var, $value) = split(' = ', trim($line));
			$var =~ s/(\[|\])//g;
			$cfg{$var} = $value;
			#print "cfg[$var] = $value\n";
		}
	}
	close(CFG);
}


# ----------------------------------------------------------
# --------- DatabaseConnect --------------------------------
# ----------------------------------------------------------
# this function assumes all variables are global... 
# they should be defined at the top of every file
# ----------------------------------------------------------
sub DatabaseConnect {
	
	if ($dev) {
		$db = Mysql->connect($mysqldevhost, $mysqldevdatabase, $mysqldevuser, $mysqldevpassword) || Error("Can NOT connect to $mysqldevhost\n");
	}
	else {
		$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || Error("Can NOT connect to $cfg{'mysqlhost'}\n");
	}
}


# ----------------------------------------------------------
# --------- CheckNumLockFiles ------------------------------
# ----------------------------------------------------------
sub CheckNumLockFiles {
	my ($lockfileprefix, $lockdir) = @_;
	
	my @lockfiles = ();
	@lockfiles = glob("$lockdir/$lockfileprefix.*");
	
	my $numlocks = @lockfiles;
	
	print "Found $numlocks lock files\n";
	return $numlocks;
}


# ----------------------------------------------------------
# --------- CreateLockFile ---------------------------------
# ----------------------------------------------------------
sub CreateLockFile {
	my ($lockfileprefix, $lockdir, $numinstances) = @_;
	
	my ($lockfile, $logfile);
	
	#for (my $i=0; $i<=$numinstances+1; $i++) {
		$lockfile = "$lockdir/$lockfileprefix.$$";
		$logfile = "$lockdir/$lockfileprefix.log.$$";
		#if (-e "$lockdir/$lockfileprefix.$i") {
		#	print "$lockfile exists\n";
		#}
		#else {
			print "Creating $lockfile.\n";
			open LOCKFILE, ("> $lockfile");
			my $datetime = CreateCurrentDate();
			print LOCKFILE $datetime;
			close LOCKFILE;
			#last;
			chmod(0777,$lockfile);
		#}
	#}
	
	return ($lockfile, $logfile);
}


# ----------------------------------------------------------
# --------- ModuleCheckIfActive ----------------------------
# ----------------------------------------------------------
sub ModuleCheckIfActive {
	my ($scriptname, $db) = @_;
	
	my $sqlstring = "select * from modules where module_name = '$scriptname' and module_isactive = 1";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	if ($result->numrows < 1) {
		return 0;
	}
	else {
		return 1;
	}
}


# ----------------------------------------------------------
# --------- ModuleDBCheckIn --------------------------------
# ----------------------------------------------------------
sub ModuleDBCheckIn {
	my ($scriptname, $db) = @_;
	
	my $sqlstring = "update modules set module_laststart = now(), module_status = 'running', module_numrunning = module_numrunning + 1 where module_name = '$scriptname'";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
}


# ----------------------------------------------------------
# --------- ModuleDBCheckOut -------------------------------
# ----------------------------------------------------------
sub ModuleDBCheckOut {
	my ($scriptname, $db) = @_;
	
	my $sqlstring = "update modules set module_laststop = now(), module_status = 'stopped', module_numrunning = module_numrunning - 1 where module_name = '$scriptname'";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);

	$sqlstring = "delete from module_procs where module_name = '$scriptname' and process_id = '$$'";
	$result = SQLQuery($sqlstringA, __FILE__, __LINE__);
}


# ----------------------------------------------------------
# --------- ModuleRunningCheckIn ---------------------------
# ----------------------------------------------------------
# this is a deadman's switch. if the module doesn't check in
# after a certain period of time, the module is assumed to
# be dead and is reset so it can start again
sub ModuleRunningCheckIn {
	my ($scriptname, $db) = @_;
	
	# insert a row if it doesn't exist
	my $sqlstring = "insert ignore into module_procs (module_name, process_id) values ('$scriptname', $$)";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
	
	# update the running time
	my $sqlstring = "update module_procs set last_checkin = now() where module_name = '$scriptname' and process_id = $$";
	my $result = $db->query($sqlstring) || SQLError($db->errmsg(),$sqlstring);
}


# ----------------------------------------------------------
# --------- SetModuleRunning -------------------------------
# ----------------------------------------------------------
sub SetModuleRunning() {

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring = "update modules set module_laststart = now(), module_status = 'running', module_numrunning = module_numrunning + 1 where module_name = '$scriptname'";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
}


# ----------------------------------------------------------
# --------- SetModuleStopped -------------------------------
# ----------------------------------------------------------
sub SetModuleStopped() {

	# connect to the database
	$db = Mysql->connect($cfg{'mysqlhost'}, $cfg{'mysqldatabase'}, $cfg{'mysqluser'}, $cfg{'mysqlpassword'}) || die("Can NOT connect to $cfg{'mysqlhost'}\n");
	
	my $sqlstring = "update modules set module_laststop = now(), module_status = 'stopped', module_numrunning = module_numrunning - 1 where module_name = '$scriptname'";
	my $result = $db->query($sqlstring) || SQLError("[File: " . __FILE__ . " Line: " . __LINE__ . "]" . $db->errmsg(),$sqlstring);
}


# -------------------------------------------------------------------
# ----------- CreateCurrentDate -------------------------------------
# -------------------------------------------------------------------
sub CreateCurrentDate {
	my ($sec,$min,$hour,$day,$month,$year,$wday,$yday,$isdst) = localtime(time);
	$year -= 100;
	$year += 2000;
	$month++;
	if (length($hour) == 1) { $hour = "0" . $hour; }
	if (length($sec) == 1) { $sec = "0" . $sec; }
	if (length($min) == 1) { $min = "0" . $min; }
	if (length($month) == 1) { $month = "0" . $month; }
	if (length($day) == 1) { $day = "0" . $day; }
	my $time = "$month/$day/$year $hour:$min:$sec";

	return $time;
}


# -------------------------------------------------------------------
# ----------- CreateLogDate -----------------------------------------
# -------------------------------------------------------------------
sub CreateLogDate {
	my ($sec,$min,$hour,$day,$month,$year,$wday,$yday,$isdst) = localtime(time);
	$year -= 100;
	$year += 2000;
	$month++;
	if (length($hour) == 1) { $hour = "0" . $hour; }
	if (length($sec) == 1) { $sec = "0" . $sec; }
	if (length($min) == 1) { $min = "0" . $min; }
	if (length($month) == 1) { $month = "0" . $month; }
	if (length($day) == 1) { $day = "0" . $day; }
	my $time = "$year$month$day$hour$min$sec";

	return $time;
}


# -------------------------------------------------------------------
# ----------- CreateMySQLDate ---------------------------------------
# -------------------------------------------------------------------
sub CreateMySQLDate {
	my ($sec,$min,$hour,$day,$month,$year,$wday,$yday,$isdst) = localtime(time);
	$year -= 100;
	$year += 2000;
	$month++;
	if (length($hour) == 1) { $hour = "0" . $hour; }
	if (length($sec) == 1) { $sec = "0" . $sec; }
	if (length($min) == 1) { $min = "0" . $min; }
	if (length($month) == 1) { $month = "0" . $month; }
	if (length($day) == 1) { $day = "0" . $day; }
	my $time = "$year-$month-$day $hour:$min:$sec";

	return $time;
}


# -------------------------------------------------------------------
# ----------- CreateMySQLDateFromFile -------------------------------
# -------------------------------------------------------------------
sub CreateMySQLDateFromFile {
	my ($file) = @_;
	
	my($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime,$blksize,$blocks) = stat("$file");
	
	my ($sec,$min,$hour,$day,$month,$year,$wday,$yday,$isdst) = $ctime;
	$year -= 100;
	$year += 2000;
	$month++;
	if (length($hour) == 1) { $hour = "0" . $hour; }
	if (length($sec) == 1) { $sec = "0" . $sec; }
	if (length($min) == 1) { $min = "0" . $min; }
	if (length($month) == 1) { $month = "0" . $month; }
	if (length($day) == 1) { $day = "0" . $day; }
	my $time = "$year-$month-$day $hour:$min:$sec";

	return $time;
}


# -------------------------------------------------------------------
# ----------- EscapeMySQLString -------------------------------------
# -------------------------------------------------------------------
sub EscapeMySQLString {
	my ($str) = @_;
	
	$str =~ s/'/\\'/g;
	$str =~ s/"/\\"/g;
	$str =~ tr/\000-\037//;
	return $str;
}


# -------------------------------------------------------------------------
# -------------- RunSystemCommand -----------------------------------------
# -------------------------------------------------------------------------
sub RunSystemCommand {
	my ($systemstring, $print, $run) = @_;

#	if ($print) { print "[$systemstring]\n"; }

	my @output;
	
	if ($run) {
		@output = `$systemstring`;
		if ($print) { print join "\n", @output; }
	}

#	RecordEvent("$systemstring",'system',join("\n",@output));

	return @output;
}


# -------------------------------------------------------------------------
# -------------- trim -----------------------------------------------------
# -------------------------------------------------------------------------
sub trim($)
{
	my $string = shift;
	if ($string eq "") {
		return "";
	}
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	chomp($string);
	return $string;
}


# -------------------------------------------------------------------
# ----------- GetTotalCPUTime ---------------------------------------
# -------------------------------------------------------------------
sub GetTotalCPUTime {
	my ($usertime,$systemtime,$cusertime,$csystemtime) = times();
	return $usertime + $systemtime + $cusertime + $csystemtime;
}


# ----------------------------------------------------------
# --------- SendTextEmail ----------------------------------
# ----------------------------------------------------------
sub SendTextEmail {
	my ($to, $subject, $body) = @_;

	#Create a new object with 'new'. 
	my $smtp;
	if (not $smtp = Net::SMTP::TLS->new($cfg{'emailserver'}, Port=>$cfg{'emailport'}, User=>$cfg{'emailusername'}, Password=>$cfg{'emailpassword'})) {
	#if (not $smtp = Net::SMTP::SSL->new('smtp.gmail.com', Port=>587, Debug=>1)) {
		die "Could not connect to SMTP:TLS server\n";
	}

	#$smtp->auth($cfg{'emailusername'}, $cfg{'emailpassword'}) || die "Autentication failed";
	
	$smtp->mail($cfg{'emailusername'} . "\n");
	my @recepients = split(/,/, $to);
	foreach my $recp (@recepients) {
		$smtp->to($recp . "\n");
	}

	#Start the message.
	$smtp->data();
	#Send the message.
	$smtp->datasend("From: $cfg{'emailusername'}\n");
	$smtp->datasend("To: $to\n");
	$smtp->datasend("Subject: $subject\n");
	
	# attempt to break up the message into 15kb sections
	# TLS has an odd limit: any string passed to datasend() must be shorter than 2^14 bytes (~16kb) or else its truncated and repeated
	while (length($body) > 15000) {
		my $frag = substr($body, 0, 15000);
		$body= substr($body, 15000);
		$smtp->datasend("$frag");
	}
	
	$smtp->datasend("$body \n\n");
	#End the message. 
	$smtp->dataend();
	#Close the connection to your server. 
	$smtp->quit();
  
}


# ----------------------------------------------------------
# --------- SendHTMLEmail ----------------------------------
# ----------------------------------------------------------
sub SendHTMLEmail {
	my ($to, $subject, $htmlbody) = @_;

	if (trim($to) eq "") {
		return "No recipients specified";
	}
	if (trim($subject) eq "") {
		return "Empty subject line";
	}
	if (trim($htmlbody) eq "") {
		return "Empty email body";
	}
	
	WriteLog("Emaillib is [" . $cfg{'emaillib'} . "]");
	if (($cfg{'emaillib'} eq '') || ($cfg{'emaillib'} eq 'Net-SMTP-TLS')) {
		#Create a new object with 'new'. 
		my $smtp;
		if (not $smtp = Net::SMTP::TLS->new($cfg{'emailserver'}, Port=>$cfg{'emailport'}, User=>$cfg{'emailusername'}, Password=>$cfg{'emailpassword'})) {
			print "Could not connect to server\n";
		}
		#print "Connected to $cfg{'emailserver'}:$cfg{'emailport'} with $cfg{'emailusername'}/$cfg{'emailpassword'}\n";

		# Create arbitrary boundary text used to seperate
		# different parts of the message
		my ($bi, $bn, @bchrs);
		my $boundry = "";
		foreach $bn (48..57,65..90,97..122) {
			$bchrs[$bi++] = chr($bn);
		}
		foreach $bn (0..20) {
			$boundry .= $bchrs[rand($bi)];
		}
		
		# send the header
		$smtp->mail($cfg{'emailusername'} . "\n");
		WriteLog("Sending mail to [$to]");
		my @recepients = split(/,/, $to);
		foreach my $recp (@recepients) {
			#print "Sending mail to [$recp]\n";
			$smtp->to($recp . "\n");
		}

		#Start the message.
		$smtp->data();
		#Send the message.
		$smtp->datasend("From: $cfg{'emailusername'}\n");
		$smtp->datasend("To: $to\n");
		$smtp->datasend("Subject: $subject\n");
		$smtp->datasend("MIME-Version: 1.0\n");
		$smtp->datasend("Content-Type: multipart/mixed; BOUNDARY=\"$boundry\"\n");
		$smtp->datasend("\n--$boundry\n");
		$smtp->datasend("Content-Type: text/html\n");

		# attempt to break up the message into 15kb sections
		# TLS has an odd limit: any string passed to datasend() must be shorter than 2^14 bytes (~16kb) or else its truncated and repeated
		while (length($htmlbody) > 15000) {
			my $frag = substr($htmlbody, 0, 15000);
			$htmlbody= substr($htmlbody, 15000);
			$smtp->datasend("$frag");
		}
		
		$smtp->datasend("$htmlbody \n\n");
		$smtp->datasend("\n--$boundry\n");
		#End the message. 
		$smtp->datasend("\n--$boundry--\n"); # send boundary end message
		$smtp->datasend("\n");
		$smtp->dataend();
		#Close the connection to your server. 
		$smtp->quit();
	}
	elsif ($cfg{'emaillib'} eq 'Email-Send-SMTP-Gmail') {
		WriteLog("Using the Email::Send::SMTP::Gmail module");
		my ($mail,$error)=Email::Send::SMTP::Gmail->new( -smtp=>$cfg{'emailserver'}, -login=>$cfg{'emailusername'}, -pass=>$cfg{'emailpassword'}, -port=>$cfg{'emailport'});
		WriteLog("Connection error, if any [$error]; ");
		#print "session error: $error" unless ($mail!=-1);
		$mail->send( -to=>$to, -subject=>$subject, -body=>$htmlbody, -contenttype=>'text/html');
		$mail->bye;	
	}
}


# ----------------------------------------------------------
# --------- WriteLog ---------------------------------------
# ----------------------------------------------------------
sub WriteLog {
	my ($msg) = @_;

	if (defined($msg)) {
		if ($debug) {
			print "$msg\n";
		}
		else {
			if (trim($msg) ne '') {
				print $log "[" . CreateCurrentDate() . "][pid $$] $msg\n";
			}
		}
		
		return $msg;
	}
	else {
		return 0;
	}
}


# ----------------------------------------------------------
# --------- AppendLog --------------------------------------
# ----------------------------------------------------------
sub AppendLog {
	my ($f,$msg) = @_;
	
	open (F, ">>", $f) || WriteLog("Could not open [$f] for appending. Could not write [$msg]");
	print F "[" . CreateCurrentDate() . "][pid $$] $msg\n";
	close(F);
}


# ----------------------------------------------------------
# --------- SQLQuery ---------------------------------------
# ----------------------------------------------------------
sub SQLQuery {
	my ($sql,$F,$L) = @_;
	
	my $result = $db->query($sql) || SQLError("[File: $F Line: $L]" . $db->errmsg(),$sql);

	return $result;
}


# -------------------------------------------------------------------------
# -------------- Error ----------------------------------------------------
# -------------------------------------------------------------------------
sub Error {
	my ($error) = @_;
	
	WriteLog("FATAL ERROR: $error");

	SendTextEmail($cfg{'adminemail'}, "$scriptname Fatal Error: $error", "The following error occurred: $error");
	SendHTMLEmail($cfg{'adminemail'}, "$scriptname Fatal Error: $error", "The following error occurred: $error");
	
	exit(0);
}


# -------------------------------------------------------------------------
# -------------- SQLError -------------------------------------------------
# -------------------------------------------------------------------------
sub SQLError {
	my ($sql, $error) = @_;
	
	WriteLog("SQL Error: '$error' in statement [$sql]");
	SendTextEmail("$cfg{'adminemail'}", "$scriptname Fatal SQL Error", "SQL Error: [$error'] in statement [$sql]");
	SendHTMLEmail("$cfg{'adminemail'}", "$scriptname Fatal SQL Error", "SQL Error: [$error'] in statement [$sql]");
	exit(0);
}


# ----------------------------------------------------------
# --------- GetDirectorySize -------------------------------
# ----------------------------------------------------------
sub GetDirectorySize {
	my ($dir) = @_;

	my $size = 0;
	
	# get the size of the unzipped data
	my $systemstring = "du -sb $dir";
	my $output = `$systemstring`;
	my @parts = split(/\s/,$output);
	$size = $parts[0];

	my @files = <$dir/*.*>;
	my $count = @files;
	
	return ($size, $count);
}


# -------------------------------------------------------------------------
# -------------- CompressText ---------------------------------------------
# -------------------------------------------------------------------------
sub CompressText() {
	my (@f) = @_;
	
	my $lineback1 = ""; # previous line
	my $lineback2 = ""; #current -2 line
	my $lineback3 = ""; #current -3 line
	my $skip = 0;
	my $result;
	
	#print "results: " . @f;
	# go through line by line
	foreach my $line (@f) {
		# only if this line matches the previous 3 lines do we substitute ... and set skip to true
		if ((substr($line,0,15) eq substr($lineback1,0,15)) && (substr($line,0,15) eq substr($lineback2,0,15)) && (substr($line,0,15) eq substr($lineback3,0,15))) {
			if (!$skip) {
				$result .= "     ...\n     (results truncated)\n     ...\n";
				$skip = 1;
			}
		}
		else {
			$result .= "$line";
			$skip = 0;
		}
		
		$lineback3 = $lineback2;
		$lineback2 = $lineback1;
		$lineback1 = $line;
	}
	
	return $result;
}


# ----------------------------------------------------------
# --------- GenerateRandomString ---------------------------
# ----------------------------------------------------------
sub GenerateRandomString
{
	my $length_of_randomstring=shift;# the length of 
			 # the random string to generate

	my @chars=('a'..'z','A'..'Z','0'..'9');
	my $random_string;
	foreach (1..$length_of_randomstring) 
	{
		# rand @chars will generate a random 
		# number between 0 and scalar @chars
		$random_string.=$chars[rand @chars];
	}
	return $random_string;
}


# ----------------------------------------------------------
# --------- IsDICOMFile ------------------------------------
# ----------------------------------------------------------
sub IsDICOMFile {
	my ($f) = @_;
	
	# check if its really a dicom file...
	my $type = '';
	my $exifTool = new Image::ExifTool;
	my $tags = $exifTool->ImageInfo($f);
	$type = $tags->{'FileType'};
	if (defined($type)) {
		if (($type ne "DICOM") && ($type ne "ACR")) {
			return 0;
		}
		else {
			if (defined($tags->{'Error'})) {
				return -1;
			}
		}
	}
	else {
		return 0;
	}
	return 1;
}


# ----------------------------------------------------------
# --------- GetSQLComparison -------------------------------
# ----------------------------------------------------------
sub GetSQLComparison {
	my ($c) = @_;

	WriteLog("Inside GetSQLComparison($c)");
	
	$c =~ s/\s*//g; # remove all whitespace
	
	# check if there is anything to format
	if ($c eq "") { return (0,0); }
	
	my $comp = 0;
	my $num = 0;
	if (substr($c,0,2) eq "<=") {
		$comp = "<=";
		$num = substr($c,2);
	}
	elsif (substr($c,0,2) eq ">=") {
		$comp = ">=";
		$num = substr($c,2);
	}
	elsif (substr($c,0,1) eq "<") {
		$comp = "<";
		$num = substr($c,1);
	}
	elsif (substr($c,0,1) eq ">") {
		$comp = ">";
		$num = substr($c,1);
	}
	elsif (substr($c,0,1) eq "~") {
		$comp = "<>";
		$num = substr($c,1);
	}
	else {
		$comp = "=";
		$num = $c;
	}
	
	return ($comp, $num);
}


# ----------------------------------------------------------
# ------- GetDataPathFromSeriesID --------------------------
# ----------------------------------------------------------
sub GetDataPathFromSeriesID {
	my ($id, $modality) = @_;
	$modality = lc($modality);
	
	$sqlstring = "select * from $modality"."_series a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.$modality"."series_id = $id";
	$result = SQLQuery($sqlstring, __FILE__, __LINE__);
	if ($result->numrows > 0) {
		my %row = $result->fetchhash;
		$uid = $row{'uid'};
		$studynum = $row{'study_num'};
		$seriesnum = $row{'series_num'};
		$subjectid = $row{'subject_id'};
		$studyid = $row{'study_id'};
		
		$path = $cfg{'archivedir'} . "/$uid/$studynum/$seriesnum";
		return ($path, $uid, $studynum, $studyid, $subjectid);
	}
	else {
		return ("","","",0,0);
	}
}


# ----------------------------------------------------------
# --------- MakePath ---------------------------------------
# ----------------------------------------------------------
sub MakePath {
	my ($p) = @_;
	
	WriteLog("Creating path [$p]");
	#print "Creating path [$p]\n";
	my $systemstring = "mkdir -pv '$p'";
	WriteLog("[$systemstring]: " . `$systemstring 2>&1`);
	return 1;
	
	make_path($p, {mode => 0777, verbose => 1, error => \my $err});
	if (@$err) {
		for my $diag (@$err) {
			my ($file, $message) = %$diag;
			if ($file eq '') {
				print "general error creating [$p]: $message\n";
				WriteLog("general error creating [$p]: $message");
			}
			else {
				print "problem unlinking $file: $message\n";
				WriteLog("problem unlinking $file: $message");
			}
		}
		return 0;
	}
	else {
		print "No error encountered when creating [$p]\n";
		WriteLog("No error encountered when creating [$p]");
		return 1;
	}
}


# ----------------------------------------------------------
# --------- IsDirEmpty -------------------------------------
# ----------------------------------------------------------
sub IsDirEmpty {
    my $dirname = shift;
    opendir(my $dh, $dirname) or return 0;
    return scalar(grep { $_ ne "." && $_ ne ".." } readdir($dh)) == 0;
}


# ----------------------------------------------------------
# --------- InsertAnalysisEvent ----------------------------
# ----------------------------------------------------------
sub InsertAnalysisEvent {
	my ($analysisID, $pipelineID, $pipelineVersion, $studyID, $event, $message) = @_;
	
	my $hostname = `hostname`;
	$event = EscapeMySQLString($event);
	$message = EscapeMySQLString($message);
	
	my $sqlstring = "insert into analysis_history (analysis_id, pipeline_id, pipeline_version, study_id, analysis_event, analysis_hostname, event_message) values ($analysisID, $pipelineID, $pipelineVersion, $studyID, '$event', '$hostname', '$message')";
	my $result = SQLQuery($sqlstring, __FILE__, __LINE__);
}


# must return 1 for this file to be included correctly
1;