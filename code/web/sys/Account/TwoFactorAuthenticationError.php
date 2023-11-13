<?php

class TwoFactorAuthenticationError extends AspenError {
	public $userId;
	public $twoFactorStartDate;
	public const MUST_ENROLL = 1;
	public const MUST_COMPLETE_AUTHENTICATION = 2;
	/**
	 * @var $twoFactorAuthStatus int
	 * 1 = must enroll
	 * 2 = must complete authentication
	 */
	public $twoFactorAuthStatus;

	public function __construct($userId, $twoFactorAuthStatus, $message) {
		parent::__construct($message, null);
		$this->userId = $userId;
		$this->twoFactorAuthStatus = $twoFactorAuthStatus;
		$this->twoFactorStartDate = time();
	}
}