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
		$bookCoverInfo->recordType = 'series';
		$bookCoverInfo->recordId = $series->id;
		if ($bookCoverInfo->find(true)) {
			$bookCoverInfo->imageSource = '';
			$bookCoverInfo->thumbnailLoaded = 0;
			$bookCoverInfo->mediumLoaded = 0;
			$bookCoverInfo->largeLoaded = 0;
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
		if (empty($activeSort)) {
			$activeSort = 'volume asc';
		}

		if ($series->find(true)) {
			// Send list to template so title/description can be displayed:
			$interface->assign('series', $series);
			$authors = explode("|", $series->author);
			$interface->assign('authors', $authors);

			$seriesRecordDriver = new SeriesRecordDriver($listId);
			$interface->assign('cover', $seriesRecordDriver->getBookcoverUrl('medium'));

			$this->buildListForDisplay($series, $activeSort);

			$template = 'seriesMembers.tpl';

		} else {
			$template = 'invalidReserve.tpl';
		}

		$this->display($template, isset($series->displayName) ? $series->displayName : translate([
			'text' => 'Series',
			'isPublicFacing' => true,
		]), '', false);
	}

	/**
	 * Assign all necessary values to the interface.
	 *
	 * @access  public
	 * @param Series $list
	 */
	public function buildListForDisplay(Series $list, $sortName = "volume asc") {
		global $interface;

		$queryParams = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if ($queryParams == null) {
			$queryParams = [];
		} else {
			$queryParamsTmp = explode("&", $queryParams);
			$queryParams = [];
			foreach ($queryParamsTmp as $param) {
				[
					$name,
					$value,
				] = explode("=", $param);
				$queryParams[$name] = $value;
			}
		}

		$recordsPerPage = isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize'])) ? $_REQUEST['pageSize'] : 20;
		$totalRecords = $list->numTitlesInSeries();
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
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
		if (preg_match('/[&?]page=/', $link)) {
			$link = preg_replace("/page=\\d+/", "page=%d", $link);
		} elseif (strpos($link, "?") > 0) {
			$link .= "&page=%d";
		} else {
			$link .= "?page=%d";
		}

		$sortOptions = [
			'displayName' => [
				'desc' => 'Title',
				'selected' => $sortName == 'displayName',
				'sortUrl' => "/Series/{$list->id}?" . http_build_query(array_merge($queryParams, ['sort' => 'displayName'])),
			],
			'pubDate' => [
				'desc' => 'Publication Date',
				'selected' => $sortName == 'pubDate',
				'sortUrl' => "/Series/{$list->id}?" . http_build_query(array_merge($queryParams, ['sort' => 'pubDate'])),
			],
			'volumeAsc' => [
				'desc' => 'Volume Number Ascending',
				'selected' => $sortName == 'volume asc',
				'sortUrl' => "/Series/{$list->id}?" . http_build_query(array_merge($queryParams, ['sort' => 'volume asc'])),
			],
			'volumeDesc' => [
				'desc' => 'Volume Number Descending',
				'selected' => $sortName == 'volumed desc',
				'sortUrl' => "/Series/{$list->id}?" . http_build_query(array_merge($queryParams, ['sort' => 'volume desc'])),
			],
		];

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