<?php
require_once __DIR__ . '/../bootstrap.php';

//Update all tickets based on status
require_once ROOT_DIR . '/sys/Support/Ticket.php';
require_once ROOT_DIR . '/sys/Support/TicketStatusFeed.php';
require_once ROOT_DIR . '/sys/Support/TicketComponentFeed.php';
require_once ROOT_DIR . '/sys/Support/TicketQueueFeed.php';
require_once ROOT_DIR . '/sys/Support/TicketSeverityFeed.php';
require_once ROOT_DIR . '/sys/Support/TicketTrendBugsBySeverity.php';
require_once ROOT_DIR . '/sys/Support/TicketTrendByPartner.php';
require_once ROOT_DIR . '/sys/Support/TicketTrendByQueue.php';
require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';

//Whether to load historic data, this is off by default, but can be used to populate the DB as best possible.
$ticketStat = new TicketTrendByQueue();
$numExistingStats = $ticketStat->count();
$loadHistoricDataForTicketsByQueue = $numExistingStats == 0;
$ticketStat = null;

$ticketStat = new TicketTrendBugsBySeverity();
$numExistingStats = $ticketStat->count();
$loadHistoricDataForBugsBySeverity = $numExistingStats == 0;
$ticketStat = null;

$ticketStat = new TicketTrendByPartner();
$numExistingStats = $ticketStat->count();
$loadHistoricDataForTicketsByPartner = $numExistingStats == 0;
$ticketStat = null;

//Tickets by Queue
if ($loadHistoricDataForTicketsByQueue) {
	//Clear old data
	$ticketStat = new TicketTrendByQueue();
	$ticketStat->delete(true);
	$ticketStat = null;

	//Start with Mark's first Day at ByWater when tickets started getting entered
	$startDate = strtotime('2020-10-29');
	$endDate = time();
	for ($tmpDate = $startDate; $tmpDate < $endDate; $tmpDate += 24 * 60 * 60) {
		$nextDay = $tmpDate + 24 * 60 * 60;
		$ticketQueues = new TicketQueueFeed();
		$ticketQueues->find();
		while ($ticketQueues->fetch()) {
			//Open tickets
			$ticketQuery = new Ticket();
			$ticketQuery->queue = $ticketQueues->name;
			$ticketQuery->whereAdd("status <> 'Closed'");
			$ticketQuery->whereAdd("dateCreated <= $tmpDate");
			$numTickets = $ticketQuery->count();
			$ticketStat = new TicketTrendByQueue();
			$ticketStat->year = date('Y', $tmpDate);
			$ticketStat->month = date('n', $tmpDate);
			$ticketStat->day = date('j', $tmpDate);
			$ticketStat->queue = $ticketQueues->name;

			if ($ticketStat->find(true)) {
				$ticketStat->count = $numTickets;
				$ticketStat->update();
			} else {
				$ticketStat->count = $numTickets;
				$ticketStat->insert();
			}
			$ticketStat = null;
			$ticketQuery = null;

			//Closed tickets
			$ticketQuery = new Ticket();
			$ticketQuery->queue = $ticketQueues->name;
			$ticketQuery->whereAdd("status = 'Closed'");
			$ticketQuery->whereAdd("dateCreated <= $tmpDate");
			$ticketQuery->whereAdd("dateClosed >= $nextDay");
			$numTickets = $ticketQuery->count();
			$ticketStat = new TicketTrendByQueue();
			$ticketStat->year = date('Y', $tmpDate);
			$ticketStat->month = date('n', $tmpDate);
			$ticketStat->day = date('j', $tmpDate);
			$ticketStat->queue = $ticketQueues->name;

			if ($ticketStat->find(true)) {
				$ticketStat->count = $ticketStat->count + $numTickets;
				$ticketStat->update();
			} else {
				$ticketStat->count = $numTickets;
				$ticketStat->insert();
			}
			$ticketStat = null;
			$ticketQuery = null;
		}
	}
} else {
	$ticketQueues = new TicketQueueFeed();
	$ticketQueues->find();
	while ($ticketQueues->fetch()) {
		//Only updating current values from today
		// Query tickets for today to generate stats
		$ticketQuery = new Ticket();
		$ticketQuery->whereAdd("status <> 'Closed'");
		$ticketQuery->queue = $ticketQueues->name;
		$numTickets = $ticketQuery->count();

		$ticketStat = new TicketTrendByQueue();
		$ticketStat->year = date('Y');
		$ticketStat->month = date('n');
		$ticketStat->day = date('j');
		$ticketStat->queue = $ticketQueues->name;

		if ($ticketStat->find(true)) {
			$ticketStat->count = $numTickets;
			$ticketStat->update();
		} else {
			$ticketStat->count = $numTickets;
			$ticketStat->insert();
		}
		$ticketStat = null;
		$ticketQuery = null;
	}
	$ticketQueues = null;
}

// Bugs by severity
if ($loadHistoricDataForBugsBySeverity) {
	//Clear old data
	$ticketStat = new TicketTrendBugsBySeverity();
	$ticketStat->delete(true);
	$ticketStat = null;

	$severitiesToLoad = [];
	$severitiesToLoad[] = null;
	$ticketSeverity = new TicketSeverityFeed();
	$ticketSeverity->find();
	while ($ticketSeverity->fetch()) {
		$severitiesToLoad[] = $ticketSeverity->name;
	}
	$ticketSeverity = null;

	//Start with Mark's first Day at ByWater when tickets started getting entered
	$startDate = strtotime('2020-10-29');
	$endDate = time();
	for ($tmpDate = $startDate; $tmpDate < $endDate; $tmpDate += 24 * 60 * 60) {
		$nextDay = $tmpDate + 24 * 60 * 60;
		foreach ($severitiesToLoad as $severity) {
			//Open tickets
			$ticketQuery = new Ticket();
			$ticketQuery->queue = 'Bugs';
			if ($severity == null) {
				$ticketQuery->whereAdd('severity IS NULL');
			} else {
				$ticketQuery->severity = $severity;
			}
			$ticketQuery->whereAdd("status <> 'Closed'");
			$ticketQuery->whereAdd("dateCreated <= $tmpDate");
			$numTickets = $ticketQuery->count();
			$ticketStat = new TicketTrendBugsBySeverity();
			$ticketStat->year = date('Y', $tmpDate);
			$ticketStat->month = date('n', $tmpDate);
			$ticketStat->day = date('j', $tmpDate);
			if ($severity == null) {
				$ticketStat->severity = 'Not Set';
			} else {
				$ticketStat->severity = $severity;
			}

			if ($ticketStat->find(true)) {
				$ticketStat->count = $numTickets;
				$ticketStat->update();
			} else {
				$ticketStat->count = $numTickets;
				$ticketStat->insert();
			}
			$ticketStat = null;
			$ticketQuery = null;

			//Closed tickets
			$ticketQuery = new Ticket();
			$ticketQuery->queue = 'Bugs';
			if ($severity == null) {
				$ticketQuery->whereAdd('severity IS NULL');
			} else {
				$ticketQuery->severity = $severity;
			}
			$ticketQuery->whereAdd("status = 'Closed'");
			$ticketQuery->whereAdd("dateCreated <= $tmpDate");
			$ticketQuery->whereAdd("dateClosed >= $nextDay");
			$numTickets = $ticketQuery->count();
			$ticketStat = new TicketTrendBugsBySeverity();
			$ticketStat->year = date('Y', $tmpDate);
			$ticketStat->month = date('n', $tmpDate);
			$ticketStat->day = date('j', $tmpDate);
			if ($severity == null) {
				$ticketStat->severity = 'Not Set';
			} else {
				$ticketStat->severity = $severity;
			}

			if ($ticketStat->find(true)) {
				$ticketStat->count = $ticketStat->count + $numTickets;
				$ticketStat->update();
			} else {
				$ticketStat->count = $numTickets;
				$ticketStat->insert();
			}
			$ticketStat = null;
			$ticketQuery = null;
		}
	}
}else {
	$ticketSeverity = new TicketSeverityFeed();
	$ticketSeverity->find();
	while ($ticketSeverity->fetch()) {
		// Query tickets for today to generate stats
		$ticketQuery = new Ticket();
		$ticketQuery->queue = 'Bugs';
		$ticketQuery->whereAdd("status <> 'Closed'");
		$ticketQuery->severity = $ticketSeverity->name;
		$numTickets = $ticketQuery->count();

		$ticketStat = new TicketTrendBugsBySeverity();
		$ticketStat->year = date('Y');
		$ticketStat->month = date('n');
		$ticketStat->day = date('j');
		$ticketStat->severity = $ticketSeverity->name;

		if ($ticketStat->find(true)) {
			$ticketStat->count = $numTickets;
			$ticketStat->update();
		} else {
			$ticketStat->count = $numTickets;
			$ticketStat->insert();
		}
		$ticketQuery = null;
		$ticketStat = null;
	}
	$ticketSeverity = null;
}
//Tickets by Partner
if ($loadHistoricDataForTicketsByPartner) {
	//Clear old data
	$ticketStat = new TicketTrendByPartner();
	$ticketStat->delete(true);
	$ticketStat = null;

	$aspenSite = new AspenSite();
	$aspenSite->siteType = "0";
	$aspenSite->whereAdd('implementationStatus <> 0 AND implementationStatus <> 4');
	$aspenSite->find();
	$partners = [];
	$partners[] = null;
	while ($aspenSite->fetch()){
		$partners[] = $aspenSite->id;
	}
	$aspenSite = null;

	//Start with Mark's first Day at ByWater when tickets started getting entered
	$startDate = strtotime('2020-10-29');
	$endDate = time();
	for ($tmpDate = $startDate; $tmpDate < $endDate; $tmpDate += 24 * 60 * 60) {
		$nextDay = $tmpDate + 24 * 60 * 60;

		//Open tickets
		$ticketQuery = new Ticket();
		$ticketQuery->whereAdd("status <> 'Closed'");
		$ticketQuery->whereAdd("dateCreated <= $tmpDate");
		$ticketQuery->groupBy('requestingPartner');
		$ticketQuery->selectAdd();
		$ticketQuery->selectAdd('count(*) as numTickets');
		$ticketQuery->selectAdd('requestingPartner');
		$ticketQuery->find();
		$partnersFound = [];
		while ($ticketQuery->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$numTickets = $ticketQuery->numTickets;
			$ticketStat = new TicketTrendByPartner();
			$ticketStat->year = date('Y', $tmpDate);
			$ticketStat->month = date('n', $tmpDate);
			$ticketStat->day = date('j', $tmpDate);
			$ticketStat->requestingPartner = $ticketQuery->requestingPartner;
			if ($ticketStat->find(true)) {
				$ticketStat->count = $numTickets;
				$ticketStat->update();
			} else {
				$ticketStat->count = $numTickets;
				$ticketStat->insert();
			}
			$partnersFound[$ticketQuery->requestingPartner] = $ticketQuery->requestingPartner;
		}
		$ticketStat = null;
		$ticketQuery = null;

		//Closed tickets
		$ticketQuery = new Ticket();
		$ticketQuery->whereAdd("status = 'Closed'");
		$ticketQuery->whereAdd("dateCreated <= $tmpDate");
		$ticketQuery->whereAdd("dateClosed >= $nextDay");
		$ticketQuery->groupBy('requestingPartner');
		$ticketQuery->selectAdd();
		$ticketQuery->selectAdd('count(*) as numTickets');
		$ticketQuery->selectAdd('requestingPartner');
		$ticketQuery->find();
		while ($ticketQuery->fetch()) {
			/** @noinspection PhpUndefinedFieldInspection */
			$numTickets = $ticketQuery->numTickets;
			$ticketStat = new TicketTrendByPartner();
			$ticketStat->year = date('Y', $tmpDate);
			$ticketStat->month = date('n', $tmpDate);
			$ticketStat->day = date('j', $tmpDate);
			$ticketStat->requestingPartner = $ticketQuery->requestingPartner;
			if ($ticketStat->find(true)) {
				$ticketStat->count = $numTickets;
				$ticketStat->update();
			} else {
				$ticketStat->count = $numTickets;
				$ticketStat->insert();
			}
			$partnersFound[$ticketQuery->requestingPartner] = $ticketQuery->requestingPartner;
		}
		$ticketStat = null;
		$ticketQuery = null;

		//Set 0's for this day for any partners that were not found:
		foreach ($partners as $partnerId) {
			if (!in_array($partnerId, $partnersFound)) {
				$ticketStat = new TicketTrendByPartner();
				$ticketStat->year = date('Y', $tmpDate);
				$ticketStat->month = date('n', $tmpDate);
				$ticketStat->day = date('j', $tmpDate);
				$ticketStat->requestingPartner = $partnerId;
				if (!$ticketStat->find(true)) {
					$ticketStat->count = 0;
					$ticketStat->insert();
				}
				$ticketStat = null;
			}
		}
	}
} else {
	require_once ROOT_DIR . '/sys/Greenhouse/AspenSite.php';
	$aspenSite = new AspenSite();
	$aspenSite->siteType = "0";
	$aspenSite->whereAdd('implementationStatus <> 0 AND implementationStatus <> 4');
	$aspenSite->find();
	while ($aspenSite->fetch()) {
		$ticketQuery = new Ticket();
		$ticketQuery->requestingPartner = $aspenSite->id;
		$ticketQuery->whereAdd("status <> 'Closed'");
		$numTickets = $ticketQuery->count();

		$ticketStat = new TicketTrendByPartner();
		$ticketStat->year = date('Y');
		$ticketStat->month = date('n');
		$ticketStat->day = date('j');
		$ticketStat->requestingPartner = $aspenSite->id;

		if ($ticketStat->find(true)) {
			$ticketStat->count = $numTickets;
			$ticketStat->update();
		} else {
			$ticketStat->count = $numTickets;
			$ticketStat->insert();
		}
		$ticketStat = null;
		$ticketQuery = null;
	}
	$aspenSite = null;
}


//$ticketQueues = new TicketQueueFeed();
//$ticketQueues->find();
//while ($ticketQueues->fetch()) {
//	// Loop through status
//	$ticketStatus = new TicketStatusFeed();
//	$ticketStatus->find();
//	while ($ticketStatus->fetch()) {
//		// Loop through severity
//		$ticketSeverity = new TicketSeverityFeed();
//		$ticketSeverity->find();
//		while ($ticketSeverity->fetch()) {
//			// Query tickets for today to generate stats
//			$ticketQuery = new Ticket();
//			$ticketQuery->queue = $ticketQueues->name;
//			$ticketQuery->status = $ticketStatus->name;
//			$ticketQuery->severity = $ticketSeverity->name;
//			$numTickets = $ticketQuery->count();
//
//			$ticketStat = new TicketStats();
//			$ticketStat->year = date('Y');
//			$ticketStat->month = date('n');
//			$ticketStat->day = date('j');
//			$ticketStat->queue = $ticketQueues->name;
//			$ticketStat->status = $ticketStatus->name;
//			$ticketStat->severity = $ticketSeverity->name;
//
//			if ($ticketStat->find(true)) {
//				$ticketStat->count = $ticketQuery->count();
//				$ticketStat->update();
//			} else {
//				$ticketStat->count = $ticketQuery->count();
//				$ticketStat->insert();
//			}
//
//			// Loop through Components
//
//
//		}
//	}
//}