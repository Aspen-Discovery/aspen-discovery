<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';
require_once ROOT_DIR . '/sys/DataObjectUtil.php';

class Admin_CreateCollectionSpotlight extends Action
{
	/** @noinspection PhpUnused */
	function getInitializationJs() : string
	{
		return 'return AspenDiscovery.CollectionSpotlights.updateSpotlightFields();';
	}
	
	function launch()
	{
		$user = UserAccount::getLoggedInUser();

		$source = $_REQUEST['source'];
		$sourceId = $_REQUEST['id'];
		if (!empty($user) && !empty($source) && !empty($sourceId)) { // make sure we received this input & the user is logged in
			$existingSpotlightId = isset($_REQUEST['collectionSpotlightId']) ? $_REQUEST['collectionSpotlightId'] : -1;
			$spotlightName = isset($_REQUEST['spotlightName']) ? $_REQUEST['spotlightName'] : '';
			$replaceExisting = isset($_REQUEST['replaceExisting']) ? $_REQUEST['replaceExisting'] : '';
			$replaceIds = isset($_REQUEST['collectionSpotlightListId']) ? $_REQUEST['collectionSpotlightListId'] : '';
			$replaceListIds = explode(".", $replaceIds);
			$replaceListId = $replaceListIds[0];

			if ($existingSpotlightId == -1) {
				$collectionSpotlight = new CollectionSpotlight();
				$collectionSpotlight->name = $spotlightName;
				if (UserAccount::userHasPermission('Administer All Collection Spotlights')) {
					//Get all spotlights for the library
					$userLibrary = Library::getPatronHomeLibrary();
					$collectionSpotlight->libraryId = $userLibrary->libraryId;
				} else {
					$collectionSpotlight->libraryId = -1;
				}
				$collectionSpotlight->customCss = '';
				$collectionSpotlight->showTitle = 1;
				$collectionSpotlight->showAuthor = 0;
				$collectionSpotlight->showRatings = 0;
				$collectionSpotlight->style = 'horizontal-carousel';
				$collectionSpotlight->autoRotate = 0;
				$collectionSpotlight->coverSize = 'medium';
				$collectionSpotlight->description = '';
				$collectionSpotlight->showTitleDescriptions = 1;
				$collectionSpotlight->onSelectCallback = '';
				$collectionSpotlight->listDisplayType = 'tabs';
				$collectionSpotlight->showMultipleTitles = 1;
				$collectionSpotlight->insert();
			} else {
				$collectionSpotlight = new CollectionSpotlight();
				$collectionSpotlight->id = $existingSpotlightId;
				$collectionSpotlight->find(true);
			}

			if(!isset($_REQUEST['replaceExisting'])) {
				//Add the list to the spotlight
				$spotlightList = new CollectionSpotlightList();
				$spotlightList->collectionSpotlightId = $collectionSpotlight->id;
				$spotlightList->displayFor = 'all';
				if ($source == 'search') {
					$spotlightList->sourceListId = -1;
					$spotlightList->sourceCourseReserveId = -1;
					/** @var SearchObject_AbstractGroupedWorkSearcher $searchObj */
					$searchObj = SearchObjectFactory::initSearchObject();
					$searchObj->init();
					$searchObj = $searchObj->restoreSavedSearch($sourceId, false, true);
					if (!$spotlightList->updateFromSearch($searchObj)) {
						return array(
							'success' => false,
							'message' => "Sorry, this search is too complex to create a spotlight from."
						);
					}
				} elseif ($source == 'list') {
					$spotlightList->sourceListId = $sourceId;
					$spotlightList->sourceCourseReserveId = -1;
					$spotlightList->source = 'List';
				} elseif ($source == 'course_reserve') {
					$spotlightList->sourceListId = -1;
					$spotlightList->sourceCourseReserveId = $sourceId;
					$spotlightList->source = 'CourseReserve';
				}

				$spotlightList->name = $spotlightName;
				$spotlightList->weight = 0;
				$spotlightList->insert();
			} else {
				//Find the existing lists
				//Delete the existing lists
				//Add the list to the spotlight
				$spotlightList = new CollectionSpotlightList();
				$spotlightList->id = $replaceListId;
				$spotlightList->find();
				if ($source == 'search') {
					$spotlightList->sourceListId = -1;
					$spotlightList->sourceCourseReserveId = -1;
					/** @var SearchObject_AbstractGroupedWorkSearcher $searchObj */
					$searchObj = SearchObjectFactory::initSearchObject();
					$searchObj->init();
					$searchObj = $searchObj->restoreSavedSearch($sourceId, false, true);
					if (!$spotlightList->updateFromSearch($searchObj)) {
						return array(
							'success' => false,
							'message' => "Sorry, this search is too complex to create a spotlight from."
						);
					}
				} elseif ($source == 'list') {
					$spotlightList->sourceListId = $sourceId;
					$spotlightList->sourceCourseReserveId = -1;
					$spotlightList->source = 'List';
				} elseif ($source == 'course_reserve') {
					$spotlightList->sourceListId = -1;
					$spotlightList->sourceCourseReserveId = $sourceId;
					$spotlightList->source = 'CourseReserve';
				}
				$spotlightList->name = $spotlightName;
				$spotlightList->update();
			}

			//Redirect to the collection spotlight
			header("Location: /Admin/CollectionSpotlights?objectAction=view&id={$collectionSpotlight->id}");
			return false;
		}else{
			return array(
				'success' => false,
				'message' => "You must be logged in and provide information about the search to create the spotlight."
			);
		}
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/BrowseCategories', 'Browse Categories');
		return $breadcrumbs;
	}
}