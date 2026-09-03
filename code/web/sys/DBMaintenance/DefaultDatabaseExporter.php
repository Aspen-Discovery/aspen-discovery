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
			'account_profiles' => '',
			'browse_category' => '',
			'browse_category_group' => '',
			'browse_category_group_entry' => '',
			'events_facet' => '',
			'events_facet_groups' => '',
			'grouped_work_display_settings' => '',
			'grouped_work_facet' => '',
			'grouped_work_facet_groups' => '',
			'grouped_work_format_sort_group' => '',
			'ip_lookup' => '',
			'languages' => '',
			'layout_settings' => '',
			'library' => '',
			'library_themes' => '',
			'list_indexing_settings' => '',
			'location' => '',
			'materials_request_status' => '',
			'open_archives_facet_groups' => '',
			'open_archives_facets' => '',
			'system_variables' => '',
			'themes' => '',
			'user' => "source = 'admin'",
			'user_list_facet_groups' => '',
			'user_roles' => '',
			'variables' => '',
			'web_builder_audience' => '',
			'web_builder_category' => '',
			'website_facet_groups' => '',
			'website_facets' => '',
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
