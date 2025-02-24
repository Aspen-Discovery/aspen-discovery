<?php

require_once ROOT_DIR . '/JSON_Action.php';

class Events_AJAX extends JSON_Action {

	/** @noinspection PhpUnused */
	public function getEventTypesAndSubLocationsForLocation() {
		require_once ROOT_DIR . '/sys/Events/EventType.php';
		$result = [
			'success' => false,
			'title' => translate([
				'text' => "Error",
				'isAdminFacing' => true,
			]),
			'message' =>  translate([
				'text' => 'Unknown location',
				'isAdminFacing' => true,
			])
		];
		if (!empty($_REQUEST['locationId'])) {
			$eventTypeIds = EventType::getEventTypeIdsForLocation($_REQUEST['locationId']);
			$sublocations = Location::getEventSublocations($_REQUEST['locationId']);
			if (!empty($eventTypeIds)) {
				$result = [
					'success' => true,
					'eventTypeIds' => json_encode($eventTypeIds),
					'sublocations' => json_encode($sublocations),
				];
			} else {
				$result = [
					'success' => true,
					'eventTypeIds' => '',
					'title' => translate([
						'text' => "No available event types",
						'isAdminFacing' => true,
					]),
					'message' => translate([
						'text' => 'No event types are available for this location.',
						'isAdminFacing' => true,
					])
				];
			}
		}
		return $result;
	}

	/** @noinspection PhpUnused */
	public function getEventTypeFields() {
		require_once ROOT_DIR . '/sys/Events/EventType.php';
		$result = [
			'success' => false,
			'title' => translate([
				'text' => "Error",
				'isAdminFacing' => true,
			]),
			'message' =>  translate([
				'text' => 'Unknown event type.',
				'isAdminFacing' => true,
			])
		];
		if (!empty($_REQUEST['eventTypeId'])) {
			$eventType = new EventType();
			$eventType->id = $_REQUEST['eventTypeId'];
			if ($eventType->find(true)) {
				$fieldStructure = $eventType->getFieldSetFields();
				global $interface;
				$fieldHTML = [];
				foreach ($fieldStructure as $property) {
					$interface->assign('property', $property);
					$fieldHTML[] = $interface->fetch('DataObjectUtil/property.tpl');
				}
				$locations = $eventType->getLocations();
				$result = [
					'success' => true,
					'eventType' => $eventType->jsonSerialize(),
					'typeFields' => $fieldHTML,
					'locationIds' => json_encode(array_keys($locations)),
				];
			}
		}
		return $result;
	}

}