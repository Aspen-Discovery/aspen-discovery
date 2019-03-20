<?php

require_once ROOT_DIR . '/services/Archive/Object.php';
class Archive_Image extends Archive_Object{
	function launch(){
		global $interface;
		$this->loadArchiveObjectData();
		//$this->loadExploreMoreContent();

		$interface->assign('showExploreMore', true);
		$interface->setTemplate('image.tpl');

		// Display Page
		$this->display('image.tpl');
	}
}