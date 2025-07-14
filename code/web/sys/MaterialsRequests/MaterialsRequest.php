<?php /** @noinspection PhpMissingFieldTypeInspection */

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class MaterialsRequest extends DataObject {
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
	public $createdEmailSent;
	public $readyForHolds;
	public $selectedHoldCandidateId;
	public $holdsCreated;
	public $placeHoldWhenAvailable;
	public $illItem;
	public $holdPickupLocation;
	public $bookmobileStop;
	public $assignedTo;
	public $staffComments;

	public $holdFailureMessage;

	public $hasExistingRecord;
	public $lastCheckForExistingRecord;
	public $existingRecordUrl;

	protected $_holdCandidateRecords;
	protected $_selectedHoldCandidate;

	public static function getObjectStructure(string $context) : array {
		if ($context == 'requestsNeedingHolds') {
			return [
				'id' => [
					'property' => 'id',
					'type' => 'label',
					'label' => 'Request Id',
					'description' => 'The unique id of the request within the database',
					'uniqueProperty' => true,
				],
				'patronBarcode' => [
					'property' => 'patronBarcode',
					'type' => 'label',
					'label' => 'Patron Barcode',
					'description' => 'The requesting patron\'s barcode',
					'canSort' => false,
				],
				'title' => [
					'property' => 'title',
					'type' => 'label',
					'label' => 'Title',
					'description' => 'The title of the request',
				],
				'author' => [
					'property' => 'author',
					'type' => 'label',
					'label' => 'Author',
					'description' => 'The author of the request',
				],
				'displayFormat' => [
					'property' => 'displayFormat',
					'type' => 'label',
					'label' => 'Format',
					'description' => 'The format of the request',
					'canSort' => false,
				],
				'numHoldCandidates' => [
					'property' => 'numHoldCandidates',
					'type' => 'label',
					'label' => 'Num Hold Candidates',
					'description' => 'The number of hold candidates',
					'canSort' => false,
				],
				'selectedHoldCandidate' => [
					'property' => 'selectedHoldCandidate',
					'type' => 'label',
					'label' => 'Selected Hold Candidate',
					'description' => 'The hold candidate that will be used',
					'canSort' => false,
				],
				'holdFailureMessage' => [
					'property' => 'holdFailureMessage',
					'type' => 'label',
					'label' => 'Hold Failure Message',
					'description' => 'The error if any that occurred when placing the hold',
					'canSort' => false,
				]
			];
		}else{
			//This needs to be implemented and needs to be responsive to fields the library has set up
			return [];
		}
	}

	public function getUniquenessFields(): array {
		return ['id'];
	}

	public function getNumericColumnNames(): array {
		return [
			'emailSent',
			'holdsCreated',
			'assignedTo',
			'createdEmailSent'
		];
	}

	public function __get($name) {
		if ($name == 'patronBarcode') {
			return $this->getCreatedByUserBarcode();
		}elseif ($name == 'displayFormat') {
			return $this->getDisplayFormat();
		}elseif ($name == 'numHoldCandidates') {
			return count($this->getHoldCandidates());
		}elseif ($name == 'selectedHoldCandidate') {
			if ($this->selectedHoldCandidateId == 0) {
				return 'None';
			}else{
				$selectedHoldCandidate = $this->getSelectedHoldCandidate();
				if ($selectedHoldCandidate != null) {
					return $this->getSelectedHoldCandidate()->__toString();
				}else{
					return 'Invalid selection';
				}
			}
		}else{
			return parent::__get($name);
		}
	}

	static function getFormats(bool $activeFormatsOnly): array {
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestFormat.php';
		$customFormats = new MaterialsRequestFormat();
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
		if ($activeFormatsOnly) {
			$customFormats->activeForNewRequests = 1;
		}

		if ($customFormats->count() == 0) {
			// Default Formats to use when no custom formats are created.

			/** @var MaterialsRequestFormat[] $defaultFormats */
			$defaultFormats = MaterialsRequestFormat::getDefaultMaterialRequestFormats($requestLibrary->libraryId);
			$availableFormats = [];

			global $configArray;
			foreach ($defaultFormats as $materialRequestFormat) {
				$format = $materialRequestFormat->format;
				if (!isset($configArray['MaterialsRequestFormats'][$format]) || $configArray['MaterialsRequestFormats'][$format]) {
					$availableFormats[$format] = $materialRequestFormat->formatLabel;
				}
			}

		} else {
			$customFormats->orderBy('weight');
			$availableFormats = $customFormats->fetchAll('format', 'formatLabel');
		}

		return $availableFormats;
	}

	public function getDisplayFormat() : string {
		$formatObject = $this->getFormatObject();
		if ($formatObject !== false) {
			return $formatObject->formatLabel;
		}else{
			return 'Unknown';
		}
	}
	public function getFormatObject() : MaterialsRequestFormat|false {
		if (!empty($this->libraryId) && !empty($this->formatId)) {
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestFormat.php';
			$format = new MaterialsRequestFormat();
			$format->id = $this->formatId;
			$format->libraryId = $this->libraryId;
			if ($format->find(true)) {
				return $format;
			} else {
				foreach (MaterialsRequestFormat::getDefaultMaterialRequestFormats($this->libraryId) as $defaultFormat) {
					if ($this->format == $defaultFormat->format) {
						return $defaultFormat;
					}
				}
			}
		}
		return false;
	}

	public function getFormatObjectByFormat() : MaterialsRequestFormat|false {
		if (!empty($this->libraryId) && !empty($this->format)) {
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestFormat.php';
			$format = new MaterialsRequestFormat();
			$format->format = $this->format;
			$format->libraryId = $this->libraryId;
			if ($format->find(true)) {
				return $format;
			} else {
				foreach (MaterialsRequestFormat::getDefaultMaterialRequestFormats($this->libraryId) as $defaultFormat) {
					if ($this->format == $defaultFormat->format) {
						return $defaultFormat;
					}
				}
			}
		}
		return false;
	}

	static $materialsRequestEnabled = null;

	static function enableAspenMaterialsRequest($forceReload = false) : bool {
		if (MaterialsRequest::$materialsRequestEnabled != null && !$forceReload) {
			return MaterialsRequest::$materialsRequestEnabled;
		}
		global $library;

		$enableAspenMaterialsRequest = true;
		if ($library->enableMaterialsRequest != 1) {
			$enableAspenMaterialsRequest = false;
		} elseif (UserAccount::isLoggedIn()) {
			$homeLibrary = Library::getPatronHomeLibrary();
			if (is_null($homeLibrary)) {
				//User does not have a home library, this is likely an admin account.  Use the active library
				$homeLibrary = $library;
			}
			if ($homeLibrary->enableMaterialsRequest != 1) {
				$enableAspenMaterialsRequest = false;
			} elseif ($homeLibrary->libraryId != $library->libraryId) {
				$enableAspenMaterialsRequest = false;
			}
		}

		MaterialsRequest::$materialsRequestEnabled = $enableAspenMaterialsRequest;
		return $enableAspenMaterialsRequest;
	}

	function getHoldLocationName($locationId) : string|bool {
		$holdLocation = new Location();
		if ($holdLocation->get($locationId)) {
			return $holdLocation->displayName;
		}
		return false;
	}

	/**
	 * @return MaterialsRequestFormFields[]
	 */
	function getRequestFormFields($libraryId, $isStaffRequest = false) : array {
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestFormFields.php';
		$formFields = new MaterialsRequestFormFields();
		$formFields->libraryId = $libraryId;
		$formFields->orderBy('weight');
		/** @var MaterialsRequestFormFields[] $fieldsToSortByCategory */
		$fieldsToSortByCategory = $formFields->fetchAll();

		// If no values set get the defaults.
		if (empty($fieldsToSortByCategory)) {
			$fieldsToSortByCategory = $formFields::getDefaultFormFields($libraryId);
		}

		if (!$isStaffRequest) {
			foreach ($fieldsToSortByCategory as $fieldKey => $fieldDetails) {
				//Remove any fields that are available to staff only
				if (in_array($fieldDetails->fieldType, [
					'assignedTo',
					'createdBy',
					'libraryCardNumber',
					'id',
					'status',
					'staffComments',
				])) {
					unset($fieldsToSortByCategory[$fieldKey]);
				}
			}
		}

		// If we use another interface variable which is sorted by category, this should be a method in the Interface class
		$requestFormFields = [];
		if ($fieldsToSortByCategory) {
			foreach ($fieldsToSortByCategory as $formField) {
				if (!array_key_exists($formField->formCategory, $requestFormFields)) {
					$requestFormFields[$formField->formCategory] = [];
				}
				$requestFormFields[$formField->formCategory][] = $formField;
			}
		}
		return $requestFormFields;
	}

	/**
	 * @return MaterialsRequestFormFields[]
	 */
	static function getRequestFormFieldsForApi($libraryId) : array {
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestFormFields.php';
		$formFields = new MaterialsRequestFormFields();
		$formFields->libraryId = $libraryId;
		$formFields->orderBy('weight');
		/** @var MaterialsRequestFormFields[] $fieldsToSortByCategory */
		$fieldsToSortByCategory = $formFields->fetchAll();

		// If no values set get the defaults.
		if (empty($fieldsToSortByCategory)) {
			$fieldsToSortByCategory = $formFields::getDefaultFormFields($libraryId);
		}

		foreach ($fieldsToSortByCategory as $fieldKey => $fieldDetails) {
			//Remove any fields that are available to staff only
			if (in_array($fieldDetails->fieldType, [
				'assignedTo',
				'createdBy',
				'libraryCardNumber',
				'id',
				'status',
				'staffComments',
			])) {
				unset($fieldsToSortByCategory[$fieldKey]);
			}
		}

		$requestFormFields = [];
		if ($fieldsToSortByCategory) {
			foreach ($fieldsToSortByCategory as $formField) {
				if (!array_key_exists($formField->formCategory, $requestFormFields)) {
					$requestFormFields[$formField->formCategory] = [];
				}
				$requestFormFields[$formField->formCategory][] = $formField;
			}
		}

		return $requestFormFields;

	}

	function getAuthorLabelsAndSpecialFields($libraryId) : array {
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestFormat.php';
		return MaterialsRequestFormat::getAuthorLabelsAndSpecialFields($libraryId);
	}

	function sendStatusChangeEmail() : void {
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->id = $this->status;
		if ($materialsRequestStatus->find(true)) {
			if ($materialsRequestStatus->sendEmailToPatron == 1 && $this->email) {
				require_once ROOT_DIR . '/sys/Email/Mailer.php';
				$mail = new Mailer();

				$replyToAddress = $emailSignature = '';
				if (!empty($this->assignedTo)) {
					require_once ROOT_DIR . '/sys/Account/User.php';
					$staffSettings = new User();
					$staffSettings->id = $this->assignedTo;
					if ($staffSettings->find(true)) {
						if (!empty($staffSettings->materialsRequestReplyToAddress)) {
							$replyToAddress = $staffSettings->materialsRequestReplyToAddress;
						}
						if (!empty($staffSettings->materialsRequestEmailSignature)) {
							$emailSignature = $staffSettings->materialsRequestEmailSignature;
						}
					}
				}

				$body = '*****This is an auto-generated email response. Please do not reply.*****';
				$body .= "\r\n\r\n" . $materialsRequestStatus->emailTemplate;

				if (!empty($emailSignature)) {
					$body .= "\r\n\r\n" . $emailSignature;
				}

				//Replace tags with appropriate values
				$materialsRequestUser = new User();
				$materialsRequestUser->id = $this->createdBy;
				$materialsRequestUser->find(true);
				foreach ($materialsRequestUser as $fieldName => $fieldValue) {
					if (!is_array($fieldValue)) {
						$body = str_replace('{' . $fieldName . '}', $fieldValue, $body);
					}
				}
				foreach ($this as $fieldName => $fieldValue) {
					if (!is_array($fieldValue)) {
						$body = str_replace('{' . $fieldName . '}', $fieldValue, $body);
					}
				}
				$mailSent = $mail->send($this->email, translate([
					'text' => "Your Materials Request Update",
					'isPublicFacing' => true,
				]), $body, $replyToAddress);
				if (!$mailSent) {
					global $interface;
					$interface->assign('error', 'Could not send material request update');
				}
			}
		}
	}

	function sendStaffNewMaterialsRequestEmail() : void {
		global $configArray;
		global $interface;
		if ($this->getCreatedByUser() !== false && empty($this->createdEmailSent)) {
			$patronLibrary = $this->getCreatedByUser()->getHomeLibrary();
			if ($patronLibrary->materialsRequestSendStaffEmailOnNew && !empty($patronLibrary->materialsRequestNewEmail)) {
				$url = $configArray['Site']['url'] . '/MaterialsRequest/ManageRequests';
				require_once ROOT_DIR . '/sys/Email/Mailer.php';
				$mail = new Mailer();
				$replyToAddress = '';
				$subject = translate([
					'text' => "New Materials Request submitted",
					'isAdminFacing' => true,
					'isPublicFacing' => true
				]);
				$body = translate([
					'text' => 'Hi',
					'isAdminFacing' => true,
					'isPublicFacing' => true
				]);
				$body .= ', <br><br>';
				$body .= translate([
						'text' => "A new Materials Request has been submitted at %1%",
						1 => $patronLibrary->displayName,
						'isAdminFacing' => true,
						'isPublicFacing' => true,
					]) . ": <br>";
				$body .= $this->getEmailBody($patronLibrary->libraryId);
				$body .= "<br>";
				$body .= translate([
					'text' => "View more details online at %1%",
					1 => $url,
					'isAdminFacing' => true,
					'isPublicFacing' => true
				]);
				$body .= '<br>' . translate([
						'text' => 'Materials Request originated from',
						'isAdminFacing' => true,
						'isPublicFacing' => true
					]) . ' ' . $interface->getVariable('url');
				$body .= '<br><br>' . translate([
						'text' => 'Thanks',
						'isAdminFacing' => true,
						'isPublicFacing' => true
					]) . ', <br>' . $patronLibrary->displayName;
				$mail->send($patronLibrary->materialsRequestNewEmail, $subject, '', $replyToAddress, $body);
				$this->createdEmailSent = 1;
				$this->update();
			}
		}
	}

	function sendStaffNewMaterialsRequestAssignedEmail() : void {
		global $library;
		global $configArray;
		if($library->materialsRequestSendStaffEmailOnAssign) {
			$staffUser = new User();
			$staffUser->id = $this->assignedTo;
			if($staffUser->find(true)) {
				if($staffUser->materialsRequestSendEmailOnAssign && $staffUser->email) {
					$staffLibrary = $staffUser->getHomeLibrary();
					if (is_null($staffLibrary)) {
						$staffLibrary = $library;
					}
					$url = $configArray['Site']['url'] . '/MaterialsRequest/ManageRequests';
					require_once ROOT_DIR . '/sys/Email/Mailer.php';
					$mail = new Mailer();
					$subject = translate([
						'text' => "You've been assigned a Materials Request",
						'isAdminFacing' => true,
						'isPublicFacing' => true]);
					$body = translate([
							'text' => 'Hi',
							'isAdminFacing' => true,
							'isPublicFacing' => true
						]);
					$body .= ", <br><br>";
					$body .= translate([
						'text' => "You've been assigned a Materials Request",
							'isAdminFacing' => true,
							'isPublicFacing' => true])
						. ": <br>";
					$body .= $this->getEmailBody($staffLibrary->libraryId);
					$body .= "<br>" . translate([
						'text' => "View more details online at",
							'isAdminFacing' => true,
							'isPublicFacing' => true])
						. ' ' . $configArray['Site']['url'] . '/MaterialsRequest/ManageRequests';
					$body .= '<br><br>' . translate([
							'text' => 'Thanks',
							'isAdminFacing' => true,
							'isPublicFacing' => true
						]) . ', <br>' . $staffLibrary->displayName;
					$mail->send($staffUser->email, $subject, '', '', $body);
				}
			}
		}
	}

	static function sendStaffNewMaterialsRequestAssignedEmailBulk($numRequests, $user) : void {
		global $library;
		global $configArray;
		if($library->materialsRequestSendStaffEmailOnAssign) {
			$staffUser = new User();
			$staffUser->id = $user;
			if($staffUser->find(true)) {
				if($staffUser->materialsRequestSendEmailOnAssign && $staffUser->email) {
					$staffLibrary = $staffUser->getHomeLibrary();
					if (is_null($staffLibrary)) {
						$staffLibrary = $library;
					}
					$url = $configArray['Site']['url'] . '/MaterialsRequest/ManageRequests';
					require_once ROOT_DIR . '/sys/Email/Mailer.php';
					$mail = new Mailer();
					$subject = translate([
						'text' => "You've been assigned %1% Materials Requests at %2%",
						1 => $numRequests,
						2 => $library->displayName,
						'isAdminFacing' => true,
						'isPublicFacing' => true
					]);
					$body = translate([
						'text' => "Hi",
						'isAdminFacing' => true,
						'isPublicFacing' => true
					]);
					$body .= ", <br><br>";
					$body .= translate([
						'text' => "You've been assigned %1% Materials Requests. You can view more details online at %2%",
						1 => $numRequests,
						2 => $url,'isAdminFacing' => true,
						'isPublicFacing' => true
					]);
					$body .= "<br><br>";
					$body .= translate([
						'text' => 'Thanks',
						'isAdminFacing' => true,
						'isPublicFacing' => true
					]) . ', <br>' . $staffLibrary->displayName;
					$mail->send($staffUser->email, $subject, '', '', $body);
				}
			}
		}
	}

	function getEmailBody($libraryId): string {
		$requestFormFields = $this->getRequestFormFields($libraryId, true);
		$body = '<table style="border: 1px solid black; border-collapse: collapse; margin-top: 5px; margin-bottom: 5px; width: 100%"><tbody>';
		foreach($requestFormFields as $formFields) {
			foreach($formFields as $formField) {
				$value = $formField->fieldType;
				if($this->$value) {
					if ($formField->fieldType == 'format' || $formField->fieldType == 'author' || $formField->fieldType == 'title' || $formField->fieldType == 'id') {
						$body .= '<tr>';
						$body .= '<td style="border: 1px solid black; border-collapse: collapse; padding: 5px; width: 25%"><strong>' . translate([
								'text' => $formField->fieldLabel,
								'isPublicFacing' => true,
								'isAdminFacing' => true
							]) . '</strong></td>';
						$body .= '<td style="border: 1px solid black; border-collapse: collapse; padding: 5px">' . translate([
								'text' => $this->$value,
								'isPublicFacing' => true,
								'isAdminFacing' => true
							]) . '</td>';
						$body .= '</tr>';
					}
				}
			}
		}
		$body .= '</tbody></table>';
		return $body;
	}

	public function getDetails(User $user) : MaterialsRequest{
		$homeLibrary = $user->getHomeLibrary();
		if(is_null($homeLibrary)) {
			global $library;
			$homeLibrary = $library;
		}

		$materialsRequest = new MaterialsRequest();
		$materialsRequest->id = $this->id;

		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
		$statusQuery = new MaterialsRequestStatus();
		$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');

		// Pick-up Locations
		$locationQuery = new Location();
		$materialsRequest->joinAdd($locationQuery, 'LEFT', 'location', 'holdPickupLocation', 'locationId');

		// Format Labels
		$formats = new MaterialsRequestFormat();
		$formats->libraryId = $homeLibrary->libraryId;
		$usingDefaultFormats = $formats->count() == 0;

		$materialsRequest->selectAdd();
		$materialsRequest->selectAdd('materials_request.*, status.description as statusLabel, location.displayName as location');
		if (!$usingDefaultFormats) {
			$materialsRequest->joinAdd($formats, 'LEFT', 'materials_request_formats', 'formatId', 'id');
			$materialsRequest->selectAdd('materials_request_formats.formatLabel,materials_request_formats.authorLabel, materials_request_formats.specialFields');
		}

		if($materialsRequest->find(true)) {
			if ($usingDefaultFormats) {
				$defaultFormats = MaterialsRequestFormat::getDefaultMaterialRequestFormats();
				/** @var MaterialsRequestFormat $format */
				foreach ($defaultFormats as $format) {
					if ($materialsRequest->format == $format->format) {
						/** @noinspection PhpUndefinedFieldInspection */
						$materialsRequest->formatLabel = $format->formatLabel;
						/** @noinspection PhpUndefinedFieldInspection */
						$materialsRequest->authorLabel = $format->authorLabel;
						/** @noinspection PhpUndefinedFieldInspection */
						$materialsRequest->specialFields = $format->specialFields;
						break;
					}
				}
			}
		}

		return $materialsRequest;
	}

	/** @noinspection PhpUnused */
	function getCreatedByFirstName() : string {
		if ($this->getCreatedByUser()) {
			return $this->_createdByUser->firstname;
		} else {
			return '';
		}
	}

	/** @noinspection PhpUnused */
	function getCreatedByLastName() : string {
		if ($this->getCreatedByUser()) {
			return $this->_createdByUser->lastname;
		} else {
			return '';
		}
	}

	/** @noinspection PhpUnused */
	function getCreatedByUserBarcode() : string {
		if ($this->getCreatedByUser()) {
			return $this->_createdByUser->getBarcode();
		} else {
			return '';
		}
	}

	/** @var User */
	protected $_createdByUser = null;

	function getCreatedByUser() : User|false {
		if ($this->_createdByUser == null) {
			$this->_createdByUser = new User();
			$this->_createdByUser->id = $this->createdBy;
			if (!$this->_createdByUser->find(true)) {
				$this->_createdByUser = false;
			}
		}
		return $this->_createdByUser;
	}

	/** @var User */
	protected $_assigneeUser = null;

	function getAssigneeUser() : User|false {
		if ($this->_assigneeUser == null) {
			if (empty($this->assignedTo)) {
				$this->_assigneeUser = false;
			} else {
				$this->_assigneeUser = new User();
				$this->_assigneeUser->id = $this->assignedTo;
				if (!$this->_assigneeUser->find(true)) {
					$this->_assigneeUser = false;
				}
			}
		}
		return $this->_assigneeUser;
	}

	/** @noinspection PhpUnused */
	function getAssigneeName() : string {
		if ($this->getAssigneeUser() !== false) {
			return $this->_assigneeUser->displayName;
		} else {
			return '';
		}
	}

	public function okToExport(array $selectedFilters): bool {
		$okToExport = parent::okToExport($selectedFilters);
		if (in_array($this->libraryId, $selectedFilters['libraries'])) {
			$okToExport = true;
		}
		return $okToExport;
	}

	public function toArray($includeRuntimeProperties = true, $encryptFields = false): array {
		$return = parent::toArray($includeRuntimeProperties, $encryptFields);
		unset ($return['libraryId']);
		unset ($return['createdBy']);
		unset ($return['assignedTo']);

		return $return;
	}

	public function getLinksForJSON(): array {
		$links = parent::getLinksForJSON();
		//library
		$allLibraries = Library::getLibraryListAsObjects(false);
		if (array_key_exists($this->libraryId, $allLibraries)) {
			$library = $allLibraries[$this->libraryId];
			$links['library'] = empty($library->subdomain) ? $library->ilsCode : $library->subdomain;
		}
		//created by
		$user = new User();
		$user->id = $this->createdBy;
		if ($user->find(true)) {
			$links['createdBy'] = $user->ils_barcode;
		}
		//assigned to
		$user = new User();
		$user->id = $this->assignedTo;
		if ($user->find(true)) {
			$links['assignedTo'] = $user->ils_barcode;
		}
		//Status
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->libraryId = $this->libraryId;
		$materialsRequestStatus->id = $this->status;
		if ($materialsRequestStatus->find(true)) {
			$links['status'] = $materialsRequestStatus->description;
		}

		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') : void {
		parent::loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting');

		if (isset($jsonData['library'])) {
			$allLibraries = Library::getLibraryListAsObjects(false);
			$subdomain = $jsonData['library'];
			if (array_key_exists($subdomain, $mappings['libraries'])) {
				$subdomain = $mappings['libraries'][$subdomain];
			}
			foreach ($allLibraries as $tmpLibrary) {
				if ($tmpLibrary->subdomain == $subdomain || $tmpLibrary->ilsCode == $subdomain) {
					$this->libraryId = $tmpLibrary->libraryId;
					break;
				}
			}
		}
		if (isset($jsonData['createdBy'])) {
			$username = $jsonData['createdBy'];
			$user = new User();
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				$this->createdBy = $user->id;
			}
		}
		if (isset($jsonData['assignedTo'])) {
			$username = $jsonData['assignedTo'];
			$user = new User();
			$user->ils_barcode = $username;
			if ($user->find(true)) {
				$this->assignedTo = $user->id;
			}
		}
		if (isset($jsonData['status'])) {
			$status = $jsonData['status'];
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
			$requestStatus = new MaterialsRequestStatus();
			$requestStatus->libraryId = $this->libraryId;
			$requestStatus->description = $status;
			if ($requestStatus->find(true)) {
				$this->status = $requestStatus->id;
			}
		}
	}

	/**
	 * @return MaterialsRequestHoldCandidate[]
	 */
	public function getHoldCandidates() : array {
		if ($this->_holdCandidateRecords == null) {
			if (!empty($this->id)) {
				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestHoldCandidate.php';
				$holdCandidate = new MaterialsRequestHoldCandidate();
				$holdCandidate->requestId = $this->id;
				$this->_holdCandidateRecords = $holdCandidate->fetchAll();
			}else{
				$this->_holdCandidateRecords = [];
			}
		}
		return $this->_holdCandidateRecords;
	}

	public function canActiveUserEdit(): bool {
		global $action;
		if (!empty($action) && $action == 'RequestsNeedingHolds'){
			return false;
		}else{
			return parent::canActiveUserEdit();
		}
	}

	function getAdditionalListActions(): array {
		$objectActions = [];

		$holdCandidates = $this->getHoldCandidates();
		if (count($holdCandidates) > 1) {
			$objectActions[] = [
				'text' => 'Select Hold Candidate',
				'onclick' => "return AspenDiscovery.MaterialsRequest.showSelectHoldCandidateForm('$this->id')",
				'url' => '',
			];
		}
		if ($this->selectedHoldCandidateId > 0) {
			$objectActions[] = [
				'text' => 'Place Hold',
				'url' => "/MaterialsRequest/RequestsNeedingHolds?objectAction=placeSelectedHolds&selectedObject[$this->id]=on",
				'onclick' => "AspenDiscovery.showMessage('" . translate(['text'=>'Placing Holds', 'isAdminFacing'=>true]) . ", ". translate(['text'=>'Placing holds on the selected title(s)', 'isAdminFacing'=>true]) . ")"
			];
		}

		return $objectActions;
	}

	public function getSelectedHoldCandidate() : MaterialsRequestHoldCandidate|bool{
		if ($this->_selectedHoldCandidate === null) {
			if ($this->selectedHoldCandidateId > 0) {
				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestHoldCandidate.php';
				$holdCandidate = new MaterialsRequestHoldCandidate();
				$holdCandidate->id = $this->selectedHoldCandidateId;
				if ($holdCandidate->find(true)) {
					$this->_selectedHoldCandidate = $holdCandidate;
				}else{
					$this->_selectedHoldCandidate = false;
				}
			}else{
				$this->_selectedHoldCandidate = false;
			}
		}
		return $this->_selectedHoldCandidate;
	}

	/** @noinspection PhpUnused */
	public function automaticCheckForExistingRecordNeedsToBeDone() : bool {
		//Automatically check for existing records if it has been an hour and an existing record with the same format has not been found.
		return ((time() - $this->lastCheckForExistingRecord) > 60 * 60) && !$this->hasExistingRecord;
	}

	public function insert($context = '') : int|bool {
		$ret = parent::insert($context);
		if ($ret) {
			$this->sendStaffNewMaterialsRequestEmail();
		}
		return $ret;
	}
}
