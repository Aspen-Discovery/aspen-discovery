<?php

require_once ROOT_DIR . '/sys/Storage/LocalStorageDriver.php';

class StorageDriverFactory {
	private static ?StorageDriver $instance = null;

	public static function get(): StorageDriver {
		if (self::$instance === null) {
			self::$instance = self::create();
		}
		return self::$instance;
	}

	// Allows tests to inject a driver without touching config.
	public static function set(StorageDriver $driver): void {
		self::$instance = $driver;
	}

	public static function reset(): void {
		self::$instance = null;
	}

	public static function resolveDataRoot(): string {
		global $configArray, $serverName;
		return $configArray['Site']['dataPath'] ?? '/data/aspen-discovery/' . $serverName;
	}

	private static function create(): StorageDriver {
		// Phase 2 (cdn_storage_driver) extends this method to instantiate
		// S3StorageDriver when the active StorageSetting has driver='s3'.
		return new LocalStorageDriver(self::resolveDataRoot());
	}
}
