<?php
require_once 'DB/DataObject.php';

class Analytics_Session extends DB_DataObject
{
	public $__table = 'analytics_session';                        // table name
	public $id;
	public $session_id;
	public $rememberMe;
	public $sessionStartTime;
	public $lastRequestTime;
	public $countryId;
	public $stateId;
	public $cityId;
	public $latitude;
	public $longitude;
	public $ip;
	public $themeId;
	public $mobile;
	public $deviceId;
	public $physicalLocationId;
	public $patronTypeId;
	public $homeLocationId;

	function setTheme($theme){
		global $memCache;
		global $configArray;
		$analyticsThemeId = $memCache->get('analytics_theme_' . $theme);
		if ($analyticsThemeId == false){
			//Check to see if the theme is in the database
			require_once ROOT_DIR . '/sys/analytics/Analytics_Theme.php';
			$analyticsTheme = new Analytics_Theme;
			$analyticsTheme->value = $theme;
			if (!$analyticsTheme->find(true)){
				$analyticsTheme->insert();
			}
			$analyticsThemeId = $analyticsTheme->id;
			$memCache->add('analytics_theme_' . $theme,$analyticsThemeId, 0, $configArray['Caching']['analytics_references']);
		}
		$this->themeId = $analyticsThemeId;
	}

	function setDevice($device){
		global $memCache;
		global $configArray;
		$analyticsDeviceId = $memCache->get('analytics_device_' . $device);
		if ($analyticsDeviceId == false){
			//Check to see if the theme is in the database
			require_once ROOT_DIR . '/sys/analytics/Analytics_Device.php';
			$analyticsDevice = new Analytics_Device;
			$analyticsDevice->value = $device;
			if (!$analyticsDevice->find(true)){
				$analyticsDevice->insert();
			}
			$analyticsDeviceId = $analyticsDevice->id;
			$memCache->add('analytics_device_' . $device, $analyticsDeviceId, 0, $configArray['Caching']['analytics_references']);
		}
		$this->deviceId = $analyticsDeviceId;
	}

	function setPhysicalLocation($physicalLocation){
		global $memCache;
		global $configArray;
		$analyticsPhysicalLocationId = $memCache->get('analytics_physicalLocation_' . $physicalLocation);
		if ($analyticsPhysicalLocationId == false){
			//Check to see if the theme is in the database
			require_once ROOT_DIR . '/sys/analytics/Analytics_PhysicalLocation.php';
			$analyticsPhysicalLocation = new Analytics_PhysicalLocation;
			$analyticsPhysicalLocation->value = $physicalLocation;
			if (!$analyticsPhysicalLocation->find(true)){
				$analyticsPhysicalLocation->insert();
			}
			$analyticsPhysicalLocationId = $analyticsPhysicalLocation->id;
			$memCache->add('analytics_physicalLocation_' . $physicalLocation, $analyticsPhysicalLocationId, 0, $configArray['Caching']['analytics_references']);
		}
		$this->physicalLocationId = $analyticsPhysicalLocationId;
	}

	function setPatronType($patronType){
		global $memCache;
		global $configArray;
		$analyticsPatronTypeId = $memCache->get('analytics_patronType_' . $patronType);
		if ($analyticsPatronTypeId == false){
			//Check to see if the theme is in the database
			require_once ROOT_DIR . '/sys/analytics/Analytics_PatronType.php';
			$analyticsPatronType = new Analytics_PatronType;
			$analyticsPatronType->value = $patronType;
			if (!$analyticsPatronType->find(true)){
				$analyticsPatronType->insert();
			}
			$analyticsPatronTypeId = $analyticsPatronType->id;
			$memCache->add('analytics_patronType_' . $patronType, $analyticsPatronTypeId, 0, $configArray['Caching']['analytics_references']);
		}
		$this->patronTypeId = $analyticsPatronTypeId;
	}

	function setCountry($country){
		global $memCache;
		global $configArray;
		$analyticsCountryId = $memCache->get('analytics_country_' . $country);
		if ($analyticsCountryId == false){
			//Check to see if the theme is in the database
			require_once ROOT_DIR . '/sys/analytics/Analytics_Country.php';
			$analyticsCountry = new Analytics_Country;
			$analyticsCountry->value = $country;
			if (!$analyticsCountry->find(true)){
				$analyticsCountry->insert();
			}
			$analyticsCountryId = $analyticsCountry->id;
			$memCache->add('analytics_country_' . $country, $analyticsCountryId, 0, $configArray['Caching']['analytics_references']);
		}
		$this->countryId = $analyticsCountryId;
	}

	function setState($state){
		global $memCache;
		global $configArray;
		$analyticsStateId = $memCache->get('analytics_state_' . $state);
		if ($analyticsStateId == false){
			//Check to see if the theme is in the database
			require_once ROOT_DIR . '/sys/analytics/Analytics_State.php';
			$analyticsState = new Analytics_State;
			$analyticsState->value = $state;
			if (!$analyticsState->find(true)){
				$analyticsState->insert();
			}
			$analyticsStateId = $analyticsState->id;
			$memCache->add('analytics_state_' . $state, $analyticsStateId, 0, $configArray['Caching']['analytics_references']);
		}
		$this->stateId = $analyticsStateId;
	}

	function setCity($city){
		global $memCache;
		global $configArray;
		$analyticsCityId = $memCache->get('analytics_city_' . $city);
		if ($analyticsCityId == false){
			//Check to see if the theme is in the database
			require_once ROOT_DIR . '/sys/analytics/Analytics_City.php';
			$analyticsCity = new Analytics_City;
			$analyticsCity->value = $city;
			if (!$analyticsCity->find(true)){
				$analyticsCity->insert();
			}
			$analyticsCityId = $analyticsCity->id;
			$memCache->add('analytics_city_' . $city, $analyticsCityId, 0, $configArray['Caching']['analytics_references']);
		}
		$this->cityId = $analyticsCityId;
	}
}