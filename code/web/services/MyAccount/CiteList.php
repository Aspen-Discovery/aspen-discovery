<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

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