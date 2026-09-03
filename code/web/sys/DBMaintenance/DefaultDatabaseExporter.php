<?php

/**
 * Exports the database structure and default data to the SQL script used to
 * initialize new Aspen Discovery installations (install/aspen.sql).
 *
 * All output is generated through PDO so the result does not depend on the
 * version of any external dump tooling, and inserts always carry explicit
 * column lists derived from the schema they were generated against.
 */
class DefaultDatabaseExporter {

	private PDO $aspen_db;

	public function __construct(PDO $aspen_db) {
		$this->aspen_db = $aspen_db;
	}

	/**
	 * Tables where all rows are exported in addition to the structure
	 */
	public function getDataTables(): array {
		return [
			'bad_words',
			'db_update',
			'modules',
			'permissions',
			'role_permissions',
			'roles',
		];
	}

	/**
	 * Tables holding the default rows a new installation starts with.
	 * An optional WHERE clause limits the export to the default rows.
	 */
	public function getSeedTables(): array {
		return [
			'account_profiles' => "name = 'admin'",
			'browse_category' => "textId IN ('main_new_fiction', 'main_new_non_fiction')",
			'browse_category_group' => 'id = 1',
			'browse_category_group_entry' => 'browseCategoryGroupId = 1',
			'events_facet' => 'facetGroupId = 1',
			'events_facet_groups' => 'id = 1',
			'grouped_work_display_settings' => 'id <= 5',
			'grouped_work_facet' => 'facetGroupId <= 4',
			'grouped_work_facet_groups' => 'id <= 4',
			'grouped_work_format_sort_group' => 'id = 1',
			'ip_lookup' => "ip = '127.0.0.1'",
			'languages' => "code = 'en'",
			'layout_settings' => 'id = 1',
			'library' => 'libraryId = 1',
			'library_themes' => 'libraryId = 1',
			'list_indexing_settings' => 'id = 1',
			'location' => 'locationId = 1',
			'materials_request_status' => 'libraryId = -1',
			'open_archives_facet_groups' => 'id = 1',
			'open_archives_facets' => 'facetGroupId = 1',
			'system_variables' => '',
			'themes' => 'id = 1',
			'user' => "source = 'admin'",
			'user_list_facet_groups' => 'id = 1',
			'user_roles' => "userId IN (SELECT id FROM user WHERE source = 'admin')",
			'variables' => "name IN ('lastHooplaExport', 'validateChecksumsFromDisk', 'offline_mode_when_offline_login_allowed', 'fullReindexIntervalWarning', 'fullReindexIntervalCritical', 'bypass_export_validation', 'last_validatemarcexport_time', 'last_export_valid', 'record_grouping_running', 'last_grouping_time', 'partial_reindex_running', 'last_reindex_time', 'lastPartialReindexFinish', 'full_reindex_running', 'lastFullReindexFinish', 'num_title_in_unique_sitemap', 'num_titles_in_most_popular_sitemap')",
			'web_builder_audience' => "name IN ('Adults', 'Children', 'Everyone', 'Parents', 'Seniors', 'Teens', 'Tweens')",
			'web_builder_category' => "name IN ('Arts and Music', 'eBooks and Audiobooks', 'Homework Help', 'Languages and Culture', 'Library Documents and Policies', 'Lifelong Learning', 'Local History', 'Newspapers and Magazines', 'Reading Recommendations', 'Reference and Research', 'Video Streaming')",
			'website_facet_groups' => 'id = 1',
			'website_facets' => 'facetGroupId = 1',
		];
	}

	public function exportToFile(string $exportFile): void {
		$fhnd = fopen($exportFile, 'w');
		$fileOpened = $fhnd !== false;
		if (!$fileOpened) {
			throw new RuntimeException("Could not open $exportFile for writing");
		}

		fwrite($fhnd, "SET FOREIGN_KEY_CHECKS=0;\n");

		$listTablesStmt = $this->aspen_db->query("SHOW TABLES");
		$allTables = $listTablesStmt->fetchAll(PDO::FETCH_COLUMN);
		foreach ($allTables as $table) {
			$this->writeTableStructure($fhnd, $table);
		}

		foreach ($this->getDataTables() as $table) {
			$this->writeTableData($fhnd, $table);
		}
		foreach ($this->getSeedTables() as $table => $filter) {
			$this->writeTableData($fhnd, $table, $filter);
		}

		fwrite($fhnd, "SET FOREIGN_KEY_CHECKS=1;\n");
		fclose($fhnd);
	}

	/**
	 * @param resource $fhnd Open file handle to write to
	 */
	public function writeTableStructure($fhnd, string $table): void {
		fwrite($fhnd, "DROP TABLE IF EXISTS $table;\n");
		$createTableStmt = $this->aspen_db->query("SHOW CREATE TABLE " . $table);
		$createTableSql = $createTableStmt->fetch(PDO::FETCH_ASSOC);
		$createTableValue = preg_replace('/ AUTO_INCREMENT=\d+/', '', $createTableSql['Create Table']);
		//MariaDB 10.6+ renders the utf8 charset as utf8mb3, which servers older than 10.6
		//cannot import. Normalize so the output does not depend on the server version.
		$createTableValue = str_replace('utf8mb3', 'utf8', $createTableValue);
		fwrite($fhnd, $createTableValue . ";\n");
	}

	/**
	 * Write INSERT statements with an explicit column list derived from the current
	 * structure so the generated inserts always match the schema they were generated with.
	 *
	 * @param resource $fhnd Open file handle to write to
	 */
	public function writeTableData($fhnd, string $table, string $filter = ''): void {
		$columnsStmt = $this->aspen_db->query("SHOW COLUMNS FROM $table");
		$columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
		$columnList = '`' . implode('`, `', $columns) . '`';

		$dataQuery = "SELECT * FROM $table";
		$hasFilter = !empty($filter);
		if ($hasFilter) {
			$dataQuery .= " WHERE $filter";
		}
		$dataQuery .= " ORDER BY 1";
		$dataStmt = $this->aspen_db->query($dataQuery);

		$rows = [];
		while ($row = $dataStmt->fetch(PDO::FETCH_NUM)) {
			$values = [];
			foreach ($row as $value) {
				$values[] = $value === null ? 'NULL' : $this->aspen_db->quote($value);
			}
			$rows[] = '(' . implode(',', $values) . ')';
			$batchIsFull = count($rows) === 500;
			if ($batchIsFull) {
				fwrite($fhnd, "INSERT INTO `$table` ($columnList) VALUES\n" . implode(",\n", $rows) . ";\n");
				$rows = [];
			}
		}

		$hasRemainingRows = !empty($rows);
		if ($hasRemainingRows) {
			fwrite($fhnd, "INSERT INTO `$table` ($columnList) VALUES\n" . implode(",\n", $rows) . ";\n");
		}
	}
}
