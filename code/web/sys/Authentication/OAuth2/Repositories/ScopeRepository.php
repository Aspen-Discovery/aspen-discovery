<?php

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

require_once ROOT_DIR . '/sys/Authentication/OAuth2/Entities/OAuth2ScopeEntity.php';

class ScopeRepository implements ScopeRepositoryInterface {

	public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface {
		global $logger;
		$logger->log("[OAuth2] ScopeRepository::getScopeEntityByIdentifier() - Looking up scope: " . $identifier, Logger::LOG_DEBUG);
		
		$validScopes = OAuth2Client::getScopeOptions();
		$validClaims = OpenIDClient::getClaimsOptions();

		if (array_key_exists($identifier, $validScopes)) {
			$logger->log("[OAuth2] ScopeRepository::getScopeEntityByIdentifier() - Found scope in validScopes: " . $identifier, Logger::LOG_DEBUG);
			$scope = new OAuth2ScopeEntity();
			$scope->setIdentifier($identifier);
			return $scope;
		}

		if (array_key_exists($identifier, $validClaims) || $identifier === 'openid') {
			$logger->log("[OAuth2] ScopeRepository::getScopeEntityByIdentifier() - Found scope in validClaims or openid: " . $identifier, Logger::LOG_DEBUG);
			$scope = new OAuth2ScopeEntity();
			$scope->setIdentifier($identifier);
			return $scope;
		}

		$logger->log("[OAuth2] ScopeRepository::getScopeEntityByIdentifier() - Scope not found: " . $identifier, Logger::LOG_WARNING);
		return null;
	}

	public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null, ?string $authCodeId = null): array {
		global $logger;
		$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Finalizing scopes for client: " . $clientEntity->getIdentifier() . ", grant type: " . $grantType, Logger::LOG_DEBUG);
		
		require_once ROOT_DIR . '/sys/Authentication/OAuth2/OAuth2Client.php';
		$client = new OAuth2Client();
		$client->setClientId($clientEntity->getIdentifier());
		$client->setIsActive(1);

		if (!$client->find(true)) {
			$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Client not found: " . $clientEntity->getIdentifier(), Logger::LOG_WARNING);
			return [];
		}

		$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Client found, processing scopes", Logger::LOG_DEBUG);

		$finalScopes = [];
		$clientType = '';
		$client = new OAuth2Client();
		$client->setClientId($clientEntity->getIdentifier());
		$client->setIsActive(1);
		if ($client->find(true)) {
			$clientType = 'oauth2';
		} else {
			$client = null;
		}

		if ($client === null) {
			$client = new OpenIDClient();
			$client->setClientId($clientEntity->getIdentifier());
			$client->setIsActive(1);
			if ($client->find(true)) {
				$clientType = 'openid';
			} else {
				$client = null;
			}
		}

		if ($client === null) {
			$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - CLIENT NOT FOUND", Logger::LOG_DEBUG);
			return [];
		}

		$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - FOUND {$clientType} client: " . $client->getName(), Logger::LOG_DEBUG);

		if ($clientType === 'openid') {
			$allowed = $client->getClaimsArray();
			$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Allowed claims: " . implode(', ', $allowed), Logger::LOG_DEBUG);
		} else {
			$allowed = $client->getScopesArray();
			$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Allowed scopes: " . implode(', ', $allowed), Logger::LOG_DEBUG);
		}

		if (!empty($allowed)) {
			$firstItem = reset($allowed);
			if (!is_string($firstItem) || !str_contains($firstItem, ':')) {
				$allowed = array_keys($allowed);
			}
		}

		$requestedScopesList = [];
		foreach ($scopes as $scope) {
			$requestedScopesList[] = $scope->getIdentifier();
		}
		$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Requested scopes: " . implode(', ', $requestedScopesList), Logger::LOG_DEBUG);

		foreach ($scopes as $scope) {
			$scopeIdentifier = $scope->getIdentifier();
			if (in_array($scopeIdentifier, $allowed)) {
				$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Scope approved (in allowedScopes): " . $scopeIdentifier, Logger::LOG_DEBUG);
				$finalScopes[] = $scope;
			} elseif ($scopeIdentifier === 'openid') {
				$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Scope approved (openid): " . $scopeIdentifier, Logger::LOG_DEBUG);
				$finalScopes[] = $scope;
			} elseif (in_array($scopeIdentifier, $allowed)) {
				$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Scope approved (in allowedClaims): " . $scopeIdentifier, Logger::LOG_DEBUG);
				$finalScopes[] = $scope;
			} else {
				$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Scope rejected: " . $scopeIdentifier, Logger::LOG_WARNING);
			}
		}

		$finalScopesList = [];
		foreach ($finalScopes as $scope) {
			$finalScopesList[] = $scope->getIdentifier();
		}
		$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Final approved scopes: " . implode(', ', $finalScopesList), Logger::LOG_DEBUG);

		if (!empty($requestedScopesList) && empty($finalScopes)) {
			$logger->log("[OAuth2] ScopeRepository::finalizeScopes() - Client requested scopes but none were approved. Rejecting token request.", Logger::LOG_WARNING);
			throw new OAuthServerException('The requested scopes are not valid for this client', 5, 'invalid_scope');
		}

		return $finalScopes;
	}
}