<?php

require_once ROOT_DIR . '/sys/Storage/StorageDriver.php';

class LocalStorageDriver implements StorageDriver {
	private string $dataRoot;
	private string $publicRoot;

	// Served directly by Apache from the docroot on every deployment type; see StorageDriverFactory::resolvePublicRoot().
	private const PUBLIC_KEY_PREFIXES = ['files/', 'images/', 'fonts/'];

	public function __construct(string $dataRoot, string $publicRoot) {
		$this->dataRoot = rtrim($dataRoot, '/');
		$this->publicRoot = rtrim($publicRoot, '/');
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
