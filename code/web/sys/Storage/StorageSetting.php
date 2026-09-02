<?php

use AsyncAws\S3\S3Client;
use AsyncAws\S3\Input\ListObjectsV2Request;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\Input\DeleteObjectRequest;
use AsyncAws\Core\Exception\Http\HttpException;
use AsyncAws\Core\Exception\Http\NetworkException;

class StorageSetting extends DataObject {
	public $__table = 'storage_settings';
	public $id;
	public $name;
	public $driver;
	public $isActive;
	public $bucket;
	public $accessKeyId;
	public $accessKeySecret;
	public $region;
	public $endpoint;
	public $baseUrl;
	public $verifiedStatus;
	public $verifiedMessage;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A label to identify this storage configuration.',
				'maxLength' => 255,
				'required' => true,
			],
			'driver' => [
				'property' => 'driver',
				'type' => 'enum',
				'values' => [
					'local' => 'Local Storage',
					's3'    => 'S3-Compatible Storage (CDN Mode)',
				],
				'translateValues' => true,
				'isPublicFacing' => false,
				'isAdminFacing' => true,
				'label' => 'Storage Driver',
				'description' => 'The storage backend to use for uploaded files.',
				'default' => 'local',
				'required' => true,
				'onchange' => 'AspenDiscovery.StorageSettings.toggleS3Fields(this.value)',
			],
			's3FieldToggle' => [
				'property'               => 's3FieldToggle',
				'type'                   => 'label',
				'label'                  => '',
				'description'            => '',
				'hideInLists'            => true,
				'suppressNotSetForEmpty' => true,
				'doNotEscape'            => true,
				'hiddenByDefault'        => true,
			],
			'isActive' => [
				'property' => 'isActive',
				'type' => 'checkbox',
				'label' => 'Active',
				'description' => 'Use this configuration as the active storage backend. Only one configuration can be active at a time.',
				'default' => 0,
			],
			'effectiveDataRoot' => [
				'property'    => 'effectiveDataRoot',
				'type'        => 'label',
				'label'       => 'Storage Directory',
				'description' => 'The directory where uploaded files are stored on this server. To change this, update the data path in your server configuration.',
				'hideInLists' => true,
			],
			'bucket' => [
				'property'        => 'bucket',
				'type'            => 'text',
				'label'           => 'Bucket',
				'description'     => 'The S3 bucket name. The bucket must already exist on the provider; it is not created automatically.',
				'default'         => '',
				'required'        => true,
				'hiddenByDefault' => true,
			],
			'accessKeyId' => [
				'property'        => 'accessKeyId',
				'type'            => 'text',
				'label'           => 'Access Key ID',
				'description'     => 'The access key ID for S3 authentication.',
				'default'         => '',
				'required'        => true,
				'hiddenByDefault' => true,
			],
			'accessKeySecret' => [
				'property'        => 'accessKeySecret',
				'type'            => 'storedPassword',
				'label'           => 'Access Key Secret',
				'description'     => 'The secret access key for S3 authentication.',
				'default'         => '',
				'required'        => true,
				'hideInLists'     => true,
				'hiddenByDefault' => true,
			],
			'region' => [
				'property'        => 'region',
				'type'            => 'text',
				'label'           => 'Region',
				'description'     => 'The S3 region (e.g. us-east-1). Required for AWS S3; optional for S3-compatible providers.',
				'default'         => 'us-east-1',
				'required'        => false,
				'hiddenByDefault' => true,
			],
			'endpoint' => [
				'property'        => 'endpoint',
				'type'            => 'text',
				'label'           => 'Endpoint URL',
				'description'     => 'Custom endpoint URL for S3-compatible providers. Leave empty for AWS S3.',
				'default'         => '',
				'required'        => false,
				'hiddenByDefault' => true,
			],
			'baseUrl' => [
				'property'        => 'baseUrl',
				'type'            => 'text',
				'label'           => 'Public Base URL',
				'description'     => 'The public URL that serves the bucket directly (a CDN domain in front of it, or the bucket\'s own public endpoint).',
				'default'         => '',
				'required'        => true,
				'hiddenByDefault' => true,
			],
			'publicUrlStatus' => [
				'property'        => 'publicUrlStatus',
				'type'            => 'label',
				'label'           => 'Public URL Status',
				'description'     => 'Whether the Public Base URL has been confirmed to serve files anonymously. Re-checked every time this configuration is saved.',
				'hiddenByDefault' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = ''): int|bool {
		if (!$this->validateS3Credentials()) {
			return false;
		}
		$this->refreshPublicUrlStatus();
		$this->markVerificationFieldsChanged();
		if ($this->isActive) {
			global $aspen_db;
			$aspen_db->query('UPDATE storage_settings SET isActive = 0');
		}
		return parent::insert($context);
	}

	public function update(string $context = ''): int|bool {
		if (!$this->validateS3Credentials()) {
			return false;
		}
		$this->refreshPublicUrlStatus();
		$this->markVerificationFieldsChanged();
		if ($this->isActive) {
			global $aspen_db;
			$aspen_db->query('UPDATE storage_settings SET isActive = 0 WHERE id != ' . (int)$this->id);
		}
		return parent::update($context);
	}

	// These fields are computed, not submitted, so they never reach _changedFields
	// and update() would not persist them otherwise.
	private function markVerificationFieldsChanged(): void {
		$this->_changedFields[] = 'verifiedStatus';
		$this->_changedFields[] = 'verifiedMessage';
	}

	// Re-runs only the public URL probe and saves the result, for the CDN Storage
	// self-check in SearchAPI::getIndexStatus().
	public function checkAndPersistPublicUrlStatus(): void {
		$this->refreshPublicUrlStatus();
		$this->markVerificationFieldsChanged();
		parent::update();
	}

	// Tier 1: can we authenticate and see the bucket at all. Required for the
	// backend to function; blocks save on failure.
	private function validateS3Credentials(): bool {
		if ($this->driver !== 's3') {
			return true;
		}
		if (empty($this->bucket) || empty($this->accessKeyId) || empty($this->accessKeySecret) || empty($this->baseUrl)) {
			$this->setLastError('Bucket, Access Key ID, Access Key Secret, and Public Base URL (CDN) are all required for S3 storage.');
			return false;
		}
		try {
			$client = $this->buildTestClient();
			$result = $client->listObjectsV2(new ListObjectsV2Request([
				'Bucket'  => $this->bucket,
				'MaxKeys' => 1,
			]));
			$result->resolve();
			return true;
		} catch (\Exception $e) {
			$this->setLastError('Could not connect to S3 bucket: ' . $this->describeS3Exception($e));
			return false;
		}
	}

	// Tier 2: can an anonymous browser fetch from the Public Base URL. Can fail
	// (private bucket, wrong URL) even when tier 1 passes. Never blocks save;
	// only records the status shown in the admin badge.
	private function refreshPublicUrlStatus(): void {
		if ($this->driver !== 's3') {
			$this->verifiedStatus = 'unverified';
			// '' rather than null: update() skips null values, leaving the old message
			$this->verifiedMessage = '';
			return;
		}
		if (empty($this->baseUrl)) {
			$this->verifiedStatus = 'unverified';
			$this->verifiedMessage = 'No Public Base URL configured.';
			return;
		}

		$probeKey = 'uploads/.aspen-connection-test';
		try {
			$client = $this->buildTestClient();
			$client->putObject(new PutObjectRequest([
				'Bucket'      => $this->bucket,
				'Key'         => $probeKey,
				'Body'        => 'aspen-discovery connection test',
				'ContentType' => 'text/plain',
			]));
		} catch (\Exception $e) {
			$this->verifiedStatus = 'failed';
			$this->verifiedMessage = 'Could not write a test file to the bucket: ' . $this->describeS3Exception($e);
			return;
		}

		try {
			$probeUrl = rtrim($this->baseUrl, '/') . '/' . $probeKey;
			$publicHttpClient = new \Symfony\Component\HttpClient\CurlHttpClient(['timeout' => 5, 'max_duration' => 15]);
			$response = $publicHttpClient->request('GET', $probeUrl);
			$statusCode = $response->getStatusCode();
			if ($statusCode === 200) {
				$this->verifiedStatus = 'verified';
				$this->verifiedMessage = '';
			} elseif ($statusCode === 403) {
				$this->verifiedStatus = 'failed';
				$this->verifiedMessage = "Public Base URL returned 403 Access Denied. The bucket likely doesn't have a public-read policy set.";
			} elseif ($statusCode === 404) {
				$this->verifiedStatus = 'failed';
				$this->verifiedMessage = "Public Base URL returned 404 Not Found. Double check it points at the bucket named \"$this->bucket\".";
			} else {
				$this->verifiedStatus = 'failed';
				$this->verifiedMessage = "Public Base URL returned an unexpected HTTP $statusCode.";
			}
		} catch (\Exception $e) {
			$this->verifiedStatus = 'failed';
			$this->verifiedMessage = 'Could not reach the Public Base URL: ' . $e->getMessage();
		} finally {
			try {
				$client->deleteObject(new DeleteObjectRequest([
					'Bucket' => $this->bucket,
					'Key'    => $probeKey,
				]));
			} catch (\Exception $e) {
				// Best effort cleanup; a leftover probe file isn't worth failing the check over.
			}
		}
	}

	private function buildTestClient(): S3Client {
		$httpClient = new \Symfony\Component\HttpClient\CurlHttpClient(['timeout' => 5, 'max_duration' => 15]);
		return new S3Client(
			[
				'accessKeyId'      => $this->accessKeyId,
				'accessKeySecret'  => $this->accessKeySecret,
				'region'           => $this->region ?: 'us-east-1',
				'endpoint'         => $this->endpoint ?: null,
				'pathStyleEndpoint' => !empty($this->endpoint),
			],
			null,
			$httpClient
		);
	}

	// Maps AsyncAws exceptions to a human-readable reason using the standard
	// S3 API error-code contract (AWS error codes like AccessDenied,
	// NoSuchBucket, InvalidAccessKeyId), which any S3-compatible provider
	// implements the same way, not specific to AWS or MinIO.
	private function describeS3Exception(\Exception $e): string {
		if ($e instanceof HttpException) {
			$awsCode = $e->getAwsCode();
			switch ($awsCode) {
				case 'InvalidAccessKeyId':
					return 'The Access Key ID was rejected by the provider.';
				case 'SignatureDoesNotMatch':
					return 'The Access Key Secret is incorrect.';
				case 'NoSuchBucket':
					return "No bucket named \"$this->bucket\" exists on this provider.";
				case 'AccessDenied':
					return 'The credentials are valid but do not have permission to access this bucket.';
				default:
					return $awsCode ? "$awsCode: {$e->getAwsMessage()}" : $e->getMessage();
			}
		}
		if ($e instanceof NetworkException) {
			return "Could not reach the endpoint (" . ($this->endpoint ?: 'default AWS endpoint') . "). Check the Endpoint URL and network connectivity.";
		}
		return $e->getMessage();
	}

	public function updateStructureForEditingObject($structure): array {
		if ($this->driver !== 'local') {
			unset($structure['effectiveDataRoot']);
		}
		return $structure;
	}

	public function __get($name) {
		if ($name === 'effectiveDataRoot') {
			return StorageDriverFactory::resolveDataRoot();
		}
		if ($name === 'publicUrlStatus') {
			if ($this->driver !== 's3') {
				return '';
			}
			// inAttribute: the label field escapes its value, so skip translation mode's injected markup
			if ($this->verifiedStatus === 'verified') {
				return translate(['text' => 'Verified. Files are served directly from the Public Base URL.', 'isAdminFacing' => true, 'inAttribute' => true]);
			}
			if ($this->verifiedStatus === 'failed') {
				return translate(['text' => 'Not publicly readable. Images will not display until the bucket allows public reads.', 'isAdminFacing' => true, 'inAttribute' => true]) . ' ' . $this->verifiedMessage;
			}
			return $this->verifiedMessage ?: translate(['text' => 'Not yet verified. Save this configuration to check.', 'isAdminFacing' => true, 'inAttribute' => true]);
		}
		if ($name === 's3FieldToggle') {
			return '<script>
AspenDiscovery.StorageSettings = AspenDiscovery.StorageSettings || {};
AspenDiscovery.StorageSettings.toggleS3Fields = function(driver) {
	var s3Fields = ["bucket", "accessKeyId", "accessKeySecret", "region", "endpoint", "baseUrl", "publicUrlStatus"];
	var isS3 = driver === "s3";
	s3Fields.forEach(function(field) {
		$("#propertyRow" + field).toggle(isS3);
	});
};
$(document).ready(function() {
	AspenDiscovery.StorageSettings.toggleS3Fields($("#driverSelect").val());
});
</script>';
		}
		return parent::__get($name);
	}

	function getActiveAdminSection(): string {
		return 'system_admin';
	}

	function canActiveUserEdit(): bool {
		return UserAccount::userHasPermission('Administer Storage Settings');
	}

	public function getLinkedObjectStructure(): array {
		return [
			[
				'object' => 'ImageUpload',
				'class' => ROOT_DIR . '/sys/File/ImageUpload.php',
				'linkingProperty' => 'storageSettingId',
				'objectName' => 'Image Upload',
				'objectNamePlural' => 'Image Uploads',
			],
		];
	}
}
