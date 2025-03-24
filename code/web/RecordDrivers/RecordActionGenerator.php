<?php

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

//Local ILL Requests
function getSpecificVolumeLocalIllRequestAction($module, $source, $id, $volumeInfo) : array {
	//Check to see if the user can do local ILL by PType
	if (UserAccount::isLoggedIn()) {
		$user = UserAccount::getActiveUserObj();
		$ptype = $user->getPTypeObj();
		if ($ptype == null || !$ptype->allowLocalIll) {
			return [];
		}
	}
	return [
		'title' => translate([
			'text' => 'Request %1%',
			1 => $volumeInfo['volumeName'],
			'isPublicFacing' => true,
		]),
		'url' => '',
		'id' => "actionButton$id",
		'onclick' => "return AspenDiscovery.Record.showLocalIllRequest('$module', '$source', '$id', '{$volumeInfo['volumeId']}');",
		'requireLogin' => false,
		'type' => 'local_ill_request',
		'volumeId' => $volumeInfo['volumeId'],
		'volumeName' => $volumeInfo['volumeName']
	];
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
