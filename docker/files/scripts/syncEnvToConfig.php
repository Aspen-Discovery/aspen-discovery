<?php
/**
 * Sync environment variables to existing config files.
 * Only updates values for env vars that are explicitly set.
 * Preserves all other configuration.
 *
 * Runs on every container start to apply env var changes.
 */

require_once __DIR__ . '/../logger/DockerLogger.php';
DockerLogger::init('BACKEND');

$siteName = getenv('SITE_NAME');
if (!$siteName) {
	DockerLogger::error("SITE_NAME environment variable is required");
	exit(1);
}

$configDir = "/usr/local/aspen-discovery/sites/{$siteName}/conf";

if (!is_dir($configDir)) {
	DockerLogger::info("Config directory not found, skipping env sync: {$configDir}");
	exit(0);
}

// Map env vars to ini file locations: [envVar => [file, section, key]]
$envMapping = [
	// Database (config.pwd.ini)
	'DATABASE_HOST'     => ['config.pwd.ini', 'Database', 'database_aspen_host'],
	'DATABASE_PORT'     => ['config.pwd.ini', 'Database', 'database_aspen_dbport'],
	'DATABASE_NAME'     => ['config.pwd.ini', 'Database', 'database_aspen_dbname'],
	'DATABASE_USER'     => ['config.pwd.ini', 'Database', 'database_user'],
	'DATABASE_PASSWORD' => ['config.pwd.ini', 'Database', 'database_password'],

	// Site (config.ini)
	'TZ'                => ['config.ini', 'Site', 'timezone'],
	'URL'               => ['config.ini', 'Site', 'url'],
	'TITLE'             => ['config.ini', 'Site', 'title'],
	'LIBRARY'           => ['config.ini', 'Site', 'libraryName'],

	// Solr (config.ini)
	'SOLR_HOST'         => ['config.ini', 'Index', 'solrHost'],
	'SOLR_PORT'         => ['config.ini', 'Index', 'solrPort'],

	// System (config.ini)
	'DEBUG'             => ['config.ini', 'System', 'debug'],
];

// Group changes by file
$fileChanges = [];
$changedEnvVars = [];

foreach ($envMapping as $envVar => [$file, $section, $key]) {
	$value = getenv($envVar);
	if ($value !== false && $value !== '') {
		$fileChanges[$file][$section][$key] = $value;
		$changedEnvVars[] = $envVar;
	}
}

if (empty($fileChanges)) {
	DockerLogger::info("No environment variables to sync");
	exit(0);
}

DockerLogger::info("Syncing environment variables: " . implode(', ', $changedEnvVars));

// Apply changes to each file
foreach ($fileChanges as $file => $sections) {
	$filePath = "{$configDir}/{$file}";
	if (!file_exists($filePath)) {
		DockerLogger::warn("Config file not found: {$filePath}");
		continue;
	}

	$updated = updateIniFile($filePath, $sections);
	if ($updated) {
		DockerLogger::info("Updated {$file}");
	}
}

// Rebuild database_dsn if any DB component changed
if (isset($fileChanges['config.pwd.ini']['Database'])) {
	rebuildDsn($configDir);
	DockerLogger::info("Rebuilt database_dsn");
}

DockerLogger::info("Environment sync complete");

// =============================================================================
// Helper Functions
// =============================================================================

/**
 * Update specific values in an INI file while preserving all other content.
 *
 * @param string $filePath Path to the INI file
 * @param array $changes Array of [section => [key => value]] to update
 * @return bool True if file was modified
 */
function updateIniFile(string $filePath, array $changes): bool {
	$lines = file($filePath, FILE_IGNORE_NEW_LINES);
	$currentSection = null;
	$modified = false;

	foreach ($lines as $i => $line) {
		$trimmed = trim($line);

		// Track current section
		if (preg_match('/^\[(.+)\]$/', $trimmed, $m)) {
			$currentSection = $m[1];
			continue;
		}

		// Skip comments and empty lines
		if ($trimmed === '' || (isset($trimmed[0]) && $trimmed[0] === ';')) {
			continue;
		}

		// Check if this line should be updated
		if ($currentSection && isset($changes[$currentSection])) {
			foreach ($changes[$currentSection] as $key => $newValue) {
				// Match key at start of line (handles various spacing)
				if (preg_match('/^' . preg_quote($key, '/') . '\s*=/', $trimmed)) {
					// Determine if value needs quotes
					$needsQuotes = strpos($newValue, ' ') !== false || strpos($newValue, '{') !== false || strpos($newValue, '}') !== false || strpos($newValue, ';') !== false;
					$formatted = $needsQuotes ? "\"{$newValue}\"" : $newValue;

					// Preserve original indentation
					$indent = '';
					if (preg_match('/^(\s*)/', $line, $indentMatch)) {
						$indent = $indentMatch[1];
					}

					$lines[$i] = "{$indent}{$key} = {$formatted}";
					$modified = true;
					unset($changes[$currentSection][$key]);
				}
			}
		}
	}

	if ($modified) {
		file_put_contents($filePath, implode("\n", $lines) . "\n");
	}

	return $modified;
}

/**
 * Rebuild the database_dsn value from environment variables.
 * Falls back to INI file values or defaults if env vars not set.
 *
 * @param string $configDir Path to the config directory
 */
function rebuildDsn(string $configDir): void {
	$pwdFile = "{$configDir}/config.pwd.ini";

	if (!file_exists($pwdFile)) {
		return;
	}

	$config = parse_ini_file($pwdFile, true);

	// Prefer env vars, then INI file, then defaults
	$host = getenv('DATABASE_HOST') ?: ($config['Database']['database_aspen_host'] ?? 'localhost');
	$port = getenv('DATABASE_PORT') ?: ($config['Database']['database_aspen_dbport'] ?? '3306');
	$name = getenv('DATABASE_NAME') ?: ($config['Database']['database_aspen_dbname'] ?? 'aspen');

	$dsn = "mysql:host={$host};port={$port};dbname={$name}";

	// Update dsn in file
	updateIniFile($pwdFile, ['Database' => ['database_dsn' => $dsn]]);
}
