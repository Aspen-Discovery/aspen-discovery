<?php

require_once 'IndexRecordDriver.php';

class OpenArchivesRecordDriver extends IndexRecordDriver {
	private $valid;

	public function __construct($recordData) {
		if (is_array($recordData)) {
			parent::__construct($recordData);
			$this->valid = true;
		} else {
			require_once ROOT_DIR . '/sys/SearchObject/OpenArchivesSearcher.php';
			$searchObject = new SearchObject_OpenArchivesSearcher();
			$recordData = $searchObject->getRecord($recordData);
			parent::__construct($recordData);
			$this->valid = true;
		}
	}

	public function isValid() {
		return $this->valid;
	}

	public function getListEntry($listId = null, $allowEdit = true) {
		//Use getSearchResult to do the bulk of the assignments
		$this->getSearchResult('list', false);
		//Switch template
		return 'RecordDrivers/OpenArchives/listEntry.tpl';
	}

	public function getSearchResult($view = 'list', $showListsAppearingOn = true): string {
		if ($view == 'covers') { // Displaying Results as bookcover tiles
			return $this->getBrowseResult();
		}

		global $interface;

		$interface->assign('id', $this->getId());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('openArchiveUrl', $this->getLinkUrl());
		$interface->assign('title', $this->getTitle());
		if (isset($this->fields['description'])) {
			$interface->assign('description', $this->getDescription());
		} else {
			$interface->assign('description', '');
		}
		if (isset($this->fields['type'])) {
			$interface->assign('type', $this->fields['type']);
		}
		$interface->assign('source', $this->fields['source'] ?? '');
		$interface->assign('publisher', $this->fields['publisher'] ?? '');
		if (array_key_exists('date_text', $this->fields)) {
			$interface->assign('date', $this->fields['date_text']);
		} else {
			if (array_key_exists('date', $this->fields)) {
				// Check to see whether $this->fields['date'] is an array then loop through each member.
				//	- Extract date portion directly from ISO format to avoid timezone conversion issues.
				//	- Fallback to original method for non-ISO dates.
				if (is_array($this->fields['date'])) {
					$date = '';
					foreach ($this->fields['date'] as $datePart) {

						if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $datePart, $matches)) {
							$date .= $matches[1] . ' ';
						} else {
							$date .= date('Y-m-d', strtotime($datePart)) . ' ';
						}
					}
					$interface->assign('date', trim($date));
				} else {
					if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $this->fields['date'], $matches)) {
						$interface->assign('date', $matches[1]);
					} else {
						$interface->assign('date', date('Y-m-d', $this->fields['date']));
					}
				}
			} else {
				$interface->assign('date');
			}
		}

		//Check to see if there are lists the record is on
		if ($showListsAppearingOn) {
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$appearsOnLists = UserList::getUserListsForRecord('OpenArchives', $this->getId());
			$interface->assign('appearsOnLists', $appearsOnLists);
		}

		require_once ROOT_DIR . '/sys/OpenArchives/OpenArchivesRecordUsage.php';
		$openArchivesUsage = new OpenArchivesRecordUsage();
		$openArchivesUsage->openArchivesRecordId = $this->getUniqueID();
		global $aspenUsage;
		$openArchivesUsage->instance = $aspenUsage->getInstance();
		$openArchivesUsage->year = date('Y');
		$openArchivesUsage->month = date('n');
		if ($openArchivesUsage->find(true)) {
			$openArchivesUsage->timesViewedInSearch++;
			$openArchivesUsage->update();
		} else {
			$openArchivesUsage->timesViewedInSearch = 1;
			$openArchivesUsage->timesUsed = 0;
			$openArchivesUsage->insert();
		}

		return 'RecordDrivers/OpenArchives/result.tpl';
	}

	public function getBrowseResult() {
		global $interface;
		$interface->assign('openInNewWindow', true);
		$interface->assign('onclick', "AspenDiscovery.OpenArchives.trackUsage('{$this->getId()}')");
		return parent::getBrowseResult();
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false) {
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=open_archives";

		return $bookCoverUrl;
	}

	public function getModule(): string {
		return 'OpenArchives';
	}

	public function getStaffView() {
		// TODO: Implement getStaffView() method.
	}

	public function getDescription() {
		if (!empty($this->fields['description'])) {
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
	public function getUniqueID() {
		return $this->fields['id'];
	}

	public function getLinkUrl($absolutePath = false) {
		return $this->fields['identifier'];
	}

}