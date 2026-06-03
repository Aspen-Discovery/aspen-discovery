<?php

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

require_once ROOT_DIR . '/sys/Authentication/OAuth2/Entities/OAuth2AuthCodeEntity.php';
require_once ROOT_DIR . '/sys/Authentication/OAuth2/Tokens/OAuth2AuthCode.php';

class AuthCodeRepository implements AuthCodeRepositoryInterface {

	public function getNewAuthCode(): AuthCodeEntityInterface {
		global $logger;
		$logger->log("[OAuth2] AuthCodeRepository::getNewAuthCode() - Creating new auth code entity", Logger::LOG_DEBUG);
		return new OAuth2AuthCodeEntity();
	}

	public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void {
		global $logger;
		$logger->log("[OAuth2] AuthCodeRepository::persistNewAuthCode() - Persisting auth code: " . $authCodeEntity->getIdentifier(), Logger::LOG_DEBUG);
		
		$authCode = new OAuth2AuthCode();
		$authCode->code_id = $authCodeEntity->getIdentifier();
		$authCode->user_id = $authCodeEntity->getUserIdentifier();
		$authCode->client_id = $authCodeEntity->getClient()->getIdentifier();
		
		$logger->log("[OAuth2] AuthCodeRepository::persistNewAuthCode() - Code ID: " . $authCode->code_id . ", User ID: " . $authCode->user_id . ", Client ID: " . $authCode->client_id, Logger::LOG_DEBUG);

		$scopes = [];
		foreach ($authCodeEntity->getScopes() as $scope) {
			$scopes[] = $scope->getIdentifier();
		}
		$authCode->scopes = implode(' ', $scopes);
		$authCode->redirect_uri = $authCodeEntity->getRedirectUri();
		
		$logger->log("[OAuth2] AuthCodeRepository::persistNewAuthCode() - Scopes: " . $authCode->scopes . ", Redirect URI: " . $authCode->redirect_uri, Logger::LOG_DEBUG);

		// Handle PKCE (Proof Key for Code Exchange) if supported
		if (method_exists($authCodeEntity, 'getCodeChallenge')) {
			$authCode->code_challenge = $authCodeEntity->getCodeChallenge();
		} else {
			$authCode->code_challenge = null;
		}

		if (method_exists($authCodeEntity, 'getCodeChallengeMethod')) {
			$authCode->code_challenge_method = $authCodeEntity->getCodeChallengeMethod();
		} else {
			$authCode->code_challenge = null;
		}
		
		if (!empty($authCode->code_challenge)) {
			$logger->log("[OAuth2] AuthCodeRepository::persistNewAuthCode() - PKCE Challenge Method: " . ($authCode->code_challenge_method ?? 'none'), Logger::LOG_DEBUG);
		}

		$authCode->expires_at = $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s');
		$authCode->revoked = 0;
		$authCode->insert();
		
		$logger->log("[OAuth2] AuthCodeRepository::persistNewAuthCode() - Auth code persisted. Expires at: " . $authCode->expires_at, Logger::LOG_DEBUG);
	}

	public function revokeAuthCode($codeId): void {
		global $logger;
		$logger->log("[OAuth2] AuthCodeRepository::revokeAuthCode() - Revoking auth code: " . $codeId, Logger::LOG_DEBUG);
		
		$authCode = new OAuth2AuthCode();
		$authCode->code_id = $codeId;
		if ($authCode->find(true)) {
			$authCode->revoked = 1;
			$authCode->update();
			$logger->log("[OAuth2] AuthCodeRepository::revokeAuthCode() - Auth code successfully revoked: " . $codeId, Logger::LOG_DEBUG);
		} else {
			$logger->log("[OAuth2] AuthCodeRepository::revokeAuthCode() - Auth code not found: " . $codeId, Logger::LOG_WARNING);
		}
	}

	public function isAuthCodeRevoked($codeId): bool {
		global $logger;
		$logger->log("[OAuth2] AuthCodeRepository::isAuthCodeRevoked() - Checking revocation status for code: " . $codeId, Logger::LOG_DEBUG);
		
		$authCode = new OAuth2AuthCode();
		$authCode->code_id = $codeId;
		if ($authCode->find(true)) {
			$isRevoked = $authCode->isRevoked();
			$logger->log("[OAuth2] AuthCodeRepository::isAuthCodeRevoked() - Code revoked status: " . ($isRevoked ? 'true' : 'false'), Logger::LOG_DEBUG);
			return $isRevoked;
		}
		
		$logger->log("[OAuth2] AuthCodeRepository::isAuthCodeRevoked() - Auth code not found, returning revoked: true", Logger::LOG_WARNING);
		return true;
	}
}
