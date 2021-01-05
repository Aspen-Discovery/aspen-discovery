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
			} else if (in_array($method, array('getBookingCalendar'))) {
				header('Content-type: text/html');
				header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
				header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
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
		header("Content-Disposition: attachment; filename={$id}.mrc");
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
	function getPlaceHoldForm()
	{
		global $interface;
		global $library;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			if (isset($_REQUEST['volume'])) {
				$interface->assign('volume', $_REQUEST['volume']);
			}

			if (!$this->setupHoldForm($recordSource, $rememberHoldPickupLocation, $locations)){
				return array(
					'holdFormBypassed' => false,
					'title' => 'Unable to place hold',
					'message' => '<p>This account is not associated with a library, please contact your library.</p>',
					'success' => false
				);
			}

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($id);
			$title = rtrim($marcRecord->getTitle(), ' /');
			$interface->assign('id', $marcRecord->getId());

			//Figure out what types of holds to allow
			$items = $marcRecord->getCopies();
			$format = $marcRecord->getPrimaryFormat();

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
					$results = $user->placeItemHold($id, $_REQUEST['selectedItem'], $user->_homeLocationCode, null);
				} else {
					if (isset($_REQUEST['volume'])) {
						$results = $user->placeVolumeHold($shortId, $_REQUEST['volume'], $user->_homeLocationCode);
					} else {
						$results = $user->placeHold($id, $user->_homeLocationCode, null);
					}
				}
				$results['holdFormBypassed'] = true;

				if ($results['success'] && $library->showWhileYouWait) {
					$recordDriver = RecordDriverFactory::initRecordDriverById($id);
					if ($recordDriver->isValid()) {
						$groupedWorkId = $recordDriver->getPermanentId();
						require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
						$groupedWorkDriver = new GroupedWorkDriver($groupedWorkId);
						$whileYouWaitTitles = $groupedWorkDriver->getWhileYouWait();

						$interface->assign('whileYouWaitTitles', $whileYouWaitTitles);

						if (count($whileYouWaitTitles) > 0) {
							$results['message'] .= "<h3>" . translate("While You Wait") . "</h3>";
							$results['message'] .= $interface->fetch('GroupedWork/whileYouWait.tpl');
						}
					}
				} else {
					$interface->assign('whileYouWaitTitles', []);
				}
			} else if (count($locations) == 0) {
				$results = array(
					'holdFormBypassed' => false,
					'title' => 'Unable to place hold',
					'message' => '<p>Sorry, no copies of this title are available to your account.</p>',
					'success' => false
				);
			} else {
				$results = array(
					'holdFormBypassed' => false,
					'title' => empty($title) ? 'Place Hold' : 'Place Hold on ' . $title,
					'modalBody' => $interface->fetch("Record/hold-popup.tpl"),
					'success' => true
				);
				if ($holdType != 'none') {
					$results['modalButtons'] = "<button type='submit' name='submit' id='requestTitleButton' class='btn btn-primary' onclick='return AspenDiscovery.Record.submitHoldForm();'>" . translate("Submit Hold Request") . "</button>";
				}
			}
		} else {
			$results = array(
				'holdFormBypassed' => false,
				'title' => 'Please login',
				'message' => "You must be logged in.  Please close this dialog and login before placing your hold.",
				'success' => false
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getPlaceHoldEditionsForm()
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
				'title' => 'Place Hold on Alternate Edition?',
				'modalBody' => $interface->fetch('Record/hold-select-edition-popup.tpl'),
				'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.showPlaceHold(\'Record\', \'' . $recordSource . '\', \'' . $id . '\');">No, place a hold on this edition</a>'
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

	/** @noinspection PhpUnused */
	function getPlaceHoldVolumesForm()
	{
		global $interface;
		if (UserAccount::isLoggedIn()) {
			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($id);
			$interface->assign('id', $marcRecord->getId());

			if (!$this->setupHoldForm($recordSource, $rememberHoldPickupLocation, $locations)){
				return array(
					'holdFormBypassed' => false,
					'title' => 'Unable to place hold',
					'modalBody' => '<p>This account is not associated with a library, please contact your library.</p>',
					'modalButtons' => ""
				);
			}

			$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());
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
				'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.placeVolumeHold(\'Record\', \'' . $recordSource . '\', \'' . $id . '\');">' . translate('Place Hold') . '</a>'
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

	/** @noinspection PhpUnused */
	function getBookMaterialForm($errorMessage = null)
	{
		global $interface;
		if (UserAccount::isLoggedIn()) {
			$id = $_REQUEST['id'];

			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$marcRecord = new MarcRecordDriver($id);
			$title = $marcRecord->getTitle();
			$interface->assign('id', $id);
			if ($errorMessage) $interface->assign('errorMessage', $errorMessage);
			$results = array(
				'title' => 'Schedule ' . $title,
				'modalBody' => $interface->fetch("Record/book-materials-form.tpl"),
				'modalButtons' => '<button class="btn btn-primary" onclick="$(\'#bookMaterialForm\').submit()">Schedule Item</button>'
				// Clicking invokes submit event, which allows the validator to act before calling the ajax handler
			);
		} else {
			$results = array(
				'title' => 'Please login',
				'modalBody' => "You must be logged in.  Please close this dialog and login before scheduling this item.",
				'modalButtons' => ""
			);
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getBookingCalendar()
	{
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') !== false) list(, $recordId) = explode(':', $recordId, 2); // remove any prefix from the recordId
		if (!empty($recordId)) {
			$user = UserAccount::getLoggedInUser();
			$catalog = $user->getCatalogDriver();
			return $catalog->getBookingCalendar($recordId);
		}
		return null;
	}

	/** @noinspection PhpUnused */
	function bookMaterial()
	{
		if (!empty($_REQUEST['id'])) {
			$recordId = $_REQUEST['id'];
			if (strpos($recordId, ':') !== false) list(, $recordId) = explode(':', $recordId, 2); // remove any prefix from the recordId
		}
		if (empty($recordId)) {
			return array('success' => false, 'message' => 'Item ID is required.');
		}
		if (isset($_REQUEST['startDate'])) {
			$startDate = $_REQUEST['startDate'];
		} else {
			return array('success' => false, 'message' => 'Start Date is required.');
		}

		$startTime = empty($_REQUEST['startTime']) ? null : $_REQUEST['startTime'];
		$endDate = empty($_REQUEST['endDate']) ? null : $_REQUEST['endDate'];
		$endTime = empty($_REQUEST['endTime']) ? null : $_REQUEST['endTime'];

		$user = UserAccount::getLoggedInUser();
		if ($user) { // The user is already logged in
			return $user->bookMaterial($recordId, $startDate, $startTime, $endDate, $endTime);

		} else {
			return array('success' => false, 'message' => 'User not logged in.');
		}
	}

	function placeHold()
	{
		global $interface;
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') > 0) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			list($source, $shortId) = explode(':', $recordId, 2);
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
					if (isset($_REQUEST['rememberHoldPickupLocation']) && ($_REQUEST['rememberHoldPickupLocation'] == 'true' || $_REQUEST['rememberHoldPickupLocation'] == 'on')){
						if ($user->rememberHoldPickupLocation == false){
							$user->rememberHoldPickupLocation = true;

							//Get the branch id for the hold
							$holdBranch = new Location();
							$holdBranch->code = $pickupBranch;
							if ($holdBranch->find(true)){
								$user->homeLocationId = $holdBranch->locationId;
								$user->_homeLocationCode = $holdBranch->code;
							}
							$user->update();
						}
					}
					$holdType = $_REQUEST['holdType'];

					if (!empty($_REQUEST['cancelDate'])) {
						$cancelDate = $_REQUEST['cancelDate'];
					} else {
						if ($homeLibrary->defaultNotNeededAfterDays <= 0) {
							$cancelDate = null;
						} else {
							//Default to a date 6 months (half a year) in the future.
							$nnaDate = time() + $homeLibrary->defaultNotNeededAfterDays * 24 * 60 * 60;
							$cancelDate = date('m/d/Y', $nnaDate);
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
						$interface->assign('pickupBranch', $pickupBranch);
						$items = $return['items'];
						$interface->assign('items', $items);
						$interface->assign('message', $return['message']);
						$interface->assign('id', $shortId);
						$interface->assign('patronId', $patron->id);
						if (!empty($_REQUEST['autologout'])) $interface->assign('autologout', $_REQUEST['autologout']); // carry user selection to Item Hold Form

						$interface->assign('showDetailedHoldNoticeInformation', $homeLibrary->showDetailedHoldNoticeInformation);
						$interface->assign('treatPrintNoticesAsPhoneNotices', $homeLibrary->treatPrintNoticesAsPhoneNotices);

						// Need to place item level holds.
						$results = array(
							'success' => true,
							'needsItemLevelHold' => true,
							'message' => $interface->fetch('Record/item-hold-popup.tpl'),
							'title' => isset($return['title']) ? $return['title'] : '',
						);
					} else { // Completed Hold Attempt
						$interface->assign('message', $return['message']);
						$interface->assign('success', $return['success']);

						$canUpdateContactInfo = $homeLibrary->allowProfileUpdates == 1;
						// set update permission based on active library's settings. Or allow by default.
						$canChangeNoticePreference = $homeLibrary->showNoticeTypeInProfile == 1;
						// when user preference isn't set, they will be shown a link to account profile. this link isn't needed if the user can not change notification preference.
						$interface->assign('canUpdate', $canUpdateContactInfo);
						$interface->assign('canChangeNoticePreference', $canChangeNoticePreference);
						$interface->assign('showDetailedHoldNoticeInformation', $homeLibrary->showDetailedHoldNoticeInformation);
						$interface->assign('treatPrintNoticesAsPhoneNotices', $homeLibrary->treatPrintNoticesAsPhoneNotices);
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
							'title' => isset($return['title']) ? $return['title'] : '',
						);
						if (isset($_REQUEST['autologout'])) {
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
	function getUploadPDFForm(){
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
			'title' => translate('Upload a PDF'),
			'modalBody' => $interface->fetch("Record/upload-pdf-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadPDFForm\").submit()'>" . translate("Upload PDF") . "</button>"
		];
	}

	/** @noinspection PhpUnused */
	function getUploadSupplementalFileForm(){
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
			'title' => translate('Upload a Supplemental File'),
			'modalBody' => $interface->fetch("Record/upload-supplemental-file-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadSupplementalFileForm\").submit()'>" . translate("Upload File") . "</button>"
		];
	}

	/** @noinspection PhpUnused */
	function uploadPDF(){
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
	function uploadSupplementalFile(){
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
								if (in_array($fileExtension, ['csv', 'doc', 'docx', 'odp', 'ods', 'odt', 'ppt', 'pptx', 'xls', 'xlsx'])){
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
								$result['message'] = "Incorrect file type ($fileType).  Please upload one of the following files: .CSV, .DOC, .DOCX, .ODP, .ODS, .ODT, .PPT, .PPTX, .XLS, .XLSX";
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

	function deleteUploadedFile(){
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
	function showSelectDownloadForm(){
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
			$buttonTitle = translate('Download PDF');
		}else{
			$buttonTitle = translate('Download Supplemental File');
		}
		return [
			'title' => 'Select File to download',
			'modalBody' => $interface->fetch("Record/select-download-file-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#downloadFile\").submit()'>{$buttonTitle}</button>"
		];
	}

	/** @noinspection PhpUnused */
	function showSelectFileToViewForm(){
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

		$buttonTitle = translate('View PDF');
		return [
			'title' => 'Select PDF to View',
			'modalBody' => $interface->fetch("Record/select-view-file-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#viewFile\").submit()'>{$buttonTitle}</button>"
		];
	}

	function getStaffView(){
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
			$result['message'] = 'Could not find that record';
		}
		return $result;
	}

	function setupHoldForm($recordSource, &$rememberHoldPickupLocation, &$locations){
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
		$currentHolds = $ilsSummary['numAvailableHolds'] + $ilsSummary['numUnavailableHolds'];
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
		if (count($linkedUsers)) {
			foreach ($locations as $location) {
				if (count($location->pickupUsers) > 1) {
					$multipleAccountPickupLocations = true;
					break;
				}
			}
		}

		if (!$multipleAccountPickupLocations) {
			$rememberHoldPickupLocation = $user->rememberHoldPickupLocation;
			$interface->assign('rememberHoldPickupLocation', $rememberHoldPickupLocation);
		} else {
			$rememberHoldPickupLocation = false;
		}

		$interface->assign('pickupLocations', $locations);
		$interface->assign('multipleUsers', $multipleAccountPickupLocations); // switch for displaying the account drop-down (used for linked accounts)

		global $library;
		$interface->assign('showHoldCancelDate', $library->showHoldCancelDate);
		$interface->assign('defaultNotNeededAfterDays', $library->defaultNotNeededAfterDays);
		$interface->assign('showDetailedHoldNoticeInformation', $library->showDetailedHoldNoticeInformation);
		$interface->assign('treatPrintNoticesAsPhoneNotices', $library->treatPrintNoticesAsPhoneNotices);

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
	function getBreadcrumbs()
	{
		return [];
	}
}
