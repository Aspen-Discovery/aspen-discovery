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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

//require_once ROOT_DIR . '/sys/SIP2.php'; // not used at this time. plb 6-17-2016
require_once ROOT_DIR . '/Drivers/ScreenScrapingDriver.php';
abstract class Horizon extends ScreenScrapingDriver{

	protected $db;
	protected $useDb = true;
	protected $hipUrl;
	protected $hipProfile;
	protected $selfRegProfile;
	public $accountProfile;
	function __construct($accountProfile) {
		$this->accountProfile = $accountProfile;
		// Load Configuration for this Module
		global $configArray;

		$this->hipUrl = $configArray['Catalog']['hipUrl'];
		$this->hipProfile = $configArray['Catalog']['hipProfile'];
		$this->selfRegProfile = $configArray['Catalog']['selfRegProfile'];

		// Connect to database
		if (!isset($configArray['Catalog']['useDb']) || $configArray['Catalog']['useDb'] == true){
			try{
				if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0 ){
					sybase_min_client_severity(11);
					$this->db = @sybase_connect($configArray['Catalog']['database'] ,
					$configArray['Catalog']['username'],
					$configArray['Catalog']['password']);
				}else{
						$this->db = mssql_connect($configArray['Catalog']['host'] . ':' . $configArray['Catalog']['port'],
							$configArray['Catalog']['username'],
							$configArray['Catalog']['password']);

						// Select the database
						mssql_select_db($configArray['Catalog']['database']);
					}
			}catch (Exception $e){
					global $logger;
				$logger->log("Could not load Horizon database", PEAR_LOG_ERR);
			}
		}else{
				$this->useDb = false;
		}
	}

	public function getMyFines($patron, $includeMessages){
		if ($this->useDb){
			return $this->getMyFinesViaDB($patron, $includeMessages);
		}else{
			return $this->getMyFinesViaHIP($patron, $includeMessages);
		}
	}

	public function getMyFinesViaHIP($patron, $includeMessages){
		global $configArray;
		global $logger;

		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Go to items out page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$configArray['Catalog']['hipProfile']}&menu=account&submenu=blocks";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Loading fines $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR_Singleton::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		curl_setopt($curl_connection, CURLOPT_POST, true);
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'Login to Your Account',
      'login_prompt' => 'true',
      'menu' => 'account',
      'profile' => $configArray['Catalog']['hipProfile'],
      'ri' => '',
      'sec1' => $patron->cat_username,
      'sec2' => $patron->cat_password,
      'session' => $sessionId,
		);
		$post_string = http_build_query($post_data);

		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		preg_match_all('/<tr>.*?<td bgcolor="#FFFFFF"><a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<a class="normalBlackFont2">(.*?)<\/a>.*?<\/tr>/s', $sresult, $messageInfo, PREG_SET_ORDER);
		$messages = array();
		for ($matchi = 0; $matchi < count($messageInfo); $matchi++) {
			$messages[] = array(
                'reason' => $messageInfo[$matchi][1],
                'amount' => $messageInfo[$matchi][3],
                'message' => ($messageInfo[$matchi][2] != '&nbsp;') ? $messageInfo[$matchi][2] : '',
                'date' => $messageInfo[$matchi][4]
			);
		}
		unlink($cookie);
		return $messages;
	}
	public function getMyFinesViaDB($patron, $includeMessages = false)
	{
		$sql = "select title_inverted.title as TITLE, item.bib# as BIB_NUM, item.item# as ITEM_NUM, " .
               "burb.borrower# as BORROWER_NUM, burb.amount as AMOUNT, burb.comment, " .
               "burb.date as DUEDATE, " .
               "burb.block as FINE, burb.amount as BALANCE from burb " .
               "left join item on item.item#=burb.item# " .
		           "left join title_inverted on title_inverted.bib# = item.bib# " .
               "join borrower on borrower.borrower#=burb.borrower# " .
               "join borrower_barcode on borrower_barcode.borrower#=burb.borrower# " .
               "where borrower_barcode.bbarcode='" . $patron->cat_username . "'" ;

		if ($includeMessages == false){
			$sql .= " and amount != 0";
		}
		//$sql .= " ORDER BY burb.date ASC";

		//print_r($sql);
		try {
			$sqlStmt = $this->_query($sql);

			$balance = 0;

			while ($row = $this->_fetch_assoc($sqlStmt)) {
				if (preg_match('/infocki|infodue|infocil|infocko|note|spec|supv/i', $row['FINE'])){
					continue;
				}

				//print_r($row);
				$checkout = '';
				$duedate = $this->addDays('1970-01-01', $row['DUEDATE']);
				$bib_num = $row['BIB_NUM'];
				$item_num = $row['ITEM_NUM'];
				$borrower_num = $row['BORROWER_NUM'];
				$amount = $row['AMOUNT'];
				$balance += $amount;
				$comment = is_null($row['comment']) ? $row['TITLE'] : $row['comment'];

				if (isset($bib_num) && isset($item_num))
				{
					$cko = "select date as CHECKOUT " .
                           "from burb where borrower#=" . $borrower_num . " " .
                           "and item#=" . $item_num . " and block='infocko'";
					$sqlStmt_cko = $this->_query($cko);

					if ($row_cko = $this->_fetch_assoc($sqlStmt_cko)) {
						$checkout = $this->addDays('1970-01-01', $row_cko['CHECKOUT']);
					}

					$due = "select convert(varchar(12),dateadd(dd, date, '01 jan 1970')) as DUEDATE " .
                           "from burb where borrower#=" . $borrower_num . " " .
                           "and item#=" . $item_num . " and block='infodue'";
					$sqlStmt_due = $this->_query($due);

					if ($row_due = $this->_fetch_assoc($sqlStmt_due)) {
						$duedate = $row_due['DUEDATE'];
					}
				}

				$fineList[] = array('id' => $bib_num,
                                    'message'  => $comment,
                                    'amount'   => $amount > 0 ? '$' . sprintf('%0.2f', $amount / 100) : '',
                                    'reason'   => $this->translateFineMessageType($row['FINE']),
                                    'balance'  => $balance,  // TODO: not in my fines template
                                    'checkout' => $checkout, // TODO: not in my fines template
                                    'date'     => date('M j, Y', strtotime($duedate)));
			}
			return $fineList;
		} catch (PDOException $e) {
			return new PEAR_Error($e->getMessage());
		}

	}

	/**
	 * @param User $user                     The User Object to make updates to
	 * @param boolean $canUpdateContactInfo  Permission check that updating is allowed
	 * @return array                         Array of error messages for errors that occurred
	 */
	function updatePatronInfo($user, $canUpdateContactInfo){
		$updateErrors = array();
		if ($canUpdateContactInfo) {
			global $configArray;
			//Check to make sure the patron alias is valid if provided
			if (isset($_REQUEST['displayName']) && $_REQUEST['displayName'] != $user->displayName && strlen($_REQUEST['displayName']) > 0) {
				//make sure the display name is less than 15 characters
				if (strlen($_REQUEST['displayName']) > 15) {
					$updateErrors[] = 'Sorry your display name must be 15 characters or less.';
					return $updateErrors;
				} else {
					//Make sure that we are not using bad words
					require_once ROOT_DIR . '/Drivers/marmot_inc/BadWord.php';
					$badWords     = new BadWord();
					$okToAdd = $badWords->hasBadWords($_REQUEST['displayName']);
					if (!$okToAdd) {
						$updateErrors[] = 'Sorry, that name is in use or invalid.';
						return $updateErrors;
					}
					//Make sure no one else is using that
					$userValidation = new User();
					$userValidation->query("SELECT * from {$userValidation->__table} WHERE id <> {$user->id} and displayName = '{$_REQUEST['displayName']}'");
					if ($userValidation->N > 0) {
						$updateErrors[] = 'Sorry, that name is in use or is invalid.';
						return $updateErrors;
					}
				}
			}

			//Start at My Account Page
			$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$configArray['Catalog']['hipProfile']}&menu=account";
			$sResult = $this->_curlGetPage($curl_url);

			//Extract the session id from the requestcopy javascript on the page
			if (preg_match('/\\?session=(.*?)&/s', $sResult, $matches)) {
				$sessionId = $matches[1];
			} else {
				PEAR_Singleton::raiseError('Could not load session information from page.');
			}

			// Now Enable Cookie Session (This must come after the first page fetch, or there will be no curl response.)
			// It looks like this setting is not needed at all. Retaining in the case that is not always true
//			curl_setopt_array($this->curl_connection, array(
//				CURLOPT_COOKIESESSION => true,
//			));

			//Login by posting username and password
			global $logger;
			$logger->log("Logging into user account from updatePatronInfo $curl_url", PEAR_LOG_INFO);
			$post_data   = array(
				'aspect' => 'overview',
				'button' => 'Login to Your Account',
				//'ipp' => '20',
				//'lastlogin' => '1299616721524',
				'login_prompt' => 'true',
				'menu' => 'account',
				//'npp' => '10',
				'profile' => $configArray['Catalog']['hipProfile'],
				'ri' => '',
				'sec1' => $user->cat_username,
				'sec2' => $user->cat_password,
				'session' => $sessionId,
				//'spp' => '20'
			);
			$curl_url    = $this->hipUrl . "/ipac20/ipac.jsp";
			$sResult = $this->_curlPostPage($curl_url, $post_data);
			//TODO check for Login success

			//Update patron information.  Use HIP to update the e-mail to make sure that all business rules are followed.
			if (isset($_REQUEST['email'])) {
				$post_data   = array(
					'menu' => 'account',
					'newemailtext' => $_REQUEST['email'],
					'newpin' => '',
					'oldpin' => '',
					'profile' => $configArray['Catalog']['hipProfile'],
					'renewpin' => '',
					'session' => $sessionId,
					'submenu' => 'info',
					'updateemail' => 'Update',
				);
				$sResult = $this->_curlPostPage($curl_url, $post_data);

				//check for errors in boldRedFont1
				if (preg_match('/<td.*?class="boldRedFont1".*?>(.*?)(?:<br>)*<\/td>/si', $sResult, $matches)) {
					$updateErrors[] = $matches[1];
				} else {
					// Update the users email address in the Pika database
					$user->email = $_REQUEST['email'];
				}
			}

			if (isset($_REQUEST['oldPin']) && strlen($_REQUEST['oldPin']) > 0 && isset($_REQUEST['newPin']) && strlen($_REQUEST['newPin']) > 0) {
				$post_data   = array(
					'menu' => 'account',
					'newemailtext' => $_REQUEST['email'],
					'newpin' => $_REQUEST['newPin'],
					'oldpin' => $_REQUEST['oldPin'],
					'profile' => $configArray['Catalog']['hipProfile'],
					'renewpin' => $_REQUEST['verifyPin'],
					'session' => $sessionId,
					'submenu' => 'info',
					'updatepin' => 'Update',
				);
				$sResult = $this->_curlPostPage($curl_url, $post_data);

				//check for errors in boldRedFont1
				if (preg_match('/<td.*?class="boldRedFont1".*?>(.*?)(?:<br>)*<\/td>/', $sResult, $matches)) {
					$updateErrors[] = $matches[1];
				} else {
					//Update the users cat_password in the Pika database
					$user->cat_password = $_REQUEST['newPin'];
				}
			}
			if (isset($_REQUEST['phone'])) {
				//TODO: Implement Setting Notification Methods
				$updateErrors[] = 'Phone number can not be updated.';
			}
			if (isset($_REQUEST['address1']) || isset($_REQUEST['city']) || isset($_REQUEST['state']) || isset($_REQUEST['zip'])) {
				//TODO: Implement Setting Notification Methods
				$updateErrors[] = 'Address Information can not be updated.';
			}
			if (isset($_REQUEST['notices'])) {
				//TODO: Implement Setting Notification Methods
				$updateErrors[] = 'Notice Method can not be updated.';
			}
			if (isset($_REQUEST['pickuplocation'])) {
				//TODO: Implement Setting Pick-up Locations
				$updateErrors[] = 'Pickup Locations can not be updated.';
			}

			//check to see if the user has provided an alias
			if ((isset($_REQUEST['displayName']) && $_REQUEST['displayName'] != $user->displayName) ||
				(isset($_REQUEST['disableRecommendations']) && $_REQUEST['disableRecommendations'] != $user->disableRecommendations) ||
				(isset($_REQUEST['disableCoverArt']) && $_REQUEST['disableCoverArt'] != $user->disableCoverArt) ||
				(isset($_REQUEST['bypassAutoLogout']) && $_REQUEST['bypassAutoLogout'] != $user->bypassAutoLogout)
			) {
				$user->displayName            = $_REQUEST['displayName'];
				$user->disableRecommendations = $_REQUEST['disableRecommendations'];
				$user->disableCoverArt        = $_REQUEST['disableCoverArt'];
				if (isset($_REQUEST['bypassAutoLogout'])) {
					$user->bypassAutoLogout = $_REQUEST['bypassAutoLogout'] == 'yes' ? 1 : 0;
				}
			}

			// update Pika user data & clear cache of patron profile
			$user->update();
//			UserAccount::updateSession($user); //TODO if this is required it must be determined that the user being updated is the same as the session holding user.
//			$user->clearCache(); // Done in User object now by calling method

		} else $updateErrors[] = 'You do not have permission to update profile information.';
		return $updateErrors;
	}

	public function getRecordTitle($recordId){
		//Get the title of the book.
		$searchObject = SearchObjectFactory::initSearchObject();

		// Retrieve Full Marc Record
		if (!($record = $searchObject->getRecord($recordId))) {
			$title = null;
		}else{
			if (isset($record['title_full'][0])){
				$title = $record['title_full'][0];
			}else{
				$title = $record['title'];
			}
		}
		return $title;
	}

	function addDays($givendate,$day) {
		$cd = strtotime($givendate);
		$newdate = date('Y-m-d H:i:s', mktime(date('H',$cd),
		date('i',$cd), date('s',$cd), date('m',$cd),
		date('d',$cd)+$day, date('Y',$cd)));
		return $newdate;
	}

	function addMinutes($givendate,$minutes) {
		$cd = strtotime($givendate);
		$newdate = date('Y-m-d H:i:s', mktime(date('H',$cd),
		date('i',$cd) + $minutes, date('s',$cd), date('m',$cd),
		date('d',$cd), date('Y',$cd)));
		return $newdate;
	}

	protected function _query($query){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_query($query);
		}else{
			return mssql_query($query);
		}
	}

	protected function _fetch_assoc($result_id){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_fetch_assoc($result_id);
		}else{
			return mssql_fetch_assoc($result_id);
		}
	}

	protected function _fetch_array($result_id){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_fetch_array($result_id);
		}else{
			return mssql_fetch_array($result_id);
		}
	}

	protected function _num_rows($result_id){
		global $configArray;
		if (strcasecmp($configArray['System']['operatingSystem'], 'windows') == 0){
			return sybase_num_rows($result_id);
		}else{
			return mssql_num_rows($result_id);
		}
	}

/**
	 * Email the user's pin number to the account on record if any exists.
	 */
	function emailPin($barcode){
		global $configArray;
		if ($this->useDb){
			$sql = "SELECT name, borrower.borrower#, bbarcode, pin#, email_name, email_address from borrower inner join borrower_barcode on borrower.borrower# = borrower_barcode.borrower# inner join borrower_address on borrower.borrower# = borrower_address.borrower#  where bbarcode= '" . mysql_escape_string($barcode) . "'";

			try {
				$sqlStmt = $this->_query($sql);
				$foundPatron = false;
				while ($row = $this->_fetch_assoc($sqlStmt)) {
					$pin = $row['pin#'];
					$email = $row['email_address'];
					$foundPatron = true;
					break;
				}

				if ($foundPatron){
					if (strlen($email) == 0){
						return array('error' => 'Your account does not have an email address on record. Please visit your local library to retrieve your PIN number.');
					}
					require_once ROOT_DIR . '/sys/Mailer.php';

					$mailer = new VuFindMailer();
					$subject = "PIN number for your Library Card";
					$body = "The PIN number for your Library Card is $pin.  You may use this PIN number to login to your account.";
					$mailer->send($email, $configArray['Site']['email'], $subject, $body);
					return array(
						'success' => true,
						'pin' => $pin,
						'email' => $email,
					);
				}else{
					return array('error' => 'Sorry, we could not find an account with that barcode.');
				}
			} catch (PDOException $e) {
				return array(
					'error' => 'Unable to read your PIN from the database.  Please try again later.'
					);
			}
		}else{
			$result = array(
				'error' => 'This functionality requires a connection to the database.',
			);
		}
		return $result;
	}

	abstract function translateCollection($collection);

	abstract function translateLocation($locationCode);

	abstract function translateStatus($status);

	public function hasNativeReadingHistory() {
		return false;
	}
}