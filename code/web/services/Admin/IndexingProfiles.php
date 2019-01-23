<?php
/**
 * Admin interface for creating indexing profiles
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 6/30/2015
 * Time: 1:23 PM
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';
require_once ROOT_DIR . '/sys/Indexing/IndexingProfile.php';
class Admin_IndexingProfiles extends ObjectEditor {
	function launch()
	{
		global $interface;
		$objectAction = isset($_REQUEST['objectAction']) ? $_REQUEST['objectAction'] : null;
		if ($objectAction == 'viewMarcFiles') {
			$id = $_REQUEST['id'];
			$interface->assign('id', $id);
			$files = array();
			$indexProfile = new IndexingProfile();
			if ($indexProfile->get($id) && !empty($indexProfile->marcPath)) {

				$marcPath = $indexProfile->marcPath;
				if ($handle = opendir($marcPath)) {
					while (false !== ($entry = readdir($handle))) {
						if ($entry != "." && $entry != "..") {
							$files[$entry] = filectime($marcPath . DIR_SEP . $entry);
						}
					}
					closedir($handle);
					$interface->assign('files', $files);
					$interface->assign('IndexProfileName', $indexProfile->name);
					$this->display('marcFiles.tpl', 'Marc Files');
				}
			}
		} else {
			parent::launch();
		}
	}


	function getObjectType(){
		return 'IndexingProfile';
	}
	function getToolName(){
		return 'IndexingProfiles';
	}
	function getPageTitle(){
		return 'Indexing Profiles';
	}
	function getAllObjects(){
		$list = array();

		$object = new IndexingProfile();
		$object->orderBy('name');
		$object->find();
		while ($object->fetch()){
			$list[$object->id] = clone $object;
		}

		return $list;
	}
	function getObjectStructure(){
		return IndexingProfile::getObjectStructure();
	}
	function getAllowableRoles(){
		return array('opacAdmin');
	}
	function getPrimaryKeyColumn(){
		return 'id';
	}
	function getIdKeyColumn(){
		return 'id';
	}
	function canAddNew(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}
	function canDelete(){
		$user = UserAccount::getLoggedInUser();
		return UserAccount::userHasRole('opacAdmin');
	}

	function getInstructions(){
		return 'For more information about indexing profiles, see the <a href="https://docs.google.com/document/d/1OA_HKMmgf4nm2l3ckojHiTHnlbo4dKMNnec3wCtGsLk">online documentation</a>.';
	}

	function getAdditionalObjectActions($existingObject){
		$actions = array();
		if ($existingObject && $existingObject->id != ''){
			$actions[] = array(
				'text' => 'View MARC files',
				'url' => '/Admin/IndexingProfiles?objectAction=viewMarcFiles&id=' . $existingObject->id,
			);
		} else {
			$actions[] = array(
				'text' => 'Populate as a Sideload',
				'onclick' => "$('#indexingClass').val('SideLoadedEContent');$('#groupingClass').val('SideLoadedRecordGrouper');$('#recordDriver').val('SideLoadedRecord');$('#catalogDriver').val('na');$('#recordUrlComponent').val('');$(this).parent().after($('<div>Remember to update the relevant library and location Records to Include/Own settings.</div>').addClass('alert alert-warning')); return false",
			);
		}

		return $actions;
	}

	//TODO Add to ObjectEditor Class
	function display($mainContentTemplate, $pageTitle, $sidebarTemplate='Search/home-sidebar.tpl') {
		global $interface;
		if (!empty($sidebarTemplate)) $interface->assign('sidebar', $sidebarTemplate);
		$interface->setTemplate($mainContentTemplate);
		$interface->setPageTitle($pageTitle);
		$interface->assign('moreDetailsTemplate', 'GroupedWork/moredetails-accordion.tpl');
		$interface->display('layout.tpl');
	}

}