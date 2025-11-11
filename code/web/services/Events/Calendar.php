<?php


class Events_Calendar extends Action {
	function launch() : void {
		global $interface;
		global $timer;

		$calendarDisplaySettingId = $this->getCalendarDisplaySettingId();
		$eventFieldsToShow = [];
		if (!empty($calendarDisplaySettingId)) {
			$eventFieldsToShow = $this->getEventFieldsToShow($calendarDisplaySettingId);
		}
		// Include Search Engine Class
		require_once ROOT_DIR . '/sys/SolrConnector/Solr.php';

		$today = new DateTime();
		$useWeek = 0;
		$week = 0;
		$month = 0;
		$paddedMonth = 0;
		$lastDayInMonth = 0;
		$formattedWeekYear = '';
		$formattedMonthYear = '';
		if (!empty($_REQUEST['week'])) {
			$week = $_REQUEST['week'];
			$interface->assign("weekNumber", $week);
			$interface->assign("monthNumber", '');
			$useWeek = 1;
		} else if (!empty($_REQUEST['month'])) {
			$month = $_REQUEST['month'];
			$interface->assign("monthNumber", $month);
			$interface->assign("weekNumber", '');
		} else {
			$month = $today->format('m');
		}
		if (!empty($_REQUEST['year'])) {
			$year = $_REQUEST['year'];
		} else {
			$year = $today->format('Y');
		}
		$interface->assign("yearNumber", $year);
		$interface->assign("useWeek", $useWeek);
		//Print settings
		$printEndTime = isset($_REQUEST['endTime']) ? filter_var($_REQUEST['endTime'], FILTER_VALIDATE_BOOLEAN) : false;
		$interface->assign("printEndTime", $printEndTime);
		if ($useWeek) {
			$paddedWeek = str_pad($week, 2, '0', STR_PAD_LEFT);
			$weekFilter = $year . '-' . $paddedWeek;
			$calendarStart = "{$year}W$paddedWeek";
			$calendarStartDay = strtotime($calendarStart . " - 1 days"); // So that the week starts on Sunday
			$formattedWeekYear = date("M j, Y", $calendarStartDay) . " - " . date("M j, Y", strtotime($calendarStart . "+ 5 days"));
			$month = date("n", strtotime($calendarStart));
			$interface->assign('calendarMonth', $formattedWeekYear);
			$monthLink = "/Events/Calendar?month=$month&year=$year";
			$interface->assign("monthLink", $monthLink);

			$prevWeek = $week - 1;
			$prevYear = $year;
			$lastWeekLastYear = date('W', strtotime('December 28th ' . ($year - 1)));
			if ($prevWeek == 0) {
				$prevWeek = $lastWeekLastYear;
				$prevYear--;
			}
			$prevLink = "/Events/Calendar?week=$prevWeek&year=$prevYear";
			$interface->assign('prevLink', $prevLink);

			$nextWeek = $week + 1;
			$nextYear = $year;
			$lastWeekThisYear = date('W', strtotime('December 28th ' . $year));
			if ($nextWeek > $lastWeekThisYear) {
				$nextWeek = 1;
				$nextYear++;
			}
			$nextLink = "/Events/Calendar?week=$nextWeek&year=$nextYear";
		} else {
			$paddedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
			$monthFilter = $year . '-' . $paddedMonth;
			$calendarStart = "$paddedMonth/1/$year";
			$calendarStartDay = new DateTime($calendarStart);
			$formattedMonthYear = $calendarStartDay->format("M Y");
			$week = (int)$calendarStartDay->format("W") + 1;
			$interface->assign('calendarMonth', $formattedMonthYear);
			$weekLink = "/Events/Calendar?week=$week&year=$year";
			$interface->assign("weekLink", $weekLink);

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
		}
		$interface->assign('nextLink', $nextLink);


		// Initialise from the current search globals
		/** @var SearchObject_EventsSearcher $searchObject */
		$searchObject = SearchObjectFactory::initSearchObject('Events');
		$searchObject->init();
		$searchObject->setPrimarySearch(false);
		$searchObject->setLimit(1000);
		//We have a default hidden filter to only show events after today, needs to be cleared for calendars.
		$searchObject->clearHiddenFilters();
		//Instead we limit to just this month.
		if ($useWeek) {
			$searchObject->addHiddenFilter("event_week", '"' . $weekFilter . '"');
		} else {
			$searchObject->addHiddenFilter("event_month", '"' . $monthFilter . '"');
		}
		// Check permissions before showing private events
		if (!UserAccount::userHasPermission('View Private Events for All Locations')) {
			if (!UserAccount::userHasPermission([
				'View Private Events for Home Library Locations',
				'View Private Events for Home Location'
			])) {
				$searchObject->addHiddenFilter('-private', "private");
			} else {
				if (!UserAccount::userHasPermission('View Private Events for Home Library Locations')) {
					$user = UserAccount::getLoggedInUser();
					$locations = array_values($user->getAdditionalAdministrationLocations());
					$locations[] = $user->getHomeLocationName();
					$searchObject->addHiddenFilter('private', '("' . implode('" OR "private_', $locations) . '" OR "public")');
				} else {
					$locations = array_values(Location::getLocationList(true));
					$searchObject->addHiddenFilter('private', '("private_' . implode('" OR "private_', $locations) . '" OR "public")');
				}
			}
		}
		$searchObject->setSort('start_date_sort');

		$timer->logTime('Setup Search');

		// Process Search
		$searchResult = $searchObject->processSearch(true, true);
		if ($searchResult instanceof AspenError) {
			AspenError::raiseError($searchResult->getMessage());
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

		$defaultTimezone = new DateTimeZone(date_default_timezone_get());

		//Set up the calendar display
		//Get a list of weeks for the month
		$weeks = [];
		if ($useWeek) {
			$month = date("n", $calendarStartDay);
			$paddedMonth = date("m", $calendarStartDay);
			$dayNum = date("j", $calendarStartDay);
			$lastDayInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$maxDay = date("d", strtotime($calendarStart . " + 6 days"));
		} else {
			$dayNum = 1;
			/** @noinspection PhpSuspiciousNameCombinationInspection */
			$maxDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		}
		for ($i = 0; $i < 5; $i++) {
			$week = [
				'days' => [],
			];

			$startDayIndex = 0;
			if ($i == 0) {
				if (!$useWeek) {
					$startDayIndex = $calendarStartDay->format('N');
				}
				for ($j = 0; $j < $startDayIndex; $j++) {
					$week['days'][] = [
						'day' => '',
						'fullDate' => '',
						'events' => [],
					];
				}
			}
			for ($j = $startDayIndex; $j < 7; $j++) {
				$eventDay = $year . '-' . $paddedMonth . '-' . str_pad($dayNum, 2, '0', STR_PAD_LEFT);
				$eventDate = new DateTime($eventDay);

				$eventDayObj = [
					'day' => $dayNum,
					'fullDate' => $eventDate->format('l, F jS'),
					'events' => [],
				];

				//Loop through search results to find events for this day
				foreach ($searchResults as $result) {
					if (in_array($eventDay, $result['event_day'])) {
						$startDate = new DateTime($result['start_date']);
						$startDate->setTimezone($defaultTimezone);
						if ($printEndTime) {
							// Save space by leaving off AM/PM from start time
							$formattedTime = date_format($startDate, "h:i");
						} else {
							$formattedTime = date_format($startDate, "h:iA");
						}
						$endDate = new DateTime($result['end_date']);
						$endDate->setTimezone($defaultTimezone);
						$formattedTime .= '<span class="end-time"> - ' . date_format($endDate, "h:iA") . "</span>";
						if (($endDate->getTimestamp() - $startDate->getTimestamp()) > 24 * 60 * 60) {
							$formattedTime = 'All day';
						}
						$isCancelled = false;
						if (array_key_exists('reservation_state', $result) && in_array('Cancelled', $result['reservation_state'])) {
							$isCancelled = true;
						}
						$url = "";
						$eventFields = [];
						if (str_starts_with($result['id'], 'communico')){
							$url = '/Communico/' . $result['id'] . '/Event';
						}
						elseif (str_starts_with($result['id'], 'libcal')){
							$url = '/Springshare/' . $result['id'] . '/Event';
						}
						elseif (str_starts_with($result['id'], 'lc_')){
							$url = '/LibraryMarket/' . $result['id'] . '/Event';
						}
						elseif (str_starts_with($result['id'], 'assabet')){
							$url = '/Assabet/' . $result['id'] . '/Event';
						}
						elseif (str_starts_with($result['id'], 'aspen')){
							$url = '/AspenEvents/' . $result['id'] . '/Event';
						}

						if (!empty($eventFieldsToShow)) {
							foreach ($eventFieldsToShow as $fieldToShow) {
								$fieldName = str_replace(" ", "_", $fieldToShow->getSolrFieldName());
								if (in_array($fieldName, ['branch', 'room', 'description'])) {
									//If the event does not have the field set, ignore it
									if (!empty($result[$fieldName])) {
										$eventFields[$fieldName] = [
											'settings' => $fieldToShow,
											'value' => $result[$fieldName]
										];
									}
								}else{
									$pattern = '/custom_([a-z]+)_'.$fieldName.'/i';
									$keys = array_keys($result);
									$matches = preg_grep($pattern, $keys);
									if (!empty($matches)) {
										foreach($matches as $match) {
											$eventFields[$match] = [
												'settings' => $fieldToShow,
												'value' => $result[$match]
											];
										}
									}
								}
							}
						}

						$eventDayObj['events'][] = [
							'id' => $result['id'],
							'title' => $result['title'],
							'link' => $url,
							'formattedTime' => $formattedTime,
							'isCancelled' => $isCancelled,
							'eventFields' => $eventFields,
						];
					}
				}
				$week['days'][] = $eventDayObj;

				$dayNum++;
				if ($useWeek) {
					if ($dayNum > $lastDayInMonth) {
						$dayNum = 1;
						$paddedMonth = str_pad($month + 1, 2, '0', STR_PAD_LEFT);
					}
					if ($paddedMonth == 13) {
						$paddedMonth = "01";
					}
				} else if ($dayNum > $maxDay) {
					break;
				}
			}
			$weeks[] = $week;
			if ($useWeek) {
				break;
			} else if ($dayNum > $maxDay) {
				break;
			}
		}
		$interface->assign('weeks', $weeks);

		$headerImage = $this->getHeaderImage($calendarDisplaySettingId);
		$interface->assign('headerImage', $headerImage['image'] ?? '');
		$interface->assign('headerAlt', $headerImage['altText'] ?? '');

		if ($useWeek) {
			$this->display('calendar.tpl', 'Events Calendar ' . $formattedWeekYear, '');
		} else {
			$this->display('calendar.tpl', 'Events Calendar ' . $formattedMonthYear, '');
		}
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Admin/Home', 'Administration Home');
		$breadcrumbs[] = new Breadcrumb('/Admin/Home#events', 'Events');
		$breadcrumbs[] = new Breadcrumb('/Events/Calendar', 'Events Calendar');
		return $breadcrumbs;
	}

	function getHeaderImage(int $calendarDisplaySettingId = 0) : array {
		require_once ROOT_DIR . '/sys/Events/CalendarDisplaySetting.php';
		$setting = new CalendarDisplaySetting();
		$setting->id = $calendarDisplaySettingId;
		$headerImage = [];
		if ($setting->find(true)) {
			$headerImage["image"] =  !empty($setting->cover) ? "/files/original/" . $setting->cover : '';
			$headerImage["altText"] =  $setting->altText ?? '';
		}
		return $headerImage;
	}

	function getCalendarDisplaySettingId() : int {
		global $library;
		$calendarDisplaySettingId = 0;
		require_once ROOT_DIR . '/sys/Events/CalendarDisplaySettingLibrary.php';
		$setting = new CalendarDisplaySettingLibrary();
		$setting->libraryId = $library->libraryId;
		if ($setting->find(true)) {
			$calendarDisplaySettingId = $setting->calendarDisplaySettingId;
		}
		return $calendarDisplaySettingId;
	}

	function getEventFieldsToShow($calendarDisplaySettingId) : array {
		require_once ROOT_DIR . '/sys/Events/EventFieldCalendarOptions.php';
		require_once ROOT_DIR . '/sys/Events/EventField.php';
		$eventFieldOptions = new EventFieldCalendarOptions();
		$eventFieldOptions->calendarDisplaySettingId = $calendarDisplaySettingId;
		$eventFieldOptions->orderBy('weight');
		$allEventFieldOptions = $eventFieldOptions->fetchAll();
		//Apply user selections
		foreach ($allEventFieldOptions as $eventFieldOption) {
			if (isset($_REQUEST['calendar_' . $eventFieldOption->eventFieldId])) {
				$eventFieldOption->printedCalendar = filter_var($_REQUEST['calendar_' . $eventFieldOption->eventFieldId], FILTER_VALIDATE_BOOLEAN);
			}
			if (isset($_REQUEST['agenda_' . $eventFieldOption->eventFieldId])) {
				$eventFieldOption->printedAgenda = filter_var($_REQUEST['agenda_' . $eventFieldOption->eventFieldId], FILTER_VALIDATE_BOOLEAN);
			}
		}
		return $allEventFieldOptions;
	}

}