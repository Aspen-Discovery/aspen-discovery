<?php

require_once ROOT_DIR . '/Action.php';

class AJAX_JSON extends Action {

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

	/** @noinspection PhpUnused */
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
				'message' => translate(['text'=>'Nothing was deleted, may have been deleted already.', 'isAdminFacing'=>true])
			];
		}else{
			return [
				'title' => translate(['text'=>'Error.', 'isAdminFacing'=>true]),
				'success' => true,
				'message' => translate(['text'=>'The term was deleted successfully.', 'isAdminFacing'=>true])
			];
		}
	}

	/** @noinspection PhpUnused */
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

	/** @noinspection PhpUnused */
	function getTranslationForm(){
		if (UserAccount::userHasPermission('Translate Aspen')){
			$translationTerm = new TranslationTerm();
			$translationTerm->id = $_REQUEST['termId'];
			if ($translationTerm->find(true)){
				global $interface;
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
					'title' => translate(['text'=>'Translate a term', 'isAdminFacing'=>true]),
					'modalBody' => $interface->fetch('Translation/termTranslationForm.tpl'),
					'modalButtons' => "<button class='tool btn btn-primary' onclick='AspenDiscovery.saveTranslation()'>" . translate(['text'=>'Update Translation', 'isAdminFacing'=>true]) . "</button>"
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

	/** @noinspection PhpUnused */
	function saveTranslation(){
		$translationId = strip_tags($_REQUEST['translationId']);
		$newTranslation = $_REQUEST['translation'];
		$translation = new Translation();
		$translation->id = $translationId;
		$result = [
			'success' => false,
			'message' => translate(['text'=>'Unknown Error', 'isPublicFacing'=>true])
		];
		if (UserAccount::userHasPermission('Translate Aspen')){
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

	/** @noinspection PhpUnused */
	function getUserLists(){
		$user = UserAccount::getLoggedInUser();
		$lists = $user->getLists();
		$userLists = array();
		foreach($lists as $current) {
			$userLists[] = array('id' => $current->id, 'title' => $current->title);
		}
		return $userLists;
	}

	/** @noinspection PhpUnused */
	function loginUser(){
		//Login the user.  Must be called via Post parameters.
		global $interface;
		global $logger;
		$logger->log("Starting JSON/loginUser session: " . session_id(), Logger::LOG_DEBUG);
		$isLoggedIn = UserAccount::isLoggedIn();
		if (!$isLoggedIn){
			try{
				$user = UserAccount::login();

				$interface->assign('user', $user); // PLB Assignment Needed before error checking?
				if (!$user || ($user instanceof AspenError)){

					// Expired Card Notice
					if ($user && $user->getMessage() == 'Your library card has expired. Please contact your local library to have your library card renewed.') {
						return array(
							'success' => false,
							'message' => translate(['text' => 'Your library card has expired. Please contact your local library to have your library card renewed.', 'isPublicFacing'=>true])
						);
					}

					// User needs to enroll into 2FA
					if($user && $user->getMessage() == 'You must enroll into two-factor authentication before logging in.') {
						return array(
							'success' => false,
							'enroll2FA' => true
						);
					}

					// User needs to authenticate with 2FA
					if ($user && $user->getMessage() == 'You must authenticate before logging in. Please provide the 6-digit code that was emailed to you.') {
						return array(
							'success' => false,
							'has2FA' => true
						);
					}

					// General Login Error
					/** @var AspenError $error */
					$error = $user;
					$message = ($user instanceof AspenError) ? translate(['text'=>$error->getMessage(),'isPublicFacing'=>true]) : translate(['text'=>"Sorry that login information was not recognized, please try again.",'isPublicFacing'=>true]);
					return array(
						'success' => false,
						'message' => $message
					);
				}else{
					$logger->log("User was logged in successfully session: " . session_id(),Logger::LOG_DEBUG);
				}
			} catch (UnknownAuthenticationMethodException $e) {
				$logger->log("Error logging user in $e",Logger::LOG_DEBUG);
				return array(
					'success' => false,
					'message' => $e->getMessage()
				);
			}
		}else{
			$logger->log("User is already logged in",Logger::LOG_DEBUG);
			$user = UserAccount::getLoggedInUser();
		}

		$patronHomeBranch = Location::getUserHomeLocation();
		//Check to see if materials request should be activated
		require_once ROOT_DIR . '/sys/MaterialsRequest.php';

		return array(
			'success' => true,
			'twoFactor' => false,
			'name' => $user->displayName,
			'phone' => $user->phone,
			'email' => $user->email,
			'homeLocation' => isset($patronHomeBranch) ? $patronHomeBranch->code : '',
			'homeLocationId' => isset($patronHomeBranch) ? $patronHomeBranch->locationId : '',
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

	/** @noinspection PhpUnused */
	function getHoursAndLocations(){
		//Get a list of locations for the current library
		global $library;
		$tmpLocation = new Location();
		$tmpLocation->libraryId = $library->libraryId;
		$tmpLocation->showInLocationsAndHoursList = 1;
		$tmpLocation->orderBy('isMainBranch DESC, displayName'); // List Main Branches first, then sort by name
		$libraryLocations = array();
		$tmpLocation->find();
		if ($tmpLocation->getNumResults() == 0){
			//Get all locations
			$tmpLocation = new Location();
			$tmpLocation->showInLocationsAndHoursList = 1;
			$tmpLocation->orderBy('displayName');
			$tmpLocation->find();
		}

		$locationsToProcess = [];
		while ($tmpLocation->fetch()){
			$locationsToProcess[] = clone $tmpLocation;
		}

		require_once ROOT_DIR . '/sys/Enrichment/GoogleApiSetting.php';
		$googleSettings = new GoogleApiSetting();
		if ($googleSettings->find(true)){
			$mapsKey = $googleSettings->googleMapsKey;
		}else{
			$mapsKey = null;
		}
		require_once ROOT_DIR . '/sys/Parsedown/AspenParsedown.php';
		$parsedown = AspenParsedown::instance();
		$parsedown->setBreaksEnabled(true);
		foreach ($locationsToProcess as $locationToProcess){
			$mapAddress = urlencode(preg_replace('/\r\n|\r|\n/', '+', $locationToProcess->address));
			$hours = $locationToProcess->getHours();
			foreach ($hours as $key => $hourObj){
				if (!$hourObj->closed){
					$hourString = $hourObj->open;
					list($hour, $minutes) = explode(':', $hourString);
					if ($hour < 12){
						if ($hour == 0) {
							$hour += 12;
						}
						$hourObj->open = +$hour.":$minutes AM"; // remove leading zeros in the hour
					}elseif ($hour == 12 && $minutes == '00'){
						$hourObj->open = 'Noon';
					}elseif ($hour == 24 && $minutes == '00'){
						$hourObj->open = 'Midnight';
					}else{
						if ($hour != 12) {
							$hour -= 12;
						}
						$hourObj->open = "$hour:$minutes PM";
					}
					$hourString = $hourObj->close;
					list($hour, $minutes) = explode(':', $hourString);
					if ($hour < 12){
						if ($hour == 0) {
							$hour += 12;
						}
						$hourObj->close = "$hour:$minutes AM";
					}elseif ($hour == 12 && $minutes == '00'){
						$hourObj->close = 'Noon';
					}elseif ($hour == 24 && $minutes == '00'){
						$hourObj->close = 'Midnight';
					}else{
						if ($hour != 12) {
							$hour -= 12;
						}
						$hourObj->close = "$hour:$minutes PM";
					}
				}
				$hours[$key] = $hourObj;
			}
			$libraryLocation = [
				'id' => $locationToProcess->locationId,
				'name' => $locationToProcess->displayName,
				'address' => preg_replace('/\r\n|\r|\n/', '<br>', $locationToProcess->address),
				'phone' => $locationToProcess->phone,
				'tty' => $locationToProcess->tty,
				//'map_image' => "http://maps.googleapis.com/maps/api/staticmap?center=$mapAddress&zoom=15&size=200x200&sensor=false&markers=color:red%7C$mapAddress",
				'hours' => $hours,
				'hasValidHours' => $locationToProcess->hasValidHours(),
				'description' => $parsedown->parse($locationToProcess->description)
			];

			if (!empty($mapsKey)){
				$libraryLocation['map_link'] = "http://maps.google.com/maps?f=q&hl=en&geocode=&q=$mapAddress&ie=UTF8&z=15&iwloc=addr&om=1&t=m&key=$mapsKey";
			}
			$libraryLocations[$locationToProcess->locationId] = $libraryLocation;
		}

		global $interface;
		$interface->assign('libraryLocations', $libraryLocations);
		return $interface->fetch('AJAX/libraryHoursAndLocations.tpl');
	}

	/** @noinspection PhpUnused */
	function getAutoLogoutPrompt(){
		global $interface;
		$masqueradeMode = UserAccount::isUserMasquerading();
		return array(
			'title'        => translate(['text'=>'Still There?','isPublicFacing'=>true]),
			'modalBody'    => $interface->fetch('AJAX/autoLogoutPrompt.tpl'),
			'modalButtons' => "<div id='continueSession' class='btn btn-primary' onclick='continueSession();'>" . translate(['text'=>'Continue','isPublicFacing'=>true]) . "</div>" .
				( $masqueradeMode ?
						"<div id='endSession' class='btn btn-primary' onclick='AspenDiscovery.Account.endMasquerade()'>" . translate(['text' => 'End Masquerade', 'isAdminFacing'=>true]) . "</div>" .
						"<div id='endSession' class='btn btn-warning' onclick='endSession()'>" . translate(['text'=>'Logout', 'isAdminFacing'=>true]) . "</div>"
					:
						"<div id='endSession' class='btn btn-warning' onclick='endSession()'>" . translate(['text'=>'Logout', 'isAdminFacing'=>true]) . "</div>"
				)
		);
	}

	/** @noinspection PhpUnused */
	function getReturnToHomePrompt(){
		global $interface;
		return array(
				'title'        => translate(['text'=>'Still There?','isPublicFacing'=>true]),
				'modalBody'    => $interface->fetch('AJAX/autoReturnToHomePrompt.tpl'),
				'modalButtons' => "<a id='continueSession' class='btn btn-primary' onclick='continueSession();'>" . translate(['text'=>'Continue','isPublicFacing'=>true]) . "</a>"
		);
	}

	/** @noinspection PhpUnused */
	function getPayFinesAfterAction(){
		global $interface;
		return array(
				'title'        => translate(['text'=>'Pay Fines','isPublicFacing'=>true]),
				'modalBody'    => $interface->fetch('AJAX/refreshFinesAccountInfo.tpl'),
				'modalButtons' => '<a class="btn btn-primary" href="/MyAccount/Fines?reload">Refresh My Fines Information</a>'
		);
	}

	/** @noinspection PhpUnused */
	function formatCurrency(){
		$currencyValue = isset($_REQUEST['currencyValue']) ? $_REQUEST['currencyValue'] : 0;

		global $activeLanguage;

		$currencyCode = 'USD';
		$variables = new SystemVariables();
		if ($variables->find(true)){
			$currencyCode = $variables->currencyCode;
		}

		$currencyFormatter = new NumberFormatter( $activeLanguage->locale . '@currency=' . $currencyCode, NumberFormatter::CURRENCY );

		$formattedCurrency = $currencyFormatter->formatCurrency($currencyValue, $currencyCode);

		return [
			'success' => true,
			'formattedValue' => $formattedCurrency
		];
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}