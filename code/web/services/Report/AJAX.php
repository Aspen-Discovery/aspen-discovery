<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once ROOT_DIR . '/AJAXHandler.php';

class Report_AJAX extends AJAXHandler {

	protected $methodsThatRespondWithJSONUnstructured = array(
		'getActiveSessions',
		'getRecentActivity',
	);

	/**
	 * Active sessions are any sessions where the last request happened less than 1 minute ago.
	 */
	function getActiveSessions(){
		global $analytics;

		$analyticsSession = $analytics->getSessionFilters();
		if ($analyticsSession == null){
			$analyticsSession = new Analytics_Session();
		}

		$analyticsSession->whereAdd('lastRequestTime >= ' . (time() - 60));
		$analyticsSession->find();
		return array('activeSessionCount' => $analyticsSession->N);
	}

	/**
	 * Recent activity includes users, searches done, events, and page views
	 */
	function getRecentActivity(){
		global $analytics;

		$interval         = isset($_REQUEST['interval']) ? $_REQUEST['interval'] : 10;
		$curTime          = time();
		$activityByMinute = array();

		$analyticsSession = $analytics->getSessionFilters();
		if ($analyticsSession == null){
			$analyticsSession = new Analytics_Session();
		}
		$analyticsSession->selectAdd('count(id) as numActiveUsers');
		$analyticsSession->whereAdd('lastRequestTime > ' . ($curTime - $interval));
		//$analyticsSession->whereAdd("lastRequestTime <= $curTime");
		if ($analyticsSession->find(true)){
			$activityByMinute['activeUsers'] = $analyticsSession->numActiveUsers;
		}else{
			$activityByMinute['activeUsers'] = 0;
		}

		$pageView = new Analytics_PageView();
		$pageView->selectAdd('count(id) as numPageViews');
		$pageView->whereAdd("pageEndTime > " . ($curTime - $interval));
		//$pageView->whereAdd("pageEndTime <= $curTime");
		if ($pageView->find(true)){
			$activityByMinute['pageViews'] = $pageView->numPageViews;
		}else{
			$activityByMinute['pageViews'] = 0;
		}

		$searches = new Analytics_Search();
		$searches->selectAdd('count(id) as numSearches');
		$searches->whereAdd("searchTime > " . ($curTime - $interval));
		//$searches->whereAdd("searchTime <= $curTime");
		if ($searches->find(true)){
			$activityByMinute['searches'] = $searches->numSearches;
		}else{
			$activityByMinute['searches'] = 0;
		}

		$events = new Analytics_Event();
		$events->selectAdd('count(id) as numEvents');
		$events->whereAdd("eventTime > " . ($curTime - $interval));
		//$events->whereAdd("eventTime <= $curTime");
		if ($events->find(true)){
			$activityByMinute['events'] = $events->numEvents;
		}else{
			$activityByMinute['events'] = 0;
		}

		return $activityByMinute;
	}


}
