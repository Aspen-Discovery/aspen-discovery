<?php

class WebBuilder_PortalPage extends Action
{
	/** @var PortalPage */
	private $portalPage;

	function __construct()
	{
		parent::__construct();
		http_response_code(200);

		require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';
		$this->portalPage = new PortalPage();
		$id = strip_tags($_REQUEST['id']);
		$this->portalPage->id = $id;
		$userCanAccess = $this->canView();
		$portalPageFounded = $this->portalPage->find(true);

		if(!$portalPageFounded){
			global $interface;
			$interface->assign('module','Error');
			$interface->assign('action','Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		} else {
			if (!$userCanAccess && isset($_REQUEST['raw'])) {
				//Check to see if this IP is ok for API calls
				if (IPAddress::allowAPIAccessForClientIP()) {
					$userCanAccess = true;
				}
			}
			if (!$userCanAccess) {
				global $interface;
				$interface->assign('module', 'Error');
				$interface->assign('action', 'Handle401');
				require_once ROOT_DIR . "/services/Error/Handle401.php";
				$actionClass = new Error_Handle401();
				$actionClass->launch();
				die();
			}

		}
	}

	function launch()
	{
		global $interface;
		$interface->assign('inPageEditor', false);
		$title = $this->portalPage->title;
		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);
		$interface->assign('title',$title);
		$interface->assign('rows', $this->portalPage->getRows());

		if (isset($_REQUEST['raw']) && $_REQUEST['raw'] == 'true'){
			echo $interface->fetch('WebBuilder/portalPage.tpl');
			exit();
		}else{
			$this->display('portalPage.tpl', $title, '', false);
		}
		$this->display('portalPage.tpl', $title, '', false);
	}

	function canView() : bool
	{
		$this->portalPage->find();
		if($this->portalPage->fetch(true)){

			return $this->portalPage->canView();
		}
		else{
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