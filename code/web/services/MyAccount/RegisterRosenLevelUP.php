<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

// TO DO: activate recaptcha
// TO DO: provide Rosen with Limitless Libraries school list
// TO DO: easily [?] add additional students for a single parent
// TO DO: make use of linked accounts

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
    private $user;
    private $userIsStudent;

    function launch()
    {
        global $interface;
        global $library;
        global $configArray;
        $this->levelUPResult = new stdClass();
        $user = UserAccount::getLoggedInUser();
        $this->configArrayLevelUP = $configArray['RosenLevelUP'];

        if ($user) {
            // TO DO: disable form for non-BTY 21-25
            if ($user->patronType < 21 && $user->patronType > 25) { // Hardcoded for Nashville - patron types for [everything but] K-2
                $this->userIsEligibleStudent = false;
            } else {
                // check whether student already has Rosen LevelUP username
                $this->userIsEligibleStudent = true;
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
                $this->levelUPQuery($this->student_username, 'STUDENT');

                $fields = $this->getLevelUPRegistrationFields();

                if (isset($_REQUEST['submit'])) {
                    /*
                                        require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
                                        $recaptchaValid = RecaptchaSetting::validateRecaptcha();

                                        if (!$recaptchaValid) {
                                            $interface->assign('captchaMessage', 'The CAPTCHA response was incorrect, please try again.');
                                        } else {
                    */
                    //Submit the form to Rosen


                    /* check usernames for availability */

                    $this->levelUPResult->parent_username_avail = 0;
                    $this->levelUPResult->parent_username_ok = 0;
                    $this->levelUPResult->student_username_avail = 0;

                    $parent_username = $_REQUEST['parent_username'];
                    $student_username = $_REQUEST['student_username'];

                    $this->levelUPQuery($parent_username, 'PARENT');
                    $curl = curl_init($this->configArrayLevelUP['lu_api_host'] . '/external/users/' . $parent_username);

                    curl_setopt_array($curl, array(
                        CURLOPT_HEADER => false,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => array(
                            'Cookie: COOKIE-BEARER=' . $cookies['COOKIE-BEARER']
                        ),
                    ));

                    $levelUPResult->QueryResponseParent = json_decode(curl_exec($curl), true);
                    curl_close($curl);

                    if (!isset($levelUPResult->QueryResponseParent['id']) && !empty($parent_username)) {
                        $levelUPResult->parent_username_avail = 1;
                    } elseif ($_REQUEST['parent_email'] == $levelUPResult->QueryResponseParent['email']) { // Allow returning parent to sign up additional students
                        $levelUPResult->parent_username_ok = 1;
                    } else {
                        $levelUPResult->error = 'Parent Username already exists.';
                        $levelUPResult->message = 'Parent Username associated with a different email address already exists. Please register with a different Parent Username.';
                    }

                    $curl = curl_init($this->configArrayLevelUP['lu_api_host'] . '/external/users/' . $student_username);

                    curl_setopt_array($curl, array(
                        CURLOPT_HEADER => false,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => array(
                            'Cookie: COOKIE-BEARER=' . $cookies['COOKIE-BEARER']
                        ),
                    ));

                    $levelUPResult->QueryResponseStudent = json_decode(curl_exec($curl), true);

                    curl_close($curl);

                    if (!isset($student_result['id']) && !empty($student_username)) {
                        $levelUPResult->student_username_avail = 1;
                    } elseif ($_REQUEST['student_email'] == $levelUPResult->QueryResponseStudent['email']) { // Identify re-registering student
                        $levelUPResult->student_username_ok = 1;
                        $levelUPResult->error = 'Student Username already exists.';
                        $levelUPResult->message = 'Student Username has already been registered. Please <a href="https://levelupreader.com/app/#/login">log in to Rose LevelUP</a>';
                    } else {
                        $levelUPResult->error = 'Student Username already exists.';
                        $levelUPResult->message = 'Student Username associated with a different email address already exists. Please register with a different Student Username.';
                    }

                    if (($levelUPResult->parent_username_avail == 1 || $levelUPResult->parent_username_ok == 1) && $levelUPResult->student_username_avail == 1) {
                        $parent_first_name = $_REQUEST['parent_first_name'];
                        $parent_last_name = $_REQUEST['parent_last_name'];
                        $parent_email = $_REQUEST['parent_email'];
                        $parent_pw = $_REQUEST['parent_pw'];
                        $student_grade_level = strtoupper($_REQUEST['student_grade_level']);
                        switch ($student_grade_level) {
                            case 'P':
                                $student_grade_level = 'K'; // Pre-K will be coded as K for LevelUP
                                break;
                            case '3':
                                $student_grade_level = '2'; // 3rd grade will be coded as 2nd grade for LevelUP
                                break;
                        }
                        $student_first_name = $_REQUEST['student_first_name'];
                        $student_last_name = $_REQUEST['student_last_name'];
                        $student_pw = $_REQUEST['student_pw'];

                        $json_string = '[{"districts":';
                        $json_string .= '[{"name": "' . $this->configArrayLevelUP['lu_district_name'] . '",';
                        $json_string .= '"location": "default",';
                        $json_string .= '"districtManagers": [],';
                        $json_string .= '"schools": [{';
                        $json_string .= '"name": "' . $this->configArrayLevelUP['lu_school_name'] . '",';
                        $json_string .= '"classRooms": [{';
                        $json_string .= '"name": "' . $parent_username . '",';
                        $json_string .= '"gradeLevel": "' . $student_grade_level . '",';
                        $json_string .= '"accounts": [{';
                        if ($levelUPResult->parent_username_avail == 1) {
                            $json_string .= '"name": "' . $parent_first_name . '",';
                            $json_string .= '"surname": "' . $parent_last_name . '",';
                            $json_string .= '"username": "' . $parent_username . '",';
                            $json_string .= '"password": "' . $parent_pw . '",';
                            $json_string .= '"role": "TEACHER",';
                            $json_string .= '"email": "' . $parent_email . '",';
                            $json_string .= '"phone": "null",';
                            $json_string .= '"gameSettings": {';
                            $json_string .= '"gameMoney": 100,';
                            $json_string .= '"gameLimit": "MIN_20"';
                            $json_string .= '}';
                            $json_string .= '},{';
                        }
                        $json_string .= '"name": "' . $student_first_name . '",';
                        $json_string .= '"surname": "' . $student_last_name . '",';
                        $json_string .= '"username": "' . $student_username . '",';
                        $json_string .= '"password": "' . $student_pw . '",';
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
                            CURLOPT_HEADER => false,
                            CURLOPT_POST => true,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POSTFIELDS => $json_string,
                            CURLOPT_HTTPHEADER => array(
                                'X-XSRF-TOKEN: ' . $cookies['XSRF-TOKEN'],
                                'Cookie: XSRF-TOKEN=' . $cookies['XSRF-TOKEN'],
                                'Cookie: COOKIE-BEARER=' . $cookies['COOKIE-BEARER'],
                                'Content-Type: application/json'
                            ),
                        ));

                        $levelUPResult->UploadResponse = curl_exec($curl);
                        curl_close($curl);

                        /* check that the usernames were created */

                        if (stripos($levelUPResult->UploadResponse, 'HTTP/2 201') === 0) {
                            // query to ensure parent account exists
                            // query to ensure student account exists, then success!
                        } elseif (stripos($levelUPResult->UploadResponse, 'HTTP/2 400') === 0) {
                            $levelUPResult->error = 'Bad Request';
                            $levelUPResult->status = '27';
                            $levelUPResult->message = 'Rosen LevelUP User Account API yielded ' . $levelUPResult->status . ": User already exists.";
                        } elseif (stripos($levelUPResult->UploadResponse, 'HTTP/2 401') === 0) {
                            $levelUPResult->error = 'Unauthorized';
                            $levelUPResult->status = '401';
                            $levelUPResult->message = 'Rosen LevelUP User Account API yielded ' . $levelUPResult->status . ": " . $levelUPResult->error;
                        } elseif (stripos($levelUPResult->UploadResponse, 'HTTP/2 403') === 0) {
                            $levelUPResult->error = 'Forbidden';
                            $levelUPResult->status = '403';
                            $levelUPResult->message = 'Rosen LevelUP User Account API yielded ' . $levelUPResult->status . ": " . $levelUPResult->error;
                        } elseif (stripos($levelUPResult->UploadResponse, 'HTTP/2 404') === 0) {
                            $levelUPResult->error = 'Not Found';
                            $levelUPResult->status = '404';
                            $levelUPResult->message = 'Rosen LevelUP User Account API yielded ' . $levelUPResult->status . ": " . $levelUPResult->error;
                        } else {
                            $levelUPResult->error = 'Unavailable';
                            $levelUPResult->message = 'Rosen LevelUP User Account API is not currently available';
                        }


                        if (empty($levelUPResult->error)) {

                            $parent_username = $_REQUEST['parent_username'];
                            $student_username = $_REQUEST['student_username'];

                            $curl = curl_init($this->configArrayLevelUP['lu_api_host'] . '/external/users/' . $parent_username);

                            curl_setopt_array($curl, array(
                                CURLOPT_HEADER => false,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER => array(
                                    'Cookie: COOKIE-BEARER=' . $cookies['COOKIE-BEARER']
                                ),
                            ));

                            $parent_result = json_decode(curl_exec($curl), true);
                            curl_close($curl);

                            if (!$parent_result['id']) {
                                $error = 'Parent Username not created.';
                            }

                            $curl = curl_init($this->configArrayLevelUP['lu_api_host'] . '/external/users/' . $student_username);

                            curl_setopt_array($curl, array(
                                CURLOPT_HEADER => false,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER => array(
                                    'Cookie: COOKIE-BEARER=' . $cookies['COOKIE-BEARER']
                                ),
                            ));
                            $student_result = json_decode(curl_exec($curl), true);
                            curl_close($curl);

                            if (!$student_result['id']) {
                                $error = 'Student Username not created.';
                            }

                        }
                    }

                    global $logger;
                    $levelUPErrorMessage = empty($levelUPResult->message) ? '' : ' LevelUP Message :' . $levelUPResult->message;
                    $logger->log('Error from LevelUP. User ID : ' . $user->id . $levelUPErrorMessage, Logger::LOG_NOTICE);
                    $interface->assign('registerRosenLevelUPResult', $levelUPResult);

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
        $this->levelUPResult->LoginResponse = $this->levelUPParseResult('Login', $curl, $loginResponse);
        curl_close($curl);
        preg_match_all('/Set-Cookie:\s*([^;]*)/mi', $this->levelUPResult->LoginResponse, $matches);
        $cookies = array();
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        $this->levelUPResult->cookies = $cookies;
    }

    function levelUPParseResult($method, $curl, $response) {
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerLength = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseHeader = substr($response, 0, $headerLength);
        $responseBody = substr($response, $headerLength);
        if ($responseCode == '200' && $method == 'Login') {
            // let $this->levelUPLogin() grab the cookies
        } elseif ($responseCode == '200' && $method = 'Query') {
            $response = json_decode($responseBody, true);
        } elseif ($responseCode == '401') {
            $response->error = 'Unauthorized';
            $response->status = '401';
            $response->message = 'Rosen LevelUP User Account API yielded ' . $response->status . ": " . $response->error;
        } elseif ($responseCode == '403') {
            $response->error = 'Forbidden';
            $response->status = '403';
            $response->message = 'Rosen LevelUP User Account API yielded ' . $response->status . ": " . $response->error;
        } elseif ($responseCode == '404') {
            $response->error = 'Not Found';
            $response->status = '404';
            $response->message = 'Rosen LevelUP User Account API yielded ' . $response->status . ": " . $response->error;
        } else {
            $response = new stdClass();
            $response->error = 'Unavailable';
            $response->message = 'Rosen LevelUP User Account API is not currently available';
        }
        return $response;
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
        $response = $this->levelUPParseResult('Query', $curl, $response);
        // TO DO ^ catch HTTP error before passing on down...
        curl_close($curl);

        if (!isset($response['id']) && !empty($username)) {
            $response->${"$role_username_avail"} = 1;
        } elseif (isset($_REQUEST['student_email']) && ($_REQUEST['student_email'] == $levelUPResult->QueryResponseStudent['email'])) { // Identify re-registering student
            $response->${"$role_username_ok"} = 1;
            $response->error = $role . ' Username already exists.';
            $response->message = $role . ' Username has already been registered. Please <a href="https://levelupreader.com/app/#/login">log in to Rose LevelUP</a>';
        } else {
            $response = new stdClass();
            $response->error = $role . ' Username already exists.';
            $response->message = $role . ' Username associated with a different email address already exists. Please register with a different ' . $role . ' Username.';
        }
        return $response;
    }
}

