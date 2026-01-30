<?php

require_once ROOT_DIR . '/services/API/AbstractAPI.php';

/**
 * Test API for validating OpenAPI-driven authorization
 * 
 * This API uses the new launchWithOpenAPI() flow for spec-driven auth.
 * Used for testing the OAuth permission system.
 */
class TestAPI extends AbstractAPI {
	
	function launch(): void {
		$this->launchWithOpenAPI();
	}

	/**
	 * Public endpoint - no authentication required
	 */
	function ping(): array {
		return [
			'success' => true,
			'message' => 'pong',
			'timestamp' => time(),
			'scope' => $this->getAuthorizedScope()
		];
	}

	/**
	 * Authenticated endpoint - requires valid OAuth or Greenhouse auth
	 */
	function whoami(): array {
		$user = $this->getAuthorizedUser();
		
		if ($user === false || $user === null) {
			return [
				'success' => true,
				'authenticated' => false,
				'scope' => $this->getAuthorizedScope(),
				'message' => 'No user context (app-level or IP auth)'
			];
		}
		
		return [
			'success' => true,
			'authenticated' => true,
			'scope' => $this->getAuthorizedScope(),
			'user' => [
				'id' => $user->id,
				'username' => $user->username ?? $user->ils_barcode ?? 'unknown',
				'displayName' => $user->displayName ?? $user->firstname . ' ' . $user->lastname
			]
		];
	}

	/**
	 * Greenhouse-only endpoint
	 */
	function appInfo(): array {
		return [
			'success' => true,
			'scope' => $this->getAuthorizedScope(),
			'message' => 'This endpoint is for Greenhouse/LiDA apps only',
			'serverTime' => date('c')
		];
	}

	/**
	 * Endpoint requiring specific permission
	 */
	function adminCheck(): array {
		$user = $this->getAuthorizedUser();
		
		return [
			'success' => true,
			'scope' => $this->getAuthorizedScope(),
			'message' => 'You have the required permission',
			'user' => $user ? ($user->displayName ?? $user->username) : 'superuser'
		];
	}

	/**
	 * Endpoint that allows both user and greenhouse auth
	 */
	function flexibleAuth(): array {
		$user = $this->getAuthorizedUser();
		$scope = $this->getAuthorizedScope();
		
		return [
			'success' => true,
			'scope' => $scope,
			'authMethod' => $user ? 'user' : 'app',
			'message' => "Authenticated via $scope scope"
		];
	}

	/**
	 * Endpoint blocked from IP access
	 */
	function noIpAccess(): array {
		return [
			'success' => true,
			'scope' => $this->getAuthorizedScope(),
			'message' => 'IP whitelist cannot access this endpoint'
		];
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
