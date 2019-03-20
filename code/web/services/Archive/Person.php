<?php

require_once ROOT_DIR . '/services/Archive/Entity.php';
class Archive_Person extends Archive_Entity{
	function launch(){
		global $interface;

		$this->loadArchiveObjectData();
		$this->loadRelatedContentForEntity();
		$this->recordDriver->loadLinkedData();

		$interface->assign('showExploreMore', true);

		// Display Page
		$this->display('person.tpl');
	}
}