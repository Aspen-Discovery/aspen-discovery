<?php

/**
 * API Method Configuration Trait
 *
 * This trait provides automatic method permission discovery using docblock annotations
 */
trait APIMethodConfiguration {

	private static $methodConfigCache = [];

	/**
	 * Get method configuration from docblock annotations
	 *
	 * @param string $method The method name to analyze
	 * @return array Configuration with keys: oauth, token, public, scopes
	 */
	protected function getMethodConfiguration($method): array {
		$className = get_class($this);
		$cacheKey = $className . '::' . $method;

		if (isset(self::$methodConfigCache[$cacheKey])) {
			return self::$methodConfigCache[$cacheKey];
		}

		$config = [
			'oauth' => false,
			'token' => false,
			'public' => false,
			'scopes' => []
		];

		try {
			$reflection = new ReflectionMethod($className, $method);
			$docComment = $reflection->getDocComment();

			if ($docComment) {
				// Parse docblock annotations
				if (preg_match('/@oauth\s+(true|false|yes|no)/i', $docComment, $matches)) {
					$config['oauth'] = in_array(strtolower($matches[1]), [
						'true',
						'yes'
					]);
				}

				if (preg_match('/@token\s+(true|false|yes|no)/i', $docComment, $matches)) {
					$config['token'] = in_array(strtolower($matches[1]), [
						'true',
						'yes'
					]);
				}

				if (preg_match('/@public\s+(true|false|yes|no)/i', $docComment, $matches)) {
					$config['public'] = in_array(strtolower($matches[1]), [
						'true',
						'yes'
					]);
				}

				// Parse scopes - supports multiple scopes separated by commas
				if (preg_match('/@scopes?\s+([^\r\n]+)/i', $docComment, $matches)) {
					$scopeList = trim($matches[1]);
					$config['scopes'] = array_map('trim', explode(',', $scopeList));
				}

				// Auto-detect scopes based on method name patterns if not explicitly set
				if (empty($config['scopes']) && ($config['oauth'] || $config['token'])) {
					$config['scopes'] = $this->inferScopesFromMethodName($method);
				}

				// Default to oauth and token auth if method is not explicitly public
				if (!$config['public'] && !$config['oauth'] && !$config['token']) {
					$config['oauth'] = true;
					$config['token'] = true;
				}
			}
		} catch (ReflectionException $e) {
			// Method doesn't exist - return default config
		}

		// Cache the result
		self::$methodConfigCache[$cacheKey] = $config;

		return $config;
	}

	/**
	 * Automatically infer scopes based on method naming conventions
	 */
	protected function inferScopesFromMethodName($method): array {
		$apiScope = $this->getAPIScopePrefix();
		if (preg_match('/^(create|add|edit|delete|update|remove|clear|set)/i', $method)) {
			return [$apiScope . ':write'];
		}
		if (preg_match('/^(get|list|search|find|load|retrieve|show|view)/i', $method)) {
			return [$apiScope . ':read'];
		}
		return [$apiScope . ':read'];
	}

	/**
	 * Get the API scope prefix for this API
	 * Override in subclasses to define API-specific scope prefixes
	 */
	protected function getAPIScopePrefix(): string {
		$className = get_class($this);
		return strtolower(str_replace('API', '', $className));
	}

	/**
	 * Get all methods that support OAuth authentication
	 */
	protected function getOAuthMethods(): array {
		return $this->getMethodsByType('oauth');
	}

	/**
	 * Get all methods that support token authentication
	 */
	protected function getTokenMethods(): array {
		return $this->getMethodsByType('token');
	}

	/**
	 * Get all methods that allow public access
	 */
	protected function getPublicMethods(): array {
		return $this->getMethodsByType('public');
	}

	/**
	 * Get methods by authentication type
	 */
	private function getMethodsByType($type): array {
		$methods = [];
		$reflection = new ReflectionClass(get_class($this));

		foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			$methodName = $method->getName();

			// Skip magic methods and inherited methods from base classes
			if (strpos($methodName, '__') === 0 || $method->getDeclaringClass()->getName() !== get_class($this)) {
				continue;
			}

			$config = $this->getMethodConfiguration($methodName);
			if ($config[$type]) {
				$methods[] = $methodName;
			}
		}

		return $methods;
	}

	/**
	 * Get required scopes for a method using docblock annotations
	 */
	protected function getRequiredScopesFromAnnotations($method): array {
		$config = $this->getMethodConfiguration($method);
		return $config['scopes'];
	}
}
