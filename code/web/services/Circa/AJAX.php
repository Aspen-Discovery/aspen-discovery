<?php
/**
 * AJAX Processing for Circa Module
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/15/13
 * Time: 2:27 PM
 */
require_once ROOT_DIR . '/Action.php';

class Circa_AJAX extends Action{
	function launch() {
		global $timer;
		$method = $_GET['method'];
		$timer->logTime("Starting method $method");
		if (true){
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}
	}

	function UpdateInventoryForBarcode(){
		global $configArray;
		$barcode = is_array($_REQUEST['barcodes']) ? $_REQUEST['barcodes'] : array($_REQUEST['barcodes']);
		$login = $_REQUEST['login'];
		$password1 = $_REQUEST['password'];
		$initials = $_REQUEST['initials'];
		$password2 = $_REQUEST['password2'];
		$updateIncorrectStatuses = $_REQUEST['updateIncorrectStatuses'];
		$result = array(
			'barcode' => $barcode[0]
		);

		try {
			$catalog = CatalogFactory::getCatalogConnectionInstance();;
			$results = $catalog->doInventory($login, $password1, $initials, $password2, $barcode, $updateIncorrectStatuses);
			if ($results['success'] == false){
				$result['success'] = false;
				$result['message'] = $results['message'];
			}else{
				$result['success'] = true;
				$result['barcodes'] = $results['barcodes'];
			}
		} catch (PDOException $e) {
			// What should we do with this error?
			if ($configArray['System']['debug']) {
				echo '<pre>';
				echo 'DEBUG: ' . $e->getMessage();
				echo '</pre>';
			}
		}

		return json_encode($result);
	}
}