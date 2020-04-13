<?php


class GoogleApiSetting extends DataObject
{
	public $__table = 'google_api_settings';    // table name
	public $id;
	public $googleAnalyticsTrackingId;
	public $googleAnalyticsLinkingId;
	public $googleAnalyticsLinkedProperties;
	public $googleAnalyticsDomainName;
	public $googleBooksKey;
	public $googleMapsKey;
	public $googleTranslateKey;
	public $googleTranslateLanguages;

	public static function getObjectStructure()
	{
		return array(
			'id' => array('property' => 'id', 'type' => 'label', 'label' => 'Id', 'description' => 'The unique id'),
			'googleAnalyticsTrackingId' => array('property' => 'googleAnalyticsTrackingId', 'type' => 'text', 'label' => 'Google Analytics Tracking ID', 'description' => 'The Google analytics Tracking ID to use'),
			'googleAnalyticsLinkingId' => array('property' => 'googleAnalyticsLinkingId', 'type' => 'text', 'label' => 'Google Analytics Linking ID', 'description' => 'The Google analytics Linking ID to use'),
			'googleAnalyticsLinkedProperties' => array('property' => 'googleAnalyticsLinkedProperties', 'type' => 'textarea', 'label' => 'Google Analytics Linked Properties (one per line)', 'description' => 'The Google analytics properties to link to'),
			'googleAnalyticsDomainName' => array('property' => 'googleAnalyticsDomainName', 'type' => 'text', 'label' => 'Google Analytics Domain Name', 'description' => 'The Google analytics domain name to use'),
			'googleBooksKey' => array('property' => 'googleBooksKey', 'type' => 'text', 'label' => 'Google Books Key', 'description' => 'The Google books API key to use'),
			'googleMapsKey' => array('property' => 'googleMapsKey', 'type' => 'text', 'label' => 'Google Maps Key', 'description' => 'The Google maps API key to use'),
			'googleTranslateKey' => array('property' => 'googleTranslateKey', 'type' => 'text', 'label' => 'Google Translate Key', 'description' => 'The Google translate API key to use'),
			'googleTranslateLanguages' => array('property' => 'googleTranslateLanguages', 'type' => 'text', 'label' => 'Google Translate Languages', 'description' => 'The Google translate lanaguages to show', 'default'=>'ar,da,en,es,fr,de,it,ja,pl,pt,ru,sv,th,vi,zh-CN,zh-TW'),
		);
	}
}