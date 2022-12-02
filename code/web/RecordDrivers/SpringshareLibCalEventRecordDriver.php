<?php

require_once 'IndexRecordDriver.php';
require_once ROOT_DIR . '/sys/Events/SpringshareLibCalEvent.php';

class SpringshareLibCalEventRecordDriver extends IndexRecordDriver {
	private $valid;
	/** @var SpringshareLibCalEvent */
	private $eventObject;

	public function __construct($recordData) {
		if (is_array($recordData)) {
			parent::__construct($recordData);
			$this->valid = true;
		} else {
			require_once ROOT_DIR . '/sys/SearchObject/EventsSearcher.php';
			$searchObject = new SearchObject_EventsSearcher();
			$recordData = $searchObject->getRecord($recordData);
			parent::__construct($recordData);
			$this->valid = true;
		}
	}

	public function isValid() {
		return $this->valid;
	}

	public function getListEntry($listId = null, $allowEdit = true) {
		return $this->getSearchResult('list');
	}

	public function getSearchResult($view = 'list') {
		global $interface;

		$interface->assign('id', $this->getId());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('eventUrl', $this->getLinkUrl());
		$interface->assign('externalUrl', $this->getExternalUrl());
		$interface->assign('branch', $this->getBranch());
		$interface->assign('title', $this->getTitle());
		if (isset($this->fields['description'])) {
			$interface->assign('description', $this->fields['description']);
		} else {
			$interface->assign('description', '');
		}
		if (array_key_exists('reservation_state', $this->fields) && in_array('Cancelled', $this->fields['reservation_state'])) {
			$interface->assign('isCancelled', true);
		} else {
			$interface->assign('isCancelled', false);
		}
		$interface->assign('start_date', $this->fields['start_date']);
		$interface->assign('end_date', $this->fields['end_date']);
		$interface->assign('source', isset($this->fields['source']) ? $this->fields['source'] : '');

		require_once ROOT_DIR . '/sys/Events/EventsUsage.php';
		$eventsUsage = new EventsUsage();
		$eventsUsage->type = $this->getType();
		$eventsUsage->source = $this->getSource();
		$eventsUsage->identifier = $this->getIdentifier();
		$eventsUsage->year = date('Y');
		$eventsUsage->month = date('n');
		if ($eventsUsage->find(true)) {
			$eventsUsage->timesViewedInSearch++;
			$eventsUsage->update();
		} else {
			$eventsUsage->timesViewedInSearch = 1;
			$eventsUsage->timesUsed = 0;
			$eventsUsage->insert();
		}

		return 'RecordDrivers/Events/springshare_libcal_result.tpl';
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false) {
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=springshare_libcal_event";

		return $bookCoverUrl;
	}

	public function getModule(): string {
		return 'SpringshareLibCal'; // TODO: verify module name 2022 03 16 James
	}

	public function getMoreDetailsOptions() {
		global $interface;
		$moreDetailsOptions = new StdClass();
		if ($interface->getVariable('showStaffView')) {
			$moreDetailsOptions['staff'] = [
				'label' => 'Staff View',
				'body' => $interface->fetch($this->getStaffView()),
			];
		}
		return $moreDetailsOptions;
	}

	public function getStaffView() {
		global $interface;
		return $this->getEventObject()->getDecodedData();
	}

	public function getDescription() {
		if (isset($this->fields['description'])) {
			return $this->fields['description'];
		} else {
			return '';
		}
	}

	/**
	 * Return the unique identifier of this record within the Solr index;
	 * useful for retrieving additional information (like tags and user
	 * comments) from the external MySQL database.
	 *
	 * @access  public
	 * @return  string              Unique identifier.
	 */

	public function getPermanentID() {
		return $this->getUniqueID();
	}

	public function getUniqueID() {
		return $this->fields['id'];
	}

	public function getExternalUrl($absolutePath = false) {
		return $this->fields['url'];
	}

	public function getLinkUrl($absolutePath = false) {
		return '/Springshare/' . $this->getId() . '/Event';
	}

	private function getType() {
		return $this->fields['type'];
	}

	private function getSource() {
		return $this->fields['source'];
	}

	function getEventCoverUrl() {
		return $this->fields['image_url'];
	}

	function getEventObject() {
		if ($this->eventObject == null) {
			$this->eventObject = new SpringshareLibCalEvent();
			$this->eventObject->externalId = $this->getIdentifier();
			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		return $this->eventObject;
	}

	private function getIdentifier() {
		return $this->fields['identifier'];
	}

	// TODO: eliminate dependence on smarty formatting of string return value; return unix timestamp instead like Library Market Library Calendar. James 2022 03 20
	public function getStartDate(): ?object {
		try {
			$startDate = new DateTime($this->fields['start_date']);
			$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $startDate;
		} catch (Exception $e) {
			return null;
		}
	}

	// TODO: eliminate dependence on smarty formatting of string return value; return unix timestamp instead like Library Market Library Calendar. James 2022 03 20
	public function getStartDateString() {
		try {
			return $this->fields['start_date'];
		} catch (Exception $e) {
			return null;
		}
	}

	public function getEndDateString() {
		try {
			return $this->fields['end_date'];
		} catch (Exception $e) {
			return null;
		}
	}

	public function getSpotlightResult(CollectionSpotlight $collectionSpotlight, string $index) {
		$result = parent::getSpotlightResult($collectionSpotlight, $index);
		if ($collectionSpotlight->style == 'text-list') {
			global $interface;
			$interface->assign('start_date', $this->fields['start_date']);
			$interface->assign('end_date', $this->fields['end_date']);
			$result['formattedTextOnlyTitle'] = $interface->fetch('RecordDrivers/Events/formattedTextOnlyTitle.tpl');
		}

		return $result;
	}

	public function getAudiences() {
		return $this->fields['age_group'];
	}

	public function getCategories() {
		return $this->fields['program_type'];
	}

	public function getBranch() {
		return implode(", ", $this->fields['branch']);
	}

	public function isRegistrationRequired(): bool {
		if ($this->fields['registration_required'] == "Yes") {
			return true;
		} else {
			return false;
		}
	}
}