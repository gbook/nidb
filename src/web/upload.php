<?
 // ------------------------------------------------------------------------------
 // NiDB upload.php
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

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
		chmod($path, 0777);
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    function getSize() {
        //die("{'error':'testing the getSize()'}");    
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 5000000000){
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;
		if (($GLOBALS['cfg']['uploadsizelimit'] == "") || ($GLOBALS['cfg']['uploadsizelimit'] < 0)) {
			$this->sizeLimit = 100 * 1024 * 1024;
		}
		else {
			$this->sizeLimit = $GLOBALS['cfg']['uploadsizelimit'] * 1024 * 1024;
		}
        
        $this->checkServerSettings();       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
        }        
    }
    
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
		//return array('error'=>'made it to checkpoint 0');
        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory [$uploadDirectory] isn't writable.");
        }
		//return array('error'=>'made it to checkpoint 1');
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
		//return array('error'=>'made it to checkpoint 2');
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
		//return array('error'=>'made it to checkpoint 3');
        
        if ($size > $this->sizeLimit) {
            return array('error' => 'File is too large');
        }
		if ($this->sizeLimit == 0) {
            return array('error' => 'Max upload file size is not set, contact NiDB admin');
		}
		//return array('error'=>'made it to checkpoint 4');
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];
		//return array('error'=>'made it to checkpoint 5');

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }
		//return array('error'=>'made it to checkpoint 6');
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= rand(10, 99);
            }
        }
		//return array('error'=>'made it to checkpoint 7');
        
        if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
            return array('success'=>true);
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
        //return array('error'=>'should not be here 8');
        
    }    
}


	
/* params are stored in $_GET */
$modality = strtolower((string)GetVariable("modality"));
$seriesid = (int)GetVariable("seriesid");

/* --------------------------------------------------------------------------
   Validate the request before touching the filesystem.
   'mrbeh' = behavioral files attached to an MR series (stored in <series>/beh/).
   Otherwise the modality must be a real NiDB modality: it becomes both a table
   name and a directory name, so it is never used unvalidated.
   -------------------------------------------------------------------------- */
if ($modality == "mrbeh") {
	$seriesmodality = "mr";
	$subdir = "beh";
}
else {
	$seriesmodality = $modality;
	$subdir = $modality;
	if (($seriesmodality == "mr") || (GetSeriesTableName($seriesmodality) == '') || !IsNiDBModality($seriesmodality)) {
		echo htmlspecialchars(json_encode(array('error' => 'Invalid modality')), ENT_NOQUOTES);
		exit(0);
	}
}
$seriestable = GetSeriesTableName($seriesmodality);

$sqlstring = "select a.series_num, b.study_num, c.project_id, d.uid from `$seriestable` a left join studies b on a.study_id = b.study_id left join enrollment c on b.enrollment_id = c.enrollment_id left join subjects d on c.subject_id = d.subject_id where a.`{$seriesmodality}series_id` = ?";
$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
mysqli_stmt_bind_param($stmt, 'i', $seriesid);
$result2 = MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$seriesid]);
mysqli_stmt_close($stmt);
$row = mysqli_fetch_array($result2, MYSQLI_ASSOC);
if (!$row || (($row['uid'] ?? '') == '')) {
	echo htmlspecialchars(json_encode(array('error' => 'Series not found')), ENT_NOQUOTES);
	exit(0);
}

/* uploading files into a series is a data change: Edit Data on the series' project */
$projectid = (int)$row['project_id'];
$perms = GetCurrentUserProjectPermissions(array($projectid));
if (!GetPerm($perms, 'modifydata', $projectid)) {
	echo htmlspecialchars(json_encode(array('error' => 'You do not have permission to upload files to this series')), ENT_NOQUOTES);
	exit(0);
}

$study_num = (int)$row['study_num'];
$uid = $row['uid'];
$series_num = (int)$row['series_num'];
$uploadpath = $GLOBALS['cfg']['archivedir'] . "/$uid/$study_num/$series_num/$subdir/";
if (!file_exists($uploadpath)) {
	mkdir($uploadpath,0777,true);
}

// list of valid extensions, ex. array("jpeg", "xml", "bmp")
$allowedExtensions = array();
// max file size in bytes
$sizeLimit = $GLOBALS['cfg']['uploadsizelimit'] * 1024 * 1024;

$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
$result = $uploader->handleUpload($uploadpath);

if (($result['success'] ?? false) == true) {
	/* update the DB with the files that were uploaded */
	$filecount = count(glob("$uploadpath*") ?: array());
	$filesize = GetDirectorySize($uploadpath);

	if ($modality == "mrbeh") {
		$sqlstring = "update mr_series set numfiles_beh = ?, beh_size = ? where mrseries_id = ?";
	}
	else {
		$systemstring = "chmod -R 777 " . escapeshellarg($uploadpath);
		$output = `$systemstring`;
		$sqlstring = "update `$seriestable` set series_numfiles = ?, series_size = ? where `{$seriesmodality}series_id` = ?";
	}
	$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
	mysqli_stmt_bind_param($stmt, 'iii', $filecount, $filesize, $seriesid);
	MySQLiBoundQuery($stmt, __FILE__, __LINE__, $sqlstring, [$filecount, $filesize, $seriesid]);
	mysqli_stmt_close($stmt);
}

// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);


/* functions must be at the end of the script, classes at the beginning, eh? */
function GetDirectorySize($dirname) {
	$folderSize = 0;

	// open the directory, if the script cannot open the directory then return folderSize = 0
	$dir_handle = @opendir($dirname);
	if (!$dir_handle) return 0;

	// traversal for every entry in the directory
	while (($file = readdir($dir_handle)) !== false) {

		// ignore '.' and '..' directory
		if (($file != ".") && ($file != "..")) {
			if (is_dir($dirname."/".$file)) {
				$folderSize += GetDirectorySize($dirname.'/'.$file); // recurse into subdirectories
			}
			else {
				$folderSize += filesize($dirname."/".$file);
			}
		}
	}
	closedir($dir_handle);

	return $folderSize;
}
?>