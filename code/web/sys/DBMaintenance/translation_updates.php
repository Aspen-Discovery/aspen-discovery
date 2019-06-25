<?php

function getTranslationUpdates() {
    return [
        'languages_setup' => [
            'title' => 'Language Setup',
            'description' => 'Initial setup of language table. ',
            'continueOnError' => false,
            'sql' => [
                "CREATE TABLE IF NOT EXISTS languages (" .
	                "id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
                    "weight INT NOT NULL DEFAULT '0', " .
	                "code CHAR(3) NOT NULL, " .
	                "displayName VARCHAR(50), " .
                    "displayNameEnglish VARCHAR(50), " .
	                "facetValue VARCHAR(100) NOT NULL " .
                ")",
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
			    'ALTER TABLE translation_terms ADD INDEX url (samplePageUrl)'
		    ]
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
		    ]
	    ],
    ];
}
