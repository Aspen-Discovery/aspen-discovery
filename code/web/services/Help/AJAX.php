<?php
require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Proxy_Request.php';

global $configArray;

class Help_AJAX extends Action {

	function AJAX() {

	}

	function launch() {
		global $analytics;
		$analytics->disableTracking();
		$method = (isset($_GET['method']) && !is_array($_GET['method'])) ? $_GET['method'] : '';
		if (in_array($method, array('getSupportForm'))){
			header('Content-type: application/json');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}else{
			header ('Content-type: text/xml');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past

			$xmlResponse = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
			$xmlResponse .= "<AJAXResponse>\n";
			if (method_exists($this, $method)) {
				$xmlResponse .= $this->$_GET['method']();
			} else {
				$xmlResponse .= '<Error>Invalid Method</Error>';
			}
			$xmlResponse .= '</AJAXResponse>';

			echo $xmlResponse;
		}
	}

	function getSupportForm(){
		global $interface;
		$user = UserAccount::getActiveUserObj();

	// Presets for the form to be filled out with
		$interface->assign('lightbox', true);
		if ($user){
			$name = $user->firstname .' '. $user->lastname;
			$interface->assign('name', $name);
			$interface->assign('email', $user->email);
		}

		$results = array(
			'title' => 'eContent Support Request',
			'modalBody' => $interface->fetch('Help/eContentSupport.tpl'),
			'modalButtons' => '<span class="tool btn btn-primary" onclick="VuFind.EContent.submitHelpForm();">Submit</span>'
		);
		return json_encode($results);
	}

}
