<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/services/MyResearch/lib/FavoriteHandler.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Suggestions.php';

/**
 * MyResearch Home Page
 *
 * This controller needs some cleanup and organization.
 *
 * @version  $Revision: 1.27 $
 */
class SuggestedTitles extends MyAccount
{

	function launch()
	{
		global $interface;
		global $configArray;
		global $timer;

		$suggestions = Suggestions::getSuggestions();
		$timer->logTime("Loaded suggestions");

		// Setup Search Engine Connection
		$url = $configArray['Index']['url'];
		/** @var SearchObject_GroupedWorkSearcher $solrDb */
		$solrDb = new GroupedWorksSolrConnector($url);

		$resourceList = array();
		$curIndex = 0;
		if (is_array($suggestions)) {
			$suggestionIds = array();
			foreach($suggestions as $suggestion) {
				$suggestionIds[] = $suggestion['titleInfo']['id'];
			}
			$records = $solrDb->getRecords($suggestionIds);
			foreach($records as $record) {
				$interface->assign('resultIndex', ++$curIndex);
				/** @var GroupedWorkDriver $recordDriver */
				$recordDriver = RecordDriverFactory::initRecordDriver($record);
				$resourceEntry = $interface->fetch($recordDriver->getSearchResult());
				$resourceList[] = $resourceEntry;
			}
		}
		$timer->logTime("Loaded results for suggestions");
		$interface->assign('resourceList', $resourceList);

		//Check to see if the user has rated any titles
		$user = UserAccount::getLoggedInUser();
		$interface->assign('hasRatings', $user->hasRatings());

		$this->display('suggestedTitles.tpl', 'Recommended for You');
	}

}