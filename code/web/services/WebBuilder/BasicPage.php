<?php


class WebBuilder_BasicPage extends Action{
	/** @var BasicPage */
	private $basicPage;
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
		$this->basicPage = new BasicPage();
		$this->basicPage->id = $id;
		if (!$this->basicPage->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Page');
			die();
		}

		$interface->assign('contents', $this->basicPage->getFormattedContents());
		$interface->assign('title', $this->basicPage->title);

		$this->display('basicPage.tpl', $this->basicPage->title, '', false);
	}

	function getBreadcrumbs()
	{
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/', 'Home');
		$breadcrumbs[] = new Breadcrumb('', $this->basicPage->title, true);
		return $breadcrumbs;
	}
}