<?php
require_once ROOT_DIR . '/Action.php';

class CourseReserves_Home extends Action {

	function __construct($isStandalonePage = false) {
		parent::__construct($isStandalonePage);

		// Hide Covers when the user has set that setting on an Account Page
		$this->setShowCovers();
	}

	/** @noinspection PhpUnused */
	function reloadCover(){
		$courseReserveId = $_REQUEST['id'];
		$courseReserve = new CourseReserve();
		$courseReserve->id = $courseReserveId;

		require_once ROOT_DIR . '/sys/Covers/BookCoverInfo.php';
		$bookCoverInfo = new BookCoverInfo();
		$bookCoverInfo->recordType = 'course_reserve';
		$bookCoverInfo->recordId = $courseReserve->id;
		if ($bookCoverInfo->find(true)){
			$bookCoverInfo->imageSource = '';
			$bookCoverInfo->thumbnailLoaded = 0;
			$bookCoverInfo->mediumLoaded = 0;
			$bookCoverInfo->largeLoaded = 0;
			$bookCoverInfo->update();
		}

		return array('success' => true, 'message' => 'Covers have been reloaded.  You may need to refresh the page to clear your local cache.');
	}

	function launch() {
		global $interface;

		// Fetch List object
		$listId = $_REQUEST['id'];
		require_once ROOT_DIR . '/sys/CourseReserves/CourseReserve.php';
		require_once ROOT_DIR . '/sys/CourseReserves/CourseReserveEntry.php';
		$courseReserve = new CourseReserve();
		$courseReserve->id = $listId;
		if ($courseReserve->find(true)){
			// Send list to template so title/description can be displayed:
			$interface->assign('courseReserve', $courseReserve);

			$this->buildListForDisplay($courseReserve);

			$template = 'courseReserve.tpl';

		}else{
			$template = 'invalidReserve.tpl';
		}

		$this->display($template, isset($courseReserve->title) ? $courseReserve->title : translate(['text' => 'Course Reserve', 'isPublicFacing'=>true]), '', false);
	}

	/**
	 * Assign all necessary values to the interface.
	 *
	 * @access  public
	 * @param CourseReserve $list
	 */
	public function buildListForDisplay(CourseReserve $list)
	{
		global $interface;

		$queryParams = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if ($queryParams == null){
			$queryParams = [];
		}else{
			$queryParamsTmp = explode("&", $queryParams);
			$queryParams = [];
			foreach ($queryParamsTmp as $param) {
				list($name, $value) = explode("=", $param);
				$queryParams[$name] = $value;
			}
		}

		$recordsPerPage = isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize'])) ? $_REQUEST['pageSize'] : 20;
		$totalRecords = $list->numTitlesOnReserve();
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$startRecord = ($page - 1) * $recordsPerPage;
		if ($startRecord < 0){
			$startRecord = 0;
		}
		$endRecord = $page * $recordsPerPage;
		if ($endRecord > $totalRecords){
			$endRecord = $totalRecords;
		}
		$pageInfo = array(
			'resultTotal' => $totalRecords,
			'startRecord' => $startRecord,
			'endRecord'   => $endRecord,
			'perPage'     => $recordsPerPage
		);
		$resourceList = $list->getCourseReserveRecords($startRecord , $recordsPerPage, 'html', null);
		$interface->assign('resourceList', $resourceList);

		// Set up paging of list contents:
		$interface->assign('recordCount', $pageInfo['resultTotal']);
		$interface->assign('recordStart', $pageInfo['startRecord']);
		$interface->assign('recordEnd',   $pageInfo['endRecord']);
		$interface->assign('recordsPerPage', $pageInfo['perPage']);

		$link = $_SERVER['REQUEST_URI'];
		if (preg_match('/[&?]page=/', $link)){
			$link = preg_replace("/page=\\d+/", "page=%d", $link);
		}else if (strpos($link, "?") > 0){
			$link .= "&page=%d";
		}else{
			$link .= "?page=%d";
		}
		$options = array('totalItems' => $pageInfo['resultTotal'],
			'perPage' => $pageInfo['perPage'],
			'fileName' => $link,
			'append'    => false);
		require_once ROOT_DIR . '/sys/Pager.php';
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Course Reserve');
		return $breadcrumbs;
	}
}