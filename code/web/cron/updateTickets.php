<?php
require_once __DIR__ . '/../bootstrap.php';

//Update all tickets based on status
require_once ROOT_DIR . '/sys/Support/TicketStatusFeed.php';
require_once ROOT_DIR . '/sys/Support/Ticket.php';

$openTicketsFound = [];
$ticketStatusFeeds = new TicketStatusFeed();
$ticketStatusFeeds->find();
while ($ticketStatusFeeds->fetch()){
	$ticketsInFeed = getTicketInfoFromFeed($ticketStatusFeeds->rssFeed);
	foreach ($ticketsInFeed as $ticketInfo) {
		$ticket = getTicket($ticketInfo);
		$ticket->status = $ticketStatusFeeds->name;
		$ticket->update();
		$openTicketsFound[$ticket->ticketId] = $ticket->ticketId;
	}
}
//There are too many closed tickets to get an RSS feed, we need to just mark anything closed we don't see.
$ticket = new Ticket();
$ticket->whereAdd("status <> 'Closed'");
$ticket->find();
while ($ticket->fetch()){
	if (!in_array($ticket->ticketId, $openTicketsFound)){
		$ticket->status = 'Closed';
		$ticket->dateClosed = time();
		$ticket->update();
	}
}

//Update all tickets based on their queues
require_once ROOT_DIR . '/sys/Support/TicketQueueFeed.php';
$ticketQueueFeeds = new TicketQueueFeed();
$ticketQueueFeeds->find();
while ($ticketQueueFeeds->fetch()){
	$ticketsInFeed = getTicketInfoFromFeed($ticketQueueFeeds->rssFeed);
	foreach ($ticketsInFeed as $ticketInfo) {
		$ticket = getTicket($ticketInfo);
		$ticket->queue = $ticketQueueFeeds->name;
		$ticket->update();
	}
}

//Update all tickets based on their severity
require_once ROOT_DIR . '/sys/Support/TicketSeverityFeed.php';
$ticketSeverityFeeds = new TicketSeverityFeed();
$ticketSeverityFeeds->find();
while ($ticketSeverityFeeds->fetch()){
	$ticketsInFeed = getTicketInfoFromFeed($ticketSeverityFeeds->rssFeed);
	foreach ($ticketsInFeed as $ticketInfo) {
		$ticket = getTicket($ticketInfo);
		$ticket->severity = $ticketSeverityFeeds->name;
		$ticket->update();
	}
}

//Update all tickets based on assigned component


//Update all tickets from partner feeds


//Update stats for today
//require_once ROOT_DIR . '/sys/Support/TicketStats.php';
//$ticketStats = new TicketStats();
//$ticketStats->year = date('Y');
//$ticketStats->month = date('n');
//$ticketStats->day = date('d');


die;

function getTicketInfoFromFeed($feedUrl) : array{
	$rssDataRaw = @file_get_contents($feedUrl);
	if ($rssDataRaw == false){
		echo("Could not load data from $feedUrl \r\n");
		return [];
	}else {
		$rssData = new SimpleXMLElement($rssDataRaw);
		$ns = $rssData->getNamespaces(true);
		$activeTickets = [];
		if (!empty($rssData->item)) {
			foreach ($rssData->item as $item) {
				$matches = [];
				preg_match('/.*id=(\d+)/', $item->link, $matches);
				$dcData = $item->children($ns['dc']);
				$activeTickets[$matches[1]] = [
					'id' => $matches[1],
					'title' => (string)$item->title,
					'description' => (string)$item->description,
					'link' => (string)$item->link,
					'dateCreated' => (string)$dcData->date,
				];
			}
		}
		return $activeTickets;
	}
}

function getTicket($ticketInfo) : Ticket {
	$ticket = new Ticket();
	$ticket->ticketId = $ticketInfo['id'];
	if ($ticket->find(true)){
		return $ticket;
	}else{
		$ticket = new Ticket();
		$ticket->ticketId = $ticketInfo['id'];
		$ticket->title = $ticketInfo['title'];
		$ticket->description = $ticketInfo['description'];
		$ticket->displayUrl = $ticketInfo['link'];
		$ticket->dateCreated = strtotime($ticketInfo['dateCreated']);
		$ticket->insert();
		return $ticket;
	}
}