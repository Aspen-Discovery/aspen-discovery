<?php

class Library_System extends Action{

	function launch() {
		global $interface;
		global $configArray;

		$librarySystem = new Library();
		$librarySystem->libraryId = $_REQUEST['id'];
		if ($librarySystem->find(true)){
			$interface->assign('library', $librarySystem);
		}

		$semanticData = array(
				'@context' => 'http://schema.org',
				'@type' => 'Organization',
				'name' => $librarySystem->displayName,
		);
		//add branches
		$locations = new Location();
		$locations->libraryId = $librarySystem->libraryId;
		$locations->orderBy('isMainBranch DESC, displayName'); // List Main Branches first, then sort by name
		$locations->find();
		$subLocations = array();
		$branches = array();
		while ($locations->fetch()){
			$branches[] = array(
					'name' => $locations->displayName,
					'link' => $configArray['Site']['url'] . "/Library/{$locations->locationId}/Branch"
			);
			$subLocations[] = array(
				'@type' => 'Organization',
				'name' => $locations->displayName,
				'url' => $configArray['Site']['url'] . "/Library/{$locations->locationId}/Branch"

			);
		}
		if (count($subLocations)){
			$semanticData['subOrganization'] = $subLocations;
			$interface->assign('branches', $branches);
		}
		$interface->assign('semanticData', json_encode($semanticData));

		$this->display('system.tpl', $librarySystem->displayName);
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}