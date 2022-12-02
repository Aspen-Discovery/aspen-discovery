<?php


class GoogleApiSetting extends DataObject {
	public $__table = 'google_api_settings';    // table name
	public $id;
	public $googleAnalyticsVersion;
	public $googleAnalyticsTrackingId;
	public $googleAnalyticsLinkingId;
	public $googleAnalyticsLinkedProperties;
	public $googleAnalyticsDomainName;
	public $googleBooksKey;
	public $googleMapsKey;

	public static function getObjectStructure(): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'googleAnalyticsVersion' => [
				'property' => 'googleAnalyticsVersion',
				'type' => 'enum',
				'values' => [
					'v3' => 'Version 3',
					'v4' => 'Version 4',
				],
				'label' => 'Google Analytics Version',
				'description' => 'The version of Google Analytics to use',
			],
			'googleAnalyticsTrackingId' => [
				'property' => 'googleAnalyticsTrackingId',
				'type' => 'text',
				'label' => 'Google Analytics Tracking ID',
				'description' => 'The Google analytics Tracking ID to use',
			],
			'googleAnalyticsLinkingId' => [
				'property' => 'googleAnalyticsLinkingId',
				'type' => 'text',
				'label' => 'Google Analytics Linking ID',
				'description' => 'The Google analytics Linking ID to use',
			],
			'googleAnalyticsLinkedProperties' => [
				'property' => 'googleAnalyticsLinkedProperties',
				'type' => 'textarea',
				'label' => 'Google Analytics Linked Properties (one per line)',
				'description' => 'The Google analytics properties to link to',
			],
			'googleAnalyticsDomainName' => [
				'property' => 'googleAnalyticsDomainName',
				'type' => 'text',
				'label' => 'Google Analytics Domain Name',
				'description' => 'The Google analytics domain name to use',
			],
			'googleBooksKey' => [
				'property' => 'googleBooksKey',
				'type' => 'storedPassword',
				'label' => 'Google Books Key',
				'description' => 'The Google books API key to use',
				'hideInLists' => true,
			],
			'googleMapsKey' => [
				'property' => 'googleMapsKey',
				'type' => 'storedPassword',
				'label' => 'Google Maps Key',
				'description' => 'The Google maps API key to use',
				'hideInLists' => true,
			],
		];
	}
}