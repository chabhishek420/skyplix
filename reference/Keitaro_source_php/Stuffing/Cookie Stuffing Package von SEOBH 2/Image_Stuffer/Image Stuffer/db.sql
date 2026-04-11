-- phpMyAdmin SQL Dump
-- version 2.9.1.1-Debian-8
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Erstellungszeit: 29. September 2008 um 22:28
-- Server Version: 5.0.32
-- PHP-Version: 5.2.0-8+etch11
-- 
-- Datenbank: `m4rcosql18`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `cookies`
-- 

CREATE TABLE `cookies` (
  `id` int(8) NOT NULL auto_increment,
  `active` int(11) NOT NULL default '1',
  `name` varchar(150) NOT NULL,
  `alias` varchar(80) NOT NULL,
  `url` varchar(255) NOT NULL,
  `referer` varchar(255) NOT NULL,
  `img` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `stats`
-- 

CREATE TABLE `stats` (
  `id` int(8) NOT NULL auto_increment,
  `c_id` int(8) NOT NULL,
  `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `referer` varchar(255) NOT NULL,
  `ip` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `c_id` (`c_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=3335 ;

-- 
-- Constraints der exportierten Tabellen
-- 

-- 
-- Constraints der Tabelle `stats`
-- 
ALTER TABLE `stats`
  ADD CONSTRAINT `stats_ibfk_1` FOREIGN KEY (`c_id`) REFERENCES `cookies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
