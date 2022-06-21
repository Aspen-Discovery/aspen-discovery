<?php
/**
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
require_once ROOT_DIR . '/services/API/ItemAPI.php';
require_once ROOT_DIR . '/services/API/ListAPI.php';
require_once ROOT_DIR . '/services/API/SearchAPI.php';
require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';

class AnodeAPI extends Action
{

	function launch()
	{
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			$result = $this->$method();
			$output = json_encode(array('result' => $result));
			require_once ROOT_DIR . '/sys/SystemLogging/APIUsage.php';
			APIUsage::incrementStat('AnodeAPI', $method);
		} else {
			$output = json_encode(array('error' => 'invalid_method'));
		}
		echo $output;
	}

	/**
	 * Returns information about the titles within a list
	 * according to the parameters of
	 * Anode Compatibility API Description at
	 * https://docs.google.com/document/d/1N_LiYaK56WLWXTIxzDvmwdVQ3WgogopTnHHixKc_2zk
	 *
	 * @param string $listId - The list to show
	 * @param integer $numGroupedWorksToShow - the maximum number of titles that should be shown
	 * @return array
	 * @noinspection PhpUnused
	 */
	function getAnodeListGroupedWorks($listId = NULL, $numGroupedWorksToShow = NULL)
	{
		if (!$listId) {
			$listId = $_REQUEST['listId'];
		}
		if (isset($_GET['branch']) && in_array($_GET['branch'], array("bl","bx","ep","ma","se"))) { // Nashville hardcoded
			$branch = $_GET['branch'];
		} else {
			$branch = "catalog";
		}
		$listAPI = new ListAPI();
		$result = $listAPI->getListTitles($listId, $numGroupedWorksToShow);
		$result = $this->getAnodeGroupedWorks($result, $branch);
		return $result;
	}

	/**
	 * Returns information about a grouped work's related titles ("More Like This")
	 *
	 * @param string $id - The initial grouped work
	 * @return    array
	 * @noinspection PhpUnused
	 */
	function getAnodeRelatedGroupedWorks($id = NULL)
	{
		global $configArray;
		if (!isset($id)) {
			$id = $_REQUEST['id'];
		}
		if (isset($_GET['branch']) && in_array($_GET['branch'], array("bl","bx","ep","ma","se"))) { // Nashville hardcoded
			$branch = $_GET['branch'];
		} else {
			$branch = "catalog";
		}
		//Load Similar titles (from Solr)
		$url = $configArray['Index']['url'];
		$systemVariables = SystemVariables::getSystemVariables();
		if ($systemVariables->searchVersion == 1){
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';
			$db = new GroupedWorksSolrConnector($url);
		}else{
			require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector2.php';
			$db = new GroupedWorksSolrConnector2($url);
		}
		$similar = $db->getMoreLikeThis($id);
		if (isset($similar) && count($similar['response']['docs']) > 0) {
			$similarTitles = array();

			foreach ($similar['response']['docs'] as $key => $similarTitle) {
				$similarTitles['titles'][] = $similarTitle;
			}
			$result = $this->getAnodeGroupedWorks($similarTitles, $branch);
		} else {
			$result = ['titles' => []];
		}


		return $result;
	}

	function getAnodeGroupedWorks($result, $branch)
	{
		if (!isset($result['titles'])) {
			$result['titles'] = array();
		} else {
			//Rebuild the titles array since we don't want indexes to have gaps in them (so we don't convert the array to an object in json)
			$titles = $result['titles'];
			$result['titles'] = [];
			foreach ($titles as &$groupedWork) {
				$itemAPI = new ItemAPI();
				$_GET['id'] = $groupedWork['id'];
				$groupedWorkRecord = $itemAPI->loadSolrRecord($groupedWork['id']);
				if (isset($groupedWorkRecord['title_display'])) {
					$groupedWork['title'] = $groupedWorkRecord['title_display'];
				}
				if (!isset($groupedWorkRecord['image'])) {
					$groupedWork['image'] = '/bookcover.php?id=' . $groupedWork['id'] . '&size=medium&type=grouped_work';
				}
				if (isset($groupedWorkRecord['display_description'])) {
					$groupedWork['description'] = $groupedWorkRecord['display_description'];
				}
				if (isset($groupedWorkRecord['rating'])) {
					$groupedWork['rating'] = $groupedWorkRecord['rating'];
				}
				if (isset($groupedWorkRecord['series'][0])) {
					$groupedWork['series'] = $groupedWorkRecord['series'][0];
				}
				if (isset($groupedWorkRecord['genre'])) {
					$groupedWork['genre'] = $groupedWorkRecord['genre'];
				}
				if (isset($groupedWorkRecord['publisher'])) {
					$groupedWork['publisher'] = $groupedWorkRecord['publisher'];
				}
				if (isset($groupedWorkRecord['language'])) {
					$groupedWork['language'] = $groupedWorkRecord['language'];
				}
				if (isset($groupedWorkRecord['literary_form'])) {
					$groupedWork['literary_form'] = $groupedWorkRecord['literary_form'];
				}
				if (isset($groupedWorkRecord['author2-role'])) {
					$groupedWork['contributors'] = $groupedWorkRecord['author2-role'];
				}
				if (isset($groupedWorkRecord['edition'])) {
					$groupedWork['edition'] = $groupedWorkRecord['edition'];
				}
				if (isset($groupedWorkRecord['publishDateSort'])) {
					$groupedWork['published'] = $groupedWorkRecord['publishDateSort'];
				}
				if (isset($groupedWorkRecord['econtent_source_' . $branch])) {
					$groupedWork['econtent_source'] = $groupedWorkRecord['econtent_source_' . $branch];
				}
				if (isset($groupedWorkRecord['physical'])) {
					$groupedWork['physical'] = $groupedWorkRecord['physical'];
				}
				if (isset($groupedWorkRecord['isbn'])) {
					$groupedWork['isbn'] = $groupedWorkRecord['isbn'];
				}
				$groupedWork['availableHere'] = false;

// TO DO: include MPAA ratings, Explicit Lyrics advisory, etc.
//				$groupedWork['contentRating'] = $groupedWorkRecord['???'];

				$groupedWorkDriver = new GroupedWorkDriver($groupedWork['id']);

				$relatedRecords = $groupedWorkDriver->getRelatedRecords();
				foreach ($relatedRecords as $relatedRecord){
					foreach ($relatedRecord->getItems() as $item){
						$groupedWork['items'][] = array(
							'01_bibIdentifier' => $relatedRecord->id,
							'02_itemIdentifier' => $item->itemId,
							'03_bibFormat' => $relatedRecord->format,
							'04_bibFormatCategory' => $relatedRecord->formatCategory,
							'05_statusGrouped' => $item->groupedStatus,
							'06_status' => $item->status,
							'07_availableHere' => $item->locallyOwned && $item->available,
							'08_itemShelfLocation' => $item->shelfLocation,
							'09_itemLocationCode' => $item->locationCode,
							'10_itemCallNumber' => $item->callNumber,
							'11_available' => $item->available
						);
						if ($item->locallyOwned && $item->available){
							$groupedWork['availableHere'] = true;
						}
					}
				}

				unset($groupedWork['length']);
				unset($groupedWork['ratingData']);
				unset($groupedWork['shortId']);
				unset($groupedWork['small_image']);
				unset($groupedWork['titleURL']);
				unset($groupedWork['publishDate']);
				unset($groupedWork['title_display']);
				unset($groupedWork['title_short']);
				unset($groupedWork['title_full']);
				unset($groupedWork['author_display']);
				unset($groupedWork['publisherStr']);
				unset($groupedWork['topic_facet']);
				unset($groupedWork['subject_facet']);
				unset($groupedWork['lexile_score']);
				unset($groupedWork['accelerated_reader_interest_level']);
				unset($groupedWork['primary_isbn']);
				unset($groupedWork['display_description']);
				unset($groupedWork['auth_author2']);
				unset($groupedWork['author2-role']);
				unset($groupedWork['series_with_volume']);
				unset($groupedWork['literary_form_full']);
				unset($groupedWork['record_details']);
				unset($groupedWork['item_details']);
				unset($groupedWork['accelerated_reader_point_value']);
				unset($groupedWork['accelerated_reader_reading_level']);

				$result['titles'][] = $groupedWork;
			}
		}
		return $result;
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}
