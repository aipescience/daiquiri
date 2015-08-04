CREATE TABLE IF NOT EXISTS `Query_Jobs` (
  `id` BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `table` VARCHAR(256) NOT NULL,
  `database` VARCHAR(256) NOT NULL,
  `host` VARCHAR(256) NOT NULL,
  `query` TEXT NOT NULL,
  `actualQuery` TEXT NOT NULL,
  `user_id` INTEGER NOT NULL,
  `status_id` INTEGER NOT NULL,
  `prev_status_id` INTEGER NOT NULL,
  `type_id` INTEGER NOT NULL,
  `group_id` INTEGER,
  `prev_id` INTEGER,
  `next_id` INTEGER,
  `time` DATETIME,
  `ip` VARCHAR(45),
  `nrows` BIGINT,
  `size` BIGINT,
  `complete` BOOL NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Query_Groups` (
  `id` BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `user_id` INTEGER NOT NULL,
  `prev_id` INTEGER,
  `next_id` INTEGER,
  `name` VARCHAR(256) NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Query_Examples` (
  `id` BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `order` INTEGER,
  `name` VARCHAR(256) NOT NULL,
  `query` TEXT NOT NULL,
  `description` TEXT,
  `publication_role_id` INTEGER
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';
