<?php

require_once 'IndexRecordDriver.php';
require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarEvent.php';

class LibraryCalendarEventRecordDriver extends IndexRecordDriver {
	private $valid;
	/** @var LMLibraryCalendarEvent */
	private $eventObject;
	private $originalId;

	public function __construct($recordData) {
		if (is_array($recordData)) {
			parent::__construct($recordData);
			$this->valid = true;
		} else {
			// Store the original ID for use when record is not found in search index.
			$this->originalId = $recordData;
			disableErrorHandler();
			try {
				require_once ROOT_DIR . '/sys/SearchObject/EventsSearcher.php';
				$searchObject = new SearchObject_EventsSearcher();
				$recordData = $searchObject->getRecord($recordData);
				if ($recordData == null) {
					$this->valid = false;
				} else {
					parent::__construct($recordData);
					$this->valid = true;
				}
			} catch (Exception $e) {
				$this->valid = false;
			}
			enableErrorHandler();
		}
	}

	public function isValid() {
		return $this->valid;
	}

	public function getListEntry($listId = null, $allowEdit = true) {
		//Use getSearchResult to do the bulk of the assignments
		$this->getSearchResult('list', false);

		global $interface;
		$interface->assign('eventVendor', 'library_market');

		//Switch template
		return 'RecordDrivers/Events/listEntry.tpl';
	}

	public function getTitle(){
		$title = isset($this->fields['title']) ? $this->fields['title'] : (isset($this->fields['title_display']) ? $this->fields['title_display'] : '');
		if (strpos($title, '|') > 0) {
			$title = substr($title, 0, strpos($title, '|'));
		}
		return trim($title);
	}

	public function getSearchResult($view = 'list') {
		global $interface;

		$interface->assign('id', $this->getId());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('medium'));
		$interface->assign('eventUrl', $this->getLinkUrl());
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
		$allDayEvent = false;
		$multiDayEvent = false;
		if ($this->isAllDayEvent()){
			$allDayEvent = true;
		}
		if ($this->isMultiDayEvent()){
			$allDayEvent = false; //if the event is multiple days we don't want to say it's all day
			$multiDayEvent = true;
		}
		$interface->assign('allDayEvent', $allDayEvent);
		$interface->assign('multiDayEvent', $multiDayEvent);
		$interface->assign('start_date', $this->fields['start_date']);
		$interface->assign('end_date', $this->fields['end_date']);
		$interface->assign('source', isset($this->fields['source']) ? $this->fields['source'] : '');

		if (IPAddress::showDebuggingInformation()) {
			$interface->assign('summScore', $this->getScore());
			$interface->assign('summExplain', $this->getExplain());
		}

		require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
		$eventSettings = new LMLibraryCalendarSetting;
		$eventSettings->id = $this->getSource();
		if ($eventSettings->find(true)){
			$interface->assign('eventsInLists', $eventSettings->eventsInLists);
			$interface->assign('bypassEventPage', $eventSettings->bypassAspenEventPages);
		}
		$interface->assign('isStaff', UserAccount::isStaff());


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

		return 'RecordDrivers/Events/library_calendar_result.tpl';
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false): string {
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}

		// For expired events that don't have a valid ID, use the original ID from constructor.
		$uniqueId = $this->getUniqueID();
		if (empty($uniqueId) && !$this->isValid() && !empty($this->originalId)) {
			$uniqueId = $this->originalId;
		}

		$bookCoverUrl .= "/bookcover.php?id={$uniqueId}&size={$size}&type=library_calendar_event";

		return $bookCoverUrl;
	}

	public function getModule(): string {
		return 'LMLCEvents';
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

	public function getStatus() {
		if (array_key_exists('reservation_state', $this->fields) && in_array('Cancelled', $this->fields['reservation_state'])) {
			return "Cancelled";
		} else {
			return "Active";
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
	public function getUniqueID() {
		return $this->fields['id'];
	}

	public function getLinkUrl($absolutePath = false) {
		require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
		$eventSettings = new LMLibraryCalendarSetting;
		$eventSettings->id = $this->getSource();
		if ($eventSettings->find(true)){
			if ($eventSettings->bypassAspenEventPages){
				return $this->fields['url'];
			} else {
				return '/LibraryMarket/' . $this->getId() . '/Event';
			}
		}
	}

	public function getRegistrationModalBody() {
		require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
		$eventSettings = new LMLibraryCalendarSetting;
		$eventSettings->id = $this->getSource();
		if ($eventSettings->find(true)){
			if ($eventSettings->registrationModalBody){
				return $eventSettings->registrationModalBody;
			} else {
				return null;
			}
		}
	}

	public function getRegistrationModalBodyForAPI() {
		require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
		$eventSettings = new LMLibraryCalendarSetting;
		$eventSettings->id = $this->getSource();
		if ($eventSettings->find(true)){
			if (!empty($eventSettings->registrationModalBodyApp)){
				return $eventSettings->registrationModalBodyApp;
			} else {
				return null;
			}
		}
	}

	public function getExternalUrl($absolutePath = false) {
		return $this->fields['url'];
	}

	public function getAudiences() {
		if (array_key_exists('age_group', $this->fields)){
			return $this->fields['age_group'];
		}
	}

	public function getBranch() {
		return implode(", ", $this->fields['branch']);
	}

	private function getType() {
		return $this->fields['type'];
	}

	public function getIntegration() {
		return $this->fields['type'];
	}

	public function getSource() {
		return $this->fields['source'];
	}

	function getEventCoverUrl() {
		$decodedData = $this->getEventObject()->getDecodedData();
		if (!empty($decodedData->image)) {
			return $decodedData->image;
		}
		return null;
	}

	function getEventObject() {
		if ($this->eventObject == null) {
			$this->eventObject = new LMLibraryCalendarEvent();
			$this->eventObject->externalId = $this->getIdentifier();
			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		return $this->eventObject;
	}

	function getStartDateFromDB($id) : ?object {
		if ($this->eventObject == null) {
			$this->eventObject = new LMLibraryCalendarEvent();
			$this->eventObject->externalId = preg_replace('/^lc_\d+_/', '', $id);

			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		$data = $this->eventObject->getDecodedData();

		try {
			$startDate = new DateTime($data->start_date);
			$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $startDate;
		} catch (Exception $e) {
			return null;
		}

	}

	function getTitleFromDB($id) {
		if ($this->eventObject == null) {
			$this->eventObject = new LMLibraryCalendarEvent();
			$this->eventObject->externalId = preg_replace('/^lc_\d+_/', '', $id);

			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		$data = $this->eventObject->getDecodedData();

		return $data->title;
	}

	private function getIdentifier() {
		return $this->fields['identifier'];
	}

	public function getStartDate() {
		try {
			//Need to specify timezone since we start as a timstamp
			$startDate = new DateTime($this->fields['start_date']);
			$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $startDate;
		} catch (Exception $e) {
			return null;
		}
	}

	public function getEndDate() {
		try {
			//Need to specify timezone since we start as a timstamp
			$endDate = new DateTime($this->fields['end_date']);
			$endDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $endDate;
		} catch (Exception $e) {
			return null;
		}
	}

	public function isAllDayEvent() {
		try {
			$start = new DateTime($this->fields['start_date']);
			$end = new DateTime($this->fields['end_date']);

			$interval = $start->diff($end);

			if ($interval->h == 24 || ($interval->i == 0 && $interval->h == 0)){ //some events don't last an hour
				return 1;
			}
			return 0;
		} catch (Exception $e) {
			return null;
		}
	}

	public function isMultiDayEvent() {
		try {
			$start = new DateTime($this->fields['start_date']);
			$end = new DateTime($this->fields['end_date']);

			$interval = $start->diff($end);

			if ($interval->d > 0){
				return 1;
			}
			return 0;
		} catch (Exception $e) {
			return null;
		}
	}

	public function isRegistrationRequired(): bool {
		if ($this->fields['registration_required'] == "Yes") {
			return true;
		} else {
			return false;
		}
	}

	public function inEvents() {
		if (UserAccount::isLoggedIn()) {
			return UserAccount::getActiveUserObj()->inUserEvents($this->getId());
		}else{
			return false;
		}
	}

	public function isRegisteredForEvent() {
		if (UserAccount::isLoggedIn()) {
			return UserAccount::getActiveUserObj()->isRegistered($this->getId());
		}else{
			return false;
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

	public function getBypassSetting() {
		require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
		$eventSettings = new LMLibraryCalendarSetting;
		$eventSettings->id = $this->getSource();
		if ($eventSettings->find(true)){
			return $eventSettings->bypassAspenEventPages;
		}

		return false;
	}

	public function getAllowInListsSetting() {
		require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarSetting.php';
		$eventSettings = new LMLibraryCalendarSetting;
		$eventSettings->id = $this->getSource();
		if ($eventSettings->find(true)){
			return $eventSettings->eventsInLists;
		}

		return false;
	}

	public function getSummaryInformation() {
		return [
			'id' => $this->getUniqueID(),
			'shortId' => $this->getIdentifier(),
			'recordtype' => 'event',
			'image' => $this->getBookcoverUrl('medium'),
			'title' => $this->getTitle(),
			'description' => strip_tags($this->getDescription()),
			'isAllDay' => $this->isAllDayEvent(),
			'start_date' => $this->getStartDate(),
			'end_date' => $this->getEndDate(),
			'registration_required' => $this->isRegistrationRequired(),
			'bypass' => $this->getBypassSetting(),
			'url' => $this->getExternalUrl(),
			'source' => 'library_calendar',
			'author' => null,
			'format' => null,
			'ratingData' => null,
			'language' => null,
			'publisher' => '',
			'length' => '',
			'titleURL' => null,
		];
	}
}