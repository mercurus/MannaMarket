-- phpMyAdmin SQL Dump
-- version 4.1.6
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2014 at 06:29 AM
-- Server version: 5.6.16
-- PHP Version: 5.5.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `mannamarket`
--
CREATE DATABASE IF NOT EXISTS `mannamarket` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `manna676_mannamarket`;

-- --------------------------------------------------------

--
-- Table structure for table `family`
--

CREATE TABLE IF NOT EXISTS `family` (
  `familyid` int(11) NOT NULL AUTO_INCREMENT,
  `familyname` varchar(25) NOT NULL,
  `primarylocation` char(4) NOT NULL,
  `address1` varchar(50) NOT NULL,
  `address2` varchar(25) DEFAULT NULL,
  `city` varchar(25) NOT NULL,
  `state` char(2) NOT NULL DEFAULT 'MN',
  `zip` char(5) DEFAULT NULL,
  `householdincome` mediumint(6) NOT NULL,
  `numminors` tinyint(2) NOT NULL DEFAULT '0',
  `numadults` tinyint(2) NOT NULL DEFAULT '0',
  `numseniors` tinyint(2) NOT NULL DEFAULT '0',
  `blockgrant` bit(1) NOT NULL,
  `prayer` bit(1) NOT NULL,
  `crisis` bit(1) NOT NULL,
  PRIMARY KEY (`familyid`),
  UNIQUE KEY `familyname` (`familyname`),
  KEY `primarylocation` (`primarylocation`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- RELATIONS FOR TABLE `family`:
--   `primarylocation`
--       `mmlocations` -> `alias`
--

--
-- Dumping data for table `family`
--

INSERT INTO `family` (`familyid`, `familyname`, `primarylocation`, `address1`, `address2`, `city`, `state`, `zip`, `householdincome`, `numminors`, `numadults`, `numseniors`, `blockgrant`, `prayer`, `crisis`) VALUES
(1, 'Gregory', 'yuwy', '123 Fake St', '', 'Fridley', 'MN', '55432', 10000, 0, 2, 0, b'0', b'0', b'0');

-- --------------------------------------------------------

--
-- Table structure for table `guestlog`
--

CREATE TABLE IF NOT EXISTS `guestlog` (
  `familyid` int(11) NOT NULL,
  `location` char(4) NOT NULL,
  `date` date NOT NULL,
  `extra` bit(1) DEFAULT NULL,
  `warning` bit(1) DEFAULT NULL,
  `notes` tinytext,
  KEY `familyid` (`familyid`,`location`),
  KEY `location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `guestlog`:
--   `location`
--       `mmlocations` -> `alias`
--   `familyid`
--       `family` -> `familyid`
--

-- --------------------------------------------------------

--
-- Table structure for table `mmlocations`
--

CREATE TABLE IF NOT EXISTS `mmlocations` (
  `alias` char(4) NOT NULL,
  `name` varchar(50) NOT NULL,
  `address` varchar(50) NOT NULL,
  `city` varchar(20) NOT NULL,
  `state` char(2) NOT NULL DEFAULT 'MN',
  `zip` char(5) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `distday` varchar(10) NOT NULL,
  `registration` varchar(20) NOT NULL,
  `distribution` varchar(20) NOT NULL,
  `mealserved` bit(1) NOT NULL,
  `areaserved` text NOT NULL,
  PRIMARY KEY (`alias`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `mmlocations`
--

INSERT INTO `mmlocations` (`alias`, `name`, `address`, `city`, `state`, `zip`, `phone`, `distday`, `registration`, `distribution`, `mealserved`, `areaserved`) VALUES
('bknz', 'Brooklyn Center Church of the Nazarene', '501 73rd Ave. N', 'Brooklyn Center', 'MN', '55444', '763-561-0822', 'Monday', '4:00 PM - 6:30 PM', '4:00 PM - 6:30 PM', b'0', 'Open boundaries, except for communities served by other Manna Markets. Boundaries subject to change without notice.'),
('frcv', 'Fridley Covenant Church', '6390 University Ave. NE', 'Fridley', 'MN', '55432', '763-571-1657', 'Tuesday', '5:00 PM', '6:00 PM', b'0', 'Does not serve communities served by other Manna Markets. Boundaries subject to change without notice.'),
('rfhp', 'Refuge of Hope', '16060 Crosstown Blvd. NW', 'Andover', 'MN', '55304', '763-434-3200', 'Friday', '5:00 PM', '6:00 PM', b'1', 'Open boundaries, except for communities served by other Manna Markets. Boundaries subject to change without notice.'),
('shcv', 'Good Shepherd Covenant Church', '12610 Jefferson St. NE', 'Blaine', 'MN', '55434', '763-755-3730', 'Friday', '5:00 PM', '6:00 PM', b'1', 'Blaine, Andover, Coon Rapids, Ham Lake'),
('snlt', 'SonLight Church of the Nazarene', '3860 Flowerfield Road NE ', 'Blaine', 'MN', '55014', '763-784-1607', 'Monday', '4:45 PM & 6:00 PM', '5:45 PM & 6:30 PM', b'1', 'Circle Pines, Lexington, Lino Lakes, East Blaine, Centerville'),
('trnl', 'Trinity Lutheran Church', '3812 229th Ave NW', 'St Francis', 'MN', '55070', '763-753-1234', 'Friday', '5:00 PM', '6:00 PM', b'1', 'Open boundaries, except for communities served by other Manna Markets. Boundaries subject to change without notice.'),
('yuwy', 'YouthWay Ministries', '3301 92nd Ave. NE', 'Blaine', 'MN', '55449', '763-205-1035', 'Saturday', '1:45 PM', '2:00 PM', b'0', 'Centennial Square Mobile Home Community in Blaine');

-- --------------------------------------------------------

--
-- Table structure for table `person`
--

CREATE TABLE IF NOT EXISTS `person` (
  `personid` int(11) NOT NULL AUTO_INCREMENT,
  `familyid` int(11) NOT NULL,
  `firstname` varchar(25) NOT NULL,
  `lastname` varchar(25) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `emergencycontact` varchar(20) DEFAULT NULL,
  `emergencyphone` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `avgvolhrs` decimal(3,1) NOT NULL,
  PRIMARY KEY (`personid`),
  KEY `familyid` (`familyid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- RELATIONS FOR TABLE `person`:
--   `familyid`
--       `family` -> `familyid`
--

--
-- Dumping data for table `person`
--

INSERT INTO `person` (`personid`, `familyid`, `firstname`, `lastname`, `phone`, `email`, `emergencycontact`, `emergencyphone`, `dob`, `avgvolhrs`) VALUES
(1, 1, 'Forrest', 'Gregory', '763-464-0534', 'fgregory@youthwayministries.org ', '', '', '2014-01-01', '5.0'),
(2, 1, 'Nancy', 'Gregory', '763-464-0534', 'fgregory@youthwayministries.org ', '', '', '2014-01-01', '5.0');

-- --------------------------------------------------------

--
-- Table structure for table `pickuplocations`
--

CREATE TABLE IF NOT EXISTS `pickuplocations` (
  `locationid` int(11) NOT NULL AUTO_INCREMENT,
  `primarylocation` char(4) DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `notes` tinytext,
  PRIMARY KEY (`locationid`),
  KEY `primarylocation` (`primarylocation`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- RELATIONS FOR TABLE `pickuplocations`:
--   `primarylocation`
--       `mmlocations` -> `alias`
--

--
-- Dumping data for table `pickuplocations`
--

INSERT INTO `pickuplocations` (`locationid`, `primarylocation`, `name`, `notes`) VALUES
(1, NULL, 'Second Harvest Truck', 'A donation straight from a Second Harvest Truck.');

-- --------------------------------------------------------

--
-- Table structure for table `pickuplog`
--

CREATE TABLE IF NOT EXISTS `pickuplog` (
  `pickupid` int(11) NOT NULL AUTO_INCREMENT,
  `locationid` int(11) NOT NULL,
  `mmalias` char(4) NOT NULL,
  `personid` int(11) NOT NULL,
  `pickupdate` date NOT NULL,
  `2htruck` smallint(11) unsigned NOT NULL DEFAULT '0',
  `bakery` smallint(11) unsigned NOT NULL DEFAULT '0',
  `dairy` smallint(11) unsigned NOT NULL DEFAULT '0',
  `deli` smallint(11) unsigned NOT NULL DEFAULT '0',
  `grocery` smallint(11) unsigned NOT NULL DEFAULT '0',
  `household` smallint(11) unsigned NOT NULL DEFAULT '0',
  `meat` smallint(11) unsigned NOT NULL DEFAULT '0',
  `produce` smallint(11) unsigned NOT NULL DEFAULT '0',
  `extrafood` smallint(11) unsigned NOT NULL DEFAULT '0',
  `notes` tinytext,
  PRIMARY KEY (`pickupid`),
  KEY `locationid` (`locationid`),
  KEY `mmalias` (`mmalias`),
  KEY `personid` (`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- RELATIONS FOR TABLE `pickuplog`:
--   `locationid`
--       `pickuplocations` -> `locationid`
--   `mmalias`
--       `mmlocations` -> `alias`
--   `personid`
--       `person` -> `personid`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `username` varchar(12) NOT NULL,
  `password` varchar(20) NOT NULL,
  `authoritylevel` tinyint(1) NOT NULL DEFAULT '3',
  `personid` int(11) NOT NULL,
  `userenabled` bit(1) NOT NULL DEFAULT b'1',
  PRIMARY KEY (`username`),
  KEY `personid` (`personid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `users`:
--   `personid`
--       `person` -> `personid`
--

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`username`, `password`, `authoritylevel`, `personid`, `userenabled`) VALUES
('fgregory', '.4R35T%', 1, 1, b'1'),
('ngregory', '.N4Nsee%', 1, 2, b'1');

-- --------------------------------------------------------

--
-- Table structure for table `volunteerlog`
--

CREATE TABLE IF NOT EXISTS `volunteerlog` (
  `personid` int(11) NOT NULL,
  `location` char(4) NOT NULL,
  `volunteerdate` date NOT NULL,
  `hours` decimal(3,1) NOT NULL,
  KEY `personid` (`personid`),
  KEY `location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- RELATIONS FOR TABLE `volunteerlog`:
--   `location`
--       `mmlocations` -> `alias`
--   `personid`
--       `person` -> `personid`
--

--
-- Constraints for dumped tables
--

--
-- Constraints for table `family`
--
ALTER TABLE `family`
  ADD CONSTRAINT `family_ibfk_1` FOREIGN KEY (`primarylocation`) REFERENCES `mmlocations` (`alias`) ON UPDATE CASCADE;

--
-- Constraints for table `guestlog`
--
ALTER TABLE `guestlog`
  ADD CONSTRAINT `guestlog_ibfk_2` FOREIGN KEY (`location`) REFERENCES `mmlocations` (`alias`) ON UPDATE CASCADE,
  ADD CONSTRAINT `guestlog_ibfk_1` FOREIGN KEY (`familyid`) REFERENCES `family` (`familyid`) ON UPDATE CASCADE;

--
-- Constraints for table `person`
--
ALTER TABLE `person`
  ADD CONSTRAINT `person_ibfk_1` FOREIGN KEY (`familyid`) REFERENCES `family` (`familyid`) ON UPDATE CASCADE;

--
-- Constraints for table `pickuplocations`
--
ALTER TABLE `pickuplocations`
  ADD CONSTRAINT `pickuplocations_ibfk_1` FOREIGN KEY (`primarylocation`) REFERENCES `mmlocations` (`alias`) ON UPDATE CASCADE;

--
-- Constraints for table `pickuplog`
--
ALTER TABLE `pickuplog`
  ADD CONSTRAINT `pickuplog_ibfk_3` FOREIGN KEY (`locationid`) REFERENCES `pickuplocations` (`locationid`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pickuplog_ibfk_1` FOREIGN KEY (`mmalias`) REFERENCES `mmlocations` (`alias`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pickuplog_ibfk_2` FOREIGN KEY (`personid`) REFERENCES `person` (`personid`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`personid`) REFERENCES `person` (`personid`) ON UPDATE CASCADE;

--
-- Constraints for table `volunteerlog`
--
ALTER TABLE `volunteerlog`
  ADD CONSTRAINT `volunteerlog_ibfk_2` FOREIGN KEY (`location`) REFERENCES `mmlocations` (`alias`) ON UPDATE CASCADE,
  ADD CONSTRAINT `volunteerlog_ibfk_1` FOREIGN KEY (`personid`) REFERENCES `person` (`personid`) ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
