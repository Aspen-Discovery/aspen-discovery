<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

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
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		return UserAccount::userHasRole('opacAdmin');
	}
	function getAdditionalObjectActions($existingObject){
		return [];
	}

	/** @noinspection PhpUnused */
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
		return '';
	}

	function getInitializationJs(){
		return 'return AspenDiscovery.Admin.updateMaterialsRequestFields();';
	}
}