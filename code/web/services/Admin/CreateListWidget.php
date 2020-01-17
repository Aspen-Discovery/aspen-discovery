<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/ListWidget.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/ListWidgetList.php';
require_once ROOT_DIR . '/sys/DataObjectUtil.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class CreateListWidget extends Action {
	function launch() 	{
		$user = UserAccount::getLoggedInUser();

		$source = $_REQUEST['source'];
		$sourceId = $_REQUEST['id'];
		if (!empty($user) && !empty($source) && !empty($sourceId)) { // make sure we received this input & the user is logged in
			$existingWidget = isset($_REQUEST['widgetId']) ? $_REQUEST['widgetId'] : -1;
			$widgetName     = isset($_REQUEST['widgetName']) ? $_REQUEST['widgetName'] : '';

			if ($existingWidget == -1) {
				$widget       = new ListWidget();
				$widget->name = $widgetName;
				if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('contentEditor') || UserAccount::userHasRole('libraryManager') || UserAccount::userHasRole('locationManager')) {
					//Get all widgets for the library
					$userLibrary       = Library::getPatronHomeLibrary();
					$widget->libraryId = $userLibrary->libraryId;
				} else {
					$widget->libraryId = -1;
				}
				$widget->customCss             = '';
				$widget->autoRotate            = 0;
				$widget->description           = '';
				$widget->showTitleDescriptions = 1;
				$widget->onSelectCallback      = '';
				$widget->fullListLink          = '';
				$widget->listDisplayType       = 'tabs';
				$widget->showMultipleTitles    = 1;
				$widget->insert();
			} else {
				$widget     = new ListWidget();
				$widget->id = $existingWidget;
				$widget->find(true);
			}

			//Make sure to save the search
			if ($source == 'search') {
				$searchObject     = new SearchEntry();
				$searchObject->id = $sourceId;
				$searchObject->find(true);
				$searchObject->saved = 1;
				$searchObject->user_id = $user->id;
				$searchObject->update();
			}

			//Add the list to the widget
			$widgetList               = new ListWidgetList();
			$widgetList->listWidgetId = $widget->id;
			$widgetList->displayFor   = 'all';
			$widgetList->source       = "$source:$sourceId";
			$widgetList->name         = $widgetName;
			$widgetList->weight       = 0;
			$widgetList->insert();

			//Redirect to the widget
			header("Location: /Admin/CollectionSpotlights?objectAction=view&id={$widget->id}" );
		}
	}
}