<?php
/** @noinspection PhpUnused */
function getUpdates21_13_00() : array
{
	global $configArray;

	return [
		/*'name' => [
			'title' => '',
			'description' => '',
			'sql' => [
				''
			]
		], //sample*/
		'open_archives_image_regex' => [
			'title' => 'Open Archives Image Regular Expression',
			'description' => 'Add a regular expression to get thumbnails for Open Archives',
			'sql' => [
				"ALTER TABLE open_archives_collection ADD COLUMN imageRegex VARCHAR(100) DEFAULT ''"
			]
		], //open_archives_image_regex
		'polaris_item_identifiers' => [
			'title' => 'Store item identifiers for Polaris',
			'description' => 'Store item identifiers for Polaris',
			'sql' => [
				"UPDATE indexing_profiles set itemRecordNumber = '9' WHERE indexingClass = 'Polaris'"
			]
		], //polaris_item_identifiers
		'polaris_full_update_21_13' => [
			'title' => 'Run a full update for polaris',
			'description' => 'Run a full update for polaris',
			'sql' => [
				"UPDATE indexing_profiles set runFullUpdate = 1 WHERE indexingClass = 'Polaris'"
			]
		], //polaris_full_update_21_13
		'utf8mb4support' => [
			'title' => 'UTF-8 MB4 support',
			'description' => 'Support emojis and other extended characters within the database',
			'continueOnError' => true,
			'sql' => [
				"ALTER DATABASE " . $configArray['Database']['database_aspen_dbname'] . " DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;",
				"ALTER TABLE cached_values CHANGE COLUMN value value VARCHAR(16000)",
				"ALTER TABLE grouped_work_alternate_titles CHANGE COLUMN alternateTitle alternateTitle VARCHAR(709)",
				"ALTER TABLE indexed_edition DROP INDEX edition",
				"ALTER TABLE indexed_edition DROP INDEX edition_2",
				"ALTER TABLE indexed_edition MODIFY COLUMN edition VARCHAR(1000) collate utf8mb4_bin",
				"ALTER TABLE indexed_physicalDescription DROP INDEX physicalDescription",
				"ALTER TABLE indexed_physicalDescription DROP INDEX physicalDescription_2",
				"ALTER TABLE indexed_physicalDescription MODIFY COLUMN physicalDescription VARCHAR(1000) collate utf8mb4_bin",
				"ALTER TABLE session MODIFY COLUMN created DATETIME DEFAULT CURRENT_TIMESTAMP",
				"ALTER TABLE translation_terms DROP INDEX term",
				"ALTER TABLE translation_terms MODIFY COLUMN term VARCHAR(1000) collate utf8mb4_bin",
				"updateAllTablesToUtf8mb4",
				"ALTER TABLE indexed_edition ADD INDEX edition(edition(500))",
				"ALTER TABLE indexed_physicalDescription ADD INDEX physicalDescription (physicalDescription(500))",
				"ALTER TABLE translation_terms ADD INDEX term (term(500))",
			]
		],
		'placard_alt_text' => [
			'title' => 'Add alt text for Placard Images',
			'description' => 'Add alt text for Placard Images',
			'sql' => [
				'ALTER TABLE placards ADD COLUMN altText VARCHAR(500)'
			]
		], //placard_alt_text
		'increase_nonHoldableITypes' => [
			'title' => 'Increase nonHoldableITypes length',
			'description' => 'Increase the length of the nonHoldableITypes field',
			'sql' => [
				'ALTER TABLE indexing_profiles CHANGE COLUMN nonHoldableITypes nonHoldableITypes varchar(600) DEFAULT NULL'
			]
		], //increase_nonHoldableITypes
	];
}

function updateAllTablesToUtf8mb4(&$update)
{
	global $configArray;
	set_time_limit(0);

	global $aspen_db;
	$updateOk = true;
	$result = $aspen_db->query("show tables", PDO::FETCH_ASSOC);
	$allTables = $result->fetchAll();
	foreach ($allTables as $tableInfo){
		$tableName = reset($tableInfo);
		$fullSQL = "";
		try {
			//Get a list of the collations for all tables to see if we need to preserve binary collations
			$modifyClause = '';
			$columnInfoResult = $aspen_db->query("SELECT * FROM information_schema.COLUMNS WHERE table_schema = '{$configArray['Database']['database_aspen_dbname']}' AND table_name = '{$tableName}'", PDO::FETCH_ASSOC);
			$allColumnInfo = $columnInfoResult->fetchAll();
			foreach ($allColumnInfo as $columnInfo){
				if ($columnInfo['COLLATION_NAME'] == 'utf8_bin' || $columnInfo['COLLATION_NAME'] == 'utf8mb4_bin'){
					$modifyClause .= ", MODIFY {$columnInfo['COLUMN_NAME']} {$columnInfo['COLUMN_TYPE']} CHARACTER SET utf8mb4 COLLATE utf8mb4_bin";
					if ($columnInfo['IS_NULLABLE']){
						$modifyClause .= " NULL";
					}else{
						$modifyClause .= " NOT NULL";
					}
					if ($columnInfo['COLUMN_DEFAULT'] != null){
						if ($columnInfo['COLUMN_DEFAULT'] == '"NULL"' || $columnInfo['COLUMN_DEFAULT'] == "'NULL'" || $columnInfo['COLUMN_DEFAULT'] == 'NULL') {
							$modifyClause .= " DEFAULT NULL";
						}else{
							$modifyClause .= " DEFAULT '{$columnInfo['COLUMN_DEFAULT']}'";
						}
					}
				}
			}
			if (empty($modifyClause)) {
				$fullSQL = "ALTER TABLE $tableName CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
			}else{
				$fullSQL = "ALTER TABLE $tableName DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci $modifyClause";
			}
			$aspen_db->exec($fullSQL);
		}catch (Exception $e){
			if (isset($update['continueOnError']) && $update['continueOnError']) {
				if (!isset($update['status'])) {
					$update['status'] = '';
				}
				$update['status'] .= '<br/><strong>' . $tableName . "</strong> failed to update to utf8mb4 <br/> -  $fullSQL <br/> - " .$e->getMessage() . '<br/>';
			} else {
				$update['status'] = '<br/><strong>' . $tableName . "</strong> failed to update to utf8mb4 <br/> - $fullSQL <br/> - ". $e->getMessage() . '<br/>';
				$updateOk = false;
			}
		}
	}
	return $updateOk;
}
