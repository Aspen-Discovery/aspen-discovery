<?php

class WebBuilder_PortalPage extends Action
{
	/** @var PortalPage */
	private $page;

	function __construct()
	{
		parent::__construct();

		//Make sure the user has permission to access the page
		$userCanAccess = $this->canView();

		if (!$userCanAccess && isset($_REQUEST['raw'])){
			//Check to see if this IP is ok for API calls
			If (IPAddress::allowAPIAccessForClientIP()){
				$userCanAccess = true;
			}
		}

		if (!$userCanAccess){
			global $interface;
			$interface->assign('id', strip_tags($_REQUEST['id']));
			$interface->assign('module', $_REQUEST['module']);
			$interface->assign('action', $_REQUEST['action']);
			$this->display('noPermission.tpl', 'Access Error', '');
			exit();
		}
	}

	function launch()
	{
		global $interface;

		$interface->assign('inPageEditor', false);

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';
		$this->page = new PortalPage();
		$this->page->id = $id;
		if (!$this->page->find(true)){
			global $interface;
			$interface->assign('module','Error');
			$interface->assign('action','Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}

		$interface->assign('rows', $this->page->getRows());

		if (isset($_REQUEST['raw']) && $_REQUEST['raw'] == 'true'){
			echo $interface->fetch('WebBuilder/portalPage.tpl');
			exit();
		}else{
			$this->display('portalPage.tpl', $this->page->title, '', false);
		}
	}

	function canView() : bool
	{
		require_once ROOT_DIR . '/sys/WebBuilder/PortalPageAccess.php';
		require_once ROOT_DIR . '/sys/Account/PType.php';
		require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';

		$id = strip_tags($_REQUEST['id']);
		$page = new PortalPage();
		$page->id = $id;
		$page->find();
		if($page->fetch(true)){
			return $page->canView();
		}else{
			return false;
		}

	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		if ($this->page != null) {
			$breadcrumbs[] = new Breadcrumb('', $this->page->title, true);
			if (UserAccount::userHasPermission(['Administer All Custom Pages', 'Administer Library Custom Pages'])) {
				$breadcrumbs[] = new Breadcrumb('/WebBuilder/PortalPages?id=' . $this->page->id . '&objectAction=edit', 'Edit', true);
			}
		}
		return $breadcrumbs;
	}
}