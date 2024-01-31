<?php

/**
 * Class DonationsSetting - Store settings for Donations
 */

require_once ROOT_DIR . '/sys/Donations/DonationValue.php';
require_once ROOT_DIR . '/sys/Donations/DonationFormFields.php';
require_once ROOT_DIR . '/sys/Donations/DonationEarmark.php';
require_once ROOT_DIR . '/sys/Donations/DonationDedicationType.php';

class DonationsSetting extends DataObject {
	public $__table = 'donations_settings';
	public $id;
	public $name;
	public $allowDonationsToBranch;
	public $allowDonationEarmark;
	public $allowDonationDedication;
	public $requiresAddressInfo;
	public $donationsContent;
	public $donationEmailTemplate;

	private $_libraries;

	static function getObjectStructure($context = ''): array {
		$donationsValuesStructure = DonationValue::getObjectStructure($context);
		unset($donationsValuesStructure['donationSettingId']);

		$donationsFormFieldsStructure = DonationFormFields::getObjectStructure($context);
		unset($donationsFormFieldsStructure['donationSettingId']);

		$donationsEarmarksStructure = DonationEarmark::getObjectStructure($context);
		unset($donationsEarmarksStructure['donationSettingId']);

		$donationsDedicationTypesStructure = DonationDedicationType::getObjectStructure($context);
		unset($donationsDedicationTypesStructure['donationSettingId']);

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		$structure = [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'A name for the settings',
				'maxLength' => 50,
			],
			'allowDonationsToBranch' => [
				'property' => 'allowDonationsToBranch',
				'type' => 'checkbox',
				'label' => 'Allow users to select a specific branch to send their donation to',
				'description' => 'Whether or not users can specify that their donation goes to a specific branch.',
				'note' => 'You can manage the visibility of branches in Location Settings',
			],
			'allowDonationEarmark' => [
				'property' => 'allowDonationEarmark',
				'type' => 'checkbox',
				'label' => 'Allow users to choose an earmark for their donation',
				'description' => 'Whether or not users can specify that their donation goes to a specific library need.',
				'onchange' => 'return AspenDiscovery.Admin.updateDonationsSettingFields();',
			],
			'allowDonationDedication' => [
				'property' => 'allowDonationDedication',
				'type' => 'checkbox',
				'label' => 'Allow users to make their donation in dedication of someone',
				'description' => 'Whether or not users can ask that their donation be dedicated to someone.',
				'onchange' => 'return AspenDiscovery.Admin.updateDonationsSettingFields();',
			],
			'requiresAddressInfo' => [
				'property' => 'requiresAddressInfo',
				'type' => 'checkbox',
				'label' => 'Allow users to enter their address when making a donation.',
				'description' => 'Whether or not users are prompted to add address information to make a donation.',
			],
			'donationsContent' => [
				'property' => 'donationsContent',
				'type' => 'html',
				'label' => 'Page Content',
				'description' => 'Content that is displayed on the page before the form.',
				'hideInLists' => true,
			],
			'donationEmailTemplate' => [
				'property' => 'donationEmailTemplate',
				'type' => 'html',
				'label' => 'Email Template',
				'description' => 'Content that is displayed in the user\'s email receipt.',
				'hideInLists' => true,
			],
			'donationValues' => [
				'property' => 'donationValues',
				'type' => 'oneToMany',
				'label' => 'Donation Values',
				'description' => 'Determine what values are available for users to select when making a donation.',
				'keyThis' => 'donationSettingId',
				'keyOther' => 'donationSettingId',
				'subObjectType' => 'DonationValue',
				'structure' => $donationsValuesStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'hideInLists' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],

			'donationEarmarks' => [
				'property' => 'donationEarmarks',
				'type' => 'oneToMany',
				'label' => 'Donation Earmarks',
				'description' => 'Determine what earmarks are available to users when making a donation.',
				'keyThis' => 'donationSettingId',
				'keyOther' => 'donationSettingId',
				'subObjectType' => 'DonationEarmark',
				'structure' => $donationsEarmarksStructure,
				'sortable' => true,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'hideInLists' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],

			'donationDedicationTypes' => [
				'property' => 'donationDedicationTypes',
				'type' => 'oneToMany',
				'label' => 'Donation Dedication Types',
				'description' => 'Determine what types of dedications are available to users when making a donation.',
				'keyThis' => 'donationSettingId',
				'keyOther' => 'donationSettingId',
				'subObjectType' => 'DonationDedicationType',
				'structure' => $donationsDedicationTypesStructure,
				'sortable' => false,
				'storeDb' => true,
				'allowEdit' => false,
				'canEdit' => false,
				'hideInLists' => true,
				'canAddNew' => true,
				'canDelete' => true,
			],

			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that use these settings',
				'values' => $libraryList,
				'hideInLists' => false,
			],
		];

		if (!UserAccount::userHasPermission('Library eCommerce Options')) {
			unset($structure['libraries']);
		}
		return $structure;
	}

	public function __get($name) {
		if ($name == "libraries") {
			if (!isset($this->_libraries) && $this->id) {
				$this->_libraries = [];
				$obj = new Library();
				$obj->donationSettingId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_libraries[$obj->libraryId] = $obj->libraryId;
				}
			}
			return $this->_libraries;
		} elseif ($name == 'donationFormFields') {
			return $this->getDonationFormFields();
		} elseif ($name == 'donationValues') {
			return $this->getDonationValues();
		} elseif ($name == 'donationEarmarks') {
			return $this->getDonationEarmarks();
		} elseif ($name == 'donationDedicationTypes') {
			return $this->getDonationDedicationTypes();
		} else {
			return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} elseif ($name == 'donationFormFields') {
			$this->_donationFormFields = $value;
		} elseif ($name == 'donationValues') {
			$this->_donationValues = $value;
		} elseif ($name == 'donationEarmarks') {
			$this->_donationEarmarks = $value;
		} elseif ($name == 'donationDedicationTypes') {
			$this->_donationDedicationTypes = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveDonationFormFields();
			$this->saveDonationValues();
			$this->saveDonationEarmarks();
			$this->saveDonationDedicationTypes();
		}
		return true;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveDonationFormFields();
			$this->saveDonationValues();
			$this->saveDonationEarmarks();
			$this->saveDonationDedicationTypes();
		}
		return $ret;
	}

	public function saveLibraries() {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$library = new Library();
				$library->libraryId = $libraryId;
				$library->find(true);
				if (in_array($libraryId, $this->_libraries)) {
					//We want to apply the scope to this library
					if ($library->donationSettingId != $this->id) {
						$library->donationSettingId = $this->id;
						$library->update();
					}
				} else {
					//It should not be applied to this scope. Only change if it was applied to the scope
					if ($library->donationSettingId == $this->id) {
						$library->donationSettingId = -1;
						$library->update();
					}
				}
			}
			unset($this->_libraries);
		}
	}

	private $_donationFormFields;

	public function setDonationFormFields($value) {
		$this->_donationFormFields = $value;
	}

	/**
	 * @return array|null
	 */
	public function getDonationFormFields() {
		if (!isset($this->_donationFormFields) && $this->id) {
			$this->_donationFormFields = [];

			$donationFormFields = new DonationFormFields();
			$donationFormFields->donationSettingId = $this->id;
			if ($donationFormFields->find()) {
				while ($donationFormFields->fetch()) {
					$this->_donationFormFields[$donationFormFields->id] = clone $donationFormFields;
				}
			}

		}
		return $this->_donationFormFields;
	}

	private $_donationValues;

	public function setDonationValues($value) {
		$this->_donationValues = $value;
	}

	/**
	 * @return array|null
	 */
	public function getDonationValues() {
		if (!isset($this->_donationValues) && $this->id) {
			$this->_donationValues = [];

			$donationValues = new DonationValue();
			$donationValues->donationSettingId = $this->id;
			$donationValues->orderBy('weight');
			if ($donationValues->find()) {
				while ($donationValues->fetch()) {
					$this->_donationValues[$donationValues->id] = clone $donationValues;
				}
			}

		}
		return $this->_donationValues;
	}

	private $_donationEarmarks;

	public function setDonationEarmarks($value) {
		$this->_donationEarmarks = $value;
	}

	/**
	 * @return array|null
	 */
	public function getDonationEarmarks() {
		if (!isset($this->_donationEarmarks) && $this->id) {
			$this->_donationEarmarks = [];

			$donationEarmarks = new DonationEarmark();
			$donationEarmarks->donationSettingId = $this->id;
			$donationEarmarks->orderBy('weight');
			if ($donationEarmarks->find()) {
				while ($donationEarmarks->fetch()) {
					$this->_donationEarmarks[$donationEarmarks->id] = clone $donationEarmarks;
				}
			}

		}
		return $this->_donationEarmarks;
	}

	private $_donationDedicationTypes;

	public function setDonationDedications($value) {
		$this->_donationDedicationTypes = $value;
	}

	/**
	 * @return array|null
	 */
	public function getDonationDedicationTypes() {
		if (!isset($this->_donationDedicationTypes) && $this->id) {
			$this->_donationDedicationTypes = [];

			$donationDedications = new DonationDedicationType();
			$donationDedications->donationSettingId = $this->id;
			$donationDedications->orderBy('label');
			if ($donationDedications->find()) {
				while ($donationDedications->fetch()) {
					$this->_donationDedicationTypes[$donationDedications->id] = clone $donationDedications;
				}
			}

		}
		return $this->_donationDedicationTypes;
	}

	public function getDefaultFormFields(): array {
		$defaultFieldsToDisplay = [];

		// Donation Information
		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'Donation Amount';
		$defaultField->textId = 'valueList';
		$defaultField->type = 'select';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'What would you like your donation to support?';
		$defaultField->textId = 'earmarkList';
		$defaultField->type = 'select';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'If your donation is for a specific branch, please select the branch';
		$defaultField->textId = 'locationList';
		$defaultField->type = 'select';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Choose an amount to donate';
		$defaultField->label = 'Dedicate my donation in honor or in memory of someone';
		$defaultField->textId = 'shouldBeDedicated';
		$defaultField->type = 'checkbox';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Honoree information';
		$defaultField->label = 'Choose an amount to donate';
		$defaultField->textId = 'dedicationType';
		$defaultField->type = 'radio';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Honoree information';
		$defaultField->label = 'Honoree\'s First Name';
		$defaultField->textId = 'honoreeFirstName';
		$defaultField->type = 'text';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Honoree information';
		$defaultField->label = 'Honoree\'s Last Name';
		$defaultField->textId = 'honoreeLastName';
		$defaultField->type = 'text';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Honoree information';
		$defaultField->label = 'Notify someone about this in memory or in honor of gift';
		$defaultField->textId = 'shouldBeNotified';
		$defaultField->type = 'checkbox';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Notification party information';
		$defaultField->label = 'Notification Party First Name';
		$defaultField->textId = 'notificationFirstName';
		$defaultField->type = 'text';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Notification party information';
		$defaultField->label = 'Notification Party Last Name';
		$defaultField->textId = 'notificationLastName';
		$defaultField->type = 'text';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Notification party information';
		$defaultField->label = 'Address';
		$defaultField->textId = 'notificationAddress';
		$defaultField->type = 'text';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Notification party information';
		$defaultField->label = 'City';
		$defaultField->textId = 'notificationCity';
		$defaultField->type = 'text';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Notification party information';
		$defaultField->label = 'State';
		$defaultField->textId = 'notificationState';
		$defaultField->type = 'text';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Notification party information';
		$defaultField->label = 'Zipcode';
		$defaultField->textId = 'notificationZip';
		$defaultField->type = 'text';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		// User Information
		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Enter your information';
		$defaultField->label = 'First Name';
		$defaultField->textId = 'firstName';
		$defaultField->type = 'text';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Enter your information';
		$defaultField->label = 'Last Name';
		$defaultField->textId = 'lastName';
		$defaultField->type = 'text';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Enter your information';
		$defaultField->label = 'Don\'t show my name publicly';
		$defaultField->textId = 'makeAnonymous';
		$defaultField->type = 'checkbox';
		$defaultField->required = 0;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		$defaultField = new DonationFormFields();
		$defaultField->donationSettingId = $this->id;
		$defaultField->category = 'Enter your information';
		$defaultField->label = 'Email Address';
		$defaultField->textId = 'emailAddress';
		$defaultField->type = 'text';
		$defaultField->note = 'Your receipt will be emailed here';
		$defaultField->required = 1;
		$defaultField->insert();
		$defaultFieldsToDisplay[] = $defaultField;

		if ($this->requiresAddressInfo) {
			$defaultField = new DonationFormFields();
			$defaultField->donationSettingId = $this->id;
			$defaultField->category = 'Enter your information';
			$defaultField->label = 'Address';
			$defaultField->textId = 'address';
			$defaultField->type = 'text';
			$defaultField->required = 0;
			$defaultField->insert();
			$defaultFieldsToDisplay[] = $defaultField;

			$defaultField = new DonationFormFields();
			$defaultField->donationSettingId = $this->id;
			$defaultField->category = 'Enter your information';
			$defaultField->label = 'Address 2';
			$defaultField->textId = 'address2';
			$defaultField->type = 'text';
			$defaultField->required = 0;
			$defaultField->insert();
			$defaultFieldsToDisplay[] = $defaultField;

			$defaultField = new DonationFormFields();
			$defaultField->donationSettingId = $this->id;
			$defaultField->category = 'Enter your information';
			$defaultField->label = 'City';
			$defaultField->textId = 'city';
			$defaultField->type = 'text';
			$defaultField->required = 0;
			$defaultField->insert();
			$defaultFieldsToDisplay[] = $defaultField;

			$defaultField = new DonationFormFields();
			$defaultField->donationSettingId = $this->id;
			$defaultField->category = 'Enter your information';
			$defaultField->label = 'State';
			$defaultField->textId = 'state';
			$defaultField->type = 'text';
			$defaultField->required = 0;
			$defaultField->insert();
			$defaultFieldsToDisplay[] = $defaultField;

			$defaultField = new DonationFormFields();
			$defaultField->donationSettingId = $this->id;
			$defaultField->category = 'Enter your information';
			$defaultField->label = 'Zip';
			$defaultField->textId = 'zip';
			$defaultField->type = 'text';
			$defaultField->required = 0;
			$defaultField->insert();
			$defaultFieldsToDisplay[] = $defaultField;
		}

		return $defaultFieldsToDisplay;

	}

	public function saveDonationFormFields() {
		if (isset ($this->_donationFormFields) && is_array($this->_donationFormFields)) {
			$this->saveOneToManyOptions($this->_donationFormFields, 'donationSettingId');
			unset($this->_donationFormFields);
		}
	}

	public function saveDonationValues() {
		if (isset ($this->_donationValues) && is_array($this->_donationValues)) {
			$this->saveOneToManyOptions($this->_donationValues, 'donationSettingId');
			unset($this->_donationValues);
		}
	}

	public function saveDonationEarmarks() {
		if (isset ($this->_donationEarmarks) && is_array($this->_donationEarmarks)) {
			$this->saveOneToManyOptions($this->_donationEarmarks, 'donationSettingId');
			unset($this->_donationEarmarks);
		}
	}

	public function saveDonationDedicationTypes() {
		if (isset ($this->_donationDedicationTypes) && is_array($this->_donationDedicationTypes)) {
			$this->saveOneToManyOptions($this->_donationDedicationTypes, 'donationSettingId');
			unset($this->_donationDedicationTypes);
		}
	}

	/**
	 * @return Location[]
	 */
	public function getLocations(): array {
		$locations = [];
		$location = new Location();
		$location->orderBy('isMainBranch desc');
		$location->orderBy('displayName');
		$location->libraryId = $this->libraryId;
		$location->find();
		while ($location->fetch()) {
			$locations[$location->locationId] = clone($location);
		}
		return $locations;
	}

	/** @noinspection PhpUnused */
	function defaultDonationForm() {
		$defaultFieldsToDisplay = DonationFormFields::getDefaults($this->id);
		$this->clearDonationFormFields();
		$this->setDonationFormFields($defaultFieldsToDisplay);
		$this->update();
		header("Location: /Admin/DonationsSettings?objectAction=edit&id=" . $this->id);
		die();
	}

	/** @noinspection PhpUnused */
	function defaultDonationValues() {
		$defaultValuesToDisplay = DonationValue::getDefaults($this->id);
		$this->setDonationValues($defaultValuesToDisplay);
		$this->update();
		header("Location: /Admin/DonationsSettings?objectAction=edit&id=" . $this->id);
		die();
	}

	/** @noinspection PhpUnused */
	function defaultDonationEarmarks() {
		$defaultEarmarksToDisplay = DonationEarmark::getDefaults($this->id);
		$this->setDonationEarmarks($defaultEarmarksToDisplay);
		$this->update();
		header("Location: /Admin/DonationsSettings?objectAction=edit&id=" . $this->id);
		die();
	}

	/** @noinspection PhpUnused */
	function defaultDonationDedicationTypes() {
		$defaultDedicationsToDisplay = DonationDedicationType::getDefaults($this->id);
		$this->setDonationDedications($defaultDedicationsToDisplay);
		$this->update();
		header("Location: /Admin/DonationsSettings?objectAction=edit&id=" . $this->id);
		die();
	}

	public function clearDonationFormFields() {
		$this->clearOneToManyOptions('DonationsFormFields', 'donationSettingId');
		/** @noinspection PhpUndefinedFieldInspection */
		$this->donationFormFields = [];
	}

	function getEditLink($context): string {
		return '/Admin/DonationsSettings?objectAction=edit&id=' . $this->id;
	}
}