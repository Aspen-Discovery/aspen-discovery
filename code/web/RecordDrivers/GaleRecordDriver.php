<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';

class GaleRecordDriver extends RecordInterface {
	/** @var SimpleXMLElement|null */
	private $record;

	public function __construct($record) {
		if (is_string($record)) {
			/** @var SearchObject_GaleSearcher $galeSearcher */
			$galeSearcher = SearchObjectFactory::initSearchObject("Gale");
			$this->record = $galeSearcher->retrieveRecord($record);
			$this->record->registerXPathNamespace('zs', 'http://www.loc.gov/zing/srw/');
			$this->record->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
		}else{
			$this->record= $record;
			$this->record->registerXPathNamespace('zs', 'http://www.loc.gov/zing/srw/');
			$this->record->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
		}
	}

	public function isValid(): bool {
		return true;
	}

	public function getBookcoverUrl($size = 'small', $absolutePath = false) {
		global $configArray;
		$base = $absolutePath ? $configArray['Site']['url'] : '';
		return $base . "/bookcover.php?id={$this->getUniqueID()}&size={$size}&type=gale";
	}

	/**
	 * @param bool $unscoped
	 * @return string
	 */
	public function getLinkUrl($unscoped = false) {
		return $this->getRecordUrl();
	}

	/**
	 * @return string
	 */
	public function getAbsoluteUrl() {
		return $this->getRecordUrl();
	}

	public function getRecordUrl() {
		$identifiers = $this->record->xpath('dc:identifier');
		if (!empty($identifiers)) {
			return (string)$identifiers[0];
		}
		return null;
	}
	
	public function getModule(): string {
		return 'Gale';
	}

	public function getSearchResult($view = 'list', $showListsAppearingOn = true) {
		if ($view == 'covers') { 
			return $this->getBrowseResult();
		}
		global $interface;

		$id = $this->getUniqueID();
		$formats = $this->getFormats();
		$interface->assign('summId', $id);
		$interface->assign('summShortId', $id);
		$interface->assign('module', $this->getModule());
		$interface->assign('summFormats', $formats);
		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getAuthor());
		$interface->assign('summSourceDatabase', $this->getSourceDatabase());
		$interface->assign('summPublicationDates', $this->getPublicationDate());
		$interface->assign('summHasFullText', $this->getFullText());

		//Check to see if there are lists the record is on
		if ($showListsAppearingOn) {
			require_once ROOT_DIR . '/sys/UserLists/UserList.php';
			$appearsOnLists = UserList::getUserListsForRecord('gale', $this->getUniqueID());
			$interface->assign('appearsOnLists', $appearsOnLists);
		}

		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));

		require_once ROOT_DIR . '/sys/Gale/GaleRecordUsage.php';
		global $aspenUsage;
		$recordUsage = new GaleRecordUsage();
		$recordUsage->instance = $aspenUsage->getInstance();
		$recordUsage->galeId = $this->getUniqueID();
		$recordUsage->year = date('Y');
		$recordUsage->month = date('n');
		if ($recordUsage->find(true)) {
			$recordUsage->timesViewedInSearch++;
			$recordUsage->update();
		} else {
			$recordUsage->timesViewedInSearch = 1;
			$recordUsage->timesUsed = 0;
			$recordUsage->insert();
		}
		return 'RecordDrivers/Gale/result.tpl';
	}

	public function getBrowseResult() {
		global $interface;

		$interface->assign('summId', $this->getUniqueID());
		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());

		//Get cover image size
		$appliedTheme = $interface->getAppliedTheme();
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('medium'));

		$accessibleBrowseCategories = 0;
		if ($appliedTheme) {
			if($appliedTheme->browseCategoryImageSize == 1) {
				$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('large'));
			}
			$accessibleBrowseCategories = $appliedTheme->accessibleBrowseCategories;
		} else {
			$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
		}
		$interface->assign('accessibleBrowseCategories', $accessibleBrowseCategories);

		return 'RecordDrivers/Gale/browse_result.tpl';
	}


	/**
	 * Assign necessary Smarty variables and return a template name to
	 * load in order to display a summary of the item suitable for use in
	 * search results.
	 *
	 * @access  public
	 * @return  string              Name of Smarty template file to display.
	 */
	public function getCombinedResult() {
		global $interface;
		$formats = $this->getFormats();
		$id = $this->getUniqueID();
		
		$interface->assign('summId', $id);
		$interface->assign('summShortId', $id);
		$interface->assign('module', $this->getModule());
		$interface->assign('summFormats', $formats);
		$interface->assign('summUrl', $this->getLinkUrl());
		$interface->assign('summTitle', $this->getTitle());
		$interface->assign('summAuthor', $this->getAuthor());
		$interface->assign('summSourceDatabase', $this->getSourceDatabase());
		$interface->assign('bookCoverUrl', $this->getBookcoverUrl('small'));
		$interface->assign('bookCoverUrlMedium', $this->getBookcoverUrl('medium'));
		$interface->assign('summPublicationDates', $this->getPublicationDate());
		$interface->assign('summHasFullText', $this->getFullText());

		return 'RecordDrivers/Gale/combinedResult.tpl';
	}

	public function getSpotlightResult(CollectionSpotlight $collectionSpotlight, string $index) {
		global $interface;

		$interface->assign('showRatings', $collectionSpotlight->showRatings);
		$interface->assign('key', $index);
		$interface->assign('title', $this->getTitle());
		$interface->assign('author', $this->getAuthor());
		$interface->assign('description', $this->getDescription());
		$interface->assign('shortId', $this->getUniqueID());
		$interface->assign('id', $this->getUniqueID());
		$interface->assign('titleURL', $this->getLinkUrl());

		if ($collectionSpotlight->coverSize == 'small') {
			$imageUrl = $this->getBookcoverUrl('small');
		} else {
			$imageUrl = $this->getBookcoverUrl('medium');
		}
		$interface->assign('imageUrl', $imageUrl);

		if ($collectionSpotlight->showRatings) {
			$interface->assign('ratingData', null);
			$interface->assign('showNotInterested', false);
		}

		$result = [
			'title' => $this->getTitle(),
			'author' => $this->getAuthor(),
		];
		if ($collectionSpotlight->style == 'text-list') {
			$result['formattedTextOnlyTitle'] = $interface->fetch('CollectionSpotlight/formattedTextOnlyTitle.tpl');
		} elseif ($collectionSpotlight->style == 'horizontal-carousel') {
			$result['formattedTitle'] = $interface->fetch('CollectionSpotlight/formattedHorizontalCarouselTitle.tpl');
		} else {
			$result['formattedTitle'] = $interface->fetch('CollectionSpotlight/formattedTitle.tpl');
		}

		return $result;
	}

	public function getStaffView() {
		return null;
	}

	public function getListEntry($listId = null, $allowEdit = true) {
		$this->getSearchResult('list');

		//Switch template
		return 'RecordDrivers/Gale/listEntry.tpl';
	}
	public function getId() {
		return $this->getUniqueID();
	}

	public function getUniqueID() {
		$identifier = $this->getPrimaryIdentifier();
		return $identifier;
	}

	public function getPermanentId() {
		return $this->getUniqueID();
	}

	public function getTitle() {
		$title = $this->record->xpath('dc:title');
		return (string)$title[0] ?? "Unknown";
	}

	public function getAuthor() {
		$authors = $this->record->xpath('dc:creator') ?: [];
		return implode(', ', array_map('strval', $authors));
	}

	public function getDescription() {
		return '';
	}

	public function getMoreDetailsOptions() : array {
		return [];
	}

	public function getPrimaryIdentifier(): ?string {
		$identifier = $this->getRecordUrl();
		if (preg_match('~/doc/([^/]+)/~', $identifier, $matches)) {
			$recordId = $matches[1];
		}
		return $recordId;
	}

	public function getFormats() {
		$format = $this->record->xpath('dc:type');
		return (string)$format[0] ?? "Unknown";
	}
	public function getSourceDatabase() {
		$relation = $this->record->xpath('dc:relation') ?: [];
		return implode(', ', array_map('strval', $relation));
	}

	public function getPublicationDate() {
		$publicationDate = $this->record->xpath('dc:date');
		if (empty($publicationDate)) {
			return null;
		}
		$year = substr($publicationDate[0], 0, 4);
		$month = substr($publicationDate[0], 4, 2);
		$day = substr($publicationDate[0], 6, 2);

		if ($month == '00' && $day == '00') {
			return $year;
		}
		if ($day == '00') {
			return $month . '/' . $year;
		}
		return $month . '/' . $day . '/' . $year;
	}
	public function getExploreMoreInfo() {
		return [];
	}

	public function getFullText() {
		$rights = $this->record->xpath('dc:rights');
		if (!empty($rights)) {
			foreach ($rights as $right) {
				$value = (string)$right;
				if (stripos($value, 'Text: Yes') !== false) {
					return 'Yes';
				}
			}
			return 'No';
		}
		return 'No';
	}

}
