<?php

function getOpenArchivesUpdates() {
    return [
        'open_archives_collection' => array(
            'title' => 'Open Archive Collections',
            'description' => 'Add a table to track collections of Open Archives Materials.',
            'sql' => array(
                "CREATE TABLE open_archives_collection (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    baseUrl VARCHAR(255) NOT NULL,
                    setName VARCHAR(100) NOT NULL,
                    fetchFrequency ENUM('hourly', 'daily', 'weekly', 'monthly', 'yearly', 'once'),
                    lastFetched INT(11)
                ) ENGINE = InnoDB",
            ),
        ),

        'open_archives_collection_filtering' => array(
            'title' => 'Open Archive Collection Filtering',
            'description' => 'Add the ability to filter a collection by subject.',
            'sql' => array(
                "ALTER TABLE open_archives_collection ADD COLUMN subjectFilters MEDIUMTEXT",
            ),
        ),

        'open_archives_collection_subjects' => array(
            'title' => 'Open Archive Collection Subjects',
            'description' => 'Add a field to list all of the available subjects in a collection (to make filtering easier).',
            'sql' => array(
                "ALTER TABLE open_archives_collection ADD COLUMN subjects MEDIUMTEXT",
            ),
        ),

        'open_archives_record' => array(
            'title' => 'Open Archive Record',
            'description' => 'Add a table to track records within Open Archives',
            'sql' => array(
                "CREATE TABLE open_archives_record (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    sourceCollection INT(11) NOT NULL,
                    permanentUrl VARCHAR(512) NOT NULL
                ) ENGINE = InnoDB",
                "ALTER TABLE open_archives_record ADD UNIQUE INDEX (sourceCollection, permanentUrl)"
            ),
        ),

        'track_open_archive_user_usage' => array(
            'title' => 'Open Archive Usage by user',
            'description' => 'Add a table to track how often a particular user uses the Open Archives.',
            'sql' => array(
                "CREATE TABLE user_open_archives_usage (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    userId INT(11) NOT NULL,
                    openArchivesCollectionId INT(11) NOT NULL,
                    year INT(4) NOT NULL,
                    firstUsed INT(11) NOT NULL,
                    lastUsed INT(11) NOT NULL,
                    usageCount INT(11)
                ) ENGINE = InnoDB",
                "ALTER TABLE user_open_archives_usage ADD INDEX (openArchivesCollectionId, year, userId)",
            ),
        ),

        'track_open_archive_record_usage' => array(
            'title' => 'Open Archive Record Usage',
            'description' => 'Add a table to track how records within open archives are viewed.',
            'continueOnError' => true,
            'sql' => array(
                "CREATE TABLE open_archives_record_usage (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    openArchivesRecordId INT(11),
                    year INT(4) NOT NULL,
                    timesViewedInSearch INT(11) NOT NULL,
                    timesUsed INT(11) NOT NULL
                ) ENGINE = InnoDB",
                "ALTER TABLE open_archives_record_usage ADD INDEX (openArchivesRecordId, year)",
            ),
        ),

        'open_archive_tracking_adjustments' => array(
            'title' => 'Open Archive Tracking Adjustments',
            'description' => 'Track by month rather than just by year',
            'continueOnError' => true,
            'sql' => array(
                "ALTER TABLE user_open_archives_usage ADD COLUMN month INT(2) NOT NULL default 4",
                "ALTER TABLE open_archives_record_usage ADD COLUMN month INT(2) NOT NULL default 4",
                "ALTER TABLE user_open_archives_usage DROP COLUMN firstUsed",
                "ALTER TABLE user_open_archives_usage DROP COLUMN lastUsed",
            ),
        ),

	    'create_open_archives_module' => [
		    'title' => 'Create Open Archives Module',
		    'description' => 'Setup Open Archives module',
		    'sql' => [
			    "INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Open Archives', 'open_archives', 'oai_indexer')"
		    ]
	    ]
    ];
}