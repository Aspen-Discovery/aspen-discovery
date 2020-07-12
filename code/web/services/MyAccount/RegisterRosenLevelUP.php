<?php

require_once ROOT_DIR . '/services/MyAccount/MyAccount.php';
require_once ROOT_DIR . '/recaptcha/recaptchalib.php';

class MyAccount_RegisterRosenLevelUP extends MyAccount {

    function launch()
    {
        global $interface;
        global $library;
        global $configArray;
        $user = UserAccount::getLoggedInUser();

        $userIsStudent = false;
        $parent_first_name = '';
        $parent_last_name = '';
        $parent_username = '';
        $parent_email = '';
        $student_first_name = '';
        $student_last_name = '';
        $student_username = '';
        $student_school = '';
        $student_grade_level = '';

        if ($user) {
            // LINKED ACCOUNTS
            // TO DO: establish logged in user as Student and autofill form
            // Determine which user we are showing/updating settings for
            //$linkedUsers = $user->getLinkedUsers();

            //$patronId = isset($_REQUEST['patronId']) ? $_REQUEST['patronId'] : $user->id;
            /** @var User $patron */
            //$patron = $user->getUserReferredTo($patronId);

            // Linked Accounts Selection Form set-up
            //if (count($linkedUsers) > 0) {
            //    array_unshift($linkedUsers, $user); // Adds primary account to list for display in account selector
            //    $interface->assign('linkedUsers', $linkedUsers);
            //    $interface->assign('selectedUser', $patronId);
            //}

            // TO DO: disable form for non-BTY 21-25
            if ($user->patronType < 21 && $user->patronType > 25) { // Hardcoded for Nashville - patron types for [everything but] K-2
                $userIsEligibleStudent = false;
            } else {
                $userIsEligibleStudent = true;
                $student_first_name = $user->firstname;
                $student_last_name = $user->lastname;
                $student_username = $user->cat_username;
                $student_school = $user->_homeLocation;
                switch ($user->patronType) {
                    case 21:
                        $student_grade_level = 'P'; // Pre-K will be coded as K for LevelUP
                        break;
                    case 22:
                        $student_grade_level = 'K';
                        break;
                    case 23:
                        $student_grade_level = '1';
                        break;
                    case 24:
                        $student_grade_level = '2';
                        break;
                    case 25:
                        $student_grade_level = '3'; // 3rd grade will be coded as 2nd grade for LevelUP
                        break;
                }

                /** @var  CatalogConnection $catalog */
                $catalog = CatalogFactory::getCatalogConnectionInstance();

                $fields = array();
                $fields[] = array('property' => 'student_username', 'default' => $student_username, 'type' => 'text', 'label' => 'Student Rosen LevelUP Username', 'maxLength' => 40, 'required' => true);
                $fields[] = array('property' => 'student_pw', 'type' => 'storedPassword', 'label' => 'Student Rosen LevelUP Password', 'maxLength' => 40, 'required' => true);
                $fields[] = array('property' => 'student_first_name', 'default' => $student_first_name, 'type' => 'text', 'label' => 'Student First Name', 'maxLength' => 40, 'required' => true);
                $fields[] = array('property' => 'student_last_name', 'default' => $student_last_name, 'type' => 'text', 'label' => 'Student Last Name', 'maxLength' => 40, 'required' => true);
                $locationList = array();
                $locationList[0] = "not enrolled in an MNPS school";
                $locationList[$user->_homeLocationCode] = $user->_homeLocation;
                $fields[] = array('property' => 'student_school', 'default' => $user->_homeLocationCode, 'type' => 'enum', 'label' => 'Student School', 'values' => $locationList, 'required' => true);
                $fields[] = array('property' => 'student_grade_level', 'default' => $student_grade_level, 'type' => 'enum', 'label' => 'Student Grade Level', 'values' =>array('P'=>'Pre-K','K','1','2','3'), 'required' => true); // TO DO: remove 4th grade
                $fields[] = array('property' => 'parent_username', 'default' => $parent_username, 'type' => 'text', 'label' => 'Parent Rosen LevelUP Username', 'maxLength' => 40, 'required' => true);
                $fields[] = array('property' => 'parent_pw', 'type' => 'storedPassword', 'label' => 'Parent Rosen LevelUP Password', 'maxLength' => 40, 'required' => true);
                $fields[] = array('property' => 'parent_first_name', 'default' => $parent_first_name, 'type' => 'text', 'label' => 'Parent First Name', 'maxLength' => 40, 'required' => true);
                $fields[] = array('property' => 'parent_last_name', 'default' => $parent_last_name, 'type' => 'text', 'label' => 'Parent Last Name', 'maxLength' => 40, 'required' => true);
                $fields[] = array('property' => 'parent_email', 'default' => $parent_email, 'type' => 'email', 'label' => 'Parent Email', 'maxLength' => 128, 'required' => true);

                // TO DO: provide Rosen with Limitless Libraries school list
                // TO DO: easily [?] add additional students

                if (isset($_REQUEST['submit'])) {
/*
                    require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
                    $recaptchaValid = RecaptchaSetting::validateRecaptcha();

                    if (!$recaptchaValid) {
                        $interface->assign('captchaMessage', 'The CAPTCHA response was incorrect, please try again.');
                    } else {
*/
                        //Submit the form to Rosen

                        /* login to the api */
                        $curl = curl_init($configArray['RosenLevelUP']['lu_api_host'] . '/api/login');

                        curl_setopt_array($curl, array(
                            CURLOPT_HEADER => true,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POSTFIELDS => "{\"username\": \"" . $configArray['RosenLevelUP']['lu_api_un'] . "\",\"password\": \"" . $configArray['RosenLevelUP']['lu_api_pw'] . "\"}",
                            CURLOPT_HTTPHEADER => array(
                                "Content-Type: application/json"
                            ),
                        ));

                        $response = curl_exec($curl);
                        curl_close($curl);

                        /* parse authentication cookies */

                        preg_match_all('/Set-Cookie:\s*([^;]*)/mi', $response, $matches);
                        $cookies = array();
                        foreach ($matches[1] as $item) {
                            parse_str($item, $cookie);
                            $cookies = array_merge($cookies, $cookie);
                        }

                    /* check usernames for availability */

                        $parent_username_avail = 0;
                        $student_username_avail = 0;
                        $error = false;

                        $parent_username = $_REQUEST['parent_username'];
                        $student_username = $_REQUEST['student_username'];

                        $curl = curl_init($configArray['RosenLevelUP']['lu_api_host'] . '/external/users/' . $parent_username);

                        curl_setopt_array($curl, array(
                            CURLOPT_HEADER => false,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTPHEADER => array(
                                'Cookie: COOKIE-BEARER=' . $cookies['COOKIE-BEARER']
                            ),
                        ));

                        $parent_result = json_decode(curl_exec($curl), true);

                        curl_close($curl);

                        if (!isset($parent_result['id']) && !empty($parent_username)) {
                            $parent_username_avail = 1;
                        } else {
                            $error = true; // parent username already exists
                        }

                        $curl = curl_init($configArray['RosenLevelUP']['lu_api_host'] . '/external/users/' . $student_username);

                        curl_setopt_array($curl, array(
                            CURLOPT_HEADER => false,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_HTTPHEADER => array(
                                'Cookie: COOKIE-BEARER=' . $cookies['COOKIE-BEARER']
                            ),
                        ));

                        $student_result = json_decode(curl_exec($curl), true);

                        curl_close($curl);

                        if (!isset($student_result['id']) && !empty($student_username)) {
                            $student_username_avail = 1;
                        } else {
                            $error = true; // student username already exists
                        }

                        /* set error to true if usernames not available, otherwise create classroom and accounts */
                        // TO DO: if username is present, perhaps registrants just need to log in? API Query *might* return extant user's password

                        if ($parent_username_avail == 0 || $student_username_avail == 0) {
                            $error = true;
                        } else if ($parent_username_avail == 1 && $student_username_avail == 1) {

                            $parent_first_name = $_REQUEST['parent_first_name'];
                            $parent_last_name = $_REQUEST['parent_last_name'];
                            $parent_email = $_REQUEST['parent_email'];
                            $parent_pw = $_REQUEST['parent_pw'];
                            $student_grade_level = strtoupper($_REQUEST['student_grade_level']);
                            switch($student_grade_level) {
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
                            $json_string .= '[{"name": "' . $configArray['RosenLevelUP']['lu_district_name'] . '",';
                            $json_string .= '"location": "default",';
                            $json_string .= '"districtManagers": [],';
                            $json_string .= '"schools": [{';
                            $json_string .= '"name": "' . $configArray['RosenLevelUP']['lu_school_name'] . '",';
                            $json_string .= '"classRooms": [{';
                            $json_string .= '"name": "' . $parent_username . '",';
                            $json_string .= '"gradeLevel": "' . $student_grade_level . '",';
                            $json_string .= '"accounts": [{';
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
                            $curl = curl_init($configArray['RosenLevelUP']['lu_api_host'] . '/external/users/upload');

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

                            $response = curl_exec($curl);
print_r('create users response: ');
var_dump($response);

                            curl_close($curl);
                        }

                        /* check that the usernames were created */

                        if (!$error) {
                            $parent_username_avail = 0;
                            $student_username_avail = 0;
                            $error = false;

                            $parent_username = $_REQUEST['parent_username'];
                            $student_username = $_REQUEST['student_username'];

                            $curl = curl_init($configArray['RosenLevelUP']['lu_api_host'] . '/external/users/' . $parent_username);

                            curl_setopt_array($curl, array(
                                CURLOPT_HEADER => false,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER => array(
                                    'Cookie: COOKIE-BEARER=' . $cookies['COOKIE-BEARER']
                                ),
                            ));

                            $parent_result = json_decode(curl_exec($curl), true);
var_dump($parent_result);
                            curl_close($curl);

                            if (!$parent_result['id']) {
                                $error = true;
                            }

                            $curl = curl_init($configArray['RosenLevelUP']['lu_api_host'] . '/external/users/' . $student_username);

                            curl_setopt_array($curl, array(
                                CURLOPT_HEADER => false,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER => array(
                                    'Cookie: COOKIE-BEARER=' . $cookies['COOKIE-BEARER']
                                ),
                            ));
                            $student_result = json_decode(curl_exec($curl), true);
var_dump($student_result);
                            curl_close($curl);

                            if (!$student_result['id']) {
                                $error = true;
                            }

                        }

                        /* redirect to confirmation or error page */
/*
                        if (!$error) {
                            header('Location: levelup_signup_form_confirmation.html');
                        } else {
                            header('Location: levelup_signup_form_error.html');
                        }
*/

                    global $logger;
                    $levelUPErrorMessage = empty($levelUPResult->message) ? '' : ' LevelUP Message :' . $levelUPResult->message;
                    $logger->log('Error from LevelUP. User ID : ' . $user->id . $levelUPErrorMessage, Logger::LOG_NOTICE);

                    $interface->assign('registerRosenLevelUPResult', $levelUPResult);
// recaptcha                    }

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

                /* // TO DO: enable recaptcha
                        // Set up captcha to limit spam self registrations
                        require_once ROOT_DIR . '/sys/Enrichment/RecaptchaSetting.php';
                        $recaptcha = new RecaptchaSetting();
                        if ($recaptcha->find(true) && !empty($recaptcha->publicKey)){
                            $captchaCode        = recaptcha_get_html($recaptcha->publicKey);
                            $interface->assign('captcha', $captchaCode);
                        }
                */

                $fieldsForm = $interface->fetch('DataObjectUtil/objectEditForm.tpl');
                $interface->assign('registerRosenLevelUPForm', $fieldsForm);
                $this->display('registerRosenLevelUP.tpl', 'Register for Rosen LevelUP');
            }
        }
    }
}