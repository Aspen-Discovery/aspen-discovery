<?php

require_once ROOT_DIR . '/sys/Storage/StorageDriver.php';

use AsyncAws\S3\S3Client;
use AsyncAws\S3\Input\GetObjectRequest;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\Input\DeleteObjectRequest;
use AsyncAws\S3\Input\HeadObjectRequest;

class S3StorageDriver implements StorageDriver {
	private S3Client $client;
	private string $bucket;
	private string $baseUrl;

	public function __construct(S3Client $client, string $bucket, string $baseUrl) {
		$this->client  = $client;
		$this->bucket  = $bucket;
		$this->baseUrl = rtrim($baseUrl, '/');
	}

	public function url(string $key, array $transforms = []): string {
		if (empty($this->baseUrl)) {
			// No public base URL configured; caller must proxy via read().
			return '';
		}
		$url = $this->baseUrl . '/' . ltrim($key, '/');
		if (!empty($transforms)) {
			$url .= '?' . http_build_query($transforms);
		}
		return $url;
	}

	public function read(string $key): string|false {
		try {
			$result = $this->client->getObject(new GetObjectRequest([
				'Bucket' => $this->bucket,
				'Key'    => ltrim($key, '/'),
			]));
			return $result->getBody()->getContentAsString();
		} catch (\Exception $e) {
			global $logger;
			$logger->log("S3StorageDriver: failed to read $key from bucket $this->bucket: " . $e->getMessage(), Logger::LOG_ERROR);
			return false;
		}
	}

	public function write(string $key, string $tmpPath, string $mimeType = ''): bool {
		$handle = fopen($tmpPath, 'rb');
		if ($handle === false) {
			global $logger;
			$logger->log("S3StorageDriver: could not open local file $tmpPath for upload to $key", Logger::LOG_ERROR);
			return false;
		}
		try {
			$this->client->putObject(new PutObjectRequest([
				'Bucket'      => $this->bucket,
				'Key'         => ltrim($key, '/'),
				'Body'        => $handle,
				'ContentType' => $mimeType ?: 'application/octet-stream',
			]));
			return true;
		} catch (\Exception $e) {
			global $logger;
			$logger->log("S3StorageDriver: failed to write $key to bucket $this->bucket: " . $e->getMessage(), Logger::LOG_ERROR);
			return false;
		} finally {
			fclose($handle);
		}
	}

	public function delete(string $key): bool {
		try {
			$this->client->deleteObject(new DeleteObjectRequest([
				'Bucket' => $this->bucket,
				'Key'    => ltrim($key, '/'),
			]));
			return true;
		} catch (\Exception $e) {
			global $logger;
			$logger->log("S3StorageDriver: failed to delete $key from bucket $this->bucket: " . $e->getMessage(), Logger::LOG_ERROR);
			return false;
		}
	}

	public function exists(string $key): bool {
		try {
			$result = $this->client->headObject(new HeadObjectRequest([
				'Bucket' => $this->bucket,
				'Key'    => ltrim($key, '/'),
			]));
			$result->resolve();
			return true;
		} catch (\Exception $e) {
			// A missing key is routine (e.g. existence checks before overwrite);
			// only log at debug level to avoid flooding the error log.
			global $logger;
			$logger->log("S3StorageDriver: $key not found in bucket $this->bucket: " . $e->getMessage(), Logger::LOG_DEBUG);
			return false;
		}
	}
}
