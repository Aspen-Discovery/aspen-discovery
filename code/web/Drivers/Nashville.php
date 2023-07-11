<?php

require_once ROOT_DIR . '/Drivers/CarlX.php';

class Nashville extends CarlX {

	public function __construct($accountProfile) {
		parent::__construct($accountProfile);
	}

    static $fineTypeTranslations = [
        'F' => 'Fine',
        'F2' => 'Processing',
        'FS' => 'Manual',
        'L' => 'Lost',
        'NR' => 'Manual', // NR = Non Resident, a juke to identify Nashville non resident account fees
    ];

    static $fineTypeSIP2Translations = [
        'F' => '04',
        'F2' => '05',
        'FS' => '01',
        'L' => '07',
        'NR' => '01', // NR = Non Resident, a juke to identify Nashville non resident account fees
    ];

	public function completeFinePayment(User $patron, UserPayment $payment): array {
		global $logger;
		global $serverName;
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mailer = new Mailer();
		require_once ROOT_DIR . '/sys/SystemVariables.php';
		$systemVariables = SystemVariables::getSystemVariables();
		$accountLinesPaid = explode(',', $payment->finesPaid);
		$patron->id = $payment->userId;
		$patronId = $patron->cat_username;
		$allPaymentsSucceed = true;
		foreach ($accountLinesPaid as $line) {
			// MSB Payments are in the form of fineId|paymentAmount
			[
				$feeId,
				$pmtAmount,
			] = explode('|', $line);
			[
				$feeId,
				$feeType,
			] = explode('-', $feeId);
			$feeTypeSIP = Nashville::$fineTypeSIP2Translations[$feeType];
			if (strlen($feeId) == 13 && strpos($feeId, '1700') === 0) { // we stripped out leading octothorpes (#) from CarlX manual fines in CarlX.php getFines() which take the form "#".INSTBIT (Institution; Nashville = 1700) in order to sidestep CSS/javascript selector "#" problems; need to add them back for updating CarlX via SIP2 Fee Paid
				$feeId = '#' . $feeId;
			}
			$response = $this->feePaidViaSIP($feeTypeSIP, '02', $pmtAmount, 'USD', $feeId, '', $patronId); // As of CarlX 9.6, SIP2 37/38 BK transaction id is written by CarlX as a receipt number; CarlX will not keep information passed through 37 BK; hence transId should be empty instead of, e.g., MSB's Transaction ID at $payment->orderId
			// If failed with 'Invalid patron ID', check for changed patron ID
			if ($response['success'] === false && $response['message'] == 'Invalid patron ID.') {
				$newPatronIds = $this->getPatronIDChanges($patronId);
				if ($newPatronIds) {
					foreach ($newPatronIds as $newPatronId) {
						$logger->log("MSB Payment CarlX update failed on Payment Reference ID $payment->id on FeeID $feeId : " . $response['message'] . ". Trying patron id change lookup on $patronId, found " . $newPatronId['NEWPATRONID'], Logger::LOG_ERROR);
						$response = $this->feePaidViaSIP($feeTypeSIP, '02', $pmtAmount, 'USD', $feeId, '', $newPatronId['NEWPATRONID']);
						if ($response['success'] === false) {
							$logger->log("MSB Payment CarlX update failed on Payment Reference ID $payment->id on FeeID $feeId : " . $response['message'], Logger::LOG_ERROR);
							$allPaymentsSucceed = false;
						} else {
							$logger->log("MSB Payment CarlX update succeeded on Payment Reference ID $payment->id on FeeID $feeId : " . $response['message'], Logger::LOG_DEBUG);
							$allPaymentsSucceed = true;
							break;
						}
					}
				} else {
					$logger->log("MSB Payment CarlX update failed on Payment Reference ID $payment->id on FeeID $feeId : " . $response['message'] . ". Trying patron id change lookup... failed to find patron id change for $patronId", Logger::LOG_ERROR);
				}
			}
			if ($response['success'] === false) {
				$logger->log("MSB Payment CarlX update failed on Payment Reference ID $payment->id on FeeID $feeId : " . $response['message'], Logger::LOG_ERROR);
				$allPaymentsSucceed = false;
			} else {
				$logger->log("MSB Payment CarlX update succeeded on Payment Reference ID $payment->id on FeeID $feeId : " . $response['message'], Logger::LOG_DEBUG);
				if ($feeType == 'NR') {
					$this->updateNonResident($patron);
				}
			}
		}
		if ($allPaymentsSucceed === false) {
			$success = false;
			$message = "MSB Payment CarlX update failed for Payment Reference ID $payment->id . See messages.log for details on individual items.";
			$level = Logger::LOG_ERROR;
			$payment->completed = 9;
		} else {
			$success = true;
			$message = "MSB payment successfully recorded in CarlX for Payment Reference ID $payment->id .";
			$level = Logger::LOG_NOTICE;
			$payment->completed = 1;
		}
		$payment->update();
		$this->createPatronPaymentNote($patronId, $payment->id);
		$logger->log($message, $level);
		if ($level == Logger::LOG_ERROR) {
			if (!empty($systemVariables->errorEmail)) {
				$mailer->send($systemVariables->errorEmail, "$serverName Error with MSB Payment", $message);
			}
		}
		return [
			'success' => $success,
			'message' => $message,
		];
	}

	public function canPayFine($system): bool {
		$canPayFine = false;
		if ($system == 'NPL') {
			$canPayFine = true;
		}
		return $canPayFine;
	}

    // Following successful online payment, update Patron with new Expiration Date
	protected function updateNonResident(User $user): array {
		global $logger;
		$patronId = $user->cat_username;
		$request = $this->getSearchbyPatronIdRequest($user);
		$request->Patron = new stdClass();
		$request->Patron->ExpirationDate = date('c', strtotime('+1 year'));
		//$logger->log("Request updatePatron\r\n" . print_r($request, true), Logger::LOG_DEBUG);
		$result = $this->doSoapRequest('updatePatron', $request, $this->patronWsdl, $this->genericResponseSOAPCallOptions);
		//$logger->log("Result of updatePatron\r\n" . print_r($result, true), Logger::LOG_DEBUG);
		if ($result) {
			$success = stripos($result->ResponseStatuses->ResponseStatus->ShortMessage, 'Success') !== false;
			if (!$success) {
				$success = false;
				$message = "Failed to update Non Resident Patron in CarlX for $patronId .";
				$level = Logger::LOG_ERROR;
			} else {
				$success = true;
				$message = "Non Resident Patron updated successfully in CarlX for $patronId .";
				$level = Logger::LOG_NOTICE;
			}
		} else {
			$success = false;
			$message = "CarlX ILS gave no response when attempting to update Non Resident Patron $patronId .";
			$level = Logger::LOG_ERROR;
		}
		$logger->log($message, $level);
		return [
			'success' => $success,
			'message' => $message,
		];
	}
	
	protected function createPatronPaymentNote($patronId, $paymentId): array {
		global $logger;
		global $serverName;
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mailer = new Mailer();
		$systemVariables = SystemVariables::getSystemVariables();
		$request = new stdClass();
		$request->Note = new stdClass();
		$request->Note->PatronID = $patronId;
		$request->Note->NoteType = 2;
		$request->Note->NoteText = "Nexus Transaction Reference: $paymentId";
		$request->Modifiers = '';
		$result = $this->doSoapRequest('addPatronNote', $request);
		if ($result) {
			$success = stripos($result->ResponseStatuses->ResponseStatus->ShortMessage, 'Success') !== false;
			if (!$success) {
				$success = false;
				$message = "Failed to add patron note for payment in CarlX for Reference ID $paymentId .";
				$level = Logger::LOG_ERROR;
			} else {
				$success = true;
				$message = "Patron note for payment added successfully in CarlX for Reference ID $paymentId .";
				$level = Logger::LOG_NOTICE;
			}
		} else {
			$success = false;
			$message = "CarlX ILS gave no response when attempting to add patron note for payment Reference ID $paymentId .";
			$level = Logger::LOG_ERROR;
		}
		$logger->log($message, $level);
		if ($level == Logger::LOG_ERROR) {
			if (!empty($systemVariables->errorEmail)) {
				$mailer->send($systemVariables->errorEmail, "$serverName Error with MSB Payment", $message);
			}
		}
		return [
			'success' => $success,
			'message' => $message,
		];
	}

	protected function feePaidViaSIP($feeType = '01', $pmtType = '02', $pmtAmount, $curType = 'USD', $feeId = '', $transId = '', $patronId = ''): array {
		$mySip = $this->initSIPConnection();
		if (!is_null($mySip)) {
			$in = $mySip->msgFeePaid($feeType, $pmtType, $pmtAmount, $curType, $feeId, $transId, $patronId);
			$msg_result = $mySip->get_message($in);
			ExternalRequestLogEntry::logRequest('carlx.feePaid', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, []);
			if (preg_match("/^38/", $msg_result)) {
				$result = $mySip->parseFeePaidResponse($msg_result);
				$success = ($result['fixed']['PaymentAccepted'] == 'Y');
				$message = $result['variable']['AF'][0];
				$message = empty($transId) ? $message : $transId . ": " . $message;
				return [
					'success' => $success,
					'message' => $message,
				];
			} else {
				return [
					'success' => false,
					'message' => [
						'text' => 'Unknown problem with circulation server, please try again later.',
						'isPublicFacing' => true,
					],
				];
			}
		} else {
			return [
				'success' => false,
				'message' => [
					'text' => 'Could not connect to circulation server, please try again later.',
					'isPublicFacing' => true,
				],
			];
		}
	}

	public function getFines(User $patron, $includeMessages = false): array {
		$myFines = [];

		$request = $this->getSearchbyPatronIdRequest($patron);

		// Fines
		$request->TransactionType = 'Fine';
		$result = $this->doSoapRequest('getPatronTransactions', $request);
		//global $logger;
		//$logger->log("Result of getPatronTransactions (Fine)\r\n" . print_r($result, true), Logger::LOG_ERROR);
		if ($result && !empty($result->FineItems->FineItem)) {
			if (!is_array($result->FineItems->FineItem)) {
				$result->FineItems->FineItem = [$result->FineItems->FineItem];
			}
			foreach ($result->FineItems->FineItem as $fine) {
				// hard coded Nashville school branch IDs
				if ($fine->Branch == 0) {
					$fine->Branch = $fine->TransactionBranch;
				}
				$fine->System = $this->getFineSystem($fine->Branch);
				$fine->CanPayFine = $this->canPayFine($fine->System);

				$fine->FineAmountOutstanding = 0;
				if ($fine->FineAmountPaid > 0) {
					$fine->FineAmountOutstanding = $fine->FineAmount - $fine->FineAmountPaid;
				} else {
					$fine->FineAmountOutstanding = $fine->FineAmount;
				}

				if (strpos($fine->Identifier, 'ITEM ID: ') === 0) {
					$fine->Identifier = substr($fine->Identifier, 9);
				}
				$fine->Identifier = str_replace('#', '', $fine->Identifier);

				if ($fine->TransactionCode == 'FS' && stripos($fine->FeeNotes, 'COLLECTION') !== false) {
					$fineType = 'COLLECTION AGENCY';
					$fine->FeeNotes = 'COLLECTION AGENCY: must be paid last';
				} elseif ($fine->TransactionCode == 'FS' && stripos($fine->FeeNotes, 'Non Resident Ful') !== false) {
					$fineType = 'FEE';
					$fine->FeeNotes = $fineType . ' (' . Nashville::$fineTypeTranslations[$fine->TransactionCode] . ') ' . $fine->FeeNotes;
                    $fine->TransactionCode = 'NR';
				} else {
                    $fineType = 'FEE';
                    $fine->FeeNotes = $fineType . ' (' . Nashville::$fineTypeTranslations[$fine->TransactionCode] . ') ' . $fine->FeeNotes;
                }

				$myFines[] = [
					'fineId' => $fine->Identifier . "-" . $fine->TransactionCode,
					'type' => $fineType,
					'reason' => $fine->FeeNotes,
					'amount' => $fine->FineAmount,
					'amountVal' => $fine->FineAmount,
					'amountOutstanding' => $fine->FineAmountOutstanding,
					'amountOutstandingVal' => $fine->FineAmountOutstanding,
					'message' => $fine->Title,
					'date' => date('M j, Y', strtotime($fine->FineAssessedDate)),
					'system' => $fine->System,
					'canPayFine' => $fine->CanPayFine,
				];

			}
		}

		// Lost Item Fees
		if ($result && $result->LostItemsCount > 0) {
			$request->TransactionType = 'Lost';
			$result = $this->doSoapRequest('getPatronTransactions', $request);
			//$logger->log("Result of getPatronTransactions (Lost)\r\n" . print_r($result, true), Logger::LOG_ERROR);

			if ($result && !empty($result->LostItems->LostItem)) {
				if (!is_array($result->LostItems->LostItem)) {
					$result->LostItems->LostItem = [$result->LostItems->LostItem];
				}
				foreach ($result->LostItems->LostItem as $fine) {
					// hard coded Nashville school branch IDs
					if ($fine->Branch == 0) {
						$fine->Branch = $fine->TransactionBranch;
					}
					$fine->System = $this->getFineSystem($fine->Branch);
					$fine->CanPayFine = $this->canPayFine($fine->System);

					$fine->FeeAmountOutstanding = 0;
					if (!empty($fine->FeeAmountPaid) && $fine->FeeAmountPaid > 0) {
						$fine->FeeAmountOutstanding = $fine->FeeAmount - $fine->FeeAmountPaid;
					} else {
						$fine->FeeAmountOutstanding = $fine->FeeAmount;
					}

					if (strpos($fine->Identifier, 'ITEM ID: ') === 0) {
						$fine->Identifier = substr($fine->Identifier, 9);
					}

					$fineType = 'FEE';
					$fine->FeeNotes = $fineType . ' (' . Nashville::$fineTypeTranslations[$fine->TransactionCode] . ') ' . $fine->FeeNotes;

					$myFines[] = [
						'fineId' => $fine->Identifier . "-" . $fine->TransactionCode,
						'type' => $fineType,
						'reason' => $fine->FeeNotes,
						'amount' => $fine->FeeAmount,
						'amountVal' => $fine->FeeAmount,
						'amountOutstanding' => $fine->FeeAmountOutstanding,
						'amountOutstandingVal' => $fine->FeeAmountOutstanding,
						'message' => $fine->Title,
						'date' => date('M j, Y', strtotime($fine->TransactionDate)),
						'system' => $fine->System,
						'canPayFine' => $fine->CanPayFine,
					];

				}
				// The following epicycle is required because CarlX PatronAPI GetPatronTransactions Lost does not report FeeAmountOutstanding. See TLC ticket https://ww2.tlcdelivers.com/helpdesk/Default.asp?TicketID=515720
				$myLostFines = $this->getLostViaSIP($patron->cat_username);
				$myFinesIds = array_column($myFines, 'fineId');
				foreach ($myLostFines as $myLostFine) {
					$keys = array_keys($myFinesIds, $myLostFine['fineId'] . '-L');
					foreach ($keys as $key) {
						// CarlX can have Processing fees and Lost fees associated with the same item id; here we target only the Lost, because Processing fees correctly report previous partial payments through the PatronAPI
						if (substr($myFines[$key]['fineId'], -1) == "L") {
							$myFines[$key]['amountOutstanding'] = $myLostFine['amountOutstanding'];
							$myFines[$key]['amountOutstandingVal'] = $myLostFine['amountOutstandingVal'];
							break;
						}
					}
				}
			}
		}
		$sorter = function ($a, $b) {
			$systemA = $a['system'];
			$systemB = $b['system'];
			if ($systemA === $systemB) {
				$messageA = $a['message'];
				$messageB = $b['message'];
				return strcasecmp($messageA, $messageB);
			}
			return strcasecmp($systemA, $systemB);
		};
		uasort($myFines, $sorter);
		return $myFines;
	}

	public function getFineSystem($branchId): string {
		if (($branchId >= 30 && $branchId <= 178 && $branchId != 42 && $branchId != 167 && $branchId != 171) || ($branchId >= 180 && $branchId <= 212 && $branchId != 185 && $branchId != 187)) {
			return "MNPS";
		} else {
			return "NPL";
		}
	}

	protected function getLostViaSIP(string $patronId): array {
		$mySip = $this->initSIPConnection();
		$mySip->patron = $patronId;
		if (!is_null($mySip)) {
			$in = $mySip->msgPatronInformation('none');
			$msg_result = $mySip->get_message($in);
			ExternalRequestLogEntry::logRequest('carlx.getLost', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, []);
			if (preg_match("/^64/", $msg_result)) {
				$result = $mySip->parsePatronInfoResponse($msg_result);
				$fineCount = $result['fixed']['FineCount'];
				$in = $mySip->msgPatronInformation('fine', 1, $fineCount);
				$msg_result = $mySip->get_message($in);
				ExternalRequestLogEntry::logRequest('carlx.fine', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, []);
				if (preg_match("/^64/", $msg_result)) {
					$myLostFees = [];
					$result = $mySip->parsePatronInfoResponse($msg_result);
					foreach ($result['variable']['AV'] as $feeItem) {
						$feeItemFields = explode('^', $feeItem);
						$feeItemParsed = [];
						foreach ($feeItemFields as $feeItemField) {
							$fieldName = substr($feeItemField, 0, 1);
							$fieldValue = substr($feeItemField, 1);
							$feeItemParsed[$fieldName] = $fieldValue;
						}
						if ($feeItemParsed['S'] == 'Lost - fee charged') {
							$myLostFees[] = [
								'fineId' => $feeItemParsed['I'],
								//'type' => 'L',
								//'reason' => $feeItem['T'],
								//'amount' => $feeItem[], // not in CarlX SIP2 Patron Information > Fees
								//'amountVal' => $feeItem[], // not in CarlX SIP2 Patron Information > Fees
								'amountOutstanding' => $feeItemParsed['F'],
								'amountOutstandingVal' => $feeItemParsed['F'],
								//'message' => $feeItem['T'],
								//'date' => date('M j, Y', strtotime($feeItem['1']))
							];
						}
					}
					return $myLostFees;
				}
			}
		}
	}

	function getSelfRegTemplate($reason): string {
		global $activeLanguage;
		if ($reason == 'duplicate_email') {
			if ($activeLanguage->code == 'es') {
				return 'Emails/es-nashville-self-registration-denied-duplicate_email.tpl';
			} else { // assume en
				return 'Emails/nashville-self-registration-denied-duplicate_email.tpl';
			}
		} elseif ($reason == 'duplicate_name+birthdate') {
			if ($activeLanguage->code == 'es') {
				return 'Emails/es-nashville-self-registration-denied-duplicate_name+birthdate.tpl';
			} else { // assume en
				return 'Emails/nashville-self-registration-denied-duplicate_name+birthdate.tpl';
			}
		} elseif ($reason == 'success') {
			if ($activeLanguage->code == 'es') {
				return 'Emails/es-nashville-self-registration.tpl';
			} else { // assume en
				return 'Emails/nashville-self-registration.tpl';
			}
		} else {
			return '';
		}
	}

	protected function initSIPConnection() {
		$mySip = new sip2();
		$mySip->hostname = $this->accountProfile->sipHost;
		$mySip->port = $this->accountProfile->sipPort;

		if ($mySip->connect()) {
			//send selfcheck status message
			$in = $mySip->msgSCStatus();
			$msg_result = $mySip->get_message($in);
			// Make sure the response is 98 as expected
			if (preg_match("/^98/", $msg_result)) {
				$result = $mySip->parseACSStatusResponse($msg_result);
				ExternalRequestLogEntry::logRequest('carlx.selfCheckStatus', 'SIP2', $mySip->hostname . ':' . $mySip->port, [], $in, 0, $msg_result, []);

				//  Use result to populate SIP2 setings
				// These settings don't seem to apply to the CarlX Sandbox. pascal 7-12-2016
				if (isset($result['variable']['AO'][0])) {
					$mySip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
				} else {
					$mySip->AO = 'NASH'; /* set AO to value returned */ // hardcoded for Nashville
				}
				if (isset($result['variable']['AN'][0])) {
					$mySip->AN = $result['variable']['AN'][0]; /* set AN to value returned */
				} else {
					$mySip->AN = '';
				}
			}
			return $mySip;
		} else {
			return null;
		}
	}

	public function getCollectionReportData($location, $date): array {
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$sql = <<<EOT
			-- get the almost-whole enchilada (on shelf, charged, missing, lost, damaged, in processing; NOT withdrawn) for an owning branch
			with i as (
				select
					i.*
				from item_v i
				right join branch_v b on i.branch = b.branchnumber
				where b.branchcode = '$location'
			), r as (
				select
					r.refid
					, max(r.returndate) as returndate
				from itemnotewhohadit_v r
				group by r.refid
			), ir as (
				select
					*
				from i
				left join r on i.item = r.refid
			), irb as (
				select
					ir.*
					, b.title
					, b.author
					, b.publishingdate
				from ir
				left join bbibmap_v b on ir.bid = b.bid
			), ix as (
				select
					distinct irb.item
					, irb.bid
					, irb.title as Title
					, irb.author as Author
					, irb.publishingdate as PubDate
					, irb.price as Price
					, l.locname as Location
					, m.medcode as Media
					, irb.cn as CallNumber
					, irb.returndate as LastReturned
					, irb.cumulativehistory Circ
					, irb.item as Barcode
					, irb.creationdate as Created
					, c.description as Status
					, irb.statusdate as StatusDate
				from irb
				left join location_v l on irb.location = l.locnumber
				left join media_v m on irb.media = m.mednumber
				left join systemitemcodes_v c on irb.status = c.code
			)
			select
				*
			from ix
			order by Location, CallNumber
EOT;
		$stid = oci_parse($this->dbConnection, $sql);
		// consider using oci_set_prefetch to improve performance
		// oci_set_prefetch($stid, 1000);
		oci_execute($stid);
		$data = [];
		while (($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) != false) {
			$data[] = $row;
		}
		oci_free_statement($stid);
		return $data;
	}
}
