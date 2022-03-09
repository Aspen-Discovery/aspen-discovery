<?php

class Hoopla_AJAX extends Action
{
	function launch() {
		global $timer;
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (method_exists($this, $method)) {
			$timer->logTime("Starting method $method");

			echo json_encode($this->$method());
		}else{
			echo json_encode(array('error'=>'invalid_method'));
		}
	}

	/** @noinspection PhpUnused */
	function getCheckOutPrompts(){
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];
		if (strpos($id, ':') !== false) {
			list(, $id) = explode(':', $id);
		}
		if ($user) {
			$hooplaUsers = $user->getRelatedEcontentUsers('hoopla');

			require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
			$driver = new HooplaDriver();

			if ($id) {
				global $interface;
				$interface->assign('hooplaId', $id);

				//TODO: need to determine what happens to cards without a Hoopla account
				$hooplaUserStatuses = array();
				foreach ($hooplaUsers as $tmpUser) {
					$checkOutStatus                   = $driver->getAccountSummary($tmpUser);
					$hooplaUserStatuses[$tmpUser->id] = $checkOutStatus;
				}

				if (count($hooplaUsers) > 1) {
					$interface->assign('hooplaUsers', $hooplaUsers);
					$interface->assign('hooplaUserStatuses', $hooplaUserStatuses);

					return
						array(
							'title'   => translate(['text'=>'Hoopla Check Out', 'isPublicFacing'=>true]),
							'body'    => $interface->fetch('Hoopla/ajax-checkout-prompt.tpl'),
							'buttons' => '<button class="btn btn-primary" type= "button" title="Check Out" onclick="return AspenDiscovery.Hoopla.checkOutHooplaTitle(\'' . $id . '\');">' . translate(['text'=>'Check Out', 'isPublicFacing'=>true]) . '</button>'
						);
				} elseif (count($hooplaUsers) == 1) {
					$hooplaUser = reset($hooplaUsers);
					if ($hooplaUser->id != $user->id) {
						$interface->assign('hooplaUser', $hooplaUser); // Display the account name when not using the main user
					}
					$checkOutStatus = $hooplaUserStatuses[$hooplaUser->id];
					if (!$checkOutStatus) {
						require_once ROOT_DIR . '/RecordDrivers/HooplaRecordDriver.php';
						$hooplaRecord = new HooplaRecordDriver($id);

						// Base Hoopla Title View Url
						$accessLink = $hooplaRecord->getAccessLink();
						$hooplaRegistrationUrl = $accessLink['url'];
						$hooplaRegistrationUrl .= (parse_url($hooplaRegistrationUrl, PHP_URL_QUERY) ? '&' : '?') . 'showRegistration=true'; // Add Registration URL parameter

						return array(
							'title'   => translate(['text'=>'Create Hoopla Account', 'isPublicFacing'=>true]),
							'body'    => $interface->fetch('Hoopla/ajax-hoopla-single-user-checkout-prompt.tpl'),
							'buttons' =>
								'<button id="theHooplaButton" class="btn btn-default" type="button" title="Check Out" onclick="return AspenDiscovery.Hoopla.checkOutHooplaTitle(\'' . $id . '\', ' . $hooplaUser->id . ')">' . translate(['text'=>'I registered, Check Out now', 'isPublicFacing'=>true]) . '</button>'
								.'<a class="btn btn-primary" role="button" href="'.$hooplaRegistrationUrl.'" target="_blank" title="Register at Hoopla" onclick="$(\'#theHooplaButton+a,#theHooplaButton\').toggleClass(\'btn-primary btn-default\');">' . translate(['text'=>'Register at Hoopla', 'isPublicFacing'=>true]) . '</a>'
						);
					}
					if ($hooplaUser->hooplaCheckOutConfirmation) {
						$interface->assign('hooplaPatronStatus', $checkOutStatus);
						return
							array(
								'title'   => translate(['text'=>'Confirm Hoopla Check Out', 'isPublicFacing'=>true]),
								'body'    => $interface->fetch('Hoopla/ajax-hoopla-single-user-checkout-prompt.tpl'),
								'buttons' => '<button class="btn btn-primary" type="button" title="Check Out" onclick="return AspenDiscovery.Hoopla.checkOutHooplaTitle(\'' . $id . '\', ' . $hooplaUser->id . ')">' . translate(['text'=>'Check Out', 'isPublicFacing'=>true]) . '</button>'
							);
					}else{
						// Go ahead and checkout the title
						return array(
							'title'   => translate(['text'=>'Checking out Hoopla title', 'isPublicFacing'=>true]),
							'body'    => "<script>AspenDiscovery.Hoopla.checkOutHooplaTitle('{$id}', '{$hooplaUser->id}')</script>",
							'buttons' => ''
						);
					}
				} else {
					// No Hoopla Account Found, give the user an error message
					$invalidAccountMessage = translate(['text' => 'The barcode or library for this account is not valid for Hoopla. Please contact your local library for more information.', 'isPublicFacing'=>true]);
					global $logger;
					$logger->log('No valid Hoopla account was found to check out a Hoopla title.', Logger::LOG_ERROR);
					return
						array(
							'title'   => translate(['text'=>'Invalid Hoopla Account', 'isPublicFacing'=>true]),
							'body'    => '<p class="alert alert-danger">'. $invalidAccountMessage .'</p>',
							'buttons' => ''
						);
				}
			} else {
				return array(
					'title'   => translate(['text'=>'Error', 'isPublicFacing'=>true]),
					'body'    => translate(['text'=>'Item to checkout was not provided.', 'isPublicFacing'=>true]),
					'buttons' => ''
				);
            }
		}else{
			return array(
				'title'   => translate(['text'=>'Error', 'isPublicFacing'=>true]),
				'body'    => translate(['text'=>'You must be logged in to checkout an item.', 'isPublicFacing'=>true])
					.'<script>Globals.loggedIn = false;  AspenDiscovery.Hoopla.getCheckOutPrompts(\''.$id.'\')</script>',
				'buttons' => ''
			);
		}

	}

	/** @noinspection PhpUnused */
	function checkOutHooplaTitle() {
		$user = UserAccount::getLoggedInUser();
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron   = $user->getUserReferredTo($patronId);
			if ($patron) {
				global $interface;
				if ($patron->id != $user->id) {
					$interface->assign('hooplaUser', $patron); // Display the account name when not using the main user
				}

				$id = $_REQUEST['id'];
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$driver = new HooplaDriver();
				$result = $driver->checkOutTitle($patron, $id);
				if (!empty($_REQUEST['stopHooplaConfirmation'])) {
					$patron->hooplaCheckOutConfirmation = 0;
					$patron->update();
				}
				if ($result['success']) {
					$checkOutStatus = $driver->getAccountSummary($patron);
					$interface->assign('hooplaPatronStatus', $checkOutStatus);
					$title = empty($result['title']) ? translate(['text'=>"Title checked out successfully", 'isPublicFacing'=>true]) : translate(['text'=> "%1% checked out successfully", 1=>$result['title'],'isPublicFacing'=>true]);
                    /** @noinspection HtmlUnknownTarget */
                    return array(
						'success' => true,
						'title'   => $title,
						'message' => $interface->fetch('Hoopla/hoopla-checkout-success.tpl'),
						'buttons' => '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">' . translate(['text'=>'View My Check Outs', 'isPublicFacing'=>true]) . '</a>'
					);
				} else {
					return $result;
				}
			}else{
				return array('success'=>false, 'message'=>translate(['text'=>'Sorry, it looks like you don\'t have permissions to checkout titles for that user.', 'isPublicFacing'=>true]));
			}
		}else{
			return array('success'=>false, 'message'=>translate(['text'=>'You must be logged in to checkout an item.', 'isPublicFacing'=>true]));
		}
	}

	/** @noinspection PhpUnused */
	function returnCheckout() {
		$user = UserAccount::getLoggedInUser();
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron   = $user->getUserReferredTo($patronId);
			if ($patron) {
				$id = $_REQUEST['id'];
				require_once ROOT_DIR . '/Drivers/HooplaDriver.php';
				$driver = new HooplaDriver();
				return $driver->returnCheckout($patron, $id);
			}else{
				return array('success'=>false, 'message'=>translate(['text'=>'Sorry, it looks like you don\'t have permissions to return titles for that user.', 'isPublicFacing'=>true]));
			}
		}else{
			return array('success'=>false, 'message'=>translate(['text'=>'You must be logged in to return an item.', 'isPublicFacing'=>true]));
		}
	}

	/** @noinspection PhpUnused */
	function getLargeCover()
	{
		global $interface;

		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		return array(
			'title' => translate(['text'=>'Cover Image', 'isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("Hoopla/largeCover.tpl"),
			'modalButtons' => ""
		);
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}