<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

// TO DO: activate recaptcha
// TO DO: easily [?] add additional students for a single parent
// TO DO: make use of linked accounts
// TO DO: disambiguate role TEACHER vs PARENT

class MyAccount_RegisterRosenLevelUP extends MyAccount
{
    private $configArrayLevelUP;
    private $cookies;
    private $homeLocationCode;
    private $homeLocation;
    private $levelUPResult;
    private $parent_first_name;
    private $parent_last_name;
    private $parent_username;
    private $parent_email;
    private $student_first_name;
    private $student_last_name;
    private $student_username;
    private $student_school;
    private $student_grade_level;

    function launch()
    {
        global $interface;
        global $configArray;
        $this->levelUPResult = new stdClass();
        $this->levelUPResult->interfaceArray = array();
        $user = UserAccount::getLoggedInUser();
        $this->configArrayLevelUP = $configArray['RosenLevelUP'];

        if ($user) {
            // Disable form for ineligible patron types
            if ($user->patronType < 21 || $user->patronType > 25) { // Hardcoded for Nashville - patron types for [everything but] K-2
                global $logger;
                $this->levelUPResult->interfaceArray['message'] = 'Error: logged-in patron is not a Pre-Kindergarten through Third Grade student enrolled in a Limitless Libraries-eligible MNPS or charter school. Please log in with a different patron. For further assistance, use the <a href="https://nashvillepl.libanswers.com/form.php?queue_id=2537">Contact Us form</a>.'; // Hardcoded for Nashville
                $logger->log('Error from LevelUP. User ID : ' . $user->id . 'Ineligible user', Logger::LOG_NOTICE);
                $interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
                $this->display('registerRosenLevelUP.tpl', 'Register for Rosen LevelUP');
            } else {
                $this->homeLocationCode = $user->_homeLocationCode;
                $this->homeLocation = $user->_homeLocation;
                $this->student_first_name = $user->firstname;
                $this->student_last_name = $user->lastname;
                $this->student_username = $user->cat_username;
                $this->student_school = $user->_homeLocation;
                switch ($user->patronType) {
                    case 21:
                        $this->student_grade_level = 'P'; // Pre-K will be coded as K for LevelUP
                        break;
                    case 22:
                        $this->student_grade_level = 'K';
                        break;
                    case 23:
                        $this->student_grade_level = '1';
                        break;
                    case 24:
                        $this->student_grade_level = '2';
                        break;
                    case 25:
                        $this->student_grade_level = '3'; // 3rd grade will be coded as 2nd grade for LevelUP
                        break;
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
                    /*
                                        require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
                                        $recaptchaValid = RecaptchaSetting::validateRecaptcha();

                                        if (!$recaptchaValid) {
                                            $interface->assign('captchaMessage', 'The CAPTCHA response was incorrect, please try again.');
                                        } else {
                    */
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
                        $logger->log('Error from LevelUP. User ID : ' . $user->id . '. ' .$this->levelUPResult->parentQueryResponse->error, Logger::LOG_NOTICE);
                        $interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
                    }

                    // check student username for availability
                    $this->levelUPResult->student_username_avail = 0;
                    if ($this->student_username != $_REQUEST['student_username']) { // because we already checked whether the patronid is in use as a Rosen LevelUP username
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
                    }

                    // register new users
                    if (($this->levelUPResult->parent_username_avail == 1 || $this->levelUPResult->parent_username_ok == 1) && $this->levelUPResult->student_username_avail == 1) {
                        $this->levelUPResult->UploadResponse = $this->levelUPUpload();
                        if ($this->levelUPResult->UploadResponse->status == '200') {
                            global $logger;
                            $this->levelUPResult->interfaceArray['success'] = 'success';
                            $this->levelUPResult->interfaceArray['message'] = 'Congratulations! you have successfully registered STUDENT ' . $this->student_username . ' with PARENT ' . $this->parent_username . '. Please <a href="https://levelupreader.com/app/#/login">log in to Rosen LevelUP</a>. For further assistance, use the <a href="https://nashvillepl.libanswers.com/form.php?queue_id=2537">Contact Us form</a>.'; // Hardcoded for Nashville'
                            $logger->log('LevelUP. User ID : ' . $user->id . ' successfully registered STUDENT ' . $this->student_username . ' with PARENT ' . $this->parent_username, Logger::LOG_NOTICE);
                            $interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
                        } else {
                            global $logger;
                            $this->levelUPResult->interfaceArray['message'] = $this->levelUPResult->UploadResponse->message;
                            $logger->log('Error from LevelUP. User ID : ' . $user->id . '. ' . $this->levelUPResult->UploadResponse->error, Logger::LOG_NOTICE);
                            $interface->assign('registerRosenLevelUPResult', $this->levelUPResult->interfaceArray);
                        }
                    }

                    // Pre-fill form with user supplied data
                    foreach ($fields as &$property) {
                        if ($property['type'] == 'section') {
                            foreach ($property['properties'] as &$propertyInSection) {
                                $userValue = $_REQUEST[$propertyInSection['property']];
                                $propertyInSection['default'] = $userValue;
                            }
                        } else {
                            $userValue = $_REQUEST[$property['property']];
                            $property['default'] = $userValue;
                        }
                    }
                }

                $interface->assign('submitUrl', '/MyAccount/RegisterRosenLevelUP');
                $interface->assign('structure', $fields);
                $interface->assign('saveButtonText', 'Register');
// TO DO: reaptcha?
                $fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
                $interface->assign('registerRosenLevelUPForm', $fieldsForm);

                $this->display('registerRosenLevelUP.tpl', 'Register for Rosen LevelUP');
            }
        }
    }


    function getLevelUPRegistrationFields() {
        $fields = array();
        $fields[] = array('property' => 'student_username', 'default' => $this->student_username, 'type' => 'text', 'label' => 'Student Rosen LevelUP Username', 'maxLength' => 40, 'required' => true);
        $fields[] = array('property' => 'student_pw', 'type' => 'storedPassword', 'label' => 'Student Rosen LevelUP Password', 'maxLength' => 40, 'required' => true);
        $fields[] = array('property' => 'student_first_name', 'default' => $this->student_first_name, 'type' => 'text', 'label' => 'Student First Name', 'maxLength' => 40, 'required' => true);
        $fields[] = array('property' => 'student_last_name', 'default' => $this->student_last_name, 'type' => 'text', 'label' => 'Student Last Name', 'maxLength' => 40, 'required' => true);
        $locationList = array();
        $locationList[0] = "not enrolled in an MNPS school";
        $locationList[$this->homeLocationCode] = $this->homeLocation;
        $fields[] = array('property' => 'student_school', 'default' => $this->homeLocationCode, 'type' => 'enum', 'label' => 'Student School', 'values' => $locationList, 'required' => true);
        $fields[] = array('property' => 'student_grade_level', 'default' => $this->student_grade_level, 'type' => 'enum', 'label' => 'Student Grade Level', 'values' => array('P' => 'Pre-K', 'K', '1', '2', '3'), 'required' => true); // TO DO: remove 4th grade
        $fields[] = array('property' => 'parent_username', 'default' => $this->parent_username, 'type' => 'text', 'label' => 'Parent Rosen LevelUP Username', 'maxLength' => 40, 'required' => true);
        $fields[] = array('property' => 'parent_pw', 'type' => 'storedPassword', 'label' => 'Parent Rosen LevelUP Password', 'maxLength' => 40, 'required' => true);
        $fields[] = array('property' => 'parent_first_name', 'default' => $this->parent_first_name, 'type' => 'text', 'label' => 'Parent First Name', 'maxLength' => 40, 'required' => true);
        $fields[] = array('property' => 'parent_last_name', 'default' => $this->parent_last_name, 'type' => 'text', 'label' => 'Parent Last Name', 'maxLength' => 40, 'required' => true);
        $fields[] = array('property' => 'parent_email', 'default' => $this->parent_email, 'type' => 'email', 'label' => 'Parent Email', 'maxLength' => 128, 'required' => true);
        return $fields;
    }

    function levelUPLogin()
    {
        $curl = curl_init($this->configArrayLevelUP['lu_api_host'] . '/api/login');
        curl_setopt_array($curl, array(
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => "{\"username\": \"" . $this->configArrayLevelUP['lu_api_un'] . "\",\"password\": \"" . $this->configArrayLevelUP['lu_api_pw'] . "\"}",
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
            $parseResponse->message = 'Rosen LevelUP User Account API yielded ' . $parseResponse->status . ": " . $parseResponse->error;
        } elseif ($responseCode == '404') {
            $parseResponse->content = json_decode($responseBody, true);
            $parseResponse->error = 'Not found';
            $parseResponse->status = '404';
            $parseResponse->message = 'Rosen LevelUP User Account API yielded ' . $parseResponse->status . ": " . $parseResponse->error;
        } elseif ($responseCode) {
            $parseResponse->status = $responseCode;
            $parseResponse->message = 'Rosen LevelUP User Account API yielded HTTP response code ' . $parseResponse->status;
        } else {
            $parseResponse = new stdClass();
            $parseResponse->error = 'Unavailable';
            $parseResponse->message = 'Rosen LevelUP User Account API is not currently available';
        }
        return $parseResponse;
    }

    function levelUPQuery($username, $role = 'STUDENT') {
        /* query LevelUP username */
        $curl = curl_init($this->configArrayLevelUP['lu_api_host'] . '/external/users/' . $username);
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

        if ($queryResponse->status == '404') { // i.e., $username not found
            $this->levelUPResult->{strtolower($role)."_username_avail"} = 1;
        } elseif ($queryResponse->status == '200') { // i.e., username found
            $queryResponse->error = $role . ' Username already exists.';
            $queryResponse->message = $role . ' Username ' . $username . ' has already been registered with Rosen LevelUP';
            if ($role == 'PARENT') {
                $queryResponse->message .= ' with a different email address';
            }
            $queryResponse->message .= '. Please <a href="https://levelupreader.com/app/#/login">log in to Rosen LevelUP</a> or register with a different ' . $role . ' Username';
            if ($role == 'PARENT') {
                $queryResponse->message .= ' or email address';
            }
            $queryResponse->message .= '. For further assistance, use the <a href="https://nashvillepl.libanswers.com/form.php?queue_id=2537">Contact Us form</a>.'; // Hardcoded for Nashville
        } else {
// ?
        }
        return $queryResponse;
    }

    function levelUPUpload() {
        $this->student_grade_level = strtoupper($_REQUEST['student_grade_level']);
        switch ($this->student_grade_level) {
            case 'P':
                $this->student_grade_level = 'K'; // Pre-K will be coded as K for LevelUP
                break;
            case '3':
                $this->student_grade_level = '2'; // 3rd grade will be coded as 2nd grade for LevelUP
                break;
        }
        $this->student_first_name = $_REQUEST['student_first_name'];
        $this->student_last_name = $_REQUEST['student_last_name'];
        $this->student_pw = $_REQUEST['student_pw'];

        $json_string = '[{"districts":';
        $json_string .= '[{"name": "' . $this->configArrayLevelUP['lu_district_name'] . '",';
        $json_string .= '"location": "default",';
        $json_string .= '"districtManagers": [],';
        $json_string .= '"schools": [{';
        $json_string .= '"name": "' . $this->configArrayLevelUP['lu_school_name'] . '",';
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
        $curl = curl_init($this->configArrayLevelUP['lu_api_host'] . '/external/users/upload');

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

}

