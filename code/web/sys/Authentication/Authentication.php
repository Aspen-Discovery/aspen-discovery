<?php

interface Authentication {
	public function __construct($additionalInfo);

	/**
	 * Authenticate the user in the system
	 *
	 * @param $validatedViaSSO boolean
	 *
	 * @return mixed
	 */
	public function authenticate($validatedViaSSO);

	/**
	 * @param $username       string
	 * @param $password       string
	 * @param $parentAccount  User|null
	 * @param $validatedViaSSO boolean
	 * @return bool|AspenError|string
	 */
	public function validateAccount($username, $password, $parentAccount, $validatedViaSSO);
}