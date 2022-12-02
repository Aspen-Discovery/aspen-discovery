<?php

function getTranslationUpdates() {
	return [
		'languages_setup' => [
			'title' => 'Language Setup',
			'description' => 'Initial setup of language table. ',
			'continueOnError' => false,
			'sql' => [
				"CREATE TABLE IF NOT EXISTS languages (" . "id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " . "weight INT NOT NULL DEFAULT '0', " . "code CHAR(3) NOT NULL, " . "displayName VARCHAR(50), " . "displayNameEnglish VARCHAR(50), " . "facetValue VARCHAR(100) NOT NULL " . ")",
				"ALTER TABLE languages ADD UNIQUE INDEX `code` (`code`)",
				"INSERT INTO languages (code, displayName, displayNameEnglish, facetValue) VALUES ('en', 'English', 'English', 'English')",
			],
		],

		'languages_show_for_translators' => [
			'title' => 'Languages option to show for translators only',
			'description' => 'Option to show languages to translators only for use while translations are being built. ',
			'sql' => [
				"ALTER TABLE languages ADD COLUMN displayToTranslatorsOnly TINYINT(1) DEFAULT 0",
			],
		],

		'language_locales' => [
			'title' => 'Language locales',
			'description' => 'Add locales to languages for use when formatting numbers',
			'sql' => [
				"ALTER TABLE languages ADD COLUMN locale VARCHAR(10) DEFAULT 'en-US'",
			],
		],

		'translation_terms' => [
			'title' => 'Translation Term',
			'description' => 'Initial setup of translation term table',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS translation_terms (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					term VARCHAR(50) NOT NULL, 
					parameterNotes VARCHAR(255),
					samplePageUrl VARCHAR(255)
				)',
				'ALTER TABLE translation_terms ADD UNIQUE INDEX term (term)',
				'ALTER TABLE translation_terms ADD INDEX url (samplePageUrl)',
			],
		],

		'translations' => [
			'title' => 'Translations',
			'description' => 'Initial setup of translations table',
			'sql' => [
				'CREATE TABLE IF NOT EXISTS translations (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					termId INT NOT NULL,
					languageId INT NOT NULL,
					translation TEXT,
					translated TINYINT NOT NULL DEFAULT 0
				)',
				'ALTER TABLE translations ADD UNIQUE INDEX term_language (termId, languageId)',
				'ALTER TABLE translations ADD INDEX translation_status (languageId, translated)',
			],
		],

		'translator_role' => [
			'title' => 'Translator Role',
			'description' => 'Add the translator role',
			'sql' => [
				"INSERT INTO roles (name, description) VALUES ('translator', 'Allows the user to translate the system.')",
			],
		],

		'translation_term_default_text' => [
			'title' => 'Translation Term Default Text',
			'description' => 'Add default text to translation term so we can determine when to reload translations',
			'sql' => [
				'ALTER TABLE translation_terms ADD COLUMN defaultText TEXT',
				'ALTER TABLE translations ADD COLUMN needsReview TINYINT(1) DEFAULT 0',
			],
		],

		'translation_case_sensitivity' => [
			'title' => 'Translation case sensitivity',
			'description' => 'Make sure that translations are case sensitive so Book and BOOK can be translated differently',
			'sql' => [
				"ALTER TABLE translations CHANGE translation translation TEXT COLLATE utf8_bin",
				"DELETE FROM translations WHERE languageId = 1",
				"TRUNCATE TABLE cached_values",
			],
		],

		'translation_term_case_sensitivity' => [
			'title' => 'Translation term case sensitivity',
			'description' => 'Make sure that translations are case sensitive so Book and BOOK can be translated differently',
			'sql' => [
				"ALTER TABLE translation_terms CHANGE term term VARCHAR(50) COLLATE utf8_bin",
				"TRUNCATE TABLE cached_values",
			],
		],

		'translation_term_increase_length' => [
			'title' => 'Translation term increase length of term',
			'description' => 'Make sure that translations are case sensitive so Book and BOOK can be translated differently',
			'sql' => [
				"ALTER TABLE translation_terms CHANGE term term VARCHAR(1000) COLLATE utf8_bin",
				"TRUNCATE TABLE cached_values",
			],
		],
	];
}
