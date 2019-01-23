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

require_once ROOT_DIR . '/CatalogConnection.php';

require_once ROOT_DIR . '/Action.php';

class HoldItems extends Action
{
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
			PEAR_Singleton::raiseError(new PEAR_Error('Cannot Process Place Hold - ILS Not Supported'));
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
			$return = $this->catalog->placeItemHold($user, $recordId, $itemNumber, '', $_REQUEST['type']);
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
			$showMessage = true;
		}
		if (!$atLeast1Successful){
			$hold_message_data['successful'] = 'none';
		}

		$class = $configArray['Index']['engine'];
		$db = new $class($configArray['Index']['url']);

		$_SESSION['hold_message'] = $hold_message_data;
		if (isset($_SESSION['hold_referrer'])){
			$logger->log('Hold Referrer is set, redirecting to there.  type = ' . $_REQUEST['type'], PEAR_LOG_INFO);
			//Redirect for hold cancellation or update
			header("Location: " . $_SESSION['hold_referrer']);
			unset($_SESSION['hold_referrer']);
			if (isset($_SESSION['autologout'])){
				unset($_SESSION['autologout']);
				$masqueradeMode = UserAccount::isUserMasquerading();
				if ($masqueradeMode) {
					require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
					MyAccount_Masquerade::endMasquerade();
				} else {
					UserAccount::softLogout();
				}
			}
		}else{
			$logger->log('No referrer set, but there is a message to show, go to the main holds page', PEAR_LOG_INFO);
			header("Location: " . $configArray['Site']['path'] . '/MyResearch/Holds');
		}
	}
}