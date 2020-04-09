<?php
require_once ROOT_DIR . '/JSON_Action.php';

class WebBuilder_AJAX extends JSON_Action
{
	function getPortalCellValuesForSource() {
		$result = [
			'success' => false,
			'message' => 'Unknown error'
		];

		$sourceType = $_REQUEST['sourceType'];
		switch ($sourceType){
		case 'basic_page':
		case 'basic_page_teaser':
			require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
			$list = [];
			$list[-1] = 'Select a page';

			$basicPage = new BasicPage();
			$basicPage->orderBy('title');
			$basicPage->find();

			while ($basicPage->fetch()){
				$list[$basicPage->id] = $basicPage->title;
			}

			$result = [
				'success' => true,
				'values' => $list
			];
			break;
		case 'collection_spotlight':
			require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
			$list = [];
			$list[-1] = 'Select a spotlight';

			$collectionSpotlight = new CollectionSpotlight();
			if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('contentEditor') || UserAccount::userHasRole('libraryManager') || UserAccount::userHasRole('locationManager')){
				$homeLibrary = Library::getPatronHomeLibrary();
				$collectionSpotlight->whereAdd('libraryId = ' . $homeLibrary->libraryId . ' OR libraryId = -1');
			}
			$collectionSpotlight->orderBy('name');
			$collectionSpotlight->find();
			while ($collectionSpotlight->fetch()){
				$list[$collectionSpotlight->id] = $collectionSpotlight->name;
			}

			$result = [
				'success' => true,
				'values' => $list
			];
			break;
		case 'event_calendar':
		case 'event_spotlight':
		default:
			$result['message'] = 'Unhandled Source Type ' . $sourceType;
		}

		return $result;
	}
}