<?php
require_once ROOT_DIR . '/sys/WebBuilder/QuickPollSubmissionSelection.php';

class QuickPollSubmission extends DataObject {
	public $__table = 'web_builder_quick_poll_submission';
	public $id;
	public $pollId;
	public $libraryId;
	public $userId;
	public $name;
	public $email;
	public $dateSubmitted;
	public $_selectedOptions;

	public function getUniquenessFields(): array {
		return [
			'id',
		];
	}

	public static function getObjectStructure($context = ''): array {
		$quickPollSelectedOptionStructure = QuickPollSubmissionSelection::getObjectStructure($context);
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'libraryName' => [
				'property' => 'libraryName',
				'type' => 'label',
				'label' => 'Library',
				'description' => 'The name of the library for the submission',
			],
			'userName' => [
				'property' => 'userName',
				'type' => 'label',
				'label' => 'User Name',
				'description' => 'The name of the user who made the submission',
			],
			'email' => [
				'property' => 'email',
				'type' => 'label',
				'label' => 'Email',
				'description' => 'The email address of the user who made the submission',
			],
			'dateSubmitted' => [
				'property' => 'dateSubmitted',
				'type' => 'timestamp',
				'label' => 'Date Submitted',
				'description' => 'The date of the form submission',
				'readOnly' => true,
			],
			'selectedOptions' => [
				'property' => 'selectedOptions',
				'type' => 'oneToMany',
				'label' => 'Selected Options',
				'description' => 'The options selected in the response',
				'keyThis' => 'id',
				'keyOther' => 'pollSubmissionIdId',
				'subObjectType' => 'QuickPollSelectedOption',
				'structure' => $quickPollSelectedOptionStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => false,
				'canDelete' => false,
			]
		];
	}

	public function __get($name) {
		if (isset($this->_data[$name])) {
			return $this->_data[$name] ?? null;
		} elseif ($name == 'libraryName') {
			$library = new Library();
			$library->id = $this->libraryId;
			if ($library->find(true)) {
				$this->_data[$name] = $library->displayName;
			}
			$library->__destruct();
			return $this->_data[$name] ?? null;
		} elseif ($name == 'userName') {
			$user = new User();
			$user->id = $this->userId;
			if ($user->find(true)) {
				$this->_data[$name] = $this->name ?? $user->displayName;
			} else {
				$this->_data[$name] = $this->name;
			}
			$user->__destruct();
			return $this->_data[$name];
		} elseif ($name == 'selectedOptions') {
			if ($this->_selectedOptions == null) {
				$this->_selectedOptions = [];
				$selectedOption = new QuickPollSubmissionSelection();
				$selectedOption->pollSubmissionId = $this->id;
				$selectedOption->find();
				while ($selectedOption->fetch()) {
					$quickPollOption = new QuickPollOption();
					$quickPollOption->id = $selectedOption->pollOptionId;
					$quickPollOption->find(true);
					if(!empty($quickPollOption->label)){
						$this->_selectedOptions[$selectedOption->id] = clone($selectedOption);
					}
				}
				$selectedOption->__destruct();
			}
			return $this->_selectedOptions;
		}
		return parent::__get($name);
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->libraryId, $selectedFilters['libraries'])) {
			$okToExport = true;
		}
		return $okToExport;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset ($return['libraryId']);

		return $return;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		//library
		$allLibraries = Library::getLibraryListAsObjects(false);
		if (array_key_exists($this->libraryId, $allLibraries)) {
			$library = $allLibraries[$this->libraryId];
			$links['library'] = empty($library->subdomain) ? $library->ilsCode : $library->subdomain;
		}
		//User
		$user = new User();
		$user->id = $this->userId;
		if ($user->find(true)) {
			$links['user'] = $user->ils_barcode;
		}
		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting');

		if (isset($jsonData['library'])) {
			$allLibraries = Library::getLibraryListAsObjects(false);
			$subdomain = $jsonData['library'];
			if (array_key_exists($subdomain, $mappings['libraries'])) {
				$subdomain = $mappings['libraries'][$subdomain];
			}
			foreach ($allLibraries as $tmpLibrary) {
				if ($tmpLibrary->subdomain == $subdomain || $tmpLibrary->ilsCode == $subdomain) {
					$this->libraryId = $tmpLibrary->libraryId;
					break;
				}
			}
		}
		if (isset($jsonData['user'])) {
			$username = $jsonData['user'];
			$user = new User();
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				$this->userId = $user->id;
			}
		}
	}
}