<?php
/** @noinspection SqlResolve */
function getSummonUpdates() {
	return [
        'createSummonModule' => [
			'title' => 'Create Summon module',
			'description' => 'Setup modules for Summon Integration',
			'sql' => [
				"INSERT INTO modules (name, indexName, backgroundProcess) VALUES ('Summon', '', '')",

			],
		],
    ];
}