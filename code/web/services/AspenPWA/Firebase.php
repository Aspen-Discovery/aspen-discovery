<?php

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/AspenPWA/Setting.php';

class AspenPWA_Firebase extends Action {

	///firebase-messaging-sw.js redirects to here
	function launch() {
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('content-type: text/javascript; charset=utf-8');
		http_response_code(200);
		echo file_get_contents($_SERVER['DOCUMENT_ROOT'].'/interface/themes/responsive/js/aspen/serviceWorker.js');
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
?>