DROP TABLE IF EXISTS nette_exceptions_log;
CREATE TABLE IF NOT EXISTS nette_exceptions_log(
	"id" SERIAL PRIMARY KEY NOT NULL,
	"created" timestamp with time zone NOT NULL,
	"guid" character varying,
	"timestamp" timestamp with time zone,
	"priority" character varying,
	"location" character varying,
	"exception_file" character varying,
	"error_text" character varying
);

CREATE INDEX nette_exceptions_log_created_ix on nette_exceptions_log(created);
CREATE INDEX nette_exceptions_log_guid_ix on nette_exceptions_log(guid);
CREATE INDEX nette_exceptions_log_timestamp_ix on nette_exceptions_log(timestamp);
CREATE INDEX nette_exceptions_log_priority_ix on nette_exceptions_log(priority);
CREATE INDEX nette_exceptions_log_location_ix on nette_exceptions_log(location);
