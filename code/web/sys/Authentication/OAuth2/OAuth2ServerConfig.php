<?php

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;

require_once ROOT_DIR . '/sys/Authentication/OAuth2/Repositories/ClientRepository.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/Repositories/AccessTokenRepository.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/Repositories/ScopeRepository.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/Repositories/RefreshTokenRepository.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/Repositories/UserRepository.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/Repositories/AuthCodeRepository.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OpenIDConnectConfig.php';

class OAuth2ServerConfig {

	private static ?AuthorizationServer $authorizationServer = null;
	private static ?ResourceServer $resourceServer = null;

	/**
	 * @throws Exception
	 */
	public static function getAuthorizationServer(): AuthorizationServer {
		global $logger;
		
		if (self::$authorizationServer === null) {
			$logger->log("[OAuth2] OAuth2ServerConfig::getAuthorizationServer() - Initializing authorization server", Logger::LOG_DEBUG);
			
			$clientRepository = new ClientRepository();
			$accessTokenRepository = new AccessTokenRepository();
			$authCodeRepository = new AuthCodeRepository();
			$refreshTokenRepository = new RefreshTokenRepository();
			$scopeRepository = new ScopeRepository();
			$userRepository = new UserRepository();

			$logger->log("[OAuth2] OAuth2ServerConfig::getAuthorizationServer() - All repositories initialized", Logger::LOG_DEBUG);

			$privateKeyPath = ROOT_DIR . '/sys/Authentication/OAuth2/private.key';
			$encryptionKey = self::getEncryptionKey();

			$logger->log("[OAuth2] OAuth2ServerConfig::getAuthorizationServer() - Using private key path: " . $privateKeyPath, Logger::LOG_DEBUG);

			self::$authorizationServer = new AuthorizationServer($clientRepository, $accessTokenRepository, $scopeRepository, $privateKeyPath, $encryptionKey);
			$logger->log("[OAuth2] OAuth2ServerConfig::getAuthorizationServer() - AuthorizationServer instance created", Logger::LOG_DEBUG);

			$authCodeGrant = new AuthCodeGrant($authCodeRepository, $refreshTokenRepository, new DateInterval('PT10M') // Authorization codes expire after 10 minutes
			);
			$authCodeGrant->setRefreshTokenTTL(new DateInterval('P1M')); // Refresh tokens expire after 1 month
			$logger->log("[OAuth2] OAuth2ServerConfig::getAuthorizationServer() - AuthCodeGrant configured (code TTL: 10 min, refresh TTL: 1 month)", Logger::LOG_DEBUG);

			self::$authorizationServer->enableGrantType($authCodeGrant, new DateInterval('PT1H') // Access tokens expire after 1 hour
			);

			$passwordGrant = new PasswordGrant($userRepository, $refreshTokenRepository);
			$passwordGrant->setRefreshTokenTTL(new DateInterval('P1M')); // Refresh tokens expire after 1 month
			$logger->log("[OAuth2] OAuth2ServerConfig::getAuthorizationServer() - PasswordGrant configured (token TTL: 1 hour, refresh TTL: 1 month)", Logger::LOG_DEBUG);

			self::$authorizationServer->enableGrantType($passwordGrant, new DateInterval('PT1H') // Access tokens expire after 1 hour
			);

			$refreshTokenGrant = new RefreshTokenGrant($refreshTokenRepository);
			$refreshTokenGrant->setRefreshTokenTTL(new DateInterval('P1M')); // New refresh tokens expire after 1 month
			$logger->log("[OAuth2] OAuth2ServerConfig::getAuthorizationServer() - RefreshTokenGrant configured (token TTL: 1 hour, refresh TTL: 1 month)", Logger::LOG_DEBUG);

			self::$authorizationServer->enableGrantType($refreshTokenGrant, new DateInterval('PT1H') // Access tokens expire after 1 hour
			);

			$clientCredentialsGrant = new ClientCredentialsGrant($clientRepository, $scopeRepository);
			$logger->log("[OAuth2] OAuth2ServerConfig::getAuthorizationServer() - ClientCredentialsGrant configured (token TTL: 4 hours)", Logger::LOG_DEBUG);
			
			self::$authorizationServer->enableGrantType($clientCredentialsGrant, new DateInterval('PT4H') // Client credentials tokens last longer (4 hours)
			);

			$logger->log("[OAuth2] OAuth2ServerConfig::getAuthorizationServer() - Authorization server fully initialized with all grant types", Logger::LOG_DEBUG);
		}

		return self::$authorizationServer;
	}

	public static function getResourceServer(): ResourceServer {
		global $logger;
		
		if (self::$resourceServer === null) {
			$logger->log("[OAuth2] OAuth2ServerConfig::getResourceServer() - Initializing resource server", Logger::LOG_DEBUG);
			
			$accessTokenRepository = new AccessTokenRepository();
			$publicKeyPath = ROOT_DIR . '/sys/Authentication/OAuth2/public.key';

			$logger->log("[OAuth2] OAuth2ServerConfig::getResourceServer() - Using public key path: " . $publicKeyPath, Logger::LOG_DEBUG);

			self::$resourceServer = new ResourceServer($accessTokenRepository, $publicKeyPath);

			$logger->log("[OAuth2] OAuth2ServerConfig::getResourceServer() - Resource server fully initialized", Logger::LOG_DEBUG);
		}

		return self::$resourceServer;
	}

	private static function getEncryptionKey(): string {
		global $logger;

		$keyFile = ROOT_DIR . '/sys/Authentication/OAuth2/encryption.key';

		if (!file_exists($keyFile)) {
			$logger->log("[OAuth2] OAuth2ServerConfig::getEncryptionKey() - Encryption key file not found, generating new key: " . $keyFile, Logger::LOG_DEBUG);
			$key = base64_encode(random_bytes(32));
			file_put_contents($keyFile, $key);
			chmod($keyFile, 0600);
			$logger->log("[OAuth2] OAuth2ServerConfig::getEncryptionKey() - New encryption key generated and saved", Logger::LOG_DEBUG);
		} else {
			$logger->log("[OAuth2] OAuth2ServerConfig::getEncryptionKey() - Loading existing encryption key", Logger::LOG_DEBUG);
		}

		return base64_decode(file_get_contents($keyFile));
	}

	public static function generateKeyPairIfNeeded(): void {
		global $logger;

		$privateKeyPath = ROOT_DIR . '/sys/Authentication/OAuth2/private.key';
		$publicKeyPath = ROOT_DIR . '/sys/Authentication/OAuth2/public.key';

		if (!file_exists($privateKeyPath) || !file_exists($publicKeyPath)) {
			$logger->log("[OAuth2] OAuth2ServerConfig::generateKeyPairIfNeeded() - RSA key pair not found, generating new pair", Logger::LOG_DEBUG);
			self::generateKeyPair($privateKeyPath, $publicKeyPath);
		} else {
			$logger->log("[OAuth2] OAuth2ServerConfig::generateKeyPairIfNeeded() - RSA key pair already exists", Logger::LOG_DEBUG);
		}
	}

	private static function generateKeyPair(string $privateKeyPath, string $publicKeyPath): void {
		global $logger;

		$logger->log("[OAuth2] OAuth2ServerConfig::generateKeyPair() - Generating RSA 2048-bit key pair", Logger::LOG_DEBUG);
		
		$config = [
			"digest_alg" => "sha512",
			"private_key_bits" => 2048,
			"private_key_type" => OPENSSL_KEYTYPE_RSA,
		];

		$res = openssl_pkey_new($config);
		if ($res === false) {
			$logger->log("[OAuth2] OAuth2ServerConfig::generateKeyPair() - Failed to generate private key", Logger::LOG_ERROR);
			return;
		}

		openssl_pkey_export($res, $privateKey);
		file_put_contents($privateKeyPath, $privateKey);
		chmod($privateKeyPath, 0600);
		$logger->log("[OAuth2] OAuth2ServerConfig::generateKeyPair() - Private key saved to: " . $privateKeyPath, Logger::LOG_DEBUG);

		$publicKeyDetails = openssl_pkey_get_details($res);
		$publicKey = $publicKeyDetails["key"];
		file_put_contents($publicKeyPath, $publicKey);
		chmod($publicKeyPath, 0600);
		$logger->log("[OAuth2] OAuth2ServerConfig::generateKeyPair() - Public key saved to: " . $publicKeyPath, Logger::LOG_DEBUG);

		$logger->log("[OAuth2] OAuth2ServerConfig::generateKeyPair() - RSA key pair generation completed successfully", Logger::LOG_DEBUG);
	}

	/**
	 *  Generate ID Token for OpenID Connect
	 */
	public static function generateIDToken(array $user, string $clientId, string $issuer): string {
		global $logger;

		$logger->log("[OAuth2] OAuth2ServerConfig::generateIDToken() - Generating ID token for client: " . $clientId . ", issuer: " . $issuer, Logger::LOG_DEBUG);
		
		$issuedAt = time();
		$claims = OpenIDConnectConfig::buildIDTokenClaims($user, $clientId, $issuedAt, $issuer);

		$logger->log("[OAuth2] OAuth2ServerConfig::generateIDToken() - ID token claims built successfully", Logger::LOG_DEBUG);

		$token = self::createJWT($claims);

		$logger->log("[OAuth2] OAuth2ServerConfig::generateIDToken() - ID token generated and signed", Logger::LOG_DEBUG);

		return $token;
	}

	/**
	 * Create and sign JWT token for OpenID Connect
	 */
	private static function createJWT(array $claims): string {
		global $logger;

		$logger->log("[OAuth2] OAuth2ServerConfig::createJWT() - Creating and signing JWT token", Logger::LOG_DEBUG);
		
		$header = [
			'typ' => 'JWT',
			'alg' => 'RS256',
		];

		$header64 = self::base64urlEncode(json_encode($header));
		$payload64 = self::base64urlEncode(json_encode($claims));

		$privateKeyPath = ROOT_DIR . '/sys/Authentication/OAuth2/private.key';
		$privateKey = file_get_contents($privateKeyPath);

		$signature = '';
		$signResult = openssl_sign($header64 . '.' . $payload64, $signature, $privateKey, OPENSSL_ALGO_SHA256);

		if (!$signResult) {
			$logger->log("[OAuth2] OAuth2ServerConfig::createJWT() - Failed to sign JWT token", Logger::LOG_ERROR);
		} else {
			$logger->log("[OAuth2] OAuth2ServerConfig::createJWT() - JWT token successfully signed with RS256", Logger::LOG_DEBUG);
		}

		$signature64 = self::base64urlEncode($signature);

		$jwt = $header64 . '.' . $payload64 . '.' . $signature64;

		$logger->log("[OAuth2] OAuth2ServerConfig::createJWT() - JWT token creation completed", Logger::LOG_DEBUG);

		return $jwt;
	}

	private static function base64urlEncode(string $str): string {
		return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
	}
}
