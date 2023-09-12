<?php

require_once ROOT_DIR . '/sys/WebBuilder/QuickPollOption.php';
require_once ROOT_DIR . '/sys/WebBuilder/LibraryQuickPoll.php';

class QuickPoll extends DB_LibraryLinkedObject {
	public $__table = 'web_builder_quick_poll';
	public $id;
	public $title;
	public $urlAlias;
	public $requireLogin;
	public $requireName;
	public $requireEmail;
	public $introText;
	public $submissionResultText;
	public $allowSuggestingNewOptions;
	public $allowMultipleSelections;
	public $status;

	/** @var array $_libraries */
	private $_libraries;
	/** @var CustomFormField[] */
	private $_pollOptions;

	public function getUniquenessFields(): array {
		return [
			'id',
		];
	}

	public function getNumericColumnNames(): array {
		return ['requireLogin'];
	}

	static function getObjectStructure($context = ''): array {
		$quickPollOptionStructure = QuickPollOption::getObjectStructure($context);
		unset ($quickPollOptionStructure['weight']);
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Custom Forms'));
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id within the database',
			],
			'title' => [
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'The title of the page',
				'size' => '40',
				'maxLength' => 100,
				'required' => true,
			],
			'urlAlias' => [
				'property' => 'urlAlias',
				'type' => 'text',
				'label' => 'URL Alias (no domain, should start with /)',
				'description' => 'The url of the page (no domain name)',
				'size' => '40',
				'maxLength' => 100,
			],
			'status' => [
				'property' => 'status',
				'type' => 'enum',
				'label' => 'Status',
				'values' => [
					1 => 'Being Created',
					2 => 'Open',
					3 => 'Closed',
				],
				'description' => 'The status of the poll',
				'required' => true,
				'default' => 1,
			],
			'introText' => [
				'property' => 'introText',
				'type' => 'markdown',
				'label' => 'Introductory Text',
				'description' => 'Introductory Text displayed above the fields',
				'hideInLists' => true,
			],
			'submissionResultText' => [
				'property' => 'submissionResultText',
				'type' => 'markdown',
				'label' => 'Submission Result Text',
				'description' => 'Text to be displayed to the user when submission is complete',
				'hideInLists' => true,
			],
			'requireLogin' => [
				'property' => 'requireLogin',
				'type' => 'checkbox',
				'label' => 'Require Login',
				'description' => 'Whether or not the user must be logged in to view the form',
				'default' => 1,
			],
			'requireName' => [
				'property' => 'requireName',
				'type' => 'checkbox',
				'label' => 'Require Name',
				'description' => 'Whether or not the user must provide their name as part of submission',
				'default' => 1,
			],
			'requireEmail' => [
				'property' => 'requireEmail',
				'type' => 'checkbox',
				'label' => 'Require Email',
				'description' => 'Whether or not the user must provide their email as part of submission',
				'default' => 1,
			],
			'pollOptions' => [
				'property' => 'pollOptions',
				'type' => 'oneToMany',
				'label' => 'Fields',
				'description' => 'Fields within the form',
				'keyThis' => 'id',
				'keyOther' => 'formId',
				'subObjectType' => 'QuickPollOption',
				'structure' => $quickPollOptionStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'canAddNew' => true,
				'canDelete' => true,
			],
			'allowMultipleSelections' => [
				'property' => 'allowMultipleSelections',
				'type' => 'checkbox',
				'label' => 'Allow Multiple Selections',
				'description' => 'Whether or not users can submit multiple choices or if only one can be selected',
				'default' => 0,
			],
			'allowSuggestingNewOptions' => [
				'property' => 'allowSuggestingNewOptions',
				'type' => 'checkbox',
				'label' => 'Allow Suggesting New Options',
				'description' => 'Whether or not users can suggest new options that will show in the poll for future respondents',
				'default' => 0,
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => true,
			],
		];
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->savePollOptions();
		}
		return $ret;
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->savePollOptions();
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} elseif ($name == "pollOptions") {
			return $this->getPollOptions();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == "pollOptions") {
			$this->_pollOptions = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function delete($useWhere = false) {
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$this->clearLibraries();
			$this->clearPollOptions();
		}
		return $ret;
	}

	public function getLibraries() : ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$libraryLink = new LibraryQuickPoll();
			$libraryLink->pollId = $this->id;
			$libraryLink->find();
			while ($libraryLink->fetch()) {
				$this->_libraries[$libraryLink->libraryId] = $libraryLink->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function getPollOptions() {
		if (!isset($this->_pollOptions) && $this->id) {
			$this->_pollOptions = [];
			$pollOption = new QuickPollOption();
			$pollOption->pollId = $this->id;
			$pollOption->orderBy('weight');
			$pollOption->find();
			while ($pollOption->fetch()) {
				$this->_pollOptions[$pollOption->id] = clone $pollOption;
			}
		}
		return $this->_pollOptions;
	}

	public function saveLibraries() {
		if (isset($this->_libraries) && is_array($this->_libraries)) {
			$this->clearLibraries();

			foreach ($this->_libraries as $libraryId) {
				$libraryLink = new LibraryQuickPoll();

				$libraryLink->pollId = $this->id;
				$libraryLink->libraryId = $libraryId;
				$libraryLink->insert();
			}
			unset($this->_libraries);
		}
	}

	public function savePollOptions() {
		if (isset($this->_pollOptions) && is_array($this->_pollOptions)) {
			$this->saveOneToManyOptions($this->_pollOptions, 'pollId');
			unset($this->_pollOptions);
		}
	}

	private function clearLibraries() {
		//Delete links to the libraries
		$libraryLink = new LibraryQuickPoll();
		$libraryLink->pollId = $this->id;
		return $libraryLink->delete(true);
	}

	private function clearPollOptions() {
		//Delete links to the libraries
		$pollOption = new QuickPollOption();
		$pollOption->pollId = $this->id;
		return $pollOption->delete(true);
	}

	public function getFormattedPoll() {
		global $interface;
		if (!UserAccount::isLoggedIn()) {
			if (!$this->requireLogin) {
				require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
				$recaptcha = new RecaptchaSetting();
				if ($recaptcha->find(true) && !empty($recaptcha->publicKey)) {
					$captchaCode = recaptcha_get_html($recaptcha->publicKey, $this->id);
					$interface->assign('captcha', $captchaCode);
					$interface->assign('captchaKey', $recaptcha->publicKey);
				}
			} else {
				return "<div class='alert alert-warning'>" . translate([
						'text' => 'You must be logged to view this poll',
						'isPublicFacing' => true,
					]) . '</div>';
			}
		}

		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		$parsedown->setBreaksEnabled(true);
		$introText = $parsedown->parse($this->introText);

		$interface->assign('introText', $introText);
		$interface->assign('poll', $this);
		$interface->assign('pollOptions', $this->getPollOptions());

		return $interface->fetch('WebBuilder/quickPoll.tpl');
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();

		//Form Fields
		$pollOptions = $this->getPollOptions();
		$links['pollOptions'] = [];
		foreach ($pollOptions as $pollOption) {
			$pollOptionArray = $pollOption->toArray(false, true);
			$links['pollOptions'][] = $pollOptionArray;
		}

		return $links;
	}

	public function loadRelatedLinksFromJSON($jsonLinks, $mappings, $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonLinks, $mappings, $overrideExisting);

		if (array_key_exists('pollOptions', $jsonLinks)) {
			$formFields = [];
			foreach ($jsonLinks['pollOptions'] as $pollOption) {
				$pollOptionObj = new QuickPollOption();
				$pollOptionObj->pollId = $this->id;
				unset($pollOption['pollId']);
				$pollOptionObj->loadFromJSON($pollOption, $mappings, $overrideExisting);
				$formFields[$pollOptionObj->id] = $pollOptionObj;
			}
			$this->_pollOptions = $formFields;
			$result = true;
		}

		return $result;
	}

	public function getPollResults() {
		$results = [];
		$obj = new QuickPollSubmission();
		$obj->pollId = $this->id;
		$obj->find();
		while($obj->fetch()) {
			$results[] = clone $obj;
		}
		return $results;
	}

	public function getPollResultsForGraph() {
		$results = [];
		$pollOptions = $this->getPollOptions();

		$submissions = [];
		$submission = new QuickPollSubmission();
		$submission->pollId = $this->id;
		$submission->find();
		while($submission->fetch()) {
			$submissions[] = $submission->id;
		}

		$selections = [];
		foreach($submissions as $obj) {
			$selection = new QuickPollSubmissionSelection();
			$selection->pollSubmissionId = $obj;
			$selection->find();
			while($selection->fetch()) {
				if(!array_key_exists($selection->pollOptionId, $selections)) {
					$selections[$selection->pollOptionId]['count'] = 1;
				} else {
					$selections[$selection->pollOptionId]['count'] += 1;
				}
			}
		}

		foreach($pollOptions as $option) {
			$results[$option->id]['id'] = $option->id;
			$results[$option->id]['label'] = $option->label;
			$results[$option->id]['count'] = 0;
			if(isset($selections[$option->id])) {
				$results[$option->id]['count'] = $selections[$option->id]['count'];
			}

		}
		return $results;
	}
}