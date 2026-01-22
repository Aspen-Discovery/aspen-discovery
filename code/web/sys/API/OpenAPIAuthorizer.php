<?php

class OpenAPIAuthorizer {
	private static array $specCache = [];
	
	public static function authorize(string $apiClass, string $method, $user, bool $ipAllowed = false): array {
		$config = self::getMethodConfig($apiClass, $method);
		
		if ($config === null) {
			return [
				'allowed' => false,
				'error' => 'invalid_method',
				'code' => 404,
				'message' => "Method '$method' is not defined in the API specification"
			];
		}
		
		$auth = $config['x-aspen-authorization'] ?? [];
		
		if (!empty($auth['public']) && $auth['public'] === true) {
			return ['allowed' => true, 'scope' => 'public'];
		}
		
		if ($ipAllowed) {
			$scope = $auth['scope'] ?? 'ip';
			if (!isset($auth['ipAllowed']) || $auth['ipAllowed'] === true) {
				return ['allowed' => true, 'scope' => 'ip'];
			}
		}
		
		if ($user === false || $user === null) {
			return [
				'allowed' => false,
				'error' => 'unauthorized',
				'code' => 401,
				'message' => 'Authentication required'
			];
		}
		
		$scope = $auth['scope'] ?? 'patron';
		$requiredPermissions = $auth['permissions'] ?? [];
		
		if ($scope === 'staff' && !empty($requiredPermissions)) {
			if (!$user->hasPermission($requiredPermissions)) {
				return [
					'allowed' => false,
					'error' => 'insufficient_permissions',
					'code' => 403,
					'message' => 'Required permissions: ' . implode(', ', $requiredPermissions)
				];
			}
		}
		
		return ['allowed' => true, 'scope' => $scope];
	}
	
	public static function getMethodConfig(string $apiClass, string $method): ?array {
		$spec = self::loadSpec($apiClass);
		$paths = $spec['paths'] ?? [];
		
		if (isset($paths[$method])) {
			$methodDef = $paths[$method];
			foreach (['get', 'post', 'put', 'delete', 'patch'] as $httpMethod) {
				if (isset($methodDef[$httpMethod])) {
					return $methodDef[$httpMethod];
				}
			}
			return $methodDef;
		}
		
		return null;
	}
	
	private static function loadSpec(string $apiClass): array {
		if (!isset(self::$specCache[$apiClass])) {
			$specFile = ROOT_DIR . "/openapi/{$apiClass}_openapi.json";
			
			if (file_exists($specFile)) {
				$content = file_get_contents($specFile);
				$spec = json_decode($content, true);
				
				if (json_last_error() !== JSON_ERROR_NONE) {
					global $logger;
					$logger->log("Failed to parse OpenAPI spec for $apiClass: " . json_last_error_msg(), Logger::LOG_ERROR);
					self::$specCache[$apiClass] = ['paths' => []];
				} else {
					self::$specCache[$apiClass] = $spec;
				}
			} else {
				self::$specCache[$apiClass] = ['paths' => []];
			}
		}
		
		return self::$specCache[$apiClass];
	}
}
