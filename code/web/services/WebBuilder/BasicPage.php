<?php


class WebBuilder_BasicPage extends Action{
	/** @var BasicPage */
	private $basicPage;

	function __construct()
	{
		parent::__construct();
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

		require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
		$this->basicPage = new BasicPage();
		$this->basicPage->id = $id;
		if (!$this->basicPage->find(true)){
			global $interface;
			$interface->assign('module','Error');
			$interface->assign('action','Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}else{
			$title = $this->basicPage->title;
		}

		$interface->assign('contents', $this->basicPage->getFormattedContents());
		$interface->assign('title', $title);

		$this->display('basicPage.tpl', $title, '', false);
	}

	function canView() : bool
	{
		require_once ROOT_DIR . '/sys/WebBuilder/BasicPageAccess.php';
		require_once ROOT_DIR . '/sys/Account/PType.php';
		require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';

		$requireLogin = 0;
		$allowInLibrary = 0;
		$id = strip_tags($_REQUEST['id']);
		$page = new BasicPage();
		$page->id = $id;
		if ($page->find(true)){
			return $page->canView();
		}else{
			return false;
		}

	}
	function getBreadcrumbs() : array
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		if ($this->basicPage != null) {
			$breadcrumbs[] = new Breadcrumb('', $this->basicPage->title, true);
			if (UserAccount::userHasPermission(['Administer All Basic Pages', 'Administer Library Basic Pages'])){
				$breadcrumbs[] = new Breadcrumb('/WebBuilder/BasicPages?id=' . $this->basicPage->id . '&objectAction=edit', 'Edit', true);
			}
		}
		return $breadcrumbs;
	}
}