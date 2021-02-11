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
	function getAllObjects($page, $recordsPerPage){
		$libraryList = array();

		$user = UserAccount::getLoggedInUser();
		if (UserAccount::userHasPermission('Administer All Libraries')){
			$object = new Library();
			$object->orderBy($this->getSort());
			$this->applyFilters($object);
			$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
			$object->find();
			while ($object->fetch()){
				$libraryList[$object->libraryId] = clone $object;
			}
		}else{
			//This doesn't need pagination since there should only be one
			$patronLibrary = Library::getLibraryForLocation($user->homeLocationId);
			$libraryList[$patronLibrary->libraryId] = clone $patronLibrary;
		}

		return $libraryList;
	}
	function getDefaultSort()
	{
		return 'subdomain asc';
	}
	function getObjectStructure(){
		$objectStructure = Library::getObjectStructure();
		if (!UserAccount::userHasPermission('Administer All Libraries')){
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
	function canAddNew(){
		return UserAccount::userHasPermission('Administer All Libraries');
	}
	function canDelete(){
		return UserAccount::userHasPermission('Administer All Libraries');
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

			$facetsToCopy = $libraryToCopyFrom->getArchiveSearchFacets();
			foreach ($facetsToCopy as $facetKey => $facet){
				$facet->libraryId = $libraryId;
				$facet->id = null;
				$facetsToCopy[$facetKey] = $facet;
			}
			$library->setArchiveSearchFacets($facetsToCopy);
			$library->update();
			header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		}else{
			//Prompt user for the library to copy from
			$allLibraries = $this->getAllObjects(1, 5000);

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

	/** @noinspection PhpUnused */
	function resetArchiveSearchFacetsToDefault(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearArchiveSearchFacets();

			$defaultFacets = Library::getDefaultArchiveSearchFacets($libraryId);

			$library->setArchiveSearchFacets($defaultFacets);
			$library->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
	}

	/** @noinspection PhpUnused */
	function resetArchiveMoreDetailsToDefault(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearArchiveMoreDetailsOptions();

			require_once ROOT_DIR . '/sys/LibraryArchiveMoreDetails.php';
			$defaultArchiveMoreDetailsOptions = LibraryArchiveMoreDetails::getDefaultOptions($libraryId);

			$library->setArchiveMoreDetailsOptions($defaultArchiveMoreDetailsOptions);
			$library->update();

			$_REQUEST['objectAction'] = 'edit';
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
	}

	/** @noinspection PhpUnused */
	function defaultMaterialsRequestForm(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearMaterialsRequestFormFields();

			$defaultFieldsToDisplay = MaterialsRequestFormFields::getDefaultFormFields($libraryId);
			$library->setMaterialsRequestFormFields($defaultFieldsToDisplay);
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();

	}

	/** @noinspection PhpUnused */
	function defaultMaterialsRequestFormats(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearMaterialsRequestFormats();

			$defaultMaterialsRequestFormats = MaterialsRequestFormats::getDefaultMaterialRequestFormats($libraryId);
			$library->setMaterialsRequestFormats($defaultMaterialsRequestFormats);
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();
	}

	/** @noinspection PhpUnused */
	function defaultArchiveExploreMoreOptions(){
		$library = new Library();
		$libraryId = $_REQUEST['id'];
		$library->libraryId = $libraryId;
		if ($library->find(true)){
			$library->clearExploreMoreBar();
			$library->setExploreMoreBar(ArchiveExploreMoreBar::getDefaultArchiveExploreMoreOptions($libraryId));
			$library->update();
		}
		header("Location: /Admin/Libraries?objectAction=edit&id=" . $libraryId);
		die();
	}

	function getInstructions(){
		return '/Admin/HelpManual?page=Library-Systems';
	}

	function getInitializationJs(){
		return 'return AspenDiscovery.Admin.updateMaterialsRequestFields();';
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#primary_configuration', 'Primary Configuration');
		$breadcrumbs[] = new Breadcrumb('/Admin/Libraries', 'Library Systems');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'primary_configuration';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Libraries', 'Administer Home Library']);
	}
}