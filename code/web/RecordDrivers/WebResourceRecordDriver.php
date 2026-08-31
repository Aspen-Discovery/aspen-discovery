<?php

require_once 'IndexRecordDriver.php';

class WebResourceRecordDriver extends IndexRecordDriver {
	private $valid;
	private $recordtype;

	public function __construct($recordData) {
		if (is_array($recordData)) {
			parent::__construct($recordData);
			$this->valid = true;
		} else {
			disableErrorHandler();
			require_once ROOT_DIR . '/sys/SearchObject/WebsitesSearcher.php';
			$searchObject = new SearchObject_WebsitesSearcher();
			$recordData = $searchObject->getRecord($recordData);
			enableErrorHandler();
			if (empty($recordData)) {
				parent::__construct($recordData);
				$this->valid = false;
			} else {
				parent::__construct($recordData);
				$this->valid = true;
			}
		}
		if ($this->valid) {
			$this->recordtype = $this->fields['recordtype'];
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
		$interface->assign('idNumber', $this->getNumericId());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('pageUrl', $this->getLinkUrl());
		$interface->assign('website_name', $this->fields['website_name']);
		$interface->assign('title', $this->getTitle());
		$webResource = $this->getWebResource();
		$openInNewTab = false;
		if ($webResource && $webResource->openInNewTab) {
			$openInNewTab = true;
		}
		$interface->assign('openInNewTab', $openInNewTab);
		if (isset($this->fields['description'])) {
			$interface->assign('description', strip_tags($this->getDescription()));
		} else {
			$interface->assign('description', '');
		}
		$interface->assign('source', isset($this->fields['source']) ? $this->fields['source'] : '');

		return 'RecordDrivers/WebPage/result.tpl';
	}

	private null|WebResource|false $webResource = null;
	private function getWebResource() : WebResource|false {
		if ($this->webResource === null) {
			require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
			$this->webResource = new WebResource();
			$this->webResource->id = $this->getNumericId();
			if (!$this->webResource->find(true)) {
				$this->webResource = false;
			}
		}
		return $this->webResource;
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false) {
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		$webResource = $this->getWebResource();
		if ($webResource && !empty($webResource->logo)) {
			return '/files/thumbnail/' . $webResource->logo;
		}
		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=WebResource";
		return $bookCoverUrl;
	}

	public function getModule(): string {
		return 'WebBuilder';
	}

	public function getStaffView() {
		// TODO: Implement getStaffView() method.
	}

	public function getDescription() {
		if (isset($this->fields['description'])) {
			return strip_tags($this->fields['description']);
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

	public function getNumericId(): string {
		return str_replace('WebResource:', '', $this->getUniqueID());
	}

	public function getLinkUrl($absolutePath = false) {
		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		$webResource = new WebResource();
		$webResource->id = $this->getNumericId();
		if ($webResource->find(true)) {
			$libraryId = null;
			$activeLibrary = Library::getActiveLibrary();
			if ($activeLibrary != null) {
				$libraryId = $activeLibrary->libraryId;
			}

			return $webResource->getUrlForLibrary($libraryId, $this->fields['source_url']);
		}

		return $this->fields['source_url'];
	}
}