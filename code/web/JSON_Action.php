<?php

require_once 'Action.php';

class JSON_Action extends Action {
	function launch($method = null) {
		global $timer;
		if ($method == null) {
			$method = (isset($_REQUEST['method']) && !is_array($_REQUEST['method'])) ? $_REQUEST['method'] : '';
		}
		$this->outputHeaders();
		if (method_exists($this, $method)) {
			$timer->logTime("Starting method $method");

			$result = $this->$method();
			if (empty($result)) {
				$result = [
					'result' => false,
					'message' => translate([
						'text' => 'Method did not return results',
						'isPublicFacing' => true,
					]),
				];
			}
			$this->outputEncodedResult($result);
		} else {
			$this->outputEncodedResult(['error' => 'invalid_method']);
		}
	}

	protected function outputHeaders(): void {
		header('Content-type: application/json');
		header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	}

	/**
	 * @param array $result
	 */
	protected function outputEncodedResult(array $result) {
		$encodedData = json_encode($result);
		if ($encodedData == false) {
			//TODO: Should this send an error report?
			global $logger;
			$logger->log("Error encoding json data\r\n" . print_r($result, true), Logger::LOG_ERROR);
			$result = [
				'result' => false,
				'message' => 'JSON Encoding failed ' . json_last_error() . ' - ' . json_last_error_msg(),
			];
			echo json_encode($result);
		} else {
			echo($encodedData);
		}
	}

	function getBreadcrumbs(): array {
		return [];
	}
}