<?php

require_once 'IndexRecordDriver.php';
require_once ROOT_DIR . '/sys/Events/LMLibraryCalendarEvent.php';

class LibraryCalendarEventRecordDriver extends IndexRecordDriver
{
	private $valid;
	/** @var LMLibraryCalendarEvent */
	private $eventObject;

	public function __construct($recordData)
	{
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

	public function isValid()
	{
		return $this->valid;
	}

	public function getListEntry($listId = null, $allowEdit = true)
	{
		return $this->getSearchResult('list');
	}

	public function getSearchResult($view = 'list')
	{
		global $interface;

		$interface->assign('id', $this->getId());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('eventUrl', $this->getLinkUrl());
		$interface->assign('title', $this->getTitle());
		if (isset($this->fields['description'])) {
			$interface->assign('description', $this->fields['description']);
		} else {
			$interface->assign('description', '');
		}
		if (array_key_exists('reservation_state', $this->fields) && in_array('Cancelled', $this->fields['reservation_state'] )) {
			$interface->assign('isCancelled', true);
		}else{
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

		return 'RecordDrivers/Events/library_calendar_result.tpl';
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false)
	{
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=library_calendar_event";

		return $bookCoverUrl;
	}

	public function getModule()
	{
		return 'LMLCEvents';
	}

	public function getStaffView()
	{
		// TODO: Implement getStaffView() method.
	}

	public function getDescription()
	{
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
	public function getUniqueID()
	{
		return $this->fields['id'];
	}

	public function getLinkUrl($absolutePath = false)
	{
		return $this->fields['url'];
	}

	private function getType()
	{
		return $this->fields['type'];
	}

	private function getSource()
	{
		return $this->fields['source'];
	}

	function getEventCoverUrl()
	{
		$decodedData = $this->getEventObject()->getDecodedData();
		if (!empty($decodedData->image)){
			return $decodedData->image;
		}
		return null;
	}

	function getEventObject(){
		if ($this->eventObject == null){
			$this->eventObject = new LMLibraryCalendarEvent();
			$this->eventObject->externalId = $this->getIdentifier();
			if (!$this->eventObject->find(true)){
				$this->eventObject = false;
			}
		}
		return $this->eventObject;
	}

	private function getIdentifier()
	{
		return $this->fields['identifier'];
	}

	public function getStartDate()
	{
		try {
			//Need to specify timezone since we start as a timstamp
			$startDate = new DateTime($this->fields['start_date']);
			$startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
			return $startDate;
		} catch (Exception $e) {
			return null;
		}
	}

	public function getSpotlightResult(CollectionSpotlight $collectionSpotlight, string $index){
		$result = parent::getSpotlightResult($collectionSpotlight, $index);
		if ($collectionSpotlight->style == 'text-list'){
			global $interface;
			$interface->assign('start_date', $this->fields['start_date']);
			$interface->assign('end_date', $this->fields['end_date']);
			$result['formattedTextOnlyTitle'] = $interface->fetch('RecordDrivers/Events/formattedTextOnlyTitle.tpl');
		}

		return $result;
	}
}