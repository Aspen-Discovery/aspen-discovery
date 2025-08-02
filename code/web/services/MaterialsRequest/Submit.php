<?php

require_once ROOT_DIR . "/sys/MaterialsRequests/MaterialsRequest.php";
require_once ROOT_DIR . "/sys/MaterialsRequests/MaterialsRequestStatus.php";

/**
 * MaterialsRequest Submission processing, processes a new request for the user and
 * displays a success/fail message to the user.
 */
class MaterialsRequest_Submit extends Action {

	function launch() : void {
		global $interface;
		global $library;

		$maxActiveRequests = $library->maxActiveRequests;
		$maxRequestsPerYear = $library->maxRequestsPerYear;
		$accountPageLink = '/MaterialsRequest/MyRequests';
		$interface->assign('accountPageLink', $accountPageLink);
		$interface->assign('maxActiveRequests', $maxActiveRequests);
		$interface->assign('maxRequestsPerYear', $maxRequestsPerYear);

		//Make sure that the user is valid
		$processForm = true;
		$user = null;
		if (!UserAccount::isLoggedIn()) {
			try {
				$user = UserAccount::login();
			} catch (UnknownAuthenticationMethodException) {
				//This is handled later because $user is null
			}
		}else{
			$user = UserAccount::getLoggedInUser();
		}
		if ($user == null) {
			$interface->assign('success', false);
			$interface->assign('error', translate([
				'text' => 'Sorry, we could not log you in.  Please enter a valid barcode and pin number submit a materials request.',
				'isPublicFacing' => true,
			]));
			$processForm = false;
		}
		if ($processForm) {
			//Check to see if the user type is ok to submit a request
			$enableMaterialsRequest = $user->canSuggestMaterials();
			$interface->assign('enableMaterialsRequest', $enableMaterialsRequest);
			if (!$enableMaterialsRequest) {
				$interface->assign('success', false);
				$interface->assign('error', translate([
					'text' => 'Sorry, only residents may submit materials requests at this time.',
					'isPublicFacing' => true,
				]));
			} elseif ($_REQUEST['format'] == 'article' && $_REQUEST['acceptCopyright'] != 1) {
				$interface->assign('success', false);
				$interface->assign('error', translate([
					'text' => 'Sorry, you must accept the copyright agreement before submitting a materials request.',
					'isPublicFacing' => true,
				]));
			} else {
				//Check to see how many active materials request results the user has already.
				$materialsRequest = new MaterialsRequest();
				$materialsRequest->createdBy = UserAccount::getActiveUserId();
				$statusQuery = new MaterialsRequestStatus();
				$homeLibrary = Library::getPatronHomeLibrary();
				if (is_null($homeLibrary)) {
					$homeLibrary = $library;
				}
				$statusQuery->libraryId = $homeLibrary->libraryId;
				$statusQuery->isActive = 1;
				$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
				$openRequests = $materialsRequest->count();

				$materialsRequest->find();
				$interface->assign('openRequests', $openRequests);

				if ($enableMaterialsRequest === 1 && $materialsRequest->getNumResults() >= $maxActiveRequests) {
					$interface->assign('success', false);
					$interface->assign('error', translate([
							'text' => "You've already reached your maximum limit of %1% materials requests open at one time. Once we've processed your existing materials requests, you'll be able to submit again.",
							1 => $maxActiveRequests,
							'isPublicFacing' => true,
						]) . "<a href='$accountPageLink' class='btn btn-info'>" . translate([
							'text' => 'View Materials Requests',
							'isPublicFacing' => true,
						]) . "</a>.");
				} else {
					//Check the total number of requests created this year
					$materialsRequest = new MaterialsRequest();
					$materialsRequest->createdBy = UserAccount::getActiveUserId();
					if ($homeLibrary->yearlyRequestLimitType == 0) {
						$materialsRequest->whereAdd('dateCreated >= unix_timestamp(now() - interval 1 year)');
					}else{
						$calendarStartMonthDay = $homeLibrary->requestCalendarStartDate;
						//Figure out if we're after the calendar start date for the year
						$currentMonthDay = date('m-d');
						$requestStartYear = date('Y');
						if ($currentMonthDay <= $calendarStartMonthDay) {
							$requestStartYear = $requestStartYear - 1;
						}
						$requestStartDate = date_create_from_format('m-d-Y', "$calendarStartMonthDay-$requestStartYear");
						$requestStartTime = $requestStartDate->getTimestamp();
						$materialsRequest->whereAdd("dateCreated >= $requestStartTime");
					}
					//To be fair, don't include any requests that were canceled by the patron
					$statusQuery = new MaterialsRequestStatus();
					$statusQuery->whereAdd('isPatronCancel = 0 OR ISNULL(isPatronCancel)');
					$materialsRequest->joinAdd($statusQuery, 'INNER', 'status', 'status', 'id');
					$requestsThisYear = $materialsRequest->count();
					$interface->assign('requestsThisYear', $requestsThisYear);
					if ($enableMaterialsRequest === 1 && $requestsThisYear >= $maxRequestsPerYear) {
						$interface->assign('success', false);
						$interface->assign('error', translate([
								'text' => "You've already reached your maximum limit of %1% materials requests per year.",
								1 => $maxRequestsPerYear,
								'isPublicFacing' => true,
							]) . "<a href='$accountPageLink' class='btn btn-info'>" . translate([
								'text' => 'View Materials Requests',
								'isPublicFacing' => true,
							]) . "</a>.");
					} else {
						$user = UserAccount::getLoggedInUser();
						$samePatron = true;
						if ($_REQUEST['patronIdCheck'] != $user->id){
							$samePatron = false;
						}
						if ($samePatron){
							//Materials request can be submitted.
							$materialsRequest = new MaterialsRequest();
							$materialsRequest->format = empty($_REQUEST['format']) ? '' : strip_tags($_REQUEST['format']);
							if (empty($materialsRequest->format)) {
								$interface->assign('success', false);
								$interface->assign('error', 'No format was specified.');
							} else {
								$materialsRequest->phone = isset($_REQUEST['phone']) ? substr(strip_tags($_REQUEST['phone']), 0, 15) : '';
								$materialsRequest->email = isset($_REQUEST['email']) ? strip_tags($_REQUEST['email']) : '';
								$materialsRequest->title = isset($_REQUEST['title']) ? strip_tags($_REQUEST['title']) : '';
								$materialsRequest->season = isset($_REQUEST['season']) ? strip_tags($_REQUEST['season']) : '';
								$materialsRequest->magazineTitle = isset($_REQUEST['magazineTitle']) ? strip_tags($_REQUEST['magazineTitle']) : '';
								$materialsRequest->magazineDate = isset($_REQUEST['magazineDate']) ? strip_tags($_REQUEST['magazineDate']) : '';
								$materialsRequest->magazineVolume = isset($_REQUEST['magazineVolume']) ? strip_tags($_REQUEST['magazineVolume']) : '';
								$materialsRequest->magazineNumber = isset($_REQUEST['magazineNumber']) ? strip_tags($_REQUEST['magazineNumber']) : '';
								$materialsRequest->magazinePageNumbers = isset($_REQUEST['magazinePageNumbers']) ? strip_tags($_REQUEST['magazinePageNumbers']) : '';
								$materialsRequest->author = empty($_REQUEST['author']) ? '' : strip_tags($_REQUEST['author']);
								$materialsRequest->ageLevel = isset($_REQUEST['ageLevel']) ? strip_tags($_REQUEST['ageLevel']) : '';
								$materialsRequest->bookType = isset($_REQUEST['bookType']) ? strip_tags($_REQUEST['bookType']) : '';
								$materialsRequest->isbn = isset($_REQUEST['isbn']) ? substr(strip_tags($_REQUEST['isbn']), 0, 15) : '';
								$materialsRequest->upc = isset($_REQUEST['upc']) ? strip_tags($_REQUEST['upc']) : '';
								$materialsRequest->issn = isset($_REQUEST['issn']) ? strip_tags($_REQUEST['issn']) : '';
								$materialsRequest->oclcNumber = isset($_REQUEST['oclcNumber']) ? strip_tags($_REQUEST['oclcNumber']) : '';
								$materialsRequest->publisher = empty($_REQUEST['publisher']) ? '' : strip_tags($_REQUEST['publisher']);
								$materialsRequest->publicationYear = empty($_REQUEST['publicationYear']) ? '' : substr(strip_tags($_REQUEST['publicationYear']), 0, 4);
								$materialsRequest->about = empty($_REQUEST['about']) ? '' : strip_tags($_REQUEST['about']);
								$materialsRequest->comments = empty($_REQUEST['comments']) ? '' : strip_tags($_REQUEST['comments']);
								$materialsRequest->placeHoldWhenAvailable = empty($_REQUEST['placeHoldWhenAvailable']) ? 0 : $_REQUEST['placeHoldWhenAvailable'];
								$materialsRequest->holdPickupLocation = empty($_REQUEST['holdPickupLocation']) ? '' : $_REQUEST['holdPickupLocation'];
								$materialsRequest->bookmobileStop = empty($_REQUEST['bookmobileStop']) ? '' : $_REQUEST['bookmobileStop'];
								$materialsRequest->illItem = empty($_REQUEST['illItem']) ? 0 : $_REQUEST['illItem'];
								$materialsRequest->source = (empty($_REQUEST['source']) || !is_numeric($_REQUEST['source'])) ? 1 : $_REQUEST['source'];

								$materialsRequest->libraryId = $homeLibrary->libraryId;

								$formatObject = $materialsRequest->getFormatObjectByFormat();
								if (!empty($formatObject->id)) {
									$materialsRequest->formatId = $formatObject->id;
								}

								if (isset($_REQUEST['ebookFormat']) && $formatObject->hasSpecialFieldOption('Ebook format')) {
									$materialsRequest->subFormat = strip_tags($_REQUEST['ebookFormat']);

								} elseif (isset($_REQUEST['eaudioFormat']) && $formatObject->hasSpecialFieldOption('Eaudio format')) {
									$materialsRequest->subFormat = strip_tags($_REQUEST['eaudioFormat']);
								}

								if (isset($_REQUEST['abridged'])) {
									if ($_REQUEST['abridged'] == 'abridged') {
										$materialsRequest->abridged = 1;
									} elseif ($_REQUEST['abridged'] == 'unabridged') {
										$materialsRequest->abridged = 0;
									} else {
										$materialsRequest->abridged = 2; //Not applicable
									}
								}

								$defaultStatus = new MaterialsRequestStatus();
								$defaultStatus->isDefault = 1;
								$defaultStatus->libraryId = $homeLibrary->libraryId;
								if (!$defaultStatus->find(true)) {
									$interface->assign('success', false);
									$interface->assign('error', translate([
										'text' => 'There was an error submitting your materials request, could not determine the default status.',
										'isPublicFacing' => true,
									]));
								} else {
									header('Location: /MaterialsRequest/MyRequests');
									$materialsRequest->status = $defaultStatus->id;
									$materialsRequest->dateCreated = time();
									$materialsRequest->createdBy = UserAccount::getActiveUserId();
									$materialsRequest->dateUpdated = time();

									if ($materialsRequest->insert()) {
										$user->updateMessage = translate([
											'text' => 'Your request for %1% by %2% was submitted successfully.',
											'isPublicFacing' => true,
											1 => $materialsRequest->title,
											2 => $materialsRequest->author
										]);
										$user->updateMessageIsError = false;
										$user->update();
									} else {
										$user->updateMessage = translate([
											'text' => 'There was an error submitting your materials request.',
											'isPublicFacing' => true
										]);
										$user->updateMessageIsError = true;
										$user->update();
									}
								}
							}
						} else {
							$interface->assign('success', false);
							$interface->assign('error', translate([
								'text' => 'Wrong account credentials, please try again.',
								'isPublicFacing' => true,
							]));
						}
					}
				}
			}
		}

		$sidebar = '';
		if (UserAccount::isLoggedIn()) {
			$sidebar = 'Search/home-sidebar.tpl';
		}

		$this->display('submission-result.tpl', 'Submission Result', $sidebar);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/MyAccount/Home', 'Your Account');
		$breadcrumbs[] = new Breadcrumb('/MaterialsRequest/MyRequests', 'My Materials Requests');
		return $breadcrumbs;
	}
}