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

	public function getRequests(User $patron)
	{
		require_once ROOT_DIR . '/sys/User/Hold.php';
		$unavailableHolds = array();
		if ($this->settings != false){
			//Fetch requests for the user
			if ($this->loginToVdx($patron)){
				//Get the "My Requests" page
				$myRequestsUrl = "{$this->settings->baseUrl}/zportal/zengine?VDXaction=IllSearchAdvanced";
				$myRequestsResponse = $this->curlWrapper->curlGetPage($myRequestsUrl);
				if ($this->curlWrapper->getResponseCode() == 200){
					//Get the number of requests
					$matches = [];
					if (preg_match('%<td class="reqnavlinks-wrap hits">.*?<p><span class="availbodybold">(\d*)&nbsp;</span>request found&nbsp;.*?</p>.*?</td>%s', $myRequestsResponse, $matches)){
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
									if (array_key_exists(1, $tableCells)){
										$value = strip_tags(trim($tableCells[1][0]));
									}else{
										$value = '';
									}

									if ($label == 'ILL Number') {
										if ($curRequest != null) {
											$unavailableHolds[] = $curRequest;
										}
										$curRequest = new Hold();
										$curRequest->userId = $patron->id;
										$curRequest->type = 'vdx';
										$curRequest->sourceId = $value;
									} elseif ($label == 'Author') {
										$curRequest->author = $value;
									} elseif ($label == 'Title') {
										$curRequest->title = $value;
									} elseif ($label == 'Status') {
										$curRequest->status = $value;
									} elseif ($label == 'Circulation Status') {
										//$curRequest['circulationStatus'] = $value;
									} elseif ($label == 'Needed by') {
										$curRequest->expirationDate = strtotime($value);
									} elseif ($label == 'Pickup Location') {
										$curRequest->pickupLocationName = $value;
									} elseif ($label == '') {
										//$curRequest['circulationStatus'] .= $value;
									} elseif ($label == 'Cancel') {
										$curRequest->cancelable = true;
									} else {
										//Unknown label
										echo("Unknown label");
									}
								}
							}
							if ($curRequest != null){
								$unavailableHolds[] = $curRequest;
							}
						}
					}
				}
			}
		}

		//TODO: Load the VDX requests we have in the database and match them up.

		return [
			'unavailable' => $unavailableHolds
		];
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
		$newRequest->maximumFee = strip_tags($requestFields['maximumFee']);
		$newRequest->catalogKey = strip_tags($requestFields['catalogKey']);
		$newRequest->note = strip_tags($requestFields['note']);
		$newRequest->pickupLocation = strip_tags($requestFields['pickupLocation']);
		$newRequest->status = 'New';
		$newRequest->insert();

		//To submit, email the submission email address
		require_once ROOT_DIR . '/sys/Email/Mailer.php';
		$mailer = new Mailer();

		$body = "USERID=$user->cat_username\r\n";
		$body .= "ClientCategory=$user->patronType\r\n";
		$body .= "PatronKey=**TBD**\r\n";
		$body .= "ClientLocation=**TBD**\r\n";
		$body .= "ExternalLocation=**TBD**\r\n";
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
		$body .= "ReqMaxCostCurr=USD " . $newRequest->maximumFee . "\r\n";
		$body .= "ReqISBN=" . $newRequest->isbn . "\r\n";
		$body .= "ControlNumbers._new=1\r\n";
		$body .= "ControlNumbers.icn_rota_pos=-1\r\n";
		$body .= "ControlNumbers.icn_loc_well_known=4\r\n";
		$body .= "ControlNumbers.icn_control_number=" . $newRequest->catalogKey . "\r\n";
		$body .= "ReqClassmark=\r\n";
		$body .= "ReqPubPlace=\r\n";
		$body .= "PickupLocation=" . $newRequest->pickupLocation . "\r\n";
		$body .= "ReqVerifySource=**TBD**\r\n";
		$body .= "AuthorisationStatus=**TBD**\r\n";

		if (!empty($newRequest->note)) {
			$body .= "NOTE=" . $newRequest->note . "\r\n";
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
}