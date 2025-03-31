<?php

require_once ROOT_DIR . '/Drivers/CarlX.php';

class Nashville extends CarlX {

	public function __construct($accountProfile) {
		parent::__construct($accountProfile);
	}

	// MSB payments only
	static $fineTypeTranslations = [
		'F' => 'Fine',
		'F2' => 'Processing',
		'FS' => 'Manual',
		'L' => 'Lost',
		'NR' => 'Manual', // NR = Non Resident, a juke to identify Nashville non resident account fees
	];

	// MSB payments only
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

		global $library;
		if ($library->finePaymentType == 3) {
			//Do MSB payment stuff
			$accountLinesPaid = explode(',', $payment->finesPaid);
			$patron->id = $payment->userId;
			$patronId = $patron->ils_barcode;
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
					$message = is_array($response['message']) ? implode(', ', $response['message']) : (string)$response['message'];
					$logger->log("MSB Payment CarlX update failed on Payment Reference ID $payment->id on FeeID $feeId : " . $message, Logger::LOG_ERROR);
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
			$this->createPatronPaymentNote($patronId, $payment->id, $feeId); // TODO: add $feeId when turning on SnapPay
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
		// End MSB payment stuff
		} else {
			//Do SnapPay stuff

			// Nashville Non-Resident processing PART 1:
			// BEFORE recording payments in CarlX,
			// check if any fines are NON-RESIDENT FEE.
			// Make an array to cover the case that two or more active non-resident fees are on the account.
			$carlXFines = $this->getFines($patron);
			$nonResidentFeeIds = [];
			foreach ($carlXFines as $fine) {
				if ($fine['type'] == 'NON-RESIDENT FEE') {
					// Store nonResidentFee fine id
					$nonResidentFeeIds[] = $fine['fineId'];
				}
			}

			$result = parent::completeFinePayment($patron, $payment);
			if ($result['success'] === false) {
				$message = "Online payment CarlX update failed for Payment Reference ID $payment->id. See messages.log for details on individual items.";
				$level = Logger::LOG_ERROR;
			} else {
				$message = "Online payment successfully recorded in CarlX for Payment Reference ID $payment->id.";
				$level = Logger::LOG_DEBUG;

				// Nashville Non-Resident processing PART 2:
				// AFTER all payments succeed,
				// Look for Non-Resident Fee associated with patron's account and whether it is included in this payment
				// If so, update the patron's expiration date
				if (!empty($nonResidentFeeIds)) {
					$accountLinesPaid = explode(',', $payment->finesPaid);
					foreach ($accountLinesPaid as $line) {
						[$feeId, $pmtAmount] = explode('|', $line);
						foreach ($nonResidentFeeIds as $nonResidentFeeId) {
							if ($nonResidentFeeId == $feeId) {
								$this->updateNonResident($patron);
							}
						}
					}
				}
			}
			$logger->log($message, $level);

			if ($level == Logger::LOG_ERROR) {
				if (!empty($systemVariables->errorEmail)) {
					$mailer->send($systemVariables->errorEmail, "$serverName Error with online payment", $message);
				}
			}
			return [
				'success' => $result['success'],
				'message' => $message,
			];
		// End SnapPay stuff
		}
	}

	public function canPayFine($system): bool {
		$canPayFine = false;
		if ($system == 'NPL') {
			$canPayFine = true;
		}
		return $canPayFine;
	}

	// Following successful online payment, update Patron with new Expiration Date = today + 1 year
	protected function updateNonResident(User $user): array {
		global $logger;
		$patronId = $user->ils_barcode;
		$request = $this->getSearchbyPatronIdRequest($user);
		$request->Patron = new stdClass();
		// If patron expiration date is one year or more in the future, do not update
		if (strtotime($request->Patron->ExpirationDate) > strtotime('+1 year')) {
			$logger->log("Non Resident Patron $patronId has an expiration date more than one year in the future. No update needed.", Logger::LOG_DEBUG);
			return [
				'success' => true,
				'message' => "Non Resident Patron $patronId has an expiration date more than one year in the future. No update needed.",
			];
		}
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
	
	protected function createPatronPaymentNote($patron, $payment, $receiptNumber): void {
		global $logger;
		global $library;
		if ($library->finePaymentType == 3) {
			// Do MSB payment stuff
			global $serverName;
			require_once ROOT_DIR . '/sys/Email/Mailer.php';
			$mailer = new Mailer();
			$systemVariables = SystemVariables::getSystemVariables();
			$request = new stdClass();
			$request->Note = new stdClass();
			$request->Note->PatronID = $patron;
			$request->Note->NoteType = 2;
			$request->Note->NoteText = "Nexus Transaction Reference: $payment";
			$request->Modifiers = '';
			$result = $this->doSoapRequest('addPatronNote', $request);
			if ($result) {
				$success = stripos($result->ResponseStatuses->ResponseStatus->ShortMessage, 'Success') !== false;
				if (!$success) {
					$success = false;
					$message = "Failed to add patron note for payment in CarlX for Reference ID $payment .";
					$level = Logger::LOG_ERROR;
				} else {
					$success = true;
					$message = "Patron note for payment added successfully in CarlX for Reference ID $payment .";
					$level = Logger::LOG_NOTICE;
				}
			} else {
				$success = false;
				$message = "CarlX ILS gave no response when attempting to add patron note for payment Reference ID $payment .";
				$level = Logger::LOG_ERROR;
			}
			$logger->log($message, $level);
			if ($level == Logger::LOG_ERROR) {
				if (!empty($systemVariables->errorEmail)) {
					$mailer->send($systemVariables->errorEmail, "$serverName Error with MSB Payment", $message);
				}
			}
//		return [
//			'success' => $success,
//			'message' => $message,
//		];

		} else {
			// Do SnapPay stuff
			$paymentNote = new stdClass();
			$paymentNote->Note = new stdClass();
			$paymentNote->Note->PatronID = $patron->ils_barcode;
			$paymentNote->Note->NoteType = 2;
			$paymentNote->Note->NoteText = "$payment->paymentType Aspen Payment: $payment->id; CarlX Receipt: $receiptNumber";
			$paymentNote->Modifiers = '';
			$addPaymentNoteResult = $this->doSoapRequest('addPatronNote', $paymentNote, $this->patronWsdl, $this->genericResponseSOAPCallOptions, []);
			if ($addPaymentNoteResult) {
				$success = stripos($addPaymentNoteResult->ResponseStatuses->ResponseStatus[0]->ShortMessage, 'Success') !== false;
				if (!$success) {
					$logger->log("Failed to add patron note for payment in CarlX for Reference ID $payment->id", Logger::LOG_ERROR);
				}
			} else {
				$logger->log("CarlX gave no response when attempting to add patron note for payment Reference ID $payment->id", Logger::LOG_ERROR);
			}
		}
	}

	protected function feePaidViaSIP($SIP2FeeType = '01', $pmtType = '02', $pmtAmount, $curType = 'USD', $feeId = '', $transId = '', $patronId = ''): array {
		$mySip = $this->initSIPConnection();
		if (!is_null($mySip)) {
			$in = $mySip->msgFeePaid($SIP2FeeType, $pmtType, $pmtAmount, $curType, $feeId, $transId, $patronId);
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
		global $library;
		if ($library->finePaymentType == 3){
			// Do MSB payment stuff
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
					$myLostFines = $this->getLostViaSIP($patron->ils_barcode);
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
			// End MSB payment stuff
		} else {
			// Do SnapPay stuff
			$myFines = parent::getFines($patron, $includeMessages);
			foreach ($myFines as &$fine) {
				// Ensure $fine is an object
				if (is_array($fine)) {
					$fine = (object)$fine;
				}
				$fine->system = $this->getFineSystem($fine->branch);
				$fine->canPayFine = $this->canPayFine($fine->system);
				if ($fine->type == 'FS' && stripos($fine->reason, 'COLLECTION') !== false) {
					$fine->type = 'COLLECTION AGENCY';
					$fine->reason = 'COLLECTION AGENCY: must be paid last';
				} elseif ($fine->type == 'FS' && stripos($fine->reason, 'Non Resident Ful') !== false) {
					$fine->type = 'NON-RESIDENT FEE';
					$fine->reason = 'NON-RESIDENT FEE: ' . $fine->reason;
				} else {
					$fine->type = 'FEE';
					$fine->reason = 'FEE: ' . $fine->reason;
				}
				// Cast back to array
				$fine = (array)$fine;
			}
			unset($fine);
			// Sort fines by system (MNPS/NPL), then type (with Collection Agency at the bottom), then reason (ehich includes title for Lost items)
			$sorter = function ($a, $b) {
				$systemA = $a['system'];
				$systemB = $b['system'];
				if ($systemA === $systemB) {
					$typeA = $a['type'];
					$typeB = $b['type'];
					// Check if either type equals 'COLLECTION AGENCY'
					$isCollectionA = $typeA === 'COLLECTION AGENCY';
					$isCollectionB = $typeB === 'COLLECTION AGENCY';
					// If both are 'COLLECTION' or neither are, sort normally
					if ($isCollectionA && !$isCollectionB) {
						return 1; // $a should be after $b
					} elseif (!$isCollectionA && $isCollectionB) {
						return -1; // $a should be before $b
					} else {
						if ($typeA === $typeB) {
							$reasonA = $a['reason'];
							$reasonB = $b['reason'];
							return strcasecmp($reasonA, $reasonB);
						}
						return strcasecmp($typeA, $typeB);
					}
				}
				return strcasecmp($systemA, $systemB);
			};
			uasort($myFines, $sorter);
			return $myFines;
		}
	}

	public function getFineSystem($branchId): string {
		if (($branchId >= 30 && $branchId <= 178 && $branchId != 42 && $branchId != 167 && $branchId != 171) || ($branchId >= 180 && $branchId <= 212 && $branchId != 185 && $branchId != 187)) {
			return "MNPS";
		} else {
			return "NPL";
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

	public function getCollectionReportData($location, $date): array {
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$sql = <<<EOT
			-- get the almost-whole enchilada (on shelf, charged, missing, lost, damaged, in processing; NOT withdrawn) for an owning branch
			with i as (
				select
					i.*
				from item_v2 i
				right join branch_v2 b on i.branch = b.branchnumber
				where upper(b.branchcode) = upper('$location')
			), r as (
				select
					r.refid
					, max(r.returndate) as returndate
				from itemnotewhohadit_v2 r
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
				left join bbibmap_v2 b on ir.bid = b.bid
			), ix as (
				select 
					distinct irb.item as Barcode
					, irb.title as Title
					, irb.author as Author
					, irb.publishingdate as PubDate
					, irb.price as Price
					, l.locname as Location
					, m.medcode as Media
					, irb.cn as CallNumber
					, to_char(irb.returndate, 'MM/DD/YYYY') as LastReturned
					, irb.cumulativehistory Circ
					, to_char(irb.creationdate, 'MM/DD/YYYY') as Created
					, c.description as Status
					, to_char(irb.statusdate, 'MM/DD/YYYY') as StatusDate
				from irb
				left join location_v2 l on irb.location = l.locnumber
				left join media_v2 m on irb.media = m.mednumber
				left join systemitemcodes_v2 c on irb.status = c.code
				where c.type not in ('A','D') -- exclude Acquisitions Dummy and Circulation Dummy status types
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

	public function getHoldsReportData($location): array {
		$this->initDatabaseConnection();
		$sql = <<<EOT
-- getHoldsReportData, combining bib level and item level holds 2024 07 09
			with holds_vs_items as (
				select
					t.bid
					, t.occur
					, pb.branchname as PICKUP_BRANCH
					, p.name as PATRON_NAME
					, p.sponsor as HOME_ROOM
					, bb.btyname as GRD_LVL
					, p.patronid as P_BARCODE
					, l.locname as SHELF_LOCATION
					, b.title as TITLE
					, i.cn as CALL_NUMBER
					, i.item as ITEM_ID
					, dense_rank() over (partition by t.bid order by t.occur asc) as occur_dense_rank
					, dense_rank() over (partition by t.bid order by i.item asc) as nth_item_on_shelf
				from transbid_v2 t
				left join patron_v2 p on t.patronid = p.patronid
				left join bbibmap_v2 b on t.bid = b.bid
				left join bty_v2 bb on p.bty = bb.btynumber
				left join branch_v2 ob on t.holdingbranch = ob.branchnumber -- Origin/Owning Branch
				left join item_v2 i on ( t.bid = i.bid and t.holdingbranch = i.branch)
				left join location_v2 l on i.location = l.locnumber
				left join branch_v2 pb on t.pickupbranch = pb.branchnumber -- Pickup Branch
				where upper(ob.branchcode) = upper('$location')
				-- and t.pickupbranch = ob.branchnumber -- commented out in 23.08.01 to include MNPS Exploratorium holds; originally meant to ensure a lock between school collection and pickup branch ; pickup branch field changed from t.renew to t.pickupbranch in CarlX 9.6.8.0
				and t.transcode = 'R*'
				and i.status = 'S'
				order by 
					t.bid
					, t.occur
			), 
			fillable as (
				select 
					h.*
				from holds_vs_items h
				where h.occur_dense_rank = h.nth_item_on_shelf
				order by 
					h.SHELF_LOCATION
					, h.CALL_NUMBER
					, h.TITLE
					, h.occur_dense_rank
			), 
			bib_level_holds as (
				select
					PATRON_NAME
					, PICKUP_BRANCH
					, HOME_ROOM
					, GRD_LVL
					, P_BARCODE
					, SHELF_LOCATION
					, TITLE
					, CALL_NUMBER
					, ITEM_ID
				from fillable
			),
			item_level_holds as (
				select
					p.name as PATRON_NAME
					, pb.branchname as PICKUP_BRANCH
					, p.sponsor as HOME_ROOM
					, bb.btyname as GRD_LVL
					, p.patronid as P_BARCODE
					, l.locname as SHELF_LOCATION
					, b.title as TITLE
					, i.cn as CALL_NUMBER
					, i.item as ITEM_ID
				from transitem_v2 t
				left join item_v2 i on t.item = i.item
				left join patron_v2 p on t.patronid = p.patronid
				left join bbibmap_v2 b on i.bid = b.bid
				left join bty_v2 bb on p.bty = bb.btynumber
				left join branch_v2 ob on t.holdingbranch = ob.branchnumber -- Origin Branch
				left join location_v2 l on i.location = l.locnumber
				left join branch_v2 pb on t.pickupbranch = pb.branchnumber -- Pickup Branch
				where upper(ob.branchcode) = upper('$location')
				and t.transcode = 'R*'
				order by 
					i.bid
			)
			, holds as (
				select
					bib_level_holds.*
				from bib_level_holds
				union
				select
					item_level_holds.*
				from item_level_holds
			)
			select * from holds
			order by 
				SHELF_LOCATION
				, CALL_NUMBER
				, TITLE
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
		public function getStudentBarcodeData($location, $homeroom): array  {
		$this->initDatabaseConnection();
		// query students by school and homeroom
		/** @noinspection SqlResolve */
		// If homeroom is ALLSTUDENTS, then we are looking for all students in the school
		if ($homeroom == 'ALLSTUDENTS') {
			$sql = <<<EOT
				select
					patronbranch.branchcode
					, patronbranch.branchname
					, p.bty as bty
					, case
						when p.bty < 21 or p.bty > 34
							then null
						-- bty 21 = Pre-K, 22 = K, 23 = 1st ... 34 = 12th
						when p.bty = 21 then 'PreK'
						when p.bty = 22 then 'K'
						when p.bty = 23 then '1st'
						when p.bty = 24 then '2nd'
						when p.bty = 25 then '3rd'
						else p.bty-22 || 'th'
					end as grade
					, 'HR: ' || initcap(p.sponsor) as homeroom
					, p.street2 as homeroomid
					, p.name as patronname
					, p.patronid
					, p.lastname
					, p.firstname
					, p.middlename
					, p.suffixname
				from
					patron_v2 p
					left join branch_v2 patronbranch on patronbranch.branchnumber = p.defaultbranch
					left join bty_v2 bty on p.bty = bty.btynumber
				where
					p.bty in ('21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','46','47')
					and upper(patronbranch.branchcode) = upper('$location')
					and p.street2 is not null
				order by
					1, 3, 7, 8, 9
EOT;
		}
		// If homeroom is ALL_*, then we are looking for all students in a grade level
		elseif (strpos($homeroom, 'ALL_') === 0) {
			$bty = substr($homeroom, 4);
			$sql = <<<EOT
				select
					patronbranch.branchcode
					, patronbranch.branchname
					, p.bty as bty
					, case
						when p.bty < 21 or p.bty > 34
							then null
						-- bty 21 = Pre-K, 22 = K, 23 = 1st ... 34 = 12th
						when p.bty = 21 then 'PreK'
						when p.bty = 22 then 'K'
						when p.bty = 23 then '1st'
						when p.bty = 24 then '2nd'
						when p.bty = 25 then '3rd'
						else p.bty-22 || 'th'
					end as grade
					, 'HR: ' || initcap(p.sponsor) as homeroom
					, p.street2 as homeroomid
					, p.name as patronname
					, p.patronid
					, p.lastname
					, p.firstname
					, p.middlename
					, p.suffixname
				from
					patron_v2 p
					left join branch_v2 patronbranch on patronbranch.branchnumber = p.defaultbranch
					left join bty_v2 bty on p.bty = bty.btynumber
				where
					p.bty = '$bty'
					and p.bty in ('21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','46','47')
					and upper(patronbranch.branchcode) = upper('$location')
					and p.street2 is not null
				order by
					p.name
EOT;
// If homeroom is a specific homeroom, then we are looking for students -- and staff -- in that homeroom
		} else {
			$sql = <<<EOT
				select
					patronbranch.branchcode
					, patronbranch.branchname
					, p.bty AS bty
					, case
						when p.bty < 21 or p.bty > 34
							then null
						-- bty 21 = Pre-K, 22 = K, 23 = 1st ... 34 = 12th
						when p.bty = 21 then 'PreK'
						when p.bty = 22 then 'K'
						when p.bty = 23 then '1st'
						when p.bty = 24 then '2nd'
						when p.bty = 25 then '3rd'
						else p.bty-22 || 'th'
					end as grade
					, case 
							when (p.bty = 13 OR p.bty = 40 OR p.bty = 51)
							then 'HR: ' || initcap(p.name)
							else 'HR: ' || initcap(p.sponsor)
						end as homeroom
					, case 
							when (p.bty = 13 OR p.bty = 40 OR p.bty = 51) 
							then p.patronid
							else p.street2
						end as homeroomid
					, p.name AS patronname
					, p.patronid
					, p.lastname
					, p.firstname
					, p.middlename
					, p.suffixname
				from
					patron_v2 p
					left join branch_v2 patronbranch on patronbranch.branchnumber = p.defaultbranch
					left join bty_v2 bty on p.bty = bty.btynumber
				where
					p.bty = bty.btynumber
					and patronbranch.branchnumber = p.defaultbranch
					and (
						(
							p.bty in ('21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','46','47')
							and upper(patronbranch.branchcode) = upper('$location')
							and p.street2 = '$homeroom'
						) or (
							(p.bty = 13 OR p.bty = 40 OR p.bty = 51)
							and p.patronid = '$homeroom'
						)
					)
				order by
					patronbranch.branchcode
					, case
						when (p.bty = 13 OR p.bty = 40 OR p.bty = 51) then 0 -- sort staff above students
						else 1
					end
					, p.sponsor
					, p.name
EOT;
		}
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
	public function getStudentBarcodeDataHomerooms($location) : array {
		$this->initDatabaseConnection();
		// query school branch codes and homerooms
		/** @noinspection SqlResolve */
		/** @noinspection SqlConstantExpression */
		$sql = <<<EOT
			select 
				branchcode
				, min(grade) as bty
				, homeroomid
				, min(homeroomname) as homeroomname
				, case
					when min(grade) < max(grade)
						then replace(replace(trim(to_char(min(grade),'00')),'-01','PK'),'00','KI') || '-' || replace(replace(trim(to_char(max(grade),'00')),'-01','PK'),'00','KI')
					else replace(replace(trim(to_char(min(grade),'00')),'-01','PK'),'00','KI') || '___'
				end as grade
			from (
				select distinct
					b.branchcode
					, s.street2 as homeroomid
					, nvl(regexp_replace(upper(h.name),'[^A-Z]','_'),'_NULL_') as homeroomname
					, case
						when s.bty < 21 or s.bty > 34
							then null
						else s.bty-22 -- bty 21 = Pre-K, 22 = K, 23 = 1st ... 34 = 12th
					end as grade
				from
					branch_v2 b
				left join patron_v2 s on b.branchnumber = s.defaultbranch
				left join patron_v2 h on s.street2 = h.patronid
				where
					upper(b.branchcode) = upper('$location')
					and s.street2 is not null
					and s.bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42','46','47','51')
				order by
				b.branchcode
				, homeroomname
			) a
			group by branchcode, homeroomid
			--			order by branchcode, homeroomname
			union all
			-- entries for 'all students by grade', e.g., 'All Fifth Grade Students'
			select
				distinct b.branchcode
				, p.bty
				, 'ALL_' || p.bty as homeroomid
				, '' as homeroomname
				, case
					when (p.bty >= 21 and p.bty <= 34) then 'all ' || replace(replace(trim(to_char(p.bty-22,'00')),'-01','PK'),'00','KI') || ' ' || bty.btyname
					else 'all ' || bty.btyname
				end as grade
			from patron_v2 p
			left join branch_v2 b on p.defaultBranch = b.branchnumber
			left join bty_v2 bty on p.bty = bty.btynumber
			where ((p.bty >= 21 and p.bty <= 34) or p.bty in (35,36,37,46,47))
			and upper(b.branchcode) = upper('$location')
			union all
			-- entry for All Students
			select
				distinct b.branchcode
				, 999 as bty
				, 'ALLSTUDENTS' as homeroomid
				, '' as homeroomname
				, 'all students' as grade
			from patron_v2 p
			left join branch_v2 b on p.defaultBranch = b.branchnumber
			left join bty_v2 bty on p.bty = bty.btynumber
			where ((p.bty >= 21 and p.bty <= 34) or p.bty in (35,36,37,46,47))
			and upper(b.branchcode) = upper('$location')
			order by 2, 5, 4
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
		public function getStudentReportData($location, $showOverdueOnly, $date) : ?array {
		$this->initDatabaseConnection();
		if ($showOverdueOnly == 'checkedOut') {
			$statuses = "(TRANSITEM_v2.transcode = 'O' or transitem_v2.transcode='L' or transitem_v2.transcode='C')";
		} elseif ($showOverdueOnly == 'overdue') {
			$statuses = "(TRANSITEM_v2.transcode = 'O' or transitem_v2.transcode='L')";
		}
		if ($showOverdueOnly == 'checkedOut' || $showOverdueOnly == 'overdue') {
			/** @noinspection SqlResolve */
			$sql = <<<EOT
				select
					patronbranch.branchcode AS Home_Lib_Code
					, patronbranch.branchname AS Home_Lib
					, bty_v2.btynumber AS P_Type
					, bty_v2.btyname AS Grd_Lvl
					, patron_v2.sponsor AS Home_Room
					, patron_v2.name AS Patron_Name
					, patron_v2.patronid AS P_Barcode
					, case
						when itembranch.branchgroup = 2 then 'MNPS'
						else 'NPL'
					end AS SYSTEM
					, item_v2.cn AS Call_Number
					, bbibmap_v2.title AS Title
					, to_char(transitem_v2.duedate,'MM/DD/YYYY') AS Due_Date
					, item_v2.price AS Owed
					, to_char(transitem_v2.duedate,'MM/DD/YYYY') AS Due_Date_Dup
					, item_v2.item AS Item
					, item_v2.bid AS recordId
				from 
					bbibmap_v2
					, branch_v2 patronbranch
					, branch_v2 itembranch
					, branchgroup_v2 patronbranchgroup
					, branchgroup_v2 itembranchgroup
					, bty_v2
					, item_v2
					, location_v2
					, patron_v2
					, transitem_v2
				where
					patron_v2.patronid = transitem_v2.patronid
					and patron_v2.bty = bty_v2.btynumber
					and transitem_v2.item = item_v2.item
					and bbibmap_v2.bid = item_v2.bid
					and patronbranch.branchnumber = patron_v2.defaultbranch
					and location_v2.locnumber = item_v2.location
					and itembranch.branchnumber = transitem_v2.holdingbranch
					and itembranchgroup.branchgroup = itembranch.branchgroup
					and $statuses
					and patronbranch.branchgroup = '2'
					and patronbranchgroup.branchgroup = patronbranch.branchgroup
					and bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42','46','47','51')
					and upper(patronbranch.branchcode) = upper('$location')
				order by 
					patronbranch.branchcode
					, patron_v2.bty
					, patron_v2.sponsor
					, patron_v2.name
					, itembranch.branchgroup
					, item_v2.cn
					, bbibmap_v2.title
EOT;
		} elseif ($showOverdueOnly == 'fees') {
			/** @noinspection SqlResolve */
			$sql = <<<EOT
				-- school fees report CarlX sql
				with p as ( -- first gather patrons of the requested branch
					select
						b.branchcode
						, b.branchname
						, p.bty
						, t.btyname
						, p.sponsor
						, p.name
						, p.patronid
					from patron_v2 p
					left join branch_v2 b on p.defaultbranch = b.branchnumber
					left join bty_v2 t on p.bty = t.btynumber
					where p.bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42','46','47','51')
					and upper(b.branchcode) = upper('$location')
				), r as (
					select
						p.branchcode
						, p.branchname
						, p.bty
						, p.btyname
						, p.sponsor
						, p.name
						, p.patronid
						, r.callnumber
						, r.title
						, to_char(r.duedate,'MM/DD/YYYY') as due
						, to_char(r.amountowed / 100, 'fm999D00') as owed
						, r.item
					from p 
					left join report3fines_v2 r on p.patronid = r.patronid
					where r.patronid is not null
						and r.amountowed > 0
					order by 
						p.branchcode
						, p.bty
						, p.sponsor
						, p.name
						, r.callnumber
						, r.title
				) 
				select
					r.branchcode AS Home_Lib_Code
					, r.branchname AS Home_Lib
					, r.bty AS P_Type
					, r.btyname AS Grd_Lvl
					, r.sponsor AS Home_Room
					, r.name AS Patron_Name
					, r.patronid AS P_Barcode
					, case
						when itembranch.branchgroup = 2 then 'MNPS'
						else 'NPL'
					end AS SYSTEM
					, r.callnumber AS Call_Number
					, r.title AS Title
					, r.due AS Due_Date
					, r.owed as Owed
					, r.due AS Due_Date_Dup
					, r.item AS Item
					, i.bid AS recordId
				from r
				left join item_v2 i on r.item = i.item
				left join branch_v2 itembranch on i.owningbranch = itembranch.branchnumber
				order by 
					r.branchcode
					, r.bty
					, r.sponsor
					, r.name
					, itembranch.branchgroup
					, r.callnumber
					, r.title
EOT;
		}
		$stid = oci_parse($this->dbConnection, $sql);
		// consider using oci_set_prefetch to improve performance
		// oci_set_prefetch($stid, 1000);
		oci_execute($stid);
		while (($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) != false) {
			require_once ROOT_DIR . '/RecordDrivers/MarcRecordDriver.php';
			$recordDriver = new MarcRecordDriver($this->accountProfile->recordSource . ':' . parent::fullCarlIDfromBID($row['RECORDID']));
			if ($recordDriver->isValid()) {
				$row['coverUrl'] = $recordDriver->getBookcoverUrl('small', true);
			} else {
				$row['coverUrl'] = '';
			}
			$data[] = $row;
		}
		oci_free_statement($stid);
		// if data does not exist, return empty array
		if (!isset($data)) {
			$data = [];
		}
		return $data;
	}
	public function getWeedingReportData($location): array {
//        set_time_limit(0);
		ini_set('memory_limit', '6G');
		$this->initDatabaseConnection();
		/** @noinspection SqlResolve */
		$sql = <<<EOT
			-- Weeding Report 2024 05 05 by James Staub. This query is NOT efficient and often takes more than 1 minute to run.
			with 
			i as (
				select
					m.medname
					, l.locname
					, substr(l.loccode,2) as collection
					, upper(i.cn) as item_callnumber
					, i.item
					, s.description as status
					, i.cumulativehistory
					, i.bid
					, to_char(i.creationdate, 'MM/DD/YYYY') as creationdate
				from item_v2 i
				left join media_v2 m on i.media = m.mednumber
				left join location_v2 l on i.location = l.locnumber
				left join systemitemcodes_v2 s on i.status = s.code
				right join branch_v2 b on i.branch = b.branchnumber
				where upper(b.branchcode) = upper('$location')
				and s.type not in ('A', 'D')
			),
			r as (
				select
					r.refid
					, to_char(max(r.returndate),'MM/DD/YYYY') as returndate
				from itemnotewhohadit_v2 r
				group by r.refid
			), 
			ir as (
				select
					i.*
					, r.returndate
				from i
				left join r on i.item = r.refid
			),
			ib as (
				select
					ir.*
					, b.title
					, b.author
					, b.publishingdate
					, trim(trailing chr(10) from b.callnumber) as bib_callnumber -- because a handful of bib callnumbers have newline characters hidden at the end
				from ir
				left join bbibmap_v2 b on ir.bid = b.bid
			),
			bd as ( -- dewey number, up to hundredths place, from item record first, bibliographic record if item call number is not Dewey, or else false if no Dewey number found in Item call number or bibliographic record
				select
					ib.*
					, case
						when collection = 'ADAPT' then 1105
						when collection = 'BIGBK' then 1046
						when collection = 'BIOG' then 1005
						when collection = 'BOARD' then 1045
						when collection = 'EVERY' then 1093
						when collection = 'FIC' then 1013
						when collection = 'MOVIE' then 1020
						when collection = 'OTHER' then 1059
						when collection = 'PER' then 1027
						when collection = 'TECH' then 1076
						when collection = 'TEXT' then 1106
				-- NB: the regular expressions below should be modified when moving between Oracle SQL and php
				-- In Oracle SQL, backreference should be '\1', otherwise "invalid number" error
				-- In php, backreference should be '\\1', otherwise "Warning: oci_fetch_array(): ORA-24374: define not done before fetch or execute and fetch in C:\web\aspen-discovery\code\web\Drivers\Nashville.php on line ..."
						when regexp_like(item_callnumber,'^[^0-9]*[0-9]{3} ')
							then to_number(regexp_replace(item_callnumber,'^[^0-9]*([0-9]{3}) .*$','\\1'))
						when regexp_like(item_callnumber,'^[^0-9]*[0-9]{3}\.[0-9]{0,2}.*$')
							then to_number(regexp_replace(item_callnumber,'^[^0-9]*([0-9]{3}\.[0-9]{0,2}).*$','\\1'))
						when regexp_like(bib_callnumber,'^[^0-9]*[0-9]{3} ')
							then to_number(regexp_replace(bib_callnumber,'^[^0-9]*([0-9]{3}) .*$','\\1'))
						when regexp_like(bib_callnumber,'^[^0-9]*[0-9]{3}\.[0-9]{0,2}.*$')
							then to_number(regexp_replace(bib_callnumber,'^[^0-9]*([0-9]{3}\.[0-9]{0,2}).*$','\\1'))
						when collection = 'AUBK' then 1001
						when collection = 'GRAPH' then 1014
						when collection = 'PROF' then 1028
						when collection = 'REF' then 1029
						else -1
					end as dewey_number
				from ib
			),
			d (dewey_number, dewey_label, keep, discard) as ( -- dewey numbers from 2023 karen lowe workshop spreadsheet
				select '-1','Unrecognized collection','1000','-1000' from dual union all
				select '000','General Works','-7','-15' from dual union all
				select '001','General Works','-7','-15' from dual union all
				select '001.9','Phenomena','-18','-26' from dual union all
				select '004','Computers/technology','-5','-10' from dual union all
				select '005','Computers/Ethics','-5','-10' from dual union all
				select '006','Computers/Ethics','-5','-10' from dual union all
				select '010','Bibliographies','-18','-26' from dual union all
				select '020','Library/Information Science','-7','-14' from dual union all
				select '030','General Encyclopedic Works','-6','-11' from dual union all
				select '050','General Serial Publications','-8','-16' from dual union all
				select '060','Organizations/Museums','-15','-21' from dual union all
				select '070','News/Journalism','-7','-16' from dual union all
				select '080','General Collections','-15','-21' from dual union all
				select '090','Manuscripts/Rare Books','-18','-26' from dual union all
				select '100','Philosophy-Gen. Topics','-15','-25' from dual union all
				select '110','Metaphysics','-15','-25' from dual union all
				select '120','Knowledge/Cause-Man','-15','-25' from dual union all
				select '130','Paranormal Phenomena','-15','-25' from dual union all
				select '133.1','Ghosts/stories','-19','-29' from dual union all
				select '133.4','Magic/Witchcraft','-17','-25' from dual union all
				select '135','Dreams/Mysteries','-17','-25' from dual union all
				select '140','Philosophical Viewpoints','-17','-25' from dual union all
				select '150','Psychology','-16','-25' from dual union all
				select '152','Emotions/Feelings','-14','-20' from dual union all
				select '155','Developmental Psychology','-14','-20' from dual union all
				select '156','Behavior','-14','-20' from dual union all
				select '158','Coping w/… (Issues)','-8','-14' from dual union all
				select '160','Logic','-15','-25' from dual union all
				select '170','Ethics/Character Education','-8','-14' from dual union all
				select '180','Ancient/Oriental Philosophy','-18','-25' from dual union all
				select '190','Modern Western Philosophy','-15','-25' from dual union all
				select '200','Religion – Gen. Topics','-14','-20' from dual union all
				select '210','Natural Theology','-14','-20' from dual union all
				select '220','Bible','-18','-26' from dual union all
				select '230','Christian Theology','-18','-26' from dual union all
				select '240','Christian Moral and Devotional','-18','-26' from dual union all
				select '250','Local Christian Church','-18','-26' from dual union all
				select '260','Christian Social Theology','-18','-26' from dual union all
				select '270','Church History','-18','-26' from dual union all
				select '280','Denominations/Sects','-18','-26' from dual union all
				select '290','Comparative Religion','-14','-20' from dual union all
				select '291','Mythology','-18','-26' from dual union all
				select '292','Mythology','-18','-26' from dual union all
				select '293','Mythology','-18','-26' from dual union all
				select '294','Other Religions','-14','-20' from dual union all
				select '295','Other Religions','-14','-20' from dual union all
				select '296','Other Religions','-14','-20' from dual union all
				select '297','Other Religions','-14','-20' from dual union all
				select '298','Other Religions','-14','-20' from dual union all
				select '299','Other Religions','-14','-20' from dual union all
				select '300','Sociology-Gen. Topics','-10','-16' from dual union all
				select '301.2','Culture/Processes','-10','-16' from dual union all
				select '301.24','Social Change','-10','-16' from dual union all
				select '301.3','Censorship/Prejudice/Propaganda','-10','-16' from dual union all
				select '301.3','Ecology','-10','-16' from dual union all
				select '301.4','Sexes/Marriage/Family','-10','-16' from dual union all
				select '302','Communication','-10','-16' from dual union all
				select '303','Social Interaction','-10','-16' from dual union all
				select '303.2','Communication','-10','-16' from dual union all
				select '303.4','Change/Group Interaction','-10','-16' from dual union all
				select '303.6','Conflict/Terrorism','-10','-16' from dual union all
				select '304','Social Behavior','-10','-16' from dual union all
				select '304.2','Ecology','-10','-16' from dual union all
				select '304.5','Genetics','-10','-16' from dual union all
				select '304.6','Population','-10','-16' from dual union all
				select '304.8','Immigration','-10','-16' from dual union all
				select '305','Social Groups','-10','-16' from dual union all
				select '306','Culture','-10','-16' from dual union all
				select '307','Communities/Cities/Towns','-10','-16' from dual union all
				select '309','Social Situations/Conditions','-10','-16' from dual union all
				select '310','Statistics','-15','-21' from dual union all
				select '320','Politics/Political Science','-10','-16' from dual union all
				select '320.1','State','-10','-16' from dual union all
				select '320.3','Comparative Government','-10','-16' from dual union all
				select '320.4','Civics','-10','-16' from dual union all
				select '321','Governments and States','-10','-16' from dual union all
				select '322','Relation of State/Groups','-10','-16' from dual union all
				select '323','Civil and Political Rights','-10','-16' from dual union all
				select '323.1','Racial/Ethnic Groups','-10','-16' from dual union all
				select '323.4','Civil Rights/Liberty','-10','-16' from dual union all
				select '323.5','Political Rights','-10','-16' from dual union all
				select '323.6','Citizenship','-10','-16' from dual union all
				select '324','Voting/Electoral Process','-10','-16' from dual union all
				select '325','Migration/Colonization','-10','-16' from dual union all
				select '326','Slavery/Emancipation','-10','-16' from dual union all
				select '327','International relations/Spies','-10','-16' from dual union all
				select '328','Legislation              ','-10','-16' from dual union all
				select '330','Economics-General Topics','-5','-11' from dual union all
				select '331','Labor Economics/Careers','-5','-11' from dual union all
				select '332','Finance/Money','-5','-11' from dual union all
				select '333','Land Economics/Conservation','-8','-14' from dual union all
				select '334','Cooperatives','-8','-14' from dual union all
				select '335','Socialism/Related Systems','-8','-14' from dual union all
				select '336','Public Finance/Taxes','-5','-11' from dual union all
				select '337','International economics','-5','-11' from dual union all
				select '338','Production','-5','-11' from dual union all
				select '338.1','Costs/Prices/Income','-5','-11' from dual union all
				select '339','Macroeconomics','-5','-11' from dual union all
				select '340','Law-General Topics','-10','-16' from dual union all
				select '341','International Law/United Nations','-5','-11' from dual union all
				select '341.26','States','-10','-16' from dual union all
				select '341.73','Defense/Mutual Security','-5','-11' from dual union all
				select '342','Constitutional Law','-18','-24' from dual union all
				select '342.4','Government Structure','-10','-16' from dual union all
				select '342.5','Legislative Branch','-10','-16' from dual union all
				select '342.6','Executive Branch','-10','-16' from dual union all
				select '342.7','Election Law','-10','-16' from dual union all
				select '342.9','Local Government','-10','-16' from dual union all
				select '343','Military/tax/trade/industrial law','-5','-11' from dual union all
				select '344','Social/labor/welfare Law','-10','-16' from dual union all
				select '345','Criminal Law','-10','-16' from dual union all
				select '346','Private Law','-10','-16' from dual union all
				select '347','Civil Procedure/Courts','-13','-19' from dual union all
				select '348','Laws/Regulations/Cases','-13','-19' from dual union all
				select '350','Government','-10','-16' from dual union all
				select '351','Government','-10','-16' from dual union all
				select '352','Central Governments/Local Units     ','-10','-16' from dual union all
				select '353','Federal and State Government','-10','-16' from dual union all
				select '354','Specific Central Governments','-10','-16' from dual union all
				select '355','Military Science','-5','-11' from dual union all
				select '356','Military Science','-5','-11' from dual union all
				select '357','Military Science','-5','-11' from dual union all
				select '358','Military Science','-5','-11' from dual union all
				select '359','Military Science','-5','-11' from dual union all
				select '360','Social Services/Issues/Disasters','-10','-16' from dual union all
				select '361','Gen. Social problems/services','-10','-16' from dual union all
				select '362','Social Welfare Problems','-10','-16' from dual union all
				select '362.1','Physical Illness','-10','-16' from dual union all
				select '362.2','Mental Illness/Drugs','-6','-12' from dual union all
				select '362.4','Physical Disabilities','-10','-16' from dual union all
				select '362.5','Poverty','-10','-16' from dual union all
				select '362.6','Aged People','-10','-16' from dual union all
				select '362.7','Young People','-10','-16' from dual union all
				select '362.8','Families/Unwed Mothers','-10','-16' from dual union all
				select '362.9','Minority/Labor/Victims','-10','-16' from dual union all
				select '363','Other Social Services/Issues','-10','-16' from dual union all
				select '363.2','Police','-10','-16' from dual union all
				select '363.3','Public Safety','-10','-16' from dual union all
				select '363.34','Terrorism','-10','-16' from dual union all
				select '363.4','Public Morals','-10','-16' from dual union all
				select '363.5','Public Works','-10','-16' from dual union all
				select '363.6','Public Utilities','-10','-16' from dual union all
				select '363.7','Ecology','-10','-16' from dual union all
				select '364','Crime/Alleviation','-15','-21' from dual union all
				select '365','Penal Institutions/Prisons','-15','-21' from dual union all
				select '366','Associations','-15','-21' from dual union all
				select '367','General Clubs','-15','-21' from dual union all
				select '368','Insurance','-15','-21' from dual union all
				select '369','Misc. Associations/Boy/Girl Scouts','-15','-21' from dual union all
				select '370','Education-General Topics','-15','-21' from dual union all
				select '371','School Mgt./Special Ed.','-15','-21' from dual union all
				select '372','Elementary Education','-15','-21' from dual union all
				select '373','Secondary Education','-15','-21' from dual union all
				select '374','Adult Education','-15','-21' from dual union all
				select '375','Curricula','-15','-21' from dual union all
				select '376','Education of Women','-15','-21' from dual union all
				select '377','Schools and Religion','-15','-21' from dual union all
				select '378','Higher Education','-15','-21' from dual union all
				select '379','Education and the State','-15','-21' from dual union all
				select '380','Commerce','-8','-14' from dual union all
				select '381','Commerce','-8','-14' from dual union all
				select '383','Communication','-5','-11' from dual union all
				select '384','Communication','-5','-11' from dual union all
				select '385','Transportation','-8','-14' from dual union all
				select '386','Transportation','-8','-14' from dual union all
				select '387','Transportation','-8','-14' from dual union all
				select '388','Transportation','-8','-14' from dual union all
				select '389','Metrology','-18','-24' from dual union all
				select '390','Customs/Etiquette/Folklore','-18','-26' from dual union all
				select '391','Costume/Personal Appearance','-25','-31' from dual union all
				select '392','Customs of Life/Domestic Life','-10','-16' from dual union all
				select '393','Death Customs','-10','-16' from dual union all
				select '394','Holidays/General Customs','-25','-31' from dual union all
				select '395','Manners/Etiquette','-10','-16' from dual union all
				select '398','Folklore/Fairy Tales','-25','-31' from dual union all
				select '399','Customs of War/Diplomacy','-10','-16' from dual union all
				select '400','Language-General Topics','-18','-26' from dual union all
				select '410','Linguistics','-18','-26' from dual union all
				select '420','English/Grammar','-18','-26' from dual union all
				select '423','English Dictionaries','-18','-26' from dual union all
				select '430','German','-18','-26' from dual union all
				select '440','French','-18','-26' from dual union all
				select '450','Italian','-18','-26' from dual union all
				select '460','Spanish','-18','-26' from dual union all
				select '470','Latin','-18','-26' from dual union all
				select '480','Greek','-18','-26' from dual union all
				select '490','Other Languages','-18','-26' from dual union all
				select '500','Science-General Topics','-10','-16' from dual union all
				select '501','Science-General Topics','-10','-16' from dual union all
				select '502','Equipment/Instruments','-15','-21' from dual union all
				select '503','Dictionaries/Encyclopedias','-10','-16' from dual union all
				select '507','Projects/Experiments','-15','-21' from dual union all
				select '508','Nature/Seasons','-20','-26' from dual union all
				select '509','Science History','-18','-24' from dual union all
				select '510','Mathematics','-15','-21' from dual union all
				select '512','Algebra/Number Theory','-15','-21' from dual union all
				select '513','Arithmetic','-15','-21' from dual union all
				select '514','Topology','-15','-21' from dual union all
				select '515','Analysis','-15','-21' from dual union all
				select '516','Geometry','-15','-21' from dual union all
				select '519','Probability','-15','-21' from dual union all
				select '520','Space Science/Astronomy','-5','-11' from dual union all
				select '521','Astronomy-Theories','-5','-11' from dual union all
				select '522','Astronomy-Instruments','-5','-11' from dual union all
				select '523','Celestial Bodies/Phenomena','-5','-11' from dual union all
				select '523.1','Universe','-5','-11' from dual union all
				select '523.2','Solar System','-5','-11' from dual union all
				select '523.3','Moon','-5','-11' from dual union all
				select '523.4','Planets','-5','-11' from dual union all
				select '523.5','Meteors','-5','-11' from dual union all
				select '523.6','Comets','-5','-11' from dual union all
				select '523.7','Sun','-5','-11' from dual union all
				select '523.8','Stars','-5','-11' from dual union all
				select '523.9','Satellites','-5','-11' from dual union all
				select '525','Earth/Seasons/Tides/Day/Night','-10','-16' from dual union all
				select '526','Mathematical Geography','-10','-16' from dual union all
				select '529','Time','-10','-16' from dual union all
				select '530','Physical Science/Physics','-15','-21' from dual union all
				select '531','Matter/Energy (Mechanics); Force/Motion; Simple Machines','-15','-21' from dual union all
				select '532','Water/Fluids','-15','-21' from dual union all
				select '533','Air/Gases','-15','-21' from dual union all
				select '534','Sound','-15','-21' from dual union all
				select '535','Light/Color','-15','-21' from dual union all
				select '536','Heat','-15','-21' from dual union all
				select '537','Electricity/Electronics','-15','-21' from dual union all
				select '538','Magnetism','-15','-21' from dual union all
				select '539','Atoms/Modern Physics','-5','-11' from dual union all
				select '540','Chemistry','-15','-21' from dual union all
				select '541','Physical/theoretical chemistry','-10','-16' from dual union all
				select '542','Laboratories/Equipment','-15','-21' from dual union all
				select '546','Elements (Inorganic Chemistry)','-15','-21' from dual union all
				select '547','Organic Chemistry','-15','-21' from dual union all
				select '548','Crystals','-18','-24' from dual union all
				select '549','Rocks/Minerals (Mineralogy)','-18','-24' from dual union all
				select '550','Earth Science-Gen. Topics','-15','-21' from dual union all
				select '551','Geology','-15','-21' from dual union all
				select '551.2','Earthquakes/Volcanoes','-15','-21' from dual union all
				select '551.3','Glaciers/Icebergs','-15','-21' from dual union all
				select '551.4','Landforms/Oceanography','-15','-21' from dual union all
				select '551.5','Weather/Climate','-5','-11' from dual union all
				select '551.6','Weather/Climate','-5','-11' from dual union all
				select '552','Rocks/Minerals (Petrology)','-18','-26' from dual union all
				select '553','Economic Geology','-10','-16' from dual union all
				select '560','Paleontology-General Topics','-15','-21' from dual union all
				select '562','Fossils','-15','-21' from dual union all
				select '563','Fossils','-15','-21' from dual union all
				select '564','Fossils','-15','-21' from dual union all
				select '565','Fossils','-15','-21' from dual union all
				select '566','Dinosaurs','-15','-21' from dual union all
				select '567','Dinosaurs','-15','-21' from dual union all
				select '568','Dinosaurs','-15','-21' from dual union all
				select '569','Fossil Mammals','-15','-21' from dual union all
				select '570','Life Science-General Topics','-10','-16' from dual union all
				select '571','Life Science-General Topics','-10','-16' from dual union all
				select '572','Photosynthesis','-10','-16' from dual union all
				select '573','Physical Anthropology','-10','-16' from dual union all
				select '574','Biology/Environment','-15','-21' from dual union all
				select '574.8','Tissues/Cells/Molecules','-15','-21' from dual union all
				select '575','Microbiology','-16','-22' from dual union all
				select '576','Genetics','-10','-16' from dual union all
				select '577','Habitats/Ecosystems/Biomes/Food Chains/Life Cycles','-15','-21' from dual union all
				select '578','Microscopy','-15','-21' from dual union all
				select '579','Collection/Preservation/Specimen','-15','-21' from dual union all
				select '580','Botany/Plants-General Topics','-18','-26' from dual union all
				select '581','Plant Growth/Development','-18','-26' from dual union all
				select '582','Seed-bearing Plants','-18','-26' from dual union all
				select '583','Trees/Other Plants','-18','-26' from dual union all
				select '584','Trees/Other Plants','-18','-26' from dual union all
				select '585','Trees/Other Plants','-18','-26' from dual union all
				select '586','Trees/Other Plants','-18','-26' from dual union all
				select '587','Trees/Other Plants','-18','-26' from dual union all
				select '588','Trees/Other Plants','-18','-26' from dual union all
				select '589','Trees/Other Plants','-18','-26' from dual union all
				select '590','Zoology/Animals-General Topics','-18','-26' from dual union all
				select '591','Animal Behavior','-18','-26' from dual union all
				select '592','Invertebrates','-18','-26' from dual union all
				select '593','Protozoa ','-18','-26' from dual union all
				select '594','Mollusks','-18','-26' from dual union all
				select '595','Other Invertebrates','-18','-26' from dual union all
				select '595.7','Insects','-18','-26' from dual union all
				select '596','Vertebrates','-18','-26' from dual union all
				select '597','Fish/Amphibians/Reptiles','-18','-26' from dual union all
				select '598','Birds','-18','-26' from dual union all
				select '599','Mammals','-18','-26' from dual union all
				select '600','Technology-General Topics','-5','-11' from dual union all
				select '601','Technology-General Topics','-5','-11' from dual union all
				select '602','Technology-General Topics','-5','-11' from dual union all
				select '603','Technology-General Topics','-5','-11' from dual union all
				select '604','Technology-General Topics','-5','-11' from dual union all
				select '605','Technology-General Topics','-5','-11' from dual union all
				select '606','Technology-General Topics','-5','-11' from dual union all
				select '607','Technology-General Topics','-5','-11' from dual union all
				select '608','Inventions','-10','-16' from dual union all
				select '609','History of Tech./Inventors','-15','-21' from dual union all
				select '610','Medicine-General Topics','-5','-11' from dual union all
				select '611','Human Body/Systems','-5','-11' from dual union all
				select '612','Human Body/Systems','-5','-11' from dual union all
				select '612','Five Senses','-10','-16' from dual union all
				select '613','Health/Hygiene/Fitness','-10','-16' from dual union all
				select '614','Prevention of Disease','-5','-11' from dual union all
				select '615','Drugs','-5','-11' from dual union all
				select '616','Diseases','-5','-11' from dual union all
				select '617','Surgery/Dentistry','-5','-11' from dual union all
				select '618','Gynecology/Med. Specialties','-5','-11' from dual union all
				select '619','Experimental Medicine','-5','-11' from dual union all
				select '620','Engineering-General Topics','-5','-11' from dual union all
				select '621','Energy/Energy Sources','-5','-11' from dual union all
				select '621.8','Simple Machines','-15','-21' from dual union all
				select '622','Mining Engineering','-10','-16' from dual union all
				select '623','Military Engineering','-10','-16' from dual union all
				select '624','Civil Engineering','-10','-16' from dual union all
				select '625','Railroads/Roads/Transportation','-10','-16' from dual union all
				select '627','Hydraulic Engineering','-10','-16' from dual union all
				select '628','Sanitary/Municipal Engineering','-10','-16' from dual union all
				select '629','Transportation','-10','-16' from dual union all
				select '629.1','Air','-10','-16' from dual union all
				select '629.2','Land (Cars, Trucks, Cycles)','-10','-16' from dual union all
				select '629.4','Space Technology/Exploration','-10','-16' from dual union all
				select '629.8','Robotics','-10','-16' from dual union all
				select '630','Agriculture-General Topics','-15','-21' from dual union all
				select '631','Techniques/Equipment','-10','-16' from dual union all
				select '632','Plant Injuries/Diseases/Pests','-10','-16' from dual union all
				select '633','Field Crops','-15','-21' from dual union all
				select '634','Orchards/Forestry','-15','-21' from dual union all
				select '635','Garden Crops','-15','-21' from dual union all
				select '636','Pets','-15','-21' from dual union all
				select '637','Dairy','-15','-21' from dual union all
				select '638','Insect Culture','-15','-21' from dual union all
				select '639','Hunting/Fishing/Conservation','-15','-21' from dual union all
				select '640','Home Economics-Gen. Topics','-15','-21' from dual union all
				select '641','Food/Drink','-15','-21' from dual union all
				select '641.5','Cookbooks','-22','-30' from dual union all
				select '642','Food/Meal Service','-15','-21' from dual union all
				select '643','Housing/Equipment','-15','-21' from dual union all
				select '644','Household Utilities','-15','-21' from dual union all
				select '645','Household Furnishings','-15','-21' from dual union all
				select '646','Sewing','-15','-21' from dual union all
				select '646.7','Grooming','-10','-16' from dual union all
				select '647','Public Households','-15','-21' from dual union all
				select '648','Housekeeping','-15','-21' from dual union all
				select '649','Child Rearing','-15','-21' from dual union all
				select '650','Office/Mgt. Services','-5','-11' from dual union all
				select '651','Office Services','-5','-11' from dual union all
				select '652','Office Services','-5','-11' from dual union all
				select '653','Office Services','-5','-11' from dual union all
				select '654','Office Services','-5','-11' from dual union all
				select '655','Office Services','-5','-11' from dual union all
				select '656','Office Services','-5','-11' from dual union all
				select '657','Office Services','-5','-11' from dual union all
				select '658','Office Services','-5','-11' from dual union all
				select '659','Advertising/Public Relations','-5','-11' from dual union all
				select '660','Chemical Technology','-5','-11' from dual union all
				select '670','Manufacturing-Gen. topics','-5','-11' from dual union all
				select '680','Manufacturing for Specific Uses','-5','-11' from dual union all
				select '690','Buildings','-5','-11' from dual union all
				select '700','Fine Arts-General Topics','-18','-26' from dual union all
				select '701','Fine Arts-General Topics','-18','-26' from dual union all
				select '702','Fine Arts-General Topics','-18','-26' from dual union all
				select '703','Fine Arts-General Topics','-18','-26' from dual union all
				select '704','Fine Arts-General Topics','-18','-26' from dual union all
				select '705','Fine Arts-General Topics','-18','-26' from dual union all
				select '706','Fine Arts-General Topics','-18','-26' from dual union all
				select '707','Fine Arts-General Topics','-18','-26' from dual union all
				select '708','Fine Arts-General Topics','-18','-26' from dual union all
				select '709','Art History','-18','-30' from dual union all
				select '710','Civic and Landscape Art','-18','-26' from dual union all
				select '720','Architecture','-18','-26' from dual union all
				select '730','Sculpture/Plastic Arts','-18','-26' from dual union all
				select '740','Drawing','-18','-26' from dual union all
				select '741.59','Graphic Novels','-18','-26' from dual union all
				select '745','Crafts/Decorative Arts','-18','-26' from dual union all
				select '746','Textile Arts','-18','-26' from dual union all
				select '747','Interior Decorating','-18','-26' from dual union all
				select '748','Glass','-18','-26' from dual union all
				select '749','Furniture/Accessories','-18','-26' from dual union all
				select '750','Painting-General Topics','-18','-26' from dual union all
				select '751','Painting-General Topics','-18','-26' from dual union all
				select '752','Painting-General Topics','-18','-26' from dual union all
				select '753','Painting-General Topics','-18','-26' from dual union all
				select '754','Painting-General Topics','-18','-26' from dual union all
				select '755','Painting-General Topics','-18','-26' from dual union all
				select '756','Painting-General Topics','-18','-26' from dual union all
				select '757','Painting-General Topics','-18','-26' from dual union all
				select '758','Painting-General Topics','-18','-26' from dual union all
				select '759','History of Painting','-20','-30' from dual union all
				select '760','Graphic Arts/Printing','-15','-21' from dual union all
				select '770','Photography','-10','-16' from dual union all
				select '780','Music','-18','-26' from dual union all
				select '790','Recreational Arts','-18','-26' from dual union all
				select '791','Public Performances','-18','-26' from dual union all
				select '792','Theater','-18','-26' from dual union all
				select '793','Indoor Games','-18','-26' from dual union all
				select '794','Games of Skill','-18','-26' from dual union all
				select '795','Games of Chance','-18','-26' from dual union all
				select '796','Sports-General Topics','-10','-16' from dual union all
				select '796.1','Kiting','-10','-16' from dual union all
				select '796.2','Skating','-10','-16' from dual union all
				select '796.32','Basketball/Volleyball','-10','-16' from dual union all
				select '796.33','Football/Soccer','-10','-16' from dual union all
				select '796.34','Tennis/Golf','-10','-16' from dual union all
				select '796.35','Baseball/Softball','-10','-16' from dual union all
				select '796.4','Track/Field/Gymnastics','-10','-16' from dual union all
				select '796.5','Hiking/Camping','-10','-16' from dual union all
				select '796.6','Biking','-10','-16' from dual union all
				select '796.7','Racing','-10','-16' from dual union all
				select '796.8','Martial Arts','-10','-16' from dual union all
				select '796.9','Ice/Snow Sports','-10','-16' from dual union all
				select '797','Water/Air Sports','-10','-16' from dual union all
				select '798','Equestrian/Animal racing','-10','-16' from dual union all
				select '799','Fishing/Hunting','-10','-16' from dual union all
				select '800','Literature-General Topics','-25','-31' from dual union all
				select '801','Philosophy and Theory','-25','-31' from dual union all
				select '802','Miscellany','-25','-31' from dual union all
				select '803','Dictionaries and Encyclopedias','-25','-31' from dual union all
				select '806','Serial Publications','-25','-31' from dual union all
				select '807','Organizations','-25','-31' from dual union all
				select '808','Rhetoric/Collections','-25','-31' from dual union all
				select '809','Literature History/Criticism','-25','-31' from dual union all
				select '810','American Literature','-25','-31' from dual union all
				select '811','American Poetry','-25','-31' from dual union all
				select '812','American Drama','-25','-31' from dual union all
				select '813','American Fiction','-25','-31' from dual union all
				select '814','American Essays','-25','-31' from dual union all
				select '815','American Speeches','-25','-31' from dual union all
				select '816','American Letters','-25','-31' from dual union all
				select '817','American Satire/Humor','-25','-31' from dual union all
				select '818','American Miscellany','-25','-31' from dual union all
				select '820','English Literature','-25','-31' from dual union all
				select '821','English Poetry','-25','-31' from dual union all
				select '822','English Drama','-25','-31' from dual union all
				select '823','English Fiction','-25','-31' from dual union all
				select '824','English Essays','-25','-31' from dual union all
				select '825','English Speeches','-25','-31' from dual union all
				select '826','English Letters','-25','-31' from dual union all
				select '827','English Satire/Humor','-25','-31' from dual union all
				select '828','English Miscellany','-25','-31' from dual union all
				select '829','Old English/Anglo-Saxon','-25','-31' from dual union all
				select '830','German Literature','-25','-31' from dual union all
				select '840','French Literature','-25','-31' from dual union all
				select '850','Italian Literature','-25','-31' from dual union all
				select '860','Spanish Literature','-25','-31' from dual union all
				select '870','Latin Literature','-25','-31' from dual union all
				select '880','Greek Literature','-25','-31' from dual union all
				select '890','Other Languages Literature','-25','-31' from dual union all
				select '900','Gen. Geography/History*','-20','-27' from dual union all
				select '901','Gen. Geography/History*','-20','-27' from dual union all
				select '902','Gen. Geography/History*','-20','-27' from dual union all
				select '903','Gen. Geography/History*','-20','-27' from dual union all
				select '904','Events','-20','-27' from dual union all
				select '909','General World History','-20','-27' from dual union all
				select '910','Geography-General Topics','-5','-11' from dual union all
				select '910.4','Shipwrecks/Pirates','-20','-29' from dual union all
				select '910.9','Explorers','-20','-29' from dual union all
				select '911','Historical Maps/Atlases','-20','-29' from dual union all
				select '912','Maps/Atlases','-5','-11' from dual union all
				select '913','Ancient World Geography','-20','-29' from dual union all
				select '914','Europe-Geography','-10','-16' from dual union all
				select '915','Asia-Geography','-10','-16' from dual union all
				select '916','Africa-Geography','-10','-16' from dual union all
				select '917','North America-Geography','-10','-16' from dual union all
				select '917.1','Canada-Geography','-10','-16' from dual union all
				select '917.2','Mexico-Geography','-10','-16' from dual union all
				select '917.3','United States-Geography','-10','-16' from dual union all
				select '918','South America-Geography','-10','-16' from dual union all
				select '919','Other Areas-Geography','-10','-16' from dual union all
				select '920','Collective Biography','-13','-20' from dual union all
				select '921','Individual Biography','-13','-20' from dual union all
				select '929','Genealogy/Names/Insignia','-10','-22' from dual union all
				select '930','Ancient World-History*','-20','-29' from dual union all
				select '931','Ancient China','-20','-29' from dual union all
				select '932','Ancient Egypt','-20','-29' from dual union all
				select '933','Ancient Palestine','-20','-29' from dual union all
				select '934','Ancient India','-20','-29' from dual union all
				select '935','Ancient Mesopotamia/Iranian Plateau','-20','-29' from dual union all
				select '936','Ancient Europe','-20','-29' from dual union all
				select '937','Ancient Rome','-20','-29' from dual union all
				select '938','Ancient Greece','-20','-29' from dual union all
				select '939','Other Ancient Lands','-20','-29' from dual union all
				select '940','Europe-History*','-20','-29' from dual union all
				select '940.1','Middle Ages','-20','-29' from dual union all
				select '940.2','Renaissance/Reformation','-20','-29' from dual union all
				select '940.3','World War I','-20','-29' from dual union all
				select '904.4','World War I','-20','-29' from dual union all
				select '940.53','World War II','-20','-29' from dual union all
				select '940.54','World War II','-20','-29' from dual union all
				select '940.55','Modern Europe','-20','-29' from dual union all
				select '941','Great Britain/Scotland/Ireland','-10','-16' from dual union all
				select '942','England/Wales','-10','-16' from dual union all
				select '943','Germany/Central Europe','-10','-16' from dual union all
				select '944','France/Monaco','-10','-16' from dual union all
				select '944','Italy','-10','-16' from dual union all
				select '946','Spain/Portugal','-10','-16' from dual union all
				select '947','Eastern Europe/Sov. Union','-10','-16' from dual union all
				select '948','North Europe/Scandinavia','-10','-16' from dual union all
				select '949','Other Parts of Europe','-10','-16' from dual union all
				select '950','Asia/Far East-History*','-20','-29' from dual union all
				select '951','China','-10','-16' from dual union all
				select '552','Japan','-10','-16' from dual union all
				select '953','Arabian Peninsula','-10','-16' from dual union all
				select '954','South Asia/India','-10','-16' from dual union all
				select '955','Iran','-10','-16' from dual union all
				select '956','Middle East (Near East)','-10','-16' from dual union all
				select '957','Siberia (Asiatic Russia)','-10','-16' from dual union all
				select '958','Central Asia','-10','-16' from dual union all
				select '959','Southeast Asia','-10','-16' from dual union all
				select '960','Africa-History*','-20','-29' from dual union all
				select '961','Tunisia/Libya','-10','-16' from dual union all
				select '962','Egypt/Nile/Sudan','-10','-16' from dual union all
				select '963','Ethiopia','-10','-16' from dual union all
				select '964','Morocco/Canary Islands','-10','-16' from dual union all
				select '965','Algeria','-10','-16' from dual union all
				select '966','West Africa','-10','-16' from dual union all
				select '967','Central Africa','-10','-16' from dual union all
				select '968','South Africa','-10','-16' from dual union all
				select '969','South Indian Ocean','-10','-16' from dual union all
				select '970','North America-History*','-20','-29' from dual union all
				select '970.1','Native Americans','-15','-21' from dual union all
				select '971','Canada','-10','-16' from dual union all
				select '972','Mexico/Middle America','-10','-16' from dual union all
				select '973','United States','-10','-16' from dual union all
				select '973.1','Discovery','-20','-29' from dual union all
				select '973.2','Colonial Period','-20','-29' from dual union all
				select '973.3','Revolutionary War','-20','-29' from dual union all
				select '973.4','Constitutional Period','-20','-29' from dual union all
				select '973.5','Westward Expansion','-20','-29' from dual union all
				select '973.6','Westward Expansion','-20','-29' from dual union all
				select '973.7','Civil War','-20','-29' from dual union all
				select '973.8','Reconstruction','-20','-29' from dual union all
				select '973.9','20th Century/Contemporary US','-20','-29' from dual union all
				select '974','U.S., Northeastern states*','-10','-16' from dual union all
				select '975','U.S., Southeastern states*','-10','-16' from dual union all
				select '976','U.S., South central states*','-10','-16' from dual union all
				select '977','U.S., North central states*','-10','-16' from dual union all
				select '978','U.S., Western states*','-10','-16' from dual union all
				select '979','U.S., Great basin/Pacific slope States*','-10','-16' from dual union all
				select '980','South America-History*','-20','-29' from dual union all
				select '981','Brazil','-10','-16' from dual union all
				select '982','Argentina','-10','-16' from dual union all
				select '983','Chile','-10','-16' from dual union all
				select '984','Bolivia','-10','-16' from dual union all
				select '985','Peru','-10','-16' from dual union all
				select '986','Colombia/Ecuador','-10','-16' from dual union all
				select '987','Venezuela','-10','-16' from dual union all
				select '988','Guiana','-10','-16' from dual union all
				select '989','Paraguay/Uruguay','-10','-16' from dual union all
				select '990','Other Areas-History*','-20','-29' from dual union all
				select '993','New Zealand','-10','-16' from dual union all
				select '994','Australia','-10','-16' from dual union all
				select '995','Melanesia/New Guinea','-10','-16' from dual union all
				select '996','Polynesia','-10','-16' from dual union all
				select '996.9','Hawaii','-10','-16' from dual union all
				select '997','Atlantic Ocean Islands','-10','-16' from dual union all
				select '998','Arctic Islands/Antarctica','-10','-16' from dual union all
				select '999','Extraterrestrial worlds','-10','-16' from dual union all
				select '1001','audiobook','-15','-16' from dual union all -- loccode ~ .AUBK
				select '1014','comic/graphic','-23','-32' from dual union all -- loccode ~ .GRAPH
				select '1028','professional','-18','-24' from dual union all -- loccode ~ .PROF
				select '1029','reference','-13','-19' from dual union all -- loccode ~ .REF
				select '1105','adaptive books','1000','-1000' from dual union all -- loccode ~ .ADAPT
				select '1046','big book','-23','-32' from dual union all -- loccode ~ .BIGBK
				select '1005','biography','-13','-20' from dual union all -- loccode ~ .BIOG
				select '1045','board book','-23','-32' from dual union all -- loccode ~ .BOARD
				select '1093','everyone','-23','-32' from dual union all -- loccode ~ .EVERY
				select '1013','fiction','-23','-32' from dual union all -- loccode ~ .FIC
				select '1020','movie','-16','-17' from dual union all -- loccode ~ .MOVIE
				select '1059','other','1000','-1000' from dual union all -- loccode ~ .OTHER
				select '1027','periodical','1000','-1000' from dual union all -- loccode ~ .PER
				select '1076','technology/computers','1000','-1000' from dual union all -- loccode ~ .TECH
				select '1106','textured bags','1000','-1000' from dual -- loccode ~ .TEXT
			), 
			drange as ( -- "deranged" because James thinks he's funny
				select
					d.*
					, to_number(dewey_number) as drange_start
					, case
						when dewey_number = '000'
							then 100
						when dewey_number < 1000
							then (dewey_number/1000 + power(10,-length(to_char(dewey_number/1000))+1))*1000 
						when dewey_number > 1000
							then to_number(dewey_number)+1
						else -9 -- this value was picked out of a hat to be out of other ranges. THere should be ZERO calculations that end with this value
						end as drange_stop
				from d
			),
			id as (
				select 
					bd.*
					, case
						when drange.drange_start >= -1 and drange.drange_stop <= 1000
							then drange.dewey_number 
						when drange.drange_start > 1000
							then regexp_replace(bd.locname, '(adult |everyone |kids |teen )','')
						else 'OOPS SOMETHING WENT WRONG'
						end
						as calculated_dewey
					, length(drange.dewey_number) as length
					, drange.keep as keepyear
					, drange.discard as discardyear
				from bd
				left join drange 
					on 
						to_number(bd.dewey_number) >= drange.drange_start 
					and
						to_number(bd.dewey_number) < drange.drange_stop
				where bd.dewey_number >= -1
				order by bd.dewey_number asc, bd.item asc, drange.dewey_number desc , length desc -- should sort the "correct" (i.e., most granular) calculated_dewey available to the top of the list
			),
			dranked as ( -- "dranked" because James STILL thinks he's funny, should probably be "drowed" since it's not as deep as deep_rank, which could be a D&D joke, or maybe "drowNed" with n=1
				select
					id.*
					, row_number() over (
							partition by item
							order by calculated_dewey desc , length desc -- should sort the "correct" (i.e., most granular) calculated_dewey available to the top of the list
						) rn
				from id
			),
			x as (
				select 
					dranked.*
				from dranked
				where rn = 1 
				order by locname, item_callnumber, author, title, item
			)
			select
				x.collection
				, x.calculated_dewey
				, x.item_callnumber
				, x.item
				, x.status
				, x.bid
				, x.title
				, x.author
				, x.publishingdate
				, x.cumulativehistory
				, x.returndate
				, x.creationdate
				, case
					when validate_conversion(publishingdate as number) != 1 then 'FIX PUB DATE'
					when publishingdate is null then 'FIX PUB DATE'
					when to_number(publishingdate) > (to_number(to_char(sysdate, 'YYYY')) + to_number(x.keepyear)) then 'KEEP'
					when to_number(publishingdate) < (to_number(to_char(sysdate, 'YYYY')) + to_number(x.discardyear)) then 'DISCARD'
					else 'EVALUATE'
				end as action
				, case
					when x.cumulativehistory >= 30
						then 'EVALUATE FOR WEAR AND TEAR'
					else ''
				end as grubby
			from x
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
