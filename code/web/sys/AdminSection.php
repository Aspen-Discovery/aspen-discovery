<?php

require_once ROOT_DIR . '/sys/AdminSection.php';
class AdminSection
{
	public $label;
	public $actions = [];

	public function __construct($label)
	{
		$this->label = $label;
	}

	/**
	 * @param AdminAction $adminAction
	 * @param string|string[] $requiredPermission
	 * @return boolean
	 */
	public function addAction($adminAction, $requiredPermission){
		$user = UserAccount::getActiveUserObj();
		if ($user->hasPermission($requiredPermission)){
			$this->actions[] = $adminAction;
			return true;
		}else{
			return false;
		}
	}

	/** @noinspection PhpUnused */
	public function hasActions(){
		return count($this->actions) > 0;
	}
}