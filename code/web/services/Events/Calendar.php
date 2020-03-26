<?php


class Events_Calendar extends Action
{
	function launch()
	{
		global $interface;
		global $timer;

		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';

		$today = new DateTime();
		if (isset($_REQUEST['month'])) {
			$month = $_REQUEST['month'];
		}else{
			$month = $today->format('m');
		}
		if (isset($_REQUEST['year'])) {
			$year = $_REQUEST['year'];
		}else{
			$year = $today->format('Y');
		}
		$monthFilter = $year . '-' . $month;

		// Initialise from the current search globals
		/** @var SearchObject_EventsSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Events');
		$searchObject->init();
		$searchObject->setPrimarySearch(false);
		$searchObject->setLimit(1000);
		$searchObject->addFilter("event_month", $monthFilter);

		$timer->logTime('Setup Search');
	}
}