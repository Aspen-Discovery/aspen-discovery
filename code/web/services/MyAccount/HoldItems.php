<?php

require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/Action.php';

class HoldItems extends Action
{
    /** @var CatalogConnection */
	var $catalog;

	function launch()
	{
		global $configArray;

		try {
			$this->catalog = CatalogFactory::getCatalogConnectionInstance();
		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}

		// Check How to Process Hold
		if (method_exists($this->catalog->driver, 'placeHold')) {
			$this->placeHolds();
		} else {
			AspenError::raiseError(new AspenError('Cannot Process Place Hold - ILS Not Supported'));
		}
	}

	function placeHolds()
	{
		$selectedTitles = $_REQUEST['title'];
		global $interface;
		global $configArray;
		$user = UserAccount::getLoggedInUser();
		global $logger;

		$ids = array();
		foreach ($selectedTitles as $recordId => $itemNumber){
			$ids[] = $recordId;
		}
		$interface->assign('ids', $ids);

		$hold_message_data = array(
          'successful' => 'all',
          'campus' => $_REQUEST['campus'],
          'titles' => array()
		);

		$atLeast1Successful = false;
		foreach ($selectedTitles as $recordId => $itemNumber){
			$return = $this->catalog->placeItemHold($user, $recordId, $itemNumber, '');
			$hold_message_data['titles'][] = $return;
			if (!$return['success']){
				$hold_message_data['successful'] = 'partial';
			}else{
				$atLeast1Successful = true;
			}
			//Check to see if there are item level holds that need follow-up by the user
			if (isset($return['items'])){
				$hold_message_data['showItemForm'] = true;
			}
		}
		if (!$atLeast1Successful){
			$hold_message_data['successful'] = 'none';
		}

		$_SESSION['hold_message'] = $hold_message_data;

        $logger->log('No referrer set, but there is a message to show, go to the main holds page', Logger::LOG_NOTICE);
        header("Location: " . $configArray['Site']['path'] . '/MyResearch/Holds');
	}
}