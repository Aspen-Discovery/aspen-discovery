<?php


class WebBuilder_BasicPage extends Action{
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/WebBuilder/BasicPage.php';
		$basicPage = new BasicPage();
		$basicPage->id = $id;
		if (!$basicPage->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Page');
			die();
		}

		$interface->assign('contents', $basicPage->getFormattedContents());
		$interface->assign('title', $basicPage->title);

		$sidebar = null;
		if ($basicPage->showSidebar){
			$sidebar = 'Search/home-sidebar.tpl';
		}
		$this->display('basicPage.tpl', $basicPage->title, $sidebar, false);
	}
}