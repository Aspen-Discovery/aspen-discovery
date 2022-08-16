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

	public function submitRequest(User $user, $requestFields){
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
		$body .= "ReqTitle=" . strip_tags($_REQUEST['title']) . "\r\n";
		$body .= "ReqAuthor=" . strip_tags($_REQUEST['author']) . "\r\n";
		$body .= "ReqPublisher=" . strip_tags($_REQUEST['publisher']) . "\r\n";
		$body .= "ReqPubDate=\r\n";
		$body .= "ReqAdditional=Patron response to will pay: " . ($_REQUEST['acceptFee'] ? 'Yes' : 'No') . "\r\n";
		$body .= "ReqMaxCostCurr=USD " . strip_tags($_REQUEST['maximumFee']) . "\r\n";
		$body .= "ReqISBN=" . strip_tags($_REQUEST['isbn']) . "\r\n";
		$body .= "ControlNumbers._new=1\r\n";
		$body .= "ControlNumbers.icn_rota_pos=-1\r\n";
		$body .= "ControlNumbers.icn_loc_well_known=4\r\n";
		$body .= "ControlNumbers.icn_control_number=" . strip_tags($_REQUEST['catalogKey']) . "\r\n";
		$body .= "ReqClassmark=\r\n";
		$body .= "ReqPubPlace=\r\n";
		$body .= "PickupLocation=" . strip_tags($_REQUEST['pickupLocation']) . "\r\n";
		$body .= "ReqVerifySource=**TBD**\r\n";
		$body .= "AuthorisationStatus=**TBD**\r\n";

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