<?php

require_once ROOT_DIR . '/Action.php';
require_once(ROOT_DIR . '/services/Admin/Admin.php');
require_once(ROOT_DIR . '/sys/LocalEnrichment/EditorialReview.php');
require_once ROOT_DIR . '/sys/DataObjectUtil.php';
require_once ROOT_DIR . '/sys/Pager.php';

class EditorialReview_Search extends Admin_Admin {

	function launch()
	{
		global $interface;
		global $configArray;

		$results = array();

		$editorialReview = new EditorialReview();

		$currentPage = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$recordsPerPage = 20;
		$searchUrl = $configArray['Site']['path'] . '/EditorialReview/Search';
		$searchParams = array();
		foreach ($_REQUEST as $key=>$value){
			if (!in_array($key, array('module', 'action', 'page'))){
				$searchParams[] = "$key=$value";
			}
		}
		$searchUrl = $searchUrl . '?page=%d&' . implode('&', $searchParams);
		$interface->assign('page', $currentPage);

		$editorialReview = new EditorialReview();
		if (isset($_REQUEST['sortOptions'])){
			$editorialReview->orderBy($_REQUEST['sortOptions']);
			$interface->assign('sort', $_REQUEST['sortOptions']);
		}
    $numTotalFiles = $editorialReview->count();
		$editorialReview->limit(($currentPage - 1) * $recordsPerPage, 20);
		$editorialReview->find();
		if ($editorialReview->N > 0){
			while ($editorialReview->fetch()){
				$results[] = clone $editorialReview;
			}
		}
		$interface->assign('results', $results);

		$options = array('totalItems' => $numTotalFiles,
                     'fileName'   => $searchUrl,
                     'perPage'    => $recordsPerPage);
		$pager = new Pager($options);
		$interface->assign('pageLinks', $pager->getLinks());

		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('search.tpl');

		$interface->display('layout.tpl');
	}

	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'contentEditor');
	}
}
