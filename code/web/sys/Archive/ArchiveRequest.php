<?php

class ArchiveRequest extends DataObject {
	public $__table = 'archive_requests';
	public $id;
	public $name;
	public $address;
	public $address2;
	public $city;
	public $state;
	public $zip;
	public $country;
	public $phone;
	public $alternatePhone;
	public $email;
	public $format;
	public $purpose;
	public $pid;
	public $dateRequested;

	public static function getObjectStructure($context = ''): array {
		$structure = [
			[
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'Name',
				'maxLength' => 100,
				'required' => true,
			],
			[
				'property' => 'address',
				'type' => 'text',
				'label' => 'Address',
				'description' => 'Address',
				'maxLength' => 200,
				'required' => false,
			],
			[
				'property' => 'address2',
				'type' => 'text',
				'label' => 'Address 2',
				'description' => 'Address 2',
				'maxLength' => 200,
				'required' => false,
			],
			[
				'property' => 'city',
				'type' => 'text',
				'label' => 'City',
				'description' => 'City',
				'maxLength' => 200,
				'required' => false,
			],
			[
				'property' => 'state',
				'type' => 'text',
				'label' => 'State',
				'description' => 'State',
				'maxLength' => 200,
				'required' => false,
			],
			[
				'property' => 'zip',
				'type' => 'text',
				'label' => 'Zip/Postal Code',
				'description' => 'Address',
				'maxLength' => 12,
				'required' => false,
			],
			[
				'property' => 'country',
				'type' => 'text',
				'label' => 'Country',
				'description' => 'Country',
				'maxLength' => 50,
				'required' => false,
				'default' => 'United States',
			],
			[
				'property' => 'phone',
				'type' => 'text',
				'label' => 'Phone',
				'description' => 'Phone',
				'maxLength' => 20,
				'required' => true,
			],
			[
				'property' => 'alternatePhone',
				'type' => 'text',
				'label' => 'Alternate Phone',
				'description' => 'Alternate Phone',
				'maxLength' => 20,
				'required' => false,
			],
			[
				'property' => 'email',
				'type' => 'email',
				'label' => 'Email Address',
				'description' => 'Email Address',
				'maxLength' => 100,
				'required' => true,
			],
			[
				'property' => 'format',
				'type' => 'text',
				'label' => 'Format Required (Black and White/Color, Print/Digital, etc)',
				'description' => 'Additional information about how you want the materials delivered',
				'maxLength' => 255,
				'required' => false,
			],
			[
				'property' => 'purpose',
				'type' => 'textarea',
				'label' => 'Purpose: Provide information about how this image will be used: Description, title of publication, author, publisher, date of publication, number of copies produced, etc',
				'description' => 'Additional information about what you will use the copy(copies) for',
				'required' => true,
				'hideInLists' => true,
			],
			'pid' => [
				'property' => 'pid',
				'type' => 'hidden',
				'label' => 'PID of Object',
				'description' => 'ID of the object in ',
				'maxLength' => 50,
				'required' => true,
			],
			'dateRequested' => [
				'property' => 'dateRequested',
				'type' => 'hidden',
				'label' => 'The date this request was made',
			],

		];
		return $structure;
	}

	public function insert($context = '') {
		$this->dateRequested = time();
		return parent::insert();
	}
}