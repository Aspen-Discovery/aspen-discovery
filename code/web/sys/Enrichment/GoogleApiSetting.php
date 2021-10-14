<?php


class GoogleApiSetting extends DataObject
{
	public $__table = 'google_api_settings';    // table name
	public $id;
	public $googleAnalyticsVersion;
	public $googleAnalyticsTrackingId;
	public $googleAnalyticsLinkingId;
	public $googleAnalyticsLinkedProperties;
	public $googleAnalyticsDomainName;
	public $googleBooksKey;
	public $googleMapsKey;

	public static function getObjectStructure() : array
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'googleAnalyticsVersion' => array('property' => 'googleAnalyticsVersion', 'type'=>'enum', 'values'=>['v3'=>'Version 3', 'v4'=>'Version 4'],'label'=>'Google Analytics Version', 'description' => 'The version of Google Analytics to use'),
			'googleAnalyticsTrackingId' => array('property' => 'googleAnalyticsTrackingId', 'type' => 'text', 'label' => 'Google Analytics Tracking ID', 'description' => 'The Google analytics Tracking ID to use'),
			'googleAnalyticsLinkingId' => array('property' => 'googleAnalyticsLinkingId', 'type' => 'text', 'label' => 'Google Analytics Linking ID', 'description' => 'The Google analytics Linking ID to use'),
			'googleAnalyticsLinkedProperties' => array('property' => 'googleAnalyticsLinkedProperties', 'type' => 'textarea', 'label' => 'Google Analytics Linked Properties (one per line)', 'description' => 'The Google analytics properties to link to'),
			'googleAnalyticsDomainName' => array('property' => 'googleAnalyticsDomainName', 'type' => 'text', 'label' => 'Google Analytics Domain Name', 'description' => 'The Google analytics domain name to use'),
			'googleBooksKey' => array('property' => 'googleBooksKey', 'type' => 'text', 'label' => 'Google Books Key', 'description' => 'The Google books API key to use'),
			'googleMapsKey' => array('property' => 'googleMapsKey', 'type' => 'storedPassword', 'label' => 'Google Maps Key', 'description' => 'The Google maps API key to use'),
		);
	}
}