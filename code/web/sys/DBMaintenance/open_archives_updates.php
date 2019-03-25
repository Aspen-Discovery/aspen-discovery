<?php

function getOpenArchivesUpdates() {
    return [
//        'open_archives_collection' => array(
//            'title' => 'Open Archive Usage by user',
//            'description' => 'Add a table to track how often a particular user uses the Open Archives.',
//            'sql' => array(
//                "CREATE TABLE open_archives_collection (
//                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
//                    name VARCHAR(100) NOT NULL,
//                    baseUrl VARCHAR(255) NOT NULL,
//                    setName VARCHAR(100) NOT NULL,
//                    fetchFrequency ENUM('hourly', 'daily', 'weekly', 'monthly', 'yearly', 'once'),
//                    lastFetched INT(11)
//                ) ENGINE = InnoDB",
//            ),
//        ),
//
//        'open_archives_record' => array(
//            'title' => 'Open Archive Usage by user',
//            'description' => 'Add a table to track how often a particular user uses the Open Archives.',
//            'sql' => array(
//                "CREATE TABLE open_archives_record (
//                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
//                    sourceCollection INT(11) NOT NOT NULL,
//                    permanentUrl VARCHAR(512) NOT NULL,
//                ) ENGINE = InnoDB",
//                "ALTER TABLE open_archives_record ADD INDEX (sourceCollection, id)"
//            ),
//        ),
//
//        'track_open_archive_user_usage' => array(
//            'title' => 'Open Archive Usage by user',
//            'description' => 'Add a table to track how often a particular user uses the Open Archives.',
//            'sql' => array(
//                "CREATE TABLE user_open_archives_usage (
//                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
//                    userId INT(11) NOT NULL,
//                    openArchivesCollectionId INT(11) NOT NULL,
//                    year INT(4) NOT NULL,
//                    firstUsed INT(11) NOT NULL,
//                    lastUsed INT(11) NOT NULL,
//                    usageCount INT(11)
//                ) ENGINE = InnoDB",
//                "ALTER TABLE user_open_archives_usage ADD INDEX (openArchivesCollectionId, year, userId)",
//            ),
//        ),
//
//        'track_open_archive_record_usage' => array(
//            'title' => 'Open Archive Usage by user',
//            'description' => 'Add a table to track how often a particular user uses the Open Archives.',
//            'sql' => array(
//                "CREATE TABLE open_archives_record_usage (
//                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
//                    openArchivesRecordId INT(11),
//                    year INT(4) NOT NULL,
//                    timesViewedInSearch INT(11) NOT NULL,
//                    timesUsed INT(11) NOT NULL
//                ) ENGINE = InnoDB",
//                "ALTER TABLE user_open_archives_usage ADD INDEX (openArchivesRecordId, year)",
//            ),
//        ),
    ];
}