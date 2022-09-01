<?php
require_once ROOT_DIR . '/JSON_Action.php';

class Axis360_AJAX extends JSON_Action
{
	function placeHold()
	{
		$user = UserAccount::getLoggedInUser();

		$id = $_REQUEST['id'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				if (isset($_REQUEST['axis360Email'])) {
					if ($_REQUEST['axis360Email'] != $patron->axis360Email) {
						$patron->axis360Email = $_REQUEST['axis360Email'];
						$patron->update();
					}
				}
				if (isset($_REQUEST['promptForAxis360Email'])) {
					if ($_REQUEST['promptForAxis360Email'] == 1 || $_REQUEST['promptForAxis360Email'] == 'yes' || $_REQUEST['promptForAxis360Email'] == 'on') {
						$patron->promptForAxis360Email = 1;
					} else {
						$patron->promptForAxis360Email = 0;
					}
					$patron->update();
				}

				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$driver = new Axis360Driver();
				return $driver->placeHold($patron, $id);
			} else {
				return array('result' => false, 'message' => translate(['text' => 'Sorry, it looks like you don\'t have permissions to place holds for that user.', 'isPublicFacing'=> true]));
			}
		} else {
			return array('result' => false, 'message' => 'You must be logged in to place a hold.');
		}
	}

	function checkOutTitle()
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$driver = new Axis360Driver();
				$result = $driver->checkoutTitle($patron, $id);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				if ($result['success']) {
					/** @noinspection HtmlUnknownTarget */
					$result['title'] = translate(['text'=>"Title Checked Out Successfully", 'isPublicFacing'=>true]);
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate(['text'=>'View My Check Outs', 'isPublicFacing'=>true]) . '</a>';
				} else {
					$result['title'] = translate(['text'=>"Error Checking Out Title", 'isPublicFacing'=>true]);
				}
				return $result;
			} else {
				return array('result' => false, 'title' => translate(['text'=>"Error Checking Out Title", 'isPublicFacing'=>true]), 'message' => translate(['text' => 'Sorry, it looks like you don\'t have permissions to checkout titles for that user.', 'isPublicFacing'=>true]));
			}
		} else {
			return array('result' => false, 'title' => translate(['text'=>"Error Checking Out Title", 'isPublicFacing'=>true]), 'message' => translate(['text'=>'You must be logged in to checkout an item.', 'isPublicFacing'=>true]));
		}
	}

	/** @noinspection PhpUnused */
	function getHoldPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$usersWithAxis360Access = $this->getAxis360Users($user);

		if ($user->axis360Email == 'undefined') {
			$user->axis360Email = '';
		}

		if(strlen($user->axis360Email) == 0) {
			if($user->email) {
				$user->axis360Email = $user->email;
			}
		}

		$promptForEmail = false;
		if (strlen($user->axis360Email) == 0 || $user->promptForAxis360Email == 1) {
			$promptForEmail = true;
		}

		$interface->assign('axis360Email', $user->axis360Email);
		$interface->assign('promptForEmail', $promptForEmail);

		if(count($usersWithAxis360Access) == 1) {
			$interface->assign('patronId', reset($usersWithAxis360Access)->id);
		}

		if(count($usersWithAxis360Access) == 0) {
			// No Axis 360 Account Found, let the user create one if they want
			return [
				'success' => false,
				'promptNeeded' => true,
				'promptTitle' => translate(['text'=>'Error', 'isPublicFacing'=>true]),
				'prompts' => translate(['text'=>'Your account is not valid for Axis360, please contact your local library.', 'isPublicFacing'=>true]),
				'buttons' => ''
			];
		}
		elseif ($promptForEmail && count($usersWithAxis360Access) > 1) {
			$promptTitle = translate(['text'=>'Axis 360 Hold Options', 'isPublicFacing'=>true]);
			return array(
					'success' => true,
					'promptNeeded' => true,
					'promptTitle' => translate(['text'=>$promptTitle,'isPublicFacing'=>true]),
					'prompts' => $interface->fetch('Axis360/ajax-hold-prompt.tpl'),
					'buttons' => '<button class="btn btn-primary" type="submit" name="submit" onclick="return AspenDiscovery.Axis360.processHoldPrompts();">' . translate(['text' => 'Place Hold', 'isPublicFacing'=>true]) . '</button>'
			);
		} elseif ($promptForEmail && count($usersWithAxis360Access) == 1) {
			$promptTitle = translate(['text'=>'Axis 360 Hold Options', 'isPublicFacing'=>true]);
			return array(
				'success' => true,
				'promptNeeded' => true,
				'promptTitle' => translate(['text'=>$promptTitle,'isPublicFacing'=>true]),
				'prompts' => $interface->fetch('Axis360/ajax-hold-prompt.tpl'),
				'patronId' => reset($usersWithAxis360Access)->id,
				'buttons' => '<button class="btn btn-primary" type="submit" name="submit" onclick="return AspenDiscovery.Axis360.processHoldPrompts();">' . translate(['text' => 'Place Hold', 'isPublicFacing'=>true]) . '</button>'
			);
		} else {
			return [
				'success' => true,
				'patronId' => reset($usersWithAxis360Access)->id,
				'promptNeeded' => false,
				'axis360Email' => $user->axis360Email,
				'promptForAxis360Email' => $user->promptForAxis360Email,
			];
		}
	}

	/** @noinspection PhpUnused */
	function getCheckOutPrompts()
	{
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);
		$interface->assign('checkoutType', 'book');

		$usersWithAxis360Access = $this->getAxis360Users($user);

		if (count($usersWithAxis360Access) > 1) {
			$promptTitle = translate(['text' => 'Axis 360 Checkout Options', 'isPublicFacing'=>true]);
			return array(
					'promptNeeded' => true,
					'promptTitle' => $promptTitle,
					'prompts' => $interface->fetch('Axis360/ajax-checkout-prompt.tpl'),
					'buttons' => '<input class="btn btn-primary" type="submit" name="submit" value="' . translate(['text' => 'Checkout Title', 'inAttribute'=>true, 'isPublicFacing'=>true]) . '" onclick="return AspenDiscovery.Axis360.processCheckoutPrompts();">'
				);
		} elseif (count($usersWithAxis360Access) == 1) {
			return array(
					'patronId' => reset($usersWithAxis360Access)->id,
					'promptNeeded' => false,
				);
		} else {
			// No Axis 360 Account Found, let the user create one if they want
			return [
				'promptNeeded' => true,
				'promptTitle' => translate(['Error', 'isPublicFacing'=>true]),
				'prompts' => translate(['Your account is not valid for Axis360, please contact your local library.', 'isPublicFacing'=>true]),
				'buttons' => ''
			];
		}
	}

	function cancelHold() : array
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$driver = new Axis360Driver();
				return $driver->cancelHold($patron, $id);
			} else {
				return array('result' => false, 'message' => translate(['text'=>'Sorry, it looks like you don\'t have permissions to cancel holds for that user.', 'isPublicFacing'=>true]));
			}
		} else {
			return array('result' => false, 'message' => translate(['text'=>'You must be logged in to cancel holds.', 'isPublicFacing'=>true]));
		}
	}

	function renewCheckout()
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$driver = new Axis360Driver();
				return $driver->renewCheckout($patron, $id);
			} else {
				return array('result' => false, 'message' => translate(['text'=>'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.', 'isPublicFacing'=>true]));
			}
		} else {
			return array('result' => false, 'message' => translate(['text'=>'You must be logged in to renew titles.', 'isPublicFacing'=>true]));
		}
	}

	/** @noinspection PhpUnused */
	function returnCheckout()
	{
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		if ($user) {
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
				$driver = new Axis360Driver();
				return $driver->returnCheckout($patron, $id);
			} else {
				return array('result' => false, 'message' => translate(['text'=>'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.', 'isPublicFacing'=>true]));
			}
		} else {
			return array('result' => false, 'message' => translate(['text'=>'You must be logged in to return titles.', 'isPublicFacing'=>true]));
		}
	}
	/**
	 * @param User $user
	 * @return User[]
	 */
	private function getAxis360Users(User $user)
	{
		global $interface;
		$users = $user->getRelatedEcontentUsers('axis360');
		$usersWithAxis360Access = [];
		require_once ROOT_DIR . '/Drivers/Axis360Driver.php';
		foreach ($users as $tmpUser) {
			$usersWithAxis360Access[] = $tmpUser;
		}
		$interface->assign('users', $usersWithAxis360Access);
		return $usersWithAxis360Access;
	}

	function getStaffView(){
		$result = [
			'success' => false,
			'message' => translate(['text'=>'Unknown error loading staff view', 'isPublicFacing'=>true])
		];
		$id = $_REQUEST['id'];
		require_once ROOT_DIR . '/RecordDrivers/Axis360RecordDriver.php';
		$recordDriver = new Axis360RecordDriver($id);
		if ($recordDriver->isValid()){
			global $interface;
			$interface->assign('recordDriver', $recordDriver);
			$result = [
				'success' => true,
				'staffView' => $interface->fetch($recordDriver->getStaffView())
			];
		}else{
			$result['message'] = translate(['text'=>'Could not find that record', 'isPublicFacing'=>true]);
		}
		return $result;
	}

	function freezeHold() : array
	{
		$user = UserAccount::getLoggedInUser();
		$result = array(
			'success' => false,
			'message' => translate(['text'=>'Error freezing hold.', 'isPublicFacing'=>true])
		);
		if (!$user) {
			$result['message'] = translate(['text'=>'You must be logged in to freeze a hold.  Please close this dialog and login again.', 'isPublicFacing'=>true]);
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate(['text'=>'Sorry, you do not have access to freeze holds for the supplied user.', 'isPublicFacing'=>true]);
			} else {
				if (empty($_REQUEST['recordId'])) {
					// We aren't getting all the expected data, so make a log entry & tell user.
					$result['message'] = translate(['text'=>'Information about the hold to be frozen was not provided.', 'isPublicFacing'=>true]);
				} else {
					$recordId = $_REQUEST['recordId'];
					$result = $patronOwningHold->freezeAxis360Hold($recordId);
				}
			}
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Freeze Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$result['message'] = translate(['text'=>'No Patron was specified.', 'isPublicFacing'=>true]);
		}

		return $result;
	}

	function thawHold() : array
	{
		$user = UserAccount::getLoggedInUser();
		$result = array( // set default response
			'success' => false,
			'message' => translate(['text'=>'Error thawing hold.', 'isPublicFacing'=>true])
		);

		if (!$user) {
			$result['message'] = translate(['text'=>'You must be logged in to thaw a hold.  Please close this dialog and login again.', 'isPublicFacing'=>true]);
		} elseif (!empty($_REQUEST['patronId'])) {
			$patronId = $_REQUEST['patronId'];
			$patronOwningHold = $user->getUserReferredTo($patronId);

			if ($patronOwningHold == false) {
				$result['message'] = translate(['text'=>'Sorry, you do not have access to thaw holds for the supplied user.', 'isPublicFacing'=>true]);
			} else {
				if (empty($_REQUEST['recordId'])) {
					$result['message'] = translate(['text'=>'Information about the hold to be thawed was not provided.', 'isPublicFacing'=>true]);
				} else {
					$recordId = $_REQUEST['recordId'];
					$result = $patronOwningHold->thawAxis360Hold($recordId);
				}
			}
		} else {
			// We aren't getting all the expected data, so make a log entry & tell user.
			global $logger;
			$logger->log('Thaw Hold, no patron Id was passed in AJAX call.', Logger::LOG_ERROR);
			$result['message'] = translate(['text'=>'No Patron was specified.', 'isPublicFacing'=>true]);
		}

		return $result;
	}

	/** @noinspection PhpUnused */
	function getLargeCover(){
		global $interface;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		return array(
			'title' => translate(['text'=>'Cover Image', 'isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("Axis360/largeCover.tpl"),
			'modalButtons' => ""
		);
	}
}