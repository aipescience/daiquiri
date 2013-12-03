CREATE TABLE IF NOT EXISTS `Query_Jobs` (
  `id` BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `table` VARCHAR(256) NOT NULL,
  `database` VARCHAR(256) NOT NULL,
  `host` VARCHAR(256) NOT NULL,
  `query` TEXT NOT NULL,
  `actualQuery` TEXT NOT NULL,
  `user_id` INTEGER,
  `status_id` INTEGER NOT NULL,  
  `time` DATETIME,
  `comment` TEXT
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Query_Examples` (
  `id` BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(256) NOT NULL,
  `query` TEXT NOT NULL,
  `description` TEXT,
  `publication_role_id` INTEGER
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';
