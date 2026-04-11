ALTER TABLE `links` CHANGE `hits` `hits` BIGINT( 20 ) NOT NULL DEFAULT '0';

ALTER TABLE `links` ADD `blocks` BIGINT NOT NULL DEFAULT '0' AFTER `hits` ,
ADD `stuffs` BIGINT NOT NULL DEFAULT '0' AFTER `blocks` ;

ALTER TABLE `campaigns` ADD `logging` BOOL NOT NULL DEFAULT '1';

CREATE TABLE `useragents` (
`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`useragent` VARCHAR( 255 ) NOT NULL ,
INDEX ( `useragent` )
) ENGINE = MYISAM ;

ALTER TABLE `logs` DROP `actiontype` ;