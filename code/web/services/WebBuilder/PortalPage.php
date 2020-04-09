<?php


class WebBuilder_PortalPage extends Action
{

	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);

		require_once ROOT_DIR . '/sys/WebBuilder/PortalPage.php';
		$page = new PortalPage();
		$page->id = $id;
		if (!$page->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Page');
			die();
		}

		$interface->assign('rows', $page->getRows());

		$sidebar = null;
		if ($page->showSidebar){
			$sidebar = 'Search/home-sidebar.tpl';
		}
		$this->display('portalPage.tpl', $page->title, $sidebar, false);
	}
}