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

//Whether or not to load historic data, this is off by default, but can be used to populate the DB as best possible.
$loadHistoricDataForTicketsByQueue = false;
$loadHistoricDataForBugsBySeverity = false;
$loadHistoricDataForTicketsByPartner = true;

//Tickets by Queue
if ($loadHistoricDataForTicketsByQueue) {
	//Clear old data
	$ticketStat = new TicketTrendByQueue();
	$ticketStat->delete(true);

	$oldestTicket = new Ticket();
	$oldestTicket->orderBy('dateCreated asc');
	$oldestTicket->limit(0, 1);
	if ($oldestTicket->find(true)){
		$startDate = date('Y-m-d', $oldestTicket->dateCreated);
		$startDate = strtotime($startDate);
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
			}
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
	}
}

// Bugs by severity
if ($loadHistoricDataForBugsBySeverity) {
	//Clear old data
	$ticketStat = new TicketTrendBugsBySeverity();
	$ticketStat->delete(true);

	$severitiesToLoad = [];
	$severitiesToLoad[] = null;
	$ticketSeverity = new TicketSeverityFeed();
	$ticketSeverity->find();
	while ($ticketSeverity->fetch()) {
		$severitiesToLoad[] = $ticketSeverity->name;
	}

	$oldestTicket = new Ticket();
	$oldestTicket->queue = 'Bugs';
	$oldestTicket->orderBy('dateCreated asc');
	$oldestTicket->limit(0, 1);
	if ($oldestTicket->find(true)){
		$startDate = date('Y-m-d', $oldestTicket->dateCreated);
		$startDate = strtotime($startDate);
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
			}
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
	}
}
//Tickets by Partner
if ($loadHistoricDataForTicketsByPartner) {
	//Clear old data
	$ticketStat = new TicketTrendByPartner();
	$ticketStat->delete(true);

	$aspenSite = new AspenSite();
	$aspenSite->siteType = "0";
	$aspenSite->whereAdd('implementationStatus <> 0 AND implementationStatus <> 4');
	$aspenSite->find();
	$partners = [];
	$partners[] = null;
	while ($aspenSite->fetch()){
		$partners[] = $aspenSite->id;
	}
	$oldestTicket = new Ticket();
	$oldestTicket->orderBy('dateCreated asc');
	$oldestTicket->limit(0, 1);
	if ($oldestTicket->find(true)){
		$startDate = date('Y-m-d', $oldestTicket->dateCreated);
		$startDate = strtotime($startDate);
		$endDate = time();
		for ($tmpDate = $startDate; $tmpDate < $endDate; $tmpDate += 24 * 60 * 60) {
			$nextDay = $tmpDate + 24 * 60 * 60;
			foreach ($partners as $partner) {
				//Open tickets
				$ticketQuery = new Ticket();
				if ($partner == null) {
					$ticketQuery->whereAdd('requestingPartner IS NULL');
				} else {
					$ticketQuery->requestingPartner = $partner;
				}
				$ticketQuery->whereAdd("status <> 'Closed'");
				$ticketQuery->whereAdd("dateCreated <= $tmpDate");
				$numTickets = $ticketQuery->count();
				$ticketStat = new TicketTrendByPartner();
				$ticketStat->year = date('Y', $tmpDate);
				$ticketStat->month = date('n', $tmpDate);
				$ticketStat->day = date('j', $tmpDate);
				if ($partner == null) {
					$ticketStat->requestingPartner = null;
				} else {
					$ticketStat->requestingPartner = $partner;
				}
				if ($ticketStat->find(true)) {
					$ticketStat->count = $numTickets;
					$ticketStat->update();
				} else {
					$ticketStat->count = $numTickets;
					$ticketStat->insert();
				}

				//Closed tickets
				$ticketQuery = new Ticket();
				if ($partner == null) {
					$ticketQuery->whereAdd('requestingPartner IS NULL');
				} else {
					$ticketQuery->requestingPartner = $partner;
				}
				$ticketQuery->whereAdd("status = 'Closed'");
				$ticketQuery->whereAdd("dateCreated <= $tmpDate");
				$ticketQuery->whereAdd("dateClosed >= $nextDay");
				$numTickets = $ticketQuery->count();
				$ticketStat = new TicketTrendByPartner();
				$ticketStat->year = date('Y', $tmpDate);
				$ticketStat->month = date('n', $tmpDate);
				$ticketStat->day = date('j', $tmpDate);
				if ($partner == null) {
					$ticketStat->requestingPartner = null;
				} else {
					$ticketStat->requestingPartner = $partner;
				}
				if ($ticketStat->find(true)) {
					$ticketStat->count = $numTickets;
					$ticketStat->update();
				} else {
					$ticketStat->count = $numTickets;
					$ticketStat->insert();
				}
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
		$ticketQueues = new TicketQueueFeed();
		$ticketQueues->find();
		while ($ticketQueues->fetch()) {
			$ticketStat = new TicketTrendByPartner();
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
		}
	}
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