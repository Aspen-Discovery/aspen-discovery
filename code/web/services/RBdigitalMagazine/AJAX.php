<?php
require_once ROOT_DIR . '/Action.php';

/** @noinspection PhpUnused */
class RBdigitalMagazine_AJAX extends Action
{
	function launch()
	{
		$method = $_GET['method'];
		if (method_exists($this, $method)) {
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		} else {
			echo json_encode(array('error' => 'invalid_method'));
		}
	}

	function getBreadcrumbs() : array
	{
		return [];
	}
}