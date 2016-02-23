CREATE TABLE IF NOT EXISTS Uws_Jobs (
  `jobId` VARCHAR(128) PRIMARY KEY NOT NULL,
  `runId` VARCHAR(256),
  `ownerId` VARCHAR(256),
  `phase` VARCHAR(64) NOT NULL,
  `quote` DATETIME,
  `creationTime` DATETIME,
  `startTime` DATETIME,
  `endTime` DATETIME,
  `executionDuration` BIGINT,
  `destruction` DATETIME,
  `parameters` TEXT,
  `results` TEXT,
  `errorSummary` TEXT,
  `jobInfo` TEXT
) ENGINE InnoDB COLLATE 'utf8_unicode_ci';
