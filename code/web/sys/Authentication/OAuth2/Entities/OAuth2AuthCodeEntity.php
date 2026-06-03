<?php

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * OAuth2 Authorization Code Entity
 * Implements the League OAuth2 Server AuthCodeEntityInterface
 */
class OAuth2AuthCodeEntity implements AuthCodeEntityInterface {

	protected ?string $codeChallenge = null;
	protected ?string $codeChallengeMethod = null;

	/**
	 * Get the code challenge (PKCE)
	 */
	public function getCodeChallenge(): ?string {
		return $this->codeChallenge;
	}

	/**
	 * Set the code challenge (PKCE)
	 */
	public function setCodeChallenge(?string $codeChallenge): void {
		$this->codeChallenge = $codeChallenge;
	}

	/**
	 * Get the code challenge method (PKCE)
	 */
	public function getCodeChallengeMethod(): ?string {
		return $this->codeChallengeMethod;
	}

	/**
	 * Set the code challenge method (PKCE)
	 */
	public function setCodeChallengeMethod(?string $codeChallengeMethod): void {
		$this->codeChallengeMethod = $codeChallengeMethod;
	}

	public function getRedirectUri(): string|null {
		return $this->redirecturi;
	}

	public function setRedirectUri(string $uri): void {
		$this->redirecturi = $uri;
	}

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
	 * Set the identifier of the user associated with the token.
	 *
	 * @param non-empty-string $identifier
	 */
	public function setUserIdentifier(string $identifier): void {
		$this->userIdentifier = $identifier;
	}

	/**
	 * Get the token user's identifier.
	 *
	 * @return non-empty-string|null
	 */
	public function getUserIdentifier(): string|null {
		return $this->userIdentifier;
	}

	/**
	 * Get the client that the token was issued to.
	 */
	public function getClient(): ClientEntityInterface {
		return $this->client;
	}

	/**
	 * Set the client that the token was issued to.
	 */
	public function setClient(ClientEntityInterface $client): void {
		$this->client = $client;
	}

	/**
	 * Associate a scope with the token.
	 */
	public function addScope(ScopeEntityInterface $scope): void {
		$this->scopes[] = $scope;
	}

	/**
	 * Return an array of scopes associated with the token.
	 *
	 * @return array
	 */
	public function getScopes(): array {
		return $this->scopes;
	}
}
