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

require_once 'DriverInterface.php';
require_once ROOT_DIR . '/sys/SIP2.php';
require_once ROOT_DIR . '/Drivers/HorizonAPI.php';

class Anythink extends HorizonAPI {
	/**
	 * @param AccountProfile $accountProfile
	 */
	public function __construct($accountProfile){
		parent::__construct($accountProfile);
	}

	function translateFineMessageType($code){
		switch ($code){
			case "abs":       return "Automatic Bill Sent";
			case "acr":       return "Address Correction Requested";
			case "adjcr":     return "Adjustment credit, for changed";
			case "adjdbt":    return "Adjustment debit, for changed";
			case "balance":   return "Balancing Entry";
			case "bcbr":      return "Booking Cancelled by Borrower";
			case "bce":       return "Booking Cancelled - Expired";
			case "bcl":       return "Booking Cancelled by Library";
			case "bcsp":      return "Booking Cancelled by Suspension";
			case "bct":       return "Booking Cancelled - Tardy";
			case "bn":        return "Billing Notice";
			case "chgs":      return "Charges Misc. Fees";
			case "cr":        return "Claimed Return";
			case "credit":    return "Credit";
			case "damage":    return "Damaged";
			case "dc":        return "Debt Collection";
			case "dynbhm":    return "Dynix Being Held Mail";
			case "dynbhp":    return "Dynix Being Held Phone";
			case "dynfnl":    return "Dynix Final Overdue Notice";
			case "dynhc":     return "Dynix Hold Cancelled";
			case "dynhexp":   return "Dynix Hold Expired";
			case "dynhns":    return "Dynix Hold Notice Sent";
			case "dynnot1":   return "Dynix First Overdue Notice";
			case "dynnot2":   return "Dynix Second Overdue Notice";
			case "edc":       return "Exempt from Debt Collection";
			case "fdc":       return "Force to Debt Collection";
			case "fee":       return "ILL fees/Postage";
			case "final":     return "Final Overdue Notice";
			case "finalr":    return "Final Recall Notice";
			case "fine":      return "Fine";
			case "hcb":       return "Hold Cancelled by Borrower";
			case "hcl":       return "Hold Cancelled by Library";
			case "hclr":      return "Hold Cancelled & Reinserted in";
			case "he":        return "Hold Expired";
			case "hncko":     return "Hold Notification - Deliver";
			case "hncsa":     return "Hold - from closed stack";
			case "hnmail":    return "Hold Notification - Mail";
			case "hnphone":   return "Hold Notification - Phone";
			case "ill":       return "Interlibrary Loan Notification";
			case "in":        return "Invoice";
			case "infocil":   return "Checkin Location";
			case "infocki":   return "Checkin date";
			case "infocko":   return "Checkout date";
			case "infodue":   return "Due date";
			case "inforen":   return "Renewal date";
			case "l":         return "Lost";
			case "ld":        return "Lost on Dynix";
			case "lf":        return "Found";
			case "LostPro":   return "Lost Processing Fee";
			case "lr":        return "Lost Recall";
			case "msg":       return "Message to Borrower";
			case "nocko":     return "No Checkout";
			case "Note":      return "Comment";
			case "notice1":   return "First Overdue Notice";
			case "notice2":   return "Second Overdue Notice";
			case "notice3":   return "Third Overdue Notice";
			case "noticr1":   return "First Recall Notice";
			case "noticr2":   return "Second Recall Notice";
			case "noticr3":   return "Third Recall Notice";
			case "noticr4":   return "Fourth Recall Notice";
			case "noticr5":   return "Fifth Recall Notice";
			case "nsn":       return "Never Send Notices";
			case "od":        return "Overdue Still Out";
			case "odd":       return "Overdue Still Out on Dynix";
			case "odr":       return "Recalled and Overdue Still Out";
			case "onlin":     return "Online Registration";
			case "payment":   return "Fine Payment";
			case "pcr":       return "Phone Correction Requested";
			case "priv":      return "Privacy - Family permission";
			case "rd":        return "Request Deleted";
			case "re":        return "Request Expired";
			case "recall":    return "Item is recalled before due date";
			case "refund":    return "Refund of Payment";
			case "ri":        return "Reminder Invoice";
			case "rl":        return "Requested item lost";
			case "rn":        return "Reminder Billing Notice";
			case "spec":      return "Special Message";
			case "supv":      return "See Supervisor";
			case "suspend":   return "Suspension until ...";
			case "unpd":      return "Damaged Material Replacement";
			case "waiver":    return "Waiver of Fine";
			default:
				return $code;
		}
	}

	public function translateLocation($locationCode){
		$locationCode = strtolower($locationCode);
		$locationMap = array(
        'acpl' => 'Administration',
        'be' => 'Bennett',
        'br'  => 'Brighton',
        'cc'  => 'Commerce City',
        'ext' => 'In Motion Bookmobile',
        'ng'  => 'Huron Street',
        'pm' => 'Perl Mack',
        'th'  => 'Washington Street',
        'wf' => 'Wright Farms',
        );
        return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : 'Unknown' ;
	}

	public function translateCollection($collectionCode){
		$collectionCode = strtolower($collectionCode);
		$collectionMap = array(
			'ajob'   => 'Jobs & Employment',
			'at'     => 'Audio Tapes',
			'atls'   => 'Atlas Case',
			'b'      => 'Biographies',
			'bb'     => 'Book Bags',
			'board'  => 'Board Books',
			'cd'     => 'Audio CDs',
			'cdd'    => 'Data CDs',
			'cde'    => 'Easy CDs',
			'cdi'    => 'CD I',
			'cdj'    => 'Juvenile CDs',
			'cdt'    => 'Teen CDs',
			'co'     => 'Colorado Section',
			'coe'    => 'Easy Colorado',
			'coi'    => 'Colorado Section',
			'coj'    => 'Juvenile Colorado',
			'cot'    => 'Colorado Section',
			'dvd'    => 'DVDs',
			'dvdbox' => 'DVD Media Box',
			'e'      => 'Easy Non-fiction',
			'ebook'  => 'E-Books / E-Audiobooks',
			'ecap'   => 'ABCs & 123s',
			'ef'     => 'Easy Picture Book',
			'ency'   => 'Encyclopedias',
			'eqx'    => 'Equipment',
			'er'     => 'Easy Reader',
			'f'      => 'Adult Fiction',
			'FA'     => 'Fast Add',
			'FA-BI'  => 'Fast Add',
			'FA-I'   => 'Fast Add',
			'fan'    => 'Fantasy',
			'gn'     => 'Graphic Novels',
			'gw'     => 'Genealogy & Western History',
			'hilo'   => 'HI LO',
			'hol'    => 'Adult Holiday',
			'hole'   => 'Easy Holiday',
			'holj'   => 'Juvenile Holiday',
			'hrl'    => 'Brighton Health Resources Library',
			'i'      => 'Intermediate Non-Fiction',
			'if'     => 'Intermediate Fiction',
			'if1'    => 'I Early Chapter Books',
			'ill'    => 'Item Not Available for Lending',
			'indx'   => 'Index Tables',
			'j'      => 'Juvenile Non-Fiction',
			'jb'     => 'Juvenile Biography',
			'jdvd'   => 'Juvenile DVDs',
			'jf'     => 'Chapter Books',
			'jf1'    => 'Chapter Books',
			'jfan'   => 'Chapter Books -- Fantasy',
			'jgn'    => 'Juvenile Graphic Novels',
			'jrcs'   => 'Juvenile Reading Club',
			'kit'    => "Children's Book & CD kit",
			'laptop' => 'Anythink Laptops',
			'lit'    => 'Literacy',
			'lp'     => 'Large Print Fiction',
			'lpnf'   => 'Large Print Non-Fiction',
			'lsta'   => 'LSTA',
			'mcd'    => 'Music CDs',
			'mcdj'   => 'Juvenile Music CDs',
			'mcdpop' => 'Music CDs POP',
			'mcf'    => 'Adult Fiction',
			'mcn'    => 'Adult Non-fiction',
			'mpa'    => 'Playaway fiction',
			'mpan'   => 'Playaway Non-fiction',
			'mpj'    => 'Playaway Juvenile fiction',
			'mpjn'   => 'Playaway Children Non-fiction',
			'mpt'    => 'Playaway YA fiction',
			'mptn'   => 'Playaway YA Non-fiction',
			'mxblu'  => 'CD World / Folk / Bluegrass',
			'mxchl'  => 'CD Children',
			'mxcls'  => 'CD Classical',
			'mxctr'  => 'CD Country',
			'mxgos'  => 'CD Gospel',
			'mxhol'  => 'CD Holiday',
			'mxjaz'  => 'CD Jazz / R&B',
			'mxopr'  => 'CD Opera',
			'mxpop'  => 'CD Pop / Rock / Rap',
			'mxstr'  => 'CD Soundtracks / Musicals',
			'mys'    => 'Mysteries',
			'new'    => 'New Books',
			'newco'  => 'New Colorado Books',
			'newmy'  => 'New Mysteries',
			'newnf'  => 'New Non-Fiction',
			'newsf'  => 'New Science Fiction',
			'newwe'  => 'New Westerns',
			'nf'     => 'Adult Non-Fiction',
			'ol'     => 'Overload',
			'per'    => 'Periodicals',
			'pn'     => 'FOTO',
			'puz'    => 'Puzzles & Games',
			'rcs'    => 'Book Club',
			'read'   => 'Readers',
			'ref'    => 'Reference',
			'ref7d'  => '7 Day Reference',
			'refa'   => 'Juvenile Reference',
			'refb'   => 'Business Reference',
			'refc'   => 'Refc',
			'refco'  => 'Colorado Reference',
			'refe'   => 'Easy Reference',
			'refi'   => 'Intermediate Reference',
			'refj'   => 'Juvenile Reference',
			'refr'   => 'Ready Reference',
			'refx'   => 'Reference - Special Shelving',
			'rom'    => 'Romance',
			'sf'     => 'Science Fiction',
			'span'   => 'Spanish Adult',
			'spane'  => 'Span E',
			'spaner' => 'Spanish Readers',
			'spani'  => 'Intermediate Spanish',
			'spanj'  => 'Juvenile Spanish',
			'spant'  => 'Teen Spanish',
			't'      => 'Teen Nonfiction',
			'tco'	 => 'Colorado Teen',
			'tf'     => 'Teen Fiction',
			'tfan'   => 'YA Fantasy',
			'tgn'    => 'YA Graphic Novels',
			'tmys'   => 'YA Mystery',
			'trcs'   => 'YA Reading Club',
			'trom'   => 'YA Romance',
			'u'      => 'Special Collection - Fast CAT',
			'vf'     => 'Vertical File',
			'vtx'    => 'Video Tapes',
			'west'   => 'Westerns',
			'xbconly' => 'ON ORDER',
			'xparpro' => 'ON ORDER',
			'xunpro' => 'ON ORDER',

		);
		return isset($collectionMap[$collectionCode]) ? $collectionMap[$collectionCode] : 'Unknown';
	}

	public function translateStatus($statusCode){
		return mapValue('item_status', $statusCode);
	}

	public function getLocationMapLink($locationCode){
		$locationCode = strtolower($locationCode);
		$locationMap = array(
        );
		return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : '' ;
	}

	public function getLibraryHours($locationId, $timeToCheck){
		return null;
	}

	function selfRegister(){
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

		//Start at My Account Page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$this->selfRegProfile}&menu=account";
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
		$logger->log("Loading Full Record $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR_Singleton::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'New User',
      'login_prompt' => 'true',
      'menu' => 'account',
			'newuser_prompt' => 'true',
      'profile' => $this->selfRegProfile,
      'ri' => '',
      'sec1' => '',
      'sec2' => '',
      'session' => $sessionId,
		);
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		/*
		Form variables are built using horizonAPI class, getSelfRegistrationFields() which uses the API to get fields from ILS.
	*/
		$firstName = strip_tags($_REQUEST['firstname']);
		$lastName = strip_tags($_REQUEST['lastname']);
		$address1 = strip_tags($_REQUEST['address1']);
		$address2 = strip_tags($_REQUEST['address2']);
		$citySt = strip_tags($_REQUEST['city_st']);
		$zip = strip_tags($_REQUEST['postal_code']);
		$email = strip_tags($_REQUEST['email_address']);
		$sendNoticeBy = strip_tags($_REQUEST['send_notice_by']);
		$pin = strip_tags($_REQUEST['pin#']);
		$confirmPin = strip_tags($_REQUEST['confirmpin#']);
		$phone = strip_tags($_REQUEST['phone_no']);
		$phoneType = strip_tags($_REQUEST['phone_type']); // option 'z' has a label of 't', rather than 'telephone'. (also pager options needed?)
		$language = strip_tags($_REQUEST['language']);
		$borrowerNote = strip_tags($_REQUEST['borrower_note']); // used as a BirthDate Field. plb 12-10-2014

		//Register the patron
		$post_data = array(
      'address1' => $address1,
		  'address2' => $address2,
			'aspect' => 'basic',
			'pin#' => $pin,
			'button' => 'I accept',
			'city_st' => $citySt,
			'confirmpin#' => $confirmPin,
			'email_address' => $email,
			'firstname' => $firstName,
			'borrower_note' => $borrowerNote,
			'ipp' => 20,
			'lastname' => $lastName,
			'language' => $language,
			'location' => $location,
			'menu' => 'account',
			'newuser_info' => 'true',
			'npp' => 30,
			'postal_code' => $zip,
      'phone_no' => $phone,
			'phone_type' => $phoneType,
      'profile' => $this->selfRegProfile,
			'ri' => '',
			'send_notice_by' => $sendNoticeBy,
			'session' => $sessionId,
			'spp' => 20
		);

//		$post_items = array();
//		foreach ($post_data as $key => $value) {
//			$post_items[] = $key . '=' . urlencode($value);
//		}
//		$post_string = implode ('&', $post_items);
		$post_string = http_build_query($post_data);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url . '#focus');
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		//Get the temporary barcode from the page
		if (preg_match('/Here is your temporary barcode\\. Use it for future authentication:&nbsp;([\\d-]+)/s', $sresult, $regs)) {
			$tempBarcode = $regs[1];
			//Append the library prefix to the card number
			$barcodePrefix = $configArray['Catalog']['barcodePrefix'];
			$tempBarcode = substr($barcodePrefix, 0, 6) . $tempBarcode;
			$success = true;
		}else{
			$success = false;
			$tempBarcode = null;
		}

		unlink($cookie);

		return array(
		  'barcode' => $tempBarcode,
		  'success'  => $success
		);

	}
}
?>