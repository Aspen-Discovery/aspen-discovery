<?php

/**
 * This file is a wrapper around the mobileesp library for browser detection.
 * We chose mobileesp as VuFind's default option because it is fairly robust
 * and has an Apache license which allows free redistribution.  However, it
 * is not the only option available.
 *
 * You can also replace this entire file with the code available for download
 * at http://detectmobilebrowsers.mobi/ if you would like to try alternative
 * detection rules.  Other detection libraries beyond these two options also
 * exist; it should be relatively easy to plug any of them in by modifying the
 * mobile_device_detect function below.
 */
require_once ROOT_DIR . '/sys/mobileesp/mdetect.php';

function mobile_device_detect() {
	// Do the most exhaustive device detection possible; other method calls
	// may be used instead of DetectMobileLong if you want to target a narrower
	// class of devices.
	$mobile = new uagent_info();
	return $mobile->DetectMobileLong();
}

function get_device_name() {
	// Do the most exhaustive device detection possible; other method calls
	// may be used instead of DetectMobileLong if you want to target a narrower
	// class of devices.
	$mobile = new uagent_info();
	if ($mobile->DetectKindle()) {
		return 'Kindle';
	} elseif ($mobile->DetectKindleFire() || $mobile->DetectAmazonSilk()) {
		return 'Kindle Fire';
	} elseif ($mobile->DetectIpad()) {
		return 'iPad';
	} elseif ($mobile->DetectIphone()) {
		return 'iPhone';
	} elseif ($mobile->DetectMac()) {
		return 'Mac';
	} elseif ($mobile->DetectAndroidPhone()) {
		return 'Android Phone';
	} elseif ($mobile->DetectAndroidTablet()) {
		return 'Android Tablet';
	} elseif ($mobile->DetectBlackBerry()) {
		return 'BlackBerry';
	} elseif ($mobile->DetectGoogleTV()) {
		return 'Google TV';
	} elseif ($mobile->DetectIos()) {
		return 'iOS';
	} else {
		return 'PC';
	}

}