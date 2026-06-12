<?php

class ExpirationInformation {
	private const SECONDS_PER_DAY = 86400;

	public int $expirationDate = 0; //Expiration Date in time since epoch
	public int $renewalWindowDays = 30;

	private ?bool $_expired = null;
	private ?bool $_expireClose = null;

	private function loadExpirationInfo() : void {
		if ($this->expirationDate <= 0) {
			$this->_expired = false;
			$this->_expireClose = false;
			return;
		}

		$timeToExpire = $this->expirationDate - time();
		$this->_expired = $timeToExpire <= 0;
		$this->_expireClose = $timeToExpire <= $this->renewalWindowDays * self::SECONDS_PER_DAY;
	}

	public function isExpired() : bool {
		if ($this->_expired === null) {
			$this->loadExpirationInfo();
		}
		return $this->_expired;
	}

	public function isExpirationClose() : bool {
		if ($this->_expireClose === null) {
			$this->loadExpirationInfo();
		}
		return $this->_expireClose;
	}

	public function expiresOn() : string {
		if (empty($this->expirationDate)) {
			return '';
		}else{
			return date('M j, Y', $this->expirationDate);
		}
	}
}