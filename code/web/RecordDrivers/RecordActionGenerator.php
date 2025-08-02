<?php

function getUntitledVolumeHoldAction($module, $source, $id, $variationId) : array {
	return [
		'title' => translate([
			'text' => 'Place Hold',
			'isPublicFacing' => true,
		]),
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "return AspenDiscovery.Record.showPlaceHold('$module', '$source', '$id', '~untitled~', '$variationId');",
		'requireLogin' => false,
		'type' => 'ils_hold',
	];
}
//Regular ILS holds
function getHoldRequestAction($module, $source, $id, $variationId) : array {
	return [
		'title' => translate([
			'text' => 'Place Hold',
			'isPublicFacing' => true,
		]),
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "return AspenDiscovery.Record.showPlaceHold('$module', '$source', '$id', '', '$variationId');",
		'requireLogin' => false,
		'type' => 'ils_hold',
	];
}

function getSpecificVolumeHoldAction($module, $source, $id, $volumeInfo) : array {
	return [
		'title' => translate([
			'text' => 'Hold %1%',
			1 => $volumeInfo['volumeName'],
			'isPublicFacing' => true,
		]),
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "return AspenDiscovery.Record.showPlaceHold('$module', '$source', '$id', '{$volumeInfo['volumeId']}');",
		'requireLogin' => false,
		'type' => 'ils_hold',
		'volumeId' => $volumeInfo['volumeId'],
		'volumeName' => $volumeInfo['volumeName'],
	];
}

function getMultiVolumeHoldAction($module, $source, $id) : array {
	return [
		'title' => translate([
			'text' => 'Place Hold',
			'isPublicFacing' => true,
		]),
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "return AspenDiscovery.Record.showPlaceHoldVolumes('$module', '$source', '$id');",
		'requireLogin' => false,
		'type' => 'ils_hold',
	];
}

function getMultiVolumeRequestAction($module, $source, $id, $recordDriver) : array {
	global $library;
	$activeLibrary = $library;
	if (UserAccount::isLoggedIn()) {
		$user = UserAccount::getActiveUserObj();
		$activeLibrary = $user->getHomeLibrary();
	}

	if ($activeLibrary->enableMaterialsRequest != 0) {
		return getRedirectToMaterialsRequestAction($activeLibrary, $id, null, $recordDriver);
	}else {
		if (!empty($activeLibrary->localIllEmail)) {
			$redirectParams =[
				'title' => $recordDriver->getTitle(),
				'author' => $recordDriver->getPrimaryAuthor() ?? '',
				'volume' => '',
				'recordId' => $id
			];
			return [
				'title' => translate([
					'text' => 'Request',
					'isPublicFacing' => true,
				]),
				'url' => '',
				'id' => "actionButton$id",
				'onclick' => "return AspenDiscovery.Record.showLocalIllEmailForm('$module', '$source', '$id');",
				'requireLogin' => false,
				'type' => 'local_ill_request_email',
				'btnType' => 'btn-local-ill-request btn-action',
				'redirectParams' => $redirectParams
			];
		}else{
			return [
				'title' => translate([
					'text' => 'Request',
					'isPublicFacing' => true,
				]),
				'url' => '',
				'id' => "actionButton$id",
				'onclick' => "return AspenDiscovery.Record.showLocalIllRequest('$module', '$source', '$id');",
				'requireLogin' => false,
				'type' => 'local_ill_request',
				'btnType' => 'btn-local-ill-request btn-action'
			];
		}
	}
}

function getRedirectToMaterialsRequestAction($activeLibrary, $id, $volumeInfo, $recordDriver) : array {
	$redirectUrl = '';
	$type = '';
	if ($activeLibrary->enableMaterialsRequest == 1) {
		//Aspen request system
		$redirectUrl = '/MaterialsRequest/NewRequest';
		$type = 'local_ill_request_material_request';
	}else if ($activeLibrary->enableMaterialsRequest == 2) {
		//ILS request system
		$redirectUrl = '/MaterialsRequest/NewRequestIls';
		$type = 'local_ill_request_material_request_ils';
	}else if ($activeLibrary->enableMaterialsRequest == 3) {
		$redirectUrl = $activeLibrary->externalMaterialsRequestUrl;
		$type = 'local_ill_request_external_request';
	}
	if (!str_contains($redirectUrl, '?')) {
		$redirectUrl .= '?';
	}else{
		$redirectUrl .= '&';
	}

	$title = urlencode($recordDriver->getTitle());
	if ($title == null) {
		$title = '';
	}
	$redirectUrl .= "title=" . urlencode($title);
	$redirectParams =[
		'title' => $title,
		'author' => '',
		'volume' => '',
		'isLocalIll' => false
	];
	$primaryAuthor = $recordDriver->getPrimaryAuthor();
	if ($primaryAuthor != null) {
		$redirectUrl .= "&author=" . urlencode($primaryAuthor);
		$redirectParams['author'] = $primaryAuthor;
	}
	if ($activeLibrary->enableMaterialsRequest == 1) {
		$redirectUrl .= "&isLocalIll=true";
		$redirectParams['isLocalIll'] = true;
	}
	if ($volumeInfo != null) {
		$redirectUrl .= "&volume=" . urlencode($volumeInfo['volumeName']);
		$title = translate([
			'text' => 'Request %1%',
			1 => $volumeInfo['volumeName'],
			'isPublicFacing' => true,
		]);
		$redirectParams['volume'] = $volumeInfo['volumeName'];
	}else{
		$title = translate([
			'text' => 'Request',
			'isPublicFacing' => true,
		]);
	}
	if ($type == 'local_ill_request_external_request') {
		$redirectParams['url'] = $redirectUrl;
	}
	//redirect to URL
	$message = translate([
		'text' => "Titles with volumes must be requested via our request system.",
		'isPublicFacing' => true,
		'inAttribute' => true,
	]);
	$buttonText = translate([
		'text' => 'Continue',
		'isPublicFacing' => true,
		'inAttribute' => true,
	]);
	return [
		'title' => $title,
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "AspenDiscovery.MaterialsRequest.showRedirectToMaterialsRequestForm('$title', '$message', '$buttonText', '$redirectUrl');return false;",
		'redirectUrl' => $redirectUrl,
		'requireLogin' => false,
		'type' => $type,
		'btnType' => 'btn-local-ill-request btn-action',
		'redirectParams' => $redirectParams
	];
}

//Local ILL Requests
function getSpecificVolumeLocalIllRequestAction($module, $source, $id, $volumeInfo, $recordDriver) : array {
	//Because Local ILL does not currently support requests on volumes, direct the user to the material request system as appropriate.
	global $library;
	$activeLibrary = $library;
	if (UserAccount::isLoggedIn()) {
		$user = UserAccount::getActiveUserObj();
		$activeLibrary = $user->getHomeLibrary();
	}
	//Check to see if the user can do local ILL by PType
	if (UserAccount::isLoggedIn()) {
		$user = UserAccount::getActiveUserObj();
		$ptype = $user->getPTypeObj();
		if ($ptype == null || !$ptype->allowLocalIll) {
			return [];
		}
	}

	if ($activeLibrary->enableMaterialsRequest != 0) {
		return getRedirectToMaterialsRequestAction($activeLibrary, $id, $volumeInfo, $recordDriver);
	}else{
		//Just display the error message
		if (!empty($activeLibrary->localIllEmail)) {
			$redirectParams =[
				'title' => $recordDriver->getTitle(),
				'author' => $recordDriver->getPrimaryAuthor() ?? '',
				'volume' => $volumeInfo['volumeName'],
				'recordId' => $id
			];
			return [
				'title' => translate([
					'text' => 'Request %1%',
					1 => $volumeInfo['volumeName'],
					'isPublicFacing' => true,
				]),
				'url' => '',
				'id' => "actionButton$id",
				'onclick' => "return AspenDiscovery.Record.showLocalIllEmailForm('$module', '$source', '$id', '{$volumeInfo['volumeName']}');",
				'requireLogin' => false,
				'type' => 'local_ill_request_email',
				'btnType' => 'btn-local-ill-request btn-action',
				'redirectParams' => $redirectParams
			];
		}else {
			$title = translate([
				'text' => '%1% Request Unavailable',
				1 => $volumeInfo['volumeName'],
				'isPublicFacing' => true,
			]);
			$message = translate([
				'text' => "Titles with volumes cannot be requested from other libraries via the catalog. Please contact the library to request this title.",
				'isPublicFacing' => true,
				'inAttribute' => true,
			]);
			return [
				'title' => $title,
				'url' => '',
				'id' => "actionButton$id",
				'onclick' => "AspenDiscovery.showMessage('$title', '$message');return false;",
				'requireLogin' => false,
				'type' => 'local_ill_request_unavailable',
				'btnType' => 'btn-local-ill-request btn-action',
				'message' => $message,
			];
		}
	}
}

function getLocalIllRequestAction($module, $source, $id) : array {
	//Check to see if the user can do local ILL by PType
	if (UserAccount::isLoggedIn()) {
		$user = UserAccount::getActiveUserObj();
		$ptype = $user->getPTypeObj();
		if ($ptype == null || !$ptype->allowLocalIll) {
			return getLocalIllNotAllowedAction($id);
		}
	}
	return [
		'title' => translate([
			'text' => 'Request',
			'isPublicFacing' => true,
		]),
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "return AspenDiscovery.Record.showLocalIllRequest('$module', '$source', '$id');",
		'requireLogin' => false,
		'type' => 'local_ill_request',
		'btnType' => 'btn-local-ill-request btn-action'
	];
}

function getNoVolumesCanBeRequestedAction($module, $source, $id) : array {
	//Check to see if the user can do local ILL by PType
	if (UserAccount::isLoggedIn()) {
		$user = UserAccount::getActiveUserObj();
		$ptype = $user->getPTypeObj();
		if ($ptype == null || !$ptype->allowLocalIll) {
			return getLocalIllNotAllowedAction($id);
		}
	}
	$title = translate([
		'text' => 'Request Unavailable',
		'isPublicFacing' => true,
		'inAttribute' => true,
	]);
	$message = translate([
		'text' => "Titles with volumes cannot be requested from other libraries via the catalog. Please contact the library to request this title.",
		'isPublicFacing' => true,
		'inAttribute' => true,
	]);
	return [
		'title' => $title,
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "AspenDiscovery.showMessage('$title', '$message');return false;",
		'requireLogin' => false,
		'type' => 'local_ill_request',
		'btnType' => 'btn-local-ill-request btn-action'
	];
}

function getLocalIllNotAllowedAction($id) :array {
	$title = translate([
		'text' => 'Request Not Allowed',
		'isPublicFacing' => true,
		'inAttribute' => true,
	]);
	$message = translate([
		'text' => "Sorry, your account is not allowed to place requests.",
		'isPublicFacing' => true,
		'inAttribute' => true,
	]);
	return [
		'title' => $title,
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "AspenDiscovery.showMessage('$title', '$message');return false;",
		'requireLogin' => false,
		'type' => 'local_ill_request',
		'btnType' => 'btn-local-ill-request btn-action'
	];
}
//VDX Requests
function getVdxRequestAction($module, $source, $id) : array {
	return [
		'title' => translate([
			'text' => 'Request',
			'isPublicFacing' => true,
		]),
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "return AspenDiscovery.Record.showVdxRequest('$module', '$source', '$id');",
		'requireLogin' => false,
		'type' => 'vdx_request',
		'btnType' => 'btn-vdx-request btn-action'
	];
}

//PDF Actions
function getViewSinglePdfAction($fileId) : array {
	return [
		'title' => translate([
			'text' => 'View PDF',
			'isPublicFacing' => true,
		]),
		'url' => "/Files/$fileId/ViewPDF",
		'requireLogin' => false,
		'type' => 'view_pdf',
	];
}

function getDownloadSinglePdfAction($id, $fileId) : array {
	return [
		'title' => translate([
			'text' => 'Download PDF',
			'isPublicFacing' => true,
		]),
		'url' => "/Record/$id/DownloadPDF?fileId=$fileId",
		'requireLogin' => false,
		'type' => 'download_pdf',
	];
}

function getViewMultiPdfAction($id) : array {
	return [
		'title' => 'View PDF',
		'url' => '',
		'onclick' => "return AspenDiscovery.Record.selectFileToView('$id', 'RecordPDF');",
		'requireLogin' => false,
		'type' => 'view_pdf',
	];
}

function getDownloadMultiPdfAction($id) : array {
	return [
		'title' => 'Download PDF',
		'url' => '',
		'onclick' => "return AspenDiscovery.Record.selectFileDownload('$id', 'RecordPDF');",
		'requireLogin' => false,
		'type' => 'download_pdf',
	];
}

//Supplemental File Actions
function getDownloadSingleSupplementalFileAction($id, $fileId) : array {
	return [
		'title' => translate([
			'text' => 'Download Supplemental File',
			'isPublicFacing' => true,
		]),
		'url' => "/Record/$id/DownloadSupplementalFile?fileId=$fileId",
		'requireLogin' => false,
		'type' => 'download_supplemental_file',
	];
}

function getDownloadMultiSupplementalFileAction($id) : array {
	return [
		'title' => translate([
			'text' => 'Download Supplemental File',
			'isPublicFacing' => true,
		]),
		'url' => '',
		'onclick' => "return AspenDiscovery.Record.selectFileDownload('$id', 'RecordSupplementalFile');",
		'requireLogin' => false,
		'type' => 'download_supplemental_file',
	];
}
