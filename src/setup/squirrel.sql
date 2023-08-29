-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 11, 2023 at 05:38 PM
-- Server version: 10.3.28-MariaDB
-- PHP Version: 7.2.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `squirrel`
--

-- --------------------------------------------------------

--
-- Table structure for table `analysis`
--

CREATE TABLE `analysis` (
  `analysis_id` int(11) NOT NULL,
  `study_id` int(11) NOT NULL,
  `pipeline_id` int(11) NOT NULL,
  `analysis_date` datetime NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `size` double NOT NULL DEFAULT 0,
  `numfiles` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `drugs`
--

CREATE TABLE `drugs` (
  `drug_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `date_start` int(11) DEFAULT NULL,
  `date_end` int(11) DEFAULT NULL,
  `date_entry` int(11) DEFAULT NULL,
  `drug_name` varchar(255) DEFAULT NULL,
  `dose_description` varchar(255) DEFAULT NULL,
  `dose_key` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `experiments`
--

CREATE TABLE `experiments` (
  `experiment_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `experiment_name` varchar(255) NOT NULL,
  `size` int(11) NOT NULL DEFAULT 0,
  `numfiles` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `groupanalysis`
--

CREATE TABLE `groupanalysis` (
  `groupanalysis_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `groupanalysis_name` varchar(255) NOT NULL,
  `groupanalysis_desc` text DEFAULT NULL,
  `groupanalysis_date` datetime DEFAULT NULL,
  `numfiles` int(11) NOT NULL DEFAULT 0,
  `size` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `measures`
--

CREATE TABLE `measures` (
  `measure_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `instrument_name` varchar(255) DEFAULT NULL,
  `rater` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `value` text DEFAULT NULL,
  `description` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` bigint(20) NOT NULL,
  `pkg_name` varchar(255) NOT NULL,
  `pkg_desc` text DEFAULT NULL,
  `pkg_date` datetime DEFAULT NULL,
  `pkg_subjectdirformat` enum('orig','seq') NOT NULL DEFAULT 'orig',
  `pkg_studydirformat` enum('orig','seq') NOT NULL DEFAULT 'orig',
  `pkg_seriesdirformat` enum('orig','seq') NOT NULL DEFAULT 'orig',
  `pkg_dataformat` enum('orig','anon','anonfull','nifti3d','nifti3dgz','nifti4d','nifti4dgz') NOT NULL DEFAULT 'orig',
  `pkg_license` text DEFAULT NULL,
  `pkg_readme` text DEFAULT NULL,
  `pkg_changes` text DEFAULT NULL,
  `pkg_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `params`
--

CREATE TABLE `params` (
  `param_id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `param_key` varchar(255) DEFAULT NULL,
  `param_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pipelines`
--

CREATE TABLE `pipelines` (
  `pipeline_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `pipeline_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `pipeline_date` datetime DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `primaryscript_name` varchar(255) DEFAULT NULL,
  `secondaryscript_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `series`
--

CREATE TABLE `series` (
  `series_id` int(11) NOT NULL,
  `study_id` int(11) NOT NULL,
  `series_num` int(11) NOT NULL,
  `series_datetime` datetime NOT NULL,
  `seriesuid` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `protocol` varchar(255) DEFAULT NULL,
  `experiment_id` int(11) DEFAULT NULL,
  `size` bigint(20) NOT NULL DEFAULT 0,
  `numfiles` int(11) NOT NULL DEFAULT 0,
  `behsize` bigint(20) NOT NULL DEFAULT 0,
  `behnumfiles` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `studies`
--

CREATE TABLE `studies` (
  `study_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `number` int(11) NOT NULL DEFAULT 0,
  `datetime` datetime DEFAULT NULL,
  `age` double NOT NULL DEFAULT 0,
  `height` double DEFAULT 0,
  `weight` double NOT NULL DEFAULT 0,
  `modality` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `studyuid` varchar(255) DEFAULT NULL,
  `visittype` varchar(255) DEFAULT NULL,
  `daynumber` int(11) DEFAULT NULL,
  `timepoint` int(11) DEFAULT NULL,
  `equipment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `id` varchar(255) DEFAULT NULL,
  `altids` text DEFAULT NULL,
  `guid` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `sex` enum('F','M','O','U') DEFAULT NULL,
  `gender` enum('F','M','O','U') DEFAULT NULL,
  `ethnicity1` varchar(255) DEFAULT NULL,
  `ethnicity2` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL,
  `ip_address` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `first_login` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `num_packages_created` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `analysis`
--
ALTER TABLE `analysis`
  ADD PRIMARY KEY (`analysis_id`);

--
-- Indexes for table `drugs`
--
ALTER TABLE `drugs`
  ADD PRIMARY KEY (`drug_id`);

--
-- Indexes for table `experiments`
--
ALTER TABLE `experiments`
  ADD PRIMARY KEY (`experiment_id`);

--
-- Indexes for table `groupanalysis`
--
ALTER TABLE `groupanalysis`
  ADD PRIMARY KEY (`groupanalysis_id`);

--
-- Indexes for table `measures`
--
ALTER TABLE `measures`
  ADD PRIMARY KEY (`measure_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `params`
--
ALTER TABLE `params`
  ADD PRIMARY KEY (`param_id`);

--
-- Indexes for table `pipelines`
--
ALTER TABLE `pipelines`
  ADD PRIMARY KEY (`pipeline_id`);

--
-- Indexes for table `series`
--
ALTER TABLE `series`
  ADD PRIMARY KEY (`series_id`);

--
-- Indexes for table `studies`
--
ALTER TABLE `studies`
  ADD PRIMARY KEY (`study_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analysis`
--
ALTER TABLE `analysis`
  MODIFY `analysis_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drugs`
--
ALTER TABLE `drugs`
  MODIFY `drug_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `experiments`
--
ALTER TABLE `experiments`
  MODIFY `experiment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groupanalysis`
--
ALTER TABLE `groupanalysis`
  MODIFY `groupanalysis_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `measures`
--
ALTER TABLE `measures`
  MODIFY `measure_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `params`
--
ALTER TABLE `params`
  MODIFY `param_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pipelines`
--
ALTER TABLE `pipelines`
  MODIFY `pipeline_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `series`
--
ALTER TABLE `series`
  MODIFY `series_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `studies`
--
ALTER TABLE `studies`
  MODIFY `study_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
