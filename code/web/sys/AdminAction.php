<?php

class AdminAction {
	public $label;
	public $description;
	public $link;
	public $subActions = [];

	public function __construct($label, $description, $link) {
		$this->label = $label;
		$this->description = $description;
		$this->link = $link;
	}

	/**
	 * @param AdminAction $adminAction
	 * @param string|string[] $requiredPermission
	 */
	public function addSubAction($adminAction, $requiredPermission) {
		if (UserAccount::userHasPermission($requiredPermission)) {
			$this->subActions[] = $adminAction;
		}
	}

	/** @noinspection PhpUnused */
	public function hasSubActions() {
		return count($this->subActions) > 0;
	}
}