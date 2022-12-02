<?php
require_once ROOT_DIR . '/RecordDrivers/IndexRecordDriver.php';
require_once ROOT_DIR . '/sys/Genealogy/Person.php';

class PersonRecord extends IndexRecordDriver {
	/** @var Person $person */
	private $person;
	private $id;

	public function __construct($record) {
		// Call the parent's constructor...
		parent::__construct($record);

		$this->id = $this->getUniqueID();
	}

	private function getPerson() {
		if (!isset($this->person)) {
			$person = new Person();
			$person->personId = $this->id;
			if ($person->find(true)) {
				$this->person = $person;
			}
		}
		return $this->person;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @param string $view
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getSearchResult($view = 'list') {
		global $interface;

		$interface->assign('summId', $this->id);

		$person = $this->getPerson();
		$interface->assign('summPicture', $person->picture);

		$name = $this->getName();
		$interface->assign('summTitle', trim($name));
		if (!empty($person)) {
			$interface->assign('birthDate', $person->formatPartialDate($person->birthDateDay, $person->birthDateMonth, $person->birthDateYear));
			$interface->assign('deathDate', $person->formatPartialDate($person->deathDateDay, $person->deathDateMonth, $person->deathDateYear));
			$interface->assign('lastUpdate', $person->lastModified);
			$interface->assign('dateAdded', $person->dateAdded);
			$interface->assign('numObits', count($person->obituaries));
		}

		return 'RecordDrivers/Person/result.tpl';
	}

	function getBreadcrumb() {
		return $this->getName();
	}

	function getName() {
		$name = '';
		if (isset($this->fields['firstName'])) {
			$name = $this->fields['firstName'];
		}
		if (isset($this->fields['middleName'])) {
			$name .= ' ' . $this->fields['middleName'];
		}
		if (isset($this->fields['nickName']) && strlen($this->fields['nickName']) > 0) {
			$name .= ' "' . $this->fields['nickName'] . '"';
		}
		if (isset($this->fields['maidenName']) && strlen($this->fields['maidenName']) > 0) {
			$name .= ' (' . $this->fields['maidenName'] . ')';
		}
		if (isset($this->fields['lastName']) && strlen($this->fields['lastName']) > 0) {
			$name .= ' ' . $this->fields['lastName'];
		}
		return $name;
	}


	function getPermanentId() {
		return $this->id;
	}

	function getBookcoverUrl($size = 'small', $absolutePath = false) {
		$person = $this->getPerson();
		global $configArray;
		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		if ($person->picture) {
			if ($size == 'small') {
				$bookCoverUrl .= '/files/thumbnail/' . $this->person->picture;
			} else {
				$bookCoverUrl .= '/files/medium/' . $this->person->picture;
			}

		} else {
			$bookCoverUrl .= '/interface/themes/responsive/images/person.png';
		}
		return $bookCoverUrl;
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * user's favorites list.
	 *
	 * @access  public
	 * @param int $listId ID of list containing desired tags/notes (or
	 *                              null to show tags/notes from all user's lists).
	 * @param bool $allowEdit Should we display edit controls?
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getListEntry($listId = null, $allowEdit = true) {
		$this->getSearchResult('list');

		//Switch template
		return 'RecordDrivers/Person/listEntry.tpl';
	}

	public function getModule(): string {
		return 'Person';
	}

	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display the full record information on the Staff
	 * View tab of the record view page.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getStaffView() {
		return '';
	}

	public function getDescription() {
		return '';
	}

	public function getBrowseResult() {
		global $interface;
		$id = $this->getUniqueID();
		$interface->assign('summId', $id);

		$url = $this->getRecordUrl();

		$interface->assign('summUrl', $url);
		$interface->assign('summTitle', $this->getName());

		//Get cover image size
		global $interface;
		$appliedTheme = $interface->getAppliedTheme();

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));

		if ($appliedTheme != null && $appliedTheme->browseCategoryImageSize == 1) {
			$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('large'));
		} else {
			$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
		}

		return 'RecordDrivers/Person/browse_result.tpl';
	}
}