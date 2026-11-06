<?php

class AspenPWASetting extends DataObject {
	public $__table = 'aspen_pwa_settings';
	public $id;
	public $name;
	public $shortName;
	public $description;
	public $themeId;
	public $manifestID;
	public $startURL;
	public $slug;
	public $sha256CertFingerprint;
	public $icon;

	private $_libraries;

	static function getSettingsForCurrentLibrary(): AspenPWASetting | null {
		$settings = new AspenPWASetting();
		$library = Library::getActiveLibrary();
		$settings->id = $library->AspenPWASettingId;
		if($settings->find(true))
		{
			return $settings;
		}
		
		global $logger;
		$logger->log("No Settings found for active library or no active library found", Logger::LOG_ERROR);
		return null;
	}

	static function getObjectStructure($context = ''): array {

		require_once ROOT_DIR . '/sys/Theming/Theme.php';
		$theme = new Theme();
		$availableThemes = [];
		$theme->orderBy('themeName');
		$theme->find();
		while ($theme->fetch()) {
			$availableThemes[$theme->id] = $theme->themeName;
		}
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
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
				'note' => 'The name to use in the app manifest',
				'maxLength' => 50,
				'required' => true,
				'default' => 'Aspen Progressive Web Application(PWA)'
			],
			'shortName' => [
				'property' => 'shortName',
				'type' => 'text',
				'label' => 'Short Name',
				'note' => 'The short_name to use in the app manifest',
				'maxLength' => 50,
				'required' => true,
				'default' => 'Aspen PWA'
			],
			'description' => [
				'property' => 'description',
				'type' => 'text',
				'label' => 'Description',
				'note' => 'The description to use in the app manifest',
				'maxLength' => 200,
				'required' => true,
				'default' => 'A progressive web application for Aspen Discovery'
			],
			'themeId' => [
				'property' => 'themeId',
				'type' => 'enum',
				'label' => 'Theme',
				'values' => $availableThemes,
				'note' => 'The theme which should be used for the application',
			],
			'manifestID' => [
				'property' => 'manifestID',
				'type' => 'text',
				'label' => 'Manifest ID',
				'note' => 'A Unique identifier for your web application: https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Manifest/Reference/id',
				'maxLength' => 50,
				'required' => true,
			],
			'startURL' => [
				'property' => 'startURL',
				'type' => 'text',
				'label' => 'Start URL',
				'note' => 'URL for the application to start at.',
				'maxLength' => 50,
				'required' => true,
				'default' => '/',
			],
			'slug' => [
				'property' => 'slug',
				'type' => 'text',
				'label' => 'Slug',
				'note' => 'slug to identify the application',
				'maxLength' => 50,
				'required' => true,
			],
			'sha256CertFingerprint' => [
				'property' => 'sha256CertFingerprint',
				'type' => 'text',
				'label' => 'Sha 256 Cert Fingerprint',
				'note' => "Required only for Google Play Store publishing. If you haven't published yet, leave this blank. You can retrieve this value from the Google Play Console after your initial submission. (https://support.google.com/googleplay/android-developer/answer/16641489?hl=en)",
				'maxLength' => 200,
				'default' => " ",
				'required' => false,
			],
			'icon' => [
				'property' => 'icon',
				'type' => 'image',
				'label' => 'Icon (512x512 pixels)',
				'description' => 'The PNG icon to use for the application. If none provided, Aspen will use the Aspen LiDA & PWA logo from the theme if it exists.',
				'validTypes' => ['image/png'],
				'required' => false,
				'thumbWidth' => 180,
				'maxWidth' => 512,
				'maxHeight' => 512,
				'hideInLists' => true,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'note' => 'Define libraries that use these settings.',
				'values' => $libraryList,
				'hideInLists' => true,
			]
		];

		return $structure;
	}

	public function __get($name) {
		if ($name == 'libraries') {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->AspenPWASettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == 'libraries') {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return true;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->AspenPWASettingId != $this->id) {
						$library->AspenPWASettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->AspenPWASettingId == $this->id) {
						$library->AspenPWASettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}
?>