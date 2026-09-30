<?php

use League\OAuth2\Server\Exception\OAuthServerException;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2ServerConfig.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Client.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2UserConsent.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/RateLimiter/OAuth2RateLimiter.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/Entities/OAuth2UserEntity.php';
require_once ROOT_DIR . '/sys/UserAccount.php';

class Authentication_OAuth2_Authorize extends Action {
	private $authRequest;
	private $server;

	/**
	 * Override display to use standalone layout
	 */
	function display($mainContentTemplate, $pageTitle, $sidebarTemplate = '', $translateTitle = true): void {
		global $interface;
		$interface->assign('sidebar', false);
		$interface->assign('breadcrumbs', $this->getBreadcrumbs());
		$interface->setTemplate($mainContentTemplate);
		$interface->setPageTitle($pageTitle, $translateTitle, false, true);

		$interface->display('standalone-layout.tpl');
	}

	/**
	 * @param null $method
	 * @throws Exception
	 */
	function launch($method = null): void {
		global $logger;
		if (!OAuth2RateLimiter::enforce('auth')) {
			return; // Rate limit response already sent
		}

		$logger->log("[OAuth2] OAuth2_Authorize - REQUEST METHOD: " . $_SERVER['REQUEST_METHOD'], Logger::LOG_DEBUG);
		$logger->log("[OAuth2] OAuth2_Authorize - GET params: " . json_encode($_GET), Logger::LOG_DEBUG);
		$logger->log("[OAuth2] OAuth2_Authorize - POST params: " . json_encode($_POST), Logger::LOG_DEBUG);

		OAuth2ServerConfig::generateKeyPairIfNeeded();
		$this->server = OAuth2ServerConfig::getAuthorizationServer();

		try {
			$request = $this->createServerRequest();

			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$this->handleAuthorizationApproval($request);
				return;
			}

			$this->authRequest = $this->server->validateAuthorizationRequest($request);
			if (UserAccount::isLoggedIn() && $this->completeAuthorizationIfConsentAlreadyGranted()) {
				return;
			}

			if (UserAccount::isLoggedIn()) {
				$this->displayApprovalForm();
			} else {
				$this->displayLoginForm();
			}

		} catch (OAuthServerException $exception) {
			$logger->log("[OAuth2] OAuthServerException caught: " . $exception->getErrorType() . " - " . $exception->getMessage(), Logger::LOG_ERROR);
			$logger->log("[OAuth2] HTTP Status: " . $exception->getHttpStatusCode(), Logger::LOG_ERROR);
			$this->handleOAuthError($exception);

		} catch (Exception $exception) {
			$logger->log("[OAuth2] General Exception: " . $exception->getMessage(), Logger::LOG_ERROR);
			$logger->log("[OAuth2] Trace: " . $exception->getTraceAsString(), Logger::LOG_ERROR);
			$this->handleGeneralError($exception);
		}
	}

	/**
	 * Handle the authorization approval flow
	 * @throws Exception
	 */
	private function handleAuthorizationApproval($request): void {
		global $logger;
		try {
			$this->authRequest = $this->server->validateAuthorizationRequest($request);

			if (isset($_POST['username']) && isset($_POST['password'])) {
				if ($this->handleLogin($_POST['username'], $_POST['password'])) {
					if ($this->completeAuthorizationIfConsentAlreadyGranted()) {
						return;
					}
					$this->displayApprovalForm();
					return;
				} else {
					global $interface;
					$interface->assign('loginError', 'Invalid username or password.');
					$this->displayLoginForm();
					return;
				}
			}

			if (!UserAccount::isLoggedIn()) {
				global $interface;
				$interface->assign('loginError', 'You must be logged in to authorize this request.');
				$this->displayLoginForm();
				return;
			}

			$approved = isset($_POST['approve']) && $_POST['approve'] === 'yes';

			if ($approved) {
				$user = UserAccount::getLoggedInUser();
				if (!$user) {
					throw new Exception('No authenticated user found');
				}

				$this->storeConsentForUser($user->id);

				$userEntity = new OAuth2UserEntity();
				$userEntity->setIdentifier($user->id);
				$this->authRequest->setUser($userEntity);
				$this->authRequest->setAuthorizationApproved(true);

				$response = $this->createResponse();
				$response = $this->server->completeAuthorizationRequest($this->authRequest, $response);
				$this->sendPsr7Response($response);
			} else {
				$redirectUri = $this->authRequest->getRedirectUri();
				$state = $this->authRequest->getState();

				$params = ['error' => 'access_denied'];
				if ($state) {
					$params['state'] = $state;
				}

				$separator = strpos($redirectUri, '?') === false ? '?' : '&';
				$redirectUrl = $redirectUri . $separator . http_build_query($params);

				header('Location: ' . $redirectUrl);
				exit;
			}

		} catch (OAuthServerException $exception) {
			$logger->log("[OAuth2] OAuthServerException in handleAuthorizationApproval: " . $exception->getErrorType(), Logger::LOG_ERROR);
			$this->handleOAuthError($exception);
		}
	}

	/**
	 * Display the login form
	 */
	private function displayLoginForm(): void {
		global $interface;
		global $library;

		$interface->assign('usernameLabel', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Your Name');
		$interface->assign('passwordLabel', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number');

		$interface->assign('showOAuth2LoginForm', true);
		if (isset($this->authRequest)) {
			$interface->assign('clientName', $this->authRequest->getClient()->getName());
			$interface->assign('clientId', $this->authRequest->getClient()->getIdentifier());
		}

		$this->display('../OAuth2/oauth2_login.tpl', 'Authorization Required', false, true);
	}

	/**
	 * Display the authorization approval form
	 */
	private function displayApprovalForm(): void {
		global $interface;
		$user = UserAccount::getLoggedInUser();
		if (!$user) {
			global $interface;
			$interface->assign('loginError', 'Session error. Please try logging in again.');
			$this->displayLoginForm();
			return;
		}

		$client = $this->getOAuth2ClientRecord();
		$requestedScopeIds = $this->getRequestedScopeIdentifiers();
		$scopeDescriptions = $this->getScopeDescriptions($requestedScopeIds);
		$existingConsent = $client ? $this->getExistingConsentForUser($user->id, $client) : null;
		$previouslyApprovedScopeIds = $existingConsent ? $existingConsent->getScopesArray() : [];
		$newScopeIds = array_values(array_diff($requestedScopeIds, $previouslyApprovedScopeIds));
		$removedScopeIds = array_values(array_diff($previouslyApprovedScopeIds, $requestedScopeIds));
		$consentChanged = $existingConsent !== null && (!$existingConsent->hasExactScopes($requestedScopeIds));
		$clientName = $client ? $client->getName() : $this->authRequest->getClient()->getName();
		$clientIdentifier = $client ? $client->getClientId() : $this->authRequest->getClient()->getIdentifier();

		$userInfo = [
			'id' => $user->id,
			'displayName' => $user->displayName ?? ($user->firstname . ' ' . $user->lastname),
			'username' => $user->cat_username ?? $user->ils_barcode,
		];

		$interface->assign('client', (object)[
			'id' => $clientIdentifier,
			'name' => $clientName,
		]);
		$interface->assign('scopes', $scopeDescriptions);
		$interface->assign('hasPriorConsent', $existingConsent !== null);
		$interface->assign('consentChanged', $consentChanged);
		$interface->assign('newScopes', $this->getScopeDescriptions($newScopeIds));
		$interface->assign('removedScopes', $this->getScopeDescriptions($removedScopeIds));
		$interface->assign('user', (object)$userInfo);
		$interface->assign('authorizationUrl', $_SERVER['REQUEST_URI']);

		$this->display('../OAuth2/oauth2_authorize.tpl', 'Authorize ' . $clientName, false, true);
	}

	/**
	 * Create a PSR-7 server request from the current HTTP request
	 */
	private function createServerRequest(): ServerRequestInterface {
		return ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
	}

	/**
	 * Create a PSR-7 response
	 */
	private function createResponse(): ResponseInterface {
		return new Response();
	}

	/**
	 * Send a PSR-7 response
	 */
	private function sendPsr7Response(ResponseInterface $response): void {
		http_response_code($response->getStatusCode());
		foreach ($response->getHeaders() as $name => $values) {
			foreach ($values as $value) {
				header($name . ': ' . $value, false);
			}
		}
		echo $response->getBody();
	}

	/**
	 * Get human-readable description for scope
	 */
	private function getScopeDescription(string $scope): ?string {
		$descriptions = OAuth2Client::getUserConsentScopeOptions();
		return $descriptions[$scope] ?? null;
	}

	private function getScopeDescriptions(array $scopeIds): array {
		$scopeDescriptions = [];
		foreach ($scopeIds as $scopeId) {
			$scopeDescription = $this->getScopeDescription($scopeId);
			if ($scopeDescription !== null) {
				$scopeDescriptions[$scopeId] = $scopeDescription;
			}
		}
		return $scopeDescriptions;
	}

	private function getRequestedScopeIdentifiers(): array {
		$scopeIds = [];
		foreach ($this->authRequest->getScopes() as $scope) {
			$scopeIds[] = $scope->getIdentifier();
		}
		return OAuth2UserConsent::normalizeScopes($scopeIds);
	}

	private function getOAuth2ClientRecord(): ?OAuth2Client {
		$client = new OAuth2Client();
		$client->setClientId($this->authRequest->getClient()->getIdentifier());
		$client->setIsActive(1);
		return $client->find(true) ? $client : null;
	}

	private function getExistingConsentForUser(int $userId, OAuth2Client $client): ?OAuth2UserConsent {
		$consent = new OAuth2UserConsent();
		$consent->user_id = $userId;
		$consent->oauth2_client_id = $client->id;
		return $consent->find(true) ? $consent : null;
	}

	private function completeAuthorizationIfConsentAlreadyGranted(): bool {
		global $logger;

		$user = UserAccount::getLoggedInUser();
		if (!$user) {
			return false;
		}

		$client = $this->getOAuth2ClientRecord();
		if (!$client) {
			return false;
		}

		$existingConsent = $this->getExistingConsentForUser($user->id, $client);
		if (!$existingConsent) {
			return false;
		}

		$requestedScopeIds = $this->getRequestedScopeIdentifiers();
		if (!$existingConsent->hasExactScopes($requestedScopeIds)) {
			return false;
		}

		$logger->log("[OAuth2] Reusing remembered consent for user {$user->id} and client {$client->getClientId()}", Logger::LOG_DEBUG);

		$userEntity = new OAuth2UserEntity();
		$userEntity->setIdentifier($user->id);
		$this->authRequest->setUser($userEntity);
		$this->authRequest->setAuthorizationApproved(true);

		$response = $this->createResponse();
		$response = $this->server->completeAuthorizationRequest($this->authRequest, $response);
		$this->sendPsr7Response($response);
		return true;
	}

	private function storeConsentForUser(int $userId): void {
		$client = $this->getOAuth2ClientRecord();
		if (!$client) {
			return;
		}

		$consent = $this->getExistingConsentForUser($userId, $client);
		if (!$consent) {
			$consent = new OAuth2UserConsent();
			$consent->user_id = $userId;
			$consent->oauth2_client_id = $client->id;
		}

		$consent->scopes = $this->getRequestedScopeIdentifiers();
		if (!empty($consent->id)) {
			$consent->update();
		} else {
			$consent->insert();
		}
	}

	/**
	 * Handle login attempt
	 */
	private function handleLogin(string $username, string $password): bool {
		$user = UserAccount::validateAccount($username, $password);
		if ($user && !($user instanceof AspenError)) {
			UserAccount::login();
			return true;
		}
		return false;
	}

	/**
	 * Handle OAuth server exceptions
	 */
	private function handleOAuthError(OAuthServerException $exception): void {
		global $interface;
		$interface->assign('error', $exception->getErrorType());
		$interface->assign('errorDescription', $exception->getMessage());
		http_response_code($exception->getHttpStatusCode());

		$this->display('../OAuth2/oauth2_error.tpl', 'Authorization Error', false, true);
	}

	/**
	 * Handle general exceptions
	 */
	private function handleGeneralError(Exception $exception): void {
		global $interface;
		$interface->assign('error', 'server_error');
		$interface->assign('errorDescription', 'An unexpected error occurred: ' . $exception->getMessage());
		http_response_code(500);

		$this->display('../OAuth2/oauth2_error.tpl', 'Server Error', false, true);
	}

	function getBreadcrumbs(): array {
		return [];
	}
}
