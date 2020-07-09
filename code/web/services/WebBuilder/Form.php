<?php


class WebBuilder_Form extends Action{
	function launch()
	{
		global $interface;

		$id = strip_tags($_REQUEST['id']);
		$interface->assign('id', $id);

		require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
		$form = new CustomForm();
		$form->id = $id;
		if (!$form->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Page');
			die();
		}

		$interface->assign('introText', $form->introText);
		$interface->assign('contents', $form->getFormattedFields());
		$interface->assign('title', $form->title);

		$this->display('customForm.tpl', $form->title, 'Search/home-sidebar.tpl', false);
	}
}