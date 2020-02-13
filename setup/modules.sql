-- phpMyAdmin SQL Dump
-- version 4.4.11
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 14, 2019 at 08:10 PM
-- Server version: 10.0.33-MariaDB
-- PHP Version: 7.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ado2`
--

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE IF NOT EXISTS `modules` (
  `module_id` int(11) NOT NULL,
  `module_name` varchar(200) NOT NULL,
  `module_status` varchar(25) NOT NULL,
  `module_numrunning` int(11) NOT NULL DEFAULT '0',
  `module_laststart` datetime NOT NULL,
  `module_laststop` datetime NOT NULL,
  `module_isactive` tinyint(1) NOT NULL,
  `module_debug` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`module_id`, `module_name`, `module_status`, `module_numrunning`, `module_laststart`, `module_laststop`, `module_isactive`, `module_debug`) VALUES
(1, 'import', 'stopped', 0, now(), now(), 1, 0),
(2, 'export', 'stopped', 0, now(), now(), 1, 0),
(3, 'mriqa', 'running', 0, now(), now(), 1, 0),
(4, 'pipeline', 'running', 0, now(), now(), 1, 0),
(5, 'qc', 'stopped', 0, now(), now(), 1, 0),
(6, 'fileio', 'stopped', 0, now(), now(), 1, 0),
(7, 'importuploaded', 'stopped', 0, now(), now(), 1, 0),
(8, 'modulemanager', 'stopped', 0, now(), now(), 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=25;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
