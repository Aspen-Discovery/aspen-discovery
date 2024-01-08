<?php

abstract class SearchObject_SummonBaseSearcher {

    // SEARCH PARAMETERS
	protected $view = null;
	protected $defaultView = 'list';
	// Search terms
	protected $searchTerms = [];
	// Sorting
	protected $sort = null;
	protected $defaultSort = 'relevance';
	protected $defaultSortByType = [];

    /**
	 * Add view mode to the object based on the $_REQUEST super global.
	 *
	 * @access  protected
	 */
	protected function initView() {
		if (!empty($this->view)) { //return view if it has already been set.
			return;
		}
		// Check for a view parameter in the url.
		if (isset($_REQUEST['view'])) {
			if ($_REQUEST['view'] == 'rss') {
				// we don't want to store rss in the Session variable
				$this->view = 'rss';
			} elseif ($_REQUEST['view'] == 'excel') {
				// we don't want to store excel in the Session variable
				$this->view = 'excel';
			} else {
				// store non-rss views in Session for persistence
				$validViews = $this->getViewOptions();
				// make sure the url parameter is a valid view
//				if (in_array($_REQUEST['view'], array_keys($validViews))) {
				if (in_array($_REQUEST['view'], $validViews)) { // currently using a simple array listing the views (not listed in the keys)
					$this->view = $_REQUEST['view'];
					$_SESSION['lastView'] = $this->view;
				} else {
					$this->view = $this->defaultView;
				}
			}
		} elseif (isset($_SESSION['lastView']) && !empty($_SESSION['lastView'])) {
			// if there is nothing in the URL, check the Session variable
			$this->view = $_SESSION['lastView'];
		} else {
			// otherwise load the default
			$this->view = $this->defaultView;
		}
	}

    // /**
	//  * Add page number to the object based on the $_REQUEST super global.
	//  *
	//  * @access  protected
	//  */
	// protected function initPage() {
	// 	if (isset($_REQUEST['page'])) {
	// 		$page = $_REQUEST['page'];
	// 		if (is_array($page)) {
	// 			$page = array_pop($page);
	// 		}
	// 		$this->page = strip_tags($page);
	// 	}
	// 	$this->page = intval($this->page);
	// 	if ($this->page < 1) {
	// 		$this->page = 1;
	// 	}
	// }

	// function setPage($page) {
	// 	$this->page = intval($page);
	// 	if ($this->page < 1) {
	// 		$this->page = 1;
	// 	}
	// }

    protected function getViewOptions() {
		if (isset($this->viewOptions) && is_array($this->viewOptions)) {
			return $this->viewOptions;
		} else {
			return [];
		}
	}

}