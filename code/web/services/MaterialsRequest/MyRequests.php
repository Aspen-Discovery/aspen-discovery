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
require_once ROOT_DIR . '/sys/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequestStatus.php';
require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';

/**
 * MaterialsRequest MyRequests Page, displays materials request information for the active user.
 */
class MaterialsRequest_MyRequests extends MyAccount
{

	function launch()
	{
		global $configArray;
		global $interface;

		$showOpen = true;
		if (isset($_REQUEST['requestsToShow']) && $_REQUEST['requestsToShow'] == 'allRequests'){
			$showOpen  = false;
		}
		$interface->assign('showOpen', $showOpen);

//		global $library;
		$homeLibrary = Library::getPatronHomeLibrary();

		$maxActiveRequests  = isset($homeLibrary) ? $homeLibrary->maxOpenRequests : 5;
		$maxRequestsPerYear = isset($homeLibrary) ? $homeLibrary->maxRequestsPerYear : 60;
		$interface->assign('maxActiveRequests', $maxActiveRequests);
		$interface->assign('maxRequestsPerYear', $maxRequestsPerYear);

		$defaultStatus = new MaterialsRequestStatus();
		$defaultStatus->isDefault = 1;
		$defaultStatus->libraryId = $homeLibrary->libraryId;
		$defaultStatus->find(true);
		$interface->assign('defaultStatus', $defaultStatus->id);

		//Get a list of all materials requests for the user
		$allRequests = array();
		if (UserAccount::isLoggedIn()){
			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = UserAccount::getActiveUserId();
			$materialsRequests->whereAdd('dateCreated >= unix_timestamp(now() - interval 1 year)');

			$statusQueryNotCancelled = new MaterialsRequestStatus();
			$statusQueryNotCancelled->libraryId = $homeLibrary->libraryId;
			$statusQueryNotCancelled->isPatronCancel = 0;
			$materialsRequests->joinAdd($statusQueryNotCancelled);

			$requestsThisYear = $materialsRequests->count();
			$interface->assign('requestsThisYear', $requestsThisYear);

			$statusQuery = new MaterialsRequestStatus();
			$statusQuery->libraryId = $homeLibrary->libraryId;
			$statusQuery->isOpen = 1;

			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = UserAccount::getActiveUserId();
			$materialsRequests->joinAdd($statusQuery);
			$openRequests = $materialsRequests->count();
			$interface->assign('openRequests', $openRequests);

			$formats = MaterialsRequest::getFormats();

			$materialsRequests = new MaterialsRequest();
			$materialsRequests->createdBy = UserAccount::getActiveUserId();
			$materialsRequests->orderBy('title, dateCreated');

			$statusQuery = new MaterialsRequestStatus();
			if ($showOpen){
				$user = UserAccount::getActiveUserObj();
				$homeLibrary = $user->getHomeLibrary();
				$statusQuery->libraryId = $homeLibrary->libraryId;
				$statusQuery->isOpen = 1;
			}
			$materialsRequests->joinAdd($statusQuery);
			$materialsRequests->selectAdd();
			$materialsRequests->selectAdd('materials_request.*, description as statusLabel');
			$materialsRequests->find();
			while ($materialsRequests->fetch()){
				if (array_key_exists($materialsRequests->format, $formats)){
					$materialsRequests->format = $formats[$materialsRequests->format];
				}
				$allRequests[] = clone $materialsRequests;
			}
		}else{
			header('Location: ' . $configArray['Site']['path'] . '/MyAccount/Home?followupModule=MaterialsRequest&followupAction=MyRequests');
			exit;
		}
		$interface->assign('allRequests', $allRequests);
		$interface->assign('shortPageTitle', 'My ' . translate('Materials_Request_alt'). 's' );

		$title = 'My '. translate('Materials_Request_alt') .'s';
		$this->display('myMaterialRequests.tpl', $title);
	}
}