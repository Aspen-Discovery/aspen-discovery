<?php

class VdxSetting extends DataObject {
	public $__table = 'vdx_settings';
	public $id;
	public $name;
	public $baseUrl;
	public $submissionEmailAddress;

	public static function getObjectStructure(): array
	{
		$locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer ILL Hold Groups'));

		return [
			'id' => ['property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'],
			'name' => ['property' => 'name', 'type' => 'text', 'label' => 'Name', 'description' => 'The Name of the Hold Group', 'maxLength' => 50],
			'baseUrl' => ['property' => 'baseUrl', 'type' => 'url', 'label' => 'Base Url', 'description' => 'The URL for the VDX System', 'maxLength' => 255],
			'submissionEmailAddress' => ['property' => 'submissionEmailAddress', 'type' => 'email', 'label' => 'Submission Email Address', 'description' => 'The Address where new submissions are sent', 'maxLength' => 255],
		];
	}

	/**
	 * @return string[]
	 */
	public function getUniquenessFields(): array
	{
		return ['name'];
	}

	public function submitRequest(User $user, array $requestFields) : array{
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

		if ($mailer->send($this->submissionEmailAddress, 'Document_Request', $body, null, null)){
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