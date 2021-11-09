
CREATE TABLE IF NOT EXISTS `#__jdocmanual_menu` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `language_code` CHAR(8) NOT NULL DEFAULT 'en',
  `menu_key` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `menu` MEDIUMTEXT NULL DEFAULT NULL, 
  `state` TINYINT NOT NULL DEFAULT '1',
  `last_update` DATETIME on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
  PRIMARY KEY (`id`)
) ENGINE = InnoDB; 

CREATE TABLE IF NOT EXISTS `#__jdocmanual_pages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `language_code` CHAR(8) NOT NULL DEFAULT 'en',
  `jdoc_key` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `content` MEDIUMTEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` TINYINT(4) NOT NULL DEFAULT '1',
  `last_update` timestamp on update CURRENT_TIMESTAMP default CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`language_code`)
) ENGINE = InnoDB;
