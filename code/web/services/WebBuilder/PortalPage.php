<?php

class WebBuilder_PortalPage extends Action
{
	/** @var PortalPage */
	private $page;

	function __construct()
	{
		//Make sure the user has permission to access the page
		$userCanAccess = $this->canView();

		if (!$userCanAccess){
			$this->display('noPermission.tpl', 'Access Error', '');
			exit();
		}
	}

	function launch()
	{
		global $interface;

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

		$this->display('portalPage.tpl', $this->page->title, '', false);
	}

	function canView() : bool
	{
		global $locationSingleton;
		require_once ROOT_DIR . '/sys/WebBuilder/PortalPageAccess.php';
		require_once ROOT_DIR . '/sys/Account/PType.php';
		require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';

		$requireLogin = 0;
		$allowInLibrary = 0;
		$id = strip_tags($_REQUEST['id']);
		$page = new PortalPage();
		$page->id = $id;
		$page->find();
		while($page->fetch()){
			$requireLogin = $page->requireLogin;
			$allowInLibrary = $page->requireLoginUnlessInLibrary;
		}

		$inLibrary = $locationSingleton->getIPLocation();
		$user = UserAccount::getLoggedInUser();
		if($requireLogin){
			if($allowInLibrary && $inLibrary != null) {
				return true;
			}
			if(!$user) {
				return false;
			}
			else {
				$userPatronType = $user->patronType;
				$userId = $user->id;

				$patronType = new pType();
				$patronType->pType = $userPatronType;
				$patronType->find();
				if ($userPatronType == NULL && $userId == 1) {
					return true;
				} else {
					while ($patronType->fetch()) {
						$patronTypeId = $patronType->id;
					}

					$patronTypeLink = new PortalPageAccess();
					$patronTypeLink->portalPageId = $id;
					$patronTypeLink->patronTypeId = $patronTypeId;
					$patronTypeLink->find();
					if ($patronTypeLink->find()) {
						return true;
					} else {
						return false;
					}
				}
			}
		} else {
			return true;
		}

	}

	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->page->title, true);
		if (UserAccount::userHasPermission(['Administer All Custom Pages', 'Administer Library Custom Pages'])){
			$breadcrumbs[] = new Breadcrumb('/WebBuilder/PortalPages?id=' . $this->page->id . '&objectAction=edit', 'Edit', true);
		}
		return $breadcrumbs;
	}
}