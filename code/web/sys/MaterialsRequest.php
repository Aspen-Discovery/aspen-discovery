<?php
/**
 * Table Definition for Materials Request
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class MaterialsRequest extends DB_DataObject
{
	public $__table = 'materials_request';   // table name

	// Note: if table column names are changed, data for class MaterialsRequestFieldsToDisplay will need updated.
	public $id;
	public $libraryId;
	public $title;
	public $season;
	public $magazineTitle;
	public $magazineDate;
	public $magazineVolume;
	public $magazineNumber;
	public $magazinePageNumbers;
	public $author;
	public $format;
	public $formatId;
	public $subFormat;
	public $ageLevel;
	public $bookType;
	public $isbn;
	public $upc;
	public $issn;
	public $oclcNumber;
	public $publisher;
	public $publicationYear;
	public $abridged;
	public $about;
	public $comments;
	public $status;
	public $phone;
	public $email;
	public $dateCreated;
	public $createdBy;
	public $dateUpdated;
	public $emailSent;
	public $holdsCreated;
	public $placeHoldWhenAvailable;
	public $illItem;
	public $holdPickupLocation;
	public $bookmobileStop;
	public $assignedTo;

	//Dynamic properties setup by joins
	public $numRequests;
	public $description;
	public $userId;
	public $firstName;
	public $lastName;

	function keys() {
		return array('id');
	}

	static function getFormats(){
		require_once ROOT_DIR . '/sys/MaterialsRequestFormats.php';
		$availableFormats = array();
		$customFormats = new MaterialsRequestFormats();
		global $library;
		$requestLibrary = $library;
		if (UserAccount::isLoggedIn()) {
			$user = UserAccount::getLoggedInUser();
			$homeLibrary = $user->getHomeLibrary();
			if (isset($homeLibrary)) {
				$requestLibrary = $homeLibrary;
			}
		}

		$customFormats->libraryId = $requestLibrary->libraryId;

		if ($customFormats->count() == 0 ) {
			// Default Formats to use when no custom formats are created.

			/** @var MaterialsRequestFormats[] $defaultFormats */
			$defaultFormats = MaterialsRequestFormats::getDefaultMaterialRequestFormats($requestLibrary->libraryId);
			$availableFormats = array();

			global $configArray;
			foreach ($defaultFormats as $index => $materialRequestFormat){
				$format = $materialRequestFormat->format;
				if (isset($configArray['MaterialsRequestFormats'][$format]) && $configArray['MaterialsRequestFormats'][$format] == false){
					// dont add this format
				} else {
					$availableFormats[$format] = $materialRequestFormat->formatLabel;
				}
			}

		} else {
			$customFormats->orderBy('weight');
			$availableFormats = $customFormats->fetchAll('format', 'formatLabel');
		}

		return $availableFormats;
	}

	public function getFormatObject() {
		if (!empty($this->libraryId) && !empty($this->format)) {
			require_once ROOT_DIR . '/sys/MaterialsRequestFormats.php';
			$format = new MaterialsRequestFormats();
			$format->format = $this->format;
			$format->libraryId = $this->libraryId;
			if ($format->find(1)) {
				return $format;
			} else {
				foreach (MaterialsRequestFormats::getDefaultMaterialRequestFormats($this->libraryId) as $defaultFormat) {
					if ($this->format == $defaultFormat->format) {
						return $defaultFormat;
					}

				}
			}
		}
		return false;
	}

	static $materialsRequestEnabled = null;
	static function enableMaterialsRequest($forceReload = false){
		if (MaterialsRequest::$materialsRequestEnabled != null && $forceReload == false){
			return MaterialsRequest::$materialsRequestEnabled;
		}
		global $configArray;
		global $library;

		//First make sure we are enabled in the config file
		if (isset($configArray['MaterialsRequest']) && isset($configArray['MaterialsRequest']['enabled'])){
			$enableMaterialsRequest = $configArray['MaterialsRequest']['enabled'];
			//Now check if the library allows material requests
			if ($enableMaterialsRequest){
				if (isset($library) && $library->enableMaterialsRequest == 0){
					$enableMaterialsRequest = false;
				}else if (UserAccount::isLoggedIn()){
					$homeLibrary = Library::getPatronHomeLibrary();
					if (is_null($homeLibrary)){
						$enableMaterialsRequest = false;
					}else if ($homeLibrary->enableMaterialsRequest == 0){
						$enableMaterialsRequest = false;
					}else if (isset($library) && $homeLibrary->libraryId != $library->libraryId){
						$enableMaterialsRequest = false;
					}else if (isset($configArray['MaterialsRequest']['allowablePatronTypes'])){
						//Check to see if we need to do additional restrictions by patron type
						$allowablePatronTypes = $configArray['MaterialsRequest']['allowablePatronTypes'];
						if (strlen($allowablePatronTypes) > 0){
							$user = UserAccount::getLoggedInUser();
							if (!preg_match("/^$allowablePatronTypes$/i", $user->patronType)){
								$enableMaterialsRequest = false;
							}
						}
					}
				}
			}
		}else{
			$enableMaterialsRequest = false;
		}
		MaterialsRequest::$materialsRequestEnabled = $enableMaterialsRequest;
		return $enableMaterialsRequest;
	}

	function getHoldLocationName($locationId) {
		require_once ROOT_DIR . '/Drivers/marmot_inc/Location.php';
		$holdLocation = new Location();
		if ($holdLocation->get($locationId)) {
			return $holdLocation->holdingBranchLabel;
		}
		return false;
	}

	function getRequestFormFields($libraryId, $isStaffRequest = false) {
		require_once ROOT_DIR . '/sys/MaterialsRequestFormFields.php';
		$formFields            = new MaterialsRequestFormFields();
		$formFields->libraryId = $libraryId;
		$formFields->orderBy('weight');
		/** @var MaterialsRequestFormFields[] $fieldsToSortByCategory */
		$fieldsToSortByCategory = $formFields->fetchAll();

		// If no values set get the defaults.
		if (empty($fieldsToSortByCategory)) {
			$fieldsToSortByCategory = $formFields::getDefaultFormFields($libraryId);
		}

		if (!$isStaffRequest){
			foreach ($fieldsToSortByCategory as $fieldKey => $fieldDetails){
				if (in_array($fieldDetails->fieldType, array('assignedTo','createdBy','libraryCardNumber','id','status'))){
					unset($fieldsToSortByCategory[$fieldKey]);
				}
			}
		}

		// If we use another interface variable that is sorted by category, this should be a method in the Interface class
		$requestFormFields = array();
		if ($fieldsToSortByCategory) {
			foreach ($fieldsToSortByCategory as $formField) {
				if (!array_key_exists($formField->formCategory, $requestFormFields)) {
					$requestFormFields[$formField->formCategory] = array();
				}
				$requestFormFields[$formField->formCategory][] = $formField;
			}
		}
		return $requestFormFields;
	}

	function getAuthorLabelsAndSpecialFields($libraryId) {
		require_once ROOT_DIR . '/sys/MaterialsRequestFormats.php';
		return MaterialsRequestFormats::getAuthorLabelsAndSpecialFields($libraryId);
	}
}
