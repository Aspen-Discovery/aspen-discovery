<?php
/**
 * Allows staff to return titles and checkout titles while the ILS is offline
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/26/13
 * Time: 10:27 AM
 */

class Circa_OfflineCirculation extends Action{
	function launch()
	{
		global $interface, $configArray;
		$error = '';

		if (isset($_POST['submit'])){
			require_once ROOT_DIR . '/sys/OfflineCirculationEntry.php';
			//Store information into the database
			$login = $_REQUEST['login'];
			$interface->assign('lastLogin', $login);
			$password1 = $_REQUEST['password1'];
			$interface->assign('lastPassword1', $password1);
			/*
			$initials = $_REQUEST['initials'];
			$interface->assign('lastInitials', $initials);
			$password2 = $_REQUEST['password2'];
			$interface->assign('lastPassword2', $password2);
			*/

			$loginInfoValid = true;
			if (strlen($login) == 0){
				$error .= "Please enter your login.<br>";
				$loginInfoValid = false;
			}
			if (strlen($password1) == 0){
				$error .= "Please enter your login password.<br>";
				$loginInfoValid = false;
			}
			/*if (strlen($initials) == 0){
				$initials = $login;
			}
			if (strlen($password2) == 0){
				$password2 = $password1;
			}*/

			if ($loginInfoValid){
				//$barcodesToCheckIn = $_REQUEST['barcodesToCheckIn'];
				$patronBarcode = $_REQUEST['patronBarcode'];
				$barcodesToCheckOut = $_REQUEST['barcodesToCheckOut'];

				//First store any titles that are being checked in
				/*if (strlen(trim($barcodesToCheckIn)) > 0){
					$barcodesToCheckIn = preg_split('/[\\s\\r\\n]+/', $barcodesToCheckIn);
					foreach ($barcodesToCheckIn as $barcode){
						$offlineCirculationEntry = new OfflineCirculationEntry();
						$offlineCirculationEntry->timeEntered = time();
						$offlineCirculationEntry->itemBarcode = $barcode;
						$offlineCirculationEntry->login = $login;
						$offlineCirculationEntry->loginPassword = $password1;
						$offlineCirculationEntry->initials = $initials;
						$offlineCirculationEntry->initialsPassword = $password2;
						$offlineCirculationEntry->type = 'Check In';
						$offlineCirculationEntry->status = 'Not Processed';
						$offlineCirculationEntry->insert();
					}
				}*/
				$numItemsCheckedOut = 0;
				if (strlen(trim($barcodesToCheckOut)) > 0 && strlen($patronBarcode) > 0){
					$userObj = new User();
					$patronId = null;
					$userObj->cat_password = $patronBarcode;
					if ($userObj->find()){
						$userObj->fetch();
						$patronId = $userObj->id;
					}
					$barcodesToCheckOut = preg_split('/[\\s\\r\\n]+/', $barcodesToCheckOut);
					if (!is_array($barcodesToCheckOut)){
						$barcodesToCheckOut = array($barcodesToCheckOut);
					}
					foreach ($barcodesToCheckOut as $barcode){
						$barcode = trim($barcode);
						if (strlen($barcode) > 0){
							$offlineCirculationEntry = new OfflineCirculationEntry();
							$offlineCirculationEntry->timeEntered = time();
							$offlineCirculationEntry->itemBarcode = $barcode;
							$offlineCirculationEntry->login = $login;
							$offlineCirculationEntry->loginPassword = $password1;
							//$offlineCirculationEntry->initials = $initials;
							//$offlineCirculationEntry->initialsPassword = $password2;
							$offlineCirculationEntry->patronBarcode = $patronBarcode;
							$offlineCirculationEntry->patronId = $patronId;
							$offlineCirculationEntry->type = 'Check Out';
							$offlineCirculationEntry->status = 'Not Processed';
							if ($offlineCirculationEntry->insert()){
								$numItemsCheckedOut++;
							}else{
								$error .= "Could not check out item $barcode to patron {$patronBarcode}.<br>";
							}
						}
					}
				}
				$results = "Successfully added <strong>{$numItemsCheckedOut}</strong> items to offline circulation transactions for patron <strong>{$patronBarcode}</strong>.<br>";
			}
			if (isset($results)) $interface->assign('results', $results);
			else $error .= 'No Items were checked out.<br>';
		}

		$interface->assign('error', $error);

		$ils_name = $configArray['Catalog']['ils'] ? $configArray['Catalog']['ils'] : 'ILS';
		$interface->assign('ILSname', $ils_name);

		//Get view & load template
		$interface->setPageTitle('Offline Circulation');

		$interface->assign('sidebar', 'MyAccount/account-sidebar.tpl');
		$interface->setTemplate('offlineCirculation.tpl');
		$interface->display('layout.tpl', 'Circa');
	}
}