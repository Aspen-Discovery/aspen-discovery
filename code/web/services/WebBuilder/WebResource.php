<?php


class WebBuilder_WebResource extends Action{
	function launch()
	{
		global $interface;
		global $configArray;

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
		$interface->assign('webResource', $webResource);
		$interface->assign('logo', '/files/original/' . $webResource->logo);

		$sidebar = 'Search/home-sidebar.tpl';
		$this->display('webResource.tpl', $webResource->name, $sidebar, false);
	}
}