<?php

use League\OAuth2\Server\Entities\UserEntityInterface;

/**
 * OAuth2 User Entity implementation
 * Since League OAuth2 Server doesn't provide a concrete UserEntity class
 */
class OAuth2UserEntity implements UserEntityInterface {
	private $identifier;

	public function getIdentifier(): string {
		return $this->identifier;
	}

	public function setIdentifier($identifier): void {
		$this->identifier = $identifier;
	}
}
