<?php

interface Authentication {
	public function __construct($additionalInfo);

	/**
	 * Authenticate the user in the system
	 * @return mixed
	 */
	public function authenticate(bool $validatedViaSSO, ?AccountProfile $accountProfile) : mixed;

	/**
	 * @param $username       string
	 * @param $password       string
	 * @param $parentAccount  User|null
	 * @param $validatedViaSSO boolean
	 * @param $accountProfile AccountProfile
	 *
	 * @return bool|AspenError|string
	 */
	public function validateAccount($username, $password, $accountProfile, $parentAccount, $validatedViaSSO);
}