<?php

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

/**
 * OAuth2 Refresh Token Entity implementation
 */
class OAuth2RefreshTokenEntity implements RefreshTokenEntityInterface {
	/**
	 * Get the token's identifier.
	 *
	 * @return non-empty-string
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}

	/**
	 * Set the token's identifier.
	 *
	 * @param non-empty-string $identifier
	 */
	public function setIdentifier(string $identifier): void {
		$this->identifier = $identifier;
	}

	/**
	 * Get the token's expiry date time.
	 */
	public function getExpiryDateTime(): DateTimeImmutable {
		return $this->expiryDateTime;
	}

	/**
	 * Set the date time when the token expires.
	 */
	public function setExpiryDateTime(DateTimeImmutable $dateTime): void {
		$this->expiryDateTime = $dateTime;
	}

	/**
	 * Set the access token that the refresh token was associated with.
	 */
	public function setAccessToken(\League\OAuth2\Server\Entities\AccessTokenEntityInterface $accessToken): void {
		$this->accessToken = $accessToken;
	}

	/**
	 * Get the access token that the refresh token was originally associated with.
	 */
	public function getAccessToken(): \League\OAuth2\Server\Entities\AccessTokenEntityInterface {
		return $this->accessToken;
	}
}
