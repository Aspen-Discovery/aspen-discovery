<?php /** @noinspection PhpMissingFieldTypeInspection */


class LocalIllForm extends DataObject {
	public $__table = 'local_ill_form';
	public $id;
	public $name;
	public $introText;
	//We always show title
	public $showAcceptFee;
	public $requireAcceptFee;
	public $defaultMaxFee;
	public $showMaximumFee;
	public $feeInformationText;
	public $showNote;
	//We always show Pickup Library

	protected $_locations;

	/** @noinspection PhpUnusedParameterInspection */
	public static function getObjectStructure($context = ''): array {
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Local ILL Forms'));

		return [
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
				'description' => 'The Name of the Form',
				'maxLength' => 50,
			],
			'introText' => [
				'property' => 'introText',
				'type' => 'textarea',
				'label' => 'Intro Text',
				'description' => 'Introductory Text to be displayed at the top of the form',
				'maxLength' => 5000,
			],
			'showAcceptFee' => [
				'property' => 'showAcceptFee',
				'type' => 'checkbox',
				'label' => 'Show Accept Fee?',
				'description' => 'Whether or not the user should be prompted to accept the fee (if any)',
			],
			'requireAcceptFee' => [
				'property' => 'requireAcceptFee',
				'type' => 'checkbox',
				'label' => 'Require Accept Fee?',
				'description' => 'Whether or not the user should be required to accept the fee (if any)',
			],
			'showMaximumFee' => [
				'property' => 'showMaximumFee',
				'type' => 'checkbox',
				'label' => 'Show Maximum Fee?',
				'description' => 'Whether or not the user should be prompted for the maximum fee they will pay',
			],
			'defaultMaxFee' => [
				'property' => 'defaultMaxFee',
				'type' => 'text',
				'label' => 'Default Maximum Fee',
				'description' => 'The Default Maximum Fee to show to the patron.',
				'default' => 0
			],
			'feeInformationText' => [
				'property' => 'feeInformationText',
				'type' => 'textarea',
				'label' => 'Fee Information Text',
				'description' => 'Text to be displayed to give additional information about the fees charged.',
				'maxLength' => 5000,
			],
			'showNote' => [
				'property' => 'showNote',
				'type' => 'checkbox',
				'label' => 'Show Note?',
				'description' => 'Whether or not the user should be allowed to enter a note',
			],

			'locations' => [
				'property' => 'locations',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Locations',
				'description' => 'Define the locations that use this form',
				'values' => $locationList,
				'hideInLists' => false,
			],
		];
	}

	/**
	 * @return string[]
	 */
	public function getUniquenessFields(): array {
		return ['name'];
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLocations();
		}
		return $ret;
	}

	public function delete($useWhere = false): int {
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$location = new Location();
			$location->localIllFormId = $this->id;
			$location->find();
			while ($location->fetch()) {
				$location->localIllFormId = -1;
				$location->update();
			}
		}
		return $ret;
	}

	public function __get($name) {
		if ($name == "locations") {
			return $this->getLocations();
		} else {
			return parent::__get($name);
		}
	}

	public function saveLocations() {
		if (isset ($this->_locations) && is_array($this->_locations)) {
			$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Local ILL Forms'));
			foreach ($locationList as $locationId => $displayName) {
				$location = new Location();
				$location->locationId = $locationId;
				$location->find(true);
				if (in_array($locationId, $this->_locations)) {
					if ($location->localIllFormId != $this->id) {
						$location->localIllFormId = $this->id;
						$location->update();
					}
				} else {
					if ($location->localIllFormId == $this->id) {
						$location->localIllFormId = -1;
						$location->update();
					}
				}
			}
			unset($this->_locations);
		}
		return $this->_locations;
	}

	public function __set($name, $value) {
		if ($name == "locations") {
			$this->_locations = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	public function getFormFields(?MarcRecordDriver $marcRecordDriver, ?string $volumeId = null): array {
		$fields = [];
		if ($this->introText) {
			$fields['introText'] = [
				'property' => 'introText',
				'type' => 'alert',
				'alertType' => 'alert-info',
				'label' => 'Introductory Text',
				'default' => $this->introText,
				'description' => '',
			];
		}
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		$title = ($marcRecordDriver != null ? StringUtils::removeTrailingPunctuation($marcRecordDriver->getTitle()) : '');
		$relatedRecord = $marcRecordDriver->getRelatedRecord();
		$volumeData = $relatedRecord->getUnsuppressedVolumeData();
		$volumeDataLocal = $relatedRecord->getUnsuppressedLocallyOwnedVolumes();
		$showSelectVolume = false;
		if (!empty($volumeData)) {
			$showSelectVolume = true;
			foreach ($volumeData as $key => $volume) {
				if (!empty($volumeId)) {
					if ($volume->volumeId == $volumeId) {
						$title .= ' ' . $volume->displayLabel;
						$showSelectVolume = false;
						break;
					}
				}
				if (!in_array($key, $volumeDataLocal)) {
					$volume->setNeedsIllRequest(true);
				}
			}
		}
		$fields['title'] = [
			'property' => 'title',
			'type' => 'label',
			'label' => 'Title',
			'description' => '',
			'maxLength' => 255,
			'default' => $title,
		];
		if ($this->showAcceptFee) {
			if (!empty($this->feeInformationText)) {
				$fields['feeInformationText'] = [
					'property' => 'feeInformationText',
					'type' => 'label',
					'label' => $this->feeInformationText,
					'value' => '',
					'description' => '',
					'suppressNotSetForEmpty' => true,
				];
			}
			if ($this->showMaximumFee) {
				$fields['maximumFeeAmount'] = [
					'property' => 'maximumFeeAmount',
					'type' => 'currency',
					'label' => 'Maximum Fee ',
					'description' => 'The maximum fee you are willing to pay to have this title transferred to the library.',
					'default' => $this->defaultMaxFee ?? 0,
					'displayFormat' => '%0.2f',
				];
				$fields['acceptFee'] = [
					'property' => 'acceptFee',
					'type' => 'checkbox',
					'label' => 'I will pay any fees associated with this request up to the maximum amount defined above',
					'description' => '',
					'required' => $this->requireAcceptFee,
				];
			} else {
				$fields['acceptFee'] = [
					'property' => 'acceptFee',
					'type' => 'checkbox',
					'label' => 'I will pay any fees associated with this request',
					'description' => '',
					'required' => $this->requireAcceptFee,
				];
			}
		}
		$user = UserAccount::getLoggedInUser();
		$locations = $user->getValidPickupBranches($user->getCatalogDriver()->accountProfile->recordSource);
		$pickupLocations = [];
		foreach ($locations as $key => $location) {
			if ($location instanceof Location) {
				$pickupLocations[$location->code] = $location->displayName;
			} else {
				if ($key == '0default') {
					$pickupLocations[-1] = $location;
				}
			}
		}
		$fields['pickupLocation'] = [
			'property' => 'pickupLocation',
			'type' => 'enum',
			'values' => $pickupLocations,
			'label' => 'Pickup Location',
			'description' => 'Where you would like to pickup the title',
			'required' => true,
			'default' => $user->getHomeLocationCode(),
		];
		if ($this->showNote) {
			$fields['note'] = [
				'property' => 'note',
				'type' => 'text',
				'label' => 'Note',
				'description' => 'Any additional information you want us to have about this request (40 characters maximum)',
				'required' => false,
				'default' => '',
				'maxLength' => 40
			];
		}
		$fields['catalogKey'] = [
			'property' => 'catalogKey',
			'type' => 'hidden',
			'label' => 'Record Number',
			'description' => 'The record number to be requested',
			'maxLength' => 20,
			'required' => false,
			'default' => ($marcRecordDriver != null ? $marcRecordDriver->getId() : ''),
		];
		if (!empty($volumeId)) {
			$fields['volumeId'] = [
				'property' => 'volumeId',
				'type' => 'hidden',
				'label' => 'Volume Id',
				'description' => 'The volume Id for the hold',
				'required' => false,
				'default' => $volumeId
			];
		}elseif ($showSelectVolume) {
			$volumeOptions = [
				"unselected" => translate(['text'=>"Please select a volume from the list below", 'isPublicFacing'=>true])
			];

			$numItemsWithoutVolumes = 0;
			foreach ($relatedRecord->getItems() as $item) {
				if (empty($item->volumeId)) {
					$numItemsWithoutVolumes++;
				}
			}
			$alwaysPlaceVolumeHoldWhenVolumesArePresent = $marcRecordDriver->getCatalogDriver()->alwaysPlaceVolumeHoldWhenVolumesArePresent();
			if ($numItemsWithoutVolumes > 0 && $alwaysPlaceVolumeHoldWhenVolumesArePresent) {
				$blankVolume = new IlsVolumeInfo();
				$blankVolume->displayLabel = translate([
					'text' => 'Untitled Volume',
					'isPublicFacing' => true,
				]);
				$blankVolume->volumeId = '';
				$blankVolume->recordId = $marcRecordDriver->getIdWithSource();
				$blankVolume->relatedItems = '';
				$blankVolume->setHasLocalItems(false);
				foreach ($relatedRecord->getItems() as $item) {
					if (empty($item->volumeId)) {
						if ($item->libraryOwned || $item->locallyOwned) {
							$blankVolume->setHasLocalItems(true);
						}
						$blankVolume->relatedItems .= $item->itemId . '|';
					}
				}
				$volumeData[] = $blankVolume;
			}

			global $library;
			foreach ($volumeData as $volume) {
				if (!$volume->needsIllRequest()) {
					$volumeLabel = $volume->displayLabel;
					if ($volume->hasLocalItems()) {
						$volumeLabel .=  '(' . translate([ 'text'=>"Owned by %1%", '1'=>$library->displayName, 'isPublicFacing'=>true]) . ')';
					}
				}else{
					$volumeLabel =  translate([ 'text'=>"%1% (Not Requestable)", '1'=>$volume->displayLabel, 'isPublicFacing'=>true]);
				}
				$volumeOptions[$volume->volumeId] = $volumeLabel;
			}

			$fields['volumeId'] = [
				'property' => 'volumeId',
				'type' => 'enum',
				'label' => 'Volume',
				'description' => 'The volume to request',
				'required' => false,
				'values' => $volumeOptions
			];
		}
		return $fields;
	}

	private function getLocations() : array {
		if (!isset($this->_locations)) {
			$this->_locations = [];
			if (!empty($this->id)) {
				$obj = new Location();
				$obj->localIllFormId = $this->id;
				$obj->find();
				while ($obj->fetch()) {
					$this->_locations[$obj->locationId] = $obj->locationId;
				}
			}
		}
		return $this->_locations;
	}

	public function getFormFieldsForApi() : array {
		$fields['introText'] = [
			'type' => 'text',
			'property' => 'introText',
			'display' => $this->introText ? 'show' : 'hide',
			'label' => $this->introText,
			'description' => '',
			'required' => false,
			'maxLength' => 255,
		];

		$fields['title'] = [
			'type' => 'input',
			'property' => 'title',
			'display' => 'show',
			'label' => translate([
				'text' => 'Title',
				'isPublicFacing' => true,
			]),
			'description' => translate([
				'text' => 'The title to request',
				'isPublicFacing' => true,
			]),
			'maxLength' => 255,
		];

		$fields['feeInformationText'] = [
			'type' => 'text',
			'property' => 'feeInformationText',
			'display' => $this->showAcceptFee ? 'show' : 'hide',
			'label' => $this->feeInformationText,
			'description' => '',
			'required' => false,
			'maxLength' => 255,
		];

		$fields['showMaximumFee'] = [
			'type' => 'number',
			'property' => 'showMaximumFee',
			'display' => $this->showMaximumFee ? 'show' : 'hide',
			'label' => translate([
				'text' => 'Maximum Fee',
				'isPublicFacing' => true,
			]),
			'description' => translate([
				'text' => 'The maximum fee you are willing to pay to have this title transferred to the library.',
				'isPublicFacing' => true,
			]),
			'required' => false,
			'maxLength' => 10,
			'default' => $this->defaultMaxFee ?? 0
		];

		$fields['acceptFee'] = [
			'type' => 'checkbox',
			'property' => 'acceptFee',
			'display' => $this->showAcceptFee ? 'show' : 'hide',
			'label' => translate([
				'text' => 'I will pay any fees associated with this request up to the maximum amount defined above',
				'isPublicFacing' => true,
			]),
			'description' => '',
			'required' => $this->requireAcceptFee,
		];

		$fields['note'] = [
			'type' => 'text',
			'property' => 'note',
			'display' => 'show',
			'label' => translate([
				'text' => 'Note',
				'isPublicFacing' => true,
			]),
			'description' => translate([
				'text' => 'Any additional information you want us to have about this request (40 characters maximum)',
				'isPublicFacing' => true,
			]),
			'required' => false,
			'maxLength' => 40,
		];

		$fields['catalogKey'] = [
			'type' => 'text',
			'property' => 'catalogKey',
			'display' => 'hide',
			'label' => translate([
				'text' => 'Record Number',
				'isPublicFacing' => true,
			]),
			'description' => translate([
				'text' => 'The record number to be requested',
				'isPublicFacing' => true,
			]),
			'required' => false,
			'maxLength' => 20,
		];

		$fields['volumeId'] = [
			'type' => 'text',
			'property' => 'volumeId',
			'display' => 'hide',
			'label' => translate([
				'text' => 'Volume ID',
				'isPublicFacing' => true,
			]),
			'description' => translate([
				'text' => 'The volume to be requested',
				'isPublicFacing' => true,
			]),
			'required' => false,
		];

		require_once ROOT_DIR . '/services/API/UserAPI.php';
		$user = new UserAPI();

		$pickupLocations = 'Unable to get pickup locations for given user';

		$validPickupLocations = $user->getValidPickupLocations();
		if ($validPickupLocations['success']) {
			$pickupLocations = $user->getValidPickupLocations();
			$pickupLocations = $pickupLocations['pickupLocations'];
		}

		$fields['pickupLocation'] = [
			'type' => 'select',
			'property' => 'pickupLocation',
			'display' => 'show',
			'label' => translate([
				'text' => 'Pickup Location',
				'isPublicFacing' => true,
			]),
			'description' => translate([
				'text' => 'Where you would like to pickup the title',
				'isPublicFacing' => true,
			]),
			'required' => true,
			'maxLength' => 255,
			'options' => $pickupLocations,
		];

		return $fields;
	}
}
