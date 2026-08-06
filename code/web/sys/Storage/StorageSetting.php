<?php

class StorageSetting extends DataObject {
	public $__table = 'storage_settings';
	public $id;
	public $name;
	public $driver;
	public $isActive;

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
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function update(string $context = ''): int|bool {
		if ($this->isActive) {
			global $aspen_db;
			$aspen_db->query('UPDATE storage_settings SET isActive = 0 WHERE id != ' . (int)$this->id);
		}
		return parent::update($context);
	}

	public function insert(string $context = ''): int|bool {
		// On insert, deactivate all others if this one is set as active.
		if ($this->isActive) {
			global $aspen_db;
			$aspen_db->query('UPDATE storage_settings SET isActive = 0');
		}
		return parent::insert($context);
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
