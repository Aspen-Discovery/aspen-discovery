<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class Session extends DataObject {
	public $__table = 'session';
	protected $id;
	protected $session_id;
	protected $data;
	protected $last_used;
	protected $created;
	protected $remember_me;

	function getNumericColumnNames(): array {
		return [
			'remember_me',
			'last_used',
		];
	}

	function update($context = '') {
		if ($this->data == null) {
			$this->data = '';
		}
		$ret = parent::update();
		global $interface;
		if (isset($interface)) {
			$interface->assign('session', $this->session_id . ', remember me ' . $this->remember_me);
		}
		return $ret;
	}

	function insert($context = '') {
		if ($this->data == null) {
			$this->data = '';
		}
		return parent::insert();
	}

	public function getTimeUntilSessionExpiration() : int {
		if (UserAccount::isUserMasquerading()) {
			$sessionLifespan = SessionInterface::$masqueradeLifeTime;
		} else {
			if ($this->remember_me == '1') {
				$sessionLifespan = SessionInterface::$rememberMeLifetime;
			} else {
				$sessionLifespan = SessionInterface::$lifetime;
			}
		}

		return $sessionLifespan * 1000;
	}
}
