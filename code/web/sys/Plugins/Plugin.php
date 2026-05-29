<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Plugin extends DataObject {
	public $__table = 'plugin';
	public $id;
	public $name;
	public $version;
	public $description;
	public $author;
	public $enabled; // 0 = disabled, 1 = enabled
	public $updateDate;
	public $minAspenVersion; // Minimum required Aspen version

	static $_objectStructure = [];

	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context]) && self::$_objectStructure[$context] !== null) {
			return self::$_objectStructure[$context];
		}
		$structure = [
			'name' => [
				'property' => 'name',
				'type' => 'label',
				'label' => 'Plugin Name',
				'description' => 'The display name of the plugin',
			],
			'version' => [
				'property' => 'version',
				'type' => 'label',
				'label' => 'Version',
				'description' => 'Current version of the plugin',
			],
			'description' => [
				'property' => 'description',
				'type' => 'label',
				'label' => 'Description',
				'description' => 'Description of what the plugin does',
			],
			'author' => [
				'property' => 'author',
				'type' => 'label',
				'label' => 'Author',
				'description' => 'Plugin author/developer',
			],
			'enabled' => [
				'property' => 'enabled',
				'type' => 'checkbox',
				'label' => 'Enabled?',
				'description' => 'Whether the plugin is enabled or disabled',
			],
			'updateDate' => [
				'property' => 'updateDate',
				'type' => 'timestamp',
				'label' => 'Update Date',
				'description' => 'When the plugin was last updated',
				'readonly' => true,
			],
			'minAspenVersion' => [
				'property' => 'minAspenVersion',
				'type' => 'label',
				'label' => 'Min Aspen Version',
				'description' => 'Minimum required Aspen Discovery version',
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function getUniquenessFields(): array {
		return ['name'];
	}

	public function insert(string $context = ''): int|bool {
		$this->updateDate = time();
		return parent::insert($context);
	}

	public function update(string $context = ''): int|bool {
		$this->updateDate = time();
		return parent::update($context);
	}

	/**
	 * Plugins should not be deleted through the standard delete action.
	 * They must be uninstalled via the uninstall method instead.
	 */
	public function canActiveUserDelete(): bool {
		return false;
	}
}