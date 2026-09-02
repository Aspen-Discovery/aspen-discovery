<?php /** @noinspection PhpMissingFieldTypeInspection */


class SyndeticsSetting extends DataObject {
	public $__table = 'syndetics_settings';    // table name
	public $id;
	public $name;
	public $syndeticsUnbound;
	public $syndeticsKey;
	public $unboundAccountNumber;
	public $unboundInstanceNumber;
	public $hasSummary;
	public $hasAvSummary;
	public $hasAvProfile;
	public $hasToc;
	public $hasExcerpt;
	public $hasFictionProfile;
	public $hasAuthorNotes;
	public $hasVideoClip;

	private $_libraries;

	public function getNumericColumnNames(): array {
		return [
			'hasSummary',
			'hasAvSummary',
			'hasAvProfile',
			'hasToc',
			'hasExcerpt',
			'hasFictionProfile',
			'hasAuthorNotes',
			'hasVideoClip',
		];
	}

	static $_objectStructure = [];
	static function getObjectStructure(string $context = ''): array {
		if (isset(self::$_objectStructure[$context])) {
			return self::$_objectStructure[$context];
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
				'description' => 'A Name for the Syndetics Subscription for internal use',
				'maxlength' => 255,
				'required' => true,
			],
			'syndeticsKey' => [
				'property' => 'syndeticsKey',
				'type' => 'text',
				'label' => 'Syndetics Key (Client Code)',
				'description' => 'The key/client code for the subscription and required for providing cover images.',
			],
			'syndeticsUnbound' => [
				'property' => 'syndeticsUnbound',
				'type' => 'checkbox',
				'label' => 'Syndetics Unbound',
				'description' => 'Check this option if this is a Syndetics Unbound Subscription',
				'default' => 0,
				'onchange' => "return AspenDiscovery.Admin.updateSyndeticsFields();"
			],
			'unboundAccountNumber' => [
				'property' => 'unboundAccountNumber',
				'type' => 'integer',
				'label' => 'Unbound Account Number',
				'description' => 'Enter the account number for syndetics unbound',
				'default' => 0,
			],
			'unboundInstanceNumber' => [
				'property' => 'unboundInstanceNumber',
				'type' => 'integer',
				'label' => 'Unbound Instance Number',
				'description' => 'Enter the instance number for syndetics unbound (may be left at 0 to ignore)',
				'default' => 0,
			],
			'hasSummary' => [
				'property' => 'hasSummary',
				'type' => 'checkbox',
				'label' => 'Has Summary',
				'description' => 'Whether or not the summary is available in the subscription',
				'default' => 1,
			],
			'hasAvSummary' => [
				'property' => 'hasAvSummary',
				'type' => 'checkbox',
				'label' => 'Has Audio Visual Summary',
				'description' => 'Whether or not the summary is available in the subscription',
			],
			'hasAvProfile' => [
				'property' => 'hasAvProfile',
				'type' => 'checkbox',
				'label' => 'Has Audio Visual Profile',
				'description' => 'Whether or not the summary is available in the subscription',
			],
			'hasToc' => [
				'property' => 'hasToc',
				'type' => 'checkbox',
				'label' => 'Has Table of Contents',
				'description' => 'Whether or not the table of contents is available in the subscription',
				'default' => 1,
			],
			'hasExcerpt' => [
				'property' => 'hasExcerpt',
				'type' => 'checkbox',
				'label' => 'Has Excerpt',
				'description' => 'Whether or not the excerpt is available in the subscription',
				'default' => 1,
			],
			'hasFictionProfile' => [
				'property' => 'hasFictionProfile',
				'type' => 'checkbox',
				'label' => 'Has Fiction Profile',
				'description' => 'Whether or not the excerpt is available in the subscription',
			],
			'hasAuthorNotes' => [
				'property' => 'hasAuthorNotes',
				'type' => 'checkbox',
				'label' => 'Has Author Notes',
				'description' => 'Whether or not author notes are available in the subscription',
			],
			'hasVideoClip' => [
				'property' => 'hasVideoClip',
				'type' => 'checkbox',
				'label' => 'Has Video Clip',
				'description' => 'Whether or not the excerpt is available in the subscription',
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that can use these settings',
				'values' => $libraryList,
				'hideInLists' => false,
				'forcesReindex' => true,
			],
		];

		self::$_objectStructure[$context] = $structure;
		return self::$_objectStructure[$context];
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->syndeticsSettingId = $this->id;
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
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update(string $context = '') : bool|int {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function insert(string $context = '') : int|bool {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function saveLibraries() : void{
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->syndeticsSettingId != $this->id) {
						$library->syndeticsSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->syndeticsSettingId == $this->id) {
						$library->syndeticsSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}
}
