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
		$patronId = $user->ils_barcode;
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
				where b.branchcode = '$location'
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
			with holds_vs_items as (
				select
					t.bid
					, t.occur
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
				left join branch_v2 ob on t.holdingbranch = ob.branchnumber -- Origin Branch
				left join item_v2 i on ( t.bid = i.bid and t.holdingbranch = i.branch)
				left join location_v2 l on i.location = l.locnumber
				where ob.branchcode = '$location'
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
			)
			select
				PATRON_NAME
				, HOME_ROOM
				, GRD_LVL
				, P_BARCODE
				, SHELF_LOCATION
				, TITLE
				, CALL_NUMBER
				, ITEM_ID
			from fillable
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
        $sql = <<<EOT
			select
				patronbranch.branchcode
				, patronbranch.branchname
				, bty_v2.btynumber AS bty
				, bty_v2.btyname as grade
				, case 
						when bty = 13 
						then patron_v2.name
						else patron_v2.sponsor
					end as homeroom
				, case 
						when bty = 13 
						then patron_v2.patronid
						else patron_v2.street2
					end as homeroomid
				, patron_v2.name AS patronname
				, patron_v2.patronid
				, patron_v2.lastname
				, patron_v2.firstname
				, patron_v2.middlename
				, patron_v2.suffixname
			from
				branch_v2 patronbranch
				, bty_v2
				, patron_v2
			where
				patron_v2.bty = bty_v2.btynumber
				and patronbranch.branchnumber = patron_v2.defaultbranch
				and (
					(
						patron_v2.bty in ('21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','46','47')
						and patronbranch.branchcode = '$location'
						and patron_v2.street2 = '$homeroom'
					) or (
						patron_v2.bty = 13
						and patron_v2.patronid = '$homeroom'
					)
				)
			order by
				patronbranch.branchcode
				, case
					when patron_v2.bty = 13 then 0
					else 1
				end
				, patron_v2.sponsor
				, patron_v2.name
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

    public function getStudentBarcodeDataHomerooms($location) : array {
        $this->initDatabaseConnection();
        // query school branch codes and homerooms
        /** @noinspection SqlResolve */
        /** @noinspection SqlConstantExpression */
        $sql = <<<EOT
			select 
			  branchcode
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
                  when s.bty > 36
                    then null
                    else s.bty-22 
                end as grade
			  from
				branch_v2 b
				  left join patron_v2 s on b.branchnumber = s.defaultbranch
				  left join patron_v2 h on s.street2 = h.patronid
			  where
				b.branchcode = '$location'
				and s.street2 is not null
				and s.bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42','46','47')
			  order by
				b.branchcode
				, homeroomname
			) a
			group by branchcode, homeroomid
			order by branchcode, homeroomname
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
                      , itembranch.branchgroup AS SYSTEM
                      , item_v2.cn AS Call_Number
                      , bbibmap_v2.title AS Title
                      , to_char(transitem_v2.dueornotneededafterdate,'MM/DD/YYYY') AS Due_Date
                      , item_v2.price AS Owed
                      , to_char(transitem_v2.dueornotneededafterdate,'MM/DD/YYYY') AS Due_Date_Dup
                      , item_v2.item AS Item
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
                      and bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42','46','47')
                      and patronbranch.branchcode = '$location'
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
                    where p.bty in ('13','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','40','42','46','47')
                    and b.branchcode = '$location'
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
                    , to_char(r.dueornotneededafterdate,'MM/DD/YYYY') as due
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
                    , itembranch.branchgroup AS SYSTEM
                    , r.callnumber AS Call_Number
                    , r.title AS Title
                    , r.due AS Due_Date
                    , r.owed as Owed
                    , r.due AS Due_Date_Dup
                    , r.item AS Item
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
            $data[] = $row;
        }
        oci_free_statement($stid);
        return $data;
    }
}
