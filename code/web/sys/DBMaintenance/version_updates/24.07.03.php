<?php

/** @noinspection PhpUnused */
function getUpdates24_07_03(): array {
	/** @noinspection SqlWithoutWhere */
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark - ByWater
		'add_configuration_for_index_deletions' => [
			'title' => 'Add configuration for index deletions',
			'description' => 'Add additional configuration for how records are deleted from solr',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE system_variables ADD COLUMN deletionCommitInterval INT DEFAULT 1000",
				"ALTER TABLE system_variables ADD COLUMN waitAfterDeleteCommit TINYINT DEFAULT 0",
			]
		], //add_configuration_for_index_deletions

	];
}