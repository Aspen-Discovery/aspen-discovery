<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/RecordDrivers/SeriesRecordDriver.php';

class Series_Home extends Action {

	function __construct($isStandalonePage = false) {
		parent::__construct($isStandalonePage);

		// Hide Covers when the user has set that setting on an Account Page
		$this->setShowCovers();
	}

	/** @noinspection PhpUnused */
	function reloadCover() : array {
		$seriesId = $_REQUEST['id'];
		$series = new Series();
		$series->id = $seriesId;

		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->setRecordType('series');
		$bookCoverInfo->setRecordId($series->id);
		if ($bookCoverInfo->find(true)) {
			$bookCoverInfo->setImageSource('');
			$bookCoverInfo->setThumbnailLoaded(0);
			$bookCoverInfo->setMediumLoaded(0);
			$bookCoverInfo->setLargeLoaded(0);
			$bookCoverInfo->update();
		}

		return [
			'success' => true,
			'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.',
		];
	}

	function launch() : void {
		global $interface;

		// Fetch the Series object
		$listId = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/Series/Series.php';
		require_once ROOT_DIR . '/sys/Series/SeriesMember.php';
		$series = new Series();
		$series->id = $listId;

		//Determine the sort options
		if (isset($_REQUEST['sort'])) {
			$activeSort = $_REQUEST['sort'];
		}

		if ($series->find(true)) {
			if (empty($activeSort)) {
				$activeSort = $series->getDefaultSortMethodName();
			}

			global $library;
			$groupedWorkDisplaySettings = $library->getGroupedWorkDisplaySettings();
			$interface->assign('formatDisplayStyle', $groupedWorkDisplaySettings->formatDisplayStyle);
			$interface->assign('hideManifestationsInMobileView', $groupedWorkDisplaySettings->hideManifestationsInMobileView);

			// Send list to template so title/description can be displayed:
			$interface->assign('series', $series);
			$authors = explode("|", $series->author);
			$interface->assign('authors', $authors);

			$seriesRecordDriver = new SeriesRecordDriver($listId);
			if ($seriesRecordDriver->isValid()) {
				$interface->assign('cover', $seriesRecordDriver->getBookcoverUrl('medium'));
			}else{
				//This series has not been indexed yet
				$bookCoverUrl = "/bookcover.php?type=series&id=$listId&size=medium";
				$interface->assign('cover', $bookCoverUrl);
			}

			$this->buildListForDisplay($series, $activeSort);

			$template = 'seriesMembers.tpl';

		} else {
			$template = 'invalidSeries.tpl';
		}

		$this->display($template, $series->displayName ?? translate([
			'text' => 'Series',
			'isPublicFacing' => true,
		]), '', false);
	}

	/**
	 * Assign all necessary values to the interface.
	 * @param Series $list
	 * @param string $sortName
	 *
	 * @access  public
	 */
	public function buildListForDisplay(Series $list, string $sortName) : void {
		global $interface;

		$queryParams = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if ($queryParams == null) {
			$queryParams = [];
		} else {
			$queryParamsTmp = explode("&", $queryParams);
			$queryParams = [];
			foreach ($queryParamsTmp as $param) {
				if (str_contains($param, '=')) {
					[
						$name,
						$value,
					] = explode("=", $param);
				}else{
					$name = $param;
					$value = '';
				}

				$queryParams[$name] = $value;
			}
		}

		$recordsPerPage = isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize'])) ? $_REQUEST['pageSize'] : 20;
		$totalRecords = $list->numScopedTitlesInSeries();
		$page = $_REQUEST['page'] ?? 1;
		$startRecord = ($page - 1) * $recordsPerPage;
		if ($startRecord < 0) {
			$startRecord = 0;
		}
		$endRecord = $page * $recordsPerPage;
		if ($endRecord > $totalRecords) {
			$endRecord = $totalRecords;
		}
		$pageInfo = [
			'resultTotal' => $totalRecords,
			'startRecord' => $startRecord,
			'endRecord' => $endRecord,
			'perPage' => $recordsPerPage,
		];
		$resourceList = $list->getSeriesRecords($startRecord, $recordsPerPage, 'html', $sortName);
		$interface->assign('resourceList', $resourceList);

		// Set up paging of list contents:
		$interface->assign('recordCount', $pageInfo['resultTotal']);
		$interface->assign('recordStart', $pageInfo['startRecord']);
		$interface->assign('recordEnd', $pageInfo['endRecord']);
		$interface->assign('recordsPerPage', $pageInfo['perPage']);

		$link = $_SERVER['REQUEST_URI'];

		$sortOptions = [
			'volumeAsc' => [
				'desc' => 'Volume',
				'selected' => $sortName == 'volume',
				'sortUrl' => "/Series/$list->id?sort=volume",
			],
			'displayName' => [
				'desc' => 'Title',
				'selected' => $sortName == 'displayName' || $sortName == 'title',
				'sortUrl' => "/Series/$list->id?sort=displayName",
			],
			'pubDate' => [
				'desc' => 'Publication Date',
				'selected' => $sortName == 'pubDate',
				'sortUrl' => "/Series/$list->id?sort=pubDate",
			],
			'pubDateDesc' => [
				'desc' => 'Publication Date Descending',
				'selected' => $sortName == 'pubDate desc',
				'sortUrl' => "/Series/$list->id?sort=pubDate+desc",
			],
		];
		if ($list->sortMethod == 5) {
			$sortOptions['custom'] = [
				'desc' => 'Default Order',
				'selected' => $sortName == 'custom',
				'sortUrl' => "/Series/$list->id?sort=custom",
			];
		}

		$interface->assign('sortList', $sortOptions);

		$options = [
			'totalItems' => $pageInfo['resultTotal'],
			'perPage' => $pageInfo['perPage'],
			'fileName' => $link,
			'append' => false,
		];
		require_once ROOT_DIR . '/sys/Pager.php';
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->lastSearch)) {
			$breadcrumbs[] = new Breadcrumb($this->lastSearch, 'Series Search Results');
		} else {
			$breadcrumbs[] = new Breadcrumb('', 'Series');
		}
		return $breadcrumbs;
	}
}
