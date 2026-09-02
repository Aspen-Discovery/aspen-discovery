<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/Indexing/TranslationMapValue.php';

class TranslationMap extends DataObject {
	public $__table = 'translation_maps';    // table name
	public $__displayNameColumn = 'name';
	public $id;
	public $indexingProfileId;
	public $name;
	public /** @noinspection PhpUnused */
		$usesRegularExpressions;
	/** @var TranslationMapValue[] */
	protected $_translationMapValues;

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
		}

		$indexingProfiles = [];
		require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
		$indexingProfile = new IndexingProfile();
		$indexingProfile->orderBy('name');
		$indexingProfile->find();
		while ($indexingProfile->fetch()) {
			$indexingProfiles[$indexingProfile->id] = $indexingProfile->name;
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'indexingProfileId' => [
				'property' => 'indexingProfileId',
				'type' => 'enum',
				'values' => $indexingProfiles,
				'label' => 'Indexing Profile Id',
				'description' => 'The Indexing Profile this map is associated with',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the translation map',
				'maxLength' => '50',
				'required' => true,
			],
			'usesRegularExpressions' => [
				'property' => 'usesRegularExpressions',
				'type' => 'checkbox',
				'label' => 'Use Regular Expressions',
				'description' => 'When on, values will be treated as regular expressions',
				'hideInLists' => false,
				'default' => false,
				'forcesReindex' => true,
			],

			'translationMapValues' => [
				'property' => 'translationMapValues',
				'type' => 'oneToMany',
				'label' => 'Values',
				'description' => 'The values for the translation map.',
				'keyThis' => 'id',
				'keyOther' => 'translationMapId',
				'subObjectType' => 'TranslationMapValue',
				'structure' => TranslationMapValue::getObjectStructure($context),
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'forcesReindex' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "translationMapValues") {
			return $this->getTranslationMapValues();
		}
		return parent::__get($name);
	}

	public function getTranslationMapValues(): ?array {
		if (!isset($this->_translationMapValues)) {
			//Get the list of translation maps
			if ($this->id) {
				$this->_translationMapValues = [];
				$value = new TranslationMapValue();
				$value->translationMapId = $this->id;
				$value->orderBy('value ASC');
				$value->find();
				while ($value->fetch()) {
					$this->_translationMapValues[$value->id] = clone($value);
				}
			}
		}
		return $this->_translationMapValues;
	}

	public function __set($name, $value) {
		if ($name == "translationMapValues") {
			$this->_translationMapValues = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Override the update functionality to save the associated translation maps
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update(string $context = '') : int|bool {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveMapValues();
		}
		return $ret;
	}

	/**
	 * Override the update functionality to save the associated translation maps
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveMapValues();
		}
		return $ret;
	}

	public function saveMapValues() : void {
		if (isset ($this->_translationMapValues)) {
			foreach ($this->_translationMapValues as $value) {
				if ($value->_deleteOnSave) {
					$value->delete();
				} else {
					if (isset($value->id) && is_numeric($value->id)) {
						$value->update();
					} else {
						$value->translationMapId = $this->id;
						$value->insert();
					}
				}
			}
			//Clear the translation maps so they are reloaded the next time
			unset($this->_translationMapValues);
		}
	}

	/** @noinspection PhpUnusedParameterInspection */
	public function getEditLink(string $context): string {
		return '/ILS/TranslationMaps?objectAction=edit&id=' . $this->id;
	}

	public function translate($untranslatedValue) {
		$mapValues = $this->getTranslationMapValues();
		foreach ($mapValues as $mapValue) {
			if ($mapValue->value == $untranslatedValue) {
				return $mapValue->translation;
			}
		}
		return $untranslatedValue;
	}

}