<?php

use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * Simple Scope Entity implementation for OAuth2 Server
 * Since League OAuth2 Server doesn't provide a concrete ScopeEntity class
 */
class OAuth2ScopeEntity implements ScopeEntityInterface {
	private string $identifier;

	public function getIdentifier(): string {
		return $this->identifier;
	}

	public function setIdentifier($identifier): void {
		$this->identifier = $identifier;
	}

	public function jsonSerialize(): string {
		return $this->getIdentifier();
	}
}
