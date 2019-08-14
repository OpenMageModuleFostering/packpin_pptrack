<?php
$installer = $this;

$installer->startSetup();

$table = $installer->getTable('pptrack/visit');
$tableNameTrack = $installer->getTable('pptrack/track');



$sql=<<<SQLTEXT

CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `track_id` INT DEFAULT NULL,
  `user_hash` char(32) NOT NULL,
  `user_ip` varchar(255) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `track_id` (`track_id`),
  KEY `user_hash` (`user_hash`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `{$tableNameTrack}` ADD INDEX(`created_at`);

SQLTEXT;

$installer->run($sql);

$installer->endSetup();