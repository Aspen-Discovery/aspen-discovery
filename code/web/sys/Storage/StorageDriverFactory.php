<?php

require_once ROOT_DIR . '/sys/Storage/LocalStorageDriver.php';

class StorageDriverFactory {
	private static ?StorageDriver $instance = null;
	private static ?StorageDriver $localInstance = null;
	private static array $instancesById = [];

	public static function get(): StorageDriver {
		global $logger;
		if (self::$instance === null) {
			self::$instance = self::create();
		}
		$logger->log('StorageDriverFactory::get() resolved active driver ' . get_class(self::$instance), Logger::LOG_DEBUG);
		return self::$instance;
	}

	// Resolves the driver a specific file was actually written to, which may
	// differ from the currently active driver returned by get(). $id is a
	// storage_settings.id; null means Local Storage, since that was the only
	// backend before per-file tracking existed.
	public static function getById(?int $id): StorageDriver {
		global $logger;
		if ($id === null) {
			$logger->log('StorageDriverFactory::getById(null) resolved LocalStorageDriver', Logger::LOG_DEBUG);
			return self::getLocalDriver();
		}
		if (!isset(self::$instancesById[$id])) {
			self::$instancesById[$id] = self::createFromId($id);
		}
		$logger->log("StorageDriverFactory::getById($id) resolved " . get_class(self::$instancesById[$id]), Logger::LOG_DEBUG);
		return self::$instancesById[$id];
	}

	// Allows tests to inject a driver without touching config.
	public static function set(StorageDriver $driver): void {
		self::$instance = $driver;
	}

	public static function reset(): void {
		self::$instance = null;
		self::$localInstance = null;
		self::$instancesById = [];
	}

	public static function resolveDataRoot(): string {
		global $configArray, $serverName;
		return $configArray['Site']['dataPath'] ?? '/data/aspen-discovery/' . $serverName;
	}

	// The storage_settings.id a new write should be recorded against. Null
	// whenever the active backend is Local Storage, matching the
	// null-means-local convention getById() relies on.
	public static function getActiveSettingId(): ?int {
		$setting = self::loadActiveSetting();
		if ($setting === null || $setting->driver !== 's3') {
			return null;
		}
		return $setting->id;
	}

	private static function create(): StorageDriver {
		$setting = self::loadActiveSetting();
		return self::buildDriver($setting);
	}

	private static function createFromId(int $id): StorageDriver {
		$setting = self::loadSettingById($id);
		return self::buildDriver($setting);
	}

	private static function buildDriver(?StorageSetting $setting): StorageDriver {
		if ($setting !== null && $setting->driver === 's3' && !empty($setting->bucket)) {
			require_once ROOT_DIR . '/sys/Storage/S3StorageDriver.php';

			// Short timeouts so an unreachable S3 endpoint fails fast instead
			// of tying up a PHP-FPM worker for the platform default (which
			// can run well past a minute).
			$httpClient = new Symfony\Component\HttpClient\CurlHttpClient([
				'timeout' => 5,
				'max_duration' => 15,
			]);
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

			return new S3StorageDriver($client, $setting->bucket, $setting->baseUrl);
		}

		return self::getLocalDriver();
	}

	private static function getLocalDriver(): StorageDriver {
		if (self::$localInstance === null) {
			self::$localInstance = new LocalStorageDriver(self::resolveDataRoot());
		}
		return self::$localInstance;
	}

	// Public so callers that need the setting itself (not just a driver
	// instance) -- e.g. a self-check that wants to re-verify and report on
	// the active S3 backend's public URL -- don't have to duplicate this query.
	public static function getActiveSetting(): ?StorageSetting {
		return self::loadActiveSetting();
	}

	private static function loadActiveSetting(): ?StorageSetting {
		require_once ROOT_DIR . '/sys/Storage/StorageSetting.php';
		$setting = new StorageSetting();
		$setting->isActive = 1;
		return $setting->find(true) ? $setting : null;
	}

	private static function loadSettingById(int $id): ?StorageSetting {
		require_once ROOT_DIR . '/sys/Storage/StorageSetting.php';
		$setting = new StorageSetting();
		$setting->id = $id;
		return $setting->find(true) ? $setting : null;
	}
}
