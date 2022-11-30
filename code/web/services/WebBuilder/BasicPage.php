<?php


class WebBuilder_BasicPage extends Action{
	/** @var BasicPage */
	private $basicPage;

	function __construct()
	{
		parent::__construct();

		require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';

		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$this->basicPage = new BasicPage();
		$this->basicPage->id = $id;

		if (!$this->basicPage->find(true)) {
			$interface->assign('module','Error');
			$interface->assign('action','Handle404');
			require_once ROOT_DIR . "/services/Error/Handle404.php";
			$actionClass = new Error_Handle404();
			$actionClass->launch();
			die();
		}
		else if ( !$this->canView() ) {
			$interface->assign('module','Error');
			$interface->assign('action','Handle401');
			require_once ROOT_DIR . "/services/Error/Handle401.php";
			$actionClass = new Error_Handle401();
			$actionClass->launch();
			die();
		}
	}

	function launch()
	{
		global $interface;

		$title = $this->basicPage->title;
		$interface->assign('id', $this->basicPage->id);
		$interface->assign('contents', $this->basicPage->getFormattedContents());
		$interface->assign('title', $title);

		$this->display('basicPage.tpl', $title, '', false);
	}

	function canView() : bool
	{
		return $this->basicPage->canView();
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