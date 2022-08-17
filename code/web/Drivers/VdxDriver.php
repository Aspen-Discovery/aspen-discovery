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
}