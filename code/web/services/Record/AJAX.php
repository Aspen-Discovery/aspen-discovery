<?php

require_once ROOT_DIR . '/Action.php';

global $configArray;

class Record_AJAX extends Action
{

	function launch()
	{
		global $timer;
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		$timer->logTime("Starting method $method");
		if (method_exists($this, $method)) {
			// Methods intend to return JSON data
			if ($method == 'downloadMarc') {
				echo $this->$method();
			} else {
				header('Content-type: application/json');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
				echo json_encode($this->$method());
			}
		} else {
			$output = json_encode(array('error' => 'invalid_method'));
			echo $output;
		}
	}


	/** @noinspection PhpUnused */
	function downloadMarc()
	{
		$id = $_REQUEST['id'];
		$marcData = MarcLoader::loadMarcRecordByILSId($id);
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Content-Disposition: attachment; filename=$id.mrc");
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');

		header('Content-Length: ' . strlen($marcData->toRaw()));
		ob_clean();
		flush();
		echo($marcData->toRaw());
	}

	/** @noinspection PhpUnused */
	function getVdxRequestForm() : array
	{
		global $interface;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$id = $_REQUEST['id'];
			if (strpos($id, ':') > 0){
				list(,$id) = explode(':', $id);
			}
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
			require_once ROOT_DIR . '/sys/VDX/VdxForm.php';
			$vdxSettings = new VdxSetting();
			if ($vdxSettings->find(true)){
				$homeLocation = Location::getDefaultLocationForUser();
				if ($homeLocation != null){
					//Get configuration for the form.
					$vdxForm = new VdxForm();
					$vdxForm->id = $homeLocation->vdxFormId;
					if ($vdxForm->find(true)){
						require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
						$marcRecord = new MarcRecordDriver($id);

						$interface->assign('vdxForm', $vdxForm);
						$vdxFormFields = $vdxForm->getFormFields($marcRecord);
						$interface->assign('structure', $vdxFormFields);
						$interface->assign('vdxFormFields', $interface->fetch('DataObjectUtil/ajaxForm.tpl'));

						$results = array(
							'title' => translate(['text'=>'Request Title', 'isPublicFacing'=>true]),
							'modalBody' => $interface->fetch("Record/vdx-request-popup.tpl"),
							'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.submitVdxRequest(\'Record\', \'' . $id . '\')">' . translate(['text'=>'Place Request','isPublicFacing'=>true]) . '</a>',
							'success' => true
						);
					}else{
						$results = array(
							'title' => translate(['text'=>'Invalid Configuration', 'isPublicFacing'=>true]),
							'message' => translate(['text'=>"Unable to find the specified form.", 'isPublicFacing'=>true]),
							'success' => false
						);
					}
				}else{
					$results = array(
						'title' => translate(['text'=>'Invalid Configuration', 'isPublicFacing'=>true]),
						'message' => translate(['text'=>"Unable to determine home library to place request from.", 'isPublicFacing'=>true]),
						'success' => false
					);
				}
			}else{
				$results = array(
					'title' => translate(['text'=>'Invalid Configuration', 'isPublicFacing'=>true]),
					'message' => translate(['text'=>"VDX Settings do not exist, please contact the library to make a request.", 'isPublicFacing'=>true]),
					'success' => false
				);
			}
		} else {
			$results = array(
				'title' => translate(['text'=>'Please login', 'isPublicFacing'=>true]),
				'message' => translate(['text'=>"You must be logged in.  Please close this dialog and login before placing your request.", 'isPublicFacing'=>true]),
				'success' => false
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function submitVdxRequest() : array{
		if (UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/Drivers/VdxDriver.php';
			require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
			require_once ROOT_DIR . '/sys/VDX/VdxForm.php';
			$vdxSettings = new VdxSetting();
			if ($vdxSettings->find(true)){
				$vdxDriver = new VdxDriver();
				$results = $vdxDriver->submitRequest($vdxSettings, UserAccount::getActiveUserObj(), $_REQUEST);
			}else{
				$results = array(
					'title' => translate(['text'=>'Invalid Configuration', 'isPublicFacing'=>true]),
					'message' => translate(['text'=>"VDX Settings do not exist, please contact the library to make a request.", 'isPublicFacing'=>true]),
					'success' => false
				);
			}
		}else {
			$results = array(
				'title' => translate(['text' => 'Please login', 'isPublicFacing' => true]),
				'message' => translate(['text' => "You must be logged in.  Please close this dialog and login before placing your request.", 'isPublicFacing' => true]),
				'success' => false
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getPlaceHoldForm()
	{
		global $interface;
		global $library;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$id = $_REQUEST['id'];
			if (strpos($id, ':') > 0){
				list(,$id) = explode(':', $id);
			}
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			if (isset($_REQUEST['volume'])) {
				$interface->assign('volume', $_REQUEST['volume']);
			}

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($id);

			if (!$this->setupHoldForm($recordSource, $rememberHoldPickupLocation, $marcRecord, $locations)){
				return array(
					'holdFormBypassed' => false,
					'title' => translate(['text' => 'Unable to place hold', 'isPublicFacing'=>true]),
					'message' => '<p>' . translate(['text' => 'This account is not associated with a library, please contact your library.', 'isPublicFacing'=>true]) . '</p>',
					'success' => false
				);
			}

			$title = rtrim($marcRecord->getTitle(), ' /');
			$interface->assign('id', $marcRecord->getId());

			//Figure out what types of holds to allow
			$items = $marcRecord->getCopies();
			$format = $marcRecord->getPrimaryFormat();

			if (isset($_REQUEST['volume'])) {
				$holdType = 'volume';
			}else{
				global $indexingProfiles;
				$indexingProfile = $indexingProfiles[$marcRecord->getRecordType()];
				$formatMap = $indexingProfile->formatMap;
				/** @var FormatMapValue $formatMapValue */
				$holdType = 'bib';
				foreach ($formatMap as $formatMapValue) {
					if (strcasecmp($formatMapValue->format, $format) === 0) {
						$holdType = $formatMapValue->holdType;
						break;
					}
				}
				if ($holdType == 'either'){
					//Check for an override at the library level
					if ($library->treatBibOrItemHoldsAs == 2){
						$holdType = 'bib';
					}elseif ($library->treatBibOrItemHoldsAs == 3){
						$holdType = 'item';
					}
				}

				//Check to see if we need to override this to an item hold because there are volumes being handled with an item level hold
				if ($holdType == 'bib') {
					$relatedRecord = $marcRecord->getRelatedRecord();
					if (count($relatedRecord->getVolumeData()) > 0){
						$catalogDriver = $marcRecord->getCatalogDriver();
						if ($catalogDriver->treatVolumeHoldsAsItemHolds()){
							$holdType = 'item';
						}
					}
				}
			}

			$interface->assign('items', $items);
			$interface->assign('holdType', $holdType);

			//See if we can bypass the holds form.  We can do this if the user wants to automatically use their home location
			//And it's a valid pickup location
			$bypassHolds = false;
			if ($rememberHoldPickupLocation) {
				$homeLocation = $user->getHomeLocation();
				if ($homeLocation != null && $homeLocation->validHoldPickupBranch != 2) {
					if ($holdType == 'bib') {
						$bypassHolds = true;
					} elseif ($holdType != 'none' && count($items) == 1) {
						$bypassHolds = true;
					}
				} else {
					$rememberHoldPickupLocation = false;
					$interface->assign('rememberHoldPickupLocation', $rememberHoldPickupLocation);
				}
			}

			if ($bypassHolds) {
				if (strpos($id, ':') !== false) {
					list(, $shortId) = explode(':', $id);
				} else {
					$shortId = $id;
				}
				if ($holdType == 'item' && isset($_REQUEST['selectedItem'])) {
					$results = $user->placeItemHold($id, $_REQUEST['selectedItem'], $user->getPickupLocationCode());
				} else {
					if (isset($_REQUEST['volume'])) {
						$results = $user->placeVolumeHold($shortId, $_REQUEST['volume'], $user->getPickupLocationCode());
					} else {
						$results = $user->placeHold($id, $user->getPickupLocationCode());
					}
				}
				if ($results['success']){
					if (empty($results['needsItemLevelHold'])){
						$results['title'] = translate(['text' => 'Hold Placed Successfully', 'isPublicFacing'=>true]);
					}
				}else{
					$results['title'] = translate(['text' => 'Hold Failed', 'isPublicFacing'=>true]);
				}
				$results['holdFormBypassed'] = true;

				//If the result was successful, add a message for where the hold can be picked up with a link to the preferences page.
				if ($results['success']){
					$pickupLocation = new Location();
					$pickupLocation->locationId = $user->pickupLocationId;
					$pickupLocationName = '';
					if ($pickupLocation->find(true)){
						$pickupLocationName = $pickupLocation->displayName;
					}
					if (count($locations) > 1) {
						$results['message'] .= '<br/>' . translate(['text'=>"When ready, your hold will be available at %1%, you can change your default pickup location <a href='/MyAccount/MyPreferences'>here</a>.", 1=>$pickupLocationName, 'isPublicFacing'=>true]);
					}else{
						$results['message'] .= '<br/>' . translate(['text'=>'When ready, your hold will be available at %1%', 1=>$pickupLocationName, 'isPublicFacing'=>true]);
					}
					$results['message'] = "<div class='alert alert-success'>" . $results['message'] . '</div>';
				}else{
					if (isset($results['confirmationNeeded']) && $results['confirmationNeeded'] == true){
						$results['modalButtons'] = '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.confirmHold(\'Record\', \'' . $shortId . '\', ' . $results['confirmationId'] . ')">' . translate(['text'=>'Yes, Place Hold','isPublicFacing'=>true]) . '</a>';
					}
				}


				if ($results['success'] && $library->showWhileYouWait) {
					$recordDriver = RecordDriverFactory::initRecordDriverById($id);
					if ($recordDriver->isValid()) {
						$groupedWorkId = $recordDriver->getPermanentId();
						require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
						$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId);
						$whileYouWaitTitles = $groupedWorkDriver->getWhileYouWait();

						$interface->assign('whileYouWaitTitles', $whileYouWaitTitles);

						if (count($whileYouWaitTitles) > 0) {
							$results['message'] .= "<h3>" . translate(['text'=>"While You Wait", 'isPublicFacing'=>true]) . "</h3>";
							$results['message'] .= $interface->fetch('GroupedWork/whileYouWait.tpl');
						}
					}
				} else {
					$interface->assign('whileYouWaitTitles', []);
					if (isset($results['items'])) {
						$results = $this->getItemHoldForm($user->_homeLocationCode, $results, $shortId, $user, $user->getHomeLibrary());
						$results['holdFormBypassed'] = true;
					}
				}
			} else if (count($locations) == 0) {
				$results = array(
					'holdFormBypassed' => false,
					'title' => translate(['text'=>'Unable to place hold', 'isPublicFacing'=>true]),
					'message' => '<p>' . translate(['text'=>'Sorry, no copies of this title are available to your account.', 'isPublicFacing'=>true]) . '</p>',
					'success' => false
				);
			} else {
				$results = array(
					'holdFormBypassed' => false,
					'title' => empty($title) ? translate(['text'=>'Place Hold', 'isPublicFacing'=>true]) : translate(['text'=>'Place Hold on %1%', 1=> $title, 'isPublicFacing'=>true]),
					'modalBody' => $interface->fetch("Record/hold-popup.tpl"),
					'success' => true
				);
				if ($holdType != 'none') {
					$results['modalButtons'] = "<button type='submit' name='submit' id='requestTitleButton' class='btn btn-primary' onclick='return AspenDiscovery.Record.submitHoldForm();'><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;" . translate(['text' => "Submit Hold Request", 'isPublicFacing'=>true]) . "</button>";
				}
			}
		} else {
			$results = array(
				'holdFormBypassed' => false,
				'title' => translate(['text'=>'Please login', 'isPublicFacing'=>true]),
				'message' => translate(['text'=>"You must be logged in.  Please close this dialog and login before placing your hold.", 'isPublicFacing'=>true]),
				'success' => false
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getPlaceHoldEditionsForm() : array
	{
		global $interface;
		if (UserAccount::isLoggedIn()) {

			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			if (isset($_REQUEST['volume'])) {
				$interface->assign('volume', $_REQUEST['volume']);
			}

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($id);
			$groupedWork = $marcRecord->getGroupedWorkDriver();
			$relatedManifestations = $groupedWork->getRelatedManifestations();
			$format = $marcRecord->getFormat();
			$relatedManifestations = $relatedManifestations[$format[0]];
			$interface->assign('relatedManifestation', $relatedManifestations);
			$results = array(
				'title' => translate(['text' => 'Place Hold on Alternate Edition?', 'isPublicFacing'=>true]),
				'modalBody' => $interface->fetch('Record/hold-select-edition-popup.tpl'),
				'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.showPlaceHold(\'Record\', \'' . $recordSource . '\', \'' . $id . '\');">No, place a hold on this edition</a>'
			);
		} else {
			$results = array(
				'title' => translate(['text' => 'Please login', 'isPublicFacing'=>true]),
				'modalBody' => translate(['text' => "You must be logged in.  Please close this dialog and login before placing your hold.", 'isPublicFacing'=>true]),
				'modalButtons' => ''
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getPlaceHoldVolumesForm() : array
	{
		global $interface;
		if (UserAccount::isLoggedIn()) {
			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($id);
			$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());
			$interface->assign('id', $marcRecord->getId());

			if (!$this->setupHoldForm($recordSource, $rememberHoldPickupLocation, $marcRecord, $locations)){
				return array(
					'holdFormBypassed' => false,
					'title' => 'Unable to place hold',
					'modalBody' => '<p>This account is not associated with a library, please contact your library.</p>',
					'modalButtons' => ""
				);
			}

			$numItemsWithVolumes = 0;
			$numItemsWithoutVolumes = 0;
			foreach ($relatedRecord->getItems() as $item){
				if (empty($item->volume)){
					$numItemsWithoutVolumes++;
				}else{
					$numItemsWithVolumes++;
				}
			}

			$interface->assign('hasItemsWithoutVolumes', $numItemsWithoutVolumes > 0);
			$interface->assign('majorityOfItemsHaveVolumes', $numItemsWithVolumes > $numItemsWithoutVolumes);

			//Get a list of volumes for the record
			require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
			$volumeData = array();
			$volumeDataDB = new IlsVolumeInfo();
			$volumeDataDB->recordId = $marcRecord->getIdWithSource();
			$volumeDataDB->orderBy('displayOrder ASC, displayLabel ASC');
			if ($volumeDataDB->find()) {
				while ($volumeDataDB->fetch()) {
					$volumeData[] = clone($volumeDataDB);
				}
			}
			$volumeDataDB = null;
			unset($volumeDataDB);

			$interface->assign('volumes', $volumeData);

			$results = array(
				'title' => 'Select a volume to place a hold on',
				'modalBody' => $interface->fetch('Record/hold-select-volume-popup.tpl'),
				'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.placeVolumeHold(\'Record\', \'' . $recordSource . '\', \'' . $id . '\');">' . translate(['text' => 'Place Hold', 'isPublicFacing'=>true]) . '</a>'
			);
		} else {
			$results = array(
				'title' => 'Please login',
				'modalBody' => "You must be logged in.  Please close this dialog and login before placing your hold.",
				'modalButtons' => ''
			);
		}
		return $results;
	}

	function placeHold() : array
	{
		global $interface;
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') > 0) {
			list(, $shortId) = explode(':', $recordId, 2);
		} else {
			$shortId = $recordId;
		}

		$user = UserAccount::getLoggedInUser();
		if ($user) {
			//The user is already logged in
			$alreadyLoggedOut = false;

			if (!empty($_REQUEST['pickupBranch'])) {
				//Check to see what account we should be placing a hold for
				//Rather than asking the user for this explicitly, we do it based on the pickup location
				$pickupBranch = $_REQUEST['pickupBranch'];

				$patron = null;
				if (!empty($_REQUEST['selectedUser'])) {
					$selectedUserId = $_REQUEST['selectedUser'];
					if (is_numeric($selectedUserId)) { // we expect an id
						if ($user->id == $selectedUserId) {
							$patron = $user;
						} else {
							$linkedUsers = $user->getLinkedUsers();
							foreach ($linkedUsers as $tmpUser) {
								if ($tmpUser->id == $selectedUserId) {
									$patron = $tmpUser;
									break;
								}
							}
						}
					}
				} else {
					//block below sets the $patron variable to place the hold through pick-up location. (shouldn't be needed anymore. plb 10-27-2015)
					$location = new Location();
					$userPickupLocations = $location->getPickupBranches($user);
					foreach ($userPickupLocations as $tmpLocation) {
						if ($tmpLocation->code == $pickupBranch) {
							$patron = $user;
							break;
						}
					}
					if ($patron == null) {
						//Check linked users
						$linkedUsers = $user->getLinkedUsers();
						foreach ($linkedUsers as $tmpUser) {
							$location = new Location();
							$userPickupLocations = $location->getPickupBranches($tmpUser);
							foreach ($userPickupLocations as $tmpLocation) {
								if ($tmpLocation->code == $pickupBranch) {
									$patron = $tmpUser;
									break;
								}
							}
							if ($patron != null) {
								break;
							}
						}
					}
				}
				if ($patron == null) {
					$results = array(
						'success' => false,
						'message' => 'You must select a valid user to place the hold for.',
						'title' => 'Select valid user',
					);
				} else {
					$homeLibrary = $patron->getHomeLibrary();

					$holdType = $_REQUEST['holdType'];

					if (!empty($_REQUEST['cancelDate'])) {
						$cancelDate = $_REQUEST['cancelDate'];
					} else {
						if ($homeLibrary->defaultNotNeededAfterDays <= 0) {
							$cancelDate = null;
						} else {
							//Default to a date based on the default not needed after days in the library configuration.
							$nnaDate = time() + $homeLibrary->defaultNotNeededAfterDays * 24 * 60 * 60;
							$cancelDate = date('Y-m-d', $nnaDate);
						}
					}

					if ($holdType == 'item' && isset($_REQUEST['selectedItem'])) {
						$return = $patron->placeItemHold($shortId, $_REQUEST['selectedItem'], $pickupBranch, $cancelDate);
					} else {
						if (isset($_REQUEST['volume']) && $holdType == 'volume') {
							$return = $patron->placeVolumeHold($shortId, $_REQUEST['volume'], $pickupBranch);
						} else {
							$return = $patron->placeHold($shortId, $pickupBranch, $cancelDate);
						}
					}

					if (isset($return['items'])) {
						$results = $this->getItemHoldForm($pickupBranch, $return, $shortId, $patron, $homeLibrary);
					} else { // Completed Hold Attempt
						$interface->assign('message', $return['message']);
						$interface->assign('success', $return['success']);

						$confirmationNeeded = false;
						if ($return['success']){
							//Only update remember hold pickup location and the preferred pickup location if the  hold is successful
							if (isset($_REQUEST['rememberHoldPickupLocation']) && ($_REQUEST['rememberHoldPickupLocation'] == 'true' || $_REQUEST['rememberHoldPickupLocation'] == 'on')){
								if ($patron->rememberHoldPickupLocation == 0){
									$patron->rememberHoldPickupLocation = 1;
									$patron->update();
								}
							}
							$pickupLocation = new Location();
							if ($pickupLocation->get('code', $pickupBranch)){
								if ($pickupLocation->locationId != $user->pickupLocationId){
									$patron->pickupLocationId = $pickupLocation->locationId;
									$patron->update();
								}
							}
						}else if (isset($return['confirmationNeeded']) && $return['confirmationNeeded']){
							$confirmationNeeded = true;
						}
						$interface->assign('confirmationNeeded', $confirmationNeeded);

						$canUpdateContactInfo = $homeLibrary->allowProfileUpdates == 1;
						// set update permission based on active library's settings. Or allow by default.
						$canChangeNoticePreference = $homeLibrary->showNoticeTypeInProfile == 1;
						// when user preference isn't set, they will be shown a link to account profile. this link isn't needed if the user can not change notification preference.
						$interface->assign('canUpdate', $canUpdateContactInfo);
						$interface->assign('canChangeNoticePreference', $canChangeNoticePreference);
						$interface->assign('profile', $patron);

						//Get the grouped work for the record
						global $library;
						if ($library->showWhileYouWait && !isset($_REQUEST['autologout'])) {
							$recordDriver = RecordDriverFactory::initRecordDriverById($recordId);
							if ($recordDriver->isValid()) {
								$groupedWorkId = $recordDriver->getPermanentId();
								require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
								$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId);
								$whileYouWaitTitles = $groupedWorkDriver->getWhileYouWait();

								$interface->assign('whileYouWaitTitles', $whileYouWaitTitles);
							}
						}else{
							$interface->assign('whileYouWaitTitles', []);
						}

						$results = array(
							'success' => $return['success'],
							'message' => $interface->fetch('Record/hold-success-popup.tpl'),
							'title' => $return['title'] ?? '',
							'confirmationNeeded' => $confirmationNeeded,
						);
						if ($confirmationNeeded){
							$results['modalButtons'] = '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.confirmHold(\'Record\', \'' . $shortId . '\', ' . $return['confirmationId'] . ')">' . translate(['text' => 'Yes, Place Hold', 'isPublicFacing'=>true]) . '</a>';
						}
						if (isset($_REQUEST['autologout']) && $return['success']) {
							$masqueradeMode = UserAccount::isUserMasquerading();
							if ($masqueradeMode) {
								require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
								MyAccount_Masquerade::endMasquerade();
							} else {
								UserAccount::softLogout();
							}
							$results['autologout'] = true;
							$alreadyLoggedOut = true;
						}
					}
				}
			} else {
				$results = array(
					'success' => false,
					'message' => 'No pick-up location is set.  Please choose a Location for the title to be picked up at.',
				);
			}

			if (isset($_REQUEST['autologout']) && !$alreadyLoggedOut && !(isset($results['needsItemLevelHold']) && $results['needsItemLevelHold'])) {
				// Only go through the auto-logout when the holds process is completed. Item level holds require another round of interaction with the user.
				$masqueradeMode = UserAccount::isUserMasquerading();
				if ($masqueradeMode) {
					require_once ROOT_DIR . '/services/MyAccount/Masquerade.php';
					MyAccount_Masquerade::endMasquerade();
				} else {
					UserAccount::softLogout();
				}
				$results['autologout'] = true;
			}
		} else {
			$results = array(
				'success' => false,
				'message' => 'You must be logged in to place a hold.  Please close this dialog and login.',
				'title' => 'Please login',
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function confirmHold() : array
	{
		$user = UserAccount::getLoggedInUser();
		if ($user) {
			global $interface;
			$recordId = $_REQUEST['id'];
			if (strpos($recordId, ':') > 0) {
				list(, $shortId) = explode(':', $recordId, 2);
			} else {
				$shortId = $recordId;
			}
			$confirmationId = $_REQUEST['confirmationId'];
			$return = $user->confirmHold($recordId, $confirmationId);
			$confirmationNeeded = false;
			if (isset($return['confirmationNeeded']) && $return['confirmationNeeded']){
				$confirmationNeeded = true;
			}
			$interface->assign('confirmationNeeded', $confirmationNeeded);

			//Get the grouped work for the record
			global $library;
			if ($library->showWhileYouWait && !isset($_REQUEST['autologout'])) {
				$recordDriver = RecordDriverFactory::initRecordDriverById($recordId);
				if ($recordDriver->isValid()) {
					$groupedWorkId = $recordDriver->getPermanentId();
					require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
					$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId);
					$whileYouWaitTitles = $groupedWorkDriver->getWhileYouWait();

					$interface->assign('whileYouWaitTitles', $whileYouWaitTitles);
				}
			}else{
				$interface->assign('whileYouWaitTitles', []);
			}

			$interface->assign('message', $return['message']);
			$results = array(
				'success' => $return['success'],
				'message' => $interface->fetch('Record/hold-success-popup.tpl'),
				'title' => $return['title'] ?? '',
				'confirmationNeeded' => $confirmationNeeded,
			);
			if ($confirmationNeeded){
				$results['modalButtons'] = '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.confirmHold(\'Record\', \'' . $shortId . '\', ' . $return['confirmationId'] . ')">' . translate(['text' => 'Yes, Place Hold', 'isPublicFacing'=>true]) . '</a>';
			}
		} else {
			$results = array(
				'title' => 'Please login',
				'message' => "You must be logged in.  Please close this dialog and login before placing your hold.",
				'success' => false
			);
		}
		return $results;
	}

		/** @noinspection PhpUnused */
	function getUploadPDFForm() : array {
		global $interface;

		$id = $_REQUEST['id'];
		if (strpos($id, ':')){
			list(,$id) = explode(':', $id);
		}
		$interface->assign('id', $id);

		//Figure out the maximum upload size
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$interface->assign('max_file_size', SystemUtils::file_upload_max_size() / (1024 * 1024));

		return [
			'title' => translate(['text' => 'Upload a PDF', 'isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("Record/upload-pdf-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadPDFForm\").submit()'>" . translate(['text' => "Upload PDF", 'isPublicFacing'=>true]) . "</button>"
		];
	}

	/** @noinspection PhpUnused */
	function getUploadSupplementalFileForm() : array {
		global $interface;

		$id = $_REQUEST['id'];
		if (strpos($id, ':')){
			list(,$id) = explode(':', $id);
		}
		$interface->assign('id', $id);

		//Figure out the maximum upload size
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$interface->assign('max_file_size', SystemUtils::file_upload_max_size() / (1024 * 1024));

		return [
			'title' => translate(['text' => 'Upload a Supplemental File', 'isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("Record/upload-supplemental-file-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadSupplementalFileForm\").submit()'>" . translate(['text' => "Upload File", 'isPublicFacing'=>true]) . "</button>"
		];
	}

	/** @noinspection PhpUnused */
	function uploadPDF() : array {
		$result = [
			'success' => false,
			'title' => 'Uploading PDF',
			'message' => 'Sorry your pdf could not be uploaded'
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Upload PDFs'))){
			if (isset($_FILES['pdfFile'])) {
				$uploadedFile = $_FILES['pdfFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
					$result['message'] = "No PDF was uploaded";
				} else if (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
					$result['message'] =  "Error in file upload " . $uploadedFile["error"];
				} else {
					$id = $_REQUEST['id'];
					$recordDriver = RecordDriverFactory::initRecordDriverById($id);
					if (!$recordDriver->isValid()){
						$result['message'] =  "Could not find the record to attach this file to";
					}else{
						//Upload data files
						global $serverName;
						$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/record_pdfs/';
						if (!file_exists($dataPath)){
							global $configArray;
							if ($configArray['System']['operatingSystem'] == 'windows') {
								if (!mkdir($dataPath, 0777, true)) {
									$result['message'] = 'Could not create the directory on the server';
								}
							}else{
								if (!mkdir($dataPath, 0755, true)) {
									$result['message'] = 'Could not create the directory on the server';
								}
							}
						}
						$destFullPath = $dataPath . $recordDriver->getId() . '_' . $uploadedFile["name"];
						if (!file_exists($destFullPath)) {
							$fileType = $uploadedFile["type"];
							if ($fileType == 'application/pdf') {
								if (copy($uploadedFile["tmp_name"], $destFullPath)) {
									require_once ROOT_DIR . '/sys/File/FileUpload.php';
									$fileUpload = new FileUpload();
									$fileUpload->title = $_REQUEST['title'];
									$fileUpload->fullPath = $destFullPath;
									$fileUpload->type = 'RecordPDF';
									$fileUpload->insert();

									require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
									$recordFile = new RecordFile();
									$recordFile->type = $recordDriver->getRecordType();
									$recordFile->identifier = $recordDriver->getUniqueID();
									$recordFile->fileId = $fileUpload->id;
									$recordFile->insert();

									$result['success'] = true;

								}else{
									$result['message'] = 'Could not save the file on the server';
								}
							} else {
								$result['message'] = 'Incorrect file type.  Please upload a PDF';
							}
						}else{
							$result['message'] = 'A file with this name already exists. Please rename your file.';
						}
					}
				}
			} else {
				$result['message'] = 'No file was uploaded, please try again.';
			}
		}
		if ($result['success']){
			$result['message'] = 'Your file has been uploaded successfully';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function uploadSupplementalFile() : array {
		$result = [
			'success' => false,
			'title' => 'Uploading Supplemental File',
			'message' => 'Sorry your file could not be uploaded'
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Upload Supplemental Files'))){
			if (isset($_FILES['supplementalFile'])) {
				$uploadedFile = $_FILES['supplementalFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
					$result['message'] = "No File was uploaded";
				} else if (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
					$result['message'] =  "Error in file upload " . $uploadedFile["error"];
				} else {
					$id = $_REQUEST['id'];
					$recordDriver = RecordDriverFactory::initRecordDriverById($id);
					if (!$recordDriver->isValid()){
						$result['message'] =  "Could not find the record to attach this file to";
					}else{
						//Upload data files
						global $serverName;
						$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/record_files/';
						if (!file_exists($dataPath)){
							global $configArray;
							if ($configArray['System']['operatingSystem'] == 'windows') {
								if (!mkdir($dataPath, 0777, true)) {
									$result['message'] = 'Could not create the directory on the server';
								}
							}else{
								if (!mkdir($dataPath, 0755, true)) {
									$result['message'] = 'Could not create the directory on the server';
								}
							}
						}
						$destFullPath = $dataPath . $recordDriver->getId() . '_' . $uploadedFile["name"];
						if (!file_exists($destFullPath)) {
							$fileType = $uploadedFile["type"];
							$fileOk = false;
							if (in_array($fileType, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.oasis.opendocument.spreadsheet',
									'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text', 'text/csv',
									'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.oasis.opendocument.presentation'])) {
								$fileOk = true;
							}elseif ($fileType = 'application/octect-stream'){
								$fileExtension = $uploadedFile["name"];
								$fileExtension = strtolower(substr($fileExtension, strrpos($fileExtension, '.') + 1));
								if (in_array($fileExtension, ['csv', 'doc', 'docx', 'odp', 'ods', 'odt', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx'])){
									$fileOk = true;
								}
							}
							if ($fileOk){
								if (copy($uploadedFile["tmp_name"], $destFullPath)) {
									require_once ROOT_DIR . '/sys/File/FileUpload.php';
									$fileUpload = new FileUpload();
									$fileUpload->title = $_REQUEST['title'];
									$fileUpload->fullPath = $destFullPath;
									$fileUpload->type = 'RecordSupplementalFile';
									$fileUpload->insert();

									require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
									$recordFile = new RecordFile();
									$recordFile->type = $recordDriver->getRecordType();
									$recordFile->identifier = $recordDriver->getUniqueID();
									$recordFile->fileId = $fileUpload->id;
									$recordFile->insert();

									$result['success'] = true;

								}else{
									$result['message'] = 'Could not save the file on the server';
								}
							} else {
								$result['message'] = "Incorrect file type ($fileType).  Please upload one of the following files: .CSV, .DOC, .DOCX, .ODP, .ODS, .ODT, .PDF, .PPT, .PPTX, .XLS, .XLSX";
							}
						}else{
							$result['message'] = 'A file with this name already exists. Please rename your file.';
						}
					}
				}
			} else {
				$result['message'] = 'No file was uploaded, please try again.';
			}
		}
		if ($result['success']){
			$result['message'] = 'Your file has been uploaded successfully';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteUploadedFile() : array {
		$result = [
			'success' => false,
			'title' => 'Deleting Uploaded File',
			'message' => 'Unknown error deleting file'
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission(['Upload PDFs', 'Upload Supplemental Files']))){
			$fileId = $_REQUEST['fileId'];
			$id = $_REQUEST['id'];

			/** @var MarcRecordDriver $recordDriver */
			$recordDriver = RecordDriverFactory::initRecordDriverById($id);
			if ($recordDriver->isValid()){
				require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
				$recordFile = new RecordFile();
				$recordFile->type = $recordDriver->getRecordType();
				$recordFile->identifier = $recordDriver->getUniqueID();
				$recordFile->fileId = $fileId;
				if ($recordFile->find(true)){
					require_once ROOT_DIR . '/sys/File/FileUpload.php';
					$fileUpload = new FileUpload();
					$fileUpload->id = $fileId;
					if ($fileUpload->find(true)){
						if (unlink($fileUpload->fullPath)){
							$fileUpload->delete();
							$recordFile->delete();
							$result['success'] = true;
							$result['message'] = 'The file was deleted successfully';
						}else{
							$result['message'] = 'Could not delete the file';
						}
					}else{
						$result['message'] = 'Could not find the file to delete';
					}
				}else {
					$result['message'] = 'The file does not appear to be attached to this record';
				}
			}else{
				$result['message'] = 'Could not load the record to delete the file from';
			}
		}else{
			$result['message'] = 'You do not have the correct permissions to delete this file';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function showSelectDownloadForm() : array {
		global $interface;

		$id = $_REQUEST['id'];
		$fileType = $_REQUEST['type'];
		$interface->assign('fileType', $fileType);
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if (strpos($id, ':')){
			list(,$id) = explode(':', $id);
		}
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
		require_once ROOT_DIR . '/sys/File/FileUpload.php';
		$recordFile = new RecordFile();
		$recordFile->type = $recordDriver->getRecordType();
		$recordFile->identifier = $recordDriver->getUniqueID();
		$recordFile->find();
		$validFiles = [];
		while ($recordFile->fetch()){
			$fileUpload = new FileUpload();
			$fileUpload->id = $recordFile->fileId;
			$fileUpload->type = $fileType;
			if ($fileUpload->find(true)){
				$validFiles[$recordFile->fileId] = $fileUpload->title;
			}
		}
		asort($validFiles);
		$interface->assign('validFiles', $validFiles);

		if ($fileType == 'RecordPDF'){
			$buttonTitle = translate(['text' => 'Download PDF', 'isPublicFacing'=>true]);
		}else{
			$buttonTitle = translate(['text' => 'Download Supplemental File', 'isPublicFacing'=>true]);
		}
		return [
			'title' => 'Select File to download',
			'modalBody' => $interface->fetch("Record/select-download-file-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#downloadFile\").submit()'>$buttonTitle</button>"
		];
	}

	/** @noinspection PhpUnused */
	function showSelectFileToViewForm() : array {
		global $interface;

		$id = $_REQUEST['id'];
		$fileType = $_REQUEST['type'];
		$interface->assign('fileType', $fileType);
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if (strpos($id, ':')){
			list(,$id) = explode(':', $id);
		}
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
		require_once ROOT_DIR . '/sys/File/FileUpload.php';
		$recordFile = new RecordFile();
		$recordFile->type = $recordDriver->getRecordType();
		$recordFile->identifier = $recordDriver->getUniqueID();
		$recordFile->find();
		$validFiles = [];
		while ($recordFile->fetch()){
			$fileUpload = new FileUpload();
			$fileUpload->id = $recordFile->fileId;
			$fileUpload->type = $fileType;
			if ($fileUpload->find(true)){
				$validFiles[$recordFile->fileId] = $fileUpload->title;
			}
		}
		asort($validFiles);
		$interface->assign('validFiles', $validFiles);

		$buttonTitle = translate(['text' => 'View PDF', 'isPublicFacing'=>true]);
		return [
			'title' => translate(['text' => 'Select PDF to View', 'isPublicFacing'=>true]),
			'modalBody' => $interface->fetch("Record/select-view-file-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#viewFile\").submit()'>$buttonTitle</button>"
		];
	}

	function getStaffView() : array {
		$result = [
			'success' => false,
			'message' => 'Unknown error loading staff view'
		];
		$id = $_REQUEST['id'];
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
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

	/**
	 * @param string $recordSource
	 * @param bool $rememberHoldPickupLocation
	 * @param MarcRecordDriver $marcRecord
	 * @param Location[] $locations
	 * @return bool
	 */
	function setupHoldForm($recordSource, &$rememberHoldPickupLocation, $marcRecord, &$locations) : bool {
		global $interface;
		$user = UserAccount::getLoggedInUser();
		if ($user->getCatalogDriver() == null) {
			return false;
		}
		//Get information to show a warning if the user does not have sufficient holds
		require_once ROOT_DIR . '/sys/Account/PType.php';
		$maxHolds = -1;
		//Determine if we should show a warning
		$ptype = new PType();
		$ptype->pType = UserAccount::getUserPType();
		if ($ptype->find(true)) {
			$maxHolds = $ptype->maxHolds;
		}
		$interface->assign('maxHolds', $maxHolds);
		$ilsSummary = $user->getCatalogDriver()->getAccountSummary($user);
		$currentHolds = $ilsSummary->getNumHolds();
		$interface->assign('currentHolds', $currentHolds);
		//TODO: this check will need to account for linked accounts now
		if ($maxHolds != -1 && ($currentHolds + 1 > $maxHolds)) {
			$interface->assign('showOverHoldLimit', true);
		}

		//Check to see if the user has linked users that we can place holds for as well
		//If there are linked users, we will add pickup locations for them as well
		$locations = $user->getValidPickupBranches($recordSource);
		$multipleAccountPickupLocations = false;
		$linkedUsers = $user->getLinkedUsers();
		if (count($linkedUsers) > 0) {
			foreach ($locations as $location) {
				if (is_object($location) && count($location->pickupUsers) > 1) {
					$multipleAccountPickupLocations = true;
					break;
				}
			}
		}

		//Check to see if the record must be picked up at the holding branch
		$pickupAt = 0;
		$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());
		$format = $marcRecord->getPrimaryFormat();
		global $indexingProfiles;
		$indexingProfile = $indexingProfiles[$relatedRecord->source];
		$formatMap = $indexingProfile->formatMap;
		/** @var FormatMapValue $formatMapValue */
		foreach ($formatMap as $formatMapValue) {
			if (strcasecmp($formatMapValue->format, $format) === 0) {
				$pickupAt = max($pickupAt, $formatMapValue->pickupAt);
				break;
			}
		}

		//If we have to pickup at the holding branch, filter the list of available pickup locations to
		//only include locations where the item is
		if ($pickupAt > 0){
			$itemLocations = [];
			$items = $relatedRecord->getItems();
			foreach ($items as $item){
				if ($pickupAt == 2){
					//Item can be picked up at any branch within the library
					if (!isset($itemLocations[$item->locationCode])) {
						$locationForLocationCode = new Location();
						$locationForLocationCode->code = $item->locationCode;
						if ($locationForLocationCode->find(true)){
							$libraryForLocation = $locationForLocationCode->getParentLibrary();
							foreach ($libraryForLocation->getLocations() as $libraryBranch){
								$itemLocations[$libraryBranch->code] = $libraryBranch->code;
							}
						}
					}
				}else{
					//Item can be picked up at just the owning branch
					$itemLocations[$item->locationCode] = $item->locationCode;
				}
			}


			foreach($locations as $locationKey => $location){
				if (is_object($location) && !in_array($location->code, $itemLocations)){
					unset($locations[$locationKey]);
				}
			}
		}
		$interface->assign('pickupAt', $pickupAt);

		//Check to see if we need to prompt for hold notifications
		$promptForHoldNotifications = $user->getCatalogDriver()->isPromptForHoldNotifications();
		$interface->assign('promptForHoldNotifications', $promptForHoldNotifications);
		if ($promptForHoldNotifications) {
			$interface->assign('holdNotificationTemplate', $user->getCatalogDriver()->getHoldNotificationTemplate());
		}

		global $library;
		if (!$multipleAccountPickupLocations && !$promptForHoldNotifications && $library->allowRememberPickupLocation) {
			//If the patron's preferred pickup location is not valid then force them to pick a new location
			$preferredPickupLocationIsValid = false;
			foreach ($locations as $location){
				if (is_object($location) && ($location->locationId == $user->pickupLocationId)){
					$preferredPickupLocationIsValid = true;
					break;
				}
			}
			if ($preferredPickupLocationIsValid) {
				$rememberHoldPickupLocation = $user->rememberHoldPickupLocation;
			}else{
				$rememberHoldPickupLocation = false;
			}
		} else {
			$rememberHoldPickupLocation = false;
		}
		$interface->assign('rememberHoldPickupLocation', $rememberHoldPickupLocation);

		$interface->assign('pickupLocations', $locations);
		$interface->assign('multipleUsers', $multipleAccountPickupLocations); // switch for displaying the account drop-down (used for linked accounts)

		$interface->assign('showHoldCancelDate', $library->showHoldCancelDate);
		$interface->assign('defaultNotNeededAfterDays', $library->defaultNotNeededAfterDays);
		$interface->assign('allowRememberPickupLocation', $library->allowRememberPickupLocation && !$promptForHoldNotifications);
		$interface->assign('showLogMeOut', $library->showLogMeOutAfterPlacingHolds);

		$activeIP = IPAddress::getActiveIp();
		$subnet = IPAddress::getIPAddressForIP($activeIP);

		if ($subnet != false) {
			$interface->assign('logMeOutDefault', $subnet->defaultLogMeOutAfterPlacingHoldOn);
		} else {
			$interface->assign('logMeOutDefault', 0);
		}

		$holdDisclaimers = array();
		$patronLibrary = $user->getHomeLibrary();
		if ($patronLibrary == null) {
			return false;
		}
		if (strlen($patronLibrary->holdDisclaimer) > 0) {
			$holdDisclaimers[$patronLibrary->displayName] = $patronLibrary->holdDisclaimer;
		}
		foreach ($linkedUsers as $linkedUser) {
			$linkedLibrary = $linkedUser->getHomeLibrary();
			if (strlen($linkedLibrary->holdDisclaimer) > 0) {
				$holdDisclaimers[$linkedLibrary->displayName] = $linkedLibrary->holdDisclaimer;
			}
		}

		$interface->assign('holdDisclaimers', $holdDisclaimers);

		return true;
	}
	function getBreadcrumbs() : array
	{
		return [];
	}

	/**
	 * @param $pickupBranch
	 * @param array $return
	 * @param string $shortId
	 * @param $patron
	 * @param Library|null $homeLibrary
	 * @return array
	 */
	protected function getItemHoldForm($pickupBranch, array $return, string $shortId, $patron, ?Library $homeLibrary): array
	{
		global $interface;
		$interface->assign('pickupBranch', $pickupBranch);
		$items = $return['items'];
		$interface->assign('items', $items);
		$interface->assign('message', $return['message']);
		$interface->assign('id', $shortId);
		$interface->assign('patronId', $patron->id);
		if (!empty($_REQUEST['autologout'])) $interface->assign('autologout', $_REQUEST['autologout']); // carry user selection to Item Hold Form

		// Need to place item level holds.
		return array(
			'success' => true,
			'needsItemLevelHold' => true,
			'message' => $interface->fetch('Record/item-hold-popup.tpl'),
			'title' => translate(['text' => 'Select an Item', 'isPublicFacing'=>true]),
			'modalButtons' => "<button type='submit' name='submit' id='requestTitleButton' class='btn btn-primary' onclick='return AspenDiscovery.Record.submitHoldForm();'><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;" . translate(['text' => "Submit Hold Request", 'isPublicFacing'=>true]) . "</button>"
		);
	}
}
