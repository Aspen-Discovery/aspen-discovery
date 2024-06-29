<?php

require_once 'IndexRecordDriver.php';

class GrapesPageRecordDriver extends IndexRecordDriver {
	private $valid;
	private $recordtype;

	public function __construct($recordData) {
		if (is_array($recordData)) {
			parent::__construct($recordData);
			$this->valid = true;
		} else {
			require_once ROOT_DIR . '/sys/SearchObject/WebsitesSearcher.php';
			$searchObject = new SearchObject_WebsitesSearcher();
			$recordData = $searchObject->getRecord($recordData);
			parent::__construct($recordData);
			$this->valid = true;
		}
		$this->recordtype = $this->fields['recordtype'];
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
		$interface->assign('pageUrl', $this->getLinkUrl());
		$interface->assign('website_name', $this->fields['website_name']);
		$interface->assign('title', $this->getTitle());
		if (isset($this->fields['description'])) {
			$interface->assign('description', strip_tags($this->getDescription()));
		} else {
			$interface->assign('description', '');
		}
		$interface->assign('source', isset($this->fields['source']) ? $this->fields['source'] : '');
		return 'RecordDrivers/WebPage/result.tpl';
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false) {
		global $configArray;

		if ($absolutePath) {
			$bookCoverUrl = $configArray['Site']['url'];
		} else {
			$bookCoverUrl = '';
		}
		$bookCoverUrl .= "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=GrapesPage";

		return $bookCoverUrl;
	}

	public function getModule(): string {
		return 'WebBuilder';
	}

	public function getStaffView() {
		// TODO: Implement getStaffView() method.
	}

	public function getDescription() {
		$grapesPage = $this->getGrapesPage();
		if ($grapesPage != null && $grapesPage->canView()) {
			if (isset($this->fields['description'])) {
				return strip_tags($this->fields['description']);
			} else {
				return '';
			}
		} else {
			if ($grapesPage != null) {
				return $grapesPage->getHiddenReason();
			} else {
				return '';
			}
		}
	}

	private $grapesPage;

	private function getGrapesPage(): ?GrapesPage {
		if ($this->grapesPage == null) {
			require_once ROOT_DIR . '/sys/WebBuilder/GrapesPage.php';
			$this->grapesPage = new GrapesPage();
			[
				,
				$id,
			] = explode(':', $this->getId());
			$this->grapesPage->id = $id;
			if ($this->grapesPage->find(true)) {
				return $this->grapesPage;
			} else {
				return null;
			}
		} else {
			return $this->grapesPage;
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
		return $this->fields['source_url'];
	}
}