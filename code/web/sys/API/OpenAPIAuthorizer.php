<?php

class OpenAPIAuthorizer {
	private static array $specCache = [];
	
	/**
	 * Authorize an API request based on OpenAPI spec
	 * 
	 * Scopes (can be string or array for OR logic):
	 * - public: No authentication required
	 * - greenhouse: Greenhouse/LiDA app-level auth
	 * - user: Authenticated user (checks permissions array if present)
	 * 
	 * Bypasses (not scopes - these override scope checks):
	 * - Whitelisted IP: Grants access unless endpoint sets ipAllowed: false
	 * - Superuser: User with 'Use All API Endpoints' permission
	 * 
	 * Examples:
	 *   "scope": "user"                        - Authenticated user
	 *   "scope": "user", "permissions": [...]  - User with specific permissions
	 *   "scope": ["user", "greenhouse"]        - Authenticated user OR app keys
	 * 
	 * @param string $apiClass The API class name
	 * @param string $method The method name
	 * @param mixed $user The authenticated user or false
	 * @param bool $ipAllowed Whether request is from whitelisted IP
	 * @param bool $greenhouseAuth Whether Greenhouse/LiDA auth succeeded
	 * @return array Authorization result
	 */
	public static function authorize(string $apiClass, string $method, $user, bool $ipAllowed = false, bool $greenhouseAuth = false): array {
		$config = self::getMethodConfig($apiClass, $method);
		
		if ($config === null) {
			return [
				'allowed' => false,
				'error' => 'invalid_method',
				'code' => 404,
				'message' => "Method '$method' is not defined in the API specification"
			];
		}
		
		// Superuser bypass - 'Use All API Endpoints' grants full access
		if ($user !== false && $user !== null && $user->hasPermission('Use All API Endpoints')) {
			return ['allowed' => true, 'scope' => 'superuser'];
		}
		
		$auth = $config['x-aspen-authorization'] ?? [];
		
		// Public endpoints - no auth required
		if (!empty($auth['public']) && $auth['public'] === true) {
			return ['allowed' => true, 'scope' => 'public'];
		}
		
		// IP-based access (always checked first if allowed)
		if ($ipAllowed) {
			if (!isset($auth['ipAllowed']) || $auth['ipAllowed'] === true) {
				return ['allowed' => true, 'scope' => 'ip'];
			}
		}
		
		// Get scopes - can be string or array
		$scopeConfig = $auth['scope'] ?? 'user';
		$scopes = is_array($scopeConfig) ? $scopeConfig : [$scopeConfig];
		$requiredPermissions = $auth['permissions'] ?? [];
		
		// Try each scope (OR logic)
		foreach ($scopes as $scope) {
			$result = self::checkScope($scope, $user, $greenhouseAuth, $requiredPermissions);
			if ($result['allowed']) {
				return $result;
			}
		}
		
		// None of the scopes matched - return appropriate error
		if (in_array('user', $scopes) && $user !== false && !empty($requiredPermissions)) {
			return [
				'allowed' => false,
				'error' => 'insufficient_permissions',
				'code' => 403,
				'message' => 'Required permissions: ' . implode(', ', $requiredPermissions)
			];
		}
		
		return [
			'allowed' => false,
			'error' => 'unauthorized',
			'code' => 401,
			'message' => 'Authentication required'
		];
	}
	
	/**
	 * Check if a specific scope is satisfied
	 */
	private static function checkScope(string $scope, $user, bool $greenhouseAuth, array $requiredPermissions): array {
		switch ($scope) {
			case 'greenhouse':
				if ($greenhouseAuth) {
					return ['allowed' => true, 'scope' => 'greenhouse'];
				}
				break;
				
			case 'user':
				if ($user !== false && $user !== null) {
					if (!empty($requiredPermissions) && !$user->hasPermission($requiredPermissions)) {
						return ['allowed' => false];
					}
					return ['allowed' => true, 'scope' => 'user'];
				}
				break;
		}
		
		return ['allowed' => false];
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
	
	public static function getAllMethods(string $apiClass): array {
		$spec = self::loadSpec($apiClass);
		return array_keys($spec['paths'] ?? []);
	}
	
	public static function methodExists(string $apiClass, string $method): bool {
		return self::getMethodConfig($apiClass, $method) !== null;
	}
	
	/**
	 * Get response configuration for a method
	 * 
	 * x-aspen-response options:
	 * - raw: bool - Don't wrap response in ['result' => ...], output directly
	 * - contentType: string - Override Content-Type header (default: application/json)
	 * - cacheMaxAge: int - Set Cache-Control max-age in seconds
	 * - xmlDeclaration: bool - Prepend XML declaration for XML responses
	 * 
	 * @param string $apiClass The API class name
	 * @param string $method The method name
	 * @return array Response configuration
	 */
	public static function getResponseConfig(string $apiClass, string $method): array {
		$config = self::getMethodConfig($apiClass, $method);
		if ($config === null) {
			return [];
		}
		return $config['x-aspen-response'] ?? [];
	}
	
	public static function clearCache(): void {
		self::$specCache = [];
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
