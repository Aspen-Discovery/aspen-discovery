<?php
/**
 * A page to display errors related to OverDrive downloads
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/6/2015
 * Time: 11:53 PM
 */

require_once ROOT_DIR . '/Action.php';

class Help_OverDriveError extends Action{
	function launch() {
		global $interface;

		$interface->display('Help/overdriveError.tpl');
	}
}