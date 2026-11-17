<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../../code/web/sys/DBMaintenance/DefaultDatabaseExporter.php';
require_once __DIR__ . '/../../../../../code/web/sys/Translation/Language.php';

/**
 * Tests for DefaultDatabaseExporter, which generates install/aspen.sql.
 */
class DefaultDatabaseExporterTests extends TestCase {

	private DefaultDatabaseExporter $exporter;
	private string $exportFile;

	protected function setUp(): void {
		parent::setUp();
		global $aspen_db;
		$this->exporter = new DefaultDatabaseExporter($aspen_db);
		$this->exportFile = tempnam(sys_get_temp_dir(), 'aspen_sql_');
	}

	protected function tearDown(): void {
		global $aspen_db;

		$exportFileExists = file_exists($this->exportFile);
		if ($exportFileExists) {
			unlink($this->exportFile);
		}
		$aspen_db->exec("DELETE FROM languages WHERE code LIKE 'zt%'");
		$aspen_db->exec("DELETE FROM web_builder_audience WHERE name LIKE 'zt_%'");

		parent::tearDown();
	}

	private function openMemoryHandle() {
		return fopen('php://memory', 'r+');
	}

	private function readHandle($fhnd): string {
		rewind($fhnd);
		return stream_get_contents($fhnd);
	}

	public function testStructureContainsDropAndCreate(): void {
		$fhnd = $this->openMemoryHandle();
		$this->exporter->writeTableStructure($fhnd, 'languages');
		$output = $this->readHandle($fhnd);

		$this->assertStringStartsWith("DROP TABLE IF EXISTS languages;\n", $output);
		$this->assertStringContainsString('CREATE TABLE `languages`', $output);
		$this->assertStringEndsWith(";\n", $output);
	}

	public function testStructureStripsAutoIncrementCounter(): void {
		$fhnd = $this->openMemoryHandle();
		$this->exporter->writeTableStructure($fhnd, 'bad_words');
		$output = $this->readHandle($fhnd);

		$this->assertStringNotContainsString('AUTO_INCREMENT=', $output);
		$this->assertStringContainsString('AUTO_INCREMENT', $output);
	}

	public function testDataUsesExplicitColumnListMatchingSchema(): void {
		global $aspen_db;
		$fhnd = $this->openMemoryHandle();
		$this->exporter->writeTableData($fhnd, 'languages');
		$output = $this->readHandle($fhnd);

		$this->assertMatchesRegularExpression('/^INSERT INTO `languages` \(`[^)]+`\) VALUES$/m', $output);
		preg_match('/^INSERT INTO `languages` \(([^)]+)\) VALUES$/m', $output, $matches);
		$columnsInInsert = array_map(function ($column) {
			return trim($column, ' `');
		}, explode(',', $matches[1]));
		$schemaColumns = $aspen_db->query("SHOW COLUMNS FROM languages")->fetchAll(PDO::FETCH_COLUMN);
		$this->assertSame($schemaColumns, $columnsInInsert);
	}

	public function testDataQuotesValuesAndPreservesNull(): void {
		$language = new Language();
		$language->code = 'zt1';
		$language->displayName = "Quote ' and \\ backslash";
		$language->displayNameEnglish = null;
		$language->facetValue = 'Test';
		$this->assertNotFalse($language->insert());

		$fhnd = $this->openMemoryHandle();
		$this->exporter->writeTableData($fhnd, 'languages', "code = 'zt1'");
		$output = $this->readHandle($fhnd);

		$this->assertStringContainsString("'Quote \\' and \\\\ backslash'", $output);
		$this->assertStringContainsString('NULL', $output);
	}

	public function testDataAppliesFilter(): void {
		$fhnd = $this->openMemoryHandle();
		$this->exporter->writeTableData($fhnd, 'user', "source = 'no_such_source'");
		$output = $this->readHandle($fhnd);

		$this->assertSame('', $output);
	}

	public function testDataChunksLargeTables(): void {
		global $aspen_db;
		$totalRows = (int)$aspen_db->query("SELECT COUNT(*) FROM bad_words")->fetchColumn();
		$this->assertGreaterThan(500, $totalRows, 'bad_words should exceed one chunk for this test to be meaningful');

		$fhnd = $this->openMemoryHandle();
		$this->exporter->writeTableData($fhnd, 'bad_words');
		$output = $this->readHandle($fhnd);

		$expectedStatements = (int)ceil($totalRows / 500);
		$this->assertSame($expectedStatements, substr_count($output, 'INSERT INTO `bad_words`'));
		$this->assertSame($totalRows, preg_match_all('/^\(/m', $output));
	}

	public function testExportCoversEveryTableAndAllConfiguredData(): void {
		global $aspen_db;
		$this->exporter->exportToFile($this->exportFile);
		$output = file_get_contents($this->exportFile);

		$this->assertStringStartsWith("SET FOREIGN_KEY_CHECKS=0;\n", $output);
		$this->assertStringEndsWith("SET FOREIGN_KEY_CHECKS=1;\n", $output);
		$this->assertStringNotContainsString('AUTO_INCREMENT=', $output);

		$allTables = $aspen_db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
		foreach ($allTables as $table) {
			$this->assertStringContainsString("DROP TABLE IF EXISTS $table;", $output, "Missing DROP for $table");
			$this->assertStringContainsString("CREATE TABLE `$table`", $output, "Missing CREATE for $table");
		}

		$tableFilters = array_merge(array_fill_keys($this->exporter->getDataTables(), ''), $this->exporter->getSeedTables());
		foreach ($tableFilters as $table => $filter) {
			$tableHasRows = $this->countRows($table, $filter) > 0;
			if ($tableHasRows) {
				$this->assertStringContainsString("INSERT INTO `$table`", $output, "Missing data for $table");
			}
		}
	}

	private function countRows(string $table, string $filter): int {
		global $aspen_db;
		$countQuery = "SELECT COUNT(*) FROM $table";
		$hasFilter = !empty($filter);
		if ($hasFilter) {
			$countQuery .= " WHERE $filter";
		}
		return (int)$aspen_db->query($countQuery)->fetchColumn();
	}

	public function testSeedFiltersOnlyExportDefaultRows(): void {
		global $aspen_db;
		$aspen_db->exec("INSERT INTO web_builder_audience (name) VALUES ('zt_test_audience')");

		$fhnd = $this->openMemoryHandle();
		$filter = $this->exporter->getSeedTables()['web_builder_audience'];
		$this->exporter->writeTableData($fhnd, 'web_builder_audience', $filter);
		$output = $this->readHandle($fhnd);

		$this->assertStringNotContainsString('zt_test_audience', $output);
	}

	public function testExportUsesSeedDataFileInPlaceOfSeedTables(): void {
		$seedDataFile = tempnam(sys_get_temp_dir(), 'aspen_seed_');
		file_put_contents($seedDataFile, "INSERT INTO `languages` (`id`, `code`) VALUES (999, 'zt9');\n");

		$this->exporter->exportToFile($this->exportFile, $seedDataFile);
		$output = file_get_contents($this->exportFile);
		unlink($seedDataFile);

		$this->assertStringContainsString("VALUES (999, 'zt9');", $output);
		$this->assertStringNotContainsString('INSERT INTO `library`', $output);
		$this->assertStringContainsString('INSERT INTO `modules`', $output);
		$this->assertStringContainsString('CREATE TABLE `library`', $output);
	}

	public function testExportRejectsUnreadableSeedDataFile(): void {
		$this->expectException(RuntimeException::class);
		$this->exporter->exportToFile($this->exportFile, '/no/such/seed_data.sql');
	}

	public function testSeedDataExportContainsOnlySeedTableData(): void {
		$this->exporter->exportSeedDataToFile($this->exportFile);
		$output = file_get_contents($this->exportFile);

		$this->assertStringContainsString('INSERT INTO `languages`', $output);
		$this->assertStringNotContainsString('CREATE TABLE', $output);
		$this->assertStringNotContainsString('INSERT INTO `modules`', $output);
	}

	public function testExportFromGeneratedSeedFileMatchesDatabaseExport(): void {
		$seedDataFile = tempnam(sys_get_temp_dir(), 'aspen_seed_');
		$this->exporter->exportSeedDataToFile($seedDataFile);

		$this->exporter->exportToFile($this->exportFile, $seedDataFile);
		$exportFromSeedFile = file_get_contents($this->exportFile);
		unlink($seedDataFile);

		$this->exporter->exportToFile($this->exportFile);
		$exportFromDatabase = file_get_contents($this->exportFile);

		$this->assertSame($exportFromDatabase, $exportFromSeedFile);
	}

	public function testExportInsertColumnListsAlwaysMatchSchema(): void {
		global $aspen_db;
		$this->exporter->exportToFile($this->exportFile);
		$output = file_get_contents($this->exportFile);

		preg_match_all('/^INSERT INTO `([^`]+)` \(([^)]+)\) VALUES$/m', $output, $matches, PREG_SET_ORDER);
		$this->assertNotEmpty($matches);

		$checkedTables = [];
		foreach ($matches as $match) {
			$table = $match[1];
			$tableAlreadyChecked = isset($checkedTables[$table]);
			if ($tableAlreadyChecked) {
				continue;
			}
			$checkedTables[$table] = true;

			$columnsInInsert = array_map(function ($column) {
				return trim($column, ' `');
			}, explode(',', $match[2]));
			$schemaColumns = $aspen_db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
			$this->assertSame($schemaColumns, $columnsInInsert, "Insert column list for $table does not match its schema");
		}
	}

	public function testExportOnlyIncludesAdminUsers(): void {
		$this->exporter->exportToFile($this->exportFile);
		$output = file_get_contents($this->exportFile);

		preg_match('/^INSERT INTO `user` .*?VALUES\n(.*?);\n/ms', $output, $matches);
		$noUserInsertFound = empty($matches);
		if ($noUserInsertFound) {
			$this->markTestSkipped('No admin users present in the source database');
		}
		$this->assertStringContainsString("'admin'", $matches[1]);
		$this->assertStringNotContainsString("'phpunit'", $matches[1]);
	}
}
