<?php
/**
 *
 * Copyright (C) Anythink Libraries 2012.
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
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 *
 */

require_once ROOT_DIR . "/Action.php";
require_once ROOT_DIR . "/sys/MaterialsRequest.php";

/**
 * MaterialsRequest Home Page, displays an existing Materials Request.
 */
class MaterialsRequest_NewRequest extends Action
{

	function launch()
	{
		global /** @var Location $locationSingleton */
		$configArray,
		$interface,
		$library,
		$locationSingleton;

		if (!UserAccount::isLoggedIn()) {
			header('Location: ' . $configArray['Site']['path'] . '/MyAccount/Home?followupModule=MaterialsRequest&followupAction=NewRequest');
			exit;
		} else {
			// Hold Pick-up Locations
			$locations = $locationSingleton->getPickupBranches(UserAccount::getActiveUserObj(), UserAccount::getUserHomeLocationId());

			$pickupLocations = array();
			foreach ($locations as $curLocation) {
				$pickupLocations[] = array(
					'id' => $curLocation->locationId,
					'displayName' => $curLocation->displayName,
					'selected' => $curLocation->selected,
				);
			}
			$interface->assign('pickupLocations', $pickupLocations);

			//Get a list of formats to show
			$availableFormats = MaterialsRequest::getFormats();
			$interface->assign('availableFormats', $availableFormats);

			//Setup a default title based on the search term
			$interface->assign('new', true);
			$request                         = new MaterialsRequest();
			$request->placeHoldWhenAvailable = true; // set the place hold option on by default
			$request->illItem                = true; // set the place hold option on by default
			if (isset($_REQUEST['lookfor']) && strlen($_REQUEST['lookfor']) > 0) {
				$searchType = isset($_REQUEST['basicType']) ? $_REQUEST['basicType'] : (isset($_REQUEST['type']) ? $_REQUEST['type'] : 'Keyword');
				if (strcasecmp($searchType, 'author') == 0) {
					$request->author = $_REQUEST['lookfor'];
				} else {
					$request->title = $_REQUEST['lookfor'];
				}
			}

			$user = UserAccount::getActiveUserObj();
			if ($user) {
				$request->phone = str_replace(array('### TEXT ONLY ', '### TEXT ONLY'), '', $user->phone);
				if ($user->email != 'notice@salidalibrary.org') {
					$request->email = $user->email;
				}
			}

			$interface->assign('materialsRequest', $request);

			$interface->assign('showEbookFormatField', $configArray['MaterialsRequest']['showEbookFormatField']);
//			$interface->assign('showEaudioFormatField', $configArray['MaterialsRequest']['showEaudioFormatField']);
			$interface->assign('requireAboutField', $configArray['MaterialsRequest']['requireAboutField']);

			$useWorldCat = false;
			if (isset($configArray['WorldCat']) && isset($configArray['WorldCat']['apiKey'])) {
				$useWorldCat = strlen($configArray['WorldCat']['apiKey']) > 0;
			}
			$interface->assign('useWorldCat', $useWorldCat);

			if (isset($library)) {
				// Get the Fields to Display for the form
				$requestFormFields = $request->getRequestFormFields($library->libraryId);
				$interface->assign('requestFormFields', $requestFormFields);

				// Add bookmobile Stop to the pickup locations if that form field is being used.
				foreach ($requestFormFields as $catagory) {
					/** @var MaterialsRequestFormFields $formField */
					foreach ($catagory as $formField) {
						if ($formField->fieldType == 'bookmobileStop') {
							$pickupLocations[] = array(
								'id' => 'bookmobile',
								'displayName' => $formField->fieldLabel,
								'selected' => false,
							);
							$interface->assign('pickupLocations', $pickupLocations);
							break 2;
						}
					}
				}

				// Get Author Labels for all Formats and Formats that use Special Fields
				list($formatAuthorLabels, $specialFieldFormats) = $request->getAuthorLabelsAndSpecialFields($library->libraryId);

				$interface->assign('formatAuthorLabelsJSON', json_encode($formatAuthorLabels));
				$interface->assign('specialFieldFormatsJSON', json_encode($specialFieldFormats));
			}

			// Set up for User Log in
			if (isset($library)) {
				$interface->assign('newMaterialsRequestSummary', $library->newMaterialsRequestSummary);

				$interface->assign('enableSelfRegistration', $library->enableSelfRegistration);
				$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
				$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');
			} else {
				$interface->assign('enableSelfRegistration', 0);
				$interface->assign('usernameLabel', 'Your Name');
				$interface->assign('passwordLabel', 'Library Card Number');
			}

			$this->display('new.tpl', translate('Materials_Request_alt'));
		}
	}
}