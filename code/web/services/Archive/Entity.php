<?php

require_once ROOT_DIR . '/services/Archive/Object.php';
abstract class Archive_Entity extends Archive_Object {
	function loadRelatedContentForEntity(){
		global $interface;
		$directlyRelatedObjects = $this->recordDriver->getDirectlyRelatedArchiveObjects();
		$interface->assign('directlyRelatedObjects', $directlyRelatedObjects);
	}

}