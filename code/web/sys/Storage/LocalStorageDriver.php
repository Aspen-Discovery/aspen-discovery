<?php

require_once ROOT_DIR . '/sys/Storage/StorageDriver.php';

class LocalStorageDriver implements StorageDriver {

	// Served directly by Apache from the docroot on every deployment type; see StorageDriverFactory::resolvePublicRoot().
	private const PUBLIC_KEY_PREFIXES = ['files/', 'images/', 'fonts/'];
	private const DIRECTORY_MODE = 0775;

	private string $dataRoot;
	private string $publicRoot;
	private ?string $groupOwner;

	public function __construct(string $dataRoot, string $publicRoot, ?string $groupOwner = null) {
		$this->dataRoot = rtrim($dataRoot, '/');
		$this->publicRoot = rtrim($publicRoot, '/');
		$this->groupOwner = $groupOwner;
	}

	private function isPublicKey(string $key): bool {
		foreach (self::PUBLIC_KEY_PREFIXES as $prefix) {
			if (str_starts_with($key, $prefix)) {
				return true;
			}
		}
		return false;
	}

	private function fullPath(string $key): string {
		$root = $this->isPublicKey($key) ? $this->publicRoot : $this->dataRoot;
		return $root . '/' . ltrim($key, '/');
	}

	public function url(string $key, array $transforms = []): string {
		return $this->isPublicKey($key) ? '/' . $key : '';
	}

	public function read(string $key): string|false {
		$path = $this->fullPath($key);
		if (!file_exists($path)) {
			return false;
		}
		return file_get_contents($path);
	}

	public function readStream(string $key) {
		$path = $this->fullPath($key);
		if (!file_exists($path)) {
			return false;
		}
		return fopen($path, 'rb');
	}

	public function write(string $key, string $tmpPath, string $mimeType = ''): bool {
		$dest = $this->fullPath($key);
		$dir = dirname($dest);
		if (!file_exists($dir)) {
			global $logger;
			// Collect every missing level so intermediate directories get the same group and mode
			$newDirs = [];
			for ($missing = $dir; !file_exists($missing); $missing = dirname($missing)) {
				$newDirs[] = $missing;
			}
			mkdir($dir, self::DIRECTORY_MODE, true);
			// A failure here doesn't block the write, but leaves the directory unusable by the other user
			foreach ($newDirs as $newDir) {
				if ($this->groupOwner !== null && !@chgrp($newDir, $this->groupOwner)) {
					$logger->log("Could not set group {$this->groupOwner} on $newDir", Logger::LOG_ERROR);
				}
				if (!@chmod($newDir, self::DIRECTORY_MODE)) {
					$logger->log("Could not set mode on $newDir", Logger::LOG_ERROR);
				}
			}
		}
		return copy($tmpPath, $dest);
	}

	public function delete(string $key): bool {
		$path = $this->fullPath($key);
		if (!file_exists($path)) {
			return false;
		}
		return unlink($path);
	}

	public function exists(string $key): bool {
		return file_exists($this->fullPath($key));
	}

	public function size(string $key): int|false {
		$path = $this->fullPath($key);
		if (!file_exists($path)) {
			return false;
		}
		return filesize($path);
	}
}
