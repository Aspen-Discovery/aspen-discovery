<?php

/**
 * Migrates uploaded files from local storage to an S3-compatible backend.
 *
 * Usage:
 *   php migrateLocalFilesToS3.php --setting-id=<id> [--dry-run] [--path=<subpath>]
 *
 * Options:
 *   --setting-id=N   ID of the StorageSetting record to use as the target.
 *   --dry-run        List what would be migrated without uploading anything.
 *   --path=<subpath> Only migrate files under uploads/web_builder_image/<subpath>
 *                    (e.g. --path=full, or --path=full/some_image.png) instead of
 *                    everything. Useful for migrating in batches or retrying a
 *                    single file.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

set_time_limit(-1);
ini_set('memory_limit', '1G');

// =============================================================================
// Parse arguments
// =============================================================================

$args = [];
foreach ($_SERVER['argv'] ?? [] as $arg) {
	if (preg_match('/^--([^=]+)(?:=(.*))?$/', $arg, $m)) {
		$args[$m[1]] = $m[2] ?? true;
	}
}

$dryRun    = isset($args['dry-run']);
$settingId = isset($args['setting-id']) ? (int)$args['setting-id'] : null;
$subPath   = isset($args['path']) ? trim($args['path'], '/') : '';

if (!$settingId) {
	echo "Usage: php migrateLocalFilesToS3.php --setting-id=<id> [--dry-run] [--path=<subpath>]\n";
	echo "       Run without --setting-id to list available configurations:\n\n";
	listSettings();
	exit(1);
}

if ($dryRun) {
	echo "DRY RUN: no files will be uploaded.\n\n";
}

// =============================================================================
// Load and validate target StorageSetting
// =============================================================================

require_once ROOT_DIR . '/sys/Storage/StorageSetting.php';

$setting = new StorageSetting();
$setting->id = $settingId;
if (!$setting->find(true)) {
	echo "Error: No storage setting found with id={$settingId}.\n";
	listSettings();
	exit(1);
}

if ($setting->driver !== 's3') {
	echo "Error: Setting '{$setting->name}' uses driver '{$setting->driver}', not 's3'.\n";
	exit(1);
}

if (empty($setting->bucket) || empty($setting->accessKeyId) || empty($setting->accessKeySecret)) {
	echo "Error: Setting '{$setting->name}' is missing bucket or credentials.\n";
	exit(1);
}

// =============================================================================
// Set up source (local) and target (S3) drivers
// =============================================================================

global $configArray, $serverName;
$dataRoot = rtrim($configArray['Site']['dataPath'] ?? '/data/aspen-discovery/' . $serverName, '/');

require_once ROOT_DIR . '/sys/Storage/LocalStorageDriver.php';
$source = new LocalStorageDriver($dataRoot);

require_once ROOT_DIR . '/sys/Storage/S3StorageDriver.php';
// Explicit httpClient avoids AsyncAws probing for the optional amphp/http-client
// package, which crashes under this codebase's autoloader when it's not installed.
$httpClient = new \Symfony\Component\HttpClient\CurlHttpClient(['timeout' => 5, 'max_duration' => 15]);
$client = new AsyncAws\S3\S3Client(
	[
		'accessKeyId'      => $setting->accessKeyId,
		'accessKeySecret'  => $setting->accessKeySecret,
		'region'           => $setting->region ?: 'us-east-1',
		'endpoint'         => $setting->endpoint ?: null,
		'pathStyleEndpoint' => !empty($setting->endpoint),
	],
	null,
	$httpClient
);
$target = new S3StorageDriver($client, $setting->bucket, $setting->baseUrl);

// =============================================================================
// Walk the image uploads directory and migrate
// =============================================================================

// ImageUpload/S3StorageDriver only ever read/write keys under this prefix
// (see ImageUpload::generateDerivatives()). $dataRoot also holds unrelated
// site data (MARC records, SQL backups, fonts, etc.) that must never be
// walked or uploaded here.
$imagesRoot = $dataRoot . '/uploads/web_builder_image';
$walkRoot = $subPath !== '' ? $imagesRoot . '/' . $subPath : $imagesRoot;

if (!file_exists($walkRoot)) {
	echo "Error: {$walkRoot} does not exist.\n";
	exit(1);
}

echo "Source : {$walkRoot}\n";
echo "Target : s3://{$setting->bucket}  (setting: {$setting->name})\n\n";

$migrated = 0;
$skipped  = 0;
$failed   = 0;

if (is_file($walkRoot)) {
	$files = [new SplFileInfo($walkRoot)];
} else {
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($walkRoot, FilesystemIterator::SKIP_DOTS)
	);
}

foreach ($files as $file) {
	if (!$file->isFile()) {
		continue;
	}

	$absolutePath = $file->getPathname();
	$key = ltrim(substr($absolutePath, strlen($dataRoot)), '/');

	if ($target->exists($key)) {
		echo "  SKIP  {$key}\n";
		$skipped++;
		continue;
	}

	if ($dryRun) {
		echo "  WOULD UPLOAD  {$key}\n";
		$migrated++;
		continue;
	}

	$mimeType = mime_content_type($absolutePath) ?: 'application/octet-stream';

	if ($target->write($key, $absolutePath, $mimeType)) {
		echo "  OK    {$key}\n";
		$migrated++;
	} else {
		echo "  FAIL  {$key}\n";
		$failed++;
	}
}

// =============================================================================
// Summary
// =============================================================================

$label = $dryRun ? 'Would migrate' : 'Migrated';
echo "\nDone.\n";
echo "  {$label} : {$migrated}\n";
echo "  Skipped  : {$skipped}\n";
if (!$dryRun) {
	echo "  Failed   : {$failed}\n";
}

// =============================================================================
// Repoint image_uploads rows whose files are now fully present in the target
// bucket; copying the bytes doesn't change storageSettingId, which is what
// getDisplayUrl()/generateDerivatives() actually read from.
// =============================================================================

require_once ROOT_DIR . '/sys/File/ImageUpload.php';

$repointed = 0;
$incomplete = 0;

$imageUpload = new ImageUpload();
$imageUpload->find();
while ($imageUpload->fetch()) {
	if ((int)$imageUpload->storageSettingId === $settingId || empty($imageUpload->fullSizePath)) {
		continue;
	}

	$variantProperties = [
		'full'    => 'fullSizePath',
		'x-large' => 'xLargeSizePath',
		'large'   => 'largeSizePath',
		'medium'  => 'mediumSizePath',
		'small'   => 'smallSizePath',
	];
	$complete = true;
	foreach ($variantProperties as $variant => $property) {
		$path = $imageUpload->$property;
		if (empty($path)) {
			continue;
		}
		if (!$target->exists('uploads/web_builder_image/' . $variant . '/' . $path)) {
			$complete = false;
			break;
		}
	}

	if (!$complete) {
		$incomplete++;
		continue;
	}

	if ($dryRun) {
		echo "  WOULD REPOINT  image_uploads id={$imageUpload->id}\n";
	} else {
		global $aspen_db;
		$aspen_db->query('UPDATE image_uploads SET storageSettingId = ' . $settingId . ' WHERE id = ' . (int)$imageUpload->id);
	}
	$repointed++;
}

$repointLabel = $dryRun ? 'Would repoint' : 'Repointed';
echo "\n{$repointLabel} : {$repointed} image_uploads row(s) to setting id={$settingId}\n";
if ($incomplete > 0) {
	echo "Left alone  : {$incomplete} row(s) with files not yet fully present in the target bucket\n";
}

if ($failed > 0) {
	exit(1);
}

// =============================================================================
// Helper
// =============================================================================

function listSettings(): void {
	require_once ROOT_DIR . '/sys/Storage/StorageSetting.php';
	$setting = new StorageSetting();
	$setting->find();
	$rows = [];
	while ($setting->fetch()) {
		$rows[] = sprintf('  id=%-4d  driver=%-6s  active=%-3s  %s',
			$setting->id,
			$setting->driver,
			$setting->isActive ? 'yes' : 'no',
			$setting->name
		);
	}
	if (empty($rows)) {
		echo "No storage configurations found. Add one via Admin > Storage Settings.\n";
	} else {
		echo "Available storage configurations:\n";
		echo implode("\n", $rows) . "\n";
	}
}
