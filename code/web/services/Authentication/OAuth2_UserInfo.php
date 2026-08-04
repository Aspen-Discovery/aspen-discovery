<?php

require_once ROOT_DIR . '/JSON_Action.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Middleware.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OpenIDConnectConfig.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Client.php';

/**
 * OpenID Connect UserInfo Endpoint
 *
 * Returns authenticated user's claims
 * Requires valid OAuth2 access token with 'openid' scope
 * AND client must have OpenID Connect support enabled
 *
 * Usage:
 * GET /Authentication/OAuth2/UserInfo
 * Authorization: Bearer {access_token}
 */
class Authentication_OAuth2_UserInfo extends JSON_Action {

	function launch($method = null): void {
		global $logger;
		$logger->log("[OAuth2] OAuth2_UserInfo - Starting UserInfo endpoint request", Logger::LOG_DEBUG);
		
		// Require 'openid' scope for OIDC compliance
		if (!OAuth2Middleware::authenticate(['openid'])) {
			$logger->log("[OAuth2] OAuth2_UserInfo - Authentication failed or 'openid' scope missing", Logger::LOG_WARNING);
			// Error response already sent by middleware
			return;
		}

		$logger->log("[OAuth2] OAuth2_UserInfo - User authenticated successfully", Logger::LOG_DEBUG);

		// Get authenticated user
		$user = OAuth2Middleware::getAuthenticatedUser();
		if (!$user) {
			$logger->log("[OAuth2] OAuth2_UserInfo - User object not found after authentication", Logger::LOG_WARNING);
			http_response_code(401);
			header('Content-Type: application/json');
			echo json_encode([
				'error' => 'invalid_token',
				'error_description' => 'User not authenticated',
			]);
			return;
		}

		$logger->log("[OAuth2] OAuth2_UserInfo - User loaded: " . $user->id, Logger::LOG_DEBUG);

		// Verify that the client has OpenID Connect support enabled
		$clientId = OAuth2Middleware::getAuthenticatedClientId();
		$logger->log("[OAuth2] OAuth2_UserInfo - Validating OpenID Connect support for client: " . ($clientId ?? 'none'), Logger::LOG_DEBUG);
		
		if (!$this->validateClientOpenIDSupport($clientId)) {
			$logger->log("[OAuth2] OAuth2_UserInfo - Client does not support OpenID Connect: " . ($clientId ?? 'none'), Logger::LOG_WARNING);
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode([
				'error' => 'access_denied',
				'error_description' => 'Client is not authorized to access UserInfo endpoint (OpenID Connect not enabled)',
			]);
			return;
		}

		$logger->log("[OAuth2] OAuth2_UserInfo - Client supports OpenID Connect", Logger::LOG_DEBUG);

		// Build user info response based on scopes
		$scopes = $this->getTokenScopes();
		$logger->log("[OAuth2] OAuth2_UserInfo - Token scopes: " . implode(', ', $scopes), Logger::LOG_DEBUG);
		
		$userInfo = $this->buildUserInfo($user, $scopes);

		$logger->log("[OAuth2] OAuth2_UserInfo - UserInfo response built successfully for user: " . $user->id, Logger::LOG_DEBUG);

		header('Content-Type: application/json');
		echo json_encode($userInfo);
	}

	/**
	 * Validate that the client has OpenID Connect support enabled
	 */
	private function validateClientOpenIDSupport(?string $clientId): bool {
		global $logger;
		
		if (!$clientId) {
			$logger->log("[OAuth2] OAuth2_UserInfo::validateClientOpenIDSupport() - No client ID provided", Logger::LOG_WARNING);
			return false;
		}

		$client = new OpenIDClient();
		$client->setClientId($clientId);
		
		if (!$client->find(true)) {
			$logger->log("[OAuth2] OAuth2_UserInfo::validateClientOpenIDSupport() - Client not found: " . $clientId, Logger::LOG_WARNING);
			return false;
		}

		$logger->log("[OAuth2] OAuth2_UserInfo::validateClientOpenIDSupport() - Client found: " . $client->getName(), Logger::LOG_DEBUG);

		// if we found a valid OIDC client they are allowed here
		return true;
	}


	/**
	 * Get scopes from the current OAuth2 token
	 */
	private function getTokenScopes(): array {
		// This would be extracted from the validated token
		// For now, return all scopes as a default
		return [
			'openid',
			'profile',
			'email',
		];
	}

	/**
	 * Build UserInfo response based on requested scopes
	 */
	private function buildUserInfo($user, array $scopes): array {
		$info = [
			'sub' => (string)$user->id,
		];

		if (in_array('profile', $scopes)) {
			$info = array_merge($info, [
				'name' => $user->username,
				'preferred_username' => $user->username,
				'profile' => null,
			]);
		}

		if (in_array('email', $scopes)) {
			$info = array_merge($info, [
				'email' => $user->email ?? null,
				'email_verified' => !empty($user->email),
			]);
		}

		if (in_array('phone', $scopes)) {
			$info = array_merge($info, [
				'phone_number' => $user->phone ?? null,
				'phone_number_verified' => !empty($user->phone),
			]);
		}

		if (in_array('address', $scopes)) {
			$info = array_merge($info, [
				'address' => [
					'formatted' => $user->address ?? null,
				],
			]);
		}

		$info['library'] = $user->homeLibrary ?? null;

		return $info;
	}
}
