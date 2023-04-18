<?php

class SharedContent extends DataObject {
	public $__table = 'shared_content';
	public $id;
	public $type;
	public $name;
	public $sharedFrom;
	public $sharedByUserName;
	public $shareDate;
	public $approved;
	public $approvalDate;
	public $approvedBy;
	public $description;
	public $data;

	public static function getObjectStructure($context) {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'type' => [
				'property' => 'type',
				'type' => 'label',
				'label' => 'Object Type',
				'description' => 'The Type of content being shared',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'maxLength' => 100,
				'description' => 'The name of the content being shared',
			],
			'description' => [
				'property' => 'description',
				'type' => 'textArea',
				'label' => 'Description',
				'hideInLists' => true,
				'description' => 'A description of the content being shared',
			],
			'sharedFrom' => [
				'property' => 'sharedFrom',
				'type' => 'label',
				'label' => 'Shared From',
				'description' => 'The library who shared the content',
			],
			'sharedByUserName' => [
				'property' => 'sharedByUserName',
				'type' => 'label',
				'label' => 'Shared By',
				'description' => 'The user who shared the content',
			],
			'shareDate' => [
				'property' => 'shareDate',
				'type' => 'timestamp',
				'readOnly' => true,
				'label' => 'Share Date',
				'description' => 'When the content was shared',
			],
			'approved' => [
				'property' => 'approved',
				'type' => 'checkbox',
				'label' => 'Approved?',
				'description' => 'Whether or not the content is approved for use in the community',
				'required' => false,
			],
			'approvalDate' => [
				'property' => 'approvalDate',
				'type' => 'timestamp',
				'readOnly' => true,
				'label' => 'Approval Date',
				'description' => 'When the content was approved for use',
			],
			'approvedByName' => [
				'property' => 'approvedByName',
				'type' => 'label',
				'readOnly' => true,
				'label' => 'Approved By',
				'description' => 'Who approved the content for use',
			],
			'data' => [
				'property' => 'data',
				'type' => 'textarea',
				'label' => 'Data',
				'readOnly' => true,
				'description' => 'The JSON content that was shared',
				'hideInLists' => true,
			],
		];
	}

	public function update($context = '') {
		if ($this->approved && empty($this->approvalDate)) {
			$this->approvalDate = time();
			$this->approvedBy = UserAccount::getActiveUserId();
		}
		return parent::update($context);
	}

	/** @var User[] */
	private static $usersById = [];

	function __get($name) {
		if ($name == 'approvedByName') {
			if (empty($this->approvedBy)) {
				return '';
			}
			if (empty($this->_data['approvedBy'])) {
				if (!array_key_exists($this->approvedBy, SharedContent::$usersById)) {
					$user = new User();
					$user->id = $this->approvedBy;
					if ($user->find(true)) {
						SharedContent::$usersById[$this->approvedBy] = $user;
					}
				}
				if (array_key_exists($this->approvedBy, SharedContent::$usersById)) {
					$user = SharedContent::$usersById[$this->approvedBy];
					if (!empty($user->displayName)) {
						$this->_data['approvedByName'] = $user->displayName;
					} else {
						$this->_data['approvedByName'] = $user->firstname . ' ' . $user->lastname;
					}
				} else {
					$this->_data['approvedByName'] = translate([
						'text' => 'Unknown',
						'isPublicFacing' => true,
					]);
				}

			}
		}
		return $this->_data[$name] ?? null;
	}
}