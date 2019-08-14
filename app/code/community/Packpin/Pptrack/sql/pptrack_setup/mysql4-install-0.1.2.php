<?php
$installer = $this;

$installer->startSetup();

$tableNameTrack = $installer->getTable('pptrack/track');
$tableNameDetails = $installer->getTable('pptrack/trackdetail');
$tableNameCarriers = $installer->getTable('pptrack/carrier');
$tableNameSettings = $installer->getTable('pptrack/setting');

$installer->run("DROP TABLE IF EXISTS `{$tableNameDetails}`;");
$installer->run("DROP TABLE IF EXISTS `{$tableNameTrack}`;");
$installer->run("DROP TABLE IF EXISTS `{$tableNameCarriers}`;");
$installer->run("DROP TABLE IF EXISTS `{$tableNameSettings}`;");

$sql=<<<SQLTEXT

CREATE TABLE IF NOT EXISTS `{$tableNameTrack}` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `hash` varchar(255) NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `shipment_id` INT DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `carrier_code` varchar(50) DEFAULT NULL,
  `carrier_name` varchar(255) DEFAULT NULL,
  `postal_code` varchar(50) DEFAULT NULL,
  `ship_date` varchar(50) DEFAULT NULL,
  `destination_country` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `created_at` INT DEFAULT NULL,
  `updated_at` INT DEFAULT NULL,
  `submitted` INT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`),
  KEY `order_id` (`order_id`),
  KEY `submitted` (`submitted`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `{$tableNameDetails}` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `track_id` INT NOT NULL,
  `carrier` varchar(50) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `status_string` varchar(255) DEFAULT NULL,
  `event_date` DATE DEFAULT NULL,
  `event_time` TIME DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `country` char(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `{$tableNameCarriers}` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `homepage` varchar(255) DEFAULT NULL,
  `enabled` INT NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$tableNameSettings}` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `setting` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `{$tableNameDetails}`
  ADD KEY `track_details_track_id_foreign` (`track_id`);

ALTER TABLE `{$tableNameDetails}`
  ADD CONSTRAINT `track_details_track_id_foreign` FOREIGN KEY (`track_id`) REFERENCES `{$tableNameTrack}` (`id`) ON DELETE CASCADE;

ALTER TABLE `$tableNameSettings`
  ADD UNIQUE KEY `pp_settings_setting_unique` (`setting`);

SQLTEXT;

$installer->run($sql);

$installer->endSetup();