<?php
require_once ROOT_DIR . '/sys/VDX/VdxSetting.php';
class VdxDriver
{
	private $settings;
	/** @var CurlWrapper */
	private $curlWrapper;

	public function __construct(){
		$vdxSettings = new VdxSetting();
		if ($vdxSettings->find(true)){
			$this->settings = $vdxSettings;
			$this->curlWrapper = new CurlWrapper();
		}else{
			$this->settings = false;
		}
	}

	public function getRequests(User $patron) : array
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$openRequests = array();
		$closedRequests = array();
		if ($this->settings != false){
			//Fetch requests for the user
			if ($this->loginToVdx($patron)){
				//Get the "My Requests" page
				$myRequestsUrl = "{$this->settings->baseUrl}/zportal/zengine?VDXaction=IllSearchAdvanced";
				$myRequestsResponse = $this->curlWrapper->curlGetPage($myRequestsUrl);
				if ($this->curlWrapper->getResponseCode() == 200){
					//Get the number of requests
					$matches = [];
					if (preg_match('%<td class="reqnavlinks-wrap hits">.*?<p><span class="availbodybold">(\d*)&nbsp;</span>requests? found&nbsp;.*?</p>.*?</td>%s', $myRequestsResponse, $matches)){
						$numRequests = $matches[1];
					}else{
						$numRequests = 0;
					}

					//Get all the requests
					if (preg_match('%<table cellspacing="0" class="results" border="0">(.*?)</table>%s', $myRequestsResponse, $matches)){
						$resultsTable = $matches[1];
						if (preg_match_all('%<tr>.*?</tr>%s', $resultsTable, $tableRows, PREG_SET_ORDER)){
							$curRequest = null;
							foreach ($tableRows as $tableRow){
								if (preg_match_all('%<td.*?>(.*?)</td>%s', $tableRow[0], $tableCells, PREG_SET_ORDER)) {
									$label = trim(strip_tags($tableCells[0][1]));
									$label = str_replace(':', '', $label);
									$originalLabel = $tableCells[0][1];
									if (array_key_exists(1, $tableCells)){
										$value = $tableCells[1][0];
										$trimmedValue = trim(strip_tags(trim($tableCells[1][0])));
									}else{
										$value = '';
										$trimmedValue = '';
									}

									if ($label == 'ILL Number') {
										if ($curRequest != null) {
											if ((strpos($curRequest->status, 'Completed') === false) && $curRequest->status != 'Cancelled') {
												$openRequests[] = $curRequest;
											}else{
												$closedRequests[] = $curRequest;
											}
										}
										$curRequest = new Hold();
										$curRequest->userId = $patron->id;
										$curRequest->type = 'interlibrary_loan';
										$curRequest->source = 'vdx';
										$curRequest->sourceId = $trimmedValue;
										$curRequest->recordId = '';
									} elseif ($label == 'Author') {
										$curRequest->author = $trimmedValue;
									} elseif ($label == 'Title') {
										$curRequest->title = $trimmedValue;
									} elseif ($label == 'Status') {
										$curRequest->status = $trimmedValue;
									} elseif ($label == 'Circulation Status') {
										//$curRequest['circulationStatus'] = $value;
									} elseif ($label == 'Needed by') {
										$curRequest->expirationDate = strtotime($trimmedValue);
									} elseif ($label == 'Pickup Location') {
										$curRequest->pickupLocationName = $trimmedValue;
									} elseif ($label == '') {
										//$curRequest['circulationStatus'] .= $value;
									} elseif ($label == 'Cancel') {
										$curRequest->cancelable = true;
										//<a href="zengine?VDXaction=IllTerminateRequest&amp;command=117&amp;hit=0" onclick="disableRefresh(); return true; return false;" style="background-image: url(&quot;images/sr_cancelrequest.gif&quot;);" title="Cancel" class="icon" onmousemove="window.status='Cancel';" onmouseover="this.style.backgroundImage='url(images/sr_cancelrequest_on.gif)'" onmouseout="this.style.backgroundImage='url(images/sr_cancelrequest.gif)'"><span>Cancel</span></a>
										if (preg_match('/zengine\?VDXaction=IllTerminateRequest&amp;command=117&amp;hit=(\d+)/s', $originalLabel, $hitMatches)){
											$curRequest->cancelId  = $hitMatches[1];
										}
									} elseif ($label == 'Date Completed') {
										//Ignore this one
									} else {
										//Unknown label
										echo("Unknown label $label" );
									}
								}
							}
							if ($curRequest != null){
								if ((strpos($curRequest->status, 'Completed') === false) && $curRequest->status != 'Cancelled') {
									$openRequests[] = $curRequest;
								}else{
									$closedRequests[] = $curRequest;
								}
							}
						}
					}
				}
			}
		}

		//TODO: Load the VDX requests we have in the database and match them up.
		require_once ROOT_DIR . '/sys/VDX/VdxRequest.php';
		$vdxRequest = new VdxRequest();
		$vdxRequest->userId = $patron->id;
		$vdxRequest->find();
		while ($vdxRequest->fetch()){
			if (empty($vdxRequest->vdxId)) {
				//Try to sync with a hold we have read from VDX.
				foreach ($openRequests as $request){
					if ($request->title == $vdxRequest->title && $request->author == $vdxRequest->author){
						$vdxRequest->vdxId = $request->sourceId;
						$vdxRequest->status = $request->status;
						$request->recordId = $vdxRequest->catalogKey;
						$vdxRequest->update();
						break;
					}
				}
				if (!empty($vdxRequest->vdxId)) {
					continue;
				}
				//If we didn't find it in the open requests, check the closed requests we do this as 2 stages in case someone requests the same title multiple times.
				foreach ($closedRequests as $request) {
					if ($request->title == $vdxRequest->title && $request->author == $vdxRequest->author) {
						$vdxRequest->vdxId = $request->sourceId;
						$vdxRequest->status = $request->status;
						$request->recordId = $vdxRequest->catalogKey;
						$vdxRequest->update();
						break;
					}
				}
				if (!empty($vdxRequest->vdxId)) {
					continue;
				}
				//If we still don't have a VDX ID, it might have either not arrived within VDX yet, or it might be closed.
				//We'll give it 24 hours to show up and if it hasn't, we can assume that it's been closed.
				if (time() - $vdxRequest->datePlaced > (24 * 60 * 60)){
					$vdxRequest->status = 'Not found in VDX';
					break;
				}else{
					//Create a temporary open request for it
					$curRequest = new Hold();
					$curRequest->userId = $patron->id;
					$curRequest->type = 'interlibrary_loan';
					$curRequest->source = 'vdx';
					$curRequest->sourceId = $vdxRequest->catalogKey;
					$curRequest->recordId = $vdxRequest->catalogKey;
					$curRequest->title = $vdxRequest->title;
					$curRequest->author = $vdxRequest->author;
					$curRequest->status = 'Pending';
					$curRequest->pickupLocationName = $vdxRequest->pickupLocation;
					$curRequest->cancelable = false;
					$openRequests[] = $curRequest;
				}
			}
		}

		return [
			'unavailable' => $openRequests
		];
	}

	public function getAccountSummary(User $user) : AccountSummary {
		list($existingId, $summary) = $user->getCachedAccountSummary('vdx');

		if ($summary === null || isset($_REQUEST['reload'])) {
			//Get account information from api
			require_once ROOT_DIR . '/sys/User/AccountSummary.php';
			$summary = new AccountSummary();
			$summary->userId = $user->id;
			$summary->source = 'vdx';
			$summary->resetCounters();

			$requests = $this->getRequests($user);
			$summary->numUnavailableHolds = count($requests['unavailable']);
		}

		return $summary;
	}

	private function loginToVdx(User $user)
	{
		$loginUrl = "{$this->settings->baseUrl}/zportal/zengine";

		$loginPageResponse = $this->curlWrapper->curlGetPage($loginUrl);
		if ($this->curlWrapper->getResponseCode() == 200){
			if (preg_match('/INPUT type="hidden" name="login_service_id" value="(.*?)"/', $loginPageResponse, $matches)){
				$loginServiceId = $matches[1];
			}else{
				return false;
			}
		}else{
			return false;
		}

		$postParams = array(
			'login_user' => $user->cat_username,
			'login_password' => $user->cat_password,
			'login_service_id' => $loginServiceId,
			'.x' => 'Login',
			'VDXaction' => 'Login'
		);

		$loginResponse = $this->curlWrapper->curlPostPage($loginUrl, $postParams);
		if ($this->curlWrapper->getResponseCode() == 200){
			if (strpos($loginResponse, 'Sign Out') !== false){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	public function submitRequest(VdxSetting $settings, User $user, array $requestFields) : array{
		require_once ROOT_DIR . '/sys/VDX/VdxRequest.php';
		$newRequest = new VdxRequest();
		$newRequest->userId = $user->id;
		$newRequest->datePlaced = time();
		$newRequest->title = strip_tags($requestFields['title']);
		$newRequest->author = strip_tags($requestFields['author']);
		$newRequest->publisher = strip_tags($requestFields['publisher']);
		$newRequest->isbn = strip_tags($requestFields['isbn']);
		$newRequest->feeAccepted = $requestFields['acceptFee'] == 'true' ? 1 : 0;
		$newRequest->maximumFeeAmount = strip_tags($requestFields['maximumFeeAmount']);
		$newRequest->catalogKey = strip_tags($requestFields['catalogKey']);
		$newRequest->note = strip_tags($requestFields['note']);
		$newRequest->pickupLocation = strip_tags($requestFields['pickupLocation']);
		$newRequest->status = 'New';
		if (!$newRequest->insert()){
			global $logger;
			$logger->log("Could not insert new request " . $newRequest->getLastError(), Logger::LOG_ERROR);
		}

		//To submit, email the submission email address
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mailer = new Mailer();

		//Load client location and external location from location
		$userHomeLocation = $user->getHomeLocation();
		$vdxLocation = empty($userHomeLocation->vdxLocation) ? $userHomeLocation->code : $userHomeLocation->vdxLocation;

		$body = "USERID=$user->cat_username\r\n";
		$body .= "ClientCategory=$user->patronType\r\n";
		$body .= "PatronKey=$settings->patronKey\r\n";
		$body .= "ClientLocation=$vdxLocation\r\n";
		$body .= "ExternalLocation=$vdxLocation\r\n";
		$body .= "ClientFirstName=$user->firstname\r\n";
		$body .= "ClientLastName=$user->lastname\r\n";
		$body .= "ClientAddr4Street=\r\n";
		$body .= "ClientAddr4City=\r\n";
		$body .= "ClientAddr4Region=\r\n";
		$body .= "ClientAddr4Code=\r\n";
		$body .= "ClientAddr4Phone=\r\n";
		$body .= "ClientEmailAddress=$user->email\r\n";
		$body .= "service_type_1=\r\n";
		$body .= "ReqTitle=" . $newRequest->title . "\r\n";
		$body .= "ReqAuthor=" . $newRequest->author . "\r\n";
		$body .= "ReqPublisher=" . $newRequest->publisher . "\r\n";
		$body .= "ReqPubDate=\r\n";
		$body .= "ReqAdditional=Patron response to will pay: " . ($newRequest->feeAccepted ? 'Yes' : 'No') . "\r\n";
		$body .= "ReqMaxCostCurr=USD " . $newRequest->maximumFeeAmount . "\r\n";
		$body .= "ReqISBN=" . $newRequest->isbn . "\r\n";
		$body .= "ControlNumbers._new=1\r\n";
		$body .= "ControlNumbers.icn_rota_pos=-1\r\n";
		$body .= "ControlNumbers.icn_loc_well_known=4\r\n";
		$body .= "ControlNumbers.icn_control_number=" . $newRequest->catalogKey . "\r\n";
		$body .= "ReqClassmark=\r\n";
		$body .= "ReqPubPlace=\r\n";
		$body .= "PickupLocation=" . $newRequest->pickupLocation . "\r\n";
		$body .= "ReqVerifySource=$settings->reqVerifySource\r\n";


		if (!empty($newRequest->note)) {
			$body .= "NOTE=" . $newRequest->note . "\r\n";
			$body .= "AuthorisationStatus=MAUTH\r\n";
		}else{
			$body .= "AuthorisationStatus=TAUTH\r\n";
		}

		if ($mailer->send($settings->submissionEmailAddress, 'Document_Request', $body, null, null)){
			$results = array(
				'title' => translate(['text' => 'Request Sent', 'isPublicFacing' => true]),
				'message' => translate(['text' => "Your request has been submitted. You can check the status of your request within your account.", 'isPublicFacing' => true]),
				'success' => true
			);
		}else{
			$results = array(
				'title' => translate(['text' => 'Request Failed', 'isPublicFacing' => true]),
				'message' => translate(['text' => "Could not send email to VDX system.", 'isPublicFacing' => true]),
				'success' => false
			);
		}
		return $results;
	}

	public function cancelRequest(User $patron, string $requestId, $cancelId){
		$result = [
			'success' => 'false',
			'message' => translate(['text'=>'Unknown error cancelling request', 'isPublicFacing' => true])
		];

		$loginUrl = "{$this->settings->baseUrl}/zportal/zengine";
		$loginPageResponse = $this->curlWrapper->curlGetPage($loginUrl);
		if ($this->loginToVdx($patron)){
			$myRequestsUrl = "{$this->settings->baseUrl}/zportal/zengine?VDXaction=IllSearchAdvanced";
			$myRequestsResponse = $this->curlWrapper->curlGetPage($myRequestsUrl);

			$getCancelFormUrl = "{$this->settings->baseUrl}/zportal/zengine?VDXaction=IllTerminateRequest&command=117&hit=$cancelId";
			$getCancelFormResponse = $this->curlWrapper->curlGetPage($getCancelFormUrl);

			//Submit a post
			$postParams = [
				'command'=>'117',
				'page'=>'main',
				'form_submitted'=>'false',
				'illno'=> $requestId,
				'request_terminated'=>'Y',
				'termination_date'=>'_sysdate_',
				'FormName'=>'request_terminate_form',
				'auth_status'=>'AUTH',
				'IllSubmit.x'=>'Cancel Request',
				'VDXaction'=>'IllSubmit'
			];
			$cancelUrl = "{$this->settings->baseUrl}/zportal/zengine";

			$headers  = array(
				'Content-Type: application/x-www-form-urlencoded',
			);
			$this->curlWrapper->addCustomHeaders($headers, false);
			$cancelResponse = $this->curlWrapper->curlPostPage($cancelUrl, $postParams);
			if ($this->curlWrapper->getResponseCode() == '200' || $this->curlWrapper->getResponseCode() == '302'){
				if (preg_match("/<p>.*?Request # <span class=\"resultsbright\">&nbsp;.*?$requestId</span>&nbsp; has been cancelled.*?</p>/", $cancelResponse)){
					$result = [
						'success' => 'true',
						'message' => translate(['text'=>'Your request was cancelled successfully', 'isPublicFacing' => true])
					];
				}else{
					$result['message'] = translate(['text'=>'Failed to cancel the request, please try again in a few minutes. If this problem persists, please contact the library.', 'isPublicFacing' => true]);
				}
			}
		}else{
			$result['message'] = translate(['text'=>'Could not login to the interlibrary loan system', 'isPublicFacing' => true]);
			return $result;
		}

		return $result;
	}
}