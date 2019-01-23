<?php
/**
 * Displays Information about Digital Repository (Islandora) Exhibit
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/7/2015
 * Time: 7:55 AM
 */

require_once ROOT_DIR . '/services/Archive/Entity.php';
class Archive_Organization extends Archive_Entity{
	function launch(){
		global $interface;

		$this->loadArchiveObjectData();
		//$this->loadExploreMoreContent();
		$this->recordDriver->loadLinkedData();
		$this->loadRelatedContentForEntity();

		$interface->assign('showExploreMore', true);

		// Display Page
		$this->display('baseArchiveObject.tpl');
	}
}