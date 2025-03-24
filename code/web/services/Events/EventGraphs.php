<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';
require_once ROOT_DIR . '/sys/Events/EventInstance.php';
require_once ROOT_DIR . '/sys/Events/Event.php';
require_once ROOT_DIR . '/sys/Events/EventField.php';

class Events_EventGraphs extends Admin_Admin {

	function launch() {
		global $interface;

		$stat = "eventHours";

		// Form options
		$timeframe = $_REQUEST['timeframe'] ?? 'days';
		$interface->assign('timeframe', $timeframe);
		$fromDate = $_REQUEST['fromDate'] ?? '';
		$interface->assign('fromDate', $fromDate);
		$toDate = $_REQUEST['toDate'] ?? '';
		$interface->assign('toDate', $toDate);
		$eventType = $_REQUEST['type'] ?? '';
		$interface->assign('eventTypeValue', $eventType);
		$interface->assign('eventTypes', EventType::getEventTypeList(true));

		// $libraryList = Library::getLibraryList(!UserAccount::userHasPermission('View Event Reports For All Libraries'));
		$locations = Location::getLocationList(!UserAccount::userHasPermission('View Event Reports for All Libraries') || UserAccount::userHasPermission('View Event Reports for Home Library'));
		$location = $_REQUEST['location'] ?? '';
		$interface->assign('locationValue', $location);
		$interface->assign('locations', $locations);

		$sublocation = $_REQUEST['sublocation'] ?? '';
		// Only get sublocation options if there's a location
		if ($location != '') {
			$sublocations = Location::getEventSublocations($location);
			$interface->assign('sublocationValue', $sublocation);
			$interface->assign('sublocations', $sublocations);
		} else {
			$interface->assign('sublocations', '');
		}

		$checkboxFields = EventField::getEventFieldsByTypes([2]);
		$interface->assign('checkboxFields', $checkboxFields);
		$selectFields = EventField::getEventFieldsByTypes([3]);
		$interface->assign('selectFields', $selectFields);
		$fields = array_filter($_REQUEST, function($v, $k) {
			return str_contains($k, 'field_') && $v != NULL && $v !== '';
		}, ARRAY_FILTER_USE_BOTH);
		foreach ($fields as $key => $value) {
			$field[$key] = $_REQUEST[$key];
		}
		$interface->assign('fields', $fields);
		$query = $_REQUEST['query'] ?? '';
		$interface->assign('query', $query);


		$title = 'Aspen Event Hours';
		$interface->assign('section', 'Events');
		$interface->assign('showCSVExportButton', true);
		$interface->assign('graphTitle', $title);
		// $this->assignGraphSpecificTitle($stat);
		$this->getAndSetInterfaceDataSeries($stat, $timeframe, $eventType, $location, $sublocation, $query, $fields, $fromDate, $toDate);
		$interface->assign('stat', $stat);
		$interface->assign('propName', 'exportToCSV');
		$title = $interface->getVariable('graphTitle');
		$this->assignGraphSpecificTitle($stat, $timeframe, $eventType, $location, $sublocation, $query, $fields, $fromDate, $toDate);
		$this->display('event-graph.tpl', $title);
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/EventGraphs', 'Events Graphs');
		return $breadcrumbs;
	}

	function getActiveAdminSection(): string {
		return 'events';
	}

	function getModule(): string {
		return 'Events';
	}

	function getToolName(): string {
		return 'Events';
	}

	function canView(): bool {
		return UserAccount::userHasPermission([
			"View Event Reports for All Libraries",
			"View Event Reports for Home Library",
		]);
	}

	public function buildCSV() {
		global $interface;

		$stat = "eventHours";

		// Form options
		$timeframe = $_REQUEST['timeframe'] ?? 'days';
		$eventType = $_REQUEST['type'] ?? '';
		$location = $_REQUEST['location'] ?? '';
		$sublocation = $_REQUEST['sublocation'] ?? '';
		$fromDate = $_REQUEST['fromDate'] ?? '';
		$toDate = $_REQUEST['toDate'] ?? '';
		$eventType = $_REQUEST['type'] ?? '';
		$fields = array_filter($_REQUEST, function($v, $k) {
			return str_contains($k, 'field_') && $v != NULL && $v !== '';
		}, ARRAY_FILTER_USE_BOTH);
		foreach ($fields as $key => $value) {
			$field[$key] = $_REQUEST[$key];
		}
		$query = $_REQUEST['query'] ?? '';

		$this->getAndSetInterfaceDataSeries($stat, $timeframe, $eventType, $location, $sublocation, $query, $fields, $fromDate, $toDate);
		$dataSeries = $interface->getVariable('dataSeries');

		$filename = "AspenUsageData_{$stat}.csv";
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header('Content-Type: text/csv; charset=utf-8');
		header("Content-Disposition: attachment;filename={$filename}");
		$fp = fopen('php://output', 'w');
		$graphTitles = array_keys($dataSeries);
		$numGraphTitles = count($dataSeries);

		// builds the header for each section of the table in the CSV - column headers: Dates, and the title of the graph
		for($i = 0; $i < $numGraphTitles; $i++) {
			$dataSerie = $dataSeries[$graphTitles[$i]];
			$numRows = count($dataSerie['data']);
			$dates = array_keys($dataSerie['data']);
			$header = ['Dates', $graphTitles[$i]];
			fputcsv($fp, $header);

			if( empty($numRows)) {
				fputcsv($fp, ['no data found!']);
			}
			// builds each subsequent data row - aka the column value
			for($j = 0; $j < $numRows; $j++) {
				$date = $dates[$j];
				$value = $dataSerie['data'][$date];
				$row = [$date, $value];
				fputcsv($fp, $row);
			}
		}
		exit();
	}

	private function getAndSetInterfaceDataSeries($stat, $timeframe, $eventType, $location, $sublocation = '', $query = '', $fields = [], $fromDate = '', $toDate = ''): void {
		global $interface;

		$dataSeries = [];
		$columnLabels = [];
		$userHours = new EventInstance();
		$userHours->selectAdd();
		$userHours->whereAdd("event_instance.deleted = 0");
		$userHours->whereAdd("event_instance.status = 1"); // Exclude cancelled events
		if ($fromDate != '') {
			$userHours->whereAdd("event_instance.date >= {$userHours->escape($fromDate)}");
		}
		if ($toDate != '') {
			$userHours->whereAdd("event_instance.date <= {$userHours->escape($toDate)}");
		}
		if (!empty($query) || !empty($fields)) {
			$eventField = new EventEventField();
			foreach ($fields as $key => $value) {
				$eventField->whereAdd("eventFieldId = " . $eventField->escape(substr($key, -1)) . " AND value = " . $eventField->escape($value));
			}
			$eventField->groupBy("eventId");
			$userHours->joinAdd($eventField, 'INNER', 'eventEventField', 'eventId', 'eventId');
		}
		$restrictByHomeLibrary = !UserAccount::userHasPermission('View Event Reports for All Libraries') || UserAccount::userHasPermission('View Event Reports for Home Library');
		$interface->assign('libraryRestriction', $restrictByHomeLibrary ? " at Your Home Library" : "");
		if (!empty($eventType) || !empty($location) || $restrictByHomeLibrary || !empty($query)) {
			$event = new Event();
			if (!empty($eventType)) {
				$event->whereAdd("eventTypeId = " . $event->escape($eventType));
			}
			if (!empty($location)) {
				$event->whereAdd("locationId = " . $event->escape($location));
				if (!empty($sublocation)) {
					$event->whereAdd("sublocationId = " . $event->escape($sublocation));
				}
			} elseif ($restrictByHomeLibrary) {
				$homeLibraryLocations = Location::getLocationList(true);
				$event->whereAddIn('locationId', array_keys($homeLibraryLocations), true,'AND');
			}
			$userHours->joinAdd($event, 'INNER', 'event', 'eventId', 'id');
		}
		if (!empty($query)) {
			$escapedQuery = $userHours->escape('%' . $query . '%');
			$userHours->whereAdd("(event.title LIKE $escapedQuery OR event.description LIKE $escapedQuery OR eventEventField.value LIKE $escapedQuery)");
		}
		switch ($timeframe) {
			case "weeks":
				$userHours->selectAdd("WEEK(date) AS week, YEAR(date) AS year");
				$userHours->groupBy("week, year");
				break;
			case "months":
				$userHours->selectAdd("MONTH(date) AS month, YEAR(date) AS year");
				$userHours->groupBy("month, year");
				break;
			case "years":
				$userHours->selectAdd("YEAR(date) AS year");
				$userHours->groupBy("year");
				break;
			case "days":
			default: // default to hours per day
				$userHours->selectAdd("date");
				$userHours->groupBy("date");
		}
		$userHours->orderBy('date');

		if ($stat == "eventHours") {
			$dataSeries['Event Hours'] = [
				'borderColor' => 'rgba(255, 99, 132, 1)',
				'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
				'data' => [],
			];
			$userHours->selectAdd('SUM(length) / 60 AS sumHours');
		}

		$userHours->find();

		while ($userHours->fetch()) {
			switch ($timeframe) {
				case "weeks":
					$curPeriod = "{$userHours->week}-{$userHours->year}";
					break;
				case "months":
					$curPeriod = "{$userHours->month}-{$userHours->year}";
					break;
				case "years":
					$curPeriod = "{$userHours->year}";
					break;
				case "days":
				default: // Default to hours per day
					$curPeriod = "{$userHours->date}";
			}

			$columnLabels[] = $curPeriod;

			if ($stat == 'eventHours') {
				/** @noinspection PhpUndefinedFieldInspection */
				$dataSeries['Event Hours']['data'][$curPeriod] = $userHours->sumHours;
			}
		}

		$interface->assign('columnLabels', $columnLabels);
		$interface->assign('dataSeries', $dataSeries);
		$interface->assign('translateDataSeries', true);
		$interface->assign('translateColumnLabels', false);
	}

	private function assignGraphSpecificTitle($stat, $timeframe, $eventType, $location, $sublocation, $query, $fields, $fromDate, $toDate) {
		global $interface;
		$title = $interface->getVariable('graphTitle');
		$title .= " by " . ucfirst(substr($timeframe, 0, -1));
		if (!empty($eventType) || !empty($location) || !empty($sublocation) || !empty($query) || !empty($fields)) {
			$title .= " - ";
			if (!empty($eventType)) {
				$eventTypes = EventType::getEventTypeList(true);
				$title .= "Event Type: " . $eventTypes[$eventType] . ", ";
			}
			if (!empty($location)) {
				$locations = Location::getLocationList(!UserAccount::userHasPermission('View Event Reports for All Libraries') || UserAccount::userHasPermission('View Event Reports for Home Library'));
				$title .= "Location: " . $locations[$location] . ", ";
			}
			if (!empty($sublocation)) {
				$sublocations = Location::getEventSublocations($location);
				$title .= "Sublocation: " . $sublocations[$sublocation] . ", ";
			}
			if (!empty($fields)) {
				$fieldData = EventField::getEventFieldsByTypes([2, 3]);
				foreach ($fields as $key => $value) {
					if ($fieldData[substr($key, -1)]->type == 2) {
						$optionName = "true";
					} else {
						$values = explode("\n", $fieldData[substr($key, -1)]->allowableValues);
						$optionName = $values[$value];
					}
					$title .= $fieldData[substr($key, -1)]->name . ": " . $optionName . ", ";
				}
			}
			if (!empty($query)) {
				$title .= "Search Term: '" . $query . "', ";
			}
			if (!empty($fromDate) || !empty($toDate)) {
				$title .= "Date Range:  " . ($fromDate ?? 'earliest event') . " - " . ($toDate ?? 'latest event') . ", ";
			}
			$title = substr($title, 0, -2);
		}
		$interface->assign('graphTitle', $title);

	}

}