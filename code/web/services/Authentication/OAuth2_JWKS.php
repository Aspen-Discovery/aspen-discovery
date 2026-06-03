<?php

require_once ROOT_DIR . '/JSON_Action.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OpenIDConnectConfig.php';

/**
 * JSON Web Key Set (JWKS) Endpoint
 *
 * Returns public keys for verifying ID token signatures
 *
 * Usage:
 * GET /.well-known/jwks.json
 */
class Authentication_OAuth2_JWKS extends JSON_Action {

	function launch($method = null): void {
		global $logger;
		$logger->log("[OAuth2] OAuth2_JWKS - Starting JWKS endpoint request", Logger::LOG_DEBUG);
		
		$publicKeyPath = ROOT_DIR . '/services/Authentication/OAuth2Server/public.key';
		$logger->log("[OAuth2] OAuth2_JWKS - Public key path: " . $publicKeyPath, Logger::LOG_DEBUG);
		
		if (!file_exists($publicKeyPath)) {
			$logger->log("[OAuth2] OAuth2_JWKS - WARNING: Public key file not found at: " . $publicKeyPath, Logger::LOG_WARNING);
		}
		
		$jwks = OpenIDConnectConfig::getJWKS($publicKeyPath);

		$logger->log("[OAuth2] OAuth2_JWKS - JWKS document generated successfully", Logger::LOG_DEBUG);

		header('Content-Type: application/json');
		header('Cache-Control: public, max-age=3600');  // Cache for 1 hour

		echo json_encode($jwks);
	}
}
