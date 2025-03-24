<?php

require_once 'IndexRecordDriver.php';
require_once ROOT_DIR . '/sys/Events/EventInstance.php';
require_once ROOT_DIR . '/sys/Events/Event.php';

class AspenEventRecordDriver extends IndexRecordDriver {
	private $valid;
	/** @var AspenEventRecordDriver */
	private $eventObject;

	public function __construct($recordData) {
		if (is_array($recordData)) {
			parent::__construct($recordData);
			$this->valid = true;
		} else {
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
		$interface->assign('eventVendor', 'aspenEvent');

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

//		require_once ROOT_DIR . '/sys/Events/AssabetSetting.php';
//		$eventSettings = new AssabetSetting;
//		$eventSettings->id = $this->getSource();
//		if ($eventSettings->find(true)){

//			$interface->assign('bypassEventPage', $eventSettings->bypassAspenEventPages);
//		}
	$interface->assign('isStaff', UserAccount::isStaff());
	$interface->assign('eventsInLists', true);
	$interface->assign('bypassEventPage', false);

	$interface->assign('upcomingInstanceCount', $this->getEventObject()->getUpcomingInstanceCount() ?? 0);
	$interface->assign('private', $this->isPrivate() ? 'private' : '');

//		require_once ROOT_DIR . '/sys/Events/EventsUsage.php';
//		$eventsUsage = new EventsUsage();
//		$eventsUsage->type = $this->getType();
//		$eventsUsage->source = $this->getSource();
//		$eventsUsage->identifier = $this->getIdentifier();
//		$eventsUsage->year = date('Y');
//		$eventsUsage->month = date('n');
//		if ($eventsUsage->find(true)) {
//			$eventsUsage->timesViewedInSearch++;
//			$eventsUsage->update();
//		} else {
//			$eventsUsage->timesViewedInSearch = 1;
//			$eventsUsage->timesUsed = 0;
//			$eventsUsage->insert();
//		}

		return 'RecordDrivers/Events/aspenEvent_result.tpl';
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false, $type = "aspenEvent_event") {
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type={$type}";

		return $bookCoverUrl;
	}

	public function getModule(): string {
		return 'Events';
	}

	public function getStaffView() {
		global $interface;
		return $this->getEventObject();
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

	public function isPrivate() {
		if (array_key_exists('private', $this->fields)) {
			return in_array("private", $this->fields['private']);
		}else{
			return false;
		}
	}

	public function getFullDescription() {
		if (isset($this->fields['description'])) {
			return $this->fields['description'];
		} else {
			return '';
		}
	}

	public function getEventTypeFields() {
		$keys = array_keys($this->fields);
		$typeFields = [];
		$html = "";
		foreach ($keys as $key) {
			if (str_starts_with($key, 'custom_')) {
				$typeFields[$key] = $this->fields[$key];
			}
		}
		foreach ($typeFields as $key => $value) {
			$pattern = '/custom_([a-z]+)_/i';
			$fieldname = preg_replace($pattern, "", $key);
			$fieldname = str_replace("_", " ", $fieldname);
			if (str_contains($key, "custom_facet")) {
				continue;
			}
			if (is_array($value)) {
				if (count($value) == 0 || empty($value[0])) {
					continue;
				}
				if (str_contains($key, "url")) {
					$html .= "<li>$fieldname: <a href='$value[0]'>$value[0]</a></li>";
				} else if (str_contains($key, "email")) {
					$html .= "<li>$fieldname: <a href='mailto:$value[0]'>$value[0]</a></li>";
				} else {
					$html .= "<li>$fieldname: $value[0]</li>";
				}
			} else if (str_contains($key, "bool")) {
				$value = $value == 1 ? "Yes" : "No";
				$html .= "<li>$fieldname: " . $value . "</li>";
			} else {
				if (!empty($value)) {
					$html .= "<li>$fieldname: $value</li>";
				}
			}
		}
		return $html;
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
		return '/AspenEvents/' . $this->getId() . '/Event';
	}

	public function getExternalUrl($absolutePath = false) {
		return null;
	}

	public function getAudiences() {
		if (array_key_exists('age_group_facet', $this->fields)){
			return $this->fields['age_group_facet'];
		}
	}

	public function getProgramTypes() {
		if (array_key_exists('program_type_facet', $this->fields)){
			return $this->fields['program_type_facet'];
		}
	}
	public function getOtherEventsInSeries() {
		$eventInstance = $this->getEventObject();
		$series = $eventInstance->getSeries();
		$idPrefix = substr($this->getId(), 0, - strlen($this->getIdentifier()));
		$events = [];
		foreach ($series as $event) {
			$events[$idPrefix . $event->id] = strtotime($event->date);
		}
		return $events;
	}

	public function getBranch() {
		return implode(", ", array_key_exists("branch", $this->fields) ? $this->fields['branch'] : []);
	}

	public function getRoom() {
		return implode(", ", array_key_exists("room", $this->fields) ? $this->fields['room'] : []);
	}

	public function getType() {
		return array_key_exists("event_type", $this->fields) ? $this->fields['event_type'] : '';
	}

	public function getIntegration() {
		return $this->fields['type'];
	}

	public function getSource() {
		return $this->fields['source'];
	}

	function getEventCoverUrl() {
		if (!empty($this->fields['image_url'])) {
			global $interface;
			return $this->getBookcoverUrl('medium', false, "aspenEvent_eventRecord");
		}
		return null;
	}

	function getCoverImagePath() {
		if (!empty($this->fields['image_url'])) {
			return $this->fields['image_url'];
		}
		return false;
	}

	function getEventObject() {
		if ($this->eventObject == null) {
			$this->eventObject = new EventInstance();
			$this->eventObject->id = $this->getIdentifier();
			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		return $this->eventObject;
	}

	function getStartDateFromDB($id) : ?object {
		if ($this->eventObject == null) {
			$this->eventObject = new EventInstance();
			$this->eventObject->$id;

			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		$data = $this->eventObject;

		try {
			$startDate = new DateTime($data->date . " " . $data->time);
			$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $startDate;
		} catch (Exception $e) {
			return null;
		}

	}

	function getTitleFromDB($id) {
		if ($this->eventObject == null) {
			$this->eventObject = new Event();
			$this->eventObject->externalId;

			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
			}
		}
		$data = $this->eventObject;

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
		if (array_key_exists("registration_required", $this->fields) && $this->fields['registration_required'] == "Yes") {
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
		return false;
	}

	public function getAllowInListsSetting() {
		return true;
	}

	public function getRegistrationModalBody() {
		return null;
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
			'url' => null,
			'source' => 'aspenEvents',
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