<?php

require_once 'IndexRecordDriver.php';

class LibraryCalendarEventRecordDriver extends IndexRecordDriver
{
	private $valid;

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

	public function getListEntry($user, $listId = null, $allowEdit = true)
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
			$interface->assign('description', $this->getDescription());
		} else {
			$interface->assign('description', '');
		}
		$interface->assign('source', isset($this->fields['source']) ? $this->fields['source'] : '');

		require_once ROOT_DIR . '/sys/Events/EventsUsage.php';
		$eventsUsage = new EventsUsage();
		$eventsUsage->type = $this->getType();
		$eventsUsage->source = $this->getSource();
		$eventsUsage->identifier = $this->getUniqueID();
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
		return $this->fields['description'];
	}

	public function getItemActions($itemInfo)
	{
		return array();
	}

	public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null)
	{
		return array();
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
		return $this->fields['identifier'];
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


}