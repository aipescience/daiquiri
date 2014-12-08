CREATE TABLE IF NOT EXISTS `Data_Databases` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `order` INTEGER,
  `name` VARCHAR(256) NOT NULL,
  `description` TEXT,
  `publication_select` BOOLEAN,
  `publication_update` BOOLEAN,
  `publication_insert` BOOLEAN,
  `publication_show` BOOLEAN,
  `publication_role_id` INTEGER
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Data_Functions` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `order` INTEGER,
  `name` VARCHAR(256) NOT NULL,
  `description` TEXT,
  `publication_role_id` INTEGER
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Data_Tables` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `database_id` INTEGER NOT NULL,
  `order` INTEGER,
  `name` VARCHAR(256) NOT NULL,
  `description` TEXT,
  `publication_select` BOOLEAN,
  `publication_update` BOOLEAN,
  `publication_insert` BOOLEAN,
  `publication_role_id` INTEGER
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Data_Columns` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `table_id` INTEGER NOT NULL,
  `order` INTEGER,
  `name` VARCHAR(256) NOT NULL,
  `type` VARCHAR(256) NOT NULL,
  `unit` VARCHAR(256),
  `ucd` VARCHAR(256),
  `description` TEXT,
  `publication_select` BOOLEAN,
  `publication_update` BOOLEAN,
  `publication_insert` BOOLEAN,
  `publication_role_id` INTEGER
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Data_Static` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `alias` VARCHAR(256) NOT NULL,
  `path` VARCHAR(256) NOT NULL,
  `publication_role_id` INTEGER
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Data_UCD` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `type` CHAR(2) NOT NULL,
  `word` VARCHAR(256),
  `description` TEXT
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';
