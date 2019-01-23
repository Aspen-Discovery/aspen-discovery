<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once ROOT_DIR . '/Drivers/Millennium.php';
/**
 * VuFind Connector for Nashville Public Library's Innovative catalog (Millenium)
 *
 * This class uses screen scraping techniques to gather record holdings written
 * by Adam Bryn of the Tri-College consortium.
 *
 * @author Adam Brin <abrin@brynmawr.com>
 *
 * Extended by Mark Noble and CJ O'Hara based on specific requirements for
 * Marmot Library Network.
 *
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @author CJ O'Hara <cj@marmot.org>
 *
 * Extended by James Staub based on specific requirements for
 * Nashville Public Library
 *
 * @author James Staub <james.staub@nashville.gov>
 */
class Nashville extends Millennium{
	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile){
		parent::__construct($accountProfile);
	}

	/**
	 * Login with barcode and pin
	 *
	 * @see Drivers/Millennium::patronLogin()
	 */
	public function patronLogin($barcode, $pin, $validatedViaSSO)
	{
		global $offlineMode;
		if ($offlineMode){
			return parent::patronLogin($barcode, $pin, $validatedViaSSO);
		}else{
			// if patron attempts to Create New PIN
			if (isset($_REQUEST['password2']) && strlen($_REQUEST['password2']) > 0){
				$errors = $this->_pin_create($barcode,$_REQUEST['password'],$_REQUEST['password2']);
				//TODO pass error messages back to user.
			}
			return parent::patronLogin($barcode, $pin, $validatedViaSSO);
		}
	}

	public function _getLoginFormValues($patron){
		$loginData = array();
		$loginData['pin'] = $patron->cat_password;
		$loginData['code'] = $patron->cat_username;
		$loginData['submit'] = 'submit';
		return $loginData;
	}

	public function _getBarcode(){
		$user = UserAccount::getLoggedInUser();
		return $user->cat_username;
	}

	protected function _getHoldResult($holdResultPage){
		$hold_result = array();
		//Get rid of header and footer information and just get the main content
		if (preg_match('/success/', $holdResultPage)){
			//Hold was successful
			$hold_result['success'] = true;
			if (!isset($reason) || strlen($reason) == 0){
				$hold_result['message'] = 'Your hold was placed successfully';
			}else{
				$hold_result['message'] = $reason;
			}
		}else if (preg_match('/<font color="red" size="\+2">(.*?)<\/font>/is', $holdResultPage, $reason)){
			//Got an error message back.
			$hold_result['success'] = false;
			$hold_result['message'] = $reason[1];
		}else{
			//Didn't get a reason back.  This really shouldn't happen.
			$hold_result['success'] = false;
			$hold_result['message'] = 'Did not receive a response from the circulation system.  Please try again in a few minutes.';
		}
		return $hold_result;
	}

//TODO updatePin() & _pin_create() have common functionality, should they be combined into one method? Maybe a new method that both make calls to?

	protected function _pin_create($barcode, $pin1, $pin2) {
		$curl_url = $this->getVendorOpacUrl() . "/patroninfo";
		$curl_connection = $this->_curl_connect($curl_url);
		$sresult = curl_exec($curl_connection);
		//Scrape the 'lt' value from the IPSSO login page
		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $sresult, $loginMatches)) {
					$lt = $loginMatches[1];
				//POST the first round - pin is blank
				        $post_data['code'] = $barcode;
				        $post_data['pin'] = "";
				        $post_data['lt'] = $lt;
				        $post_data['_eventId'] = 'submit';
					$redirectPageInfo = curl_getinfo($curl_connection, CURLINFO_EFFECTIVE_URL);
					$sresult = $this->_curlPostPage($redirectPageInfo, $post_data);
				//Is the patron's PIN already set?
					if (preg_match('/<fieldset class="newpin" id="ipssonewpin">/si', $sresult, $newPinMatches)) {
					//if (preg_match('/Please enter a new PIN/si', $sresult, $newPinMatches)) {
				//Scrape the 'lt' value from the IPSSO login page primed to receive a new PIN, which is different from the last page's 'lt' value
						if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $sresult, $loginMatches)) {
							$lt2 = $loginMatches[1];
				//POST the second round - pin1 and pin2
							$post_data['code'] = $barcode;
							$post_data['pin1'] = $pin1;
							$post_data['pin2'] = $pin2;
							$post_data['lt'] = $lt2;
							$post_data['_eventId'] = 'submit';
							$redirectPageInfo = curl_getinfo($curl_connection, CURLINFO_EFFECTIVE_URL);
							$sresult = $this->_curlPostPage($redirectPageInfo, $post_data);
							if (preg_match('/<div id="status" class="errors">(.+?)<\/div>/si', $sresult, $ipssoErrors)) {
								$ipssoError = $ipssoErrors[1];
								return $ipssoError;
							}
						} else {
							return 'Unable to connect to library system correctly.';
							//echo("lt2 not found at " . $redirectPageInfo . "\n");
						}
					} else {
						return 'Pin is already set';
						//PIN is already set in patron record
						//echo("new PIN message NOT FOUND at " . $redirectPageInfo . "\n");
					}
				} else {
					return 'Unable to connect to library system correctly.';
					//echo("lt not found in sresult\n");
				}
				//unlink($cookie_jar); // 20150617 JAMES commented out while messing around - need to ensure user1 doesn't accidentally get user2 info
	}

//	protected function _pin_create($barcode, $pin1, $pin2) {
//		global $configArray;
//// Case: Millennium patron record does not have PIN.
//// When a library uses IPSSO - Innovative Patron Single Sign On - the initial login does a redirect and requires additonal parameters.
//// ipsso.html ifpinneeded token is active. Need to do an actual login rather than just checking patron dump
//// 1. retrieve the ipsso.html page at /patroninfo to scrape the 'lt' value
//// 2. POST the first round, pin=''
//// 3. retrieve the ipsso.html page primed to receive pin1 and pin2 input, scrape the second 'lt' value
//// 4. POST the second round, pin1 and pin2 from user input
//// 5. display ipsso-generated error messages
//// 6. PATRONAPI dump
//// 7. PATRONAPI pintest
//// 8. display PATRONAPI error messages
//// 9. redirect patron to /MyAccount/Home
//		$curl_url = $this->getVendorOpacUrl() . "/patroninfo";
//		$curl_connection = $this->_curl_connect($curl_url);
//		$sresult = curl_exec($curl_connection);
//		//Scrape the 'lt' value from the IPSSO login page
//		if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $sresult, $loginMatches)) {
//					$lt = $loginMatches[1];
//				//POST the first round - pin is blank
//				        $post_data['code'] = $barcode;
//				        $post_data['pin'] = "";
//				        $post_data['lt'] = $lt;
//				        $post_data['_eventId'] = 'submit';
////				        $post_items = array();
////				        foreach ($post_data as $key => $value) {
////				                $post_items[] = $key . '=' . $value;
////				        }
////				        $post_string = implode ('&', $post_items);
//					$redirectPageInfo = curl_getinfo($curl_connection, CURLINFO_EFFECTIVE_URL);
////				        curl_setopt($curl_connection, CURLOPT_URL, $redirectPageInfo);
////					curl_setopt($curl_connection, CURLOPT_POST, true);
////				        curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
////				        $sresult = curl_exec($curl_connection);
//					$sresult = $this->_curlPostPage($redirectPageInfo, $post_data);
//				//Is the patron's PIN already set?
//					if (preg_match('/<fieldset class="newpin" id="ipssonewpin">/si', $sresult, $newPinMatches)) {
//					//if (preg_match('/Please enter a new PIN/si', $sresult, $newPinMatches)) {
//				//Scrape the 'lt' value from the IPSSO login page primed to receive a new PIN, which is different from the last page's 'lt' value
//						if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $sresult, $loginMatches)) {
//							$lt2 = $loginMatches[1];
//				//POST the second round - pin1 and pin2
//							$post_data['code'] = $barcode;
//							$post_data['pin1'] = $pin1;
//							$post_data['pin2'] = $pin2;
//							$post_data['lt'] = $lt2;
//							$post_data['_eventId'] = 'submit';
////							$post_items = array();
////							foreach ($post_data as $key => $value) {
////								$post_items[] = $key . '=' . $value;
////							}
////							$post_string = implode ('&', $post_items);
//							$redirectPageInfo = curl_getinfo($curl_connection, CURLINFO_EFFECTIVE_URL);
////							curl_setopt($curl_connection, CURLOPT_URL, $redirectPageInfo);
////							curl_setopt($curl_connection, CURLOPT_POST, true);
////							curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
////							$sresult = curl_exec($curl_connection);
//							$sresult = $this->_curlPostPage($redirectPageInfo, $post_data);
//							if (preg_match('/<div id="status" class="errors">(.+?)<\/div>/si', $sresult, $ipssoErrors)) {
//								$ipssoError = $ipssoErrors[1];
//								return $ipssoError;
//								//echo($ipssoError."\n");
////SOME ERROR MESSAGES THAT APPEAR WHEN SETTING PIN
////PIN insertion failed. Your record is in use by system. Please try again later.
////Please enter a new PIN.
////PINs do not match. Try again!
////Your pin is not complex enough to be secure. Please select another one.
////Marmot[?] error catchers
////if (preg_match('/the information you submitted was invalid/i', $sresult)){
////	PEAR_Singleton::raiseError('Unable to register your new pin #.  The pin was invalid or this account already has a pin set for it.');
////}else if (preg_match('/PIN insertion failed/i', $sresult)){
////	PEAR_Singleton::raiseError('Unable to register your new pin #.  PIN insertion failed.');
////}
//							}
//						} else {
//							return 'Unable to connect to library system correctly.';
//							//echo("lt2 not found at " . $redirectPageInfo . "\n");
//						}
//					} else {
//						return 'Pin is already set';
//						//PIN is already set in patron record
//						//echo("new PIN message NOT FOUND at " . $redirectPageInfo . "\n");
//					}
//				} else {
//					return 'Unable to connect to library system correctly.';
//					//echo("lt not found in sresult\n");
//				}
//				//unlink($cookie_jar); // 20150617 JAMES commented out while messing around - need to ensure user1 doesn't accidentally get user2 info
//	}

	public function updatePin($user, $oldPin, $newPin, $confirmNewPin){
		global $configArray;

		//Login to the patron's account
		$barcode = $this->_getBarcode($user);
		//Attempt to call new PIN popup form for patron record 1. WebPAC will challenge for barcode/PIN.
		//After authentication check succeeds, WebPAC (without any help from us) will replace "1" with the patron record number
		$curl_url = $this->getVendorOpacUrl() . "/patroninfo/1/newpin";
		$curl_connection = $this->_curl_connect($curl_url);
		$sresult = curl_exec($curl_connection);
		//only bother to log in using the ipsso login page if it appears; user session might allow patron to go directly to newpin page
		if (preg_match('/ipssopinentry/', $sresult)) {
			$post_data = array();
			$post_data['code'] = $barcode;
			$post_data['pin']= $oldPin;
			//Scrape the 'lt' value from the IPSSO login page
			if (preg_match('/<input type="hidden" name="lt" value="(.*?)" \/>/si', $sresult, $loginMatches)) {
				$lt = $loginMatches[1];
				$post_data['lt'] = $lt;
			}
			$post_data['_eventId'] = 'submit';
			$redirectPageInfo = curl_getinfo($curl_connection, CURLINFO_EFFECTIVE_URL);

			$sresult = $this->_curlPostPage($redirectPageInfo, $post_data);
			if (preg_match('/<div id="status" class="errors">(.+?)<\/div>/si', $sresult, $ipssoErrors)) {
				$ipssoError = $ipssoErrors[1];
				return $ipssoError."\n";
			}
		}

		//Issue a post request to update the pin
		$post_data = array();
		$post_data['code'] = $barcode;
		$post_data['pin']= $oldPin;
		$post_data['pin1']= $newPin;
		$post_data['pin2']= $confirmNewPin;
		$curl_url = curl_getinfo($curl_connection, CURLINFO_EFFECTIVE_URL);
		$sresult = $this->_curlPostPage($curl_url, $post_data);
		if ($sresult){
			if (preg_match('/Your PIN has been modified/i', $sresult)){
				$user->cat_password = $newPin;
				$user->update();
//				UserAccount::updateSession($user); // needed?? TODO if needed, determine this $user is the same as the user logged in.
				return "Your pin number was updated successfully.";
			} else if (preg_match('/class="errormessage">(.+?)<\/div>/is', $sresult, $matches)){
				return trim($matches[1]);
//POSSIBLE ERRORS FROM /newpin
//Old PIN does not match PIN in record.
//New PINs do not match
//Your pin must consist of numeric characters only.
//Your pin is not complex enough to be secure. Please select another one.
//SUCCESS=Your PIN has been modified.

			} else {
				return "Sorry, your PIN has not been modified : unknown error. Please try again later.";
			}
		}else{
			return "Sorry, we could not update your pin number. Please try again later.";
		}
	}

	public function showLinksForRecordsWithItems() {
		return true;
	}
}
