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

DROP TABLE IF EXISTS `content_sync_log_import`;
CREATE TABLE `content_sync_log_import` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `user_id` int(11) unsigned NOT NULL,
  `object_id` int(11) unsigned NOT NULL,
  `object_version` int(11) unsigned NOT NULL,
  `object_data` TEXT DEFAULT NULL,
  `status` INT(3) UNSIGNED NOT NULL DEFAULT 0,
  `import_time` FLOAT(7,4) UNSIGNED NOT NULL DEFAULT 0,
  `date` int(11) UNSIGNED NOT NULL,
  `error` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
