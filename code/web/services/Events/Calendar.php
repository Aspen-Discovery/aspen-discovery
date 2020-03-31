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
		$paddedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
		$monthFilter = $year . '-' . $paddedMonth;
		$calendarStart = "$paddedMonth/1/$year";
		$calendarStartDay = new DateTime($calendarStart);
		$formattedMonthYear = $calendarStartDay->format("M Y");
		$interface->assign('calendarMonth', $formattedMonthYear);

		$prevMonth = $month - 1;
		$prevYear = $year;
		if ($prevMonth == 0) {
			$prevMonth = 12;
			$prevYear--;
		}
		$prevLink = "/Events/Calendar?month=$prevMonth&year=$prevYear";
		$interface->assign('prevLink', $prevLink);

		$nextMonth = $month + 1;
		$nextYear = $year;
		if ($nextMonth == 13) {
			$nextMonth = 1;
			$nextYear++;
		}
		$nextLink = "/Events/Calendar?month=$nextMonth&year=$nextYear";
		$interface->assign('nextLink', $nextLink);


		// Initialise from the current search globals
		/** @var SearchObject_EventsSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Events');
		$searchObject->init();
		$searchObject->setPrimarySearch(false);
		$searchObject->setLimit(1000);
		$searchObject->addHiddenFilter("event_month", $monthFilter);

		$timer->logTime('Setup Search');

		// Process Search
		$result = $searchObject->processSearch(true, true);
		if ($result instanceof AspenError) {
			/** @var AspenError $result */
			AspenError::raiseError($result->getMessage());
		}
		$timer->logTime('Process Search');

		// Some more variables
		//   Those we can construct AFTER the search is executed, but we need
		//   no matter whether there were any results
		$interface->assign('lookfor', $searchObject->displayQuery());
		$interface->assign('searchType', $searchObject->getSearchType());
		// Will assign null for an advanced search
		$interface->assign('searchIndex', $searchObject->getSearchIndex());

		// 'Finish' the search... complete timers and log search history.
		$searchObject->close();

		$searchResults = $searchObject->getResultRecordSet();

		//Setup the calendar display
		//Get a list of weeks for the month
		$weeks = [];
		$dayNum = 1;
		$maxDay = cal_days_in_month ( CAL_GREGORIAN, $month , $year);
		for ($i = 0; $i < 5; $i++){
			$week = [
				'days' => []
			];
			//TODO: Offset if start of week is not a Sunday
			for ($j = 0; $j < 7; $j++){
				$eventDayObj = [
					'day' => $dayNum,
					'events' => []
				];
				$eventDay = $year . '-' . $paddedMonth . '-' . str_pad($dayNum, 2, '0', STR_PAD_LEFT);
				//Loop through search results to find events for this day
				foreach ($searchResults as $result) {
					if (in_array($eventDay, $result['event_day'])){
						$eventDayObj['events'][] = [
							'id' => $result['id'],
							'title' => $result['title'],
							'link' => $result['url']
						];
					}
				}
				$week['days'][] = $eventDayObj;

				$dayNum++;
				if ($dayNum > $maxDay) {
					break;
				}
			}
			$weeks[] = $week;
			if ($dayNum > $maxDay) {
				break;
			}
		}
		$interface->assign('weeks', $weeks);

		$this->display('calendar.tpl', 'Events Calendar ' . $formattedMonthYear, 'Search/results-sidebar.tpl');
	}
}