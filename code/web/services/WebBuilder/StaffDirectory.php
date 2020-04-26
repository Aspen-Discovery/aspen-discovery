<?php


class StaffDirectory extends Action
{
	function launch()
	{
		global $interface;

		require_once ROOT_DIR . '/sys/WebBuilder/StaffMember.php';
		$staffMember = new StaffMember();
		$staffMember->orderBy('name');
		$staffMember->find();
		$staffMembers = [];
		$hasPhotos = false;
		while ($staffMember->fetch()){
			$staffMembers[] = clone $staffMember;
			if (!empty($staffMember->photo)){
				$hasPhotos = true;
			}
		}

		$interface->assign('hasPhotos', $hasPhotos);
		$interface->assign('staffMembers', $staffMembers);

		$this->display('staffDirectory.tpl', 'Staff Directory');
	}
}