<?php


class PinResetToken extends DataObject {
	public $__table = 'pin_reset_token';
	public $id;
	public $userId;
	public $token;
	public $dateIssued;

	public function generateToken() {
		/** @noinspection SpellCheckingInspection */
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$tokenString = '';
		for ($i = 0; $i < 12; $i++) {
			$tokenString .= $characters[rand(0, strlen($characters) - 1)];
		}
		$this->token = $tokenString;
	}
}