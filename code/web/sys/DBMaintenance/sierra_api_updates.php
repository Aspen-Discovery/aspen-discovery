<?php

function getSierraAPIUpdates() {
	return [
		'sierra_exportLog' => [
			'title' => 'Sierra API export log',
			'description' => 'Create log for sierra export via api.',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS sierra_api_export_log(
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of log', 
					`startTime` INT(11) NOT NULL COMMENT 'The timestamp when the run started', 
					`endTime` INT(11) NULL COMMENT 'The timestamp when the run ended', 
					`lastUpdate` INT(11) NULL COMMENT 'The timestamp when the run last updated (to check for stuck processes)', 
					`notes` TEXT COMMENT 'Additional information about the run', 
					PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
			],
		],

		'sierra_exportLog_stats' => [
			'title' => 'Sierra API export log stats',
			'description' => 'Add stats to sierra export via api log.',
			'sql' => [
				"ALTER TABLE sierra_api_export_log ADD COLUMN numRecordsToProcess INT(11)",
				"ALTER TABLE sierra_api_export_log ADD COLUMN numRecordsProcessed INT(11)",
				"ALTER TABLE sierra_api_export_log ADD COLUMN numErrors INT(11)",
				"ALTER TABLE sierra_api_export_log ADD COLUMN numRemainingRecords INT(11)",
			],
		],

		'sierra_export_field_mapping' => [
			'title' => 'Sierra API export field mapping',
			'description' => 'Setup field mappings for sierra export via api.',
			'sql' => [
				"CREATE TABLE IF NOT EXISTS sierra_export_field_mapping(
					`id` INT NOT NULL AUTO_INCREMENT COMMENT 'The id of field mapping', 
					`indexingProfileId` INT(11) NOT NULL COMMENT 'The indexing profile this field mapping is associated with',
					`bcode3DestinationField` CHAR(3) NOT NULL COMMENT 'The field to place bcode3 into', 
					`bcode3DestinationSubfield` CHAR(1) NULL COMMENT 'The subfield to place bcode3 into', 
					PRIMARY KEY ( `id` )
				) ENGINE = InnoDB;",
			],
		],

		'sierra_export_field_mapping_item_fields' => [
			'title' => 'Sierra API export item field mapping',
			'description' => 'Add item export information for sierra export.',
			'sql' => [
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN callNumberExportFieldTag CHAR(1)",
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN callNumberPrestampExportSubfield CHAR(1)",
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN callNumberExportSubfield CHAR(1)",
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN callNumberCutterExportSubfield CHAR(1)",
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN callNumberPoststampExportSubfield CHAR(5)",
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN volumeExportFieldTag CHAR(1)",
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN urlExportFieldTag CHAR(1)",
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN eContentExportFieldTag CHAR(1)",
			],
		],

		'sierra_export_additional_fixed_fields' => [
			'title' => 'Sierra API map additional fixed fields',
			'description' => 'Add item export information for sierra export.',
			'sql' => [
				"ALTER TABLE sierra_export_field_mapping CHANGE COLUMN bcode3DestinationField fixedFieldDestinationField CHAR(3) NOT NULL COMMENT 'The field to place fixed field data into'",
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN materialTypeSubfield CHAR(1)",
				"ALTER TABLE sierra_export_field_mapping ADD COLUMN bibLevelLocationsSubfield CHAR(1)",
			],
		],
	];
}