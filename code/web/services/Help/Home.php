<?php


require_once ROOT_DIR . '/Action.php';

class Help_Home extends Action {
	function launch() {
		global $interface;

		// Sanitize the topic name to include only alphanumeric characters
		// or underscores.
		$safe_topic = preg_replace('/[^\w]/', '', $_GET['topic']);

		// Construct three possible template names -- the help screen in the current
		// selected language, help in the site's default language, and help in English
		// (most likely to exist).  The code will attempt to display most appropriate
		// help screen that actually exists.
		$tpl_user = 'Help/' . $interface->getLanguage() . "/{$safe_topic}.tpl";
		global $activeLanguage;
		$tpl_site = "Help/{$activeLanguage->code}/{$safe_topic}.tpl";
		$tpl_en = 'Help/en/' . $safe_topic . '.tpl';

		// Best case -- help is available in the user's chosen language
		if ($interface->template_exists($tpl_user)) {
			$interface->setTemplate($tpl_user);

			// Compromise -- help is available in the site's default language
		} elseif ($interface->template_exists($tpl_site)) {
			$interface->setTemplate($tpl_site);
			$interface->assign('warning', true);

			// Last resort -- help is available in English
		} elseif ($interface->template_exists($tpl_en)) {
			$interface->setTemplate($tpl_en);
			$interface->assign('warning', true);

			// Error -- help isn't available at all!
		} else {
			$interface->setTemplate('Help/en/unknown.tpl');
			$interface->assign('warning', true);
		}

		$interface->display('Help/help.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		return $breadcrumbs;
	}
}
