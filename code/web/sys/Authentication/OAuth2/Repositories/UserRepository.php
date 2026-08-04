<?php

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

require_once ROOT_DIR . '/sys/Authentication/OAuth2/Entities/OAuth2UserEntity.php';

class UserRepository implements UserRepositoryInterface {

	public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity): ?\League\OAuth2\Server\Entities\UserEntityInterface {
		global $logger;
		$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Attempting to authenticate user: " . $username . ", grant type: " . $grantType . ", client: " . $clientEntity->getIdentifier(), Logger::LOG_DEBUG);
		
		require_once ROOT_DIR . '/CatalogFactory.php';

		global $library;
		$driversToTest = UserAccount::getAccountProfiles();
		$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Found " . count($driversToTest) . " account profiles to test", Logger::LOG_DEBUG);
		$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Library account profile ID: " . $library->accountProfileId, Logger::LOG_DEBUG);

		foreach ($driversToTest as $driverName => $additionalInfo) {
			/** @var AccountProfile $tmpAccountProfile * */
			$tmpAccountProfile = $additionalInfo['accountProfile'];
			$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Testing driver: " . $driverName . " (ID: " . $tmpAccountProfile->id . ")", Logger::LOG_DEBUG);

			if ($library->accountProfileId == $tmpAccountProfile->id) {
				$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Account profile matches library profile, testing authentication method: " . $additionalInfo['authenticationMethod'], Logger::LOG_DEBUG);
				
				try {
					$authN = AuthenticationFactory::initAuthentication($additionalInfo['authenticationMethod'], $additionalInfo);
					$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Authentication object initialized for method: " . $additionalInfo['authenticationMethod'], Logger::LOG_DEBUG);
				} catch (UnknownAuthenticationMethodException $e) {
					$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Unknown authentication method: " . $additionalInfo['authenticationMethod'] . ", skipping", Logger::LOG_WARNING);
					continue;
				}

				$parentAccount = null;
				$validatedViaSSO = false;
				$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Validating account credentials for user: " . $username, Logger::LOG_DEBUG);
				$validatedUser = $authN->validateAccount($username, $password, $additionalInfo['accountProfile'], $parentAccount, $validatedViaSSO);

				if ($validatedUser && !($validatedUser instanceof AspenError)) {
					$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - User successfully validated: " . $username . " (ID: " . $validatedUser->id . ")", Logger::LOG_DEBUG);
					$user = new OAuth2UserEntity();
					$user->setIdentifier($validatedUser->id);
					return $user;
				} else {
					if ($validatedUser instanceof AspenError) {
						$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Authentication error for user: " . $username . ", error: " . $validatedUser->getMessage(), Logger::LOG_WARNING);
					} else {
						$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - Validation failed for user: " . $username, Logger::LOG_DEBUG);
					}
				}
			}
		}

		$logger->log("[OAuth2] UserRepository::getUserEntityByUserCredentials() - No valid authentication profile found for user: " . $username, Logger::LOG_WARNING);
		return null;
	}
}

