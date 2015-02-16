CREATE TABLE IF NOT EXISTS `Meetings_Meetings` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(256) NOT NULL,
  `slug` VARCHAR(256) NOT NULL,
  `description` TEXT,
  `begin` DATE NOT NULL,
  `end` DATE NOT NULL,
  `registration_message` TEXT,
  `participants_message` TEXT,
  `contributions_message` TEXT,
  `registration_publication_role_id` INTEGER,
  `participants_publication_role_id` INTEGER,
  `contributions_publication_role_id` INTEGER 
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_Contributions` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(256) NOT NULL,
  `abstract` TEXT NOT NULL,
  `accepted` BOOL NOT NULL,
  `participant_id` INTEGER NOT NULL,
  `contribution_type_id` INTEGER NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_ContributionTypes` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `contribution_type` VARCHAR(256) NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_Meetings_ContributionTypes` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `meeting_id` INTEGER NOT NULL,
  `contribution_type_id` INTEGER NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_Participants` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `firstname` VARCHAR(256) NOT NULL,
  `lastname` VARCHAR(256) NOT NULL,
  `affiliation` VARCHAR(256) NOT NULL,
  `arrival` DATE NOT NULL,
  `departure` DATE NOT NULL,
  `email` VARCHAR(256) NOT NULL,
  `meeting_id` INTEGER NOT NULL,
  `status_id` INTEGER NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_ParticipantStatus` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(256) NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_ParticipantDetails` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `participant_id` INTEGER NOT NULL,
  `key_id` INTEGER NOT NULL,
  `value` TEXT NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_ParticipantDetailKeys` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(256) NOT NULL,
  `hint` VARCHAR(256),
  `type_id` INTEGER NOT NULL,
  `options` TEXT,
  `required` BOOL NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_Meetings_ParticipantDetailKeys` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `meeting_id` INTEGER NOT NULL,
  `participant_detail_key_id` INTEGER NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_Registration` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(256) NOT NULL,
  `code`  VARCHAR(256) NOT NULL,
  `values` TEXT,
  `meeting_id` INTEGER NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';