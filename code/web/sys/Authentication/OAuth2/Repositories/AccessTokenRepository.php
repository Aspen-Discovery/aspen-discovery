<?php

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

require_once ROOT_DIR . '/sys/Authentication/OAuth2/Tokens/OAuth2AccessToken.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/Entities/OAuth2AccessTokenEntity.php';

class AccessTokenRepository implements AccessTokenRepositoryInterface {


	public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface {
		global $logger;
		$logger->log("[OAuth2] AccessTokenRepository::getNewToken() - Creating new access token for client: " . $clientEntity->getIdentifier() . ", user: " . ($userIdentifier ?? 'anonymous'), Logger::LOG_DEBUG);
		
		$accessToken = new OAuth2AccessTokenEntity();
		$accessToken->setClient($clientEntity);

		$scopesList = [];
		foreach ($scopes as $scope) {
			$accessToken->addScope($scope);
			$scopesList[] = $scope->getIdentifier();
		}
		
		$logger->log("[OAuth2] AccessTokenRepository::getNewToken() - Scopes: " . implode(', ', $scopesList), Logger::LOG_DEBUG);

		$accessToken->setUserIdentifier($userIdentifier ?? '');

		return $accessToken;
	}

	public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void {
		global $logger;
		$logger->log("[OAuth2] AccessTokenRepository::persistNewAccessToken() - Persisting access token", Logger::LOG_DEBUG);
		
		$token = new OAuth2AccessToken();
		$token->setTokenId($accessTokenEntity->getIdentifier());
		$token->setUserId($accessTokenEntity->getUserIdentifier());
		$token->setClientId($accessTokenEntity->getClient()->getIdentifier());

		$scopes = [];
		foreach ($accessTokenEntity->getScopes() as $scope) {
			$scopes[] = $scope->getIdentifier();
		}
		$token->setScopes(implode(',', $scopes));
		$token->setExpiresAt($accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'));
		$token->setRevoked(0);

		$logger->log("[OAuth2] AccessTokenRepository::persistNewAccessToken() - Token ID: " . $token->getTokenId() . ", User ID: " . $token->getUserId() . ", Client ID: " . $token->getClientId(), Logger::LOG_DEBUG);
		$logger->log("[OAuth2] AccessTokenRepository::persistNewAccessToken() - Scopes: " . $token->getScopes() . ", Expires at: " . $token->getExpiration(), Logger::LOG_DEBUG);

		$token->insert();
		
		$logger->log("[OAuth2] AccessTokenRepository::persistNewAccessToken() - Access token successfully persisted", Logger::LOG_DEBUG);
	}

	public function revokeAccessToken($tokenId): void {
		global $logger;
		$logger->log("[OAuth2] AccessTokenRepository::revokeAccessToken() - Revoking access token: " . $tokenId, Logger::LOG_DEBUG);
		
		$token = new OAuth2AccessToken();
		$token->setTokenId($tokenId);
		if ($token->find(true)) {
			$token->setRevoked(1);
			$token->update();
			$logger->log("[OAuth2] AccessTokenRepository::revokeAccessToken() - Access token successfully revoked: " . $tokenId, Logger::LOG_DEBUG);
		} else {
			$logger->log("[OAuth2] AccessTokenRepository::revokeAccessToken() - Access token not found: " . $tokenId, Logger::LOG_WARNING);
		}
	}

	public function isAccessTokenRevoked($tokenId): bool {
		global $logger;
		$logger->log("[OAuth2] AccessTokenRepository::isAccessTokenRevoked() - Checking revocation status for token: " . $tokenId, Logger::LOG_DEBUG);
		
		$token = new OAuth2AccessToken();
		$token->setTokenId($tokenId);
		if ($token->find(true)) {
			$isRevoked = $token->isRevoked();
			$logger->log("[OAuth2] AccessTokenRepository::isAccessTokenRevoked() - Token revoked status: " . ($isRevoked ? 'true' : 'false'), Logger::LOG_DEBUG);
			return $isRevoked;
		}
		
		$logger->log("[OAuth2] AccessTokenRepository::isAccessTokenRevoked() - Access token not found, returning revoked: false", Logger::LOG_WARNING);
		return false;
	}
}