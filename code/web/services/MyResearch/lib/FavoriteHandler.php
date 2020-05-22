<?php
/**
 *
 * Copyright (C) Villanova University 2010.
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

/**
 * FavoriteHandler Class
 *
 * This class contains shared logic for displaying lists of favorites (based on
 * earlier logic duplicated between the MyAccount/Home and MyAccount/MyList
 * actions).
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class FavoriteHandler
{
	/**
	 * Assign all necessary values to the interface.
	 *
	 * @access  public
	 */
	public function buildListForDisplay(UserList $list, $allEntries, $allowEdit = false, $sortName = 'dateAdded')
	{
		global $interface;

		$recordsPerPage = isset($_REQUEST['pageSize']) && (is_numeric($_REQUEST['pageSize'])) ? $_REQUEST['pageSize'] : 20;
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$startRecord = ($page - 1) * $recordsPerPage + 1;
		if ($startRecord < 0){
			$startRecord = 0;
		}
		$endRecord = $page * $recordsPerPage;
		if ($endRecord > count($allEntries)){
			$endRecord = count($allEntries);
		}
		$pageInfo = array(
			'resultTotal' => count($allEntries),
			'startRecord' => $startRecord,
			'endRecord'   => $endRecord,
			'perPage'     => $recordsPerPage
		);

		$queryParams = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
		if ($queryParams == null){
			$queryParams = [];
		}else{
			$queryParamsTmp = explode("&", $queryParams);
			$queryParams = [];
			foreach ($queryParamsTmp as $param) {
				list($name, $value) = explode("=", $param);
				if ($name != 'sort'){
					$queryParams[$name] = $value;
				}
			}
		}
		$sortOptions = array(
			'title' => [
				'desc' => 'Title',
				'selected' => $sortName == 'title',
				'sortUrl' => "/MyAccount/MyList/{$list->id}?" . http_build_query(array_merge($queryParams, ['sort' => 'title']))
			],
			'dateAdded' => [
				'desc' => 'Date Added',
				'selected' => $sortName == 'dateAdded',
				'sortUrl' => "/MyAccount/MyList/{$list->id}?" . http_build_query(array_merge($queryParams, ['sort' => 'dateAdded']))
			],
			'recentlyAdded' => [
				'desc' => 'Recently Added',
				'selected' => $sortName == 'recentlyAdded',
				'sortUrl' => "/MyAccount/MyList/{$list->id}?" . http_build_query(array_merge($queryParams, ['sort' => 'recentlyAdded']))
			],
			'custom' => [
				'desc' => 'User Defined',
				'selected' => $sortName == 'custom',
				'sortUrl' => "/MyAccount/MyList/{$list->id}?" . http_build_query(array_merge($queryParams, ['sort' => 'custom']))
			],
		);

		$interface->assign('sortList', $sortOptions);
		$interface->assign('userSort', ($sortName == 'custom')); // switch for when users can sort their list

		$resourceList = $list->getListRecords($startRecord, $recordsPerPage, $allowEdit, 'html');
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

}