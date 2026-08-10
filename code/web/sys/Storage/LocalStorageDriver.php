<?php

require_once ROOT_DIR . '/sys/Storage/StorageDriver.php';

class LocalStorageDriver implements StorageDriver {
	private string $dataRoot;

	public function __construct(string $dataRoot) {
		$this->dataRoot = rtrim($dataRoot, '/');
	}

	private function fullPath(string $key): string {
		return $this->dataRoot . '/' . ltrim($key, '/');
	}

	public function url(string $key, array $transforms = []): string {
		// Files under dataRoot are not served directly by Apache; callers
		// must proxy the bytes themselves via read().
		return '';
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
			mkdir($dir, 0755, true);
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
}
