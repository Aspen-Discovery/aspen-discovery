<?php
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectScope.php';
require_once ROOT_DIR . '/sys/PalaceProject/PalaceProjectCollection.php';

class PalaceProjectSetting extends DataObject {
	public $__table = 'palace_project_settings';    // table name
	public $id;
	public $apiUrl;
	public $libraryId;
	public $regroupAllRecords;
	public $runFullUpdate;
	public $lastUpdateOfChangedRecords;
	public $lastUpdateOfAllRecords;

	private $_scopes;
	private $_collections;
	protected $_instructionsForUsage;

	public static function getObjectStructure($context = ''): array {
		$palaceProjectScopeStructure = PalaceProjectScope::getObjectStructure($context);
		unset($palaceProjectScopeStructure['settingId']);

		$palaceProjectCollectionStructure = PalaceProjectCollection::getObjectStructure($context);
		unset($palaceProjectCollectionStructure['settingId']);

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'apiUrl' => [
				'property' => 'apiUrl',
				'type' => 'url',
				'label' => 'url',
				'description' => 'The URL to the API',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'text',
				'label' => 'Library ID / Short name',
				'description' => 'The Library Identifier or Short name ',
			],
			'regroupAllRecords' => [
				'property' => 'regroupAllRecords',
				'type' => 'checkbox',
				'label' => 'Regroup all Records',
				'description' => 'Whether or not all existing records should be regrouped',
				'default' => 0,
			],
			'runFullUpdate' => [
				'property' => 'runFullUpdate',
				'type' => 'checkbox',
				'label' => 'Run Full Update',
				'description' => 'Whether or not a full update of all records should be done on the next pass of indexing',
				'default' => 0,
			],
			'lastUpdateOfChangedRecords' => [
				'property' => 'lastUpdateOfChangedRecords',
				'type' => 'timestamp',
				'label' => 'Last Update of Changed Records',
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'lastUpdateOfAllRecords' => [
				'property' => 'lastUpdateOfAllRecords',
				'type' => 'timestamp',
				'label' => 'Last Update of All Records',
				'description' => 'The timestamp when just changes were loaded',
				'default' => 0,
			],
			'instructionsForUsage' => [
				'property' => 'instructionsForUsage',
				'type' => 'translatableTextBlock',
				'label' => 'Instructions For Using Palace Project',
				'description' => 'Provide instructions for how to use the Palace Project to read titles',
				'defaultTextFile' => 'PalaceProjectSetting_instructionsForUsage.MD',
				'hideInLists' => true,
			],

			'scopes' => [
				'property' => 'scopes',
				'type' => 'oneToMany',
				'label' => 'Scopes',
				'description' => 'Define scopes for the settings',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'PalaceProjectScope',
				'structure' => $palaceProjectScopeStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => true,
				'canAddNew' => true,
				'canDelete' => true,
				'additionalOneToManyActions' => [],
			],
			'collections' => [
				'property' => 'collections',
				'type' => 'oneToMany',
				'label' => 'Collections',
				'description' => 'A list of collections for the settings',
				'keyThis' => 'id',
				'keyOther' => 'settingId',
				'subObjectType' => 'PalaceProjectCollection',
				'structure' => $palaceProjectCollectionStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => true,
				'canEdit' => false,
				'canAddNew' => false,
				'canDelete' => false,
				'additionalOneToManyActions' => [],
			]
		];
	}

	public function __toString() {
		return "$this->id ($this->apiUrl)";
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveScopes();
			$this->saveCollections();
			$this->saveTextBlockTranslations('instructionsForUsage');
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			if (empty($this->_scopes)) {
				$this->_scopes = [];
				$allScope = new PalaceProjectScope();
				$allScope->settingId = $this->id;
				$allScope->name = "All Records";

				$this->_scopes[] = $allScope;
			}
			$this->saveScopes();
			$this->saveTextBlockTranslations('instructionsForUsage');
		}
		return $ret;
	}

	public function saveScopes() {
		if (isset ($this->_scopes) && is_array($this->_scopes)) {
			$this->saveOneToManyOptions($this->_scopes, 'settingId');
			unset($this->_scopes);
		}
	}

	public function saveCollections() {
		if (isset ($this->_collections) && is_array($this->_collections)) {
			$this->saveOneToManyOptions($this->_collections, 'settingId');
			unset($this->_collections);
		}
	}

	public function __get($name) {
		if ($name == "scopes") {
			if (!isset($this->_scopes) && $this->id) {
				$this->_scopes = [];
				$scope = new PalaceProjectScope();
				$scope->settingId = $this->id;
				$scope->find();
				while ($scope->fetch()) {
					$this->_scopes[$scope->id] = clone($scope);
				}
			}
			return $this->_scopes;
		} elseif ($name == "collections") {
			if (!isset($this->_collections) && $this->id) {
				$this->_collections = [];
				$collection = new PalaceProjectCollection();
				$collection->settingId = $this->id;
				$collection->find();
				while ($collection->fetch()) {
					$this->_collections[$collection->id] = clone($collection);
				}
			}
			return $this->_collections;
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "scopes") {
			$this->_scopes = $value;
		} elseif ($name == "collections") {
			$this->_collections = $value;
		} else {
			parent::__set($name, $value);
		}
	}
}