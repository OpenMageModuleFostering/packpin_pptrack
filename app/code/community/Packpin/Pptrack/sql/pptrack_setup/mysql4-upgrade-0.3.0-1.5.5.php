<?php
$installer = $this;

$installer->startSetup();

$tableNameTrack = $installer->getTable('pptrack/track');



$sql=<<<SQLTEXT

ALTER TABLE `{$tableNameTrack}` ADD `order_number` VARCHAR(50) NULL , ADD INDEX (`order_number`) ;

SQLTEXT;

$installer->run($sql);

$installer->endSetup();