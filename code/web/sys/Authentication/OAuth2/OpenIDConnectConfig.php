<?php

/**
 * OpenID Connect Configuration
 */
class OpenIDConnectConfig {

	/**
	 * OpenID Connect Discovery Endpoint
	 * Returns server metadata for clients to discover endpoints
	 *
	 * GET /.well-known/openid-configuration
	 */
	public static function getDiscoveryDocument(string $baseUrl): array {
		global $logger;
		$logger->log("[OAuth2] OpenIDConnectConfig::getDiscoveryDocument() - Generating discovery document for base URL: " . $baseUrl, Logger::LOG_DEBUG);

		$discovery = [
			'issuer' => $baseUrl,
			'authorization_endpoint' => $baseUrl . '/Authentication/OAuth2/Authorize',
			'token_endpoint' => $baseUrl . '/Authentication/OAuth2/Token',
			'userinfo_endpoint' => $baseUrl . '/Authentication/OAuth2/UserInfo',
			'jwks_uri' => $baseUrl . '/.well-known/jwks.json',
			// openid is required in scopes_supported
			'scopes_supported' => [
				'openid',
				'profile',
				'email',
				'address',
				'phone',
			],
			'response_types_supported' => [
				'code',
				'token',
				'id_token',
				'code token',
				'code id_token',
				'token id_token',
				'code token id_token',
			],
			'response_modes_supported' => [
				'query',
				'fragment',
				'form_post',
			],
			'grant_types_supported' => [
				'authorization_code',
				'refresh_token',
			],
			'subject_types_supported' => ['public'],
			'id_token_signing_alg_values_supported' => ['RS256'],
			'claim_types_supported' => ['normal'],
			'claims_supported' => [
				'iss',
				'sub',
				'aud',
				'exp',
				'iat',
				'auth_time',
				'name',
				'email',
				'email_verified',
				'profile',
				'phone_number',
				'address',
				'preferred_username',
			],
			'token_endpoint_auth_methods_supported' => [
				'client_secret_basic',
				'client_secret_post',
				'private_key_jwt',
			],
			'service_documentation' => $baseUrl . '/documentation',
		];

		$logger->log("[OAuth2] OpenIDConnectConfig::getDiscoveryDocument() - Discovery document generated successfully", Logger::LOG_DEBUG);

		return $discovery;
	}

	/**
	 * Build ID Token Claims
	 * ID Tokens contain user identity information
	 */
	public static function buildIDTokenClaims(array $user, string $clientId, string $issuedAt, string $issuer): array {
		global $logger;
		$logger->log("[OAuth2] OpenIDConnectConfig::buildIDTokenClaims() - Building ID token claims for user: " . $user['id'] . ", client: " . $clientId . ", issuer: " . $issuer, Logger::LOG_DEBUG);

		$claims = [
			// Required OIDC Claims
			'iss' => $issuer,
			'sub' => (string)$user['id'],
			'aud' => $clientId,
			'exp' => time() + 3600,
			'iat' => $issuedAt,
			'auth_time' => $issuedAt,

			// User Profile Claims (from 'profile' scope)
			'name' => $user['username'] ?? null,
			'preferred_username' => $user['username'] ?? null,
			'family_name' => $user['lastName'] ?? null,
			'given_name' => $user['firstName'] ?? null,

			// Email Claims (from 'email' scope)
			'email' => $user['email'] ?? null,

			// Phone Claims (from 'phone' scope)
			'phone_number' => $user['phone'] ?? null,

			// Address Claims (from 'address' scope)
			'address' => [
				'formatted' => $user['address'] ?? null,
				'street_address' => $user['street'] ?? null,
				'locality' => $user['city'] ?? null,
				'region' => $user['state'] ?? null,
				'postal_code' => $user['zip'] ?? null,
				'country' => $user['country'] ?? null,
			],

			// Aspen-specific claims
			'library' => $user['homeLibrary'] ?? null,
			'patron_type' => $user['patronType'] ?? null,
			'source' => $user['source'] ?? null,
		];

		$logger->log("[OAuth2] OpenIDConnectConfig::buildIDTokenClaims() - ID token claims built successfully", Logger::LOG_DEBUG);

		return $claims;
	}

	/**
	 * Get Public Keys for JWKS (JSON Web Key Set)
	 * Allows client applications to verify ID token signatures
	 */
	public static function getJWKS(string $publicKeyPath): array {
		global $logger;
		$logger->log("[OAuth2] OpenIDConnectConfig::getJWKS() - Loading JWKS from: " . $publicKeyPath, Logger::LOG_DEBUG);
		
		if (!file_exists($publicKeyPath)) {
			$logger->log("[OAuth2] OpenIDConnectConfig::getJWKS() - WARNING: Public key file not found, returning empty JWKS", Logger::LOG_WARNING);
			return ['keys' => []];
		}

		$publicKey = file_get_contents($publicKeyPath);
		$keyDetails = openssl_pkey_get_details(openssl_pkey_get_public($publicKey));

		if (!$keyDetails) {
			$logger->log("[OAuth2] OpenIDConnectConfig::getJWKS() - ERROR: Could not extract key details from public key", Logger::LOG_ERROR);
			return ['keys' => []];
		}

		// Extract RSA modulus and exponent for JWK format
		$publicKey = $keyDetails['key'];
		$keyResource = openssl_pkey_get_public($publicKey);
		$keyDetails = openssl_pkey_get_details($keyResource);

		$jwks = [
			'keys' => [
				[
					'kty' => 'RSA',
					'use' => 'sig',
					'kid' => 'rsa1',
					'n' => self::base64urlEncode($keyDetails['rsa']['n']),
					'e' => self::base64urlEncode($keyDetails['rsa']['e']),
					'alg' => 'RS256',
				],
			],
		];

		$logger->log("[OAuth2] OpenIDConnectConfig::getJWKS() - JWKS loaded successfully with 1 key", Logger::LOG_DEBUG);

		return $jwks;
	}

	private static function base64urlEncode($str): string {
		return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
	}
}
