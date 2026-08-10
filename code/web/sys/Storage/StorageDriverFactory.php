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

	private static function create(): StorageDriver {
		return self::getLocalDriver();
	}

	private static function createFromId(int $id): StorageDriver {
		return self::getLocalDriver();
	}

	private static function getLocalDriver(): StorageDriver {
		if (self::$localInstance === null) {
			self::$localInstance = new LocalStorageDriver(self::resolveDataRoot());
		}
		return self::$localInstance;
	}
}
