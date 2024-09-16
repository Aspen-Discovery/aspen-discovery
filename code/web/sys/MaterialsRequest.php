<?php

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
	public $holdsCreated;
	public $placeHoldWhenAvailable;
	public $illItem;
	public $holdPickupLocation;
	public $bookmobileStop;
	public $assignedTo;
	public $staffComments;

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

	static function getFormats(bool $activeFormatsOnly): array {
		require_once ROOT_DIR . '/sys/MaterialsRequestFormats.php';
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
		if ($activeFormatsOnly) {
			$customFormats->activeForNewRequests = 1;
		}

		if ($customFormats->count() == 0) {
			// Default Formats to use when no custom formats are created.

			/** @var MaterialsRequestFormats[] $defaultFormats */
			$defaultFormats = MaterialsRequestFormats::getDefaultMaterialRequestFormats($requestLibrary->libraryId);
			$availableFormats = [];

			global $configArray;
			foreach ($defaultFormats as $index => $materialRequestFormat) {
				$format = $materialRequestFormat->format;
				if (!isset($configArray['MaterialsRequestFormats'][$format]) || $configArray['MaterialsRequestFormats'][$format] != false) {
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

	static function enableAspenMaterialsRequest($forceReload = false) {
		if (MaterialsRequest::$materialsRequestEnabled != null && $forceReload == false) {
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

	/** @noinspection PhpUnused */
	function getHoldLocationName($locationId) {
		$holdLocation = new Location();
		if ($holdLocation->get($locationId)) {
			return $holdLocation->displayName;
		}
		return false;
	}

	function getRequestFormFields($libraryId, $isStaffRequest = false) {
		require_once ROOT_DIR . '/sys/MaterialsRequestFormFields.php';
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

		// If we use another interface variable that is sorted by category, this should be a method in the Interface class
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

	static function getRequestFormFieldsForApi($libraryId) {
		require_once ROOT_DIR . '/sys/MaterialsRequestFormFields.php';
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

	function getAuthorLabelsAndSpecialFields($libraryId) {
		require_once ROOT_DIR . '/sys/MaterialsRequestFormats.php';
		return MaterialsRequestFormats::getAuthorLabelsAndSpecialFields($libraryId);
	}

	/** @noinspection PhpUnused */
	function updateUsageTable() {
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->id = $this->status;
		if ($materialsRequestStatus->find(true)) {
			if ($materialsRequestStatus->isPurchased == 1) {
				require_once ROOT_DIR . '/sys/MaterialsRequestUsage.php';
				MaterialsRequestUsage::incrementStat($this->status, $this->libraryId);
			}
		}
	}

	function sendStatusChangeEmail() {
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
				$error = $mail->send($this->email, translate([
					'text' => "Your Materials Request Update",
					'isPublicFacing' => true,
				]), $body, $replyToAddress);
				if (($error instanceof AspenError)) {
					global $interface;
					$interface->assign('error', $error->getMessage());
				}
			}
		}
	}

	function sendStaffNewMaterialsRequestEmail() {
		global $configArray;
		global $interface;
		if ($this->getCreatedByUser() != false && $this->createdEmailSent == 0) {
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

	function sendStaffNewMaterialsRequestAssignedEmail() {
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

	static function sendStaffNewMaterialsRequestAssignedEmailBulk($numRequests, $user) {
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
		foreach($requestFormFields as $key => $formFields) {
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

	public function getDetails(User $user) {
		$homeLibrary = $user->getHomeLibrary();
		if(is_null($homeLibrary)) {
			global $library;
			$homeLibrary = $library;
		}

		$materialsRequest = new MaterialsRequest();
		$materialsRequest->id = $this->id;

		$statusQuery = new MaterialsRequestStatus();
		$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');

		// Pick-up Locations
		$locationQuery = new Location();
		$materialsRequest->joinAdd($locationQuery, 'LEFT', 'location', 'holdPickupLocation', 'locationId');

		// Format Labels
		$formats = new MaterialsRequestFormats();
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
				$defaultFormats = MaterialsRequestFormats::getDefaultMaterialRequestFormats();
				/** @var MaterialsRequestFormats $format */
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
	function getCreatedByFirstName() {
		if ($this->getCreatedByUser() != false) {
			return $this->_createdByUser->firstname;
		} else {
			return '';
		}
	}

	/** @noinspection PhpUnused */
	function getCreatedByLastName() {
		if ($this->getCreatedByUser() != false) {
			return $this->_createdByUser->lastname;
		} else {
			return '';
		}
	}

	/** @noinspection PhpUnused */
	function getCreatedByUserBarcode() {
		if ($this->getCreatedByUser() != false) {
			return $this->_createdByUser->getBarcode();
		} else {
			return '';
		}
	}

	/** @var User */
	protected $_createdByUser = null;

	function getCreatedByUser() {
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

	function getAssigneeUser() {
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
	function getAssigneeName() {
		if ($this->getAssigneeUser() != false) {
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
		//created  by
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
		$materialsRequestStatus = new MaterialsRequestStatus();
		$materialsRequestStatus->libraryId = $this->libraryId;
		$materialsRequestStatus->id = $this->status;
		if ($materialsRequestStatus->find(true)) {
			$links['status'] = $materialsRequestStatus->description;
		}

		return $links;
	}

	public function loadEmbeddedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting') {
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
			$requestStatus = new MaterialsRequestStatus();
			$requestStatus->libraryId = $this->libraryId;
			$requestStatus->description = $status;
			if ($requestStatus->find(true)) {
				$this->status = $requestStatus->id;
			}
		}
	}

	public function loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting = 'keepExisting'): bool {
		$result = parent::loadRelatedLinksFromJSON($jsonData, $mappings, $overrideExisting);
		return $result;
	}
}