<?php

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

require_once ROOT_DIR . '/sys/Authentication/OAuth2/Tokens/OAuth2RefreshToken.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/Entities/OAuth2RefreshTokenEntity.php';

class RefreshTokenRepository implements RefreshTokenRepositoryInterface {

	public function getNewRefreshToken(): RefreshTokenEntityInterface {
		global $logger;
		$logger->log("[OAuth2] RefreshTokenRepository::getNewRefreshToken() - Creating new refresh token entity", Logger::LOG_DEBUG);
		return new OAuth2RefreshTokenEntity();
	}

	public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void {
		global $logger;
		$logger->log("[OAuth2] RefreshTokenRepository::persistNewRefreshToken() - Persisting refresh token", Logger::LOG_DEBUG);
		
		$refreshToken = new OAuth2RefreshToken();
		$refreshToken->token_id = $refreshTokenEntity->getIdentifier();
		$refreshToken->access_token_id = $refreshTokenEntity->getAccessToken()->getIdentifier();
		$refreshToken->expires_at = $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s');
		$refreshToken->revoked = 0;
		
		$logger->log("[OAuth2] RefreshTokenRepository::persistNewRefreshToken() - Token ID: " . $refreshToken->token_id . ", Access Token ID: " . $refreshToken->access_token_id, Logger::LOG_DEBUG);
		$logger->log("[OAuth2] RefreshTokenRepository::persistNewRefreshToken() - Expires at: " . $refreshToken->expires_at, Logger::LOG_DEBUG);
		
		$refreshToken->insert();
		
		$logger->log("[OAuth2] RefreshTokenRepository::persistNewRefreshToken() - Refresh token successfully persisted", Logger::LOG_DEBUG);
	}

	public function revokeRefreshToken($tokenId): void {
		global $logger;
		$logger->log("[OAuth2] RefreshTokenRepository::revokeRefreshToken() - Revoking refresh token: " . $tokenId, Logger::LOG_DEBUG);
		
		$refreshToken = new OAuth2RefreshToken();
		$refreshToken->token_id = $tokenId;
		if ($refreshToken->find(true)) {
			$refreshToken->revoked = 1;
			$refreshToken->update();
			$logger->log("[OAuth2] RefreshTokenRepository::revokeRefreshToken() - Refresh token successfully revoked: " . $tokenId, Logger::LOG_DEBUG);
		} else {
			$logger->log("[OAuth2] RefreshTokenRepository::revokeRefreshToken() - Refresh token not found: " . $tokenId, Logger::LOG_WARNING);
		}
	}

	public function isRefreshTokenRevoked($tokenId): bool {
		global $logger;
		$logger->log("[OAuth2] RefreshTokenRepository::isRefreshTokenRevoked() - Checking revocation status for token: " . $tokenId, Logger::LOG_DEBUG);
		
		$refreshToken = new OAuth2RefreshToken();
		$refreshToken->token_id = $tokenId;
		if ($refreshToken->find(true)) {
			$isRevoked = $refreshToken->isRevoked();
			$logger->log("[OAuth2] RefreshTokenRepository::isRefreshTokenRevoked() - Token revoked status: " . ($isRevoked ? 'true' : 'false'), Logger::LOG_DEBUG);
			return $isRevoked;
		}
		
		$logger->log("[OAuth2] RefreshTokenRepository::isRefreshTokenRevoked() - Refresh token not found, returning revoked: true", Logger::LOG_WARNING);
		return true;
	}
}
