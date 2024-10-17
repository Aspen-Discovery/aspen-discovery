<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class UserContribution extends DataObject {
	public $__table = 'redwood_user_contribution';
	public $id;
	public $userId;
	public $title;
	public $creator;
	public $dateCreated;
	public $description;
	public $suggestedSubjects;
	public $howAcquired;
	public $filePath;
	public $status;
	public $license;
	public $allowRemixing;
	public $prohibitCommercialUse;
	public $requireShareAlike;
	public $dateContributed;

	public static function getObjectStructure($context = ''): array {
		$structure = [
			[
				'property' => 'title',
				'type' => 'text',
				'label' => 'Title',
				'description' => 'Title of the file',
				'maxLength' => 255,
				'required' => true,
			],
			[
				'property' => 'creator',
				'type' => 'text',
				'label' => 'Creator',
				'description' => 'Creator of the file',
				'maxLength' => 255,
			],
			[
				'property' => 'dateCreated',
				'type' => 'date',
				'label' => 'Date Created',
				'description' => 'When the picture was taken or file created',
			],
			[
				'property' => 'description',
				'type' => 'textarea',
				'label' => 'Description',
				'description' => 'Description of the file',
			],
			[
				'property' => 'suggestedSubjects',
				'type' => 'text',
				'label' => 'Subject(s) separated by commas',
				'description' => 'Subject(s) that should be applied separated by commas',
			],
			[
				'property' => 'howAcquired',
				'type' => 'text',
				'label' => 'How Acquired',
				'description' => 'How the file was acquired',
				'maxLength' => 255,
			],
			[
				'property' => 'filePath',
				'type' => 'file',
				'label' => 'File to submit',
				'description' => 'The file to submit',
			],
			[
				'property' => 'license',
				'type' => 'enum',
				'values' => [
					'none' => 'Unknown',
					'CC0' => 'Creative Commons 0, no rights reserved',
					'cc' => 'Creative Commons',
					'public' => 'Public Domain',
				],
				'label' => 'License',
				'description' => 'The license that applies to the file',
			],
			[
				'property' => 'allowRemixing',
				'type' => 'checkbox',
				'label' => 'Allow Remixing',
				'description' => 'Whether or not the file can be changed after downloading',
			],
			[
				'property' => 'prohibitCommercialUse',
				'type' => 'checkbox',
				'label' => 'Prohibit Commercial Usage',
				'description' => 'Prohibit commercial use of the file',
			],
			[
				'property' => 'requireShareAlike',
				'type' => 'checkbox',
				'label' => 'Require Share Alike',
				'description' => 'If the downloaded file has been changed does the change need the same rights?',
			],

		];
		return $structure;
	}

	public function insert($context = '') {
		global $user;
		$this->dateContributed = time();
		$this->userId = $user->id;
		$this->status = 'submitted';
		return parent::insert();
	}
}