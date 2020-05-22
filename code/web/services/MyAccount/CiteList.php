<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';

class CiteList extends Action {
	function launch() {
		global $interface;

		//Get all lists for the user

		// Fetch List object
		if (isset($_REQUEST['listId'])){
			$list = new UserList();
			$list->id = $_GET['listId'];
			$list->find(true);
		}
		$interface->assign('favList', $list);

		// Get all titles on the list
		$citationFormat = $_REQUEST['citationFormat'];
		$citationFormats = CitationBuilder::getCitationFormats();
		$interface->assign('citationFormat', $citationFormats[$citationFormat]);
		$citations = $list->getListRecords(0, -1, false, 'citations', $citationFormat);

		$interface->assign('citations', $citations);

		// Display Page
		$interface->assign('listId', $list->id);
		$pageTitle = translate(['text' => 'Citations for %1%', '1'=>$list->title]);
		$this->display('listCitations.tpl', $pageTitle, 'Search/home-sidebar.tpl', false);
	}
}