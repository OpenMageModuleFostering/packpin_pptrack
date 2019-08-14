<?php
$installer = $this;

$installer->startSetup();

$tableNameUnsubscribe = $installer->getTable('pptrack/trackunsubscribed');


$sql=<<<SQLTEXT

CREATE TABLE IF NOT EXISTS `{$tableNameUnsubscribe}` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `track_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `track_id` (`track_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

SQLTEXT;

$installer->run($sql);

$installer->endSetup();