DROP TABLE IF EXISTS `content_sync_log_request`;
CREATE TABLE `content_sync_log_request` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `object_id` int(11) unsigned NOT NULL,
  `object_version` int(11) unsigned NOT NULL,
  `object_data` TEXT DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `response_status` INT(3) UNSIGNED NOT NULL DEFAULT 0,
  `response_headers` TEXT DEFAULT NULL,
  `response_time` FLOAT(7,4) UNSIGNED NOT NULL DEFAULT 0,
  `response_error` VARCHAR(255) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `date` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
