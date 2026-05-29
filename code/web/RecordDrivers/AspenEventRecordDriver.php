<?php

require_once 'IndexRecordDriver.php';
require_once ROOT_DIR . '/sys/Events/EventInstance.php';
require_once ROOT_DIR . '/sys/Events/Event.php';

class AspenEventRecordDriver extends IndexRecordDriver {
	private $valid;
	/** @var EventInstance */
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

	public static function isValidSourceId(string $sourceId): bool {
		return (bool)preg_match('/^aspenEvent_\d+_\d+$/', $sourceId);
	}

	public static function sanitizeSourceId(string $sourceId): ?string {
		if (!self::isValidSourceId($sourceId)) {
			return null;
		}
		return $sourceId;
	}

	public static function invalidSourceIdResult(): array {
		return [
			'success' => false,
			'message' => translate([
				'text' => 'Invalid event source ID.',
				'isPublicFacing' => true,
			]),
		];
	}

	public static function extractEventInstanceId(string $sourceId): ?int {
		if (!self::isValidSourceId($sourceId)) {
			return null;
		}
		$parts = explode('_', $sourceId);
		return (int)end($parts);
	}

	public function getListEntry($listId = null, $allowEdit = true) {
		//Use getSearchResult to do the bulk of the assignments
		$this->getSearchResult('list', false);

		global $interface;
		$interface->assign('eventVendor', 'aspenEvents');

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
	$interface->assign('hiddenTimestamps', $this->hiddenTimestamps());
	$interface->assign('numberOfSeats', $this->getNumberOfSeats());
	$interface->assign('availableSeats', $this->getAvailableSeats());
	$interface->assign('isEventFull', $this->isEventFull());

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

	public function hiddenTimestamps() {
		if (isset($this->fields['hidden_timestamps'])) {
			if ($this->fields['hidden_timestamps'] == "true") {
				return true;
			};
		}
		return false;
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
		global $configArray;
		$relativePath = 'MyEvents?page=1&eventsFilter=upcoming';
		if ($absolutePath){
			return $configArray['Site']['url'] . "/MyAccount/$relativePath";
		}
		return $relativePath;
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
		global $library;
		if (empty($library->allowEventRegistration)) {
			return false;
		}
		return array_key_exists("registration_required", $this->fields) && $this->fields['registration_required'] == "Yes";
	}

	public function getNumberOfSeats(): ?int {
		$eventObject = $this->getEventObject();
		if ($eventObject) {
			return $eventObject->getEffectiveNumberOfSeats();
		}
		return null;
	}

	public function getAvailableSeats(): ?int {
		$eventObject = $this->getEventObject();
		if ($eventObject) {
			return $eventObject->getAvailableSeats();
		}
		return null;
	}

	public function getRegistrationCount(): int {
		$eventObject = $this->getEventObject();
		if ($eventObject) {
			return $eventObject->getRegistrationCount();
		}
		return 0;
	}

	public function isEventFull(): bool {
		$eventObject = $this->getEventObject();
		if ($eventObject) {
			return !$eventObject->hasAvailableSeats();
		}
		return false;
	}

	public function inEvents() {
		if (UserAccount::isLoggedIn()) {
			return UserAccount::getActiveUserObj()->inUserEvents($this->getId());
		}else{
			return false;
		}
	}

	/**
	 * Determines whether a user should or should not see the "Registration Information" link on Aspen Events.
	 **/
	public function isRegisteredForEvent() {
		$user = UserAccount::getLoggedInUser();
		if (!$user) {
			return false;
		}

		// if linked users are enabled and if any is not registered, display the modal link
		global $library;
		if ($library->allowLinkedAccounts) {
			$linkedUserIds = $user->getLinkedUsers();
			foreach ($linkedUserIds as $linkedUserId) {
				if (!$this->isUserRegisteredForEvent($linkedUserId)) {
					return false;
				}
			}
		}

		// check the active user's registration 
		return $this->isUserRegisteredForEvent();
	}

	/**
	 * Checks a user's registration by their id.
	 * If no userId is specified, checks registration status for the active user.
	 **/
	public function isUserRegisteredForEvent($userId = null) {
		if (!UserAccount::isLoggedIn()) {
			return false;
		}
		require_once ROOT_DIR . '/sys/Events/UserAspenEventInstanceRegistration.php';
		$registration = new UserAspenEventInstanceRegistration();
		$registration->userId = $userId ? $userId : UserAccount::getActiveUserId();
		$registration->eventInstanceId = $this->getIdentifier();
		return $registration->isUserRegisteredForEvent();
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

	public function getRegistrationModalBody(): string|null {
		require_once ROOT_DIR . '/sys/Events/AspenEventSetting.php';
		$eventSettings = new AspenEventSetting;
		$eventSettings->id = $this->getSource();
		if (!$eventSettings->find(true)){
			return "Aspen Events are not configured";
		}
		return $eventSettings->getRegistrationModalBody();
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
			'number_of_seats' => $this->getNumberOfSeats(),
			'available_seats' => $this->getAvailableSeats(),
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

	public function getDisplayBranchOnThumbnail() {
		$eventInstance = $this->getEventObject();
		if ($eventInstance) {
			require_once ROOT_DIR . '/sys/Events/Event.php';
			$event = new Event();
			$event->id = $eventInstance->eventId;
			if ($event->find(true)) {
				return $event->displayEventBranchOnThumbnail;
			}
		}
		return false;
	}

	function getBranchFromDB($id) {
	
		if (strpos($id, '_') !== false) {
			$parts = explode('_', $id);
			$numericId = end($parts);
		} else {
			$numericId = $id;
		}
	
		if ($this->eventObject == null || $this->eventObject->id != $numericId) {
			$this->eventObject = new EventInstance();
			$this->eventObject->id = $numericId;

			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
				return '';
			}
		}
	
		if ($this->eventObject === false) {
			return '';
		}

		require_once ROOT_DIR . '/sys/Events/Event.php';
		$event = new Event();
		$event->id = $this->eventObject->eventId;
		if ($event->find(true)) {
			require_once ROOT_DIR . '/sys/LibraryLocation/Location.php';
			$location = new Location();
			$location->locationId = $event->locationId;
			if ($location->find(true)) {
				return $location->displayName;
			}
		}
		return false;
	}

	function getDisplayBranchOnThumbnailFromDB($id) {
		
		if (strpos($id, '_') !== false) {
			$parts = explode('_', $id);
			$numericId = end($parts);
		} else {
			$numericId = $id;
		}
		
		if ($this->eventObject == null || $this->eventObject->id != $numericId) {
			$this->eventObject = new EventInstance();
			$this->eventObject->id = $numericId;

			if (!$this->eventObject->find(true)) {
				$this->eventObject = false;
				return false;
			}
		}
		
		if ($this->eventObject === false) {
			return false;
		}
		
		require_once ROOT_DIR . '/sys/Events/Event.php';
		$event = new Event();
		$event->id = $this->eventObject->eventId;
		if ($event->find(true)) {
			return $event->displayEventBranchOnThumbnail;
		}
		
		return false;
	}

	public function saveUserEventEntry($sourceId, $userId): void {
		require_once ROOT_DIR . '/sys/Events/UserEventsEntry.php';
		$userEventsEntry = new UserEventsEntry();
		$userEventsEntry->sourceId = $sourceId;
		$userEventsEntry->userId = $userId;

		if ($userEventsEntry->find(true)) {
			return;
		}

		$userEventsEntry->title = mb_substr($this->getTitle(), 0, 50);
		$userEventsEntry->eventDate = $this->getStartDate()->getTimestamp();
		$userEventsEntry->regRequired = $this->isRegistrationRequired() ? 1 : 0;
		$userEventsEntry->location = $this->getBranch();
		$userEventsEntry->dateAdded = time();
		$userEventsEntry->insert();
	}
}