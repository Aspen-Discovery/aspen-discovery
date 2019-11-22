<?php
require_once ROOT_DIR . '/Action.php';

global $configArray;

class OverDrive_AJAX extends Action {

	function launch() {
		$method = $_GET['method'];
		if (method_exists($this, $method)) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else {
			echo json_encode(array('error'=>'invalid_method'));
		}
	}

	function placeHold(){
		$user = UserAccount::getLoggedInUser();

		$overDriveId = $_REQUEST['overDriveId'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron){
				if (isset($_REQUEST['overdriveEmail'])){
					if ($_REQUEST['overdriveEmail'] != $patron->overdriveEmail){
						$patron->overdriveEmail = $_REQUEST['overdriveEmail'];
						$patron->update();
					}
				}
				if (isset($_REQUEST['overdriveAutoCheckout'])){
					if ($_REQUEST['overdriveAutoCheckout'] == '1' || $_REQUEST['overdriveAutoCheckout'] == 'yes' || $_REQUEST['overdriveAutoCheckout'] == 'on'){
						$patron->overdriveAutoCheckout = 1;
					}else{
						$patron->overdriveAutoCheckout = 0;
					}
					$patron->update();
				}
				if (isset($_REQUEST['promptForOverdriveEmail'])){
					if ($_REQUEST['promptForOverdriveEmail'] == 1 || $_REQUEST['promptForOverdriveEmail'] == 'yes' || $_REQUEST['promptForOverdriveEmail'] == 'on'){
						$patron->promptForOverdriveEmail = 1;
					}else{
						$patron->promptForOverdriveEmail = 0;
					}
					$patron->update();
				}

                require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
                $driver = new OverDriveDriver();
				$holdMessage = $driver->placeHold($patron, $overDriveId);
				return json_encode($holdMessage);
			}else{
				return json_encode(array('result'=>false, 'message'=>translate(['text'=>'no_permissions_for_hold','defaultText'=>'Sorry, it looks like you don\'t have permissions to place holds for that user.'])));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to place a hold.'));
		}
	}

	function renewTitle(){
		$user = UserAccount::getLoggedInUser();
		$overDriveId = $_REQUEST['overDriveId'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
                $driver = new OverDriveDriver();
				$result = $driver->renewCheckout($patron, $overDriveId);
				return json_encode($result);
			}else{
				return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.'));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to renew titles.'));
		}
	}

	function checkOutTitle(){
		$user = UserAccount::getLoggedInUser();
		$overDriveId = $_REQUEST['overDriveId'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
                require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
                $driver = new OverDriveDriver();
				$result = $driver->checkOutTitle($patron, $overDriveId);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				if ($result['success']){
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate('View My Check Outs') . '</a>';
				}
				return json_encode($result);
			}else{
				return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to checkout titles for that user.'));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to checkout an item.'));
		}
	}

	function returnCheckout(){
		$user = UserAccount::getLoggedInUser();
		$overDriveId = $_REQUEST['overDriveId'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
                require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
                $driver = new OverDriveDriver();
				$result = $driver->returnCheckout($patron, $overDriveId);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				return json_encode($result);
			}else{
				return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to return titles for that user.'));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to return an item.'));
		}
	}

	function selectOverDriveDownloadFormat(){
		$user = UserAccount::getLoggedInUser();
		$overDriveId = $_REQUEST['overDriveId'];
		$formatId = $_REQUEST['formatId'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
                require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
                $driver = new OverDriveDriver();
				$result = $driver->selectOverDriveDownloadFormat($overDriveId, $formatId, $patron);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				return json_encode($result);
			}else{
				return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to download titles for that user.'));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to download a title.'));
		}
	}

	function getDownloadLink(){
		$user = UserAccount::getLoggedInUser();
		$overDriveId = $_REQUEST['overDriveId'];
		$formatId = $_REQUEST['formatId'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
                require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
                $driver = new OverDriveDriver();
				$result = $driver->getDownloadLink($overDriveId, $formatId, $patron);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				return json_encode($result);
			}else{
				return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to download titles for that user.'));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to download a title.'));
		}
	}

	function getHoldPrompts(){
		if (!UserAccount::isLoggedIn()){
			return json_encode(
				array(
					'success' => false,
					'message' => 'You must be logged in to place holds, please login again.'
				)
			);
		}
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('overDriveId', $id);
		if ($user->overdriveEmail == 'undefined'){
			$user->overdriveEmail = '';
		}
		$promptForEmail = false;
		if (strlen($user->overdriveEmail) == 0 || $user->promptForOverdriveEmail == 1){
			$promptForEmail = true;
		}

		$overDriveUsers = $user->getRelatedEcontentUsers('overdrive');
		$interface->assign('overDriveUsers', $overDriveUsers);
		if (count($overDriveUsers) == 1){
			$interface->assign('patronId', reset($overDriveUsers)->id);
		}

		$interface->assign('overdriveEmail', $user->overdriveEmail);
		$interface->assign('overdriveAutoCheckout', $user->overdriveAutoCheckout);
		$interface->assign('promptForEmail', $promptForEmail);
		if (count($overDriveUsers) == 0){
			return json_encode(
				array(
					'success' => false,
					'message' => 'Could not find a valid user to place a hold for, please check with your library to validate your account'
				)
			);
		}else if ($promptForEmail || count($overDriveUsers) > 1){
			$promptTitle = 'OverDrive Hold Options';
			return json_encode(
				array(
					'success' => true,
					'promptNeeded' => true,
					'promptTitle' => translate($promptTitle),
					'prompts' => $interface->fetch('OverDrive/ajax-hold-prompt.tpl'),
					'buttons' => '<button class="btn btn-primary" type="submit" name="submit" onclick="return AspenDiscovery.OverDrive.processOverDriveHoldPrompts();">' . translate('Place Hold') . '</button>'
				)
			);
		}else{
			return json_encode(
				array(
					'success' => true,
					'patronId' => reset($overDriveUsers)->id,
					'promptNeeded' => false,
					'overdriveEmail' => $user->overdriveEmail,
					'promptForOverdriveEmail' => $promptForEmail,
				)
			);
		}
	}

	function getCheckOutPrompts(){
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('overDriveId', $id);

		$overDriveUsers = $user->getRelatedEcontentUsers('overdrive');
		$interface->assign('overDriveUsers', $overDriveUsers);

		if (count($overDriveUsers) > 1){
			$promptTitle = 'OverDrive Checkout Options';
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle'  => $promptTitle,
					'prompts'      => $interface->fetch('OverDrive/ajax-checkout-prompt.tpl'),
					'buttons'      => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout Title" onclick="return AspenDiscovery.OverDrive.processOverDriveCheckoutPrompts();">'
				)
			);
		} elseif (count($overDriveUsers) == 1){
			return json_encode(
				array(
					'patronId' => reset($overDriveUsers)->id,
					'promptNeeded' => false,
				)
			);
		} else {
			// No Overdrive Account Found, give the user an error message
			global $logger;
			$logger->log('No valid Overdrive account was found to check out an Overdrive title.', Logger::LOG_ERROR);
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle'  => 'Error',
					'prompts'      => 'No valid Overdrive account was found to check this title out with.',
					'buttons'      => ''
				)
			);
		}

	}

	function cancelHold(){
		$user = UserAccount::getLoggedInUser();
		$overDriveId = $_REQUEST['overDriveId'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
                require_once ROOT_DIR . '/Drivers/OverDriveDriver.php';
                $driver = new OverDriveDriver();
				$result = $driver->cancelHold($patron, $overDriveId);
				return json_encode($result);
			}else{
				return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to download cancel holds for that user.'));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to cancel holds.'));
		}
	}

	function freezeHold() {
		$user = UserAccount::getLoggedInUser();
		$result = array(
			'success' => false,
			'message' => 'Error '.translate('freezing').' hold.'
		);
		if (!$user){
			$result['message'] = 'You must be logged in to '. translate('freeze') .' a hold.  Please close this dialog and login again.';
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false){
				$result['message'] = 'Sorry, you do not have access to '. translate('freeze') .' holds for the supplied user.';
			}else{
				if (empty($_REQUEST['overDriveId'])) {
					// We aren't getting all the expected data, so make a log entry & tell user.
					global $logger;
					$result['message'] = 'Information about the hold to be '. translate('frozen') .' was not provided.';
				}else{
					$overDriveId = $_REQUEST['overDriveId'];
					$reactivationDate = isset($_REQUEST['reactivationDate']) ? $_REQUEST['reactivationDate'] : null;
					$result = $patronOwningHold->freezeOverDriveHold($overDriveId, $reactivationDate);
					if ($result['success']) {
						$notice = translate('freeze_info_notice');
						if (translate('frozen') != 'frozen') {
							$notice = str_replace('frozen', translate('frozen'), $notice);  // Translate the phrase frozen from the notice.
						}
						$message = '<div class="alert alert-success">'.$result['message'] .'</div>'. ($notice ? '<div class="alert alert-info">'.$notice .'</div>' : '');
						$result['message'] = $message;
					}

					if (!$result['success'] && is_array($result['message'])) {
						/** @var string[] $messageArray */
						$messageArray = $result['message'];
						$result['message'] = implode('; ', $messageArray);
						// Millennium Holds assumes there can be more than one item processed. Here we know only one got processed,
						// but do implode as a fallback
					}
				}
			}
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Freeze Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$result['message'] = 'No Patron was specified.';
		}

		return json_encode($result);
	}

	// called by js function Account.freezeHold
	function getReactivationDateForm(){
		global $interface;

		$interface->assign('patronId', UserAccount::getActiveUserId());
		$interface->assign('overDriveId', $_REQUEST['overDriveId']);

		$title = translate('Freeze Hold'); // language customization
		$results = array(
			'title'        => $title,
			'modalBody'    => $interface->fetch("OverDrive/reactivationDate.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' id='doFreezeHoldWithReactivationDate' onclick='$(\".form\").submit(); return false;'>$title</button>"
		);
		return json_encode($results);
	}

	function thawHold() {
		$user = UserAccount::getLoggedInUser();
		$result = array( // set default response
			'success' => false,
			'message' => 'Error thawing hold.'
		);

		if (!$user){
			$result['message'] = 'You must be logged in to '. translate('thaw') .' a hold.  Please close this dialog and login again.';
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false){
				$result['message'] = 'Sorry, you do not have access to '. translate('thaw') .' holds for the supplied user.';
			}else{
				if (empty($_REQUEST['overDriveId'])) {
					$result['message'] = 'Information about the hold to be '. translate('thawed') .' was not provided.';
				}else{
					$overDriveId = $_REQUEST['overDriveId'];
					$result = $patronOwningHold->thawOverDriveHold($overDriveId);
				}
			}
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Thaw Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$result['message'] = 'No Patron was specified.';
		}

		return json_encode($result);
	}

	function setAutoCheckoutForHold(){
		$user = UserAccount::getLoggedInUser();
		$result = array( // set default response
			'success' => false,
			'message' => 'Error thawing hold.'
		);

		if (!$user){
			$result['message'] = 'You must be logged in to set auto checkout for a hold.  Please close this dialog and login again.';
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false){
				$result['message'] = 'Sorry, you do not have access to update holds for the supplied user.';
			}else{
				if (empty($_REQUEST['overDriveId'])) {
					$result['message'] = 'Information about the hold to be updated was not provided.';
				}else{
					$overDriveId = $_REQUEST['overDriveId'];
					$autoCheckout = $_REQUEST['autoCheckout'];
					$result = $patronOwningHold->setAutoCheckoutForOverDriveHold($overDriveId, $autoCheckout);
				}
			}
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Thaw Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$result['message'] = 'No Patron was specified.';
		}

		return json_encode($result);
	}
}