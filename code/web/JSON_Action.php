<?php

require_once 'Action.php';

class JSON_Action extends Action {
	function launch($method = null) : void {
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
	protected function outputEncodedResult(array $result) : void {
		$encodedData = json_encode($result);
		if ($encodedData === false) {
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

	protected function checkRequiredModule(string $moduleName) : void {
		global $enabledModules;
		if (!in_array($moduleName, $enabledModules)) {
			$this->outputEncodedResult([
				'success' => false,
				'error' => "$moduleName is not active",
				'message' => "$moduleName is not active"
			]);
			die();
		}
	}

	protected function checkRequiredPermission(string|array $permission) : void {
		if (!UserAccount::userHasPermission($permission)) {
			$this->outputEncodedResult([
				'success' => false,
				'error' => "You don't have permission to access this functionality",
				'message' => "You don't have permission to access this functionality"
			]);
			die();
		}
	}

	protected function checkRequiredParameters(array $parameterNames) : void {
		foreach ($parameterNames as $parameterName) {
			if (empty($_REQUEST[$parameterName])) {
				$this->outputEncodedResult([
					'success' => false,
					'error' => "Missing $parameterName parameter"
				]);
				die();
			}
		}
	}

	protected function requireLoggedInUser(?string $titleTextIfLoggedOut = null, ?string $bodyTextIfLoggedOut = null, ?bool $publicFacing = true) : void {
		if (!UserAccount::isLoggedIn()) {
			echo json_encode([
				'success' => false,
				'title' => translate([
					'text' => $titleTextIfLoggedOut ?? 'Error',
					'isPublicFacing' => $publicFacing,
					'isAdminFacing' => !$publicFacing,
				]),
				'message' => translate([
					'text' => $bodyTextIfLoggedOut ?? 'You must be logged in to access this functionality.',
					'isPublicFacing' => $publicFacing,
					'isAdminFacing' => !$publicFacing,
				]),
			]);
			die();
		}
	}

	protected function successResult(?string $title, string $message) : array {
		return [
			'success' => true,
			'title' => translate([
				'text' => $title ?? 'Success',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => $message,
				'isPublicFacing' => true,
			]),
		];
	}

	protected function failureResult(?string $title, string $message) : array {
		return [
			'success' => false,
			'title' => translate([
				'text' => $title ?? 'Error',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => $message,
				'isPublicFacing' => true,
			]),
		];
	}

	protected function successResultAdmin(?string $title, string $message) : array {
		return [
			'success' => true,
			'title' => translate([
				'text' => $title ?? 'Success',
				'isAdminFacing' => true,
			]),
			'message' => translate([
				'text' => $message,
				'isAdminFacing' => true,
			]),
		];
	}

	protected function failureResultAdmin(?string $title, string $message) : array {
		return [
			'success' => false,
			'title' => translate([
				'text' => $title ?? 'Error',
				'isAdminFacing' => true,
			]),
			'message' => translate([
				'text' => $message,
				'isAdminFacing' => true,
			]),
		];
	}

	function getBreadcrumbs(): array {
		return [];
	}
}