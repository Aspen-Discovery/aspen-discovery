<?php


class StaffDirectory extends Action
{
	function launch()
	{
		global $interface;

		require_once ROOT_DIR . '/sys/WebBuilder/StaffMember.php';
		$staffMember = new StaffMember();
		$staffMember->find();
		$staffMembers = [];
		while ($staffMember->fetch()){
			$staffMembers[] = clone $staffMember;
		}

		$interface->assign('staffMembers', $staffMembers);

		$sidebar = null;
		$this->display('staffDirectory.tpl', 'Staff Directory', $sidebar, false);
	}
}