<?php


class CustomFormSubmission extends DataObject {
	public $__table = 'web_builder_custom_from_submission';
	public $id;
	public $formId;
	public $libraryId;
	public $userId;
	public $dateSubmitted;
	public $submission;
	public $isRead;

	public function getUniquenessFields(): array {
		return [
			'id',
		];
	}

	public static function getObjectStructure($context = ''): array {
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
			'dateSubmitted' => [
				'property' => 'dateSubmitted',
				'type' => 'timestamp',
				'label' => 'Date Submitted',
				'description' => 'The date of the form submission',
			],
			'isRead' => [
				'property' => 'isRead',
				'type' => 'checkbox',
				'label' => 'Mark as Read',
				'description' => 'If the submission has been read, archive it',
			],
			'submission' => [
				'property' => 'submission',
				'type' => 'html',
				'label' => 'Submission contents',
				'description' => 'The information that was submitted by the user',
				'hideInLists' => true,
			],
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
				$this->_data[$name] = empty($user->displayName) ? ($user->firstname . ' ' . $user->lastname) : $user->displayName;
			}
			$user->__destruct();
			return $this->_data[$name] ?? null;
		} elseif ($name == 'submission') {
            error_log("ENTRO AL SUBMISSION");
            if (!isset($this->submission)) {
                $this->submission = '';
                $submissionSelection = new CustomFormSubmissionSelection();
                $submissionSelection->formSubmissionId = $this->id;
                $submissionSelection->find();
                while ($submissionSelection->fetch()) {
                    $customFormField = new CustomFormField();
                    $customFormField->id = $submissionSelection->submissionFieldId;
                    $customFormField->find(true);
                    if(!empty($customFormField->label)){
                        $copySubmissionField = clone($customFormField);
                        $this->_data[$name] = $this->submission . $copySubmissionField;
                    }
                }
                error_log("LGM SUBMISSION SELECTION : " . print_r($submissionSelection,true));
                $submissionSelection->__destruct();
            }
            return $this->_data[$name];
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