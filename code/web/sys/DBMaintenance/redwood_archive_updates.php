<?php

function getRedwoodArchiveUpdates() {
	return [
		'redwood_user_contribution' => [
			'title' => 'Redwood - User Contribution',
			'description' => 'Add a table to user submissions for the archives.',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE redwood_user_contribution (
                    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    userId INT(11) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    creator VARCHAR(255),
                    dateCreated VARCHAR(10),
                    description MEDIUMTEXT,
                    suggestedSubjects MEDIUMTEXT,
                    howAcquired VARCHAR(255),
                    filePath VARCHAR(255),
                    status ENUM('submitted', 'accepted', 'rejected'),
                    license ENUM('none', 'CC0', 'cc', 'public'),
                    allowRemixing TINYINT(1) DEFAULT 0,
                    prohibitCommercialUse TINYINT(1) DEFAULT 0,
                    requireShareAlike TINYINT(1) DEFAULT 0,
                    dateContributed INT(11)
                ) ENGINE = InnoDB",
				"ALTER TABLE redwood_user_contribution ADD INDEX (userId)",
			],
		],
	];
}