<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/UserList.php';
require_once ROOT_DIR . '/services/MyResearch/lib/FavoriteHandler.php';

class CiteList extends Action {
	function launch() {
		global $interface;

		//Get all lists for the user

		// Fetch List object
		if (isset($_REQUEST['listId'])){
			/** @var UserList $list */
			$list = new UserList();
			$list->id = $_GET['listId'];
			$list->find(true);
		}
		$interface->assign('favList', $list);

		// Get all titles on the list
//		$favorites = $list->getListEntries();
//		$favList = new FavoriteHandler($favorites, null, $list->id, false);
		//TODO: test this
		$favList = new FavoriteHandler($list, null, false);
		$citationFormat = $_REQUEST['citationFormat'];
		$citationFormats = CitationBuilder::getCitationFormats();
		$interface->assign('citationFormat', $citationFormats[$citationFormat]);
		$citations = $favList->getCitations($citationFormat);

		$interface->assign('citations', $citations);

		// Display Page
		$interface->assign('listId', $list->id);
		$this->display('listCitations.tpl', 'Citations for ' . $list->title);
	}
}