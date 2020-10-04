<?php


class WebBuilder_PortalPage extends Action
{
	/** @var PortalPage */
	private $page;
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';
		$this->page = new PortalPage();
		$this->page->id = $id;
		if (!$this->page->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Page');
			die();
		}

		$interface->assign('rows', $this->page->getRows());

		$this->display('portalPage.tpl', $this->page->title, '', false);
	}

	function getBreadcrumbs()
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