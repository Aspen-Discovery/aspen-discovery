<?php

require_once ROOT_DIR . '/sys/Grouping/AuthorAuthorityAlternative.php';

class AuthorAuthority extends DataObject {
	public $__table = 'author_authority';
	public $id;
	public $author;
	public $normalized;
	public $dateAdded;
	private $_alternatives;

	public static function getObjectStructure($context = ''): array {
		$alternativesStructure = AuthorAuthorityAlternative::getObjectStructure($context);
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'author' => [
				'property' => 'author',
				'type' => 'text',
				'label' => 'Authoritative Author',
				'description' => 'The author name to use instead of the alternatives',
			],
			'normalized' => [
				'property' => 'normalized',
				'type' => 'text',
				'label' => 'Normalized Value',
				'description' => 'The normalized value for grouping',
				'readOnly' => true,
			],
			'dateAdded' => [
				'property' => 'dateAdded',
				'type' => 'timestamp',
				'label' => 'Date Added',
				'description' => 'The date the record was added',
				'readOnly' => true,
			],
			'alternatives' => [
				'property' => 'alternatives',
				'type' => 'oneToMany',
				'label' => 'Alternative Names',
				'description' => 'Information about the alternative names this person goes by',
				'keyThis' => 'id',
				'keyOther' => 'authorId',
				'subObjectType' => 'AuthorAuthorityAlternative',
				'structure' => $alternativesStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'forcesReindex' => true,
			],
		];
	}

	function __get($name) {
		if ($name == 'alternatives') {
			return $this->getAlternatives();
		} else {
			return $this->_data[$name];
		}
	}

	function __set($name, $value) {
		if ($name == 'alternatives') {
			$this->_alternatives = $value;
		} else {
			$this->_data[$name] = $value;
		}
	}

	/**
	 * @return array|null
	 */
	public function getAlternatives() {
		if (!isset($this->_alternatives) && $this->id) {
			$this->_alternatives = [];
			$alternativeAuthor = new AuthorAuthorityAlternative();
			$alternativeAuthor->authorId = $this->id;
			$alternativeAuthor->orderBy('alternativeAuthor');
			if ($alternativeAuthor->find()) {
				while ($alternativeAuthor->fetch()) {
					$this->_alternatives[$alternativeAuthor->id] = clone $alternativeAuthor;
				}
			}
		}
		return $this->_alternatives;
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveAlternates();
		}
		return $ret;
	}

	/**
	 * Override the insert functionality to save the related objects
	 *
	 * @see DB/DB_DataObject::insert()
	 */
	public function insert($context = '') {
		$this->dateAdded = time();
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveAlternates();
		}
		return $ret;
	}

	public function delete($useWhere = false) {
		$ret = parent::delete($useWhere);
		if ($ret && !$useWhere) {
			//Delete alternatives
			$alternatives = new AuthorAuthorityAlternative();
			$alternatives->authorId = $this->id;
			$alternatives->delete(true);
		}
		return $ret;
	}

	public function saveAlternates() {
		if (isset ($this->_alternatives) && is_array($this->_alternatives)) {
			$this->saveOneToManyOptions($this->_alternatives, 'authorId');
			unset($this->_alternatives);
		}
	}
}