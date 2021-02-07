<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';
require_once ROOT_DIR . '/sys/Rosen/RosenLevelUPSetting.php';

// TO DO: activate recaptcha
// TO DO: clean up Upload json build
// TO DO: easily [?] add additional students for a single parent
// TO DO: make use of linked accounts
// TO DO: disambiguate role TEACHER vs PARENT

class MyAccount_RegisterRosenLevelUP extends MyAccount
{
	private $cookies;
	private $levelUPResult;
	private $parent_email;
	private $parent_first_name;
	private $parent_last_name;
	private $parent_username;
	private $rosen_help;
	private $rosenLevelUPSetting;
	private $student_first_name;
	private $student_grade_level;
	private $student_is_eligible;
	private $student_last_name;
	private $student_school_code;
	private $student_school_name;
	private $student_username;

	function launch()
	{
		global $interface;
		$this->levelUPResult = new stdClass();
		$this->levelUPResult->interfaceArray = array();
		$user = UserAccount::getLoggedInUser();
		$this->rosenLevelUPSetting = new RosenLevelUPSetting();
		if (!$this->rosenLevelUPSetting->find(true)){
			global $logger;
			$this->levelUPResult->interfaceArray['message'] = translate(['text' => 'rosen_error_setup', 'defaultText' => 'Error: Rosen LevelUP is not set up for this Library System']);
			$logger->log('Error: Rosen LevelUP is not set up for this Library System', Logger::LOG_NOTICE);
			$interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
			$this->display('registerRosenLevelUP.tpl', 'Register for Rosen LevelUP');
			return;
		}

		$this->rosen_help = translate(['text' => 'rosen_help', 'defaultText' => 'For further assistance, use the Help menu.']);

		if ($user) {
			// Disable form for ineligible patron types
			$this->student_is_eligible = false;
			if ($this->rosenLevelUPSetting->lu_eligible_ptypes == '*') {
				$this->student_is_eligible = true;
			} else if (preg_match("/\b({$user->patronType})\b/", $this->rosenLevelUPSetting->lu_eligible_ptypes)) {
				$this->student_is_eligible = true;
			}
			if ($this->student_is_eligible == false) {
				global $logger;
				$this->levelUPResult->interfaceArray['message'] = translate(['text' => 'rosen_error_ineligible', 'defaultText' => 'Error: patron is not eligible to register for Rosen LevelUP. <a href=\"/MyAccount/RegisterRosenLevelUP\">Log in with a different Library account</a>.']);
				$logger->log('Error from LevelUP. User ID : ' . $user->id . 'Ineligible user', Logger::LOG_NOTICE);
				$interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
				$this->display('registerRosenLevelUP.tpl', 'Register for Rosen LevelUP');
				UserAccount::softLogout();
			} elseif ($this->student_is_eligible == true) {
				$this->student_first_name = $user->firstname;
				$this->student_last_name = $user->lastname;
				$this->student_username = $user->cat_username;
				if (!empty($user->getHomeLocation()->subdomain)) {
					$this->student_school_code = $user->getHomeLocation()->subdomain;
				} else {
					$this->student_school_code = $user->getHomeLocation()->code;
				}
				$this->student_school_name = $user->getHomeLocation()->displayName;

				if (!empty($this->rosenLevelUPSetting->lu_ptypes_k) && preg_match($this->rosenLevelUPSetting->lu_ptypes_k, $user->patronType) == 1) {
					$this->student_grade_level = 'K';
				}
				if (!empty($this->rosenLevelUPSetting->lu_ptypes_1) && preg_match($this->rosenLevelUPSetting->lu_ptypes_1, $user->patronType) == 1) {
					$this->student_grade_level = '1';
				}
				if (!empty($this->rosenLevelUPSetting->lu_ptypes_2) && preg_match($this->rosenLevelUPSetting->lu_ptypes_2, $user->patronType) == 1) {
					$this->student_grade_level = '2';
				}

				$this->levelUPLogin();
				$fields = $this->getLevelUPRegistrationFields();

				if (!isset($_REQUEST['submit'])) {
					// check whether student already has Rosen LevelUP username
					$this->levelUPResult->student_username_avail = 0;
					$this->levelUPResult->studentQueryResponse = $this->levelUPQuery($this->student_username, 'STUDENT');
					if ($this->levelUPResult->studentQueryResponse->status != '404') {
						global $logger;
						$this->levelUPResult->interfaceArray['message'] = $this->levelUPResult->studentQueryResponse->message;
						$logger->log('Error from LevelUP. User ID : ' . $user->id . '. ' . $this->levelUPResult->studentQueryResponse->error, Logger::LOG_NOTICE);
						$interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
					} elseif ($this->levelUPResult->studentQueryResponse->status == '404') {
						$this->levelUPResult->student_username_avail = 1;
					}
				} elseif (isset($_REQUEST['submit'])) {
					// recaptcha evaluation
					require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
					$recaptchaValid = RecaptchaSetting::validateRecaptcha();
					if (!$recaptchaValid) {
						$interface->assign('captchaMessage', translate('The CAPTCHA response was incorrect, please try again.'));
					} else {
						//Submit the form to Rosen
						// check parent username for availability; if it ain't available, check for identical email; if identical email, allow parent to register additional students
						$this->levelUPResult->parent_username_avail = 0;
						$this->levelUPResult->parent_username_ok = 0;
						$this->parent_email = $_REQUEST['parent_email'];
						$this->parent_username = $_REQUEST['parent_username'];
						$this->levelUPResult->parentQueryResponse = $this->levelUPQuery($this->parent_username, 'PARENT');
						if ($this->levelUPResult->parentQueryResponse->status == '200') {
							if ($this->parent_email == $this->levelUPResult->parentQueryResponse->content['email']) {
								$this->levelUPResult->parent_username_ok = 1;
							} else {
								global $logger;
								$this->levelUPResult->interfaceArray['message'] = $this->levelUPResult->parentQueryResponse->message;
								$logger->log('Error from LevelUP. User ID : ' . $user->id . '. ' . $this->levelUPResult->parentQueryResponse->error, Logger::LOG_NOTICE);
								$interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
							}
						} elseif ($this->levelUPResult->parentQueryResponse->status == '404') { // i.e., parent username not found
							$this->levelUPResult->parent_username_avail = 1;
						} else {
							global $logger;
							$this->levelUPResult->interfaceArray['message'] = $this->levelUPResult->parentQueryResponse->message;
							$logger->log('Error from LevelUP. User ID : ' . $user->id . '. ' . $this->levelUPResult->parentQueryResponse->error, Logger::LOG_NOTICE);
							$interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
						}

						// check student username for availability
						$this->levelUPResult->student_username_avail = 0;
						$this->levelUPResult->studentQueryResponse = $this->levelUPQuery($_REQUEST['student_username'], 'STUDENT');
						if ($this->levelUPResult->studentQueryResponse->status != '404') {
							global $logger;
							$this->levelUPResult->interfaceArray['message'] = $this->levelUPResult->studentQueryResponse->message;
							$logger->log('Error from LevelUP. User ID : ' . $user->id . '. ' . $this->levelUPResult->studentQueryResponse->error, Logger::LOG_NOTICE);
							$interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
						} elseif ($this->levelUPResult->studentQueryResponse->status == '404') {
							$this->student_username = $_REQUEST['student_username'];
							$this->levelUPResult->student_username_avail = 1;
						}

						// register new users
						if (($this->levelUPResult->parent_username_avail == 1 || $this->levelUPResult->parent_username_ok == 1) && $this->levelUPResult->student_username_avail == 1) {
							$this->levelUPResult->UploadResponse = $this->levelUPUpload();
							if ($this->levelUPResult->UploadResponse->status == '200') {
								global $logger;
								$this->levelUPResult->interfaceArray['success'] = 'success';
								$this->levelUPResult->interfaceArray['message'] = translate(['text' => 'rosen_success', 'defaultText' => "<p>Congratulations!!! You have successfully registered </p><p>STUDENT Username %1% with </p><p>PARENT Username %2%. </p><p>You will receive an email shortly with these details. </p><p>Please <a href=\"https://levelupreader.com/app/#/login\">log in to Rosen LevelUP</a> or <a href=\"/MyAccount/RegisterRosenLevelUP\">register another student</a>.</p>", 1 => $this->student_username, 2 => $this->parent_username]);
								$logger->log('LevelUP. User ID : ' . $user->id . ' successfully registered STUDENT ' . $this->student_username . ' with PARENT ' . $this->parent_username, Logger::LOG_NOTICE);

								// following successful registration, email the parent with registration information
								try {
									$body = $this->parent_first_name . " " . $this->parent_last_name . "\n\n";
									$body .= translate(['text' => 'rosen_email_1', 'defaultText' =>'Welcome to LevelUP! Your PARENT username: %2%. Your STUDENT\'S username: %1%.', 1 => $this->student_username, 2 => $this->parent_username]);
									$body_template = $interface->fetch('Emails/rosen-levelup.tpl');
									$body .= $body_template;
									require_once ROOT_DIR . '/sys/Email/Mailer.php';
									$mail = new Mailer();
									$subject = 'Welcome to LevelUP, brought to you by the Nashville Public Library';
									$mail->send($this->parent_email, $subject, $body, 'no-reply@nashville.gov');
								} catch (Exception $e) {
									// SendGrid Failed
								}

								$interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
								UserAccount::softLogout();
							} else {
								global $logger;
				 				$this->levelUPResult->interfaceArray['message'] = $this->levelUPResult->UploadResponse->message;
								$logger->log('Error from LevelUP. User ID : ' . $user->id . '. ' . $this->levelUPResult->UploadResponse->error, Logger::LOG_NOTICE);
								$interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
							}
						}
					}

					// Pre-fill form with user supplied data
					//TODO: Move this to DataObjectUtil so we can reload forms based on submission when they fail validation
					foreach ($fields as &$property) {
						if ($property['type'] == 'section') {
							foreach ($property['properties'] as &$propertyInSection) {
								if ($property['type'] != 'storedPassword' && $property['type'] != 'password'){
									$userValue = $_REQUEST[$propertyInSection['property']];
									$propertyInSection['default'] = $userValue;
								}
							}
						} elseif ($property['type'] != 'storedPassword' && $property['type'] != 'password'){
							$userValue = $_REQUEST[$property['property']];
							$property['default'] = $userValue;
						}
					}
				}

				$interface->assign('submitUrl', '/MyAccount/RegisterRosenLevelUP');
				$interface->assign('structure', $fields);
				$interface->assign('saveButtonText', 'Register');
				// Set up captcha to limit spam self registrations
				require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
				$recaptcha = new RecaptchaSetting();
				if ($recaptcha->find(true) && !empty($recaptcha->publicKey)) {
					$captchaCode = recaptcha_get_html($recaptcha->publicKey);
					$interface->assign('captcha', $captchaCode);
				}
				$interface->assign('formLabel', 'Register for Rosen LevelUP');

				$fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
				$interface->assign('registerRosenLevelUPForm', $fieldsForm);
				$this->display('registerRosenLevelUP.tpl', 'Register for Rosen LevelUP');
			}
		}
	}

	function getLevelUPRegistrationFields() {
		$fields = array();
		$fields[] = array('property' => 'student_username', 'default' => $this->student_username, 'type' => 'text', 'label' => 'Student Rosen LevelUP Username', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property' => 'student_pw', 'type' => 'storedPassword', 'label' => 'Student Rosen LevelUP Password', 'maxLength' => 40, 'required' => true, 'repeat' => true);
		$fields[] = array('property' => 'student_first_name', 'default' => $this->student_first_name, 'type' => 'text', 'label' => 'Student First Name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property' => 'student_last_name', 'default' => $this->student_last_name, 'type' => 'text', 'label' => 'Student Last Name', 'maxLength' => 40, 'required' => true);
		$locationList = array();
		$locationList[0] = "school not listed";
		$locationList[$this->rosenLevelUPSetting->lu_location_code_prefix . $this->student_school_code] = $this->student_school_name;
		$fields[] = array('property' => 'student_school', 'default' => $this->rosenLevelUPSetting->lu_location_code_prefix . $this->student_school_code, 'type' => 'enum', 'label' => 'Student School', 'values' => $locationList, 'required' => true);
		$fields[] = array('property' => 'student_grade_level', 'default' => $this->student_grade_level, 'type' => 'enum', 'label' => 'Student Grade Level, K-2', 'values' => array('K', '1', '2'), 'required' => true);
		$fields[] = array('property' => 'parent_username', 'default' => $this->parent_username, 'type' => 'text', 'label' => 'Parent Rosen LevelUP Username', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property' => 'parent_pw', 'type' => 'storedPassword', 'label' => 'Parent Rosen LevelUP Password', 'maxLength' => 40, 'required' => true, 'repeat' => true);
		$fields[] = array('property' => 'parent_first_name', 'default' => $this->parent_first_name, 'type' => 'text', 'label' => 'Parent First Name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property' => 'parent_last_name', 'default' => $this->parent_last_name, 'type' => 'text', 'label' => 'Parent Last Name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property' => 'parent_email', 'default' => $this->parent_email, 'type' => 'email', 'label' => 'Parent Email', 'maxLength' => 128, 'required' => true);
		return $fields;
	}

	function levelUPLogin()
	{
		$curl = curl_init($this->rosenLevelUPSetting->lu_api_host . '/api/login');
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => "{\"username\": \"" . $this->rosenLevelUPSetting->lu_api_un . "\",\"password\": \"" . $this->rosenLevelUPSetting->lu_api_pw . "\"}",
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json"
			),
		));
		$loginResponse = curl_exec($curl);
		$this->levelUPResult->LoginResponse = $this->levelUPParseResponse('Login', $curl, $loginResponse);
		curl_close($curl);
		preg_match_all('/Set-Cookie:\s*([^;]*)/mi', $this->levelUPResult->LoginResponse, $matches);
		$cookies = array();
		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}
		$this->levelUPResult->cookies = $cookies;
	}

	function levelUPParseResponse($method, $curl, $response) {
		$parseResponse = new stdClass();
		$responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$headerLength = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$responseHeader = substr($response, 0, $headerLength);
		$responseBody = substr($response, $headerLength);
		if ($responseCode == '200' && $method == 'Login') {
			$parseResponse = $response; // let $this->levelUPLogin() do the work to grab the cookies
		} elseif ($responseCode == '200' && ($method == 'Query' || $method == 'Upload')) {
			$parseResponse->content = json_decode($responseBody, true);
			$parseResponse->error = '';
			$parseResponse->status = $responseCode;
			$parseResponse->message = translate(['text' => 'rosen_info_http_response', 'defaultText' => "Rosen LevelUP User Account API yielded HTTP response code"]) . $parseResponse->status;
		} elseif ($responseCode == '404') {
			$parseResponse->content = json_decode($responseBody, true);
			$parseResponse->error = 'Not found';
			$parseResponse->status = '404';
			$parseResponse->message = translate(['text' => 'rosen_info_http_response', 'defaultText' => "Rosen LevelUP User Account API yielded HTTP response code"]) . $parseResponse->status;
		} elseif ($responseCode) {
			$parseResponse->status = $responseCode;
			$parseResponse->message = translate(['text' => 'rosen_info_http_response', 'defaultText' => "Rosen LevelUP User Account API yielded HTTP response code"]) . $parseResponse->status;
		} else {
			$parseResponse = new stdClass();
			$parseResponse->error = 'Unavailable';
			$parseResponse->message = translate(['text' => 'rosen_error_unavailable', 'defaultText' => 'Rosen LevelUP User Account API is not currently available']);
		}
		return $parseResponse;
	}

	function levelUPQuery($username, $role = 'STUDENT') {
		/* query LevelUP username */
		$curl = curl_init($this->rosenLevelUPSetting->lu_api_host . '/external/users/' . $username);
		curl_setopt_array($curl, array(
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array(
				'Cookie: COOKIE-BEARER=' . $this->levelUPResult->cookies['COOKIE-BEARER']
			),
		));
		$response = curl_exec($curl);
		$queryResponse = $this->levelUPParseResponse('Query', $curl, $response);
		curl_close($curl);
		$queryResponse->message = '';
		if ($queryResponse->status == '404') { // i.e., username not found
			$this->levelUPResult->{strtolower($role)."_username_avail"} = 1;
		} elseif ($queryResponse->status == '200') { // i.e., username found
			$queryResponse->error = $role . ' Username already exists.';
			if ($role == 'STUDENT') {
				$queryResponse->message = translate(['text' => 'rosen_student_username_found', 'defaultText' => "%1% %2% has already been registered with Rosen LevelUP. Please <a href=\"https://levelupreader.com/app/#/login\">log in to Rosen LevelUP</a> or register with a different Username for this %1%", 1 => translate($role), 2 => $username]);
			} elseif ($role == 'PARENT') {
				$queryResponse->message = translate(['text' => 'rosen_parent_username_found', 'defaultText' => "%1% %2% has already been registered with Rosen LevelUP with a different email address. Please <a href=\"https://levelupreader.com/app/#/login\">log in to Rosen LevelUP</a> or register with a different Username or a different email for this %1%", 1 => translate($role), 2 => $username]);
			}
		} else {
			// TO DO: figure out what the other cases are and implement them
		}
		return $queryResponse;
	}

	function levelUPUpload() {
		$this->student_grade_level = strtoupper($_REQUEST['student_grade_level']);
		$this->student_first_name = $_REQUEST['student_first_name'];
		$this->student_last_name = $_REQUEST['student_last_name'];
		$this->student_pw = $_REQUEST['student_pw'];

		$json_string = '[{"districts":';
		$json_string .= '[{"name": "' . $this->rosenLevelUPSetting->lu_district_name . '",';
		$json_string .= '"location": "default",';
		$json_string .= '"districtManagers": [],';
		$json_string .= '"schools": [{';
		$json_string .= '"name": "' . $this->rosenLevelUPSetting->lu_school_name . '",';
		$json_string .= '"classRooms": [{';
		$json_string .= '"name": "' . $this->parent_username . '",';
		$json_string .= '"gradeLevel": "' . $this->student_grade_level . '",';
		$json_string .= '"accounts": [{';
		if ($this->levelUPResult->parent_username_avail == 1) {
			$this->parent_first_name = $_REQUEST['parent_first_name'];
			$this->parent_last_name = $_REQUEST['parent_last_name'];
			$this->parent_email = $_REQUEST['parent_email'];
			$this->parent_pw = $_REQUEST['parent_pw'];
			$json_string .= '"name": "' . $this->parent_first_name . '",';
			$json_string .= '"surname": "' . $this->parent_last_name . '",';
			$json_string .= '"username": "' . $this->parent_username . '",';
			$json_string .= '"password": "' . $this->parent_pw . '",';
			$json_string .= '"role": "TEACHER",';
			$json_string .= '"email": "' . $this->parent_email . '",';
			$json_string .= '"phone": "null",';
			$json_string .= '"gameSettings": {';
			$json_string .= '"gameMoney": 100,';
			$json_string .= '"gameLimit": "MIN_20"';
			$json_string .= '}';
			$json_string .= '},{';
		}
		$json_string .= '"name": "' . $this->student_first_name . '",';
		$json_string .= '"surname": "' . $this->student_last_name . '",';
		$json_string .= '"username": "' . $this->student_username . '",';
		$json_string .= '"password": "' . $this->student_pw . '",';
		$json_string .= '"role": "STUDENT",';
		$json_string .= '"email": "null",';
		$json_string .= '"phone": "null",';
		$json_string .= '"gameSettings": {';
		$json_string .= '"gameMoney": 100,';
		$json_string .= '"gameLimit": "MIN_20"';
		$json_string .= '}';
		$json_string .= '}],';
		$json_string .= '"paymentSetting": null';
		$json_string .= '}],';
		$json_string .= '"schoolManagers": []';
		$json_string .= '}]}],';
		$json_string .= '"multiDistrictManager": null';
		$json_string .= '}]\'';
		$curl = curl_init($this->rosenLevelUPSetting->lu_api_host . '/external/users/upload');

		curl_setopt_array($curl, array(
			CURLOPT_HEADER => true,
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS => $json_string,
			CURLOPT_HTTPHEADER => array(
				'X-XSRF-TOKEN: ' . $this->levelUPResult->cookies['XSRF-TOKEN'],
				'Cookie: XSRF-TOKEN=' . $this->levelUPResult->cookies['XSRF-TOKEN'],
				'Cookie: COOKIE-BEARER=' . $this->levelUPResult->cookies['COOKIE-BEARER'],
				'Content-Type: application/json'
			),
		));
		$response = curl_exec($curl);
		$uploadResponse = $this->levelUPParseResponse('Upload', $curl, $response);
		curl_close($curl);
		return $uploadResponse;
	}

	function getBreadcrumbs()
	{
		return [];
	}
}

