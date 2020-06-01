<?php


class WebBuilder_WebResource extends Action{
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		$webResource = new WebResource();
		$webResource->id = $id;
		if (!$webResource->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Page');
			die();
		}

		$interface->assign('description', $webResource->getFormattedDescription());
		$interface->assign('title', $webResource->name);

		$sidebar = 'Search/home-sidebar.tpl';
		$this->display('webResource.tpl', $webResource->name, $sidebar, false);
	}
}