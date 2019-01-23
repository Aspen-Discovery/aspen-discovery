<?php

/**
 * Displays Information about Digital Repository (Islandora) Entity
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 3/22/2016
 * Time: 11:15 AM
 */
require_once ROOT_DIR . '/services/Archive/Object.php';
abstract class Archive_Entity extends Archive_Object {
	function loadRelatedContentForEntity(){
		global $interface;
		$directlyRelatedObjects = $this->recordDriver->getDirectlyRelatedArchiveObjects();
		$interface->assign('directlyRelatedObjects', $directlyRelatedObjects);
	}

}