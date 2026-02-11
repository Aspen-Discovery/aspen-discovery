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
	public $installDate;
	public $updateDate;
	public $configData; // JSON string for plugin configuration
	public $hasJsInjection;
	public $jsFiles; // JSON array of JS files to inject
	public $cssFiles; // JSON array of CSS files to inject
	public $hookPoints; // JSON array of hook points this plugin implements

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Plugin Name',
				'description' => 'The display name of the plugin',
				'maxLength' => 100,
				'required' => true,
			],
			'slug' => [
				'property' => 'slug',
				'type' => 'text',
				'label' => 'Plugin Slug',
				'description' => 'Unique identifier for the plugin (no spaces, alphanumeric)',
				'maxLength' => 50,
				'required' => true,
			],
			'version' => [
				'property' => 'version',
				'type' => 'text',
				'label' => 'Version',
				'description' => 'Current version of the plugin',
				'maxLength' => 20,
				'required' => true,
			],
			'description' => [
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'description' => 'Description of what the plugin does',
				'rows' => 3,
			],
			'author' => [
				'property' => 'author',
				'type' => 'text',
				'label' => 'Author',
				'description' => 'Plugin author/developer',
				'maxLength' => 100,
			],
			'status' => [
				'property' => 'status',
				'type' => 'enum',
				'label' => 'Status',
				'description' => 'Whether the plugin is enabled or disabled',
				'values' => [
					0 => 'Disabled',
					1 => 'Enabled',
				],
				'default' => 0,
			],
			'installDate' => [
				'property' => 'installDate',
				'type' => 'timestamp',
				'label' => 'Install Date',
				'description' => 'When the plugin was installed',
				'serverValidation' => 'validateInstallDate',
			],
			'updateDate' => [
				'property' => 'updateDate',
				'type' => 'timestamp',
				'label' => 'Update Date',
				'description' => 'When the plugin was last updated',
				'serverValidation' => 'validateUpdateDate',
			],
			'configData' => [
				'property' => 'configData',
				'type' => 'textarea',
				'label' => 'Configuration Data',
				'description' => 'JSON configuration data for the plugin',
				'rows' => 5,
			],
			'hasJsInjection' => [
				'property' => 'hasJsInjection',
				'type' => 'checkbox',
				'label' => 'Has JS Injection',
				'description' => 'Whether this plugin injects JavaScript',
				'default' => 0,
			],
			'jsFiles' => [
				'property' => 'jsFiles',
				'type' => 'textarea',
				'label' => 'JavaScript Files',
				'description' => 'JSON array of JavaScript files to inject',
				'rows' => 3,
			],
			'cssFiles' => [
				'property' => 'cssFiles',
				'type' => 'textarea',
				'label' => 'CSS Files',
				'description' => 'JSON array of CSS files to inject',
				'rows' => 3,
			],
			'hookPoints' => [
				'property' => 'hookPoints',
				'type' => 'textarea',
				'label' => 'Hook Points',
				'description' => 'JSON array of hook points this plugin implements',
				'rows' => 3,
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
		$this->installDate = time();
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

	public function getJsFilesArray(): array {
		if (empty($this->jsFiles)) {
			return [];
		}
		$files = json_decode($this->jsFiles, true);
		return is_array($files) ? $files : [];
	}

	public function setJsFilesArray(array $files): void {
		$this->jsFiles = json_encode($files);
	}

	public function getCssFilesArray(): array {
		if (empty($this->cssFiles)) {
			return [];
		}
		$files = json_decode($this->cssFiles, true);
		return is_array($files) ? $files : [];
	}

	public function setCssFilesArray(array $files): void {
		$this->cssFiles = json_encode($files);
	}

	public function getHookPointsArray(): array {
		if (empty($this->hookPoints)) {
			return [];
		}
		$hooks = json_decode($this->hookPoints, true);
		return is_array($hooks) ? $hooks : [];
	}

	public function setHookPointsArray(array $hooks): void {
		$this->hookPoints = json_encode($hooks);
	}

	/**
	 * Get the plugin directory path
	 */
	public function getPluginDirectory(): string {
		global $configArray;
		$local = $configArray['Site']['local'];
		return "$local/plugins/{$this->slug}";
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
} 