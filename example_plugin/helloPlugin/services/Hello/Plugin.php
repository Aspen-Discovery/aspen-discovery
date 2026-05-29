<?php

class Hello_Plugin extends Action {

	function launch() {
		global $interface;
		$this->display(HELLOPLUGIN_ROOT . '/interface/themes/Hello/plugin.tpl', 'Hello World', null, false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('', 'Example Plugin');
		return $breadcrumbs;
	}
}