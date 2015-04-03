-- MySQL dump 10.13  Distrib 5.1.61, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: ado2
-- ------------------------------------------------------
-- Server version	5.1.61-log


--
-- Table structure for table `analysis`
--

CREATE TABLE `analysis` (
  `analysis_id` bigint(11) NOT NULL AUTO_INCREMENT,
  `pipeline_id` int(11) NOT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT '0',
  `pipeline_dependency` int(11) NOT NULL,
  `study_id` int(11) NOT NULL,
  `analysis_qsubid` bigint(20) unsigned NOT NULL,
  `analysis_status` enum('complete','pending','processing','error','submitted','','notcompleted','NoMatchingStudies','rerunresults') DEFAULT NULL,
  `analysis_statusmessage` varchar(255) DEFAULT NULL,
  `analysis_statusdatetime` timestamp NULL DEFAULT NULL,
  `analysis_notes` text NOT NULL,
  `analysis_iscomplete` tinyint(1) NOT NULL,
  `analysis_isbad` tinyint(1) NOT NULL DEFAULT '0',
  `analysis_datalog` mediumtext NOT NULL,
  `analysis_rerunresults` tinyint(1) NOT NULL,
  `analysis_result` varchar(50) DEFAULT NULL,
  `analysis_resultmessage` text,
  `analysis_numseries` int(11) DEFAULT NULL,
  `analysis_swversion` varchar(255) NOT NULL,
  `analysis_hostname` varchar(255) NOT NULL,
  `analysis_disksize` double NOT NULL DEFAULT '0',
  `analysis_numfiles` int(11) NOT NULL,
  `analysis_startdate` timestamp NULL DEFAULT NULL,
  `analysis_clusterstartdate` timestamp NULL DEFAULT NULL,
  `analysis_clusterenddate` timestamp NULL DEFAULT NULL,
  `analysis_enddate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`analysis_id`),
  UNIQUE KEY `pipeline_id_2` (`pipeline_id`,`pipeline_version`,`study_id`),
  KEY `study_id` (`study_id`),
  KEY `pipeline_id` (`pipeline_id`),
  KEY `analysis_status` (`analysis_status`),
  KEY `analysis_disksize` (`analysis_disksize`),
  KEY `pipeline_dependency` (`pipeline_dependency`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Table structure for table `analysis2`
--

CREATE TABLE `analysis2` (
  `analysis_id` bigint(11) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT '0',
  `pipeline_dependency` int(11) NOT NULL,
  `study_id` int(11) NOT NULL,
  `analysis_qsubid` bigint(20) unsigned NOT NULL,
  `analysis_status` enum('complete','pending','processing','error','submitted','','notcompleted','NoMatchingStudies','rerunresults') DEFAULT NULL,
  `analysis_statusmessage` varchar(255) DEFAULT NULL,
  `analysis_statusdatetime` timestamp NULL DEFAULT NULL,
  `analysis_notes` text NOT NULL,
  `analysis_iscomplete` tinyint(1) NOT NULL,
  `analysis_datalog` mediumtext NOT NULL,
  `analysis_rerunresults` tinyint(1) NOT NULL,
  `analysis_result` varchar(50) DEFAULT NULL,
  `analysis_resultmessage` text,
  `analysis_numseries` int(11) DEFAULT NULL,
  `analysis_swversion` varchar(255) NOT NULL,
  `analysis_hostname` varchar(255) NOT NULL,
  `analysis_disksize` double NOT NULL DEFAULT '0',
  `analysis_startdate` timestamp NULL DEFAULT NULL,
  `analysis_clusterstartdate` timestamp NULL DEFAULT NULL,
  `analysis_clusterenddate` timestamp NULL DEFAULT NULL,
  `analysis_enddate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`analysis_id`),
  UNIQUE KEY `pipeline_id_2` (`pipeline_id`,`pipeline_version`,`study_id`),
  KEY `study_id` (`study_id`),
  KEY `pipeline_id` (`pipeline_id`),
  KEY `analysis_status` (`analysis_status`),
  KEY `analysis_disksize` (`analysis_disksize`),
  KEY `pipeline_dependency` (`pipeline_dependency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `analysis3`
--

CREATE TABLE `analysis3` (
  `analysis_id` bigint(11) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT '0',
  `pipeline_dependency` int(11) NOT NULL,
  `study_id` int(11) NOT NULL,
  `analysis_qsubid` bigint(20) unsigned NOT NULL,
  `analysis_status` enum('complete','pending','processing','error','submitted','','notcompleted','NoMatchingStudies','rerunresults') DEFAULT NULL,
  `analysis_statusmessage` varchar(255) DEFAULT NULL,
  `analysis_statusdatetime` timestamp NULL DEFAULT NULL,
  `analysis_notes` text NOT NULL,
  `analysis_iscomplete` tinyint(1) NOT NULL,
  `analysis_datalog` mediumtext NOT NULL,
  `analysis_rerunresults` tinyint(1) NOT NULL,
  `analysis_result` varchar(50) DEFAULT NULL,
  `analysis_resultmessage` text,
  `analysis_numseries` int(11) DEFAULT NULL,
  `analysis_swversion` varchar(255) NOT NULL,
  `analysis_hostname` varchar(255) NOT NULL,
  `analysis_disksize` double NOT NULL DEFAULT '0',
  `analysis_startdate` timestamp NULL DEFAULT NULL,
  `analysis_clusterstartdate` timestamp NULL DEFAULT NULL,
  `analysis_clusterenddate` timestamp NULL DEFAULT NULL,
  `analysis_enddate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`analysis_id`),
  UNIQUE KEY `pipeline_id_2` (`pipeline_id`,`pipeline_version`,`study_id`),
  KEY `study_id` (`study_id`),
  KEY `pipeline_id` (`pipeline_id`),
  KEY `analysis_status` (`analysis_status`),
  KEY `analysis_disksize` (`analysis_disksize`),
  KEY `pipeline_dependency` (`pipeline_dependency`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `analysis_data`
--

CREATE TABLE `analysis_data` (
  `analysisdata_id` int(11) NOT NULL AUTO_INCREMENT,
  `analysis_id` int(11) NOT NULL,
  `data_id` int(11) NOT NULL,
  `modality` varchar(25) NOT NULL,
  PRIMARY KEY (`analysisdata_id`),
  UNIQUE KEY `analysis_id` (`analysis_id`,`data_id`,`modality`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `analysis_group`
--

CREATE TABLE `analysis_group` (
  `analysisgroup_id` int(11) NOT NULL AUTO_INCREMENT,
  `pipeline_id` int(11) NOT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT '0',
  `pipeline_dependency` int(11) NOT NULL,
  `analysisgroup_status` enum('complete','pending','processing') DEFAULT NULL,
  `analysisgroup_statusmessage` varchar(255) DEFAULT NULL,
  `analysisgroup_statusdatetime` timestamp NULL DEFAULT NULL,
  `analysisgroup_iscomplete` tinyint(1) NOT NULL,
  `analysisgroup_result` varchar(50) DEFAULT NULL,
  `analysisgroup_resultmessage` text,
  `analysisgroup_numstudies` int(11) DEFAULT NULL,
  `analysisgroup_startdate` timestamp NULL DEFAULT NULL,
  `analysisgroup_clusterstartdate` timestamp NULL DEFAULT NULL,
  `analysisgroup_clusterenddate` timestamp NULL DEFAULT NULL,
  `analysisgroup_enddate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`analysisgroup_id`),
  UNIQUE KEY `pipeline_id_2` (`pipeline_id`,`pipeline_version`),
  KEY `pipeline_id` (`pipeline_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `analysis_resultnames`
--

CREATE TABLE `analysis_resultnames` (
  `resultname_id` int(11) NOT NULL AUTO_INCREMENT,
  `result_name` varchar(255) NOT NULL,
  PRIMARY KEY (`resultname_id`),
  UNIQUE KEY `result_name` (`result_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 PACK_KEYS=0;

--
-- Table structure for table `analysis_results`
--

CREATE TABLE `analysis_results` (
  `analysisresults_id` int(11) NOT NULL AUTO_INCREMENT,
  `analysis_id` int(11) NOT NULL,
  `result_type` char(1) NOT NULL COMMENT 'image, file, text, value',
  `result_nameid` int(11) NOT NULL,
  `result_text` text,
  `result_value` double DEFAULT NULL,
  `result_unitid` int(11) NOT NULL,
  `result_filename` text,
  `result_isimportant` tinyint(1) DEFAULT NULL,
  `result_count` smallint(5) unsigned DEFAULT '0',
  PRIMARY KEY (`analysisresults_id`),
  UNIQUE KEY `analysis_id` (`analysis_id`,`result_type`,`result_nameid`),
  KEY `result_value` (`result_value`),
  KEY `result_type` (`result_type`),
  KEY `result_nameid` (`result_nameid`),
  KEY `result_unitid` (`result_unitid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `analysis_resultunit`
--

CREATE TABLE `analysis_resultunit` (
  `resultunit_id` int(11) NOT NULL AUTO_INCREMENT,
  `result_unit` varchar(25) NOT NULL,
  PRIMARY KEY (`resultunit_id`),
  UNIQUE KEY `units` (`result_unit`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `assessment_data`
--

CREATE TABLE `assessment_data` (
  `formdata_id` int(11) NOT NULL AUTO_INCREMENT,
  `formfield_id` int(11) NOT NULL,
  `experiment_id` int(11) NOT NULL,
  `value_text` text,
  `value_number` double NOT NULL,
  `value_string` varchar(255) NOT NULL,
  `value_binary` blob NOT NULL,
  `value_date` date NOT NULL,
  `update_username` varchar(50) NOT NULL COMMENT 'last username to change this value',
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`formdata_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `assessment_formfields`
--

CREATE TABLE `assessment_formfields` (
  `formfield_id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) DEFAULT NULL,
  `formfield_desc` text COMMENT 'The question description, such as ''DSM score'', or ''Which hand do you use most often''',
  `formfield_values` text COMMENT 'a list of possible values',
  `formfield_datatype` enum('multichoice','singlechoice','string','text','number','date','header','binary','calculation') DEFAULT NULL COMMENT 'multichoice, singlechoice, string, text, number, date, header, binary',
  `formfield_calculation` varchar(255) NOT NULL COMMENT '(q1+q4)/5',
  `formfield_calculationconversion` text NOT NULL COMMENT 'comma seperated list of converting strings into numbers (A=1,B=2, etc)',
  `formfield_haslinebreak` tinyint(1) NOT NULL,
  `formfield_scored` tinyint(1) NOT NULL,
  `formfield_order` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`formfield_id`),
  KEY `fk_formfielddef_formdef1` (`form_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `assessment_forms`
--

CREATE TABLE `assessment_forms` (
  `form_id` int(11) NOT NULL AUTO_INCREMENT,
  `form_title` varchar(100) DEFAULT NULL,
  `form_desc` text,
  `form_creator` varchar(30) NOT NULL COMMENT 'creator username',
  `form_createdate` datetime NOT NULL,
  `form_ispublished` tinyint(1) NOT NULL DEFAULT '0',
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`form_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `experiment_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) DEFAULT NULL,
  `form_id` int(11) DEFAULT NULL,
  `exp_groupid` int(11) NOT NULL,
  `exp_admindate` datetime DEFAULT NULL COMMENT 'Date the experiment was administered',
  `experimentor` varchar(45) DEFAULT NULL COMMENT 'Just a name... anyone could adminisister the experiment, so they need not be registered in the system',
  `rater_username` varchar(25) NOT NULL,
  `label` varchar(255) NOT NULL,
  `notes` text NOT NULL,
  `iscomplete` tinyint(1) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`experiment_id`),
  KEY `fk_experiments_subject_project1` (`enrollment_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `audio_series`
--

CREATE TABLE `audio_series` (
  `audioseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) NOT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_size` double NOT NULL,
  `series_notes` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL,
  `audio_desc` text NOT NULL,
  `audio_cputime` double NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`audioseries_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `audit_results`
--

CREATE TABLE `audit_results` (
  `auditresult_id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_num` int(11) NOT NULL,
  `compare_direction` enum('dbtofile','filetodb','consistency','orphan') NOT NULL,
  `problem` enum('filecountmismatch','namemismatch','seriesdescmismatch','nonconsecutiveseries','orphan_noparentstudy','orphan_noparentsubject','subjectmissing','studymissing','seriesmissing','invalidprojectid','blankmodality','seriesdatatypemissing','dicommismatch') NOT NULL,
  `mismatch` varchar(255) NOT NULL,
  `mismatchcount` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `study_id` int(11) NOT NULL,
  `modality` varchar(50) NOT NULL,
  `series_id` int(11) NOT NULL,
  `subject_uid` varchar(20) NOT NULL,
  `study_num` int(11) NOT NULL,
  `series_num` int(11) NOT NULL,
  `data_type` varchar(50) NOT NULL,
  `file_numfiles` int(11) NOT NULL,
  `db_numfiles` int(11) NOT NULL,
  `file_string` varchar(255) NOT NULL,
  `db_string` varchar(255) NOT NULL,
  `audit_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`auditresult_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `binary_series`
--

CREATE TABLE `binary_series` (
  `binaryseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_size` double NOT NULL,
  `series_numfiles` int(11) NOT NULL,
  `series_description` varchar(255) NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`binaryseries_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `changelog`
--

CREATE TABLE `changelog` (
  `changelog_id` int(11) NOT NULL AUTO_INCREMENT,
  `performing_userid` int(11) NOT NULL,
  `affected_userid` int(11) NOT NULL,
  `affected_instanceid` int(11) NOT NULL,
  `affected_siteid` int(11) NOT NULL,
  `affected_projectid` int(11) NOT NULL,
  `affected_subjectid` int(11) NOT NULL,
  `affected_enrollmentid` int(11) NOT NULL,
  `affected_studyid` int(11) NOT NULL,
  `affected_seriesid` int(11) NOT NULL,
  `change_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `change_event` enum('changepassword','changeprojectpermissions','changeprojectname','changeuserinfo') NOT NULL,
  `change_desc` text NOT NULL,
  PRIMARY KEY (`changelog_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `common`
--

CREATE TABLE `common` (
  `common_id` int(11) NOT NULL AUTO_INCREMENT,
  `common_type` enum('number','file','text') NOT NULL,
  `common_group` varchar(100) NOT NULL,
  `common_name` varchar(100) NOT NULL,
  `common_desc` text NOT NULL,
  `common_number` double NOT NULL,
  `common_text` text NOT NULL,
  `common_file` varchar(255) NOT NULL,
  `common_size` int(11) NOT NULL,
  PRIMARY KEY (`common_id`),
  UNIQUE KEY `common_group` (`common_group`,`common_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `consent_series`
--

CREATE TABLE `consent_series` (
  `consentseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` varchar(255) NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`consentseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `contact_id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_fullname` varchar(255) NOT NULL,
  `contact_title` varchar(255) NOT NULL,
  `contact_address1` varchar(255) NOT NULL,
  `contact_address2` varchar(255) NOT NULL,
  `contact_address3` varchar(255) NOT NULL,
  `contact_city` varchar(255) NOT NULL,
  `contact_state` varchar(255) NOT NULL,
  `contact_zip` varchar(255) NOT NULL,
  `contact_country` varchar(255) NOT NULL,
  `contact_phone1` varchar(255) NOT NULL,
  `contact_phone2` varchar(255) NOT NULL,
  `contact_phone3` varchar(255) NOT NULL,
  `contact_email1` varchar(255) NOT NULL,
  `contact_email2` varchar(255) NOT NULL,
  `contact_email3` varchar(255) NOT NULL,
  `contact_website` varchar(255) NOT NULL,
  `contact_company` varchar(255) NOT NULL,
  `contact_department` varchar(255) NOT NULL,
  PRIMARY KEY (`contact_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `cr_series`
--

CREATE TABLE `cr_series` (
  `crseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`crseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `cs_prefs`
--

CREATE TABLE `cs_prefs` (
  `csprefs_id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `description` text NOT NULL,
  `shortname` varchar(255) NOT NULL,
  `extralines` text NOT NULL,
  `startdate` datetime NOT NULL,
  `enddate` datetime NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `do_dicomconvert` tinyint(1) NOT NULL,
  `do_reorient` tinyint(1) NOT NULL,
  `do_realign` tinyint(1) NOT NULL,
  `do_msdcalc` tinyint(1) NOT NULL,
  `do_coregister` tinyint(1) NOT NULL,
  `do_slicetime` tinyint(1) NOT NULL,
  `do_normalize` tinyint(1) NOT NULL,
  `do_smooth` tinyint(1) NOT NULL,
  `do_artrepair` tinyint(1) NOT NULL,
  `do_filter` tinyint(1) NOT NULL,
  `do_segment` tinyint(1) NOT NULL,
  `do_behmatchup` tinyint(1) NOT NULL,
  `do_stats` tinyint(1) NOT NULL,
  `do_censor` tinyint(1) NOT NULL,
  `do_autoslice` tinyint(1) NOT NULL,
  `do_db` tinyint(1) NOT NULL,
  `dicom_filepattern` varchar(255) NOT NULL,
  `dicom_format` varchar(255) NOT NULL,
  `dicom_writefileprefix` varchar(255) NOT NULL,
  `dicom_outputdir` text NOT NULL,
  `reorient_pattern` varchar(255) NOT NULL,
  `reorient_vector` varchar(255) NOT NULL,
  `reorient_write` tinyint(1) NOT NULL,
  `realign_coregister` tinyint(1) NOT NULL,
  `realign_reslice` tinyint(1) NOT NULL,
  `realign_useinrialign` tinyint(1) NOT NULL,
  `realign_pattern` varchar(255) NOT NULL,
  `realign_inri_rho` varchar(255) NOT NULL,
  `realign_inri_cutoff` double NOT NULL,
  `realign_inri_quality` double NOT NULL,
  `realign_fwhm` double NOT NULL,
  `realign_tomean` tinyint(1) NOT NULL,
  `realign_pathtoweight` varchar(255) NOT NULL,
  `realign_writeresliceimg` tinyint(1) NOT NULL,
  `realign_writemean` tinyint(1) NOT NULL,
  `coreg_run` tinyint(1) NOT NULL,
  `coreg_runreslice` tinyint(1) NOT NULL,
  `coreg_ref` varchar(255) NOT NULL,
  `coreg_source` varchar(255) NOT NULL,
  `coreg_otherpattern` varchar(255) NOT NULL,
  `coreg_writeref` varchar(255) NOT NULL,
  `slicetime_pattern` varchar(255) NOT NULL,
  `slicetime_sliceorder` varchar(255) NOT NULL,
  `slicetime_refslice` varchar(255) NOT NULL,
  `slicetime_ta` varchar(255) NOT NULL,
  `norm_determineparams` tinyint(1) NOT NULL,
  `norm_writeimages` tinyint(1) NOT NULL,
  `norm_paramstemplate` varchar(255) NOT NULL,
  `norm_paramspattern` varchar(255) NOT NULL,
  `norm_paramssourceweight` varchar(255) NOT NULL,
  `norm_paramsmatname` varchar(255) NOT NULL,
  `norm_writepattern` varchar(255) NOT NULL,
  `norm_writematname` varchar(255) NOT NULL,
  `smooth_kernel` varchar(255) NOT NULL,
  `smooth_pattern` varchar(255) NOT NULL,
  `art_pattern` varchar(255) NOT NULL,
  `filter_pattern` varchar(255) NOT NULL,
  `filter_cuttofffreq` double NOT NULL,
  `segment_pattern` varchar(255) NOT NULL,
  `segment_outputgm` varchar(255) NOT NULL,
  `segment_outputwm` varchar(255) NOT NULL,
  `segment_outputcsf` varchar(255) NOT NULL,
  `segment_outputbiascor` int(11) NOT NULL,
  `segment_outputcleanup` int(11) NOT NULL,
  `is_fulltext` tinyint(1) NOT NULL,
  `fulltext` text NOT NULL,
  `beh_queue` varchar(255) NOT NULL,
  `beh_digits` varchar(50) NOT NULL,
  `stats_makeasciis` tinyint(1) NOT NULL,
  `stats_asciiscriptpath` text NOT NULL,
  `stats_behdirname` varchar(255) NOT NULL,
  `stats_relativepath` tinyint(1) NOT NULL,
  `stats_dirname` varchar(255) NOT NULL,
  `stats_pattern` varchar(255) NOT NULL,
  `stats_behunits` varchar(255) NOT NULL,
  `stats_volterra` tinyint(1) NOT NULL,
  `stats_basisfunction` int(11) NOT NULL,
  `stats_onsetfiles` text NOT NULL,
  `stats_durationfiles` text NOT NULL,
  `stats_regressorfiles` text NOT NULL,
  `stats_regressornames` text NOT NULL,
  `stats_paramnames` text NOT NULL,
  `stats_paramorders` text NOT NULL,
  `stats_paramfiles` text NOT NULL,
  `stats_censorfiles` text NOT NULL,
  `stats_fit_xbflength` int(11) NOT NULL,
  `stats_fit_xbforder` int(11) NOT NULL,
  `stats_timemodulation` text NOT NULL,
  `stats_parametricmodulation` text NOT NULL,
  `stats_globalfx` tinyint(1) NOT NULL,
  `stats_highpasscutoff` int(11) NOT NULL,
  `stats_serialcorr` tinyint(1) NOT NULL,
  `stats_tcontrasts` text NOT NULL,
  `stats_tcon_columnlabels` text NOT NULL,
  `stats_tcontrastnames` text NOT NULL,
  `autoslice_cons` varchar(255) NOT NULL,
  `autoslice_p` double NOT NULL,
  `autoslice_background` varchar(255) NOT NULL,
  `autoslice_slices` varchar(255) NOT NULL,
  `autoslice_emailcons` varchar(255) NOT NULL,
  `db_overwritebeta` tinyint(1) NOT NULL,
  `db_fileprefix` varchar(255) NOT NULL,
  `db_betanums` text NOT NULL,
  `db_threshold` double NOT NULL,
  `db_smoothkernel` varchar(255) NOT NULL,
  `db_imcalcs` text NOT NULL,
  `db_imnames` text NOT NULL,
  PRIMARY KEY (`csprefs_id`),
  UNIQUE KEY `shortname` (`shortname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `ct_series`
--

CREATE TABLE `ct_series` (
  `ctseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_contrastbolusagent` varchar(255) NOT NULL,
  `series_bodypartexamined` varchar(255) NOT NULL,
  `series_scanoptions` varchar(255) NOT NULL,
  `series_spacingz` double NOT NULL,
  `series_spacingx` double NOT NULL,
  `series_spacingy` double NOT NULL,
  `series_imgrows` int(11) NOT NULL,
  `series_imgcols` int(11) NOT NULL,
  `series_imgslices` int(11) NOT NULL,
  `series_kvp` double NOT NULL,
  `series_datacollectiondiameter` double NOT NULL,
  `series_contrastbolusroute` varchar(255) NOT NULL,
  `series_rotationdirection` varchar(10) NOT NULL,
  `series_exposuretime` double NOT NULL,
  `series_xraytubecurrent` double NOT NULL,
  `series_filtertype` varchar(255) NOT NULL,
  `series_generatorpower` double NOT NULL,
  `series_convolutionkernel` varchar(255) NOT NULL,
  `numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_datatype` varchar(50) NOT NULL,
  `series_status` varchar(50) NOT NULL,
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_numfiles` int(11) NOT NULL,
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ctseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `data_requests`
--

CREATE TABLE `data_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `req_username` varchar(50) NOT NULL,
  `req_ip` varchar(30) NOT NULL,
  `req_groupid` int(11) NOT NULL,
  `req_pipelinedownloadid` int(11) NOT NULL COMMENT 'filled if this is part of a pipeline download',
  `req_modality` varchar(20) NOT NULL,
  `req_downloadimaging` tinyint(1) NOT NULL,
  `req_downloadbeh` tinyint(1) NOT NULL,
  `req_downloadqc` tinyint(1) NOT NULL,
  `req_destinationtype` varchar(20) NOT NULL COMMENT 'nfs, localftp, remoteftp',
  `req_nfsdir` varchar(255) NOT NULL,
  `req_seriesid` int(11) NOT NULL,
  `req_subjectprojectid` int(11) NOT NULL,
  `req_filetype` varchar(20) NOT NULL,
  `req_gzip` tinyint(1) NOT NULL,
  `req_anonymize` int(11) NOT NULL,
  `req_preserveseries` tinyint(1) NOT NULL,
  `req_dirformat` varchar(50) NOT NULL,
  `req_timepoint` int(11) NOT NULL,
  `req_ftpusername` varchar(50) NOT NULL,
  `req_ftppassword` varchar(50) NOT NULL,
  `req_ftpserver` varchar(100) NOT NULL,
  `req_ftpport` int(11) NOT NULL DEFAULT '21',
  `req_ftppath` varchar(255) NOT NULL,
  `req_ftplog` text NOT NULL,
  `req_nidbusername` varchar(255) NOT NULL,
  `req_nidbpassword` varchar(255) NOT NULL,
  `req_nidbserver` varchar(255) NOT NULL,
  `req_nidbinstanceid` int(11) NOT NULL DEFAULT '0',
  `req_nidbsiteid` int(11) NOT NULL DEFAULT '0',
  `req_nidbprojectid` int(11) NOT NULL DEFAULT '0',
  `req_downloadid` int(11) NOT NULL,
  `req_behonly` tinyint(1) NOT NULL,
  `req_behformat` varchar(35) NOT NULL,
  `req_behdirrootname` varchar(50) NOT NULL,
  `req_behdirseriesname` varchar(255) NOT NULL,
  `req_date` timestamp NULL DEFAULT NULL,
  `req_completedate` timestamp NULL DEFAULT NULL,
  `req_cputime` double NOT NULL,
  `req_status` varchar(25) NOT NULL,
  `req_results` text NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `req_groupid` (`req_groupid`),
  KEY `req_date` (`req_date`),
  KEY `req_status` (`req_status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `eeg_series`
--

CREATE TABLE `eeg_series` (
  `eegseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `series_status` varchar(255) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`eegseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`),
  KEY `series_desc` (`series_desc`),
  KEY `series_protocol` (`series_protocol`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `enroll_subgroup` varchar(50) NOT NULL,
  `enroll_startdate` datetime DEFAULT NULL,
  `enroll_enddate` datetime NOT NULL,
  `irb_consent` blob COMMENT 'scanned image of the IRB consent form',
  `lastupdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`enrollment_id`),
  KEY `project_id` (`project_id`,`subject_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `et_series`
--

CREATE TABLE `et_series` (
  `etseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`etseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `families`
--

CREATE TABLE `families` (
  `family_id` int(11) NOT NULL AUTO_INCREMENT,
  `family_uid` varchar(10) NOT NULL,
  `family_createdate` datetime NOT NULL,
  `family_name` varchar(255) NOT NULL,
  `family_isactive` tinyint(1) NOT NULL DEFAULT '1',
  `family_lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`family_id`),
  UNIQUE KEY `family_uid` (`family_uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `family_members`
--

CREATE TABLE `family_members` (
  `familymember_id` int(11) NOT NULL AUTO_INCREMENT,
  `family_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `fm_createdate` datetime NOT NULL,
  PRIMARY KEY (`familymember_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `fileio_requests`
--

CREATE TABLE `fileio_requests` (
  `fileiorequest_id` int(11) NOT NULL AUTO_INCREMENT,
  `fileio_operation` enum('copy','delete','move','detach','anonymize','createlinks','rearchive','rearchivesubject','rearchiveidonly','rearchivesubjectidonly') NOT NULL,
  `data_type` enum('pipeline','analysis','subject','study','series','groupanalysis') NOT NULL,
  `data_id` int(11) NOT NULL,
  `data_destination` varchar(255) NOT NULL,
  `modality` varchar(50) NOT NULL,
  `anonymize_fields` text NOT NULL,
  `request_status` enum('pending','deleting','complete','error') NOT NULL DEFAULT 'pending',
  `request_message` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `requestdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `startdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enddate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`fileiorequest_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `group_data`
--

CREATE TABLE `group_data` (
  `subjectgroup_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `data_id` int(11) NOT NULL,
  `modality` varchar(10) NOT NULL,
  PRIMARY KEY (`subjectgroup_id`),
  UNIQUE KEY `group_id` (`group_id`,`data_id`,`modality`),
  KEY `idx_group_data` (`modality`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  `group_type` varchar(25) NOT NULL COMMENT 'subject, study, series',
  `group_owner` int(11) NOT NULL COMMENT 'user_id of the group owner',
  `instance_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_name` (`group_name`,`group_owner`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `import_received`
--

CREATE TABLE `import_received` (
  `importreceived_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `import_transactionid` int(11) NOT NULL,
  `import_uploadid` int(11) NOT NULL,
  `import_filename` varchar(255) NOT NULL,
  `import_filesize` bigint(20) NOT NULL,
  `import_datetime` datetime NOT NULL,
  `import_md5` varchar(40) NOT NULL,
  `import_success` tinyint(1) NOT NULL,
  `import_userid` int(11) NOT NULL,
  `import_instanceid` int(11) NOT NULL,
  `import_projectid` int(11) NOT NULL,
  `import_siteid` int(11) NOT NULL,
  `import_route` varchar(255) NOT NULL,
  PRIMARY KEY (`importreceived_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `import_requestdirs`
--

CREATE TABLE `import_requestdirs` (
  `importrequestdir_id` int(11) NOT NULL,
  `importrequest_id` int(11) NOT NULL,
  `dir_num` int(11) NOT NULL,
  `dir_type` enum('modality','seriesdesc','seriesnum','studydesc','studydatetime','thefiles','beh','subjectid') NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `import_requests`
--

CREATE TABLE `import_requests` (
  `importrequest_id` int(11) NOT NULL AUTO_INCREMENT,
  `import_transactionid` int(11) NOT NULL,
  `import_datatype` enum('dicom','measures','nondicom','parrec','nifti','eeg') NOT NULL,
  `import_modality` varchar(50) NOT NULL,
  `import_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `import_status` varchar(50) NOT NULL,
  `import_message` varchar(255) NOT NULL,
  `import_startdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `import_enddate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `import_equipment` varchar(255) NOT NULL,
  `import_siteid` int(11) NOT NULL,
  `import_projectid` int(11) NOT NULL,
  `import_instanceid` int(11) NOT NULL,
  `import_uuid` varchar(255) NOT NULL,
  `import_anonymize` tinyint(1) NOT NULL,
  `import_permanent` tinyint(1) NOT NULL,
  `import_matchidonly` tinyint(1) NOT NULL,
  `import_filename` varchar(255) NOT NULL,
  `import_userid` int(11) NOT NULL,
  `import_fileisseries` tinyint(1) NOT NULL COMMENT 'if each file should be its own series',
  PRIMARY KEY (`importrequest_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `import_transactions`
--

CREATE TABLE `import_transactions` (
  `importtrans_id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_startdate` datetime NOT NULL,
  `transaction_enddate` datetime NOT NULL,
  `transaction_status` varchar(20) NOT NULL,
  `transaction_source` varchar(255) NOT NULL,
  `transaction_username` varchar(255) NOT NULL,
  PRIMARY KEY (`importtrans_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `importlogs`
--

CREATE TABLE `importlogs` (
  `importlog_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `filename_orig` text NOT NULL,
  `filename_new` varchar(255) NOT NULL,
  `fileformat` varchar(255) NOT NULL,
  `importstartdate` datetime NOT NULL,
  `result` text NOT NULL,
  `importid` int(11) NOT NULL,
  `importgroupid` int(11) NOT NULL,
  `importsiteid` int(11) NOT NULL,
  `importprojectid` int(11) NOT NULL,
  `importpermanent` tinyint(1) NOT NULL,
  `importanonymize` tinyint(1) NOT NULL,
  `importuuid` varchar(255) NOT NULL,
  `patientid_orig` varchar(50) NOT NULL,
  `modality_orig` varchar(255) NOT NULL,
  `patientname_orig` varchar(255) NOT NULL,
  `patientdob_orig` varchar(255) NOT NULL,
  `patientsex_orig` varchar(255) NOT NULL,
  `stationname_orig` varchar(255) NOT NULL,
  `institution_orig` varchar(255) NOT NULL,
  `studydatetime_orig` varchar(255) NOT NULL,
  `seriesdatetime_orig` varchar(255) NOT NULL,
  `seriesnumber_orig` varchar(255) NOT NULL,
  `studydesc_orig` varchar(255) NOT NULL,
  `seriesdesc_orig` varchar(255) NOT NULL,
  `protocol_orig` varchar(255) NOT NULL,
  `patientage_orig` varchar(255) NOT NULL,
  `slicenumber_orig` varchar(255) NOT NULL,
  `instancenumber_orig` varchar(255) NOT NULL,
  `slicelocation_orig` varchar(255) NOT NULL,
  `acquisitiondatetime_orig` varchar(255) NOT NULL,
  `contentdatetime_orig` varchar(255) NOT NULL,
  `sopinstance_orig` varchar(255) NOT NULL,
  `modality_new` varchar(255) NOT NULL,
  `patientname_new` varchar(255) NOT NULL,
  `patientdob_new` varchar(255) NOT NULL,
  `patientsex_new` varchar(255) NOT NULL,
  `stationname_new` varchar(255) NOT NULL,
  `studydatetime_new` varchar(255) NOT NULL,
  `seriesdatetime_new` varchar(255) NOT NULL,
  `seriesnumber_new` varchar(255) NOT NULL,
  `studydesc_new` varchar(255) NOT NULL,
  `seriesdesc_new` varchar(255) NOT NULL,
  `protocol_new` varchar(255) NOT NULL,
  `patientage_new` varchar(255) NOT NULL,
  `subject_uid` varchar(255) NOT NULL,
  `study_num` int(11) NOT NULL,
  `subjectid` int(11) NOT NULL,
  `studyid` int(11) NOT NULL,
  `seriesid` int(11) NOT NULL,
  `enrollmentid` int(11) NOT NULL,
  `project_number` varchar(255) NOT NULL,
  `series_created` tinyint(1) NOT NULL,
  `study_created` tinyint(1) NOT NULL,
  `subject_created` tinyint(1) NOT NULL,
  `family_created` tinyint(1) NOT NULL,
  `enrollment_created` tinyint(1) NOT NULL,
  `overwrote_existing` tinyint(1) NOT NULL,
  PRIMARY KEY (`importlog_id`),
  KEY `importstartdate` (`importstartdate`),
  KEY `stationname_orig` (`stationname_orig`),
  KEY `studydatetime_orig` (`studydatetime_orig`),
  KEY `importstartdate_2` (`importstartdate`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `instance`
--

CREATE TABLE `instance` (
  `instance_id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_uid` varchar(25) NOT NULL,
  `instance_name` varchar(255) NOT NULL,
  `instance_ownerid` int(11) NOT NULL,
  `instance_default` tinyint(1) NOT NULL,
  PRIMARY KEY (`instance_id`),
  UNIQUE KEY `instance_name` (`instance_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `instance_billing`
--

CREATE TABLE `instance_billing` (
  `billingitem_id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `pricing_id` int(11) NOT NULL,
  `quantity` double NOT NULL,
  `bill_datestart` datetime NOT NULL,
  `bill_dateend` datetime NOT NULL,
  `bill_notes` text NOT NULL,
  PRIMARY KEY (`billingitem_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `instance_contact`
--

CREATE TABLE `instance_contact` (
  `instancecontact_id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`instancecontact_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `instance_invoice`
--

CREATE TABLE `instance_invoice` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `invoice_date` datetime NOT NULL,
  `invoice_paid` tinyint(1) NOT NULL,
  `invoice_paiddate` datetime NOT NULL,
  `invoice_paymethod` varchar(255) NOT NULL,
  PRIMARY KEY (`invoice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `instance_pricing`
--

CREATE TABLE `instance_pricing` (
  `pricing_id` int(11) NOT NULL AUTO_INCREMENT,
  `pricing_startdate` datetime NOT NULL,
  `pricing_enddate` datetime NOT NULL,
  `pricing_itemname` varchar(255) NOT NULL,
  `pricing_unit` varchar(255) NOT NULL,
  `pricing_price` double NOT NULL,
  `pricing_comments` text NOT NULL,
  `pricing_internal` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`pricing_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `instance_usage`
--

CREATE TABLE `instance_usage` (
  `instanceusage_id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL,
  `usage_date` date NOT NULL,
  `pricing_id` int(11) NOT NULL,
  `usage_amount` double NOT NULL,
  PRIMARY KEY (`instanceusage_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `manual_qa`
--

CREATE TABLE `manual_qa` (
  `manualqa_id` int(11) NOT NULL AUTO_INCREMENT,
  `series_id` int(11) NOT NULL,
  `modality` varchar(10) NOT NULL,
  `rater_id` int(11) NOT NULL,
  `value` int(11) NOT NULL COMMENT '0,1, or 2',
  PRIMARY KEY (`manualqa_id`),
  UNIQUE KEY `series_id` (`series_id`,`modality`,`rater_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `measureinstruments`
--

CREATE TABLE `measureinstruments` (
  `measureinstrument_id` int(11) NOT NULL AUTO_INCREMENT,
  `instrument_name` varchar(255) NOT NULL,
  `instrument_group` varchar(255) NOT NULL,
  `instrument_notes` text NOT NULL COMMENT 'mostly used for coding instructions (1=female, 2=male, etc)',
  PRIMARY KEY (`measureinstrument_id`),
  UNIQUE KEY `measure_name` (`instrument_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `measurenames`
--

CREATE TABLE `measurenames` (
  `measurename_id` int(11) NOT NULL AUTO_INCREMENT,
  `measure_name` varchar(255) NOT NULL,
  `measure_group` varchar(255) NOT NULL,
  `measure_multiple` tinyint(1) NOT NULL COMMENT 'Indicates if a measure can have more than one entry',
  `measure_notes` text NOT NULL COMMENT 'mostly used for coding instructions (1=female, 2=male, etc)',
  PRIMARY KEY (`measurename_id`),
  UNIQUE KEY `measure_name` (`measure_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `measures`
--

CREATE TABLE `measures` (
  `measure_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `measure_dateentered` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `measure_dateentered2` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `instrumentname_id` int(11) NOT NULL,
  `measurename_id` int(11) NOT NULL,
  `measure_type` enum('s','n') NOT NULL,
  `measure_valuestring` varchar(255) NOT NULL,
  `measure_valuenum` double NOT NULL,
  `measure_notes` text NOT NULL,
  `measure_instrument` varchar(255) NOT NULL,
  `measure_rater` varchar(50) NOT NULL,
  `measure_rater2` varchar(50) NOT NULL,
  `measure_isdoubleentered` tinyint(1) NOT NULL,
  `measure_datecomplete` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `measure_lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`measure_id`),
  UNIQUE KEY `enrollment_id` (`enrollment_id`,`measurename_id`,`measure_type`,`measure_valuestring`,`measure_valuenum`,`measure_isdoubleentered`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `modalities`
--

CREATE TABLE `modalities` (
  `mod_id` int(11) NOT NULL AUTO_INCREMENT,
  `mod_code` varchar(15) NOT NULL,
  `mod_desc` varchar(255) NOT NULL,
  `mod_enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`mod_id`),
  UNIQUE KEY `pk_modalities_0` (`mod_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `modality_protocol`
--

CREATE TABLE `modality_protocol` (
  `modality` varchar(10) NOT NULL,
  `protocol` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `module_prefs`
--

CREATE TABLE `module_prefs` (
  `mp_id` int(11) NOT NULL AUTO_INCREMENT,
  `mp_module` varchar(50) NOT NULL,
  `mp_pref` varchar(255) NOT NULL,
  `mp_value` varchar(255) NOT NULL,
  PRIMARY KEY (`mp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_name` varchar(200) NOT NULL,
  `module_status` varchar(25) NOT NULL,
  `module_numrunning` int(11) NOT NULL DEFAULT '0',
  `module_laststart` datetime NOT NULL,
  `module_laststop` datetime NOT NULL,
  `module_isactive` tinyint(1) NOT NULL,
  PRIMARY KEY (`module_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `mostrecent`
--

CREATE TABLE `mostrecent` (
  `mostrecent_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `study_id` int(11) DEFAULT NULL,
  `mostrecent_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mostrecent_id`),
  UNIQUE KEY `user_id_2` (`user_id`,`study_id`),
  UNIQUE KEY `user_id` (`user_id`,`subject_id`),
  KEY `idx_mostrecent` (`subject_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `mr_qa`
--

CREATE TABLE `mr_qa` (
  `mrqa_id` int(11) NOT NULL AUTO_INCREMENT,
  `mrseries_id` int(11) DEFAULT NULL,
  `io_snr` double DEFAULT NULL,
  `pv_snr` double DEFAULT NULL,
  `move_minx` double DEFAULT NULL,
  `move_miny` double DEFAULT NULL,
  `move_minz` double DEFAULT NULL,
  `move_maxx` double DEFAULT NULL,
  `move_maxy` double DEFAULT NULL,
  `move_maxz` double DEFAULT NULL,
  `acc_minx` double NOT NULL,
  `acc_miny` double NOT NULL,
  `acc_minz` double NOT NULL,
  `acc_maxx` double NOT NULL,
  `acc_maxy` double NOT NULL,
  `acc_maxz` double NOT NULL,
  `rot_minp` double DEFAULT NULL,
  `rot_minr` double DEFAULT NULL,
  `rot_miny` double DEFAULT NULL,
  `rot_maxp` double DEFAULT NULL,
  `rot_maxr` double DEFAULT NULL,
  `rot_maxy` double DEFAULT NULL,
  `motion_rsq` double NOT NULL,
  `cputime` double DEFAULT NULL,
  `status` varchar(25) NOT NULL,
  `lastupdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mrqa_id`),
  KEY `mriseries_id` (`mrseries_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `mr_series`
--

CREATE TABLE `mr_series` (
  `mrseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_datetime` datetime DEFAULT NULL COMMENT '(0008,0021) & (0008,0031)',
  `series_desc` varchar(255) DEFAULT NULL COMMENT 'MP Rage, AOD, etc(0018,1030)',
  `series_altdesc` varchar(255) NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_sequencename` varchar(45) DEFAULT NULL COMMENT 'epfid2d1_64, etc\n(0018,0024)',
  `series_num` int(11) DEFAULT NULL,
  `series_tr` double DEFAULT NULL COMMENT '(0018,0080)',
  `series_te` double DEFAULT NULL COMMENT '(0018,0081)',
  `series_flip` double DEFAULT NULL COMMENT '(0018,1314)',
  `phaseencodedir` varchar(20) NOT NULL COMMENT 'either ROW or COL. when combined with phaseencodeangle, it will give the A>P, R>L etc',
  `phaseencodeangle` double NOT NULL COMMENT 'in radians',
  `PhaseEncodingDirectionPositive` tinyint(1) NOT NULL,
  `series_spacingx` double DEFAULT NULL COMMENT '(0028,0030) field 1',
  `series_spacingy` double DEFAULT NULL COMMENT '(0028,0030) field 2',
  `series_spacingz` double DEFAULT NULL COMMENT '(0018,0050)',
  `series_fieldstrength` double DEFAULT NULL COMMENT '(0018,0087)',
  `img_rows` int(11) DEFAULT NULL COMMENT '(0028,0010)',
  `img_cols` int(11) DEFAULT NULL COMMENT '(0028,0011)',
  `img_slices` int(11) DEFAULT NULL COMMENT 'often derived from the number of dicom files',
  `image_type` varchar(255) NOT NULL,
  `image_comments` varchar(255) NOT NULL,
  `bold_reps` int(11) NOT NULL,
  `numfiles` int(11) DEFAULT NULL,
  `series_size` double NOT NULL COMMENT 'number of bytes',
  `data_type` varchar(20) NOT NULL,
  `is_derived` tinyint(1) NOT NULL DEFAULT '0',
  `numfiles_beh` int(11) NOT NULL,
  `beh_size` double NOT NULL,
  `series_notes` text NOT NULL,
  `series_status` varchar(20) DEFAULT NULL COMMENT 'pending, processing, complete',
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mrseries_id`),
  UNIQUE KEY `study_id_2` (`study_id`,`series_num`),
  KEY `series_desc` (`series_desc`),
  KEY `study_id` (`study_id`),
  KEY `series_protocol` (`series_protocol`),
  KEY `series_tr` (`series_tr`),
  KEY `series_altdesc` (`series_altdesc`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 PACK_KEYS=0;

--
-- Table structure for table `mr_studyqa`
--

CREATE TABLE `mr_studyqa` (
  `mrstudyqa_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `t1_numcompared` int(11) NOT NULL,
  `t1_comparedseriesids` text NOT NULL,
  `t1_derivedseriesid` int(11) NOT NULL,
  `t1_comparisonmatrix` text NOT NULL,
  `t1_matrixremovethreshold` double NOT NULL,
  `t1_snrremovethreshold` double NOT NULL,
  `cputime` double DEFAULT NULL,
  `lastupdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mrstudyqa_id`),
  KEY `mriseries_id` (`study_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `nidb_sites`
--

CREATE TABLE `nidb_sites` (
  `site_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_uid` varchar(20) NOT NULL,
  `site_uuid` varchar(255) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `site_address` varchar(255) NOT NULL,
  `site_contact` varchar(255) NOT NULL,
  PRIMARY KEY (`site_id`),
  UNIQUE KEY `uuid` (`site_uuid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `nm_series`
--

CREATE TABLE `nm_series` (
  `nmseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`nmseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notif_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notif_type` varchar(50) NOT NULL,
  `notif_protocol` varchar(100) NOT NULL,
  `notif_snrvalue` double NOT NULL,
  `notif_snrcriteria` varchar(5) NOT NULL,
  `notif_snrvariable` double NOT NULL,
  PRIMARY KEY (`notif_id`),
  KEY `idx_notifications` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `ot_series`
--

CREATE TABLE `ot_series` (
  `otseries_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `bold_reps` int(11) NOT NULL,
  `modality` varchar(50) NOT NULL,
  `data_type` varchar(255) NOT NULL,
  `series_size` double NOT NULL COMMENT 'number of bytes',
  `series_status` varchar(20) DEFAULT NULL COMMENT 'pending, processing, complete',
  `series_notes` varchar(255) NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`otseries_id`),
  KEY `fk_mri_series_studies1` (`study_id`),
  KEY `series_desc` (`series_desc`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `pipeline_data_def`
--

CREATE TABLE `pipeline_data_def` (
  `pipelinedatadef_id` int(11) NOT NULL AUTO_INCREMENT,
  `pipeline_id` int(11) NOT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT '0',
  `pdd_order` int(11) NOT NULL,
  `pdd_seriescriteria` enum('all','first','last','largestsize','smallestsize','highestiosnr','highestpvsnr','earliest','latest') NOT NULL DEFAULT 'all',
  `pdd_type` enum('primary','associated') NOT NULL DEFAULT 'primary',
  `pdd_assoctype` enum('nearesttime','samestudytype') NOT NULL,
  `pdd_protocol` text NOT NULL,
  `pdd_imagetype` varchar(255) NOT NULL,
  `pdd_modality` varchar(255) NOT NULL,
  `pdd_dataformat` varchar(30) NOT NULL,
  `pdd_gzip` tinyint(1) NOT NULL DEFAULT '0',
  `pdd_location` varchar(255) NOT NULL COMMENT 'path to the data, relative to the root subject directory',
  `pdd_useseries` tinyint(1) NOT NULL,
  `pdd_preserveseries` tinyint(1) NOT NULL,
  `pdd_behformat` varchar(50) NOT NULL,
  `pdd_behdir` varchar(255) NOT NULL,
  `pdd_enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`pipelinedatadef_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `pipeline_dependencies`
--

CREATE TABLE `pipeline_dependencies` (
  `pipelinedep_id` int(11) NOT NULL AUTO_INCREMENT,
  `pipeline_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`pipelinedep_id`),
  UNIQUE KEY `pipeline_id` (`pipeline_id`,`parent_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `pipeline_download`
--

CREATE TABLE `pipeline_download` (
  `pipelinedownload_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pipelinedownload_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `pipeline_groups`
--

CREATE TABLE `pipeline_groups` (
  `pipelinegroup_id` int(11) NOT NULL AUTO_INCREMENT,
  `pipeline_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`pipelinegroup_id`),
  UNIQUE KEY `pipeline_id` (`pipeline_id`,`group_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `pipeline_status`
--

CREATE TABLE `pipeline_status` (
  `pipelinestatus_id` int(11) NOT NULL AUTO_INCREMENT,
  `pipeline_modulerunnum` bigint(20) NOT NULL,
  `pipeline_modulestarttime` datetime NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `pipelinestatus_starttime` datetime NOT NULL,
  `pipelinestatus_stoptime` datetime NOT NULL,
  `pipelinestatus_order` int(11) NOT NULL,
  `pipelinestatus_status` enum('pending','complete','running') NOT NULL,
  `pipelinestatus_result` text NOT NULL,
  `pipelinestatus_lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pipelinestatus_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `pipeline_steps`
--

CREATE TABLE `pipeline_steps` (
  `pipelinestep_id` int(11) NOT NULL AUTO_INCREMENT,
  `pipeline_id` int(11) DEFAULT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT '1',
  `ps_command` text,
  `ps_workingdir` text,
  `ps_order` int(11) DEFAULT NULL,
  `ps_description` varchar(255) DEFAULT NULL,
  `ps_enabled` tinyint(1) NOT NULL,
  `ps_logged` tinyint(1) NOT NULL,
  PRIMARY KEY (`pipelinestep_id`),
  KEY `fk_pipeline_steps_pipelines1` (`pipeline_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `pipelines`
--

CREATE TABLE `pipelines` (
  `pipeline_id` int(11) NOT NULL AUTO_INCREMENT,
  `pipeline_name` varchar(50) NOT NULL,
  `pipeline_desc` varchar(255) DEFAULT NULL,
  `pipeline_admin` int(25) NOT NULL COMMENT 'username',
  `pipeline_createdate` datetime NOT NULL,
  `pipeline_level` int(11) NOT NULL COMMENT '1,2,3, N (first, second, third, Nth level)',
  `pipeline_group` varchar(255) NOT NULL,
  `pipeline_directory` varchar(255) NOT NULL,
  `pipeline_dependency` text NOT NULL,
  `pipeline_dependencylevel` varchar(255) NOT NULL,
  `pipeline_dependencydir` enum('','root','subdir') NOT NULL,
  `pipeline_groupid` text NOT NULL,
  `pipeline_grouptype` varchar(25) NOT NULL,
  `pipeline_dynamicgroupid` int(11) NOT NULL,
  `pipeline_status` varchar(20) NOT NULL,
  `pipeline_statusmessage` varchar(255) NOT NULL,
  `pipeline_laststart` datetime NOT NULL,
  `pipeline_lastfinish` datetime NOT NULL,
  `pipeline_lastcheck` datetime NOT NULL,
  `pipeline_dataand` tinyint(1) NOT NULL DEFAULT '0',
  `pipeline_completefiles` text NOT NULL COMMENT 'comma separated list of files to check to assume the analysis is complete',
  `pipeline_numproc` int(11) NOT NULL COMMENT 'number of concurrent jobs allowed to run',
  `pipeline_queue` varchar(50) NOT NULL,
  `pipeline_submithost` varchar(255) NOT NULL,
  `pipeline_notes` text NOT NULL,
  `pipeline_removedata` tinyint(1) NOT NULL,
  `pipeline_resultsscript` text NOT NULL,
  `pipeline_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `pipeline_testing` tinyint(1) NOT NULL,
  `pipeline_isprivate` tinyint(1) NOT NULL,
  `pipeline_ishidden` tinyint(1) NOT NULL,
  `pipeline_version` int(11) NOT NULL DEFAULT '1',
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pipeline_id`),
  UNIQUE KEY `pipeline_name` (`pipeline_name`,`pipeline_version`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `ppi_series`
--

CREATE TABLE `ppi_series` (
  `ppiseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` varchar(255) NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ppiseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `prescriptionnames`
--

CREATE TABLE `prescriptionnames` (
  `rxname_id` int(11) NOT NULL AUTO_INCREMENT,
  `rx_name` varchar(255) NOT NULL,
  `rx_group` varchar(255) NOT NULL,
  PRIMARY KEY (`rxname_id`),
  UNIQUE KEY `measure_name` (`rx_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `rx_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `rx_startdate` datetime NOT NULL,
  `rx_enddate` datetime NOT NULL,
  `rx_doseamount` double NOT NULL,
  `rx_doseitem` varchar(255) NOT NULL,
  `rx_dosefrequency` varchar(255) NOT NULL,
  `rx_route` varchar(255) NOT NULL COMMENT 'oral, iv, suppository, etc',
  `rxname_id` int(11) NOT NULL,
  `rx_group` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `project_protocol`
--

CREATE TABLE `project_protocol` (
  `projectprotocol_id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `protocolgroup_id` int(11) NOT NULL,
  `pp_criteria` enum('required','recommended','conditional','') NOT NULL,
  `pp_perstudyquantity` int(11) NOT NULL,
  `pp_perprojectquantity` int(11) NOT NULL,
  PRIMARY KEY (`projectprotocol_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` int(11) NOT NULL DEFAULT '0',
  `project_uid` varchar(20) NOT NULL,
  `project_name` varchar(60) DEFAULT NULL,
  `project_admin` int(11) DEFAULT NULL,
  `project_pi` int(11) DEFAULT NULL,
  `project_sharing` char(1) DEFAULT NULL COMMENT 'F = full sharing, access to data\nV = view subjects, experiments, studies only\nP = private, no data seen by others',
  `project_costcenter` varchar(45) DEFAULT NULL,
  `project_startdate` date DEFAULT NULL,
  `project_enddate` date DEFAULT NULL,
  `project_irbapprovaldate` date DEFAULT NULL,
  `project_status` varchar(15) DEFAULT NULL,
  `lastupdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `project_costcenter` (`project_costcenter`),
  KEY `fk_projects_users` (`project_admin`),
  KEY `fk_projects_users1` (`project_pi`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='System can have multiple projects. There must be 1 project a';

--
-- Table structure for table `protocol_group`
--

CREATE TABLE `protocol_group` (
  `protocolgroup_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocolgroup_name` varchar(50) NOT NULL,
  `protocolgroup_modality` varchar(40) NOT NULL,
  PRIMARY KEY (`protocolgroup_id`),
  UNIQUE KEY `protocolgroup_name` (`protocolgroup_name`,`protocolgroup_modality`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='specifies the protocol group name and modality';

--
-- Table structure for table `protocolgroup_items`
--

CREATE TABLE `protocolgroup_items` (
  `pgitem_id` int(11) NOT NULL AUTO_INCREMENT,
  `protocolgroup_id` int(11) NOT NULL,
  `pgitem_protocol` varchar(255) NOT NULL,
  PRIMARY KEY (`pgitem_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `public_downloads`
--

CREATE TABLE `public_downloads` (
  `pd_id` int(11) NOT NULL AUTO_INCREMENT,
  `pd_createdate` datetime NOT NULL,
  `pd_expiredate` datetime NOT NULL,
  `pd_expiredays` int(11) NOT NULL,
  `pd_createdby` varchar(50) NOT NULL COMMENT 'userid of the owner',
  `pd_zippedsize` double NOT NULL,
  `pd_unzippedsize` double NOT NULL,
  `pd_filename` varchar(255) NOT NULL,
  `pd_desc` varchar(255) NOT NULL,
  `pd_notes` text NOT NULL,
  `pd_filecontents` longtext NOT NULL,
  `pd_shareinternal` tinyint(1) NOT NULL,
  `pd_registerrequired` tinyint(1) NOT NULL,
  `pd_password` varchar(255) NOT NULL,
  `pd_status` varchar(50) NOT NULL,
  `pd_key` varchar(255) NOT NULL,
  `pd_numdownloads` bigint(20) NOT NULL,
  PRIMARY KEY (`pd_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `qc_modules`
--

CREATE TABLE `qc_modules` (
  `qcmodule_id` int(11) NOT NULL AUTO_INCREMENT,
  `qcm_modality` varchar(20) NOT NULL,
  `qcm_name` varchar(255) NOT NULL COMMENT 'full name of the module in the qcmodules directory',
  `qcm_isenabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`qcmodule_id`),
  KEY `qcmodule_id` (`qcmodule_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `qc_moduleseries`
--

CREATE TABLE `qc_moduleseries` (
  `qcmoduleseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `qcmodule_id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `modality` varchar(25) NOT NULL,
  `cpu_time` double NOT NULL,
  PRIMARY KEY (`qcmoduleseries_id`),
  UNIQUE KEY `qcmodule_id` (`qcmodule_id`,`series_id`,`modality`),
  KEY `series_id` (`series_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `qc_resultnames`
--

CREATE TABLE `qc_resultnames` (
  `qcresultname_id` int(11) NOT NULL AUTO_INCREMENT,
  `qcresult_name` varchar(255) NOT NULL DEFAULT '',
  `qcresult_type` enum('graph','image','histogram','minmax','number','textfile') NOT NULL DEFAULT 'number',
  `qcresult_units` varchar(255) NOT NULL DEFAULT 'unitless',
  `qcresult_labels` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`qcresultname_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `qc_results`
--

CREATE TABLE `qc_results` (
  `qcresults_id` int(11) NOT NULL AUTO_INCREMENT,
  `qcmoduleseries_id` int(11) NOT NULL,
  `qcresultname_id` int(11) NOT NULL,
  `qcresults_valuenumber` double DEFAULT NULL,
  `qcresults_valuetext` blob NOT NULL,
  `qcresults_valuefile` varchar(255) DEFAULT NULL,
  `qcresults_datetime` datetime DEFAULT NULL,
  `qcresults_cputime` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`qcresults_id`),
  UNIQUE KEY `qcmoduleseries_id` (`qcmoduleseries_id`,`qcresultname_id`),
  KEY `qcmoduleseries_id_2` (`qcmoduleseries_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `rater_id` int(11) NOT NULL,
  `data_id` int(11) NOT NULL,
  `data_modality` varchar(50) NOT NULL,
  `rating_type` varchar(50) NOT NULL COMMENT 'subject, study, series, analysis',
  `rating_value` int(11) NOT NULL,
  `rating_notes` text NOT NULL,
  `rating_date` datetime NOT NULL,
  PRIMARY KEY (`rating_id`),
  KEY `idx_ratings` (`rater_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `remote_logins`
--

CREATE TABLE `remote_logins` (
  `remotelogin_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `login_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `login_result` enum('success','failure') NOT NULL,
  PRIMARY KEY (`remotelogin_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `snp_alleles`
--

CREATE TABLE `snp_alleles` (
  `snpallele_id` int(11) NOT NULL AUTO_INCREMENT,
  `snp_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `allele` char(2) NOT NULL,
  PRIMARY KEY (`snpallele_id`),
  UNIQUE KEY `snp_id` (`snp_id`,`enrollment_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `snp_series`
--

CREATE TABLE `snp_series` (
  `snpseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`snpseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `snps`
--

CREATE TABLE `snps` (
  `snp_id` int(11) NOT NULL AUTO_INCREMENT,
  `snp` varchar(255) NOT NULL,
  `position` int(11) NOT NULL,
  `chromosome` tinyint(3) unsigned NOT NULL,
  `reference_allele` char(2) NOT NULL,
  `genetic_distance` int(11) NOT NULL,
  PRIMARY KEY (`snp_id`),
  UNIQUE KEY `snp` (`snp`,`position`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `sr_series`
--

CREATE TABLE `sr_series` (
  `srseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`srseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `studies`
--

CREATE TABLE `studies` (
  `study_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) DEFAULT NULL,
  `study_num` int(11) NOT NULL,
  `study_desc` varchar(255) NOT NULL,
  `study_type` varchar(255) NOT NULL,
  `study_alternateid` varchar(100) NOT NULL COMMENT 'original ADO id',
  `study_modality` varchar(25) NOT NULL,
  `study_datetime` datetime DEFAULT NULL,
  `study_ageatscan` double DEFAULT NULL,
  `study_height` double NOT NULL,
  `study_weight` double NOT NULL,
  `study_bmi` double NOT NULL,
  `study_operator` varchar(45) DEFAULT NULL,
  `study_experimenter` varchar(255) NOT NULL,
  `study_performingphysician` varchar(100) DEFAULT NULL COMMENT 'may be necessary for an offsite exam, such as CT or PET at the hospital which was ordered and performed by a physician other than the PI',
  `study_site` varchar(45) DEFAULT NULL,
  `study_nidbsite` int(11) NOT NULL,
  `study_institution` varchar(255) NOT NULL,
  `study_notes` varchar(255) NOT NULL,
  `study_doradread` tinyint(1) NOT NULL,
  `study_radreaddate` datetime NOT NULL,
  `study_radreadfindings` text NOT NULL,
  `study_subjectage` double NOT NULL,
  `study_etsnellenchart` int(11) NOT NULL,
  `study_etvergence` varchar(255) NOT NULL,
  `study_ettracking` varchar(255) NOT NULL,
  `study_snpchip` varchar(255) NOT NULL,
  `study_status` varchar(20) DEFAULT NULL COMMENT 'pending, processing, complete',
  `study_isactive` tinyint(1) NOT NULL DEFAULT '1',
  `study_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`study_id`),
  KEY `fk_studies_subject_project1` (`enrollment_id`),
  KEY `subject_id` (`study_num`),
  KEY `study_modality` (`study_modality`),
  KEY `study_datetime` (`study_datetime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `subject_altuid`
--

CREATE TABLE `subject_altuid` (
  `subjectaltuid_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `altuid` varchar(50) NOT NULL,
  PRIMARY KEY (`subjectaltuid_id`),
  UNIQUE KEY `subject_id` (`subject_id`,`altuid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `subject_relation`
--

CREATE TABLE `subject_relation` (
  `subjectrelation_id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectid1` int(11) NOT NULL,
  `subjectid2` int(11) NOT NULL,
  `relation` varchar(10) NOT NULL COMMENT 'siblingm, siblingf, sibling, child, parent [subject1 is the ''relation'' of subject2]',
  PRIMARY KEY (`subjectrelation_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` char(1) DEFAULT NULL,
  `ethnicity1` enum('hispanic','nothispanic') DEFAULT NULL,
  `ethnicity2` set('asian','black','white','indian','islander','mixed','other','unknown') DEFAULT NULL,
  `height` double NOT NULL COMMENT 'stored in cm',
  `weight` double DEFAULT NULL COMMENT 'stored in kg',
  `handedness` char(1) DEFAULT NULL,
  `education` varchar(45) DEFAULT NULL,
  `phone1` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `marital_status` enum('unknown','','married','single','divorced','separated','civilunion','cohabitating','widowed','') NOT NULL DEFAULT 'unknown',
  `smoking_status` enum('unknown','never','current','past','') NOT NULL DEFAULT 'unknown',
  `uid` varchar(10) DEFAULT NULL,
  `uuid` varchar(255) NOT NULL,
  `uuid2` varchar(255) NOT NULL,
  `guid` varchar(255) NOT NULL,
  `cancontact` tinyint(1) DEFAULT NULL,
  `isactive` tinyint(1) NOT NULL DEFAULT '1',
  `isimported` tinyint(1) NOT NULL,
  `importeduuid` varchar(255) NOT NULL,
  `lastupdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `uid` (`uid`),
  KEY `isactive` (`isactive`),
  KEY `name` (`name`,`birthdate`,`gender`,`isactive`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `surgery_series`
--

CREATE TABLE `surgery_series` (
  `surgeryseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`surgeryseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `task_series`
--

CREATE TABLE `task_series` (
  `taskseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` varchar(255) NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`taskseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `us_series`
--

CREATE TABLE `us_series` (
  `usseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`usseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `favorite_id` int(11) NOT NULL,
  `favorite_type` set('project','subject') NOT NULL,
  `favorite_objectid` int(11) NOT NULL,
  `favorite_user` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `user_instance`
--

CREATE TABLE `user_instance` (
  `userinstance_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `isdefaultinstance` tinyint(1) NOT NULL,
  `instance_joinrequest` tinyint(1) NOT NULL,
  PRIMARY KEY (`userinstance_id`),
  UNIQUE KEY `user_id` (`user_id`,`instance_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `user_project`
--

CREATE TABLE `user_project` (
  `userproject_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `view_data` tinyint(1) NOT NULL,
  `view_phi` tinyint(1) NOT NULL,
  `write_data` tinyint(1) NOT NULL,
  `write_phi` tinyint(1) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`userproject_id`),
  KEY `user_id` (`user_id`,`project_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  `login_type` enum('NIS','Standard','Guest','Pending') NOT NULL,
  `user_instanceid` int(11) NOT NULL,
  `user_fullname` varchar(150) NOT NULL,
  `user_firstname` varchar(255) NOT NULL,
  `user_midname` char(1) NOT NULL,
  `user_lastname` varchar(255) NOT NULL,
  `user_institution` varchar(255) NOT NULL,
  `user_country` varchar(255) NOT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `user_email2` varchar(255) NOT NULL,
  `user_address1` varchar(255) NOT NULL,
  `user_address2` varchar(255) NOT NULL,
  `user_city` varchar(255) NOT NULL,
  `user_state` varchar(255) NOT NULL,
  `user_zip` varchar(255) NOT NULL,
  `user_phone1` varchar(255) NOT NULL,
  `user_phone2` varchar(255) NOT NULL,
  `user_website` varchar(255) NOT NULL,
  `user_dept` varchar(255) NOT NULL,
  `user_lastlogin` timestamp NULL DEFAULT NULL,
  `user_logincount` int(11) DEFAULT '0',
  `user_enabled` tinyint(1) DEFAULT '0',
  `user_isadmin` tinyint(1) DEFAULT '0',
  `user_issiteadmin` tinyint(1) NOT NULL DEFAULT '0',
  `user_canimport` tinyint(1) NOT NULL DEFAULT '0',
  `sendmail_dailysummary` tinyint(1) NOT NULL,
  `user_enablebeta` tinyint(1) NOT NULL DEFAULT '0',
  `lastupdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `users_pending`
--

CREATE TABLE `users_pending` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  `user_instanceid` int(11) NOT NULL,
  `user_fullname` varchar(150) NOT NULL,
  `user_institution` varchar(255) NOT NULL,
  `user_country` varchar(255) NOT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `emailkey` varchar(255) NOT NULL,
  `signupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_firstname` varchar(255) NOT NULL,
  `user_midname` varchar(255) NOT NULL,
  `user_lastname` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `user_email` (`user_email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `video_series`
--

CREATE TABLE `video_series` (
  `videoseries_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`videoseries_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table `xa_series`
--

CREATE TABLE `xa_series` (
  `xaseries_id` int(11) NOT NULL AUTO_INCREMENT,
  `study_id` int(11) DEFAULT NULL,
  `series_num` int(11) NOT NULL,
  `series_desc` varchar(255) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `series_protocol` varchar(255) NOT NULL,
  `series_numfiles` int(11) NOT NULL COMMENT 'total number of files',
  `series_size` double NOT NULL COMMENT 'size of all the files',
  `series_notes` text NOT NULL,
  `series_createdby` varchar(50) NOT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`xaseries_id`),
  KEY `fk_eeg_series_studies1` (`study_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- Dump completed on 2015-04-03 13:52:21
