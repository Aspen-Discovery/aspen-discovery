<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class PType extends DataObject
{
	public $__table = 'ptype';   // table name
	public $id;
	public $pType;                //varchar(45)
	public $description;
	public $maxHolds;            //int(11)
	public $assignedRoleId;
	public $restrictMasquerade;
	public $isStaff;
	public $twoFactorAuthSettingId;

	public function getNumericColumnNames(): array
	{
		return ['isStaff', 'maxHolds', 'restrictMasquerade'];
	}

	static function getObjectStructure() : array
	{
		$roles = [];
		$roles[-1] = 'None';
		$role = new Role();
		$role->orderBy('name');
		$role->find();
		while ($role->fetch()){
			$roles[$role->roleId] = $role->name;
		}
		$structure = array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id of the p-type within the database', 'hideInLists' => false),
			'pType' => array('property' => 'pType', 'type' => 'text', 'label' => 'P-Type', 'description' => 'The P-Type for the patron'),
			'description' => array('property' => 'description', 'type' => 'text', 'label' => 'Description', 'description' => 'A description for the Patron Type', 'maxLength' => 100),
			'maxHolds' => array('property' => 'maxHolds', 'type' => 'integer', 'label' => 'Max Holds', 'description' => 'The maximum holds that a patron can have.', 'default' => 300),
			'assignedRoleId' => array('property' => 'assignedRoleId', 'type' => 'enum', 'values' => $roles, 'label' => 'Assigned Role', 'description' => 'Automatically assign a role to a user based on patron type', 'default' => '-1'),
			'isStaff' => array('property' => 'isStaff', 'type' => 'checkbox', 'label' => 'Treat as staff', 'description' => 'Treat the user as staff, but without specific permissions in Aspen','default' => 0),
			'restrictMasquerade' => array('property' => 'restrictMasquerade', 'type' => 'checkbox', 'label' => 'Restrict masquerade from accessing patrons of this type', 'description' => 'Users without the ability to masquerade as restricted patrons will not be able to masquerade as this type','default' => 0),
			'twoFactorAuthSettingId' => array('property' => 'twoFactorAuthSettingId', 'type' => 'text', 'label' => 'Two-factor authentication setting', 'description' => 'The unique id of the two-factor authentication setting tied to this patron type','readonly'=>true)
		);
		if (!UserAccount::userHasPermission('Administer Permissions')){
			unset($structure['assignedRoleId']);
		}
		return $structure;
	}

	static function getPatronTypeList(): array
	{
		$patronType = new pType();
		$patronType->orderBy('pType');
		$patronType->find();
		$patronTypeList = [];
		while ($patronType->fetch()) {
			$patronTypeLabel = $patronType->pType;
			if (!empty($patronType->description)){
				$patronTypeLabel .= ' - ' . $patronType->description;
			}
			$patronTypeList[$patronType->id] = $patronTypeLabel;
		}
		return $patronTypeList;
	}
}