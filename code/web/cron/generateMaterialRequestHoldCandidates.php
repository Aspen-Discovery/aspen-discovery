<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestFormat.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestFormatMapping.php';
require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestHoldCandidate.php';
require_once ROOT_DIR . '/sys/SolrConnector/GroupedWorksSolrConnector.php';

$searchObject = SearchObjectFactory::initSearchObject();
$searchObject->init();
$searchObject->clearFacets();

$staffUsersWithNewHoldCandidates = [];

//Load request format mappings
$requestFormatMapping = new MaterialsRequestFormatMapping();
$requestFormatMapping->find();
$requestFormatMappings = [];
while ($requestFormatMapping->fetch()) {
	if (!array_key_exists($requestFormatMapping->materialsRequestFormatId, $requestFormatMappings)){
		$requestFormatMappings[$requestFormatMapping->materialsRequestFormatId] = [];
	}
	$requestFormatMappings[$requestFormatMapping->materialsRequestFormatId][] = $requestFormatMapping->catalogFormat;
}


//Loop through all eligible request statuses
$requestStatusToCheckForHolds  = new MaterialsRequestStatus();
$requestStatusToCheckForHolds->checkForHolds = 1;
$requestStatusToCheckForHolds->find();
while ($requestStatusToCheckForHolds->fetch()) {
	//check all requests in that status
	$requestToCheck = new MaterialsRequest();
	$requestToCheck->status = $requestStatusToCheckForHolds->id;
	//Check the request for new candidates even if some have already been found
	//In case we are waiting for another edition
	$requestToCheck->find();
	while ($requestToCheck->fetch()) {
		//Check to see if we have an ISBN, UPC, or ISSN
		$controlNumber = $requestToCheck->isbn;
		if (empty($controlNumber)) {
			$controlNumber = $requestToCheck->upc;
		}
		if (empty($controlNumber)) {
			$controlNumber = $requestToCheck->issn;
		}
		//Set scoping for the search based on the request library
		$searchObject->disableScoping();
		$searchObject->disableSpelling();

		if (!empty($controlNumber)) {
			//Do a search of the catalog by control number,
			$searchObject->setSearchTerms([
				'index' => 'ISN',
				'lookfor' => $controlNumber,
			]);
		}else{
			//Do a title author search, this may want to be an advanced search
			$trimmedTitle = trim($requestToCheck->title);

			$searchObject->setSearchTerms([
				'index' => 'Keyword',
				'lookfor' => "(title_exact:\"$trimmedTitle\" AND (author:$requestToCheck->author OR author_exact:$requestToCheck->author))",
			]);
		}

		$results = $searchObject->processSearch();
		if ($results instanceof AspenError) {
			//For now just go to the next one
			continue;
		}

		// 'Finish' the search... complete timers and log search history.
		$searchObject->close();
		if ($searchObject->getResultTotal() > 0) {
			$existingHoldCandidates = [];
			$existingHoldCandidate = new MaterialsRequestHoldCandidate();
			$existingHoldCandidate->requestId = $requestToCheck->id;
			$existingHoldCandidate->find();
			while ($existingHoldCandidate->fetch()) {
				$existingHoldCandidates[] = $existingHoldCandidate->source . ':' . $existingHoldCandidate->sourceId;
			}

			$formatMappingsForRequest = $requestFormatMappings[$requestToCheck->formatId];
			$recordSet = $searchObject->getResultRecordSet();
			foreach ($recordSet as $recordKey => $record) {
				//If we are doing a title/author search, make sure the title and author are correct?

				//If we find anything, verify that the formats of the items are correct
				$isFormatValid = false;
				foreach ($record['format'] as $activeFormat) {
					if (in_array($activeFormat, $formatMappingsForRequest)) {
						$isFormatValid = true;
						break;
					}
				}
				if ($isFormatValid) {
					//Loop through the actual records to figure out which specific record or records are correct
					$recordDriver = $searchObject->getRecordDriverForResult($record);
					foreach ($recordDriver->getRelatedManifestations() as $manifestation) {
						if (in_array($manifestation->format, $formatMappingsForRequest)) {
							foreach ($manifestation->getRelatedRecords() as $record) {
								//Add request candidates to the materials request (if they don't exist already)
								$shortId = substr($record->id, strlen($record->source) + 1);
								if (!in_array($record->source . ':' . $shortId, $existingHoldCandidates)){
									$materialsRequestHoldCandidate = new MaterialsRequestHoldCandidate();
									$materialsRequestHoldCandidate->requestId = $requestToCheck->id;
									$materialsRequestHoldCandidate->source = $record->source;
									//The record includes the source, strip that off before saving
									$materialsRequestHoldCandidate->sourceId = $shortId;
									$materialsRequestHoldCandidate->insert();

									//Add the staff member to the list of staff members with new hold candidates
									$staffUsersWithNewHoldCandidates[$requestToCheck->assignedTo] = $requestToCheck->assignedTo;
								}
								if ($requestToCheck->readyForHolds == 0) {
									$requestToCheck->readyForHolds = 1;
									$requestToCheck->update();
								}
							}
						}
					}
				}
			}
		}

		//Check to see if there is only one hold candidate and if so, automatically select it
		$existingHoldCandidate = new MaterialsRequestHoldCandidate();
		$existingHoldCandidate->requestId = $requestToCheck->id;
		if ($requestToCheck->selectedHoldCandidateId == 0) {
			$existingHoldCandidate = new MaterialsRequestHoldCandidate();
			$existingHoldCandidate->requestId = $requestToCheck->id;
			$existingHoldCandidate->find();
			if ($existingHoldCandidate->getNumResults() === 1){
				$existingHoldCandidate->fetch();
				$requestToCheck->selectedHoldCandidateId = $existingHoldCandidate->id;
				$requestToCheck->update();
			}
		}
	}
}

//email staff that have new hold candidates
foreach ($staffUsersWithNewHoldCandidates as $staffUsersWithNewHoldCandidate) {
	$staffUser = new User();
	$staffUser->id = $staffUsersWithNewHoldCandidate;
	if ($staffUser->find(true)) {
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mail = new Mailer();
		$replyToAddress = "";
		$body = "*****" . translate([
				'text' => 'This is an auto-generated email response. Please do not reply.',
				'isPublicFacing' => true,
			]) . "*****";
		$body .= "\r\n\r\n" . translate([
				'text' => 'One or more of your material requests have new hold candidates. Please login to Aspen to verify these requests and place holds as appropriate.',
				'isPublicFacing' => true,
			]);

		if (!empty($staffUser->email)) {
			$email = $mail->send($staffUser->email, translate([
				'text' => "Holds Ready to be Placed for Materials Requests",
				'isPublicFacing' => true,
			]), $body, $replyToAddress);
		}
	}
}
