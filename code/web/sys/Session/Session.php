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

	/**
	 * @return mixed
	 */
	public function getSessionId() : mixed {
		return $this->session_id;
	}

	/**
	 * @param mixed $session_id
	 */
	public function setSessionId($session_id): void {
		$this->session_id = $session_id;
	}

	/**
	 * @return mixed
	 */
	public function getRememberMe() {
		return $this->remember_me;
	}

	/**
	 * @param mixed $remember_me
	 */
	public function setRememberMe($remember_me): void {
		$this->remember_me = $remember_me;
	}

	/**
	 * @return mixed
	 */
	public function getLastUsed() {
		return $this->last_used;
	}

	/**
	 * @param mixed $last_used
	 */
	public function setLastUsed($last_used): void {
		$this->last_used = $last_used;
	}


	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id): void {
		$this->id = $id;
	}

	/**
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data): void {
		$this->data = $data;
	}

	/**
	 * @return mixed
	 */
	public function getCreated() {
		return $this->created;
	}

	/**
	 * @param mixed $created
	 */
	public function setCreated($created): void {
		$this->created = $created;
	}
}
