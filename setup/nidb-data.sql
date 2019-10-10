INSERT IGNORE INTO `instance` (`instance_id`, `instance_uid`, `instance_name`, `instance_ownerid`, `instance_default`) VALUES
(1, 'I1234ABC', 'NiDB', 1, 1);

INSERT IGNORE INTO `modalities` (`mod_id`, `mod_code`, `mod_desc`, `mod_enabled`) VALUES
(1, 'MR', 'MRI - Magnetic Resonance Imaging', 1),
(2, 'CT', 'CT - Computed Tomography', 1),
(3, 'EEG', 'EEG - Electroencephalography', 1),
(4, 'VIDEO', 'Video', 1),
(5, 'ECG', 'ECG - Electrocardiogram', 1),
(6, 'US', 'Ultrasound', 1),
(7, 'MEG', 'MEG - Magnetoencephalography', 1),
(8, 'XRAY', 'X-ray', 1),
(9, 'PT', 'PET - Positron Emission Tomography', 1),
(10, 'OT', 'Other DICOM', 1),
(11, 'PPI', 'Pre-pulse inhibition', 1),
(12, 'ET', 'Eye-tracking', 1),
(13, 'XA', 'XA - X-ray angiography', 1),
(14, 'CR', 'CR - Computed radiography (digital x-ray)', 1),
(15, 'SURGERY', 'Pre-surgical Mapping', 1),
(16, 'AUDIO', 'Audio', 1),
(17, 'SNP', 'SNP genetic information', 1),
(18, 'CONSENT', 'Consent form', 1),
(19, 'TASK', 'Task', 1);

INSERT IGNORE INTO `modules` (`module_id`, `module_name`, `module_status`, `module_numrunning`, `module_laststart`, `module_laststop`, `module_isactive`) VALUES
(1, 'audit', 'stopped', 0, now(), now(), 0),
(2, 'dailybackup', 'stopped', 0, now(), now(), 0),
(3, 'export', 'stopped', 0, now(), now(), 1),
(4, 'fileio', 'stopped', 0, now(), now(), 1),
(5, 'import', 'stopped', 0, now(), now(), 1),
(6, 'importuploaded', 'stopped', 0, now(), now(), 1),
(7, 'modulemanager', 'stopped', 0, now(), now(), 1),
(8, 'mriqa', 'stopped', 0, now(), now(), 1),
(9, 'notifications', 'stopped', 0, now(), now(), 0),
(10, 'pipeline', 'stopped', 0, now(), now(), 1),
(11, 'qc', 'stopped', 0, now(), now(), 1),
(12, 'usage', 'stopped', 0, now(), now(), 0);

INSERT IGNORE INTO `nidb_sites` (`site_id`, `site_uid`, `site_uuid`, `site_name`, `site_address`, `site_contact`) VALUES
(1, 0, uuid(), 'Default Site name', 'Default Site address', 'Default Site contact');

INSERT IGNORE INTO `notifications` (`notiftype_id`, `notiftype_name`, `notiftype_desc`, `notiftype_needproject`) VALUES
(2, 'Data collection report', 'This will send a report of collected data where study dates fall within the report range.', 1),
(3, 'Pipeline summary', 'Summary of current or recently completed pipeline jobs', 0),
(4, 'Missing data', 'Report on missing or potentially incomplete data', 1);

INSERT IGNORE INTO `projects` (`project_id`, `instance_id`, `project_name`, `project_admin`, `project_pi`, `project_sharing`, `project_costcenter`, `project_startdate`, `project_enddate`, `project_irbapprovaldate`, `project_status`, `lastupdate`) VALUES
(1, 1, 'Generic Project', 1, 1, 'F', '999999', '0000-00-00', '3000-00-00', NULL, 'active', now()),
(2, 1, 'Clinical Scan', 1, 1, 'F', '888888', '0000-00-00', '3000-00-00', NULL, 'active', now());

INSERT IGNORE INTO `user_instance` (`userinstance_id`, `user_id`, `instance_id`, `isdefaultinstance`, `instance_joinrequest`) VALUES
(1, 1, 1, 1, 0);

INSERT IGNORE INTO `users` (`user_id`, `username`, `password`, `login_type`, `user_instanceid`, `user_fullname`, `user_firstname`, `user_midname`, `user_lastname`, `user_institution`, `user_country`, `user_email`, `user_email2`, `user_address1`, `user_address2`, `user_city`, `user_state`, `user_zip`, `user_phone1`, `user_phone2`, `user_website`, `user_dept`, `user_lastlogin`, `user_logincount`, `user_enabled`, `user_isadmin`, `user_issiteadmin`, `user_canimport`, `sendmail_dailysummary`, `user_enablebeta`, `lastupdate`) VALUES
(1, 'admin', sha1('password'), 'Standard', 1, 'Administrator', '', '', '', '', '', 'email@email.com', '', '', '', '', '', '', '', '', '', '', '0000-00-00 00:00:00', 0, 1, 1, 1, 0, 0, 0, '0000-00-00 00:00:00');
