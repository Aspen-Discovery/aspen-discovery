<?php

class CatalogConnection {
	/**
	 * A boolean value that defines whether a connection has been successfully
	 * made.
	 *
	 * @access public
	 * @var    bool
	 */
	public $status = false;

	/** @var AccountProfile */
	public $accountProfile;

	/**
	 * The object of the appropriate driver.
	 *
	 * @access private
	 * @var    AbstractIlsDriver
	 */
	public $driver;

	/**
	 * Constructor
	 *
	 * This is responsible for instantiating the driver that has been specified.
	 *
	 * @param string $driver The name of the driver to load.
	 * @param AccountProfile $accountProfile
	 * @throws PDOException error if we cannot connect to the driver.
	 *
	 * @access public
	 */
	public function __construct($driver, $accountProfile) {
		$path = ROOT_DIR . "/Drivers/{$driver}.php";
		if (is_readable($path) && $driver != 'AbstractIlsDriver') {
			require_once $path;

			try {
				$this->driver = new $driver($accountProfile);
			} catch (PDOException $e) {
				global $logger;
				$logger->log("Unable to create driver $driver for account profile {$accountProfile->name}", Logger::LOG_ERROR);
				throw $e;
			}

			$this->accountProfile = $accountProfile;
			$this->status = true;
		}
	}

	/**
	 * Check Function
	 *
	 * This is responsible for checking the driver configuration to determine
	 * if the system supports a particular function.
	 *
	 * @param string $function The name of the function to check.
	 *
	 * @return mixed On success, an associative array with specific function keys
	 * and values; on failure, false.
	 * @access public
	 */
	public function checkFunction($function) {
		// Extract the configuration from the driver if available:
		$functionConfig = method_exists($this->driver, 'getConfig') ? $this->driver->getConfig($function) : false;

		// See if we have a corresponding check method to analyze the response:
		$checkMethod = "_checkMethod" . $function;
		if (!method_exists($this, $checkMethod)) {
			//Just see if the method exists on the driver
			return method_exists($this->driver, $function);
		}

		// Send back the settings:
		return $this->$checkMethod($functionConfig);
	}

	/**
	 * Patron Login
	 *
	 * This is responsible for authenticating a patron against the catalog.
	 *
	 * @param string $username The patron username
	 * @param string $password The patron password
	 * @param User $parentAccount A parent account that we are linking from if any
	 * @param boolean $validatedViaSSO True if the patron has already been validated via SSO.  If so we don't need to validation, just retrieve information
	 *
	 * @return User|AspenError|null     User object or null if the user cannot be logged in
	 * @access public
	 */
	public function patronLogin($username, $password, $parentAccount = null, $validatedViaSSO = false) {
		global $offlineMode;
		global $loginAllowedWhileOffline;

		$barcodesToTest = [];
		$barcodesToTest[$username] = $username;
		if ($this->accountProfile->authenticationMethod != 'db') {
			$barcodesToTest[preg_replace('/[^a-zA-Z\d]/', '', trim($username))] = preg_replace('/[^a-zA-Z\d]/', '', trim($username));
			//Special processing to allow users to login with short barcodes
			global $library;
			if ($library) {
				if ($library->barcodePrefix) {
					if (strpos($username, $library->barcodePrefix) !== 0) {
						//Add the barcode prefix to the barcode
						$barcodesToTest[$library->barcodePrefix . $username] = $library->barcodePrefix . $username;
					}
				}
			}
		}
		$barcodesToTest = array_unique($barcodesToTest);

		//Get the existing user from the database.  This validates that the username and password on record are correct
		$user = null;
		foreach ($barcodesToTest as $barcode) {
			$user = $this->getUserFromDatabase($barcode, $password, $username);
			if ($user != null) {
				break;
			}
		}

		if ($offlineMode && !$loginAllowedWhileOffline) {
			if ($user == null) {
				return null;
			}
		} else {
			if ($user != null) {
				//If we have a valid patron, only revalidate every 15 minutes
				//Make sure the password is correct though
				if ($user->lastLoginValidation < (time() - 15 * 60)) {
					$doPatronLogin = true;
				} else {
					//If the password is the same, we're still ok
					if ($user->cat_password == $password) {
						$doPatronLogin = false;
					} else {
						$doPatronLogin = true;
					}
				}
			} else {
				$doPatronLogin = true;
			}
			if ($doPatronLogin || isset($_REQUEST['reload'])) {
				//Catalog is online, do the login
				$user = $this->driver->patronLogin($username, $password, $validatedViaSSO);
				if ($user && !($user instanceof AspenError)) {
					try {
						$user->lastLoginValidation = time();
						$user->update();
					} catch (Exception $e) {
						//This happens before database update
					}
				}
			}
		}

		if ($user && !($user instanceof AspenError)) {
			$invalidUser = false;
			if (!empty($user->firstname) && (strip_tags($user->firstname) != $user->firstname)) {
				$invalidUser = true;
			} elseif (!empty($user->lastname) && (strip_tags($user->lastname) != $user->lastname)) {
				$invalidUser = true;
			} elseif (!empty($user->email) && (strip_tags($user->email) != $user->email)) {
				$invalidUser = true;
			} elseif (!empty($user->username) && (strip_tags($user->username) != $user->username)) {
				$invalidUser = true;
			} elseif (!empty($user->ils_username) && (strip_tags($user->ils_username) != $user->ils_username)) {
				$invalidUser = true;
			} elseif (!empty($user->ils_barcode) && (strip_tags($user->ils_barcode) != $user->ils_barcode)) {
				$invalidUser = true;
			} elseif (!empty($user->displayName) && (strip_tags($user->displayName) != $user->displayName)) {
				$invalidUser = true;
			} elseif (!empty($user->phone) && (strip_tags($user->phone) != $user->phone)) {
				$invalidUser = true;
			} elseif (!empty($user->patronType) && (strip_tags($user->patronType) != $user->patronType)) {
				$invalidUser = true;
			}
			if ($invalidUser) {
				return new AspenError('Invalid User Account Information, please contact your library');
			}

			if ($parentAccount) {
				$user->setParentUser($parentAccount);
			} // only set when the parent account is passed.

			//Record stats to show the user logged in
			$indexingProfile = $this->accountProfile->getIndexingProfile();
			if ($indexingProfile != null) {
				require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
				$userUsage = new UserILSUsage();
				global $aspenUsage;
				$userUsage->instance = $aspenUsage->getInstance();
				$userUsage->userId = $user->id;
				$userUsage->indexingProfileId = $this->accountProfile->getIndexingProfile()->id;
				$userUsage->year = date('Y');
				$userUsage->month = date('n');
				if (!$userUsage->find(true)) {
					$userUsage->insert();
				}
			}
		}

		return $user;
	}

	/**
	 * @param string $patronBarcode
	 * @param string $patronUsername
	 * @return bool|User
	 */
	public function findNewUser(string $patronBarcode, string $patronUsername) {
		return $this->driver->findNewUser($patronBarcode, $patronUsername);
	}

	/**
	 * @param string $patronEmail
	 * @return bool|User
	 */
	public function findNewUserByEmail(string $patronEmail) {
		return $this->driver->findNewUserByEmail($patronEmail);
	}

	/**
	 * @param string $field
	 * @param string $value
	 * @return bool|User
	 */
	public function findUserByField(string $field, string $value) {
		return $this->driver->findUserByField($field, $value);
	}

	/**
	 * @param User $user
	 */
	public function updateUserWithAdditionalRuntimeInformation($user) {
		global $timer;
		$timer->logTime("Starting to Update Additional Runtime information for user " . $user->id);

		$homeLibrary = Library::getLibraryForLocation($user->homeLocationId);
		if ($homeLibrary) {
			if ($homeLibrary->enableMaterialsRequest == 1) {
				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
				require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
				$materialsRequest = new MaterialsRequest();
				$materialsRequest->createdBy = $user->id;
				$statusQuery = new MaterialsRequestStatus();
				$statusQuery->whereAdd('(isOpen = 1 OR isActive = 1)');
				$statusQuery->libraryId = $homeLibrary->libraryId;
				$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
				$materialsRequest->find();
				$user->setNumMaterialsRequests($materialsRequest->getNumResults());
				$timer->logTime("Updated number of active materials requests");
			} elseif ($homeLibrary->enableMaterialsRequest == 2) {
				$user->setNumMaterialsRequests($this->getNumMaterialsRequests($user));
			}
		}

		$timer->logTime("Updated Additional Runtime information for user " . $user->id);
	}

	public function getRegistrationCapabilities(): array {
		return $this->driver->getRegistrationCapabilities();
	}

	/**
	 * Check to see if an account exists within the ILS for the given email and if so email the patron with the barcode.
	 * The email should be pre-validated when calling this method.
	 * @param string $email - The email address to look for
	 * @return array
	 */
	public function lookupAccountByEmail($email): array {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'The email supplied was not valid.',
					'isPublicFacing' => true
				])
			];
		} else {
			$cardsByEmail = $this->driver->lookupAccountByEmail($email);
			if ($cardsByEmail['success']) {
				$sendMessageToPatron = true;
				if (isset($_REQUEST['bypassPatronMessaging'])) {
					$sendMessageToPatron = !filter_var($_REQUEST['bypassPatronMessaging'], FILTER_VALIDATE_BOOLEAN);
				}
				if ($sendMessageToPatron) {
					//Email the list to the patron's email
					require_once ROOT_DIR . '/sys/Email/Mailer.php';
					$eMailer = new Mailer();
					$accountInfo = '';
					foreach ($cardsByEmail['accountInformation'] as $cardInfo) {
						$accountInfo .= $cardInfo['name'] . ' - ' . $cardInfo['cardNumber'] . "\n";
					}
					$body = translate([
						'text' => "You recently requested your account information. Based on the email entered, the following barcodes were found.\n\n%1%\nIf you did not request this information, please contact the library.",
						'isPublicFacing' => true,
						1 => $accountInfo
					]);


					$result = $eMailer->send($email, translate([
						'text' => "Your library account",
						'isPublicFacing' => true,
					]), $body);
					if ($result) {
						$cardsByEmail['message'] = translate([
							'text' => 'An email containing your library card was sent to %1%.',
							'isPublicFacing' => true,
							1 => $email
						]);
					} else {
						$cardsByEmail['message'] = translate([
							'text' => 'We could not send your barcode to %1%, please contact the library to retrieve your library card.',
							'isPublicFacing' => true,
							1 => $email
						]);
					}
				}
				return $cardsByEmail;
			} else {
				return $cardsByEmail;
			}
		}
	}

	public function lookupAccountByPhoneNumber($phone): array {
		if (strlen($phone) >= 7 && strlen($phone) <= 11) {
			$result = $this->driver->lookupAccountByPhoneNumber($phone);
			if ($result['success']) {
				global $library;
				if ($library->twilioSettingId != -1) {
					$accountInfo = '';
					foreach ($result['accountInformation'] as $cardInfo) {
						$accountInfo .= $cardInfo['name'] . ' - ' . $cardInfo['cardNumber'] . "\n";
					}
					$body = translate([
						'text' => "Your library account(s)\n%1%",
						'isPublicFacing' => true,
						1 => $accountInfo
					]);

					//Check to see if we should send a notification
					$sendMessageToPatron = true;
					if (isset($_REQUEST['bypassPatronMessaging'])) {
						$sendMessageToPatron = !filter_var($_REQUEST['bypassPatronMessaging'], FILTER_VALIDATE_BOOLEAN);
					}
					if ($sendMessageToPatron) {
						require_once ROOT_DIR . '/sys/SMS/TwilioSetting.php';
						$twilioSetting = new TwilioSetting();
						$twilioSetting->id = $library->twilioSettingId;
						if ($twilioSetting->find(true)) {
							$isNumberValid = $twilioSetting->validatePhoneNumber($phone);
							if ($isNumberValid) {
								$smsResponse = $twilioSetting->sendMessage($body, $phone);
								if ($smsResponse['success']) {
									$result['message'] = translate([
										'text' => 'A message containing your library card was sent to %1%.',
										'isPublicFacing' => true,
										1 => $phone
									]);
								} else {
									$result['message'] = translate([
										'text' => 'We could not send a message to %1%. Please contact the library to retrieve your library card.',
										'isPublicFacing' => true,
										1 => $phone
									]);
								}
							} else {
								$result['message'] = translate([
									'text' => 'We could not send a message to %1%. Please contact the library to retrieve your library card.',
									'isPublicFacing' => true,
									1 => $phone
								]);
							}
						} else {
							$result['message'] = translate([
								'text' => 'We could not send a message to %1%. Please contact the library to retrieve your library card.',
								'isPublicFacing' => true,
								1 => $phone
							]);
						}
					}
				}
				return $result;
			} else {
				return $result;
			}
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'The phone number supplied was not valid.',
					'isPublicFacing' => true
				])
			];
		}
	}

	public function getBasicRegistrationForm(): array {
		return $this->driver->getBasicRegistrationForm();
	}

	public function processBasicRegistrationForm(bool $addressValidated): array {
		return $this->driver->processBasicRegistrationForm($addressValidated);
	}

	/**
	 * @param mixed $barcode
	 * @return array
	 */
	private function sendAspenPasswordResetEmailForBarcode(array $accountProfileInfo, mixed $barcode): array {
		$result = [
			'success' => false,
			'foundPatron' => false,
			'error' => translate([
				'text' => "Unknown error sending password reset.",
				'isPublicFacing' => true,
			]),
		];

		global $library;
		$userToResetPin = false;
		$accountProfile = $accountProfileInfo['accountProfile'];
		if ($library->accountProfileId == $accountProfile->id) {
			//Check the ILS we are connected to
			$userToResetPin = new User();
			$userToResetPin->source = $accountProfile->name;
			$userToResetPin->ils_barcode = $barcode;
			if (!$userToResetPin->find(true)) {
				$accountProfileDriver = $accountProfileInfo['driver'];
				require_once ROOT_DIR . '/CatalogFactory.php';
				$catalogConnectionInstance = CatalogFactory::getCatalogConnectionInstance($accountProfileDriver, $accountProfile);
				$userToResetPin = $catalogConnectionInstance->findNewUser($barcode, '');
			}
		}elseif ($accountProfile->authenticationMethod == 'db') {
			//Check anything we do database authentication for
			$userToResetPin = new User();
			$userToResetPin->source = $accountProfile->name;
			$userToResetPin->username = $barcode;
			if (!$userToResetPin->find(true)) {
				$userToResetPin = false;
			}
		}

		if ($userToResetPin === false) {
			$result['error'] = translate([
				'text' => "Could not find a patron with that barcode, please contact the library.",
				'isPublicFacing' => true,
			]);
		} else {
			$result['foundPatron'] = true;
			if (empty($userToResetPin->email)) {
				$result['error'] = translate([
					'text' => "That account does not have an email associated with it, please contact the library.",
					'isPublicFacing' => true,
				]);
			} else {
				require_once ROOT_DIR . '/sys/Account/PinResetToken.php';
				$pinResetToken = new PinResetToken();
				$pinResetToken->userId = $userToResetPin->id;
				$pinResetToken->generateToken();
				$pinResetToken->dateIssued = time();
				if ($pinResetToken->insert()) {
					require_once ROOT_DIR . '/sys/Email/Mailer.php';
					$mailer = new Mailer();

					global $configArray;
					$resetUrl = $configArray['Site']['url'] . '/MyAccount/CompletePinReset?token=' . $pinResetToken->token;
					$subject = translate([
						'text' => 'Reset PIN',
						'isPublicFacing' => true,
					]);
					$body = translate([
						'text' => 'Hi %1%,',
						1 => $userToResetPin->firstname,
						'isPublicFacing' => true,
					]);
					$body .= "\r\n" . translate([
							'text' => 'It looks like you forgot your PIN. Click on the link or copy/paste the URL below into a browser to reset your password. This link will only work for 60 minutes, after that you’ll have to request a new link.',
							'isPublicFacing' => true,
						]);
					$body .= "\r\n\r\n" . $resetUrl;
					$body .= "\r\n" . translate([
							'text' => 'You can also paste the following code into the page where you generated the reset.',
							'isPublicFacing' => true,
						]);
					$body .= "\r\n\r\n" . $pinResetToken->token;

					/** @noinspection HtmlRequiredLangAttribute */
					$htmlBody = "<html><table><tr><td>" . translate([
							'text' => 'Hi %1%,',
							1 => $userToResetPin->firstname,
							'isPublicFacing' => true,
						]) . '<br/>';
					$htmlBody .= translate([
							'text' => 'It looks like you forgot your PIN. Click on the button or copy/paste the URL below into a browser to reset your password. This link will only work for 60 minutes, after that you’ll have to request a new link',
							'isPublicFacing' => true,
							1 => $userToResetPin->firstname,
						]) . "</td>";
					$htmlBody .= "<tr><td style='text-align: center'><a href='{$resetUrl}'>" . translate([
							'text' => 'CREATE NEW PIN',
							'isPublicFacing' => true,
						]) . '</a></td></tr>';
					$htmlBody .= '<tr><td></td></tr>';
					$htmlBody .= "<tr><td style='text-align: center'>" . translate([
							'text' => 'Reset Token',
							'isPublicFacing' => true,
						]) . '</tr>';
					$htmlBody .= "<tr><td style='text-align: center'>" . $pinResetToken->token . '</td></tr>';
					$htmlBody .= '</table></html>';


					if ($mailer->send($userToResetPin->email, $subject, $body, null, $htmlBody)) {
						$result['success'] = true;
						$result['message'] = translate([
							'text' => "The email with your PIN reset link was sent. Please click on the link within that email or enter the code below.",
							'isPublicFacing' => true,
						]);
					} else {
						$result['error'] = translate([
							'text' => "The email with your PIN reset link could not be sent, please contact the library.",
							'isPublicFacing' => true,
						]);
					}
				} else {
					$result['error'] = translate([
						'text' => "Could not generate PIN reset token.",
						'isPublicFacing' => true,
					]);
				}
			}
		}
		return $result;
	}

	public function isBlockedFromIllRequests(User $user) {
		return $this->driver->isBlockedFromIllRequests($user);
	}

	/**
	 * @param $nameFromUser  string
	 * @param $nameFromIls   string
	 * @return boolean
	 */
	private function areNamesSimilar($nameFromUser, $nameFromIls) {
		$fullName = str_replace(",", " ", $nameFromIls);
		$fullName = str_replace(";", " ", $fullName);
		$fullName = str_replace(";", "'", $fullName);
		$fullName = preg_replace("/\\s{2,}/", " ", $fullName);
		$allNameComponents = preg_split('^[\s-]^', strtolower($fullName));

		//Get the first name that the user supplies.
		//This expects the user to enter one or two names and only
		//Validates the first name that was entered.
		$enteredNames = preg_split('^[\s-]^', strtolower($nameFromUser));
		$userValid = false;
		foreach ($enteredNames as $name) {
			if (in_array($name, $allNameComponents, false)) {
				$userValid = true;
				break;
			}
		}
		return $userValid;
	}

	/**
	 * Get Patron Transactions
	 *
	 * This is responsible for retrieving all transactions (i.e. checked out items)
	 * by a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 * @return Checkout[]            Array of the patron's transactions on success,
	 * AspenError otherwise.
	 * @access public
	 */
	public function getCheckouts(User $user): array {
		$accountSummary = $user->getCachedAccountSummary('ils');
		$cachedCheckouts = $user->getCachedCheckoutsForSource('ils');
		if ($accountSummary->areCheckoutsStale() || isset($_REQUEST['reload']) || isset($_REQUEST['refreshCheckouts'])) {
			$checkouts = $this->driver->getCheckouts($user);

			$cachedCheckouts = $this->driver->updateCachedCheckoutsBasedOnActiveCheckouts($cachedCheckouts, $checkouts, $accountSummary);
		}
		return $cachedCheckouts;
	}

	/**
	 * Get Patron Fines
	 *
	 * This is responsible for retrieving all fines by a specific patron.
	 *
	 * @param User $patron The patron from patronLogin
	 * @param bool $includeMessages Whether messages should be included in the output
	 *
	 * @return mixed        Array of the patron's fines on success, AspenError
	 * otherwise.
	 * @access public
	 */
	public function getFines(User $patron, bool $includeMessages = false): array {
		$fines = $this->driver->getFines($patron, $includeMessages);
		foreach ($fines as &$fine) {
			if (!array_key_exists('canPayFine', $fine)) {
				$fine['canPayFine'] = true;
			}
		}
		return $fines;
	}

	/**
	 * Get Reading History for the user account.
	 *
	 * @param User $patron
	 * @param int $page
	 * @param int $recordsPerPage
	 * @param string $sortOption
	 * @param string $filter
	 * @param bool $forExport
	 * @return array Array of the patron's reading list.
	 * @access  public
	 */
	function getReadingHistory(User $patron, int $page = 1, int $recordsPerPage = 20, string $sortOption = "checkedOut", string $filter = "", bool $forExport = false): array {
		global $timer;
		global $offlineMode;
		$timer->logTime("Starting to load reading history.");

		$result = [
			'historyActive' => $patron->trackReadingHistory,
			'titles' => [],
			'numTitles' => 0,
		];
		if (!$patron->trackReadingHistory) {
			return $result;
		}
		if (!$offlineMode) {
			if (!$patron->initialReadingHistoryLoaded && !$patron->forceReadingHistoryLoad) {
				$okToLoadFromIls = true;
				$masqueradeMode = UserAccount::isUserMasquerading();
				if ($masqueradeMode) {
					$okToLoadFromIls = $this->driver->canLoadReadingHistoryInMasqueradeMode();
				}
				if ($okToLoadFromIls) {
					$patron->forceReadingHistoryLoad = true;
					$patron->update();
				}
			}

			if ($page == 1 && empty($filter)) {
				$this->updateReadingHistoryBasedOnCurrentCheckouts($patron, false);
				$timer->logTime("Finished updating reading history based on current checkouts.");
				$this->updateReadingHistoryGroupedWorks($patron);
				$timer->logTime("Finished updating reading history grouped works.");
			}
		}

		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		$readingHistoryDB = new ReadingHistoryEntry();
		$readingHistoryDB->userId = $patron->id;
		$readingHistoryDB->whereAdd('deleted =  0');
		if (!empty($filter)) {
			$escapedFilter = $readingHistoryDB->escape('%' . $filter . '%');
			$readingHistoryDB->whereAdd("title LIKE $escapedFilter OR author LIKE $escapedFilter OR format LIKE $escapedFilter");
		}
		$readingHistoryDB->selectAdd();
		$readingHistoryDB->selectAdd('MAX(id) as id');
		$readingHistoryDB->selectAdd('groupedWorkPermanentId');
		$readingHistoryDB->selectAdd('MAX(title) as title');
		$readingHistoryDB->selectAdd('MAX(author) as author');
		$readingHistoryDB->selectAdd('MAX(checkInDate) as checkInDate');
		$readingHistoryDB->selectAdd('MAX(checkOutDate) as checkOutDate');
		$readingHistoryDB->selectAdd('SUM(CASE WHEN checkInDate IS NULL THEN 1 END) as checkedOut');
		$readingHistoryDB->selectAdd('COUNT(id) as timesUsed');
		$readingHistoryDB->selectAdd('GROUP_CONCAT(DISTINCT(format)) as format');
		if ($sortOption == "checkedOut") {
			$readingHistoryDB->orderBy('checkedOut DESC, MAX(checkOutDate) DESC, title ASC');
		} elseif ($sortOption == "returned") {
			$readingHistoryDB->orderBy('checkInDate DESC, title ASC');
		} elseif ($sortOption == "title") {
			$readingHistoryDB->orderBy('title ASC, MAX(checkOutDate) DESC');
		} elseif ($sortOption == "author") {
			// Push entries with no author to the bottom when sorting by author.
			$readingHistoryDB->orderBy("CASE WHEN author IS NULL OR author = '' THEN 1 ELSE 0 END ASC, author ASC, title ASC, MAX(checkOutDate) DESC");
		} elseif ($sortOption == "format") {
			$readingHistoryDB->orderBy('format ASC, title ASC, MAX(checkOutDate) DESC');
		}
		// Group by groupedWorkPermanentId to consolidate entries with the same work
		// but different title/author punctuation variations. For NULL permanent IDs,
		// group by title and author to prevent unrelated items from merging.
		$readingHistoryDB->groupBy([
			'groupedWorkPermanentId',
			'CASE WHEN groupedWorkPermanentId IS NULL THEN title ELSE NULL END',
			'CASE WHEN groupedWorkPermanentId IS NULL THEN author ELSE NULL END'
		]);

		$numTitles = $readingHistoryDB->count();

		if ($recordsPerPage != -1) {
			$firstIndex = ($page - 1) * $recordsPerPage;
			$readingHistoryDB->limit($firstIndex, $recordsPerPage);
		} else {
			$firstIndex = 0;
		}
		$readingHistoryDB->find();
		$readingHistoryTitles = [];
		while ($readingHistoryDB->fetch()) {
			$historyEntry = $this->getHistoryEntryForDatabaseEntry($readingHistoryDB, $forExport);
			$historyEntry['index'] = ++$firstIndex;

			$detailRecords = [];
			$detailQuery = new ReadingHistoryEntry();
			$detailQuery->userId = $patron->id;
			$detailQuery->deleted = 0;

			// Match by groupedWorkPermanentId to get all records in this group.
			if (!empty($readingHistoryDB->groupedWorkPermanentId)) {
				$detailQuery->groupedWorkPermanentId = $readingHistoryDB->groupedWorkPermanentId;
			} else {
				// For entries without a permanent ID, fall back to title and author matching.
				$detailQuery->title = $readingHistoryDB->title;
				$detailQuery->author = $readingHistoryDB->author;
			}
			$detailQuery->orderBy('checkOutDate DESC');
			$detailQuery->find();

			$hasBarcode = false;
			$hasCallNumber = false;
			$hasVolume = false;
			if (!$forExport) {
				while ($detailQuery->fetch()) {
					$detailRecords[] = [
						'id' => $detailQuery->id,
						'checkOutDate' => $detailQuery->checkOutDate,
						'checkInDate' => $detailQuery->checkInDate,
						'editedCheckInDate' => $detailQuery->editedCheckInDate,
						'format' => $detailQuery->format,
						'source' => $detailQuery->source,
						'sourceId' => $detailQuery->sourceId,
						'barcode' => $detailQuery->barcode,
						'callNumber' => $detailQuery->callNumber,
						'volume' => $detailQuery->volume,
					];
					if (!empty($detailQuery->barcode)) {
						$hasBarcode = true;
					}
					if (!empty($detailQuery->callNumber)) {
						$hasCallNumber = true;
					}
					if (!empty($detailQuery->volume)) {
						$hasVolume = true;
					}
				}
			}
			$historyEntry['detailRecords'] = $detailRecords;
			$historyEntry['hasBarcode'] = $hasBarcode;
			$historyEntry['hasCallNumber'] = $hasCallNumber;
			$historyEntry['hasVolume'] = $hasVolume;

			$readingHistoryTitles[] = $historyEntry;
		}
		$timer->logTime("Loaded " . count($readingHistoryTitles) . " titles from the reading history");

		return [
			'historyActive' => $patron->trackReadingHistory,
			'titles' => $readingHistoryTitles,
			'numTitles' => $numTitles,
		];
	}

	/**
	 * Do an update or edit of reading history information.  Current actions are:
	 * deleteMarked
	 * deleteAll
	 * exportList
	 * optOut
	 *
	 * @param User $patron The user to do the reading history action on
	 * @param string $action The action to perform
	 * @param array $selectedTitles The titles to do the action on if applicable
	 * @return array success and message are the array keys
	 */
	function doReadingHistoryAction(User $patron, string $action, array $selectedTitles): array {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Failed to Perform Reading History Action',
				'isPublicFacing' => true
			]),
			'message' => translate([
				'text' => 'Unknown Error',
				'isPublicFacing' => true,
			]),
		];
		if ($action == 'deleteMarked') {
			$numDeleted = 0;
			$numFailed = 0;
			foreach ($selectedTitles as $id => $titleId) {
				$readingHistoryDB = new ReadingHistoryEntry();
				$readingHistoryDB->userId = $patron->id;
				if (is_numeric($id) || is_numeric($titleId)) {
					$readingHistoryDB->id = is_numeric($id) ? $id : $titleId;
					if ($readingHistoryDB->find(true)) {
						$readingHistoryDB->deleted = 1;
						$readingHistoryDB->update();
						$numDeleted++;
					} else {
						$numFailed++;
					}
				} else {
					$numFailed++;
				}
			}

			if ($numDeleted > 0) {
				$result['success'] = true;
				$result['title'] = translate([
					'text' => $numDeleted === 1 ? 'Successfully Deleted Reading History Entry' : 'Successfully Deleted Reading History Entries',
					'isPublicFacing' => true
				]);
				if ($numFailed > 0) {
					$deletedText = $numDeleted === 1 ? 'entry' : 'entries';
					$failedText = $numFailed === 1 ? 'entry' : 'entries';
					$result['message'] = translate([
						'text' => "Deleted %1% $deletedText from your reading history. %2% $failedText could not be deleted.",
						1 => $numDeleted,
						2 => $numFailed,
						'isPublicFacing' => true,
					]);
				} else {
					$entryText = $numDeleted === 1 ? 'entry' : 'entries';
					$result['message'] = translate([
						'text' => "Deleted %1% $entryText from your reading history.",
						1 => $numDeleted,
						'isPublicFacing' => true,
					]);
				}
			} else {
				$result['success'] = false;
				$result['title'] = translate([
					'text' => 'Failed to Delete Reading History Entries',
					'isPublicFacing' => true
				]);
				$result['message'] = translate([
					'text' => 'No entries could be deleted from your reading history.',
					'isPublicFacing' => true,
				]);
			}
		} elseif ($action == 'deleteAll') {
			//Remove all titles from the database (do not remove from ILS)
			$readingHistoryDB = new ReadingHistoryEntry();
			$readingHistoryDB->userId = $patron->id;
			$readingHistoryDB->find();
			while ($readingHistoryDB->fetch()) {
				$readingHistoryDB->deleted = 1;
				$readingHistoryDB->update();
			}
			$patron->totalCostSavings = 0;
			$patron->update();
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'Deleted all entries from Reading History.',
				'isPublicFacing' => true,
			]);
		} elseif ($action == 'optOut') {
			//Delete the reading history (permanently this time since we are opting out)
			$readingHistoryDB = new ReadingHistoryEntry();
			$readingHistoryDB->userId = $patron->id;
			$readingHistoryDB->delete(true);

			//Opt out within Aspen since the ILS does not seem to implement this functionality
			$patron->trackReadingHistory = false;

			//Do not unmark that the initial reading history was loaded to avoid reloading if the ILS does track it.
			//TODO: Remove everything from the ILS when available.
			//$patron->initialReadingHistoryLoaded = false;
			$patron->update();
			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'You have opted out of tracking Reading History.',
				'isPublicFacing' => true,
			]);
		} elseif ($action == 'optIn') {
			//Opt in within Aspen since the ILS does not seem to implement this functionality
			$patron->trackReadingHistory = true;
			$patron->update();

			$result['success'] = true;
			$result['message'] = translate([
				'text' => 'You have opted in to tracking Reading History.',
				'isPublicFacing' => true,
			]);
		}
		if ($this->driver->performsReadingHistoryUpdatesOfILS()) {
			$performIlsAction = true;
			$homeLibrary = $patron->getHomeLibrary();
			if ($action == 'optIn') {
				$performIlsAction = $homeLibrary->optInToReadingHistoryUpdatesILS;
			} elseif ($action == 'optOut') {
				$performIlsAction = $homeLibrary->optOutOfReadingHistoryUpdatesILS;
			}
			if ($performIlsAction) {
				$ilsResult = $this->driver->doReadingHistoryAction($patron, $action, $selectedTitles);
				if (is_array($ilsResult)) {
					if (!$ilsResult['success']) {
						$result['success'] = false;
						$result['message'] = $ilsResult['message'];
					} else {
						if ($result['success'] && !empty($ilsResult['message'])) {
							$result['message'] .= ' ' . $ilsResult['message'];
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Get Patron Holds
	 *
	 * This is responsible for retrieving all holds for a specific patron.
	 *
	 * @param User $user The user to load transactions for
	 *
	 * @return array        Array of the patron's holds
	 * @access public
	 */
	public function getHolds(User $user): array {
		$accountSummary = $user->getCachedAccountSummary('ils');
		$cachedHolds = $user->getCachedHoldsForSource('ils');
		if ($accountSummary->areHoldsStale() || isset($_REQUEST['reload']) || isset($_REQUEST['refreshHolds'])) {
			$holds = $this->driver->getHolds($user);

			$userLibrary = $user->getHomeLibrary();
			foreach ($holds as $sectionName => $holdSection) {
				//Check all unavailable holds to see if we can't update pickup location
				//We will not do available here because the individual drivers check more specifically
				//allowChangingPickupLocationForUnavailableHolds defaults to on
				if ($sectionName == 'unavailable') {
					if (!$userLibrary->allowChangingPickupLocationForUnavailableHolds) {
						/** @var Hold $hold */
						foreach ($holdSection as $hold) {
							$hold->locationUpdateable = false;
						}
					}
				}
			}

			$cachedHolds = $this->driver->updateCachedHoldsBasedOnActiveHolds($cachedHolds, $holds, $accountSummary);
		}
		return $cachedHolds;
	}

	/**
	 * Place Hold
	 *
	 * This is responsible for both placing holds.  This should be called from the user object rather than calling directly.
	 * The user object takes care of updating account summary etc.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $pickupBranch The branch where the user wants to pick up the item when available
	 * @param ?string $cancelDate
	 * @param ?string $pickupSublocation The sublocation within the location where the user wants to pick up the item
	 * @return  mixed                 True if successful, false if unsuccessful
	 *                                If an error occurs, return an AspenError
	 * @access  public
	 */
	function placeHold(User $patron, string $recordId, string $pickupBranch, ?string $cancelDate = null, ?string $pickupSublocation = null) : array {
		$result = $this->driver->placeHold($patron, $recordId, $pickupBranch, $cancelDate, $pickupSublocation);
		if ($result['success']) {
			$indexingProfileId = $this->driver->getIndexingProfile()->id;
			//Track usage by the user
			require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
			$userUsage = new UserILSUsage();
			global $aspenUsage;
			$userUsage->instance = $aspenUsage->getInstance();
			$userUsage->userId = $patron->id;
			$userUsage->indexingProfileId = $indexingProfileId;
			$userUsage->year = date('Y');
			$userUsage->month = date('n');

			if ($userUsage->find(true)) {
				$userUsage->usageCount++;
				$userUsage->update();
			} else {
				$userUsage->usageCount = 1;
				$userUsage->insert();
			}

			//Track usage of the record
			require_once ROOT_DIR . '/sys/ILS/ILSRecordUsage.php';
			$recordUsage = new ILSRecordUsage();
			global $aspenUsage;
			$recordUsage->instance = $aspenUsage->getInstance();
			$recordUsage->indexingProfileId = $indexingProfileId;
			$recordUsage->recordId = $recordId;
			$recordUsage->year = date('Y');
			$recordUsage->month = date('n');
			if ($recordUsage->find(true)) {
				$recordUsage->timesUsed++;
				$recordUsage->update();
			} else {
				$recordUsage->timesUsed = 1;
				$recordUsage->insert();
			}
		}
		return $result;
	}

	/**
	 * Place Item Hold
	 *
	 * This is responsible for placing item level holds.
	 *
	 * @param User $patron The User to place a hold for
	 * @param string $recordId The id of the bib record
	 * @param string $itemId The id of the item to hold
	 * @param string $pickupBranch The branch where the user wants to pick up the item when available
	 * @param null|string $cancelDate The date to automatically cancel the hold if not filled
	 * @return  mixed               True if successful, false if unsuccessful
	 *                              If an error occurs, return a AspenError
	 * @access  public
	 */
	function placeItemHold(User $patron, string $recordId, string $itemId, string $pickupBranch, ?string $cancelDate = null, ?string $pickupSublocation = null) : array {
		return $this->driver->placeItemHold($patron, $recordId, $itemId, $pickupBranch, $cancelDate, $pickupSublocation);
	}

	function updatePatronInfo($user, $canUpdateContactInfo, $fromMasquerade = false): array {
		//Make sure data is valid
		foreach ($_REQUEST as $requestVar) {
			if ($requestVar != strip_tags($requestVar)) {
				return [
					'success' => false,
					'messages' => ['Invalid information provided, please try again.']
				];
			}
		}

		return $this->driver->updatePatronInfo($user, $canUpdateContactInfo, $fromMasquerade);
	}

	/**
	 * @param User $user
	 * @param string $homeLibraryCode
	 * @return array
	 */
	function updateHomeLibrary(User $user, string $homeLibraryCode): array {
		$result = $this->driver->updateHomeLibrary($user, $homeLibraryCode);
		if ($result['success']) {
			$location = new Location();
			$location->code = $homeLibraryCode;
			if ($location->find(true)) {
				if ($user->homeLocationId != $location->locationId) {
					$user->__set('homeLocationId', $location->locationId);
				}
				$user->_homeLocationCode = $homeLibraryCode;
				$user->_homeLocation = $location;
				$user->update();

				$parentLibrary = $location->getParentLibrary();
				if ($parentLibrary != null) {
					if (!$parentLibrary->allowPickupLocationUpdates || !$parentLibrary->allowRememberPickupLocation) {
						$user->pickupLocationId = $location->locationId;
					}
				}
			}
		}
		return $result;
	}

	function selfRegister($viaSSO = false, $ssoUser = []): array {
		//Make sure data is valid
		foreach ($_REQUEST as $requestVar) {
			if ($requestVar != strip_tags($requestVar)) {
				return [
					'success' => false,
					'message' => 'Invalid information provided, please try again.'
				];
			}
		}

		if (!$viaSSO) {
			$result = $this->driver->selfRegister();
		} else {
			$result = $this->driver->selfRegisterViaSSO($ssoUser);
		}
		if ($result['success'] == true) {
			//Track usage by the user
			require_once ROOT_DIR . '/sys/ILS/UserILSUsage.php';
			$userUsage = new UserILSUsage();
			global $aspenUsage;
			$userUsage->instance = $aspenUsage->getInstance();
			$userUsage->userId = -1;
			$userUsage->indexingProfileId = $this->driver->getIndexingProfile()->id;
			$userUsage->year = date('Y');
			$userUsage->month = date('n');

			if ($userUsage->find(true)) {
				$userUsage->selfRegistrationCount++;
				$userUsage->update();
			} else {
				$userUsage->selfRegistrationCount = 1;
				$userUsage->insert();
			}

			if (!$viaSSO) {
				if (isset($result['sendWelcomeMessage']) && $result['sendWelcomeMessage'] == true) {
					//See if we need to send a welcome email to the patron
					require_once ROOT_DIR . '/sys/Email/EmailTemplate.php';
					$emailTemplate = EmailTemplate::getActiveTemplate('welcome');
					if ($emailTemplate != null) {
						$newUser = $result['newUser'];
						if ($newUser instanceof User && !empty($newUser->email)) {
							$parameters = [
								'user' => $newUser,
								'library' => $newUser->getHomeLibrary()
							];
							$emailTemplate->sendEmail($newUser->email, $parameters);
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Default method -- pass along calls to the driver if available; return
	 * false otherwise.  This allows custom functions to be implemented in
	 * the driver without constant modification to the connection class.
	 *
	 * @param string $methodName The name of the called method.
	 * @param array $params Array of passed parameters.
	 *
	 * @return mixed             Varies by method (false if undefined method)
	 * @access public
	 */
	public function __call($methodName, $params) {
		$method = [
			$this->driver,
			$methodName,
		];
		if (is_callable($method)) {
			return call_user_func_array($method, $params);
		}
		return false;
	}

	public function getSelfRegistrationTerms() {
		return $this->driver->getSelfRegistrationTerms();
	}

	public function getSelfRegistrationFields() {
		return $this->driver->getSelfRegistrationFields();
	}

	/**
	 * @param ReadingHistoryEntry $readingHistoryDB
	 * @return array
	 */
	public function getHistoryEntryForDatabaseEntry(ReadingHistoryEntry $readingHistoryDB, bool $forExport): array {
		$historyEntry = [];
		$historyEntry['id'] = $readingHistoryDB->id;
		$historyEntry['title'] = $readingHistoryDB->title;
		$historyEntry['author'] = $readingHistoryDB->author;
		$historyEntry['format'] = $readingHistoryDB->format;
		$historyEntry['checkout'] = $readingHistoryDB->checkOutDate;
		$historyEntry['checkin'] = $readingHistoryDB->checkInDate;
		/** @noinspection PhpUndefinedFieldInspection */
		$historyEntry['timesUsed'] = $readingHistoryDB->timesUsed;
		/** @noinspection PhpUndefinedFieldInspection */
		$historyEntry['checkedOut'] = $readingHistoryDB->checkedOut !== null;
		$historyEntry['permanentId'] = $readingHistoryDB->groupedWorkPermanentId;
		$historyEntry['isIll'] = $readingHistoryDB->isIll;
		if (!$forExport) {
			require_once ROOT_DIR . '/RecordDrivers/GroupedWorkDriver.php';
			$recordDriver = new GroupedWorkDriver($readingHistoryDB->groupedWorkPermanentId);
			if ($recordDriver->isValid()) {
				$historyEntry['recordDriver'] = $recordDriver;
				$historyEntry['ratingData'] = $recordDriver->getRatingData();
				$historyEntry['linkUrl'] = $recordDriver->getLinkUrl();
				$historyEntry['coverUrl'] = $recordDriver->getBookcoverUrl('medium');
				$historyEntry['existsInCatalog'] = TRUE;
			} else {
				$historyEntry['existsInCatalog'] = FALSE;
				$historyEntry['ratingData'] = '';
				$historyEntry['linkUrl'] = '';
				$historyEntry['coverUrl'] = '';
			}
		}

		return $historyEntry;
	}

	public function bypassReadingHistoryUpdate(User $patron, bool $isNightlyUpdate): bool {
		// Only update every 5 minutes in normal situations.
		$curTime = time();
		if ((($curTime - $patron->lastReadingHistoryUpdate) < 60 * 5) && !isset($_REQUEST['reload'])) {
			return true;
		}
		return $this->driver->bypassReadingHistoryUpdate($patron, $isNightlyUpdate);
	}

	/**
	 * @param User $patron
	 * @param bool $isNightlyUpdate
	 * @return array
	 */
	public function updateReadingHistoryBasedOnCurrentCheckouts(User $patron, bool $isNightlyUpdate): array {
		if ($this->bypassReadingHistoryUpdate($patron, $isNightlyUpdate)) {
			return [
				'message' => 'Bypassed reading history update',
				'skipped' => true
			];
		}
		$activeHistoryTitles = [];
		require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
		require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
		// Include deleted titles to prevent duplicates.
		$readingHistoryDB = new ReadingHistoryEntry();
		$readingHistoryDB->userId = $patron->id;
		$readingHistoryDB->whereAdd('checkInDate IS NULL');
		$readingHistoryDB->find();
		while ($readingHistoryDB->fetch()) {
			$historyEntry = [];
			$historyEntry['source'] = $readingHistoryDB->source;
			$historyEntry['sourceId'] = $readingHistoryDB->sourceId;
			$historyEntry['barcode'] = $readingHistoryDB->barcode ?: null;
			$historyEntry['ids'] = [];
			$historyEntry['ids'][] = $readingHistoryDB->id;
			if (!empty($historyEntry['barcode'])) {
				$key = strtolower($historyEntry['source'] . ':' . $historyEntry['sourceId'] . '_' . $historyEntry['barcode']);
			} else {
				$key = strtolower($historyEntry['source'] . ':' . $historyEntry['sourceId']);
			}
			if (array_key_exists($key, $activeHistoryTitles)) {
				if (IPAddress::showDebuggingInformation()) {
					global $logger;
					$logger->log("Adding {$readingHistoryDB->id} to active history entry $key.", Logger::LOG_ERROR);
				}
				$activeHistoryTitles[$key]['ids'][] = $readingHistoryDB->id;
			} else {
				if (IPAddress::showDebuggingInformation()) {
					global $logger;
					$logger->log("Adding new record $key, {$readingHistoryDB->id} to active history entries.", Logger::LOG_ERROR);
				}
				$activeHistoryTitles[$key] = $historyEntry;
			}
		}

		$checkouts = $patron->getCheckouts(false);
		foreach ($checkouts as $checkout) {
			$source = $checkout->source;
			$sourceId = $checkout->sourceId;
			$barcode = $checkout->barcode;
			if (!empty($barcode)) {
				$key = strtolower($source . ':' . $sourceId . '_' . $barcode);
			} else {
				$key = strtolower($source . ':' . $sourceId);
			}
			if (array_key_exists($key, $activeHistoryTitles)) {
				unset($activeHistoryTitles[$key]);
			} else {
				// If a checkout is currently active and there is already an active history entry for the same title without a barcode,
				// skip creating a duplicate (with a barcode) and keep the no-barcode entry active without assigning a check-in date.
				if (!empty($barcode)) {
					$noBarcodeKey = strtolower($source . ':' . $sourceId);
					if (array_key_exists($noBarcodeKey, $activeHistoryTitles)) {
						unset($activeHistoryTitles[$noBarcodeKey]);
						continue;
					}
				}

				$historyEntryDB = new ReadingHistoryEntry();
				$historyEntryDB->userId = $patron->id;
				if (!empty($checkout->groupedWorkId)) {
					$historyEntryDB->groupedWorkPermanentId = $checkout->groupedWorkId;
				} else {
					$historyEntryDB->groupedWorkPermanentId = "";
				}
				$historyEntryDB->source = $source;
				$historyEntryDB->sourceId = $sourceId;
				$historyEntryDB->barcode = $barcode;
				$historyEntryDB->callNumber = $checkout->callNumber ?? null;
				$historyEntryDB->volume = $checkout->volume ?? null;
				$historyEntryDB->title = !empty($checkout->title) ? StringUtils::trimStringToLengthAtWordBoundary($checkout->title, 150, true) : "";
				$historyEntryDB->author = !empty($checkout->author) ? StringUtils::trimStringToLengthAtWordBoundary($checkout->author, 75, true) : "";
				$historyEntryDB->format = substr($checkout->format ?? "", 0, 50);
				$historyEntryDB->checkOutDate = $checkout->checkoutDate ?? time();
				$historyEntryDB->costSavings = $checkout->getReplacementCost();
				if (!$historyEntryDB->insert()) {
					global $logger;
					$logger->log("Could not insert new reading history entry.", Logger::LOG_ERROR);
				} else {
					if ($patron->enableCostSavings) {
						$patron->__set('totalCostSavings', $patron->totalCostSavings + $historyEntryDB->costSavings);
					}
				}
			}
		}

		if (IPAddress::showDebuggingInformation()) {
			global $logger;
			$logger->log("There are " . count($activeHistoryTitles) . " titles that have been checked in.", Logger::LOG_ERROR);
		}
		foreach ($activeHistoryTitles as $historyEntry) {
			if (IPAddress::showDebuggingInformation()) {
				global $logger;
				$logger->log("There are " . count($historyEntry['ids']) . " ids for this history entry.", Logger::LOG_ERROR);
			}
			// Set checkInDate for all reading history entries that are no longer checked out.
			foreach ($historyEntry['ids'] as $id) {
				$historyEntryDB = new ReadingHistoryEntry();
				$historyEntryDB->id = $id;
				if ($historyEntryDB->find(true)) {
					$historyEntryDB->checkInDate = time();
					$numUpdates = $historyEntryDB->update();
					if (IPAddress::showDebuggingInformation()) {
						global $logger;
						if ($numUpdates != 1) {
							$logger->log("Could not update reading history entry $id.", Logger::LOG_ERROR);
						} else {
							$logger->log("Marked $id as checked in.", Logger::LOG_ERROR);
						}
					}
				}
			}
		}

		$patron->__set('lastReadingHistoryUpdate', time());
		$patron->update();

		return [
			'message' => 'Reading history updated',
			'skipped' => false
		];
	}

	/**
	 * Update NULL groupedWorkPermanentId values in reading history by
	 * looking up the current grouped work for the sourceId.
	 * This is for checked out items that were suppressed and had no valid
	 * grouped work, but now they do after no longer being suppressed.
	 *
	 * @param User $patron
	 * @return void
	 * @noinspection SqlResolve
	 * @noinspection SqlDialectInspection
	 */
	public function updateReadingHistoryGroupedWorks(User $patron): void {
		if ($this->bypassReadingHistoryUpdate($patron, false)) {
			return;
		}

		// First check if there are any NULL groupedWorkPermanentId entries to update.
		$checkSql = "
			SELECT COUNT(*) as count
			FROM user_reading_history_work
			WHERE userId = :userId
			AND (groupedWorkPermanentId IS NULL OR groupedWorkPermanentId = '')
		";

		try {
			global $aspen_db;
			$stmt = $aspen_db->prepare($checkSql);
			$stmt->bindValue(':userId', $patron->id, PDO::PARAM_INT);
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($result['count'] > 0) {
				// Use a direct SQL query to efficiently update entries with joined grouped work data.
				$updateSql = "
					UPDATE user_reading_history_work urh
					JOIN grouped_work_primary_identifiers gwpi ON gwpi.type = urh.source AND gwpi.identifier = urh.sourceId
					JOIN grouped_work gw ON gw.id = gwpi.grouped_work_id
					SET urh.groupedWorkPermanentId = gw.permanent_id
					WHERE urh.userId = :userId
					AND (urh.groupedWorkPermanentId IS NULL OR urh.groupedWorkPermanentId = '')
				";

				$updateStmt = $aspen_db->prepare($updateSql);
				$updateStmt->bindValue(':userId', $patron->id, PDO::PARAM_INT);
				$updateStmt->execute();
			}
		} catch (Exception $e) {
			global $logger;
			$logger->log("Error updating reading history grouped works for user $patron->id: " . $e->getMessage(), Logger::LOG_ERROR);
		}
	}

	function cancelHold(User $patron, string $recordId, ?string $cancelId = null, ?bool $isIll = false): array {
		//Make sure the hold should be cancelable
		$holds = $this->getHolds($patron);
		$holdToCancel = $this->driver->getHoldByCancelId($holds, $recordId, $cancelId);
		$okToCancel = false;
		if ($holdToCancel != null && $holdToCancel->cancelable) {
			$okToCancel = true;
		}
		if ($okToCancel) {
			$result = $this->driver->cancelHold($patron, $recordId, $cancelId, $isIll);
			if ($result['success']) {
				$this->driver->updateCachesForCancelledHold($patron, $holdToCancel);
			}
			return $result;
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'This hold cannot be canceled',
					'isPublicFacing' => true
				]),
			];
		}
	}

	function freezeHold(User $patron, string $recordId, string $itemToFreezeId, ?string $dateToReactivate): array {
		$holds = $this->getHolds($patron);
		$holdToFreeze = $this->driver->getHoldByCancelId($holds, $recordId, $itemToFreezeId);
		$result = $this->driver->freezeHold($patron, $recordId, $itemToFreezeId, $dateToReactivate);
		if ($result['success']) {
			$holdToFreeze->markFrozen($dateToReactivate);
		}
		return $result;
	}

	function thawHold(User $patron, string $recordId, string $itemToThawId): array {
		$holds = $this->getHolds($patron);
		$holdToThaw = $this->driver->getHoldByCancelId($holds, $recordId, $itemToThawId);
		$result = $this->driver->thawHold($patron, $recordId, $itemToThawId);
		if ($result['success']) {
			$holdToThaw->markThawed();
		}
		return $result;
	}

	function changeHoldPickupLocation(User $patron, string $holdId, string $newPickupLocation, ?string $newPickupSublocation = null): array {
		return $this->driver->changeHoldPickupLocation($patron, $holdId, $newPickupLocation, $newPickupSublocation);
	}

	public function renewCheckout(User $patron, string $recordId, ?string $itemId = null, ?string $itemIndex = null) : array {
		$result = $this->driver->renewCheckout($patron, $recordId, $itemId, $itemIndex);
		if ($result['success']) {
			$accountSummary = $patron->getCachedAccountSummary('ils');
			$accountSummary->markCheckoutsStale();
		}
		return $result;
	}

	public function renewAll(User $patron): array {
		if ($this->driver->hasFastRenewAll()) {
			$result = $this->driver->renewAll($patron);
			if ($result['success']) {
				$accountSummary = $patron->getCachedAccountSummary('ils');
				$accountSummary->markCheckoutsStale();
			}
			return $result;
		} else {
			//Get all list of all transactions
			$currentTransactions = $this->driver->getCheckouts($patron);
			/** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
			$renewResult = [
				'success' => true,
				'message' => [],
				'Renewed' => 0,
				'NotRenewed' => 0,
			];
			$renewResult['Total'] = count($currentTransactions);
			$numRenewals = 0;
			$failure_messages = [];
			foreach ($currentTransactions as $transaction) {
				$curResult = $this->renewCheckout($patron, $transaction->recordId, $transaction->renewIndicator, null);
				if ($curResult['success']) {
					$numRenewals++;
				} else {
					if ($curResult['message'] == 'The item could not be renewed') {
						require_once ROOT_DIR . '/sys/Utils/StringUtils.php';
						$failure_messages[] = StringUtils::removeTrailingPunctuation($transaction->title) . ' could not be renewed';
					}
				}
			}
			$renewResult['Renewed'] += $numRenewals;
			$renewResult['NotRenewed'] = $renewResult['Total'] - $renewResult['Renewed'];
			if ($renewResult['NotRenewed'] > 0) {
				$renewResult['success'] = false;
				$renewResult['message'] = $failure_messages;
			} else {
				$renewResult['message'][] = "All items were renewed successfully.";
			}
			return $renewResult;
		}
	}

	public function placeVolumeHold(User $patron, string $recordId, string $volumeId, string $pickupBranch, ?string $pickupSublocation = null) : array {
		return $this->driver->placeVolumeHold($patron, $recordId, $volumeId, $pickupBranch, $pickupSublocation);
	}

	public function importListsFromIls($patron) : array {
		set_time_limit(0);
		return $this->driver->importListsFromIls($patron);
	}

	/**
	 * Resets the PIN/Password.  At this point, the confirmation matches the new pin so no need to reconfirm
	 * @param User $user
	 * @param string $oldPin
	 * @param string $newPin
	 * @return string[] a message to the user letting them know what happened
	 */
	function updatePin(User $user, ?string $oldPin, string $newPin) {
		return $this->driver->updatePin($user, $oldPin, $newPin);
	}

	function showOutstandingFines() {
		return $this->driver->showOutstandingFines();
	}

	/**
	 * Returns one of four values
	 * - none - No forgot password functionality exists
	 * - emailResetLink - A link to reset the pin is emailed to the user
	 * - emailPin - The pin itself is emailed to the user
	 * - emailAspenResetLink - A link to reset the pin is emailed to the user.  Reset happens within Aspen.
	 * @return string
	 */
	function getForgotPasswordType() {
		if ($this->driver == null) {
			return null;
		}
		return $this->driver->getForgotPasswordType();
	}

	public function getEmailResetPinTemplate() {
		global $interface;
		global $library;

		$interface->assign('usernameLabel', str_replace('Your', '', $library->loginFormUsernameLabel ? $library->loginFormUsernameLabel : 'Name'));
		$interface->assign('passwordLabel', str_replace('Your', '', $library->loginFormPasswordLabel ? $library->loginFormPasswordLabel : 'Library Card Number'));

		if (isset($_REQUEST['email'])) {
			$interface->assign('email', $_REQUEST['email']);
		}
		if (isset($_REQUEST['barcode'])) {
			$interface->assign('barcode', $_REQUEST['barcode']);
		}
		if (isset($_REQUEST['username'])) {
			$interface->assign('username', $_REQUEST['username']);
		}
		if (isset($_REQUEST['resendEmail'])) {
			$interface->assign('resendEmail', $_REQUEST['resendEmail']);
		}

		if ($this->getForgotPasswordType() == 'emailAspenResetLink') {
			return 'aspenEmailResetPinLink.tpl';
		} else {
			return $this->driver->getEmailResetPinTemplate();
		}
	}

	function processEmailResetPinForm() : array {
		global $library;
		$result = [
			'success' => false,
			'error' => translate([
				'text' => "Unknown error sending password reset.",
				'isPublicFacing' => true,
			]),
		];

		$accountProfilesToCheck = UserAccount::getAccountProfiles();
		foreach ($accountProfilesToCheck as $accountProfileInfo) {
			$tmpAccountProfile = $accountProfileInfo['accountProfile'];
			if ($library->accountProfileId == $tmpAccountProfile->id) {
				if ($this->getForgotPasswordType() == 'emailAspenResetLink') {
					//Get the user from the driver
					if (empty($_REQUEST['reset_username'])) {
						$result['error'] = translate([
							'text' => "Barcode not provided. You must provide a barcode to use password reset.",
							'isPublicFacing' => true,
						]);
					} else {
						$barcode = $_REQUEST['reset_username'];
						$result = $this->sendAspenPasswordResetEmailForBarcode($accountProfileInfo, $barcode);
					}
				} else {
					$result = $this->driver->processEmailResetPinForm();
				}
			}elseif ($tmpAccountProfile->authenticationMethod == 'db') {
				if (empty($_REQUEST['reset_username']) && empty($_REQUEST['username'])) {
					$result['error'] = translate([
						'text' => "Username not provided. You must provide a username to use password reset.",
						'isPublicFacing' => true,
					]);
				} else {
					$username = $_REQUEST['reset_username'] ?? $_REQUEST['username'];
					$result = $this->sendAspenPasswordResetEmailForBarcode($accountProfileInfo, $username);
				}
			}
			if ($result['success'] || !empty($result['foundPatron'])){
				break;
			}
		}
		return $result;
	}

	function hasMaterialsRequestSupport() {
		return $this->driver->hasMaterialsRequestSupport();
	}

	function getNewMaterialsRequestForm(User $user) {
		return $this->driver->getNewMaterialsRequestForm($user);
	}

	function patronEligibleForILLRequests(User $user){
		if( empty($this->driver)){
			return false;
		}
		return $this->driver->patronEligibleForILLRequests($user);
	}

	function processMaterialsRequestForm($user) {
		return $this->driver->processMaterialsRequestForm($user);
	}

	function getNumMaterialsRequests(User $user): int {
		return $this->driver->getNumMaterialsRequests($user);
	}

	/** @noinspection PhpUnused */
	function getMaterialsRequests(User $user) {
		return $this->driver->getMaterialsRequests($user);
	}

	function getMaterialsRequestsPage(User $user) {
		return $this->driver->getMaterialsRequestsPage($user);
	}

	function deleteMaterialsRequests(User $user) {
		return $this->driver->deleteMaterialsRequests($user);
	}

	function getPatronUpdateForm(User $user) {
		return $this->driver->getPatronUpdateForm($user);
	}

	public function getAccountSummary(User $user) : AccountSummary {
		$summary = $user->getCachedAccountSummary('ils');

		if ($summary->dataIsStale || isset($_REQUEST['reload'])) {
			$summary = $this->driver->getAccountSummary($user);
			$summary->lastLoaded = time();
			$summary->dataIsStale = false;
			$summary->update();
		}
		return $summary;
	}

	public function getExpirationInformation(User $user) : ExpirationInformation {
		return $this->driver->getExpirationInformation($user);
	}

	public function getDebarmentStatus(User $user) {
		return $this->driver->getDebarmentStatus($user);
	}

	/**
	 * @return bool
	 */
	public function showMessagingSettings(): bool {
		if ($this->driver == null) {
			return false;
		}
		return $this->driver->showMessagingSettings();
	}

	/**
	 * @param User $user
	 * @return string
	 */
	public function getMessagingSettingsTemplate(User $user): ?string {
		return $this->driver->getMessagingSettingsTemplate($user);
	}

	public function processMessagingSettingsForm(User $user): array {
		return $this->driver->processMessagingSettingsForm($user);
	}

	public function completeFinePayment(User $patron, UserPayment $payment) : array {
		$result = $this->driver->completeFinePayment($patron, $payment);
		//Ensure that fine amount gets reloaded
		$patron->clearCachedAccountSummaryForSource('ils');
		return $result;
	}

	public function patronEligibleForHolds(User $patron) : array {
		if (empty($this->driver)) {
			return [
				'isEligible' => false,
				'message' => '',
				'fineLimitReached' => false,
				'maxPhysicalCheckoutsReached' => false,
				'expiredPatronWhoCannotPlaceHolds' => false,
			];
		}
		return $this->driver->patronEligibleForHolds($patron);
	}

	public function patronEligibleForRenewals($patron) : array {
		if( empty($this->driver)){
			return [
				'isEligible' => false,
				'message' => '',
				'fineLimitReached' => false,
				'maxPhysicalCheckoutsReached' => false,
				'expiredPatronWhoCannotPlaceHolds' => false,
			];
		}
		return $this->driver->patronEligibleForHolds($patron);
	}
	
	public function getShowAutoRenewSwitch(User $patron) {
		if (empty($this->driver)) {
			return false;
		}
		return $this->driver->getShowAutoRenewSwitch($patron);
	}

	public function isAutoRenewalEnabledForUser(User $patron) {
		if (empty($this->driver)) {
			return false;
		}
		return $this->driver->isAutoRenewalEnabledForUser($patron);
	}

	public function updateAutoRenewal(User $patron, bool $allowAutoRenewal) {
		return $this->driver->updateAutoRenewal($patron, $allowAutoRenewal);
	}

	public function getPasswordRecoveryTemplate() {
		return $this->driver->getPasswordRecoveryTemplate();
	}

	public function processPasswordRecovery() {
		return $this->driver->processPasswordRecovery();
	}

	public function getEmailResetPinResultsTemplate() {
		if ($this->getForgotPasswordType() == 'emailAspenResetLink') {
			return 'aspenEmailResetPinResults.tpl';
		} else {
			return $this->driver->getEmailResetPinResultsTemplate();
		}
	}

	function getPasswordPinValidationRules() : array {
		return $this->driver->getPasswordPinValidationRules();
	}

	/**
	 * @param $barcode
	 * @param string|null $password
	 * @param string|null $username
	 * @return User|null
	 */
	protected function getUserFromDatabase($barcode, $password, $username) {
		global $timer;
		global $logger;
		$user = new User();
		//First check the barcode field
		$user->source = $this->driver->accountProfile->name;
		$user->ils_barcode = $barcode;
		$foundUser = false;
		try {
			if ($user->find(true)) {
				$foundUser = true;
			}
		} catch (Exception $e) {
			//ils_barcode probably has not been created yet
			$user = null;
		}
		if ($foundUser) {
			if ($this->driver->accountProfile->loginConfiguration == 'barcode_pin') {
				//We load the account based on the barcode make sure the pin matches
				$userValid = $password != null && $user->cat_password == $password;
				if (!$userValid) {
					$timer->logTime("offline patron login failed due to invalid password");
					$logger->log("offline patron login failed due to invalid password", Logger::LOG_NOTICE);
					$user = null;
				}
			} else {
				//We still load based on barcode, make sure the username is similar
				$userValid = $this->areNamesSimilar($username, $user->ils_password);
				if (!$userValid) {
					$timer->logTime("offline patron login failed due to invalid name");
					$logger->log("offline patron login failed due to invalid name", Logger::LOG_NOTICE);
					$user = null;
				}
			}
		} else {
			$timer->logTime("Loading patron $barcode from database failed because we haven't seen this user before or it is not valid for account profile {$this->driver->accountProfile->name}");
			$logger->log("Loading patron $barcode from database failed because we haven't seen this user before or it is not valid for account profile {$this->driver->accountProfile->name}", Logger::LOG_NOTICE);
			$user = null;
		}
		return $user;
	}

	public function supportsLoginWithUsername(): bool {
		return $this->driver->supportsLoginWithUsername();
	}

	public function hasEditableUsername() : bool {
		return $this->driver->hasEditableUsername();
	}

	public function getEditableUsername(User $user) : ?string {
		return $this->driver->getEditableUsername($user);
	}

	public function updateEditableUsername(User $user, string $username): array {
		return $this->driver->updateEditableUsername($user, $username);
	}

	public function logout(User $user) {
		$this->driver->logout($user);
		$user->lastLoginValidation = 0;
		$user->update();
	}

	public function getHoldsReportData($location) {
		return $this->driver->getHoldsReportData($location);
	}

	public function getStudentReportData($location, $showOverdueOnly, $date) {
		return $this->driver->getStudentReportData($location, $showOverdueOnly, $date);
	}

	public function getWeedingReportData($location) {
		return $this->driver->getWeedingReportData($location);
	}
	
	public function getLibrarianFacebookData() {
		return $this->driver->getLibrarianFacebookData();
	}

	/**
	 * Loads any contact information that is not stored by Aspen Discovery from the ILS. Updates the user object.
	 *
	 * @param User $user
	 */
	public function loadContactInformation(User $user) {
		$this->driver->loadContactInformation($user);
	}

	public function getILSMessages(User $user) {
		return $this->driver->getILSMessages($user);
	}

	public function confirmHold(User $user, string $recordId, string $confirmationId) : array {
		return $this->driver->confirmHold($user, $recordId, $confirmationId);
	}

	public function treatVolumeHoldsAsItemHolds() {
		return $this->driver->treatVolumeHoldsAsItemHolds();
	}

	public function getPluginStatus(string $pluginName) {
		return $this->driver->getPluginStatus($pluginName);
	}

	public function getCurbsidePickupSettings(string $locationCode): array {
		return $this->driver->getCurbsidePickupSettings($locationCode);
	}

	public function getPatronCurbsidePickups(User $user): array {
		return $this->driver->getPatronCurbsidePickups($user);
	}

	public function newCurbsidePickup(User $user, string $pickupLocation, string $pickupTime, ?string $pickupNote): array {
		return $this->driver->newCurbsidePickup($user, $pickupLocation, $pickupTime, $pickupNote);
	}

	public function cancelCurbsidePickup(User $user, string $pickupId): array {
		return $this->driver->cancelCurbsidePickup($user, $pickupId);
	}

	public function checkInCurbsidePickup(User $user, string $pickupId): array {
		return $this->driver->checkInCurbsidePickup($user, $pickupId);
	}

	public function getAllCurbsidePickups(): array {
		return $this->driver->getAllCurbsidePickups();
	}

	public function restrictValidPickupLocationsForRecordByILS(): bool {
		return $this->driver->restrictValidPickupLocationsForRecordByILS();
	}

	public function getValidPickupLocationsForRecordFromILS(string $recordId, User $user): array {
		return $this->driver->getValidPickupLocationsForRecordFromILS($recordId, $user);
	}

	public function isPromptForHoldNotifications(): bool {
		return $this->driver->isPromptForHoldNotifications();
	}

	public function getHoldNotificationTemplate(User $user): ?string {
		return $this->driver->getHoldNotificationTemplate($user);
	}

	public function loadHoldNotificationInfo(User $user): ?array {
		return $this->driver->loadHoldNotificationInfo($user);
	}

	public function validateUniqueId(User $user) {
		$this->driver->validateUniqueId($user);
	}

	/**
	 * Map from the property names required for self registration to
	 * the IdP property names returned from SAML2Authentication
	 *
	 * @return array|bool
	 */
	public function getLmsToSso($isStaffUser, $isStudentUser, $useGivenUserId, $useGivenCardnumber) {
		return $this->driver->lmsToSso($isStaffUser, $isStudentUser, $useGivenUserId, $useGivenCardnumber);
	}

	public function getPatronIDChanges($searchPatronID): ?array {
		return $this->driver->getPatronIDChanges($searchPatronID);
	}

	public function showHoldNotificationPreferences(): bool {
		return $this->driver->showHoldNotificationPreferences();
	}

	public function getHoldNotificationPreferencesTemplate(User $user): ?string {
		return $this->driver->getHoldNotificationPreferencesTemplate($user);
	}

	public function processHoldNotificationPreferencesForm(User $user): array {
		return $this->driver->processHoldNotificationPreferencesForm($user);
	}

	public function showHoldPosition(): bool {
		return $this->driver->showHoldPosition();
	}

	public function suspendRequiresReactivationDate(): bool {
		return $this->driver->suspendRequiresReactivationDate();
	}

	public function showDateWhenSuspending(): bool {
		return $this->driver->showDateWhenSuspending();
	}

	public function reactivateDateNotRequired(): bool {
		return $this->driver->reactivateDateNotRequired();
	}

	public function showHoldPlacedDate(): bool {
		return $this->driver->showHoldPlacedDate();
	}

	public function showHoldExpirationTime(): bool {
		return $this->driver->showHoldExpirationTime();
	}

	public function showOutDateInCheckouts(): bool {
		return $this->driver->showOutDateInCheckouts();
	}

	public function showTimesRenewed(): bool {
		return $this->driver->showTimesRenewed();
	}

	public function showRenewalsRemaining(): bool {
		return $this->driver->showRenewalsRemaining();
	}

	public function showWaitListInCheckouts(): bool {
		return $this->driver->showWaitListInCheckouts();
	}

	/**
	 * Returns true if reset username is a separate page independent of the patron information page
	 *
	 * @return bool
	 */
	public function showResetUsernameLink(): bool {
		return $this->driver->showResetUsernameLink();
	}

	public function getUsernameValidationRules(): array {
		return $this->driver->getUsernameValidationRules();
	}

	public function showPreferredNameInProfile(): bool {
		return $this->driver->showPreferredNameInProfile();
	}

	public function showDateInFines(): bool {
		return $this->driver->showDateInFines();
	}

	public function initiatePasswordResetByEmail(): array {
		if ($this->getForgotPasswordType() == 'emailAspenResetLink') {
			$email = $_REQUEST['email'];
			if (!filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'This provided email is not valid, please provide a properly formatted email address.',
						'isPublicFacing' => true,
					])
				];
			} else {
				//Get the user for this
				return [
					'success' => false,
					'message' => translate([
						'text' => 'Patron reset by email is not currently supported.',
						'isPublicFacing' => true,
					])
				];
			}
		} else {
			return $this->driver->initiatePasswordResetByEmail();
		}
	}

	public function initiatePasswordResetByBarcode(): array {
		if ($this->getForgotPasswordType() == 'emailAspenResetLink') {
			if (!isset($_REQUEST['barcode'])) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'The barcode was not provided, please submit the barcode to reset the PIN for.',
						'isPublicFacing' => true,
					])
				];
			} else {
				//Get the user for this
				$barcode = $_REQUEST['barcode'];
				$result = $this->sendAspenPasswordResetEmailForBarcode($barcode);
				return [
					'success' => $result['success'],
					'message' => $result['success'] ? $result['message'] : $result['error']
				];
			}
		} else {
			return $this->driver->initiatePasswordResetByBarcode();
		}
	}

	public function checkoutBySip(User $patron, string $barcode, $currentLocationId): array {
		return $this->driver->checkoutBySip($patron, $barcode, $currentLocationId);
	}

	public function hasAPICheckout(): bool {
		return $this->driver->hasAPICheckout();
	}

	public function checkoutByAPI(User $patron, $barcode, Location $currentLocation): array {
		return $this->driver->checkoutByAPI($patron, $barcode, $currentLocation);
	}

	public function hasAPICheckin() : bool {
		return $this->driver->hasAPICheckin();
	}

	public function checkInByAPI(User $patron, string $barcode, Location $currentLocation): array {
		return $this->driver->checkInByAPI($patron, $barcode, $currentLocation);
	}

	public function checkInBySip(User $patron, string $barcode, Location $currentLocation): array {
		return $this->driver->checkInBySip($patron, $barcode, $currentLocation);
	}

	public function allowUpdatesOfPreferredName(User $patron): bool {
		return $this->driver->allowUpdatesOfPreferredName($patron);
	}

	public function supportAccountNotifications(): bool {
		return $this->driver->supportAccountNotifications();
	}

	public function getMessageTypes(): array {
		return $this->driver->getMessageTypes();
	}

	public function updateAccountNotifications(ILSNotificationSetting $ilsNotificationSetting, ?CronLogEntry $cronLogEntry): array {
		if ($this->supportAccountNotifications()) {
			//Get a list of all users that have account notifications turned on
			require_once ROOT_DIR . '/sys/Account/UserNotificationToken.php';
			$userNotificationToken = new UserNotificationToken();
			$userNotificationToken->notifyAccount = 1;
			$userNotificationToken->selectAdd();
			$userNotificationToken->selectAdd('DISTINCT(userId)');
			$userNotificationToken->find();
			if (!is_null($cronLogEntry)) {
				$cronLogEntry->notes .= "Updating Account Notifications for {$userNotificationToken->getNumResults()} users.<br/>";
				$cronLogEntry->update();
			}
			$result = [
				'success' => true,
				'message' => '',
				'numUserUpdates' => 0,
				'numFailedUserUpdates' => 0,
				'numMessagesAdded' => 0,
			];
			while ($userNotificationToken->fetch()) {
				$user = new User();
				$user->id = $userNotificationToken->userId;
				if ($user->find(true)) {
					$barcode = $user->getBarcode();
					$cronLogEntry->notes .= "- Updating User with $barcode.<br/>";
					$cronLogEntry->update();
					if ($user->canReceiveNotifications('notifyAccount')) {
						$userResult = $this->driver->updateAccountNotifications($user, $ilsNotificationSetting, $cronLogEntry);
						if ($userResult['success']) {
							$result['numUserUpdates']++;
							$result['numMessagesAdded'] +=  $userResult['numMessagesAdded'];
						} else {
							$result['numFailedUserUpdates']++;
							$result['success'] = false;
						}
					}
				}else{
					$cronLogEntry->numErrors++;
					$cronLogEntry->notes .= "Could not find user {$userNotificationToken->userId}.<br/>";
					$cronLogEntry->update();
				}
			}
			if (!is_null($cronLogEntry)) {
				$cronLogEntry->notes .= "Added a total of {$result['numMessagesAdded']} messages.<br/>";
				if ($result['numFailedUserUpdates'] > 0) {
					$cronLogEntry->notes .= "Failed to update {$result['numFailedUserUpdates']} users.<br/>";
				}
				$cronLogEntry->update();
			}
			return $result;
		}else{
			return [
				'success' => false,
				'message' => 'This functionality has not been implemented for this ILS',
			];
		}
	}

	public function updateUserMessageQueue(User $patron): array {
		return $this->driver->updateUserMessageQueue($patron);
	}

	public function submitLocalIllRequest(User $patron, LocalIllForm $localIllForm): array {
		if ($this->driver->supportsLocalIllRequests()) {
			//Check to be sure the user has remaining requests
			if (!$patron->hasRemainingLocalIllRequests()) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'You have reached the maximum number of requests that your library allows. You may request additional titles once the titles you have requested are returned.',
						'isPublicFacing' => true,
					]),
					'title' => translate([
						'text' => 'No Requests Left',
						'isPublicFacing' => true,
					]),
				];
			}
			$patronPType = $patron->getPTypeObj();
			if ($patronPType == null || $patronPType->allowLocalIll === 0) {
				return [
					'success' => false,
					'message' => translate([
						'text' => 'Sorry, your account is not allowed to make requests from other libraries.',
						'isPublicFacing' => true,
					]),
					'title' => translate([
						'text' => 'Requests not allowed',
						'isPublicFacing' => true,
					]),
				];
			}

			return $this->driver->submitLocalIllRequest($patron, $localIllForm);
		} else {
			return [
				'success' => false,
				'message' => translate([
					'text' => 'This ILS does not support local ILL requests',
					'isPublicFacing' => true,
				]),
				'title' => translate([
					'text' => 'Error',
					'isPublicFacing' => true,
				]),
			];
		}
	}

	public function hasIlsConsentSupport(): bool {
		return $this->driver->hasIlsConsentSupport();
	}

	public function isPatronAccountLocked(User $patron, $fine): bool {
		return $this->driver->isPatronAccountLocked($patron, $fine);
	}
}
