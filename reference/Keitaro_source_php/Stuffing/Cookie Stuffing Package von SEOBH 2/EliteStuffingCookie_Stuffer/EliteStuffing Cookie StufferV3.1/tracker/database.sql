-- phpMyAdmin SQL Dump
-- version 2.11.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 02, 2008 at 01:31 AM
-- Server version: 5.0.51
-- PHP Version: 5.2.4-2ubuntu5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `blackhatcookie`
--

-- --------------------------------------------------------

--
-- Table structure for table `affiliates`
--

CREATE TABLE IF NOT EXISTS `affiliates` (
  `id` int(255) NOT NULL auto_increment,
  `affiliate_name` char(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `affiliate_links`
--

CREATE TABLE IF NOT EXISTS `affiliate_links` (
  `id` int(255) NOT NULL auto_increment,
  `affiliate_id` char(255) collate utf8_unicode_ci NOT NULL,
  `url` char(255) collate utf8_unicode_ci NOT NULL,
  `banner` char(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `referers`
--

CREATE TABLE IF NOT EXISTS `referers` (
  `id` int(255) NOT NULL auto_increment,
  `referers` char(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `safe_referers`
--

CREATE TABLE IF NOT EXISTS `safe_referers` (
  `id` int(255) NOT NULL auto_increment,
  `safe_referers` char(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tracking`
--

CREATE TABLE IF NOT EXISTS `tracking` (
  `id` int(255) NOT NULL auto_increment,
  `link_id` char(255) collate utf8_unicode_ci NOT NULL,
  `cookies` char(255) collate utf8_unicode_ci NOT NULL,
  `impressions` char(255) collate utf8_unicode_ci NOT NULL,
  `maxctr` decimal(65,2) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
