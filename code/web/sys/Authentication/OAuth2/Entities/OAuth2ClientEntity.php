<?php

use League\OAuth2\Server\Entities\ClientEntityInterface;

/**
 * OAuth2 Client Entity implementation
 */
class OAuth2ClientEntity implements ClientEntityInterface {

	public function setName($name): void {
		$this->name = $name;
	}

	public function setRedirectUri($uri): void {
		$this->redirectUri = $uri;
	}

	public function setIsConfidential($isConfidential = true): void {
		$this->isConfidential = $isConfidential;
	}

	public function setIdentifier(string $getClientId): void {
		$this->identifier = $getClientId;
	}

	/**
	 * Get the client's identifier.
	 *
	 * @return non-empty-string
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}

	/**
	 * Get the client's name.
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Returns the registered redirect URI (as a string). Alternatively return
	 * an indexed array of redirect URIs.
	 *
	 * @return string|string[]
	 */
	public function getRedirectUri(): string|array {
		return $this->redirectUri;
	}

	/**
	 * Returns true if the client is confidential.
	 */
	public function isConfidential(): bool {
		return $this->isConfidential;
	}
}
