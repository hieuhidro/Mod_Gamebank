-- phpMyAdmin SQL Dump
-- version 3.4.10.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 24, 2014 at 05:47 PM
-- Server version: 5.5.20
-- PHP Version: 5.3.10

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `test`
--

-- --------------------------------------------------------

--
-- Table structure for table `payment_history`
--

CREATE TABLE IF NOT EXISTS `payment_history` (
  `paymentid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `serial` text COLLATE utf8_unicode_ci NOT NULL,
  `cardnumber` text COLLATE utf8_unicode_ci NOT NULL,
  `cardvalue` text COLLATE utf8_unicode_ci NOT NULL,
  `status` int(11) NOT NULL DEFAULT '10000',
  PRIMARY KEY (`historyid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Save history user exchange' AUTO_INCREMENT=3 ;

--
-- Dumping data for table `payment_history`
--

INSERT INTO `payment_history` (`historyid`, `userid`, `datetime`, `serial`, `cardnumber`, `cardvalue`, `status`) VALUES
(1, 1, '2014-07-24 17:44:49', 'sdfsdsf', '123123', 'sfsdfsdf', 1223),
(2, 1, '2014-07-24 17:47:30', '123123', '123123', 'sfsdfsdf', 1223);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
