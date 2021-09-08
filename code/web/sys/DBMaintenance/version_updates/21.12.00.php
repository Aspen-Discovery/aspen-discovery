<?php
/** @noinspection PhpUnused */
function getUpdates21_12_00() : array
{
	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample */
		'placard_languages' => [
			'title' => 'Placard Languages',
			'description' => 'Allow Placards to be limited by language',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE placard_language (
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							placardId INT,
							languageId INT,
							UNIQUE INDEX placardLanguage(placardId, languageId)
						) ENGINE = INNODB;',
				'INSERT INTO placard_language (languageId, placardId) SELECT languages.id, placards.id from languages, placards;'
			]
		], //placard_languages
	];
}