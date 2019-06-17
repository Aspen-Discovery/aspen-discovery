<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/services/Admin/Admin.php';

class Translation_Translations extends Admin_Admin
{

	function launch()
	{
		// TODO: Implement launch() method.
		global $interface;
		/** @var Translator $translator */
		global $translator;
		$translationModeActive = $translator->translationModeActive();
		$interface->assign('translationModeActive', $translationModeActive);

		$this->display('translations.tpl', 'Translations');
	}

	function getAllowableRoles()
	{
		return ['opacAdmin', 'translator'];
	}
}