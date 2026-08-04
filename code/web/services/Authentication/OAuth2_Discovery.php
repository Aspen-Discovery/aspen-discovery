<?php

require_once ROOT_DIR . '/JSON_Action.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OpenIDConnectConfig.php';

/**
 * OpenID Connect Discovery Endpoint
 *
 * Returns server metadata for OIDC-compliant clients
 *
 * Usage:
 * GET /.well-known/openid-configuration
 */
class Authentication_OAuth2_Discovery extends JSON_Action {

	function launch($method = null): void {
		global $logger;
		$logger->log("[OAuth2] OAuth2_Discovery - Starting OpenID Connect discovery endpoint", Logger::LOG_DEBUG);
		
		$baseUrl = $this->getBaseUrl();
		$logger->log("[OAuth2] OAuth2_Discovery - Base URL: " . $baseUrl, Logger::LOG_DEBUG);
		
		$discovery = OpenIDConnectConfig::getDiscoveryDocument($baseUrl);

		$logger->log("[OAuth2] OAuth2_Discovery - Discovery document generated successfully", Logger::LOG_DEBUG);

		header('Content-Type: application/json');
		header('Cache-Control: public, max-age=3600');  // Cache for 1 hour

		echo json_encode($discovery);
	}

	private function getBaseUrl(): string {
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'];
		$basePath = dirname($_SERVER['SCRIPT_NAME']);

		return $protocol . '://' . $host . $basePath;
	}
}
