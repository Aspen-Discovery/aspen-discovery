<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';
require_once ROOT_DIR . '/sys/DataObjectUtil.php';

class Admin_CreateCollectionSpotlight extends Action {

	function launch(): bool|array {
		$user = UserAccount::getLoggedInUser();

		$source = $_REQUEST['source'];
		$sourceId = $_REQUEST['id'];
		if (empty($user) || empty($source) || empty($sourceId)) {
			return [
				'success' => false,
				'message' => "You must be logged in and provide information about the search to create a spotlight.",
			];
		}

		$existingSpotlightId = $_REQUEST['collectionSpotlightId'] ?? -1;
		$spotlightName = $_REQUEST['spotlightName'] ?? '';
		$replaceIds = $_REQUEST['collectionSpotlightListId'] ?? '';
		$replaceListIds = explode(".", $replaceIds);
		$replaceListId = $replaceListIds[1] ?? '';

		$collectionSpotlight = new CollectionSpotlight();
		if ($existingSpotlightId == -1) {
			$collectionSpotlight->name = $spotlightName;
			if (UserAccount::userHasPermission('Administer All Collection Spotlights')) {
				$collectionSpotlight->libraryId = -1;
			} else {
				$userLibrary = Library::getPatronHomeLibrary();
				if (!$userLibrary) {
					$libraries = Library::getLibraryList(true);
					if (empty($libraries)) {
						return [
							'success' => false,
							'message' => "Your account has permission to Administer Library Collection Spotlights, but it does not have a home library set."
						];
					}else{
						$collectionSpotlight->libraryId = array_keys($libraries)[0];
					}
				}else{
					$collectionSpotlight->libraryId = $userLibrary->libraryId;
				}
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
			$collectionSpotlight->numTitlesToShow = 25;
			$collectionSpotlight->insert();
		} else {
			$collectionSpotlight->id = $existingSpotlightId;
			$collectionSpotlight->find(true);
		}

		$spotlightList = new CollectionSpotlightList();
		if (!isset($_REQUEST['replaceExisting'])) {
			//Add the list to the spotlight
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
					return [
						'success' => false,
						'message' => "Sorry, this search is too complex to use for creating a spotlight.",
					];
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
					return [
						'success' => false,
						'message' => "Sorry, this search is too complex to use for creating a spotlight.",
					];
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
			$listCount = $collectionSpotlight->getNumLists();
			if ($listCount == 1) {
				$collectionSpotlight->name = $spotlightName;
				$collectionSpotlight->update();
			}
			$spotlightList->name = $spotlightName;
			$spotlightList->update();
		}

		header("Location: /Admin/CollectionSpotlights?objectAction=view&id={$collectionSpotlight->id}");
		return false;
	}

	function getInitializationJs(): string {
		return 'return AspenDiscovery.CollectionSpotlights.updateSpotlightFields();';
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/BrowseCategories', 'Browse Categories');
		return $breadcrumbs;
	}
}