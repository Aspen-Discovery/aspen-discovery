<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for the createDefaultDatabaseScript cron entry point and its
 * seed data command line options.
 */
class CreateDefaultDatabaseScriptTests extends TestCase {

	private string $scriptPath;
	private string $aspenSqlPath;
	private string $aspenSqlBackup;

	protected function setUp(): void {
		parent::setUp();
		$this->scriptPath = ROOT_DIR . '/cron/createDefaultDatabaseScript.php';
		$this->aspenSqlPath = ROOT_DIR . '/../../install/aspen.sql';
		$this->aspenSqlBackup = file_get_contents($this->aspenSqlPath);
	}

	protected function tearDown(): void {
		file_put_contents($this->aspenSqlPath, $this->aspenSqlBackup);
		parent::tearDown();
	}

	private function runScript(string $arguments): array {
		$output = [];
		exec("php $this->scriptPath {$_SERVER['aspen_server']} $arguments 2>&1", $output, $exitCode);
		return [
			$exitCode,
			implode("\n", $output),
		];
	}

	public function testExportSeedDataOptionWritesOnlySeedData(): void {
		$seedDataFile = tempnam(sys_get_temp_dir(), 'aspen_seed_');
		[
			$exitCode,
			$output,
		] = $this->runScript("--export-seed-data=$seedDataFile");
		$this->assertSame(0, $exitCode, $output);

		$seedData = file_get_contents($seedDataFile);
		unlink($seedDataFile);
		$this->assertStringContainsString('INSERT INTO `languages`', $seedData);
		$this->assertStringNotContainsString('CREATE TABLE', $seedData);
		$this->assertSame($this->aspenSqlBackup, file_get_contents($this->aspenSqlPath));
	}

	public function testSeedDataOptionReplacesSeedTablesInExport(): void {
		$seedDataFile = tempnam(sys_get_temp_dir(), 'aspen_seed_');
		file_put_contents($seedDataFile, "INSERT INTO `languages` (`id`, `code`) VALUES (999, 'zt9');\n");

		[
			$exitCode,
			$output,
		] = $this->runScript("--seed-data=$seedDataFile");
		unlink($seedDataFile);
		$this->assertSame(0, $exitCode, $output);

		$export = file_get_contents($this->aspenSqlPath);
		$this->assertStringContainsString("VALUES (999, 'zt9');", $export);
		$this->assertStringNotContainsString('INSERT INTO `library`', $export);
		$this->assertStringContainsString('CREATE TABLE `library`', $export);
	}
}
