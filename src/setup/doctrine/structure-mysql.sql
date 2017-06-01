DROP TABLE IF EXISTS nette_exceptions_log;
CREATE TABLE IF NOT EXISTS nette_exceptions_log(
	`id` integer PRIMARY KEY AUTO_INCREMENT NOT NULL,
	`created` datetime NOT NULL,
	`guid` varchar(255) NULL,
	`timestamp` datetime NULL,
	`priority` varchar(255) NULL,
	`location` varchar(255) NULL,
	`exception_file` varchar(255) NULL,
	`error_text` varchar(255) NULL
);

CREATE INDEX nette_exceptions_log_created_ix on nette_exceptions_log(created);
CREATE INDEX nette_exceptions_log_guid_ix on nette_exceptions_log(guid);
CREATE INDEX nette_exceptions_log_timestamp_ix on nette_exceptions_log(timestamp);
CREATE INDEX nette_exceptions_log_priority_ix on nette_exceptions_log(priority);
CREATE INDEX nette_exceptions_log_location_ix on nette_exceptions_log(location);
