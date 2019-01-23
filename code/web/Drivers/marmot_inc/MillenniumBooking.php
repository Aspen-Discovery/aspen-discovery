<?php
/**
 * Created by PhpStorm.
 * User: Pascal Brammeier
 * Date: 7/13/2015
 * Time: 10:52 AM
 */

class MillenniumBooking {
	/** @var  Millennium $driver */
	private $driver;
//	private $bookings = array();

	public function __construct($driver){
		/** @var  Millennium $driver */
		$this->driver = $driver;
	}

	/**
	 * Taken from the class MarcRecord method getShortId.
	 *
	 * @param $longId  III record Id with the 8th check digit included
	 * @return mixed|string the initial dot & the trailing check digit removed
	 */
	public static function getShortId($longId){
		$shortId = str_replace('.b', 'b', $longId);
		$shortId = substr($shortId, 0, strlen($shortId) -1);
		return $shortId;
	}

	public function bookMaterial($user, $recordId, $startDate, $startTime = null, $endDate = null, $endTime = null){
		if (empty($recordId) || empty($startDate)) { // at least these two fields should be required input
			if (!$recordId) return array('success' => false, 'message' => 'Item ID required');
			else return array('success' => false, 'message' => 'Start Date Required.');
		}
		if (!$startTime) $startTime = '8:00am';   // set a default start time if not specified (a morning time)
		if (!$endDate)   $endDate = $startDate;   // set a default end date to the start date if not specified
		if (!$endTime)   $endTime = '8:00pm';     // set a default end time if not specified (an evening time)

		// set bib number in format .b{recordNumber}
		$bib = $this->getShortId($recordId);

		$startDateTime = new DateTime("$startDate $startTime");// create a date with input and set it to the format the ILS expects
		if (!$startDateTime) {
			return array('success' => false, 'message' => 'Invalid Start Date or Time.');
		}

		$endDateTime = new DateTime("$endDate $endTime");// create a date with input and set it to the format the ILS expects
		if (!$endDateTime){
			return array('success' => false, 'message' => 'Invalid End Date or Time.');
		}

		$driver = &$this->driver;

		// Login to Millennium webPac
		$driver->_curl_login($user);

		$bookingUrl = $driver->getVendorOpacUrl() ."/webbook?/$bib=&back=";
		// the strange get url parameters ?/$bib&back= is needed to avoid a response from the server claiming a 502 proxy error
		// Scope appears to be unnecessary at this point.

		// Get pagen from form
		$curlResponse = $driver->_curlGetPage($bookingUrl);

		if (preg_match('/You cannot book this material/i', $curlResponse)){
			return array(
				'success' => false,
				'message' => 'Sorry, you cannot schedule this item.'
			);
		}

		$tag = 'input';
		$tag_pattern =
			'@<(?P<tag>'.$tag.')           # <tag
      (?P<attributes>\s[^>]+)?       # attributes, if any
            \s*/?>                   # /> or just >, being lenient here
            @xsi';
		$attribute_pattern =
			'@
        (?P<name>\w+)                         # attribute name
        \s*=\s*
        (
            (?P<quote>[\"\'])(?P<value_quoted>.*?)(?P=quote)    # a quoted value
                                    |                           # or
            (?P<value_unquoted>[^\s"\']+?)(?:\s+|$)             # an unquoted value (terminated by whitespace or EOF)
        )
        @xsi';

		if(preg_match_all($tag_pattern, $curlResponse, $matches)) {
			foreach ($matches['attributes'] as $attributes) {
				if (preg_match_all($attribute_pattern, $attributes, $attributeMatches)) {
					$search = array_flip($attributeMatches['name']); //flip so that index can be used to get actual names & values of attributes
					if (array_key_exists('name', $search)) { // find name attribute
						$attributeName  = trim($attributeMatches['value_quoted'][$search['name'] ], '"\'');
						$attributeValue = trim($attributeMatches['value_quoted'][$search['value']], '"\'');
						if ($attributeName == 'webbook_pagen') {
							$pageN = $attributeValue;
						} elseif ($attributeName == 'webbook_loc') {
							$loc = $attributeValue;
						}
					}
				}
			}
		}

		$patronId = $user->username; // username seems to be the patron Id

		$post = array(
			'webbook_pnum' => $patronId,
			'webbook_pagen' => empty($pageN) ? '2' : $pageN, // needed, reading from screen scrape; 2 or 4 are the only values i have seen so far. plb 7-16-2015
//			'refresh_cal' => '0', // not needed
//			'webbook_loc' => 'flmdv', // this may only be needed when the scoping is used
		  'webbook_bgn_Month' => $startDateTime->format('m'),
			'webbook_bgn_Day' => $startDateTime->format('d'),
			'webbook_bgn_Year' => $startDateTime->format('Y'),
			'webbook_bgn_Hour' => $startDateTime->format('h'),
			'webbook_bgn_Min' => $startDateTime->format('i'),
			'webbook_bgn_AMPM' => $startDateTime->format('H') > 11 ? 'PM' : 'AM',
			'webbook_end_n_Month' => $endDateTime->format('m'),
			'webbook_end_n_Day' => $endDateTime->format('d'),
			'webbook_end_n_Year' => $endDateTime->format('Y'),
			'webbook_end_n_Hour' => $endDateTime->format('h'),
			'webbook_end_n_Min' => $endDateTime->format('i'),
			'webbook_end_n_AMPM' => $endDateTime->format('H') > 11 ? 'PM' : 'AM', // has to be uppercase for the screenscraping
			'webbook_note' => '', // the web note doesn't seem to be displayed to the user any where after submit
		);
		if (!empty($loc)) $post['webbook_loc'] = $loc; // if we have this info add it, don't include otherwise.
		$curlResponse = $driver->_curlPostPage($bookingUrl, $post);
		if ($curlError = curl_errno($driver->curl_connection)) {
			global $logger;
			$logger->log('Curl error during booking, code: '.$curlError, PEAR_LOG_WARNING);
			return array(
				'success' => false,
				'message' => 'There was an error communicating with the circulation system.'
			);
		}

		// Look for Account Error Messages
		// <h1>There is a problem with your record.  Please see a librarian.</h1>
		$numMatches = preg_match('/<h1>(?P<error>There is a problem with your record\..\sPlease see a librarian.)<\/h1>/', $curlResponse, $matches);
		// ?P<name> syntax will creates named matches in the matches array
		if ($numMatches) {
			return array(
				'success' => false,
				'message' => is_array($matches['error']) ? implode('<br>', $matches['error']) : $matches['error'],
				'retry' => true, // communicate back that we think the user could adjust their input to get success
			);
		}


		// Look for Error Messages
		$numMatches = preg_match('/<span.\s?class="errormessage">(?P<error>.+?)<\/span>/is', $curlResponse, $matches);
		// ?P<name> syntax will creates named matches in the matches array
		if ($numMatches) {
			return array(
				'success' => false,
				'message' => is_array($matches['error']) ? implode('<br>', $matches['error']) : $matches['error'],
				'retry' => true, // communicate back that we think the user could adjust their input to get success
			);
		}

		// Look for Success Messages
		$numMatches = preg_match('/<span.\s?class="bookingsConfirmMsg">(?P<success>.+?)<\/span>/', $curlResponse, $matches);
		if ($numMatches) {
			return array(
				'success' => true,
				'message' => is_array($matches['success']) ? implode('<br>', $matches['success']) : $matches['success']
			);
		}

		// Catch all Failure
		global $logger;
		$logger->log('Unkown error during booking', PEAR_LOG_ERR);
		return array(
			'success' => false,
			'message' => 'There was an unexpected result while scheduling your item'
		);
	}

	public function cancelBookedMaterial($user, $cancelIds) {
		if (empty($cancelIds)) return array('success' => false, 'message' => 'Item ID required');

		if (!is_array($cancelIds)) $cancelIds = array($cancelIds); // for a single item

		$driver = &$this->driver;
		$scope = $driver->getLibraryScope();
		$patronInfo = $driver->_getPatronDump($user->getBarcode());

		$cancelBookingUrl = $driver->getVendorOpacUrl() ."/patroninfo~S$scope?/". $patronInfo['RECORD_#'].'/bookings';
			// scoping needed for canceling booked materials

		$driver->_curl_login($user);

		$post = array(
			'canbooksome' => 'YES'
		);
		foreach ($cancelIds as $i => $cancelId){
			if (is_numeric($i)) $post['canbook'.$i] = $cancelId; // recreating the cancelName variable canbookX
			else $post[$i] = $cancelId; // when cancelName is passed back
		}


		$initialResponse = $driver->_curlPostPage($cancelBookingUrl, $post);
		$errors = array();
		if ($curlError = curl_errno($driver->curl_connection)) return array(
			'success' => false,
			'message' => 'There was an error communicating with the circulation system.'
		);

		// get the bookings again, to verify that they were in fact really cancelled.
		$curlResponse = $driver->_curlPostPage($cancelBookingUrl, array());

		foreach ($cancelIds as $cancelId) {
			// successful cancels return books page without the item
			if (strpos($curlResponse, $cancelId) !== false) { // looking for this booking in results, meaning it failed to cancel.
				if (empty($errors)) $bookings = $this->parseBookingsPage($curlResponse); // get current bookings on first error
					// get bookings info on the first detected error
				foreach ($bookings as $booking){
					if ($booking['cancelValue'] == $cancelId) break;
				}
//					$errors[$booking['cancelValue']] = 'Failed to cancel scheduled item <strong>' . $booking['title'] . '</strong> from ' . strftime('%b %d, %Y at %I:%M %p', $booking['startDateTime']) . ' to ' . strftime('%b %d, %Y at %I:%M %p', $booking['endDateTime']);
				// Time included
				$errors[$booking['cancelValue']] = 'Failed to cancel scheduled item <strong>' . $booking['title'] . '</strong> from ' . strftime('%b %d, %Y', $booking['startDateTime']) . ' to ' . strftime('%b %d, %Y', $booking['endDateTime']);
				// Dates only
			}
		}


		if (empty($errors)) {
			return array(
				'success' => true,
				'message' => 'Your scheduled item' . (count($cancelIds) > 1 ? 's were' : ' was') . ' successfully canceled.'
			);
		}
		else {
			return array(
				'success' => false,
				'message' => $errors
			);
		}
	}

	public function cancelAllBookedMaterial($user) {
		/** @var Millennium $driver */
		$driver = &$this->driver;
		$scope = $driver->getLibraryScope();
		$patronInfo = $driver->_getPatronDump($user->getBarcode());

		$cancelBookingUrl = $driver->getVendorOpacUrl() ."/patroninfo~S$scope?/". $patronInfo['RECORD_#'].'/bookings';
			// scoping needed for canceling booked materials

		$driver->_curl_login($user);

		$post = array(
			'canbookall' => 'YES'
		);
		$initialResponse = $driver->_curlPostPage($cancelBookingUrl, $post);
		$errors = array();
		if ($curlError = curl_errno($driver->curl_connection)) return array(
			'success' => false,
			'message' => 'There was an error communicating with the circulation system.'
		);

		// get the bookings again, to verify that they were in fact really cancelled.
		$curlResponse = $driver->_curlPostPage($cancelBookingUrl, array());
		if (!strpos($curlResponse, 'No bookings found')) { // 'No bookings found' is our success phrase
			$bookings = $this->parseBookingsPage($curlResponse);
			if (!empty($bookings)) { // a booking wasn't canceled
				foreach ($bookings as $booking) {
//					$errors[$booking['cancelValue']] = 'Failed to cancel scheduled item <strong>' . $booking['title'] . '</strong> from ' . strftime('%b %d, %Y at %I:%M %p', $booking['startDateTime']) . ' to ' . strftime('%b %d, %Y at %I:%M %p', $booking['endDateTime']);
					// Time included
					$errors[$booking['cancelValue']] = 'Failed to cancel scheduled item <strong>' . $booking['title'] . '</strong> from ' . strftime('%b %d, %Y', $booking['startDateTime']) . ' to ' . strftime('%b %d, %Y', $booking['endDateTime']);
					// Dates only
				}
			}
		}

		if (empty($errors)) return array(
			'success' => true,
			'message' => 'Your scheduled items were successfully canceled.'
		);
		else return array(
			'success' => false,
			'message' => $errors
		);
	}

	/**
	 * @param User $user  The user to fetch bookings for
	 * @return array
	 */
	public function getMyBookings($user){
		$driver = &$this->driver;

//		$patronDump = $driver->_getPatronDump($driver->_getBarcode());
		// looks like this is deprecated now.

		// Fetch Millennium WebPac Bookings page
		$html = $driver->_fetchPatronInfoPage($user, 'bookings');

		// Parse out Bookings Information
		$bookings = $this->parseBookingsPage($html);

		require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
			foreach($bookings as /*$key =>*/ &$booking){
//				disableErrorHandler(); // TODO: Test by deleting the marc record file.
				$recordDriver = new MarcRecord($booking['id']);
				if ($recordDriver->isValid()){
//					$booking['id'] = $recordDriver->getUniqueID(); //redundant
					$booking['shortId'] = $recordDriver->getShortId();
					//Load title, author, and format information about the title
					$booking['title'] = $recordDriver->getTitle();
					$booking['sortTitle'] = $recordDriver->getSortableTitle();
					$booking['author'] = $recordDriver->getAuthor();
					$booking['format'] = $recordDriver->getFormat();
					$booking['isbn'] = $recordDriver->getCleanISBN(); //TODO these may not be used anywhere now that the links are built here, have to check
					$booking['upc'] = $recordDriver->getCleanUPC();   //TODO these may not be used anywhere now that the links are built here, have to check
					$booking['format_category'] = $recordDriver->getFormatCategory();
					$booking['linkUrl'] = $recordDriver->getRecordUrl();
					$booking['coverUrl'] = $recordDriver->getBookcoverUrl('medium');

					//Load rating information
//					$booking['ratingData'] = $recordDriver->getRatingData(); // not displaying ratings at this time
				}
//				enableErrorHandler();
			}

		return $bookings;
	}

	private function parseBookingsPage($html) {
		$bookings = array();

		// Table Rows for each Booking
		if(preg_match_all('/<tr\\s+class="patFuncEntry">(?<bookingRow>.*?)<\/tr>/si', $html, $rows, PREG_SET_ORDER)) {
			foreach ($rows as $index => $row) { // Go through each row

				// Get Record/Title
				if (!preg_match('/.*?<a href=\\"\/record=(?<recordId>.*?)(?:~S\\d{1,3})\\">(?<title>.*?)<\/a>.*/', $row['bookingRow'], $matches))
						 preg_match('/.*<a href=".*?\/record\/C__R(?<recordId>.*?)\\?.*?">(?<title>.*?)<\/a>.*/si',    $row['bookingRow'], $matches);
				// Don't know if this situation comes into play. It is taken from millennium holds parser. plb 7-17-2015

				$shortId = $matches['recordId'];
				$bibId = '.' . $shortId . $this->driver->getCheckDigit($shortId);
				$title = strip_tags($matches['title']);

					// Get From & To Dates
				if (preg_match_all('/.*?<td nowrap class=\\"patFuncBookDate\\">(?<bookingDate>.*?)<\/td>.*/', $row['bookingRow'], $matches, PREG_SET_ORDER)) {
					$startDateTime = trim($matches[0]['bookingDate']); // time component looks ambiguous
					$endDateTime   = trim($matches[1]['bookingDate']);
					// pass as timestamps so that the SMARTY template can handle it.
					$startDateTime = date_timestamp_get(date_create_from_format('m-d-Y g:i', $startDateTime));
					$endDateTime   = date_timestamp_get(date_create_from_format('m-d-Y g:i', $endDateTime));
				} else {
					$startDateTime = null;
					$endDateTime = null;
				}

				// Get Status
				if (preg_match('/.*?<td nowrap class=\\"patFuncStatus\\">(?<status>.*?)<\/td>.*/', $row['bookingRow'], $matches)) {
					$status = ($matches['status'] == '&nbsp;') ? '' : $matches['status']; // at this point, I don't know what status we will ever see
				} else $status = '';

				// Get Cancel Ids
//				<td class="patFuncMark"><input type="CHECKBOX" name="canbook0" id="canbook0" value="i9459912F08-17-20154:00T08-17-20154:00" /></td>
				if (preg_match('/.*?<input type="CHECKBOX".*?name=\\"(?<cancelName>.*?)\\".*?value=\\"(?<cancelValue>.*?)\\" \/>.*/', $row['bookingRow'], $matches)) {
					$cancelName = $matches['cancelName'];
					$cancelValue = $matches['cancelValue'];
				} else $cancelValue = $cancelName = '';

				$bookings[] = array(
					'id' => $bibId,
					'title' => $title,
					'startDateTime' => $startDateTime,
					'endDateTime' => $endDateTime,
					'status' => $status,
					'cancelName' => $cancelName,
					'cancelValue' => $cancelValue,
				);

			}


		}
		return $bookings;
		}


	public function getBookingCalendar($recordId){
		if (strpos($recordId, ':') !== false) list(,$recordId) = explode(':', $recordId, 2); // remove any prefix from the recordId
		$bib = $this->getShortId($recordId);
		$driver = &$this->driver;

		// Create Hourly Calendar URL
		$scope = $driver->getLibraryScope();
		$user = UserAccount::getLoggedInUser();
		$driver->_curl_login($user);
		$timestamp = time(); // the webpac hourly calendar give 30 (maybe 31) days worth from the given timestamp.
				// Since today is the soonest a user could book, let's get from today
		$hourlyCalendarUrl = $driver->getVendorOpacUrl() . "/webbook~S$scope?/$bib/hourlycal$timestamp=&back=";

		//Can only get the hourly calendar html by submitting the bookings form
			$post = array(
				'webbook_pnum' => $user->username, // username seems to be the patron Id
				'webbook_pagen' => '2', // needed, reading from screen scrape; 2 or 4 are the only values i have seen so far. plb 7-16-2015
				//			'refresh_cal' => '0', // not needed
				'webbook_bgn_Month' => '',
				'webbook_bgn_Day' => '',
				'webbook_bgn_Year' => '',
				'webbook_bgn_Hour' => '',
				'webbook_bgn_Min' => '',
				'webbook_bgn_AMPM' => '',
				'webbook_end_n_Month' => '',
				'webbook_end_n_Day' => '',
				'webbook_end_n_Year' => '',
				'webbook_end_n_Hour' => '',
				'webbook_end_n_Min' => '',
				'webbook_end_n_AMPM' => '',
				'webbook_note' => '',
			);
			$HourlyCalendarResponse = $driver->_curlPostPage($hourlyCalendarUrl, $post);

			// Extract Hourly Calendar from second response
			if(preg_match('/<div class="bookingsSelectCal">.*?<table border>(?<HourlyCalendarTable>.*?<\/table>.*?)<\/table>.*?<\/div>/si', $HourlyCalendarResponse, $table)) {

				// Modify Calendar html for our needs
				$calendarTable = str_replace(array('unavailable', 'available', 'closed', 'am'), array('active', 'success', 'active', ''), $table['HourlyCalendarTable']);
				$calendarTable = preg_replace('#<th.*?>.*?</th>#s', '<th colspan="2">Date</th><th colspan="17">Time <small>(6 AM - 11 PM)&nbsp; Times in green are Available.</small></th>', $calendarTable); // cut out the table header with the unwanted links in it.
				$calendarTable = '<table class="table table-condensed">'. $calendarTable . '</table>'; // add table tag with styling attributes

				return $calendarTable;

			}


	}

}