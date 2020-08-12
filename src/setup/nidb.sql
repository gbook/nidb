-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 11, 2020 at 05:57 PM
-- Server version: 10.3.22-MariaDB
-- PHP Version: 7.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nidb`
--

-- --------------------------------------------------------

--
-- Table structure for table `analysis`
--

CREATE TABLE `analysis` (
  `analysis_id` bigint(20) NOT NULL,
  `pipeline_id` int(11) DEFAULT NULL,
  `pipeline_version` int(11) DEFAULT 0,
  `pipeline_dependency` int(11) DEFAULT NULL,
  `study_id` int(11) DEFAULT NULL,
  `analysis_qsubid` bigint(20) UNSIGNED DEFAULT NULL,
  `analysis_status` enum('complete','pending','processing','error','submitted','','notcompleted','NoMatchingStudies','rerunresults','NoMatchingStudyDependency','IncompleteDependency','BadDependency','NoMatchingSeries','OddDependencyStatus') DEFAULT NULL,
  `analysis_statusmessage` varchar(255) DEFAULT NULL,
  `analysis_statusdatetime` timestamp NULL DEFAULT NULL,
  `analysis_notes` text DEFAULT NULL,
  `analysis_iscomplete` tinyint(1) DEFAULT NULL,
  `analysis_isbad` tinyint(1) DEFAULT NULL,
  `analysis_datalog` mediumtext DEFAULT NULL,
  `analysis_datatable` text DEFAULT NULL,
  `analysis_rerunresults` tinyint(1) DEFAULT NULL,
  `analysis_runsupplement` tinyint(1) DEFAULT NULL,
  `analysis_result` varchar(50) DEFAULT NULL,
  `analysis_resultmessage` text DEFAULT NULL,
  `analysis_numseries` int(11) DEFAULT NULL,
  `analysis_swversion` varchar(255) DEFAULT NULL,
  `analysis_hostname` varchar(255) DEFAULT NULL,
  `analysis_disksize` double DEFAULT NULL,
  `analysis_numfiles` int(11) DEFAULT NULL,
  `analysis_startdate` timestamp NULL DEFAULT NULL,
  `analysis_clusterstartdate` timestamp NULL DEFAULT NULL,
  `analysis_clusterenddate` timestamp NULL DEFAULT NULL,
  `analysis_enddate` timestamp NULL DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `analysis_data`
--

CREATE TABLE `analysis_data` (
  `analysisdata_id` int(11) NOT NULL,
  `analysis_id` bigint(20) DEFAULT NULL,
  `data_id` int(11) DEFAULT NULL,
  `modality` varchar(25) DEFAULT NULL,
  `data_enabled` tinyint(1) DEFAULT NULL,
  `data_optional` tinyint(1) DEFAULT NULL,
  `data_imagetype` varchar(100) DEFAULT NULL,
  `data_type` varchar(100) DEFAULT NULL,
  `data_level` varchar(255) DEFAULT NULL,
  `data_assoctype` varchar(255) DEFAULT NULL,
  `data_numboldreps` varchar(20) DEFAULT NULL,
  `data_found` tinyint(1) DEFAULT NULL,
  `data_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `analysis_group`
--

CREATE TABLE `analysis_group` (
  `analysisgroup_id` int(11) NOT NULL,
  `pipeline_id` int(11) DEFAULT NULL,
  `pipeline_version` int(11) DEFAULT 0,
  `pipeline_dependency` int(11) DEFAULT NULL,
  `analysisgroup_status` enum('complete','pending','processing') DEFAULT NULL,
  `analysisgroup_statusmessage` varchar(255) DEFAULT NULL,
  `analysisgroup_statusdatetime` timestamp NULL DEFAULT NULL,
  `analysisgroup_iscomplete` tinyint(1) DEFAULT NULL,
  `analysisgroup_result` varchar(50) DEFAULT NULL,
  `analysisgroup_resultmessage` text DEFAULT NULL,
  `analysisgroup_numstudies` int(11) DEFAULT NULL,
  `analysisgroup_startdate` timestamp NULL DEFAULT NULL,
  `analysisgroup_clusterstartdate` timestamp NULL DEFAULT NULL,
  `analysisgroup_clusterenddate` timestamp NULL DEFAULT NULL,
  `analysisgroup_enddate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `analysis_history`
--

CREATE TABLE `analysis_history` (
  `analysishistory_id` bigint(20) NOT NULL,
  `analysis_id` bigint(20) DEFAULT NULL,
  `pipeline_id` int(11) DEFAULT NULL,
  `pipeline_version` int(11) DEFAULT NULL,
  `study_id` int(11) DEFAULT NULL,
  `analysis_event` enum('','analysiscopy','analysiscopydata','analysiscopydataend','analysiscreated','analysiscreatelink','analysisdeleted','analysisdeleteerror','analysisdependencyid','analysismessage','analysispending','analysisrecheck','analysissetuperror','analysissubmiterror','analysissubmitted','complete','completesupplement','processing','started','startedsupplement') DEFAULT NULL,
  `analysis_hostname` varchar(255) DEFAULT NULL,
  `event_message` text DEFAULT NULL,
  `event_datetime` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=Aria DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `analysis_parent`
--

CREATE TABLE `analysis_parent` (
  `analysis_id` int(11) NOT NULL,
  `analysisparent_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `analysis_resultnames`
--

CREATE TABLE `analysis_resultnames` (
  `resultname_id` int(11) NOT NULL,
  `result_name` varchar(255) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `analysis_results`
--

CREATE TABLE `analysis_results` (
  `analysisresults_id` int(11) NOT NULL,
  `analysis_id` bigint(20) DEFAULT NULL,
  `result_type` char(1) DEFAULT NULL COMMENT 'image, file, text, value',
  `result_nameid` int(11) DEFAULT NULL,
  `result_text` text DEFAULT NULL,
  `result_value` double DEFAULT NULL,
  `result_unitid` int(11) DEFAULT NULL,
  `result_filename` text DEFAULT NULL,
  `result_isimportant` tinyint(1) DEFAULT NULL,
  `result_count` smallint(5) UNSIGNED DEFAULT 0
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `analysis_resultunit`
--

CREATE TABLE `analysis_resultunit` (
  `resultunit_id` int(11) NOT NULL,
  `result_unit` varchar(25) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `experiment_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `form_id` int(11) DEFAULT NULL,
  `exp_groupid` int(11) DEFAULT NULL,
  `exp_admindate` datetime DEFAULT NULL COMMENT 'Date the experiment was administered',
  `experimentor` varchar(45) DEFAULT NULL COMMENT 'Just a name... anyone could adminisister the experiment, so they need not be registered in the system',
  `rater_username` varchar(25) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `iscomplete` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_data`
--

CREATE TABLE `assessment_data` (
  `formdata_id` int(11) NOT NULL,
  `formfield_id` int(11) DEFAULT NULL,
  `experiment_id` int(11) DEFAULT NULL,
  `value_text` text DEFAULT NULL,
  `value_number` double DEFAULT NULL,
  `value_string` varchar(255) DEFAULT NULL,
  `value_binary` blob DEFAULT NULL,
  `value_date` date DEFAULT NULL,
  `update_username` varchar(50) DEFAULT NULL COMMENT 'last username to change this value',
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_formfields`
--

CREATE TABLE `assessment_formfields` (
  `formfield_id` int(11) NOT NULL,
  `form_id` int(11) DEFAULT NULL,
  `formfield_desc` text DEFAULT NULL COMMENT 'The question description, such as ''DSM score'', or ''Which hand do you use most often''',
  `formfield_values` text DEFAULT NULL COMMENT 'a list of possible values',
  `formfield_datatype` enum('multichoice','singlechoice','string','text','number','date','header','binary','calculation') DEFAULT NULL COMMENT 'multichoice, singlechoice, string, text, number, date, header, binary',
  `formfield_calculation` varchar(255) DEFAULT NULL COMMENT '(q1+q4)/5',
  `formfield_calculationconversion` text DEFAULT NULL COMMENT 'comma seperated list of converting strings into numbers (A=1,B=2, etc)',
  `formfield_haslinebreak` tinyint(1) DEFAULT 0,
  `formfield_scored` tinyint(1) DEFAULT 0,
  `formfield_order` varchar(45) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_forms`
--

CREATE TABLE `assessment_forms` (
  `form_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `form_title` varchar(100) DEFAULT NULL,
  `form_desc` text DEFAULT NULL,
  `form_creator` varchar(30) DEFAULT NULL COMMENT 'creator username',
  `form_createdate` datetime DEFAULT NULL,
  `form_ispublished` tinyint(1) NOT NULL DEFAULT 0,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_series`
--

CREATE TABLE `assessment_series` (
  `assessmentseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT NULL COMMENT 'total number of files',
  `series_size` double DEFAULT NULL COMMENT 'size of all the files',
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `audio_series`
--

CREATE TABLE `audio_series` (
  `audioseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_size` double DEFAULT NULL,
  `series_notes` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT NULL,
  `audio_desc` text DEFAULT NULL,
  `audio_cputime` double DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `audit_enrollment`
--

CREATE TABLE `audit_enrollment` (
  `auditenrollment_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `audit_datetime` datetime DEFAULT NULL,
  `audit_message` text DEFAULT NULL,
  `audit_number` int(11) DEFAULT NULL,
  `audit_fixed` tinyint(1) DEFAULT NULL,
  `audit_fixeddate` datetime DEFAULT NULL,
  `audit_fixedby` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `audit_results`
--

CREATE TABLE `audit_results` (
  `auditresult_id` int(11) NOT NULL,
  `audit_num` int(11) DEFAULT NULL,
  `compare_direction` enum('dbtofile','filetodb','consistency','orphan') NOT NULL,
  `problem` enum('filecountmismatch','namemismatch','seriesdescmismatch','nonconsecutiveseries','orphan_noparentstudy','orphan_noparentsubject','subjectmissing','studymissing','seriesmissing','invalidprojectid','blankmodality','seriesdatatypemissing','dicommismatch') NOT NULL,
  `mismatch` varchar(255) DEFAULT NULL,
  `mismatchcount` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `study_id` int(11) DEFAULT NULL,
  `modality` varchar(50) DEFAULT NULL,
  `series_id` int(11) DEFAULT NULL,
  `subject_uid` varchar(20) DEFAULT NULL,
  `study_num` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `data_type` varchar(50) DEFAULT NULL,
  `file_numfiles` int(11) DEFAULT NULL,
  `db_numfiles` int(11) DEFAULT NULL,
  `file_string` varchar(255) DEFAULT NULL,
  `db_string` varchar(255) DEFAULT NULL,
  `audit_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `audit_series`
--

CREATE TABLE `audit_series` (
  `auditseries_id` int(11) NOT NULL,
  `series_id` int(11) DEFAULT NULL,
  `modality` varchar(50) DEFAULT NULL,
  `audit_datetime` datetime DEFAULT NULL,
  `audit_message` text DEFAULT NULL,
  `audit_number` int(11) DEFAULT NULL,
  `audit_fixed` tinyint(1) DEFAULT NULL,
  `audit_fixeddate` datetime DEFAULT NULL,
  `audit_fixedby` varchar(50) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `audit_study`
--

CREATE TABLE `audit_study` (
  `auditstudy_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `audit_datetime` datetime DEFAULT NULL,
  `audit_message` text DEFAULT NULL,
  `audit_number` int(11) DEFAULT NULL,
  `audit_fixed` tinyint(1) DEFAULT NULL,
  `audit_fixeddate` datetime DEFAULT NULL,
  `audit_fixedby` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `audit_subject`
--

CREATE TABLE `audit_subject` (
  `auditsubject_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `audit_datetime` datetime DEFAULT NULL,
  `audit_message` text DEFAULT NULL,
  `audit_number` int(11) DEFAULT NULL,
  `audit_fixed` tinyint(1) DEFAULT NULL,
  `audit_fixeddate` datetime DEFAULT NULL,
  `audit_fixedby` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `binary_series`
--

CREATE TABLE `binary_series` (
  `binaryseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_size` double DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT NULL,
  `series_description` varchar(255) DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `calendars`
--

CREATE TABLE `calendars` (
  `calendar_id` int(11) NOT NULL,
  `calendar_name` varchar(50) DEFAULT NULL,
  `calendar_description` varchar(255) DEFAULT NULL,
  `calendar_location` varchar(255) DEFAULT NULL COMMENT 'room #, etc',
  `calendar_createdate` datetime DEFAULT NULL,
  `calendar_deletedate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_allocations`
--

CREATE TABLE `calendar_allocations` (
  `alloc_id` int(11) NOT NULL,
  `alloc_timeperiod` int(50) DEFAULT NULL COMMENT 'yearly, monthly, weekly, daily',
  `alloc_calendarid` int(11) DEFAULT NULL,
  `alloc_projectid` int(11) DEFAULT NULL,
  `alloc_amount` int(11) DEFAULT NULL COMMENT 'number of allocations per time period'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_appointments`
--

CREATE TABLE `calendar_appointments` (
  `appt_id` int(11) NOT NULL,
  `appt_groupid` int(11) DEFAULT NULL,
  `appt_username` varchar(50) DEFAULT NULL,
  `appt_calendarid` int(11) DEFAULT NULL,
  `appt_projectid` int(11) DEFAULT NULL,
  `appt_title` varchar(250) DEFAULT NULL,
  `appt_details` text DEFAULT NULL,
  `appt_startdate` datetime DEFAULT NULL,
  `appt_enddate` datetime DEFAULT NULL,
  `appt_isalldayevent` tinyint(1) DEFAULT NULL,
  `appt_istimerequest` tinyint(1) DEFAULT NULL COMMENT 'true if the user is requesting a time slot that day',
  `appt_repeats` tinyint(1) DEFAULT NULL,
  `appt_deletedate` datetime NOT NULL DEFAULT '3000-01-01 00:00:00',
  `appt_canceldate` datetime NOT NULL DEFAULT '3000-01-01 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_notifications`
--

CREATE TABLE `calendar_notifications` (
  `not_id` int(11) NOT NULL,
  `not_userid` int(11) DEFAULT NULL,
  `not_calendarid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_projectnotifications`
--

CREATE TABLE `calendar_projectnotifications` (
  `not_id` int(11) NOT NULL,
  `not_userid` int(11) DEFAULT NULL,
  `not_projectid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_projects`
--

CREATE TABLE `calendar_projects` (
  `project_id` int(11) NOT NULL,
  `project_name` varchar(50) DEFAULT NULL,
  `project_admin` varchar(50) DEFAULT NULL,
  `project_description` varchar(255) DEFAULT NULL,
  `project_startdate` datetime DEFAULT NULL,
  `project_enddate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `changelog`
--

CREATE TABLE `changelog` (
  `changelog_id` int(11) NOT NULL,
  `performing_userid` int(11) DEFAULT NULL,
  `performing_username` varchar(50) DEFAULT NULL,
  `affected_userid` int(11) DEFAULT NULL,
  `affected_instanceid1` int(11) DEFAULT NULL,
  `affected_instanceid2` int(11) DEFAULT NULL,
  `affected_siteid1` int(11) DEFAULT NULL,
  `affected_siteid2` int(11) DEFAULT NULL,
  `affected_projectid1` int(11) DEFAULT NULL,
  `affected_projectid2` int(11) DEFAULT NULL,
  `affected_subjectid1` int(11) DEFAULT NULL,
  `affected_subjectid2` int(11) DEFAULT NULL,
  `affected_enrollmentid1` int(11) DEFAULT NULL,
  `affected_enrollmentid2` int(11) DEFAULT NULL,
  `affected_studyid1` int(11) DEFAULT NULL,
  `affected_studyid2` int(11) DEFAULT NULL,
  `affected_seriesid1` int(11) DEFAULT NULL,
  `affected_seriesid2` int(11) DEFAULT NULL,
  `change_datetime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `change_event` varchar(255) DEFAULT NULL,
  `change_desc` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `changelog_subject`
--

CREATE TABLE `changelog_subject` (
  `changelog_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `change_date` datetime NOT NULL,
  `changetype` enum('','delete','obliterate','move') NOT NULL,
  `uid` varchar(10) NOT NULL,
  `newuid` varchar(10) NOT NULL,
  `log` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `common`
--

CREATE TABLE `common` (
  `common_id` int(11) NOT NULL,
  `common_type` enum('number','file','text') NOT NULL,
  `common_group` varchar(100) DEFAULT NULL,
  `common_name` varchar(100) DEFAULT NULL,
  `common_desc` text DEFAULT NULL,
  `common_number` double DEFAULT NULL,
  `common_text` text DEFAULT NULL,
  `common_file` varchar(255) DEFAULT NULL,
  `common_size` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `consent_series`
--

CREATE TABLE `consent_series` (
  `consentseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT NULL COMMENT 'total number of files',
  `series_size` double DEFAULT NULL COMMENT 'size of all the files',
  `series_notes` varchar(255) DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ishidden` tinyint(1) DEFAULT NULL,
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `contact_id` int(11) NOT NULL,
  `contact_fullname` varchar(255) DEFAULT NULL,
  `contact_title` varchar(255) DEFAULT NULL,
  `contact_address1` varchar(255) DEFAULT NULL,
  `contact_address2` varchar(255) DEFAULT NULL,
  `contact_address3` varchar(255) DEFAULT NULL,
  `contact_city` varchar(255) DEFAULT NULL,
  `contact_state` varchar(255) DEFAULT NULL,
  `contact_zip` varchar(255) DEFAULT NULL,
  `contact_country` varchar(255) DEFAULT NULL,
  `contact_phone1` varchar(255) DEFAULT NULL,
  `contact_phone2` varchar(255) DEFAULT NULL,
  `contact_phone3` varchar(255) DEFAULT NULL,
  `contact_email1` varchar(255) DEFAULT NULL,
  `contact_email2` varchar(255) DEFAULT NULL,
  `contact_email3` varchar(255) DEFAULT NULL,
  `contact_website` varchar(255) DEFAULT NULL,
  `contact_company` varchar(255) DEFAULT NULL,
  `contact_department` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `cr_series`
--

CREATE TABLE `cr_series` (
  `crseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT NULL COMMENT 'total number of files',
  `series_size` double DEFAULT NULL COMMENT 'size of all the files',
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `series_status` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ct_series`
--

CREATE TABLE `ct_series` (
  `ctseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_contrastbolusagent` varchar(255) DEFAULT NULL,
  `series_bodypartexamined` varchar(255) DEFAULT NULL,
  `series_scanoptions` varchar(255) DEFAULT NULL,
  `series_spacingz` double DEFAULT NULL,
  `series_spacingx` double DEFAULT NULL,
  `series_spacingy` double DEFAULT NULL,
  `series_imgrows` int(11) DEFAULT NULL,
  `series_imgcols` int(11) DEFAULT NULL,
  `series_imgslices` int(11) DEFAULT NULL,
  `series_kvp` double DEFAULT NULL,
  `series_datacollectiondiameter` double DEFAULT NULL,
  `series_contrastbolusroute` varchar(255) DEFAULT NULL,
  `series_rotationdirection` varchar(10) DEFAULT NULL,
  `series_exposuretime` double DEFAULT NULL,
  `series_xraytubecurrent` double DEFAULT NULL,
  `series_filtertype` varchar(255) DEFAULT NULL,
  `series_generatorpower` double DEFAULT NULL,
  `series_convolutionkernel` varchar(255) DEFAULT NULL,
  `numfiles` int(11) DEFAULT NULL COMMENT 'total number of files',
  `series_datatype` varchar(50) DEFAULT NULL,
  `series_status` varchar(50) DEFAULT NULL,
  `series_size` double DEFAULT NULL COMMENT 'size of all the files',
  `series_numfiles` int(11) DEFAULT NULL,
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `data_requests`
--

CREATE TABLE `data_requests` (
  `request_id` int(11) NOT NULL,
  `req_username` varchar(50) DEFAULT NULL,
  `req_ip` varchar(30) DEFAULT NULL,
  `req_groupid` int(11) DEFAULT NULL,
  `req_pipelinedownloadid` int(11) DEFAULT NULL COMMENT 'filled if this is part of a pipeline download',
  `req_modality` varchar(20) DEFAULT NULL,
  `req_downloadimaging` tinyint(1) DEFAULT NULL,
  `req_downloadbeh` tinyint(1) DEFAULT NULL,
  `req_downloadqc` tinyint(1) DEFAULT NULL,
  `req_destinationtype` varchar(20) DEFAULT NULL COMMENT 'nfs, localftp, remoteftp',
  `req_nfsdir` varchar(255) DEFAULT NULL,
  `req_seriesid` int(11) DEFAULT NULL,
  `req_subjectprojectid` int(11) DEFAULT NULL,
  `req_filetype` varchar(20) DEFAULT NULL,
  `req_gzip` tinyint(1) DEFAULT NULL,
  `req_anonymize` int(11) DEFAULT NULL,
  `req_preserveseries` tinyint(1) DEFAULT NULL,
  `req_dirformat` varchar(50) DEFAULT NULL,
  `req_timepoint` int(11) DEFAULT NULL,
  `req_ftpusername` varchar(50) DEFAULT NULL,
  `req_ftppassword` varchar(50) DEFAULT NULL,
  `req_ftpserver` varchar(100) DEFAULT NULL,
  `req_ftpport` int(11) NOT NULL DEFAULT 21,
  `req_ftppath` varchar(255) DEFAULT NULL,
  `req_ftplog` text DEFAULT NULL,
  `req_nidbusername` varchar(255) DEFAULT NULL,
  `req_nidbpassword` varchar(255) DEFAULT NULL,
  `req_nidbserver` varchar(255) DEFAULT NULL,
  `req_nidbinstanceid` int(11) DEFAULT 0,
  `req_nidbsiteid` int(11) DEFAULT 0,
  `req_nidbprojectid` int(11) DEFAULT 0,
  `req_downloadid` int(11) DEFAULT NULL,
  `req_behonly` tinyint(1) DEFAULT NULL,
  `req_behformat` varchar(35) DEFAULT NULL,
  `req_behdirrootname` varchar(50) DEFAULT NULL,
  `req_behdirseriesname` varchar(255) DEFAULT NULL,
  `req_date` timestamp NULL DEFAULT NULL,
  `req_completedate` timestamp NULL DEFAULT NULL,
  `req_cputime` double DEFAULT NULL,
  `req_status` varchar(25) DEFAULT NULL,
  `req_results` text DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `drugnames`
--

CREATE TABLE `drugnames` (
  `drugname_id` int(11) NOT NULL,
  `drug_name` varchar(255) NOT NULL,
  `drug_group` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `drugs`
--

CREATE TABLE `drugs` (
  `drug_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `drug_startdate` datetime NOT NULL,
  `drug_enddate` datetime DEFAULT NULL,
  `drug_doseamount` varchar(255) DEFAULT NULL,
  `drug_dosefrequency` varchar(255) DEFAULT NULL,
  `drug_route` varchar(255) DEFAULT NULL COMMENT 'oral, iv, suppository, etc',
  `drugname_id` int(11) NOT NULL,
  `drug_type` varchar(255) DEFAULT NULL,
  `drug_dosekey` varchar(255) DEFAULT NULL,
  `drug_doseunit` varchar(255) DEFAULT NULL,
  `drug_frequencymodifier` enum('every','times') DEFAULT NULL,
  `drug_frequencyvalue` double DEFAULT NULL,
  `drug_frequencyunit` enum('bolus','dose','second','minute','hour','day','week','month','year') DEFAULT NULL,
  `drug_dosedesc` varchar(255) DEFAULT NULL,
  `drug_rater` varchar(255) DEFAULT NULL,
  `drug_notes` varchar(255) DEFAULT NULL,
  `drug_entrydate` datetime DEFAULT NULL,
  `drug_recordcreatedate` datetime DEFAULT NULL,
  `drug_recordmodifydate` datetime DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ecg_series`
--

CREATE TABLE `ecg_series` (
  `ecgseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT NULL COMMENT 'total number of files',
  `series_size` double DEFAULT NULL COMMENT 'size of all the files',
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `series_status` varchar(255) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `eeg_series`
--

CREATE TABLE `eeg_series` (
  `eegseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_altdesc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT 0 COMMENT 'total number of files',
  `series_size` double DEFAULT 0 COMMENT 'size of all the files',
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `series_status` varchar(255) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT 0,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `enroll_subgroup` varchar(50) DEFAULT NULL,
  `enroll_startdate` datetime DEFAULT NULL,
  `enroll_enddate` datetime DEFAULT NULL,
  `enroll_status` enum('enrolled','completed','excluded','') NOT NULL DEFAULT '',
  `irb_consent` blob DEFAULT NULL COMMENT 'scanned image of the IRB consent form',
  `lastupdate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_checklist`
--

CREATE TABLE `enrollment_checklist` (
  `enrollmentchecklist_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `projectchecklist_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_completed` datetime DEFAULT NULL,
  `completedby` varchar(255) DEFAULT NULL COMMENT 'username, not ID, in case the user_id is deleted'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_missingdata`
--

CREATE TABLE `enrollment_missingdata` (
  `missingdata_id` int(11) NOT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `projectchecklist_id` int(11) DEFAULT NULL,
  `missing_reason` varchar(255) DEFAULT NULL,
  `missingreason_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `error_log`
--

CREATE TABLE `error_log` (
  `errorlog_id` bigint(20) NOT NULL,
  `error_hostname` varchar(255) DEFAULT NULL,
  `error_type` enum('sql','php') DEFAULT NULL,
  `error_source` enum('web','backend') DEFAULT NULL,
  `error_module` varchar(255) DEFAULT NULL,
  `error_date` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `et_series`
--

CREATE TABLE `et_series` (
  `etseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_altdesc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT NULL COMMENT 'total number of files',
  `series_size` double DEFAULT NULL COMMENT 'size of all the files',
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `exports`
--

CREATE TABLE `exports` (
  `export_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `ip` varchar(30) DEFAULT NULL,
  `download_imaging` tinyint(1) DEFAULT NULL,
  `download_beh` tinyint(1) DEFAULT NULL,
  `download_qc` tinyint(1) DEFAULT NULL,
  `destinationtype` varchar(20) DEFAULT NULL COMMENT 'nfs, localftp, remoteftp',
  `filetype` varchar(20) DEFAULT NULL,
  `do_gzip` tinyint(1) DEFAULT NULL,
  `do_preserveseries` tinyint(1) DEFAULT NULL,
  `anonymization_level` int(11) DEFAULT NULL,
  `dirformat` varchar(50) DEFAULT NULL,
  `beh_format` varchar(35) DEFAULT NULL,
  `beh_dirrootname` varchar(50) DEFAULT NULL,
  `beh_dirseriesname` varchar(255) DEFAULT NULL,
  `nfsdir` varchar(255) DEFAULT NULL,
  `remoteftp_username` varchar(50) DEFAULT NULL,
  `remoteftp_password` varchar(50) DEFAULT NULL,
  `remoteftp_server` varchar(100) DEFAULT NULL,
  `remoteftp_port` int(11) NOT NULL DEFAULT 21,
  `remoteftp_path` varchar(255) DEFAULT NULL,
  `remoteftp_log` text DEFAULT NULL,
  `remotenidb_username` varchar(255) DEFAULT NULL,
  `remotenidb_password` varchar(255) DEFAULT NULL,
  `remotenidb_server` varchar(255) DEFAULT NULL,
  `remotenidb_instanceid` int(11) DEFAULT 0,
  `remotenidb_siteid` int(11) DEFAULT 0,
  `remotenidb_projectid` int(11) DEFAULT 0,
  `remotenidb_connectionid` int(11) DEFAULT NULL,
  `remotenidb_transactionid` int(11) DEFAULT NULL,
  `publicdownloadid` int(11) DEFAULT NULL,
  `bidsreadme` text DEFAULT NULL,
  `submitdate` datetime DEFAULT NULL,
  `startdate` datetime DEFAULT NULL,
  `completedate` datetime DEFAULT NULL,
  `cputime` double DEFAULT NULL,
  `status` enum('submitted','pending','processing','complete','error','cancelled','') NOT NULL DEFAULT '',
  `log` text DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `exportseries`
--

CREATE TABLE `exportseries` (
  `exportseries_id` int(11) NOT NULL,
  `export_id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `modality` varchar(25) NOT NULL DEFAULT '',
  `startdate` datetime DEFAULT NULL,
  `enddate` datetime DEFAULT NULL,
  `timepoint_label` varchar(100) DEFAULT NULL,
  `status` enum('','error','processing','complete','submitted','cancelled') NOT NULL DEFAULT '',
  `statusmessage` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `families`
--

CREATE TABLE `families` (
  `family_id` int(11) NOT NULL,
  `family_uid` varchar(10) DEFAULT NULL,
  `family_createdate` datetime DEFAULT NULL,
  `family_name` varchar(255) DEFAULT NULL,
  `family_isactive` tinyint(1) NOT NULL DEFAULT 1,
  `family_lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `family_members`
--

CREATE TABLE `family_members` (
  `familymember_id` int(11) NOT NULL,
  `family_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `fm_createdate` datetime DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `fileio_requests`
--

CREATE TABLE `fileio_requests` (
  `fileiorequest_id` int(11) NOT NULL,
  `fileio_operation` enum('copy','delete','move','detach','anonymize','createlinks','rearchive','rearchivesubject','rearchiveidonly','rearchivesubjectidonly','rechecksuccess') NOT NULL,
  `data_type` enum('pipeline','analysis','subject','study','series','groupanalysis') NOT NULL,
  `data_id` int(11) DEFAULT NULL,
  `data_destination` varchar(255) DEFAULT NULL,
  `rearchiveprojectid` int(11) DEFAULT NULL,
  `modality` varchar(50) DEFAULT NULL,
  `anonymize_fields` text DEFAULT NULL,
  `request_status` enum('pending','complete','error','cancelled') NOT NULL DEFAULT 'pending',
  `request_message` varchar(255) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `requestdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `startdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enddate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `merge_id` int(11) DEFAULT NULL,
  `merge_ids` varchar(255) DEFAULT NULL,
  `merge_name` varchar(255) DEFAULT NULL,
  `merge_dob` date DEFAULT NULL,
  `merge_sex` char(1) DEFAULT NULL,
  `merge_ethnicity1` enum('hispanic','nothispanic','') DEFAULT NULL,
  `merge_ethnicity2` set('asian','black','white','indian','islander','mixed','other','unknown') DEFAULT NULL,
  `merge_guid` varchar(50) DEFAULT NULL,
  `merge_enrollgroup` text DEFAULT NULL,
  `merge_altuids` text DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(255) DEFAULT NULL,
  `group_type` varchar(25) DEFAULT NULL COMMENT 'subject, study, series',
  `group_owner` int(11) DEFAULT NULL COMMENT 'user_id of the group owner',
  `instance_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `group_data`
--

CREATE TABLE `group_data` (
  `subjectgroup_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `data_id` int(11) DEFAULT NULL,
  `modality` varchar(10) DEFAULT NULL,
  `date_added` date DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gsr_series`
--

CREATE TABLE `gsr_series` (
  `gsrseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT NULL,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT NULL COMMENT 'total number of files',
  `series_size` double DEFAULT NULL COMMENT 'size of all the files',
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `importlogs`
--

CREATE TABLE `importlogs` (
  `importlog_id` bigint(20) NOT NULL,
  `filename_orig` text DEFAULT NULL,
  `filename_new` varchar(255) DEFAULT NULL,
  `fileformat` varchar(255) DEFAULT NULL,
  `importstartdate` datetime DEFAULT NULL,
  `result` text DEFAULT NULL,
  `importid` int(11) DEFAULT NULL,
  `importgroupid` int(11) DEFAULT NULL,
  `importsiteid` int(11) DEFAULT NULL,
  `importprojectid` int(11) DEFAULT NULL,
  `importpermanent` tinyint(1) DEFAULT NULL,
  `importanonymize` tinyint(1) DEFAULT NULL,
  `importuuid` varchar(255) DEFAULT NULL,
  `patientid_orig` varchar(50) DEFAULT NULL,
  `modality_orig` varchar(255) DEFAULT NULL,
  `patientname_orig` varchar(255) DEFAULT NULL,
  `patientdob_orig` varchar(255) DEFAULT NULL,
  `patientsex_orig` varchar(255) DEFAULT NULL,
  `stationname_orig` varchar(255) DEFAULT NULL,
  `institution_orig` varchar(255) DEFAULT NULL,
  `studydatetime_orig` varchar(255) DEFAULT NULL,
  `seriesdatetime_orig` varchar(255) DEFAULT NULL,
  `seriesnumber_orig` varchar(255) DEFAULT NULL,
  `studydesc_orig` varchar(255) DEFAULT NULL,
  `seriesdesc_orig` varchar(255) DEFAULT NULL,
  `protocol_orig` varchar(255) DEFAULT NULL,
  `patientage_orig` varchar(255) DEFAULT NULL,
  `slicenumber_orig` varchar(255) DEFAULT NULL,
  `instancenumber_orig` varchar(255) DEFAULT NULL,
  `slicelocation_orig` varchar(255) DEFAULT NULL,
  `acquisitiondatetime_orig` varchar(255) DEFAULT NULL,
  `contentdatetime_orig` varchar(255) DEFAULT NULL,
  `sopinstance_orig` varchar(255) DEFAULT NULL,
  `modality_new` varchar(255) DEFAULT NULL,
  `patientname_new` varchar(255) DEFAULT NULL,
  `patientdob_new` varchar(255) DEFAULT NULL,
  `patientsex_new` varchar(255) DEFAULT NULL,
  `stationname_new` varchar(255) DEFAULT NULL,
  `studydatetime_new` varchar(255) DEFAULT NULL,
  `seriesdatetime_new` varchar(255) DEFAULT NULL,
  `seriesnumber_new` varchar(255) DEFAULT NULL,
  `studydesc_new` varchar(255) DEFAULT NULL,
  `seriesdesc_new` varchar(255) DEFAULT NULL,
  `protocol_new` varchar(255) DEFAULT NULL,
  `patientage_new` varchar(255) DEFAULT NULL,
  `subject_uid` varchar(255) DEFAULT NULL,
  `study_num` int(11) DEFAULT NULL,
  `subjectid` int(11) DEFAULT NULL,
  `studyid` int(11) DEFAULT NULL,
  `seriesid` int(11) DEFAULT NULL,
  `enrollmentid` int(11) DEFAULT NULL,
  `project_number` varchar(255) DEFAULT NULL,
  `series_created` tinyint(1) DEFAULT NULL,
  `study_created` tinyint(1) DEFAULT NULL,
  `subject_created` tinyint(1) DEFAULT NULL,
  `family_created` tinyint(1) DEFAULT NULL,
  `enrollment_created` tinyint(1) DEFAULT NULL,
  `overwrote_existing` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;

-- --------------------------------------------------------

--
-- Table structure for table `import_requestdirs`
--

CREATE TABLE `import_requestdirs` (
  `importrequestdir_id` int(11) NOT NULL,
  `importrequest_id` int(11) DEFAULT NULL,
  `dir_num` int(11) DEFAULT NULL,
  `dir_type` enum('modality','seriesdesc','seriesnum','studydesc','studydatetime','thefiles','beh','subjectid') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `import_requests`
--

CREATE TABLE `import_requests` (
  `importrequest_id` int(11) NOT NULL,
  `import_transactionid` int(11) DEFAULT NULL,
  `import_datatype` varchar(255) DEFAULT NULL,
  `import_modality` varchar(50) DEFAULT NULL,
  `import_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `import_status` varchar(50) DEFAULT NULL,
  `import_message` varchar(255) DEFAULT NULL,
  `import_startdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `import_enddate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `import_equipment` varchar(255) DEFAULT NULL,
  `import_siteid` int(11) DEFAULT NULL,
  `import_projectid` int(11) DEFAULT NULL,
  `import_instanceid` int(11) DEFAULT NULL,
  `import_uuid` varchar(255) DEFAULT NULL,
  `import_subjectid` varchar(255) DEFAULT NULL,
  `import_anonymize` tinyint(1) DEFAULT NULL,
  `import_permanent` tinyint(1) DEFAULT NULL,
  `import_matchidonly` tinyint(1) DEFAULT NULL,
  `import_filename` varchar(255) DEFAULT NULL,
  `import_seriesnotes` text DEFAULT NULL,
  `import_altuids` text DEFAULT NULL,
  `import_userid` int(11) DEFAULT NULL,
  `import_fileisseries` tinyint(1) DEFAULT NULL COMMENT 'if each file should be its own series',
  `numfilestotal` int(11) DEFAULT NULL,
  `numfilessuccess` int(11) DEFAULT NULL,
  `numfilesfail` int(11) DEFAULT NULL,
  `numbehtotal` int(11) DEFAULT NULL,
  `numbehsuccess` int(11) DEFAULT NULL,
  `numbehfail` int(11) DEFAULT NULL,
  `uploadreport` mediumtext DEFAULT NULL,
  `archivereport` mediumtext DEFAULT NULL,
  `import_dob` date DEFAULT NULL,
  `import_sex` char(1) DEFAULT NULL,
  `import_age` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `import_transactions`
--

CREATE TABLE `import_transactions` (
  `importtrans_id` int(11) NOT NULL,
  `transaction_startdate` datetime DEFAULT NULL,
  `transaction_enddate` datetime DEFAULT NULL,
  `transaction_status` varchar(20) DEFAULT NULL,
  `transaction_source` varchar(255) DEFAULT NULL,
  `transaction_username` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `instance`
--

CREATE TABLE `instance` (
  `instance_id` int(11) NOT NULL,
  `instance_uid` varchar(25) NOT NULL,
  `instance_name` varchar(255) NOT NULL,
  `instance_ownerid` int(11) NOT NULL,
  `instance_default` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `instance_billing`
--

CREATE TABLE `instance_billing` (
  `billingitem_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `pricing_id` int(11) NOT NULL,
  `quantity` double NOT NULL,
  `bill_datestart` datetime NOT NULL,
  `bill_dateend` datetime NOT NULL,
  `bill_notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `instance_contact`
--

CREATE TABLE `instance_contact` (
  `instancecontact_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `instance_invoice`
--

CREATE TABLE `instance_invoice` (
  `invoice_id` int(11) NOT NULL,
  `invoice_number` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `invoice_date` datetime NOT NULL,
  `invoice_paid` tinyint(1) NOT NULL,
  `invoice_paiddate` datetime NOT NULL,
  `invoice_paymethod` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `instance_pricing`
--

CREATE TABLE `instance_pricing` (
  `pricing_id` int(11) NOT NULL,
  `pricing_startdate` datetime NOT NULL,
  `pricing_enddate` datetime NOT NULL,
  `pricing_itemname` varchar(255) NOT NULL,
  `pricing_unit` varchar(255) NOT NULL,
  `pricing_price` double NOT NULL,
  `pricing_comments` text NOT NULL,
  `pricing_internal` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `instance_usage`
--

CREATE TABLE `instance_usage` (
  `instanceusage_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `usage_date` date NOT NULL,
  `pricing_id` int(11) NOT NULL,
  `usage_amount` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `manual_qa`
--

CREATE TABLE `manual_qa` (
  `manualqa_id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `modality` varchar(10) NOT NULL,
  `rater_id` int(11) NOT NULL,
  `value` int(11) NOT NULL COMMENT '0,1, or 2'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `measureinstruments`
--

CREATE TABLE `measureinstruments` (
  `measureinstrument_id` int(11) NOT NULL,
  `instrument_name` varchar(255) NOT NULL,
  `instrument_group` varchar(255) NOT NULL,
  `instrument_notes` text NOT NULL COMMENT 'mostly used for coding instructions (1=female, 2=male, etc)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `measurenames`
--

CREATE TABLE `measurenames` (
  `measurename_id` int(11) NOT NULL,
  `measure_name` varchar(255) DEFAULT NULL,
  `measure_group` varchar(255) DEFAULT NULL,
  `measure_multiple` tinyint(1) DEFAULT NULL COMMENT 'Indicates if a measure can have more than one entry',
  `measure_notes` text DEFAULT NULL COMMENT 'mostly used for coding instructions (1=female, 2=male, etc)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `measures`
--

CREATE TABLE `measures` (
  `measure_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `measure_dateentered` timestamp NULL DEFAULT NULL,
  `instrumentname_id` int(11) DEFAULT NULL,
  `measurename_id` int(11) NOT NULL,
  `measure_type` enum('s','n') DEFAULT NULL,
  `measure_valuestring` varchar(255) DEFAULT NULL,
  `measure_valuenum` double DEFAULT NULL,
  `measure_notes` text DEFAULT NULL,
  `measure_instrument` varchar(255) DEFAULT NULL,
  `measure_desc` varchar(255) DEFAULT NULL,
  `measure_rater` varchar(50) DEFAULT NULL,
  `measure_datecomplete` timestamp NULL DEFAULT NULL,
  `measure_lastupdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `study_id` int(11) DEFAULT NULL,
  `series_id` int(11) DEFAULT NULL,
  `measure_value` varchar(255) NOT NULL,
  `measure_startdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `measure_enddate` datetime DEFAULT NULL,
  `measure_duration` int(11) DEFAULT NULL,
  `measure_entrydate` datetime DEFAULT NULL,
  `measure_createdate` datetime DEFAULT NULL,
  `measure_modifydate` datetime DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `minipipelines`
--

CREATE TABLE `minipipelines` (
  `minipipeline_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL DEFAULT -1,
  `mp_version` int(11) NOT NULL DEFAULT 0,
  `mp_name` varchar(255) DEFAULT NULL,
  `mp_modifydate` datetime DEFAULT NULL,
  `mp_createdate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `minipipeline_jobs`
--

CREATE TABLE `minipipeline_jobs` (
  `minipipelinejob_id` bigint(20) NOT NULL,
  `minipipeline_id` int(11) DEFAULT NULL,
  `mp_modality` varchar(50) DEFAULT NULL,
  `mp_seriesid` int(11) DEFAULT NULL,
  `mp_status` enum('','pending','running','error','complete') DEFAULT '',
  `mp_log` longtext NOT NULL DEFAULT '',
  `mp_numinserts` int(11) DEFAULT NULL,
  `mp_queuedate` datetime DEFAULT NULL,
  `mp_startdate` datetime DEFAULT NULL,
  `mp_enddate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `minipipeline_scripts`
--

CREATE TABLE `minipipeline_scripts` (
  `minipipelinescript_id` int(11) NOT NULL,
  `minipipeline_id` int(11) NOT NULL,
  `mp_version` int(11) NOT NULL DEFAULT 0,
  `mp_executable` tinyint(1) DEFAULT 0,
  `mp_entrypoint` tinyint(1) DEFAULT 0,
  `mp_scriptname` varchar(255) DEFAULT NULL,
  `mp_script` longblob DEFAULT NULL,
  `mp_scriptsize` int(10) UNSIGNED DEFAULT NULL,
  `mp_parameterlist` text DEFAULT NULL,
  `mp_scriptmodifydate` datetime DEFAULT NULL,
  `mp_scriptcreatedate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `modalities`
--

CREATE TABLE `modalities` (
  `mod_id` int(11) NOT NULL,
  `mod_code` varchar(15) NOT NULL,
  `mod_desc` varchar(255) NOT NULL,
  `mod_enabled` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `modality_protocol`
--

CREATE TABLE `modality_protocol` (
  `modality` varchar(10) NOT NULL,
  `protocol` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL,
  `module_name` varchar(200) NOT NULL,
  `module_status` varchar(25) NOT NULL,
  `module_numrunning` int(11) NOT NULL DEFAULT 0,
  `module_laststart` datetime NOT NULL,
  `module_laststop` datetime NOT NULL,
  `module_isactive` tinyint(1) NOT NULL,
  `module_debug` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `module_prefs`
--

CREATE TABLE `module_prefs` (
  `mp_id` int(11) NOT NULL,
  `mp_module` varchar(50) NOT NULL,
  `mp_pref` varchar(255) NOT NULL,
  `mp_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `module_procs`
--

CREATE TABLE `module_procs` (
  `moduleproc_id` int(11) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `process_id` int(11) NOT NULL,
  `last_checkin` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `mostrecent`
--

CREATE TABLE `mostrecent` (
  `mostrecent_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `study_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `pipeline_id` int(11) DEFAULT NULL,
  `mostrecent_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `mr_qa`
--

CREATE TABLE `mr_qa` (
  `mrqa_id` int(11) NOT NULL,
  `mrseries_id` int(11) DEFAULT NULL,
  `io_snr` double DEFAULT NULL,
  `pv_snr` double DEFAULT NULL,
  `move_minx` double DEFAULT NULL,
  `move_miny` double DEFAULT NULL,
  `move_minz` double DEFAULT NULL,
  `move_maxx` double DEFAULT NULL,
  `move_maxy` double DEFAULT NULL,
  `move_maxz` double DEFAULT NULL,
  `acc_minx` double DEFAULT NULL,
  `acc_miny` double DEFAULT NULL,
  `acc_minz` double DEFAULT NULL,
  `acc_maxx` double DEFAULT NULL,
  `acc_maxy` double DEFAULT NULL,
  `acc_maxz` double DEFAULT NULL,
  `rot_minp` double DEFAULT NULL,
  `rot_minr` double DEFAULT NULL,
  `rot_miny` double DEFAULT NULL,
  `rot_maxp` double DEFAULT NULL,
  `rot_maxr` double DEFAULT NULL,
  `rot_maxy` double DEFAULT NULL,
  `motion_rsq` double DEFAULT NULL,
  `cputime` double DEFAULT NULL,
  `status` varchar(25) NOT NULL DEFAULT '',
  `lastupdate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mr_qcparams`
--

CREATE TABLE `mr_qcparams` (
  `mrqcparam_id` int(11) NOT NULL,
  `protocol_name` varchar(255) DEFAULT NULL,
  `project_id` int(11) NOT NULL,
  `max_x` int(11) NOT NULL,
  `max_y` int(11) NOT NULL,
  `max_z` int(11) NOT NULL,
  `min_iosnr` int(11) NOT NULL,
  `max_iosnr` int(11) NOT NULL,
  `min_pvsnr` int(11) NOT NULL,
  `max_pvsnr` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `mr_scanparams`
--

CREATE TABLE `mr_scanparams` (
  `mrscanparam_id` int(11) NOT NULL,
  `protocol_name` varchar(255) NOT NULL,
  `sequence_name` varchar(255) NOT NULL,
  `project_id` int(11) NOT NULL,
  `tr_min` double NOT NULL,
  `tr_max` double NOT NULL,
  `te_min` double NOT NULL,
  `te_max` double NOT NULL,
  `ti_min` double NOT NULL,
  `ti_max` double NOT NULL,
  `flip_min` double NOT NULL,
  `flip_max` double NOT NULL,
  `xdim_min` double NOT NULL COMMENT 'in voxels',
  `xdim_max` double NOT NULL COMMENT 'in voxels',
  `ydim_min` double NOT NULL COMMENT 'in voxels',
  `ydim_max` double NOT NULL COMMENT 'in voxels',
  `zdim_min` double NOT NULL COMMENT 'in voxels',
  `zdim_max` double NOT NULL COMMENT 'in voxels',
  `tdim_min` double NOT NULL COMMENT 'in bold reps',
  `tdim_max` double NOT NULL COMMENT 'in bold reps',
  `slicethickness_min` double NOT NULL,
  `slicethickness_max` double NOT NULL,
  `slicespacing_min` double NOT NULL,
  `slicespacing_max` double NOT NULL,
  `bandwidth_min` double NOT NULL,
  `bandwidth_max` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `mr_series`
--

CREATE TABLE `mr_series` (
  `mrseries_id` int(11) NOT NULL,
  `study_id` int(11) NOT NULL,
  `series_datetime` datetime DEFAULT NULL COMMENT '(0008,0021) & (0008,0031)',
  `series_desc` varchar(255) DEFAULT NULL COMMENT 'MP Rage, AOD, etc(0018,1030)',
  `series_altdesc` varchar(255) DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_sequencename` varchar(45) DEFAULT NULL COMMENT 'epfid2d1_64, etc(0018,0024)',
  `series_num` int(11) DEFAULT NULL,
  `series_tr` double DEFAULT NULL COMMENT '(0018,0080)',
  `series_te` double DEFAULT NULL COMMENT '(0018,0081)',
  `series_ti` double NOT NULL DEFAULT 0,
  `series_flip` double DEFAULT NULL COMMENT '(0018,1314)',
  `percent_sampling` double DEFAULT NULL,
  `percent_phaseFOV` double DEFAULT NULL,
  `phaseencodedir` varchar(20) DEFAULT NULL COMMENT 'either ROW or COL. when combined with phaseencodeangle, it will give the A>P, R>L etc',
  `phaseencodeangle` double DEFAULT NULL COMMENT 'in radians',
  `PhaseEncodingDirectionPositive` tinyint(1) DEFAULT NULL,
  `series_spacingx` double DEFAULT NULL COMMENT '(0028,0030) field 1',
  `series_spacingy` double DEFAULT NULL COMMENT '(0028,0030) field 2',
  `series_spacingz` double DEFAULT NULL COMMENT '(0018,0050)',
  `series_fieldstrength` double DEFAULT NULL COMMENT '(0018,0087)',
  `acq_matrix` varchar(20) DEFAULT NULL COMMENT '(0018,1310)',
  `img_rows` int(11) DEFAULT NULL COMMENT '(0028,0010)',
  `img_cols` int(11) DEFAULT NULL COMMENT '(0028,0011)',
  `img_slices` int(11) DEFAULT NULL COMMENT 'often derived from the number of dicom files',
  `slicethickness` double NOT NULL DEFAULT 0,
  `slicespacing` double DEFAULT NULL,
  `dimN` int(11) NOT NULL DEFAULT 0 COMMENT 'from fslval dim0',
  `dimX` int(11) NOT NULL DEFAULT 0 COMMENT 'from fslval dim1',
  `dimY` int(11) NOT NULL DEFAULT 0 COMMENT 'from fslval dim2',
  `dimZ` int(11) NOT NULL DEFAULT 0 COMMENT 'from fslval dim3',
  `dimT` int(11) NOT NULL DEFAULT 0 COMMENT 'from fslval dim4',
  `bandwidth` double NOT NULL DEFAULT 0,
  `image_type` varchar(255) DEFAULT NULL,
  `image_comments` varchar(255) DEFAULT NULL,
  `bold_reps` int(11) NOT NULL DEFAULT 0,
  `numfiles` int(11) DEFAULT NULL,
  `series_size` double NOT NULL DEFAULT 0 COMMENT 'number of bytes',
  `data_type` varchar(20) NOT NULL,
  `is_derived` tinyint(1) NOT NULL DEFAULT 0,
  `numfiles_beh` int(11) NOT NULL DEFAULT 0,
  `beh_size` double NOT NULL DEFAULT 0,
  `series_notes` text DEFAULT NULL,
  `series_status` varchar(20) DEFAULT NULL COMMENT 'pending, processing, complete',
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) NOT NULL DEFAULT 0,
  `series_createdate` datetime DEFAULT NULL,
  `lastupdate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mr_studyqa`
--

CREATE TABLE `mr_studyqa` (
  `mrstudyqa_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `t1_numcompared` int(11) NOT NULL,
  `t1_comparedseriesids` text NOT NULL,
  `t1_derivedseriesid` int(11) NOT NULL,
  `t1_comparisonmatrix` text NOT NULL,
  `t1_matrixremovethreshold` double NOT NULL,
  `t1_snrremovethreshold` double NOT NULL,
  `cputime` double DEFAULT NULL,
  `lastupdate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `nidb_sites`
--

CREATE TABLE `nidb_sites` (
  `site_id` int(11) NOT NULL,
  `site_uid` varchar(20) DEFAULT NULL,
  `site_uuid` varchar(255) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `site_address` varchar(255) DEFAULT NULL,
  `site_contact` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `nm_series`
--

CREATE TABLE `nm_series` (
  `nmseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT 0,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT 0 COMMENT 'total number of files',
  `series_size` double DEFAULT 0 COMMENT 'size of all the files',
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notiftype_id` int(11) NOT NULL,
  `notiftype_name` varchar(255) NOT NULL,
  `notiftype_desc` text NOT NULL,
  `notiftype_needproject` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `notification_user`
--

CREATE TABLE `notification_user` (
  `notif_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `notiftype_id` int(11) NOT NULL,
  `notif_frequency` enum('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'weekly'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `ot_series`
--

CREATE TABLE `ot_series` (
  `otseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL COMMENT '(0008,0021) & (0008,0031)',
  `series_desc` varchar(100) DEFAULT NULL COMMENT 'MP Rage, AOD, etc\n(0018,1030)',
  `series_sequencename` varchar(45) DEFAULT NULL COMMENT 'epfid2d1_64, etc\n(0018,0024)',
  `series_num` int(11) DEFAULT NULL,
  `series_spacingx` double DEFAULT NULL COMMENT '(0028,0030) field 1',
  `series_spacingy` double DEFAULT NULL COMMENT '(0028,0030) field 2',
  `series_spacingz` double DEFAULT NULL COMMENT '(0018,0050)',
  `img_rows` int(11) DEFAULT NULL COMMENT '(0028,0010)',
  `img_cols` int(11) DEFAULT NULL COMMENT '(0028,0011)',
  `img_slices` int(11) DEFAULT NULL COMMENT 'often derived from the number of dicom files',
  `numfiles` int(11) DEFAULT NULL,
  `series_numfiles` int(11) NOT NULL DEFAULT 1,
  `bold_reps` int(11) NOT NULL DEFAULT 0,
  `modality` varchar(50) DEFAULT NULL,
  `data_type` varchar(255) DEFAULT NULL,
  `series_size` double NOT NULL DEFAULT 0 COMMENT 'number of bytes',
  `series_status` varchar(20) DEFAULT NULL COMMENT 'pending, processing, complete',
  `series_notes` varchar(255) DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) NOT NULL DEFAULT 0,
  `lastupdate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pipelines`
--

CREATE TABLE `pipelines` (
  `pipeline_id` int(11) NOT NULL,
  `pipeline_name` varchar(50) NOT NULL,
  `pipeline_desc` varchar(255) DEFAULT NULL,
  `pipeline_admin` int(25) DEFAULT NULL COMMENT 'username',
  `pipeline_createdate` datetime DEFAULT NULL,
  `pipeline_level` int(11) DEFAULT NULL COMMENT '1,2,3, N (first, second, third, Nth level)',
  `pipeline_group` varchar(255) DEFAULT NULL,
  `pipeline_directory` varchar(255) DEFAULT NULL,
  `pipeline_dirstructure` char(1) DEFAULT NULL,
  `pipeline_usetmpdir` tinyint(1) DEFAULT NULL,
  `pipeline_tmpdir` text DEFAULT NULL,
  `pipeline_dependency` text DEFAULT NULL,
  `pipeline_dependencylevel` varchar(255) DEFAULT NULL,
  `pipeline_dependencydir` enum('','root','subdir') DEFAULT NULL,
  `pipeline_deplinktype` varchar(25) DEFAULT NULL,
  `pipeline_groupid` text DEFAULT NULL,
  `pipeline_grouptype` varchar(25) DEFAULT NULL,
  `pipeline_groupbysubject` tinyint(1) NOT NULL DEFAULT 0,
  `pipeline_projectid` text DEFAULT NULL,
  `pipeline_dynamicgroupid` int(11) DEFAULT NULL,
  `pipeline_status` varchar(20) DEFAULT NULL,
  `pipeline_statusmessage` varchar(255) DEFAULT NULL,
  `pipeline_laststart` datetime DEFAULT NULL,
  `pipeline_lastfinish` datetime DEFAULT NULL,
  `pipeline_lastcheck` datetime DEFAULT NULL,
  `pipeline_completefiles` text DEFAULT NULL COMMENT 'comma separated list of files to check to assume the analysis is complete',
  `pipeline_numproc` int(11) DEFAULT NULL COMMENT 'number of concurrent jobs allowed to run',
  `pipeline_queue` varchar(255) DEFAULT NULL,
  `pipeline_submithost` varchar(255) DEFAULT NULL,
  `pipeline_clustertype` enum('','sge','slurm') DEFAULT NULL,
  `pipeline_clusteruser` varchar(255) DEFAULT NULL,
  `pipeline_maxwalltime` bigint(20) DEFAULT NULL COMMENT 'maximum wall execution time in minutes',
  `pipeline_submitdelay` int(11) DEFAULT NULL COMMENT 'delay after studydatetime in hours',
  `pipeline_datacopymethod` varchar(50) DEFAULT NULL,
  `pipeline_notes` text DEFAULT NULL,
  `pipeline_useprofile` tinyint(1) DEFAULT NULL,
  `pipeline_removedata` tinyint(1) DEFAULT NULL,
  `pipeline_resultsscript` text DEFAULT NULL,
  `pipeline_enabled` tinyint(1) DEFAULT 0,
  `pipeline_testing` tinyint(1) DEFAULT NULL,
  `pipeline_isprivate` tinyint(1) DEFAULT NULL,
  `pipeline_ishidden` tinyint(1) DEFAULT NULL,
  `pipeline_version` int(11) DEFAULT 1,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_data`
--

CREATE TABLE `pipeline_data` (
  `pipelinedata_id` bigint(20) NOT NULL,
  `analysis_id` bigint(20) NOT NULL,
  `pd_modality` varchar(255) DEFAULT NULL,
  `pd_checked` tinyint(1) DEFAULT NULL,
  `pd_found` tinyint(1) DEFAULT NULL,
  `pd_seriesid` int(11) DEFAULT NULL,
  `pd_downloadpath` text DEFAULT NULL,
  `pd_step` int(11) DEFAULT NULL,
  `pd_msg` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_data_def`
--

CREATE TABLE `pipeline_data_def` (
  `pipelinedatadef_id` int(11) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT 0,
  `pdd_order` int(11) NOT NULL,
  `pdd_seriescriteria` enum('all','first','last','largestsize','smallestsize','highestiosnr','highestpvsnr','earliest','latest','usesizecriteria') NOT NULL DEFAULT 'all',
  `pdd_type` enum('primary','associated') NOT NULL DEFAULT 'primary',
  `pdd_level` enum('study','subject') NOT NULL,
  `pdd_assoctype` enum('nearesttime','samestudytype','nearestintime','') NOT NULL,
  `pdd_protocol` text NOT NULL,
  `pdd_imagetype` varchar(255) NOT NULL,
  `pdd_modality` varchar(255) NOT NULL,
  `pdd_dataformat` varchar(30) NOT NULL,
  `pdd_gzip` tinyint(1) NOT NULL DEFAULT 0,
  `pdd_location` varchar(255) NOT NULL COMMENT 'path to the data, relative to the root subject directory',
  `pdd_useseries` tinyint(1) NOT NULL,
  `pdd_preserveseries` tinyint(1) NOT NULL,
  `pdd_usephasedir` tinyint(1) NOT NULL,
  `pdd_behformat` varchar(50) NOT NULL,
  `pdd_behdir` varchar(255) NOT NULL,
  `pdd_enabled` tinyint(1) NOT NULL,
  `pdd_optional` tinyint(1) NOT NULL,
  `pdd_numboldreps` varchar(255) NOT NULL,
  `pdd_isprimaryprotocol` tinyint(1) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_dependencies`
--

CREATE TABLE `pipeline_dependencies` (
  `pipelinedep_id` int(11) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_download`
--

CREATE TABLE `pipeline_download` (
  `pipelinedownload_id` int(11) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `pd_admin` int(11) NOT NULL,
  `pd_protocol` varchar(255) NOT NULL,
  `pd_dirformat` varchar(50) NOT NULL,
  `pd_nfsdir` text NOT NULL,
  `pd_anonymize` tinyint(1) NOT NULL,
  `pd_gzip` tinyint(1) NOT NULL,
  `pd_preserveseries` tinyint(1) NOT NULL,
  `pd_groupbyprotocol` tinyint(1) NOT NULL COMMENT 'example: all GO1 series are in a group',
  `pd_onlynew` tinyint(1) NOT NULL COMMENT 'only download data collected after this rule was created',
  `pd_filetype` varchar(20) NOT NULL,
  `pd_modality` varchar(20) NOT NULL,
  `pd_behformat` varchar(25) NOT NULL,
  `pd_behdirrootname` varchar(50) NOT NULL,
  `pd_createdate` datetime NOT NULL,
  `pd_status` varchar(25) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_groups`
--

CREATE TABLE `pipeline_groups` (
  `pipelinegroup_id` int(11) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_history`
--

CREATE TABLE `pipeline_history` (
  `analysis_id` bigint(20) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `pipeline_version` int(11) NOT NULL,
  `study_id` int(11) NOT NULL,
  `analysis_event` varchar(100) NOT NULL,
  `analysis_datetime` timestamp NULL DEFAULT NULL,
  `analysis_hostname` varchar(100) NOT NULL,
  `event_message` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_options`
--

CREATE TABLE `pipeline_options` (
  `pipelineoptions_id` int(11) NOT NULL,
  `pipeline_id` int(11) DEFAULT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT 0,
  `pipeline_dependency` text DEFAULT NULL,
  `pipeline_dependencylevel` varchar(255) DEFAULT NULL,
  `pipeline_dependencydir` enum('','root','subdir') DEFAULT NULL,
  `pipeline_deplinktype` varchar(25) DEFAULT NULL,
  `pipeline_groupid` text DEFAULT NULL,
  `pipeline_grouptype` varchar(25) DEFAULT NULL,
  `pipeline_groupbysubject` tinyint(1) DEFAULT NULL,
  `pipeline_projectid` int(11) DEFAULT NULL,
  `pipeline_dynamicgroupid` int(11) DEFAULT NULL,
  `pipeline_completefiles` text DEFAULT NULL,
  `pipeline_resultsscript` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_procs`
--

CREATE TABLE `pipeline_procs` (
  `pp_processid` int(11) NOT NULL,
  `pp_status` varchar(50) NOT NULL,
  `pp_startdate` datetime NOT NULL,
  `pp_lastcheckin` datetime NOT NULL,
  `pp_currentpipeline` int(11) NOT NULL,
  `pp_currentsubject` int(11) NOT NULL,
  `pp_currentstudy` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_status`
--

CREATE TABLE `pipeline_status` (
  `pipelinestatus_id` int(11) NOT NULL,
  `pipeline_modulerunnum` bigint(20) NOT NULL,
  `pipeline_modulestarttime` datetime NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `pipelinestatus_starttime` datetime NOT NULL,
  `pipelinestatus_stoptime` datetime NOT NULL,
  `pipelinestatus_order` int(11) NOT NULL,
  `pipelinestatus_status` enum('pending','complete','running') NOT NULL,
  `pipelinestatus_result` text NOT NULL,
  `pipelinestatus_lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_steps`
--

CREATE TABLE `pipeline_steps` (
  `pipelinestep_id` int(11) NOT NULL,
  `pipeline_id` int(11) DEFAULT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT 1,
  `ps_supplement` tinyint(1) NOT NULL,
  `ps_command` text DEFAULT NULL,
  `ps_workingdir` text DEFAULT NULL,
  `ps_order` int(11) DEFAULT NULL,
  `ps_description` varchar(255) DEFAULT NULL,
  `ps_enabled` tinyint(1) NOT NULL,
  `ps_logged` tinyint(1) NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pipeline_version`
--

CREATE TABLE `pipeline_version` (
  `pipelineversion_id` int(11) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `version_datetime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `version_notes` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `ppi_series`
--

CREATE TABLE `ppi_series` (
  `ppiseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` varchar(255) NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `ishidden` tinyint(1) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL DEFAULT 0,
  `project_uid` varchar(20) DEFAULT NULL,
  `project_usecustomid` tinyint(1) DEFAULT 0 COMMENT '1 - uses custom IDs, 2 - uses NiDB UIDs',
  `project_name` varchar(60) NOT NULL,
  `project_admin` int(11) DEFAULT NULL,
  `project_pi` int(11) DEFAULT NULL,
  `project_sharing` char(1) DEFAULT NULL COMMENT 'F = full sharing, access to data\nV = view subjects, experiments, studies only\nP = private, no data seen by others',
  `project_costcenter` varchar(45) DEFAULT NULL,
  `project_startdate` date DEFAULT NULL,
  `project_enddate` date DEFAULT NULL,
  `project_irbapprovaldate` date DEFAULT NULL,
  `project_status` varchar(15) DEFAULT NULL,
  `lastupdate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `redcap_token` varchar(255) DEFAULT NULL,
  `redcap_server` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='System can have multiple projects. There must be 1 project a' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `project_checklist`
--

CREATE TABLE `project_checklist` (
  `projectchecklist_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `item_name` varchar(50) NOT NULL,
  `item_desc` text NOT NULL,
  `item_order` int(11) NOT NULL,
  `modality` varchar(25) NOT NULL COMMENT 'MR, CT, assessment, measure, etc',
  `protocol_name` text NOT NULL COMMENT 'for a specific modality, this specifies the protocol name',
  `count` int(11) NOT NULL COMMENT 'total number of this item',
  `frequency` int(11) NOT NULL COMMENT 'spacing between the items',
  `frequency_unit` enum('hour','day','week','month','year') NOT NULL,
  `rdocexperiment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `project_protocol`
--

CREATE TABLE `project_protocol` (
  `projectprotocol_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `protocolgroup_id` int(11) NOT NULL,
  `pp_criteria` enum('required','recommended','conditional','') NOT NULL,
  `pp_perstudyquantity` int(11) NOT NULL,
  `pp_perprojectquantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `project_template`
--

CREATE TABLE `project_template` (
  `projecttemplate_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `template_createdate` datetime DEFAULT NULL,
  `template_modifydate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `project_templatestudies`
--

CREATE TABLE `project_templatestudies` (
  `pts_id` int(11) NOT NULL,
  `pt_id` int(11) NOT NULL,
  `pts_order` int(11) DEFAULT NULL,
  `pts_visittype` varchar(255) DEFAULT NULL,
  `pts_modality` varchar(255) DEFAULT NULL,
  `pts_desc` varchar(255) DEFAULT NULL,
  `pts_operator` varchar(255) DEFAULT NULL,
  `pts_physician` varchar(255) DEFAULT NULL,
  `pts_site` varchar(255) DEFAULT NULL,
  `pts_notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `project_templatestudyitems`
--

CREATE TABLE `project_templatestudyitems` (
  `ptsitem_id` int(11) NOT NULL,
  `pts_id` int(11) DEFAULT NULL,
  `ptsitem_order` int(11) DEFAULT NULL,
  `ptsitem_protocol` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `protocolgroup_items`
--

CREATE TABLE `protocolgroup_items` (
  `pgitem_id` int(11) NOT NULL,
  `protocolgroup_id` int(11) NOT NULL,
  `pgitem_protocol` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `protocol_group`
--

CREATE TABLE `protocol_group` (
  `protocolgroup_id` int(11) NOT NULL,
  `protocolgroup_name` varchar(50) NOT NULL,
  `protocolgroup_modality` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='specifies the protocol group name and modality' ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pr_series`
--

CREATE TABLE `pr_series` (
  `prseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `ishidden` tinyint(1) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `public_downloads`
--

CREATE TABLE `public_downloads` (
  `pd_id` int(11) NOT NULL,
  `pd_createdate` datetime DEFAULT NULL,
  `pd_expiredate` datetime DEFAULT NULL,
  `pd_expiredays` int(11) DEFAULT NULL,
  `pd_createdby` varchar(50) NOT NULL COMMENT 'userid of the owner',
  `pd_zippedsize` double DEFAULT 0,
  `pd_unzippedsize` double DEFAULT 0,
  `pd_filename` varchar(255) DEFAULT NULL,
  `pd_desc` varchar(255) DEFAULT NULL,
  `pd_notes` text DEFAULT NULL,
  `pd_filecontents` longtext DEFAULT NULL,
  `pd_shareinternal` tinyint(1) DEFAULT NULL,
  `pd_ispublic` tinyint(1) DEFAULT NULL,
  `pd_registerrequired` tinyint(1) DEFAULT NULL,
  `pd_password` varchar(255) DEFAULT NULL,
  `pd_status` varchar(50) DEFAULT NULL,
  `pd_key` varchar(255) DEFAULT NULL,
  `pd_numdownloads` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `qc_modules`
--

CREATE TABLE `qc_modules` (
  `qcmodule_id` int(11) NOT NULL,
  `qcm_modality` varchar(20) NOT NULL,
  `qcm_name` varchar(255) NOT NULL COMMENT 'full name of the module in the qcmodules directory',
  `qcm_isenabled` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `qc_moduleseries`
--

CREATE TABLE `qc_moduleseries` (
  `qcmoduleseries_id` int(11) NOT NULL,
  `qcmodule_id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `modality` varchar(25) NOT NULL,
  `cpu_time` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `qc_resultnames`
--

CREATE TABLE `qc_resultnames` (
  `qcresultname_id` int(11) NOT NULL,
  `qcresult_name` varchar(255) NOT NULL DEFAULT '',
  `qcresult_type` enum('graph','image','histogram','minmax','number','textfile') NOT NULL DEFAULT 'number',
  `qcresult_units` varchar(255) NOT NULL DEFAULT 'unitless',
  `qcresult_labels` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `qc_results`
--

CREATE TABLE `qc_results` (
  `qcresults_id` int(11) NOT NULL,
  `qcmoduleseries_id` int(11) NOT NULL,
  `qcresultname_id` int(11) NOT NULL,
  `qcresults_valuenumber` double DEFAULT NULL,
  `qcresults_valuetext` blob NOT NULL,
  `qcresults_valuefile` varchar(255) DEFAULT NULL,
  `qcresults_datetime` datetime DEFAULT NULL,
  `qcresults_cputime` double NOT NULL DEFAULT 0
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL,
  `rater_id` int(11) NOT NULL,
  `data_id` int(11) NOT NULL,
  `data_modality` varchar(50) NOT NULL,
  `rating_type` varchar(50) NOT NULL COMMENT 'subject, study, series, analysis',
  `rating_value` int(11) NOT NULL,
  `rating_notes` text NOT NULL,
  `rating_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `rdoc_uploads`
--

CREATE TABLE `rdoc_uploads` (
  `rdocupload_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `modality` varchar(20) NOT NULL,
  `series_id` int(11) NOT NULL,
  `dateuploaded` datetime NOT NULL,
  `label` varchar(255) NOT NULL,
  `iscomplete` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `redcap_import_fields`
--

CREATE TABLE `redcap_import_fields` (
  `redcap_fieldgroupid` int(11) NOT NULL,
  `redcap_fieldname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `redcap_import_mapping`
--

CREATE TABLE `redcap_import_mapping` (
  `formmap_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `redcap_event` varchar(255) DEFAULT NULL,
  `redcap_form` varchar(255) DEFAULT NULL,
  `redcap_fields` text DEFAULT NULL,
  `redcap_fieldtype` varchar(255) DEFAULT NULL,
  `redcap_fieldgroupid` int(11) NOT NULL,
  `nidb_datatype` enum('m','v','d') NOT NULL COMMENT 'measure, vital, drug/dose',
  `nidb_variablename` varchar(255) DEFAULT NULL,
  `nidb_instrumentname` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- --------------------------------------------------------

--
-- Table structure for table `remote_connections`
--

CREATE TABLE `remote_connections` (
  `remoteconn_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `conn_name` varchar(255) NOT NULL,
  `remote_server` varchar(255) NOT NULL,
  `remote_username` varchar(255) NOT NULL,
  `remote_password` varchar(255) NOT NULL,
  `remote_instanceid` int(11) NOT NULL,
  `remote_projectid` int(11) NOT NULL,
  `remote_siteid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `remote_logins`
--

CREATE TABLE `remote_logins` (
  `remotelogin_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `login_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `login_result` enum('success','failure') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

CREATE TABLE `search_history` (
  `searchhistory_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `saved_name` varchar(255) NOT NULL DEFAULT '',
  `subjectuid` text DEFAULT NULL,
  `subjectaltuid` text DEFAULT NULL,
  `subjectname` varchar(255) DEFAULT NULL,
  `subjectdobstart` date DEFAULT NULL,
  `subjectdobend` date DEFAULT NULL,
  `ageatscanmin` double DEFAULT NULL,
  `ageatscanmax` double DEFAULT NULL,
  `subjectgender` char(1) DEFAULT NULL,
  `subjectgroupid` int(11) DEFAULT NULL,
  `projectid` int(11) DEFAULT NULL,
  `enrollsubgroup` varchar(255) DEFAULT NULL,
  `measuresearch` text DEFAULT NULL,
  `measurelist` text DEFAULT NULL,
  `studyinstitution` varchar(255) DEFAULT NULL,
  `studyequipment` varchar(255) DEFAULT NULL,
  `studyid` text DEFAULT NULL,
  `studyaltscanid` text DEFAULT NULL,
  `studydatestart` date DEFAULT NULL,
  `studydateend` date DEFAULT NULL,
  `studydesc` text DEFAULT NULL,
  `studyphysician` varchar(255) DEFAULT NULL,
  `studyoperator` varchar(255) DEFAULT NULL,
  `studytype` varchar(255) DEFAULT NULL,
  `studymodality` varchar(255) DEFAULT NULL,
  `studygroupid` int(11) DEFAULT NULL,
  `seriesdesc` text DEFAULT NULL,
  `usealtseriesdesc` tinyint(1) DEFAULT NULL,
  `seriessequence` text DEFAULT NULL,
  `seriesimagetype` text DEFAULT NULL,
  `seriestr` varchar(255) DEFAULT NULL,
  `seriesimagecomments` text DEFAULT NULL,
  `seriesnum` varchar(255) DEFAULT NULL,
  `seriesnumfiles` varchar(255) DEFAULT NULL,
  `seriesgroupid` int(11) DEFAULT NULL,
  `pipelineid` int(11) DEFAULT NULL,
  `pipelineresultname` text DEFAULT NULL,
  `pipelineresultunit` text DEFAULT NULL,
  `pipelineresultvalue` text DEFAULT NULL,
  `pipelineresultcompare` text DEFAULT NULL,
  `pipelineresulttype` char(1) DEFAULT NULL,
  `pipelinecolorize` tinyint(1) DEFAULT NULL,
  `pipelinecormatrix` tinyint(1) DEFAULT NULL,
  `pipelineresultstats` tinyint(1) DEFAULT NULL,
  `resultorder` varchar(255) DEFAULT NULL,
  `formid` int(11) DEFAULT NULL,
  `formfieldid` int(11) DEFAULT NULL,
  `formcriteria` text DEFAULT NULL,
  `formvalue` text DEFAULT NULL,
  `audit` tinyint(1) DEFAULT NULL,
  `qcbuiltinvariable` text DEFAULT NULL,
  `qcvariableid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `snps`
--

CREATE TABLE `snps` (
  `snp_id` int(11) NOT NULL,
  `snp` varchar(255) NOT NULL,
  `position` int(11) NOT NULL,
  `chromosome` tinyint(3) UNSIGNED NOT NULL,
  `reference_allele` char(2) NOT NULL,
  `genetic_distance` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `snp_alleles`
--

CREATE TABLE `snp_alleles` (
  `snpallele_id` int(11) NOT NULL,
  `snp_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `allele` char(2) NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `snp_series`
--

CREATE TABLE `snp_series` (
  `snpseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `ishidden` tinyint(1) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sr_series`
--

CREATE TABLE `sr_series` (
  `srseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `ishidden` tinyint(1) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `studies`
--

CREATE TABLE `studies` (
  `study_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `study_num` int(11) NOT NULL,
  `study_desc` varchar(255) NOT NULL,
  `study_type` varchar(255) DEFAULT NULL,
  `study_alternateid` varchar(100) DEFAULT NULL COMMENT 'original ADO id',
  `study_modality` varchar(25) NOT NULL,
  `study_datetime` datetime DEFAULT NULL,
  `study_ageatscan` double DEFAULT NULL,
  `study_height` double DEFAULT NULL,
  `study_weight` double DEFAULT NULL,
  `study_bmi` double DEFAULT NULL,
  `study_operator` varchar(45) DEFAULT NULL,
  `study_experimenter` varchar(255) DEFAULT NULL,
  `study_performingphysician` varchar(100) DEFAULT NULL COMMENT 'may be necessary for an offsite exam, such as CT or PET at the hospital which was ordered and performed by a physician other than the PI',
  `study_site` varchar(45) DEFAULT NULL,
  `study_nidbsite` int(11) DEFAULT NULL,
  `study_institution` varchar(255) DEFAULT NULL,
  `study_notes` varchar(255) DEFAULT NULL,
  `study_doradread` tinyint(1) DEFAULT NULL,
  `study_radreaddate` datetime DEFAULT NULL,
  `study_radreadfindings` text DEFAULT NULL,
  `study_subjectage` double DEFAULT NULL,
  `study_etsnellenchart` int(11) DEFAULT NULL,
  `study_etvergence` varchar(255) DEFAULT NULL,
  `study_ettracking` varchar(255) DEFAULT NULL,
  `study_snpchip` varchar(255) DEFAULT NULL,
  `study_status` varchar(20) DEFAULT NULL COMMENT 'pending, processing, complete',
  `study_isactive` tinyint(1) DEFAULT 1,
  `study_createdby` varchar(50) DEFAULT NULL,
  `study_createdate` datetime DEFAULT NULL,
  `lastupdate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `study_template`
--

CREATE TABLE `study_template` (
  `studytemplate_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_modality` varchar(50) NOT NULL,
  `template_visitlabel` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `study_templateitems`
--

CREATE TABLE `study_templateitems` (
  `studytemplateitem_id` int(11) NOT NULL,
  `studytemplate_id` int(11) NOT NULL,
  `item_order` int(11) NOT NULL,
  `item_protocol` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` char(1) DEFAULT NULL,
  `ethnicity1` enum('hispanic','nothispanic','') DEFAULT NULL,
  `ethnicity2` set('asian','black','white','indian','islander','mixed','other','unknown') DEFAULT NULL,
  `height` double DEFAULT NULL COMMENT 'stored in cm',
  `weight` double DEFAULT NULL COMMENT 'stored in kg',
  `handedness` char(1) DEFAULT NULL,
  `education` varchar(45) DEFAULT NULL,
  `phone1` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `marital_status` enum('unknown','','married','single','divorced','separated','civilunion','cohabitating','widowed') DEFAULT 'unknown',
  `smoking_status` enum('unknown','never','current','past','') DEFAULT 'unknown',
  `uid` varchar(10) DEFAULT NULL,
  `uuid` varchar(255) DEFAULT NULL,
  `uuid2` varchar(255) DEFAULT NULL,
  `guid` varchar(255) DEFAULT NULL,
  `cancontact` tinyint(1) DEFAULT NULL,
  `isactive` tinyint(1) DEFAULT 1,
  `isimported` tinyint(1) DEFAULT NULL,
  `importeduuid` varchar(255) DEFAULT NULL,
  `lastupdate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `subject_altuid`
--

CREATE TABLE `subject_altuid` (
  `subjectaltuid_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `altuid` varchar(255) NOT NULL,
  `isprimary` tinyint(1) NOT NULL,
  `enrollment_id` int(11) NOT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `subject_relation`
--

CREATE TABLE `subject_relation` (
  `subjectrelation_id` int(11) NOT NULL,
  `subjectid1` int(11) NOT NULL,
  `subjectid2` int(11) NOT NULL,
  `relation` varchar(10) NOT NULL COMMENT 'siblingm, siblingf, sibling, child, parent [subject1 is the ''relation'' of subject2]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `surgery_series`
--

CREATE TABLE `surgery_series` (
  `surgeryseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `system_messages`
--

CREATE TABLE `system_messages` (
  `message_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `message_date` datetime NOT NULL,
  `message_status` enum('active','deleted','pending') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `system_status`
--

CREATE TABLE `system_status` (
  `systemstatus_id` int(11) NOT NULL,
  `status_variable` varchar(255) NOT NULL,
  `status_value` varchar(255) NOT NULL,
  `status_desc` text NOT NULL,
  `status_datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tag_id` int(11) NOT NULL,
  `tagtype` enum('','dx') NOT NULL,
  `series_id` int(11) DEFAULT NULL,
  `study_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `enrollment_id` int(11) DEFAULT NULL,
  `analysis_id` int(11) DEFAULT NULL,
  `pipeline_id` int(11) DEFAULT NULL,
  `modality` varchar(20) DEFAULT NULL,
  `tag` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `task_series`
--

CREATE TABLE `task_series` (
  `taskseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` varchar(255) NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ishidden` tinyint(1) NOT NULL,
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(45) DEFAULT NULL,
  `login_type` enum('NIS','Standard','Guest','Pending') DEFAULT NULL,
  `user_instanceid` int(11) DEFAULT 0,
  `user_fullname` varchar(150) DEFAULT NULL,
  `user_firstname` varchar(255) DEFAULT NULL,
  `user_midname` char(1) DEFAULT NULL,
  `user_lastname` varchar(255) DEFAULT NULL,
  `user_institution` varchar(255) DEFAULT NULL,
  `user_country` varchar(255) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `user_email2` varchar(255) DEFAULT NULL,
  `user_address1` varchar(255) DEFAULT NULL,
  `user_address2` varchar(255) DEFAULT NULL,
  `user_city` varchar(255) DEFAULT NULL,
  `user_state` varchar(255) DEFAULT NULL,
  `user_zip` varchar(255) DEFAULT NULL,
  `user_phone1` varchar(255) DEFAULT NULL,
  `user_phone2` varchar(255) DEFAULT NULL,
  `user_website` varchar(255) DEFAULT NULL,
  `user_dept` varchar(255) DEFAULT NULL,
  `user_lastlogin` timestamp NULL DEFAULT NULL,
  `user_logincount` int(11) DEFAULT 0,
  `user_enabled` tinyint(1) DEFAULT 0,
  `user_isadmin` tinyint(1) DEFAULT 0,
  `user_issiteadmin` tinyint(1) DEFAULT 0,
  `user_canimport` tinyint(1) DEFAULT 0,
  `sendmail_dailysummary` tinyint(1) DEFAULT NULL,
  `user_enablebeta` tinyint(1) DEFAULT 0,
  `lastupdate` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_deleted` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `users_pending`
--

CREATE TABLE `users_pending` (
  `user_id` int(11) NOT NULL,
  `username` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  `user_instanceid` int(11) NOT NULL,
  `user_fullname` varchar(150) NOT NULL,
  `user_institution` varchar(255) NOT NULL,
  `user_country` varchar(255) NOT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `emailkey` varchar(255) NOT NULL,
  `signupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_firstname` varchar(255) NOT NULL,
  `user_midname` varchar(255) NOT NULL,
  `user_lastname` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `favorite_id` int(11) NOT NULL,
  `favorite_type` set('project','subject') NOT NULL,
  `favorite_objectid` int(11) NOT NULL,
  `favorite_user` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_instance`
--

CREATE TABLE `user_instance` (
  `userinstance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `isdefaultinstance` tinyint(1) DEFAULT NULL,
  `instance_joinrequest` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `user_project`
--

CREATE TABLE `user_project` (
  `userproject_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `project_admin` tinyint(1) NOT NULL DEFAULT 0,
  `view_data` tinyint(1) NOT NULL DEFAULT 0,
  `view_phi` tinyint(1) NOT NULL DEFAULT 0,
  `write_data` tinyint(1) NOT NULL DEFAULT 0,
  `write_phi` tinyint(1) NOT NULL DEFAULT 0,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `us_series`
--

CREATE TABLE `us_series` (
  `usseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT 0,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT 0 COMMENT 'total number of files',
  `series_size` double DEFAULT 0 COMMENT 'size of all the files',
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) NOT NULL DEFAULT 0,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `video_series`
--

CREATE TABLE `video_series` (
  `videoseries_id` int(11) NOT NULL,
  `study_id` int(11) NOT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_size` double NOT NULL,
  `series_notes` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL,
  `video_desc` text NOT NULL,
  `video_cputime` double NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `ishidden` tinyint(1) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vitalnames`
--

CREATE TABLE `vitalnames` (
  `vitalname_id` int(11) NOT NULL,
  `vital_name` varchar(255) NOT NULL,
  `normal_range` varchar(255) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `vitals`
--

CREATE TABLE `vitals` (
  `vital_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `vital_date` datetime NOT NULL,
  `vital_value` varchar(20) NOT NULL,
  `vital_notes` varchar(255) DEFAULT NULL,
  `vital_desc` varchar(255) DEFAULT NULL,
  `vital_rater` varchar(255) DEFAULT NULL,
  `vitalname_id` int(11) NOT NULL,
  `vital_type` varchar(255) NOT NULL,
  `vital_startdate` datetime DEFAULT NULL,
  `vital_enddate` datetime DEFAULT NULL,
  `vital_duration` bigint(20) DEFAULT NULL,
  `vital_entrydate` datetime DEFAULT NULL,
  `vital_recordcreatedate` datetime DEFAULT NULL,
  `vital_recordmodifydate` datetime DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `weather`
--

CREATE TABLE `weather` (
  `observation_id` int(11) NOT NULL,
  `obsv_location` varchar(255) NOT NULL,
  `obsv_datetime` datetime NOT NULL,
  `obsv_type` enum('','clouds','presentweather','temp','dewpoint','humidity','windspeed','winddirection','windgust','pressure','pressuretendency','precip','dailysunrise','dailysunset') NOT NULL,
  `obsv_value` double NOT NULL,
  `presentweather` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `xa_series`
--

CREATE TABLE `xa_series` (
  `xaseries_id` int(11) NOT NULL,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) DEFAULT 0,
  `series_desc` varchar(255) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL,
  `series_protocol` varchar(255) DEFAULT NULL,
  `series_numfiles` int(11) DEFAULT 0 COMMENT 'total number of files',
  `series_size` double DEFAULT 0 COMMENT 'size of all the files',
  `series_notes` text DEFAULT NULL,
  `series_createdby` varchar(50) DEFAULT NULL,
  `ishidden` tinyint(1) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `series_duration` bigint(20) DEFAULT NULL
) ENGINE=Aria DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `analysis`
--
ALTER TABLE `analysis`
  ADD PRIMARY KEY (`analysis_id`),
  ADD UNIQUE KEY `pipeline_id_2` (`pipeline_id`,`pipeline_version`,`study_id`),
  ADD KEY `analysis_disksize` (`analysis_disksize`),
  ADD KEY `analysis_isbad` (`analysis_isbad`),
  ADD KEY `analysis_runsupplement` (`analysis_runsupplement`),
  ADD KEY `analysis_status` (`analysis_status`),
  ADD KEY `pipeline_dependency` (`pipeline_dependency`),
  ADD KEY `pipeline_id` (`pipeline_id`),
  ADD KEY `study_id` (`study_id`);

--
-- Indexes for table `analysis_data`
--
ALTER TABLE `analysis_data`
  ADD PRIMARY KEY (`analysisdata_id`),
  ADD UNIQUE KEY `analysis_id` (`analysis_id`,`data_id`,`modality`),
  ADD KEY `idx_analysis_data` (`analysis_id`);

--
-- Indexes for table `analysis_group`
--
ALTER TABLE `analysis_group`
  ADD PRIMARY KEY (`analysisgroup_id`),
  ADD UNIQUE KEY `pipeline_id_2` (`pipeline_id`,`pipeline_version`),
  ADD KEY `pipeline_id` (`pipeline_id`);

--
-- Indexes for table `analysis_history`
--
ALTER TABLE `analysis_history`
  ADD PRIMARY KEY (`analysishistory_id`),
  ADD KEY `analysis_event` (`analysis_event`),
  ADD KEY `analysis_id` (`analysis_id`,`pipeline_id`,`pipeline_version`,`study_id`);

--
-- Indexes for table `analysis_resultnames`
--
ALTER TABLE `analysis_resultnames`
  ADD PRIMARY KEY (`resultname_id`),
  ADD UNIQUE KEY `result_name` (`result_name`);

--
-- Indexes for table `analysis_results`
--
ALTER TABLE `analysis_results`
  ADD PRIMARY KEY (`analysisresults_id`),
  ADD UNIQUE KEY `analysis_id` (`analysis_id`,`result_type`,`result_nameid`),
  ADD KEY `result_value` (`result_value`),
  ADD KEY `idx_analysis_results` (`analysis_id`),
  ADD KEY `result_type` (`result_type`);

--
-- Indexes for table `analysis_resultunit`
--
ALTER TABLE `analysis_resultunit`
  ADD PRIMARY KEY (`resultunit_id`),
  ADD UNIQUE KEY `units` (`result_unit`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`experiment_id`),
  ADD KEY `fk_experiments_subject_project1` (`enrollment_id`);

--
-- Indexes for table `assessment_data`
--
ALTER TABLE `assessment_data`
  ADD PRIMARY KEY (`formdata_id`);

--
-- Indexes for table `assessment_formfields`
--
ALTER TABLE `assessment_formfields`
  ADD PRIMARY KEY (`formfield_id`),
  ADD KEY `fk_formfielddef_formdef1` (`form_id`);

--
-- Indexes for table `assessment_forms`
--
ALTER TABLE `assessment_forms`
  ADD PRIMARY KEY (`form_id`,`project_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `assessment_series`
--
ALTER TABLE `assessment_series`
  ADD PRIMARY KEY (`assessmentseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`);

--
-- Indexes for table `audio_series`
--
ALTER TABLE `audio_series`
  ADD PRIMARY KEY (`audioseries_id`);

--
-- Indexes for table `audit_enrollment`
--
ALTER TABLE `audit_enrollment`
  ADD PRIMARY KEY (`auditenrollment_id`),
  ADD KEY `subject_id` (`enrollment_id`);

--
-- Indexes for table `audit_results`
--
ALTER TABLE `audit_results`
  ADD PRIMARY KEY (`auditresult_id`);

--
-- Indexes for table `audit_series`
--
ALTER TABLE `audit_series`
  ADD PRIMARY KEY (`auditseries_id`),
  ADD KEY `subject_id` (`series_id`);

--
-- Indexes for table `audit_study`
--
ALTER TABLE `audit_study`
  ADD PRIMARY KEY (`auditstudy_id`),
  ADD KEY `subject_id` (`study_id`);

--
-- Indexes for table `audit_subject`
--
ALTER TABLE `audit_subject`
  ADD PRIMARY KEY (`auditsubject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `binary_series`
--
ALTER TABLE `binary_series`
  ADD PRIMARY KEY (`binaryseries_id`);

--
-- Indexes for table `calendars`
--
ALTER TABLE `calendars`
  ADD PRIMARY KEY (`calendar_id`);

--
-- Indexes for table `calendar_allocations`
--
ALTER TABLE `calendar_allocations`
  ADD PRIMARY KEY (`alloc_id`);

--
-- Indexes for table `calendar_appointments`
--
ALTER TABLE `calendar_appointments`
  ADD PRIMARY KEY (`appt_id`),
  ADD KEY `appt_startdate` (`appt_startdate`,`appt_enddate`);

--
-- Indexes for table `calendar_notifications`
--
ALTER TABLE `calendar_notifications`
  ADD PRIMARY KEY (`not_id`);

--
-- Indexes for table `calendar_projectnotifications`
--
ALTER TABLE `calendar_projectnotifications`
  ADD PRIMARY KEY (`not_id`);

--
-- Indexes for table `calendar_projects`
--
ALTER TABLE `calendar_projects`
  ADD PRIMARY KEY (`project_id`);

--
-- Indexes for table `changelog`
--
ALTER TABLE `changelog`
  ADD PRIMARY KEY (`changelog_id`);

--
-- Indexes for table `changelog_subject`
--
ALTER TABLE `changelog_subject`
  ADD PRIMARY KEY (`changelog_id`);

--
-- Indexes for table `common`
--
ALTER TABLE `common`
  ADD PRIMARY KEY (`common_id`),
  ADD UNIQUE KEY `common_group` (`common_group`,`common_name`);

--
-- Indexes for table `consent_series`
--
ALTER TABLE `consent_series`
  ADD PRIMARY KEY (`consentseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`contact_id`);

--
-- Indexes for table `cr_series`
--
ALTER TABLE `cr_series`
  ADD PRIMARY KEY (`crseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`);

--
-- Indexes for table `ct_series`
--
ALTER TABLE `ct_series`
  ADD PRIMARY KEY (`ctseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`);

--
-- Indexes for table `data_requests`
--
ALTER TABLE `data_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_data_requests` (`req_username`),
  ADD KEY `req_date` (`req_date`),
  ADD KEY `req_groupid` (`req_groupid`),
  ADD KEY `req_status` (`req_status`);

--
-- Indexes for table `drugnames`
--
ALTER TABLE `drugnames`
  ADD PRIMARY KEY (`drugname_id`),
  ADD UNIQUE KEY `measure_name` (`drug_name`);

--
-- Indexes for table `drugs`
--
ALTER TABLE `drugs`
  ADD PRIMARY KEY (`drug_id`);

--
-- Indexes for table `ecg_series`
--
ALTER TABLE `ecg_series`
  ADD PRIMARY KEY (`ecgseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`),
  ADD KEY `series_desc` (`series_desc`),
  ADD KEY `series_protocol` (`series_protocol`);

--
-- Indexes for table `eeg_series`
--
ALTER TABLE `eeg_series`
  ADD PRIMARY KEY (`eegseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`),
  ADD KEY `series_altdesc` (`series_altdesc`),
  ADD KEY `series_desc` (`series_desc`),
  ADD KEY `series_protocol` (`series_protocol`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `project_id` (`project_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `enrollment_checklist`
--
ALTER TABLE `enrollment_checklist`
  ADD PRIMARY KEY (`enrollmentchecklist_id`);

--
-- Indexes for table `enrollment_missingdata`
--
ALTER TABLE `enrollment_missingdata`
  ADD PRIMARY KEY (`missingdata_id`),
  ADD UNIQUE KEY `enrollment_id` (`enrollment_id`,`projectchecklist_id`);

--
-- Indexes for table `error_log`
--
ALTER TABLE `error_log`
  ADD PRIMARY KEY (`errorlog_id`);

--
-- Indexes for table `et_series`
--
ALTER TABLE `et_series`
  ADD PRIMARY KEY (`etseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`),
  ADD KEY `series_altdesc` (`series_altdesc`);

--
-- Indexes for table `exports`
--
ALTER TABLE `exports`
  ADD PRIMARY KEY (`export_id`);

--
-- Indexes for table `exportseries`
--
ALTER TABLE `exportseries`
  ADD PRIMARY KEY (`exportseries_id`);

--
-- Indexes for table `families`
--
ALTER TABLE `families`
  ADD PRIMARY KEY (`family_id`),
  ADD UNIQUE KEY `family_uid` (`family_uid`);

--
-- Indexes for table `family_members`
--
ALTER TABLE `family_members`
  ADD PRIMARY KEY (`familymember_id`);

--
-- Indexes for table `fileio_requests`
--
ALTER TABLE `fileio_requests`
  ADD PRIMARY KEY (`fileiorequest_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`group_id`),
  ADD UNIQUE KEY `group_name` (`group_name`,`group_owner`);

--
-- Indexes for table `group_data`
--
ALTER TABLE `group_data`
  ADD PRIMARY KEY (`subjectgroup_id`),
  ADD UNIQUE KEY `group_id` (`group_id`,`data_id`,`modality`),
  ADD KEY `idx_group_data` (`modality`);

--
-- Indexes for table `gsr_series`
--
ALTER TABLE `gsr_series`
  ADD PRIMARY KEY (`gsrseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`);

--
-- Indexes for table `importlogs`
--
ALTER TABLE `importlogs`
  ADD PRIMARY KEY (`importlog_id`),
  ADD KEY `importstartdate` (`importstartdate`),
  ADD KEY `stationname_orig` (`stationname_orig`),
  ADD KEY `studydatetime_orig` (`studydatetime_orig`);

--
-- Indexes for table `import_requestdirs`
--
ALTER TABLE `import_requestdirs`
  ADD PRIMARY KEY (`importrequestdir_id`);

--
-- Indexes for table `import_requests`
--
ALTER TABLE `import_requests`
  ADD PRIMARY KEY (`importrequest_id`),
  ADD KEY `idx_import_requests` (`import_transactionid`),
  ADD KEY `idx_import_requests_0` (`import_modality`),
  ADD KEY `import_subjectid` (`import_subjectid`);

--
-- Indexes for table `import_transactions`
--
ALTER TABLE `import_transactions`
  ADD PRIMARY KEY (`importtrans_id`);

--
-- Indexes for table `instance`
--
ALTER TABLE `instance`
  ADD PRIMARY KEY (`instance_id`),
  ADD UNIQUE KEY `instance_name` (`instance_name`);

--
-- Indexes for table `instance_billing`
--
ALTER TABLE `instance_billing`
  ADD PRIMARY KEY (`billingitem_id`);

--
-- Indexes for table `instance_contact`
--
ALTER TABLE `instance_contact`
  ADD PRIMARY KEY (`instancecontact_id`);

--
-- Indexes for table `instance_invoice`
--
ALTER TABLE `instance_invoice`
  ADD PRIMARY KEY (`invoice_id`);

--
-- Indexes for table `instance_pricing`
--
ALTER TABLE `instance_pricing`
  ADD PRIMARY KEY (`pricing_id`);

--
-- Indexes for table `instance_usage`
--
ALTER TABLE `instance_usage`
  ADD PRIMARY KEY (`instanceusage_id`);

--
-- Indexes for table `manual_qa`
--
ALTER TABLE `manual_qa`
  ADD PRIMARY KEY (`manualqa_id`),
  ADD UNIQUE KEY `series_id` (`series_id`,`modality`,`rater_id`);

--
-- Indexes for table `measureinstruments`
--
ALTER TABLE `measureinstruments`
  ADD PRIMARY KEY (`measureinstrument_id`),
  ADD UNIQUE KEY `measure_name` (`instrument_name`);

--
-- Indexes for table `measurenames`
--
ALTER TABLE `measurenames`
  ADD PRIMARY KEY (`measurename_id`),
  ADD UNIQUE KEY `measure_name` (`measure_name`);

--
-- Indexes for table `measures`
--
ALTER TABLE `measures`
  ADD PRIMARY KEY (`measure_id`),
  ADD UNIQUE KEY `enrollment_id` (`enrollment_id`,`measurename_id`,`measure_value`,`measure_startdate`);

--
-- Indexes for table `minipipelines`
--
ALTER TABLE `minipipelines`
  ADD PRIMARY KEY (`minipipeline_id`);

--
-- Indexes for table `minipipeline_jobs`
--
ALTER TABLE `minipipeline_jobs`
  ADD PRIMARY KEY (`minipipelinejob_id`);

--
-- Indexes for table `minipipeline_scripts`
--
ALTER TABLE `minipipeline_scripts`
  ADD PRIMARY KEY (`minipipelinescript_id`),
  ADD UNIQUE KEY `minipipeline_id` (`minipipeline_id`,`mp_version`,`mp_scriptname`);

--
-- Indexes for table `modalities`
--
ALTER TABLE `modalities`
  ADD PRIMARY KEY (`mod_id`),
  ADD UNIQUE KEY `pk_modalities_0` (`mod_code`);

--
-- Indexes for table `modality_protocol`
--
ALTER TABLE `modality_protocol`
  ADD KEY `idx_modality_protocol` (`modality`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`),
  ADD UNIQUE KEY `module_name` (`module_name`);

--
-- Indexes for table `module_prefs`
--
ALTER TABLE `module_prefs`
  ADD PRIMARY KEY (`mp_id`);

--
-- Indexes for table `module_procs`
--
ALTER TABLE `module_procs`
  ADD PRIMARY KEY (`moduleproc_id`),
  ADD UNIQUE KEY `module_name` (`module_name`,`process_id`);

--
-- Indexes for table `mostrecent`
--
ALTER TABLE `mostrecent`
  ADD PRIMARY KEY (`mostrecent_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`subject_id`),
  ADD UNIQUE KEY `user_id_2` (`user_id`,`study_id`),
  ADD KEY `idx_mostrecent` (`subject_id`);

--
-- Indexes for table `mr_qa`
--
ALTER TABLE `mr_qa`
  ADD PRIMARY KEY (`mrqa_id`),
  ADD KEY `mriseries_id` (`mrseries_id`);

--
-- Indexes for table `mr_qcparams`
--
ALTER TABLE `mr_qcparams`
  ADD PRIMARY KEY (`mrqcparam_id`);

--
-- Indexes for table `mr_scanparams`
--
ALTER TABLE `mr_scanparams`
  ADD PRIMARY KEY (`mrscanparam_id`),
  ADD UNIQUE KEY `protocol_name` (`protocol_name`,`sequence_name`,`tr_min`,`tr_max`,`te_min`,`te_max`,`ti_min`,`ti_max`,`flip_min`,`flip_max`,`xdim_min`,`xdim_max`,`ydim_min`,`ydim_max`,`zdim_min`,`zdim_max`,`tdim_min`,`tdim_max`,`slicethickness_min`,`slicethickness_max`,`slicespacing_min`,`slicespacing_max`,`bandwidth_min`,`bandwidth_max`);

--
-- Indexes for table `mr_series`
--
ALTER TABLE `mr_series`
  ADD PRIMARY KEY (`mrseries_id`),
  ADD UNIQUE KEY `study_id_2` (`study_id`,`series_num`),
  ADD KEY `ishidden` (`ishidden`),
  ADD KEY `series_altdesc` (`series_altdesc`),
  ADD KEY `series_desc` (`series_desc`),
  ADD KEY `series_protocol` (`series_protocol`),
  ADD KEY `series_tr` (`series_tr`),
  ADD KEY `study_id` (`study_id`);

--
-- Indexes for table `mr_studyqa`
--
ALTER TABLE `mr_studyqa`
  ADD PRIMARY KEY (`mrstudyqa_id`),
  ADD KEY `mriseries_id` (`study_id`);

--
-- Indexes for table `nidb_sites`
--
ALTER TABLE `nidb_sites`
  ADD PRIMARY KEY (`site_id`),
  ADD UNIQUE KEY `uuid` (`site_uuid`);

--
-- Indexes for table `nm_series`
--
ALTER TABLE `nm_series`
  ADD PRIMARY KEY (`nmseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notiftype_id`);

--
-- Indexes for table `notification_user`
--
ALTER TABLE `notification_user`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `idx_notifications` (`user_id`);

--
-- Indexes for table `ot_series`
--
ALTER TABLE `ot_series`
  ADD PRIMARY KEY (`otseries_id`),
  ADD KEY `fk_mri_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`),
  ADD KEY `series_desc` (`series_desc`);

--
-- Indexes for table `pipelines`
--
ALTER TABLE `pipelines`
  ADD PRIMARY KEY (`pipeline_id`),
  ADD UNIQUE KEY `pipeline_name` (`pipeline_name`,`pipeline_version`);

--
-- Indexes for table `pipeline_data`
--
ALTER TABLE `pipeline_data`
  ADD PRIMARY KEY (`pipelinedata_id`),
  ADD KEY `analysis_id` (`analysis_id`,`pd_modality`,`pd_seriesid`);

--
-- Indexes for table `pipeline_data_def`
--
ALTER TABLE `pipeline_data_def`
  ADD PRIMARY KEY (`pipelinedatadef_id`);

--
-- Indexes for table `pipeline_dependencies`
--
ALTER TABLE `pipeline_dependencies`
  ADD PRIMARY KEY (`pipelinedep_id`),
  ADD UNIQUE KEY `pipeline_id` (`pipeline_id`,`parent_id`);

--
-- Indexes for table `pipeline_download`
--
ALTER TABLE `pipeline_download`
  ADD PRIMARY KEY (`pipelinedownload_id`);

--
-- Indexes for table `pipeline_groups`
--
ALTER TABLE `pipeline_groups`
  ADD PRIMARY KEY (`pipelinegroup_id`),
  ADD UNIQUE KEY `pipeline_id` (`pipeline_id`,`group_id`);

--
-- Indexes for table `pipeline_history`
--
ALTER TABLE `pipeline_history`
  ADD PRIMARY KEY (`analysis_id`);

--
-- Indexes for table `pipeline_options`
--
ALTER TABLE `pipeline_options`
  ADD PRIMARY KEY (`pipelineoptions_id`);

--
-- Indexes for table `pipeline_procs`
--
ALTER TABLE `pipeline_procs`
  ADD PRIMARY KEY (`pp_processid`);

--
-- Indexes for table `pipeline_status`
--
ALTER TABLE `pipeline_status`
  ADD PRIMARY KEY (`pipelinestatus_id`);

--
-- Indexes for table `pipeline_steps`
--
ALTER TABLE `pipeline_steps`
  ADD PRIMARY KEY (`pipelinestep_id`),
  ADD KEY `fk_pipeline_steps_pipelines1` (`pipeline_id`);

--
-- Indexes for table `pipeline_version`
--
ALTER TABLE `pipeline_version`
  ADD PRIMARY KEY (`pipelineversion_id`);

--
-- Indexes for table `ppi_series`
--
ALTER TABLE `ppi_series`
  ADD PRIMARY KEY (`ppiseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ppi_series` (`ishidden`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD UNIQUE KEY `project_costcenter` (`project_costcenter`),
  ADD KEY `fk_projects_users` (`project_admin`),
  ADD KEY `fk_projects_users1` (`project_pi`);

--
-- Indexes for table `project_checklist`
--
ALTER TABLE `project_checklist`
  ADD PRIMARY KEY (`projectchecklist_id`);

--
-- Indexes for table `project_protocol`
--
ALTER TABLE `project_protocol`
  ADD PRIMARY KEY (`projectprotocol_id`);

--
-- Indexes for table `project_template`
--
ALTER TABLE `project_template`
  ADD PRIMARY KEY (`projecttemplate_id`);

--
-- Indexes for table `project_templatestudies`
--
ALTER TABLE `project_templatestudies`
  ADD PRIMARY KEY (`pts_id`);

--
-- Indexes for table `project_templatestudyitems`
--
ALTER TABLE `project_templatestudyitems`
  ADD PRIMARY KEY (`ptsitem_id`);

--
-- Indexes for table `protocolgroup_items`
--
ALTER TABLE `protocolgroup_items`
  ADD PRIMARY KEY (`pgitem_id`);

--
-- Indexes for table `protocol_group`
--
ALTER TABLE `protocol_group`
  ADD PRIMARY KEY (`protocolgroup_id`),
  ADD UNIQUE KEY `protocolgroup_name` (`protocolgroup_name`,`protocolgroup_modality`);

--
-- Indexes for table `pr_series`
--
ALTER TABLE `pr_series`
  ADD PRIMARY KEY (`prseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`);

--
-- Indexes for table `public_downloads`
--
ALTER TABLE `public_downloads`
  ADD PRIMARY KEY (`pd_id`);

--
-- Indexes for table `qc_modules`
--
ALTER TABLE `qc_modules`
  ADD PRIMARY KEY (`qcmodule_id`),
  ADD KEY `qcmodule_id` (`qcmodule_id`);

--
-- Indexes for table `qc_moduleseries`
--
ALTER TABLE `qc_moduleseries`
  ADD PRIMARY KEY (`qcmoduleseries_id`),
  ADD UNIQUE KEY `qcmodule_id` (`qcmodule_id`,`series_id`,`modality`),
  ADD KEY `series_id` (`series_id`),
  ADD KEY `modality` (`modality`);

--
-- Indexes for table `qc_resultnames`
--
ALTER TABLE `qc_resultnames`
  ADD PRIMARY KEY (`qcresultname_id`);

--
-- Indexes for table `qc_results`
--
ALTER TABLE `qc_results`
  ADD PRIMARY KEY (`qcresults_id`),
  ADD UNIQUE KEY `qcmoduleseries_id` (`qcmoduleseries_id`,`qcresultname_id`),
  ADD KEY `qcmoduleseries_id_2` (`qcmoduleseries_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `idx_ratings` (`rater_id`);

--
-- Indexes for table `rdoc_uploads`
--
ALTER TABLE `rdoc_uploads`
  ADD PRIMARY KEY (`rdocupload_id`);

--
-- Indexes for table `redcap_import_fields`
--
ALTER TABLE `redcap_import_fields`
  ADD PRIMARY KEY (`redcap_fieldgroupid`,`redcap_fieldname`);

--
-- Indexes for table `redcap_import_mapping`
--
ALTER TABLE `redcap_import_mapping`
  ADD PRIMARY KEY (`formmap_id`),
  ADD UNIQUE KEY `project_id` (`project_id`,`redcap_event`,`redcap_form`,`redcap_fields`(255)) USING BTREE;

--
-- Indexes for table `remote_connections`
--
ALTER TABLE `remote_connections`
  ADD PRIMARY KEY (`remoteconn_id`);

--
-- Indexes for table `remote_logins`
--
ALTER TABLE `remote_logins`
  ADD PRIMARY KEY (`remotelogin_id`),
  ADD KEY `idx_remote_logins` (`username`);

--
-- Indexes for table `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`searchhistory_id`);

--
-- Indexes for table `snps`
--
ALTER TABLE `snps`
  ADD PRIMARY KEY (`snp_id`),
  ADD UNIQUE KEY `snp` (`snp`,`position`);

--
-- Indexes for table `snp_alleles`
--
ALTER TABLE `snp_alleles`
  ADD PRIMARY KEY (`snpallele_id`),
  ADD UNIQUE KEY `snp_id` (`snp_id`,`enrollment_id`);

--
-- Indexes for table `snp_series`
--
ALTER TABLE `snp_series`
  ADD PRIMARY KEY (`snpseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`);

--
-- Indexes for table `sr_series`
--
ALTER TABLE `sr_series`
  ADD PRIMARY KEY (`srseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`);

--
-- Indexes for table `studies`
--
ALTER TABLE `studies`
  ADD PRIMARY KEY (`study_id`),
  ADD KEY `fk_studies_subject_project1` (`enrollment_id`),
  ADD KEY `study_datetime` (`study_datetime`),
  ADD KEY `study_modality` (`study_modality`),
  ADD KEY `subject_id` (`study_num`);

--
-- Indexes for table `study_template`
--
ALTER TABLE `study_template`
  ADD PRIMARY KEY (`studytemplate_id`);

--
-- Indexes for table `study_templateitems`
--
ALTER TABLE `study_templateitems`
  ADD PRIMARY KEY (`studytemplateitem_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `uid` (`uid`),
  ADD KEY `isactive` (`isactive`),
  ADD KEY `name` (`name`,`birthdate`,`gender`,`isactive`);

--
-- Indexes for table `subject_altuid`
--
ALTER TABLE `subject_altuid`
  ADD PRIMARY KEY (`subjectaltuid_id`),
  ADD UNIQUE KEY `subject_id` (`subject_id`,`altuid`,`enrollment_id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Indexes for table `subject_relation`
--
ALTER TABLE `subject_relation`
  ADD PRIMARY KEY (`subjectrelation_id`),
  ADD KEY `idx_subject_relation` (`subjectid1`),
  ADD KEY `idx_subject_relation_0` (`subjectid2`);

--
-- Indexes for table `surgery_series`
--
ALTER TABLE `surgery_series`
  ADD PRIMARY KEY (`surgeryseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`);

--
-- Indexes for table `system_messages`
--
ALTER TABLE `system_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `system_status`
--
ALTER TABLE `system_status`
  ADD PRIMARY KEY (`systemstatus_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD UNIQUE KEY `series_id` (`series_id`,`study_id`,`subject_id`,`enrollment_id`,`analysis_id`,`pipeline_id`,`modality`,`tag`),
  ADD KEY `tag` (`tag`);

--
-- Indexes for table `task_series`
--
ALTER TABLE `task_series`
  ADD PRIMARY KEY (`taskseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users_pending`
--
ALTER TABLE `users_pending`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_email` (`user_email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`favorite_id`);

--
-- Indexes for table `user_instance`
--
ALTER TABLE `user_instance`
  ADD PRIMARY KEY (`userinstance_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`instance_id`);

--
-- Indexes for table `user_project`
--
ALTER TABLE `user_project`
  ADD PRIMARY KEY (`userproject_id`),
  ADD KEY `user_id` (`user_id`,`project_id`);

--
-- Indexes for table `us_series`
--
ALTER TABLE `us_series`
  ADD PRIMARY KEY (`usseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`),
  ADD KEY `ishidden` (`ishidden`);

--
-- Indexes for table `video_series`
--
ALTER TABLE `video_series`
  ADD PRIMARY KEY (`videoseries_id`),
  ADD KEY `ishidden` (`ishidden`);

--
-- Indexes for table `vitalnames`
--
ALTER TABLE `vitalnames`
  ADD PRIMARY KEY (`vitalname_id`),
  ADD UNIQUE KEY `measure_name` (`vital_name`);

--
-- Indexes for table `vitals`
--
ALTER TABLE `vitals`
  ADD PRIMARY KEY (`vital_id`);

--
-- Indexes for table `weather`
--
ALTER TABLE `weather`
  ADD PRIMARY KEY (`observation_id`);

--
-- Indexes for table `xa_series`
--
ALTER TABLE `xa_series`
  ADD PRIMARY KEY (`xaseries_id`),
  ADD KEY `fk_eeg_series_studies1` (`study_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analysis`
--
ALTER TABLE `analysis`
  MODIFY `analysis_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analysis_data`
--
ALTER TABLE `analysis_data`
  MODIFY `analysisdata_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analysis_group`
--
ALTER TABLE `analysis_group`
  MODIFY `analysisgroup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analysis_history`
--
ALTER TABLE `analysis_history`
  MODIFY `analysishistory_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analysis_resultnames`
--
ALTER TABLE `analysis_resultnames`
  MODIFY `resultname_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analysis_results`
--
ALTER TABLE `analysis_results`
  MODIFY `analysisresults_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analysis_resultunit`
--
ALTER TABLE `analysis_resultunit`
  MODIFY `resultunit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `experiment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_data`
--
ALTER TABLE `assessment_data`
  MODIFY `formdata_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_formfields`
--
ALTER TABLE `assessment_formfields`
  MODIFY `formfield_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_forms`
--
ALTER TABLE `assessment_forms`
  MODIFY `form_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_series`
--
ALTER TABLE `assessment_series`
  MODIFY `assessmentseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audio_series`
--
ALTER TABLE `audio_series`
  MODIFY `audioseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_enrollment`
--
ALTER TABLE `audit_enrollment`
  MODIFY `auditenrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_results`
--
ALTER TABLE `audit_results`
  MODIFY `auditresult_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_series`
--
ALTER TABLE `audit_series`
  MODIFY `auditseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_study`
--
ALTER TABLE `audit_study`
  MODIFY `auditstudy_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_subject`
--
ALTER TABLE `audit_subject`
  MODIFY `auditsubject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `binary_series`
--
ALTER TABLE `binary_series`
  MODIFY `binaryseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendars`
--
ALTER TABLE `calendars`
  MODIFY `calendar_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_allocations`
--
ALTER TABLE `calendar_allocations`
  MODIFY `alloc_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_appointments`
--
ALTER TABLE `calendar_appointments`
  MODIFY `appt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_notifications`
--
ALTER TABLE `calendar_notifications`
  MODIFY `not_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_projectnotifications`
--
ALTER TABLE `calendar_projectnotifications`
  MODIFY `not_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `calendar_projects`
--
ALTER TABLE `calendar_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `changelog`
--
ALTER TABLE `changelog`
  MODIFY `changelog_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `changelog_subject`
--
ALTER TABLE `changelog_subject`
  MODIFY `changelog_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `common`
--
ALTER TABLE `common`
  MODIFY `common_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consent_series`
--
ALTER TABLE `consent_series`
  MODIFY `consentseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cr_series`
--
ALTER TABLE `cr_series`
  MODIFY `crseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ct_series`
--
ALTER TABLE `ct_series`
  MODIFY `ctseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_requests`
--
ALTER TABLE `data_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drugnames`
--
ALTER TABLE `drugnames`
  MODIFY `drugname_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drugs`
--
ALTER TABLE `drugs`
  MODIFY `drug_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ecg_series`
--
ALTER TABLE `ecg_series`
  MODIFY `ecgseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eeg_series`
--
ALTER TABLE `eeg_series`
  MODIFY `eegseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment_checklist`
--
ALTER TABLE `enrollment_checklist`
  MODIFY `enrollmentchecklist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment_missingdata`
--
ALTER TABLE `enrollment_missingdata`
  MODIFY `missingdata_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `error_log`
--
ALTER TABLE `error_log`
  MODIFY `errorlog_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `et_series`
--
ALTER TABLE `et_series`
  MODIFY `etseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exports`
--
ALTER TABLE `exports`
  MODIFY `export_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exportseries`
--
ALTER TABLE `exportseries`
  MODIFY `exportseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `families`
--
ALTER TABLE `families`
  MODIFY `family_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `family_members`
--
ALTER TABLE `family_members`
  MODIFY `familymember_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fileio_requests`
--
ALTER TABLE `fileio_requests`
  MODIFY `fileiorequest_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `group_data`
--
ALTER TABLE `group_data`
  MODIFY `subjectgroup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gsr_series`
--
ALTER TABLE `gsr_series`
  MODIFY `gsrseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `importlogs`
--
ALTER TABLE `importlogs`
  MODIFY `importlog_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_requests`
--
ALTER TABLE `import_requests`
  MODIFY `importrequest_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_transactions`
--
ALTER TABLE `import_transactions`
  MODIFY `importtrans_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instance`
--
ALTER TABLE `instance`
  MODIFY `instance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instance_billing`
--
ALTER TABLE `instance_billing`
  MODIFY `billingitem_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instance_contact`
--
ALTER TABLE `instance_contact`
  MODIFY `instancecontact_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instance_invoice`
--
ALTER TABLE `instance_invoice`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instance_pricing`
--
ALTER TABLE `instance_pricing`
  MODIFY `pricing_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instance_usage`
--
ALTER TABLE `instance_usage`
  MODIFY `instanceusage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `manual_qa`
--
ALTER TABLE `manual_qa`
  MODIFY `manualqa_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `measureinstruments`
--
ALTER TABLE `measureinstruments`
  MODIFY `measureinstrument_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `measurenames`
--
ALTER TABLE `measurenames`
  MODIFY `measurename_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `measures`
--
ALTER TABLE `measures`
  MODIFY `measure_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `minipipelines`
--
ALTER TABLE `minipipelines`
  MODIFY `minipipeline_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `minipipeline_jobs`
--
ALTER TABLE `minipipeline_jobs`
  MODIFY `minipipelinejob_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `minipipeline_scripts`
--
ALTER TABLE `minipipeline_scripts`
  MODIFY `minipipelinescript_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modalities`
--
ALTER TABLE `modalities`
  MODIFY `mod_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module_prefs`
--
ALTER TABLE `module_prefs`
  MODIFY `mp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module_procs`
--
ALTER TABLE `module_procs`
  MODIFY `moduleproc_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mostrecent`
--
ALTER TABLE `mostrecent`
  MODIFY `mostrecent_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mr_qa`
--
ALTER TABLE `mr_qa`
  MODIFY `mrqa_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mr_qcparams`
--
ALTER TABLE `mr_qcparams`
  MODIFY `mrqcparam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mr_scanparams`
--
ALTER TABLE `mr_scanparams`
  MODIFY `mrscanparam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mr_series`
--
ALTER TABLE `mr_series`
  MODIFY `mrseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mr_studyqa`
--
ALTER TABLE `mr_studyqa`
  MODIFY `mrstudyqa_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nidb_sites`
--
ALTER TABLE `nidb_sites`
  MODIFY `site_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nm_series`
--
ALTER TABLE `nm_series`
  MODIFY `nmseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_user`
--
ALTER TABLE `notification_user`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ot_series`
--
ALTER TABLE `ot_series`
  MODIFY `otseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipelines`
--
ALTER TABLE `pipelines`
  MODIFY `pipeline_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_data`
--
ALTER TABLE `pipeline_data`
  MODIFY `pipelinedata_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_data_def`
--
ALTER TABLE `pipeline_data_def`
  MODIFY `pipelinedatadef_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_dependencies`
--
ALTER TABLE `pipeline_dependencies`
  MODIFY `pipelinedep_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_download`
--
ALTER TABLE `pipeline_download`
  MODIFY `pipelinedownload_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_groups`
--
ALTER TABLE `pipeline_groups`
  MODIFY `pipelinegroup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_history`
--
ALTER TABLE `pipeline_history`
  MODIFY `analysis_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_options`
--
ALTER TABLE `pipeline_options`
  MODIFY `pipelineoptions_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_status`
--
ALTER TABLE `pipeline_status`
  MODIFY `pipelinestatus_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_steps`
--
ALTER TABLE `pipeline_steps`
  MODIFY `pipelinestep_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipeline_version`
--
ALTER TABLE `pipeline_version`
  MODIFY `pipelineversion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ppi_series`
--
ALTER TABLE `ppi_series`
  MODIFY `ppiseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_checklist`
--
ALTER TABLE `project_checklist`
  MODIFY `projectchecklist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_protocol`
--
ALTER TABLE `project_protocol`
  MODIFY `projectprotocol_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_template`
--
ALTER TABLE `project_template`
  MODIFY `projecttemplate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_templatestudies`
--
ALTER TABLE `project_templatestudies`
  MODIFY `pts_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_templatestudyitems`
--
ALTER TABLE `project_templatestudyitems`
  MODIFY `ptsitem_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `protocolgroup_items`
--
ALTER TABLE `protocolgroup_items`
  MODIFY `pgitem_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `protocol_group`
--
ALTER TABLE `protocol_group`
  MODIFY `protocolgroup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pr_series`
--
ALTER TABLE `pr_series`
  MODIFY `prseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `public_downloads`
--
ALTER TABLE `public_downloads`
  MODIFY `pd_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qc_modules`
--
ALTER TABLE `qc_modules`
  MODIFY `qcmodule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qc_moduleseries`
--
ALTER TABLE `qc_moduleseries`
  MODIFY `qcmoduleseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qc_resultnames`
--
ALTER TABLE `qc_resultnames`
  MODIFY `qcresultname_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qc_results`
--
ALTER TABLE `qc_results`
  MODIFY `qcresults_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `redcap_import_mapping`
--
ALTER TABLE `redcap_import_mapping`
  MODIFY `formmap_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remote_connections`
--
ALTER TABLE `remote_connections`
  MODIFY `remoteconn_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remote_logins`
--
ALTER TABLE `remote_logins`
  MODIFY `remotelogin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `searchhistory_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `snps`
--
ALTER TABLE `snps`
  MODIFY `snp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `snp_alleles`
--
ALTER TABLE `snp_alleles`
  MODIFY `snpallele_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `snp_series`
--
ALTER TABLE `snp_series`
  MODIFY `snpseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sr_series`
--
ALTER TABLE `sr_series`
  MODIFY `srseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `studies`
--
ALTER TABLE `studies`
  MODIFY `study_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `study_template`
--
ALTER TABLE `study_template`
  MODIFY `studytemplate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `study_templateitems`
--
ALTER TABLE `study_templateitems`
  MODIFY `studytemplateitem_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject_altuid`
--
ALTER TABLE `subject_altuid`
  MODIFY `subjectaltuid_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject_relation`
--
ALTER TABLE `subject_relation`
  MODIFY `subjectrelation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `surgery_series`
--
ALTER TABLE `surgery_series`
  MODIFY `surgeryseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_messages`
--
ALTER TABLE `system_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_status`
--
ALTER TABLE `system_status`
  MODIFY `systemstatus_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_series`
--
ALTER TABLE `task_series`
  MODIFY `taskseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_pending`
--
ALTER TABLE `users_pending`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_instance`
--
ALTER TABLE `user_instance`
  MODIFY `userinstance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_project`
--
ALTER TABLE `user_project`
  MODIFY `userproject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `us_series`
--
ALTER TABLE `us_series`
  MODIFY `usseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `video_series`
--
ALTER TABLE `video_series`
  MODIFY `videoseries_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vitalnames`
--
ALTER TABLE `vitalnames`
  MODIFY `vitalname_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vitals`
--
ALTER TABLE `vitals`
  MODIFY `vital_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weather`
--
ALTER TABLE `weather`
  MODIFY `observation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `xa_series`
--
ALTER TABLE `xa_series`
  MODIFY `xaseries_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
