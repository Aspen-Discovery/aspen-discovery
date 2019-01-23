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
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once 'XML/Unserializer.php';

class Admin_Libraries extends ObjectEditor
{

	function getObjectType(){
		return 'Library';
	}
	function getToolName(){
		return 'Libraries';
	}
	function getPageTitle(){
		return 'Library Systems';
	}
	function getAllObjects(){
		$libraryList = array();

		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasRole('opacAdmin')){
			$library = new Library();
			$library->orderBy('subdomain');
			$library->find();
			while ($library->fetch()){
				$libraryList[$library->libraryId] = clone $library;
			}
		}else if (UserAccount::userHasRole('libraryAdmin') || UserAccount::userHasRole('libraryManager')){
			$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$libraryList[$patronLibrary->libraryId] = clone $patronLibrary;
		}

		return $libraryList;
	}
	function getObjectStructure(){
		$objectStructure = Library::getObjectStructure();
		$user = UserAccount::getLoggedInUser();
		if (!UserAccount::userHasRole('opacAdmin')){
			unset($objectStructure['isDefault']);
		}
		return $objectStructure;
	}
	function getPrimaryKeyColumn(){
		return 'subdomain';
	}
	function getIdKeyColumn(){
		return 'libraryId';
	}
	function getAllowableRoles(){
		return array('opacAdmin', 'libraryAdmin', 'libraryManager');
	}
	function canAddNew(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}
	function getAdditionalObjectActions($existingObject){
		$objectActions = array();
		if ($existingObject != null){
//			$objectActions[] = array(
//				'text' => 'Reset Facets To Default',
//				'url' => '/Admin/Libraries?id=' . $existingObject->libraryId . '&amp;objectAction=resetFacetsToDefault',
//			);
//			$objectActions[] = array(
//					'text' => 'Reset More Details To Default',
//					'url' => '/Admin/Libraries?id=' . $existingObject->libraryId . '&amp;objectAction=resetMoreDetailsToDefault',
//			);
//			$objectActions[] = array(
//				'text' => 'Copy Library Facets',
//				'url' => '/Admin/Libraries?id=' . $existingObject->libraryId . '&amp;objectAction=copyFacetsFromLibrary',
//			);
//			$objectActions[] = array(
//				'text' => 'Set Materials Request Form Structure To Default',
//				'url' => '/Admin/Libraries?id=' . $existingObject->libraryId . '&amp;objectAction=defaultMaterialsRequestForm',
//			);
//			$objectActions[] = array(
//				'text' => 'Set Materials Request Formats To Default',
//				'url' => '/Admin/Libraries?id=' . $existingObject->libraryId . '&amp;objectAction=defaultMaterialsRequestFormats',
//			);
//			$objectActions[] = array(
//				'text' => 'Set Archive Explore More Options To Default',
//				'url' => '/Admin/Libraries?id=' . $existingObject->libraryId . '&amp;objectAction=defaultArchiveExploreMoreOptions',
//			);
		}else{
			echo("Existing object is null");
		}
		return $objectActions;
	}

	function copyFacetsFromLibrary(){
		$libraryId = $_REQUEST['id'];
		if (isset($_REQUEST['submit'])){
			$library = new Library();
			$library->libraryId = $libraryId;
			$library->find(true);
			$library->clearFacets();

			$libraryToCopyFromId = $_REQUEST['libraryToCopyFrom'];
			$libraryToCopyFrom = new Library();
			$libraryToCopyFrom->libraryId = $libraryToCopyFromId;
			$library->find(true);

			$facetsToCopy = $libraryToCopyFrom->facets;
			foreach ($facetsToCopy as $facetKey => $facet){
				$facet->libraryId = $libraryId;
				$facet->id = null;
				$facetsToCopy[$facetKey] = $facet;
			}
			$library->facets = $facetsToCopy;
			$library->update();
			header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		}else{
			//Prompt user for the library to copy from
			$allLibraries = $this->getAllObjects();

			unset($allLibraries[$libraryId]);
			foreach ($allLibraries as $key => $library){
				if (count($library->facets) == 0){
					unset($allLibraries[$key]);
				}
			}
			global $interface;
			$interface->assign('allLibraries', $allLibraries);
			$interface->assign('id', $libraryId);
			$interface->assign('facetType', 'search');
			$interface->assign('objectAction', 'copyFacetsFromLibrary');
			$interface->setTemplate('../Admin/copyLibraryFacets.tpl');
		}
	}

	function copyArchiveSearchFacetsFromLibrary(){
		$libraryId = $_REQUEST['id'];
		if (isset($_REQUEST['submit'])){
			$library = new Library();
			$library->libraryId = $libraryId;
			$library->find(true);
			$library->clearArchiveSearchFacets();

			$libraryToCopyFromId = $_REQUEST['libraryToCopyFrom'];
			$libraryToCopyFrom = new Library();
			$libraryToCopyFrom->libraryId = $libraryToCopyFromId;
			$library->find(true);

			$facetsToCopy = $libraryToCopyFrom->archiveSearchFacets;
			foreach ($facetsToCopy as $facetKey => $facet){
				$facet->libraryId = $libraryId;
				$facet->id = null;
				$facetsToCopy[$facetKey] = $facet;
			}
			$library->facets = $facetsToCopy;
			$library->update();
			header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		}else{
			//Prompt user for the library to copy from
			$allLibraries = $this->getAllObjects();

			unset($allLibraries[$libraryId]);
			foreach ($allLibraries as $key => $library){
				if (count($library->archiveSearchFacets) == 0){
					unset($allLibraries[$key]);
				}
			}
			global $interface;
			$interface->assign('allLibraries', $allLibraries);
			$interface->assign('id', $libraryId);
			$interface->assign('facetType', 'archive search');
			$interface->assign('objectAction', 'copyArchiveSearchFacetsFromLibrary');
			$interface->setTemplate('../Admin/copyLibraryFacets.tpl');
		}
	}

	function resetFacetsToDefault(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearFacets();

			$defaultFacets = Library::getDefaultFacets($libraryId);

			$library->facets = $defaultFacets;
			$library->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		$structure = $this->getObjectStructure();
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
	}

	function resetArchiveSearchFacetsToDefault(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearArchiveSearchFacets();

			$defaultFacets = Library::getDefaultArchiveSearchFacets($libraryId);

			$library->archiveSearchFacets = $defaultFacets;
			$library->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		$structure = $this->getObjectStructure(); //TODO: Needed?
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
	}

	function resetMoreDetailsToDefault(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearMoreDetailsOptions();

			$defaultOptions = array();
			require_once ROOT_DIR . '/RecordDrivers/Interface.php';
			$defaultMoreDetailsOptions = RecordInterface::getDefaultMoreDetailsOptions();
			$i = 0;
			foreach ($defaultMoreDetailsOptions as $source => $defaultState){
				$optionObj = new LibraryMoreDetails();
				$optionObj->libraryId = $libraryId;
				$optionObj->collapseByDefault = $defaultState == 'closed';
				$optionObj->source = $source;
				$optionObj->weight = $i++;
				$defaultOptions[] = $optionObj;
			}

			$library->moreDetailsOptions = $defaultOptions;
			$library->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		$structure = $this->getObjectStructure();
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
	}

	function resetArchiveMoreDetailsToDefault(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearArchiveMoreDetailsOptions();

			require_once ROOT_DIR . '/sys/LibraryArchiveMoreDetails.php';
			$defaultArchiveMoreDetailsOptions = LibraryArchiveMoreDetails::getDefaultOptions($libraryId);

			$library->archiveMoreDetailsOptions = $defaultArchiveMoreDetailsOptions;
			$library->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		$structure = $this->getObjectStructure();
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
	}

	function defaultMaterialsRequestForm(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearMaterialsRequestFormFields();

			$defaultFieldsToDisplay = MaterialsRequestFormFields::getDefaultFormFields($libraryId);
			$library->materialsRequestFormFields = $defaultFieldsToDisplay;
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();

	}

	function defaultMaterialsRequestFormats(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearMaterialsRequestFormats();

			$defaultMaterialsRequestFormats = MaterialsRequestFormats::getDefaultMaterialRequestFormats($libraryId);
			$library->materialsRequestFormats = $defaultMaterialsRequestFormats;
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();
	}

	function defaultArchiveExploreMoreOptions(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearExploreMoreBar();
			$library->exploreMoreBar = ArchiveExploreMoreBar::getDefaultArchiveExploreMoreOptions($libraryId);
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();
	}

	function getInstructions(){
		return 'For more information about Library Setting configuration, see the <a href="https://docs.google.com/a/marmot.org/document/d/1oBMoPCHbhybgtcaCAALSxcZCxWEsT5cSb7mbkio6V_k">online documentation</a>.';
	}
}