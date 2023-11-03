<?php

require_once ROOT_DIR . '/sys/AdminSection.php';

class AdminSection {
	public $label;
	public $actions = [];

	public function __construct($label) {
		$this->label = $label;
	}

	/**
	 * @param AdminAction $adminAction
	 * @param boolean|string|string[] $requiredPermission
	 * @return boolean
	 */
	public function addAction($adminAction, $requiredPermission) {
		if ($requiredPermission === true || UserAccount::userHasPermission($requiredPermission)) {
			$this->actions[] = $adminAction;
			return true;
		} else {
			return false;
		}
	}

	/** @noinspection PhpUnused */
	public function hasActions() {
		return count($this->actions) > 0;
	}

	private $_translatedLabel = null;
	public function getTranslatedLabel() {
		if ($this->_translatedLabel == null) {
			$this->_translatedLabel = translate(['text' => $this->label, 'isAdminFacing' => true]);
		}
		return $this->_translatedLabel;
	}
}