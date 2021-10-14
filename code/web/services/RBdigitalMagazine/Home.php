<?php

class RBdigitalMagazine_Home extends Action{

	function launch(){
		global $interface;

		$interface->assign('showBreadcrumbs', false);
		$interface->assign('message', 'This title is no longer available.  RBdigital support has been discontinued, the title may be available in our OverDrive collection.');
		http_response_code(410);
		$this->display('../Error/410.tpl', 'Page Not Found');
	}

	function getBreadcrumbs(): array
	{
		return [];
	}

}