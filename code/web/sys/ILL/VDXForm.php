<?php

class VdxForm extends DataObject
{
	public $__table = 'vdx_form';
	public $id;
	public $name;
	public $introText;
	//We always show title
	public $showAuthor;
	public $showPublisher;
	public $showIsbn;
	public $showAcceptFee;
	public $showMaximumFee;
	public $feeInformationText;
	public $showCatalogKey;
	//We always show Note
	//We always show Pickup Library

	protected $_locations;

	public static function getObjectStructure(): array
	{
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer ILL Hold Groups'));

		return [
			'id' => ['property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'],
			'name' => ['property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The Name of the Hold Group', 'maxLength' => 50],
			'introText' => ['property' => 'introText', 'type' => 'textarea', 'label' => 'Intro Text', 'description' => 'Introductory Text to be displayed at the top of the form', 'maxLength' => 50],
			'showAuthor' => array('property' => 'showAuthor', 'type' => 'checkbox', 'label' => 'Show Author?', 'description' => 'Whether or not the user should be prompted to enter the author name'),
			'showPublisher' => array('property' => 'showPublisher', 'type' => 'checkbox', 'label' => 'Show Publisher?', 'description' => 'Whether or not the user should be prompted to enter the publisher name'),
			'showIsbn' => array('property' => 'showIsbn', 'type' => 'checkbox', 'label' => 'Show ISBN?', 'description' => 'Whether or not the user should be prompted to enter the ISBN'),
			'showAcceptFee' => array('property' => 'showAcceptFee', 'type' => 'checkbox', 'label' => 'Show Accept Fee?', 'description' => 'Whether or not the user should be prompted to accept the fee (if any)'),
			'showMaximumFee' => array('property' => 'showMaximumFee', 'type' => 'checkbox', 'label' => 'Show Maximum Fee?', 'description' => 'Whether or not the user should be prompted for the maximum fee they will pay'),
			'feeInformationText' => ['property' => 'feeInformationText', 'type' => 'textarea', 'label' => 'Fee Information Text', 'description' => 'Text to be displayed to give additional information about the fees charged.', 'maxLength' => 50],
			'showCatalogKey' => array('property' => 'showCatalogKey', 'type' => 'checkbox', 'label' => 'Show Catalog Key?', 'description' => 'Whether or not the user should be prompted for the catalog key'),

			'locations' => array(
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define locations that make up this hold group',
				'values' => $locationList,
				'hideInLists' => false
			),
		];
	}
}