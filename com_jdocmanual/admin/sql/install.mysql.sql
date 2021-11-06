
CREATE TABLE IF NOT EXISTS `#__jdocmanual_menu` ( `id` INT NOT NULL AUTO_INCREMENT,
  `source_id` TINYINT NOT NULL DEFAULT '1',
  `state` TINYINT NOT NULL DEFAULT '1',
  `menu` TEXT NULL DEFAULT NULL, 
  `last_update` DATETIME on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  PRIMARY KEY (`id`)
) ENGINE = InnoDB; 

CREATE TABLE IF NOT EXISTS `#__jdocmanual_pages` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `language_code` CHAR(8) NOT NULL DEFAULT 'en',
  `jdoc_key` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` TINYINT(4) NOT NULL DEFAULT '1',
  `last_update` timestamp on update CURRENT_TIMESTAMP default CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`language_code`)
) ENGINE = InnoDB;
