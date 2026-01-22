<?php

class OpenAPIAuthorizer {
	private static array $specCache = [];
	
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
