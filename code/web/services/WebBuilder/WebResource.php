<?php

class WebBuilder_WebResource extends Action{
	private $webResource;
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/WebBuilder/WebResource.php';
		require_once ROOT_DIR . '/RecordDrivers/WebResourceRecordDriver.php';
		disableErrorHandler();
		try {
			$resourceDriver = new WebResourceRecordDriver('WebResource:' . $id);
		}catch (Exception $e) {
			//Resource has not been indexed yet
		}
		enableErrorHandler();
		$this->webResource = new WebResource();
		$this->webResource->id = $id;
		if (!$this->webResource->find(true)){
			global $interface;
			$interface->assign('module','Error');
			$interface->assign('action','Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		$interface->assign('description', $this->webResource->getFormattedDescription());
		$interface->assign('title', $this->webResource->name);
		$interface->assign('webResource', $this->webResource);
		if ($resourceDriver->isValid()) {
			$interface->assign('logo', $resourceDriver->getBookcoverUrl('large'));
		}

		$this->display('webResource.tpl', $this->webResource->name, '', false);
	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->webResource->name, true);
		if (UserAccount::userHasPermission(['Administer All Web Resources', 'Administer Library Web Resources'])){
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/WebResources?id=' . $this->webResource->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}