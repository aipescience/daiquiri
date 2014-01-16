CREATE TABLE IF NOT EXISTS `Meetings_Meetings` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(256) NOT NULL,
  `description` TEXT
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';


CREATE TABLE IF NOT EXISTS `Meetings_Contributions` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(256) NOT NULL,
  `abstract` TEXT NOT NULL,
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
  `email` VARCHAR(256) NOT NULL,
  `meeting_id` INTEGER NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_ParticipantDetails` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `participant_id` INTEGER NOT NULL,
  `key_id` VARCHAR(256) NOT NULL,
  `value` VARCHAR(256) NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_ParticipantDetailKeys` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(256) NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';

CREATE TABLE IF NOT EXISTS `Meetings_Meetings_ParticipantDetailKeys` (
  `id` INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `meeting_id` INTEGER NOT NULL,
  `participant_detail_key_id` INTEGER NOT NULL
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';
