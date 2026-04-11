CREATE TABLE `allowrefinfo` (
  `id` bigint(20) NOT NULL auto_increment,
  `memberid` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `memberid` (`memberid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `allowrefs` (
  `id` bigint(20) NOT NULL auto_increment,
  `infoid` bigint(20) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `infoid` (`infoid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `blockipinfo` (
  `id` bigint(20) NOT NULL auto_increment,
  `memberid` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `memberid` (`memberid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `blockips` (
  `id` bigint(20) NOT NULL auto_increment,
  `infoid` bigint(20) NOT NULL,
  `ip` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `infoid` (`infoid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `blockrefinfo` (
  `id` bigint(20) NOT NULL auto_increment,
  `memberid` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `memberid` (`memberid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `blockrefs` (
  `id` bigint(20) NOT NULL auto_increment,
  `infoid` bigint(20) NOT NULL,
  `ref` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `infoid` (`infoid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `campaigns` (
  `id` bigint(20) NOT NULL auto_increment,
  `memberid` bigint(20) NOT NULL,
  `tag` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `ctr` float NOT NULL,
  `randomize` tinyint(1) NOT NULL,
  `logging` tinyint(1) NOT NULL,
  `allowrefs` bigint(20) NOT NULL,
  `blockrefs` bigint(20) NOT NULL,
  `blockips` bigint(20) NOT NULL,
  `links` bigint(20) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `memberid` (`memberid`,`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `images` (
  `id` bigint(20) NOT NULL auto_increment,
  `memberid` bigint(20) NOT NULL,
  `tag` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(4) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `memberid` (`memberid`,`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `lastkey` (
  `id` int(11) NOT NULL,
  `c0` int(11) NOT NULL,
  `c1` int(11) NOT NULL,
  `c2` int(11) NOT NULL,
  `c3` int(11) NOT NULL,
  `c4` int(11) NOT NULL,
  `c5` int(11) NOT NULL,
  `c6` int(11) NOT NULL,
  `c7` int(11) NOT NULL,
  `length` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `linkinfo` (
  `id` bigint(20) NOT NULL auto_increment,
  `memberid` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `memberid` (`memberid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `links` (
  `id` bigint(20) NOT NULL auto_increment,
  `infoid` bigint(20) NOT NULL,
  `campaignid` bigint(20) NOT NULL,
  `hits` bigint(20) NOT NULL default '0',
  `blocks` bigint(20) NOT NULL default '0',
  `stuffs` bigint(20) NOT NULL default '0',
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `infoid` (`infoid`),
  KEY `campaignid` (`campaignid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `loginfo` (
  `id` bigint(20) NOT NULL auto_increment,
  `memberid` bigint(20) NOT NULL,
  `campaignid` bigint(20) NOT NULL,
  `timestamp` varchar(255) NOT NULL default '0',
  `ip` varchar(255) NOT NULL,
  `useragent` text NOT NULL,
  `hit` varchar(255) NOT NULL default '0',
  `stuff` varchar(255) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `memberid` (`memberid`,`campaignid`,`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `logs` (
  `id` bigint(20) NOT NULL auto_increment,
  `infoid` bigint(20) NOT NULL,
  `timestamp` bigint(20) NOT NULL,
  `referer` varchar(255) NOT NULL,
  `action` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `infoid` (`infoid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `members` (
  `id` bigint(20) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `useragents` (
`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`useragent` VARCHAR( 255 ) NOT NULL ,
INDEX ( `useragent` )
) ENGINE = MYISAM ;

INSERT INTO `lastkey` (`id`, `c0`, `c1`, `c2`, `c3`, `c4`, `c5`, `c6`, `c7`, `length`) VALUES (1, 0, 0, 0, 0, 0, 0, 0, 0, 1);

INSERT INTO `members` (`id`, `username`, `password`, `email`) VALUES (1, 'default', 'default', 'none');
