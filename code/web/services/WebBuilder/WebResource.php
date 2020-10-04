<?php

class WebBuilder_WebResource extends Action{
	private $webResource;
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		$this->webResource = new WebResource();
		$this->webResource->id = $id;
		if (!$this->webResource->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Page');
			die();
		}

		$interface->assign('description', $this->webResource->getFormattedDescription());
		$interface->assign('title', $this->webResource->name);
		$interface->assign('webResource', $this->webResource);
		$interface->assign('logo', '/files/original/' . $this->webResource->logo);

		$this->display('webResource.tpl', $this->webResource->name, '', false);
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->webResource->name, true);
		return $breadcrumbs;
	}
}