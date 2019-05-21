<?php

require_once ROOT_DIR . "/Action.php";

/**
 * Materials Request Home Page to view a request after it has been submitted.
 *
 * This controller needs some cleanup and organization.
 *
 * @version  $Revision: 1.27 $
 */
class MaterialsRequest_Home extends Action
{

	function launch()
	{
		global $interface;

		$interface->setTemplate('home.tpl');
		
		$interface->display('layout.tpl');
	}
}