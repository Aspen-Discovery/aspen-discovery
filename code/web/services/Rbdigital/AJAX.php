<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/HTTP/HTTP_Request.php';

class Rbdigital_AJAX extends Action {

	function launch() {
		$method = $_GET['method'];
        header('Content-type: text/plain');
        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        echo $this->$method();
	}

	function placeHold(){
		$user = UserAccount::getLoggedInUser();

		$id = $_REQUEST['id'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron){
				require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
				$driver = new RbdigitalDriver();
				$holdMessage = $driver->placeHold($patron, $id);
				return json_encode($holdMessage);
			}else{
				return json_encode(array('result'=>false, 'message'=>translate(['text'=>'no_permissions_for_hold','defaultText'=>'Sorry, it looks like you don\'t have permissions to place holds for that user.'])));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to place a hold.'));
		}
	}

	function checkOutTitle(){
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
				$driver = new RbdigitalDriver();
				$result = $driver->checkoutTitle($patron, $id);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				if ($result['success']){
                    /** @noinspection HtmlUnknownTarget */
                    $result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">View My Check Outs</a>';
				}
				return json_encode($result);
			}else{
				return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to checkout titles for that user.'));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to checkout an item.'));
		}
	}

	function checkOutMagazine(){
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['id'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
				require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
				$driver = new RbdigitalDriver();
				$result = $driver->checkoutMagazine($patron, $id);
				//$logger->log("Checkout result = $result", Logger::LOG_NOTICE);
				if ($result['success']){
					/** @noinspection HtmlUnknownTarget */
					$result['buttons'] = '<a class="btn btn-primary" href="/MyAccount/CheckedOut" role="button">View My Check Outs</a>';
				}
				return json_encode($result);
			}else{
				return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to checkout titles for that user.'));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to checkout an item.'));
		}
	}

	function createAccount(){
        $user = UserAccount::getLoggedInUser();

        if ($user){
            $patronId = $_REQUEST['patronId'];
            $patron = $user->getUserReferredTo($patronId);
            if ($patron){
                require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
                $driver = new RbdigitalDriver();
                $createAccountMessage = $driver->createAccount($patron);
                if ($createAccountMessage['success']){
                    $followupAction = $_REQUEST['followupAction'];
                    if ($followupAction == 'checkout') {
                        return $this->checkOutTitle();
                    }elseif ($followupAction == 'checkoutMagazine') {
	                    return $this->checkOutMagazine();
                    }else{
                        return $this->placeHold();
                    }
                }
                return json_encode($createAccountMessage);
            }else{
                return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permission to create an Rbdigital account for that user.'));
            }
        }else{
            return json_encode(array('result'=>false, 'message'=>'You must be logged in prior to creating an account in Rbdigital.'));
        }
    }

	function getHoldPrompts(){
        $user = UserAccount::getLoggedInUser();
        global $interface;
        $id = $_REQUEST['id'];
        $interface->assign('id', $id);

        $users = $user->getRelatedEcontentUsers('rbdigital');
        $usersWithRbdigitalAccess = [];
        require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
        $driver = new RbdigitalDriver();
        foreach ($users as $tmpUser) {
            if ($driver->getRbdigitalId($tmpUser) != false) {
                $usersWithRbdigitalAccess[] = $tmpUser;
            }
        }
        $interface->assign('users', $usersWithRbdigitalAccess);

        if (count($usersWithRbdigitalAccess) > 1){
            $promptTitle = 'Rbdigital Hold Options';
            return json_encode(
                array(
                    'promptNeeded' => true,
                    'promptTitle'  => $promptTitle,
                    'prompts'      => $interface->fetch('Rbdigital/ajax-hold-prompt.tpl'),
                    'buttons'      => '<input class="btn btn-primary" type="submit" name="submit" value="Place Hold" onclick="return AspenDiscovery.Rbdigital.processHoldPrompts();">'
                )
            );
        } elseif (count($usersWithRbdigitalAccess) == 1){
            return json_encode(
                array(
                    'patronId' => reset($usersWithRbdigitalAccess)->id,
                    'promptNeeded' => false,
                )
            );
        } else {
            // No Rbdigital Account Found, let the user create one if they want
            return json_encode(
                array(
                    'promptNeeded' => true,
                    'promptTitle'  => 'Create an Account',
                    'prompts'      => $interface->fetch('Rbdigital/ajax-create-account-prompt.tpl'),
                    'buttons'      => '<input class="btn btn-primary" type="submit" name="submit" value="Create Account" onclick="return AspenDiscovery.Rbdigital.createAccount(\'hold\', \'' . $user->id . '\', \''. $id .'\');">'
                )
            );
        }
	}

	function getCheckOutPrompts(){
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$users = $user->getRelatedEcontentUsers('rbdigital');
		$usersWithRbdigitalAccess = [];
		require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
		$driver = new RbdigitalDriver();
		foreach ($users as $tmpUser) {
		    if ($driver->getRbdigitalId($tmpUser) != false) {
                $usersWithRbdigitalAccess[] = $tmpUser;
            }
        }
		$interface->assign('users', $usersWithRbdigitalAccess);

		if (count($usersWithRbdigitalAccess) > 1){
			$promptTitle = 'Rbdigital Checkout Options';
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle'  => $promptTitle,
					'prompts'      => $interface->fetch('Rbdigital/ajax-checkout-prompt.tpl'),
					'buttons'      => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout Title" onclick="return AspenDiscovery.Rbdigital.processCheckoutPrompts();">'
				)
			);
		} elseif (count($usersWithRbdigitalAccess) == 1){
			return json_encode(
				array(
					'patronId' => reset($usersWithRbdigitalAccess)->id,
					'promptNeeded' => false,
				)
			);
		} else {
			// No Rbdigital Account Found, let the user create one if they want
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle'  => 'Create an Account',
					'prompts'      => $interface->fetch('Rbdigital/ajax-create-account-prompt.tpl'),
					'buttons'      => '<input class="btn btn-primary" type="submit" name="submit" value="Create Account" onclick="return AspenDiscovery.Rbdigital.createAccount(\'checkout\', '. $user->id . ', '. $id . ');">'
				)
			);
		}
	}

	function getMagazineCheckOutPrompts(){
		$user = UserAccount::getLoggedInUser();
		global $interface;
		$id = $_REQUEST['id'];
		$interface->assign('id', $id);

		$users = $user->getRelatedEcontentUsers('rbdigital');
		$usersWithRbdigitalAccess = [];
		require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
		$driver = new RbdigitalDriver();
		foreach ($users as $tmpUser) {
			if ($driver->getRbdigitalId($tmpUser) != false) {
				$usersWithRbdigitalAccess[] = $tmpUser;
			}
		}
		$interface->assign('users', $usersWithRbdigitalAccess);

		if (count($usersWithRbdigitalAccess) > 1){
			$promptTitle = 'Rbdigital Checkout Options';
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle'  => $promptTitle,
					'prompts'      => $interface->fetch('Rbdigital/ajax-checkout-prompt.tpl'),
					'buttons'      => '<input class="btn btn-primary" type="submit" name="submit" value="Checkout MAgazine" onclick="return AspenDiscovery.Rbdigital.processMagazineCheckoutPrompts();">'
				)
			);
		} elseif (count($usersWithRbdigitalAccess) == 1){
			return json_encode(
				array(
					'patronId' => reset($usersWithRbdigitalAccess)->id,
					'promptNeeded' => false,
				)
			);
		} else {
			// No Rbdigital Account Found, let the user create one if they want
			return json_encode(
				array(
					'promptNeeded' => true,
					'promptTitle'  => 'Create an Account',
					'prompts'      => $interface->fetch('Rbdigital/ajax-create-account-prompt.tpl'),
					'buttons'      => '<input class="btn btn-primary" type="submit" name="submit" value="Create Account" onclick="return AspenDiscovery.Rbdigital.createAccount(\'checkoutMagazine\', '. $user->id . ', '. $id . ');">'
				)
			);
		}
	}

	function cancelHold(){
		$user = UserAccount::getLoggedInUser();
		$id = $_REQUEST['recordId'];
		if ($user){
			$patronId = $_REQUEST['patronId'];
			$patron = $user->getUserReferredTo($patronId);
			if ($patron) {
                require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
                $driver = new RbdigitalDriver();
				$result = $driver->cancelHold($patron, $id);
				return json_encode($result);
			}else{
				return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to cancel holds for that user.'));
			}
		}else{
			return json_encode(array('result'=>false, 'message'=>'You must be logged in to cancel holds.'));
		}
	}

    function renewCheckout(){
        $user = UserAccount::getLoggedInUser();
        $id = $_REQUEST['recordId'];
        if ($user){
            $patronId = $_REQUEST['patronId'];
            $patron = $user->getUserReferredTo($patronId);
            if ($patron) {
                require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
                $driver = new RbdigitalDriver();
                $result = $driver->renewCheckout($patron, $id);
                return json_encode($result);
            }else{
                return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.'));
            }
        }else{
            return json_encode(array('result'=>false, 'message'=>'You must be logged in to renew titles.'));
        }
    }

    function returnCheckout(){
        $user = UserAccount::getLoggedInUser();
        $id = $_REQUEST['recordId'];
        if ($user){
            $patronId = $_REQUEST['patronId'];
            $patron = $user->getUserReferredTo($patronId);
            if ($patron) {
                require_once ROOT_DIR . '/Drivers/RbdigitalDriver.php';
                $driver = new RbdigitalDriver();
                $result = $driver->returnCheckout($patron, $id);
                return json_encode($result);
            }else{
                return json_encode(array('result'=>false, 'message'=>'Sorry, it looks like you don\'t have permissions to modify checkouts for that user.'));
            }
        }else{
            return json_encode(array('result'=>false, 'message'=>'You must be logged in to return titles.'));
        }
    }
}