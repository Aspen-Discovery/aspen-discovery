<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Plugin extends DataObject {
	public $__table = 'plugin';
	public $id;
	public $name;
	public $slug;
	public $version;
	public $description;
	public $author;
	public $status; // 0 = disabled, 1 = enabled
	public $updateDate;
	public $configData; // JSON string for plugin configuration
	public $modifiedDate; // When plugin was last modified (from metadata)
	public $minAspenVersion; // Minimum required Aspen version
	public $maxAspenVersion; // Maximum supported Aspen version

	static function getObjectStructure($context = ''): array {
		return [
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
			'status' => [
				'property' => 'status',
				'type' => 'label',
				'label' => 'Status',
				'description' => 'Whether the plugin is enabled or disabled',
				'labelFunction' => 'getStatusLabel',
			],
			'modifiedDate' => [
				'property' => 'modifiedDate',
				'type' => 'label',
				'label' => 'Modified Date',
				'description' => 'When the plugin was last modified',
				'labelFunction' => 'getModifiedDateLabel',
			],
			'minAspenVersion' => [
				'property' => 'minAspenVersion',
				'type' => 'label',
				'label' => 'Min Aspen Version',
				'description' => 'Minimum required Aspen Discovery version',
				'labelFunction' => 'getMinAspenVersionLabel',
			],
			'maxAspenVersion' => [
				'property' => 'maxAspenVersion',
				'type' => 'label',
				'label' => 'Max Aspen Version',
				'description' => 'Maximum supported Aspen Discovery version',
				'labelFunction' => 'getMaxAspenVersionLabel',
			],
		];
	}

	public function getSerializedFieldNames(): array {
		return []; // We use JSON encoding/decoding for our data fields
	}

	public function getUniquenessFields(): array {
		return ['slug'];
	}

	public function insert($context = '') {
		$this->updateDate = time();
		return parent::insert($context);
	}

	public function update($context = '') {
		$this->updateDate = time();
		return parent::update($context);
	}

	public function isEnabled(): bool {
		return $this->status == 1;
	}

	public function enable(): bool {
		$this->status = 1;
		$this->updateDate = time();
		return $this->update();
	}

	public function disable(): bool {
		$this->status = 0;
		$this->updateDate = time();
		return $this->update();
	}

	public function getConfigArray(): array {
		if (empty($this->configData)) {
			return [];
		}
		$config = json_decode($this->configData, true);
		return is_array($config) ? $config : [];
	}

	public function setConfigArray(array $config): void {
		$this->configData = json_encode($config);
	}



	/**
	 * Get the plugin directory path
	 */
	public function getPluginDirectory(): string {
		return $this->getPluginDataPath() . "/{$this->slug}";
	}
	
	/**
	 * Get the base plugins data directory path for this instance
	 */
	public static function getPluginDataPath(): string {
		global $serverName;
		return "/data/aspen-discovery/$serverName/plugins";
	}

	/**
	 * Check if plugin directory exists
	 */
	public function pluginDirectoryExists(): bool {
		return is_dir($this->getPluginDirectory());
	}

	/**
	 * Get plugin main class file path
	 */
	public function getPluginClassFile(): string {
		return $this->getPluginDirectory() . "/{$this->slug}.php";
	}

	/**
	 * Check if plugin class file exists
	 */
	public function pluginClassFileExists(): bool {
		return file_exists($this->getPluginClassFile());
	}

	/**
	 * Get status label for display
	 */
	public function getStatusLabel(): string {
		if ($this->status == 1) {
			return '<span class="label label-success">Enabled</span>';
		} else {
			return '<span class="label label-danger">Disabled</span>';
		}
	}

	/**
	 * Get modified date label for display
	 */
	public function getModifiedDateLabel(): string {
		return $this->modifiedDate ?: 'Unknown';
	}

	/**
	 * Get minimum Aspen version label for display
	 */
	public function getMinAspenVersionLabel(): string {
		return $this->minAspenVersion ?: 'Not specified';
	}

	/**
	 * Get maximum Aspen version label for display
	 */
	public function getMaxAspenVersionLabel(): string {
		return $this->maxAspenVersion ?: 'Not specified';
	}
} 