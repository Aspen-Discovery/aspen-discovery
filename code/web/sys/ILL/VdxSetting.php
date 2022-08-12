<?php

class VdxSetting extends DataObject {
	public $__table = 'vdx_settings';
	public $id;
	public $name;
	public $baseUrl;
	public $submissionEmailAddress;

	public static function getObjectStructure(): array
	{
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer ILL Hold Groups'));

		return [
			'id' => ['property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'],
			'name' => ['property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The Name of the Hold Group', 'maxLength' => 50],
			'baseUrl' => ['property' => 'baseUrl', 'type' => 'url', 'label' => 'Base Url', 'description' => 'The URL for the VDX System', 'maxLength' => 255],
			'submissionEmailAddress' => ['property' => 'submissionEmailAddress', 'type' => 'email', 'label' => 'Submission Email Address', 'description' => 'The Address where new submissions are sent', 'maxLength' => 255],
		];
	}

	/**
	 * @return string[]
	 */
	public function getUniquenessFields(): array
	{
		return ['name'];
	}

}