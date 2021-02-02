<?php

function getGenealogyUpdates(){
	return [
		'genealogy' => [
			'title' => 'Genealogy Setup',
			'description' => 'Initial setup of genealogy information',
			'continueOnError' => true,
			'sql' => [
				//-- setup tables related to the genealogy section
				//-- person table
				"CREATE TABLE `person` (
						`personId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`firstName` VARCHAR( 100 ) NULL ,
						`middleName` VARCHAR( 100 ) NULL ,
						`lastName` VARCHAR( 100 ) NULL ,
						`maidenName` VARCHAR( 100 ) NULL ,
						`otherName` VARCHAR( 100 ) NULL ,
						`nickName` VARCHAR( 100 ) NULL ,
						`birthDate` DATE NULL ,
						`birthDateDay` INT NULL COMMENT 'The day of the month the person was born empty or null if not known',
						`birthDateMonth` INT NULL COMMENT 'The month the person was born, null or blank if not known',
						`birthDateYear` INT NULL COMMENT 'The year the person was born, null or blank if not known',
						`deathDate` DATE NULL ,
						`deathDateDay` INT NULL COMMENT 'The day of the month the person died empty or null if not known',
						`deathDateMonth` INT NULL COMMENT 'The month the person died, null or blank if not known',
						`deathDateYear` INT NULL COMMENT 'The year the person died, null or blank if not known',
						`ageAtDeath` TEXT NULL ,
						`cemeteryName` VARCHAR( 255 ) NULL ,
						`cemeteryLocation` VARCHAR( 255 ) NULL ,
						`mortuaryName` VARCHAR( 255 ) NULL ,
						`comments` MEDIUMTEXT NULL,
						`picture` VARCHAR( 255 ) NULL
						) ENGINE = InnoDB COMMENT = 'Stores information about a particular person for use in genealogy';",

				//-- marriage table
				"CREATE TABLE `marriage` (
						`marriageId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`personId` INT NOT NULL COMMENT 'A link to one person in the marriage',
						`spouseName` VARCHAR( 200 ) NULL COMMENT 'The name of the other person in the marriage if they are not in the database',
						`spouseId` INT NULL COMMENT 'A link to the second person in the marriage if the person is in the database',
						`marriageDate` DATE NULL COMMENT 'The date of the marriage if known.',
						`marriageDateDay` INT NULL COMMENT 'The day of the month the marriage occurred empty or null if not known',
						`marriageDateMonth` INT NULL COMMENT 'The month the marriage occurred, null or blank if not known',
						`marriageDateYear` INT NULL COMMENT 'The year the marriage occurred, null or blank if not known',
						`comments` MEDIUMTEXT NULL
						) ENGINE = InnoDB COMMENT = 'Information about a marriage between two people';",


				//-- obituary table
				"CREATE TABLE `obituary` (
						`obituaryId` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`personId` INT NOT NULL COMMENT 'The person this obituary is for',
						`source` VARCHAR( 255 ) NULL ,
						`date` DATE NULL ,
						`dateDay` INT NULL COMMENT 'The day of the month the obituary came out empty or null if not known',
						`dateMonth` INT NULL COMMENT 'The month the obituary came out, null or blank if not known',
						`dateYear` INT NULL COMMENT 'The year the obituary came out, null or blank if not known',
						`sourcePage` VARCHAR( 25 ) NULL ,
						`contents` MEDIUMTEXT NULL ,
						`picture` VARCHAR( 255 ) NULL
						) ENGINE = InnoDB	COMMENT = 'Information about an obituary for a person';",
			],
		],

		'genealogy_1' => [
			'title' => 'Genealogy Update 1',
			'description' => 'Update Genealogy 1 for Steamboat Springs to add cemetery information.',
			'sql' => [
				"ALTER TABLE person ADD COLUMN veteranOf VARCHAR(100) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN addition VARCHAR(100) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN block VARCHAR(100) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN lot INT(11) NULL",
				"ALTER TABLE person ADD COLUMN grave INT(11) NULL",
				"ALTER TABLE person ADD COLUMN tombstoneInscription TEXT",
				"ALTER TABLE person ADD COLUMN addedBy INT(11) NOT NULL DEFAULT -1",
				"ALTER TABLE person ADD COLUMN dateAdded INT(11) NULL",
				"ALTER TABLE person ADD COLUMN modifiedBy INT(11) NOT NULL DEFAULT -1",
				"ALTER TABLE person ADD COLUMN lastModified INT(11) NULL",
				"ALTER TABLE person ADD COLUMN privateComments TEXT",
				"ALTER TABLE person ADD COLUMN importedFrom VARCHAR(50) NULL",
			],
		],

		'genealogy_nashville_1' => [
			'title' => 'Genealogy Update : Nashville 1',
			'description' => 'Update Genealogy : for Nashville to add Nashville City Cemetery information.',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE person ADD COLUMN ledgerVolume VARCHAR(20) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN ledgerYear VARCHAR(20) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN ledgerEntry VARCHAR(20) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN sex VARCHAR(20) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN race VARCHAR(20) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN residence VARCHAR(255) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN causeOfDeath VARCHAR(255) NULL DEFAULT ''",
				"ALTER TABLE person ADD COLUMN cemeteryAvenue VARCHAR(255) NULL DEFAULT ''",
				"ALTER TABLE person CHANGE lot lot VARCHAR(20) NULL DEFAULT ''",
			],
		],

		'genealogy_obituary_date_update' => [
			'title' => 'Genealogy Obit date update',
			'description' => 'Add split date information to obituaries if not already added',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE obituary ADD COLUMN dateDay INT NULL",
				"ALTER TABLE obituary ADD COLUMN dateMonth INT NULL",
				"ALTER TABLE obituary ADD COLUMN dateYear INT NULL"
			]
		],

		'genealogy_person_date_update' => [
			'title' => 'Genealogy Person date update',
			'description' => 'Add split date information to obituaries or people if not already added',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE person ADD COLUMN birthDateDay INT NULL",
				"ALTER TABLE person ADD COLUMN birthDateMonth INT NULL",
				"ALTER TABLE person ADD COLUMN birthDateYear INT NULL",
				"ALTER TABLE person ADD COLUMN deathDateDay INT NULL",
				"ALTER TABLE person ADD COLUMN deathDateMonth INT NULL",
				"ALTER TABLE person ADD COLUMN deathDateYear INT NULL"
			]
		],

		'genealogy_marriage_date_update' => [
			'title' => 'Genealogy Marriage date update',
			'description' => 'Add split date information to marriages if not already added',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE marriage ADD COLUMN marriageDateDay INT NULL",
				"ALTER TABLE marriage ADD COLUMN marriageDateMonth INT NULL",
				"ALTER TABLE marriage ADD COLUMN marriageDateYear INT NULL"
			]
		],

		'genealogy_module' => [
			'title' => 'Create Genealogy Module',
			'description' => 'Create Genealogy Module',
			'sql' => [
				"INSERT INTO modules (name, indexName) VALUES ('Genealogy', 'genealogy')"
			]
		],

		'genealogy_lot_length' => [
			'title' => 'Genealogy Lot Length',
			'description' => 'Increase the length of the lot field within person table',
			'sql' => [
				"ALTER TABLE person CHANGE COLUMN lot lot VARCHAR(50) NULL DEFAULT ''"
			]
		]
	];
}

