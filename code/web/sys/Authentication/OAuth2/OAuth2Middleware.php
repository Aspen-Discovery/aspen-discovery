<?php

use League\OAuth2\Server\Exception\OAuthServerException;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2ServerConfig.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Client.php';

class OAuth2Middleware {

	private static $resourceServer = null;
	private static ?User $authenticatedUser = null;
	private static ?string $authenticatedClientId = null;
	private static array $currentScopes = [];

	/**
	 * Authenticate an API request using OAuth2 Bearer token
	 * @param array $requiredScopes Optional array of required scopes
	 * @return bool True if authentication successful, false otherwise
	 */
	public static function authenticate(array $requiredScopes = []): bool {
		global $logger;
		
		try {
			$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Starting OAuth2 token validation", Logger::LOG_DEBUG);
			
			if (self::$resourceServer === null) {
				$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Initializing resource server", Logger::LOG_DEBUG);
				OAuth2ServerConfig::generateKeyPairIfNeeded();
				self::$resourceServer = OAuth2ServerConfig::getResourceServer();
			}

			$request = self::createPsr7Request();
			$logger->log("[OAuth2] OAuth2Middleware::authenticate() - PSR7 request created", Logger::LOG_DEBUG);
			
			$request = self::$resourceServer->validateAuthenticatedRequest($request);
			$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Bearer token validated successfully", Logger::LOG_DEBUG);
			
			$userId = $request->getAttribute('oauth_user_id');
			$clientId = $request->getAttribute('oauth_client_id');
			$tokenScopes = $request->getAttribute('oauth_scopes', []);
			self::$currentScopes = is_array($tokenScopes) ? $tokenScopes : explode(' ', $tokenScopes);

			$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Token attributes - User ID: " . ($userId ?? 'none') . ", Client ID: " . ($clientId ?? 'none'), Logger::LOG_DEBUG);
			$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Token scopes: " . implode(', ', self::$currentScopes), Logger::LOG_DEBUG);
			
			self::$authenticatedUser = null;
			self::$authenticatedClientId = $clientId;

			if (!empty($clientId)) {
				$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Validating token scopes against client's allowed scopes", Logger::LOG_DEBUG);
				if (!self::validateClientTokenScopes($clientId, self::$currentScopes)) {
					$logger->log("[OAuth2] OAuth2Middleware::authenticate() - SECURITY FAILURE: Token scopes do not match client's allowed scopes", Logger::LOG_ERROR);
					self::sendGenericErrorResponse();
					return false;
				}
			}

			if ($userId) {
				$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Loading user entity for ID: " . $userId, Logger::LOG_DEBUG);
				self::$authenticatedUser = new User();
				self::$authenticatedUser->id = $userId;
				if (!self::$authenticatedUser->find(true)) {
					$logger->log("[OAuth2] OAuth2Middleware::authenticate() - User not found in database: " . $userId, Logger::LOG_WARNING);
					self::sendGenericErrorResponse();
					return false;
				}
				$logger->log("[OAuth2] OAuth2Middleware::authenticate() - User successfully loaded: " . $userId, Logger::LOG_DEBUG);
			} else {
				$logger->log("[OAuth2] OAuth2Middleware::authenticate() - No user ID in token (client credentials flow)", Logger::LOG_DEBUG);
				self::$authenticatedUser = null;
			}

			if (!empty($requiredScopes)) {
				$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Checking required scopes: " . implode(', ', $requiredScopes), Logger::LOG_DEBUG);
				foreach ($requiredScopes as $requiredScope) {
					if (!in_array($requiredScope, self::$currentScopes)) {
						$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Required scope missing: " . $requiredScope, Logger::LOG_WARNING);
						self::sendGenericErrorResponse();
						return false;
					}
				}
				$logger->log("[OAuth2] OAuth2Middleware::authenticate() - All required scopes validated successfully", Logger::LOG_DEBUG);
			}

			$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Authentication successful", Logger::LOG_DEBUG);
			return true;

		} catch (OAuthServerException $exception) {
			$logger->log("[OAuth2] OAuth2Middleware::authenticate() - OAuthServerException: " . $exception->getErrorType() . " - " . $exception->getMessage(), Logger::LOG_WARNING);
			self::sendOAuthErrorResponse($exception);
			return false;
		} catch (Exception $exception) {
			$logger->log("[OAuth2] OAuth2Middleware::authenticate() - Exception: " . $exception->getMessage(), Logger::LOG_WARNING);
			self::sendGenericErrorResponse();
			return false;
		}
	}

	/**
	 * Get the authenticated user from OAuth2 token
	 * @return User|null
	 */
	public static function getAuthenticatedUser(): ?User {
		return self::$authenticatedUser;
	}

	/**
	 * Get the authenticated client ID from OAuth2 token
	 * @return string|null
	 */
	public static function getAuthenticatedClientId(): ?string {
		return self::$authenticatedClientId;
	}

	private static function createPsr7Request(): ServerRequestInterface {
		return ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
	}

	private static function sendOAuthErrorResponse(OAuthServerException $exception): void {
		global $logger;
		$statusCode = $exception->getHttpStatusCode();
		$logger->log("[OAuth2] OAuth2Middleware::sendOAuthErrorResponse() - Sending OAuth error response. Status: " . $statusCode . ", Error Type: " . $exception->getErrorType() . ", Message: " . $exception->getMessage(), Logger::LOG_WARNING);

		http_response_code($statusCode);
		header('Content-Type: application/json');
		echo json_encode([
			'error' => $exception->getErrorType(),
			'error_description' => $exception->getMessage(),
		]);
	}

	/**
	 * Validate that all token scopes are actually allowed for the client
	 * This prevents tokens with unauthorized scopes from being used
	 */
	private static function validateClientTokenScopes(string $clientId, array $tokenScopes): bool {
		global $logger;

		$logger->log("[OAuth2] OAuth2Middleware::validateClientTokenScopes() - Validating token scopes for client: " . $clientId, Logger::LOG_DEBUG);

		$clientType = '';
		$client = new OAuth2Client();
		$client->setClientId($clientId);
		$client->setIsActive(1);
		if ($client->find(true)) {
			$clientType = 'oauth2';
		} else {
			$client = null;
		}

		if ($client === null) {
			$client = new OpenIDClient();
			$client->setClientId($clientId);
			$client->setIsActive(1);
			if ($client->find(true)) {
				$clientType = 'openid';
			} else {
				$client = null;
			}
		}

		if ($client === null) {
			$logger->log("[OAuth2] OAuth2Middleware::validateClientTokenScopes() - CLIENT NOT FOUND for ID: " . $clientId, Logger::LOG_DEBUG);
			return false;
		}

		$logger->log("[OAuth2] OAuth2Middleware::validateClientTokenScopes() - FOUND {$clientType} client: " . $client->getName(), Logger::LOG_DEBUG);

		if ($clientType === 'openid') {
			$allowed = $client->getClaimsArray();
			$logger->log("[OAuth2] OAuth2Middleware::validateClientTokenScopes() - Client allowed claims: " . implode(', ', $allowed), Logger::LOG_DEBUG);
		} else {
			$allowed = $client->getScopesArray();
			$logger->log("[OAuth2] OAuth2Middleware::validateClientTokenScopes() - Client allowed scopes: " . implode(', ', $allowed), Logger::LOG_DEBUG);

		}

		$logger->log("[OAuth2] OAuth2Middleware::validateClientTokenScopes() - Token has scopes: " . implode(', ', $tokenScopes), Logger::LOG_DEBUG);

		foreach ($tokenScopes as $tokenScope) {
			$isAllowed = in_array($tokenScope, $allowed) || $tokenScope === 'openid';

			if (!$isAllowed) {
				$logger->log("[OAuth2] OAuth2Middleware::validateClientTokenScopes() - SECURITY: Token scope NOT allowed for client: " . $tokenScope, Logger::LOG_ERROR);
				return false;
			}

			$logger->log("[OAuth2] OAuth2Middleware::validateClientTokenScopes() - Token scope valid for client: " . $tokenScope, Logger::LOG_DEBUG);
		}

		$logger->log("[OAuth2] OAuth2Middleware::validateClientTokenScopes() - All token scopes are valid for this client", Logger::LOG_DEBUG);
		return true;
	}

	private static function sendGenericErrorResponse(): void {
		global $logger;
		$logger->log("[OAuth2] OAuth2Middleware::sendGenericErrorResponse() - Sending generic 401 unauthorized response", Logger::LOG_WARNING);
		
		http_response_code(401);
		header('Content-Type: application/json');
		echo json_encode([
			'error' => 'unauthorized',
			'error_description' => 'Invalid or missing access token',
		]);
	}
}
