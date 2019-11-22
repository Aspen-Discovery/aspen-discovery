<?php

require_once ROOT_DIR . '/Action.php';

class AJAX_JSON extends Action {

	// define some status constants
 	const STATUS_OK        = 'OK';           // good
	const STATUS_ERROR     = 'ERROR';        // bad
	const STATUS_NEED_AUTH = 'NEED_AUTH';    // must login first

	function launch()
	{
		//header('Content-type: application/json');
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

		$method = $_GET['method'];
		if (method_exists($this, $method)) {
			if ($method == 'getHoursAndLocations'){
				header('Content-type: text/html');
				$output = $this->$method();
			}elseif (in_array($method, array('getAutoLogoutPrompt', 'getReturnToHomePrompt', 'getPayFinesAfterAction', 'getTranslationForm', 'saveTranslation', 'getLanguagePreferencesForm', 'saveLanguagePreference', 'deleteTranslationTerm'))) {
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

	function deleteTranslationTerm(){
		$termId = $_REQUEST['termId'];
		$translation = new Translation();
		$translation->termId = $termId;
		$numDeleted = $translation->delete(true);

		$term = new TranslationTerm();
		$term->id = $termId;
		$numDeleted += $term->delete(true);

		if ($numDeleted == 0){
			return [
				'success' => false,
				'message' => 'Nothing was deleted, may have been deleted already.'
			];
		}else{
			return [
				'success' => true,
				'message' => 'The term was deleted successfully.'
			];
		}
	}

	function saveLanguagePreference(){
		if (UserAccount::isLoggedIn()){
			$userObj = UserAccount::getActiveUserObj();
			$userObj->searchPreferenceLanguage = $_REQUEST['searchPreferenceLanguage'];
			$userObj->update();
			return [
				'success' => true,
				'message' => 'Your preferences were updated.  You can make changes to this setting within your account setting.'
			];
		}else{
			setcookie('searchPreferenceLanguage', $_REQUEST['searchPreferenceLanguage'], 0, '/');
			return [
				'success' => true,
				'message' => ''
			];
		}
	}

	function getTranslationForm(){
		if (UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('translator')){
			$translationTerm = new TranslationTerm();
			$translationTerm->id = $_REQUEST['termId'];
			if ($translationTerm->find(true)){
				global $interface;
				/** @var Language $activeLanguage */
				global $activeLanguage;
				$interface->assign('translationTerm', $translationTerm);
				$translation = new Translation();
				$translation->termId = $translationTerm->id;
				$translation->languageId = $activeLanguage->id;
				if ($translation->find(true)){
					$interface->assign('translation', $translation);
				}
				//English is always 1.  If we are not in english mode, show english as well for reference
				if ($activeLanguage->id != 1){
					$englishTranslation = new Translation();
					$englishTranslation->termId = $translationTerm->id;
					$englishTranslation->languageId = 1;
					if ($englishTranslation->find(true)){
						$interface->assign('englishTranslation', $englishTranslation);
					}
				}

				$result = [
					'title' => translate('Translate a term'),
					'modalBody' => $interface->fetch('Translation/termTranslationForm.tpl'),
					'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.saveTranslation()'>" . translate('Update Translation') . "</button>"
				];
			}else{
				$result = [
					'success' => false,
					'message' => 'No translation term was found'
				];
			}
		}else{
			$result = [
				'success' => false,
				'message' => 'You do not have permissions for this functionality'
			];
		}


		return $result;
	}

	function saveTranslation(){
		$translationId = strip_tags($_REQUEST['translationId']);
		$newTranslation = $_REQUEST['translation'];
		$translation = new Translation();
		$translation->id = $translationId;
		$result = [
			'success' => false,
			'message' => 'Unknown Error'
		];
		if (UserAccount::userHasRole('opacAdmin') || UserAccount::userHasRole('translator')){
			if ($translation->find(true)){
				$translation->setTranslation($newTranslation);
				$result = [
					'success' => true,
					'message' => 'Successfully updated the translation'
				];
			}else{
				$result['message'] = 'Could not find the translation';
			}
		}else{
			$result['message'] = 'You do not have permissions to translate';
		}

		return $result;
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
			try{
				$user = UserAccount::login();

				$interface->assign('user', $user); // PLB Assignment Needed before error checking?
				if (!$user || ($user instanceof AspenError)){

					// Expired Card Notice
					if ($user && $user->getMessage() == 'expired_library_card') {
						return array(
							'success' => false,
							'message' => translate('expired_library_card')
						);
					}

					// General Login Error
					/** @var AspenError $error */
					$error = $user;
					$message = ($user instanceof AspenError) ? translate($error->getMessage()) : translate("Sorry that login information was not recognized, please try again.");
					return array(
						'success' => false,
						'message' => $message
					);
				}
			} catch (UnknownAuthenticationMethodException $e) {
				return array(
					'success' => false,
					'message' => $e->getMessage()
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
			'enableMaterialsRequest' => MaterialsRequest::enableAspenMaterialsRequest(true),
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
				//'map_image' => "http://maps.googleapis.com/maps/api/staticmap?center=$mapAddress&zoom=15&size=200x200&sensor=false&markers=color:red%7C$mapAddress",
				'map_link' => "http://maps.google.com/maps?f=q&hl=en&geocode=&q=$mapAddress&ie=UTF8&z=15&iwloc=addr&om=1&t=m",
				'hours' => $hours,
                'hasValidHours' => $tmpLocation->hasValidHours()
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
												"<div id='endSession' class='btn btn-masquerade' onclick='AspenDiscovery.Account.endMasquerade()'>End Masquerade</div>" .
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
		global $interface;
		$result = array(
				'title'        => 'Pay Fines',
				'modalBody'    => $interface->fetch('AJAX/refreshFinesAccountInfo.tpl'),
				'modalButtons' => '<a class="btn btn-primary" href="/MyAccount/Fines?reload">Refresh My Fines Information</a>'
		);
		return $result;
	}
}