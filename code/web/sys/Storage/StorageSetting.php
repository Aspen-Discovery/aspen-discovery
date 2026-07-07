<?php

use AsyncAws\S3\S3Client;
use AsyncAws\S3\Input\HeadBucketRequest;

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

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
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
					's3'    => 'S3-Compatible Storage',
				],
				'label' => 'Storage Driver',
				'description' => 'The storage backend to use for uploaded files.',
				'default' => 'local',
				'required' => true,
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
				'property' => 'bucket',
				'type' => 'text',
				'label' => 'Bucket',
				'description' => 'The S3 bucket name.',
				'default' => '',
				'required' => true,
			],
			'accessKeyId' => [
				'property' => 'accessKeyId',
				'type' => 'text',
				'label' => 'Access Key ID',
				'description' => 'The access key ID for S3 authentication.',
				'default' => '',
				'required' => true,
			],
			'accessKeySecret' => [
				'property' => 'accessKeySecret',
				'type' => 'storedPassword',
				'label' => 'Access Key Secret',
				'description' => 'The secret access key for S3 authentication.',
				'default' => '',
				'required' => true,
				'hideInLists' => true,
			],
			'region' => [
				'property' => 'region',
				'type' => 'text',
				'label' => 'Region',
				'description' => 'The S3 region (e.g. us-east-1). Required for AWS S3; optional for S3-compatible providers.',
				'default' => 'us-east-1',
				'required' => false,
			],
			'endpoint' => [
				'property' => 'endpoint',
				'type' => 'text',
				'label' => 'Endpoint URL',
				'description' => 'Custom endpoint URL for S3-compatible providers (e.g. Cloudflare R2, MinIO). Leave empty for AWS S3.',
				'default' => '',
				'required' => false,
			],
			'baseUrl' => [
				'property' => 'baseUrl',
				'type' => 'text',
				'label' => 'Public Base URL',
				'description' => 'The public URL prefix used to serve files (e.g. https://cdn.example.com). Required when using S3.',
				'default' => '',
				'required' => false,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function insert(string $context = ''): int|bool {
		if (!$this->validateS3Credentials()) {
			return false;
		}
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
		if ($this->isActive) {
			global $aspen_db;
			$aspen_db->query('UPDATE storage_settings SET isActive = 0 WHERE id != ' . (int)$this->id);
		}
		return parent::update($context);
	}

	private function validateS3Credentials(): bool {
		if ($this->driver !== 's3') {
			return true;
		}
		if (empty($this->bucket) || empty($this->accessKeyId) || empty($this->accessKeySecret)) {
			$this->validationError = 'Bucket, Access Key ID, and Access Key Secret are required for S3 storage.';
			return false;
		}
		try {
			$client = new S3Client([
				'accessKeyId'     => $this->accessKeyId,
				'accessKeySecret' => $this->accessKeySecret,
				'region'          => $this->region ?: 'us-east-1',
				'endpoint'        => $this->endpoint ?: null,
			]);
			$result = $client->headBucket(new HeadBucketRequest(['Bucket' => $this->bucket]));
			$result->resolve();
			return true;
		} catch (\Exception $e) {
			$this->validationError = 'Could not connect to S3 bucket: ' . $e->getMessage();
			return false;
		}
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
