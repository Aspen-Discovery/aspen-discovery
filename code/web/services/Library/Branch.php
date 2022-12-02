<?php

class Branch extends Action {

	/** @var Location */
	private $activeLocation;

	function launch() {
		global $interface;
		global $configArray;

		$location = new Location();
		$location->locationId = $_REQUEST['id'];
		if ($location->find(true)) {
			$interface->assign('location', $location);
			$this->activeLocation = $location;

			$mapAddress = urlencode(preg_replace('/\r\n|\r|\n/', '+', $location->address));
			$hours = $location->getHours();
			$hoursSemantic = [];
			foreach ($hours as $key => $hourObj) {
				if (!$hourObj->closed) {
					$hourString = $hourObj->open;
					[
						$hour,
						$minutes,
					] = explode(':', $hourString);
					if ($hour < 12) {
						$hourObj->open .= ' AM';
					} elseif ($hour == 12) {
						$hourObj->open = 'Noon';
					} elseif ($hour == 24) {
						$hourObj->open = 'Midnight';
					} else {
						$hour -= 12;
						$hourObj->open = "$hour:$minutes PM";
					}
					$hourString = $hourObj->close;
					[
						$hour,
						$minutes,
					] = explode(':', $hourString);
					if ($hour < 12) {
						$hourObj->close .= ' AM';
					} elseif ($hour == 12) {
						$hourObj->close = 'Noon';
					} elseif ($hour == 24) {
						$hourObj->close = 'Midnight';
					} else {
						$hour -= 12;
						$hourObj->close = "$hour:$minutes PM";
					}
					$hoursSemantic[] = [
						'@type' => 'OpeningHoursSpecification',
						'opens' => $hourObj->open,
						'closes' => $hourObj->close,
						'dayOfWeek' => 'http://purl.org/goodrelations/v1#' . $hourObj->day,
					];
				}
				$hours[$key] = $hourObj;
			}
			$googleSettings = new GoogleApiSetting();
			if ($googleSettings->find(true)) {
				$mapsKey = $googleSettings->googleMapsKey;
			} else {
				$mapsKey = null;
			}
			$mapLink = "http://maps.google.com/maps?f=q&hl=en&geocode=&q=$mapAddress&ie=UTF8&z=15&iwloc=addr&om=1&t=m";
			$locationInfo = [
				'id' => $location->locationId,
				'name' => $location->displayName,
				'address' => preg_replace('/\r\n|\r|\n/', '<br>', $location->address),
				'phone' => $location->phone,
				'map_image' => "http://maps.googleapis.com/maps/api/staticmap?center=$mapAddress&zoom=15&size=200x200&sensor=false&markers=color:red%7C$mapAddress&key=$mapsKey",
				'map_link' => $mapLink,
				'hours' => $hours,
			];
			$interface->assign('locationInfo', $locationInfo);

			//Schema.org
			$semanticData = [
				'@context' => 'http://schema.org',
				'@type' => 'Library',
				'name' => $location->displayName,
				'branchCode' => $location->code,
				'parentOrganization' => $configArray['Site']['url'] . "/Library/{$location->libraryId}/System",
			];

			if ($location->address) {
				$semanticData['address'] = $location->address;
				$semanticData['hasMap'] = $mapLink;
			}
			if ($location->phone) {
				$semanticData['telephone'] = $location->phone;
			}
			if (!empty($hoursSemantic)) {
				$semanticData['openingHoursSpecification'] = $hoursSemantic;
			}

			$interface->assign('semanticData', json_encode($semanticData));
		}

		$this->display('branch.tpl', $location->displayName, 'Search/home-sidebar.tpl', false);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		if (!empty($this->activeLocation)) {
			$breadcrumbs[] = new Breadcrumb('/Library/' . $this->activeLocation->libraryId . '/System', 'Library System');
		}
		return $breadcrumbs;
	}
}