<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';
require_once ROOT_DIR . '/sys/DataObjectUtil.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class CreateCollectionSpotlight extends Action {
	function launch() 	{
		$user = UserAccount::getLoggedInUser();

		$source = $_REQUEST['source'];
		$sourceId = $_REQUEST['id'];
		if (!empty($user) && !empty($source) && !empty($sourceId)) { // make sure we received this input & the user is logged in
			$existingSpotlightId = isset($_REQUEST['collectionSpotlightId']) ? $_REQUEST['collectionSpotlightId'] : -1;
			$spotlightName     = isset($_REQUEST['spotlightName']) ? $_REQUEST['spotlightName'] : '';

			if ($existingSpotlightId == -1) {
				$collectionSpotlight       = new CollectionSpotlight();
				$collectionSpotlight->name = $spotlightName;
				if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('contentEditor') || UserAccount::userHasRole('libraryManager') || UserAccount::userHasRole('locationManager')) {
					//Get all spotlights for the library
					$userLibrary       = Library::getPatronHomeLibrary();
					$collectionSpotlight->libraryId = $userLibrary->libraryId;
				} else {
					$collectionSpotlight->libraryId = -1;
				}
				$collectionSpotlight->customCss             = '';
				$collectionSpotlight->autoRotate            = 0;
				$collectionSpotlight->description           = '';
				$collectionSpotlight->showTitleDescriptions = 1;
				$collectionSpotlight->onSelectCallback      = '';
				$collectionSpotlight->fullListLink          = '';
				$collectionSpotlight->listDisplayType       = 'tabs';
				$collectionSpotlight->showMultipleTitles    = 1;
				$collectionSpotlight->insert();
			} else {
				$collectionSpotlight     = new CollectionSpotlight();
				$collectionSpotlight->id = $existingSpotlightId;
				$collectionSpotlight->find(true);
			}

			//Make sure to save the search
			//TODO: No longer have to save the search
			if ($source == 'search') {
				$searchObject     = new SearchEntry();
				$searchObject->id = $sourceId;
				$searchObject->find(true);
				$searchObject->saved = 1;
				$searchObject->user_id = $user->id;
				$searchObject->update();
			}

			//Add the list to the spotlight
			$spotlightList               = new CollectionSpotlightList();
			$spotlightList->collectionSpotlightId = $collectionSpotlight->id;
			$spotlightList->displayFor   = 'all';
			$spotlightList->source       = "$source:$sourceId";
			$spotlightList->name         = $spotlightName;
			$spotlightList->weight       = 0;
			$spotlightList->insert();

			//Redirect to the collection spotlight
			header("Location: /Admin/CollectionSpotlights?objectAction=view&id={$collectionSpotlight->id}" );
		}
	}
}