<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlight.php';
require_once ROOT_DIR . '/sys/LocalEnrichment/CollectionSpotlightList.php';
require_once ROOT_DIR . '/sys/DataObjectUtil.php';

class Admin_CollectionSpotlights extends ObjectEditor {
	function getObjectType(){
		return 'CollectionSpotlight';
	}
	function getToolName(){
		return 'CollectionSpotlights';
	}
	function getPageTitle(){
		return 'Collection Spotlights';
	}
	function getAllObjects($page, $recordsPerPage){
		$list = array();

		$object = new CollectionSpotlight();
		if (!UserAccount::userHasPermission('Administer All Collection Spotlights')){
			$patronLibrary = Library::getPatronHomeLibrary();
			$object->libraryId = $patronLibrary->libraryId;
		}
		$object->orderBy($this->getSort());
		$this->applyFilters($object);
		$object->limit(($page - 1) * $recordsPerPage, $recordsPerPage);
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getDefaultSort()
	{
		return 'name asc';
	}

	function getObjectStructure(){
		return CollectionSpotlight::getObjectStructure();
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		//Collection spotlights should be added from search results.
		return false;
	}
	function canDelete(){
		return true;
	}
	function launch() {
		global $interface;

		$interface->assign('canAddNew', $this->canAddNew());
		$interface->assign('canCopy', $this->canCopy());
		$interface->assign('canDelete', $this->canDelete());
		$interface->assign('showReturnToList', $this->showReturnToList());

		//Figure out what mode we are in
		if (isset($_REQUEST['objectAction'])){
			$objectAction = $_REQUEST['objectAction'];
		}else{
			$objectAction = 'list';
		}

		if ($objectAction == 'delete' && isset($_REQUEST['id'])){
			parent::launch();
			exit();
		}

		//Get all available spotlights
		$availableSpotlights = array();
		$collectionSpotlight = new CollectionSpotlight();
		if (!UserAccount::userHasPermission('Administer All Collection Spotlights')){
			$homeLibrary = Library::getPatronHomeLibrary();
			$collectionSpotlight->whereAdd('libraryId = ' . $homeLibrary->libraryId . ' OR libraryId = -1');
		}
		$collectionSpotlight->orderBy('name ASC');
		$collectionSpotlight->find();
		while ($collectionSpotlight->fetch()){
			$availableSpotlights[$collectionSpotlight->id] = clone($collectionSpotlight);
		}
		$interface->assign('availableSpotlights', $availableSpotlights);

		//Get the selected spotlight
		if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
			$spotlight = $availableSpotlights[$_REQUEST['id']];
			$interface->assign('object', $spotlight);
		}else{
			$spotlight = null;
		}

		//Do actions that require pre-processing
		if ($objectAction == 'save'){
			if (!isset($spotlight)){
				$spotlight = new CollectionSpotlight();
			}
			DataObjectUtil::updateFromUI($spotlight, $collectionSpotlight->getObjectStructure());
			$validationResults = DataObjectUtil::saveObject($collectionSpotlight->getObjectStructure(), "CollectionSpotlight");
			if (!$validationResults['validatedOk']){
				$interface->assign('object', $spotlight);
				$interface->assign('errors', $validationResults['errors']);
				$objectAction = 'edit';
			}else{
				$interface->assign('object', $validationResults['object']);
				$objectAction = 'view';
			}

		}

		if ($objectAction == 'list'){
			$interface->setTemplate('collectionSpotlights.tpl');
		}else{
			if ($objectAction == 'edit' || $objectAction == 'add'){
				if (isset($_REQUEST['id'])){
					$interface->assign('spotlightId',$_REQUEST['id']);
					$interface->assign('id',$_REQUEST['id']);
				}
				$editForm = DataObjectUtil::getEditForm($collectionSpotlight->getObjectStructure());
				$interface->assign('editForm', $editForm);
				$interface->setTemplate('collectionSpotlightEdit.tpl');
			}else{
				// Set some default sizes for the iframe we embed on the view page
				switch ($spotlight->style){
					case 'horizontal':
						$width = 650;
						$height = ($spotlight->coverSize == 'medium') ? 325 : 275;
						break;
					case 'vertical' :
						$width = ($spotlight->coverSize == 'medium') ? 275 : 175;
						$height = ($spotlight->coverSize == 'medium') ? 700 : 400;
						break;
					case 'text-list' :
						$width = 500;
						$height = 200;
						break;
					case 'single' :
					case 'single-with-next' :
					default:
						$width = ($spotlight->coverSize == 'medium') ? 300 : 225;
						$height = ($spotlight->coverSize == 'medium') ? 350 : 275;
						break;
				}
				$interface->assign('width', $width);
				$interface->assign('height', $height);
				$interface->setTemplate('collectionSpotlight.tpl');
			}
		}

		$this->display($interface->getTemplate(), 'Collection Spotlights');
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#local_enrichment', 'Local Enrichment');
		$breadcrumbs[] = new Breadcrumb('/Admin/CollectionSpotlights', 'Collection Spotlights');
		return $breadcrumbs;
	}

	function getActiveAdminSection()
	{
		return 'local_enrichment';
	}

	function canView()
	{
		return UserAccount::userHasPermission(['Administer All Collection Spotlights','Administer Library Collection Spotlights']);
	}
}