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

require_once ROOT_DIR . '/Action.php';

class AJAX_JSON extends Action {

	// define some status constants
 // ( used by JSON_Autocomplete )
	const STATUS_OK        = 'OK';           // good
	const STATUS_ERROR     = 'ERROR';        // bad
	const STATUS_NEED_AUTH = 'NEED_AUTH';    // must login first

	function launch()
	{
		global $analytics;
		$analytics->disableTracking();

		//header('Content-type: application/json');
		header('Content-type: text/html');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$method = $_GET['method'];
		if (method_exists($this, $method)) {
			if ($method == 'getHoursAndLocations'){
				$output = $this->$method();
			}elseif (in_array($method, array('getAutoLogoutPrompt', 'getReturnToHomePrompt', 'getPayFinesAfterAction'))) {
				$output = json_encode($this->$method());
				// Browser-side handler ajaxLightbox() doesn't use the input format in else block below
			}else{
				$output = json_encode(array('result'=>$this->$method()));
			}
		} else {
			$output = json_encode(array('error'=>'invalid_method'));
		}

		echo $output;
	}

	function isLoggedIn(){
		return UserAccount::isLoggedIn();
	}

	function getUserLists(){
		$user = UserAccount::getLoggedInUser();
		$lists = $user->getLists();
		$userLists = array();
		foreach($lists as $current) {
			$userLists[] = array('id' => $current->id,
                    'title' => $current->title);
		}
		return $userLists;
	}

	function loginUser(){
		//Login the user.  Must be called via Post parameters.
		global $interface;
		$isLoggedIn = UserAccount::isLoggedIn();
		if (!$isLoggedIn){
			$user = UserAccount::login();

			$interface->assign('user', $user); // PLB Assignment Needed before error checking?
			if (!$user || PEAR_Singleton::isError($user)){

				// Expired Card Notice
				if ($user && $user->message == 'expired_library_card') {
					return array(
						'success' => false,
						'message' => translate('expired_library_card')
					);
				}

				// General Login Error
				/** @var PEAR_Error $error */
				$error = $user;
				$message = PEAR_Singleton::isError($user) ? translate($error->getMessage()) : translate("Sorry that login information was not recognized, please try again.");
				return array(
					'success' => false,
					'message' => $message
				);
			}
		}else{
			$user = UserAccount::getLoggedInUser();
		}

		$patronHomeBranch = Location::getUserHomeLocation();
		//Check to see if materials request should be activated
		require_once ROOT_DIR . '/sys/MaterialsRequest.php';

		return array(
			'success'=>true,
			'name'=>ucwords($user->firstname . ' ' . $user->lastname),
			'phone'=>$user->phone,
			'email'=>$user->email,
			'homeLocation'=> isset($patronHomeBranch) ? $patronHomeBranch->code : '',
			'homeLocationId'=> isset($patronHomeBranch) ? $patronHomeBranch->locationId : '',
			'enableMaterialsRequest' => MaterialsRequest::enableMaterialsRequest(true),
		);
	}

	/**
	 * Send output data and exit.
	 *
	 * @param mixed  $data   The response data
	 * @param string $status Status of the request
	 *
	 * @return void
	 * @access public
	 */
	protected function output($data, $status) {
		header('Content-type: application/javascript');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		$output = array('data'=>$data,'status'=>$status);
		echo json_encode($output);
		exit;
	}

	function trackEvent(){
		global $analytics;
		if (!isset($_REQUEST['category']) || !isset($_REQUEST['eventAction'])){
			return 'Must provide a category and action to track an event';
		}
		$analytics->enableTracking();
		$category = strip_tags($_REQUEST['category']);
		$action = strip_tags($_REQUEST['eventAction']);
		$data = isset($_REQUEST['data']) ? strip_tags($_REQUEST['data']) : '';
		$analytics->addEvent($category, $action, $data);
		return true;
	}

	function getHoursAndLocations(){
		//Get a list of locations for the current library
		global $library;
		$tmpLocation = new Location();
		$tmpLocation->libraryId = $library->libraryId;
		$tmpLocation->showInLocationsAndHoursList = 1;
		$tmpLocation->orderBy('isMainBranch DESC, displayName'); // List Main Branches first, then sort by name
		$libraryLocations = array();
		$tmpLocation->find();
		if ($tmpLocation->N == 0){
			//Get all locations
			$tmpLocation = new Location();
			$tmpLocation->showInLocationsAndHoursList = 1;
			$tmpLocation->orderBy('displayName');
			$tmpLocation->find();
		}
		while ($tmpLocation->fetch()){
			$mapAddress = urlencode(preg_replace('/\r\n|\r|\n/', '+', $tmpLocation->address));
			$clonedLocation = clone $tmpLocation;
			$hours = $clonedLocation->getHours();
			foreach ($hours as $key => $hourObj){
				if (!$hourObj->closed){
					$hourString = $hourObj->open;
					list($hour, $minutes) = explode(':', $hourString);
					if ($hour < 12){
						$hourObj->open = +$hour.":$minutes AM"; // remove leading zeros in the hour
					}elseif ($hour == 12){
						$hourObj->open = 'Noon';
					}elseif ($hour == 24){
						$hourObj->open = 'Midnight';
					}else{
						$hour -= 12;
						$hourObj->open = "$hour:$minutes PM";
					}
					$hourString = $hourObj->close;
					list($hour, $minutes) = explode(':', $hourString);
					if ($hour < 12){
						$hourObj->close .= ' AM';
					}elseif ($hour == 12){
						$hourObj->close = 'Noon';
					}elseif ($hour == 24){
						$hourObj->close = 'Midnight';
					}else{
						$hour -= 12;
						$hourObj->close = "$hour:$minutes PM";
					}
				}
				$hours[$key] = $hourObj;
			}
			$libraryLocations[] = array(
				'id' => $tmpLocation->locationId,
				'name' => $tmpLocation->displayName,
				'address' => preg_replace('/\r\n|\r|\n/', '<br>', $tmpLocation->address),
				'phone' => $tmpLocation->phone,
				'map_image' => "http://maps.googleapis.com/maps/api/staticmap?center=$mapAddress&zoom=15&size=200x200&sensor=false&markers=color:red%7C$mapAddress",
				'map_link' => "http://maps.google.com/maps?f=q&hl=en&geocode=&q=$mapAddress&ie=UTF8&z=15&iwloc=addr&om=1&t=m",
				'hours' => $hours
			);
		}

		global $interface;
		$interface->assign('libraryLocations', $libraryLocations);
		return $interface->fetch('AJAX/libraryHoursAndLocations.tpl');
	}

	function getAutoLogoutPrompt(){
		global $interface;
		$masqueradeMode = UserAccount::isUserMasquerading();
		$result = array(
			'title'        => 'Still There?',
			'modalBody'    => $interface->fetch('AJAX/autoLogoutPrompt.tpl'),
			'modalButtons' => "<div id='continueSession' class='btn btn-primary' onclick='continueSession();'>Continue</div>" .
				( $masqueradeMode ?
												"<div id='endSession' class='btn btn-masquerade' onclick='VuFind.Account.endMasquerade()'>End Masquerade</div>" .
												"<div id='endSession' class='btn btn-warning' onclick='endSession()'>Logout</div>"
					:
												"<div id='endSession' class='btn btn-warning' onclick='endSession()'>Logout</div>" )
		);
		return $result;
	}

	function getReturnToHomePrompt(){
		global $interface;
		$result = array(
				'title'        => 'Still There?',
				'modalBody'    => $interface->fetch('AJAX/autoReturnToHomePrompt.tpl'),
				'modalButtons' => "<a id='continueSession' class='btn btn-primary' onclick='continueSession();'>Continue</a>"
		);
		return $result;
	}

	function getPayFinesAfterAction(){
		global $interface,
		       $configArray;
		$result = array(
				'title'        => 'Pay Fines',
				'modalBody'    => $interface->fetch('AJAX/refreshFinesAccountInfo.tpl'),
				'modalButtons' => '<a class="btn btn-primary" href="' .$configArray['Site']['path']. '/MyAccount/Fines?reload">Refresh My Fines Information</a>'
		);
		return $result;
	}
}