<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';

global $configArray;

class Record_AJAX extends Action {

	function launch(): void {
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
			$output = json_encode(['error' => 'invalid_method']);
			echo $output;
		}
	}


	/** @noinspection PhpUnused */
	function downloadMarc(): void {
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
	function getVdxRequestForm(): array {
		global $interface;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$id = $_REQUEST['id'];
			if (strpos($id, ':') > 0) {
				[
					,
					$id,
				] = explode(':', $id);
			}
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
			require_once ROOT_DIR . '/sys/VDX/VdxForm.php';
			$vdxSettings = new VdxSetting();
			if ($vdxSettings->find(true)) {
				$homeLocation = Location::getDefaultLocationForUser();
				if ($homeLocation != null) {
					//Get configuration for the form.
					$vdxForm = new VdxForm();
					$vdxForm->id = $homeLocation->vdxFormId;
					if ($vdxForm->find(true)) {
						//Check to see if the patron is eligible to place holds
						$accountSummary = $user->getAccountSummary();
						if ($accountSummary->isExpired()) {
							$results = [
								'title' => translate([
									'text' => 'Request Title',
									'isPublicFacing' => true,
								]),
								'modalBody' => translate([
									'text' => 'Your account is not eligible to request titles from other libraries.  Please visit the library to renew your account.',
									'isPublicFacing' => true,
								]),
								'modalButtons' => '',
								'success' => true,
							];
						} elseif ($user->isBlockedFromIllRequests()) {
							$results = [
								'title' => translate([
									'text' => 'Request Title',
									'isPublicFacing' => true,
								]),
								'modalBody' => translate([
									'text' => 'Your account is not eligible to request titles from other libraries.  Please visit the library to update your account.',
									'isPublicFacing' => true,
								]),
								'modalButtons' => '',
								'success' => true,
							];
						} else {
							$marcRecord = new MarcRecordDriver($id);

							$interface->assign('vdxForm', $vdxForm);
							$vdxFormFields = $vdxForm->getFormFields($marcRecord);
							$interface->assign('structure', $vdxFormFields);
							$interface->assign('vdxFormFields', $interface->fetch('DataObjectUtil/ajaxForm.tpl'));

							$results = [
								'title' => translate([
									'text' => 'Request Title',
									'isPublicFacing' => true,
								]),
								'modalBody' => $interface->fetch("Record/vdx-request-popup.tpl"),
								'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.submitVdxRequest(\'Record\', \'' . $id . '\')">' . translate([
										'text' => 'Place Request',
										'isPublicFacing' => true,
									]) . '</a>',
								'success' => true,
							];
						}
					} else {
						$results = [
							'title' => translate([
								'text' => 'Invalid Configuration',
								'isPublicFacing' => true,
							]),
							'message' => translate([
								'text' => "Unable to find the specified form.",
								'isPublicFacing' => true,
							]),
							'success' => false,
						];
					}
				} else {
					$results = [
						'title' => translate([
							'text' => 'Invalid Configuration',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => "Unable to determine home library to place request from.",
							'isPublicFacing' => true,
						]),
						'success' => false,
					];
				}
			} else {
				$results = [
					'title' => translate([
						'text' => 'Invalid Configuration',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => "VDX Settings do not exist, please contact the library to make a request.",
						'isPublicFacing' => true,
					]),
					'success' => false,
				];
			}
		} else {
			$results = [
				'title' => translate([
					'text' => 'Please login',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => "You must be logged in.  Please close this dialog and login before placing your request.",
					'isPublicFacing' => true,
				]),
				'success' => false,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function submitVdxRequest(): array {
		if (UserAccount::isLoggedIn()) {
			require_once ROOT_DIR . '/Drivers/VdxDriver.php';
			require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
			require_once ROOT_DIR . '/sys/VDX/VdxForm.php';
			$vdxSettings = new VdxSetting();
			if ($vdxSettings->find(true)) {
				$vdxDriver = new VdxDriver();
				$results = $vdxDriver->submitRequest($vdxSettings, UserAccount::getActiveUserObj(), $_REQUEST);
			} else {
				$results = [
					'title' => translate([
						'text' => 'Invalid Configuration',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => "VDX Settings do not exist, please contact the library to make a request.",
						'isPublicFacing' => true,
					]),
					'success' => false,
				];
			}
		} else {
			$results = [
				'title' => translate([
					'text' => 'Please login',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => "You must be logged in.  Please close this dialog and login before placing your request.",
					'isPublicFacing' => true,
				]),
				'success' => false,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getLocalIllRequestForm(): array {
		global $interface;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$id = $_REQUEST['id'];
			if (strpos($id, ':') > 0) {
				[
					,
					$id,
				] = explode(':', $id);
			}
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			require_once ROOT_DIR . '/sys/InterLibraryLoan/LocalIllForm.php';

			$homeLocation = Location::getDefaultLocationForUser();
			if ($homeLocation != null) {
				//Get configuration for the form.
				$localIllForm = new LocalIllForm();
				$localIllForm->id = $homeLocation->localIllFormId;
				if ($localIllForm->find(true)) {
					//Check to see if the patron is eligible to place holds
					$accountSummary = $user->getAccountSummary();
					if ($accountSummary->isExpired()) {
						$results = [
							'title' => translate([
								'text' => 'Request Title',
								'isPublicFacing' => true,
							]),
							'modalBody' => translate([
								'text' => 'Your account is not eligible to request titles from other libraries.  Please visit the library to renew your account.',
								'isPublicFacing' => true,
							]),
							'modalButtons' => '',
							'success' => true,
						];
					} elseif ($user->isBlockedFromIllRequests()) {
						$results = [
							'title' => translate([
								'text' => 'Request Title',
								'isPublicFacing' => true,
							]),
							'modalBody' => translate([
								'text' => 'Your account is not eligible to request titles from other libraries.  Please visit the library to update your account.',
								'isPublicFacing' => true,
							]),
							'modalButtons' => '',
							'success' => true,
						];
					} elseif (!$user->hasRemainingLocalIllRequests()) {
						$results = [
							'title' => translate([
								'text' => 'No Requests Left',
								'isPublicFacing' => true,
							]),
							'modalBody' => translate([
								'text' => 'You have reached the maximum number of requests that your library allows. You may request additional titles once the titles you have requested are returned.',
								'isPublicFacing' => true,
							]),
							'modalButtons' => '',
							'success' => true,
						];
					} else {
						$marcRecord = new MarcRecordDriver($id);

						if ($marcRecord->isValid()) {
							$interface->assign('localIllForm', $localIllForm);
							$volumeId = $_REQUEST['volume'] ?? null;
							$localIllFormFields = $localIllForm->getFormFields($marcRecord, $volumeId);
							$interface->assign('structure', $localIllFormFields);
							$interface->assign('localIllFormFields', $interface->fetch('DataObjectUtil/ajaxForm.tpl'));

							$results = [
								'title' => translate([
									'text' => 'Request Title',
									'isPublicFacing' => true,
								]),
								'modalBody' => $interface->fetch("Record/local-ill-request-popup.tpl"),
								'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.submitLocalIllRequest(\'Record\', \'' . $id . '\')">' . translate([
										'text' => 'Place Request',
										'isPublicFacing' => true,
									]) . '</a>',
								'success' => true,
							];
						}else{
							$results = [
								'title' => translate([
									'text' => 'Error',
									'isPublicFacing' => true,
								]),
								'modalBody' => translate([
									'text' => 'Could not find the specified record, unable to place request.',
									'isPublicFacing' => true,
								]),
								'modalButtons' => '',
								'success' => true,
							];
						}
					}
				} else {
					$results = [
						'title' => translate([
							'text' => 'Invalid Configuration',
							'isPublicFacing' => true,
						]),
						'message' => translate([
							'text' => "Unable to find the specified form.",
							'isPublicFacing' => true,
						]),
						'success' => false,
					];
				}
			} else {
				$results = [
					'title' => translate([
						'text' => 'Invalid Configuration',
						'isPublicFacing' => true,
					]),
					'message' => translate([
						'text' => "Unable to determine home library to place request from.",
						'isPublicFacing' => true,
					]),
					'success' => false,
				];
			}
		} else {
			$results = [
				'title' => translate([
					'text' => 'Please login',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => "You must be logged in.  Please close this dialog and login before placing your request.",
					'isPublicFacing' => true,
				]),
				'success' => false,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function submitLocalIllRequest(): array {
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$results = $user->submitLocalIllRequest();
		} else {
			$results = [
				'title' => translate([
					'text' => 'Please login',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => "You must be logged in.  Please close this dialog and login before placing your request.",
					'isPublicFacing' => true,
				]),
				'success' => false,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getPlaceHoldForm() {
		global $interface;
		global $library;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$id = $_REQUEST['id'];
			if (strpos($id, ':') > 0) {
				[
					,
					$id,
				] = explode(':', $id);
			}
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			if (isset($_REQUEST['volume'])) {
				$interface->assign('volume', $_REQUEST['volume']);
			}
			$selectedVariationId = -1;
			if (isset($_REQUEST['variationId'])) {
				$interface->assign('variationId', $_REQUEST['variationId']);
				$selectedVariationId = $_REQUEST['variationId'];
			}

			$marcRecord = new MarcRecordDriver($id);

			require_once ROOT_DIR . '/sys/Account/User.php';
			$isOnHold = $user->isRecordOnHold($recordSource, $id);
			$interface->assign('isOnHold', $isOnHold);

			if (!$this->setupHoldForm($recordSource, $rememberHoldPickupLocation, $marcRecord, $locations)) {
				return [
					'holdFormBypassed' => false,
					'title' => translate([
						'text' => 'Unable to place hold',
						'isPublicFacing' => true,
					]),
					'message' => '<p>' . translate([
							'text' => 'This account is not associated with a library, please contact your library.',
							'isPublicFacing' => true,
						]) . '</p>',
					'success' => false,
				];
			}

			$title = rtrim($marcRecord->getTitle(), ' /');
			$interface->assign('id', $marcRecord->getId());

			//Figure out what types of holds to allow
			$items = $marcRecord->getCopies();
			//sort things alphabetically and newest first for periodicals/serials
			if ($marcRecord->isPeriodical()){
				$sorter = function ($a, $b){
					if ($a['shelfLocation'] == $b['shelfLocation']) {
						if ($a['callNumber'] == $b['callNumber']) {
							return 0;
						}
						return strnatcasecmp($b['callNumber'], $a['callNumber']);
					}
					return strnatcasecmp($a['shelfLocation'], $b['shelfLocation']);
				};
				uasort($items, $sorter);
			} else {
				array_multisort(array_column($items, 'description'), SORT_NATURAL, $items);
			}
			$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());
			if (count($relatedRecord->recordVariations) > 1) {
				foreach ($relatedRecord->recordVariations as $variation) {
					if (($selectedVariationId == -1) || ($selectedVariationId == $variation->databaseId)) {
						$formatValue = $variation->manifestation->format;
						global $indexingProfiles;
						$indexingProfile = $indexingProfiles[$marcRecord->getRecordType()];
						$formatMap = $indexingProfile->formatMap;
						//Loop through the format map /** @var FormatMapValue $formatMapValue */
						//Check for a format with a hold type that is not 'none'
						foreach ($formatMap as $formatMapValue) {
							if (strcasecmp($formatMapValue->format, $formatValue) === 0) {
								$holdType = $formatMapValue->holdType;
								if ($holdType != 'none') {
									$format = $formatValue;
								}
							}
						}
					}
				}
				//if we get no result and all hold types are 'none' just return the marc primary format
				if (empty($format)) {
					$format = $marcRecord->getPrimaryFormat();
				}
				//Filter the items according to the selected variation
				if ($selectedVariationId != -1) {
					foreach ($items as $index => $item) {
						if ($item['variationId'] != $selectedVariationId) {
							unset($items[$index]);
						}
					}
				}
			} else {
				$format = $marcRecord->getPrimaryFormat();
			}

			if (!empty($_REQUEST['volume'])) {
				//If we have a volume, we always place a volume hold
				$holdType = 'volume';
			} else {
				global $indexingProfiles;
				$indexingProfile = $indexingProfiles[$marcRecord->getRecordType()];
				$formatMap = $indexingProfile->formatMap;
				/** @var FormatMapValue $formatMapValue */
				//Start assuming we do a bib level hold
				$holdType = 'bib';
				//Check the format of the record to see what types of holds should be allowed
				foreach ($formatMap as $formatMapValue) {
					if (strcasecmp($formatMapValue->format, $format) === 0) {
						$holdType = $formatMapValue->holdType;
						break;
					}
				}
				if ($holdType == 'either') {
					//Check for an override at the library level
					if ($library->treatBibOrItemHoldsAs == 2) {
						$holdType = 'bib';
					} elseif ($library->treatBibOrItemHoldsAs == 3) {
						$holdType = 'item';
					}
				}

				//Check to see if we need to override this to an item hold because there are volumes being handled with an item level hold
				if ($holdType == 'bib') {
					$relatedRecord = $marcRecord->getRelatedRecord();
					if (count($relatedRecord->getUnsuppressedVolumeData()) > 0) {
						$catalogDriver = $marcRecord->getCatalogDriver();
						if ($catalogDriver->treatVolumeHoldsAsItemHolds()) {
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
				//This was done in the case of temporary/permanent branch closures to ensure users pick a new location.
				//TODO: This should maybe be their selected pickup location rather than their home location?
				$homeLocation = $user->getHomeLocation();
				if ($homeLocation != null && $homeLocation->validHoldPickupBranch != 2) {
					if ($holdType == 'bib') {
						$bypassHolds = true;
					} elseif ($holdType != 'none' && count($items) == 1) {
						$bypassHolds = true;
					}
				} else {
					$rememberHoldPickupLocation = false;
					/** @noinspection PhpConditionAlreadyCheckedInspection */
					$interface->assign('rememberHoldPickupLocation', $rememberHoldPickupLocation);
				}
			}

			//If the title is on hold for the user set $byPassHolds to false
			if ($isOnHold) {
				$bypassHolds = false;
			}

			if ($bypassHolds) {
				if (str_contains($id, ':')) {
					[
						,
						$shortId,
					] = explode(':', $id);
				} else {
					$shortId = $id;
				}
				if ($holdType == 'item' && isset($_REQUEST['selectedItem'])) {
					$results = $user->placeItemHold($id, $_REQUEST['selectedItem'], $user->getPickupLocationCode());
				} else {
					if (!empty($_REQUEST['volume'])) {
						$results = $user->placeVolumeHold($shortId, $_REQUEST['volume'], $user->getPickupLocationCode());
					} else {
						$results = $user->placeHold($id, $user->getPickupLocationCode());
					}
				}

				if ($results['success']) {
					//Only for Millennium?
					// TODO: May no longer be needed?
					if (empty($results['needsItemLevelHold'])) {
						$results['title'] = translate([
							'text' => 'Hold Placed Successfully',
							'isPublicFacing' => true,
						]);
					}
				} else {
					$results['title'] = translate([
						'text' => 'Hold Failed',
						'isPublicFacing' => true,
					]);
				}
				$results['holdFormBypassed'] = true;

				//If the result was successful, add a message for where the hold can be picked up with a link to the user preferences page.
				if ($results['success']) {
					$pickupLocation = new Location();
					$pickupLocation->locationId = $user->pickupLocationId;
					$pickupLocationName = '';
					if ($pickupLocation->find(true)) {
						$pickupLocationName = $pickupLocation->displayName;
					}
					if (count($locations) > 1) {
						$results['message'] .= '<br/>' . translate([
								'text' => "When ready, your hold will be available at %1%. You can change your default pickup location <a href='/MyAccount/MyPreferences'>here</a>.",
								1 => $pickupLocationName,
								'isPublicFacing' => true,
							]);
					} else {
						$results['message'] .= '<br/>' . translate([
								'text' => 'When ready, your hold will be available at %1%',
								1 => $pickupLocationName,
								'isPublicFacing' => true,
							]);
					}
					$results['message'] = "<div class='alert alert-success'>" . $results['message'] . '</div>';
				} else {
					if (isset($results['confirmationNeeded']) && $results['confirmationNeeded']) {
						$results['modalButtons'] = '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.confirmHold(\'Record\', \'' . $shortId . '\', ' . $results['confirmationId'] . ')">' . translate([
								'text' => 'Yes, Place Hold',
								'isPublicFacing' => true,
							]) . '</a>';
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
							$results['message'] .= "<h3>" . translate([
									'text' => "While You Wait",
									'isPublicFacing' => true,
								]) . "</h3>";
							$results['message'] .= $interface->fetch('GroupedWork/whileYouWait.tpl');
						}
					}
				} else {
					$interface->assign('whileYouWaitTitles', []);
					if (isset($results['items'])) {
						$results = $this->getItemHoldForm($user->_homeLocationCode, $results, $shortId, $user);
						$results['holdFormBypassed'] = true;
					}
				}
			} elseif (count($locations) == 0) {
				$results = [
					'holdFormBypassed' => false,
					'title' => translate([
						'text' => 'Unable to place hold',
						'isPublicFacing' => true,
					]),
					'message' => '<p>' . translate([
							'text' => 'Sorry, no copies of this title are available to your account.',
							'isPublicFacing' => true,
						]) . '</p>',
					'success' => false,
				];
			} else {
				$results = [
					'holdFormBypassed' => false,
					'title' => empty($title) ? translate([
						'text' => 'Place Hold',
						'isPublicFacing' => true,
					]) : translate([
						'text' => 'Place Hold on %1%',
						1 => $title,
						'isPublicFacing' => true,
					]),
					'modalBody' => $interface->fetch("Record/hold-popup.tpl"),
					'success' => true,
				];
				if ($holdType != 'none') {
					if ($isOnHold) {
						$results['modalButtons'] = "<button type='submit' name='submit' id='requestTitleButton' class='btn btn-primary' onclick='return AspenDiscovery.Record.submitHoldForm();'><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;" . translate([
								'text' => "Yes, Place Hold",
								'isPublicFacing' => true,
							]) . "</button>";
					} else {
						$results['modalButtons'] = "<button type='submit' name='submit' id='requestTitleButton' class='btn btn-primary' onclick='return AspenDiscovery.Record.submitHoldForm();'><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;" . translate([
								'text' => "Submit Hold Request",
								'isPublicFacing' => true,
							]) . "</button>";
					}
				}
			}
		} else {
			$results = [
				'holdFormBypassed' => false,
				'title' => translate([
					'text' => 'Please login',
					'isPublicFacing' => true,
				]),
				'message' => translate([
					'text' => "You must be logged in.  Please close this dialog and login before placing your hold.",
					'isPublicFacing' => true,
				]),
				'success' => false,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getPlaceHoldEditionsForm(): array {
		global $interface;
		if (UserAccount::isLoggedIn()) {

			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);
			if (isset($_REQUEST['volume'])) {
				$interface->assign('volume', $_REQUEST['volume']);
			}

			$marcRecord = new MarcRecordDriver($id);
			$groupedWork = $marcRecord->getGroupedWorkDriver();
			$relatedManifestations = $groupedWork->getRelatedManifestations();
			$format = $marcRecord->getFormat();
			$relatedManifestations = $relatedManifestations[$format[0]];
			$interface->assign('relatedManifestation', $relatedManifestations);
			$results = [
				'title' => translate([
					'text' => 'Place Hold on Alternate Edition?',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch('Record/hold-select-edition-popup.tpl'),
				'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.showPlaceHold(\'Record\', \'' . $recordSource . '\', \'' . $marcRecord->getId() . '\');">No, place a hold on this edition</a>',
			];
		} else {
			$results = [
				'title' => translate([
					'text' => 'Please login',
					'isPublicFacing' => true,
				]),
				'modalBody' => translate([
					'text' => "You must be logged in.  Please close this dialog and login before placing your hold.",
					'isPublicFacing' => true,
				]),
				'modalButtons' => '',
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getPlaceHoldVolumesForm(): array {
		global $interface;
		if (UserAccount::isLoggedIn()) {
			$id = $_REQUEST['id'];
			$recordSource = $_REQUEST['recordSource'];
			$interface->assign('recordSource', $recordSource);

			$marcRecord = new MarcRecordDriver($id);
			$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());
			$interface->assign('id', $marcRecord->getId());

			list($interLibraryLoanType, $treatHoldAsInterLibraryLoanRequest, $homeLocation, $holdGroups) = $marcRecord->getInterLibraryLoanIntegrationInformation($relatedRecord, 'any');

			if (!$this->setupHoldForm($recordSource, $rememberHoldPickupLocation, $marcRecord, $locations)) {
				return [
					'holdFormBypassed' => false,
					'title' => 'Unable to place hold',
					'modalBody' => '<p>This account is not associated with a library, please contact your library.</p>',
					'modalButtons' => "",
				];
			}

			//Get a list of volumes for the record
			require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
			$volumeData = [];
			$volumeDataDB = new IlsVolumeInfo();
			$volumeDataDB->recordId = $marcRecord->getIdWithSource();
			$volumeDataDB->orderBy('displayOrder ASC, displayLabel ASC');
			if ($volumeDataDB->find()) {
				while ($volumeDataDB->fetch()) {
					$volumeData[$volumeDataDB->volumeId] = clone($volumeDataDB);
					$volumeData[$volumeDataDB->volumeId]->setHasLocalItems(false);
				}
			}

			$numItemsWithVolumes = 0;
			$numItemsWithoutVolumes = 0;

			foreach ($relatedRecord->recordVariations as $variation) { // check variations for non-econtent items for records that have both econtent and physical items attached
				if (!($variation->isEContent())) {
					foreach ($variation->getRecords() as $record) {
						foreach ($record->getItems() as $item) {
							if (!$item->isEContent) {
								if (empty($item->volume)) {
									$numItemsWithoutVolumes++;
								} else {
									if (array_key_exists($item->volumeId, $volumeData)) {
										$volumeData[$item->volumeId]->addItem($item);
										if ($item->libraryOwned || $item->locallyOwned) {
											$volumeData[$item->volumeId]->setHasLocalItems(true);
										}
									}
									$numItemsWithVolumes++;
								}
							}
						}
					}
				}
			}

			list($interLibraryLoanType, , $homeLocation, $holdGroups) = $marcRecord->getInterLibraryLoanIntegrationInformation($relatedRecord, 'any');
			if ($interLibraryLoanType != null) {
				foreach ($volumeData as $key => $volumeInfo) {
					if ($marcRecord->oneOrMoreHoldableItemsOwnedByPatronHoldGroups($volumeInfo->getItems(), $holdGroups, 'any', $homeLocation->code)){
						$volumeInfo->setNeedsIllRequest(false);
					}else{
						$volumeInfo->setNeedsIllRequest(true);
					}
				}
			}

			global $library;
			$interface->assign('localSystemName', $library->displayName);
			$interface->assign('hasItemsWithoutVolumes', $numItemsWithoutVolumes > 0);
			$interface->assign('majorityOfItemsHaveVolumes', $numItemsWithVolumes > $numItemsWithoutVolumes);

			//Check to see if we need to place a volume hold
			$alwaysPlaceVolumeHoldWhenVolumesArePresent = $marcRecord->getCatalogDriver()->alwaysPlaceVolumeHoldWhenVolumesArePresent();
			$interface->assign('alwaysPlaceVolumeHoldWhenVolumesArePresent', $alwaysPlaceVolumeHoldWhenVolumesArePresent);

			if ($numItemsWithoutVolumes > 0 && $alwaysPlaceVolumeHoldWhenVolumesArePresent) {
				$blankVolume = new IlsVolumeInfo();
				$blankVolume->displayLabel = translate([
					'text' => 'Untitled Volume',
					'isPublicFacing' => true,
				]);
				$blankVolume->volumeId = '';
				$blankVolume->recordId = $marcRecord->getIdWithSource();
				$blankVolume->relatedItems = '';
				$blankVolume->setHasLocalItems(false);
				foreach ($relatedRecord->getItems() as $item) {
					if (empty($item->volumeId)) {
						if ($item->libraryOwned || $item->locallyOwned) {
							$blankVolume->setHasLocalItems(true);
						}
						$blankVolume->relatedItems .= $item->itemId . '|';
					}
				}

				//Don't add untitled if we there are not local items
				if ($interLibraryLoanType != 'localIll' || $blankVolume->hasLocalItems()) {
					$volumeData[] = $blankVolume;
				}

				$interface->assign('hasItemsWithoutVolumes', false);
				$interface->assign('majorityOfItemsHaveVolumes', true);
			}
			$volumeDataDB = null;
			unset($volumeDataDB);

			//Sort the volumes so locally owned volumes are shown first
			$volumeSorter = function (IlsVolumeInfo $a, IlsVolumeInfo $b) {
				if ($a->hasLocalItems() && !$b->hasLocalItems()) {
					return -1;
				} elseif ($b->hasLocalItems() && !$a->hasLocalItems()) {
					return 1;
				} else {
					if ($a->displayOrder > $b->displayOrder) {
						return 1;
					} elseif ($b->displayOrder > $a->displayOrder) {
						return -1;
					} else {
						return 0;
					}
				}
			};
			global $library;
			if ($library->showVolumesWithLocalCopiesFirst) {
				uasort($volumeData, $volumeSorter);
			}

			$interface->assign('volumes', $volumeData);

			$results = [
				'title' => 'Select a volume to place a hold on',
				'modalBody' => $interface->fetch('Record/hold-select-volume-popup.tpl'),
				'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.placeVolumeHold(\'Record\', \'' . $recordSource . '\', \'' . $id . '\');">' . "<i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;" . translate([
						'text' => 'Place Hold',
						'isPublicFacing' => true,
					]) . '</a>',
			];
		} else {
			$results = [
				'title' => 'Please login',
				'modalBody' => "You must be logged in.  Please close this dialog and login before placing your hold.",
				'modalButtons' => '',
			];
		}
		return $results;
	}

	function placeHold(): array {
		global $interface;
		$recordId = $_REQUEST['id'];
		if (strpos($recordId, ':') > 0) {
			[
				,
				$shortId,
			] = explode(':', $recordId, 2);
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

				$pickupSublocationId = $_REQUEST['pickupSublocation'] ?? false;
				$pickupSublocation = null;
				if ($pickupSublocationId) {
					//In the form this is set as the id of the sublocation in Aspen, but we want to pass the ILS ID
					require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';
					$pickupSublocationObject = new Sublocation();
					$pickupSublocationObject->id = $pickupSublocationId;
					if ($pickupSublocationObject->find(true)) {
						$pickupSublocation = $pickupSublocationObject->ilsId;
					}
				}

				$patron = null;
				if (!empty($_REQUEST['selectedUser'])) {
					$selectedUserId = $_REQUEST['selectedUser'];
					if (is_numeric($selectedUserId)) {
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
					//The block below sets the $patron variable to place the hold through pick-up location. (shouldn't be necessary anymore. plb 10-27-2015)
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
					$results = [
						'success' => false,
						'message' => 'You must select a valid user to place the hold for.',
						'title' => 'Select valid user',
					];
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
						$return = $patron->placeItemHold($shortId, $_REQUEST['selectedItem'], $pickupBranch, $cancelDate, $pickupSublocation);
					} else {
						if ($_REQUEST['volume'] == '~untitled~') {
							$holdType = 'volume';
							$_REQUEST['volume'] = '';
						}
						if (isset($_REQUEST['volume']) && $holdType == 'volume') {
							if ($_REQUEST['volume'] === 'unselected') {
								return [
									'success' => false,
									'message' => 'You must select a volume to place the hold on.',
									'title' => 'Select a volume',
								];
							}else {
								$return = $patron->placeVolumeHold($shortId, $_REQUEST['volume'], $pickupBranch, $pickupSublocation);
							}
						} else {
							$return = $patron->placeHold($shortId, $pickupBranch, $cancelDate, $pickupSublocation);
						}
					}

					if (isset($return['items'])) {
						$results = $this->getItemHoldForm($pickupBranch, $return, $shortId, $patron);
					} else { // Completed Hold Attempt
						$interface->assign('message', $return['message']);
						$interface->assign('success', $return['success']);

						$confirmationNeeded = false;
						if ($return['success']) {
							//Only update to remember hold pickup location and the preferred pickup location if the hold is successful
							if (isset($_REQUEST['rememberHoldPickupLocation']) && ($_REQUEST['rememberHoldPickupLocation'] == 'true' || $_REQUEST['rememberHoldPickupLocation'] == 'on')) {
								if ($patron->rememberHoldPickupLocation == 0) {
									$patron->setRememberHoldPickupLocation(1);
									$patron->update();
								}
								$pickupLocation = new Location();
								if ($pickupLocation->get('code', $pickupBranch)) {
									if ($pickupLocation->locationId != $user->pickupLocationId) {
										$patron->setPickupLocationId($pickupLocation->locationId);
										$patron->update();
									}
								}

								require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';
								$sublocation = new Sublocation();
								if ($sublocation->get('id', $pickupSublocationId)) {
									if ($pickupLocation->locationId == $sublocation->locationId) {
										if ($sublocation->id != $user->pickupSublocationId) {
											$patron->setPickupSublocationId($sublocation->id);
											$patron->update();
										}
									}
								}

							}
						} elseif (isset($return['confirmationNeeded']) && $return['confirmationNeeded']) {
							$confirmationNeeded = true;
						} else {
							//Check to see if we can use Interlibrary Loan.  We only allow Interlibrary Loan if the reason is: "hold not allowed"
							if (array_key_exists('error_code', $return) && (($return['error_code'] == 'hatErrorResponse.17286') || ($return['error_code'] == 'hatErrorResponse.447'))) {
								//Check to see if we can place the hold via Interlibrary Loan
								$illLoanType = $user->getInterlibraryLoanType();
								if ($illLoanType != 'none') {
									$interface->assign('fromHoldError', true);
									$marcRecord = new MarcRecordDriver($recordId);

									$volumeLabel = '';
									$volumeInfo = null;
									if (isset($_REQUEST['volume'])) {
										//Get the name of the volume, so we can add it as a note
										require_once ROOT_DIR . '/sys/ILS/IlsVolumeInfo.php';
										$volumeDataDB = new IlsVolumeInfo();
										$volumeDataDB->volumeId = $_REQUEST['volume'];
										$volumeInfo = $_REQUEST['volume'];
										if ($volumeDataDB->find(true)) {
											$volumeLabel = $volumeDataDB->displayLabel;
										} else {
											$volumeLabel = $_REQUEST['volume'];
										}
									}

									$homeLocation = Location::getDefaultLocationForUser();
									if ($homeLocation != null) {
										if ($illLoanType == 'vdx') {
											require_once ROOT_DIR . '/sys/VDX/VdxForm.php';

											//Get configuration for the form.
											$vdxForm = new VdxForm();
											$vdxForm->id = $homeLocation->vdxFormId;
											if ($vdxForm->find(true)) {
												$interface->assign('vdxForm', $vdxForm);

												$vdxFormFields = $vdxForm->getFormFields($marcRecord, $volumeLabel);
												$interface->assign('structure', $vdxFormFields);
												$interface->assign('vdxFormFields', $interface->fetch('DataObjectUtil/ajaxForm.tpl'));
												return [
													'title' => translate([
														'text' => 'Hold Failed, Request Title?',
														'isPublicFacing' => true,
													]),
													'modalBody' => $interface->fetch("Record/vdx-request-popup.tpl"),
													'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.submitVdxRequest(\'Record\', \'' . $recordId . '\')">' . translate([
															'text' => 'Place Request',
															'isPublicFacing' => true,
														]) . '</a>',
													'success' => true,
													'needsIllRequest' => true,
												];
											}
										} elseif ($illLoanType == 'localIll') {
											require_once ROOT_DIR . '/sys/InterLibraryLoan/LocalIllForm.php';
											//Get configuration for the form.
											$localIllForm = new LocalIllForm();
											$localIllForm->id = $homeLocation->localIllFormId;
											if ($localIllForm->find(true)) {
												$interface->assign('localIllForm', $localIllForm);

												$localIllFormFields = $localIllForm->getFormFields($marcRecord, $volumeInfo);
												$interface->assign('structure', $localIllFormFields);
												$interface->assign('localIllFormFields', $interface->fetch('DataObjectUtil/ajaxForm.tpl'));
												return [
													'title' => translate([
														'text' => 'Hold Failed, Request Title?',
														'isPublicFacing' => true,
													]),
													'modalBody' => $interface->fetch("Record/local-ill-request-popup.tpl"),
													'modalButtons' => '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.submitLocalIllRequest(\'Record\', \'' . $recordId . '\')">' . translate([
															'text' => 'Place Request',
															'isPublicFacing' => true,
														]) . '</a>',
													'success' => true,
													'needsIllRequest' => true,
												];
											}
										}
									}
								}
							}
						}

						$interface->assign('confirmationNeeded', $confirmationNeeded);

						$canUpdateContactInfo = $homeLibrary->allowProfileUpdates == 1;
						// Set the update permission based on active library's settings. Or allow by default.
						$canChangeNoticePreference = $homeLibrary->showNoticeTypeInProfile == 1;
						// when user preference isn't set, they will be shown a link to account profile. this link isn't necessary if the user cannot change notification preference.
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
								if ($groupedWorkDriver->isValid()) {
									$whileYouWaitTitles = $groupedWorkDriver->getWhileYouWait();

									$interface->assign('whileYouWaitTitles', $whileYouWaitTitles);
								} else {
									$interface->assign('whileYouWaitTitles', []);
								}
							}
						} else {
							$interface->assign('whileYouWaitTitles', []);
						}

						$results = [
							'success' => $return['success'],
							'message' => $interface->fetch('Record/hold-success-popup.tpl'),
							'title' => $return['title'] ?? '',
							'confirmationNeeded' => $confirmationNeeded,
						];
						if (isset($return['viewHoldsAction'])) {
							$results['viewHoldsAction'] = $return['viewHoldsAction'];
						}
						if ($confirmationNeeded) {
							$results['modalButtons'] = '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.confirmHold(\'Record\', \'' . $shortId . '\', ' . $return['confirmationId'] . ')">' . translate([
									'text' => 'Yes, Place Hold',
									'isPublicFacing' => true,
								]) . '</a>';
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
				$results = [
					'success' => false,
					'message' => 'No pick-up location is set.  Please choose a Location for the title to be picked up at.',
				];
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
			$results = [
				'success' => false,
				'message' => 'You must be logged in to place a hold.  Please close this dialog and login.',
				'title' => 'Please login',
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function confirmHold(): array {
		$user = UserAccount::getLoggedInUser();
		if ($user) {
			global $interface;
			$recordId = $_REQUEST['id'];
			if (strpos($recordId, ':') > 0) {
				[
					,
					$shortId,
				] = explode(':', $recordId, 2);
			} else {
				$shortId = $recordId;
			}
			$confirmationId = $_REQUEST['confirmationId'];
			$return = $user->confirmHold($recordId, $confirmationId);
			$confirmationNeeded = false;
			if (isset($return['confirmationNeeded']) && $return['confirmationNeeded']) {
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
			} else {
				$interface->assign('whileYouWaitTitles', []);
			}

			$interface->assign('message', $return['message']);
			$results = [
				'success' => $return['success'],
				'message' => $interface->fetch('Record/hold-success-popup.tpl'),
				'title' => $return['title'] ?? '',
				'confirmationNeeded' => $confirmationNeeded,
			];
			if ($confirmationNeeded) {
				$results['modalButtons'] = '<a href="#" class="btn btn-primary" onclick="return AspenDiscovery.Record.confirmHold(\'Record\', \'' . $shortId . '\', ' . $return['confirmationId'] . ')">' . translate([
						'text' => 'Yes, Place Hold',
						'isPublicFacing' => true,
					]) . '</a>';
			}
		} else {
			$results = [
				'title' => 'Please login',
				'message' => "You must be logged in.  Please close this dialog and login before placing your hold.",
				'success' => false,
			];
		}
		return $results;
	}

	/** @noinspection PhpUnused */
	function getUploadPDFForm(): array {
		global $interface;

		$id = $_REQUEST['id'];
		if (strpos($id, ':')) {
			[
				,
				$id,
			] = explode(':', $id);
		}
		$interface->assign('id', $id);

		//Figure out the maximum upload size
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$interface->assign('max_file_size', SystemUtils::file_upload_max_size() / (1024 * 1024));

		return [
			'title' => translate([
				'text' => 'Upload a PDF',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("Record/upload-pdf-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadPDFForm\").submit()'>" . translate([
					'text' => "Upload PDF",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function getUploadSupplementalFileForm(): array {
		global $interface;

		$id = $_REQUEST['id'];
		if (strpos($id, ':')) {
			[
				,
				$id,
			] = explode(':', $id);
		}
		$interface->assign('id', $id);

		//Figure out the maximum upload size
		require_once ROOT_DIR . '/sys/Utils/SystemUtils.php';
		$interface->assign('max_file_size', SystemUtils::file_upload_max_size() / (1024 * 1024));

		return [
			'title' => translate([
				'text' => 'Upload a Supplemental File',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("Record/upload-supplemental-file-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#uploadSupplementalFileForm\").submit()'>" . translate([
					'text' => "Upload File",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function uploadPDF(): array {
		$result = [
			'success' => false,
			'title' => 'Uploading PDF',
			'message' => 'Sorry your pdf could not be uploaded',
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Upload PDFs'))) {
			if (isset($_FILES['pdfFile'])) {
				$uploadedFile = $_FILES['pdfFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
					$result['message'] = "No PDF was uploaded";
				} elseif (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
					$result['message'] = "Error in file upload " . $uploadedFile["error"];
				} else {
					$id = $_REQUEST['id'];
					$recordDriver = RecordDriverFactory::initRecordDriverById($id);
					if (!$recordDriver->isValid()) {
						$result['message'] = "Could not find the record to attach this file to";
					} else {
						//Upload data files
						global $serverName;
						$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/record_pdfs/';
						if (!file_exists($dataPath)) {
							global $configArray;
							if ($configArray['System']['operatingSystem'] == 'windows') {
								if (!mkdir($dataPath, 0777, true)) {
									$result['message'] = 'Could not create the directory on the server';
								}
							} else {
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

								} else {
									$result['message'] = 'Could not save the file on the server';
								}
							} else {
								$result['message'] = 'Incorrect file type.  Please upload a PDF';
							}
						} else {
							$result['message'] = 'A file with this name already exists. Please rename your file.';
						}
					}
				}
			} else {
				$result['message'] = 'No file was uploaded, please try again.';
			}
		}
		if ($result['success']) {
			$result['message'] = 'Your file has been uploaded successfully';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function uploadSupplementalFile(): array {
		$result = [
			'success' => false,
			'title' => 'Uploading Supplemental File',
			'message' => 'Sorry your file could not be uploaded',
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission('Upload Supplemental Files'))) {
			if (isset($_FILES['supplementalFile'])) {
				$uploadedFile = $_FILES['supplementalFile'];
				if (isset($uploadedFile["error"]) && $uploadedFile["error"] == 4) {
					$result['message'] = "No File was uploaded";
				} elseif (isset($uploadedFile["error"]) && $uploadedFile["error"] > 0) {
					$result['message'] = "Error in file upload " . $uploadedFile["error"];
				} else {
					$id = $_REQUEST['id'];
					$recordDriver = RecordDriverFactory::initRecordDriverById($id);
					if (!$recordDriver->isValid()) {
						$result['message'] = "Could not find the record to attach this file to";
					} else {
						//Upload data files
						global $serverName;
						$dataPath = '/data/aspen-discovery/' . $serverName . '/uploads/record_files/';
						if (!file_exists($dataPath)) {
							global $configArray;
							if ($configArray['System']['operatingSystem'] == 'windows') {
								if (!mkdir($dataPath, 0777, true)) {
									$result['message'] = 'Could not create the directory on the server';
								}
							} else {
								if (!mkdir($dataPath, 0755, true)) {
									$result['message'] = 'Could not create the directory on the server';
								}
							}
						}
						$destFullPath = $dataPath . $recordDriver->getId() . '_' . $uploadedFile["name"];
						if (!file_exists($destFullPath)) {
							$fileType = $uploadedFile["type"];
							$fileOk = false;
							if (in_array($fileType, [
								'application/vnd.ms-excel',
								'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
								'application/vnd.oasis.opendocument.spreadsheet',
								'application/msword',
								'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
								'application/vnd.oasis.opendocument.text',
								'text/csv',
								'application/vnd.ms-powerpoint',
								'application/vnd.openxmlformats-officedocument.presentationml.presentation',
								'application/vnd.oasis.opendocument.presentation',
							])) {
								$fileOk = true;
							} elseif ($fileType = 'application/octet-stream') {
								$fileExtension = $uploadedFile["name"];
								$fileExtension = strtolower(substr($fileExtension, strrpos($fileExtension, '.') + 1));
								if (in_array($fileExtension, [
									'csv',
									'doc',
									'docx',
									'odp',
									'ods',
									'odt',
									'pdf',
									'ppt',
									'pptx',
									'xls',
									'xlsx',
								])) {
									$fileOk = true;
								}
							}
							if ($fileOk) {
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

								} else {
									$result['message'] = 'Could not save the file on the server';
								}
							} else {
								$result['message'] = "Incorrect file type ($fileType).  Please upload one of the following files: .CSV, .DOC, .DOCX, .ODP, .ODS, .ODT, .PDF, .PPT, .PPTX, .XLS, .XLSX";
							}
						} else {
							$result['message'] = 'A file with this name already exists. Please rename your file.';
						}
					}
				}
			} else {
				$result['message'] = 'No file was uploaded, please try again.';
			}
		}
		if ($result['success']) {
			$result['message'] = 'Your file has been uploaded successfully';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function deleteUploadedFile(): array {
		$result = [
			'success' => false,
			'title' => 'Deleting Uploaded File',
		];
		if (UserAccount::isLoggedIn() && (UserAccount::userHasPermission([
				'Upload PDFs',
				'Upload Supplemental Files',
			]))) {
			$fileId = $_REQUEST['fileId'];
			$id = $_REQUEST['id'];

			/** @var MarcRecordDriver $recordDriver */
			$recordDriver = RecordDriverFactory::initRecordDriverById($id);
			if ($recordDriver->isValid()) {
				require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
				$recordFile = new RecordFile();
				$recordFile->type = $recordDriver->getRecordType();
				$recordFile->identifier = $recordDriver->getUniqueID();
				$recordFile->fileId = $fileId;
				if ($recordFile->find(true)) {
					require_once ROOT_DIR . '/sys/File/FileUpload.php';
					$fileUpload = new FileUpload();
					$fileUpload->id = $fileId;
					if ($fileUpload->find(true)) {
						if (unlink($fileUpload->fullPath)) {
							$fileUpload->delete();
							$recordFile->delete();
							$result['success'] = true;
							$result['message'] = 'The file was deleted successfully';
						} else {
							$result['message'] = 'Could not delete the file';
						}
					} else {
						$result['message'] = 'Could not find the file to delete';
					}
				} else {
					$result['message'] = 'The file does not appear to be attached to this record';
				}
			} else {
				$result['message'] = 'Could not load the record to delete the file from';
			}
		} else {
			$result['message'] = 'You do not have the correct permissions to delete this file';
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	function showSelectDownloadForm(): array {
		global $interface;

		$id = $_REQUEST['id'];
		$fileType = $_REQUEST['type'];
		$interface->assign('fileType', $fileType);
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if (strpos($id, ':')) {
			[
				,
				$id,
			] = explode(':', $id);
		}
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
		require_once ROOT_DIR . '/sys/File/FileUpload.php';
		$recordFile = new RecordFile();
		$recordFile->type = $recordDriver->getRecordType();
		$recordFile->identifier = $recordDriver->getUniqueID();
		$recordFile->find();
		$validFiles = [];
		while ($recordFile->fetch()) {
			$fileUpload = new FileUpload();
			$fileUpload->id = $recordFile->fileId;
			$fileUpload->type = $fileType;
			if ($fileUpload->find(true)) {
				$validFiles[$recordFile->fileId] = $fileUpload->title;
			}
		}
		asort($validFiles);
		$interface->assign('validFiles', $validFiles);

		if ($fileType == 'RecordPDF') {
			$buttonTitle = translate([
				'text' => 'Download PDF',
				'isPublicFacing' => true,
			]);
		} else {
			$buttonTitle = translate([
				'text' => 'Download Supplemental File',
				'isPublicFacing' => true,
			]);
		}
		return [
			'title' => 'Select File to download',
			'modalBody' => $interface->fetch("Record/select-download-file-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#downloadFile\").submit()'>$buttonTitle</button>",
		];
	}

	/** @noinspection PhpUnused */
	function showSelectFileToViewForm(): array {
		global $interface;

		$id = $_REQUEST['id'];
		$fileType = $_REQUEST['type'];
		$interface->assign('fileType', $fileType);
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if (strpos($id, ':')) {
			[
				,
				$id,
			] = explode(':', $id);
		}
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/ILS/RecordFile.php';
		require_once ROOT_DIR . '/sys/File/FileUpload.php';
		$recordFile = new RecordFile();
		$recordFile->type = $recordDriver->getRecordType();
		$recordFile->identifier = $recordDriver->getUniqueID();
		$recordFile->find();
		$validFiles = [];
		while ($recordFile->fetch()) {
			$fileUpload = new FileUpload();
			$fileUpload->id = $recordFile->fileId;
			$fileUpload->type = $fileType;
			if ($fileUpload->find(true)) {
				$validFiles[$recordFile->fileId] = $fileUpload->title;
			}
		}
		asort($validFiles);
		$interface->assign('validFiles', $validFiles);

		$buttonTitle = translate([
			'text' => 'View PDF',
			'isPublicFacing' => true,
		]);
		return [
			'title' => translate([
				'text' => 'Select PDF to View',
				'isPublicFacing' => true,
			]),
			'modalBody' => $interface->fetch("Record/select-view-file-form.tpl"),
			'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#viewFile\").submit()'>$buttonTitle</button>",
		];
	}

	/** @noinspection PhpUnused */
	function showSelect856ToViewForm(): array {
		global $interface;

		$id = $_REQUEST['id'];
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if ($recordDriver->isValid()) {
			if (strpos($id, ':')) {
				[
					,
					$id,
				] = explode(':', $id);
			}
			$interface->assign('id', $id);

			$validUrls = $recordDriver->getViewable856Links();
			$interface->assign('validUrls', $validUrls);

			$buttonTitle = translate([
				'text' => 'Access Online',
				'isPublicFacing' => true,
			]);
			return [
				'title' => translate([
					'text' => 'Select Link to View',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch("Record/select-view-856-link-form.tpl"),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#view856\").submit()'>$buttonTitle</button>",
			];
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'modalBody' => translate([
					'text' => 'Could not find a record with that id',
					'isPublicFacing' => true,
				]),
				'modalButtons' => "",
			];
		}
	}

	/** @noinspection PhpUnused
	 * @noinspection PhpNoReturnAttributeCanBeAddedInspection
	 */
	function View856(): string {
		global $interface;

		$id = $_REQUEST['id'];
		$linkId = $_REQUEST['linkId'];

		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if ($recordDriver->isValid()) {
			if (strpos($id, ':')) {
				[
					,
					$id,
				] = explode(':', $id);
			}
			$interface->assign('id', $id);

			$validUrls = $recordDriver->getViewable856Links();
			header('Location: ' . $validUrls[$linkId]['url']);
		} else {
			header('Location: ' . "/Record/$id");
		}
		die();
	}

	function getStaffView(): array {
		$result = [
			'success' => false,
			'message' => 'Unknown error loading staff view',
		];
		$id = $_REQUEST['id'];
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if ($recordDriver->isValid()) {
			global $interface;
			$interface->assign('recordDriver', $recordDriver);
			$result = [
				'success' => true,
				'staffView' => $interface->fetch($recordDriver->getStaffView()),
			];
		} else {
			$result['message'] = translate([
				'text' => 'Could not find that record',
				'isPublicFacing' => true,
			]);
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
	function setupHoldForm(string $recordSource, ?bool &$rememberHoldPickupLocation, MarcRecordDriver $marcRecord, ?array &$locations): bool {
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
				if (is_object($location) && count($location->getPickupUsers()) > 1) {
					$multipleAccountPickupLocations = true;
					break;
				}
			}
		}
		$interface->assign('linkedUsers', $linkedUsers);

		//Check to see if the record must be picked up at the holding branch
		$relatedRecord = $marcRecord->getGroupedWorkDriver()->getRelatedRecord($marcRecord->getIdWithSource());
		$pickupAt = $relatedRecord->getHoldPickupSetting();
		$pickupSublocations = [];
		//1 = restrict to owning location
		//2 = restrict to the owning library
		if ($pickupAt > 0) {
			$itemLocations = $marcRecord->getValidPickupLocations($pickupAt);
			//Loop through all pickup locations for the user and remove anything that is not valid for the record
			foreach ($locations as $locationKey => $location) {
				if (is_object($location) && !in_array(strtolower($location->code), $itemLocations)) {
					unset($locations[$locationKey]);
				}
			}
		}

		foreach ($locations as $locationKey => $location) {
			if (is_object($location)) {
				$pickupSublocations[$locationKey] = $user->getValidSublocations($location->locationId);
			}
		}
		$interface->assign('pickupAt', $pickupAt);

		//Check to see if we need to prompt for hold notifications
		//   (Evergreen requires the user to choose how they want to be notified for every hold)
		$promptForHoldNotifications = $user->getCatalogDriver()->isPromptForHoldNotifications();
		$interface->assign('promptForHoldNotifications', $promptForHoldNotifications);
		if ($promptForHoldNotifications) {
			$interface->assign('holdNotificationTemplate', $user->getCatalogDriver()->getHoldNotificationTemplate($user));
		}

		global $library;
		//Check to see if we can bypass the holds popup and just place the hold
		if (!$multipleAccountPickupLocations && !$promptForHoldNotifications && $library->allowRememberPickupLocation) {
			//If the patron's preferred pickup location is not valid, then force them to pick a new location
			$preferredPickupLocationIsValid = false;
			$preferredPickupLocation = null;
			$preferredPickupSublocationIsValid = false;
			foreach ($locations as $location) {
				if (is_object($location) && ($location->locationId == $user->pickupLocationId)) {
					$preferredPickupLocationIsValid = true;
					$preferredPickupLocation = $location;
					break;
				}
			}

			$preferredPickupSublocationIsValid = true;
			if ($preferredPickupLocationIsValid) {
				//The preferred location is valid, check to see if sublocations are in use and if so if the preferred pickup area is valid
				$preferredSublocationsAtPreferredLocation = $preferredPickupLocation->getPickupSublocations($user);
				if (count($preferredSublocationsAtPreferredLocation) > 1) {
					$preferredPickupSublocationIsValid = false;
					require_once ROOT_DIR . '/sys/LibraryLocation/Sublocation.php';
					require_once ROOT_DIR . '/sys/LibraryLocation/SublocationPatronType.php';
					$patronType = $user->getPTypeObj();
					$sublocationLookup = new Sublocation();
					$sublocationLookup->id = $user->pickupSublocationId;
					$sublocationLookup->isValidHoldPickupAreaILS = 1;
					$sublocationLookup->isValidHoldPickupAreaAspen = 1;
					if ($sublocationLookup->find(true)) {
						$sublocationPType = new SublocationPatronType();
						$sublocationPType->patronTypeId = $patronType->id;
						$sublocationPType->sublocationId = $sublocationLookup->id;
						if ($sublocationPType->find(true)) {
							$preferredPickupSublocationIsValid = true;
						}
					}
				}
			}

			if ($preferredPickupLocationIsValid && $preferredPickupSublocationIsValid) {
				$rememberHoldPickupLocation = $user->rememberHoldPickupLocation;
			} else {
				$rememberHoldPickupLocation = false;
			}
		} else {
			$rememberHoldPickupLocation = false;
		}
		$interface->assign('rememberHoldPickupLocation', $rememberHoldPickupLocation);

		$interface->assign('pickupLocations', $locations);
		$interface->assign('pickupSublocations', $pickupSublocations);

		$interface->assign('multipleUsers', $multipleAccountPickupLocations); // switch for displaying the account drop-down (used for linked accounts)

		$interface->assign('showHoldCancelDate', $library->showHoldCancelDate);
		$interface->assign('defaultNotNeededAfterDays', $library->defaultNotNeededAfterDays);
		$interface->assign('allowRememberPickupLocation', $library->allowRememberPickupLocation && !$promptForHoldNotifications);
		$interface->assign('showLogMeOut', $library->showLogMeOutAfterPlacingHolds);

		$activeIP = IPAddress::getActiveIp();
		$subnet = IPAddress::getIPAddressForIP($activeIP);

		if ($subnet !== false) {
			$interface->assign('logMeOutDefault', $subnet->defaultLogMeOutAfterPlacingHoldOn);
		} else {
			$interface->assign('logMeOutDefault', 0);
		}

		$holdDisclaimers = [];
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

	function getBreadcrumbs(): array {
		return [];
	}

	/**
	 * @param $pickupBranch
	 * @param array $return
	 * @param string $shortId
	 * @param $patron
	 * @return array
	 */
	protected function getItemHoldForm($pickupBranch, array $return, string $shortId, $patron): array {
		global $interface;
		$interface->assign('pickupBranch', $pickupBranch);
		$items = $return['items'];
		$interface->assign('items', $items);
		$interface->assign('message', $return['message']);
		$interface->assign('id', $shortId);
		$interface->assign('patronId', $patron->id);
		if (!empty($_REQUEST['autologout'])) {
			$interface->assign('autologout', $_REQUEST['autologout']);
		} // carry user selection to Item Hold Form

		// Need to place item level holds.
		return [
			'success' => true,
			'needsItemLevelHold' => true,
			'message' => $interface->fetch('Record/item-hold-popup.tpl'),
			'title' => translate([
				'text' => 'Select an Item',
				'isPublicFacing' => true,
			]),
			'modalButtons' => "<button type='submit' name='submit' id='requestTitleButton' class='btn btn-primary' onclick='return AspenDiscovery.Record.submitHoldForm();'><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;" . translate([
					'text' => "Submit Hold Request",
					'isPublicFacing' => true,
				]) . "</button>",
		];
	}

	/** @noinspection PhpUnused */
	function forceReindex(): array {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';

		$id = $_REQUEST['id'];
		if (strpos($id, ':') > 0) {
			[
				,
				$id,
			] = explode(':', $id);
		}
		$recordSource = $_REQUEST['recordSource'];

		require_once ROOT_DIR . '/sys/MarcLoader.php';
		if (MarcLoader::marcExistsForILSId("$recordSource:$id")) {
			require_once ROOT_DIR . '/sys/Indexing/RecordIdentifiersToReload.php';
			$recordIdentifierToReload = new RecordIdentifiersToReload();
			$recordIdentifierToReload->type = $recordSource;
			$recordIdentifierToReload->identifier = $id;
			$recordIdentifierToReload->insert();

			return [
				'success' => true,
				'message' => 'This title will be indexed again shortly.',
			];
		} else {
			return [
				'success' => false,
				'message' => 'Unable to mark the title for indexing. Could not find the title.',
			];
		}
	}

	/** @noinspection PhpUnused */
	function showSelectItemToViewForm(): array {
		global $interface;

		$id = $_REQUEST['id'];
		$variationId = $_REQUEST['variationId'];

		/** @var MarcRecordDriver $recordDriver */
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if ($recordDriver->isValid()) {
			if (strpos($id, ':')) {
				[
					,
					$id,
				] = explode(':', $id);
			}
			$interface->assign('id', $id);

			$idWithSource = $recordDriver->getIdWithSource();
			$relatedRecord = $recordDriver->getGroupedWorkDriver()->getRelatedRecordForVariation($idWithSource, $variationId);
			$allItems = $relatedRecord->getItems();
			foreach ($allItems as $index => $item) {
				if (!$item->isEContent) {
					unset ($allItems[$index]);
				}
			}

			$interface->assign('items', $allItems);
			$interface->assign('variationId', $variationId);

			$buttonTitle = translate([
				'text' => 'Access Online',
				'isPublicFacing' => true,
			]);
			return [
				'title' => translate([
					'text' => 'Select Link to View',
					'isPublicFacing' => true,
				]),
				'modalBody' => $interface->fetch("Record/select-view-item-link-form.tpl"),
				'modalButtons' => "<button class='tool btn btn-primary' onclick='$(\"#viewItem\").submit()'><i class='fas fa-external-link-alt' role='presentation'></i> $buttonTitle</button>",
			];
		} else {
			return [
				'success' => false,
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
				'modalBody' => translate([
					'text' => 'Could not find a record with that id',
					'isPublicFacing' => true,
				]),
				'modalButtons' => "",
			];
		}
	}

	/** @noinspection PhpUnused */
	function viewItem(): array {
		$id = $_REQUEST['id'];
		$variationId = $_REQUEST['variationId'];
		$itemId = $_REQUEST['selectedItem'];

		/** @var MarcRecordDriver $recordDriver */
		$recordDriver = RecordDriverFactory::initRecordDriverById($id);
		if ($recordDriver->isValid()) {
			$idWithSource = $recordDriver->getIdWithSource();
			$relatedRecord = $recordDriver->getGroupedWorkDriver()->getRelatedRecordForVariation($idWithSource, $variationId);
			$allItems = $relatedRecord->getItems();
			foreach ($allItems as $index => $item) {
				if (!$item->isEContent) {
					unset ($allItems[$index]);
				}
			}

			foreach ($allItems as $item) {
				if ($item->itemId == $itemId) {
					$relatedUrls = $item->getRelatedUrls();
					foreach ($relatedUrls as $relatedUrl) {
						if (Library::getActiveLibrary()->libKeySettingId != -1 && !empty($relatedUrl['url'])) {
							$libKeyLink = $this->getLibKeyUrl($relatedUrl['url']);
							if (!empty($libKeyLink)) {
								return [
									'success' => true,
									'url' => $libKeyLink
								];
							} else {
								return [
									'success' => true,
									'url' => $relatedUrl['url']
								];
							}
						} else {
							return [
								'success' => true,
								'url' => $relatedUrl['url']
							];
						}
					}
				}
			}
		}
		return [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'modalBody' => translate([
				'text' => 'Could not find the url to direct to',
				'isPublicFacing' => true,
			]),
			'modalButtons' => "",
		];
	}

	private function getLibKeyUrl($uniqueIdentifierList) : ?string {
		require_once ROOT_DIR . "/Drivers/LibKeyDriver.php";
		$libKeyDriver = new LibKeyDriver();
		return $libKeyDriver->getLibKeyResult($uniqueIdentifierList)["data"]["bestIntegratorLink"]["bestLink"];
	}
}

