<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class PType extends DataObject {
	public $__table = 'ptype';   // table name
	public $id;
	public $pType;                //varchar(45)
	public $description;
	public $maxHolds;            //int(11)
	public $assignedRoleId;
	public $restrictMasquerade;
	public $isStaff;
	public $twoFactorAuthSettingId;
	public $vdxClientCategory;
	public $accountLinkingSetting;
	public $accountLinkRemoveSetting;
	public $enableReadingHistory;
	public $canSuggestMaterials;

	public function getNumericColumnNames(): array {
		return [
			'isStaff',
			'maxHolds',
			'restrictMasquerade',
			'twoFactorAuthSettingId',
			'accountLinkingSetting',
			'accountLinkRemoveSetting',
			'enableReadingHistory',
			'canSuggestMaterials',
		];
	}

	static function getObjectStructure($context = ''): array {
		$roles = [];
		$roles[-1] = 'None';
		$role = new Role();
		$role->orderBy('name');
		$role->find();
		while ($role->fetch()) {
			$roles[$role->roleId] = $role->name;
		}
		$twoFactorAuthSettings = [];
		$twoFactorAuthSettings[-1] = 'None';
		$twoFactorAuthSetting = new TwoFactorAuthSetting();
		$twoFactorAuthSetting->orderBy('name');
		$twoFactorAuthSetting->find();
		while ($twoFactorAuthSetting->fetch()) {
			$twoFactorAuthSettings[$twoFactorAuthSetting->id] = $twoFactorAuthSetting->name;
		}
		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id of the p-type within the database',
				'hideInLists' => false,
			],
			'pType' => [
				'property' => 'pType',
				'type' => 'text',
				'label' => 'P-Type',
				'description' => 'The P-Type for the patron',
			],
			'description' => [
				'property' => 'description',
				'type' => 'text',
				'label' => 'Description',
				'description' => 'A description for the Patron Type',
				'maxLength' => 100,
			],
			'maxHolds' => [
				'property' => 'maxHolds',
				'type' => 'integer',
				'label' => 'Max Holds',
				'description' => 'The maximum holds that a patron can have.',
				'default' => 300,
			],
			'assignedRoleId' => [
				'property' => 'assignedRoleId',
				'type' => 'enum',
				'values' => $roles,
				'label' => 'Assigned Role',
				'description' => 'Automatically assign a role to a user based on patron type',
				'default' => '-1',
			],
			'isStaff' => [
				'property' => 'isStaff',
				'type' => 'checkbox',
				'label' => 'Treat as staff',
				'description' => 'Treat the user as staff, but without specific permissions in Aspen',
				'default' => 0,
			],
			'restrictMasquerade' => [
				'property' => 'restrictMasquerade',
				'type' => 'checkbox',
				'label' => 'Restrict masquerade from accessing patrons of this type',
				'description' => 'Users without the ability to masquerade as restricted patrons will not be able to masquerade as this type',
				'default' => 0,
			],
			'enableReadingHistory' => [
				'property' => 'enableReadingHistory',
				'type' => 'checkbox',
				'label' => 'Enable Reading History',
				'description' => 'Whether or not reading history should be enabled for users with this PType
				',
				'note' => "Reading History must also be enabled for the user's home library",
				'default' => 1,
			],
			'twoFactorAuthSettingId' => [
				'property' => 'twoFactorAuthSettingId',
				'type' => 'enum',
				'values' => $twoFactorAuthSettings,
				'label' => 'Two-factor authentication setting',
				'description' => 'The unique id of the two-factor authentication setting tied to this patron type',
				'default' => -1,
			],
			'vdxClientCategory' => [
				'property' => 'vdxClientCategory',
				'type' => 'text',
				'values' => $twoFactorAuthSettings,
				'label' => 'VDX Client Category',
				'description' => 'The client category to be used when sending requests to VDX',
				'maxLength' => 10,
				'default' => '',
				'hideInLists' => true,
			],
			'accountLinkingSetting' => [
				'property' => 'accountLinkingSetting',
				'type' => 'enum',
				'values' => [
					0 => 'Allow to be linked to and link to others',
					1 => 'Allow only to be linked to',
					2 => 'Allow only to link to others',
					3 => 'Block all linking',
				],
				'default' => 0,
				'label' => 'Account linking setting',
				'description' => 'The account linking setting tied to this patron type',
				'onchange' => "return AspenDiscovery.Admin.linkingSettingOptionChange();",
			],
			'accountLinkRemoveSetting' => [
				'property' => 'accountLinkRemoveSetting',
				'type' => 'checkbox',
				'label' => 'Allow users to remove managing account links',
				'description' => 'Linkees with this patron type will have access to a Remove button to delete managing account links. Linkees without this permission will require staff intervention to delete managing account links.',
				'onchange' => "return AspenDiscovery.Admin.linkingRemoveSettingOptionChange();",
			],
			'canSuggestMaterials' => [
				'property' => 'canSuggestMaterials',
				'type' => 'checkbox',
				'label' => 'Allow users to create materials requests',
				'description' => 'Allow users of this patron type to create materials requests or purchase suggestions.',
			],
		];
		if (!UserAccount::userHasPermission('Administer Permissions')) {
			unset($structure['assignedRoleId']);
		}
		return $structure;
	}

	/**
	 * @param boolean $addEmpty whether or not an empty value should be returned first
	 * @param boolean $valueIsPType whether or not the value returned is the pType or database id (default)
	 * @return array
	 */
	static function getPatronTypeList(bool $addEmpty = false, bool $valueIsPType = false): array {
		$patronType = new pType();
		$patronType->orderBy('pType');
		$patronType->find();
		$patronTypeList = [];
		if ($addEmpty) {
			$patronTypeList[-1] = "";
		}
		$selectValue = 'id';
		if ($valueIsPType) {
			$selectValue = 'pType';
		}
		while ($patronType->fetch()) {
			$patronTypeLabel = $patronType->pType;
			if (!empty($patronType->description)) {
				$patronTypeLabel .= ' - ' . $patronType->description;
			}
			$patronTypeList[$patronType->$selectValue] = $patronTypeLabel;
		}
		return $patronTypeList;
	}

	static function getAccountLinkingSetting($pType): string {
		$pTypeSetting = new pType();
		$pTypeSetting->pType = $pType;
		if ($pTypeSetting->find(true)) {
			return $pTypeSetting->accountLinkingSetting;
		} else {
			return "0";
		}
	}

	static function getAccountLinkRemoveSetting($pType): string {
		$pTypeSetting = new pType();
		$pTypeSetting->pType = $pType;
		if ($pTypeSetting->find(true)) {
			return $pTypeSetting->accountLinkRemoveSetting;
		} else {
			return "1";
		}
	}

	public function update($context = '') {
		if ($this->accountLinkingSetting == 0) {
			return parent::update();
		} else {
			$user = new User();
			$user->patronType = $this->pType;
			$user->find();
			$usersToUpdate = $user->fetchAll();

			foreach ($usersToUpdate as $user) {
				if ($this->accountLinkingSetting == 1) {
					$userLink = new UserLink();
					$userLink->primaryAccountId = $user->id;
					$userLink->delete(true);
				} elseif ($this->accountLinkingSetting == 2) {
					require_once ROOT_DIR . '/sys/Account/UserMessage.php';
					$userLink = new UserLink();
					$userLink->linkedAccountId = $user->id;
					$userLink->find();
					while ($userLink->fetch()) {
						$userLink->delete();

						$userMessage = new UserMessage();
						$userMessage->messageType = 'linked_acct_notify_disabled_' . $this->id;
						$userMessage->userId = $userLink->primaryAccountId;
						$userMessage->isDismissed = "0";
						$userMessage->message = "An account you were previously linked to, $user->displayName, is no longer able to be linked to. To learn more about linked accounts, please visit your <a href='/MyAccount/LinkedAccounts'>Linked Accounts</a> page.";
						$userMessage->update();
					}
				} elseif ($this->accountLinkingSetting == 3) {
					//remove managing accounts
					require_once ROOT_DIR . '/sys/Account/UserMessage.php';
					$userLink = new UserLink();
					$userLink->linkedAccountId = $user->id;
					$userLink->find();
					while ($userLink->fetch()) {
						$userLink->delete();

						$userMessage = new UserMessage();
						$userMessage->messageType = 'linked_acct_notify_disabled_' . $this->id;
						$userMessage->userId = $userLink->primaryAccountId;
						$userMessage->isDismissed = "0";
						$userMessage->message = "An account you were previously linked to, $user->displayName, is no longer able to be linked to. To learn more about linked accounts, please visit your <a href='/MyAccount/LinkedAccounts'>Linked Accounts</a> page.";
						$userMessage->update();
					}
					//remove accounts linked to
					$userLink = new UserLink();
					$userLink->primaryAccountId = $user->id;
					$userLink->delete(true);
				}
			}
			return parent::update();
		}
	}
}